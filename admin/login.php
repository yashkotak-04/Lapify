<?php
$admin_title = 'Admin Login | Lapify';
$body_class = 'auth-page';
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

if (isAdmin()) {
    header('Location: ' . BASE_URL . '/admin/dashboard.php');
    exit();
}

$errors = [];
$identifier = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'This form has expired. Please try again.';
    }

    $identifier = trim(sanitizeInput($_POST['username'] ?? ''));
    $password = $_POST['password'] ?? '';

    if ($identifier === '') {
        $errors[] = 'Admin email is required.';
    } elseif (!filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid admin email address.';
    }
    if ($password === '') {
        $errors[] = 'Password is required.';
    }

    if (empty($errors)) {
        try {
            $pdo = getPdoConnection();
            $adminUser = null;
            $userTable = 'admins';

            $stmt = $pdo->prepare('SELECT id, full_name, username, email, phone, profile_image, password, secret_key, status FROM admins WHERE LOWER(email) = LOWER(?) OR LOWER(username) = LOWER(?) LIMIT 1');
            $stmt->execute([$identifier, $identifier]);
            $candidate = $stmt->fetch();

            if ($candidate) {
                $adminUser = $candidate;
                $userTable = 'admins';
            } else {
                // Fallback: search in users table where role = 'admin'
                $stmtUser = $pdo->prepare('SELECT id, full_name, email, password, role, status FROM users WHERE LOWER(email) = LOWER(:identifier) AND role = "admin" LIMIT 1');
                $stmtUser->execute(['identifier' => $identifier]);
                $candidateUser = $stmtUser->fetch();
                if ($candidateUser) {
                    $adminUser = $candidateUser;
                    $userTable = 'users';
                }
            }

            $passwordOk = false;
            $needsHashUpgrade = false;

            if ($adminUser) {
                $storedHash = $adminUser['password'] ?? '';
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

            if ($adminUser && $passwordOk) {
                if (($adminUser['status'] ?? 'active') !== 'active') {
                    $errors[] = 'This administrator account is inactive.';
                } else {
                    if ($needsHashUpgrade) {
                        try {
                            $newHash = password_hash($password, PASSWORD_DEFAULT);
                            $upStmt = $pdo->prepare("UPDATE {$userTable} SET password = :hash WHERE id = :id");
                            $upStmt->execute(['hash' => $newHash, 'id' => (int)$adminUser['id']]);
                        } catch (Throwable $ignore) {}
                    }

                    session_regenerate_id(true);
                    $_SESSION['admin_id'] = (int) $adminUser['id'];
                    $_SESSION['user_id'] = (int) $adminUser['id'];
                    $_SESSION['full_name'] = $adminUser['full_name'];
                    $_SESSION['email'] = $adminUser['email'];
                    $_SESSION['phone'] = $adminUser['phone'] ?? '';
                    $_SESSION['profile_image'] = $adminUser['profile_image'] ?? null;
                    $_SESSION['role'] = 'admin';
                    setFlash('success', 'Admin access granted.');
                    header('Location: ' . BASE_URL . '/admin/dashboard.php?login_success=1');
                    exit();
                }
            } else {
                $errors[] = 'Invalid admin credentials.';
            }
        } catch (Throwable $e) {
            error_log($e->getMessage());
            $errors[] = 'Admin authentication is unavailable right now.';
        }
    }
}
?>

<div class="auth-shell">
    <div class="auth-card">
        <!-- Brand Logo -->
        <div class="auth-brand-logo mb-3 text-center">
            <a href="<?= BASE_URL ?>/index.php" title="Lapify Marketplace">
                <img src="<?= BASE_URL ?>/assets/logo.svg" alt="Lapify" style="height: 54px; width: auto; max-width: 100%;">
            </a>
        </div>

        <!-- Tab Toggle -->
        <div class="auth-tabs" role="tablist" aria-label="Authentication">
            <button type="button" class="auth-tab" role="tab" aria-selected="false" onclick="window.location.href='<?= BASE_URL ?>/login.php'">User Login</button>
            <button type="button" class="auth-tab active" role="tab" aria-selected="true" onclick="window.location.href='<?= BASE_URL ?>/admin/login.php'">Admin Portal</button>
        </div>

        <div class="auth-card-header">
            <h1 class="auth-title d-flex align-items-center justify-content-center gap-2">
                <i class="bi bi-shield-lock-fill text-primary"></i>
                <span>Lapify Admin Login</span>
            </h1>
            <p class="auth-subtitle">Sign in to access the administrator command center.</p>
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

        <form action="<?= BASE_URL ?>/admin/login.php" method="POST" class="auth-form" novalidate>
            <?= renderCsrfInput() ?>

            <!-- Role selector: Buyer + Admin compact horizontal pills -->
            <div class="auth-field mb-2">
                <div class="d-flex align-items-center justify-content-between">
                    <label class="mb-0">LOG IN AS:</label>
                    <div class="auth-roles-pills">
                        <label class="auth-role-pill" onclick="window.location.href='<?= BASE_URL ?>/login.php';">
                            <input type="radio" name="login_role" value="user">
                            <i class="bi bi-cart3"></i> Buyer
                        </label>
                        <label class="auth-role-pill selected">
                            <input type="radio" name="login_role" value="admin" checked>
                            <i class="bi bi-shield-lock"></i> Admin
                        </label>
                    </div>
                </div>
            </div>

            <div class="auth-field">
                <label for="email">Admin Email Address</label>
                <div class="auth-input-wrap">
                    <i class="bi bi-envelope"></i>
                    <input type="email" id="email" name="username" value="<?= escape($identifier) ?>" placeholder="admin@lapify.com" required autocomplete="email">
                </div>
                <span class="field-error"></span>
            </div>

            <div class="auth-field">
                <label for="password">Password</label>
                <div class="auth-input-wrap">
                    <i class="bi bi-lock"></i>
                    <input type="password" id="password" name="password" placeholder="Enter your admin password" required autocomplete="current-password">
                    <button type="button" class="auth-visibility-toggle" data-target="password" aria-label="Show password">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
                <span class="field-error"></span>
            </div>

            <div class="auth-options">
                <label class="auth-checkbox">
                    <input type="checkbox" name="remember_me" value="1" checked>
                    <span>Remember me</span>
                </label>
                <a href="<?= BASE_URL ?>/login.php" class="auth-link">User Login</a>
            </div>

            <button type="submit" class="auth-btn" data-loading-text="Authenticating Admin…">
                Sign In as Admin <i class="bi bi-arrow-right"></i>
            </button>
        </form>

        <div class="auth-footer-row mt-4">
            <span>Need a buyer account?</span>
            <a href="<?= BASE_URL ?>/register.php" class="auth-link">Create account</a>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.auth-role-pill').forEach(function (role) {
        role.addEventListener('click', function () {
            const radio = role.querySelector('input[type="radio"]');
            if (radio && radio.value === 'user') {
                window.location.href = '<?= BASE_URL ?>/login.php';
                return;
            }
        });
    });
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>