<?php
// CLI-only schema migration for grouping all seats from a single checkout.
// Problem it solves: if a customer buys 3 seats in one checkout, that used
// to create 3 separate, unrelated rows in `bookings`. Adding a shared
// booking_ref (a random reference string) to each of those rows lets us
// treat them as "one order" later (e.g. on a receipt or booking history page).
// Run with: /Applications/XAMPP/bin/php db/migrate_booking_groups.php
declare(strict_types=1);

// Block this script from running over HTTP - it's a terminal-only tool.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit('Not found.');
}

$pdo = require dirname(__DIR__) . '/includes/db.php';

// Unlike CREATE TABLE IF NOT EXISTS, ALTER TABLE has no built-in "only if
// missing" option for adding a column, so we check manually with SHOW
// COLUMNS first. Without this check, running the script twice would crash
// with a "duplicate column" error.
$columns = $pdo->query("SHOW COLUMNS FROM bookings LIKE 'booking_ref'")->fetchAll();
if (!$columns) {
    // Add the new column only when it doesn't exist yet.
    $pdo->exec('ALTER TABLE bookings ADD booking_ref CHAR(32) NULL AFTER showtimeID');
}

// Same idea for the index: SHOW INDEX lets us check if it was already
// created before trying to add it again. An index on (userID, booking_ref)
// speeds up queries like "show me every seat this user booked together".
$indexes = $pdo->query("SHOW INDEX FROM bookings WHERE Key_name = 'bookings_user_ref'")->fetchAll();
if (!$indexes) {
    $pdo->exec('ALTER TABLE bookings ADD KEY bookings_user_ref (userID, booking_ref)');
}

echo "Booking groups are ready.\n";
