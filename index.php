<?php
// index.php - Homepage
$page_title = "Lapify | Buy & Sell Laptops Directly";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

$conn = getDbConnection();
$current_user = getCurrentUser();
$user_id = $current_user['id'] ?? ($_SESSION['user_id'] ?? null);

$user_exclude_sql = "";
if ($user_id !== null && (int)$user_id > 0) {
    $uid = (int)$user_id;
    $user_exclude_sql = " AND (l.user_id IS NULL OR l.user_id != {$uid})";
}

// Fetch Brands for Brand Grid
$brands_query = "SELECT * FROM brands WHERE status = 'active' ORDER BY brand_name ASC";
$brands_result = mysqli_query($conn, $brands_query);

$featured_query = "SELECT l.*, b.brand_name 
                  FROM laptops l 
                  JOIN brands b ON l.brand_id = b.id 
                  WHERE (l.status = 'approved' OR l.approval_status = 'approved' OR l.status = 'Available') AND l.status != 'pending' AND l.approval_status != 'pending' AND l.status != 'rejected' {$user_exclude_sql}
                  ORDER BY l.id DESC LIMIT 4";
$featured_result = mysqli_query($conn, $featured_query);

// Fetch 4 New Laptops
$new_query = "SELECT l.*, b.brand_name 
             FROM laptops l 
             JOIN brands b ON l.brand_id = b.id 
             WHERE (l.status = 'approved' OR l.approval_status = 'approved' OR l.status = 'Available') AND l.status != 'pending' AND l.approval_status != 'pending' AND l.status != 'rejected' AND l.type = 'New' {$user_exclude_sql}
             ORDER BY l.id DESC LIMIT 4";
$new_result = mysqli_query($conn, $new_query);

// Fetch 4 Pre-Owned (Old) Laptops
$old_query = "SELECT l.*, b.brand_name 
             FROM laptops l 
             JOIN brands b ON l.brand_id = b.id 
             WHERE (l.status = 'approved' OR l.approval_status = 'approved' OR l.status = 'Available') AND l.status != 'pending' AND l.approval_status != 'pending' AND l.status != 'rejected' AND l.type = 'Old' {$user_exclude_sql}
             ORDER BY l.id DESC LIMIT 4";
$old_result = mysqli_query($conn, $old_query);
?>

