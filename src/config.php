<?php
declare(strict_types=1);

/*
 | AskBox configuration. Edit these values before deploying.
 */
return [
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
];
