<?php
// includes/header.php - Global Page Header
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
// require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

$page_title = $page_title ?? APP_NAME . ' | ' . APP_TAGLINE;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= escape($page_title) ?></title>
    <!-- Meta Description for SEO -->
    <meta name="description" content="Lapify is the premier marketplace to buy new, buy used, and sell pre-owned laptops directly without hidden fees.">
    <meta name="csrf-token" content="<?= getCsrfToken() ?>">
    
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Font Awesome (CDN) -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" crossorigin="anonymous" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <script>document.documentElement.setAttribute('data-theme', 'light');</script>
    
    <!-- Custom Design Tokens & Styles -->
    <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/assets/favicon.svg">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/dashboard.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/auth-theme.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/auth.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/transitions.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/premium-system.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/checkout.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/brand-cards.css">
    <!-- Global Responsive Overrides (Loaded last for clean breakpoint cascading) -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/responsive.css">
</head>
<body<?= !empty($body_class) ? ' class="'.escape($body_class).'"' : '' ?>>
<main>
