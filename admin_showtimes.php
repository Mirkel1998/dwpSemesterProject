<?php
// Admin-only month calendar for creating and reviewing cinema showtimes.
require_once __DIR__ . '/includes/bootstrap.php';
require_admin($pdo);

// Strictly parse a date string; null when malformed or not a real date.
$parseDate = static function (mixed $value, string $format): ?DateTimeImmutable {
    if (!is_string($value)) {
        return null;
    }
    $date = DateTimeImmutable::createFromFormat('!' . $format, $value);
    $errors = DateTimeImmutable::getLastErrors();
    if (!$date || ($errors && ($errors['warning_count'] || $errors['error_count'])) || $date->format($format) !== $value) {
        return null;
    }
    return $date;
};

$today = new DateTimeImmutable('today');
$selectedDay = $parseDate($_GET['day'] ?? null, 'Y-m-d');
$requestedMonth = $parseDate($_GET['month'] ?? null, 'Y-m');
if ($selectedDay) {
    $monthStart = $selectedDay->modify('first day of this month');
} else {
    $monthStart = $requestedMonth ?? $today->modify('first day of this month');
    $selectedDay = $monthStart->format('Y-m') === $today->format('Y-m') ? $today : $monthStart;
}

$monthEnd = $monthStart->modify('last day of this month');
$gridStart = $monthStart->modify('monday this week');
$gridEnd = $monthEnd->modify('sunday this week');
$dayQuery = static fn (DateTimeImmutable $date): string => '?day=' . $date->format('Y-m-d');
$monthQuery = static fn (DateTimeImmutable $date): string => '?month=' . $date->format('Y-m');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $movieId = filter_var($_POST['movieID'] ?? null, FILTER_VALIDATE_INT);
    $theaterId = filter_var($_POST['theaterID'] ?? null, FILTER_VALIDATE_INT);
    $price = filter_var($_POST['price'] ?? null, FILTER_VALIDATE_INT);
    $date = $_POST['show_date'] ?? '';
    $time = $_POST['show_time'] ?? '';
    $date = is_string($date) ? $date : '';
    $time = is_string($time) ? $time : '';
    $startsAtDate = DateTimeImmutable::createFromFormat('!Y-m-d H:i', "$date $time");
    $startErrors = DateTimeImmutable::getLastErrors();
    $validStart = $startsAtDate
        && (!$startErrors || ($startErrors['warning_count'] === 0 && $startErrors['error_count'] === 0))
        && $startsAtDate->format('Y-m-d H:i') === "$date $time";
    $startsAt = $validStart ? $startsAtDate->format('Y-m-d H:i:s') : '';
    $errors = [];

    if (!$movieId || !$theaterId || !$price || $price < 1) {
        $errors[] = 'Choose a movie and theater, and enter a ticket price greater than zero.';
    }
    if (!$validStart) {
        $errors[] = 'Choose a valid showtime date and time.';
    }

    if (!$errors) {
        try {
            $exists = $pdo->prepare(
                'SELECT
                    EXISTS(SELECT 1 FROM movies WHERE movieID = ?) AS movie_exists,
                    EXISTS(SELECT 1 FROM theaters WHERE theaterID = ?) AS theater_exists,
                    (? > NOW()) AS is_future'
            );
            $exists->execute([$movieId, $theaterId, $startsAt]);
            $checks = $exists->fetch();

            if (!(int) $checks['movie_exists'] || !(int) $checks['theater_exists']) {
                $errors[] = 'The selected movie or theater is no longer available.';
            } elseif (!(int) $checks['is_future']) {
                $errors[] = 'Showtimes must be scheduled in the future.';
            } else {
                $conflict = $pdo->prepare(
                    'SELECT 1 FROM showtimes WHERE theaterID = ? AND starts_at = ? LIMIT 1'
                );
                $conflict->execute([$theaterId, $startsAt]);
                if ($conflict->fetchColumn()) {
                    $errors[] = 'That theater already has a showtime at this date and time.';
                } else {
                    $insert = $pdo->prepare(
                        'INSERT INTO showtimes (movieID, theaterID, starts_at, price) VALUES (?, ?, ?, ?)'
                    );
                    $insert->execute([$movieId, $theaterId, $startsAt, $price]);
                    flash('success', 'Showtime added to the calendar.');
                    redirect('admin_showtimes.php' . $dayQuery($startsAtDate));
                }
            }
        } catch (PDOException $ex) {
            error_log($ex->getMessage());
            $errors[] = 'Could not add the showtime. Please try again.';
        }
    }

    foreach ($errors as $error) {
        flash('error', $error);
    }
    redirect('admin_showtimes.php' . $dayQuery($validStart ? $startsAtDate : $selectedDay));
}

