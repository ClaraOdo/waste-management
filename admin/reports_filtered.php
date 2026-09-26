<?php
$basePath = '../';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');

$statusFilter = $_GET['status'] ?? '';
$zoneFilter   = $_GET['zone_id'] ?? '';
$fromDate     = $_GET['from_date'] ?? '';
$toDate       = $_GET['to_date'] ?? '';

$sql = "SELECT r.report_id, r.report_type, r.status, r.created_at, u.full_name, z.zone_name
        FROM reports r
        JOIN users u ON u.user_id = r.user_id
        JOIN zones z ON z.zone_id = r.zone_id
        WHERE 1=1";
$params = [];
if ($statusFilter !== '') { $sql .= " AND r.status = :status"; $params['status'] = $statusFilter; }
if ($zoneFilter !== '' && ctype_digit($zoneFilter)) { $sql .= " AND r.zone_id = :zone_id"; $params['zone_id'] = $zoneFilter; }
if ($fromDate !== '') { $sql .= " AND DATE(r.created_at) >= :from_date"; $params['from_date'] = $fromDate; }
if ($toDate !== '') { $sql .= " AND DATE(r.created_at) <= :to_date"; $params['to_date'] = $toDate; }
$sql .= " ORDER BY r.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$zones = $pdo->query('SELECT zone_id, zone_name FROM zones ORDER BY zone_name')->fetchAll();

$pageTitle = 'Filtered Report List';
require __DIR__ . '/../includes/header.php';
?>

<div class="page-head">
    <div>
        <h1>Report 2 &mdash; Filtered report list</h1>
        <p>Search issue reports by status, zone and date range for record-keeping or audits.</p>
    </div>
    <div>
        <a href="reports_summary.php" class="btn btn-secondary btn-sm">&larr; Report 1: Summary</a>
        <a href="reports_schedule.php" class="btn btn-secondary btn-sm">Report 3: Schedule &rarr;</a>
    </div>
</div>

<form class="filter-bar" method="get">
    <div class="field">
        <label for="status">Status</label>
        <select id="status" name="status">
            <option value="">All</option>
            <?php foreach (['Pending', 'In Progress', 'Resolved'] as $s): ?>
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
        <label for="from_date">From</label>
        <input type="date" id="from_date" name="from_date" value="<?= e($fromDate) ?>">
    </div>
    <div class="field">
        <label for="to_date">To</label>
        <input type="date" id="to_date" name="to_date" value="<?= e($toDate) ?>">
    </div>
    <div class="field">
        <button type="submit" class="btn btn-secondary btn-sm">Apply</button>
    </div>
</form>

<div class="card">
    <div class="page-head" style="border:none; margin-bottom:8px; padding-bottom:0;">
        <h3 style="margin:0;">Results (<?= count($rows) ?>)</h3>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>#</th><th>Date</th><th>Resident</th><th>Zone</th><th>Type</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <?php $cls = $r['status'] === 'Resolved' ? 'badge-resolved' : ($r['status'] === 'In Progress' ? 'badge-progress' : 'badge-pending'); ?>
                <tr>
                    <td>#<?= (int) $r['report_id'] ?></td>
                    <td><?= date('d M Y', strtotime($r['created_at'])) ?></td>
                    <td><?= e($r['full_name']) ?></td>
                    <td><?= e($r['zone_name']) ?></td>
                    <td><?= e($r['report_type']) ?></td>
                    <td><span class="badge <?= $cls ?>"><?= e($r['status']) ?></span></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($rows)): ?>
                <tr><td colspan="6">No reports match these filters.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
