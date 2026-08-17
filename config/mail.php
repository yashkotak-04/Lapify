<?php
// config/mail.php - Email Configuration for Lapify
// Supports SMTP (Gmail, Outlook, Mailtrap, SendGrid, etc.) and local development logging fallback.

return [
    /*
     |--------------------------------------------------------------------------
     | Default Mail Driver
     |--------------------------------------------------------------------------
     | Options: 'smtp', 'log'
     */
    'driver' => getenv('MAIL_DRIVER') ?: 'smtp',

    /*
     |--------------------------------------------------------------------------
     | SMTP Host & Port Configuration
     |--------------------------------------------------------------------------
     | Gmail:              host => 'smtp.gmail.com',      port => 587, encryption => 'tls' (or port 465 / 'ssl')
     | Outlook/Office 365: host => 'smtp.office365.com',  port => 587, encryption => 'tls'
     | Mailtrap:           host => 'sandbox.smtp.mailtrap.io', port => 2525, encryption => 'tls'
     */
    'host' => getenv('MAIL_HOST') ?: 'smtp.gmail.com',
    'port' => (int)(getenv('MAIL_PORT') ?: 587),

    /*
     |--------------------------------------------------------------------------
     | Encryption Protocol
     |--------------------------------------------------------------------------
     | Options: 'tls', 'ssl', or '' (none)
     */
    'encryption' => getenv('MAIL_ENCRYPTION') ?: 'tls',

    /*
     |--------------------------------------------------------------------------
     | SMTP Credentials
     |--------------------------------------------------------------------------
     | To send real emails from localhost using Gmail:
     | 1. Enable 2-Step Verification on your Google Account (myaccount.google.com).
     | 2. Go to: https://myaccount.google.com/apppasswords
     | 3. Generate a 16-character App Password for "Mail".
     | 4. Enter your full Gmail address in 'username' (or MAIL_USERNAME in .env).
     | 5. Enter the 16-character App Password in 'password' (or MAIL_PASSWORD in .env).
     */
    'username' => getenv('MAIL_USERNAME') ?: 'kotakyash192@gmail.com',
    'password' => getenv('MAIL_PASSWORD') ?: 'hqsqmewzoxxumegq',

    /*
     |--------------------------------------------------------------------------
     | From Address & Name
     |--------------------------------------------------------------------------
     | If left empty, Lapify will automatically use your SMTP username as the sender.
     */
    'from' => [
        'address' => getenv('MAIL_FROM_ADDRESS') ?: '',
        'name'    => getenv('MAIL_FROM_NAME') ?: 'Lapify Marketplace',
    ],

    /*
     |--------------------------------------------------------------------------
     | Local Development Log File
     |--------------------------------------------------------------------------
     | Used as fallback when running locally and SMTP credentials are not yet configured.
     */
    'log_file' => __DIR__ . '/../logs/password_resets.log',
];
