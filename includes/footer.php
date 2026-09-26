</main>
<?php if (is_logged_in() && ($_SESSION['role'] ?? '') === 'resident' && !empty($_SESSION['zone_id'])):
    $fStmt = $pdo->prepare('SELECT zone_name FROM zones WHERE zone_id = :zid');
    $fStmt->execute(['zid' => $_SESSION['zone_id']]);
    $fZone = $fStmt->fetchColumn();
    if ($fZone): ?>
    <div class="zone-badge-fixed">📍 <?= e($fZone) ?></div>
<?php endif; endif; ?>
<footer class="site-footer">
    <div class="container">
        <p>WasteWatch &mdash; Community Waste Collection &amp; Reporting System.
        Built in support of <strong>SDG 11: Sustainable Cities and Communities</strong>.</p>
        <p>&copy; <?= date('Y') ?> WasteWatch. All rights reserved.</p>
    </div>
</footer>
<script src="<?= isset($basePath) ? $basePath : '' ?>assets/js/validate.js"></script>
</body>
</html>
