<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (current_user($pdo)) {
    redirect('index.php');
}

$errors = [];
$username = $email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $username = trim((string) ($_POST['username'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (!preg_match('/^[A-Za-z0-9_]{3,20}$/', $username)) {
        $errors[] = 'Username: 3-20 letters, numbers or underscores.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
        $errors[] = 'Enter a valid email.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }

    if (!$errors) {
        try {
            $pdo->prepare('INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)')
                ->execute([$username, $email, password_hash($password, PASSWORD_DEFAULT)]);
            $userId = (int) $pdo->lastInsertId();
            login_user($userId);
            Gamification::onRegister($pdo, $userId);
            redirect('index.php');
        } catch (PDOException $ex) {
            if ($ex->getCode() === '23000') {
                $errors[] = 'Username or email already taken.';
            } else {
                error_log($ex->getMessage());
                $errors[] = 'Could not create account.';
            }
        }
    }
}

$pageTitle = 'Join';
require __DIR__ . '/includes/header.php';
?>
<h1>NEW PLAYER</h1>
<?php foreach ($errors as $err): ?><div class="flash error"><?= e($err) ?></div><?php endforeach; ?>
<form method="post" class="panel narrow">
    <?= csrf_field() ?>
    <label>Username <input name="username" value="<?= e($username) ?>" required maxlength="20"></label>
    <label>Email <input type="email" name="email" value="<?= e($email) ?>" required></label>
    <label>Password <input type="password" name="password" required minlength="8"></label>
    <button class="btn" type="submit">Create account</button>
</form>
<?php require __DIR__ . '/includes/footer.php'; ?>
