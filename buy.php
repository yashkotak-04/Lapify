<?php
// buy.php - Catalog & Filter Page
$page_title = "Browse Laptops | Lapify Marketplace";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

$conn = getDbConnection();
$current_user = getCurrentUser();
$user_id = $current_user['id'] ?? ($_SESSION['user_id'] ?? null);

// Helper function to sanitize and extract clean numeric price
function parsePriceInput($input) {
    if ($input === null || $input === '') {
        return null;
    }
    // Remove commas, currency symbols, spaces, and any non-numeric/non-dot characters
    $cleaned = preg_replace('/[^\d\.]/', '', (string)$input);
    if ($cleaned === '' || !is_numeric($cleaned)) {
        return null;
    }
    $val = (float)$cleaned;
    return $val >= 0 ? $val : null;
}

// Read GET Filter parameters
$search = sanitizeInput($_GET['search'] ?? '');
$raw_brand = sanitizeInput($_GET['brand'] ?? '');
$brand_filter = is_numeric($raw_brand) ? intval($raw_brand) : 0;
$type_filter = sanitizeInput($_GET['type'] ?? '');
$condition_filter = sanitizeInput($_GET['condition'] ?? '');
$processor_filter = sanitizeInput($_GET['processor'] ?? '');

$raw_price_min = $_GET['price_min'] ?? null;
$raw_price_max = $_GET['price_max'] ?? null;

$price_min = parsePriceInput($raw_price_min);
$price_max = parsePriceInput($raw_price_max);

// Auto-correct inverted price bounds if user puts min > max
if ($price_min !== null && $price_max !== null && $price_min > $price_max) {
    $temp = $price_min;
    $price_min = $price_max;
    $price_max = $temp;
}

$sort_by = sanitizeInput($_GET['sort'] ?? 'newest');

// Count active filters for UI badges
$active_filters_count = 0;
if (!empty($search)) $active_filters_count++;
if ($brand_filter > 0 || !empty($raw_brand)) $active_filters_count++;
if (!empty($type_filter)) $active_filters_count++;
if (!empty($condition_filter)) $active_filters_count++;
if (!empty($processor_filter)) $active_filters_count++;
if ($price_min !== null || $price_max !== null) $active_filters_count++;

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

