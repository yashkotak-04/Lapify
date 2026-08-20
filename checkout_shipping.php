<?php
// checkout_shipping.php - Checkout Step 2: Shipping Address & Method
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/checkout_session.php';
require_once __DIR__ . '/includes/checkout_stepper.php';

$checkout = getCheckoutSession();
if (!$checkout || empty($checkout['items'])) {
    setFlash('warning', 'Please start from your cart.');
    header('Location: ' . BASE_URL . '/checkout_cart.php');
    exit();
}

requireCheckoutStep(2);

$curr = getCurrentUser();
$defaultFirst = '';
$defaultLast = '';
$defaultPhone = '';
if ($curr) {
    $parts = explode(' ', trim($curr['full_name'] ?? ''), 2);
    $defaultFirst = $parts[0] ?? '';
    $defaultLast  = $parts[1] ?? '';
    $rawPhone = preg_replace('/\D/', '', $curr['phone'] ?? '');
    if (strlen($rawPhone) > 10 && str_starts_with($rawPhone, '91')) {
        $rawPhone = substr($rawPhone, 2);
    }
    $defaultPhone = substr($rawPhone, -10);
}

$errors = [];
$shipping = $checkout['shipping'] ?? [
    'first_name'   => $defaultFirst,
    'last_name'    => $defaultLast,
    'address'      => '',
    'apt'          => '',
    'city'         => '',
    'state'        => '',
    'zip'          => '',
    'phone'        => $defaultPhone,
    'save_address' => false,
];
$shippingMethod = $checkout['shipping_method'] ?? 'standard';

$isShippingSubmit = $_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['continue_payment']) || (isset($_POST['form_action']) && $_POST['form_action'] === 'continue_payment'));
if ($isShippingSubmit) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Session expired. Please try again.';
    } else {
        $cleanPhone = preg_replace('/\D/', '', $_POST['phone'] ?? '');
        if (strlen($cleanPhone) > 10 && str_starts_with($cleanPhone, '91')) {
            $cleanPhone = substr($cleanPhone, 2);
        }
        if (strlen($cleanPhone) > 10) {
            $cleanPhone = substr($cleanPhone, -10);
        }
        $cleanZip = preg_replace('/\D/', '', $_POST['zip'] ?? '');

        $shipping = [
            'first_name'   => trim(sanitizeInput($_POST['first_name'] ?? '')),
            'last_name'    => trim(sanitizeInput($_POST['last_name'] ?? '')),
            'address'      => trim(sanitizeInput($_POST['address'] ?? '')),
            'apt'          => trim(sanitizeInput($_POST['apt'] ?? '')),
            'city'         => trim(sanitizeInput($_POST['city'] ?? '')),
            'state'        => trim(sanitizeInput($_POST['state'] ?? '')),
            'zip'          => $cleanZip,
            'phone'        => $cleanPhone,
            'save_address' => !empty($_POST['save_address']),
        ];
        $shippingMethod = ($_POST['shipping_method'] ?? 'standard') === 'express' ? 'express' : 'standard';

        if ($shipping['first_name'] === '') $errors[] = 'First name is required.';
        if ($shipping['last_name'] === '') $errors[] = 'Last name is required.';
        if ($shipping['address'] === '') $errors[] = 'Street address is required.';
        if ($shipping['city'] === '') $errors[] = 'City is required.';
        if ($shipping['state'] === '') $errors[] = 'State is required.';
        if ($shipping['zip'] === '' || !preg_match('/^\d{5,6}$/', $shipping['zip'])) $errors[] = 'A valid 6-digit PIN code is required.';
        if ($shipping['phone'] === '' || !preg_match('/^\d{10}$/', $shipping['phone'])) $errors[] = 'A valid 10-digit phone number is required.';

        if (empty($errors)) {
            $shippingCost = getShippingCost($shippingMethod);
            updateCheckoutSession([
                'shipping'        => $shipping,
                'shipping_method' => $shippingMethod,
                'shipping_cost'   => $shippingCost,
                'step'            => 3,
            ]);
            header('Location: ' . BASE_URL . '/checkout_payment.php');
            exit();
        }
    }
}

$items = $checkout['items'];
$subtotal = (float)($checkout['subtotal'] ?? 0);
$discount = (float)($checkout['discount'] ?? 0);
$shippingCost = (float)($checkout['shipping_cost'] ?? 0);
$total = computeCheckoutTotal($checkout);

