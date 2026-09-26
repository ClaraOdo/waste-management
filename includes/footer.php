</main>
<?php if (is_logged_in() && ($_SESSION['role'] ?? '') === 'resident' && ! empty($_SESSION['zone_id'])):
    // Use session-cached zone name; fall back to a DB lookup if missing (old sessions)
    if (! empty($_SESSION['zone_name'])) {
        $fZone = $_SESSION['zone_name'];
    } else {
        $fStmt = $pdo->prepare('SELECT zone_name FROM zones WHERE zone_id = :zid');
        $fStmt->execute(['zid' => $_SESSION['zone_id']]);
        $fZone                 = $fStmt->fetchColumn();
        $_SESSION['zone_name'] = $fZone;
    }
    if ($fZone): ?>
    <div class="zone-badge-fixed">📍 <?php echo e($fZone) ?></div>
<?php endif;endif; ?>
<footer class="site-footer">
    <div class="container">
        <p>WasteWatch &mdash; Community Waste Collection &amp; Reporting System.
        Built in support of <strong>SDG 11: Sustainable Cities and Communities</strong>.</p>
        <p>&copy; <?php echo date('Y') ?> WasteWatch. All rights reserved.</p>
    </div>
</footer>
<script src="<?php echo isset($basePath) ? $basePath : '' ?>assets/js/validate.js"></script>
</body>
</html>