// Helper to render filter form content (used for both desktop sidebar and mobile offcanvas)
function renderFilterForm($brands, $search, $brand_filter, $type_filter, $condition_filter, $processor_filter, $price_min, $price_max, $sort_by, $isMobile = false) {
    ?>
    <form action="buy.php" method="GET" class="filter-form">
        <!-- Preserve Sort -->
        <input type="hidden" name="sort" value="<?= escape($sort_by) ?>">

        <!-- Keyword Search -->
        <div class="mb-3">
            <label class="form-label small fw-bold text-dark mb-1.5">Keyword Search</label>
            <div class="input-group input-group-sm">
                <input type="text" name="search" class="form-control rounded-start-3" value="<?= escape($search) ?>" placeholder="Model, CPU, specs...">
                <button type="submit" class="btn btn-primary rounded-end-3 px-3"><i class="bi bi-search"></i></button>
            </div>
        </div>

        <!-- Brand Filter -->
        <div class="mb-3">
            <label class="form-label small fw-bold text-dark mb-1.5">Brand</label>
            <select name="brand" class="form-select form-select-sm rounded-3">
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
            <label class="form-label small fw-bold text-dark mb-1.5">Listing Type</label>
            <select name="type" class="form-select form-select-sm rounded-3">
                <option value="">All Types (New & Pre-Owned)</option>
                <option value="New" <?= $type_filter === 'New' ? 'selected' : '' ?>>Brand New Only</option>
                <option value="Old" <?= $type_filter === 'Old' ? 'selected' : '' ?>>Pre-Owned (Old) Only</option>
            </select>
        </div>

        <!-- Condition -->
        <div class="mb-3">
            <label class="form-label small fw-bold text-dark mb-1.5">Condition</label>
            <select name="condition" class="form-select form-select-sm rounded-3">
                <option value="">Any Condition</option>
                <option value="Brand New" <?= $condition_filter === 'Brand New' ? 'selected' : '' ?>>Brand New</option>
                <option value="Like New" <?= $condition_filter === 'Like New' ? 'selected' : '' ?>>Like New</option>
                <option value="Good" <?= $condition_filter === 'Good' ? 'selected' : '' ?>>Good</option>
                <option value="Fair" <?= $condition_filter === 'Fair' ? 'selected' : '' ?>>Fair</option>
            </select>
        </div>

        <!-- Processor / CPU -->
        <div class="mb-3">
            <label class="form-label small fw-bold text-dark mb-1.5">Processor / Chip</label>
            <input type="text" name="processor" class="form-control form-control-sm rounded-3" value="<?= escape($processor_filter) ?>" placeholder="e.g. M3, Core i7, Ryzen 7">
        </div>

        <!-- Price Range -->
        <div class="mb-4">
            <label class="form-label small fw-bold text-dark mb-1.5">Price Range (₹)</label>
            <div class="row g-2">
                <div class="col-6">
                    <input type="number" name="price_min" class="form-control form-control-sm rounded-3" placeholder="Min ₹" value="<?= $price_min !== null ? (int)$price_min : '' ?>" min="0" step="1">
                </div>
                <div class="col-6">
                    <input type="number" name="price_max" class="form-control form-control-sm rounded-3" placeholder="Max ₹" value="<?= $price_max !== null ? (int)$price_max : '' ?>" min="0" step="1">
                </div>
            </div>
            <!-- Quick Price Chips (Symmetrical 2x2 Grid) -->
            <div class="row g-2 mt-1">
                <div class="col-6">
                    <a href="buy.php?price_max=50000" class="btn btn-sm btn-light border text-secondary fw-semibold w-100 py-1.5 px-1 text-center text-decoration-none <?= ($price_max == 50000 && $price_min === null) ? 'active bg-primary-subtle text-primary border-primary' : '' ?>" style="font-size: 0.78rem;">Under ₹50k</a>
                </div>
                <div class="col-6">
                    <a href="buy.php?price_min=50000&price_max=100000" class="btn btn-sm btn-light border text-secondary fw-semibold w-100 py-1.5 px-1 text-center text-decoration-none <?= ($price_min == 50000 && $price_max == 100000) ? 'active bg-primary-subtle text-primary border-primary' : '' ?>" style="font-size: 0.78rem;">₹50k - ₹1L</a>
                </div>
                <div class="col-6">
                    <a href="buy.php?price_min=100000&price_max=200000" class="btn btn-sm btn-light border text-secondary fw-semibold w-100 py-1.5 px-1 text-center text-decoration-none <?= ($price_min == 100000 && $price_max == 200000) ? 'active bg-primary-subtle text-primary border-primary' : '' ?>" style="font-size: 0.78rem;">₹1L - ₹2L</a>
                </div>
                <div class="col-6">
                    <a href="buy.php?price_min=200000" class="btn btn-sm btn-light border text-secondary fw-semibold w-100 py-1.5 px-1 text-center text-decoration-none <?= ($price_min == 200000 && $price_max === null) ? 'active bg-primary-subtle text-primary border-primary' : '' ?>" style="font-size: 0.78rem;">₹2L+</a>
                </div>
            </div>
        </div>

        <div class="d-flex flex-column gap-2">
            <button type="submit" class="btn btn-primary w-100 rounded-3 fw-bold btn-sm py-2.5 shadow-sm">
                <i class="bi bi-funnel me-1.5"></i>Apply Filters
            </button>
            <a href="buy.php" class="btn btn-outline-secondary w-100 rounded-3 fw-medium btn-sm py-2 text-center text-decoration-none">
                <i class="bi bi-arrow-counterclockwise me-1.5"></i>Reset All
            </a>
        </div>
    </form>
    <?php
}
?>

