<?php
// checkout_payment.php - Checkout Step 3: Cash on Delivery Confirmation
// Creates the order + order_items + payments rows on Cash on Delivery order placement.
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/checkout_session.php';
require_once __DIR__ . '/includes/checkout_stepper.php';
require_once __DIR__ . '/includes/order_status.php';

$checkout = getCheckoutSession();
if (!$checkout || empty($checkout['items'])) {
    setFlash('warning', 'Please start from your cart.');
    header('Location: ' . BASE_URL . '/checkout_cart.php');
    exit();
}

requireCheckoutStep(3);

$errors = [];

$isPaymentSubmit = $_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['process_payment']) || (isset($_POST['form_action']) && $_POST['form_action'] === 'process_payment'));
if ($isPaymentSubmit) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Session expired. Please try again.';
    } else {
        $conn = getDbConnection();
        $user = getCurrentUser();
        $userId = (int)$user['id'];

        $items = $checkout['items'];
        $subtotal = (float)$checkout['subtotal'];
        $discount = (float)($checkout['discount'] ?? 0);
        $shippingCost = (float)($checkout['shipping_cost'] ?? 0);
        $total = computeCheckoutTotal($checkout);
        $shippingMethod = $checkout['shipping_method'] ?? 'standard';
        $shippingInfo = $checkout['shipping'] ?? [];
        $promoCode = $checkout['promo_code'] ?? null;

        $fullAddress = trim(
            ($shippingInfo['address'] ?? '') . ' ' .
            ($shippingInfo['apt'] ?? '') . ', ' .
            ($shippingInfo['city'] ?? '') . ', ' .
            ($shippingInfo['state'] ?? '') . ' ' .
            ($shippingInfo['zip'] ?? '')
        );

        mysqli_begin_transaction($conn);
        try {
            $orderNumber = 'LPF-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(3)));
            $nowSql = date('Y-m-d H:i:s');

            // Insert order with time-driven tracking columns
            $orderStmt = mysqli_prepare($conn, "INSERT INTO orders (order_number, user_id, total_amount, status, placed_at, status_updated_at, shipping_method, shipping_address, promo_code, discount_amount) VALUES (?, ?, ?, 'placed', ?, ?, ?, ?, ?, ?)");
            if (!$orderStmt) {
                throw new Exception('Unable to create order record.');
            }
            mysqli_stmt_bind_param($orderStmt, "sidsssssd", $orderNumber, $userId, $total, $nowSql, $nowSql, $shippingMethod, $fullAddress, $promoCode, $discount);
            mysqli_stmt_execute($orderStmt);
            $orderId = mysqli_insert_id($conn);
            mysqli_stmt_close($orderStmt);

            // Insert order items + decrement stock (FOR UPDATE locking)
            foreach ($items as $item) {
                $itemId = (int)$item['id'];
                $qty = max(1, (int)($item['selected_quantity'] ?? 1));
                $price = (float)$item['price'];

                // Lock stock row
                $stockStmt = mysqli_prepare($conn, "SELECT COALESCE(stock_quantity, quantity, 1) AS available_stock, user_id, status, approval_status FROM laptops WHERE id = ? FOR UPDATE");
                mysqli_stmt_bind_param($stockStmt, "i", $itemId);
                mysqli_stmt_execute($stockStmt);
                $stockRow = mysqli_fetch_assoc(mysqli_stmt_get_result($stockStmt));
                mysqli_stmt_close($stockStmt);

                if (!$stockRow) {
                    throw new Exception("{$item['model']} is no longer available.");
                }

                if (isOwnListing($userId, (int)$stockRow['user_id'])) {
                    throw new Exception("You cannot purchase your own listing ({$item['model']}).");
                }

                $availableStock = max(0, (int)($stockRow['available_stock'] ?? 1));
                if ($qty > $availableStock) {
                    throw new Exception("{$item['model']} only has {$availableStock} unit(s) available.");
                }

                $newStock = max(0, $availableStock - $qty);
                $updateStock = mysqli_prepare($conn, "UPDATE laptops SET stock_quantity = ?, quantity = ? WHERE id = ?");
                mysqli_stmt_bind_param($updateStock, "iii", $newStock, $newStock, $itemId);
                mysqli_stmt_execute($updateStock);
                mysqli_stmt_close($updateStock);

                $itemStmt = mysqli_prepare($conn, "INSERT INTO order_items (order_id, laptop_id, brand_name, model, price, quantity) VALUES (?, ?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($itemStmt, "iissdi", $orderId, $itemId, $item['brand_name'], $item['model'], $price, $qty);
                mysqli_stmt_execute($itemStmt);
                mysqli_stmt_close($itemStmt);
            }

            // Insert payment record (Cash on Delivery)
            $codRef = 'COD-' . strtoupper(bin2hex(random_bytes(6)));
            $payStmt = mysqli_prepare($conn, "INSERT INTO payments (order_id, user_id, amount, payment_method, payment_status, transaction_id) VALUES (?, ?, ?, 'cod', 'pending', ?)");
            mysqli_stmt_bind_param($payStmt, "iids", $orderId, $userId, $total, $codRef);
            mysqli_stmt_execute($payStmt);
            mysqli_stmt_close($payStmt);

            // Clear the user's cart
            $delCart = mysqli_prepare($conn, "DELETE FROM cart WHERE user_id = ?");
            mysqli_stmt_bind_param($delCart, "i", $userId);
            mysqli_stmt_execute($delCart);
            mysqli_stmt_close($delCart);

            mysqli_commit($conn);

            // Store the order id for the confirm page, clear checkout session
            $_SESSION['last_order_id'] = (int)$orderId;
            clearCheckoutSession();

            setFlash('success', 'Order placed successfully with Cash on Delivery!');
            header('Location: ' . BASE_URL . '/checkout_confirm.php?order_id=' . (int)$orderId);
            exit();
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $errors[] = $e->getMessage();
        }
    }
}

