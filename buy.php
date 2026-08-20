<?php
// buy.php - Catalog & Filter Page
$page_title = "Browse Laptops | Lapify Marketplace";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

$conn = getDbConnection();
$current_user = getCurrentUser();
$user_id = $current_user['id'] ?? ($_SESSION['user_id'] ?? null);

// Read GET Filter parameters
$search = sanitizeInput($_GET['search'] ?? '');
$raw_brand = sanitizeInput($_GET['brand'] ?? '');
$brand_filter = is_numeric($raw_brand) ? intval($raw_brand) : 0;
$type_filter = sanitizeInput($_GET['type'] ?? '');
$condition_filter = sanitizeInput($_GET['condition'] ?? '');
$processor_filter = sanitizeInput($_GET['processor'] ?? '');
$price_min = isset($_GET['price_min']) && $_GET['price_min'] !== '' ? floatval($_GET['price_min']) : null;
$price_max = isset($_GET['price_max']) && $_GET['price_max'] !== '' ? floatval($_GET['price_max']) : null;
$sort_by = sanitizeInput($_GET['sort'] ?? 'newest');

// Pagination
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 12;
$offset = ($page - 1) * $limit;

$where_clauses = ["(l.status = 'approved' OR l.approval_status = 'approved' OR l.status = 'Available') AND l.status != 'pending' AND l.approval_status != 'pending' AND l.status != 'rejected'"];
$params = [];
$param_types = "";

// Exclude current logged-in user's own listings from the buy catalog (other users can still see it)
if ($user_id !== null && (int)$user_id > 0) {
    $where_clauses[] = "(l.user_id IS NULL OR l.user_id != ?)";
    $params[] = (int)$user_id;
    $param_types .= "i";
}

if (!empty($search)) {
    $where_clauses[] = "(l.model LIKE ? OR l.description LIKE ? OR l.processor LIKE ? OR b.brand_name LIKE ?)";
    $search_param = "%{$search}%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= "ssss";
}

if ($brand_filter > 0) {
    $where_clauses[] = "l.brand_id = ?";
    $params[] = $brand_filter;
    $param_types .= "i";
} elseif (!empty($raw_brand)) {
    $where_clauses[] = "(LOWER(b.brand_name) = LOWER(?) OR l.brand_id = (SELECT id FROM brands WHERE LOWER(brand_name) = LOWER(?) LIMIT 1))";
    $params[] = $raw_brand;
    $params[] = $raw_brand;
    $param_types .= "ss";
}

if (!empty($type_filter) && in_array($type_filter, ['New', 'Old'])) {
    $where_clauses[] = "l.type = ?";
    $params[] = $type_filter;
    $param_types .= "s";
}

if (!empty($condition_filter)) {
    $where_clauses[] = "l.condition = ?";
    $params[] = $condition_filter;
    $param_types .= "s";
}

if (!empty($processor_filter)) {
    $where_clauses[] = "l.processor LIKE ?";
    $params[] = "%{$processor_filter}%";
    $param_types .= "s";
}

if ($price_min !== null) {
    $where_clauses[] = "l.price >= ?";
    $params[] = $price_min;
    $param_types .= "d";
}

if ($price_max !== null) {
    $where_clauses[] = "l.price <= ?";
    $params[] = $price_max;
    $param_types .= "d";
}

$where_sql = implode(" AND ", $where_clauses);

// Sorting
$order_sql = "ORDER BY l.id DESC";
if ($sort_by === 'price_asc') {
    $order_sql = "ORDER BY l.price ASC";
} elseif ($sort_by === 'price_desc') {
    $order_sql = "ORDER BY l.price DESC";
}

// 1. Count total matching rows
$count_sql = "SELECT COUNT(*) FROM laptops l JOIN brands b ON l.brand_id = b.id WHERE {$where_sql}";
$count_stmt = mysqli_prepare($conn, $count_sql);
if (!empty($param_types)) {
    mysqli_stmt_bind_param($count_stmt, $param_types, ...$params);
}
mysqli_stmt_execute($count_stmt);
mysqli_stmt_bind_result($count_stmt, $total_items);
mysqli_stmt_fetch($count_stmt);
mysqli_stmt_close($count_stmt);

$total_items = (int)($total_items ?? 0);
$total_pages = ceil($total_items / $limit);

