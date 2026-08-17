<?php
// checkout_confirm.php - Checkout Step 4: Order Confirmed
// Shows the animated checkmark, order summary, and live-computed tracking.
$page_title = "Order Confirmed | Lapify";
$body_class = 'checkout-page';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/checkout_session.php';
require_once __DIR__ . '/includes/checkout_stepper.php';
require_once __DIR__ . '/includes/order_status.php';

requireLogin();

$orderId = (int)($_GET['order_id'] ?? $_SESSION['last_order_id'] ?? 0);
if ($orderId <= 0) {
    setFlash('warning', 'No order found to confirm.');
    header('Location: ' . BASE_URL . '/orders.php');
    exit();
}

// Load order with live-computed status (auto-advances based on elapsed time)
$order = getOrderWithLiveStatus($orderId);
if (!$order || (int)$order['user_id'] !== (int)getCurrentUser()['id']) {
    setFlash('error', 'Order not found.');
    header('Location: ' . BASE_URL . '/orders.php');
    exit();
}

$conn = getDbConnection();
$itemStmt = mysqli_prepare($conn, "SELECT brand_name, model, price, quantity FROM order_items WHERE order_id = ?");
mysqli_stmt_bind_param($itemStmt, "i", $orderId);
mysqli_stmt_execute($itemStmt);
$items = mysqli_fetch_all(mysqli_stmt_get_result($itemStmt), MYSQLI_ASSOC);
mysqli_stmt_close($itemStmt);

$trackingSteps = buildOrderTrackingSteps($order);
$deliveryRange = estimateDeliveryRange($order);
$status = strtolower((string)($order['status'] ?? 'placed'));

require_once __DIR__ . '/includes/navbar.php';
?>

<div class="checkout-container">
    <?php renderCheckoutStepper(4); ?>

    <div class="checkout-step-view">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <div class="checkout-card text-center">
                    <!-- Animated checkmark -->
                    <svg class="confirm-check" viewBox="0 0 100 100" aria-hidden="true">
                        <circle cx="50" cy="50" r="48"/>
                        <path d="M28 52 L44 68 L74 36"/>
                    </svg>

                    <h2>Order Confirmed!</h2>
                    <p class="checkout-sub">Your order has been placed! Pay in cash when it arrives.</p>

                    <?php displayFlash(); ?>

                    <!-- Auto-redirect Status Box -->
                    <div id="redirect-status-box" class="redirect-status-box my-3 p-3 rounded-3" style="background: #f0f9ff; border: 1px solid #bae6fd;">
                        <div class="d-flex align-items-center justify-content-center gap-2 mb-2">
                            <span class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></span>
                            <span id="redirect-msg" class="fw-semibold text-dark">Redirecting to your invoice in <span id="countdown-sec">3</span>s...</span>
                        </div>
                        <div class="progress" style="height: 6px; background: #e0f2fe;">
                            <div id="redirect-progress-bar" class="progress-bar bg-primary" style="width: 0%; transition: width 0.1s linear;"></div>
                        </div>
                    </div>

                    <!-- Summary -->
                    <div class="confirm-summary">
                        <div class="confirm-summary-item">
                            <div class="label">Order ID</div>
                            <div class="value"><?= escape($order['order_number']) ?></div>
                        </div>
                        <div class="confirm-summary-item">
                            <div class="label">Estimated Delivery</div>
                            <div class="value"><?= escape($deliveryRange['start']) ?> – <?= escape($deliveryRange['end']) ?></div>
                        </div>
                        <div class="confirm-summary-item">
                            <div class="label">Total Due on Delivery</div>
                            <div class="value"><?= formatPrice($order['total_amount']) ?></div>
                        </div>
                    </div>

                    <!-- Order items recap -->
                    <div class="text-start mb-4">
                        <h3 class="fs-6 fw-bold mb-2">Items</h3>
                        <?php foreach ($items as $item): ?>
                            <div class="checkout-summary-item">
                                <span class="item-name"><?= escape($item['brand_name']) ?> <?= escape($item['model']) ?></span>
                                <span class="item-qty">× <?= (int)$item['quantity'] ?></span>
                                <span class="item-line"><?= formatPrice((float)$item['price'] * (int)$item['quantity']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Live order tracking -->
                    <div class="text-start">
                        <h3 class="fs-6 fw-bold mb-3">Order Tracking</h3>
                        <div class="tracking-list">
                            <?php foreach ($trackingSteps as $step): ?>
                                <div class="tracking-step <?= !empty($step['done']) ? 'done' : '' ?> <?= !empty($step['in_progress']) ? 'in-progress' : '' ?>">
                                    <span class="tracking-step-icon">
                                        <?php if (!empty($step['done'])): ?>
                                            <i class="bi bi-check-lg"></i>
                                        <?php elseif (!empty($step['in_progress'])): ?>
                                            <i class="bi bi-hourglass-split"></i>
                                        <?php else: ?>
                                            <i class="bi bi-circle"></i>
                                        <?php endif; ?>
                                    </span>
                                    <div class="tracking-step-info">
                                        <div class="tracking-step-title"><?= escape($step['label']) ?></div>
                                        <div class="tracking-step-status">
                                            <?php if (!empty($step['done'])): ?>
                                                Completed
                                            <?php elseif (!empty($step['in_progress'])): ?>
                                                IN PROGRESS
                                            <?php else: ?>
                                                Pending
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="d-flex flex-column flex-sm-row gap-3 mt-4 justify-content-center">
                        <a href="buy.php" class="btn-checkout btn-checkout-outline">
                            <i class="bi bi-arrow-left me-1"></i> Continue Shopping
                        </a>
                        <a href="invoice.php?order_id=<?= (int)$order['id'] ?>" class="btn-checkout" id="view-invoice-now-btn">
                            <i class="bi bi-file-earmark-pdf me-1"></i> View Invoice Now
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const orderId = <?= (int)$order['id'] ?>;
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const redirectTime = prefersReducedMotion ? 1000 : 3000;
    const progressBar = document.getElementById('redirect-progress-bar');
    const countdownSec = document.getElementById('countdown-sec');
    
    let startTime = performance.now();
    let redirecting = false;

    function updateRedirectProgress(now) {
        if (redirecting) return;
        const elapsed = now - startTime;
        const pct = Math.min(100, (elapsed / redirectTime) * 100);
        const remaining = Math.max(0, Math.ceil((redirectTime - elapsed) / 1000));
        
        if (progressBar) progressBar.style.width = pct + '%';
        if (countdownSec) countdownSec.textContent = remaining;

        if (elapsed >= redirectTime) {
            redirecting = true;
            window.location.href = 'invoice.php?order_id=' + orderId;
        } else {
            requestAnimationFrame(updateRedirectProgress);
        }
    }

    // Delay start slightly to let the checkmark draw animation play
    setTimeout(function () {
        startTime = performance.now();
        requestAnimationFrame(updateRedirectProgress);
    }, prefersReducedMotion ? 0 : 500);
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>