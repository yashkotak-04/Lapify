<?php
// checkout_cart.php - Checkout Step 1: Cart Review
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/checkout_session.php';
require_once __DIR__ . '/includes/checkout_stepper.php';

$hasDirect = isset($_GET['direct_laptop_id']) && (int)$_GET['direct_laptop_id'] > 0;
if ($hasDirect) {
    $directId = (int)$_GET['direct_laptop_id'];
    $conn = getDbConnection();
    $current_u = getCurrentUser();
    $uId = $current_u['id'] ?? null;
    if ($uId) {
        $stmt = $conn->prepare("INSERT INTO cart (user_id, laptop_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE created_at = NOW()");
        $stmt->bind_param("ii", $uId, $directId);
        $stmt->execute();
        $stmt->close();
    }
}

$checkout = initCheckoutSession($hasDirect);

// Handle AJAX promo code application
$isApplyPromo = $_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['apply_promo']) || (isset($_POST['form_action']) && $_POST['form_action'] === 'apply_promo'));
if ($isApplyPromo) {
    header('Content-Type: application/json');
    $csrf = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!verifyCsrfToken($csrf)) {
        echo json_encode(['success' => false, 'message' => 'Invalid or expired security token. Please refresh the page.']);
        exit();
    }

    $code = trim($_POST['promo_code'] ?? '');
    $quantities = $_POST['quantity'] ?? [];
    $items = $checkout['items'] ?? [];
    $subtotal = 0.0;

    foreach ($items as &$item) {
        $id = (int)$item['id'];
        $maxStock = max(1, (int)($item['stock_quantity'] ?? $item['quantity'] ?? $item['max_stock'] ?? $item['available_stock'] ?? 1));
        $item['max_stock'] = $maxStock;
        $item['available_stock'] = $maxStock;
        if (isset($quantities[$id])) {
            $item['selected_quantity'] = max(1, min($maxStock, (int)$quantities[$id]));
        } else {
            $item['selected_quantity'] = max(1, (int)($item['selected_quantity'] ?? 1));
        }
        $subtotal += (float)$item['price'] * (int)$item['selected_quantity'];
    }
    unset($item);

    $promoResult = applyPromoCode($code, $subtotal);
    if ($promoResult['success']) {
        $discount = (float)$promoResult['discount'];
        $promoCode = $promoResult['code'];
        updateCheckoutSession([
            'items'      => $items,
            'subtotal'   => $subtotal,
            'discount'   => $discount,
            'promo_code' => $promoCode,
        ]);
        echo json_encode([
            'success'  => true,
            'message'  => $promoResult['message'],
            'discount' => $discount,
            'code'     => $promoCode,
            'subtotal' => $subtotal,
            'total'    => max(0, $subtotal - $discount + (float)($checkout['shipping_cost'] ?? 0)),
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => $promoResult['message']]);
    }
    exit();
}

// Handle item removal
$isRemove = $_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['remove_item']) || (isset($_POST['form_action']) && $_POST['form_action'] === 'remove_item'));
if ($isRemove) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Session expired. Please try again.');
    } else {
        $removeId = (int)($_POST['remove_item'] ?? $_POST['item_id'] ?? 0);
        $items = array_values(array_filter($checkout['items'], function ($item) use ($removeId) {
            return (int)$item['id'] !== $removeId;
        }));
        $subtotal = 0.0;
        foreach ($items as $item) {
            $subtotal += (float)$item['price'] * (int)($item['selected_quantity'] ?? 1);
        }

        // Recheck promo discount on new subtotal if promo was active
        $promoCode = $checkout['promo_code'] ?? null;
        $discount = 0.0;
        if (!empty($promoCode) && $subtotal > 0) {
            $promoRes = applyPromoCode($promoCode, $subtotal);
            if ($promoRes['success']) {
                $discount = (float)$promoRes['discount'];
            } else {
                $promoCode = null;
            }
        }

        // Also remove from DB cart
        $currUser = getCurrentUser();
        if ($currUser) {
            $conn = getDbConnection();
            $delStmt = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND laptop_id = ?");
            $delStmt->bind_param("ii", $currUser['id'], $removeId);
            $delStmt->execute();
            $delStmt->close();
        }

        updateCheckoutSession([
            'items'      => $items,
            'subtotal'   => $subtotal,
            'discount'   => $discount,
            'promo_code' => $promoCode,
        ]);
        setFlash('success', 'Item removed from your cart.');
        header('Location: ' . BASE_URL . '/checkout_cart.php');
        exit();
    }
}

