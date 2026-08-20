<?php
// admin/logout.php - Admin Logout Handler
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clear remember me cookie
if (isset($_COOKIE['lapify_remember'])) {
    setcookie('lapify_remember', '', time() - 3600, '/', '', true, true);
}

// Clear all admin and user session keys completely
unset(
    $_SESSION['admin_id'],
    $_SESSION['admin_logged_in'],
    $_SESSION['admin_username'],
    $_SESSION['admin_name'],
    $_SESSION['admin_email'],
    $_SESSION['user_id'],
    $_SESSION['full_name'],
    $_SESSION['email'],
    $_SESSION['phone'],
    $_SESSION['role'],
    $_SESSION['cart']
);

// Set success logout toast message (Green popup)
setFlash('success', "Logged out from Admin Panel successfully.");

// Redirect to Home / Index page
header("Location: " . BASE_URL . "/index.php");
exit();
