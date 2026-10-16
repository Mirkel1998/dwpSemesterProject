<?php
// Newsroom page: keep cinema updates together in this removable content module.
require_once __DIR__ . '/includes/bootstrap.php';

$stories = [
    [
        'issue' => '02',
        'category' => 'ADAPTATION WATCH',
        'title' => 'Blocks, mobs & a mountain of popcorn',
        'summary' => 'Minecraft on the big screen: build your snack tower before the opening credits.',
    ],
    [
        'issue' => '01',
        'category' => 'ARCADE HOT TAKE',
        'title' => 'Sonic movies are a speedrun',
        'summary' => 'Blink during the action and you might miss a gag. Hydrate accordingly.',
    ],
    [
        'issue' => '00',
        'category' => 'NO-SPOILER CLUB',
        'title' => 'The post-credits seat shuffle',
        'summary' => 'Stay for the credits, trade your favorite Easter eggs, and save the spoilers for the lobby.',
    ],
];

$pageTitle = 'News';
require __DIR__ . '/includes/header.php';
?>
<div class="newsroom">
    <header class="news-masthead">
        <div>
            <p class="eyebrow">PIXEL CINEMA &bull; THE FAN DESK</p>
            <h1>THE PIXEL POPCORN POST</h1>
            <p class="muted">Just-for-fun dispatches about video games making the jump to the big screen.</p>
        </div>
        <span class="news-masthead-stamp" aria-hidden="true">PRESS<br>PLAY</span>
    </header>

    <article class="news-lead">
        <div class="news-lead-copy">
            <p class="news-kicker">GAME TO SCREEN <span>03</span></p>
            <h2>Player one, meet the popcorn machine</h2>
            <p>From block-built worlds to blue-speed heroes, game adaptations bring controller-sized fun to the big screen. Here's our very serious, very unofficial popcorn report.</p>
            <a class="news-link" href="movies.php">Pick a movie for game night <span aria-hidden="true">→</span></a>
        </div>
        <div class="news-lead-art" aria-hidden="true">
            <div class="news-arcade-art">
                <div class="news-arcade-screen">
                    <span>PIXEL CINEMA</span>
                    <strong>PRESS<br>PLAY</strong>
                </div>
                <div class="news-arcade-controls">
                    <span class="news-arcade-dpad">✣</span>
                    <span class="news-arcade-buttons">●<br>●</span>
                </div>
            </div>
            <span class="news-art-star news-art-star-one">✦</span>
            <span class="news-art-star news-art-star-two">✧</span>
        </div>
    </article>

    <div class="news-section-heading">
        <div>
            <p class="eyebrow">ADAPTATIONS, ARCADE NOSTALGIA &amp; SNACK BREAKS</p>
            <h2>FRESH FROM THE FAN DESK</h2>
        </div>
        <span><?= count($stories) ?> QUICK READS</span>
    </div>

    <section class="news-grid" aria-label="Latest cinema stories">
        <?php foreach ($stories as $story): ?>
        <article class="news-story">
            <div class="news-story-meta">
                <span><?= e($story['category']) ?></span>
                <span>NO. <?= e($story['issue']) ?></span>
            </div>
            <h3><?= e($story['title']) ?></h3>
            <p><?= e($story['summary']) ?></p>
        </article>
        <?php endforeach; ?>
    </section>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