// Handle proceed to shipping
$isProceed = $_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['proceed_shipping']) || (isset($_POST['form_action']) && $_POST['form_action'] === 'proceed_shipping'));
if ($isProceed) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Session expired. Please try again.');
    } else {
        // Collect updated quantities from POST
        $quantities = $_POST['quantity'] ?? [];
        $items = $checkout['items'];
        $subtotal = 0.0;

        foreach ($items as &$item) {
            $id = (int)$item['id'];
            $maxStock = max(1, (int)($item['stock_quantity'] ?? $item['quantity'] ?? $item['max_stock'] ?? $item['available_stock'] ?? 1));
            $item['max_stock'] = $maxStock;
            $item['available_stock'] = $maxStock;

            if (isset($quantities[$id])) {
                $qty = max(1, min($maxStock, (int)$quantities[$id]));
                $item['selected_quantity'] = $qty;
            } else {
                $item['selected_quantity'] = max(1, (int)($item['selected_quantity'] ?? 1));
            }
            $subtotal += (float)$item['price'] * (int)$item['selected_quantity'];
        }
        unset($item);

        // Recalculate discount if a promo code is applied
        $promoCode = trim($_POST['promo_code'] ?? $checkout['promo_code'] ?? '');
        $discount = 0.0;
        if (!empty($promoCode) && $subtotal > 0) {
            $promoRes = applyPromoCode($promoCode, $subtotal);
            if ($promoRes['success']) {
                $discount = (float)$promoRes['discount'];
                $promoCode = $promoRes['code'];
            } else {
                $promoCode = null;
            }
        }

        updateCheckoutSession([
            'items'      => $items,
            'subtotal'   => $subtotal,
            'discount'   => $discount,
            'promo_code' => $promoCode,
            'step'       => 2,
        ]);
        header('Location: ' . BASE_URL . '/checkout_shipping.php');
        exit();
    }
}

// Refresh checkout after any POST handling
$checkout = getCheckoutSession();
$items = $checkout['items'] ?? [];
$subtotal = (float)($checkout['subtotal'] ?? 0);
$discount = (float)($checkout['discount'] ?? 0);
$promoCode = $checkout['promo_code'] ?? null;
$total = computeCheckoutTotal($checkout);

if (count($items) === 0) {
    setFlash('warning', 'Your cart is empty. Add some laptops first!');
    header('Location: ' . BASE_URL . '/buy.php');
    exit();
}

