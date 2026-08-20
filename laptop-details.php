<?php
// laptop-details.php - Single Laptop View & Contact Seller
$page_title = "Laptop Details | Lapify";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

$conn = getDbConnection();
$user_id = getCurrentUser()['id'] ?? null;

$laptop_id = intval($_GET['id'] ?? 0);
if ($laptop_id <= 0) {
    header("Location: buy.php");
    exit();
}

// Fetch laptop details with brand name & seller info
$query = "SELECT l.*, b.brand_name, 
                 u.full_name AS seller_name, u.phone AS seller_phone, u.email AS seller_email, u.created_at AS seller_joined
          FROM laptops l 
          JOIN brands b ON l.brand_id = b.id 
          JOIN users u ON l.user_id = u.id 
          WHERE l.id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $laptop_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$laptop = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$laptop) {
    setFlash('error', 'Laptop not found.');
    header("Location: buy.php");
    exit();
}

$page_title = escape($laptop['brand_name'] . ' ' . $laptop['model']) . " | Lapify";

// Fetch up to 4 related laptops (same brand, excluding current)
$rel_query = "SELECT l.*, b.brand_name 
              FROM laptops l 
              JOIN brands b ON l.brand_id = b.id 
              WHERE l.brand_id = ? AND l.id != ? AND (l.status = 'approved' OR l.approval_status = 'approved' OR l.status = 'pending')
              LIMIT 4";
$rel_stmt = mysqli_prepare($conn, $rel_query);
mysqli_stmt_bind_param($rel_stmt, "ii", $laptop['brand_id'], $laptop_id);
mysqli_stmt_execute($rel_stmt);
$related_result = mysqli_stmt_get_result($rel_stmt);

$img_src = getLaptopImageUrl($laptop) ?: 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?auto=format&fit=crop&w=800&q=80';

$is_wished = isWishlisted($conn, $user_id, $laptop['id']);
$is_in_cart = isInCart($conn, $user_id, $laptop['id']);
$is_seller = ($user_id && $user_id == $laptop['user_id']);
?>

