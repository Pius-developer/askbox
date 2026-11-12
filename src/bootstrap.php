<?php
declare(strict_types=1);

session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
    'cookie_secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
]);

define('ROOT', dirname(__DIR__));
define('DB_PATH', ROOT . '/data/askbox.sqlite');

$GLOBALS['config'] = require __DIR__ . '/config.php';

require __DIR__ . '/Database.php';
require __DIR__ . '/UserRepository.php';
require __DIR__ . '/QuestionRepository.php';
require __DIR__ . '/ShareCard.php';
require __DIR__ . '/Mailer.php';
require __DIR__ . '/helpers.php';
