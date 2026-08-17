<?php
// includes/navbar.php - Main Site Navigation Bar
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

$current_page = basename($_SERVER['PHP_SELF']);
$user = getCurrentUser();
$conn = getDbConnection();

$cart_count = 0;
$wishlist_count = 0;
$order_count = 0;
if ($user) {
    $cart_count = getCartCount($conn, $user['id']);
    $wishlist_count = getWishlistCount($conn, $user['id']);

    $order_stmt = mysqli_prepare($conn, "SELECT COUNT(*) FROM orders WHERE user_id = ?");
    if ($order_stmt) {
        mysqli_stmt_bind_param($order_stmt, "i", $user['id']);
        mysqli_stmt_execute($order_stmt);
        mysqli_stmt_bind_result($order_stmt, $order_count);
        mysqli_stmt_fetch($order_stmt);
        mysqli_stmt_close($order_stmt);
    }
}
?>
<nav class="navbar navbar-expand-lg navbar-lapify sticky-top shadow-sm">
    <div class="container">
        <!-- Brand Logo -->
        <a class="navbar-brand" href="<?= rtrim(BASE_URL, '/') ?>/index.php" aria-label="Lapify">
            <?= renderBrandLogo(['class' => 'navbar-brand-logo', 'aria-label' => 'Lapify', 'style' => 'height:52px; width:auto;']) ?>
        </a>

        <!-- Mobile Quick Actions & Toggler -->
        <div class="d-flex align-items-center gap-2 d-lg-none">
            <?php if ($user): ?>
                <a href="<?= BASE_URL ?>/cart.php" class="btn btn-light position-relative border-0 rounded-circle d-flex align-items-center justify-content-center btn-quick-icon shadow-none" style="width: 38px; height: 38px;" title="My Cart" aria-label="Shopping Cart">
                    <i class="bi bi-cart3 fs-5"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary cart-count-badge <?= $cart_count > 0 ? '' : 'd-none' ?>" style="font-size: 0.65rem;">
                        <?= $cart_count ?>
                    </span>
                </a>
            <?php endif; ?>
            <button class="navbar-toggler border-0 shadow-none p-1" type="button" data-bs-toggle="offcanvas" data-bs-target="#lapifyNavbarOffcanvas" aria-controls="lapifyNavbarOffcanvas" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
        </div>

        <!-- Offcanvas Navigation Drawer -->
        <div class="offcanvas offcanvas-end" tabindex="-1" id="lapifyNavbarOffcanvas" aria-labelledby="lapifyNavbarOffcanvasLabel">
            <div class="offcanvas-header border-bottom">
                <h5 class="offcanvas-title font-weight-bold text-primary d-flex align-items-center gap-2" id="lapifyNavbarOffcanvasLabel">
                    <i class="bi bi-laptop-fill text-primary fs-4"></i>
                    <span>Lapify</span>
                </h5>
                <button type="button" class="btn-close text-reset shadow-none" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            
            <div class="offcanvas-body">
                <!-- Nav Links -->
                <ul class="navbar-nav me-auto mb-2 mb-lg-0 align-items-lg-center">
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page === 'index.php' ? 'active font-weight-bold' : '' ?>" href="<?= BASE_URL ?>/index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page === 'buy.php' ? 'active font-weight-bold' : '' ?>" href="<?= BASE_URL ?>/buy.php">Buy Laptops</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page === 'sell.php' ? 'active font-weight-bold' : '' ?>" href="<?= BASE_URL ?>/sell.php">Sell Laptop</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page === 'about.php' ? 'active font-weight-bold' : '' ?>" href="<?= BASE_URL ?>/about.php">About Us</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page === 'contact.php' ? 'active font-weight-bold' : '' ?>" href="<?= BASE_URL ?>/contact.php">Contact</a>
                    </li>
                </ul>

                <!-- Auth Action Buttons / Dropdown -->
                <div class="d-flex navbar-actions align-items-lg-center mt-3 mt-lg-0">

                    <?php if ($user && ($user['role'] ?? '') === 'admin'): ?>
                        <a href="<?= BASE_URL ?>/admin/dashboard.php" class="btn btn-admin-back d-none d-lg-inline-flex align-items-center gap-2 me-3" title="Back to Admin Dashboard">
                            <i class="bi bi-shield-lock-fill fs-5"></i>
                            <span class="fw-semibold">Back to Admin</span>
                        </a>
                    <?php endif; ?>

                    <?php if ($user): ?>
                        <!-- Cart Quick Icon -->
                        <a href="<?= BASE_URL ?>/cart.php" class="btn btn-light position-relative me-2 border-0 rounded-circle d-none d-lg-inline-flex align-items-center justify-content-center btn-quick-icon" title="My Cart">
                            <i class="bi bi-cart3"></i>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary cart-count-badge <?= $cart_count > 0 ? '' : 'd-none' ?>">
                                <?= $cart_count ?>
                            </span>
                        </a>

                        <!-- Wishlist Quick Icon -->
                        <a href="<?= BASE_URL ?>/wishlist.php" class="btn btn-light position-relative me-2 border-0 rounded-circle d-none d-lg-inline-flex align-items-center justify-content-center btn-quick-icon" title="Wishlist">
                            <i class="bi bi-heart-fill"></i>
                            <span id="wishlist-count" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger wishlist-count-badge <?= $wishlist_count > 0 ? '' : 'd-none' ?>">
                                <?= $wishlist_count ?>
                            </span>
                        </a>

                        <a href="<?= BASE_URL ?>/orders.php" class="btn btn-light position-relative border-0 rounded-circle d-none d-lg-inline-flex align-items-center justify-content-center btn-quick-icon" title="My Orders">
                            <i class="bi bi-bag-check-fill"></i>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary order-count-badge <?= $order_count > 0 ? '' : 'd-none' ?>">
                                <?= (int)$order_count ?>
                            </span>
                        </a>

                        <!-- User Dropdown Menu (avatar only) -->
                        <div class="dropdown w-100 w-lg-auto">
                            <?php
                                $avatar_src = '';
                                if (!empty($user['profile_image']) && file_exists(PROFILE_UPLOAD_DIR . $user['profile_image'])) {
                                    $avatar_src = BASE_URL . '/uploads/profiles/' . $user['profile_image'];
                                } elseif ($user) {
                                    $avatar_src = 'https://ui-avatars.com/api/?name=' . urlencode($user['full_name']) . '&background=2563eb&color=fff&rounded=true&size=48';
                                }
                            ?>
                            <button class="btn dropdown-toggle p-0 border-0 bg-transparent rounded-circle" type="button" id="userMenuDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <img src="<?= $avatar_src ?>" alt="avatar" class="user-avatar-sm rounded-circle shadow-sm" style="width:48px;height:48px;object-fit:cover;">
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3 mt-2" aria-labelledby="userMenuDropdown">
                                <li class="px-3 py-2 user-menu-header">
                                    <div class="fw-semibold user-menu-name text-white"><?= escape($user['full_name']) ?></div>
                                    <div class="small user-menu-email text-white-50"><?= escape($user['email']) ?></div>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <?php if (($user['role'] ?? '') === 'admin'): ?>
                                    <li>
                                        <a class="dropdown-item fw-bold text-danger" href="<?= BASE_URL ?>/admin/dashboard.php">
                                            <i class="bi bi-shield-lock-fill me-2"></i>Admin Dashboard
                                        </a>
                                    </li>
                                <?php endif; ?>
                                <li>
                                    <a class="dropdown-item" href="<?= BASE_URL ?>/dashboard.php">
                                        <i class="bi bi-speedometer2 me-2"></i>User Dashboard
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="<?= BASE_URL ?>/cart.php">
                                        <i class="bi bi-cart3 me-2"></i>My Cart
                                        <span class="badge bg-primary rounded-pill ms-2 cart-count-badge <?= $cart_count > 0 ? '' : 'd-none' ?>"><?= $cart_count ?></span>
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="<?= BASE_URL ?>/my-listings.php">
                                        <i class="bi bi-laptop me-2"></i>My Listings
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="<?= BASE_URL ?>/wishlist.php">
                                        <i class="bi bi-heart me-2"></i>My Wishlist
                                    </a>
                                </li>
                                <li class="px-2 py-1">
                                    <a class="btn btn-primary btn-sm w-100 d-flex align-items-center justify-content-between rounded-3 dropdown-order-btn" href="<?= BASE_URL ?>/orders.php">
                                        <span><i class="bi bi-bag-check me-2"></i>My Orders</span>
                                        <span class="badge bg-white text-primary rounded-pill"><?= (int)$order_count ?></span>
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="<?= BASE_URL ?>/profile.php">
                                        <i class="bi bi-person-gear me-2"></i>Profile & Settings
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item text-danger" href="<?= BASE_URL ?>/logout.php">
                                        <i class="bi bi-box-arrow-right me-2"></i>Logout
                                    </a>
                                </li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <div class="d-flex flex-column flex-lg-row gap-2 w-100 w-lg-auto">
                            <a href="<?= BASE_URL ?>/login.php" class="btn btn-outline-primary px-4 rounded-pill">Log In</a>
                            <a href="<?= BASE_URL ?>/register.php" class="btn btn-primary px-4 rounded-pill">Register</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</nav>
