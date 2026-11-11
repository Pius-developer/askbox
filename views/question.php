<?php
$meta = [
    'title'       => $q['answer'] !== null
        ? $q['display_name'] . ' answered: “' . mb_strimwidth($q['body'], 0, 80, '…') . '”'
        : 'Asked to ' . $q['display_name'] . ': “' . mb_strimwidth($q['body'], 0, 80, '…') . '”',
    'description' => $q['answer'] !== null ? mb_strimwidth($q['answer'], 0, 180, '…') : 'Upvote this question so it gets answered.',
    'image'       => base_url() . '/q/' . $q['id'] . '/card.png?v=' . substr(md5($q['body'] . $q['answer']), 0, 8),
    'url'         => $shareUrl,
];
$first = explode(' ', $q['display_name'])[0];
$tweet = $q['answer'] !== null
    ? "Someone asked me this anonymously, so I answered it.\n\nAsk me anything:"
    : "Help me pick what to answer next. Upvote it here:";
?>
<section class="narrow single">
    <a href="/@<?= e($q['username']) ?>" class="back">All of <?= e($first) ?>’s answers</a>

    <?php if ($q['is_hidden']): ?><p class="notice">Only you can see this question. It’s hidden from your page.</p><?php endif; ?>
    <p class="qa-q voice"><span class="mark"><?= nl($q['body']) ?></span></p>

    <?php if ($q['answer'] !== null): ?>
        <div class="answer">
            <p class="by"><?= e($q['display_name']) ?> <span>answered <?= e(time_ago($q['answered_at'])) ?></span></p>
            <p><?= nl($q['answer']) ?></p>
        </div>
    <?php else: ?>
        <div class="vote-row">
            <?php $did = $voted; $disabled = $voted || $isOwner; require ROOT . '/views/partials/vote.php'; ?>
            <span class="quiet small"><?= e($first) ?> hasn’t answered this yet</span>
        </div>
    <?php endif; ?>

    <div class="share">
        <img src="<?= e($meta['image']) ?>" alt="Preview image people see when this link is shared" loading="lazy">
        <div class="share-row">
            <input readonly value="<?= e($shareUrl) ?>" class="js-copy-src" aria-label="Link to this question">
            <button class="btn btn-line js-copy" type="button">Copy link</button>
            <a class="btn" target="_blank" rel="noopener"
               href="https://x.com/intent/post?text=<?= rawurlencode($tweet) ?>&url=<?= rawurlencode($shareUrl) ?>">Post on X</a>
        </div>
    </div>
</section>