$items = $checkout['items'];
$subtotal = (float)($checkout['subtotal'] ?? 0);
$discount = (float)($checkout['discount'] ?? 0);
$shippingCost = (float)($checkout['shipping_cost'] ?? 0);
$total = computeCheckoutTotal($checkout);
$shippingInfo = $checkout['shipping'] ?? [];

$page_title = "Payment | Lapify Checkout";
$body_class = 'checkout-page';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="checkout-container">
    <?php renderCheckoutStepper(3); ?>

    <div class="checkout-step-view">
        <div class="row g-4">
            <!-- Left: COD Confirmation Panel -->
            <div class="col-lg-7">
                <div class="checkout-card">
                    <h2>Cash on Delivery (COD)</h2>
                    <p class="checkout-sub">Review your delivery details and place your order.</p>

                    <?php displayFlash(); ?>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger rounded-3 mb-4">
                            <ul class="mb-0 ps-3">
                                <?php foreach ($errors as $err): ?>
                                    <li><?= escape($err) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <div class="checkout-trust-banner mb-4">
                        <i class="bi bi-truck me-2"></i>
                        <span><strong>No online payment needed</strong> — pay in cash directly to the delivery agent upon delivery.</span>
                    </div>

                    <!-- COD Details & Shipping Destination Card -->
                    <div class="cod-details-box mb-4">
                        <h3 class="fs-6 fw-bold mb-3 text-dark"><i class="bi bi-geo-alt-fill text-primary me-2"></i>Delivery Address</h3>
                        <p class="mb-1 fw-bold text-dark fs-6"><?= escape(($shippingInfo['first_name'] ?? '') . ' ' . ($shippingInfo['last_name'] ?? '')) ?></p>
                        <p class="mb-1 text-dark small" style="color: #1e293b !important; font-weight: 500;">
                            <?= escape($shippingInfo['address'] ?? '') ?>
                            <?= !empty($shippingInfo['apt']) ? ', ' . escape($shippingInfo['apt']) : '' ?>
                        </p>
                        <p class="mb-1 text-dark small" style="color: #1e293b !important; font-weight: 500;">
                            <?= escape($shippingInfo['city'] ?? '') ?>, <?= escape($shippingInfo['state'] ?? '') ?> <?= escape($shippingInfo['zip'] ?? '') ?>
                        </p>
                        <p class="mb-0 text-dark small" style="color: #1e293b !important; font-weight: 500;"><i class="bi bi-telephone-fill text-primary me-1"></i><?= escape($shippingInfo['phone'] ?? '') ?></p>
                    </div>

                    <form method="POST" action="checkout_payment.php" id="payment-form">
                        <?= renderCsrfInput() ?>
                        <input type="hidden" name="form_action" value="process_payment">

                        <div class="d-flex gap-3 mt-4">
                            <a href="checkout_shipping.php" class="btn-checkout btn-checkout-outline">
                                <i class="bi bi-arrow-left"></i> Back
                            </a>
                            <button type="submit" name="process_payment" class="btn-checkout flex-grow-1">
                                Place Order (<?= formatPrice($total) ?>)
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Right: Order Summary -->
            <div class="col-lg-5">
                <div class="checkout-card checkout-summary">
                    <h2 class="fs-5 mb-3">Order Summary</h2>

                    <div class="checkout-summary-items">
                        <?php foreach ($items as $item): ?>
                        <div class="checkout-summary-item">
                            <span class="item-name"><?= escape($item['model']) ?></span>
                            <span class="item-qty">× <?= (int)($item['selected_quantity'] ?? 1) ?></span>
                            <span class="item-line"><?= formatPrice((float)$item['price'] * (int)($item['selected_quantity'] ?? 1)) ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="checkout-summary-row muted">
                        <span>Subtotal</span>
                        <span id="checkout-subtotal" data-discount="<?= $discount ?>" data-last-value="<?= $subtotal ?>"><?= formatPrice($subtotal) ?></span>
                    </div>
                    <div class="checkout-summary-row muted">
                        <span>Shipping</span>
                        <span id="checkout-shipping" data-shipping="<?= $shippingCost ?>"><?= $shippingCost > 0 ? formatPrice($shippingCost) : 'Free' ?></span>
                    </div>
                    <?php if ($discount > 0): ?>
                    <div class="checkout-summary-row muted">
                        <span>Discount (<?= escape($checkout['promo_code'] ?? '') ?>)</span>
                        <span class="discount-amount" id="checkout-discount">-<?= formatPrice($discount) ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="checkout-summary-row total">
                        <span>Total (Pay on Delivery)</span>
                        <span id="checkout-total" data-last-value="<?= $total ?>"><?= formatPrice($total) ?></span>
                    </div>

                    <div class="checkout-protection">
                        <i class="bi bi-shield-check"></i>
                        <span><strong>Lapify Buyer Protection</strong> included — pay safely when your laptop arrives.</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>