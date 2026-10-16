<?php
// FAQ page: answer common questions about the currently implemented booking flow.
require_once __DIR__ . '/includes/bootstrap.php';

$questions = [
    [
        'question' => 'How do I book a movie?',
        'answer' => 'Choose a film, select an upcoming showtime, then pick your seats. You need to be signed in to confirm a booking.',
    ],
    [
        'question' => 'Can I book seats for friends?',
        'answer' => 'Yes. Select up to eight available seats for the same showtime in one booking.',
    ],
    [
        'question' => 'Where can I find my tickets?',
        'answer' => 'Open My Account after signing in. Your tickets are listed there, and the most recent booking is highlighted.',
    ],
    [
        'question' => 'Do account levels affect ticket prices?',
        'answer' => 'Yes. The booking page shows your current level discount and the price before you confirm your seats.',
    ],
    [
        'question' => 'How can I ask for help?',
        'answer' => 'Use the Contact page to send a message to the cinema team.',
    ],
];

$pageTitle = 'FAQ';
require __DIR__ . '/includes/header.php';
?>
<header class="content-heading">
    <p class="eyebrow">PIXEL CINEMA &bull; GOOD TO KNOW</p>
    <h1>FAQ</h1>
    <p class="muted">Quick answers about films, tickets, and bookings.</p>
</header>
<section class="panel faq-list" aria-label="Frequently asked questions">
    <?php foreach ($questions as $item): ?>
    <details class="faq-item">
        <summary><?= e($item['question']) ?></summary>
        <p><?= e($item['answer']) ?></p>
    </details>
    <?php endforeach; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
