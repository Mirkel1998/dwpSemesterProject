<?php
require_once __DIR__ . '/includes/bootstrap.php';

$movies = $pdo->query(
    'SELECT m.movieID, m.title, m.release_date, m.rating,
            GROUP_CONCAT(DISTINCT g.genreName SEPARATOR ", ") AS genres,
            (SELECT MIN(s.starts_at) FROM showtimes s WHERE s.movieID = m.movieID AND s.starts_at > NOW()) AS next_show
     FROM movies m
     LEFT JOIN movieGenreRelation r ON r.movieID = m.movieID
     LEFT JOIN genre g ON g.genreID = r.genreID
     GROUP BY m.movieID, m.title, m.release_date, m.rating
     ORDER BY m.title'
)->fetchAll();

$pageTitle = 'Now Showing';
require __DIR__ . '/includes/header.php';
?>
<h1>NOW SHOWING</h1>
<p class="muted">Book seats, log in daily and level up for ticket discounts.</p>
<div class="grid">
<?php foreach ($movies as $m): ?>
    <a class="card" href="movie.php?id=<?= (int) $m['movieID'] ?>">
        <div class="poster" style="--hue:<?= ((int) $m['movieID'] * 47) % 360 ?>"><?= e(strtoupper(substr($m['title'], 0, 1))) ?></div>
        <h2><?= e($m['title']) ?></h2>
        <p class="muted"><?= e(substr((string) $m['release_date'], 0, 4)) ?> &bull; <?= e($m['genres'] ?? '-') ?></p>
        <p class="muted"><?= $m['rating'] !== null ? '&#9733; ' . e(number_format((float) $m['rating'], 1)) . '/10' : 'Not rated' ?></p>
        <p><?= $m['next_show'] ? 'Next: ' . e(date('D j M, H:i', strtotime($m['next_show']))) : 'No showings' ?></p>
    </a>
<?php endforeach; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>