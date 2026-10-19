<?php
// CLI-only, idempotent demo schedule seeder for the cinema prototype.
// This fills the `showtimes` table so movie pages have something to show
// and the booking flow has real showtimes to book seats against.
// Run with: /Applications/XAMPP/bin/php db/seed_showtimes.php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit('Not found.');
}

$pdo = require dirname(__DIR__) . '/includes/db.php';

// Grab every existing movie and theater ID - we need these to build
// showtimes, since each showtime links a movie to a theater and a time.
$movies = $pdo->query('SELECT movieID FROM movies ORDER BY movieID')->fetchAll(PDO::FETCH_COLUMN);
$theaters = $pdo->query('SELECT theaterID FROM theaters ORDER BY theaterID')->fetchAll(PDO::FETCH_COLUMN);

// Fail early with a clear message instead of silently doing nothing - this
// script depends on movies/theaters already existing (e.g. from
// CreateMovieDB.sql), so these checks catch running things out of order.
if (!$movies) {
    throw new RuntimeException('Cannot seed showtimes: no movies are in the database.');
}
if (!$theaters) {
    throw new RuntimeException('Cannot seed showtimes: no theaters are in the database.');
}

// Use the database's own idea of "today" (not PHP's) so the generated
// showtimes line up correctly with whatever timezone MySQL is configured for.
$today = $pdo->query('SELECT CURDATE()')->fetchColumn();
$firstDate = DateTimeImmutable::createFromFormat('!Y-m-d', (string) $today);
if (!$firstDate) {
    throw new RuntimeException('Could not read the database calendar date.');
}

// This INSERT ... WHERE NOT EXISTS pattern is how we make the script
// idempotent (safe to run repeatedly): it only inserts a showtime if that
// exact movie/theater/time combination isn't already in the table, so
// re-running the script won't create duplicate showtimes.
$insert = $pdo->prepare(
    'INSERT INTO showtimes (movieID, theaterID, starts_at, price)
     SELECT ?, ?, ?, 90 FROM DUAL
     WHERE NOT EXISTS (
         SELECT 1 FROM showtimes
         WHERE movieID = ? AND theaterID = ? AND starts_at = ?
     )'
);

$inserted = 0;
// Wrap all inserts in one transaction so that if something goes wrong
// partway through, we can undo everything with rollBack() instead of
// leaving only some showtimes created.
$pdo->beginTransaction();
try {
    foreach ($movies as $movieID) {
        $movieID = (int) $movieID;
        // Spread each movie across two theaters rather than showing every
        // movie in every theater. The modulo (%) picks a starting theater
        // based on the movie's ID so different movies land in different
        // theaters, and the "+1" picks the next theater in the list.
        $firstTheaterIndex = $movieID % count($theaters);
        $movieTheaters = array_values(array_unique([
            (int) $theaters[$firstTheaterIndex],
            (int) $theaters[($firstTheaterIndex + 1) % count($theaters)],
        ]));

        // Build 14 days of showings, two per day (evening + late showing),
        // for each theater this movie is playing in.
        for ($day = 1; $day <= 14; $day++) {
            $date = $firstDate->modify('+' . $day . ' days')->format('Y-m-d');
            foreach ($movieTheaters as $theaterID) {
                foreach (['18:00:00', '21:00:00'] as $time) {
                    $startsAt = $date . ' ' . $time;
                    // The same values are passed twice: once for the data
                    // we want to insert, and once for the WHERE NOT EXISTS
                    // duplicate check in the prepared statement above.
                    $insert->execute([
                        $movieID, $theaterID, $startsAt,
                        $movieID, $theaterID, $startsAt,
                    ]);
                    // rowCount() is 1 if a new row was inserted, 0 if the
                    // WHERE NOT EXISTS check skipped it as a duplicate.
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
