<?php
// dashboard.php - User Overview Dashboard
$page_title = "User Dashboard | Lapify";
require_once __DIR__ . '/includes/auth.php';
requireLogin();
require_once __DIR__ . '/includes/header.php';

$conn = getDbConnection();
$current_user = getCurrentUser();
$user_id = $current_user['id'];

// Fetch fresh user record
$u_stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ?");
mysqli_stmt_bind_param($u_stmt, "i", $user_id);
mysqli_stmt_execute($u_stmt);
$user_data = mysqli_fetch_assoc(mysqli_stmt_get_result($u_stmt));
mysqli_stmt_close($u_stmt);

// Stat Queries
// 1. My Listings Count
$listings_count = 0;
$l_stmt = mysqli_prepare($conn, "SELECT COUNT(*) FROM laptops WHERE user_id = ?");
if ($l_stmt) {
    mysqli_stmt_bind_param($l_stmt, "i", $user_id);
    mysqli_stmt_execute($l_stmt);
    mysqli_stmt_bind_result($l_stmt, $listings_count);
    mysqli_stmt_fetch($l_stmt);
    mysqli_stmt_close($l_stmt);
}

// 2. Wishlist Count
$wishlist_count = 0;
$w_stmt = mysqli_prepare($conn, "SELECT COUNT(*) FROM wishlist WHERE user_id = ?");
if ($w_stmt) {
    mysqli_stmt_bind_param($w_stmt, "i", $user_id);
    mysqli_stmt_execute($w_stmt);
    mysqli_stmt_bind_result($w_stmt, $wishlist_count);
    mysqli_stmt_fetch($w_stmt);
    mysqli_stmt_close($w_stmt);
}

// 3. Profile Completion
$profile_percent = calculateProfileCompletion($user_data);

// Fetch recent 5 listings posted by user
$recent_query = "SELECT l.*, b.brand_name 
                FROM laptops l 
                JOIN brands b ON l.brand_id = b.id 
                WHERE l.user_id = ? 
                ORDER BY l.id DESC LIMIT 5";
$r_stmt = mysqli_prepare($conn, $recent_query);
mysqli_stmt_bind_param($r_stmt, "i", $user_id);
mysqli_stmt_execute($r_stmt);
$recent_listings = mysqli_stmt_get_result($r_stmt);

require_once __DIR__ . '/includes/navbar.php';
?>

