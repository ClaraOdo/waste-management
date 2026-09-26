<?php
$basePath = '../';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        set_flash('error', 'Your session expired. Please try again.');
        redirect('manage_schedule.php');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'add_zone') {
        $name = clean($_POST['zone_name'] ?? '');
        $desc = clean($_POST['zone_description'] ?? '');
        if ($name === '') {
            $errors[] = 'Zone name is required.';
        } else {
            $stmt = $pdo->prepare('INSERT INTO zones (zone_name, zone_description) VALUES (:n, :d)');
            $stmt->execute(['n' => $name, 'd' => $desc]);
            set_flash('success', 'Zone added.');
            redirect('manage_schedule.php');
        }
    }

    if ($action === 'edit_zone') {
        $zoneId = (int) ($_POST['zone_id'] ?? 0);
        $name = clean($_POST['zone_name'] ?? '');
        $desc = clean($_POST['zone_description'] ?? '');
        if ($zoneId <= 0 || $name === '') {
            $errors[] = 'Zone name is required.';
        } else {
            $stmt = $pdo->prepare('UPDATE zones SET zone_name = :n, zone_description = :d WHERE zone_id = :id');
            $stmt->execute(['n' => $name, 'd' => $desc, 'id' => $zoneId]);
            set_flash('success', 'Zone updated.');
            redirect('manage_schedule.php');
        }
    }

    if ($action === 'add_schedule') {
        $zoneId = (int) ($_POST['zone_id'] ?? 0);
        $day    = clean($_POST['day_of_week'] ?? '');
        $time   = clean($_POST['collection_time'] ?? '');
        $type   = clean($_POST['waste_type'] ?? '');
        $notes  = clean($_POST['notes'] ?? '');
        $validDays = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
        $validTypes = ['General','Recyclable','Organic'];

        if ($zoneId <= 0 || !in_array($day, $validDays, true) || $time === '' || !in_array($type, $validTypes, true)) {
            $errors[] = 'Please complete all required schedule fields.';
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO collection_schedules (zone_id, day_of_week, collection_time, waste_type, notes)
                 VALUES (:zid, :day, :time, :type, :notes)'
            );
            $stmt->execute(['zid' => $zoneId, 'day' => $day, 'time' => $time, 'type' => $type, 'notes' => $notes]);
            set_flash('success', 'Schedule entry added.');
            redirect('manage_schedule.php');
        }
    }

    if ($action === 'edit_schedule') {
        $scheduleId = (int) ($_POST['schedule_id'] ?? 0);
        $zoneId = (int) ($_POST['zone_id'] ?? 0);
        $day    = clean($_POST['day_of_week'] ?? '');
        $time   = clean($_POST['collection_time'] ?? '');
        $type   = clean($_POST['waste_type'] ?? '');
        $notes  = clean($_POST['notes'] ?? '');
        $validDays = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
        $validTypes = ['General','Recyclable','Organic'];

        if ($scheduleId <= 0 || $zoneId <= 0 || !in_array($day, $validDays, true) || $time === '' || !in_array($type, $validTypes, true)) {
            $errors[] = 'Please complete all required schedule fields.';
        } else {
            $stmt = $pdo->prepare(
                'UPDATE collection_schedules 
                 SET zone_id = :zid, day_of_week = :day, collection_time = :time, waste_type = :type, notes = :notes
                 WHERE schedule_id = :id'
            );
            $stmt->execute(['zid' => $zoneId, 'day' => $day, 'time' => $time, 'type' => $type, 'notes' => $notes, 'id' => $scheduleId]);
            set_flash('success', 'Schedule entry updated.');
            redirect('manage_schedule.php');
        }
    }

    if ($action === 'delete_schedule') {
        $scheduleId = (int) ($_POST['schedule_id'] ?? 0);
        $stmt = $pdo->prepare('DELETE FROM collection_schedules WHERE schedule_id = :id');
        $stmt->execute(['id' => $scheduleId]);
        set_flash('success', 'Schedule entry removed.');
        redirect('manage_schedule.php');
    }
}

$zones = $pdo->query('SELECT zone_id, zone_name, zone_description FROM zones ORDER BY zone_name')->fetchAll();

// Handle editing mode
$editSchedule = null;
$editZone = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $editId = (int) $_GET['edit'];
    $stmt = $pdo->prepare("SELECT cs.*, z.zone_name 
                           FROM collection_schedules cs 
                           JOIN zones z ON z.zone_id = cs.zone_id 
                           WHERE cs.schedule_id = :id");
    $stmt->execute(['id' => $editId]);
    $editSchedule = $stmt->fetch();
}
if (isset($_GET['edit_zone']) && is_numeric($_GET['edit_zone'])) {
    $editZoneId = (int) $_GET['edit_zone'];
    $stmt = $pdo->prepare("SELECT * FROM zones WHERE zone_id = :id");
    $stmt->execute(['id' => $editZoneId]);
    $editZone = $stmt->fetch();
}