$movies = $pdo->query('SELECT movieID, title FROM movies ORDER BY title')->fetchAll();
$theaters = $pdo->query('SELECT theaterID, name, city FROM theaters ORDER BY name')->fetchAll();
$showsStmt = $pdo->prepare(
    'SELECT s.showtimeID, s.movieID, s.theaterID, s.starts_at, s.price,
            m.title, t.name AS theater_name, t.city,
            COUNT(b.bookingID) AS booked_seats
     FROM showtimes s
     JOIN movies m ON m.movieID = s.movieID
     JOIN theaters t ON t.theaterID = s.theaterID
     LEFT JOIN bookings b ON b.showtimeID = s.showtimeID
     WHERE s.starts_at >= ? AND s.starts_at < ?
     GROUP BY s.showtimeID, s.movieID, s.theaterID, s.starts_at, s.price,
              m.title, t.name, t.city
     ORDER BY s.starts_at, t.name, m.title'

     /* Fetch showtimes within the specified date range for the calendar view MAKE THIS A COMPONENT FOR IT SELF  */
);
$showsStmt->execute([$gridStart->format('Y-m-d') . ' 00:00:00', $gridEnd->modify('+1 day')->format('Y-m-d') . ' 00:00:00']);
$showsByDate = [];
foreach ($showsStmt->fetchAll() as $show) {
    $showsByDate[substr($show['starts_at'], 0, 10)][] = $show;
}
$selectedKey = $selectedDay->format('Y-m-d');
$movieHue = static fn (int $movieId): int => ($movieId * 67) % 360;
$timePresets = ['12:00', '15:00', '18:00', '21:00'];
$monthNames = [1 => 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

$pageTitle = 'Showtime calendar';
require __DIR__ . '/includes/header.php';
?>
<header class="content-heading showtime-heading">
    <p class="eyebrow">PIXEL CINEMA &bull; ADMIN CONTROL ROOM</p>
    <h1>SHOWTIME CALENDAR</h1>
    <p>Pick a day, see what is playing, and schedule a new screening.</p>
    <p><a href="admin.php">Back to Admin</a></p>
</header>

<div class="showtime-layout">
<section class="panel datepicker" aria-label="Pick a date">
    <div class="datepicker-header">
        <a class="datepicker-nav" href="<?= e($monthQuery($monthStart->modify('-1 month'))) ?>" aria-label="Previous month">&lsaquo;</a>
        <form method="get" class="datepicker-jump">
            <label class="visually-hidden" for="picker-month">Month</label>
            <select id="picker-month" name="m" data-picker-month>
                <?php foreach ($monthNames as $num => $name): ?>
                <option value="<?= $num ?>" <?= (int) $monthStart->format('n') === $num ? 'selected' : '' ?>><?= e($name) ?></option>
                <?php endforeach; ?>
            </select>
            <label class="visually-hidden" for="picker-year">Year</label>
            <select id="picker-year" name="y" data-picker-year>
                <?php for ($yr = (int) $today->format('Y') - 1; $yr <= (int) $today->format('Y') + 3; $yr++): ?>
                <option value="<?= $yr ?>" <?= (int) $monthStart->format('Y') === $yr ? 'selected' : '' ?>><?= $yr ?></option>
                <?php endfor; ?>
            </select>
        </form>
        <a class="datepicker-nav" href="<?= e($monthQuery($monthStart->modify('+1 month'))) ?>" aria-label="Next month">&rsaquo;</a>
    </div>

    <div class="datepicker-weekdays" aria-hidden="true">
        <?php foreach (['Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa', 'Su'] as $wd): ?><span><?= $wd ?></span><?php endforeach; ?>
    </div>

    <div class="datepicker-grid">
        <?php for ($cursor = $gridStart; $cursor <= $gridEnd; $cursor = $cursor->modify('+1 day')):
            $key = $cursor->format('Y-m-d');
            $dayShows = $showsByDate[$key] ?? [];
            $classes = ['datepicker-day'];
            if ($cursor->format('Y-m') !== $monthStart->format('Y-m')) { $classes[] = 'is-outside'; }
            if ($key === $today->format('Y-m-d')) { $classes[] = 'is-today'; }
            if ($key === $selectedKey) { $classes[] = 'is-selected'; }
            if ($dayShows) { $classes[] = 'has-shows'; }
        ?>
        <button type="button" class="<?= e(implode(' ', $classes)) ?>" data-day="<?= e($key) ?>"
                aria-pressed="<?= $key === $selectedKey ? 'true' : 'false' ?>"
                aria-label="<?= e($cursor->format('l, F j')) ?>, <?= count($dayShows) ?> showtime(s)">
            <span class="datepicker-number"><?= e($cursor->format('j')) ?></span>
            <span class="datepicker-dots" aria-hidden="true">
                <?php foreach (array_slice($dayShows, 0, 4) as $dot): ?><i style="--event-hue: <?= $movieHue((int) $dot['movieID']) ?>"></i><?php endforeach; ?>
            </span>
        </button>
        <?php endfor; ?>
    </div>

    <div class="datepicker-footer">
        <a class="btn small" href="<?= e($dayQuery($today)) ?>">Today</a>
        <span class="muted"><span class="datepicker-legend" aria-hidden="true"></span> has showtimes</span>
    </div>
</section>

<div class="showtime-side">
<section class="panel day-panel" aria-live="polite">
    <?php for ($cursor = $gridStart; $cursor <= $gridEnd; $cursor = $cursor->modify('+1 day')):
        $key = $cursor->format('Y-m-d');
        $dayShows = $showsByDate[$key] ?? []; ?>
    <details class="day-detail" data-day-panel="<?= e($key) ?>" <?= $key === $selectedKey ? '' : 'hidden' ?>>
        <summary class="day-detail-summary">
            <div>
                <span class="eyebrow"><?= e(strtoupper($cursor->format('l'))) ?></span>
                <h2><?= e($cursor->format('F j, Y')) ?></h2>
            </div>
            <span class="day-detail-count"><?= count($dayShows) ?> showtime<?= count($dayShows) === 1 ? '' : 's' ?></span>
        </summary>
        <?php if (!$dayShows): ?>
            <p class="day-empty">No showtimes yet. Add the first one below.</p>
        <?php endif; ?>
        <ul class="day-list">
            <?php foreach ($dayShows as $show): ?>
            <li class="day-show" style="--event-hue: <?= $movieHue((int) $show['movieID']) ?>">
                <time datetime="<?= e(date('c', strtotime($show['starts_at']))) ?>"><?= e(date('H:i', strtotime($show['starts_at']))) ?></time>
                <div>
                    <strong><?= e($show['title']) ?></strong>
                    <span><?= e($show['theater_name']) ?> &bull; <?= e($show['city']) ?></span>
                </div>
                <span class="day-show-meta"><?= (int) $show['price'] ?> kr<br><?= (int) $show['booked_seats'] ?> booked</span>
            </li>
            <?php endforeach; ?>
        </ul>
    </details>
    <?php endfor; ?>
</section>

<section class="panel showtime-form-panel" id="add-showtime">
    <div class="showtime-form-heading">
        <span class="showtime-form-icon" aria-hidden="true">&#9733;</span>
        <div>
            <p class="eyebrow">SCHEDULE A SCREENING</p>
            <h2>ADD SHOWTIME</h2>
        </div>
    </div>
    <?php if (!$movies || !$theaters): ?>
        <p class="muted">Add at least one movie and one theater before scheduling a showtime.</p>
    <?php else: ?>
    <form method="post" class="showtime-form">
        <?= csrf_field() ?>
        <label>Movie
            <select name="movieID" required>
                <option value="">Choose a movie</option>
                <?php foreach ($movies as $movie): ?>
                <option value="<?= (int) $movie['movieID'] ?>"><?= e($movie['title']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Theater
            <select name="theaterID" required>
                <option value="">Choose a theater</option>
                <?php foreach ($theaters as $theater): ?>
                <option value="<?= (int) $theater['theaterID'] ?>"><?= e($theater['name'] . ' - ' . $theater['city']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <input type="hidden" name="show_date" value="<?= e($selectedKey) ?>" data-show-date>
        <div class="showtime-form-date">
            <span>Date</span>
            <strong data-show-date-label><?= e($selectedDay->format('D, M j Y')) ?></strong>
        </div>
        <label>Start time
            <input type="time" name="show_time" value="18:00" required>
            <span class="time-presets">
                <?php foreach ($timePresets as $preset): ?>
                <button class="time-chip" type="button" data-time="<?= $preset ?>"><?= $preset ?></button>
                <?php endforeach; ?>
            </span>
        </label>
        <label>Ticket price (kr)
            <input type="number" name="price" value="90" min="1" step="1" required>
        </label>
        <button class="btn showtime-submit" type="submit">Add to calendar</button>
    </form>
    <?php endif; ?>
</section>
</div>
</div>
<script src="styling/admin_showtimes.js" defer></script>
<?php require __DIR__ . '/includes/footer.php'; ?>
