<?php
// admin/sidebar.php - Admin Sidebar Navigation Component
require_once __DIR__ . '/../includes/auth.php';
$admin_page = basename($_SERVER['PHP_SELF']);
$admin_user = getCurrentUser();
$pending_orders_count = 0;
$pending_listings_count = 0;
if ($admin_user) {
    $count_stmt = mysqli_prepare(getDbConnection(), "SELECT COUNT(*) FROM orders WHERE status = 'placed'");
    mysqli_stmt_execute($count_stmt);
    mysqli_stmt_bind_result($count_stmt, $pending_orders_count);
    mysqli_stmt_fetch($count_stmt);
    mysqli_stmt_close($count_stmt);

    $listing_count_stmt = mysqli_prepare(getDbConnection(), "SELECT COUNT(*) FROM laptops WHERE status = 'pending' OR approval_status = 'pending'");
    mysqli_stmt_execute($listing_count_stmt);
    mysqli_stmt_bind_result($listing_count_stmt, $pending_listings_count);
    mysqli_stmt_fetch($listing_count_stmt);
    mysqli_stmt_close($listing_count_stmt);
}
?>
<!-- Dark Backdrop Overlay (Closed by default) -->
<div id="adminSidebarOverlay" class="admin-sidebar-overlay"></div>

<!-- Admin Sidebar (Closed by default) -->
<div id="adminSidebar" class="admin-sidebar shadow-lg">
    <div class="d-flex align-items-center justify-content-between mb-4 px-2 pb-3 border-bottom border-secondary border-opacity-25">
        <?php
            $admin_avatar = !empty($admin_user['profile_image'] ?? null) && file_exists(PROFILE_UPLOAD_DIR . $admin_user['profile_image'])
                ? BASE_URL . '/uploads/profiles/' . $admin_user['profile_image']
                : 'https://ui-avatars.com/api/?name=' . urlencode($admin_user['full_name'] ?? 'Admin') . '&background=2563eb&color=fff';
        ?>
        <a href="<?= BASE_URL ?>/admin/profile.php" class="d-flex align-items-center gap-2.5 text-decoration-none" aria-label="Open admin profile">
            <img src="<?= escape($admin_avatar) ?>" alt="Admin profile" class="rounded-circle border border-2 border-light shadow-sm" style="width: 42px; height: 42px; object-fit: cover;">
            <div class="text-truncate" style="max-width: 140px;">
                <h6 class="fw-bold text-white mb-0 text-truncate" style="font-size: 0.92rem;"><?= escape($admin_user['full_name'] ?? 'Lapify Admin') ?></h6>
                <small class="text-white-50 text-truncate d-block" style="font-size: 11px;"><?= escape($admin_user['email'] ?? 'Administrator') ?></small>
            </div>
        </a>
        <button type="button" id="adminSidebarCloseBtn" class="btn btn-sm btn-outline-secondary rounded-circle ms-auto d-flex align-items-center justify-content-center admin-sidebar-close-btn" style="width: 32px; height: 32px; flex-shrink: 0;" aria-label="Close sidebar">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

    <ul class="nav flex-column">
        <li class="nav-item">
            <a class="nav-link <?= $admin_page === 'dashboard.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/dashboard.php">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $admin_page === 'users.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/users.php">
                <i class="bi bi-people"></i> Manage Users
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $admin_page === 'laptops.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/laptops.php">
                <i class="bi bi-laptop"></i> Manage Laptops
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $admin_page === 'brands.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/brands.php">
                <i class="bi bi-tags"></i> Manage Brands
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $admin_page === 'pending_listings.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/pending_listings.php">
                <i class="bi bi-hourglass-split"></i> Pending Listings
                <?php if ($pending_listings_count > 0): ?>
                    <span class="badge bg-warning text-dark ms-auto rounded-pill"><?= (int)$pending_listings_count ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $admin_page === 'orders.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/orders.php">
                <i class="bi bi-bag-check"></i> Manage Orders
                <?php if ($pending_orders_count > 0): ?>
                    <span class="badge bg-warning text-dark ms-auto rounded-pill"><?= (int)$pending_orders_count ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $admin_page === 'profile.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/profile.php">
                <i class="bi bi-person-gear"></i> My Profile
            </a>
        </li>
        <li class="nav-item my-3"><hr class="border-secondary opacity-25"></li>
        <li class="nav-item">
            <a class="nav-link text-danger" href="<?= BASE_URL ?>/admin/logout.php">
                <i class="bi bi-box-arrow-right"></i> Logout Admin
            </a>
        </li>
    </ul>

    <div class="mt-auto pt-4 px-2 border-top border-secondary border-opacity-25 small text-white-50">
        Logged as: <strong class="text-white d-block"><?= escape($admin_user['full_name'] ?? 'Admin') ?></strong>
    </div>
</div>
