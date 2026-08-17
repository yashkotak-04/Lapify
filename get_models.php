<?php
// get_models.php - AJAX endpoint that returns JSON list of models for a given brand.
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

// Only allow GET requests (this endpoint is read-only).
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed. Use GET.']);
    exit;
}

// Validate brand_id.
$brandId = intval($_GET['brand_id'] ?? 0);
if ($brandId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'A valid brand_id is required.']);
    exit;
}

try {
    $pdo = getPdoConnection();

    // Verify the brand actually exists and is active.
    $brandStmt = $pdo->prepare("SELECT id FROM brands WHERE id = ? AND status = 'active'");
    $brandStmt->execute([$brandId]);
    if (!$brandStmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Brand not found or inactive.']);
        exit;
    }

    // Fetch all models that belong to this brand, ordered by year then name.
    $stmt = $pdo->prepare("
        SELECT id, model_name, year
        FROM brand_models
        WHERE brand_id = ?
        ORDER BY year DESC, model_name ASC
    ");
    $stmt->execute([$brandId]);
    $models = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'brand_id' => $brandId,
        'count' => count($models),
        'models' => $models,
    ]);
} catch (Throwable $e) {
    error_log('get_models.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error while fetching models.']);
}