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

if ($order_number === '') {
    renderErrorPage(404, 'Order reference missing', 'No order reference was provided.');
}

// Fetch order header
$order_stmt = mysqli_prepare($conn, "
    SELECT o.*, 
           u.full_name AS customer_name, 
           u.email AS customer_email, 
           u.phone AS customer_phone,
           u.role AS customer_role,
           u.created_at AS customer_since
    FROM orders o 
    INNER JOIN users u ON u.id = o.user_id 
    WHERE o.order_number = ? 
    LIMIT 1
");
mysqli_stmt_bind_param($order_stmt, 's', $order_number);
mysqli_stmt_execute($order_stmt);
$order = mysqli_fetch_assoc(mysqli_stmt_get_result($order_stmt));
mysqli_stmt_close($order_stmt);

if (!$order) {
    renderErrorPage(404, 'Order not found', 'The order you requested could not be found.');
}

// Handle PDF download
if (isset($_GET['download_pdf']) && $_GET['download_pdf'] === '1') {
    $items_stmt = mysqli_prepare($conn, "SELECT * FROM order_items WHERE order_id = ?");
    mysqli_stmt_bind_param($items_stmt, 'i', $order['id']);
    mysqli_stmt_execute($items_stmt);
    $items = mysqli_fetch_all(mysqli_stmt_get_result($items_stmt), MYSQLI_ASSOC);
    mysqli_stmt_close($items_stmt);

    generateInvoicePdf($order, ['full_name' => $order['customer_name'], 'email' => $order['customer_email']], $items);
    exit();
}

// Handle Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Session expired or invalid security token.');
    } else {
        $new_status = strtolower(trim((string)($_POST['status'] ?? '')));
        $valid_statuses = ['placed', 'confirmed', 'shipped', 'delivered', 'cancelled'];
        if (in_array($new_status, $valid_statuses, true)) {
            $upd = mysqli_prepare($conn, "UPDATE orders SET status = ? WHERE id = ?");
            mysqli_stmt_bind_param($upd, 'si', $new_status, $order['id']);
            if (mysqli_stmt_execute($upd)) {
                setFlash('success', 'Order status updated to ' . ucfirst($new_status) . '.');
            } else {
                setFlash('error', 'Failed to update order status.');
            }
            mysqli_stmt_close($upd);
        }
    }
    header('Location: ' . BASE_URL . '/admin/order-details.php?order_number=' . urlencode($order_number));
    exit();
}

