<?php

// Return MySQL settings from environment variables, using local XAMPP defaults
// when unset. DB_PASSWORD is kept separate so it can be configured securely.
// Defaults match a stock XAMPP install (root, empty password). Override with Apache SetEnv elsewhere.
$password = getenv('DB_PASSWORD');

return [
    'host' => getenv('DB_HOST') ?: '127.0.0.1',
    'port' => getenv('DB_PORT') ?: '3306',
    'name' => getenv('DB_NAME') ?: 'movieDB',
    'username' => getenv('DB_USER') ?: 'root',
    'password' => $password === false ? '' : $password,
];