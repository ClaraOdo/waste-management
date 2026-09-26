<?php
    $basePath = '../';
    require_once __DIR__ . '/../includes/db.php';
    require_once __DIR__ . '/../includes/functions.php';
    require_role('admin');

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (! verify_csrf($_POST['csrf_token'] ?? '')) {
        set_flash('error', 'Your session expired. Please try again.');
        redirect('manage_reports.php');
    }
    $reportId      = (int) ($_POST['report_id'] ?? 0);
    $status        = clean($_POST['status'] ?? '');
    $notes         = clean($_POST['admin_notes'] ?? '');
    $validStatuses = ['Pending', 'In Progress', 'Resolved'];

    if ($reportId > 0 && in_array($status, $validStatuses, true)) {
        $resolvedAt = $status === 'Resolved' ? date('Y-m-d H:i:s') : null;
        $stmt       = $pdo->prepare(
            'UPDATE reports SET status = :status, admin_notes = :notes, resolved_at = :resolved_at
             WHERE report_id = :id'
        );
        $stmt->execute([
            'status'      => $status,
            'notes'       => $notes,
            'resolved_at' => $resolvedAt,
            'id'          => $reportId,
        ]);
        set_flash('success', "Report #{$reportId} updated.");
    }
    redirect('manage_reports.php' . (! empty($_GET) ? '?' . http_build_query($_GET) : ''));
    }

    // Filters
    $statusFilter = $_GET['status'] ?? '';
    $zoneFilter   = $_GET['zone_id'] ?? '';
    $typeFilter   = $_GET['report_type'] ?? '';

    $sql = "SELECT r.*, u.full_name, u.phone, z.zone_name
        FROM reports r
        JOIN users u ON u.user_id = r.user_id
        JOIN zones z ON z.zone_id = r.zone_id
        WHERE 1=1";
    $params = [];
    if ($statusFilter !== '') {$sql .= " AND r.status = :status";
    $params['status']                = $statusFilter;}
    if ($zoneFilter !== '' && ctype_digit($zoneFilter)) {$sql .= " AND r.zone_id = :zone_id";
    $params['zone_id']               = $zoneFilter;}
    if ($typeFilter !== '') {$sql .= " AND r.report_type = :type";
    $params['type']                  = $typeFilter;}
    $sql .= " ORDER BY r.created_at DESC";

    $stmt  = $pdo->prepare($sql);
    $stmt->execute($params);
    $reports  = $stmt->fetchAll();

    $zones = $pdo->query('SELECT zone_id, zone_name FROM zones ORDER BY zone_name')->fetchAll();

    $pageTitle = 'Manage Reports';
    require __DIR__ . '/../includes/header.php';
?>

<div class="page-head">
    <div>
        <h1>Issue reports</h1>
        <p>Review resident reports, update their status and leave a note for the resident.</p>
    </div>
</div>

<form class="filter-bar" method="get">
    <div class="field">
        <label for="status">Status</label>
        <select id="status" name="status">
            <option value="">All</option>
            <?php foreach (['Pending', 'In Progress', 'Resolved'] as $s): ?>
                <option value="<?php echo e($s) ?>" <?php echo $statusFilter === $s ? 'selected' : '' ?>><?php echo e($s) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="field">
        <label for="zone_id">Zone</label>
        <select id="zone_id" name="zone_id">
            <option value="">All</option>
            <?php foreach ($zones as $z): ?>
                <option value="<?php echo (int) $z['zone_id'] ?>" <?php echo $zoneFilter == $z['zone_id'] ? 'selected' : '' ?>><?php echo e($z['zone_name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="field">
        <label for="report_type">Type</label>
        <select id="report_type" name="report_type">
            <option value="">All</option>
            <?php foreach (['Missed Collection', 'Illegal Dumping', 'Overflowing Bin', 'Other'] as $t): ?>
                <option value="<?php echo e($t) ?>" <?php echo $typeFilter === $t ? 'selected' : '' ?>><?php echo e($t) ?></option>
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
                <tr><th>#</th><th>Resident</th><th>Zone</th><th>Type</th><th>Description</th><th>Status &amp; notes</th><th>Date</th></tr>
            </thead>
            <tbody>
            <?php foreach ($reports as $r): ?>
                <tr>
                    <td>#<?php echo (int) $r['report_id'] ?></td>
                    <td><?php echo e($r['full_name']) ?><br><span class="help-text"><?php echo e($r['phone']) ?></span></td>
                    <td><?php echo e($r['zone_name']) ?></td>
                    <td><?php echo e($r['report_type']) ?></td>
                    <td style="max-width:220px;"><?php echo e($r['description']) ?><br>
                        <span class="help-text"><?php echo e($r['location_details']) ?></span></td>
                    <td>
                        <form method="post" style="min-width:190px;">
                            <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()) ?>">
                            <input type="hidden" name="report_id" value="<?php echo (int) $r['report_id'] ?>">
                            <select name="status" style="margin-bottom:6px;">
                                <?php foreach (['Pending', 'In Progress', 'Resolved'] as $s): ?>
                                    <option value="<?php echo e($s) ?>" <?php echo $r['status'] === $s ? 'selected' : '' ?>><?php echo e($s) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <textarea name="admin_notes" placeholder="Note to resident" style="min-height:50px; margin-bottom:6px;"><?php echo e($r['admin_notes']) ?></textarea>
                            <button type="submit" class="btn btn-sm">Update</button>
                        </form>
                    </td>
                    <td><?php echo date('d M Y', strtotime($r['created_at'])) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($reports)): ?>
                <tr><td colspan="7">No reports match these filters.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
