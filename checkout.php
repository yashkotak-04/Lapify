<?php
// checkout.php - Legacy Checkout Entry Point -> Redirects to multi-step checkout
require_once __DIR__ . '/config/config.php';

$qs = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
header('Location: ' . BASE_URL . '/checkout_cart.php' . $qs, true, 302);
exit();