$stmt = $pdo->query("SELECT cs.schedule_id, cs.day_of_week, cs.collection_time, cs.waste_type, cs.notes, z.zone_name
                      FROM collection_schedules cs
                      JOIN zones z ON z.zone_id = cs.zone_id
                      ORDER BY z.zone_name, FIELD(cs.day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday')");
$schedules = $stmt->fetchAll();

$pageTitle = 'Manage Schedule';
require __DIR__ . '/../includes/header.php';
?>

<div class="page-head">
    <div>
        <h1>Zones &amp; collection schedule</h1>
        <p>Add collection zones and set the weekly pickup schedule for each one.</p>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-error"><?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?></div>
<?php endif; ?>

<div class="grid grid-2" style="align-items:start;">
    <form class="form-card" method="post">
        <h3 style="margin-top:0;"><?= $editSchedule ? 'Edit schedule entry' : 'Add a schedule entry' ?></h3>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="<?= $editSchedule ? 'edit_schedule' : 'add_schedule' ?>">
        <?php if ($editSchedule): ?>
            <input type="hidden" name="schedule_id" value="<?= (int) $editSchedule['schedule_id'] ?>">
        <?php endif; ?>

        <label for="zone_id">Zone</label>
        <select id="zone_id" name="zone_id" required>
            <option value="">-- Select zone --</option>
            <?php foreach ($zones as $z): ?>
                <option value="<?= (int) $z['zone_id'] ?>" <?= ($editSchedule && $editSchedule['zone_id'] == $z['zone_id']) ? 'selected' : '' ?>>
                    <?= e($z['zone_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="day_of_week">Day</label>
        <select id="day_of_week" name="day_of_week" required>
            <?php foreach (['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'] as $d): ?>
                <option value="<?= $d ?>" <?= ($editSchedule && $editSchedule['day_of_week'] === $d) ? 'selected' : '' ?>>
                    <?= $d ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="collection_time">Time</label>
        <input type="time" id="collection_time" name="collection_time" 
               value="<?= $editSchedule ? e($editSchedule['collection_time']) : '' ?>" required>

        <label for="waste_type">Waste type</label>
        <select id="waste_type" name="waste_type" required>
            <?php foreach (['General','Recyclable','Organic'] as $t): ?>
                <option value="<?= $t ?>" <?= ($editSchedule && $editSchedule['waste_type'] === $t) ? 'selected' : '' ?>>
                    <?= $t ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="notes">Notes (optional)</label>
        <input type="text" id="notes" name="notes" placeholder="e.g. Bins out by 6:45am" 
               value="<?= $editSchedule ? e($editSchedule['notes']) : '' ?>">

        <button type="submit" class="btn"><?= $editSchedule ? 'Update schedule entry' : 'Add schedule entry' ?></button>
        <?php if ($editSchedule): ?>
            <a href="manage_schedule.php" class="btn btn-secondary">Cancel</a>
        <?php endif; ?>
    </form>

    <form class="form-card" method="post">
        <h3 style="margin-top:0;"><?= $editZone ? 'Edit zone' : 'Add a new zone' ?></h3>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="<?= $editZone ? 'edit_zone' : 'add_zone' ?>">
        <?php if ($editZone): ?>
            <input type="hidden" name="zone_id" value="<?= (int) $editZone['zone_id'] ?>">
        <?php endif; ?>

        <label for="zone_name">Zone name</label>
        <input type="text" id="zone_name" name="zone_name" placeholder="e.g. Zone D - Rubaga" 
               value="<?= $editZone ? e($editZone['zone_name']) : '' ?>" required>

        <label for="zone_description">Description (optional)</label>
        <input type="text" id="zone_description" name="zone_description" 
               value="<?= $editZone ? e($editZone['zone_description']) : '' ?>">

        <button type="submit" class="btn"><?= $editZone ? 'Update zone' : 'Add zone' ?></button>
        <?php if ($editZone): ?>
            <a href="manage_schedule.php" class="btn btn-secondary">Cancel</a>
        <?php endif; ?>
    </form>
</div>

<div class="card" style="margin-top:22px;">
    <h3>Current schedule</h3>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Zone</th><th>Day</th><th>Time</th><th>Type</th><th>Notes</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($schedules as $s): ?>
                <tr>
                    <td><?= e($s['zone_name']) ?></td>
                    <td><?= e($s['day_of_week']) ?></td>
                    <td><?= date('g:i A', strtotime($s['collection_time'])) ?></td>
                    <td><?= e($s['waste_type']) ?></td>
                    <td><?= e($s['notes']) ?></td>
                    <td>
                        <a href="manage_schedule.php?edit=<?= (int) $s['schedule_id'] ?>" 
                           class="btn btn-secondary btn-sm" style="margin-right: 8px;">Edit</a>
                        <form method="post" data-confirm="Remove this schedule entry?" style="display: inline;">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="action" value="delete_schedule">
                            <input type="hidden" name="schedule_id" value="<?= (int) $s['schedule_id'] ?>">
                            <button type="submit" class="btn btn-secondary btn-sm">Remove</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($schedules)): ?>
                <tr><td colspan="6">No schedule entries yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card" style="margin-top:22px;">
    <h3>Zones</h3>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Zone Name</th><th>Description</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($zones as $z): ?>
                <tr>
                    <td><?= e($z['zone_name']) ?></td>
                    <td><?= e($z['zone_description']) ?></td>
                    <td>
                        <a href="manage_schedule.php?edit_zone=<?= (int) $z['zone_id'] ?>" 
                           class="btn btn-secondary btn-sm">Edit</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($zones)): ?>
                <tr><td colspan="3">No zones created yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
