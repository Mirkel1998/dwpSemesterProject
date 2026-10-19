<?php
// CLI-only, repeatable setup for the removable contact-form module.
// "Migration" = a script that updates the database schema (tables/columns)
// instead of changing it by hand in phpMyAdmin. Running this file is the
// same as running CreateMovieDB.sql, but it only touches this one table and
// can be re-run safely without wiping the rest of the database.
// Run with: /Applications/XAMPP/bin/php db/migrate_contact_messages.php
declare(strict_types=1);

// This guard stops the script from running if someone opens it in a
// browser. PHP_SAPI tells us how the script was started; 'cli' means it was
// run from a terminal. If it's not CLI, we 404 instead of letting a visitor
// trigger database changes over HTTP.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit('Not found.');
}

// includes/db.php returns a ready-to-use PDO connection (our link to MySQL).
$pdo = require dirname(__DIR__) . '/includes/db.php';

// CREATE TABLE IF NOT EXISTS makes this safe to run more than once: if the
// table is already there, MySQL just skips creating it instead of erroring.
$pdo->exec(
    "CREATE TABLE IF NOT EXISTS contact_messages (
        contactID INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        userID INT NULL,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(255) NOT NULL,
        subject VARCHAR(150) NOT NULL,
        message TEXT NOT NULL,
        status ENUM('new', 'read') NOT NULL DEFAULT 'new',
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY contact_messages_status_created (status, created_at),
        CONSTRAINT contact_messages_user
            FOREIGN KEY (userID) REFERENCES users(userID) ON DELETE SET NULL
    ) ENGINE=InnoDB"
    // userID is nullable and ON DELETE SET NULL, so if a user's account is
    // deleted later, their old messages stay in the table instead of being
    // deleted too - they just lose the link to that user.
);

echo "Contact message storage is ready.\n";
