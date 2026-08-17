<?php
// orders.php - User Orders List
$page_title = "My Orders | Lapify";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/order_status.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin();

$conn = getDbConnection();
$current_user = getCurrentUser();
$user_id = $current_user['id'];

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'This request has expired. Please try again.';
    } else {
        $order_id = intval($_POST['order_id'] ?? 0);
        if ($order_id <= 0) {
            $errors[] = 'Invalid order reference.';
        } else {
            $check_stmt = mysqli_prepare($conn, "SELECT id, status FROM orders WHERE id = ? AND user_id = ?");
            mysqli_stmt_bind_param($check_stmt, "ii", $order_id, $user_id);
            mysqli_stmt_execute($check_stmt);
            $existing_order = mysqli_fetch_assoc(mysqli_stmt_get_result($check_stmt));
            mysqli_stmt_close($check_stmt);

            if (!$existing_order) {
                $errors[] = 'Order not found.';
            } elseif (in_array(strtolower((string)($existing_order['status'] ?? 'pending')), ['cancelled', 'shipped', 'delivered'], true)) {
                $errors[] = 'This order cannot be cancelled anymore.';
            } elseif (!isOrderCancellable($existing_order['status'])) {
                $errors[] = 'This order cannot be cancelled anymore.';
            } else {
                $cancel_stmt = mysqli_prepare($conn, "UPDATE orders SET status = 'cancelled' WHERE id = ? AND user_id = ?");
                mysqli_stmt_bind_param($cancel_stmt, "ii", $order_id, $user_id);
                if (mysqli_stmt_execute($cancel_stmt)) {
                    setFlash('success', 'Your order has been cancelled successfully.');
                    header('Location: ' . BASE_URL . '/orders.php');
                    exit();
                } else {
                    $errors[] = 'Could not cancel the order right now. Please try again.';
                }
                mysqli_stmt_close($cancel_stmt);
            }
        }
    }
}

