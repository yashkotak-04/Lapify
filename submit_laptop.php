<?php
// submit_laptop.php - Handles form submission, runs all validations, inserts into laptops table.
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

// Must be logged in to submit a laptop listing.
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'You must be logged in to list a laptop.', 'field' => 'form']);
    exit;
}

// Only POST is allowed.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed. Use POST.', 'field' => 'form']);
    exit;
}

// Verify CSRF token.
if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    http_response_code(419);
    echo json_encode(['success' => false, 'message' => 'Session expired or invalid security token. Please refresh the page and try again.', 'field' => 'form']);
    exit;
}

// Check-only mode: used by the client-side duplicate validation before submit.
// Runs all brand/model/condition validations + duplicate check, but does NOT insert.
$checkOnly = (($_POST['check_only'] ?? '') === '1');

$user = getCurrentUser();
$userId = (int)($user['id'] ?? 0);

$errors = [];

// --- Helper validation functions ---
function validateRequiredString($key, $label, $maxLen = 255) {
    $value = trim((string)($_POST[$key] ?? ''));
    if ($value === '') {
        return ['', "{$label} is required."];
    }
    if (mb_strlen($value) > $maxLen) {
        return ['', "{$label} must be {$maxLen} characters or fewer."];
    }
    return [$value, null];
}

// --- Brand ---
[$brandId, $brandErr] = validateRequiredString('brand_id', 'Brand');
$brandId = $brandId !== '' ? (int)$brandId : 0;
if ($brandErr === null && $brandId <= 0) {
    $brandErr = 'Please select a valid laptop brand.';
}
if ($brandErr !== null) {
    $errors['brand_id'] = $brandErr;
}

// --- Model (from dependent dropdown) ---
[$modelName, $modelErr] = validateRequiredString('model', 'Model', 100);
if ($modelErr !== null) {
    $errors['model'] = $modelErr;
}

// --- Condition (New / Old) ---
$condition = strtolower(trim((string)($_POST['condition_type'] ?? '')));
if (!in_array($condition, ['new', 'old'], true)) {
    $errors['condition_type'] = 'Please select a condition (New or Old).';
}

// Price/quantity are only required for actual submission (not check-only mode).
$price = 0;
$quantity = 1;
if (!$checkOnly) {
    $price = floatval($_POST['price'] ?? 0);
    if ($price <= 0) {
        $errors['price'] = 'Please enter a valid price greater than ₹0.';
    }

    $quantity = max(1, intval($_POST['quantity'] ?? 1));
    if ($quantity < 1) {
        $errors['quantity'] = 'Quantity must be at least 1.';
    }
}

// If we already have fatal validation errors, return early.
if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Please fix the highlighted fields and try again.', 'errors' => $errors, 'field' => 'form']);
    exit;
}

