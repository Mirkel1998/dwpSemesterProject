<?php
// Fake payment page: shows a checkout form that processes no card data. Confirming
// saves the held seats and awards the same booking rewards as before.
require_once __DIR__ . '/includes/bootstrap.php';
$user = require_login($pdo);

$pending = $_SESSION['pending_booking'] ?? null;
if (!$pending || empty($pending['seats'])) {
    flash('error', 'Your seat selection has expired. Pick your seats again.');
    redirect('movies.php');
}

$stmt = $pdo->prepare(
    'SELECT s.showtimeID, s.starts_at, s.price, m.title, t.name, t.city
     FROM showtimes s
     JOIN movies m ON m.movieID = s.movieID
     JOIN theaters t ON t.theaterID = s.theaterID
     WHERE s.showtimeID = ? AND s.starts_at > NOW()'
);
$stmt->execute([$pending['showtimeID']]);
$show = $stmt->fetch();
if (!$show) {
    unset($_SESSION['pending_booking']);
    flash('error', 'That showing has already started or no longer exists.');
    redirect('movies.php');
}

$seats = $pending['seats'];
$discount = LevelRules::discountPercent((int) $user['level']);
$price = (int) round($show['price'] * (100 - $discount) / 100);
$total = count($seats) * $price;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    try {
        // Save every seat and award the booking rewards atomically.
        $pdo->beginTransaction();
        $bookingRef = bin2hex(random_bytes(16));
        $insert = $pdo->prepare(
            'INSERT INTO bookings (userID, showtimeID, booking_ref, seat_row, seat_number, price_paid)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        foreach ($seats as [$r, $n]) {
            $insert->execute([$user['userID'], $show['showtimeID'], $bookingRef, $r, $n, $price]);
        }
        Gamification::onBooking($pdo, (int) $user['userID'], count($seats), $show['starts_at']);
        $pdo->commit();
        unset($_SESSION['pending_booking']);
        flash('success', 'Booked ' . count($seats) . ' seat(s) for ' . $show['title'] . ' - ' . $total . ' kr.');
        redirect('profile.php?view=tickets');
    } catch (PDOException $ex) {
        $pdo->rollBack();
        // Discard flashes queued by the rolled-back transaction.
        pull_flashes();
        if ($ex->getCode() === '23000') {
            unset($_SESSION['pending_booking']);
            flash('error', 'Someone just took one of those seats. Pick again.');
        } else {
            error_log($ex->getMessage());
            flash('error', 'Booking failed. Try again.');
        }
        redirect('book.php?showtime=' . (int) $show['showtimeID']);
    }
}

$seatLabels = array_map(fn($s) => chr(64 + $s[0]) . $s[1], $seats);

$pageTitle = 'Payment - ' . $show['title'];
require __DIR__ . '/includes/header.php';
?>
<header class="booking-intro">
    <p class="eyebrow">PIXEL CINEMA &bull; CHECKOUT</p>
    <h1>Payment</h1>
    <p class="muted">Secure checkout &bull; encrypted connection</p>
</header>

<div class="pay-layout">
    <section class="panel pay-summary" aria-labelledby="order-summary">
        <h2 id="order-summary">Order summary</h2>
        <dl>
            <dt>Movie</dt><dd><?= e($show['title']) ?></dd>
            <dt>Theater</dt><dd><?= e($show['name']) ?>, <?= e($show['city']) ?></dd>
            <dt>Showing</dt><dd><?= e(date('D j M, H:i', strtotime($show['starts_at']))) ?></dd>
            <dt>Seats</dt><dd><?= e(implode(', ', $seatLabels)) ?></dd>
            <dt>Tickets</dt><dd><?= count($seats) ?> &times; <?= $price ?> kr<?= $discount ? " ($discount% member discount)" : '' ?></dd>
        </dl>
        <p class="pay-total">Total <strong><?= $total ?> kr</strong></p>
    </section>

    <form method="post" class="panel pay-form" autocomplete="off">
        <?= csrf_field() ?>
        <h2>Card details</h2>
        <label>Cardholder name
            <input type="text" name="card_name" required placeholder="Name on card">
        </label>
        <label>Card number
            <input type="text" name="card_number" required inputmode="numeric" placeholder="1234 5678 9012 3456" maxlength="19">
        </label>
        <div class="pay-row">
            <label>Expiry
                <input type="text" name="card_expiry" required placeholder="MM/YY" maxlength="5">
            </label>
            <label>CVC
                <input type="text" name="card_cvc" required inputmode="numeric" placeholder="123" maxlength="4">
            </label>
        </div>
        <div class="pay-actions">
            <a class="btn small" href="book.php?showtime=<?= (int) $show['showtimeID'] ?>">Back to seats</a>
            <button class="btn" type="submit">Pay <?= $total ?> kr</button>
        </div>
        <p class="muted pay-note">Your card is charged once you confirm.</p>
    </form>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
