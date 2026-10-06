<?php
// Expects $pdo (from bootstrap.php) and optional $pageTitle.
$navUser = current_user($pdo);
$flashes = pull_flashes();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle ?? 'Pixel Cinema') ?> - Pixel Cinema</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/pixel.css">
</head>
<body>
<header class="topbar">
    <a class="logo" href="index.php">PIXEL CINEMA</a>
    <nav>
        <a href="index.php">Movies</a>
        <a href="leaderboard.php">Ranks</a>
        <?php if ($navUser):
            $lvl = (int) $navUser['level'];
            $base = Gamification::xpForLevel($lvl);
            $next = Gamification::xpForLevel($lvl + 1);
            $pct = (int) round(((int) $navUser['xp'] - $base) / ($next - $base) * 100);
            ?>
            <?php if ($navUser['role'] === 'admin'): ?><a href="admin.php">Admin</a><?php endif; ?>
            <a href="profile.php"><?= e($navUser['username']) ?> LV<?= $lvl ?></a>
            <span class="xpbar small" title="<?= (int) $navUser['xp'] ?> / <?= $next ?> XP"><span style="width:<?= $pct ?>%"></span></span>
            <form method="post" action="logout.php" class="inline">
                <?= csrf_field() ?>
                <button class="btn small" type="submit">Logout</button>
            </form>
        <?php else: ?>
            <a href="login.php">Login</a>
            <a href="register.php">Join</a>
        <?php endif; ?>
    </nav>
</header>
<main>
<?php foreach ($flashes as $f): ?>
    <div class="flash <?= e($f['type']) ?>"><?= e($f['message']) ?></div>
<?php endforeach; ?>
