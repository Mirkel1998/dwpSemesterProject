<?php

// Small connection diagnostic: try the configured PDO connection and a
// lightweight query, returning a simple success message or generic HTTP 500.
try {
    $pdo = require __DIR__ . '/includes/db.php';
    $pdo->query('SELECT 1');
    echo 'Database connection works.';
} catch (Throwable $exception) {
    http_response_code(500);
    echo 'Connection failed. Check Apache’s PHP error log.';
}