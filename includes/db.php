<?php
/**
 * db.php - Central database connection using PDO.
 * Prepared statements (used throughout the app) rely on this connection
 * to prevent SQL injection.
 */

$DB_HOST    = 'localhost';
$DB_NAME    = 'wastewatch';
$DB_USER    = 'root';
$DB_PASS    = 'root';
$DB_CHARSET = 'utf8mb4';

$dsn = "mysql:host={$DB_HOST};dbname={$DB_NAME};charset={$DB_CHARSET}";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false, // use real prepared statements
];

try {
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
} catch (PDOException $e) {
    error_log('DB connection error: ' . $e->getMessage());
    die('Database connection failed. Please check your configuration or contact the administrator.');
}
