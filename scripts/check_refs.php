<?php
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('This script can only be run from the command line.');
}

require_once __DIR__ . '/../config/database.php';
$pdo = getPdoConnection();

echo "Laptops user_ids:\n";
foreach ($pdo->query('SELECT id, user_id, model FROM laptops ORDER BY id') as $r) {
    echo "{$r['id']} -> user_id={$r['user_id']} model={$r['model']}\n";
}

echo "\nWishlist rows:\n";
foreach ($pdo->query('SELECT id, user_id, laptop_id FROM wishlist ORDER BY id') as $r) {
    echo "{$r['id']} -> user_id={$r['user_id']} laptop_id={$r['laptop_id']}\n";
}

echo "\nOrders rows:\n";
foreach ($pdo->query('SELECT id, user_id, total_amount, status FROM orders ORDER BY id') as $r) {
    echo "{$r['id']} -> user_id={$r['user_id']} total_amount={$r['total_amount']} status={$r['status']}\n";
}
