<?php
// admin/dashboard.php - Admin Dashboard Overview
$admin_title = "Admin Dashboard | Lapify";
require_once __DIR__ . '/header.php';
// require_once __DIR__ . '/../includes/auth.php';

requireAdmin();

 $conn = getDbConnection();

// 1. Total Users (exclude admins)
$u_res = mysqli_query($conn, "SELECT COUNT(*) FROM users WHERE email NOT IN (SELECT email FROM admins)");
$total_users = mysqli_fetch_row($u_res)[0] ?? 0;

// 2. Total Laptops
$l_res = mysqli_query($conn, "SELECT COUNT(*) FROM laptops");
$total_laptops = mysqli_fetch_row($l_res)[0] ?? 0;

// 3. New Laptops Count
$n_res = mysqli_query($conn, "SELECT COUNT(*) FROM laptops WHERE type = 'New'");
$new_count = mysqli_fetch_row($n_res)[0] ?? 0;

// 4. Old Laptops Count
$o_res = mysqli_query($conn, "SELECT COUNT(*) FROM laptops WHERE type = 'Old'");
$old_count = mysqli_fetch_row($o_res)[0] ?? 0;

// Fetch Recent 5 Laptops
$recent_laptops = mysqli_query($conn, "SELECT l.*, b.brand_name, u.full_name AS seller_name FROM laptops l JOIN brands b ON l.brand_id = b.id JOIN users u ON l.user_id = u.id ORDER BY l.id DESC LIMIT 5");

// Fetch Recent 5 Users with role determined by presence in admins table
$recent_users = mysqli_query($conn, "SELECT u.*, CASE WHEN a.email IS NOT NULL THEN 'admin' ELSE 'user' END AS role FROM users u LEFT JOIN admins a ON u.email = a.email ORDER BY u.id DESC LIMIT 5");
?>

