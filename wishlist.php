<?php
// wishlist.php - User Wishlist Management & AJAX Endpoint
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$conn = getDbConnection();
$current_user = getCurrentUser();

// Handle AJAX POST toggle requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle') {
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

    if ($laptop_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid laptop ID']);
        exit();
    }

    // Reject if user owns this listing or laptop not found
    $owner_stmt = mysqli_prepare($conn, "SELECT user_id FROM laptops WHERE id = ?");
    mysqli_stmt_bind_param($owner_stmt, "i", $laptop_id);
    mysqli_stmt_execute($owner_stmt);
    $owner_res = mysqli_stmt_get_result($owner_stmt);
    $owner_row = mysqli_fetch_assoc($owner_res);
    mysqli_stmt_close($owner_stmt);

    if (!$owner_row) {
        echo json_encode(['status' => 'error', 'message' => 'Laptop listing not found.']);
        exit();
    }

    if ((int)$owner_row['user_id'] === (int)$user_id) {
        echo json_encode(['status' => 'error', 'message' => "You can't wishlist your own listing."]);
        exit();
    }

    // Check if already wishlisted
    if (isWishlisted($conn, $user_id, $laptop_id)) {
        // Remove from wishlist
        $stmt = mysqli_prepare($conn, "DELETE FROM wishlist WHERE user_id = ? AND laptop_id = ?");
        mysqli_stmt_bind_param($stmt, "ii", $user_id, $laptop_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        echo json_encode(['status' => 'removed', 'message' => 'Item removed from wishlist', 'wishlist_count' => getWishlistCount($conn, $user_id)]);
        exit();
    } else {
        // Add to wishlist
        $stmt = mysqli_prepare($conn, "INSERT IGNORE INTO wishlist (user_id, laptop_id) VALUES (?, ?)");
        mysqli_stmt_bind_param($stmt, "ii", $user_id, $laptop_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        echo json_encode(['status' => 'added', 'message' => 'Item added to wishlist', 'wishlist_count' => getWishlistCount($conn, $user_id)]);
        exit();
    }
}

// Regular page rendering (Requires Login)
requireLogin();

// Handle GET remove
if (isset($_GET['action']) && $_GET['action'] === 'remove' && isset($_GET['id'])) {
    $laptop_id = intval($_GET['id']);
    $stmt = mysqli_prepare($conn, "DELETE FROM wishlist WHERE user_id = ? AND laptop_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $current_user['id'], $laptop_id);
    if (mysqli_stmt_execute($stmt)) {
        setFlash('info', 'Item removed from your wishlist.');
    }
    mysqli_stmt_close($stmt);
    header("Location: " . BASE_URL . "/wishlist.php");
    exit();
}

$page_title = "My Wishlist | Lapify";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

// Fetch all laptops in current user's wishlist
$query = "SELECT l.*, b.brand_name 
          FROM wishlist w
          JOIN laptops l ON w.laptop_id = l.id
          JOIN brands b ON l.brand_id = b.id
          WHERE w.user_id = ? AND l.status = 'approved'
          ORDER BY w.id DESC";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $current_user['id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<div class="container py-5">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h2 class="fw-bold mb-1"><i class="bi bi-heart-fill text-danger me-2"></i>My Saved Wishlist</h2>
            <p class="text-muted mb-0">Saved laptop listings you are watching</p>
        </div>
        <a href="buy.php" class="btn-lapify-view-more">
            <i class="bi bi-plus-lg"></i> <span>Browse More</span>
        </a>
    </div>

    <?php displayFlash(); ?>

    <?php if (mysqli_num_rows($result) > 0): ?>
        <div class="row g-4">
            <?php while ($laptop = mysqli_fetch_assoc($result)): 
                $img_src = getLaptopImageUrl($laptop) ?: 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?auto=format&fit=crop&w=600&q=80';
                $badge_class = $laptop['type'] === 'New' ? 'badge-type-new' : 'badge-type-old';
                $is_in_cart = isInCart($conn, $current_user['id'], $laptop['id']);
            ?>
                <div class="col-12 col-sm-6 col-lg-4 col-xl-3 wishlist-card-col">
                    <div class="card card-laptop h-100">
                        <div class="laptop-img-wrapper">
                            <img src="<?= escape($img_src) ?>" alt="<?= escape($laptop['model']) ?>" onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1517336714731-489689fd1ca8?auto=format&fit=crop&w=600&q=80';">
                            <span class="badge <?= $badge_class ?> position-absolute top-0 start-0 m-3 shadow-sm">
                                <?= escape($laptop['type']) ?>
                            </span>
                            <button type="button" class="btn-wishlist btn-wishlist-toggle active" data-laptop-id="<?= $laptop['id'] ?>" title="Remove from wishlist">
                                <i class="bi bi-heart-fill text-danger"></i>
                            </button>
                        </div>
                        <div class="card-body d-flex flex-column justify-content-between p-3">
                            <div>
                                <div class="text-uppercase small text-muted font-weight-bold tracking-wide mb-1">
                                    <?= escape($laptop['brand_name']) ?>
                                </div>
                                <h5 class="card-title fw-bold text-truncate mb-2" title="<?= escape($laptop['model']) ?>">
                                    <?= escape($laptop['model']) ?>
                                </h5>
                                <div class="small text-muted mb-3 d-flex flex-wrap gap-2">
                                    <span><i class="bi bi-cpu me-1"></i><?= escape($laptop['processor'] ?? 'N/A') ?></span>
                                    <span>&bull;</span>
                                    <span><i class="bi bi-memory me-1"></i><?= escape($laptop['ram'] ?? 'N/A') ?></span>
                                </div>
                            </div>
                            <div class="d-flex align-items-center justify-content-between pt-2 border-top gap-1">
                                <div class="fw-bold text-primary">
                                    <?= formatPrice($laptop['price']) ?>
                                </div>
                                <div class="d-flex gap-1">
                                    <button type="button" class="btn <?= $is_in_cart ? 'btn-success' : 'btn-soft-primary' ?> btn-sm rounded-pill px-2.5 btn-cart-toggle" data-laptop-id="<?= $laptop['id'] ?>" title="<?= $is_in_cart ? 'In Cart' : 'Add to Cart' ?>">
                                        <i class="bi <?= $is_in_cart ? 'bi-cart-check-fill' : 'bi-cart-plus' ?>"></i>
                                    </button>
                                    <a href="laptop-details.php?id=<?= $laptop['id'] ?>" class="btn btn-primary btn-sm rounded-pill px-3">
                                        Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="text-center py-5 background-white rounded-4 shadow-sm border">
            <div class="fs-1 text-muted mb-3"><i class="bi bi-heart text-danger"></i></div>
            <h4 class="fw-bold">Your Wishlist is Empty</h4>
            <p class="text-muted">Click the heart icon on any laptop listing to save it here for later.</p>
            <a href="buy.php" class="btn btn-primary rounded-pill px-4">Explore Marketplace</a>
        </div>
    <?php endif; ?>
</div>

<?php 
mysqli_stmt_close($stmt);
require_once __DIR__ . '/includes/footer.php'; 
?>