// Fetch order items with laptop details
$items_stmt = mysqli_prepare($conn, "
    SELECT oi.*, l.image AS laptop_image, l.processor, l.ram, l.storage, l.type AS laptop_type, l.condition AS laptop_condition
    FROM order_items oi
    LEFT JOIN laptops l ON l.id = oi.laptop_id
    WHERE oi.order_id = ?
");
mysqli_stmt_bind_param($items_stmt, 'i', $order['id']);
mysqli_stmt_execute($items_stmt);
$order_items = mysqli_fetch_all(mysqli_stmt_get_result($items_stmt), MYSQLI_ASSOC);
mysqli_stmt_close($items_stmt);

$order['status'] = advanceOrderStatus($order);
$order_status = strtolower((string)($order['status'] ?? 'placed'));

$customer_name = $order['customer_name'] ?: 'Unknown Customer';
$customer_email = $order['customer_email'] ?: 'No email';
$customer_phone = $order['customer_phone'] ?? '';
$customer_role = $order['customer_role'] ?? 'user';
$customer_since = $order['customer_since'] ?? null;
$initial = strtoupper(mb_substr(trim($customer_name), 0, 1, 'UTF-8'));
if (empty($initial)) $initial = 'U';

$admin_title = 'Order ' . $order['order_number'] . ' | Lapify Admin';
require_once __DIR__ . '/header.php';
?>

<div class="dashboard-wrapper">
    <?php require_once __DIR__ . '/sidebar.php'; ?>

    <div class="dashboard-content">
        <?php require_once __DIR__ . '/topbar.php'; ?>
        
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
            <div>
                <h3 class="fw-bold mb-1"><i class="bi bi-receipt-cutoff text-primary me-2"></i>Order Details</h3>
                <p class="text-muted mb-0">Review order history and manage customer fulfillment</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="<?= BASE_URL ?>/admin/orders.php" class="btn btn-light border rounded-pill px-3.5 py-2 fw-semibold d-inline-flex align-items-center gap-2 shadow-2xs">
                    <i class="bi bi-arrow-left"></i> Back to Orders
                </a>
                <a href="<?= BASE_URL ?>/admin/order-details.php?order_number=<?= urlencode($order['order_number']) ?>&download_pdf=1" class="btn btn-primary rounded-pill px-4 py-2 fw-semibold d-inline-flex align-items-center gap-2 shadow-sm">
                    <i class="bi bi-file-earmark-pdf-fill"></i> Download Invoice PDF
                </a>
            </div>
        </div>

        <?php displayFlash(); ?>

        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
            <!-- Order Header Banner -->
            <div class="p-4 bg-white border-bottom d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="date-icon-box" style="width: 44px; height: 44px; font-size: 1.25rem;">
                        <i class="bi bi-receipt text-primary"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                            <span class="order-code-badge">
                                <span class="order-code-hash">#</span><span class="order-code-text"><?= escape($order['order_number']) ?></span>
                            </span>
                            <span class="status-pill status-pill-<?= $order_status ?>">
                                <span class="status-dot"></span>
                                <span class="text-capitalize"><?= escape(getOrderStatusLabel($order_status)) ?></span>
                            </span>
                        </div>
                        <div class="text-muted small">
                            <i class="bi bi-calendar3 me-1"></i> Placed on <?= date('M d, Y', strtotime($order['created_at'])) ?> at <?= date('h:i A', strtotime($order['created_at'])) ?>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Status Change Form -->
                <form method="POST" class="d-flex align-items-center gap-2 mb-0">
                    <?= renderCsrfInput() ?>
                    <input type="hidden" name="action" value="update_status">
                    <label class="small text-muted fw-bold d-none d-lg-inline">Update Status:</label>
                    <div class="lapify-status-select-wrap">
                        <select name="status" class="lapify-status-select" onchange="this.form.submit()" aria-label="Update order status" title="Change Order Status">
                            <option value="placed" <?= $order_status === 'placed' ? 'selected' : '' ?>>Placed</option>
                            <option value="confirmed" <?= $order_status === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                            <option value="shipped" <?= $order_status === 'shipped' ? 'selected' : '' ?>>Shipped</option>
                            <option value="delivered" <?= $order_status === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                            <option value="cancelled" <?= $order_status === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        </select>
                    </div>
                </form>
            </div>

            <div class="card-body p-4 p-md-5">
                <!-- Info Panels Grid -->
                <div class="row g-4 mb-5">
                    <!-- Buyer Info -->
                    <div class="col-lg-6">
                        <div class="order-details-info-panel h-100">
                            <div class="order-panel-header">
                                <i class="bi bi-person-badge-fill text-primary fs-5"></i>
                                <span>Customer Details</span>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2.5 py-0.5 ms-auto" style="font-size: 0.72rem; font-weight: 600;">
                                    <?= escape(ucfirst($customer_role)) ?>
                                </span>
                            </div>
                            <div class="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom border-secondary border-opacity-10">
                                <div class="customer-avatar-pill" style="width: 48px; height: 48px; font-size: 1.15rem;">
                                    <?= escape($initial) ?>
                                </div>
                                <div class="d-flex flex-column" style="min-width: 0;">
                                    <span class="customer-name fs-6 fw-bold"><?= escape($customer_name) ?></span>
                                    <span class="customer-email text-muted small mt-0.5"><i class="bi bi-envelope me-1 text-primary"></i><?= escape($customer_email) ?></span>
                                </div>
                            </div>
                            <div class="d-flex flex-column">
                                <div class="order-info-row">
                                    <div class="order-info-label">
                                        <i class="bi bi-telephone-fill"></i>
                                        <span>Phone Number</span>
                                    </div>
                                    <div class="order-info-val">
                                        <?= !empty($customer_phone) ? escape($customer_phone) : '<span class="text-muted fw-normal fst-italic">Not provided</span>' ?>
                                    </div>
                                </div>
                                <div class="order-info-row">
                                    <div class="order-info-label">
                                        <i class="bi bi-person-vcard-fill"></i>
                                        <span>Account ID</span>
                                    </div>
                                    <div class="order-info-val">
                                        <span class="badge bg-light text-secondary border font-monospace px-2.5 py-1">#USR-<?= (int)$order['user_id'] ?></span>
                                    </div>
                                </div>
                                <div class="order-info-row">
                                    <div class="order-info-label">
                                        <i class="bi bi-calendar-check-fill"></i>
                                        <span>Customer Since</span>
                                    </div>
                                    <div class="order-info-val">
                                        <?= !empty($customer_since) ? date('M d, Y', strtotime($customer_since)) : 'Registered Member' ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Order Summary -->
                    <div class="col-lg-6">
                        <div class="order-details-info-panel h-100">
                            <div class="order-panel-header">
                                <i class="bi bi-shield-check text-primary fs-5"></i>
                                <span>Order & Payment Overview</span>
                                <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-0.5 ms-auto" style="font-size: 0.72rem; font-weight: 600;">
                                    <?= count($order_items) ?> <?= count($order_items) === 1 ? 'Product' : 'Products' ?>
                                </span>
                            </div>
                            <div class="d-flex flex-column">
                                <div class="order-info-row">
                                    <div class="order-info-label">
                                        <i class="bi bi-credit-card-2-front-fill"></i>
                                        <span>Payment Method</span>
                                    </div>
                                    <div class="order-info-val">
                                        <span class="badge bg-light text-dark border rounded-pill px-3 py-1.5 fw-bold"><?= escape(strtoupper((string)($order['payment_method'] ?? 'Cash on Delivery'))) ?></span>
                                    </div>
                                </div>
                                <div class="order-info-row">
                                    <div class="order-info-label">
                                        <i class="bi bi-patch-check-fill"></i>
                                        <span>Payment Status</span>
                                    </div>
                                    <div class="order-info-val">
                                        <span class="badge <?= in_array($order_status, ['delivered', 'confirmed', 'shipped']) ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' ?> rounded-pill px-3 py-1.5 fw-bold d-inline-flex align-items-center gap-1.5">
                                            <i class="bi bi-circle-fill" style="font-size: 0.5rem;"></i>
                                            <span><?= in_array($order_status, ['delivered']) ? 'Paid & Completed' : (in_array($order_status, ['confirmed', 'shipped']) ? 'Confirmed / Verified' : 'Pending Verification') ?></span>
                                        </span>
                                    </div>
                                </div>
                                <div class="order-info-row">
                                    <div class="order-info-label">
                                        <i class="bi bi-activity"></i>
                                        <span>Fulfillment Phase</span>
                                    </div>
                                    <div class="order-info-val">
                                        <span class="status-pill status-pill-<?= $order_status ?>" style="padding: 0.28rem 0.85rem;">
                                            <span class="status-dot"></span>
                                            <span class="text-capitalize"><?= escape(getOrderStatusLabel($order_status)) ?></span>
                                        </span>
                                    </div>
                                </div>
                                <div class="order-info-row">
                                    <div class="order-info-label">
                                        <i class="bi bi-clock-history"></i>
                                        <span>Order Placed</span>
                                    </div>
                                    <div class="order-info-val">
                                        <?= date('M d, Y \a\t h:i A', strtotime($order['created_at'])) ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Purchased Products as Product Cards -->
                <div class="mb-5">
                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <h5 class="fw-bold text-dark mb-0 d-flex align-items-center">
                            <i class="bi bi-laptop-fill text-primary me-2.5 fs-5"></i>
                            <span>Purchased Items</span>
                        </h5>
                        <span class="badge bg-light text-secondary border rounded-pill px-3.5 py-1.5 fw-semibold" style="font-size: 0.82rem;">
                            <?= count($order_items) ?> <?= count($order_items) === 1 ? 'Item' : 'Items' ?>
                        </span>
                    </div>

                    <div class="d-flex flex-column gap-3.5">
                        <?php 
                        $calculated_subtotal = 0;
                        foreach ($order_items as $item): 
                            $brand = trim((string)($item['brand_name'] ?? ''));
                            $model = trim((string)($item['model'] ?? ''));
                            if ($brand !== '' && stripos($model, $brand) === 0) {
                                $productTitle = $model;
                            } elseif ($brand !== '') {
                                $productTitle = $brand . ' ' . $model;
                            } else {
                                $productTitle = $model ?: 'Laptop';
                            }

                            $item_unit_price = (float)($item['price'] ?? 0);
                            $item_qty = (int)($item['quantity'] ?? 1);
                            $item_total = $item_unit_price * $item_qty;
                            $calculated_subtotal += $item_total;

                            $img_src = getLaptopImageUrl($item) ?: 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?auto=format&fit=crop&w=300&q=80';
                        ?>
                            <div class="order-product-card">
                                <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-4">
                                    <!-- Left: Product Image & Spec Details -->
                                    <div class="d-flex align-items-center" style="min-width: 0;">
                                        <img src="<?= escape($img_src) ?>" alt="<?= escape($productTitle) ?>" class="order-product-thumb me-4">
                                        <div class="d-flex flex-column gap-2" style="min-width: 0;">
                                            <?php if ($brand !== ''): ?>
                                                <div>
                                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1" style="font-size: 0.74rem; font-weight: 700; letter-spacing: 0.03em;">
                                                        <?= escape($brand) ?>
                                                    </span>
                                                </div>
                                            <?php endif; ?>
                                            <h5 class="fw-bold text-dark mb-0 text-truncate" title="<?= escape($productTitle) ?>" style="font-size: 1.12rem;">
                                                <?= escape($productTitle) ?>
                                            </h5>
                                            <div class="d-flex flex-wrap align-items-center gap-2 mt-1">
                                                <?php if (!empty($item['processor'])): ?>
                                                    <span class="order-spec-badge"><i class="bi bi-cpu"></i> <?= escape($item['processor']) ?></span>
                                                <?php endif; ?>
                                                <?php if (!empty($item['ram'])): ?>
                                                    <span class="order-spec-badge"><i class="bi bi-memory"></i> <?= escape($item['ram']) ?></span>
                                                <?php endif; ?>
                                                <?php if (!empty($item['storage'])): ?>
                                                    <span class="order-spec-badge"><i class="bi bi-hdd"></i> <?= escape($item['storage']) ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Right: Price, Qty & Subtotal -->
                                    <div class="d-flex align-items-center justify-content-between justify-content-lg-end gap-4 gap-xl-5 flex-shrink-0 pt-3 pt-lg-0 border-top border-lg-0 border-light-subtle ps-lg-4">
                                        <div class="text-lg-end">
                                            <div class="text-muted small mb-1" style="font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600;">Unit Price</div>
                                            <div class="fw-bold text-dark" style="font-size: 1.05rem;"><?= formatPrice($item_unit_price) ?></div>
                                        </div>

                                        <div class="text-center px-2">
                                            <div class="text-muted small mb-1 d-none d-lg-block" style="font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600;">Quantity</div>
                                            <div class="badge bg-light text-dark border rounded-pill px-3.5 py-1.5 fw-bold" style="font-size: 0.9rem;">
                                                <span class="text-muted fw-normal me-1">&times;</span><?= $item_qty ?>
                                            </div>
                                        </div>

                                        <div class="text-end ps-2" style="min-width: 140px;">
                                            <div class="text-muted small mb-1" style="font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600;">Item Subtotal</div>
                                            <div class="order-price-val text-primary fs-4 fw-bold"><?= formatPrice($item_total) ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Financial Grand Total Summary (Wide Luxurious Invoice Card) -->
                <div class="order-summary-box w-100 mt-4">
                    <div class="row g-4 g-lg-5 align-items-center">
                        <!-- Left Side: Order Guarantee & Trust Badges -->
                        <div class="col-lg-6 border-end-lg pe-lg-5">
                            <div class="d-flex align-items-center mb-3">
                                <div class="date-icon-box me-3" style="width: 42px; height: 42px;">
                                    <i class="bi bi-shield-check text-primary fs-5"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold text-dark mb-0.5 fs-6">Order Assurance & Fulfillment</h6>
                                    <span class="text-muted small">Verified Lapify customer purchase</span>
                                </div>
                            </div>
                            <p class="text-muted small mb-4" style="line-height: 1.65; font-size: 0.88rem;">
                                This order is verified and processed under the <strong>Lapify Quality Assurance Protocol</strong>. All products include an authentic manufacturer warranty, secure packaging, and an official GST tax invoice.
                            </p>
                            <div class="d-flex flex-wrap align-items-center gap-2.5">
                                <div class="order-trust-pill">
                                    <i class="bi bi-truck text-success"></i>
                                    <span>Free Express Delivery</span>
                                </div>
                                <div class="order-trust-pill">
                                    <i class="bi bi-patch-check-fill text-primary"></i>
                                    <span>100% Genuine Product</span>
                                </div>
                                <div class="order-trust-pill">
                                    <i class="bi bi-file-earmark-text-fill text-info"></i>
                                    <span>Official Tax Invoice</span>
                                </div>
                            </div>
                        </div>

                        <!-- Right Side: Invoice Financial Breakdown -->
                        <div class="col-lg-6 ps-lg-5">
                            <div class="d-flex flex-column gap-2.5">
                                <div class="order-summary-row">
                                    <span class="order-summary-label">
                                        <i class="bi bi-receipt text-secondary"></i>
                                        <span>Items Subtotal</span>
                                    </span>
                                    <span class="order-summary-value text-dark"><?= formatPrice($calculated_subtotal) ?></span>
                                </div>

                                <?php 
                                $grand_total = (float)$order['total_amount'];
                                $discount = $calculated_subtotal > $grand_total ? ($calculated_subtotal - $grand_total) : 0;
                                if ($discount > 0.01): 
                                ?>
                                    <div class="order-summary-row">
                                        <span class="order-summary-label text-success">
                                            <i class="bi bi-tag-fill text-success"></i>
                                            <span class="fw-semibold">Discount / Promo Savings</span>
                                        </span>
                                        <span class="order-summary-value text-success fw-bold">-<?= formatPrice($discount) ?></span>
                                    </div>
                                <?php endif; ?>

                                <div class="order-summary-row">
                                    <span class="order-summary-label">
                                        <i class="bi bi-truck text-secondary"></i>
                                        <span>Shipping & Handling</span>
                                    </span>
                                    <span class="order-summary-value text-success fw-semibold">
                                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1">FREE</span>
                                    </span>
                                </div>

                                <div class="order-summary-row">
                                    <span class="order-summary-label">
                                        <i class="bi bi-percent text-secondary"></i>
                                        <span>Estimated Taxes (GST)</span>
                                    </span>
                                    <span class="order-summary-value text-muted small fst-italic">Included in product price</span>
                                </div>

                                <div class="order-summary-row total-row">
                                    <div>
                                        <div class="fw-bold text-dark fs-5 mb-0">Grand Total:</div>
                                        <div class="text-muted small" style="font-size: 0.76rem;">Final payable amount</div>
                                    </div>
                                    <div class="text-end">
                                        <div class="order-price-val text-primary fs-2 fw-bold"><?= formatPrice($grand_total) ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>

