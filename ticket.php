<?php
// Digital ticket page: shows one booking's movie, theater, seats, and holder.
// Only the signed-in user's own bookings are visible; anything else is a 404.
require_once __DIR__ . '/includes/bootstrap.php';
$user = require_login($pdo);

$bookingId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$stmt = $pdo->prepare(
    'SELECT b.bookingID, b.booking_ref, b.showtimeID, m.title, s.starts_at, t.name, t.city
     FROM bookings b
     JOIN showtimes s ON s.showtimeID = b.showtimeID
     JOIN movies m ON m.movieID = s.movieID
     JOIN theaters t ON t.theaterID = s.theaterID
     WHERE b.bookingID = ? AND b.userID = ?'
);
$stmt->execute([$bookingId, $user['userID']]);
$ticket = $stmt->fetch();
if (!$ticket) {
    http_response_code(404);
    exit('Ticket not found.');
}

// Seats bought in the same order share a booking_ref, so show them together.
// Older rows without a reference only match themselves.
$seatsStmt = $pdo->prepare(
    'SELECT seat_row, seat_number, price_paid
     FROM bookings
     WHERE userID = ? AND showtimeID = ? AND (booking_ref = ? OR bookingID = ?)
     ORDER BY seat_row, seat_number'
);
$seatsStmt->execute([$user['userID'], $ticket['showtimeID'], $ticket['booking_ref'], $ticket['bookingID']]);
$seats = $seatsStmt->fetchAll();

$seatLabels = array_map(
    static fn (array $seat): string => chr(64 + (int) $seat['seat_row']) . (int) $seat['seat_number'],
    $seats
);
$total = array_sum(array_map(static fn (array $seat): int => (int) $seat['price_paid'], $seats));
$reference = $ticket['booking_ref'] ?? 'PC-' . str_pad((string) $ticket['bookingID'], 6, '0', STR_PAD_LEFT);
$startsAt = strtotime($ticket['starts_at']);

// A decorative barcode derived from the reference, so each ticket looks distinct.
$barcodeBars = [];
foreach (str_split(md5($reference)) as $char) {
    $barcodeBars[] = hexdec($char) % 3 + 1;
}

$pageTitle = 'Ticket: ' . $ticket['title'];
require __DIR__ . '/includes/header.php';
?>
<section class="digital-ticket-page">
    <article class="digital-ticket">
        <header class="digital-ticket-head">
            <p class="eyebrow">PIXEL CINEMA &bull; ONLINE TICKET</p>
            <h1><?= e($ticket['title']) ?></h1>
            <p><?= e($ticket['name']) ?> &bull; <?= e($ticket['city']) ?></p>
        </header>

        <dl class="digital-ticket-info">
            <div>
                <dt>DATE</dt>
                <dd><time datetime="<?= e(date('c', $startsAt)) ?>"><?= e(date('l j F Y', $startsAt)) ?></time></dd>
            </div>
            <div>
                <dt>SHOWTIME</dt>
                <dd><?= e(date('H:i', $startsAt)) ?></dd>
            </div>
            <div>
                <dt>SEATS</dt>
                <dd><?= e(implode(', ', $seatLabels)) ?></dd>
            </div>
            <div>
                <dt>TICKETS</dt>
                <dd><?= count($seats) ?></dd>
            </div>
            <div>
                <dt>TICKET HOLDER</dt>
                <dd><?= e($user['username']) ?></dd>
            </div>
            <div>
                <dt>PAID</dt>
                <dd><?= $total ?> kr</dd>
            </div>
        </dl>

        <div class="digital-ticket-stub">
            <div class="digital-ticket-ref">
                <span>BOOKING REFERENCE</span>
                <strong><?= e($reference) ?></strong>
            </div>
            <div class="digital-ticket-barcode" aria-hidden="true">
                <?php foreach ($barcodeBars as $width): ?>
                <span style="width:<?= $width * 2 ?>px"></span>
                <?php endforeach; ?>
            </div>
            <p class="digital-ticket-note">Show this ticket at the entrance.</p>
        </div>
    </article>
    <p class="digital-ticket-back"><a href="profile.php?view=tickets">&larr; Back to my tickets</a></p>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
