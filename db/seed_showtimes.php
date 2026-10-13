<?php
// CLI-only, idempotent demo schedule seeder for the cinema prototype.
// Run with: /Applications/XAMPP/bin/php db/seed_showtimes.php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit('Not found.');
}

$pdo = require dirname(__DIR__) . '/includes/db.php';
$movies = $pdo->query('SELECT movieID FROM movies ORDER BY movieID')->fetchAll(PDO::FETCH_COLUMN);
$theaters = $pdo->query('SELECT theaterID FROM theaters ORDER BY theaterID')->fetchAll(PDO::FETCH_COLUMN);

if (!$movies) {
    throw new RuntimeException('Cannot seed showtimes: no movies are in the database.');
}
if (!$theaters) {
    throw new RuntimeException('Cannot seed showtimes: no theaters are in the database.');
}

$today = $pdo->query('SELECT CURDATE()')->fetchColumn();
$firstDate = DateTimeImmutable::createFromFormat('!Y-m-d', (string) $today);
if (!$firstDate) {
    throw new RuntimeException('Could not read the database calendar date.');
}

$insert = $pdo->prepare(
    'INSERT INTO showtimes (movieID, theaterID, starts_at, price)
     SELECT ?, ?, ?, 90 FROM DUAL
     WHERE NOT EXISTS (
         SELECT 1 FROM showtimes
         WHERE movieID = ? AND theaterID = ? AND starts_at = ?
     )'
);

$inserted = 0;
$pdo->beginTransaction();
try {
    foreach ($movies as $movieID) {
        $movieID = (int) $movieID;
        $firstTheaterIndex = $movieID % count($theaters);
        $movieTheaters = array_values(array_unique([
            (int) $theaters[$firstTheaterIndex],
            (int) $theaters[($firstTheaterIndex + 1) % count($theaters)],
        ]));

        for ($day = 1; $day <= 14; $day++) {
            $date = $firstDate->modify('+' . $day . ' days')->format('Y-m-d');
            foreach ($movieTheaters as $theaterID) {
                foreach (['18:00:00', '21:00:00'] as $time) {
                    $startsAt = $date . ' ' . $time;
                    $insert->execute([
                        $movieID, $theaterID, $startsAt,
                        $movieID, $theaterID, $startsAt,
                    ]);
                    $inserted += $insert->rowCount();
                }
            }
        }
    }

    $pdo->commit();
} catch (Throwable $error) {
    $pdo->rollBack();
    throw $error;
}

echo "Added $inserted demo showtimes for the next 14 days. Existing showtimes were left unchanged.\n";
