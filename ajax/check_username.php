<?php
/**
 * ajax/check_username.php
 * Returns JSON { available: true|false } for a candidate username.
 * Called asynchronously from assets/js/validate.js during registration.
 */
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

$username = trim($_GET['username'] ?? '');

// Basic shape validation before hitting the database.
if (!preg_match('/^[a-zA-Z0-9_]{4,20}$/', $username)) {
    echo json_encode(['available' => false, 'reason' => 'invalid_format']);
    exit;
}

$stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = :username');
$stmt->execute(['username' => $username]);
$exists = (int) $stmt->fetchColumn() > 0;

echo json_encode(['available' => !$exists]);
