<?php
$basePath = '../';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        set_flash('error', 'Your session expired. Please try again.');
        redirect('manage_pickups.php');
    }
    $requestId     = (int) ($_POST['request_id'] ?? 0);
    $status        = clean($_POST['status'] ?? '');
    $validStatuses = ['Pending', 'Scheduled', 'Completed'];

    if ($requestId > 0 && in_array($status, $validStatuses, true)) {
        $stmt = $pdo->prepare('UPDATE pickup_requests SET status = :status WHERE request_id = :id');
        $stmt->execute(['status' => $status, 'id' => $requestId]);
        set_flash('success', "Pickup request #{$requestId} updated.");
    }
    redirect('manage_pickups.php' . (!empty($_GET) ? '?' . http_build_query($_GET) : ''));
}

$statusFilter   = $_GET['status'] ?? '';
$zoneFilter     = $_GET['zone_id'] ?? '';
$categoryFilter = $_GET['waste_category'] ?? '';

$sql = "SELECT pr.*, u.full_name, u.phone, z.zone_name
        FROM pickup_requests pr
        JOIN users u ON u.user_id = pr.user_id
        JOIN zones z ON z.zone_id = pr.zone_id
        WHERE 1=1";
$params = [];

if ($statusFilter !== '') {
    $sql .= " AND pr.status = :status";
    $params['status'] = $statusFilter;
}
if ($zoneFilter !== '' && ctype_digit($zoneFilter)) {
    $sql .= " AND pr.zone_id = :zone_id";
    $params['zone_id'] = $zoneFilter;
}
if ($categoryFilter !== '') {
    $sql .= " AND pr.waste_category = :category";
    $params['category'] = $categoryFilter;
}
$sql .= " ORDER BY pr.preferred_date ASC, pr.created_at DESC";

$stmt     = $pdo->prepare($sql);
$stmt->execute($params);
$requests = $stmt->fetchAll();

$zones      = $pdo->query('SELECT zone_id, zone_name FROM zones ORDER BY zone_name')->fetchAll();
$categories = ['Bulky Items', 'Electronic Waste', 'Garden Waste', 'Construction Debris', 'Other'];

$pageTitle = 'Manage Pickups';
require __DIR__ . '/../includes/header.php';
?>

<div class="page-head">
    <div>
        <h1>Special pickup requests</h1>
        <p>Review resident pickup requests and update their scheduling status.</p>
    </div>
</div>

<form class="filter-bar" method="get">
    <div class="field">
        <label for="status">Status</label>
        <select id="status" name="status">
            <option value="">All</option>
            <?php foreach (['Pending', 'Scheduled', 'Completed'] as $s): ?>
                <option value="<?= e($s) ?>" <?= $statusFilter === $s ? 'selected' : '' ?>><?= e($s) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="field">
        <label for="zone_id">Zone</label>
        <select id="zone_id" name="zone_id">
            <option value="">All</option>
            <?php foreach ($zones as $z): ?>
                <option value="<?= (int) $z['zone_id'] ?>" <?= $zoneFilter == $z['zone_id'] ? 'selected' : '' ?>><?= e($z['zone_name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="field">
        <label for="waste_category">Category</label>
        <select id="waste_category" name="waste_category">
            <option value="">All</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= e($cat) ?>" <?= $categoryFilter === $cat ? 'selected' : '' ?>><?= e($cat) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="field">
        <button type="submit" class="btn btn-secondary btn-sm">Apply filters</button>
    </div>
</form>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>#</th><th>Resident</th><th>Zone</th><th>Category</th><th>Details</th><th>Preferred date</th><th>Status</th><th>Submitted</th></tr>
            </thead>
            <tbody>
            <?php foreach ($requests as $r): ?>
                <?php $cls = $r['status'] === 'Completed' ? 'badge-resolved' : ($r['status'] === 'Scheduled' ? 'badge-progress' : 'badge-pending'); ?>
                <tr>
                    <td>#<?= (int) $r['request_id'] ?></td>
                    <td><?= e($r['full_name']) ?><br><span class="help-text"><?= e($r['phone']) ?></span></td>
                    <td><?= e($r['zone_name']) ?></td>
                    <td><?= e($r['waste_category']) ?></td>
                    <td><?= e($r['estimated_quantity']) ?></td>
                    <td><?= date('d M Y', strtotime($r['preferred_date'])) ?></td>
                    <td>
                        <form method="post" style="min-width:140px;">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="request_id" value="<?= (int) $r['request_id'] ?>">
                            <select name="status" style="margin-bottom:6px;">
                                <?php foreach (['Pending', 'Scheduled', 'Completed'] as $s): ?>
                                    <option value="<?= e($s) ?>" <?= $r['status'] === $s ? 'selected' : '' ?>><?= e($s) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-sm">Update</button>
                        </form>
                    </td>
                    <td><?= date('d M Y', strtotime($r['created_at'])) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($requests)): ?>
                <tr><td colspan="8">No pickup requests match these filters.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
