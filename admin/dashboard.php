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
                <div class="card dashboard-recent-card w-100 border-0 shadow-sm overflow-hidden" style="border-radius: 24px !important;">
                    <div class="card-header bg-transparent p-4 border-bottom d-flex align-items-center justify-content-between">
                        <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-laptop-fill me-2 text-primary"></i>Recent Listings</h5>
                        <a href="laptops.php" class="btn btn-sm btn-outline-primary font-weight-bold rounded-pill px-3">Manage All <i class="bi bi-arrow-right ms-1"></i></a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light text-muted small text-uppercase font-weight-bold">
                                    <tr>
                                        <th class="ps-4 py-3">Model</th>
                                        <th class="py-3">Brand</th>
                                        <th class="py-3">Price</th>
                                        <th class="py-3">Seller</th>
                                        <th class="py-3 pe-4 text-end">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (mysqli_num_rows($recent_laptops) > 0): ?>
                                        <?php while ($laptop = mysqli_fetch_assoc($recent_laptops)): ?>
                                            <tr>
                                                <td class="ps-4 py-3 fw-bold text-dark"><?= escape($laptop['model']) ?></td>
                                                <td class="py-3 text-secondary"><?= escape($laptop['brand_name']) ?></td>
                                                <td class="py-3 text-primary fw-bold"><?= formatPrice($laptop['price']) ?></td>
                                                <td class="py-3 small text-secondary"><?= escape($laptop['seller_name']) ?></td>
                                                <td class="py-3 pe-4 text-end">
                                                    <span class="badge bg-success-subtle text-success rounded-pill px-3 py-2 font-weight-bold"><?= escape($laptop['status']) ?></span>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-4">No recent laptop listings available.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Users Card -->
            <div class="col-lg-5">
                <div class="card dashboard-recent-card w-100 border-0 shadow-sm overflow-hidden" style="border-radius: 24px !important;">
                    <div class="card-header bg-transparent p-4 border-bottom d-flex align-items-center justify-content-between">
                        <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-people-fill me-2 text-primary"></i>Recent Users</h5>
                        <a href="users.php" class="btn btn-sm btn-outline-primary font-weight-bold rounded-pill px-3">Manage All <i class="bi bi-arrow-right ms-1"></i></a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light text-muted small text-uppercase font-weight-bold">
                                    <tr>
                                        <th class="ps-4 py-3">Name</th>
                                        <th class="py-3">Role</th>
                                        <th class="py-3 pe-4 text-end">Joined</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (mysqli_num_rows($recent_users) > 0): ?>
                                        <?php while ($usr = mysqli_fetch_assoc($recent_users)): ?>
                                            <tr>
                                                <td class="ps-4 py-3">
                                                    <div class="fw-bold text-dark"><?= escape($usr['full_name']) ?></div>
                                                    <div class="small text-muted"><?= escape($usr['email']) ?></div>
                                                </td>
                                                <td class="py-3">
                                                    <span class="badge <?= $usr['role'] === 'admin' ? 'badge-admin-role' : 'badge-user-role' ?> rounded-pill">
                                                        <?= escape($usr['role']) ?>
                                                    </span>
                                                </td>
                                                <td class="py-3 pe-4 text-end small text-muted"><?= formatDate($usr['created_at']) ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="3" class="text-center text-muted py-4">No registered users found.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
