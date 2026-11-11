<?php $meta = ['title' => 'Not found · AskBox']; ?>
<section class="narrow">
    <div>
        <?php if (!empty($missingUser)): ?>
            <h1>@<?= e($missingUser) ?> is still available</h1>
            <p class="quiet">Nobody has claimed this AskBox yet. It could be yours.</p>
            <a href="/signup?u=<?= e(strtolower($missingUser)) ?>" class="btn">Claim this username</a>
        <?php else: ?>
            <h1>Nothing here</h1>
            <p class="quiet">This page doesn’t exist, or its owner removed it.</p>
            <a href="/" class="btn">Go home</a>
        <?php endif; ?>
    </div>
</section>
