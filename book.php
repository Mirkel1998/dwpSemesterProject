<?php
require_once __DIR__ . '/includes/bootstrap.php';
$user = require_login($pdo);

const MAX_SEATS = 8;

$id = filter_input(INPUT_GET, 'showtime', FILTER_VALIDATE_INT) ?: filter_input(INPUT_POST, 'showtime', FILTER_VALIDATE_INT);
$stmt = $pdo->prepare(
    'SELECT s.showtimeID, s.starts_at, s.price, m.title, t.name, t.city, t.seat_rows, t.seats_per_row
     FROM showtimes s
     JOIN movies m ON m.movieID = s.movieID
     JOIN theaters t ON t.theaterID = s.theaterID
     WHERE s.showtimeID = ? AND s.starts_at > NOW()'
);
$stmt->execute([$id]);
$show = $stmt->fetch();
if (!$show) {
    http_response_code(404);
    exit('Showing not found or already started.');
}

$discount = Gamification::discountPercent((int) $user['level']);
$price = (int) round($show['price'] * (100 - $discount) / 100);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $seats = [];
    foreach ((array) ($_POST['seats'] ?? []) as $raw) {
        if (is_string($raw) && preg_match('/^(\d{1,2})-(\d{1,2})$/', $raw, $mm)) {
            $r = (int) $mm[1];
            $n = (int) $mm[2];
            if ($r >= 1 && $r <= $show['seat_rows'] && $n >= 1 && $n <= $show['seats_per_row']) {
                $seats["$r-$n"] = [$r, $n];
            }
        }
    }

    if (!$seats) {
        $errors[] = 'Pick at least one seat.';
    } elseif (count($seats) > MAX_SEATS) {
        $errors[] = 'Max ' . MAX_SEATS . ' seats per booking.';
    } else {
        try {
            $pdo->beginTransaction();
            $insert = $pdo->prepare(
                'INSERT INTO bookings (userID, showtimeID, seat_row, seat_number, price_paid) VALUES (?, ?, ?, ?, ?)'
            );
            foreach ($seats as [$r, $n]) {
                $insert->execute([$user['userID'], $show['showtimeID'], $r, $n, $price]);
            }
            Gamification::onBooking($pdo, (int) $user['userID'], count($seats), $show['starts_at']);
            $pdo->commit();
            flash('success', 'Booked ' . count($seats) . ' seat(s) for ' . $show['title'] . ' - ' . count($seats) * $price . ' kr.');
            redirect('profile.php');
        } catch (PDOException $ex) {
            $pdo->rollBack();
            // Discard flashes queued by the rolled-back transaction.
            pull_flashes();
            if ($ex->getCode() === '23000') {
                $errors[] = 'Someone just took one of those seats. Pick again.';
            } else {
                error_log($ex->getMessage());
                $errors[] = 'Booking failed. Try again.';
            }
        }
    }
}

$takenStmt = $pdo->prepare('SELECT seat_row, seat_number FROM bookings WHERE showtimeID = ?');
$takenStmt->execute([$show['showtimeID']]);
$taken = [];
foreach ($takenStmt as $row) {
    $taken[$row['seat_row'] . '-' . $row['seat_number']] = true;
}

$pageTitle = 'Book ' . $show['title'];
require __DIR__ . '/includes/header.php';
?>
<h1>BOOK: <?= e($show['title']) ?></h1>
<p class="muted"><?= e($show['name']) ?>, <?= e($show['city']) ?> &bull; <?= e(date('D j M, H:i', strtotime($show['starts_at']))) ?></p>
<p>Ticket: <?= $price ?> kr<?= $discount ? " ($discount% level discount)" : '' ?>. Earn 50 XP + 10 per seat.</p>
<?php foreach ($errors as $err): ?><div class="flash error"><?= e($err) ?></div><?php endforeach; ?>

<form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="showtime" value="<?= (int) $show['showtimeID'] ?>">
    <div class="screen">SCREEN</div>
    <div class="seats">
    <?php for ($r = 1; $r <= $show['seat_rows']; $r++): ?>
        <div class="seatrow">
            <span class="rowlabel"><?= chr(64 + $r) ?></span>
            <?php for ($n = 1; $n <= $show['seats_per_row']; $n++):
                $key = "$r-$n"; $isTaken = isset($taken[$key]); ?>
                <label class="seat <?= $isTaken ? 'taken' : '' ?>">
                    <input type="checkbox" name="seats[]" value="<?= $key ?>" <?= $isTaken ? 'disabled' : '' ?>>
                    <span><?= $n ?></span>
                </label>
            <?php endfor; ?>
        </div>
    <?php endfor; ?>
    </div>
    <button class="btn" type="submit">Confirm booking</button>
</form>
<?php require __DIR__ . '/includes/footer.php'; ?>
