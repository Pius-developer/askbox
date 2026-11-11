<?php
$examples = [
    'What do you actually charge for a website?',
    'How did you get your first client?',
    'What would you do differently if you started over?',
    'Is it too late to learn to code?',
];
$claim = function () { ?>
    <form action="/signup" method="get" class="claim">
        <label class="claim-field">
            <span class="claim-prefix"><?= e(host()) ?>/@</span>
            <input name="u" placeholder="yourname" pattern="[A-Za-z0-9_]{3,20}" maxlength="20" aria-label="Choose your username" required>
        </label>
        <button class="btn">Claim this link</button>
    </form>
<?php };
?>
<section class="wrap hero">
    <p class="hero-q voice" aria-live="polite">
        <span class="mark js-typer" data-lines='<?= e(json_encode($examples)) ?>'><?= e($examples[0]) ?></span><span class="caret" aria-hidden="true"></span>
    </p>
    <p class="hero-meta">The kind of question people only ask when no one knows it’s them.</p>

    <div class="hero-pitch">
        <h1>Questions people are scared to ask you in public.</h1>
        <div>
            <p>AskBox gives you one link for your bio. Your audience asks you anything without signing up, votes on what you should answer next, and each answer gets its own page you can post anywhere.</p>
            <?php $claim(); ?>
            <p class="claim-note">Free. <?= number_format($userCount) ?> <?= $userCount === 1 ? 'person has' : 'people have' ?> claimed a link so far.</p>
        </div>
    </div>
</section>

<section class="wrap how">
    <h2>How it works</h2>
    <ol>
        <li><h3>Claim your link</h3><p>Pick a username. Your page is live at <?= e(host()) ?>/@you straight away.</p></li>
        <li><h3>People ask and vote</h3><p>Visitors ask without an account and upvote the questions they want answered.</p></li>
        <li><h3>You answer and share</h3><p>Every answer gets a page with a preview image that shows up on X, WhatsApp and LinkedIn.</p></li>
    </ol>
</section>

<section class="wrap control">
    <h2>You stay in control</h2>
    <dl>
        <div><dt>Review before anything shows</dt><dd>Turn on review mode and new questions stay hidden until you answer or approve them.</dd></div>
        <div><dt>Hide, pin or delete</dt><dd>Pin your best answers to the top. Remove anything you don’t want on your page.</dd></div>
        <div><dt>Spam is blocked for you</dt><dd>Each visitor can send three questions every ten minutes, and abusive words are rejected.</dd></div>
        <div><dt>Close your box any time</dt><dd>Pause new questions when you’re busy. Your answers stay up.</dd></div>
    </dl>
</section>

<section class="wrap last">
    <p class="voice"><span class="mark">What would your audience ask you?</span></p>
    <?php $claim(); ?>
</section>
