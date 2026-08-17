<?php
// admin/header.php - Admin Panel Top Header
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$admin_title = $admin_title ?? 'Admin Panel | Lapify';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= escape($admin_title) ?></title>
    <meta name="csrf-token" content="<?= getCsrfToken() ?>">
    
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <script>document.documentElement.setAttribute('data-theme', 'light');</script>
    
    <!-- Custom Application & Dashboard CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/dashboard.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/auth-theme.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/transitions.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/premium-system.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/brand-cards.css">
    <!-- Global Responsive Overrides -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/responsive.css">
</head>
<body<?= !empty($body_class) ? ' class="admin-panel-body '.escape($body_class).'"' : ' class="admin-panel-body bg-light"' ?>>
