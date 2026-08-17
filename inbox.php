<?php
// inbox.php - Legacy messaging page removed in this version.
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    $_SESSION['flash_info'] = "Messaging has been removed. Browse listings or use the order system instead.";
    header("Location: " . BASE_URL . "/dashboard.php");
    exit();
}

header("Location: " . BASE_URL . "/index.php");
exit();
