<?php
// Movie catalogue with live category, search, and sort controls.
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/movie_catalog.php';

[$movies, $genres] = movie_catalog_data($pdo);
$genreCounts = array_fill_keys($genres, 0);
foreach ($movies as $movie) {
    foreach (array_filter(array_map('trim', explode(',', (string) ($movie['genres'] ?? '')))) as $genre) {
        if (array_key_exists($genre, $genreCounts)) {
            $genreCounts[$genre]++;
        }
    }
}
$genreIcons = ['Action' => '✦', 'Sci-Fi' => '☄', 'Horror' => '☾', 'Romance' => '♡', 'Comedy' => '☻', 'Drama' => '✧'];
$selectedGenre = trim((string) ($_GET['genre'] ?? ''));
if (!in_array($selectedGenre, $genres, true)) {
    $selectedGenre = '';
}

$pageTitle = 'Movies';
require __DIR__ . '/includes/header.php';
?>
<header class="movies-hero">
    <div class="movies-hero-copy">
        <p class="eyebrow">THE PIXEL CINEMA PROGRAMME</p>
        <h1>FIND YOUR<br><span>FEEL-GOOD</span> FILM</h1>
        <p>Big adventures, little love stories, strange worlds. Pick a mood and find your next movie-night favorite.</p>
    </div>
    <div class="movies-hero-side" aria-hidden="true">
        <span class="movies-hero-star">✦</span>
        <span class="movies-hero-film">NOW<br>SHOWING</span>
        <span class="movies-hero-dots">•••</span>
    </div>
</header>

<section class="movie-browser" aria-labelledby="browse-title">
    <div class="browse-heading">
        <div>
            <p class="eyebrow">PICK YOUR KIND OF NIGHT</p>
            <h2 id="browse-title">BROWSE BY GENRE</h2>
        </div>
        <p class="browse-count" data-result-count aria-live="polite"><?= count($movies) ?> films</p>
    </div>

    <div class="genre-tiles" id="genre-tiles" role="group" aria-label="Filter by genre" data-genre-list>
        <button class="genre-tile <?= $selectedGenre === '' ? 'is-active' : '' ?>" type="button" data-genre="all" aria-pressed="<?= $selectedGenre === '' ? 'true' : 'false' ?>">
            <span class="genre-tile-icon" aria-hidden="true">✦</span>
            <span class="genre-tile-name">Everything</span>
            <span class="genre-tile-count"><?= count($movies) ?> films</span>
        </button>
        <?php foreach ($genres as $genre): ?>
        <button class="genre-tile <?= $selectedGenre === $genre ? 'is-active' : '' ?>" type="button" data-genre="<?= e($genre) ?>" aria-pressed="<?= $selectedGenre === $genre ? 'true' : 'false' ?>">
            <span class="genre-tile-icon" aria-hidden="true"><?= $genreIcons[$genre] ?? '✦' ?></span>
            <span class="genre-tile-name"><?= e($genre) ?></span>
            <span class="genre-tile-count"><?= $genreCounts[$genre] ? $genreCounts[$genre] . ($genreCounts[$genre] === 1 ? ' film' : ' films') : 'Coming soon' ?></span>
        </button>
        <?php endforeach; ?>
    </div>

    <div class="catalog-controls">
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

    <?php if ($movies): ?>
    <div class="movie-grid" data-movie-grid>
        <?php foreach ($movies as $m): ?>
            <?php require __DIR__ . '/includes/movie_card.php'; ?>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <p class="empty-results">There are no films in the programme yet.</p>
    <?php endif; ?>
    <p class="empty-results" data-empty-results hidden>No films match those filters. Try another search or genre.</p>
</section>
<script src="styling/movies.js?v=3" defer></script>
<?php require __DIR__ . '/includes/footer.php'; ?>
