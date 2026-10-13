<?php
// Movie-catalog home page: load the shared app setup, fetch movie details,
// then render one linked card per movie using the common page header/footer.
require_once __DIR__ . '/includes/bootstrap.php';

// Join genres into a display string and find each movie's next future showing.
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

$genres = [];
foreach ($movies as $movie) {
    foreach (explode(',', (string) ($movie['genres'] ?? '')) as $genre) {
        $genre = trim($genre);
        if ($genre !== '') {
            $genres[$genre] = true;
        }
    }
}
$genres = array_keys($genres);
sort($genres, SORT_NATURAL | SORT_FLAG_CASE);

$pageTitle = 'Now Showing';
require __DIR__ . '/includes/header.php';
?>
<section class="home-hero">
    <div class="home-hero-copy">
        <p class="eyebrow">PIXEL CINEMA &bull; YOUR LOCAL PICTURE HOUSE</p>
        <h1>FIND YOUR NEXT MOVIE</h1>
        <p class="muted">Pick a film, find a showing, and make a night of it.</p>
        <div class="hero-perks" aria-label="Booking information">
            <span>UPCOMING SHOWTIMES</span>
            <span>CHOOSE YOUR SEATS</span>
            <span>BOOK IN MINUTES</span>
        </div>
    </div>
    <img class="mascot" src="assets/mascot.svg" alt="" aria-hidden="true">
</section>

<section class="movie-browser" aria-labelledby="browse-title">
    <div class="browse-heading">
        <div>
            <p class="eyebrow">THE PROGRAMME</p>
            <h2 id="browse-title">BROWSE FILMS</h2>
        </div>
        <p class="browse-count" data-result-count aria-live="polite"><?= count($movies) ?> films</p>
    </div>

    <div class="browse-toolbar">
        <label class="movie-search">
            <span class="visually-hidden">Search films</span>
            <span aria-hidden="true">⌕</span>
            <input type="search" data-movie-search placeholder="Search films or genres" autocomplete="off">
        </label>
        <label class="movie-sort">
            <span>Sort by</span>
            <select data-movie-sort aria-label="Sort films">
                <option value="recommended">Featured</option>
                <option value="title">Title A–Z</option>
                <option value="newest">Newest</option>
                <option value="rating">Top rated</option>
            </select>
        </label>
    </div>

    <div class="genre-list" role="group" aria-label="Filter by genre" data-genre-list>
        <button class="genre-chip is-active" type="button" data-genre="all" aria-pressed="true">All films</button>
        <?php foreach ($genres as $genre): ?>
            <button class="genre-chip" type="button" data-genre="<?= e($genre) ?>" aria-pressed="false"><?= e($genre) ?></button>
        <?php endforeach; ?>
    </div>

    <div class="movie-grid" data-movie-grid>
<?php foreach ($movies as $m): ?>
    <?php
        $movieGenres = array_filter(array_map('trim', explode(',', (string) ($m['genres'] ?? ''))));
        $releaseYear = substr((string) $m['release_date'], 0, 4);
        $rating = $m['rating'] !== null ? (float) $m['rating'] : 0;
    ?>
    <a class="movie-card" href="movie.php?id=<?= (int) $m['movieID'] ?>"
       data-title="<?= e($m['title']) ?>"
       data-genres="<?= e(implode('|', $movieGenres)) ?>"
       data-year="<?= e($releaseYear) ?>"
       data-rating="<?= e((string) $rating) ?>"
       data-featured="<?= $m['next_show'] ? '1' : '0' ?>">
        <div class="poster" style="--hue:<?= ((int) $m['movieID'] * 47) % 360 ?>">
            <span class="poster-monogram" aria-hidden="true"><?= e(strtoupper(substr($m['title'], 0, 1))) ?></span>
            <span class="poster-tag"><?= $m['next_show'] ? 'NOW BOOKING' : 'NO SHOWTIMES' ?></span>
            <span class="poster-play" aria-hidden="true">▶</span>
        </div>
        <div class="movie-card-info">
            <h3><?= e($m['title']) ?></h3>
            <p class="movie-meta"><?= e($releaseYear) ?> <span aria-hidden="true">&bull;</span> <?= e($m['genres'] ?? 'Film') ?></p>
            <div class="movie-card-bottom">
                <span class="movie-rating"><?= $m['rating'] !== null ? '<span aria-hidden="true">★</span> ' . e(number_format($rating, 1)) : 'NR' ?></span>
                <span class="showing-status <?= $m['next_show'] ? 'available' : '' ?>">
                    <?= $m['next_show'] ? 'Showtimes' : 'No showtimes' ?>
                </span>
            </div>
        </div>
    </a>
<?php endforeach; ?>
    </div>
    <p class="empty-results" data-empty-results hidden>No films match those filters. Try another search or genre.</p>
</section>
<script src="styling/movies.js" defer></script>
<?php require __DIR__ . '/includes/footer.php'; ?>