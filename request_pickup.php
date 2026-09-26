<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_login();
if ($_SESSION['role'] !== 'resident') { redirect('admin/dashboard.php'); }

$errors = [];
$old = ['waste_category' => '', 'estimated_quantity' => '', 'preferred_date' => ''];
$validCategories = ['Bulky Items', 'Electronic Waste', 'Garden Waste', 'Construction Debris', 'Other'];

// Handle editing mode
$editRequest = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $editId = (int) $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM pickup_requests WHERE request_id = :id AND user_id = :uid AND status = 'Pending'");
    $stmt->execute(['id' => $editId, 'uid' => $_SESSION['user_id']]);
    $editRequest = $stmt->fetch();
    if ($editRequest) {
        $old = [
            'waste_category' => $editRequest['waste_category'],
            'estimated_quantity' => $editRequest['estimated_quantity'],
            'preferred_date' => $editRequest['preferred_date']
        ];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Your session expired. Please try again.';
    }

    $action = $_POST['action'] ?? 'create';

    // Handle delete action
    if ($action === 'delete') {
        $requestId = (int) ($_POST['request_id'] ?? 0);
        $deleteReason = clean($_POST['delete_reason'] ?? '');
        
        if (!in_array($deleteReason, ['resolved', 'mistake', 'changed_mind'], true)) {
            $errors[] = 'Please select a valid reason for deletion.';
        } else {
            $stmt = $pdo->prepare("DELETE FROM pickup_requests WHERE request_id = :id AND user_id = :uid AND status = 'Pending'");
            $stmt->execute(['id' => $requestId, 'uid' => $_SESSION['user_id']]);
            
            $reasonText = ['resolved' => 'resolved elsewhere', 'mistake' => 'submitted by mistake', 'changed_mind' => 'no longer needed'];
            set_flash('success', 'Pickup request deleted (' . $reasonText[$deleteReason] . ').');
            redirect('request_pickup.php');
        }
    }

    // Handle create/edit action
    if ($action === 'create' || $action === 'edit') {
        $old['waste_category']       = clean($_POST['waste_category'] ?? '');
        $old['estimated_quantity']   = clean($_POST['estimated_quantity'] ?? '');
        $old['preferred_date']       = clean($_POST['preferred_date'] ?? '');

        if (!in_array($old['waste_category'], $validCategories, true)) {
            $errors[] = 'Please select a valid waste category.';
        }
        $today = date('Y-m-d');
        if ($old['preferred_date'] === '' || $old['preferred_date'] < $today) {
            $errors[] = 'Please choose a valid future date.';
        }

        if (empty($errors)) {
            if ($action === 'edit' && $editRequest) {
                // Update existing request
                $stmt = $pdo->prepare(
                    'UPDATE pickup_requests SET waste_category = :cat, estimated_quantity = :qty, preferred_date = :date 
                     WHERE request_id = :id AND user_id = :uid AND status = "Pending"'
                );
                $stmt->execute([
                    'cat'  => $old['waste_category'],
                    'qty'  => $old['estimated_quantity'],
                    'date' => $old['preferred_date'],
                    'id'   => $editRequest['request_id'],
                    'uid'  => $_SESSION['user_id']
                ]);
                set_flash('success', 'Pickup request updated.');
                redirect('request_pickup.php');
            } else {
                // Create new request (existing logic)
                // Duplicate detection: same user + same category + still pending within last 14 days
                $stmt = $pdo->prepare(
                    "SELECT request_id, preferred_date, created_at FROM pickup_requests
                     WHERE user_id = :uid
                       AND waste_category = :cat
                       AND status IN ('Pending', 'Scheduled')
                       AND created_at >= NOW() - INTERVAL 14 DAY
                     ORDER BY created_at DESC
                     LIMIT 1"
                );
                $stmt->execute([
                    'uid' => $_SESSION['user_id'],
                    'cat' => $old['waste_category'],
                ]);
                $dupPickup = $stmt->fetch();

                $userConfirmedDifferent = !empty($_POST['confirmed_different']);

                if (empty($dupPickup) || $userConfirmedDifferent) {
                    $stmt = $pdo->prepare(
                        'INSERT INTO pickup_requests (user_id, zone_id, waste_category, estimated_quantity, preferred_date, status)
                         VALUES (:uid, :zid, :cat, :qty, :date, "Pending")'
                    );
                    $stmt->execute([
                        'uid'  => $_SESSION['user_id'],
                        'zid'  => $_SESSION['zone_id'],
                        'cat'  => $old['waste_category'],
                        'qty'  => $old['estimated_quantity'],
                        'date' => $old['preferred_date'],
                    ]);
                    set_flash('success', 'Pickup request submitted. The team will confirm scheduling soon.');
                    redirect('request_pickup.php');
                }
            }
        }
    }
}

