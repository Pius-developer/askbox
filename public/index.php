<?php
declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

// Built-in dev server: serve static files directly.
if (PHP_SAPI === 'cli-server') {
    $file = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (is_file($file)) {
        return false;
    }
}

$db        = Database::connection();
$users     = new UserRepository($db);
$questions = new QuestionRepository($db);
$method    = $_SERVER['REQUEST_METHOD'];
$path      = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/');
$path      = rtrim($path, '/') ?: '/';

/* ===========================================================================
 | Landing page
 * ========================================================================= */
if ($method === 'GET' && $path === '/') {
    if (current_user()) {
        redirect('/dashboard');
    }
    render('home', ['userCount' => $users->count()]);
}

/* ===========================================================================
 | Sign up / log in / log out
 * ========================================================================= */
if ($path === '/signup') {
    if (current_user()) {
        redirect('/dashboard');
    }
    $old = ['username' => trim((string) ($_GET['u'] ?? '')), 'display_name' => '', 'email' => ''];
    $errors = [];

    if ($method === 'POST') {
        verify_csrf();
        $old = [
            'username'     => strtolower(ltrim(trim((string) ($_POST['username'] ?? '')), '@')),
            'display_name' => trim((string) ($_POST['display_name'] ?? '')),
            'email'        => strtolower(trim((string) ($_POST['email'] ?? ''))),
        ];
        $password = (string) ($_POST['password'] ?? '');

        if (!preg_match('/^[a-z0-9_]{3,20}$/', $old['username'])) {
            $errors['username'] = 'Use 3–20 letters, numbers or underscores.';
        } elseif (in_array($old['username'], config('reserved_usernames'), true)) {
            $errors['username'] = 'That username is reserved.';
        } elseif ($users->exists('username', $old['username'])) {
            $errors['username'] = 'That username is taken.';
        }
        if ($old['display_name'] === '' || mb_strlen($old['display_name']) > 40) {
            $errors['display_name'] = 'Enter your name (max 40 characters).';
        }
        if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Enter a valid email.';
        } elseif ($users->exists('email', $old['email'])) {
            $errors['email'] = 'An account with that email already exists.';
        }
        if (strlen($password) < 8) {
            $errors['password'] = 'Use at least 8 characters.';
        }

        if (!$errors) {
            $id = $users->create($old['username'], $old['display_name'], $old['email'], $password);
            login_user($id);
            flash('Your AskBox is live! Share your link to start getting questions.');
            redirect('/dashboard');
        }
    }
    render('signup', compact('old', 'errors'), $errors ? 422 : 200);
}

if ($path === '/login') {
    if (current_user()) {
        redirect('/dashboard');
    }
    $error = null;
    $login = '';
    if ($method === 'POST') {
        verify_csrf();
        $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
        if ($_SESSION['login_attempts'] > 8) {
            $error = 'Too many attempts. Please wait a bit and try again.';
        } else {
            $login = trim((string) ($_POST['login'] ?? ''));
            $user  = $users->findByLogin($login);
            if ($user && password_verify((string) ($_POST['password'] ?? ''), $user['password_hash'])) {
                unset($_SESSION['login_attempts']);
                login_user((int) $user['id']);
                redirect('/dashboard');
            }
            $error = 'Wrong username/email or password.';
        }
    }
    render('login', compact('error', 'login'), $error ? 422 : 200);
}

if ($method === 'POST' && $path === '/logout') {
    verify_csrf();
    $_SESSION = [];
    session_regenerate_id(true);
    redirect('/');
}

/* ===========================================================================
 | Owner dashboard & settings
 * ========================================================================= */
if ($method === 'GET' && $path === '/dashboard') {
    $me  = require_login();
    $tab = ($_GET['tab'] ?? 'inbox') === 'answered' ? 'answered' : 'inbox';
    render('dashboard', [
        'tab'       => $tab,
        'items'     => $questions->inbox((int) $me['id'], $tab === 'answered'),
        'stats'     => $questions->stats((int) $me['id']),
        'shareUrl'  => base_url() . '/@' . $me['username'],
    ]);
}

if ($path === '/settings') {
    $me = require_login();
    $errors = [];
    if ($method === 'POST') {
        verify_csrf();
        $name = trim((string) ($_POST['display_name'] ?? ''));
        $bio  = trim((string) ($_POST['bio'] ?? ''));
        if ($name === '' || mb_strlen($name) > 40) {
            $errors[] = 'Name is required (max 40 characters).';
        }
        if (mb_strlen($bio) > 160) {
            $errors[] = 'Bio must be 160 characters or less.';
        }
        if (!$errors) {
            $users->updateProfile((int) $me['id'], $name, $bio, isset($_POST['accepting']), isset($_POST['review_first']));
            flash('Settings saved.');
            redirect('/settings');
        }
    }
    render('settings', ['errors' => $errors], $errors ? 422 : 200);
}

/* ===========================================================================
 | Public AskBox page: /@username
 * ========================================================================= */
