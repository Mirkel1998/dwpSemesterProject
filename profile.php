<?php
require_once __DIR__ . '/includes/bootstrap.php';
$user = require_login($pdo);

$settingsErrors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

$emailStmt = $pdo->prepare('SELECT email FROM users WHERE userID = ?');
$emailStmt->execute([$user['userID']]);
$currentEmail = (string) $emailStmt->fetchColumn();

$lvl = (int) $user['level'];
$base = Gamification::xpForLevel($lvl);
$next = Gamification::xpForLevel($lvl + 1);
$pct = (int) round(((int) $user['xp'] - $base) / ($next - $base) * 100);

$achievements = $pdo->prepare(
    'SELECT a.name, a.description, a.xp_reward, ua.unlocked_at
     FROM achievements a
     LEFT JOIN user_achievements ua ON ua.achievementID = a.achievementID AND ua.userID = ?
     ORDER BY a.achievementID'
);
$achievements->execute([$user['userID']]);
$achievements = $achievements->fetchAll();

$bookings = $pdo->prepare(
    'SELECT m.title, s.starts_at, t.name, b.seat_row, b.seat_number, b.price_paid
     FROM bookings b
     JOIN showtimes s ON s.showtimeID = b.showtimeID
     JOIN movies m ON m.movieID = s.movieID
     JOIN theaters t ON t.theaterID = s.theaterID
     WHERE b.userID = ?
     ORDER BY s.starts_at DESC, b.seat_row, b.seat_number'
);
$bookings->execute([$user['userID']]);
$bookings = $bookings->fetchAll();

$log = $pdo->prepare('SELECT amount, reason, created_at FROM xp_log WHERE userID = ? ORDER BY logID DESC LIMIT 10');
$log->execute([$user['userID']]);
$log = $log->fetchAll();

$pageTitle = 'Profile';
require __DIR__ . '/includes/header.php';
?>
<h1><?= e($user['username']) ?></h1>
<section class="panel">
    <h2>LV <?= $lvl ?> &ndash; <?= e(Gamification::title($lvl)) ?></h2>
    <div class="xpbar"><span style="width:<?= $pct ?>%"></span></div>
    <p><?= (int) $user['xp'] ?> / <?= $next ?> XP &bull; Login streak: <?= (int) $user['login_streak'] ?> day(s) &bull; Ticket discount: <?= Gamification::discountPercent($lvl) ?>%</p>
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
            <td><?= e($b['name']) ?></td>
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
<?php require __DIR__ . '/includes/footer.php'; ?>
