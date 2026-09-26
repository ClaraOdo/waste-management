<?php
    require_once __DIR__ . '/includes/db.php';
    require_once __DIR__ . '/includes/functions.php';

    if (is_logged_in()) {
    redirect($_SESSION['role'] === 'admin' ? 'admin/dashboard.php' : 'dashboard.php');
    }

    $pageTitle = 'Home';
    require __DIR__ . '/includes/header.php';

    $resolvedCount   = (int) $pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'Resolved'")->fetchColumn();
    $zoneCount       = (int) $pdo->query("SELECT COUNT(*) FROM zones")->fetchColumn();
    $nextCleaningDay = next_national_cleaning_day();
?>

<?php if ($nextCleaningDay): ?>
<div class="alert alert-success" style="border-left:4px solid var(--ok);">
    <strong>Uganda National Cleaning Day:</strong> the next mandatory community clean-up is
    <strong><?php echo e($nextCleaningDay->format('l, j F Y')) ?></strong>, 7:00-10:00am.
    WasteWatch covers the days in between, report an issue or check your zone's regular
    collection schedule any time.
</div>
<?php endif; ?>

<section class="hero">
    <h1>Report it. Track it. Get it collected.</h1>
    <p>WasteWatch connects residents and the local waste management team so that missed
       collections, illegal dumping and overflowing bins get fixed i.e not forgotten.
       Built to support SDG 11: Sustainable Cities and Communities.</p>
    <a class="btn" href="register.php">Create a free account</a>
    <a class="btn btn-secondary" href="login.php">Login</a>
</section>

<div class="grid grid-3">
    <div class="stat-card">
        <div class="stat-value"><?php echo $resolvedCount ?></div>
        <div class="stat-label">Issues resolved so far</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?php echo $zoneCount ?></div>
        <div class="stat-label">Collection zones covered</div>
    </div>
    <div class="stat-card">
        <div class="stat-value">3</div>
        <div class="stat-label">Ways to get involved: report, request, track</div>
    </div>
</div>

<div class="grid grid-3" style="margin-top:30px;">
    <div class="card">
        <h3>Report an issue</h3>
        <p>Missed collection, illegal dumping or an overflowing bin near you? Log it in under
           a minute and follow its status.</p>
    </div>
    <div class="card">
        <h3>Check the schedule</h3>
        <p>See exactly which day your zone's bins go out, and what type of waste is collected.</p>
    </div>
    <div class="card">
        <h3>Request a pickup</h3>
        <p>Bulky items, e-waste or garden waste that doesn't fit the regular route? Request a
           special collection.</p>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>