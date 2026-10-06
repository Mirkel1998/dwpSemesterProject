<?php

try {
    $pdo = require __DIR__ . '/includes/db.php';
    $pdo->query('SELECT 1');
    echo 'Database connection works.';
} catch (Throwable $exception) {
    http_response_code(500);
    echo 'Connection failed. Check Apache’s PHP error log.';
}