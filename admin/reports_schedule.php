<?php
$basePath = '../';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');

$zones = $pdo->query('SELECT zone_id, zone_name, zone_description FROM zones ORDER BY zone_name')->fetchAll();

$stmt = $pdo->query("SELECT zone_id, day_of_week, collection_time, waste_type, notes
                      FROM collection_schedules
                      ORDER BY zone_id, FIELD(day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday')");
$allSchedules = $stmt->fetchAll();

$scheduleByZone = [];
foreach ($allSchedules as $s) {
    $scheduleByZone[$s['zone_id']][] = $s;
}

$pageTitle = 'Schedule Report';
require __DIR__ . '/../includes/header.php';
?>

<div class="page-head">
    <div>
        <h1>Report 3 &mdash; Zone collection schedule</h1>
        <p>Weekly collection routes grouped by zone, for route planning and publication.</p>
    </div>
    <div>
        <a href="reports_summary.php" class="btn btn-secondary btn-sm">&larr; Report 1: Summary</a>
        <a href="reports_filtered.php" class="btn btn-secondary btn-sm">Report 2: Filtered list</a>
    </div>
</div>

<?php foreach ($zones as $zone): ?>
    <div class="card" style="margin-bottom:18px;">
        <h3><?= e($zone['zone_name']) ?></h3>
        <p class="help-text" style="margin-top:-6px;"><?= e($zone['zone_description']) ?></p>
        <?php $rows = $scheduleByZone[$zone['zone_id']] ?? []; ?>
        <?php if (empty($rows)): ?>
            <p>No schedule published for this zone yet.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Day</th><th>Time</th><th>Waste type</th><th>Notes</th></tr></thead>
                    <tbody>
                    <?php foreach ($rows as $s): ?>
                        <tr>
                            <td><?= e($s['day_of_week']) ?></td>
                            <td><?= date('g:i A', strtotime($s['collection_time'])) ?></td>
                            <td><?= e($s['waste_type']) ?></td>
                            <td><?= e($s['notes']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
<?php endforeach; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
