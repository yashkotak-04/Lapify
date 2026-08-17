<?php
// scripts/test_email.php - CLI SMTP Test Utility for Lapify
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('This script can only be run from the command line.');
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$targetEmail = $argv[1] ?? null;

echo "============================================================\n";
echo "           LAPIFY SMTP DIAGNOSTIC & TEST TOOL               \n";
echo "============================================================\n\n";

$configFile = __DIR__ . '/../config/mail.php';
if (!file_exists($configFile)) {
    echo "[!] Error: config/mail.php not found.\n";
    exit(1);
}

$mailConfig = require $configFile;
$smtpHost = $mailConfig['host'] ?? 'smtp.gmail.com';
$smtpPort = (int)($mailConfig['port'] ?? 587);
$smtpUser = trim((string)($mailConfig['username'] ?? ''));
$smtpPass = trim((string)($mailConfig['password'] ?? ''));
$smtpEnc  = strtolower(trim((string)($mailConfig['encryption'] ?? 'tls')));
$fromAddr = trim((string)($mailConfig['from']['address'] ?? ''));
if (empty($fromAddr) && !empty($smtpUser)) {
    $fromAddr = $smtpUser;
}

echo "Current Mail Configuration:\n";
echo " - Driver:     " . ($mailConfig['driver'] ?? 'smtp') . "\n";
echo " - Host:       " . $smtpHost . "\n";
echo " - Port:       " . $smtpPort . "\n";
echo " - Encryption: " . $smtpEnc . "\n";
echo " - Username:   " . ($smtpUser !== '' ? $smtpUser : '[NOT SET - EMPTY]') . "\n";
echo " - Password:   " . ($smtpPass !== '' ? str_repeat('*', strlen($smtpPass)) : '[NOT SET - EMPTY]') . "\n";
echo " - From Addr:  " . ($fromAddr !== '' ? $fromAddr : '[NOT SET]') . "\n\n";

if (empty($smtpUser) || empty($smtpPass)) {
    echo "[!] Notice: SMTP username or password is not configured in config/mail.php (or .env).\n";
    echo "    To send real emails using Gmail:\n";
    echo "    1. Go to https://myaccount.google.com/apppasswords\n";
    echo "    2. Generate a 16-character App Password for 'Mail'.\n";
    echo "    3. Add your email to 'username' and app password to 'password' in config/mail.php or .env.\n\n";
    if (empty($targetEmail)) {
        exit(0);
    }
}

if (empty($targetEmail)) {
    echo "To test sending an email, run:\n";
    echo "  php scripts/test_email.php your_email@gmail.com\n\n";
    exit(0);
}

echo "Attempting to send test email to: {$targetEmail}...\n\n";

$mail = new PHPMailer(true);

try {
    // Enable verbose debug output
    $mail->SMTPDebug = SMTP::DEBUG_CONNECTION;
    $mail->isSMTP();
    $mail->CharSet    = 'UTF-8';
    $mail->Host       = $smtpHost;
    $mail->SMTPAuth   = true;
    $mail->Username   = $smtpUser;
    $mail->Password   = $smtpPass;
    $mail->Port       = $smtpPort;

    if ($smtpEnc === 'ssl' || $smtpPort === 465) {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    } elseif ($smtpEnc === 'tls' || $smtpPort === 587) {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    } else {
        $mail->SMTPAutoTLS = false;
    }

    $mail->Timeout = 20;
    $mail->SMTPOptions = [
        'ssl' => [
            'verify_peer'       => false,
            'verify_peer_name'  => false,
            'allow_self_signed' => true,
        ],
    ];

    $mail->setFrom($fromAddr ?: 'noreply@lapify.com', 'Lapify Marketplace');
    $mail->addAddress($targetEmail);
    $mail->isHTML(true);
    $mail->Subject = 'Lapify SMTP Test Email';
    $mail->Body    = '<h3>Lapify SMTP Configuration Test</h3><p>If you are reading this email, your SMTP settings are <strong>working perfectly!</strong></p>';
    $mail->AltBody = 'Lapify SMTP Configuration Test: Your SMTP settings are working perfectly!';

    $mail->send();
    echo "\n[SUCCESS] Test email sent successfully to {$targetEmail}!\n";
} catch (Exception $e) {
    echo "\n[ERROR] Failed to send email:\n";
    echo $mail->ErrorInfo ?: $e->getMessage();
    echo "\n";
}