$page_title = "Shipping | Lapify Checkout";
$body_class = 'checkout-page';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="checkout-container">
    <?php renderCheckoutStepper(2); ?>

    <div class="checkout-step-view">
        <div class="row g-4">
            <!-- Left: Shipping form -->
            <div class="col-lg-7">
                <div class="checkout-card">
                    <h2>Shipping Address</h2>
                    <p class="checkout-sub">Where should we deliver your laptops?</p>

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

                    <form method="POST" action="checkout_shipping.php" id="shipping-form">
                        <?= renderCsrfInput() ?>
                        <input type="hidden" name="form_action" value="continue_payment">

                        <div class="checkout-grid-2">
                            <div class="checkout-field">
                                <label for="first_name">First Name *</label>
                                <input type="text" id="first_name" name="first_name" value="<?= escape($shipping['first_name']) ?>" placeholder="John" required autocomplete="given-name">
                            </div>
                            <div class="checkout-field">
                                <label for="last_name">Last Name *</label>
                                <input type="text" id="last_name" name="last_name" value="<?= escape($shipping['last_name']) ?>" placeholder="Doe" required autocomplete="family-name">
                            </div>
                        </div>

                        <div class="checkout-field">
                            <label for="address">Street Address *</label>
                            <input type="text" id="address" name="address" value="<?= escape($shipping['address']) ?>" placeholder="123 Main Street" required autocomplete="street-address">
                        </div>

                        <div class="checkout-field">
                            <label for="apt">Apt / Suite <span class="text-muted">(optional)</span></label>
                            <input type="text" id="apt" name="apt" value="<?= escape($shipping['apt']) ?>" placeholder="Apt 4B" autocomplete="address-line2">
                        </div>

                        <div class="checkout-grid-2">
                            <div class="checkout-field">
                                <label for="city">City *</label>
                                <input type="text" id="city" name="city" value="<?= escape($shipping['city']) ?>" placeholder="Mumbai" required autocomplete="address-level2">
                            </div>
                            <div class="checkout-field">
                                <label for="state">State *</label>
                                <input type="text" id="state" name="state" value="<?= escape($shipping['state']) ?>" placeholder="Maharashtra" required autocomplete="address-level1">
                            </div>
                        </div>

                        <div class="checkout-grid-2">
                            <div class="checkout-field">
                                <label for="zip">PIN Code *</label>
                                <input type="text" id="zip" name="zip" value="<?= escape($shipping['zip']) ?>" placeholder="400001" required autocomplete="postal-code" inputmode="numeric" maxlength="6">
                            </div>
                            <div class="checkout-field">
                                <label for="phone">Phone Number *</label>
                                <input type="tel" id="phone" name="phone" value="<?= escape($shipping['phone']) ?>" placeholder="9876543210" required autocomplete="tel" inputmode="numeric" maxlength="10">
                            </div>
                        </div>

                        <label class="checkout-checkbox">
                            <input type="checkbox" name="save_address" value="1" <?= !empty($shipping['save_address']) ? 'checked' : '' ?>>
                            <span>Save this address for future orders</span>
                        </label>

                        <h3 class="fs-6 fw-bold mt-4 mb-2">Shipping Method</h3>
                        <div class="shipping-methods">
                            <label class="shipping-method <?= $shippingMethod === 'standard' ? 'selected' : '' ?>">
                                <input type="radio" name="shipping_method" value="standard" data-cost="0" <?= $shippingMethod === 'standard' ? 'checked' : '' ?>>
                                <span class="shipping-method-info">
                                    <span class="shipping-method-name">Standard Shipping</span>
                                    <span class="shipping-method-desc">3–5 business days</span>
                                </span>
                                <span class="shipping-method-price">Free</span>
                            </label>
                            <label class="shipping-method <?= $shippingMethod === 'express' ? 'selected' : '' ?>">
                                <input type="radio" name="shipping_method" value="express" data-cost="199" <?= $shippingMethod === 'express' ? 'checked' : '' ?>>
                                <span class="shipping-method-info">
                                    <span class="shipping-method-name">Express Shipping</span>
                                    <span class="shipping-method-desc">1–2 business days</span>
                                </span>
                                <span class="shipping-method-price">₹199</span>
                            </label>
                        </div>

                        <div class="d-flex gap-3 mt-4">
                            <a href="checkout_cart.php" class="btn-checkout btn-checkout-outline">
                                <i class="bi bi-arrow-left"></i> Back to Cart
                            </a>
                            <button type="submit" name="continue_payment" class="btn-checkout flex-grow-1">
                                Continue to Payment <i class="bi bi-arrow-right"></i>
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
                        <span>Total</span>
                        <span id="checkout-total" data-last-value="<?= $total ?>"><?= formatPrice($total) ?></span>
                    </div>

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