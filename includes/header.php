<?php

// Shared page opening: expects $pdo from bootstrap.php and optional $pageTitle.
// It reads the signed-in user and queued flash messages for the navigation bar.
$navUser = current_user($pdo);
$flashes = pull_flashes();
$mascotPage = basename($_SERVER['SCRIPT_NAME'] ?? 'index.php', '.php');
if ($mascotPage === 'profile' && ($_GET['view'] ?? '') === 'tickets') {
    $mascotPage = 'tickets';
}
$mascotCounts = [
    'index' => 0,
    'movies' => 1,
    'news' => 2,
    'faq' => 1,
    'contact' => 2,
    'contact_messages' => 3,
    'admin_showtimes' => 2,
    'movie' => 2,
    'book' => 3,
    'profile' => 2,
    'tickets' => 1,
    'login' => 1,
    'register' => 2,
    'leaderboard' => 3,
    'admin' => 1,
    'admin_user' => 2,
];
$mascotCount = $mascotCounts[$mascotPage] ?? 1;
?>
<!-- Site-wide navigation, including account links and the user's XP progress. -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle ?? 'Pixel Cinema') ?> - Pixel Cinema</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&family=VT323&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styling/pixelArt.css">
</head>
<body class="page-<?= e($mascotPage) ?>">
<header class="topbar">
    <a class="logo" href="index.php">PIXEL CINEMA</a>
    <nav>
        <a href="index.php">Home</a>
        <a href="movies.php">Movies</a>
        <a href="<?= $navUser ? 'profile.php' : 'login.php' ?>">My Account</a>
        <?php if ($navUser && $navUser['role'] === 'admin'): ?><a href="admin.php">Admin</a><?php endif; ?>
        <a href="news.php">News</a>
        <a href="faq.php">FAQ</a>
        <a href="contact.php">Contact</a>
        <?php if ($navUser):
            $lvl = (int) $navUser['level'];
            $base = Gamification::xpForLevel($lvl);
            $next = Gamification::xpForLevel($lvl + 1);
            $pct = (int) round(((int) $navUser['xp'] - $base) / ($next - $base) * 100);
            ?>
            <span class="xpbar small" title="<?= (int) $navUser['xp'] ?> / <?= $next ?> XP"><span style="width:<?= $pct ?>%"></span></span>
            <form method="post" action="logout.php" class="inline">
                <?= csrf_field() ?>
                <button class="btn small" type="submit">Logout</button>
            </form>
        <?php endif; ?>
    </nav>
</header>
<main>
<!-- One-time status, error, and game-reward messages are shown above page content. -->
<?php foreach ($flashes as $f): ?>
    <div class="flash <?= e($f['type']) ?>"><?= e($f['message']) ?></div>
<?php endforeach; ?>
