<?php
// admin/logout.php - Admin Logout Handler
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

$_SESSION = array();

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

session_start();
setFlash('info', "Logged out from Admin Panel.");
header("Location: " . BASE_URL . "/admin/login.php");
exit();
