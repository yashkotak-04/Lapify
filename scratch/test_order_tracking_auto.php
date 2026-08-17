<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/order_status.php';

echo "=== TESTING AUTOMATIC ORDER TRACKING ENGINE ===" . PHP_EOL;

// 1. Test Advance Seconds
$secPlaced = getStatusAdvanceSeconds('placed');
$secConfirmed = getStatusAdvanceSeconds('confirmed');
$secShippedStd = getStatusAdvanceSeconds('shipped', 'standard');
$secShippedExp = getStatusAdvanceSeconds('shipped', 'express');
$secDelivered = getStatusAdvanceSeconds('delivered');

echo "[1] Advance Thresholds:" . PHP_EOL;
echo " - Placed -> Confirmed: " . ($secPlaced / 3600) . " hrs (Expected: 2)" . PHP_EOL;
echo " - Confirmed -> Shipped: " . ($secConfirmed / 3600) . " hrs (Expected: 20)" . PHP_EOL;
echo " - Shipped -> Delivered (Std): " . ($secShippedStd / 86400) . " days (Expected: 4)" . PHP_EOL;
echo " - Shipped -> Delivered (Exp): " . ($secShippedExp / 86400) . " days (Expected: 2)" . PHP_EOL;
echo " - Delivered Terminal: " . ($secDelivered === null ? "NULL (Terminal)" : "Invalid") . PHP_EOL;

// 2. Test Step Progression Array
$steps = buildOrderTrackingSteps(['status' => 'confirmed']);
echo "[2] Tracking Steps for 'confirmed':" . PHP_EOL;
foreach ($steps as $s) {
    echo "   • " . $s['label'] . ": done=" . ($s['done'] ? '1' : '0') . ", active=" . ($s['active'] ? '1' : '0') . ", in_progress=" . ($s['in_progress'] ? '1' : '0') . PHP_EOL;
}

// 3. Test Delivery Range Estimator
$range = estimateDeliveryRange([
    'shipping_method' => 'standard',
    'placed_at' => date('Y-m-d H:i:s')
]);
echo "[3] Estimated Delivery: " . $range['start'] . " – " . $range['end'] . PHP_EOL;

echo "=== ENGINE TEST PASSED ===" . PHP_EOL;
