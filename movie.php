<?php
require_once __DIR__ . '/includes/bootstrap.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$stmt = $pdo->prepare('SELECT movieID, title, release_date, rating FROM movies WHERE movieID = ?');
$stmt->execute([$id]);
$movie = $stmt->fetch();
if (!$movie) {
    http_response_code(404);
    exit('Movie not found.');
}

$cast = $pdo->prepare(
    'SELECT c.firstName, c.lastName, r.relationToMovie
     FROM castMovieRelation r JOIN `cast` c ON c.castID = r.castID
     WHERE r.movieID = ? ORDER BY r.relationToMovie, c.lastName'
);
$cast->execute([$id]);
$cast = $cast->fetchAll();

$shows = $pdo->prepare(
    'SELECT s.showtimeID, s.starts_at, s.price, t.name, t.city,
            t.seat_rows * t.seats_per_row AS capacity,
            (SELECT COUNT(*) FROM bookings b WHERE b.showtimeID = s.showtimeID) AS taken
     FROM showtimes s JOIN theaters t ON t.theaterID = s.theaterID
     WHERE s.movieID = ? AND s.starts_at > NOW()
     ORDER BY s.starts_at'
);
$shows->execute([$id]);
$shows = $shows->fetchAll();

$pageTitle = $movie['title'];
require __DIR__ . '/includes/header.php';
?>
<h1><?= e($movie['title']) ?></h1>
<p class="muted">Released <?= e($movie['release_date']) ?> &bull; <?= $movie['rating'] !== null ? '&#9733; ' . e(number_format((float) $movie['rating'], 1)) . '/10' : 'Not rated' ?></p>

<section class="panel">
    <h2>CAST &amp; CREW</h2>
    <ul>
    <?php foreach ($cast as $c): ?>
        <li><?= e($c['firstName'] . ' ' . $c['lastName']) ?> <span class="muted">(<?= e($c['relationToMovie']) ?>)</span></li>
    <?php endforeach; ?>
    </ul>
</section>

<section class="panel">
    <h2>SHOWTIMES</h2>
    <?php if (!$shows): ?><p>No upcoming showings.</p><?php endif; ?>
    <table>
        <?php foreach ($shows as $s): $left = (int) $s['capacity'] - (int) $s['taken']; ?>
        <tr>
            <td><?= e(date('D j M, H:i', strtotime($s['starts_at']))) ?></td>
            <td><?= e($s['name']) ?>, <?= e($s['city']) ?></td>
            <td><?= (int) $s['price'] ?> kr</td>
            <td><?= $left ?> left</td>
            <td><?= $left > 0 ? '<a class="btn small" href="book.php?showtime=' . (int) $s['showtimeID'] . '">Book</a>' : 'SOLD OUT' ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
