<?php
// Contact page: validate and store messages for the admin-only inbox.
require_once __DIR__ . '/includes/bootstrap.php';

$user = current_user($pdo);
$form = [
    'name' => $user['username'] ?? '',
    'email' => '',
    'subject' => '',
    'message' => '',
];
$errors = [];

if ($user) {
    $emailStmt = $pdo->prepare('SELECT email FROM users WHERE userID = ?');
    $emailStmt->execute([$user['userID']]);
    $form['email'] = (string) $emailStmt->fetchColumn();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach ($form as $field => $_) {
        $form[$field] = trim((string) ($_POST[$field] ?? ''));
    }

    if (strlen($form['name']) < 2 || strlen($form['name']) > 100) {
        $errors[] = 'Enter a name between 2 and 100 characters.';
    }
    if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL) || strlen($form['email']) > 255) {
        $errors[] = 'Enter a valid email address.';
    }
    if (strlen($form['subject']) < 3 || strlen($form['subject']) > 150) {
        $errors[] = 'Enter a subject between 3 and 150 characters.';
    }
    if (strlen($form['message']) < 10 || strlen($form['message']) > 5000) {
        $errors[] = 'Your message must be between 10 and 5000 characters.';
    }

    if (!$errors) {
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO contact_messages (userID, name, email, subject, message)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute([$user['userID'] ?? null, $form['name'], $form['email'], $form['subject'], $form['message']]);
            flash('success', 'Thanks for reaching out. Your message has been sent to the cinema team.');
            redirect('contact.php');
        } catch (PDOException $ex) {
            error_log($ex->getMessage());
            $errors[] = 'Your message could not be sent right now. Please try again later.';
        }
    }
}

$pageTitle = 'Contact';
require __DIR__ . '/includes/header.php';
?>
<header class="content-heading">
<section class="contact-page">
    <header class="content-heading">
        <p class="eyebrow">PIXEL CINEMA &bull; WE ARE LISTENING</p>
        <h1>CONTACT</h1>
        <p class="muted">Send a question to the cinema team. We’ll keep your message with your contact details for follow-up.</p>
    </header>
    <?php foreach ($errors as $error): ?><div class="flash error"><?= e($error) ?></div><?php endforeach; ?>
    <form method="post" class="panel narrow contact-form">
        <?= csrf_field() ?>
        <label>Your name <input name="name" value="<?= e($form['name']) ?>" required maxlength="100"></label>
        <label>Email <input type="email" name="email" value="<?= e($form['email']) ?>" required maxlength="255"></label>
        <label>Subject <input name="subject" value="<?= e($form['subject']) ?>" required maxlength="150"></label>
        <label>Message <textarea name="message" required minlength="10" maxlength="5000" rows="7"><?= e($form['message']) ?></textarea></label>
        <button class="btn" type="submit">Send message</button>
    </form>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
