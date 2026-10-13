<?php
// Movie details page: validate the requested movie, fetch its cast and
// upcoming showtimes, and render them with the shared site layout.
require_once __DIR__ . '/includes/bootstrap.php';

// Read the movie identifier from the URL and return 404 if it is invalid or absent.
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$stmt = $pdo->prepare('SELECT movieID, title, release_date, rating FROM movies WHERE movieID = ?');
$stmt->execute([$id]);
$movie = $stmt->fetch();
if (!$movie) {
    http_response_code(404);
    exit('Movie not found.');
}

// Load the people and their credited roles for this movie.
$cast = $pdo->prepare(
    'SELECT c.firstName, c.lastName, r.relationToMovie
     FROM castMovieRelation r JOIN `cast` c ON c.castID = r.castID
     WHERE r.movieID = ? ORDER BY r.relationToMovie, c.lastName'
);
$cast->execute([$id]);
$cast = $cast->fetchAll();

// Load future showings with theater details and the number of seats already booked.
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
<header class="film-heading">
    <div>
        <p class="eyebrow">PIXEL CINEMA &bull; FILM GUIDE</p>
        <h1><?= e($movie['title']) ?></h1>
    </div>
    <p class="muted">Released <?= e($movie['release_date']) ?> &bull; <?= $movie['rating'] !== null ? '&#9733; ' . e(number_format((float) $movie['rating'], 1)) . '/10' : 'Not rated' ?></p>
</header>

<section class="panel cast-panel">
    <h2>CAST &amp; CREW</h2>
    <?php if (!$cast): ?><p class="muted">Cast details are not available yet.</p><?php endif; ?>
    <ul>
    <?php foreach ($cast as $c): ?>
        <li><?= e($c['firstName'] . ' ' . $c['lastName']) ?> <span class="muted">(<?= e($c['relationToMovie']) ?>)</span></li>
    <?php endforeach; ?>
    </ul>
</section>

<section class="panel showtimes-panel">
    <h2>SHOWTIMES</h2>
    <?php if (!$shows): ?><p class="muted">There are no upcoming showtimes for this film yet. Check back soon.</p><?php endif; ?>
    <div class="showtimes-table">
    <table>
        <?php if ($shows): ?>
        <thead><tr><th>Date &amp; time</th><th>Cinema</th><th>Ticket</th><th>Availability</th><th><span class="visually-hidden">Book seats</span></th></tr></thead>
        <?php endif; ?>
        <tbody>
        <?php foreach ($shows as $s): $left = (int) $s['capacity'] - (int) $s['taken']; ?>
        <tr>
            <td><?= e(date('D j M, H:i', strtotime($s['starts_at']))) ?></td>
            <td><?= e($s['name']) ?>, <?= e($s['city']) ?></td>
            <td><?= (int) $s['price'] ?> kr</td>
            <td><?= $left ?> left</td>
            <td><?= $left > 0 ? '<a class="btn small" href="book.php?showtime=' . (int) $s['showtimeID'] . '">Book</a>' : 'SOLD OUT' ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
