<?php
$meta = ($meta ?? []) + [
    'title'       => 'AskBox: let your audience ask you anything',
    'description' => 'Get a link your audience uses to ask you anything anonymously. They upvote what you answer next, and every answer becomes a shareable card.',
    'image'       => null,
    'url'         => base_url() . ($_SERVER['REQUEST_URI'] ?? '/'),
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($meta['title']) ?></title>
    <meta name="description" content="<?= e($meta['description']) ?>">
    <meta name="theme-color" content="#ffffff">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="AskBox">
    <meta property="og:title" content="<?= e($meta['title']) ?>">
    <meta property="og:description" content="<?= e($meta['description']) ?>">
    <meta property="og:url" content="<?= e($meta['url']) ?>">
    <?php if ($meta['image']): ?>
        <meta property="og:image" content="<?= e($meta['image']) ?>">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:image" content="<?= e($meta['image']) ?>">
    <?php else: ?>
        <meta name="twitter:card" content="summary">
    <?php endif; ?>
    <meta name="twitter:creator" content="@<?= e(config('creator_x')) ?>">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><rect width='32' height='32' rx='16' fill='%23FFA26B'/><text x='16' y='23' font-size='21' font-family='Georgia' font-style='italic' font-weight='700' text-anchor='middle'>?</text></svg>">
    <link rel="preload" href="/assets/fonts/Lora-Italic-Variable.ttf" as="font" type="font/ttf" crossorigin>
    <link rel="stylesheet" href="/assets/app.css">
</head>
<body>
<header class="nav">
    <div class="nav-inner">
        <a href="/" class="wordmark">AskBox<i>?</i></a>
        <nav>
            <?php if ($me): ?>
                <a href="/@<?= e($me['username']) ?>">My page</a>
                <a href="/dashboard">Inbox</a>
                <a href="/settings" class="hide-sm">Settings</a>
                <form method="post" action="/logout" class="inline"><?= csrf_field() ?><button class="text">Log out</button></form>
            <?php else: ?>
                <a href="/login">Log in</a>
                <a href="/signup" class="btn btn-sm">Create your box</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<main>
    <?php if (!empty($flash)): ?>
        <div class="wrap"><div class="flash" role="status"><?= e($flash) ?></div></div>
    <?php endif; ?>
    <?= $content ?>
</main>

<footer class="footer">
    <div class="wrap footer-inner">
        <p>AskBox is made by Ejike Pius. Follow him on X: <a href="https://x.com/<?= e(config('creator_x')) ?>" target="_blank" rel="noopener">@<?= e(config('creator_x')) ?></a></p>
        <p class="quiet">Day 1 of building a product every day. <a href="<?= e(config('github_url')) ?>" target="_blank" rel="noopener">Read the code</a></p>
    </div>
</footer>
<script src="/assets/app.js" defer></script>
</body>
</html>