$stmt = $pdo->prepare('SELECT request_id, waste_category, estimated_quantity, preferred_date, status, created_at
                        FROM pickup_requests WHERE user_id = :uid ORDER BY created_at DESC');
$stmt->execute(['uid' => $_SESSION['user_id']]);
$myRequests = $stmt->fetchAll();

$pageTitle = 'Request Pickup';
require __DIR__ . '/includes/header.php';
?>

<div class="page-head">
    <div>
        <h1>Request a special pickup</h1>
        <p>For bulky items, e-waste, garden or construction waste that doesn't fit the regular route.</p>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-error">
        <?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="grid grid-2" style="align-items:start;">
    <form class="form-card" method="post" id="pickupForm" novalidate
        <?php if (!empty($dupPickup) && !$editRequest): ?>
            data-duplicate-warning="You already have a '<?= e($old['waste_category']) ?>' pickup request submitted on <?= date('d M Y', strtotime($dupPickup['created_at'])) ?> (Request #<?= (int)$dupPickup['request_id'] ?>) that is still pending. Click OK to submit another one anyway."
        <?php endif; ?>>
        <h3 style="margin-top:0;"><?= $editRequest ? 'Edit pickup request' : 'Request a pickup' ?></h3>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="<?= $editRequest ? 'edit' : 'create' ?>">
        <input type="hidden" name="confirmed_different" id="pickup_confirmed" value="">

        <label for="waste_category">Waste category</label>
        <select id="waste_category" name="waste_category">
            <option value="">-- Select category --</option>
            <?php foreach ($validCategories as $cat): ?>
                <option value="<?= e($cat) ?>" <?= $old['waste_category'] === $cat ? 'selected' : '' ?>><?= e($cat) ?></option>
            <?php endforeach; ?>
        </select>

        <label for="estimated_quantity">Estimated quantity / description</label>
        <input type="text" id="estimated_quantity" name="estimated_quantity"
               placeholder="e.g. 2 chairs and a mattress" value="<?= e($old['estimated_quantity']) ?>">

        <label for="preferred_date">Preferred date</label>
        <input type="date" id="preferred_date" name="preferred_date"
               min="<?= date('Y-m-d', strtotime('+1 day')) ?>" value="<?= e($old['preferred_date']) ?>">

        <button type="submit" class="btn"><?= $editRequest ? 'Update request' : 'Submit request' ?></button>
        <?php if ($editRequest): ?>
            <a href="request_pickup.php" class="btn btn-secondary">Cancel</a>
        <?php endif; ?>
    </form>

    <div class="card">
        <h3>Your pickup requests</h3>
        <?php if (empty($myRequests)): ?>
            <p>No pickup requests yet.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Category</th><th>Preferred date</th><th>Status</th><th style="width: 200px;">Actions</th></tr></thead>
                    <tbody>
                    <?php foreach ($myRequests as $r): ?>
                        <?php $cls = $r['status'] === 'Completed' ? 'badge-resolved' : ($r['status'] === 'Scheduled' ? 'badge-progress' : 'badge-pending'); ?>
                        <tr>
                            <td><?= e($r['waste_category']) ?><br><span class="help-text"><?= e($r['estimated_quantity']) ?></span></td>
                            <td><?= date('d M Y', strtotime($r['preferred_date'])) ?></td>
                            <td><span class="badge <?= $cls ?>"><?= e($r['status']) ?></span></td>
                            <td style="white-space: nowrap; width: 200px;">
                                <?php if ($r['status'] === 'Pending'): ?>
                                    <a href="request_pickup.php?edit=<?= (int) $r['request_id'] ?>" 
                                       class="btn btn-secondary btn-sm" style="margin-right: 6px;">Edit</a><!--
                                    --><form method="post" style="display: inline-block;">
                                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="request_id" value="<?= (int) $r['request_id'] ?>">
                                        <select name="delete_reason" style="font-size: 0.75rem; padding: 3px; margin-right: 4px; display: none; width: 80px;" required>
                                            <option value="">Reason</option>
                                            <option value="resolved">Resolved</option>
                                            <option value="mistake">Mistake</option>
                                            <option value="changed_mind">Changed mind</option>
                                        </select>
                                        <button type="button" class="btn btn-secondary btn-sm delete-btn">Delete</button>
                                        <button type="submit" class="btn btn-secondary btn-sm confirm-delete-btn" style="display: none; margin-left: 4px;">Confirm</button>
                                    </form>
                                <?php else: ?>
                                    <span class="help-text">No actions</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
