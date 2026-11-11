<?php $meta = ['title' => 'Inbox · AskBox']; $back = '/dashboard' . ($tab === 'answered' ? '?tab=answered' : ''); ?>
<section class="dash">
    <div class="dash-head">
        <div>
            <h1>Inbox</h1>
            <p class="quiet" style="margin:6px 0 0">Share your link and new questions land here.</p>
        </div>
        <div class="share-row">
            <input readonly value="<?= e($shareUrl) ?>" class="js-copy-src" aria-label="Your AskBox link">
            <button class="btn js-copy" type="button">Copy link</button>
        </div>
    </div>

    <div class="stats">
        <div class="stat"><b><?= $stats['total'] ?></b>questions</div>
        <div class="stat"><b><?= $stats['answered'] ?></b>answered</div>
        <div class="stat"><b><?= $stats['upvotes'] ?></b>upvotes</div>
    </div>

    <?php if (!$me['accepting']): ?>
        <p class="notice">Your box is closed, so nobody can send new questions. <a href="/settings">Open it in settings</a>.</p>
    <?php endif; ?>

    <div class="tabs">
        <a href="/dashboard" class="<?= $tab === 'inbox' ? 'active' : '' ?>">Waiting for you</a>
        <a href="/dashboard?tab=answered" class="<?= $tab === 'answered' ? 'active' : '' ?>">Answered</a>
    </div>

    <?php if (!$items): ?>
        <div class="empty">
            <?php if ($tab === 'inbox'): ?>
                <p>No questions yet. Post your link with something like “Ask me anything” to get the first ones.</p>
                <a class="btn" target="_blank" rel="noopener" href="https://x.com/intent/post?text=<?= rawurlencode('Ask me anything. It’s anonymous:') ?>&url=<?= rawurlencode($shareUrl) ?>">Post your link on X</a>
            <?php else: ?>
                <p>You haven’t answered anything yet.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php foreach ($items as $q): ?>
        <article class="inbox-item <?= $q['is_hidden'] ? 'is-hidden' : '' ?>">
            <div class="inbox-meta">
                <span class="vote tiny" aria-label="<?= (int) $q['upvotes'] ?> upvotes"><svg viewBox="0 0 12 12" aria-hidden="true"><path d="M6 1 11.5 10.5H.5Z" fill="currentColor"/></svg><?= (int) $q['upvotes'] ?></span>
                <span>Asked <?= e(time_ago($q['created_at'])) ?></span>
                <?php if ($q['is_hidden']): ?><span class="tag">Hidden from your page</span><?php endif; ?>
                <?php if ($q['is_pinned']): ?><span class="tag">Pinned</span><?php endif; ?>
            </div>
            <p class="qa-q voice"><span class="mark"><?= nl($q['body']) ?></span></p>

            <form method="post" action="/q/<?= (int) $q['id'] ?>/answer" class="answer-form">
                <?= csrf_field() ?>
                <input type="hidden" name="back" value="<?= e($back) ?>">
                <textarea name="answer" rows="<?= $q['answer'] ? 3 : 2 ?>" maxlength="2000" placeholder="Write your answer" required aria-label="Your answer"><?= e($q['answer']) ?></textarea>
                <div class="actions">
                    <button class="btn btn-sm"><?= $q['answer'] ? 'Update answer' : 'Publish answer' ?></button>
                    <?php if ($q['answer']): ?><a class="btn btn-sm btn-line" href="/q/<?= (int) $q['id'] ?>">Share</a><?php endif; ?>
                </div>
            </form>

            <div class="mod">
                <?php foreach (['pin' => $q['is_pinned'] ? 'Unpin' : 'Pin to top', 'hide' => $q['is_hidden'] ? 'Show on my page' : 'Hide', 'delete' => 'Delete'] as $act => $label): ?>
                    <form method="post" action="/q/<?= (int) $q['id'] ?>/<?= $act ?>" class="inline" <?= $act === 'delete' ? 'data-confirm="Delete this question? This can’t be undone."' : '' ?>>
                        <?= csrf_field() ?><input type="hidden" name="back" value="<?= e($back) ?>">
                        <button class="text <?= $act === 'delete' ? 'danger' : '' ?>"><?= $label ?></button>
                    </form>
                <?php endforeach; ?>
            </div>
        </article>
    <?php endforeach; ?>
</section>
