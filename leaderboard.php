<?php
require_once __DIR__ . '/includes/bootstrap.php';

$top = $pdo->query('SELECT username, xp, level FROM users ORDER BY xp DESC, userID LIMIT 10')->fetchAll();

$pageTitle = 'Ranks';
require __DIR__ . '/includes/header.php';
?>
<h1>HIGH SCORES</h1>
<section class="panel">
    <table>
    <?php foreach ($top as $i => $row): ?>
        <tr>
            <td><?= $i + 1 ?>.</td>
            <td><?= e($row['username']) ?></td>
            <td>LV <?= (int) $row['level'] ?> <span class="muted"><?= e(Gamification::title((int) $row['level'])) ?></span></td>
            <td><?= (int) $row['xp'] ?> XP</td>
        </tr>
    <?php endforeach; ?>
    </table>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
