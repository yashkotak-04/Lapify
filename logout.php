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

$_SESSION = [];
session_unset();
session_destroy();

header('Location: ' . BASE_URL . '/login.php');
exit();
