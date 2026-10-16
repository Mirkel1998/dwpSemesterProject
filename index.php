<?php
// Cinema landing page with a featured film, genre shortcuts, and a few picks.
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/movie_catalog.php';

[$movies, $genres] = movie_catalog_data($pdo);
$featuredMovie = $movies[0] ?? null;
$homePicks = array_slice($movies, $featuredMovie ? 1 : 0, 4);
$genreCounts = array_fill_keys($genres, 0);
foreach ($movies as $movie) {
    foreach (array_filter(array_map('trim', explode(',', (string) ($movie['genres'] ?? '')))) as $genre) {
        if (array_key_exists($genre, $genreCounts)) {
            $genreCounts[$genre]++;
        }
    }
}

$homeGenres = array_values(array_filter(
    ['Action', 'Sci-Fi', 'Horror', 'Romance'],
    static fn (string $genre): bool => in_array($genre, $genres, true)
));
$genreIcons = ['Action' => '✦', 'Sci-Fi' => '☄', 'Horror' => '☾', 'Romance' => '♡'];

$pageTitle = 'Home';
require __DIR__ . '/includes/header.php';
?>
<section class="home-hero home-hero-showcase">
    <div class="home-hero-copy">
        <p class="eyebrow">PIXEL CINEMA &bull; YOUR NEIGHBORHOOD PICTURE HOUSE</p>
        <h1>MAKE TONIGHT<br> A MOVIE NIGHT</h1>
        <p class="home-hero-description">Find the film, pick your seats, and let the little details make it a night.</p>
        <div class="home-hero-actions">
            <a class="hero-cta" href="movies.php">Explore movies</a>
            <a class="hero-secondary-link" href="movies.php#genre-tiles">Browse by genre <span aria-hidden="true">↓</span></a>
        </div>
        <div class="hero-perks" aria-label="Booking information">
            <span>UPCOMING SHOWTIMES</span>
            <span>CHOOSE YOUR SEATS</span>
            <span>BOOK IN MINUTES</span>
        </div>
    </div>
    <div class="home-hero-art">
        <div class="hero-sparkle hero-sparkle-one" aria-hidden="true">✦</div>
        <div class="hero-sparkle hero-sparkle-two" aria-hidden="true">✧</div>
        <img class="mascot" src="assets/mascot.svg" alt="Pixel Cinema camera mascot">
        <?php if ($featuredMovie): ?>
        <a class="hero-featured-film" href="movie.php?id=<?= (int) $featuredMovie['movieID'] ?>">
            <span class="featured-film-label">ON THE BIG SCREEN</span>
            <span class="featured-film-title"><?= e($featuredMovie['title']) ?></span>
            <span class="featured-film-meta">
                <?= e(substr((string) $featuredMovie['release_date'], 0, 4)) ?>
                <?php if ($featuredMovie['rating'] !== null): ?>
                    <span aria-hidden="true"> &bull; </span>★ <?= e(number_format((float) $featuredMovie['rating'], 1)) ?>
                <?php endif; ?>
            </span>
            <span class="featured-film-link"><?= $featuredMovie['next_show'] ? 'Find a showing →' : 'View film →' ?></span>
        </a>
        <?php endif; ?>
    </div>
</section>

<section class="home-genres" aria-labelledby="home-genres-title">
    <div class="home-section-heading">
        <div>
            <p class="eyebrow">PICK A MOOD</p>
            <h2 id="home-genres-title">WHAT ARE WE WATCHING?</h2>
        </div>
        <a href="movies.php">All genres <span aria-hidden="true">→</span></a>
    </div>
    <div class="home-genre-links">
        <?php foreach ($homeGenres as $genre): ?>
        <a class="home-genre-link" data-genre-tone="<?= e(strtolower($genre)) ?>" href="movies.php?genre=<?= rawurlencode($genre) ?>">
            <span class="home-genre-icon" aria-hidden="true"><?= $genreIcons[$genre] ?? '✦' ?></span>
            <span class="home-genre-name"><?= e($genre) ?></span>
            <span class="home-genre-count"><?= $genreCounts[$genre] ? $genreCounts[$genre] . ($genreCounts[$genre] === 1 ? ' film' : ' films') : 'Coming soon' ?></span>
        </a>
        <?php endforeach; ?>
    </div>
</section>

<section class="home-picks" aria-labelledby="home-picks-title">
    <div class="home-section-heading">
        <div>
            <p class="eyebrow">THE PROGRAMME</p>
            <h2 id="home-picks-title">A FEW GOOD PICKS</h2>
        </div>
        <a href="movies.php">See all movies <span aria-hidden="true">→</span></a>
    </div>
    <?php if ($homePicks): ?>
    <div class="movie-grid home-picks-grid">
        <?php foreach ($homePicks as $m): ?>
            <?php require __DIR__ . '/includes/movie_card.php'; ?>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
        <p class="muted">The programme is being updated. Check back soon.</p>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
