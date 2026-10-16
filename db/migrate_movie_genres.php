<?php
// CLI-only, repeatable setup for the Sci-Fi catalogue category.
// Run with: /Applications/XAMPP/bin/php db/migrate_movie_genres.php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit('Not found.');
}

$pdo = require dirname(__DIR__) . '/includes/db.php';
$pdo->beginTransaction();

$genreLookup = $pdo->prepare('SELECT genreID FROM genre WHERE genreName = ? LIMIT 1');
$genreLookup->execute(['Sci-Fi']);
$genreId = $genreLookup->fetchColumn();
if ($genreId === false) {
    $pdo->prepare('INSERT INTO genre (genreName) VALUES (?)')->execute(['Sci-Fi']);
    $genreId = $pdo->lastInsertId();
}
$genreId = (int) $genreId;

$movieLookup = $pdo->prepare('SELECT movieID FROM movies WHERE title = ? LIMIT 1');
$relationLookup = $pdo->prepare('SELECT 1 FROM movieGenreRelation WHERE movieID = ? AND genreID = ? LIMIT 1');
$relationInsert = $pdo->prepare('INSERT INTO movieGenreRelation (movieID, genreID) VALUES (?, ?)');

foreach (['Inception', 'Interstellar', 'The Matrix'] as $title) {
    $movieLookup->execute([$title]);
    $movieId = $movieLookup->fetchColumn();
    if ($movieId === false) {
        continue;
    }

    $relationLookup->execute([$movieId, $genreId]);
    if ($relationLookup->fetchColumn() === false) {
        $relationInsert->execute([$movieId, $genreId]);
    }
}

$pdo->commit();
echo "Sci-Fi genre is ready.\n";
