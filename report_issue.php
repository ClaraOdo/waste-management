<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_login();
if ($_SESSION['role'] !== 'resident') { redirect('admin/dashboard.php'); }

$errors = [];
$old = ['report_type' => '', 'description' => '', 'location_details' => ''];

// Handle editing mode
$editReport = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $editId = (int) $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM reports WHERE report_id = :id AND user_id = :uid AND status = 'Pending'");
    $stmt->execute(['id' => $editId, 'uid' => $_SESSION['user_id']]);
    $editReport = $stmt->fetch();
    if ($editReport) {
        $old = [
            'report_type' => $editReport['report_type'],
            'description' => $editReport['description'],
            'location_details' => $editReport['location_details']
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
        $reportId = (int) ($_POST['report_id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM reports WHERE report_id = :id AND user_id = :uid AND status = 'Pending'");
        $stmt->execute(['id' => $reportId, 'uid' => $_SESSION['user_id']]);
        set_flash('success', 'Report deleted successfully.');
        redirect('report_issue.php');
    }

    // Handle create/edit action
    if ($action === 'create' || $action === 'edit') {
        $validTypes = ['Missed Collection', 'Illegal Dumping', 'Overflowing Bin', 'Other'];
        $old['report_type']       = clean($_POST['report_type'] ?? '');
        $old['description']       = clean($_POST['description'] ?? '');
        $old['location_details']  = clean($_POST['location_details'] ?? '');

        if (!in_array($old['report_type'], $validTypes, true)) {
            $errors[] = 'Please select a valid issue type.';
        }
        if (mb_strlen($old['description']) < 15) {
            $errors[] = 'Please describe the issue in at least 15 characters.';
        }

        if (empty($errors)) {
            if ($action === 'edit' && $editReport) {
                // Update existing report
                $stmt = $pdo->prepare(
                    'UPDATE reports SET report_type = :type, description = :desc, location_details = :loc 
                     WHERE report_id = :id AND user_id = :uid AND status = "Pending"'
                );
                $stmt->execute([
                    'type' => $old['report_type'],
                    'desc' => $old['description'],
                    'loc'  => $old['location_details'],
                    'id'   => $editReport['report_id'],
                    'uid'  => $_SESSION['user_id']
                ]);
                set_flash('success', 'Report updated successfully.');
                redirect('report_issue.php');
            } else {
                // Create new report - duplicate detection
                $stmt = $pdo->prepare(
                    "SELECT report_id, location_details, description, created_at FROM reports
                     WHERE zone_id = :zid
                       AND report_type = :type
                       AND status IN ('Pending', 'In Progress')
                       AND created_at >= NOW() - INTERVAL 7 DAY
                     ORDER BY created_at DESC
                     LIMIT 1"
                );
                $stmt->execute([
                    'zid'  => $_SESSION['zone_id'],
                    'type' => $old['report_type'],
                ]);
                $duplicate = $stmt->fetch();

                // Only insert if no duplicate OR user confirmed it's different
                $userConfirmedDifferent = !empty($_POST['confirmed_different']);

                if (empty($duplicate) || $userConfirmedDifferent) {
                    $stmt = $pdo->prepare(
                        'INSERT INTO reports (user_id, zone_id, report_type, description, location_details, status)
                         VALUES (:uid, :zid, :type, :desc, :loc, "Pending")'
                    );
                    $stmt->execute([
                        'uid'  => $_SESSION['user_id'],
                        'zid'  => $_SESSION['zone_id'],
                        'type' => $old['report_type'],
                        'desc' => $old['description'],
                        'loc'  => $old['location_details'],
                    ]);
                    set_flash('success', 'Your report has been submitted. Thank you for helping keep your zone clean.');
                    redirect('report_issue.php');
                }
            }
        }
    }
}

$stmt = $pdo->prepare('SELECT report_id, report_type, description, location_details, status, admin_notes, created_at
                        FROM reports WHERE user_id = :uid ORDER BY created_at DESC');
$stmt->execute(['uid' => $_SESSION['user_id']]);
$myReports = $stmt->fetchAll();

$pageTitle = 'Report an Issue';
require __DIR__ . '/includes/header.php';
?>

<div class="page-head">
    <div>
        <h1>Report an issue</h1>
        <p>Missed pickup, illegal dumping or an overflowing bin? Let the team know.</p>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-error">
        <?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="grid grid-2" style="align-items:start;">
    <form class="form-card" method="post" id="reportForm" novalidate
        <?php if (!empty($duplicate) && !$editReport): ?>
            data-duplicate-warning="A '<?= e($duplicate['report_type'] ?? $old['report_type']) ?>' report was already submitted for your zone on <?= date('d M Y', strtotime($duplicate['created_at'])) ?> (Report #<?= (int)$duplicate['report_id'] ?>) and is still open. If this is a different location, click OK to submit anyway."
        <?php endif; ?>>
        <h3 style="margin-top:0;"><?= $editReport ? 'Edit report' : 'Report an issue' ?></h3>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="<?= $editReport ? 'edit' : 'create' ?>">
        <input type="hidden" name="confirmed_different" id="confirmed_different" value="">
        
        <label for="report_type">Issue type</label>
        <select id="report_type" name="report_type">
            <option value="">-- Select type --</option>
            <?php foreach (['Missed Collection', 'Illegal Dumping', 'Overflowing Bin', 'Other'] as $type): ?>
                <option value="<?= e($type) ?>" <?= $old['report_type'] === $type ? 'selected' : '' ?>><?= e($type) ?></option>
            <?php endforeach; ?>
        </select>

        <label for="location_details">Location details</label>
        <input type="text" id="location_details" name="location_details"
               placeholder="e.g. Near the market gate" value="<?= e($old['location_details']) ?>">

        <label for="description">Description</label>
        <textarea id="description" name="description" placeholder="Describe what you saw..."><?= e($old['description']) ?></textarea>
        <div class="help-text" id="descriptionCounter">0 characters</div>

        <button type="submit" class="btn"><?= $editReport ? 'Update report' : 'Submit report' ?></button>
        <?php if ($editReport): ?>
            <a href="report_issue.php" class="btn btn-secondary">Cancel</a>
        <?php endif; ?>
    </form>

    <div class="card">
        <h3>Your report history</h3>
        <?php if (empty($myReports)): ?>
            <p>No reports submitted yet.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Type</th><th>Status</th><th>Date</th><th style="width: 160px;">Actions</th></tr></thead>
                    <tbody>
                    <?php foreach ($myReports as $r): ?>
                        <?php $cls = $r['status'] === 'Resolved' ? 'badge-resolved' : ($r['status'] === 'In Progress' ? 'badge-progress' : 'badge-pending'); ?>
                        <tr>
                            <td><?= e($r['report_type']) ?><br>
                                <span class="help-text"><?= e($r['location_details']) ?></span></td>
                            <td><span class="badge <?= $cls ?>"><?= e($r['status']) ?></span>
                                <?php if (!empty($r['admin_notes'])): ?>
                                    <div class="help-text">Note: <?= e($r['admin_notes']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td><?= date('d M Y', strtotime($r['created_at'])) ?></td>
                            <td style="white-space: nowrap; width: 160px;">
                                <?php if ($r['status'] === 'Pending'): ?>
                                    <a href="report_issue.php?edit=<?= (int) $r['report_id'] ?>" 
                                       class="btn btn-secondary btn-sm" style="margin-right: 6px;">Edit</a>
                                    <form method="post" style="display: inline-block;" 
                                          onsubmit="return confirm('Are you sure you want to delete this report?');">
                                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="report_id" value="<?= (int) $r['report_id'] ?>">
                                        <button type="submit" class="btn btn-secondary btn-sm">Delete</button>
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
