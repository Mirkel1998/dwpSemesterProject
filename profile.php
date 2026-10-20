<?php
// Signed-in account page: process email/password changes, then load the user's
// progress, achievements, tickets, and recent XP history for display.
require_once __DIR__ . '/includes/bootstrap.php';
$user = require_login($pdo);
$ticketsOnly = ($_GET['view'] ?? '') === 'tickets';

$settingsErrors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$ticketsOnly) {
    // Require the current password for either account-setting update.
    csrf_check();
    $email = trim((string) ($_POST['email'] ?? ''));
    $newPassword = (string) ($_POST['password'] ?? '');
    $current = (string) ($_POST['current_password'] ?? '');

    $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE userID = ?');
    $stmt->execute([$user['userID']]);
    if (!password_verify($current, (string) $stmt->fetchColumn())) {
        $settingsErrors[] = 'Current password is wrong.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
        $settingsErrors[] = 'Enter a valid email.';
    }
    if ($newPassword !== '' && strlen($newPassword) < 8) {
        $settingsErrors[] = 'New password must be at least 8 characters.';
    }

    if (!$settingsErrors) {
        try {
            // Change the password only when a new one was supplied.
            $pdo->prepare('UPDATE users SET email = ? WHERE userID = ?')->execute([$email, $user['userID']]);
            if ($newPassword !== '') {
                $pdo->prepare('UPDATE users SET password_hash = ? WHERE userID = ?')
                    ->execute([password_hash($newPassword, PASSWORD_DEFAULT), $user['userID']]);
            }
            flash('success', 'Account updated.');
            redirect('profile.php');
        } catch (PDOException $ex) {
            $settingsErrors[] = $ex->getCode() === '23000' ? 'Email already in use.' : 'Could not update account.';
        }
    }
}

$lvl = (int) $user['level'];
$base = LevelRules::xpForLevel($lvl);
$next = LevelRules::xpForLevel($lvl + 1);
$pct = (int) round(((int) $user['xp'] - $base) / ($next - $base) * 100);

if (!$ticketsOnly) {
    $emailStmt = $pdo->prepare('SELECT email FROM users WHERE userID = ?');
    $emailStmt->execute([$user['userID']]);
    $currentEmail = (string) $emailStmt->fetchColumn();

    $achievements = $pdo->prepare(
        'SELECT a.name, a.description, a.xp_reward, ua.unlocked_at
         FROM achievements a
         LEFT JOIN user_achievements ua ON ua.achievementID = a.achievementID AND ua.userID = ?
         ORDER BY a.achievementID'
    );
    $achievements->execute([$user['userID']]);
    $achievements = $achievements->fetchAll();
}

$bookings = $pdo->prepare(
    'SELECT m.title, s.starts_at, s.showtimeID, t.name, t.city, b.seat_row, b.seat_number,
            b.price_paid, b.created_at, b.booking_ref
     FROM bookings b
     JOIN showtimes s ON s.showtimeID = b.showtimeID
     JOIN movies m ON m.movieID = s.movieID
     JOIN theaters t ON t.theaterID = s.theaterID
     WHERE b.userID = ?
     ORDER BY b.created_at DESC, b.bookingID DESC'
);
$bookings->execute([$user['userID']]);
$bookings = $bookings->fetchAll();
$newestBookingRef = $bookings ? $bookings[0]['booking_ref'] : null;
$newestBookingTime = $bookings && $newestBookingRef === null ? $bookings[0]['created_at'] : null;
$newestShowtimeId = $bookings && $newestBookingRef === null ? (int) $bookings[0]['showtimeID'] : null;

if (!$ticketsOnly) {
    $log = $pdo->prepare('SELECT amount, reason, created_at FROM xp_log WHERE userID = ? ORDER BY logID DESC LIMIT 10');
    $log->execute([$user['userID']]);
    $log = $log->fetchAll();
}

$pageTitle = $ticketsOnly ? 'My Tickets' : 'Profile';
require __DIR__ . '/includes/header.php';
?>
<?php if ($ticketsOnly): ?>
<header class="ticket-page-heading">
    <p class="eyebrow">PIXEL CINEMA &bull; BOOKING CONFIRMED</p>
    <div class="ticket-user">
        <h1><?= e($user['username']) ?></h1>
        <span class="ticket-level">LEVEL <?= $lvl ?> &bull; <?= e(LevelRules::title($lvl)) ?></span>
    </div>
