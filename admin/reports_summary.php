<?php
    $basePath = '../';
    require_once __DIR__ . '/../includes/db.php';
    require_once __DIR__ . '/../includes/functions.php';
    require_role('admin');

    // Report data, counts by status
    $stmt     = $pdo->query("SELECT status, COUNT(*) AS c FROM reports GROUP BY status");
    $byStatus = ['Pending' => 0, 'In Progress' => 0, 'Resolved' => 0];
    foreach ($stmt->fetchAll() as $row) {$byStatus[$row['status']] = (int) $row['c'];}
    $totalReports = array_sum($byStatus);

    // Report data, counts by issue type
    $stmt   = $pdo->query("SELECT report_type, COUNT(*) AS c FROM reports GROUP BY report_type ORDER BY c DESC");
    $byType = $stmt->fetchAll();

    // Report data, counts by zone
    $stmt = $pdo->query("SELECT z.zone_name, COUNT(r.report_id) AS c
                      FROM zones z LEFT JOIN reports r ON r.zone_id = z.zone_id
                      GROUP BY z.zone_id ORDER BY c DESC");
    $byZone = $stmt->fetchAll();

    // Report data, pickup requests by status
    $stmt           = $pdo->query("SELECT status, COUNT(*) AS c FROM pickup_requests GROUP BY status");
    $pickupByStatus = ['Pending' => 0, 'Scheduled' => 0, 'Completed' => 0];
    foreach ($stmt->fetchAll() as $row) {$pickupByStatus[$row['status']] = (int) $row['c'];}

    function bar($label, $count, $max)
    {
    $pct = $max > 0 ? round(($count / $max) * 100) : 0;
    echo '<div class="bar-row">';
    echo '<div class="bar-label">' . e($label) . '</div>';
    echo '<div class="bar-track"><div class="bar-fill" style="width:' . $pct . '%"></div></div>';
    echo '<div class="bar-count">' . (int) $count . '</div>';
    echo '</div>';
    }

    $pageTitle = 'Reports Summary';
    require __DIR__ . '/../includes/header.php';
?>

<div class="page-head">
    <div>
        <h1>Report 1 &mdash; Summary statistics</h1>
        <p>System-wide totals across issue reports and pickup requests.</p>
    </div>
    <div>
        <a href="reports_filtered.php" class="btn btn-secondary btn-sm">Report 2: Filtered list &rarr;</a>
        <a href="reports_schedule.php" class="btn btn-secondary btn-sm">Report 3: Schedule &rarr;</a>
    </div>
</div>

<div class="grid grid-3">
    <div class="stat-card"><div class="stat-value"><?php echo $totalReports ?></div><div class="stat-label">Total issue reports</div></div>
    <div class="stat-card"><div class="stat-value"><?php echo $byStatus['Resolved'] ?></div><div class="stat-label">Resolved</div></div>
    <div class="stat-card"><div class="stat-value"><?php echo $byStatus['Pending'] + $byStatus['In Progress'] ?></div><div class="stat-label">Still open</div></div>
</div>

<div class="grid grid-2" style="margin-top:24px; align-items:start;">
    <div class="card">
        <h3>Reports by status</h3>
        <div class="bar-chart">
            <?php $max = max($byStatus) ?: 1;foreach ($byStatus as $label => $count) {
                    bar($label, $count, $max);
                }
            ?>
        </div>
    </div>

    <div class="card">
        <h3>Reports by issue type</h3>
        <div class="bar-chart">
            <?php
                $max = 1;
                foreach ($byType as $row) {$max = max($max, (int) $row['c']);}
                foreach ($byType as $row) {bar($row['report_type'], $row['c'], $max);}
                if (empty($byType)) {
                    echo '<p>No data yet.</p>';
                }

            ?>
        </div>
    </div>
</div>

<div class="grid grid-2" style="margin-top:24px; align-items:start;">
    <div class="card">
        <h3>Reports by zone</h3>
        <div class="bar-chart">
            <?php
                $max = 1;
                foreach ($byZone as $row) {$max = max($max, (int) $row['c']);}
                foreach ($byZone as $row) {bar($row['zone_name'], $row['c'], $max);}
            ?>
        </div>
    </div>

    <div class="card">
        <h3>Pickup requests by status</h3>
        <div class="bar-chart">
            <?php $max = max($pickupByStatus) ?: 1;foreach ($pickupByStatus as $label => $count) {
                    bar($label, $count, $max);
                }
            ?>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
