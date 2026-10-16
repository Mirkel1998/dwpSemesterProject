<?php
// Admin-only inbox for contact form submissions.
require_once __DIR__ . '/includes/bootstrap.php';
require_admin($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $messageId = filter_input(INPUT_POST, 'messageID', FILTER_VALIDATE_INT);
    $status = $_POST['status'] ?? '';

    if (!$messageId || !in_array($status, ['new', 'read'], true)) {
        flash('error', 'Invalid contact message update.');
    } else {
        try {
            $stmt = $pdo->prepare('UPDATE contact_messages SET status = ? WHERE contactID = ?');
            $stmt->execute([$status, $messageId]);
            flash('success', 'Contact message updated.');
        } catch (PDOException $ex) {
            error_log($ex->getMessage());
            flash('error', 'Could not update the contact message.');
        }
    }
    redirect('contact_messages.php');
}

$messages = $pdo->query(
    "SELECT contactID, name, email, subject, message, status, created_at
     FROM contact_messages
     ORDER BY status = 'new' DESC, created_at DESC, contactID DESC"
)->fetchAll();

$pageTitle = 'Contact messages';
require __DIR__ . '/includes/header.php';
?>
<header class="content-heading">
    <p class="eyebrow">PIXEL CINEMA &bull; ADMIN</p>
    <h1>CONTACT MESSAGES</h1>
    <p><a href="admin.php">Back to Admin</a></p>
</header>
<?php if (!$messages): ?>
    <section class="panel"><p class="muted">There are no contact messages yet.</p></section>
<?php else: ?>
    <section class="content-list" aria-label="Contact messages">
        <?php foreach ($messages as $message): ?>
        <article class="panel content-card">
            <p class="eyebrow"><?= e(strtoupper($message['status'])) ?> &bull; <?= e($message['created_at']) ?></p>
            <h2><?= e($message['subject']) ?></h2>
            <p>From <?= e($message['name']) ?> &lt;<a href="mailto:<?= e($message['email']) ?>"><?= e($message['email']) ?></a>&gt;</p>
            <p class="contact-message"><?= nl2br(e($message['message'])) ?></p>
            <form method="post" class="inline">
                <?= csrf_field() ?>
                <input type="hidden" name="messageID" value="<?= (int) $message['contactID'] ?>">
                <input type="hidden" name="status" value="<?= $message['status'] === 'new' ? 'read' : 'new' ?>">
                <button class="btn small" type="submit">Mark as <?= $message['status'] === 'new' ? 'read' : 'new' ?></button>
            </form>
        </article>
        <?php endforeach; ?>
    </section>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