</header>
<section class="panel my-tickets-panel">
    <h2>MY TICKETS</h2>
    <?php if (!$bookings): ?>
        <p>No tickets yet. <a href="index.php">Browse films</a></p>
    <?php else: ?>
    <div class="ticket-list">
        <?php foreach ($bookings as $b): ?>
        <?php
            $isNewestTicket = $newestBookingRef !== null
                ? $b['booking_ref'] === $newestBookingRef
                : ($b['booking_ref'] === null
                    && $b['created_at'] === $newestBookingTime
                    && (int) $b['showtimeID'] === $newestShowtimeId);
        ?>
        <article class="cinema-ticket <?= $isNewestTicket ? 'is-newest' : '' ?>">
            <div class="cinema-ticket-main">
                <?php if ($isNewestTicket): ?><span class="newest-ticket-label">NEWEST BOOKING</span><?php endif; ?>
                <h3><?= e($b['title']) ?></h3>
                <p><?= e($b['name']) ?> &bull; <?= e($b['city']) ?></p>
                <time datetime="<?= e(date('c', strtotime($b['starts_at']))) ?>"><?= e(date('D j M, H:i', strtotime($b['starts_at']))) ?></time>
            </div>
            <div class="cinema-ticket-seat">
                <span>YOUR SEAT</span>
                <strong><?= chr(64 + (int) $b['seat_row']) ?><?= (int) $b['seat_number'] ?></strong>
            </div>
            <div class="cinema-ticket-price"><?= (int) $b['price_paid'] ?> kr</div>
        </article>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</section>
<?php else: ?>
<h1><?= e($user['username']) ?></h1>
<section class="panel">
    <h2>LV <?= $lvl ?> &ndash; <?= e(LevelRules::title($lvl)) ?></h2>
    <div class="xpbar"><span style="width:<?= $pct ?>%"></span></div>
    <p><?= (int) $user['xp'] ?> / <?= $next ?> XP &bull; Login streak: <?= (int) $user['login_streak'] ?> day(s) &bull; Ticket discount: <?= LevelRules::discountPercent($lvl) ?>%</p>
</section>

<section class="panel">
    <h2>ACHIEVEMENTS</h2>
    <div class="grid small">
    <?php foreach ($achievements as $a): ?>
        <div class="badge <?= $a['unlocked_at'] ? 'on' : 'off' ?>">
            <strong><?= e($a['name']) ?></strong>
            <p><?= e($a['description']) ?></p>
            <p class="muted"><?= $a['unlocked_at'] ? 'UNLOCKED' : 'LOCKED' ?> &bull; +<?= (int) $a['xp_reward'] ?> XP</p>
        </div>
    <?php endforeach; ?>
    </div>
</section>

<section class="panel">
    <h2>MY TICKETS</h2>
    <?php if (!$bookings): ?><p>No tickets yet. <a href="index.php">Pick a movie.</a></p><?php endif; ?>
    <table>
    <?php foreach ($bookings as $b): ?>
        <tr>
            <td><?= e($b['title']) ?></td>
            <td><?= e(date('D j M, H:i', strtotime($b['starts_at']))) ?></td>
            <td><?= e($b['name']) ?>, <?= e($b['city']) ?></td>
            <td>Seat <?= chr(64 + (int) $b['seat_row']) . (int) $b['seat_number'] ?></td>
            <td><?= (int) $b['price_paid'] ?> kr</td>
        </tr>
    <?php endforeach; ?>
    </table>
</section>

<section class="panel">
    <h2>XP LOG</h2>
    <table>
    <?php foreach ($log as $l): ?>
        <tr><td>+<?= (int) $l['amount'] ?> XP</td><td><?= e($l['reason']) ?></td><td class="muted"><?= e($l['created_at']) ?></td></tr>
    <?php endforeach; ?>
    </table>
</section>
<section class="panel">
    <h2>ACCOUNT SETTINGS</h2>
    <?php foreach ($settingsErrors as $err): ?><div class="flash error"><?= e($err) ?></div><?php endforeach; ?>
    <form method="post" class="narrow">
        <?= csrf_field() ?>
        <label>Email <input type="email" name="email" value="<?= e($currentEmail) ?>" required></label>
        <label>New password <input type="password" name="password" minlength="8" placeholder="blank = keep"></label>
        <label>Current password <input type="password" name="current_password" required></label>
        <button class="btn" type="submit">Save</button>
    </form>
</section>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
