<?php $meta = ['title' => 'Create your AskBox']; ?>
<section class="narrow">
    <div>
        <h1>Create your AskBox</h1>
        <p class="quiet">Free. Your link will be <strong><?= e(host()) ?>/@<span id="u-preview"><?= e($old['username'] ?: 'yourname') ?></span></strong></p>

        <form method="post" action="/signup" novalidate>
            <?= csrf_field() ?>
            <label for="username">Username</label>
            <input id="username" name="username" value="<?= e($old['username']) ?>" maxlength="20" autocomplete="username" required>
            <?php if (isset($errors['username'])): ?><p class="err"><?= e($errors['username']) ?></p><?php endif; ?>

            <label for="display_name">Display name</label>
            <input id="display_name" name="display_name" value="<?= e($old['display_name']) ?>" maxlength="40" placeholder="Ejike Pius" required>
            <?php if (isset($errors['display_name'])): ?><p class="err"><?= e($errors['display_name']) ?></p><?php endif; ?>

            <label for="email">Email</label>
            <input id="email" name="email" type="email" value="<?= e($old['email']) ?>" autocomplete="email" required>
            <?php if (isset($errors['email'])): ?><p class="err"><?= e($errors['email']) ?></p><?php endif; ?>

            <label for="password">Password</label>
            <input id="password" name="password" type="password" minlength="8" autocomplete="new-password" required>
            <?php if (isset($errors['password'])): ?><p class="err"><?= e($errors['password']) ?></p><?php endif; ?>

            <button class="btn btn-block">Create my box</button>
        </form>
        <p class="quiet small" style="margin-top:20px">Already have one? <a href="/login">Log in</a></p>
    </div>
</section>