// Fetch orders and advance statuses lazily (time-driven auto-tracking)
$stmt = mysqli_prepare($conn, "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$ordersRaw = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

$orders = [];
foreach ($ordersRaw as $order) {
    // Auto-advance status based on elapsed time, then update the local array
    $liveStatus = advanceOrderStatus($order);
    $order['status'] = $liveStatus;
    $orders[] = $order;
}

require_once __DIR__ . '/includes/navbar.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-11">
            <div class="d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-3 mb-4">
                <div>
                    <h2 class="fw-bold mb-1"><i class="bi bi-receipt text-primary me-2"></i>My Orders</h2>
                    <p class="text-muted mb-0">Track every purchase, review order details, and cancel eligible requests in one polished hub.</p>
                </div>
                <a href="buy.php" class="btn btn-outline-primary rounded-pill px-4">Continue Shopping</a>
            </div>

            <?php displayFlash(); ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger rounded-4 shadow-sm mb-4">
                    <ul class="mb-0 ps-3">
                        <?php foreach ($errors as $err): ?>
                            <li><?= escape($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <?php if (count($orders) > 0): ?>
                    <?php foreach ($orders as $order): ?>
                        <?php
                            $item_stmt = mysqli_prepare($conn, "SELECT brand_name, model, price, quantity FROM order_items WHERE order_id = ?");
                            mysqli_stmt_bind_param($item_stmt, "i", $order['id']);
                            mysqli_stmt_execute($item_stmt);
                            $items = mysqli_fetch_all(mysqli_stmt_get_result($item_stmt), MYSQLI_ASSOC);
                            mysqli_stmt_close($item_stmt);

                            $order_status = strtolower((string)($order['status'] ?? 'placed'));
                            $is_cancelled = $order_status === 'cancelled';
                            $is_delivered = $order_status === 'delivered';
                            $status_label = getOrderStatusLabel($order_status);
                            $tracking_steps = buildOrderTrackingSteps($order);
                        ?>
                        <div class="col-12">
                            <div class="order-card rounded-4 overflow-hidden">
                                <div class="order-card-header">
                                    <div>
                                        <div class="d-flex align-items-center gap-2 flex-wrap">
                                            <span class="order-chip">Order <?= escape($order['order_number']) ?></span>
                                            <span class="badge <?= getOrderStatusBadgeClass($order_status) ?> rounded-pill px-3 py-2 fw-semibold">
                                                <?= escape($status_label) ?>
                                            </span>
                                        </div>
                                        <div class="text-muted small mt-2">
                                            <?= formatDate($order['created_at']) ?> • <?= count($items) ?> item<?= count($items) === 1 ? '' : 's' ?>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <div class="text-muted small">Total amount</div>
                                        <div class="fs-4 fw-bold text-primary"><?= formatPrice($order['total_amount']) ?></div>
                                    </div>
                                </div>

                                <div class="order-card-body">
                                    <div class="row g-4">
                                        <div class="col-lg-8">
                                            <div class="order-track-card">
                                                <div class="d-flex align-items-center justify-content-between mb-3">
                                                    <div>
                                                        <h6 class="fw-bold mb-1">Order tracking</h6>
                                                        <p class="small text-muted mb-0">Status updates automatically as your order progresses.</p>
                                                    </div>
                                                    <span class="badge <?= getOrderStatusBadgeClass($order_status) ?> rounded-pill px-3 py-2 fw-semibold">
                                                        <?= escape($status_label) ?>
                                                    </span>
                                                </div>
                                                <div class="order-track-progress">
                                                    <?php
                                                        $doneCount = 0;
                                                        foreach ($tracking_steps as $step) { if (!empty($step['done'])) $doneCount++; }
                                                        $progressPct = $is_cancelled ? 25 : (count($tracking_steps) > 0 ? ($doneCount / count($tracking_steps)) * 100 : 70);
                                                    ?>
                                                    <div class="order-track-bar <?= $is_cancelled ? 'cancelled' : '' ?>" style="width: <?= (int)$progressPct ?>%;"></div>
                                                </div>
                                                <div class="row g-2 mt-3">
                                                    <?php foreach ($tracking_steps as $step): ?>
                                                        <div class="col-6 col-md-3">
                                                            <div class="track-step <?= !empty($step['done']) ? 'done' : '' ?> <?= !empty($step['active']) ? 'active' : '' ?>">
                                                                <span class="track-step-dot"></span>
                                                                <div class="small fw-semibold"><?= escape($step['label']) ?></div>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-lg-4">
                                            <div class="order-items-card">
                                                <div class="d-flex align-items-center justify-content-between mb-3">
                                                    <h6 class="fw-bold mb-0">Items</h6>
                                                    <a href="invoice.php?order_id=<?= $order['id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill">Invoice</a>
                                                </div>

                                                <?php foreach ($items as $item): ?>
                                                    <div class="order-item-row">
                                                        <div>
                                                            <div class="fw-semibold small">
                                                                <?= escape($item['brand_name']) ?> <?= escape($item['model']) ?>
                                                            </div>
                                                            <div class="text-muted small">Qty: <?= (int)$item['quantity'] ?></div>
                                                        </div>
                                                        <span class="order-item-pill"><?= formatPrice($item['price']) ?></span>
                                                    </div>
                                                <?php endforeach; ?>

                                                <?php if (isOrderCancellable($order_status) && !$is_cancelled): ?>
                                                    <form method="POST" class="mt-3 cancel-order-form">
                                                        <?= renderCsrfInput() ?>
                                                        <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
                                                        <input type="hidden" name="cancel_order" value="1">
                                                        <button type="button" class="btn btn-outline-danger w-100 rounded-3 cancel-order-btn" data-order-id="<?= (int)$order['id'] ?>">Cancel Order</button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="order-empty-state rounded-4 text-center">
                            <i class="bi bi-bag-x fs-1 text-primary"></i>
                            <h4 class="fw-bold mt-3 mb-2">No orders yet</h4>
                            <p class="text-muted mb-4">Your purchased laptops will appear here with full tracking and quick action controls.</p>
                            <a href="buy.php" class="btn btn-primary rounded-pill px-4">Start Shopping</a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<!-- Confirm Cancel Modal -->
<div class="modal fade" id="confirmCancelModal" tabindex="-1" aria-labelledby="confirmCancelModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4">
            <div class="modal-header border-0">
                <h5 class="modal-title" id="confirmCancelModalLabel">Confirm Cancellation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to cancel this order? This action cannot be undone.
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">No, keep order</button>
                <button type="button" id="confirm-cancel-btn" class="btn btn-danger">Yes, cancel order</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var confirmModalEl = document.getElementById('confirmCancelModal');
    if (!confirmModalEl) return;
    var confirmModal = new bootstrap.Modal(confirmModalEl);
    var activeForm = null;

    document.querySelectorAll('.cancel-order-btn').forEach(function(btn){
        btn.addEventListener('click', function(e){
            var form = btn.closest('form.cancel-order-form');
            if (form) {
                activeForm = form;
                confirmModal.show();
            }
        });
    });

    document.getElementById('confirm-cancel-btn').addEventListener('click', function(){
        if (activeForm) {
            var hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'cancel_order';
            hidden.value = '1';
            activeForm.appendChild(hidden);
            activeForm.submit();
        }
    });
});
</script>