// 2. Fetch Paginated Results
$query_sql = "SELECT l.*, b.brand_name 
              FROM laptops l 
              JOIN brands b ON l.brand_id = b.id 
              WHERE {$where_sql} 
              {$order_sql} 
              LIMIT ? OFFSET ?";

$query_params = $params;
$query_params[] = $limit;
$query_params[] = $offset;
$query_param_types = $param_types . "ii";

$stmt = mysqli_prepare($conn, $query_sql);
mysqli_stmt_bind_param($stmt, $query_param_types, ...$query_params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Fetch brands for dropdown
$brands_res = mysqli_query($conn, "SELECT * FROM brands WHERE status = 'active' ORDER BY brand_name ASC");
$brands = mysqli_fetch_all($brands_res, MYSQLI_ASSOC);
mysqli_free_result($brands_res);
?>

<div class="bg-white border-bottom py-4 mb-4">
    <div class="container">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
            <div>
                <h2 class="fw-bold mb-1">Laptop Catalog</h2>
                <p class="text-muted mb-0">Explore <?= number_format($total_items) ?> verified laptop listings</p>
            </div>
            <!-- Sort Control -->
            <form action="buy.php" method="GET" class="d-flex align-items-center gap-2">
                <!-- Retain current filters in GET -->
                <?php if (!empty($search)): ?><input type="hidden" name="search" value="<?= escape($search) ?>"><?php endif; ?>
                <?php if ($brand_filter > 0): ?><input type="hidden" name="brand" value="<?= $brand_filter ?>"><?php endif; ?>
                <?php if (!empty($type_filter)): ?><input type="hidden" name="type" value="<?= escape($type_filter) ?>"><?php endif; ?>
                <?php if (!empty($condition_filter)): ?><input type="hidden" name="condition" value="<?= escape($condition_filter) ?>"><?php endif; ?>
                <?php if ($price_min !== null): ?><input type="hidden" name="price_min" value="<?= $price_min ?>"><?php endif; ?>
                <?php if ($price_max !== null): ?><input type="hidden" name="price_max" value="<?= $price_max ?>"><?php endif; ?>
                
                <label for="sort" class="text-nowrap small font-weight-medium text-muted">Sort By:</label>
                <select name="sort" id="sort" class="form-select form-select-sm border-secondary-subtle rounded-3" onchange="this.form.submit()">
                    <option value="newest" <?= $sort_by === 'newest' ? 'selected' : '' ?>>Newest First</option>
                    <option value="price_asc" <?= $sort_by === 'price_asc' ? 'selected' : '' ?>>Price: Low to High</option>
                    <option value="price_desc" <?= $sort_by === 'price_desc' ? 'selected' : '' ?>>Price: High to Low</option>
                </select>
            </form>
        </div>
    </div>
</div>

<div class="container pb-5">
    <?php displayFlash(); ?>

    <div class="row g-4">
        <!-- Sidebar Filters -->
        <div class="col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-4 sticky-top" style="top: 90px; z-index: 10;">
                <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                    <h5 class="fw-bold mb-0"><i class="bi bi-funnel-fill text-primary me-2"></i>Filters</h5>
                    <a href="buy.php" class="text-danger small font-weight-bold text-decoration-none">Reset All</a>
                </div>

                <form action="buy.php" method="GET">
                    <!-- Keyword Search -->
                    <div class="mb-3">
                        <label class="form-label small font-weight-bold">Keyword Search</label>
                        <div class="input-group input-group-sm">
                            <input type="text" name="search" class="form-control" value="<?= escape($search) ?>" placeholder="Model, CPU, specs...">
                            <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i></button>
                        </div>
                    </div>

                    <!-- Brand Filter -->
                    <div class="mb-3">
                        <label class="form-label small font-weight-bold">Brand</label>
                        <select name="brand" class="form-select form-select-sm">
                            <option value="0">All Brands</option>
                            <?php foreach ($brands as $b): ?>
                                <option value="<?= $b['id'] ?>" <?= $brand_filter == $b['id'] ? 'selected' : '' ?>>
                                    <?= escape($b['brand_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Listing Type -->
                    <div class="mb-3">
                        <label class="form-label small font-weight-bold">Listing Type</label>
                        <select name="type" class="form-select form-select-sm">
                            <option value="">All Types (New & Used)</option>
                            <option value="New" <?= $type_filter === 'New' ? 'selected' : '' ?>>Brand New Only</option>
                            <option value="Old" <?= $type_filter === 'Old' ? 'selected' : '' ?>>Pre-Owned (Old) Only</option>
                        </select>
                    </div>

                    <!-- Condition -->
                    <div class="mb-3">
                        <label class="form-label small font-weight-bold">Condition</label>
                        <select name="condition" class="form-select form-select-sm">
                            <option value="">Any Condition</option>
                            <option value="Brand New" <?= $condition_filter === 'Brand New' ? 'selected' : '' ?>>Brand New</option>
                            <option value="Like New" <?= $condition_filter === 'Like New' ? 'selected' : '' ?>>Like New</option>
                            <option value="Good" <?= $condition_filter === 'Good' ? 'selected' : '' ?>>Good</option>
                            <option value="Fair" <?= $condition_filter === 'Fair' ? 'selected' : '' ?>>Fair</option>
                        </select>
                    </div>

                    <!-- Price Range -->
                    <div class="mb-4">
                        <label class="form-label small font-weight-bold">Price Range (₹)</label>
                        <div class="row g-2">
                            <div class="col-6">
                                <input type="number" name="price_min" class="form-control form-control-sm" placeholder="Min" value="<?= $price_min !== null ? $price_min : '' ?>" min="0">
                            </div>
                            <div class="col-6">
                                <input type="number" name="price_max" class="form-control form-control-sm" placeholder="Max" value="<?= $price_max !== null ? $price_max : '' ?>" min="0">
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 rounded-3 font-weight-bold btn-sm py-2">
                        Apply Filters
                    </button>
                </form>
            </div>
        </div>

        <!-- Laptop Cards Grid -->
        <div class="col-lg-9">
            <?php if (mysqli_num_rows($result) > 0): ?>
                <div class="row g-4 mb-4">
                    <?php while ($laptop = mysqli_fetch_assoc($result)): 
                        $img_src = getLaptopImageUrl($laptop) ?: 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?auto=format&fit=crop&w=600&q=80';
                        $is_own_listing = isOwnListing($user_id, $laptop['user_id']);
                        $is_wished = !$is_own_listing && isWishlisted($conn, $user_id, $laptop['id']);
                        $is_in_cart = isInCart($conn, $user_id, $laptop['id']);
                        $badge_class = $laptop['type'] === 'New' ? 'badge-type-new' : 'badge-type-old';
                    ?>
                        <div class="col-12 product-card-col mb-3">
                            <div class="card card-laptop card-laptop-horizontal shadow-sm border-0 rounded-4 overflow-hidden">
                                <div class="row g-0 align-items-stretch">
                                    <div class="col-md-4 col-lg-3">
                                        <div class="laptop-img-wrapper h-100 position-relative">
                                            <img src="<?= escape($img_src) ?>" alt="<?= escape($laptop['model']) ?>" onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1517336714731-489689fd1ca8?auto=format&fit=crop&w=600&q=80';">
                                            <span class="badge <?= $badge_class ?> position-absolute top-0 start-0 m-3 shadow-sm">
                                                <?= escape($laptop['type']) ?>
                                            </span>
                                            <?php if ($is_own_listing): ?>
                                                <button type="button" class="btn-wishlist btn-wishlist-toggle" data-laptop-id="<?= $laptop['id'] ?>" title="You can't wishlist your own listing" disabled style="pointer-events: none; opacity: 0.55; cursor: not-allowed;">
                                                    <i class="bi bi-heart"></i>
                                                </button>
                                            <?php else: ?>
                                                <button type="button" class="btn-wishlist btn-wishlist-toggle <?= $is_wished ? 'active' : '' ?>" data-laptop-id="<?= $laptop['id'] ?>" title="Save to wishlist">
                                                    <i class="bi <?= $is_wished ? 'bi-heart-fill text-danger' : 'bi-heart' ?>"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-md-8 col-lg-9">
                                        <div class="card-body p-3 p-md-3.5 h-100 d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
                                            <div class="horizontal-card-info flex-grow-1">
                                                <div class="text-uppercase small text-muted font-weight-bold tracking-wide mb-1">
                                                    <?= escape($laptop['brand_name']) ?>
                                                </div>
                                                <h4 class="card-title fs-5 fw-bold mb-2 text-truncate" style="max-width: 500px;" title="<?= escape($laptop['model']) ?>">
                                                    <?= escape($laptop['model']) ?>
                                                </h4>
                                                <div class="small text-muted mb-2 d-flex flex-wrap gap-2 align-items-center">
                                                    <span class="badge bg-light text-dark border px-2.5 py-1"><i class="bi bi-cpu text-primary me-1"></i><?= escape($laptop['processor'] ?? 'N/A') ?></span>
                                                    <span class="badge bg-light text-dark border px-2.5 py-1"><i class="bi bi-memory text-primary me-1"></i><?= escape($laptop['ram'] ?? 'N/A') ?></span>
                                                    <?php if (!empty($laptop['storage'])): ?>
                                                        <span class="badge bg-light text-dark border px-2.5 py-1"><i class="bi bi-hdd-rack-fill text-primary me-1"></i><?= escape($laptop['storage']) ?></span>
                                                    <?php endif; ?>
                                                    <?php if (!empty($laptop['condition_category'])): ?>
                                                        <span class="badge bg-secondary-subtle text-secondary border px-2.5 py-1"><?= escape($laptop['condition_category']) ?></span>
                                                    <?php endif; ?>
                                                </div>
                                                <div>
                                                    <?php if ($is_own_listing): ?>
                                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1 me-2"><i class="bi bi-person-check-fill me-1"></i>Your Listing</span>
                                                    <?php endif; ?>
                                                    <?php if ((int)($laptop['quantity'] ?? 1) > 0): ?>
                                                        <span class="stock-badge">✓ In Stock (<?= (int)($laptop['quantity'] ?? 1) ?> available)</span>
                                                    <?php else: ?>
                                                        <span class="stock-badge out-of-stock">✕ Out of Stock</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="horizontal-card-actions text-lg-end border-top border-lg-0 pt-2 pt-lg-0 min-w-200">
                                                <div class="fs-3 fw-bold text-primary mb-2">
                                                    <?= formatPrice($laptop['price']) ?>
                                                </div>
                                                <div class="product-action-group justify-content-lg-end">
                                                    <?php if ($is_own_listing): ?>
                                                        <a href="sell.php?edit_id=<?= $laptop['id'] ?>" class="btn btn-warning btn-product-buy text-dark fw-bold px-4 btn-edit-listing">
                                                            <i class="bi bi-pencil-square me-1.5"></i>Edit Listing
                                                        </a>
                                                    <?php elseif ($user_id): ?>
                                                        <button type="button" class="btn btn-soft-primary btn-product-icon btn-cart-toggle <?= $is_in_cart ? 'btn-success' : '' ?>" data-laptop-id="<?= $laptop['id'] ?>" title="<?= $is_in_cart ? 'In Cart' : 'Add to Cart' ?>">
                                                            <i class="bi <?= $is_in_cart ? 'bi-cart-check-fill' : 'bi-cart-plus' ?>"></i>
                                                        </button>
                                                        <a href="checkout_cart.php?direct_laptop_id=<?= $laptop['id'] ?>" class="btn btn-primary btn-product-buy <?= ((int)($laptop['quantity'] ?? 1) === 0) ? 'disabled' : '' ?>">
                                                            <i class="bi bi-lightning-fill me-1"></i>Buy Now
                                                        </a>
                                                    <?php else: ?>
                                                        <a href="login.php" class="btn btn-soft-primary btn-product-icon" title="Log in to add to cart">
                                                            <i class="bi bi-cart-plus"></i>
                                                        </a>
                                                        <a href="login.php" class="btn btn-primary btn-product-buy">
                                                            <i class="bi bi-lightning-fill me-1"></i>Buy Now
                                                        </a>
                                                    <?php endif; ?>
                                                    <a href="laptop-details.php?id=<?= $laptop['id'] ?>" class="btn btn-outline-primary btn-product-details">
                                                        Details
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>

                <!-- Pagination Nav -->
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Page navigation" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-item-link page-link" href="buy.php?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">Previous</a>
                                </li>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                    <a class="page-link" href="buy.php?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="buy.php?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">Next</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>

            <?php else: ?>
                <div class="text-center py-5 background-white rounded-4 shadow-sm border">
                    <div class="fs-1 text-muted mb-3"><i class="bi bi-search"></i></div>
                    <h4 class="fw-bold">No Laptops Found</h4>
                    <p class="text-muted">No laptop listings matched your current filter criteria.</p>
                    <a href="buy.php" class="btn btn-primary rounded-pill px-4">Reset All Filters</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php 
mysqli_stmt_close($stmt);
require_once __DIR__ . '/includes/footer.php'; 
?>
