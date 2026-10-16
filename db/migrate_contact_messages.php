<?php
// CLI-only, repeatable setup for the removable contact-form module.
// Run with: /Applications/XAMPP/bin/php db/migrate_contact_messages.php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit('Not found.');
}

$pdo = require dirname(__DIR__) . '/includes/db.php';
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
);

echo "Contact message storage is ready.\n";
