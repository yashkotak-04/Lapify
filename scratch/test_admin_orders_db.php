<?php
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'admin';
$_SESSION['admin_id'] = 1;

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/order_status.php';

echo "=== TESTING ADMIN ORDERS QUERIES ===\n";

$conn = getDbConnection();
$pdo = getPdoConnection();

// Test order status column in DB
$cols = $pdo->query("SHOW COLUMNS FROM orders")->fetchAll(PDO::FETCH_COLUMN);
echo "Orders columns: " . json_encode($cols) . "\n";

// Test advanceOrderStatus on all existing orders
$orders = $pdo->query("SELECT * FROM orders")->fetchAll(PDO::FETCH_ASSOC);
echo "Total orders in DB: " . count($orders) . "\n";
foreach ($orders as $order) {
    try {
        $adv = advanceOrderStatus($order);
        echo "Order #{$order['id']} ({$order['order_number']}): initial={$order['status']} => advanced={$adv}\n";
    } catch (Throwable $e) {
        echo "ERROR advancing order #{$order['id']}: " . $e->getMessage() . "\n";
    }
}

// Test admin/orders.php query
$testStatuses = ['all', 'placed', 'confirmed', 'shipped', 'delivered', 'cancelled'];
foreach ($testStatuses as $st) {
    $_GET['status'] = $st;
    $where_clauses = ['1=1'];
    $params = [];
    $types = '';
    if ($st !== 'all') {
        $where_clauses[] = 'o.status = ?';
        $params[] = $st;
        $types .= 's';
    }
    $where_sql = implode(' AND ', $where_clauses);
    $list_sql = "SELECT o.*, u.full_name AS customer_name, u.email AS customer_email,
                     COUNT(oi.id) AS item_count, SUM(oi.price * oi.quantity) AS item_total
                 FROM orders o
                 INNER JOIN users u ON u.id = o.user_id
                 LEFT JOIN order_items oi ON oi.order_id = o.id
                 WHERE {$where_sql}
                 GROUP BY o.id
                 ORDER BY o.created_at DESC
                 LIMIT 10 OFFSET 0";
    try {
        $stmt = mysqli_prepare($conn, $list_sql);
        if (!empty($types)) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        }
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $count = mysqli_num_rows($res);
        mysqli_stmt_close($stmt);
        echo "Status filter '{$st}': SUCCESS ({$count} rows)\n";
    } catch (Throwable $e) {
        echo "ERROR for status '{$st}': " . $e->getMessage() . "\n";
    }
}
