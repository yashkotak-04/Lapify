<?php
// login.php - User & Admin Sign In
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

redirectIfLoggedIn();

$errors = [];
$email = strtolower(trim(sanitizeInput($_GET['email'] ?? '')));
$role_choice = strtolower(trim(sanitizeInput($_POST['login_role'] ?? 'user')));
$remember = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'This form has expired. Please try again.';
    }

    $email = strtolower(trim(sanitizeInput($_POST['email'] ?? $email)));
    $password = $_POST['password'] ?? '';
    $remember = !empty($_POST['remember_me']);

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if ($password === '') {
        $errors[] = 'Password is required.';
    }

    if (empty($errors)) {
        try {
            $pdo = getPdoConnection();
            $authenticatedUser = null;
            $userTable = 'users';

            // Depending on role choice, prioritize matching table
            if ($role_choice === 'admin') {
                $stmtAdmin = $pdo->prepare('SELECT id, full_name, email, phone, password, status FROM admins WHERE LOWER(email) = LOWER(?) OR LOWER(username) = LOWER(?) LIMIT 1');
                $stmtAdmin->execute([$email, $email]);
                $candidateAdmin = $stmtAdmin->fetch();
                if ($candidateAdmin) {
                    $candidateAdmin['role'] = 'admin';
                    $authenticatedUser = $candidateAdmin;
                    $userTable = 'admins';
                } else {
                    $stmt = $pdo->prepare('SELECT id, full_name, email, phone, password, role, status FROM users WHERE LOWER(email) = LOWER(:identifier) LIMIT 1');
                    $stmt->execute(['identifier' => $email]);
                    $candidate = $stmt->fetch();
                    if ($candidate) {
                        $authenticatedUser = $candidate;
                        $userTable = 'users';
                    }
                }
            } else {
                $stmt = $pdo->prepare('SELECT id, full_name, email, phone, password, role, status FROM users WHERE LOWER(email) = LOWER(:identifier) LIMIT 1');
                $stmt->execute(['identifier' => $email]);
                $candidate = $stmt->fetch();
                if ($candidate) {
                    $authenticatedUser = $candidate;
                    $userTable = 'users';
                } else {
                    $stmtAdmin = $pdo->prepare('SELECT id, full_name, email, phone, password, status FROM admins WHERE LOWER(email) = LOWER(?) OR LOWER(username) = LOWER(?) LIMIT 1');
                    $stmtAdmin->execute([$email, $email]);
                    $candidateAdmin = $stmtAdmin->fetch();
                    if ($candidateAdmin) {
                        $candidateAdmin['role'] = 'admin';
                        $authenticatedUser = $candidateAdmin;
                        $userTable = 'admins';
                    }
                }
            }

            $passwordOk = false;
            $needsHashUpgrade = false;

            if ($authenticatedUser) {
                $storedHash = $authenticatedUser['password'] ?? '';
                if (password_verify($password, $storedHash)) {
                    $passwordOk = true;
                } elseif ($password === $storedHash) {
                    $passwordOk = true;
                    $needsHashUpgrade = true;
                } elseif (md5($password) === $storedHash) {
                    $passwordOk = true;
                    $needsHashUpgrade = true;
                }
            }

            if ($authenticatedUser && $passwordOk) {
                if (($authenticatedUser['status'] ?? 'active') !== 'active') {
                    $errors[] = 'This account is currently inactive.';
                } else {
                    // Auto-upgrade password hash if it was plaintext or legacy md5
                    if ($needsHashUpgrade) {
                        try {
                            $newHash = password_hash($password, PASSWORD_DEFAULT);
                            $upStmt = $pdo->prepare("UPDATE {$userTable} SET password = :hash WHERE id = :id");
                            $upStmt->execute(['hash' => $newHash, 'id' => (int)$authenticatedUser['id']]);
                        } catch (Throwable $ignore) {}
                    }

                    session_regenerate_id(true);
                    $_SESSION['user_id'] = (int) $authenticatedUser['id'];
                    $_SESSION['full_name'] = $authenticatedUser['full_name'];
                    $_SESSION['email'] = $authenticatedUser['email'];
                    $_SESSION['phone'] = $authenticatedUser['phone'] ?? '';
                    $_SESSION['role'] = $authenticatedUser['role'] ?? 'user';

                    if ($userTable === 'admins') {
                        $_SESSION['admin_id'] = (int) $authenticatedUser['id'];
                    }

                    if ($remember) {
                        $token = bin2hex(random_bytes(32));
                        $expiry = date('Y-m-d H:i:s', strtotime('+30 days'));
                        $tokenHash = hash('sha256', $token);
                        try {
                            $update = $pdo->prepare("UPDATE {$userTable} SET remember_token = ?, remember_expiry = ? WHERE id = ?");
                            $update->execute([$tokenHash, $expiry, (int) $authenticatedUser['id']]);
                            setcookie('lapify_remember', (int) $authenticatedUser['id'] . ':' . $token, time() + 60 * 60 * 24 * 30, '/', '', true, true);
                        } catch (Throwable $ignore) {}
                    } else {
                        setcookie('lapify_remember', '', time() - 3600, '/', '', true, true);
                    }

                    setFlash('success', 'Welcome back, ' . $authenticatedUser['full_name'] . '!');

                    // Role-based redirect: Admin → admin dashboard, Buyer → user dashboard
                    $role = strtolower((string)($authenticatedUser['role'] ?? 'user'));
                    $redirectTarget = ($role === 'admin' || $userTable === 'admins') 
                        ? BASE_URL . '/admin/dashboard.php' 
                        : BASE_URL . '/dashboard.php';

                    $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || 
                              (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'fetch') ||
                              (!empty($_POST['ajax']) || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')));

                    if ($isAjax) {
                        header('Content-Type: application/json; charset=utf-8');
                        echo json_encode([
                            'success' => true,
                            'message' => 'Welcome back, ' . $authenticatedUser['full_name'] . '!',
                            'full_name' => $authenticatedUser['full_name'],
                            'redirect' => $redirectTarget
                        ]);
                        exit();
                    }

                    header('Location: ' . $redirectTarget . '?login_success=1');
                    exit();
                }
            } else {
                $errors[] = 'Invalid email or password.';
                setFlash('error', 'Invalid email or password. Please try again.');
            }
        } catch (Throwable $e) {
            error_log($e->getMessage());
            $errors[] = 'We could not sign you in right now. Please try again later.';
            setFlash('error', 'We could not sign you in right now. Please try again later.');
        }

        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || 
                  (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'fetch') ||
                  (!empty($_POST['ajax']) || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')));

        if ($isAjax && !empty($errors)) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(422);
            echo json_encode([
                'success' => false,
                'message' => implode(' ', $errors)
            ]);
            exit();
        }
    }
}

