<?php if (!isset($pageTitle)) { $pageTitle = 'WasteWatch'; } ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle) ?> | WasteWatch</title>
<link rel="stylesheet" href="<?= isset($basePath) ? $basePath : '' ?>assets/css/style.css">
</head>
<body>
<header class="site-header">
    <div class="container header-inner">
        <a class="brand" href="<?= isset($basePath) ? $basePath : '' ?>index.php">🗑️ WasteWatch</a>
        <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation">☰</button>
        <nav class="site-nav" id="siteNav">
            <?php if (is_logged_in()): ?>
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <!-- Admin nav is only ever rendered from pages inside /admin/, so links are relative to that folder -->
                    <a href="dashboard.php">Dashboard</a>
                    <a href="manage_reports.php">Issue Reports</a>
                    <a href="manage_schedule.php">Schedules</a>
                    <a href="reports_summary.php">Analytics</a>
                <?php else: ?>
                    <!-- Resident nav is only ever rendered from root-level pages -->
                    <a href="dashboard.php">Dashboard</a>
                    <a href="report_issue.php">Report an Issue</a>
                    <a href="request_pickup.php">Request Pickup</a>
                    <a href="schedule.php">Collection Schedule</a>
                <?php endif; ?>
                <span class="nav-user">
                    Hi, <?= e($_SESSION['full_name']) ?>
                    <?php if ($_SESSION['role'] === 'resident' && !empty($_SESSION['zone_name'])): ?>
                        <span class="nav-zone">📍 <?= e($_SESSION['zone_name']) ?></span>
                    <?php endif; ?>
                </span>
                <a href="<?= $basePath ?? '' ?>logout.php" class="btn-link">Logout</a>
            <?php else: ?>
                <a href="<?= $basePath ?? '' ?>index.php">Home</a>
                <a href="<?= $basePath ?? '' ?>login.php">Login</a>
                <a href="<?= $basePath ?? '' ?>register.php" class="btn-link">Register</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<main class="container page-content">
    <?php render_flash(); ?>
