<?php
// CLI-only schema migration for grouping all seats from a single checkout.
// Run with: /Applications/XAMPP/bin/php db/migrate_booking_groups.php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit('Not found.');
}

$pdo = require dirname(__DIR__) . '/includes/db.php';
$columns = $pdo->query("SHOW COLUMNS FROM bookings LIKE 'booking_ref'")->fetchAll();
if (!$columns) {
    $pdo->exec('ALTER TABLE bookings ADD booking_ref CHAR(32) NULL AFTER showtimeID');
}

$indexes = $pdo->query("SHOW INDEX FROM bookings WHERE Key_name = 'bookings_user_ref'")->fetchAll();
if (!$indexes) {
    $pdo->exec('ALTER TABLE bookings ADD KEY bookings_user_ref (userID, booking_ref)');
}

echo "Booking groups are ready.\n";
