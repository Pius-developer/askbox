<?php $meta = ['title' => 'Log in · AskBox']; ?>
<section class="narrow">
    <div>
        <h1>Welcome back</h1>
        <?php if ($error): ?><p class="err box"><?= e($error) ?></p><?php endif; ?>
        <form method="post" action="/login">
            <?= csrf_field() ?>
            <label for="login">Username or email</label>
            <input id="login" name="login" value="<?= e($login) ?>" autocomplete="username" required>
            <label for="password">Password</label>
            <input id="password" name="password" type="password" autocomplete="current-password" required>
            <button class="btn btn-block">Log in</button>
        </form>
        <p class="quiet small" style="margin-top:20px">New here? <a href="/signup">Create your AskBox</a></p>
    </div>
</section>
