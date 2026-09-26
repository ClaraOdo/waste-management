<?php
$basePath = '../';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');

$totalUsers   = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role='resident'")->fetchColumn();
$totalReports = (int) $pdo->query("SELECT COUNT(*) FROM reports")->fetchColumn();
$pendingCount = (int) $pdo->query("SELECT COUNT(*) FROM reports WHERE status='Pending'")->fetchColumn();
$pickupCount  = (int) $pdo->query("SELECT COUNT(*) FROM pickup_requests WHERE status='Pending'")->fetchColumn();

$stmt = $pdo->query("SELECT r.report_id, r.report_type, r.status, r.created_at, u.full_name, z.zone_name
                      FROM reports r
                      JOIN users u ON u.user_id = r.user_id
                      JOIN zones z ON z.zone_id = r.zone_id
                      ORDER BY r.created_at DESC LIMIT 6");
$latestReports = $stmt->fetchAll();

$pageTitle = 'Admin Dashboard';
require __DIR__ . '/../includes/header.php';
?>

<div class="page-head">
    <div>
        <h1>Admin overview</h1>
        <p>System-wide snapshot of registered residents, reports and pickup requests.</p>
    </div>
</div>

<div class="grid grid-3">
    <div class="stat-card">
        <div class="stat-value"><?= $totalUsers ?></div>
        <div class="stat-label">Registered residents</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= $totalReports ?></div>
        <div class="stat-label">Total issue reports</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= $pendingCount ?></div>
        <div class="stat-label">Reports awaiting action</div>
    </div>
</div>

<div class="grid grid-2" style="margin-top:24px; align-items:start;">
    <div class="card">
        <h3>Latest reports</h3>
        <?php if (empty($latestReports)): ?>
            <p>No reports submitted yet.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Resident</th><th>Zone</th><th>Type</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($latestReports as $r): ?>
                        <?php $cls = $r['status'] === 'Resolved' ? 'badge-resolved' : ($r['status'] === 'In Progress' ? 'badge-progress' : 'badge-pending'); ?>
                        <tr>
                            <td><?= e($r['full_name']) ?></td>
                            <td><?= e($r['zone_name']) ?></td>
                            <td><?= e($r['report_type']) ?></td>
                            <td><span class="badge <?= $cls ?>"><?= e($r['status']) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        <a href="manage_reports.php" class="btn btn-secondary btn-sm" style="margin-top:14px;">Manage all reports</a>
    </div>

    <div class="card">
        <h3>Quick actions</h3>
        <p>Pending special-pickup requests: <strong><?= $pickupCount ?></strong></p>
        <p style="margin-top:14px;">
            <a href="manage_pickups.php" class="btn btn-secondary btn-sm">Manage special pickups</a><br><br>
            <a href="manage_schedule.php" class="btn btn-secondary btn-sm">Manage collection schedule</a><br><br>
            <a href="reports_summary.php" class="btn btn-secondary btn-sm">View analytics &amp; reports</a>
        </p>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
