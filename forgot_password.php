<?php
// forgot_password.php - Password Reset Request
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/mailer.php';

redirectIfLoggedIn();

$errors = [];
$success = '';
$email = '';
$devResetLink = '';
$devNotFoundEmail = '';
$devDispatchError = '';
$isSmtpSent = false;
$isSmtpAttemptFailed = false;

$isLocal = in_array(strtolower($_SERVER['HTTP_HOST'] ?? 'localhost'), ['localhost', '127.0.0.1', '::1'], true)
           || stripos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'This form has expired. Please try again.';
    }

    $email = strtolower(trim(sanitizeInput($_POST['email'] ?? '')));

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (empty($errors)) {
        try {
            $pdo = getPdoConnection();
            ensureAuthSchema($pdo);

            $account = null;
            $table = 'users';

            // Check users table first
            $stmt = $pdo->prepare('SELECT id, full_name, email FROM users WHERE LOWER(TRIM(email)) = LOWER(:email) LIMIT 1');
            $stmt->execute(['email' => $email]);
            $account = $stmt->fetch();

            // Check admins table if not found in users
            if (!$account) {
                $stmtAdmin = $pdo->prepare('SELECT id, full_name, email FROM admins WHERE LOWER(TRIM(email)) = LOWER(:email) LIMIT 1');
                $stmtAdmin->execute(['email' => $email]);
                $account = $stmtAdmin->fetch();
                if ($account) {
                    $table = 'admins';
                }
            }

            if ($account) {
                $token = bin2hex(random_bytes(32));
                $hash = hash('sha256', $token);

                $update = $pdo->prepare("UPDATE {$table} SET reset_token = ?, reset_expires = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE id = ?");
                $update->execute([$hash, (int)$account['id']]);

                $resetUrl = BASE_URL . '/reset_password.php?token=' . urlencode($token);
                sendPasswordResetEmail($account['email'], $account['full_name'], $resetUrl);

                $dispatch = $_SESSION['last_mail_dispatch'] ?? [];
                if (!empty($dispatch['smtp_sent'])) {
                    $isSmtpSent = true;
                    $success = 'A password reset link has been sent to ' . escape($account['email']) . '. Please check your inbox and spam folder.';
                } else {
                    $success = 'Password reset link generated for ' . escape($account['email']) . '.';
                    $devDispatchError = (string)($dispatch['error'] ?? '');
                    $isSmtpAttemptFailed = (($dispatch['driver'] ?? '') === 'smtp_failed');
                    if ($isLocal) {
                        $devResetLink = $resetUrl;
                    }
                }
            } else {
                // If on local dev, let developer know email was not in database
                if ($isLocal) {
                    $devNotFoundEmail = $email;
                }
                $success = 'If an account exists with this email address, a password reset link has been sent. Please check your inbox.';
            }
        } catch (Throwable $e) {
            error_log('Forgot password error: ' . $e->getMessage());
            $errors[] = 'We could not process your request right now. Please try again later.';
        }
    }

    $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || 
              (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'fetch') ||
              (!empty($_POST['ajax']) || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')));

    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        if (!empty($errors)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
            exit();
        }
        echo json_encode([
            'success' => true,
            'message' => $success,
            'is_smtp_sent' => $isSmtpSent,
            'dev_reset_link' => $devResetLink,
            'dev_not_found_email' => $devNotFoundEmail,
            'dev_dispatch_error' => $devDispatchError,
            'is_smtp_attempt_failed' => $isSmtpAttemptFailed,
        ]);
        exit();
    }
}

