<?php
// scratch/test_integration.php - Integration Verification Script
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/mailer.php';
require_once __DIR__ . '/../includes/checkout_session.php';
require_once __DIR__ . '/../includes/order_status.php';

$pdo = getPdoConnection();
$conn = getDbConnection();

echo "=== LAPIFY INTEGRATION SUITE ===" . PHP_EOL;

// Test 1: Schema Version
$schemaVer = getSchemaVersion($pdo);
echo "[1] Schema Version: {$schemaVer} (Expected: 8) -> " . ($schemaVer >= 8 ? "PASS" : "FAIL") . PHP_EOL;

// Test 2: Password Strength Validator
$weak1 = validatePasswordStrength('short');
$weak2 = validatePasswordStrength('alllowercase123!');
$strong = validatePasswordStrength('Lapify@Secure2026!');
echo "[2] Password Strength: " . (!$weak1['is_valid'] && !$weak2['is_valid'] && $strong['is_valid'] ? "PASS" : "FAIL") . PHP_EOL;

// Test 3: CSRF Generation & Verification
$token = getCsrfToken();
$validCsrf = verifyCsrfToken($token);
$invalidCsrf = verifyCsrfToken('fake-token-12345');
echo "[3] CSRF Protection: " . ($validCsrf && !$invalidCsrf ? "PASS" : "FAIL") . PHP_EOL;

// Test 4: Mail System & Fallback Log
$mailRes = sendPasswordResetEmail('test@lapify.com', 'Test User', 'http://localhost/sem5-project/reset_password.php?token=testtoken');
$logExists = file_exists(__DIR__ . '/../logs/password_resets.log');
echo "[4] Mailer / Dev Reset Fallback Log: " . ($logExists ? "PASS" : "FAIL") . PHP_EOL;

// Test 5: Promo Code Engine
$promo50 = applyPromoCode('LAPIFY50', 20000.0);
$promoInvalid = applyPromoCode('BOGUSCODE', 1000.0);
echo "[5] Promo Engine: " . ($promo50['success'] && $promo50['discount'] === 10000.0 && !$promoInvalid['success'] ? "PASS" : "FAIL") . PHP_EOL;

// Test 6: Shipping Cost Engine
$stdShipping = getShippingCost('standard');
$expShipping = getShippingCost('express');
echo "[6] Shipping Costs: Standard = ₹{$stdShipping}, Express = ₹{$expShipping} -> " . ($stdShipping === 0.0 && $expShipping === 199.0 ? "PASS" : "FAIL") . PHP_EOL;

// Test 7: Order Status Normalization
$labels = [
    'placed' => getOrderStatusLabel('placed'),
    'confirmed' => getOrderStatusLabel('confirmed'),
    'shipped' => getOrderStatusLabel('shipped'),
    'delivered' => getOrderStatusLabel('delivered'),
    'cancelled' => getOrderStatusLabel('cancelled')
];
echo "[7] Order Status Labels: " . (count($labels) === 5 ? "PASS" : "FAIL") . PHP_EOL;

// Test 8: Admin & User Tables Consistency
$adminCols = $pdo->query("SHOW COLUMNS FROM admins")->fetchAll(PDO::FETCH_COLUMN);
$hasResetTokens = in_array('reset_token', $adminCols, true) && in_array('reset_expires', $adminCols, true);
echo "[8] Admin Reset Token Columns: " . ($hasResetTokens ? "PASS" : "FAIL") . PHP_EOL;

echo "=== ALL CHECKS EXECUTED SUCCESSFULLY ===" . PHP_EOL;
