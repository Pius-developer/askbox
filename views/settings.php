<?php $meta = ['title' => 'Settings · AskBox']; ?>
<section class="narrow">
    <div>
        <h1>Settings</h1>
        <?php if ($errors): ?><ul class="err box"><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul><?php endif; ?>
        <form method="post" action="/settings">
            <?= csrf_field() ?>
            <label for="display_name">Display name</label>
            <input id="display_name" name="display_name" maxlength="40" value="<?= e($me['display_name']) ?>" required>

            <label for="bio">Bio <span class="quiet small" style="font-weight:400">shown under your name</span></label>
            <textarea id="bio" name="bio" rows="2" maxlength="160"><?= e($me['bio']) ?></textarea>

            <label class="check"><input type="checkbox" name="accepting" <?= $me['accepting'] ? 'checked' : '' ?>> Accept new questions</label>
            <label class="check"><input type="checkbox" name="review_first" <?= $me['review_first'] ? 'checked' : '' ?>> Review first: new questions stay hidden until I answer or show them</label>

            <button class="btn btn-block">Save settings</button>
        </form>
        <p class="quiet small">Your link: <a href="/@<?= e($me['username']) ?>"><?= e(host()) ?>/@<?= e($me['username']) ?></a></p>
    </div>
</section>
