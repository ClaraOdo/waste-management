<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_login();
if ($_SESSION['role'] !== 'resident') { redirect('admin/dashboard.php'); }

$zoneFilter = $_GET['zone_id'] ?? '';

$zones = $pdo->query('SELECT zone_id, zone_name FROM zones ORDER BY zone_name')->fetchAll();

$sql = "SELECT cs.zone_id, z.zone_name, cs.day_of_week, cs.collection_time, cs.waste_type, cs.notes
        FROM collection_schedules cs
        JOIN zones z ON z.zone_id = cs.zone_id";
$params = [];
if ($zoneFilter !== '' && ctype_digit($zoneFilter)) {
    $sql .= " WHERE cs.zone_id = :zid";
    $params['zid'] = $zoneFilter;
}
$sql .= " ORDER BY z.zone_name, FIELD(cs.day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday')";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$schedules = $stmt->fetchAll();

$pageTitle = 'Collection Schedule';
require __DIR__ . '/includes/header.php';
?>

<div class="page-head">
    <div>
        <h1>Collection schedule</h1>
        <p>Weekly collection days and waste types across all zones.</p>
    </div>
</div>

<form class="filter-bar" method="get">
    <div class="field">
        <label for="zone_id">Filter by zone</label>
        <select id="zone_id" name="zone_id" onchange="this.form.submit()">
            <option value="">All zones</option>
            <?php foreach ($zones as $zone): ?>
                <option value="<?= (int) $zone['zone_id'] ?>" <?= $zoneFilter == $zone['zone_id'] ? 'selected' : '' ?>>
                    <?= e($zone['zone_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
</form>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Zone</th><th>Day</th><th>Time</th><th>Waste type</th><th>Notes</th></tr></thead>
            <tbody>
            <?php foreach ($schedules as $s): ?>
                <tr>
                    <td><?= e($s['zone_name']) ?></td>
                    <td><?= e($s['day_of_week']) ?></td>
                    <td><?= date('g:i A', strtotime($s['collection_time'])) ?></td>
                    <td><?= e($s['waste_type']) ?></td>
                    <td><?= e($s['notes']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($schedules)): ?>
                <tr><td colspan="5">No schedule entries found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
