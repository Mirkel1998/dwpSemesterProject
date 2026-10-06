<?php

require_once __DIR__ . '/Database.php';

$config = require dirname(__DIR__) . '/config/database.php';

try {
    return Database::connect($config);
} catch (PDOException $exception) {
    error_log('Database connection failed: ' . $exception->getMessage());
    throw new RuntimeException('Database connection failed. Check the server configuration and application logs.');
}