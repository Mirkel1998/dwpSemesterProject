<?php
// CLI-only development seeder: insert the sample accounts or reset their
// passwords and game progress to the values below. It does not run via HTTP.
// "Seeding" means filling the database with sample/test data so you have
// something to log in with and test against, rather than an empty database.
// Run from CLI: php db/seed_users.php  (creates or resets the test accounts)
declare(strict_types=1);

// Block execution via a web browser - this is a developer-only tool.
if (PHP_SAPI !== 'cli') {
    exit('CLI only.');
}

$pdo = require dirname(__DIR__) . '/includes/db.php';

// username, email, password, role, xp, level, streak
// Plain-text passwords here are fine because this data never reaches the
// database as plain text - password_hash() converts it below before saving.
$users = [
    ['adminuser', 'adminuser@example.com', '123456', 'admin', 0, 1, 0],
    ['user1', 'user1@example.com', '123456', 'user', 0,  1, 0],
    ['user2', 'user2@example.com', '123456', 'user',  90, 1, 2],
    ['user3', 'user3@example.com', '123456', 'user',  0,  1, 0],
];

// Insert each sample account, updating the existing account on duplicate name
// or email; password_hash() ensures the database receives hashes, not plaintext.
// ON DUPLICATE KEY UPDATE means: if a user with this username/email already
// exists (because we ran this script before), update their row instead of
// failing with a duplicate-entry error. This is what makes the script
// repeatable/idempotent - safe to run again whenever you want to reset
// these accounts back to a known state.
$stmt = $pdo->prepare(
    'INSERT INTO users (username, email, password_hash, role, xp, level, login_streak)
     VALUES (?, ?, ?, ?, ?, ?, ?)
     ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), role = VALUES(role),
       xp = VALUES(xp), level = VALUES(level), login_streak = VALUES(login_streak), last_login_date = NULL'
);

// Loop through the array above and run the insert once per user.
// [$name, $email, $pw, ...] destructures each inner array into named
// variables so we don't have to write $users[$i][0], $users[$i][1], etc.
foreach ($users as [$name, $email, $pw, $role, $xp, $lvl, $streak]) {
    $stmt->execute([$name, $email, password_hash($pw, PASSWORD_DEFAULT), $role, $xp, $lvl, $streak]);
    echo "$name / $pw ($role, $xp XP)\n";
}