if (preg_match('#^/@([A-Za-z0-9_]{3,20})(/ask)?$#', $path, $m)) {
    $owner = $users->findByUsername($m[1]);
    if (!$owner) {
        render('404', ['missingUser' => $m[1]], 404);
    }
    $ownerId = (int) $owner['id'];

    if ($method === 'POST' && ($m[2] ?? '') === '/ask') {
        verify_csrf();
        $body   = trim(preg_replace('/[ \t]+/u', ' ', (string) ($_POST['body'] ?? '')));
        $error  = null;
        $hash   = visitor_hash();

        if (!empty($_POST['website'])) {
            // Honeypot field was filled in: almost certainly a bot. Pretend it worked.
            flash('Sent! Your question is anonymous.');
            redirect('/@' . $owner['username']);
        }
        if (!$owner['accepting']) {
            $error = $owner['display_name'] . ' is not taking questions right now.';
        } elseif (mb_strlen($body) < config('question_min') || mb_strlen($body) > config('question_max')) {
            $error = 'Questions must be ' . config('question_min') . '–' . config('question_max') . ' characters.';
        } elseif (contains_banned($body)) {
            $error = 'Please keep it respectful.';
        } elseif ($questions->recentCount($ownerId, $hash, config('window_minutes')) >= config('max_questions_per_window')) {
            $error = 'Slow down! Try again in a few minutes.';
        } elseif (current_user() && (int) current_user()['id'] === $ownerId) {
            $error = 'You can’t ask yourself a question 🙂';
        }

        if ($error) {
            $_SESSION['ask_draft'] = $body;
            flash($error);
        } else {
            $questions->ask($ownerId, $body, $hash, (bool) $owner['review_first']);
            flash($owner['review_first']
                ? 'Sent! ' . $owner['display_name'] . ' will see it soon.'
                : 'Sent! Your question is anonymous. Upvote it under “Up next”.');
        }
        redirect('/@' . $owner['username'] . (!$error && !$owner['review_first'] ? '?tab=next' : ''));
    }

    if ($method === 'GET' && empty($m[2])) {
        $tab      = ($_GET['tab'] ?? '') === 'next' ? 'next' : 'answered';
        $answered = $questions->answered($ownerId);
        $upNext   = $questions->upNext($ownerId);
        $list     = $tab === 'next' ? $upNext : $answered;
        $draft    = $_SESSION['ask_draft'] ?? '';
        unset($_SESSION['ask_draft']);
        render('profile', [
            'owner'    => $owner,
            'tab'      => $tab,
            'list'     => $list,
            'counts'   => ['answered' => count($answered), 'next' => count($upNext)],
            'voted'    => $questions->votedIds(array_map(fn($q) => (int) $q['id'], $list), visitor_hash()),
            'isOwner'  => current_user() && (int) current_user()['id'] === $ownerId,
            'draft'    => $draft,
        ]);
    }
}

/* ===========================================================================
 | Single question: /q/{id}, share card, upvote, owner actions
 * ========================================================================= */
if (preg_match('#^/q/(\d+)(?:/(card\.png|upvote|answer|pin|hide|delete))?$#', $path, $m)) {
    $q = $questions->find((int) $m[1]);
    $action = $m[2] ?? '';
    $me = current_user();
    $isOwner = $q && $me && (int) $me['id'] === (int) $q['user_id'];

    if (!$q || ($q['is_hidden'] && !$isOwner)) {
        render('404', [], 404);
    }

    if ($method === 'GET' && $action === '') {
        render('question', [
            'q'        => $q,
            'isOwner'  => $isOwner,
            'voted'    => in_array((int) $q['id'], $questions->votedIds([(int) $q['id']], visitor_hash()), true),
            'shareUrl' => base_url() . '/q/' . $q['id'],
        ]);
    }

    if ($method === 'GET' && $action === 'card.png') {
        $file = (new ShareCard(ROOT . '/data/cards'))->pathFor($q, host());
        header('Content-Type: image/png');
        header('Cache-Control: public, max-age=3600');
        readfile($file);
        exit;
    }

    if ($method === 'POST' && $action === 'upvote') {
        verify_csrf();
        $count = $q['answer'] === null ? $questions->upvote((int) $q['id'], visitor_hash()) : null;
        if (wants_json()) {
            json(['ok' => $count !== null, 'upvotes' => $count ?? (int) $q['upvotes']]);
        }
        redirect('/@' . $q['username'] . '?tab=next');
    }

    // Everything below is owner-only.
    if ($method === 'POST' && in_array($action, ['answer', 'pin', 'hide', 'delete'], true)) {
        verify_csrf();
        if (!$isOwner) {
            render('404', [], 404);
        }
        $back = $_POST['back'] ?? '/dashboard';
        $back = (is_string($back) && preg_match('#^/[A-Za-z0-9/_@?=&-]*$#', $back)) ? $back : '/dashboard';

        if ($action === 'answer') {
            $answer = trim((string) ($_POST['answer'] ?? ''));
            if ($answer === '' || mb_strlen($answer) > 2000) {
                flash('Answers must be 1–2000 characters.');
                redirect($back);
            }
            $questions->answer((int) $q['id'], $answer);
            flash('Answered! Share it: ' . base_url() . '/q/' . $q['id']);
        } elseif ($action === 'pin') {
            $questions->toggle((int) $q['id'], 'is_pinned');
            flash($q['is_pinned'] ? 'Unpinned.' : 'Pinned to the top of your page.');
        } elseif ($action === 'hide') {
            $questions->toggle((int) $q['id'], 'is_hidden');
            flash($q['is_hidden'] ? 'Now visible on your page.' : 'Hidden from your page.');
        } else {
            $questions->delete((int) $q['id']);
            flash('Question deleted.');
        }
        redirect($back);
    }
}

render('404', [], 404);
