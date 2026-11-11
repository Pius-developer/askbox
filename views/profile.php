<?php
$meta = [
    'title'       => 'Ask ' . $owner['display_name'] . ' anything',
    'description' => $owner['bio'] ?: 'Send ' . $owner['display_name'] . ' an anonymous question.',
];
$first = explode(' ', $owner['display_name'])[0];
$max = config('question_max');
?>
<section class="narrow">
    <div class="who-head">
        <h1><?= e($owner['display_name']) ?></h1>
        <p class="handle">@<?= e($owner['username']) ?></p>
    </div>
    <?php if ($owner['bio']): ?><p class="bio"><?= e($owner['bio']) ?></p><?php endif; ?>

    <?php if ($isOwner): ?>
        <p class="notice">This is how visitors see your page. <a href="/dashboard">Open your inbox</a> to answer questions.</p>
    <?php elseif ($owner['accepting']): ?>
        <form method="post" action="/@<?= e($owner['username']) ?>/ask" class="ask">
            <?= csrf_field() ?>
            <label for="body">Ask <?= e($first) ?> anything. <?= e($first) ?> won’t see who you are.</label>
            <textarea id="body" name="body" rows="3" maxlength="<?= $max ?>" placeholder="Type your question…" required data-counter="ask-count"><?= e($draft) ?></textarea>
            <div class="hp" aria-hidden="true"><label>Website <input name="website" tabindex="-1" autocomplete="off"></label></div>
            <div class="ask-foot">
                <span class="quiet small"><span id="ask-count">0</span> of <?= $max ?> characters</span>
                <button class="btn">Send anonymously</button>
            </div>
        </form>
    <?php else: ?>
        <p class="notice"><?= e($first) ?> isn’t taking new questions right now. You can still read the answers below.</p>
    <?php endif; ?>

    <div class="tabs">
        <a href="?tab=answered" class="<?= $tab === 'answered' ? 'active' : '' ?>">Answered<span><?= $counts['answered'] ?></span></a>
        <a href="?tab=next" class="<?= $tab === 'next' ? 'active' : '' ?>">Up next<span><?= $counts['next'] ?></span></a>
    </div>

    <?php if (!$list): ?>
        <p class="empty"><?= $tab === 'next'
            ? 'No questions are waiting. Ask the first one above.'
            : e($first) . ' hasn’t answered anything yet. Ask a question, then upvote it under Up next.' ?></p>
    <?php endif; ?>

    <ul class="feed">
        <?php foreach ($list as $q): ?>
            <li class="qa">
                <?php if ($q['is_pinned']): ?><p class="pinned">Pinned by <?= e($first) ?></p><?php endif; ?>
                <a href="/q/<?= (int) $q['id'] ?>" class="qa-q voice"><span class="mark"><?= nl($q['body']) ?></span></a>
                <?php if ($q['answer'] !== null): ?>
                    <div class="answer">
                        <p class="by"><?= e($owner['display_name']) ?> <span>answered <?= e(time_ago($q['answered_at'])) ?></span></p>
                        <p><?= nl($q['answer']) ?></p>
                        <a href="/q/<?= (int) $q['id'] ?>">Share this answer</a>
                    </div>
                <?php else: ?>
                    <div class="vote-row">
                        <?php $did = in_array((int) $q['id'], $voted, true); $disabled = $did || $isOwner; require ROOT . '/views/partials/vote.php'; ?>
                        <span class="quiet small">Asked <?= e(time_ago($q['created_at'])) ?></span>
                    </div>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>

    <?php if (!$isOwner): ?>
        <div class="spread">
            <div>
                <strong>Want people to ask you questions too?</strong>
                <p>Get your own AskBox link. It’s free and takes 30 seconds.</p>
            </div>
            <a href="/signup" class="btn">Create your box</a>
        </div>
    <?php endif; ?>
</section>
