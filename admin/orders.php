<?php
// admin/orders.php - Admin Order Management
$admin_title = 'Manage Orders | Lapify Admin';
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/order_status.php';

requireAdmin();

$conn = getDbConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Session expired or invalid security token.');
    } else {
        $order_id = intval($_POST['order_id'] ?? 0);
        $new_status = sanitizeInput($_POST['status'] ?? 'placed');
        $allowed_statuses = ['placed', 'confirmed', 'shipped', 'delivered', 'cancelled'];

        if ($order_id <= 0 || !in_array($new_status, $allowed_statuses, true)) {
            setFlash('error', 'Invalid order or status selected.');
        } else {
            $current_stmt = mysqli_prepare($conn, "SELECT status FROM orders WHERE id = ? LIMIT 1");
            mysqli_stmt_bind_param($current_stmt, 'i', $order_id);
            mysqli_stmt_execute($current_stmt);
            $current_status = mysqli_fetch_assoc(mysqli_stmt_get_result($current_stmt));
            mysqli_stmt_close($current_stmt);

            $current_status = strtolower((string)($current_status['status'] ?? 'placed'));

            if (in_array($current_status, ['delivered', 'cancelled'], true)) {
                setFlash('error', 'This order is already finalized.');
            } else {
                $update_stmt = mysqli_prepare($conn, "UPDATE orders SET status = ?, status_updated_at = NOW() WHERE id = ?");
                mysqli_stmt_bind_param($update_stmt, 'si', $new_status, $order_id);
                if (mysqli_stmt_execute($update_stmt)) {
                    setFlash('success', 'Order status updated to ' . ucfirst($new_status) . '.');
                } else {
                    setFlash('error', 'Failed to update order status.');
                }
                mysqli_stmt_close($update_stmt);
            }
        }
    }

    header('Location: ' . BASE_URL . '/admin/orders.php');
    exit();
}

