<?php
// cart.php - Shopping Cart & Cart AJAX Endpoint
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$conn = getDbConnection();
$current_user = getCurrentUser();

// Handle AJAX POST cart toggle/add/remove requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    $csrf = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!verifyCsrfToken($csrf)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid or expired security token. Please refresh the page.']);
        exit();
    }

    if (!$current_user) {
        echo json_encode(['status' => 'unauthorized', 'message' => 'Login required']);
        exit();
    }

    $user_id = $current_user['id'];
    $laptop_id = intval($_POST['laptop_id'] ?? 0);
    $action = sanitizeInput($_POST['action']);

    if ($laptop_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid laptop ID']);
        exit();
    }

    // Check laptop availability & approval status
    $laptop_stmt = mysqli_prepare($conn, "SELECT user_id, status, approval_status, COALESCE(stock_quantity, quantity, 1) AS stock FROM laptops WHERE id = ?");
    mysqli_stmt_bind_param($laptop_stmt, "i", $laptop_id);
    mysqli_stmt_execute($laptop_stmt);
    $laptop_row = mysqli_fetch_assoc(mysqli_stmt_get_result($laptop_stmt));
    mysqli_stmt_close($laptop_stmt);

    if (!$laptop_row) {
        echo json_encode(['status' => 'error', 'message' => 'Laptop listing not found.']);
        exit();
    }

    if ((int)$laptop_row['user_id'] === (int)$user_id) {
        echo json_encode(['status' => 'error', 'message' => "You can't add your own listing to the cart."]);
        exit();
    }

    $isApproved = in_array(strtolower((string)$laptop_row['status']), ['approved', 'available'], true) || strtolower((string)$laptop_row['approval_status']) === 'approved';
    if (!$isApproved) {
        echo json_encode(['status' => 'error', 'message' => 'This laptop is pending review and not yet available for purchase.']);
        exit();
    }

    $stock = (int)$laptop_row['stock'];
    if ($stock <= 0 && $action !== 'remove') {
        echo json_encode(['status' => 'error', 'message' => 'This laptop is currently out of stock.']);
        exit();
    }

    if ($action === 'toggle' || $action === 'add') {

        if (isInCart($conn, $user_id, $laptop_id)) {
            if ($action === 'toggle') {
                // Remove from cart
                $stmt = mysqli_prepare($conn, "DELETE FROM cart WHERE user_id = ? AND laptop_id = ?");
                mysqli_stmt_bind_param($stmt, "ii", $user_id, $laptop_id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                
                $count = getCartCount($conn, $user_id);
                echo json_encode(['status' => 'removed', 'cart_count' => $count, 'message' => 'Item removed from cart']);
                exit();
            } else {
                $count = getCartCount($conn, $user_id);
                echo json_encode(['status' => 'exists', 'cart_count' => $count, 'message' => 'Item already in cart']);
                exit();
            }
        } else {
            // Add to cart
            $stmt = mysqli_prepare($conn, "INSERT IGNORE INTO cart (user_id, laptop_id) VALUES (?, ?)");
            mysqli_stmt_bind_param($stmt, "ii", $user_id, $laptop_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            $count = getCartCount($conn, $user_id);
            echo json_encode(['status' => 'added', 'cart_count' => $count, 'message' => 'Item added to cart']);
            exit();
        }
    } elseif ($action === 'remove') {
        $stmt = mysqli_prepare($conn, "DELETE FROM cart WHERE user_id = ? AND laptop_id = ?");
        mysqli_stmt_bind_param($stmt, "ii", $user_id, $laptop_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        $count = getCartCount($conn, $user_id);
        echo json_encode(['status' => 'removed', 'cart_count' => $count, 'message' => 'Item removed from cart']);
        exit();
    }
}

// Require login for page view
requireLogin();

// Handle GET remove item
if (isset($_GET['action']) && $_GET['action'] === 'remove' && isset($_GET['id'])) {
    $laptop_id = intval($_GET['id']);
    $stmt = mysqli_prepare($conn, "DELETE FROM cart WHERE user_id = ? AND laptop_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $current_user['id'], $laptop_id);
    if (mysqli_stmt_execute($stmt)) {
        setFlash('info', 'Item removed from your cart.');
    }
    mysqli_stmt_close($stmt);
    header("Location: " . BASE_URL . "/cart.php");
    exit();
}

$page_title = "My Cart | Lapify";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

// Fetch all laptops in cart for logged in user
$query = "SELECT l.*, b.brand_name, u.full_name AS seller_name 
          FROM cart c
          JOIN laptops l ON c.laptop_id = l.id
          JOIN brands b ON l.brand_id = b.id
          JOIN users u ON l.user_id = u.id
          WHERE c.user_id = ?
          ORDER BY c.id DESC";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $current_user['id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$cart_items = [];
$total_price = 0.0;
while ($item = mysqli_fetch_assoc($result)) {
    $cart_items[] = $item;
    $total_price += (float)$item['price'];
}
mysqli_stmt_close($stmt);
?>

<div class="container py-5">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h2 class="fw-bold mb-1"><i class="bi bi-cart3 text-primary me-2"></i>Shopping Cart</h2>
            <p class="text-muted mb-0">Review items in your cart before sending purchase requests</p>
        </div>
        <a href="buy.php" class="btn-lapify-view-more">
            <i class="bi bi-plus-lg"></i> <span>Continue Shopping</span>
        </a>
    </div>

    <?php displayFlash(); ?>

    <?php if (count($cart_items) > 0): ?>
        <div class="row g-4">
            <!-- Cart Items List -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light text-muted small text-uppercase font-weight-bold">
                                    <tr>
                                        <th class="ps-4">Laptop Item</th>
                                        <th>Seller</th>
                                        <th>Price</th>
                                        <th class="pe-4 text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cart_items as $item): 
                                        $img_src = getLaptopImageUrl($item) ?: 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?auto=format&fit=crop&w=200&q=80';
                                    ?>
                                        <tr class="cart-item-row">
                                            <td class="ps-4">
                                                <div class="d-flex align-items-center gap-3">
                                                    <img src="<?= escape($img_src) ?>" alt="<?= escape($item['model']) ?>" class="rounded-3" style="width: 64px; height: 48px; object-fit: cover;">
                                                    <div>
                                                        <a href="laptop-details.php?id=<?= $item['id'] ?>" class="fw-bold text-body text-decoration-none">
                                                            <?= escape($item['model']) ?>
                                                        </a>
                                                        <div class="small text-muted"><?= escape($item['brand_name']) ?> &bull; <?= escape($item['type']) ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="small text-secondary">
                                                <?= escape($item['seller_name']) ?>
                                            </td>
                                            <td class="fw-bold text-primary fs-6">
                                                <?= formatPrice($item['price']) ?>
                                            </td>
                                            <td class="pe-4 text-end">
                                                <button type="button" class="btn btn-sm btn-outline-danger btn-cart-toggle" data-laptop-id="<?= $item['id'] ?>" title="Remove item">
                                                    <i class="bi bi-trash"></i> Remove
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Summary Card -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 p-4 sticky-top" style="top: 90px;">
                    <h5 class="fw-bold mb-3">Order Request Summary</h5>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Total Items:</span>
                        <span class="fw-bold"><?= count($cart_items) ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted">Subtotal Price:</span>
                        <span class="fw-bold text-primary fs-5"><?= formatPrice($total_price) ?></span>
                    </div>
                    <hr class="my-3">
                    <p class="small text-muted mb-3">
                        <i class="bi bi-info-circle text-primary me-1"></i>
                        Checkout sends purchase requests directly to sellers. Payment is arranged upon delivery or meetup.
                    </p>
                    <a href="checkout_cart.php" class="btn btn-primary w-100 rounded-3 py-2.5 font-weight-bold fs-6">
                        <i class="bi bi-check2-circle me-2"></i>Proceed to Checkout
                    </a>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="text-center py-5 background-white rounded-4 shadow-sm border">
            <div class="fs-1 text-muted mb-3"><i class="bi bi-cart-x"></i></div>
            <h4 class="fw-bold">Your cart is empty</h4>
            <p class="text-muted">Explore laptop listings and click "Add to Cart" to add items here.</p>
            <a href="buy.php" class="btn btn-primary rounded-pill px-4">Browse Laptop Catalog</a>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