<div class="container py-5">
    <?php displayFlash(); ?>

    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php" class="text-primary text-decoration-none fw-semibold">Home</a></li>
            <li class="breadcrumb-item"><a href="buy.php" class="text-primary text-decoration-none fw-semibold">Buy Laptops</a></li>
            <li class="breadcrumb-item"><a href="buy.php?brand=<?= $laptop['brand_id'] ?>" class="text-primary text-decoration-none fw-semibold"><?= escape($laptop['brand_name']) ?></a></li>
            <li class="breadcrumb-item active text-dark" aria-current="page"><?= escape($laptop['model']) ?></li>
        </ol>
    </nav>

    <div class="row g-4 mb-5">
        <!-- Image Column (Fit laptop image size exactly without remaining space) -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden position-relative" style="background: #ffffff !important; border: 1px solid #e2e8f0 !important;">
                <div style="position: relative; padding-top: 65%;">
                    <img src="<?= escape($img_src) ?>" alt="<?= escape($laptop['model']) ?>" class="position-absolute top-0 start-0 w-100 h-100 object-fit-cover">
                </div>
                <?php if ($is_seller): ?>
                    <button type="button" class="btn-wishlist btn-wishlist-toggle position-absolute top-0 end-0 m-3 shadow" data-laptop-id="<?= $laptop['id'] ?>" title="You can't wishlist your own listing" style="width: 44px; height: 44px; pointer-events: none; opacity: 0.55; cursor: not-allowed;" disabled>
                        <i class="bi bi-heart fs-5"></i>
                    </button>
                <?php else: ?>
                    <button type="button" class="btn-wishlist btn-wishlist-toggle <?= $is_wished ? 'active' : '' ?> position-absolute top-0 end-0 m-3 shadow" data-laptop-id="<?= $laptop['id'] ?>" title="Save to wishlist" style="width: 44px; height: 44px;">
                        <i class="bi <?= $is_wished ? 'bi-heart-fill text-danger' : 'bi-heart' ?> fs-5"></i>
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Specifications & Seller Info Column (High Contrast Black Text) -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-lg rounded-4 p-4 h-100 d-flex flex-column justify-content-between" style="background: #ffffff !important; border: 1px solid #e2e8f0 !important; color: #0f172a !important;">
                <div>
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="badge bg-primary text-white fw-bold px-3 py-2 rounded-pill text-uppercase fs-7 shadow-sm">
                            <?= escape($laptop['brand_name']) ?>
                        </span>
                        <span class="badge <?= $laptop['type'] === 'New' ? 'bg-success text-white' : 'bg-warning text-dark' ?> fw-bold px-3 py-2 rounded-pill">
                            <?= escape($laptop['type']) ?> Laptop
                        </span>
                    </div>

                    <h2 class="fw-bold mb-2" style="color: #0f172a !important;"><?= escape($laptop['model']) ?></h2>

                    <div class="display-6 font-weight-extrabold mb-4" style="color: #2563eb !important; font-weight: 800;">
                        <?= formatPrice($laptop['price']) ?>
                    </div>

                    <!-- Cart & Buy Action Buttons -->
                    <div class="row g-2 mb-4">
                        <?php if ($is_seller): ?>
                            <div class="col-6">
                                <button type="button" class="btn btn-secondary w-100 rounded-3 font-weight-bold py-2.5" disabled style="pointer-events: none; opacity: 0.6;" title="This is your own listing">
                                    <i class="bi bi-cart-plus me-1"></i>Add to Cart
                                </button>
                            </div>
                            <div class="col-6">
                                <button type="button" class="btn btn-secondary w-100 rounded-3 font-weight-bold py-2.5" disabled style="pointer-events: none; opacity: 0.6;" title="This is your own listing">
                                    <i class="bi bi-lightning-fill me-1"></i>Buy Now
                                </button>
                            </div>
                        <?php elseif ($user_id): ?>
                            <div class="col-6">
                                <button type="button" class="btn <?= $is_in_cart ? 'btn-success' : 'btn-soft-primary' ?> w-100 rounded-3 font-weight-bold py-2.5 btn-cart-toggle" data-laptop-id="<?= $laptop['id'] ?>">
                                    <i class="bi <?= $is_in_cart ? 'bi-cart-check-fill' : 'bi-cart-plus' ?> me-1"></i><?= $is_in_cart ? 'In Cart' : 'Add to Cart' ?>
                                </button>
                            </div>
                            <div class="col-6">
                                <a href="checkout_cart.php?direct_laptop_id=<?= $laptop['id'] ?>" class="btn btn-primary w-100 rounded-3 font-weight-bold py-2.5">
                                    <i class="bi bi-lightning-fill me-1"></i>Buy Now
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="col-6">
                                <a href="login.php" class="btn btn-soft-primary w-100 rounded-3 font-weight-bold py-2.5">
                                    <i class="bi bi-cart-plus me-1"></i>Add to Cart
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="login.php" class="btn btn-primary w-100 rounded-3 font-weight-bold py-2.5">
                                    <i class="bi bi-lightning-fill me-1"></i>Buy Now
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Key Spec Highlights Grid (High Contrast Black Text) -->
                    <div class="row g-2 mb-4">
                        <div class="col-6">
                            <div class="p-3 rounded-3 d-flex align-items-center gap-2" style="background: #f8fafc; border: 1.5px solid #e2e8f0;">
                                <i class="bi bi-cpu text-primary fs-4"></i>
                                <div>
                                    <div class="small fw-semibold" style="color: #64748b !important;">Processor</div>
                                    <div class="fw-bold small" style="color: #0f172a !important;"><?= escape($laptop['processor'] ?? 'N/A') ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 rounded-3 d-flex align-items-center gap-2" style="background: #f8fafc; border: 1.5px solid #e2e8f0;">
                                <i class="bi bi-memory text-primary fs-4"></i>
                                <div>
                                    <div class="small fw-semibold" style="color: #64748b !important;">RAM</div>
                                    <div class="fw-bold small" style="color: #0f172a !important;"><?= escape($laptop['ram'] ?? 'N/A') ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 rounded-3 d-flex align-items-center gap-2" style="background: #f8fafc; border: 1.5px solid #e2e8f0;">
                                <i class="bi bi-hdd-network text-primary fs-4"></i>
                                <div>
                                    <div class="small fw-semibold" style="color: #64748b !important;">Storage</div>
                                    <div class="fw-bold small" style="color: #0f172a !important;"><?= escape($laptop['storage'] ?? 'N/A') ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 rounded-3 d-flex align-items-center gap-2" style="background: #f8fafc; border: 1.5px solid #e2e8f0;">
                                <i class="bi bi-patch-check text-primary fs-4"></i>
                                <div>
                                    <div class="small fw-semibold" style="color: #64748b !important;">Condition</div>
                                    <div class="fw-bold small" style="color: #0f172a !important;"><?= escape($laptop['condition'] ?? 'N/A') ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Seller Info Section (High Contrast Black Text) -->
                <div class="border-top border-slate-200 pt-4 mt-3">
                    <h5 class="fw-bold mb-3" style="color: #0f172a !important;"><i class="bi bi-person-badge text-primary me-2"></i>Seller Information</h5>
                    <div class="d-flex align-items-center gap-3 mb-3 p-3 rounded-3" style="background: #f8fafc; border: 1.5px solid #e2e8f0;">
                        <div class="avatar-placeholder rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold" style="width:48px;height:48px;font-size:16px;flex-shrink:0;">
                            <i class="bi bi-person-fill"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-0" style="color: #0f172a !important;"><?= escape($laptop['seller_name']) ?></h6>
                            <div class="small" style="color: #64748b !important;">Member since <?= formatDate($laptop['seller_joined']) ?></div>
                        </div>
                    </div>

                    <?php if ($is_seller): ?>
                        <div class="alert alert-info border-0 rounded-3 small mb-0">
                            <i class="bi bi-info-circle me-1"></i> You are the owner of this laptop listing.
                            <a href="my-listings.php" class="fw-bold text-primary ms-1">Manage Listing</a>
                        </div>
                    <?php else: ?>
                        <div class="p-3 rounded-3" style="background: #f8fafc; border: 1.5px solid #e2e8f0;">
                            <div class="fw-bold mb-2" style="color: #0f172a !important;">Seller Contact Details</div>
                            <div class="small mb-3" style="color: #475569 !important;">Contact the seller directly for price negotiation, physical inspection, or local pickup:</div>
                            <?php if (!empty($laptop['seller_phone'])): ?>
                                <div class="mb-2">
                                    <i class="bi bi-telephone-fill text-primary me-2"></i>
                                    <a href="tel:<?= escape($laptop['seller_phone']) ?>" class="fw-semibold text-primary text-decoration-none"><?= escape($laptop['seller_phone']) ?></a>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($laptop['seller_email'])): ?>
                                <div>
                                    <i class="bi bi-envelope-fill text-primary me-2"></i>
                                    <a href="mailto:<?= escape($laptop['seller_email']) ?>" class="fw-semibold text-primary text-decoration-none"><?= escape($laptop['seller_email']) ?></a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>

    <!-- Description & Specs Details Card (High Contrast Black Text) -->
    <div class="card border-0 shadow-lg rounded-4 p-4 p-md-5 mb-5" style="background: #ffffff !important; border: 1px solid #e2e8f0 !important; color: #0f172a !important;">
        <h4 class="fw-bold mb-3" style="color: #0f172a !important;"><i class="bi bi-file-text-fill text-primary me-2"></i>Item Description & Features</h4>
        <div class="lead fs-6" style="white-space: pre-line; line-height: 1.85; color: #334155 !important;">
            <?= escape($laptop['description'] ?: 'No detailed description provided by seller.') ?>
        </div>
    </div>

    <!-- Related Laptops -->
    <?php if (mysqli_num_rows($related_result) > 0): ?>
        <section>
            <h4 class="fw-bold mb-4" style="color: #0f172a !important;">Related Laptops You Might Like</h4>
            <div class="row g-4">
                <?php while ($rel = mysqli_fetch_assoc($related_result)): 
                    $rel_img = getLaptopImageUrl($rel) ?: 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?auto=format&fit=crop&w=600&q=80';
                ?>
                    <div class="col-md-6 col-lg-3">
                        <div class="card card-laptop h-100" style="background: #ffffff !important; border: 1px solid #e2e8f0 !important;">
                            <div class="laptop-img-wrapper">
                                <img src="<?= escape($rel_img) ?>" alt="<?= escape($rel['model']) ?>" onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1517336714731-489689fd1ca8?auto=format&fit=crop&w=600&q=80';">
                            </div>
                            <div class="card-body p-3">
                                <div class="small font-weight-bold text-uppercase" style="color: #64748b !important;"><?= escape($rel['brand_name']) ?></div>
                                <h6 class="fw-bold text-truncate mb-2" style="color: #0f172a !important;"><?= escape($rel['model']) ?></h6>
                                <div class="d-flex align-items-center justify-content-between border-top border-slate-200 pt-2.5 mt-2">
                                    <div class="fw-bold text-primary" style="font-size: 1.15rem;"><?= formatPrice($rel['price']) ?></div>
                                    <a href="laptop-details.php?id=<?= $rel['id'] ?>" class="btn btn-outline-primary rounded-pill px-3.5 py-1.5 fw-semibold d-inline-flex align-items-center gap-1.5 shadow-2xs" title="View Laptop Details">
                                        <i class="bi bi-eye"></i>
                                        <span>View</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </section>
    <?php endif; ?>
</div>

<?php 
mysqli_stmt_close($rel_stmt);
require_once __DIR__ . '/includes/footer.php'; 
?>