<!-- Catalog Header Banner -->
<div class="bg-white border-bottom py-3 py-md-4 mb-3 mb-md-4">
    <div class="container">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
            <div>
                <h2 class="fw-bold mb-1 fs-3 fs-md-2">Laptop Catalog</h2>
                <p class="text-muted mb-0 small">Explore <?= number_format($total_items) ?> verified laptop listings</p>
            </div>
            <!-- Sort & Mobile Filter Toggle -->
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <!-- Mobile Filter Drawer Trigger -->
                <button type="button" class="btn btn-outline-primary btn-sm d-lg-none rounded-3 fw-bold d-inline-flex align-items-center gap-1.5 py-2 px-3" data-bs-toggle="offcanvas" data-bs-target="#lapifyFilterDrawer" aria-controls="lapifyFilterDrawer">
                    <i class="bi bi-funnel-fill"></i>
                    <span>Filters</span>
                    <?php if ($active_filters_count > 0): ?>
                        <span class="badge bg-primary text-white rounded-pill ms-1"><?= $active_filters_count ?></span>
                    <?php endif; ?>
                </button>

                <!-- Sort Control -->
                <form action="buy.php" method="GET" class="d-flex align-items-center gap-2 ms-auto">
                    <!-- Retain current filters in GET -->
                    <?php if (!empty($search)): ?><input type="hidden" name="search" value="<?= escape($search) ?>"><?php endif; ?>
                    <?php if ($brand_filter > 0): ?><input type="hidden" name="brand" value="<?= $brand_filter ?>"><?php endif; ?>
                    <?php if (!empty($type_filter)): ?><input type="hidden" name="type" value="<?= escape($type_filter) ?>"><?php endif; ?>
                    <?php if (!empty($condition_filter)): ?><input type="hidden" name="condition" value="<?= escape($condition_filter) ?>"><?php endif; ?>
                    <?php if (!empty($processor_filter)): ?><input type="hidden" name="processor" value="<?= escape($processor_filter) ?>"><?php endif; ?>
                    <?php if ($price_min !== null): ?><input type="hidden" name="price_min" value="<?= $price_min ?>"><?php endif; ?>
                    <?php if ($price_max !== null): ?><input type="hidden" name="price_max" value="<?= $price_max ?>"><?php endif; ?>
                    
                    <label for="sort" class="text-nowrap small fw-medium text-muted d-none d-sm-inline">Sort By:</label>
                    <select name="sort" id="sort" class="form-select form-select-sm border-secondary-subtle rounded-3 py-2" onchange="this.form.submit()">
                        <option value="newest" <?= $sort_by === 'newest' ? 'selected' : '' ?>>Newest First</option>
                        <option value="price_asc" <?= $sort_by === 'price_asc' ? 'selected' : '' ?>>Price: Low to High</option>
                        <option value="price_desc" <?= $sort_by === 'price_desc' ? 'selected' : '' ?>>Price: High to Low</option>
                    </select>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="container pb-5">
    <?php displayFlash(); ?>

    <div class="row g-4">
        <!-- Desktop Sidebar Filters -->
        <div class="col-lg-3 d-none d-lg-block">
            <div class="card border-0 shadow-sm rounded-4 p-4 sticky-top filter-sidebar" style="top: 90px; z-index: 10;">
                <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                    <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-funnel-fill text-primary me-2"></i>Filters</h5>
                    <?php if ($active_filters_count > 0): ?>
                        <a href="buy.php" class="text-danger small fw-bold text-decoration-none">Reset (<?= $active_filters_count ?>)</a>
                    <?php else: ?>
                        <span class="text-muted small">All Active</span>
                    <?php endif; ?>
                </div>

                <?php renderFilterForm($brands, $search, $brand_filter, $type_filter, $condition_filter, $processor_filter, $price_min, $price_max, $sort_by, false); ?>
            </div>
        </div>

        <!-- Mobile Filter Offcanvas Drawer -->
        <div class="offcanvas offcanvas-start d-lg-none" tabindex="-1" id="lapifyFilterDrawer" aria-labelledby="lapifyFilterDrawerLabel" style="max-width: 320px;">
            <div class="offcanvas-header border-bottom">
                <h5 class="offcanvas-title fw-bold text-primary d-flex align-items-center gap-2" id="lapifyFilterDrawerLabel">
                    <i class="bi bi-funnel-fill text-primary"></i>
                    <span>Filter Laptops</span>
                </h5>
                <button type="button" class="btn-close text-reset shadow-none" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body p-4">
                <?php renderFilterForm($brands, $search, $brand_filter, $type_filter, $condition_filter, $processor_filter, $price_min, $price_max, $sort_by, true); ?>
            </div>
        </div>

        <!-- Laptop Cards Grid / List -->
        <div class="col-lg-9">
            <!-- Active Filter Badges Bar -->
            <?php if ($active_filters_count > 0): ?>
                <div class="d-flex flex-wrap align-items-center gap-2 mb-3 p-2.5 bg-light rounded-3 border">
                    <span class="small fw-bold text-muted me-1"><i class="bi bi-tags-fill me-1"></i>Active Filters:</span>
                    <?php if (!empty($search)): ?>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle d-inline-flex align-items-center gap-1 py-1.5 px-2.5">
                            Search: "<?= escape($search) ?>"
                            <a href="buy.php?<?= http_build_query(array_diff_key($_GET, ['search' => ''])) ?>" class="text-primary text-decoration-none ms-1">&times;</a>
                        </span>
                    <?php endif; ?>
                    <?php if ($brand_filter > 0): 
                        $bname = "";
                        foreach ($brands as $b) { if ($b['id'] == $brand_filter) { $bname = $b['brand_name']; break; } }
                    ?>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle d-inline-flex align-items-center gap-1 py-1.5 px-2.5">
                            Brand: <?= escape($bname ?: $brand_filter) ?>
                            <a href="buy.php?<?= http_build_query(array_diff_key($_GET, ['brand' => ''])) ?>" class="text-primary text-decoration-none ms-1">&times;</a>
                        </span>
                    <?php endif; ?>
                    <?php if (!empty($type_filter)): ?>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle d-inline-flex align-items-center gap-1 py-1.5 px-2.5">
                            Type: <?= escape($type_filter) ?>
                            <a href="buy.php?<?= http_build_query(array_diff_key($_GET, ['type' => ''])) ?>" class="text-primary text-decoration-none ms-1">&times;</a>
                        </span>
                    <?php endif; ?>
                    <?php if (!empty($condition_filter)): ?>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle d-inline-flex align-items-center gap-1 py-1.5 px-2.5">
                            Condition: <?= escape($condition_filter) ?>
                            <a href="buy.php?<?= http_build_query(array_diff_key($_GET, ['condition' => ''])) ?>" class="text-primary text-decoration-none ms-1">&times;</a>
                        </span>
                    <?php endif; ?>
                    <?php if (!empty($processor_filter)): ?>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle d-inline-flex align-items-center gap-1 py-1.5 px-2.5">
                            CPU: <?= escape($processor_filter) ?>
                            <a href="buy.php?<?= http_build_query(array_diff_key($_GET, ['processor' => ''])) ?>" class="text-primary text-decoration-none ms-1">&times;</a>
                        </span>
                    <?php endif; ?>
                    <?php if ($price_min !== null || $price_max !== null): ?>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle d-inline-flex align-items-center gap-1 py-1.5 px-2.5">
                            Price: <?= $price_min !== null ? '₹' . number_format($price_min) : '₹0' ?> - <?= $price_max !== null ? '₹' . number_format($price_max) : 'Any' ?>
                            <a href="buy.php?<?= http_build_query(array_diff_key($_GET, ['price_min' => '', 'price_max' => ''])) ?>" class="text-primary text-decoration-none ms-1">&times;</a>
                        </span>
                    <?php endif; ?>
                    <a href="buy.php" class="text-danger small fw-bold text-decoration-none ms-auto">Clear All</a>
                </div>
            <?php endif; ?>

            <?php if (mysqli_num_rows($result) > 0): ?>
                <div class="row g-3 mb-4">
                    <?php while ($laptop = mysqli_fetch_assoc($result)): 
                        $img_src = getLaptopImageUrl($laptop) ?: 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?auto=format&fit=crop&w=600&q=80';
                        $is_own_listing = isOwnListing($user_id, $laptop['user_id']);
                        $is_wished = !$is_own_listing && isWishlisted($conn, $user_id, $laptop['id']);
                        $is_in_cart = isInCart($conn, $user_id, $laptop['id']);
                        $badge_class = $laptop['type'] === 'New' ? 'badge-type-new' : 'badge-type-old';
                    ?>
                        <div class="col-12 product-card-col">
                            <div class="card card-laptop card-laptop-horizontal shadow-sm border-0 rounded-4 overflow-hidden w-100">
                                <div class="row g-0 align-items-stretch">
                                    <div class="col-12 col-md-4 col-lg-4 col-xl-3">
                                        <div class="laptop-img-wrapper position-relative h-100" style="min-height: 200px;">
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
                                    <div class="col-12 col-md-8 col-lg-8 col-xl-9">
                                        <div class="card-body p-3 p-md-3.5 h-100 d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
                                            <div class="horizontal-card-info flex-grow-1" style="min-width: 0;">
                                                <div class="text-uppercase small text-muted fw-bold tracking-wide mb-1">
                                                    <?= escape($laptop['brand_name']) ?>
                                                </div>
                                                <h4 class="card-title fs-5 fw-bold mb-2 text-truncate" style="max-width: 100%;" title="<?= escape($laptop['model']) ?>">
                                                    <a href="laptop-details.php?id=<?= $laptop['id'] ?>" class="text-dark text-decoration-none hover-primary">
                                                        <?= escape($laptop['model']) ?>
                                                    </a>
                                                </h4>
                                                <div class="small text-muted mb-2.5 d-flex flex-wrap gap-1.5 align-items-center">
                                                    <span class="badge bg-light text-dark border px-2 py-1"><i class="bi bi-cpu text-primary me-1"></i><?= escape($laptop['processor'] ?? 'N/A') ?></span>
                                                    <span class="badge bg-light text-dark border px-2 py-1"><i class="bi bi-memory text-primary me-1"></i><?= escape($laptop['ram'] ?? 'N/A') ?></span>
                                                    <?php if (!empty($laptop['storage'])): ?>
                                                        <span class="badge bg-light text-dark border px-2 py-1"><i class="bi bi-hdd-rack-fill text-primary me-1"></i><?= escape($laptop['storage']) ?></span>
                                                    <?php endif; ?>
                                                    <?php if (!empty($laptop['condition_category'])): ?>
                                                        <span class="badge bg-secondary-subtle text-secondary border px-2 py-1"><?= escape($laptop['condition_category']) ?></span>
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
                                            <div class="horizontal-card-actions text-lg-end border-top border-lg-0 pt-2.5 pt-lg-0" style="min-width: 0;">
                                                <div class="fs-4 fs-lg-3 fw-bold text-primary mb-2">
                                                    <?= formatPrice($laptop['price']) ?>
                                                </div>
                                                <div class="product-action-group justify-content-lg-end">
                                                    <?php if ($is_own_listing): ?>
                                                        <a href="sell.php?edit_id=<?= $laptop['id'] ?>" class="btn btn-warning btn-product-buy text-dark fw-bold px-3 btn-edit-listing">
                                                            <i class="bi bi-pencil-square me-1"></i>Edit Listing
                                                        </a>
                                                    <?php elseif ($user_id): ?>
                                                        <button type="button" class="btn btn-soft-primary btn-product-icon btn-cart-toggle <?= $is_in_cart ? 'btn-success' : '' ?>" data-laptop-id="<?= $laptop['id'] ?>" title="<?= $is_in_cart ? 'In Cart' : 'Add to Cart' ?>" aria-label="Add to cart">
                                                            <i class="bi <?= $is_in_cart ? 'bi-cart-check-fill' : 'bi-cart-plus' ?>"></i>
                                                        </button>
                                                        <a href="checkout_cart.php?direct_laptop_id=<?= $laptop['id'] ?>" class="btn btn-primary btn-product-buy <?= ((int)($laptop['quantity'] ?? 1) === 0) ? 'disabled' : '' ?>">
                                                            <i class="bi bi-lightning-fill me-1"></i>Buy Now
                                                        </a>
                                                    <?php else: ?>
                                                        <a href="login.php" class="btn btn-soft-primary btn-product-icon" title="Log in to add to cart" aria-label="Log in to add to cart">
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
                        <ul class="pagination justify-content-center flex-wrap gap-1">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link rounded-3 px-3 py-2" href="buy.php?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">Previous</a>
                                </li>
                            <?php endif; ?>

                            <?php 
                            // Compact pagination range window
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);
                            if ($start_page > 1): ?>
                                <li class="page-item"><a class="page-link rounded-3 px-3 py-2" href="buy.php?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>">1</a></li>
                                <?php if ($start_page > 2): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
                            <?php endif; ?>

                            <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                    <a class="page-link rounded-3 px-3 py-2" href="buy.php?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($end_page < $total_pages): ?>
                                <?php if ($end_page < $total_pages - 1): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
                                <li class="page-item"><a class="page-link rounded-3 px-3 py-2" href="buy.php?<?= http_build_query(array_merge($_GET, ['page' => $total_pages])) ?>"><?= $total_pages ?></a></li>
                            <?php endif; ?>

                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link rounded-3 px-3 py-2" href="buy.php?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">Next</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>

            <?php else: ?>
                <div class="text-center py-5 bg-white rounded-4 shadow-sm border p-4">
                    <div class="fs-1 text-muted mb-3"><i class="bi bi-search"></i></div>
                    <h4 class="fw-bold">No Laptops Found</h4>
                    <p class="text-muted mb-3">No laptop listings matched your current filter criteria.</p>
                    <a href="buy.php" class="btn btn-primary rounded-pill px-4 py-2 fw-semibold">Reset All Filters</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php 
mysqli_stmt_close($stmt);
require_once __DIR__ . '/includes/footer.php'; 
?>
