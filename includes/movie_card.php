<?php
$cardGenres = array_filter(array_map('trim', explode(',', (string) ($m['genres'] ?? ''))));
$cardYear = substr((string) $m['release_date'], 0, 4);
$cardRating = $m['rating'] !== null ? (float) $m['rating'] : 0;
?>
<a class="movie-card" href="movie.php?id=<?= (int) $m['movieID'] ?>"
   data-title="<?= e($m['title']) ?>"
   data-genres="<?= e(implode('|', $cardGenres)) ?>"
   data-year="<?= e($cardYear) ?>"
   data-rating="<?= e((string) $cardRating) ?>"
   data-featured="<?= $m['next_show'] ? '1' : '0' ?>">
    <div class="poster" style="--hue:<?= ((int) $m['movieID'] * 47) % 360 ?>">
        <span class="poster-monogram" aria-hidden="true"><?= e(strtoupper(substr($m['title'], 0, 1))) ?></span>
        <span class="poster-tag"><?= $m['next_show'] ? 'NOW BOOKING' : 'NO SHOWTIMES' ?></span>
        <span class="poster-play" aria-hidden="true">▶</span>
    </div>
    <div class="movie-card-info">
        <h3><?= e($m['title']) ?></h3>
        <p class="movie-meta"><?= e($cardYear) ?> <span aria-hidden="true">&bull;</span> <?= e($m['genres'] ?? 'Film') ?></p>
        <div class="movie-card-bottom">
            <span class="movie-rating"><?= $m['rating'] !== null ? '<span aria-hidden="true">★</span> ' . e(number_format($cardRating, 1)) : 'NR' ?></span>
            <span class="showing-status <?= $m['next_show'] ? 'available' : '' ?>">
                <?= $m['next_show'] ? 'Showtimes' : 'No showtimes' ?>
            </span>
        </div>
    </div>
</a>
