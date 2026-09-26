<?php
    require_once __DIR__ . '/includes/db.php';
    require_once __DIR__ . '/includes/functions.php';
    require_login();
    if ($_SESSION['role'] !== 'resident') {redirect('admin/dashboard.php');}

    $userId = $_SESSION['user_id'];

    // My report counts
    $stmt = $pdo->prepare("SELECT status, COUNT(*) AS c FROM reports WHERE user_id = :uid GROUP BY status");
    $stmt->execute(['uid' => $userId]);
    $counts = ['Pending' => 0, 'In Progress' => 0, 'Resolved' => 0];
    foreach ($stmt->fetchAll() as $row) {$counts[$row['status']] = (int) $row['c'];}

    // Recent reports
    $stmt = $pdo->prepare("SELECT report_id, report_type, status, created_at FROM reports
                        WHERE user_id = :uid ORDER BY created_at DESC LIMIT 5");
    $stmt->execute(['uid' => $userId]);
    $recentReports = $stmt->fetchAll();

    // Next collection for my zone
    $stmt = $pdo->prepare("SELECT day_of_week, collection_time, waste_type FROM collection_schedules
                        WHERE zone_id = :zid ORDER BY FIELD(day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday')");
    $stmt->execute(['zid' => $_SESSION['zone_id']]);
    $mySchedule = $stmt->fetchAll();

    $nextCleaningDay = next_national_cleaning_day();

    $pageTitle = 'Dashboard';
    require __DIR__ . '/includes/header.php';
?>

<div class="page-head">
    <div>
        <h1>Welcome back, <?php echo e($_SESSION['full_name']) ?></h1>
        <p>Here's what's happening with your reports and your zone's collection schedule.</p>
    </div>
</div>

<?php if ($nextCleaningDay): ?>
<div class="alert alert-success" style="border-left:4px solid var(--ok);">
    <strong>Next National Cleaning Day:</strong> <?php echo e($nextCleaningDay->format('l, j F Y')) ?>,
    7:00-10:00am &mdash; the mandatory monthly community clean-up. Regular bin collection for
    your zone continues on the schedule below.
</div>
<?php endif; ?>

<div class="grid grid-3">
    <div class="stat-card">
        <div class="stat-value"><?php echo $counts['Pending'] ?></div>
        <div class="stat-label">Pending reports</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?php echo $counts['In Progress'] ?></div>
        <div class="stat-label">In progress</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?php echo $counts['Resolved'] ?></div>
        <div class="stat-label">Resolved</div>
    </div>
</div>

<div class="grid grid-2" style="margin-top:24px; align-items:start;">
    <div class="card">
        <h3>Your recent reports</h3>
        <?php if (empty($recentReports)): ?>
            <p>You haven't submitted any reports yet. <a href="report_issue.php">Report an issue &rarr;</a></p>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>#</th><th>Type</th><th>Status</th><th>Date</th></tr></thead>
                    <tbody>
                    <?php foreach ($recentReports as $r): ?>
                        <tr>
                            <td>#<?php echo (int) $r['report_id'] ?></td>
                            <td><?php echo e($r['report_type']) ?></td>
                            <td>
                                <?php
                                    $cls = $r['status'] === 'Resolved' ? 'badge-resolved' : ($r['status'] === 'In Progress' ? 'badge-progress' : 'badge-pending');
                                ?>
                                <span class="badge <?php echo $cls ?>"><?php echo e($r['status']) ?></span>
                            </td>
                            <td><?php echo date('d M Y', strtotime($r['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div class="card">
        <h3>Your zone's collection days</h3>
        <?php if (empty($mySchedule)): ?>
            <p>No schedule has been published for your zone yet.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Day</th><th>Time</th><th>Waste type</th></tr></thead>
                    <tbody>
                    <?php foreach ($mySchedule as $s): ?>
                        <tr>
                            <td><?php echo e($s['day_of_week']) ?></td>
                            <td><?php echo date('g:i A', strtotime($s['collection_time'])) ?></td>
                            <td><?php echo e($s['waste_type']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        <a href="schedule.php" class="btn btn-secondary btn-sm" style="margin-top:14px;">View full schedule</a>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>