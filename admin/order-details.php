<?php
// admin/order-details.php - Admin Order Detail / Invoice View
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/invoice-pdf.php';
require_once __DIR__ . '/../includes/order_status.php';

requireAdmin();

$conn = getDbConnection();
$order_number = sanitizeInput($_GET['order_number'] ?? '');

if (isset($_GET['download_pdf']) && $_GET['download_pdf'] === '1' && $order_number !== '') {
    $order_stmt = mysqli_prepare($conn, "SELECT o.*, u.full_name AS customer_name, u.email AS customer_email FROM orders o INNER JOIN users u ON u.id = o.user_id WHERE o.order_number = ? LIMIT 1");
    mysqli_stmt_bind_param($order_stmt, 's', $order_number);
    mysqli_stmt_execute($order_stmt);
    $order = mysqli_fetch_assoc(mysqli_stmt_get_result($order_stmt));
    mysqli_stmt_close($order_stmt);

    if ($order) {
        $items_stmt = mysqli_prepare($conn, "SELECT * FROM order_items WHERE order_id = ?");
        mysqli_stmt_bind_param($items_stmt, 'i', $order['id']);
        mysqli_stmt_execute($items_stmt);
        $items = mysqli_fetch_all(mysqli_stmt_get_result($items_stmt), MYSQLI_ASSOC);
        mysqli_stmt_close($items_stmt);

        generateInvoicePdf($order, ['full_name' => $order['customer_name'], 'email' => $order['customer_email']], $items);
    }
}

if ($order_number === '') {
    renderErrorPage(404, 'Order reference missing', 'No order reference was provided.');
}

$admin_title = 'Order Details | Lapify Admin';
require_once __DIR__ . '/header.php';

$order_stmt = mysqli_prepare($conn, "SELECT o.*, u.full_name AS customer_name, u.email AS customer_email FROM orders o INNER JOIN users u ON u.id = o.user_id WHERE o.order_number = ? LIMIT 1");
mysqli_stmt_bind_param($order_stmt, 's', $order_number);
mysqli_stmt_execute($order_stmt);
$order = mysqli_fetch_assoc(mysqli_stmt_get_result($order_stmt));
mysqli_stmt_close($order_stmt);

if (!$order) {
    renderErrorPage(404, 'Order not found', 'The order you requested could not be found.');
}

$items_stmt = mysqli_prepare($conn, "SELECT * FROM order_items WHERE order_id = ?");
mysqli_stmt_bind_param($items_stmt, 'i', $order['id']);
mysqli_stmt_execute($items_stmt);
$order_items = mysqli_fetch_all(mysqli_stmt_get_result($items_stmt), MYSQLI_ASSOC);
mysqli_stmt_close($items_stmt);

$order['status'] = advanceOrderStatus($order);
$order_status = strtolower((string)($order['status'] ?? 'placed'));
?>

<div class="dashboard-wrapper">
    <?php require_once __DIR__ . '/sidebar.php'; ?>

    <div class="dashboard-content">
        <?php require_once __DIR__ . '/topbar.php'; ?>
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
            <div>
                <h3 class="fw-bold mb-1"><i class="bi bi-receipt-cutoff text-primary me-2"></i>Order Details</h3>
                <p class="text-muted mb-0">Review order history and download the invoice</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="<?= BASE_URL ?>/admin/orders.php" class="btn btn-outline-secondary rounded-pill px-3"><i class="bi bi-arrow-left me-2"></i>Back to Orders</a>
                <a href="<?= BASE_URL ?>/admin/order-details.php?order_number=<?= urlencode($order['order_number']) ?>&download_pdf=1" class="btn btn-success rounded-pill px-3"><i class="bi bi-download me-2"></i>Download Invoice PDF</a>
            </div>
        </div>

        <?php displayFlash(); ?>

        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-primary text-white p-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                <div>
                    <h4 class="fw-bold mb-1">Order <?= escape($order['order_number']) ?></h4>
                    <p class="mb-0 text-white-50">Placed on <?= formatDate($order['created_at']) ?></p>
                </div>
                <span class="badge <?= getOrderStatusBadgeClass($order_status) ?> rounded-pill px-3 py-2 fw-semibold text-capitalize"><?= escape(getOrderStatusLabel($order_status)) ?></span>
            </div>

            <div class="card-body p-4 p-md-5">
                <div class="row g-4 mb-4">
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded-4 h-100">
                            <div class="small text-muted text-uppercase fw-bold mb-2">Buyer</div>
                            <div class="fw-bold fs-5 mb-1"><?= escape($order['customer_name']) ?></div>
                            <div class="text-muted"><?= escape($order['customer_email']) ?></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded-4 h-100">
                            <div class="small text-muted text-uppercase fw-bold mb-2">Order Summary</div>
                            <div class="d-flex justify-content-between align-items-center mb-2"><span>Items</span><strong><?= count($order_items) ?> item<?= count($order_items) === 1 ? '' : 's' ?></strong></div>
                            <div class="d-flex justify-content-between align-items-center mb-2"><span>Total</span><strong class="text-primary"><?= formatPrice($order['total_amount']) ?></strong></div>
                            <div class="d-flex justify-content-between align-items-center"><span>Status</span><strong><?= escape(getOrderStatusLabel($order_status)) ?></strong></div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive mb-4">
                    <table class="table align-middle">
                        <thead class="bg-light text-muted small text-uppercase fw-bold">
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Qty</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($order_items as $item): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold"><?= escape(($item['brand_name'] ?? '') . ' ' . ($item['model'] ?? '')) ?></div>
                                    </td>
                                    <td><?= formatPrice($item['price'] ?? 0) ?></td>
                                    <td><?= (int)($item['quantity'] ?? 1) ?></td>
                                    <td class="text-end fw-bold"><?= formatPrice((float)($item['price'] ?? 0) * (int)($item['quantity'] ?? 1)) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-end">
                    <div class="card border-0 bg-light rounded-4 px-4 py-3 w-auto">
                        <div class="small text-muted">Order Total</div>
                        <div class="fw-bold fs-4 text-primary"><?= formatPrice($order['total_amount']) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