<div class="container py-5 dashboard-page">
    <!-- Dashboard Hero -->
    <div class="dashboard-hero-card mb-5">
        <div class="dashboard-hero-overlay"></div>
        <div class="row align-items-center gx-4 position-relative" style="z-index: 2;">
            <div class="col-xl-7">
                <span class="badge hero-badge text-uppercase fw-semibold mb-3">Dashboard Overview</span>
                <h1 class="hero-title fw-bold mb-3">Welcome back, <?= escape($current_user['full_name']) ?>!</h1>
                <p class="hero-copy mb-4 text-secondary">Your seller dashboard is ready. Launch new ads, track active listings, and keep your laptop marketplace performance sharp — all from one polished panel.</p>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <a href="sell.php" class="btn btn-white btn-lg rounded-pill px-4 py-3 hero-action-btn shadow-sm">
                        <i class="bi bi-plus-lg me-2"></i>Post Free Laptop Ad
                    </a>
                    <span class="hero-tag rounded-pill">Fast listing creation</span>
                </div>
            </div>
            <div class="col-xl-5">
                <div class="hero-summary-card p-4 shadow-sm position-relative d-flex align-items-center justify-content-center" style="min-height: 220px;">
                    <img src="https://img.icons8.com/ios/120/2563eb/laptop.png" alt="Laptop image" class="img-fluid hero-dashboard-laptop" />
                </div>
            </div>
        </div>
    </div>

    <?php displayFlash(); ?>

    <!-- Stat Cards Row -->
    <div class="row g-4 mb-4 dashboard-stat-grid">
        <div class="col-12 col-sm-6 col-lg-4">
            <div class="stat-card">
                <div class="stat-icon stat-icon-blue">
                    <i class="bi bi-laptop"></i>
                </div>
                <div>
                    <div class="stat-number"><?= number_format((int) $listings_count) ?></div>
                    <div class="stat-label">My Listings</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-lg-4">
            <div class="stat-card">
                <div class="stat-icon stat-icon-green">
                    <i class="bi bi-heart"></i>
                </div>
                <div>
                    <div class="stat-number"><?= number_format((int) $wishlist_count) ?></div>
                    <div class="stat-label">Saved Laptops</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-lg-4">
            <div class="stat-card">
                <div class="stat-icon stat-icon-purple">
                    <i class="bi bi-person-check"></i>
                </div>
                <div>
                    <div class="stat-number"><?= $profile_percent ?>%</div>
                    <div class="stat-label">Profile Completed</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Shortcuts & Recent Table -->
    <div class="row g-4 mb-4">
        <div class="col-lg-4">
            <div class="dashboard-quick-actions-card p-4 h-100">
                <h5 class="fw-bold mb-3"><i class="bi bi-lightning-charge text-warning me-2"></i>Quick Actions</h5>
                <div class="d-flex flex-column gap-2">
                    <a href="sell.php" class="btn btn-outline-primary text-start font-weight-bold p-3 rounded-3 d-flex align-items-center justify-content-between">
                        <span><i class="bi bi-plus-circle me-2"></i>Post New Laptop Ad</span>
                        <i class="bi bi-chevron-right"></i>
                    </a>
                    <a href="my-listings.php" class="btn btn-light text-start font-weight-bold p-3 rounded-3 d-flex align-items-center justify-content-between">
                        <span><i class="bi bi-list-task me-2"></i>Manage My Listings</span>
                        <i class="bi bi-chevron-right"></i>
                    </a>
                    <a href="orders.php" class="btn btn-light text-start font-weight-bold p-3 rounded-3 d-flex align-items-center justify-content-between">
                        <span><i class="bi bi-receipt me-2"></i>View My Orders</span>
                        <i class="bi bi-chevron-right"></i>
                    </a>
                    <a href="my-queries.php" class="btn btn-light text-start font-weight-bold p-3 rounded-3 d-flex align-items-center justify-content-between">
                        <span><i class="bi bi-chat-left-text me-2"></i>My Support Inquiries</span>
                        <i class="bi bi-chevron-right"></i>
                    </a>
                    <a href="profile.php" class="btn btn-light text-start font-weight-bold p-3 rounded-3 d-flex align-items-center justify-content-between">
                        <span><i class="bi bi-gear me-2"></i>Edit Profile & Security</span>
                        <i class="bi bi-chevron-right"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="dashboard-recent-card d-flex flex-column h-100">
                <!-- Spacious Header -->
                <div class="dashboard-recent-header d-flex align-items-center justify-content-between p-4 border-bottom">
                    <div>
                        <h5 class="fw-bold mb-1 text-slate-900" style="font-size: 1.25rem;">Recent Postings</h5>
                        <p class="text-muted small mb-0" style="font-size: 0.88rem;">Your latest laptop listings & status</p>
                    </div>
                    <a href="my-listings.php" class="btn btn-outline-primary rounded-pill px-4 py-2 fw-semibold d-inline-flex align-items-center gap-2">
                        <span>View All</span>
                        <i class="bi bi-arrow-right"></i>
                    </a>
                </div>

                <!-- Body with Generous Spacing -->
                <div class="card-body p-4 flex-grow-1">
                    <?php if (mysqli_num_rows($recent_listings) > 0): ?>
                        <div class="d-flex flex-column gap-3">
                            <?php while ($row = mysqli_fetch_assoc($recent_listings)): 
                                $img_src = getLaptopImageUrl($row) ?: 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?auto=format&fit=crop&w=300&q=80';
                                $listing_state = strtolower((string)($row['status'] ?? $row['approval_status'] ?? 'pending'));
                                
                                if (in_array($listing_state, ['approved', 'available', 'active'])) {
                                    $status_class = 'status-pill-active';
                                    $status_label = 'Active';
                                } elseif ($listing_state === 'rejected') {
                                    $status_class = 'status-pill-rejected';
                                    $status_label = 'Rejected';
                                } else {
                                    $status_class = 'status-pill-pending';
                                    $status_label = 'Pending Approval';
                                }
                            ?>
                                <div class="posting-item-card rounded-4 border">
                                    <!-- Left: Image & Info with ample breathing room -->
                                    <div class="posting-info-wrap">
                                        <div class="posting-thumb-wrapper">
                                            <img src="<?= escape($img_src) ?>" alt="<?= escape($row['model']) ?>" class="posting-thumb shadow-2xs">
                                        </div>
                                        <div class="posting-text-wrap">
                                            <h6 class="posting-title" title="<?= escape($row['model']) ?>">
                                                <?= escape($row['model']) ?>
                                            </h6>
                                            <div class="posting-meta-row">
                                                <span class="fw-semibold text-secondary">
                                                    <i class="bi bi-tag-fill text-primary me-1"></i><?= escape($row['brand_name'] ?? 'Laptop') ?>
                                                </span>
                                                <span class="text-slate-300">•</span>
                                                <span>
                                                    <i class="bi bi-clock me-1"></i><?= formatDate($row['created_at']) ?>
                                                </span>
                                                <?php if (!empty($row['type'])): ?>
                                                    <span class="text-slate-300">•</span>
                                                    <span class="badge rounded-pill px-2.5 py-0.5 <?= (strtolower($row['type']) === 'new') ? 'badge-type-new' : 'badge-type-used' ?>">
                                                        <?= (strtolower($row['type']) === 'new') ? 'Brand New' : 'Pre-Owned' ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Right: Price, Status, and Action Button -->
                                    <div class="posting-actions-wrap">
                                        <div class="posting-price">
                                            <?= formatPrice($row['price']) ?>
                                        </div>

                                        <div>
                                            <span class="status-pill <?= $status_class ?>">
                                                <span class="status-dot"></span>
                                                <span><?= escape($status_label) ?></span>
                                            </span>
                                        </div>

                                        <div>
                                            <a href="laptop-details.php?id=<?= $row['id'] ?>" class="btn btn-outline-primary rounded-pill px-3.5 py-1.5 fw-semibold d-inline-flex align-items-center gap-1.5 shadow-2xs" title="View Listing Details">
                                                <i class="bi bi-eye"></i>
                                                <span>View</span>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5 px-4 my-auto">
                            <div class="bg-primary-subtle text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                                <i class="bi bi-laptop fs-2"></i>
                            </div>
                            <h6 class="fw-bold mb-1">No Listings Posted Yet</h6>
                            <p class="text-muted small mb-3">Post your laptop advertisement for free in minutes.</p>
                            <a href="sell.php" class="btn btn-primary rounded-pill px-4 py-2.5 fw-semibold shadow-sm">
                                <i class="bi bi-plus-lg me-1.5"></i> Post Free Laptop Ad
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
mysqli_stmt_close($r_stmt);
require_once __DIR__ . '/includes/footer.php'; 
?>
