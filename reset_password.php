<?php
// reset_password.php - Password Reset Execution
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

redirectIfLoggedIn();

$errors = [];
$success = '';
$token = trim($_GET['token'] ?? '');
$targetAccount = null;
$targetTable = 'users';

if ($token === '') {
    $errors[] = 'The password reset link is missing or invalid.';
} else {
    try {
        $pdo = getPdoConnection();
        ensureAuthSchema($pdo);
        $hash = hash('sha256', $token);

        // Check users table
        $stmt = $pdo->prepare('SELECT id, full_name, email FROM users WHERE reset_token = :hash AND (reset_expires IS NULL OR reset_expires > NOW()) LIMIT 1');
        $stmt->execute(['hash' => $hash]);
        $targetAccount = $stmt->fetch();

        // Check admins table if not found in users
        if (!$targetAccount) {
            $stmtAdmin = $pdo->prepare('SELECT id, full_name, email FROM admins WHERE reset_token = :hash AND (reset_expires IS NULL OR reset_expires > NOW()) LIMIT 1');
            $stmtAdmin->execute(['hash' => $hash]);
            $targetAccount = $stmtAdmin->fetch();
            if ($targetAccount) {
                $targetTable = 'admins';
            }
        }

        if (!$targetAccount) {
            $errors[] = 'This password reset link has expired or has already been used. Please request a new one.';
        }
    } catch (Throwable $e) {
        error_log('Reset password lookup error: ' . $e->getMessage());
        $errors[] = 'This password reset link has expired or is invalid. Please request a new one.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $targetAccount) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'This form has expired. Please try again.';
    }

    $password = (string)($_POST['password'] ?? '');
    $confirm  = (string)($_POST['confirm_password'] ?? '');

    $strength = validatePasswordStrength($password);
    if (!$strength['is_valid']) {
        $errors[] = 'Password must be at least 8 characters long and include an uppercase letter, lowercase letter, number, and special character.';
    }

    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        try {
            $pdo = getPdoConnection();
            $hash = hash('sha256', $token);
            $newPasswordHash = password_hash($password, PASSWORD_DEFAULT);

            $update = $pdo->prepare("UPDATE {$targetTable} SET password = :password, reset_token = NULL, reset_expires = NULL WHERE id = :id AND reset_token = :hash");
            $update->execute([
                'password' => $newPasswordHash,
                'id'       => (int)$targetAccount['id'],
                'hash'     => $hash,
            ]);

            if ($update->rowCount() > 0) {
                setFlash('success', 'Your password has been reset successfully. Please sign in with your new password.');
                header('Location: ' . BASE_URL . '/login.php');
                exit();
            } else {
                $errors[] = 'The reset link has expired or was already used. Please request a new link.';
            }
        } catch (Throwable $e) {
            error_log('Password reset execution error: ' . $e->getMessage());
            $errors[] = 'Failed to reset password. Please try again.';
        }
    }
}

$page_title = 'Reset Password | Lapify';
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
            <h1 class="auth-title"><i class="bi bi-shield-lock me-2 text-warning"></i>Choose New Password</h1>
            <p class="auth-subtitle">Create a strong, new password for your account.</p>
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

        <?php if ($targetAccount && empty($success)): ?>
            <form action="reset_password.php?token=<?= urlencode($token) ?>" method="POST" class="auth-form" novalidate>
                <?= renderCsrfInput() ?>
                
                <div class="auth-field">
                    <label for="password">New Password</label>
                    <div class="auth-input-wrap">
                        <i class="bi bi-lock"></i>
                        <input type="password" id="password" name="password" placeholder="Min. 8 chars, uppercase, number & symbol" required autocomplete="new-password">
                        <button type="button" class="auth-visibility-toggle" data-target="password" aria-label="Show password">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    <span class="field-error"></span>
                </div>

                <div class="auth-field">
                    <label for="confirm_password">Confirm New Password</label>
                    <div class="auth-input-wrap">
                        <i class="bi bi-lock-fill"></i>
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your new password" required autocomplete="new-password">
                        <button type="button" class="auth-visibility-toggle" data-target="confirm_password" aria-label="Show password">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    <span class="field-error"></span>
                </div>

                <button type="submit" class="auth-btn" data-loading-text="Saving Password…">
                    Save New Password <i class="bi bi-arrow-right"></i>
                </button>
            </form>
        <?php else: ?>
            <div class="text-center mt-3">
                <a href="forgot_password.php" class="btn btn-primary rounded-pill px-4 py-2.5 font-weight-bold text-white shadow-sm d-inline-block">
                    <i class="bi bi-arrow-clockwise me-1"></i> Request New Reset Link
                </a>
            </div>
        <?php endif; ?>

        <div class="auth-footer-row text-center justify-content-center mt-4">
            <span>Back to</span>
            <a href="login.php" class="auth-link">Sign In</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>