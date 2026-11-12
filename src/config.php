<?php
declare(strict_types=1);

/*
 | AskBox configuration. Edit these values before deploying.
 */
$config = [
    'app_name'   => 'AskBox',
    // Public URL with no trailing slash. Leave null to detect it from the request.
    'app_url'    => getenv('ASKBOX_URL') ?: null,

    // Creator credit shown in the footer and on share cards
    'creator'    => 'Ejike Pius',
    'creator_x'  => 'ejike_pius',
    'github_url' => 'https://github.com/pius-developer/askbox',

    // Anti-spam
    'max_questions_per_window' => 3,   // per visitor, per box
    'window_minutes'           => 10,
    'question_min'             => 3,
    'question_max'             => 280,
    'banned_words'             => ['kill yourself', 'kys', 'nigger', 'faggot', 'retard'],

    // Usernames people can't register
    'reserved_usernames' => ['admin', 'askbox', 'login', 'logout', 'signup', 'dashboard',
                             'settings', 'api', 'q', 'assets', 'support', 'help', 'about'],

    // Email (welcome email with the user's link).
    // Don't put your password here: this file is on GitHub.
    // Put real settings in config.local.php instead (see config.local.example.php).
    'mail' => [
        'driver'     => 'log',   // log = write to data/mail.log instead of sending
        'from_email' => 'hello@ejikepius.dev',
        'from_name'  => 'Ejike from AskBox',
        'reply_to'   => 'hello@ejikepius.dev',
        'host'       => '',
        'port'       => 465,
        'encryption' => 'ssl',   // ssl for port 465, tls for port 587
        'username'   => '',
        'password'   => '',
    ],
];

// Private settings that never go to GitHub (passwords etc.) override the ones above.
$local = dirname(__DIR__) . '/config.local.php';
if (is_file($local)) {
    $config = array_replace_recursive($config, require $local);
}

return $config;
