<?php
declare(strict_types=1);

function config(string $key): mixed
{
    return $GLOBALS['config'][$key] ?? null;
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Escape, then keep line breaks. */
function nl(?string $value): string
{
    return nl2br(e($value), false);
}

// ---- CSRF ------------------------------------------------------------------

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $sent = $_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        http_response_code(419);
        exit('Your session expired. Please go back, refresh and try again.');
    }
}

// ---- Visitors & auth ------------------------------------------------------

/** Anonymous, hashed visitor id (long-lived random cookie). Used for rate limits and upvotes. */
function visitor_hash(): string
{
    $id = $_COOKIE['ab_v'] ?? '';
    if (!is_string($id) || !preg_match('/^[a-f0-9]{32}$/', $id)) {
        $id = bin2hex(random_bytes(16));
        setcookie('ab_v', $id, [
            'expires'  => time() + 60 * 60 * 24 * 365,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        ]);
        $_COOKIE['ab_v'] = $id;
    }
    return hash('sha256', $id);
}

function current_user(): ?array
{
    static $user = false;
    if ($user === false) {
        $id   = $_SESSION['user_id'] ?? null;
        $user = $id ? (new UserRepository(Database::connection()))->find((int) $id) : null;
    }
    return $user;
}

function login_user(int $id): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = $id;
}

function require_login(): array
{
    $user = current_user();
    if (!$user) {
        flash('Log in to continue.');
        redirect('/login');
    }
    return $user;
}

// ---- Responses ------------------------------------------------------------

function flash(?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['flash'] = $message;
        return null;
    }
    $msg = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $msg;
}

function redirect(string $to): never
{
    header('Location: ' . $to, true, 303);
    exit;
}

function render(string $view, array $data = [], int $status = 200): never
{
    http_response_code($status);
    $data['me']    = current_user();
    $data['flash'] = flash();
    extract($data);
    ob_start();
    require ROOT . '/views/' . $view . '.php';
    $content = ob_get_clean();
    require ROOT . '/views/layout.php';
    exit;
}

function json(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function wants_json(): bool
{
    return str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
}

function base_url(): string
{
    if (config('app_url')) {
        return rtrim(config('app_url'), '/');
    }
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    return ($https ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
}

function host(): string
{
    return (string) parse_url(base_url(), PHP_URL_HOST) . (parse_url(base_url(), PHP_URL_PORT) ? ':' . parse_url(base_url(), PHP_URL_PORT) : '');
}

function time_ago(string $datetime): string
{
    $diff = time() - strtotime($datetime . ' UTC');
    return match (true) {
        $diff < 60     => 'just now',
        $diff < 3600   => intdiv($diff, 60) . 'm ago',
        $diff < 86400  => intdiv($diff, 3600) . 'h ago',
        $diff < 604800 => intdiv($diff, 86400) . 'd ago',
        default        => date('M j, Y', strtotime($datetime . ' UTC')),
    };
}

function contains_banned(string $text): bool
{
    $lower = mb_strtolower($text);
    foreach (config('banned_words') as $word) {
        if (preg_match('/\b' . preg_quote($word, '/') . '\b/u', $lower)) {
            return true;
        }
    }
    return false;
}

function avatar_initials(string $name): string
{
    $parts = preg_split('/\s+/u', trim($name)) ?: [''];
    $ini = mb_substr($parts[0], 0, 1) . (isset($parts[1]) ? mb_substr($parts[1], 0, 1) : '');
    return mb_strtoupper($ini);
}
