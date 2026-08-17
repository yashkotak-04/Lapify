<?php
$page_title = 'Create Account | Lapify';
$body_class = 'auth-page';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

redirectIfLoggedIn();

$errors = [];
$full_name = '';
$email = '';
$password = '';
$confirm_password = '';
$role = 'user';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'This form has expired. Please try again.';
    }

    $full_name = trim(sanitizeInput($_POST['full_name'] ?? ''));
    $email = strtolower(trim(sanitizeInput($_POST['email'] ?? '')));
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = 'user'; // Registration is exclusively for Buyers

    if ($full_name === '') {
        $errors[] = 'Full name is required.';
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email address is required.';
    }
    $passwordValidation = validatePasswordStrength($password);
    if (!$passwordValidation['is_valid']) {
        $errors[] = 'Password must be at least 8 characters with uppercase, lowercase, number, and special character.';
    }
    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        try {
            $pdo = getPdoConnection();

            $exists = $pdo->prepare('SELECT id FROM users WHERE LOWER(email) = LOWER(:email) LIMIT 1');
            $exists->execute(['email' => $email]);
            if ($exists->fetch()) {
                $errors[] = 'That email is already in use.';
            } else {
                $adminExists = $pdo->prepare('SELECT id FROM admins WHERE LOWER(email) = LOWER(:email) LIMIT 1');
                $adminExists->execute(['email' => $email]);
                if ($adminExists->fetch()) {
                    $errors[] = 'That email is already in use.';
                }
            }
        } catch (Throwable $e) {
            error_log($e->getMessage());
            $errors[] = 'The database is unavailable right now.';
        }
    }

    if (empty($errors)) {
        try {
            $pdo = getPdoConnection();
            $stmt = $pdo->prepare('INSERT INTO users (full_name, email, password, role, status) VALUES (:full_name, :email, :password, :role, :status)');
            $stmt->execute([
                'full_name' => $full_name,
                'email' => $email,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'role' => $role, // 'user' (Buyer) or 'admin' — never 'seller'
                'status' => 'active',
            ]);

            setFlash('success', 'Account created successfully. Please sign in to continue.');
            header('Location: ' . BASE_URL . '/login.php?registered=1&email=' . urlencode($email));
            exit();
        } catch (Throwable $e) {
            error_log($e->getMessage());
            $errors[] = 'Registration failed. Please try again.';
        }
    }
}
?>

<div class="auth-shell auth-shell-register">
    <div class="auth-card auth-card-register">
        <!-- Brand Logo -->
        <div class="auth-brand-logo">
            <img src="<?= BASE_URL ?>/assets/logo.svg" alt="Lapify" style="height:52px; width:auto;">
        </div>

        <!-- Tab Toggle -->
        <div class="auth-tabs" role="tablist" aria-label="Authentication">
            <button type="button" class="auth-tab" role="tab" aria-selected="false" onclick="window.location.href='login.php'">Sign In</button>
            <button type="button" class="auth-tab active" role="tab" aria-selected="true" onclick="window.location.href='register.php'">Create Account</button>
        </div>

        <div class="auth-card-header mb-2">
            <h1 class="auth-title fs-3 mb-0">Join Lapify</h1>
        </div>

        <?php displayFlash(); ?>

        <?php if (!empty($errors)): ?>
            <div class="auth-alert py-2 mb-2" role="alert">
                <ul class="mb-0">
                    <?php foreach ($errors as $err): ?>
                        <li><?= escape($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="register.php" method="POST" class="auth-form" novalidate>
            <?= renderCsrfInput() ?>

            <input type="hidden" name="role" value="user">

            <div class="auth-field">
                <label for="full_name">Full Name</label>
                <div class="auth-input-wrap">
                    <i class="bi bi-person"></i>
                    <input type="text" id="full_name" name="full_name" value="<?= escape($full_name) ?>" placeholder="Enter your full name" required autocomplete="name">
                </div>
                <span class="field-error"></span>
            </div>

            <div class="auth-field">
                <label for="email">Email Address</label>
                <div class="auth-input-wrap">
                    <i class="bi bi-envelope"></i>
                    <input type="email" id="email" name="email" value="<?= escape($email) ?>" placeholder="name@example.com" required autocomplete="email">
                </div>
                <span class="field-error"></span>
            </div>

            <div class="auth-field">
                <label for="password">Password</label>
                <div class="auth-input-wrap">
                    <i class="bi bi-lock"></i>
                    <input type="password" id="password" name="password" placeholder="Min 8 chars, uppercase, lowercase, number, symbol" required autocomplete="new-password">
                    <button type="button" class="auth-visibility-toggle" data-target="password" aria-label="Show password">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
                <span class="field-error"></span>
            </div>

            <div class="auth-field">
                <label for="confirm_password">Confirm Password</label>
                <div class="auth-input-wrap">
                    <i class="bi bi-lock-fill"></i>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your password" required autocomplete="new-password">
                    <button type="button" class="auth-visibility-toggle" data-target="confirm_password" aria-label="Show password">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
                <span class="field-error"></span>
            </div>

            <div class="my-2">
                <label class="auth-checkbox">
                    <input type="checkbox" id="terms-checkbox" required>
                    <span>I agree to Lapify's <a href="about.php" class="auth-link">Terms of Service</a> and <a href="about.php" class="auth-link">Privacy Policy</a></span>
                </label>
            </div>

            <button type="submit" id="create-account-btn" class="auth-btn w-100 py-3" data-loading-text="Creating Account…" disabled>
                Create Account <i class="bi bi-arrow-right ms-2"></i>
            </button>
        </form>

        <div class="auth-footer-row justify-content-center text-center mt-3 pt-2 border-top border-secondary-subtle">
            <span>Already have an account?</span>
            <a href="login.php" class="auth-link">Sign in</a>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const termsCheckbox = document.getElementById('terms-checkbox');
    const createBtn = document.getElementById('create-account-btn');

    function updateButtonState() {
        if (createBtn && termsCheckbox) {
            createBtn.disabled = !termsCheckbox.checked;
        }
    }

    if (termsCheckbox) {
        termsCheckbox.addEventListener('change', updateButtonState);
    }

    // Role selector pill click handler (supports .auth-role and .auth-role-pill)
    document.querySelectorAll('.auth-role, .auth-role-pill').forEach(function (role) {
        role.addEventListener('click', function () {
            const radio = role.querySelector('input[type="radio"]');
            if (radio) radio.checked = true;
            document.querySelectorAll('.auth-role, .auth-role-pill').forEach(function (r) {
                r.classList.toggle('selected', r === role);
            });
        });
    });

    updateButtonState();
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>