$page_title = 'Forgot Password | Lapify';
$body_class = 'auth-page';
require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-shell">
    <div class="auth-card">
        <!-- Brand Logo -->
        <div class="auth-brand-logo">
            <img src="<?= BASE_URL ?>/assets/logo.svg" alt="Lapify" style="height:52px; width:auto;">
        </div>

        <div class="auth-card-header">
            <h1 class="auth-title"><i class="bi bi-key me-2 text-warning"></i>Reset Your Password</h1>
            <p class="auth-subtitle">Enter your registered email address below and we'll send you a secure password reset link.</p>
        </div>

        <?php displayFlash(); ?>

        <?php if (!empty($errors)): ?>
            <div class="auth-alert" role="alert">
                <ul>
                    <?php foreach ($errors as $err): ?>
                        <li><?= escape($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($success !== ''): ?>
            <?php if ($isSmtpSent): ?>
                <div class="alert alert-success border-0 rounded-4 shadow-sm py-3 px-4 mb-4" role="status" style="background: rgba(34, 197, 94, 0.12); border: 1.5px solid rgba(34, 197, 94, 0.4) !important; color: #15803d !important;">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <i class="bi bi-check-circle-fill text-success fs-5"></i>
                        <strong>Email Sent Successfully!</strong>
                    </div>
                    <p class="mb-0 small" style="color: #166534 !important; font-weight: 500;"><?= $success ?></p>
                </div>
            <?php else: ?>
                <div class="alert alert-success border-0 rounded-4 shadow-sm py-3 px-4 mb-3" role="status" style="background: rgba(34, 197, 94, 0.12); border: 1.5px solid rgba(34, 197, 94, 0.4) !important; color: #15803d !important;">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <i class="bi bi-check-circle-fill text-success fs-5"></i>
                        <strong>Reset Request Processed</strong>
                    </div>
                    <p class="mb-0 small" style="color: #166534 !important; font-weight: 500;"><?= $success ?></p>
                </div>

                <?php if (!empty($devResetLink)): ?>
                    <div class="alert alert-primary border-0 rounded-4 shadow-sm p-4 mb-4 text-start" style="background: rgba(37, 99, 235, 0.08); border: 1.5px solid rgba(37, 99, 235, 0.3) !important;">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                            <span class="text-primary fw-bold fs-6">
                                <i class="bi bi-shield-lock-fill me-1"></i> Local Development Reset Link
                            </span>
                            <span class="badge bg-primary text-white px-2 py-1 small rounded-pill">Localhost Mode</span>
                        </div>

                        <?php if ($isSmtpAttemptFailed): ?>
                            <div class="alert alert-danger py-2 px-3 mb-3 small rounded-3">
                                <strong><i class="bi bi-exclamation-triangle-fill me-1"></i> SMTP Delivery Failed:</strong><br>
                                <code><?= escape($devDispatchError) ?></code>
                            </div>
                        <?php else: ?>
                            <p class="small text-secondary mb-3">
                                Running on <strong>localhost</strong> without SMTP credentials in <code>config/mail.php</code>. Because local XAMPP cannot send real emails without SMTP credentials, you can open your reset link directly below:
                            </p>
                        <?php endif; ?>

                        <div class="d-flex gap-2 flex-wrap mb-3">
                            <a href="<?= escape($devResetLink) ?>" class="btn btn-primary btn-sm rounded-pill px-4 py-2 font-weight-bold text-white shadow-sm">
                                <i class="bi bi-box-arrow-up-right me-1"></i> Open Password Reset Page
                            </a>
                        </div>

                        <details class="small text-muted" style="cursor: pointer;">
                            <summary class="fw-semibold text-primary">How to send real emails to Gmail from localhost?</summary>
                            <div class="mt-2 p-3 bg-white rounded-3 border">
                                <ol class="ps-3 mb-1 text-secondary">
                                    <li>Open your Google Account &rarr; <strong>Security</strong> &rarr; Enable <strong>2-Step Verification</strong>.</li>
                                    <li>Search for <strong>App Passwords</strong> and create one for "Mail".</li>
                                    <li>Open <code>config/mail.php</code> (or <code>.env</code>) in your project.</li>
                                    <li>Set <code>'username' =&gt; 'your_email@gmail.com'</code> and <code>'password' =&gt; 'your-16-char-app-password'</code>.</li>
                                </ol>
                            </div>
                        </details>
                    </div>
                <?php elseif (!empty($devNotFoundEmail)): ?>
                    <div class="alert alert-warning border-0 rounded-4 shadow-sm p-3 mb-4 text-start" style="background: rgba(234, 179, 8, 0.12); border: 1.5px solid rgba(234, 179, 8, 0.4) !important;">
                        <div class="small text-warning-emphasis">
                            <i class="bi bi-info-circle-fill me-1"></i> <strong>Dev Notice:</strong> No account found registered with email <code><?= escape($devNotFoundEmail) ?></code> in the database.
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        <?php endif; ?>

        <form action="forgot_password.php" method="POST" class="auth-form" novalidate>
            <?= renderCsrfInput() ?>
            
            <div class="auth-field">
                <label for="email">Email Address</label>
                <div class="auth-input-wrap">
                    <i class="bi bi-envelope"></i>
                    <input type="email" id="email" name="email" value="<?= escape($email) ?>" placeholder="name@example.com" required autocomplete="email">
                </div>
                <span class="field-error"></span>
            </div>

            <button type="submit" class="auth-btn" data-loading-text="Sending Link…">
                Send Reset Link <i class="bi bi-arrow-right"></i>
            </button>
        </form>

        <div class="auth-footer-row text-center justify-content-center mt-4">
            <span>Remembered your password?</span>
            <a href="login.php" class="auth-link">Back to Sign In</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>