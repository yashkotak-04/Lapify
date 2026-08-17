<?php
// cron/update_order_statuses.php
// Loops through all non-terminal orders and advances their status based on
// elapsed time. Meant to be scheduled via cron every 30 minutes so statuses
// stay fresh even for orders nobody is actively viewing.
//
// Cron syntax to add (adjust the PHP path to your server):
//   */30 * * * * php /path/to/lapify/cron/update_order_statuses.php
//
// For XAMPP on Windows, you can also run it manually:
//   php cron/update_order_statuses.php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/order_status.php';

// Only allow CLI execution (or a secret token for web-based cron).
$isCli = (PHP_SAPI === 'cli');
$secret = getenv('LAPIFY_CRON_SECRET') ?: '';

if (!$isCli) {
    $provided = $_GET['secret'] ?? '';
    if ($secret === '' || !hash_equals($secret, $provided)) {
        http_response_code(403);
        exit('Forbidden');
    }
}

try {
    $pdo = getPdoConnection();

    // Fetch all orders that are not in a terminal state.
    $stmt = $pdo->query("SELECT * FROM orders WHERE status NOT IN ('delivered','cancelled') ORDER BY id ASC");
    $orders = $stmt->fetchAll();

    $updated = 0;
    $unchanged = 0;

    foreach ($orders as $order) {
        $before = strtolower((string)($order['status'] ?? 'placed'));
        $after = advanceOrderStatus($order);
        if ($after !== $before) {
            $updated++;
        } else {
            $unchanged++;
        }
    }

    $message = sprintf(
        "[%s] Order status sweep complete: %d updated, %d unchanged, %d total.",
        date('Y-m-d H:i:s'),
        $updated,
        $unchanged,
        count($orders)
    );

    if ($isCli) {
        echo $message . PHP_EOL;
    } else {
        header('Content-Type: text/plain');
        echo $message;
    }
} catch (Throwable $e) {
    error_log('cron/update_order_statuses.php failed: ' . $e->getMessage());
    if ($isCli) {
        echo 'Error: ' . $e->getMessage() . PHP_EOL;
        exit(1);
    }
    http_response_code(500);
    exit('Internal error');
}