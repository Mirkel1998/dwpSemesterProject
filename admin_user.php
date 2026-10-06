<?php
require_once __DIR__ . '/includes/bootstrap.php';
$admin = require_admin($pdo);

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$editing = null;
if ($id) {
    $stmt = $pdo->prepare('SELECT userID, username, email, role, xp FROM users WHERE userID = ?');
    $stmt->execute([$id]);
    $editing = $stmt->fetch();
    if (!$editing) {
        http_response_code(404);
        exit('User not found.');
    }
}
$isSelf = $editing && (int) $editing['userID'] === (int) $admin['userID'];

$errors = [];
$form = [
    'username' => $editing['username'] ?? '',
    'email' => $editing['email'] ?? '',
    'role' => $editing['role'] ?? 'user',
    'xp' => $editing['xp'] ?? 0,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $form['username'] = trim((string) ($_POST['username'] ?? ''));
    $form['email'] = trim((string) ($_POST['email'] ?? ''));
    $form['role'] = $isSelf ? 'admin' : (($_POST['role'] ?? '') === 'admin' ? 'admin' : 'user');
    $form['xp'] = filter_var($_POST['xp'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 10000000]]);
    $password = (string) ($_POST['password'] ?? '');

    if (!preg_match('/^[A-Za-z0-9_]{3,20}$/', $form['username'])) {
        $errors[] = 'Username: 3-20 letters, numbers or underscores.';
    }
    if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL) || strlen($form['email']) > 255) {
        $errors[] = 'Enter a valid email.';
    }
    if ($form['xp'] === false) {
        $errors[] = 'XP must be a non-negative number.';
    }
    if (($password !== '' || !$editing) && strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters' . ($editing ? ' (leave blank to keep).' : '.');
    }

    if (!$errors) {
        try {
            $level = Gamification::levelForXp((int) $form['xp']);
            if ($editing) {
                $pdo->prepare('UPDATE users SET username = ?, email = ?, role = ?, xp = ?, level = ? WHERE userID = ?')
                    ->execute([$form['username'], $form['email'], $form['role'], $form['xp'], $level, $id]);
                if ($password !== '') {
                    $pdo->prepare('UPDATE users SET password_hash = ? WHERE userID = ?')
                        ->execute([password_hash($password, PASSWORD_DEFAULT), $id]);
                }
            } else {
                $pdo->prepare('INSERT INTO users (username, email, password_hash, role, xp, level) VALUES (?, ?, ?, ?, ?, ?)')
                    ->execute([$form['username'], $form['email'], password_hash($password, PASSWORD_DEFAULT), $form['role'], $form['xp'], $level]);
            }
            flash('success', 'User saved.');
            redirect('admin.php');
        } catch (PDOException $ex) {
            if ($ex->getCode() === '23000') {
                $errors[] = 'Username or email already taken.';
            } else {
                error_log($ex->getMessage());
                $errors[] = 'Could not save user.';
            }
        }
    }
}

$pageTitle = $editing ? 'Edit user' : 'New user';
require __DIR__ . '/includes/header.php';
?>
<h1><?= $editing ? 'EDIT USER' : 'NEW USER' ?></h1>
<?php foreach ($errors as $err): ?><div class="flash error"><?= e($err) ?></div><?php endforeach; ?>
<form method="post" class="panel narrow">
    <?= csrf_field() ?>
    <?php if ($editing): ?><input type="hidden" name="id" value="<?= (int) $id ?>"><?php endif; ?>
    <label>Username <input name="username" value="<?= e($form['username']) ?>" required maxlength="20"></label>
    <label>Email <input type="email" name="email" value="<?= e($form['email']) ?>" required></label>
    <label>Password <input type="password" name="password" <?= $editing ? '' : 'required' ?> minlength="8" placeholder="<?= $editing ? 'blank = keep' : '' ?>"></label>
    <label>XP <input type="number" name="xp" min="0" value="<?= (int) $form['xp'] ?>"></label>
    <label>Role
        <select name="role" <?= $isSelf ? 'disabled' : '' ?>>
            <option value="user" <?= $form['role'] === 'user' ? 'selected' : '' ?>>user</option>
            <option value="admin" <?= $form['role'] === 'admin' ? 'selected' : '' ?>>admin</option>
        </select>
    </label>
    <button class="btn" type="submit">Save</button>
    <a class="btn" href="admin.php">Cancel</a>
</form>
<?php require __DIR__ . '/includes/footer.php'; ?>
