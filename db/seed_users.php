<?php
// Run from CLI: php db/seed_users.php  (creates or resets the test accounts)
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit('CLI only.');
}

$pdo = require dirname(__DIR__) . '/includes/db.php';

// username, email, password, role, xp, level, streak
$users = [
    ['adminuser', 'adminuser@example.com', '123456', 'admin', 0, 1, 0],
    ['user1', 'user1@example.com', '123456', 'user', 0,  1, 0],
    ['user2', 'user2@example.com', '123456', 'user',  90, 1, 2],
    ['user3', 'user3@example.com', '123456', 'user',  0,  1, 0],
];

$stmt = $pdo->prepare(
    'INSERT INTO users (username, email, password_hash, role, xp, level, login_streak)
     VALUES (?, ?, ?, ?, ?, ?, ?)
     ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), role = VALUES(role),
       xp = VALUES(xp), level = VALUES(level), login_streak = VALUES(login_streak), last_login_date = NULL'
);

foreach ($users as [$name, $email, $pw, $role, $xp, $lvl, $streak]) {
    $stmt->execute([$name, $email, password_hash($pw, PASSWORD_DEFAULT), $role, $xp, $lvl, $streak]);
    echo "$name / $pw ($role, $xp XP)\n";
}