$page_title = 'Sign In | Lapify';
$body_class = 'auth-page';
require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-shell">
    <div class="auth-card">
        <!-- Brand Logo -->
        <div class="auth-brand-logo">
            <img src="<?= BASE_URL ?>/assets/logo.svg" alt="Lapify" style="height:52px; width:auto;">
        </div>

        <!-- Tab Toggle -->
        <div class="auth-tabs" role="tablist" aria-label="Authentication">
            <button type="button" class="auth-tab active" role="tab" aria-selected="true" onclick="window.location.href='login.php'">Sign In</button>
            <button type="button" class="auth-tab" role="tab" aria-selected="false" onclick="window.location.href='register.php'">Create Account</button>
        </div>

        <div class="auth-card-header">
            <h1 class="auth-title">Welcome back</h1>
            <p class="auth-subtitle">Sign in to continue your Lapify experience.</p>
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

        <form action="login.php" method="POST" class="auth-form" novalidate>
            <?= renderCsrfInput() ?>

            <!-- Role selector: Buyer + Admin compact horizontal pills -->
            <div class="auth-field mb-2">
                <div class="d-flex align-items-center justify-content-between">
                    <label class="mb-0">LOG IN AS:</label>
                    <div class="auth-roles-pills">
                        <label class="auth-role-pill <?= ($role_choice ?? 'user') === 'user' ? 'selected' : '' ?>">
                            <input type="radio" name="login_role" value="user" <?= ($role_choice ?? 'user') === 'user' ? 'checked' : '' ?>>
                            <i class="bi bi-cart3"></i> Buyer
                        </label>
                        <label class="auth-role-pill <?= ($role_choice ?? 'user') === 'admin' ? 'selected' : '' ?>" onclick="window.location.href='admin/login.php';">
                            <input type="radio" name="login_role" value="admin" <?= ($role_choice ?? 'user') === 'admin' ? 'checked' : '' ?>>
                            <i class="bi bi-shield-lock"></i> Admin
                        </label>
                    </div>
                </div>
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
                    <input type="password" id="password" name="password" placeholder="Enter your password" required autocomplete="current-password">
                    <button type="button" class="auth-visibility-toggle" data-target="password" aria-label="Show password">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
                <span class="field-error"></span>
            </div>

            <div class="auth-options">
                <label class="auth-checkbox">
                    <input type="checkbox" name="remember_me" value="1" <?= $remember ? 'checked' : '' ?>>
                    <span>Remember me</span>
                </label>
                <a href="forgot_password.php" class="auth-link">Forgot password?</a>
            </div>

            <button type="submit" class="auth-btn" data-loading-text="Signing In…">
                Sign In <i class="bi bi-arrow-right"></i>
            </button>
        </form>

        <div class="auth-footer-row mt-4">
            <span>Don't have an account?</span>
            <a href="register.php" class="auth-link">Create one</a>
        </div>

        <div class="text-center mt-3 pt-3 border-top border-secondary-subtle">
            <a href="<?= BASE_URL ?>/index.php" class="auth-back-btn">
                <i class="bi bi-arrow-left"></i> <span>Back to Home</span>
            </a>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.auth-role-pill').forEach(function (role) {
        role.addEventListener('click', function () {
            const radio = role.querySelector('input[type="radio"]');
            if (radio && radio.value === 'admin') {
                window.location.href = 'admin/login.php';
                return;
            }
            if (radio) radio.checked = true;
            document.querySelectorAll('.auth-role-pill').forEach(function (r) {
                r.classList.toggle('selected', r === role);
            });
        });
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>