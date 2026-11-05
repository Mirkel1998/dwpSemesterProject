<?php
// Login form: reject already-authenticated visitors, verify submitted
// credentials against the stored password hash, then start their session.
require_once __DIR__ . '/includes/bootstrap.php';

if (current_user($pdo)) {
    redirect('index.php');
}

$errors = [];
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    // Fetch only the account ID and password hash needed to verify the login.
    $stmt = $pdo->prepare('SELECT userID, password_hash FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $row = $stmt->fetch();

    if ($row && password_verify($password, $row['password_hash'])) {
        // Start the session, award any daily-login rewards, then open the home page.
        login_user((int) $row['userID']);
        Gamification::onLogin($pdo, (int) $row['userID']);
        redirect('index.php');
    }
    $errors[] = 'Wrong username or password.';
}

$pageTitle = 'Login';
require __DIR__ . '/includes/header.php';
?>
<h1>LOGIN</h1>
<?php foreach ($errors as $err): ?><div class="flash error"><?= e($err) ?></div><?php endforeach; ?>
<form method="post" class="panel narrow">
    <?= csrf_field() ?>
    <label>Username <input name="username" value="<?= e($username) ?>" required maxlength="20" autofocus></label>
    <label>Password <input type="password" name="password" required></label>
    <button class="btn" type="submit">Start</button>
    <p class="muted">New player? <a href="register.php">Create account</a></p>
</form>
<?php require __DIR__ . '/includes/footer.php'; ?>


