<?php
// CLI-only, repeatable setup for the Sci-Fi catalogue category.
// This script makes sure the "Sci-Fi" genre exists and is linked to a few
// specific movies, without creating duplicates if it's run more than once.
// Run with: /Applications/XAMPP/bin/php db/migrate_movie_genres.php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit('Not found.');
}

$pdo = require dirname(__DIR__) . '/includes/db.php';

// beginTransaction()/commit() group all the statements below into one unit:
// if something fails partway through, we can roll back so we never end up
// with half-finished changes (e.g. the genre inserted but no movies linked).
$pdo->beginTransaction();

// Step 1: find the Sci-Fi genre's ID, or create it if it doesn't exist yet.
$genreLookup = $pdo->prepare('SELECT genreID FROM genre WHERE genreName = ? LIMIT 1');
$genreLookup->execute(['Sci-Fi']);
$genreId = $genreLookup->fetchColumn();
if ($genreId === false) {
    // fetchColumn() returns false when no row matched, so we insert one.
    $pdo->prepare('INSERT INTO genre (genreName) VALUES (?)')->execute(['Sci-Fi']);
    // lastInsertId() gives us the auto-increment ID MySQL just generated.
    $genreId = $pdo->lastInsertId();
}
$genreId = (int) $genreId;

// Prepared statements reused in the loop below: looking up a movie by
// title, checking if the genre link already exists, and creating the link.
$movieLookup = $pdo->prepare('SELECT movieID FROM movies WHERE title = ? LIMIT 1');
$relationLookup = $pdo->prepare('SELECT 1 FROM movieGenreRelation WHERE movieID = ? AND genreID = ? LIMIT 1');
$relationInsert = $pdo->prepare('INSERT INTO movieGenreRelation (movieID, genreID) VALUES (?, ?)');

// Step 2: for each of these titles, link it to the Sci-Fi genre if it
// exists in the catalogue and isn't already linked.
foreach (['Inception', 'Interstellar', 'The Matrix'] as $title) {
    $movieLookup->execute([$title]);
    $movieId = $movieLookup->fetchColumn();
    if ($movieId === false) {
        // Skip titles that aren't in the movies table (e.g. not seeded yet).
        continue;
    }

    $relationLookup->execute([$movieId, $genreId]);
    if ($relationLookup->fetchColumn() === false) {
        // Only insert the link if it isn't there already, avoiding duplicates.
        $relationInsert->execute([$movieId, $genreId]);
    }
}

$pdo->commit();
echo "Sci-Fi genre is ready.\n";
