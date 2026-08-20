<?php
// includes/checkout_session.php - Shared multi-step checkout session state.
// Persists cart, shipping, and payment data across the 4-step flow so a
// refresh never loses progress. Also enforces step ordering (can't jump
// to Payment without completing Shipping).

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

function initCheckoutSession(bool $forceReset = false) {
    requireLogin();

    $conn = getDbConnection();
    $user = getCurrentUser();
    $userId = (int)$user['id'];

    $directLaptopId = (int)($_GET['direct_laptop_id'] ?? 0);

    // If direct laptop id is supplied, check if session already matches it
    if ($directLaptopId > 0) {
        $existingDirect = (int)($_SESSION['checkout']['direct_laptop_id'] ?? 0);
        if ($existingDirect !== $directLaptopId || $forceReset) {
            unset($_SESSION['checkout']);
        }
    }

    // If we already have a session and it's not stale, keep it.
    if (!empty($_SESSION['checkout']['items']) && empty($_GET['reset']) && !$forceReset) {
        return $_SESSION['checkout'];
    }

    $items = [];
    $total = 0.0;

    if ($directLaptopId > 0) {
        $stmt = mysqli_prepare($conn, "SELECT l.*, b.brand_name, u.full_name AS seller_name FROM laptops l JOIN brands b ON l.brand_id = b.id JOIN users u ON l.user_id = u.id WHERE l.id = ? AND (l.status = 'approved' OR l.approval_status = 'approved' OR l.status = 'Available')");
        mysqli_stmt_bind_param($stmt, "i", $directLaptopId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        if ($item = mysqli_fetch_assoc($res)) {
            if (!isOwnListing($userId, (int)$item['user_id'])) {
                $availableStock = max(1, (int)($item['stock_quantity'] ?? $item['quantity'] ?? 1));
                $item['selected_quantity'] = 1;
                $item['max_stock'] = $availableStock;
                $item['available_stock'] = $availableStock;
                $items[] = $item;
                $total += (float)$item['price'];
            }
        }
        mysqli_stmt_close($stmt);
    } else {
        $stmt = mysqli_prepare($conn, "SELECT l.*, b.brand_name, u.full_name AS seller_name FROM cart c JOIN laptops l ON c.laptop_id = l.id JOIN brands b ON l.brand_id = b.id JOIN users u ON l.user_id = u.id WHERE c.user_id = ? AND (l.status = 'approved' OR l.approval_status = 'approved' OR l.status = 'Available') ORDER BY c.id DESC");
        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        while ($item = mysqli_fetch_assoc($res)) {
            if (isOwnListing($userId, (int)$item['user_id'])) {
                continue;
            }
            $availableStock = max(1, (int)($item['stock_quantity'] ?? $item['quantity'] ?? 1));
            $item['selected_quantity'] = 1;
            $item['max_stock'] = $availableStock;
            $item['available_stock'] = $availableStock;
            $items[] = $item;
            $total += (float)$item['price'];
        }
        mysqli_stmt_close($stmt);
    }

    $checkout = [
        'items' => $items,
        'subtotal' => $total,
        'shipping_method' => 'standard',
        'shipping_cost' => 0.0,
        'discount' => 0.0,
        'promo_code' => null,
        'direct_laptop_id' => $directLaptopId > 0 ? $directLaptopId : null,
        'shipping' => null,
        'payment' => null,
        'step' => 1,
    ];

    $_SESSION['checkout'] = $checkout;
    return $checkout;
}

function getCheckoutSession() {
    return $_SESSION['checkout'] ?? null;
}

function updateCheckoutSession(array $data) {
    if (!isset($_SESSION['checkout'])) {
        $_SESSION['checkout'] = [];
    }
    $_SESSION['checkout'] = array_merge($_SESSION['checkout'], $data);
    return $_SESSION['checkout'];
}

function clearCheckoutSession() {
    unset($_SESSION['checkout']);
}

function computeCheckoutTotal(array $checkout) {
    $subtotal = (float)($checkout['subtotal'] ?? 0);
    $discount = (float)($checkout['discount'] ?? 0);
    $shipping = (float)($checkout['shipping_cost'] ?? 0);
    return max(0, $subtotal - $discount + $shipping);
}

function requireCheckoutStep(int $requiredStep) {
    $checkout = getCheckoutSession();
    $currentStep = (int)($checkout['step'] ?? 1);

    if ($requiredStep > 1 && $currentStep < $requiredStep - 1) {
        setFlash('warning', 'Please complete the previous checkout step first.');
        header('Location: ' . BASE_URL . '/checkout_cart.php');
        exit();
    }

    if ($requiredStep === 3 && empty($checkout['shipping'])) {
        setFlash('warning', 'Please complete the shipping step first.');
        header('Location: ' . BASE_URL . '/checkout_shipping.php');
        exit();
    }

    if ($requiredStep === 4 && empty($checkout['payment'])) {
        setFlash('warning', 'Please complete the payment step first.');
        header('Location: ' . BASE_URL . '/checkout_payment.php');
        exit();
    }
}

/**
 * Apply a promo code (LAPIFY50 = 50% off subtotal, capped at ₹10,000).
 */
function applyPromoCode(string $code, float $subtotal): array {
    $code = strtoupper(trim($code));
    $validCodes = [
        'LAPIFY50' => ['type' => 'percent', 'value' => 50, 'cap' => 10000],
        'LAPIFY10' => ['type' => 'percent', 'value' => 10, 'cap' => 5000],
        'SAVE500'  => ['type' => 'flat', 'value' => 500, 'cap' => null],
    ];

    if (!isset($validCodes[$code])) {
        return ['success' => false, 'message' => 'Invalid promo code. Please try LAPIFY50.'];
    }

    $rule = $validCodes[$code];
    if ($rule['type'] === 'percent') {
        $discount = $subtotal * ($rule['value'] / 100);
        if ($rule['cap'] !== null) {
            $discount = min($discount, (float)$rule['cap']);
        }
    } else {
        $discount = (float)$rule['value'];
    }

    $discount = min($discount, $subtotal); // never discount below zero

    return [
        'success'  => true,
        'message'  => 'Promo code applied! You saved ' . formatPrice($discount) . '.',
        'discount' => $discount,
        'code'     => $code,
    ];
}

/**
 * Get the shipping cost for a given method in INR.
 */
function getShippingCost(string $method): float {
    return $method === 'express' ? 199.0 : 0.0;
}

/**
 * Get a human-readable shipping method label.
 */
function getShippingMethodLabel(string $method): string {
    return $method === 'express' ? 'Express Shipping (1–2 business days)' : 'Standard Shipping (3–5 business days)';
}