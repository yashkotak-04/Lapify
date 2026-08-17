<?php
// includes/auth.php - Authentication and Authorization Helpers

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

/**
 * Check if a user is logged in
 * @return bool
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if current user is an admin
 * @return bool
 */
function isAdmin(): bool {
    return isLoggedIn() && (
        (!empty($_SESSION['role']) && strtolower($_SESSION['role']) === 'admin') ||
        !empty($_SESSION['admin_id'])
    );
}

/**
 * Require login for protected pages. Redirects to login.php if unauthenticated.
 */
function requireLogin(): void {
    if (!isLoggedIn()) {
        $_SESSION['flash_error'] = "Please log in to access this page.";
        header("Location: " . BASE_URL . "/login.php");
        exit();
    }
}

/**
 * Require admin privileges. Redirects non-admins to main home or login.
 */
function requireAdmin(): void {
    if (!isLoggedIn()) {
        $_SESSION['flash_error'] = "Admin login required.";
        header("Location: " . BASE_URL . "/admin/login.php");
        exit();
    }
    
    if (!isAdmin()) {
        $_SESSION['flash_error'] = "Access Denied: Administrative privileges required.";
        header("Location: " . BASE_URL . "/index.php");
        exit();
    }
}

/**
 * Redirect logged in users away from guest pages like login/register
 */
function redirectIfLoggedIn(): void {
    if (isLoggedIn()) {
        if (isAdmin()) {
            header("Location: " . BASE_URL . "/admin/dashboard.php");
        } else {
            header("Location: " . BASE_URL . "/dashboard.php");
        }
        exit();
    }
}

/**
 * Get active user/admin info array.
 * Queries the DB dynamically, caches within the request, and keeps $_SESSION synchronized.
 *
 * @param bool $forceRefresh
 * @return array|null
 */
function getCurrentUser(bool $forceRefresh = false): ?array {
    static $cachedUser = null;

    if ($cachedUser !== null && !$forceRefresh) {
        return $cachedUser;
    }

    if (!isLoggedIn()) {
        $cachedUser = null;
        return null;
    }

    $isAdminSession = !empty($_SESSION['admin_id']) || (!empty($_SESSION['role']) && strtolower($_SESSION['role']) === 'admin');
    $userId = (int)($isAdminSession ? ($_SESSION['admin_id'] ?? $_SESSION['user_id']) : $_SESSION['user_id']);

    try {
        $pdo = getPdoConnection();

        if ($isAdminSession) {
            $stmt = $pdo->prepare("SELECT id, username, full_name, email, phone, profile_image, status FROM admins WHERE id = :id LIMIT 1");
            $stmt->execute(['id' => $userId]);
            $row = $stmt->fetch();

            if ($row) {
                $_SESSION['admin_id']      = (int)$row['id'];
                $_SESSION['user_id']       = (int)$row['id'];
                $_SESSION['full_name']     = $row['full_name'];
                $_SESSION['email']         = $row['email'];
                $_SESSION['phone']         = $row['phone'] ?? '';
                $_SESSION['profile_image'] = $row['profile_image'] ?? null;
                $_SESSION['role']          = 'admin';

                $cachedUser = [
                    'id'            => (int)$row['id'],
                    'username'      => $row['username'] ?? '',
                    'full_name'     => $row['full_name'],
                    'email'         => $row['email'],
                    'phone'         => $row['phone'] ?? '',
                    'profile_image' => $row['profile_image'] ?? null,
                    'role'          => 'admin',
                    'status'        => $row['status'] ?? 'active',
                ];
                return $cachedUser;
            }
        }

        // Regular user or fallback lookup in users table
        $stmt = $pdo->prepare("SELECT id, full_name, email, phone, profile_image, role, status FROM users WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => (int)$_SESSION['user_id']]);
        $row = $stmt->fetch();

        if ($row) {
            $role = !empty($row['role']) ? strtolower($row['role']) : 'user';
            $_SESSION['user_id']       = (int)$row['id'];
            $_SESSION['full_name']     = $row['full_name'];
            $_SESSION['email']         = $row['email'];
            $_SESSION['phone']         = $row['phone'] ?? '';
            $_SESSION['profile_image'] = $row['profile_image'] ?? null;
            $_SESSION['role']          = $role;

            $cachedUser = [
                'id'            => (int)$row['id'],
                'full_name'     => $row['full_name'],
                'email'         => $row['email'],
                'phone'         => $row['phone'] ?? '',
                'profile_image' => $row['profile_image'] ?? null,
                'role'          => $role,
                'status'        => $row['status'] ?? 'active',
            ];
            return $cachedUser;
        }
    } catch (Throwable $e) {
        error_log('getCurrentUser error: ' . $e->getMessage());
    }

    // Fallback to existing session values if DB is temporarily unreachable
    $cachedUser = [
        'id'            => (int)$_SESSION['user_id'],
        'full_name'     => $_SESSION['full_name'] ?? 'User',
        'email'         => $_SESSION['email'] ?? '',
        'phone'         => $_SESSION['phone'] ?? '',
        'profile_image' => $_SESSION['profile_image'] ?? null,
        'role'          => $_SESSION['role'] ?? ($isAdminSession ? 'admin' : 'user'),
    ];

    return $cachedUser;
}