$status_filter = sanitizeInput($_GET['status'] ?? 'all');
$search = sanitizeInput($_GET['search'] ?? '');
$start_date = sanitizeInput($_GET['start_date'] ?? '');
$end_date = sanitizeInput($_GET['end_date'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 10;
$offset = ($page - 1) * $per_page;

$where_clauses = ['1=1'];
$params = [];
$types = '';

if ($status_filter !== 'all' && in_array($status_filter, ['placed', 'confirmed', 'shipped', 'delivered', 'cancelled'], true)) {
    $where_clauses[] = 'o.status = ?';
    $params[] = $status_filter;
    $types .= 's';
}

if (!empty($start_date)) {
    $where_clauses[] = 'o.created_at >= ?';
    $params[] = $start_date . ' 00:00:00';
    $types .= 's';
}

if (!empty($end_date)) {
    $where_clauses[] = 'o.created_at <= ?';
    $params[] = $end_date . ' 23:59:59';
    $types .= 's';
}

if (!empty($search)) {
    $where_clauses[] = '(o.order_number LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)';
    $like = '%' . $search . '%';
    $params[] = $like; $params[] = $like; $params[] = $like;
    $types .= 'sss';
}

$where_sql = implode(' AND ', $where_clauses);

$count_sql = "SELECT COUNT(DISTINCT o.id)
              FROM orders o
              INNER JOIN users u ON u.id = o.user_id
              WHERE {$where_sql}";

$count_stmt = mysqli_prepare($conn, $count_sql);
if (!empty($types)) {
    mysqli_stmt_bind_param($count_stmt, $types, ...$params);
}
mysqli_stmt_execute($count_stmt);
mysqli_stmt_bind_result($count_stmt, $total_orders);
mysqli_stmt_fetch($count_stmt);
mysqli_stmt_close($count_stmt);
$total_orders = (int)($total_orders ?? 0);
$total_pages = max(1, (int)ceil($total_orders / $per_page));
$page = min($page, $total_pages);
$offset = ($page - 1) * $per_page;

$list_sql = "SELECT o.*, u.full_name AS customer_name, u.email AS customer_email,
                 COUNT(oi.id) AS item_count, SUM(oi.price * oi.quantity) AS item_total
             FROM orders o
             INNER JOIN users u ON u.id = o.user_id
             LEFT JOIN order_items oi ON oi.order_id = o.id
             WHERE {$where_sql}
             GROUP BY o.id
             ORDER BY o.created_at DESC
             LIMIT ? OFFSET ?";

$list_params = $params;
$list_params[] = $per_page;
$list_params[] = $offset;
$list_types = $types . 'ii';

$list_stmt = mysqli_prepare($conn, $list_sql);
mysqli_stmt_bind_param($list_stmt, $list_types, ...$list_params);
mysqli_stmt_execute($list_stmt);
$orders_result = mysqli_stmt_get_result($list_stmt);
$orders_list = [];
while ($row = mysqli_fetch_assoc($orders_result)) {
    $row['status'] = advanceOrderStatus($row);
    $orders_list[] = $row;
}
mysqli_stmt_close($list_stmt);

$placed_orders_count = 0;
$placed_stmt = mysqli_prepare($conn, "SELECT COUNT(*) FROM orders WHERE status = 'placed'");
mysqli_stmt_execute($placed_stmt);
mysqli_stmt_bind_result($placed_stmt, $placed_orders_count);
mysqli_stmt_fetch($placed_stmt);
mysqli_stmt_close($placed_stmt);

$revenue_stmt = mysqli_prepare($conn, "SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
mysqli_stmt_execute($revenue_stmt);
mysqli_stmt_bind_result($revenue_stmt, $monthly_revenue);
mysqli_stmt_fetch($revenue_stmt);
mysqli_stmt_close($revenue_stmt);
$monthly_revenue = (float)($monthly_revenue ?? 0);
?>

<div class="dashboard-wrapper">
    <?php require_once __DIR__ . '/sidebar.php'; ?>

    <div class="dashboard-content">
        <?php require_once __DIR__ . '/topbar.php'; ?>
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
            <div>
                <h3 class="fw-bold mb-1"><i class="bi bi-bag-check-fill text-primary me-2"></i>Order Management</h3>
                <p class="text-muted mb-0">Track all customer purchases across the marketplace</p>
            </div>
        </div>

        <?php displayFlash(); ?>

        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon stat-icon-blue"><i class="bi bi-bag"></i></div>
                    <div>
                        <div class="stat-number"><?= number_format($total_orders) ?></div>
                        <div class="stat-label">Total Orders</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon stat-icon-amber"><i class="bi bi-hourglass-split"></i></div>
                    <div>
                        <div class="stat-number"><?= number_format($placed_orders_count) ?></div>
                        <div class="stat-label">New / Placed</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon stat-icon-green"><i class="bi bi-currency-rupee"></i></div>
                    <div>
                        <div class="stat-number"><?= formatPrice($monthly_revenue) ?></div>
                        <div class="stat-label">Revenue This Month</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 p-3 mb-4">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>All</option>
                        <option value="placed" <?= $status_filter === 'placed' ? 'selected' : '' ?>>Placed</option>
                        <option value="confirmed" <?= $status_filter === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                        <option value="shipped" <?= $status_filter === 'shipped' ? 'selected' : '' ?>>Shipped</option>
                        <option value="delivered" <?= $status_filter === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                        <option value="cancelled" <?= $status_filter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted">From</label>
                    <input type="date" name="start_date" class="form-control form-control-sm" value="<?= escape($start_date) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted">To</label>
                    <input type="date" name="end_date" class="form-control form-control-sm" value="<?= escape($end_date) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted">Search</label>
                    <input type="text" name="search" class="form-control form-control-sm" value="<?= escape($search) ?>" placeholder="Order no. or customer">
                </div>
                <div class="col-md-2 d-grid">
                    <button type="submit" class="btn btn-primary btn-sm rounded-3 fw-bold">Filter</button>
                </div>
            </form>
        </div>

        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-muted small text-uppercase fw-bold">
                            <tr>
                                <th class="ps-4">Order Number</th>
                                <th>Customer</th>
                                <th>Items</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th class="pe-4 text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($orders_list)): ?>
                                <?php foreach ($orders_list as $order): ?>
                                    <?php
                                        $order_status = strtolower((string)($order['status'] ?? 'placed'));
                                        $customer_name = $order['customer_name'] ?: 'Unknown Customer';
                                        $customer_email = $order['customer_email'] ?: 'No email';
                                        $item_count = (int)($order['item_count'] ?? 0);
                                    ?>
                                    <tr>
                                        <td class="ps-4 fw-bold text-primary"><?= escape($order['order_number']) ?></td>
                                        <td>
                                            <div class="fw-bold"><?= escape($customer_name) ?></div>
                                            <div class="small text-muted"><?= escape($customer_email) ?></div>
                                        </td>
                                        <td><?= $item_count ?> item<?= $item_count === 1 ? '' : 's' ?></td>
                                        <td class="fw-bold text-primary"><?= formatPrice($order['total_amount'] ?? 0) ?></td>
                                        <td>
                                            <span class="badge <?= getOrderStatusBadgeClass($order_status) ?> rounded-pill px-3 py-2 fw-semibold"><?= escape(getOrderStatusLabel($order_status)) ?></span>
                                        </td>
                                        <td class="small text-muted"><?= formatDate($order['created_at']) ?></td>
                                        <td class="pe-4 text-end">
                                            <div class="d-flex justify-content-end align-items-center gap-2 flex-wrap">
                                                <a href="<?= BASE_URL ?>/admin/order-details.php?order_number=<?= urlencode($order['order_number']) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i> View</a>
                                                <form method="POST" class="d-inline-block mb-0">
                                                    <?= renderCsrfInput() ?>
                                                    <input type="hidden" name="action" value="update_status">
                                                    <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
                                                    <select name="status" class="form-select form-select-sm d-inline-block w-auto" onchange="this.form.submit()" aria-label="Update order status">
                                                        <option value="placed" <?= $order_status === 'placed' ? 'selected' : '' ?>>Placed</option>
                                                        <option value="confirmed" <?= $order_status === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                                        <option value="shipped" <?= $order_status === 'shipped' ? 'selected' : '' ?>>Shipped</option>
                                                        <option value="delivered" <?= $order_status === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                                                        <option value="cancelled" <?= $order_status === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                                    </select>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">No orders match the current filters.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php if ($total_pages > 1): ?>
            <nav class="mt-4 d-flex justify-content-center">
                <ul class="pagination pagination-sm">
                    <?php
                    $query_string = http_build_query([
                        'status' => $status_filter,
                        'search' => $search,
                        'start_date' => $start_date,
                        'end_date' => $end_date,
                    ]);
                    ?>
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= BASE_URL ?>/admin/orders.php?page=<?= max(1, $page - 1) ?>&<?= $query_string ?>">Previous</a>
                    </li>
                    <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                        <li class="page-item <?= $page === $p ? 'active' : '' ?>">
                            <a class="page-link" href="<?= BASE_URL ?>/admin/orders.php?page=<?= $p ?>&<?= $query_string ?>"><?= $p ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= BASE_URL ?>/admin/orders.php?page=<?= min($total_pages, $page + 1) ?>&<?= $query_string ?>">Next</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
