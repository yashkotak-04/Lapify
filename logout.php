<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_COOKIE['lapify_remember'])) {
    setcookie('lapify_remember', '', time() - 3600, '/', '', true, true);
}

if (isset($_SESSION['user_id'])) {
    try {
        $pdo = getPdoConnection();
        $stmt = $pdo->prepare('UPDATE users SET remember_token = NULL, remember_expiry = NULL WHERE id = :id');
        $stmt->execute(['id' => (int) $_SESSION['user_id']]);
    } catch (Throwable $e) {
        error_log($e->getMessage());
    }
}

// Clear user and admin credentials from session
unset(
    $_SESSION['user_id'],
    $_SESSION['full_name'],
    $_SESSION['email'],
    $_SESSION['phone'],
    $_SESSION['role'],
    $_SESSION['cart'],
    $_SESSION['admin_id'],
    $_SESSION['admin_logged_in'],
    $_SESSION['admin_username'],
    $_SESSION['admin_name'],
    $_SESSION['admin_email']
);

// Set success logout toast message (Green popup)
setFlash('success', 'You have been logged out successfully. See you again!');

// Redirect to Home / Index page
header('Location: ' . BASE_URL . '/index.php');
exit();