$page_title = "Your Cart | Lapify Checkout";
$body_class = 'checkout-page';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="checkout-container">
    <?php renderCheckoutStepper(1); ?>

    <div class="checkout-step-view">
        <div class="row g-4">
            <!-- Left: Cart items -->
            <div class="col-lg-7">
                <div class="checkout-card">
                    <h2>Your Cart (<?= count($items) ?> item<?= count($items) === 1 ? '' : 's' ?>)</h2>
                    <p class="checkout-sub">Review your selected laptops and adjust quantities.</p>

                    <?php displayFlash(); ?>

                    <form method="POST" action="checkout_cart.php" id="cart-form">
                        <?= renderCsrfInput() ?>
                        <input type="hidden" name="form_action" id="cart-form-action" value="proceed_shipping">
                        <input type="hidden" name="promo_code" id="promo-code-hidden" value="<?= escape($promoCode ?? '') ?>">

                        <div class="checkout-items-list">
                            <?php foreach ($items as $item): ?>
                            <?php
                                $img = getLaptopImageUrl($item) ?: 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?auto=format&fit=crop&w=600&q=80';
                                $availableStock = max(1, (int)($item['stock_quantity'] ?? $item['quantity'] ?? $item['available_stock'] ?? 1));
                                $maxStock = $availableStock;
                                $qty = (int)($item['selected_quantity'] ?? 1);
                                $displayTitle = escape($item['model']);
                                if (!empty($item['brand_name']) && stripos($item['model'], $item['brand_name']) === false) {
                                    $displayTitle = escape($item['brand_name']) . ' ' . $displayTitle;
                                }
                            ?>
                            <div class="checkout-item" data-id="<?= (int)$item['id'] ?>" data-price="<?= (float)$item['price'] ?>">
                                <img src="<?= escape($img) ?>" alt="<?= escape($item['model']) ?>" class="checkout-item-thumb" onerror="this.src='https://images.unsplash.com/photo-1517336714731-489689fd1ca8?auto=format&fit=crop&w=600&q=80'">

                                <div class="checkout-item-info">
                                    <div class="checkout-item-title"><?= $displayTitle ?></div>
                                    <div class="checkout-item-sub">
                                        Condition: <span class="badge bg-secondary text-capitalize"><?= escape($item['laptop_condition'] ?? $item['condition_type'] ?? 'used') ?></span>
                                        <?php if ($availableStock <= 3): ?>
                                            <span class="text-warning ms-2"><i class="bi bi-fire"></i> Only <?= $availableStock ?> left</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="checkout-item-price mt-1"><?= formatPrice((float)$item['price'] * $qty) ?></div>
                                </div>

                                <div class="checkout-qty">
                                    <button type="button" class="btn-qty-minus qty-minus" data-qty-action="minus" aria-label="Decrease quantity"><i class="bi bi-dash"></i></button>
                                    <input type="number" name="quantity[<?= (int)$item['id'] ?>]" value="<?= $qty ?>" min="1" max="<?= $maxStock ?>" readonly>
                                    <button type="button" class="btn-qty-plus qty-plus" data-qty-action="plus" aria-label="Increase quantity"><i class="bi bi-plus"></i></button>
                                </div>

                                <button type="submit" name="remove_item" value="<?= (int)$item['id'] ?>" class="checkout-item-remove" title="Remove item" onclick="document.getElementById('cart-form-action').value='remove_item';">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="mt-4">
                            <button type="submit" name="proceed_shipping" class="btn-checkout btn-checkout-block" onclick="document.getElementById('cart-form-action').value='proceed_shipping';">
                                Proceed to Shipping <i class="bi bi-arrow-right"></i>
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
                        <div class="checkout-summary-item" data-id="<?= (int)$item['id'] ?>">
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
                        <span id="checkout-shipping" data-shipping="0">Free</span>
                    </div>
                    <?php if ($discount > 0): ?>
                    <div class="checkout-summary-row muted">
                        <span>Discount (<?= escape($checkout['promo_code'] ?? '') ?>)</span>
                        <span class="discount-amount" id="checkout-discount">-<?= formatPrice($discount) ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="checkout-summary-row total">
                        <span>Total</span>
                        <span id="checkout-total" data-last-value="<?= $total ?>"><?= formatPrice($total) ?></span>
                    </div>

                    <!-- Promo Code input -->
                    <div class="checkout-promo">
                        <input type="text" id="promo-code" placeholder="Enter coupon code" value="<?= escape($promoCode ?? '') ?>" autocomplete="off" <?= !empty($promoCode) ? 'disabled' : '' ?>>
                        <button type="button" class="btn-checkout btn-checkout-outline" id="apply-promo" <?= !empty($promoCode) ? 'disabled' : '' ?>>
                            <?= !empty($promoCode) ? '<i class="bi bi-check-lg me-1"></i>Applied' : 'Apply' ?>
                        </button>
                    </div>
                    <div id="promo-feedback">
                        <?php if (!empty($promoCode)): ?>
                            <div class="coupon-success-card" id="coupon-success-banner">
                                <div class="coupon-success-badge">
                                    <i class="bi bi-patch-check-fill"></i>
                                    <span>Coupon <strong><?= escape($promoCode) ?></strong> Applied!</span>
                                </div>
                                <div class="coupon-saved-text">Saved <?= formatPrice($discount) ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="checkout-promo-hint">Try code <code>LAPIFY10</code> or <code>LAPIFY50</code> for up to 50% off!</div>

                    <div class="checkout-protection">
                        <i class="bi bi-shield-check"></i>
                        <span><strong>Lapify Buyer Protection</strong> included — secure transactions, verified sellers.</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>