<!-- Hero Banner Section (Full Width with Lapify Border & Highlights) -->
<section class="hero-section">
    <!-- Ambient Corner Glows -->
    <div class="hero-glow-blob hero-glow-top-left"></div>
    <div class="hero-glow-blob hero-glow-bottom-right"></div>
    
    <div class="container position-relative" style="z-index: 2;">
        <div class="row justify-content-center text-center">
            <div class="col-lg-11 col-xl-10">

                <h1 class="hero-title display-4 fw-extrabold mb-3">
                    Buy New, Buy Used, & Sell Laptops
                    <span class="d-block hero-text-gradient mt-2">Without Fees</span>
                </h1>

                <p class="lead mb-4 mx-auto hero-copy" style="max-width: 740px; line-height: 1.85;">
                    Connect directly with laptop buyers and sellers in your community. Search thousands of verified specs, compare prices, and close deals safely.
                </p>

                <!-- Search Bar Form -->
                <form action="buy.php" method="GET" class="hero-search-wrapper mb-4">
                    <div class="hero-search-input-pill">
                        <i class="bi bi-search hero-search-icon"></i>
                        <input type="text" name="search" class="hero-search-input" placeholder="Search by model, brand, processor (e.g. M3 Max, i7 13th Gen, RTX 4070)..." autocomplete="off">
                        <button type="submit" class="btn btn-hero-search">
                            <i class="bi bi-search"></i>
                            <span>Find Laptops</span>
                        </button>
                    </div>
                </form>

                <!-- Spacious Quick Action Buttons -->
                <div class="hero-action-group d-flex flex-wrap align-items-center justify-content-center gap-3 mb-4">
                    <a href="buy.php" class="btn btn-hero-action-primary">
                        <i class="bi bi-grid-fill"></i>
                        <span>Browse All Laptops</span>
                    </a>
                    <a href="sell.php" class="btn btn-hero-action-outline">
                        <i class="bi bi-plus-circle-fill"></i>
                        <span>Post Free Laptop Ad</span>
                    </a>
                </div>

                <!-- Spacious Mini Trust Ribbons -->
                <div class="d-flex flex-wrap align-items-center justify-content-center gap-4 pt-2 text-muted fw-semibold hero-trust-ribbon">
                    <span class="d-inline-flex align-items-center gap-2">
                        <i class="bi bi-patch-check-fill text-success"></i> 100% Free Listing
                    </span>
                    <span class="text-slate-300 d-none d-sm-inline">•</span>
                    <span class="d-inline-flex align-items-center gap-2">
                        <i class="bi bi-shield-lock-fill text-primary"></i> Verified Specs
                    </span>
                    <span class="text-slate-300 d-none d-sm-inline">•</span>
                    <span class="d-inline-flex align-items-center gap-2">
                        <i class="bi bi-cash-stack text-warning"></i> Zero Middleman Markup
                    </span>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="container py-5">
    <?php displayFlash(); ?>

    <!-- Brand Grid Section -->
    <section class="mb-5">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h3 class="fw-bold mb-1">Browse Popular Brands</h3>
                <p class="text-muted mb-0">Select a brand to filter current listings</p>
            </div>
        </div>
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-2 row-cols-lg-4 g-3">
            <?php while ($brand = mysqli_fetch_assoc($brands_result)): 
                $logoUrl = getBrandLogoUrl($brand);

                // simple accent color map by slug
                $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '', trim($brand['brand_name'])));
                $accentMap = [
                    'acer' => '#1b9e3a',
                    'apple' => '#111111',
                    'asus' => '#1f2937',
                    'dell' => '#1170e4',
                    'hp' => '#0ea5e9',
                    'lenovo' => '#e52b2b',
                    'msi' => '#d62828'
                ];
                $accent = $accentMap[$slug] ?? '#4a90e2';
            ?>
                <div class="col">
                    <a href="buy.php?brand=<?= $brand['id'] ?>" class="brand-card h-100" style="--brand-accent: <?= $accent ?>;">
                        <div class="brand-logo-frame mb-3">
                            <?php if (!empty($logoUrl)): ?>
                                <img src="<?= escape($logoUrl) ?>" alt="<?= escape($brand['brand_name']) ?> logo" class="brand-logo-img mx-auto" />
                            <?php else: ?>
                                <div class="brand-fallback-img"><?= escape($brand['brand_name']) ?></div>
                            <?php endif; ?>
                        </div>
                        <p class="brand-name fw-semibold text-center m-0"><?= escape($brand['brand_name']) ?></p>
                    </a>
                </div>
            <?php endwhile; ?>
        </div>
    </section>

    <!-- Helper Card Component Function for Index -->
    <?php
    function renderLaptopCard($laptop, $conn, $user_id) {
        $img_src = getLaptopImageUrl($laptop) ?: 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?auto=format&fit=crop&w=600&q=80';
        
        $is_own_listing = isOwnListing($user_id, $laptop['user_id']);
        $is_wished = !$is_own_listing && isWishlisted($conn, $user_id, $laptop['id']);
        $is_in_cart = isInCart($conn, $user_id, $laptop['id']);
        $badge_class = $laptop['type'] === 'New' ? 'badge-type-new' : 'badge-type-old';
        ?>
        <div class="col-md-6 col-lg-3 product-card-col">
            <div class="card card-laptop h-100">
                <div class="laptop-img-wrapper">
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
                <div class="card-body">
                    <div>
                        <div class="text-uppercase small text-muted font-weight-bold tracking-wide mb-1">
                            <?= escape($laptop['brand_name']) ?>
                        </div>
                        <h5 class="card-title fw-bold mb-2" title="<?= escape($laptop['model']) ?>">
                            <?= escape($laptop['model']) ?>
                        </h5>
                        <div class="small text-muted mb-3 d-flex flex-wrap gap-2">
                            <span><i class="bi bi-cpu me-1"></i><?= escape($laptop['processor'] ?? 'N/A') ?></span>
                            <span>&bull;</span>
                            <span><i class="bi bi-memory me-1"></i><?= escape($laptop['ram'] ?? 'N/A') ?></span>
                        </div>
                        <div class="mb-3">
                            <?php if ((int)($laptop['quantity'] ?? 1) > 0): ?>
                                <span class="stock-badge">✓ In Stock (<?= (int)($laptop['quantity'] ?? 1) ?> available)</span>
                            <?php else: ?>
                                <span class="stock-badge out-of-stock">✕ Out of Stock</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="pt-2 border-top">
                        <div class="fw-bold text-primary mb-3">
                            <?= formatPrice($laptop['price']) ?>
                        </div>
                        <div class="product-action-group">
                            <?php if ($is_own_listing): ?>
                                <button type="button" class="btn btn-secondary btn-product-icon" title="This is your own listing" disabled style="pointer-events: none; opacity: 0.6;">
                                    <i class="bi bi-cart-plus"></i>
                                </button>
                                <button type="button" class="btn btn-secondary btn-product-buy" title="This is your own listing" disabled style="pointer-events: none; opacity: 0.6;">
                                    <i class="bi bi-lightning-fill me-1"></i>Buy Now
                                </button>
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
        <?php
    }
    ?>

    <!-- Featured Listings Section -->
    <section class="mb-5">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h3 class="fw-bold mb-1">Featured Listings</h3>
                <p class="text-muted mb-0">Handpicked recent listings from our seller community</p>
            </div>
            <a href="buy.php" class="btn-lapify-view-more"><span>View All</span> <i class="bi bi-arrow-right"></i></a>
        </div>
        <div class="row g-4">
            <?php 
            if (mysqli_num_rows($featured_result) > 0) {
                while ($laptop = mysqli_fetch_assoc($featured_result)) {
                    renderLaptopCard($laptop, $conn, $user_id);
                }
            } else {
                echo '<div class="col-12"><div class="alert alert-info border-0 rounded-3">No laptops listed yet. Be the first to list a laptop!</div></div>';
            }
            ?>
        </div>
    </section>

    <!-- Brand New Laptops Section -->
    <section class="mb-5">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h3 class="fw-bold mb-1"><i class="bi bi-box-seam text-success me-2"></i>Brand New Laptops</h3>
                <p class="text-muted mb-0">Factory fresh devices in sealed original packaging</p>
            </div>
            <a href="buy.php?type=New" class="btn-lapify-view-more"><span>View New</span> <i class="bi bi-arrow-right"></i></a>
        </div>
        <div class="row g-4">
            <?php 
            if (mysqli_num_rows($new_result) > 0) {
                while ($laptop = mysqli_fetch_assoc($new_result)) {
                    renderLaptopCard($laptop, $conn, $user_id);
                }
            } else {
                echo '<div class="col-12"><div class="alert alert-light text-muted border">No brand new laptops available right now.</div></div>';
            }
            ?>
        </div>
    </section>

    <!-- Pre-Owned (Old) Laptops Section -->
    <section class="mb-5">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h3 class="fw-bold mb-1"><i class="bi bi-recycle text-warning me-2"></i>Certified Pre-Owned & Old Laptops</h3>
                <p class="text-muted mb-0">Great performance deals at budget-friendly pre-owned prices</p>
            </div>
            <a href="buy.php?type=Old" class="btn-lapify-view-more"><span>View Used</span> <i class="bi bi-arrow-right"></i></a>
        </div>
        <div class="row g-4">
            <?php 
            if (mysqli_num_rows($old_result) > 0) {
                while ($laptop = mysqli_fetch_assoc($old_result)) {
                    renderLaptopCard($laptop, $conn, $user_id);
                }
            } else {
                echo '<div class="col-12"><div class="alert alert-light text-muted border">No pre-owned laptops available right now.</div></div>';
            }
            ?>
        </div>
    </section>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
