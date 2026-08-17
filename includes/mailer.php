<?php
// includes/mailer.php - Mail Dispatcher Service with PHPMailer & Dev Logging Fallback

require_once __DIR__ . '/../config/config.php';
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Send a Password Reset Email using PHPMailer with graceful Local Dev Fallback.
 *
 * @param string $toEmail  Recipient email address
 * @param string $toName   Recipient name
 * @param string $resetUrl Password reset link
 * @return bool True if sent or successfully logged locally, false on fatal failure.
 */
function sendPasswordResetEmail(string $toEmail, string $toName, string $resetUrl): bool {
    $mailConfig = [];
    $configFile = __DIR__ . '/../config/mail.php';
    if (file_exists($configFile)) {
        $mailConfig = require $configFile;
    }

    $smtpHost = $mailConfig['host'] ?? 'smtp.gmail.com';
    $smtpPort = (int)($mailConfig['port'] ?? 587);
    $smtpUser = trim((string)($mailConfig['username'] ?? ''));
    $smtpPass = trim((string)($mailConfig['password'] ?? ''));
    $smtpEnc  = strtolower(trim((string)($mailConfig['encryption'] ?? 'tls')));
    
    // Determine sender address: use configured address, or default to smtpUser if available
    $configuredFrom = trim((string)($mailConfig['from']['address'] ?? ''));
    if (!empty($configuredFrom) && filter_var($configuredFrom, FILTER_VALIDATE_EMAIL)) {
        $fromAddr = $configuredFrom;
    } elseif (!empty($smtpUser) && filter_var($smtpUser, FILTER_VALIDATE_EMAIL)) {
        $fromAddr = $smtpUser;
    } else {
        $fromAddr = 'noreply@lapify.com';
    }

    $fromName = !empty($mailConfig['from']['name']) ? $mailConfig['from']['name'] : 'Lapify Marketplace';
    $logFile  = $mailConfig['log_file'] ?? (__DIR__ . '/../logs/password_resets.log');

    $isLocal = in_array(strtolower($_SERVER['HTTP_HOST'] ?? 'localhost'), ['localhost', '127.0.0.1', '::1'], true)
               || stripos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false;

    // Check if SMTP credentials are provided
    $hasSmtpCreds = !empty($smtpUser) && !empty($smtpPass);

    $safeName = !empty($toName) ? htmlspecialchars($toName, ENT_QUOTES, 'UTF-8') : 'Lapify User';
    $safeUrl  = htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8');

    // Build the responsive branded HTML email body
    $year = date('Y');
    $htmlBody = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Your Lapify Password</title>
</head>
<body style="margin:0; padding:0; background-color:#f1f5f9; font-family:'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color:#0f172a;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#f1f5f9; padding:40px 15px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:560px; background-color:#ffffff; border-radius:18px; box-shadow:0 10px 25px rgba(15,23,42,0.08); overflow:hidden; border:1px solid #e2e8f0;">
                    <!-- Brand Header -->
                    <tr>
                        <td style="background:linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #2563eb 100%); padding:32px 30px; text-align:center;">
                            <h1 style="margin:0; font-size:26px; font-weight:800; color:#ffffff; letter-spacing:0.02em;">
                                <span style="color:#38bdf8;">Lap</span>ify
                            </h1>
                            <p style="margin:6px 0 0; color:#94a3b8; font-size:13px; font-weight:500;">Laptop Marketplace &amp; Exchange</p>
                        </td>
                    </tr>
                    <!-- Main Content -->
                    <tr>
                        <td style="padding:36px 32px 28px;">
                            <h2 style="margin:0 0 16px; font-size:20px; font-weight:700; color:#0f172a;">Password Reset Request</h2>
                            <p style="margin:0 0 16px; font-size:15px; line-height:1.6; color:#475569;">
                                Hello <strong>{$safeName}</strong>,
                            </p>
                            <p style="margin:0 0 24px; font-size:15px; line-height:1.6; color:#475569;">
                                We received a request to reset the password for your Lapify account. Click the button below to choose a new password:
                            </p>
                            <!-- CTA Button -->
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:0 auto 28px;">
                                <tr>
                                    <td align="center" style="border-radius:12px; background:linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); box-shadow:0 6px 18px rgba(37,99,235,0.35);">
                                        <a href="{$safeUrl}" target="_blank" style="display:inline-block; padding:14px 32px; font-size:15px; font-weight:700; color:#ffffff; text-decoration:none; border-radius:12px;">
                                            Reset My Password &rarr;
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin:0 0 16px; font-size:13px; line-height:1.6; color:#64748b;">
                                This reset link will expire in <strong>60 minutes</strong> and can be used only once.
                            </p>
                            <p style="margin:0 0 24px; font-size:13px; line-height:1.6; color:#64748b;">
                                If you did not request a password reset, you can safely ignore this email. Your current password will remain unchanged.
                            </p>
                            <hr style="border:0; border-top:1px solid #e2e8f0; margin:24px 0 16px;">
                            <p style="margin:0; font-size:12px; line-height:1.5; color:#94a3b8; word-break:break-all;">
                                Having trouble with the button? Copy and paste this URL into your browser:<br>
                                <a href="{$safeUrl}" style="color:#2563eb; text-decoration:underline;">{$safeUrl}</a>
                            </p>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="background-color:#f8fafc; padding:20px 30px; text-align:center; border-top:1px solid #e2e8f0;">
                            <p style="margin:0; font-size:12px; color:#94a3b8;">
                                &copy; {$year} Lapify. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;

    $plainBody = "Hello {$toName},\n\n"
               . "We received a request to reset your Lapify password.\n"
               . "Please open the link below to set a new password:\n\n"
               . "{$resetUrl}\n\n"
               . "This link expires in 60 minutes and can only be used once.\n\n"
               . "If you did not request this, you can safely ignore this message.\n\n"
               . "— The Lapify Team";

    // If SMTP credentials exist, send real email via PHPMailer
    if ($hasSmtpCreds && class_exists(PHPMailer::class)) {
        try {
            $mail = new PHPMailer(true);
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

            $mail->setFrom($fromAddr, $fromName);
            if (!empty($smtpUser) && filter_var($smtpUser, FILTER_VALIDATE_EMAIL)) {
                $mail->addReplyTo($smtpUser, $fromName);
            }
            $mail->addAddress($toEmail, $toName);
            $mail->isHTML(true);
            $mail->Subject = 'Reset Your Lapify Password';
            $mail->Body    = $htmlBody;
            $mail->AltBody = $plainBody;

            $mail->send();

            $_SESSION['last_mail_dispatch'] = [
                'smtp_sent' => true,
                'driver'    => 'smtp',
                'email'     => $toEmail,
                'url'       => $resetUrl,
                'error'     => null,
            ];
            return true;
        } catch (Exception $e) {
            $errorMsg = $mail->ErrorInfo ?: $e->getMessage();
            error_log('PHPMailer SMTP Error: ' . $errorMsg);
            $_SESSION['last_mail_dispatch'] = [
                'smtp_sent' => false,
                'driver'    => 'smtp_failed',
                'email'     => $toEmail,
                'url'       => $resetUrl,
                'error'     => $errorMsg,
            ];
            // Fall back to dev log if in local environment
            if ($isLocal) {
                recordLocalResetLog($logFile, $toEmail, $toName, $resetUrl, 'SMTP send failed: ' . $errorMsg);
                return true;
            }
            return false;
        }
    }

    $_SESSION['last_mail_dispatch'] = [
        'smtp_sent' => false,
        'driver'    => 'log',
        'email'     => $toEmail,
        'url'       => $resetUrl,
        'error'     => 'SMTP credentials not configured in config/mail.php',
    ];

    // Local Development Fallback: If running locally without configured SMTP credentials
    if ($isLocal) {
        recordLocalResetLog($logFile, $toEmail, $toName, $resetUrl, 'Local development mode (SMTP credentials not configured)');
        return true;
    }

    error_log("SMTP credentials missing in production environment. Mail to {$toEmail} could not be sent.");
    return false;
}

/**
 * Write a password reset development entry to the protected logs directory.
 */
function recordLocalResetLog(string $logFile, string $toEmail, string $toName, string $resetUrl, string $reason = ''): void {
    $dir = dirname($logFile);
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }

    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "============================================================\n"
              . "[{$timestamp}] PASSWORD RESET LINK (LOCAL DEV FALLBACK)\n"
              . "Reason:   {$reason}\n"
              . "To:       {$toName} <{$toEmail}>\n"
              . "Reset URL: {$resetUrl}\n"
              . "============================================================\n\n";

    @file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    error_log("[Lapify Mailer] Reset password link for {$toEmail}: {$resetUrl}");
}
