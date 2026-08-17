<?php
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'admin';
$_SESSION['admin_id'] = 1;

try {
    ob_start();
    require __DIR__ . '/../admin/orders.php';
    $output = ob_get_clean();
    echo "SUCCESS: Rendered " . strlen($output) . " bytes\n";
    echo "Check if footer is in output: " . (strpos($output, 'bootstrap.bundle.min.js') !== false ? "YES" : "NO") . "\n";
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
