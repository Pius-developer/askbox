<?php
/*
 | Private settings. Copy this file to config.local.php and fill it in.
 | config.local.php is in .gitignore, so your password never reaches GitHub.
 |
 | On cPanel: Email Accounts → create hello@ejikepius.dev → "Connect Devices"
 | shows the exact SMTP host and port to use.
 */
return [
    'mail' => [
        'driver'   => 'smtp',
        'host'     => 'mail.ejikepius.dev',
        'port'     => 465,
        'encryption' => 'ssl',   // ssl for port 465, tls for port 587
        'username' => 'hello@ejikepius.dev',
        'password' => 'the-mailbox-password',
    ],
];
