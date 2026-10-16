<?php
// Admin dashboard: require administrator access, list user accounts, and
// handle account deletion requests before rendering the user-management table.
require_once __DIR__ . '/includes/bootstrap.php';
$admin = require_admin($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $targetId = filter_input(INPUT_POST, 'userID', FILTER_VALIDATE_INT);

    if (!$targetId) {
        flash('error', 'Invalid user.');
    } elseif ($targetId === (int) $admin['userID']) {
        flash('error', 'You cannot delete your own account here.');
    } else {
        try {
            // Remove the user's dependent records before deleting their account.
            $pdo->beginTransaction();
            foreach (['bookings', 'xp_log', 'user_achievements'] as $table) {
                $pdo->prepare("DELETE FROM $table WHERE userID = ?")->execute([$targetId]);
            }
            $pdo->prepare('DELETE FROM users WHERE userID = ?')->execute([$targetId]);
            $pdo->commit();
            flash('success', 'User deleted.');
        } catch (PDOException $ex) {
            $pdo->rollBack();
            error_log($ex->getMessage());
            flash('error', 'Could not delete user.');
        }
    }
    redirect('admin.php');
}

// Fetch public account fields only; password hashes are never displayed here.
$users = $pdo->query(
    'SELECT userID, username, email, role, xp, level, created_at FROM users ORDER BY userID'
)->fetchAll();

$pageTitle = 'Admin';
require __DIR__ . '/includes/header.php';
?>
<h1>ADMIN PANEL</h1>
<a class="btn" href="admin_user.php">+ New user</a>
<a class="btn" href="contact_messages.php">Contact messages</a>
<section class="panel">
    <table>
        <tr><td>ID</td><td>User</td><td>Email</td><td>Role</td><td>LV / XP</td><td></td></tr>
        <?php foreach ($users as $u): ?>
        <tr>
            <td><?= (int) $u['userID'] ?></td>
            <td><?= e($u['username']) ?></td>
            <td><?= e($u['email']) ?></td>
            <td><?= e($u['role']) ?></td>
            <td><?= (int) $u['level'] ?> / <?= (int) $u['xp'] ?></td>
            <td>
                <a class="btn small" href="admin_user.php?id=<?= (int) $u['userID'] ?>">Edit</a>
                <?php if ((int) $u['userID'] !== (int) $admin['userID']): ?>
                <form method="post" class="inline" onsubmit="return confirm('Delete this user and all their bookings?')">
                    <?= csrf_field() ?>
                    <input type="hidden" name="userID" value="<?= (int) $u['userID'] ?>">
                    <button class="btn small" type="submit">Delete</button>
                </form>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