try {
    $pdo = getPdoConnection();

    // ---------------------------------------------------------
    // BRAND/MODEL CONSISTENCY VALIDATION:
    // Verify the submitted brand actually exists and is active.
    // ---------------------------------------------------------
    $brandStmt = $pdo->prepare("SELECT id FROM brands WHERE id = ? AND status = 'active'");
    $brandStmt->execute([$brandId]);
    if (!$brandStmt->fetch()) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'The selected brand does not exist or is inactive.', 'field' => 'brand_id']);
        exit;
    }

    // ---------------------------------------------------------
    // BRAND/MODEL CONSISTENCY VALIDATION:
    // Verify the submitted model actually belongs to this brand.
    // ---------------------------------------------------------
    $modelStmt = $pdo->prepare("SELECT id FROM brand_models WHERE brand_id = ? AND LOWER(model_name) = LOWER(?)");
    $modelStmt->execute([$brandId, $modelName]);
    if (!$modelStmt->fetch()) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'The selected model does not belong to the selected brand. Please choose a valid model.', 'field' => 'model']);
        exit;
    }

    // ---------------------------------------------------------
    // DUPLICATE MODEL VALIDATION:
    // If condition = "new", block if an existing New listing
    // already exists for the same brand + model.
    // (Old listings allow duplicates.)
    // ---------------------------------------------------------
    if ($condition === 'new') {
        $dupStmt = $pdo->prepare("
            SELECT id FROM laptops
            WHERE brand_id = ? AND model = ? AND condition_type = 'new'
            LIMIT 1
        ");
        $dupStmt->execute([$brandId, $modelName]);
        if ($dupStmt->fetch()) {
            http_response_code(422);
            echo json_encode([
                'success' => false,
                'message' => 'This model already exists as New. Duplicate new listings are not allowed.',
                'field' => 'condition_type',
            ]);
            exit;
        }
    }

    // In check-only mode, return early with success (no insert).
    if ($checkOnly) {
        echo json_encode([
            'success' => true,
            'checked' => true,
            'duplicate' => false,
        ]);
        exit;
    }

    // ---------------------------------------------------------
    // INSERT INTO laptops
    // ---------------------------------------------------------
    $processor = sanitizeInput($_POST['processor'] ?? '');
    $ram       = sanitizeInput($_POST['ram'] ?? '');
    $storage   = sanitizeInput($_POST['storage'] ?? '');
    $desc      = sanitizeInput($_POST['description'] ?? '');
    $conditionLabel = $condition === 'new' ? 'Brand New' : 'Old';

    // If description was left blank, automatically generate a rich spec-based description
    if ($desc === '') {
        $brandRow = $pdo->prepare("SELECT brand_name FROM brands WHERE id = ?");
        $brandRow->execute([$brandId]);
        $brandData = $brandRow->fetch(PDO::FETCH_ASSOC);
        $bName = $brandData['brand_name'] ?? '';

        $pTitle = $bName;
        if ($modelName) {
            $pTitle = ($bName && stripos($modelName, $bName) === false) ? ($bName . ' ' . $modelName) : $modelName;
        }

        $desc = "🌟 " . $pTitle . "\n"
              . "✨ Condition: " . ($condition === 'new' ? 'Brand New (100% Unused / Sealed)' : 'Verified Pre-Owned (Good Working Condition)') . "\n"
              . "💰 Asking Price: ₹" . number_format($price, 2) . "\n\n"
              . "⚙️ Technical Specifications:\n"
              . "• Processor: " . ($processor !== '' ? $processor : 'Standard Multi-Core CPU') . "\n"
              . "• RAM: " . ($ram !== '' ? $ram : 'Standard High-Speed RAM') . "\n"
              . "• Storage: " . ($storage !== '' ? $storage : 'High-Speed SSD') . "\n\n"
              . "📋 Product Highlights:\n"
              . ($condition === 'new'
                  ? "• 100% genuine brand new unit with original packaging and power adapter.\n"
                  : "• Thoroughly tested and verified to be 100% operational with working charger.\n")
              . "• Verified listing on Lapify Marketplace with Buyer Protection.";
    }

    // Handle image upload (optional).
    $imageName = null;
    if (!empty($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $uploadError = '';
        $uploaded = uploadImage($_FILES['image'], LAPTOP_UPLOAD_DIR, $uploadError);
        if ($uploaded === false) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => $uploadError, 'field' => 'image']);
            exit;
        }
        $imageName = $uploaded;
    }

    // Build the SQL using only columns that exist (condition_type stores new/old,
    // the human-readable `condition` field stores the descriptive label).
    $sql = "INSERT INTO laptops
            (user_id, brand_id, type, condition_type, model, processor, ram, storage, `condition`, price, description, image, quantity, stock_quantity, status, approval_status)
            VALUES
            (:user_id, :brand_id, :type, :condition_type, :model, :processor, :ram, :storage, :condition, :price, :description, :image, :quantity, :stock_quantity, 'pending', 'pending')";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'user_id'        => $userId,
        'brand_id'       => $brandId,
        'type'           => $condition === 'new' ? 'New' : 'Old',
        'condition_type' => $condition,
        'model'          => $modelName,
        'processor'      => $processor !== '' ? $processor : null,
        'ram'            => $ram !== '' ? $ram : null,
        'storage'        => $storage !== '' ? $storage : null,
        'condition'      => $conditionLabel,
        'price'          => $price,
        'description'    => $desc !== '' ? $desc : null,
        'image'          => $imageName,
        'quantity'       => $quantity,
        'stock_quantity' => $quantity,
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Your listing has been submitted and is awaiting admin approval!',
    ]);
} catch (Throwable $e) {
    error_log('submit_laptop.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error while saving your listing. Please try again.', 'field' => 'form']);
}