<div class="dashboard-wrapper">
    <!-- Admin Sidebar -->
    <?php require_once __DIR__ . '/sidebar.php'; ?>

    <!-- Main Content Area -->
    <div class="dashboard-content">
        <?php require_once __DIR__ . '/topbar.php'; ?>
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h3 class="fw-bold mb-1">Administrative Overview</h3>
                <p class="text-muted mb-0">System metrics and platform activity</p>
            </div>
        </div>

        <?php displayFlash(); ?>

        <!-- Stat Cards -->
        <div class="row g-4 mb-4">
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="stat-card">
                    <div class="stat-icon stat-icon-blue">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <div>
                        <div class="stat-number"><?= number_format($total_users) ?></div>
                        <div class="stat-label">Registered Users</div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-3">
                <div class="stat-card">
                    <div class="stat-icon stat-icon-green">
                        <i class="bi bi-laptop-fill"></i>
                    </div>
                    <div>
                        <div class="stat-number"><?= number_format($total_laptops) ?></div>
                        <div class="stat-label">Total Listings</div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-3">
                <div class="stat-card">
                    <div class="stat-icon stat-icon-purple">
                        <i class="bi bi-box-seam"></i>
                    </div>
                    <div>
                        <div class="stat-number"><?= number_format($new_count) ?></div>
                        <div class="stat-label">Brand New Laptops</div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-3">
                <div class="stat-card">
                    <div class="stat-icon stat-icon-amber">
                        <i class="bi bi-recycle"></i>
                    </div>
                    <div>
                        <div class="stat-number"><?= number_format($old_count) ?></div>
                        <div class="stat-label">Pre-Owned (Old)</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Tables Row -->
        <div class="row g-4 align-items-start">
            <!-- Recent Listings Card -->
            <div class="col-lg-7">
                <div class="card dashboard-recent-card w-100 border-0 shadow-sm overflow-hidden rounded-4">
                    <div class="card-header bg-transparent p-4 border-bottom d-flex align-items-center justify-content-between">
                        <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-laptop-fill me-2 text-primary"></i>Recent Listings</h5>
                        <a href="laptops.php" class="btn btn-outline-primary rounded-pill px-4 py-2 fw-bold d-inline-flex align-items-center gap-2 shadow-2xs">
                            <span>Manage All</span>
                            <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                    <div class="card-body p-3.5 p-md-4">
                        <?php if (mysqli_num_rows($recent_laptops) > 0): ?>
                            <div class="d-flex flex-column gap-3">
                                <?php while ($laptop = mysqli_fetch_assoc($recent_laptops)): 
                                    $img_src = getLaptopImageUrl($laptop) ?: 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?auto=format&fit=crop&w=300&q=80';
                                    $listing_status = strtolower((string)($laptop['status'] ?? $laptop['approval_status'] ?? 'pending'));
                                ?>
                                    <div class="posting-item-card p-3 rounded-3 border d-flex align-items-center justify-content-between gap-3">
                                        <div class="d-flex align-items-center gap-3" style="min-width: 0;">
                                            <img src="<?= escape($img_src) ?>" alt="" class="posting-thumb rounded-3 border flex-shrink-0 me-3" style="width: 58px; height: 44px; object-fit: cover;">
                                            <div class="d-flex flex-column gap-0.5 ps-1" style="min-width: 0;">
                                                <h6 class="fw-bold mb-0 text-dark text-truncate" style="font-size: 0.96rem;" title="<?= escape($laptop['model']) ?>"><?= escape($laptop['model']) ?></h6>
                                                <div class="small text-muted">
                                                    <span><?= escape($laptop['brand_name']) ?></span> • <span><?= escape($laptop['seller_name']) ?></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center gap-3 ms-auto flex-shrink-0">
                                            <div class="fw-bold text-primary" style="font-size: 1.05rem;"><?= formatPrice($laptop['price']) ?></div>
                                            <span class="status-pill status-pill-<?= in_array($listing_status, ['approved', 'active']) ? 'active' : ($listing_status === 'rejected' ? 'rejected' : 'pending') ?>">
                                                <span class="status-dot"></span>
                                                <span><?= escape(ucfirst($listing_status)) ?></span>
                                            </span>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center text-muted py-4">No recent laptop listings available.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Recent Users Card -->
            <div class="col-lg-5">
                <div class="card dashboard-recent-card w-100 border-0 shadow-sm overflow-hidden rounded-4">
                    <div class="card-header bg-transparent p-4 border-bottom d-flex align-items-center justify-content-between">
                        <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-people-fill me-2 text-primary"></i>Recent Users</h5>
                        <a href="users.php" class="btn btn-outline-primary rounded-pill px-4 py-2 fw-bold d-inline-flex align-items-center gap-2 shadow-2xs">
                            <span>Manage All</span>
                            <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                    <div class="card-body p-3.5 p-md-4">
                        <?php if (mysqli_num_rows($recent_users) > 0): ?>
                            <div class="d-flex flex-column gap-3">
                                <?php while ($usr = mysqli_fetch_assoc($recent_users)): 
                                    $avatar = !empty($usr['profile_image']) && file_exists(PROFILE_UPLOAD_DIR . $usr['profile_image'])
                                        ? BASE_URL . '/uploads/profiles/' . $usr['profile_image']
                                        : 'https://ui-avatars.com/api/?name=' . urlencode($usr['full_name']) . '&background=2563eb&color=fff&bold=true';
                                    $is_admin = ($usr['role'] === 'admin');
                                ?>
                                    <div class="posting-item-card p-3 rounded-3 border d-flex align-items-center justify-content-between gap-3">
                                        <div class="d-flex align-items-center gap-3" style="min-width: 0;">
                                            <img src="<?= escape($avatar) ?>" alt="<?= escape($usr['full_name']) ?>" class="rounded-circle border flex-shrink-0 shadow-2xs" style="width: 44px; height: 44px; object-fit: cover;">
                                            <div class="d-flex flex-column gap-0.5" style="min-width: 0;">
                                                <h6 class="fw-bold mb-0 text-dark text-truncate" style="font-size: 0.96rem;" title="<?= escape($usr['full_name']) ?>">
                                                    <?= escape($usr['full_name']) ?>
                                                </h6>
                                                <div class="small text-muted text-truncate" style="font-size: 0.82rem;" title="<?= escape($usr['email']) ?>">
                                                    <?= escape($usr['email']) ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center gap-3 ms-auto flex-shrink-0">
                                            <span class="badge rounded-pill px-2.5 py-1 fw-semibold text-capitalize <?= $is_admin ? 'bg-primary-subtle text-primary border border-primary-subtle' : 'bg-secondary-subtle text-secondary' ?>" style="font-size: 0.72rem;">
                                                <?= escape($usr['role']) ?>
                                            </span>
                                            <span class="small text-muted" style="font-size: 0.82rem;">
                                                <?= formatDate($usr['created_at']) ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center text-muted py-4">No registered users found.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
