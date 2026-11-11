<?php /** @var array $q  @var bool $did  @var bool $disabled */ ?>
<form method="post" action="/q/<?= (int) $q['id'] ?>/upvote" class="inline js-upvote">
    <?= csrf_field() ?>
    <button class="vote <?= $did ? 'voted' : '' ?>" <?= $disabled ? 'disabled' : '' ?> aria-label="Upvote this question (<?= (int) $q['upvotes'] ?> votes)">
        <svg viewBox="0 0 12 12" aria-hidden="true"><path d="M6 1 11.5 10.5H.5Z" fill="currentColor"/></svg>
        <span><?= (int) $q['upvotes'] ?></span>
    </button>
</form>
