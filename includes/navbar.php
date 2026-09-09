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

        <!-- Offcanvas Navigation Drawer (Enhanced Mobile Experience) -->
        <div class="offcanvas offcanvas-end offcanvas-lapify" tabindex="-1" id="lapifyNavbarOffcanvas" aria-labelledby="lapifyNavbarOffcanvasLabel">
            <div class="offcanvas-header border-bottom px-4 py-3 d-lg-none">
                <div class="d-flex align-items-center gap-2" id="lapifyNavbarOffcanvasLabel">
                    <?= renderBrandLogo(['class' => 'offcanvas-brand-logo', 'aria-label' => 'Lapify', 'style' => 'height:40px; width:auto;']) ?>
                </div>
                <button type="button" class="btn-close text-reset shadow-none" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            
            <div class="offcanvas-body p-0 p-lg-0 d-flex flex-column flex-lg-row align-items-lg-center justify-content-lg-between w-100">
                
                <!-- Mobile User / Guest Card (Mobile Only) -->
                <div class="p-3 d-lg-none mobile-drawer-header-card">
                    <?php if ($user): ?>
                        <?php
                            $mobile_avatar = '';
                            if (!empty($user['profile_image']) && file_exists(PROFILE_UPLOAD_DIR . $user['profile_image'])) {
                                $mobile_avatar = BASE_URL . '/uploads/profiles/' . $user['profile_image'];
                            } else {
                                $mobile_avatar = 'https://ui-avatars.com/api/?name=' . urlencode($user['full_name']) . '&background=2563eb&color=fff&rounded=true&size=48';
                            }
                        ?>
                        <div class="mobile-user-profile-box p-3 rounded-4">
                            <div class="d-flex align-items-center gap-3 mb-2.5">
                                <img src="<?= $mobile_avatar ?>" alt="Avatar" class="rounded-circle shadow-sm" style="width:48px; height:48px; object-fit:cover; border:2px solid #2563eb;">
                                <div class="overflow-hidden">
                                    <div class="fw-bold text-dark text-truncate fs-6"><?= escape($user['full_name']) ?></div>
                                    <div class="small text-muted text-truncate"><?= escape($user['email']) ?></div>
                                </div>
                            </div>
                            <div class="d-flex gap-2 pt-2 border-top border-secondary-subtle">
                                <a href="<?= BASE_URL ?>/cart.php" class="mobile-user-stat-pill">
                                    <i class="bi bi-cart3 text-primary"></i>
                                    <span>Cart</span>
                                    <span class="badge bg-primary text-white rounded-pill px-1.5"><?= $cart_count ?></span>
                                </a>
                                <a href="<?= BASE_URL ?>/wishlist.php" class="mobile-user-stat-pill">
                                    <i class="bi bi-heart-fill text-danger"></i>
                                    <span>Wishlist</span>
                                    <span class="badge bg-danger text-white rounded-pill px-1.5"><?= $wishlist_count ?></span>
                                </a>
                                <a href="<?= BASE_URL ?>/orders.php" class="mobile-user-stat-pill">
                                    <i class="bi bi-bag-check-fill text-success"></i>
                                    <span>Orders</span>
                                    <span class="badge bg-success text-white rounded-pill px-1.5"><?= (int)$order_count ?></span>
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="mobile-guest-hero-box p-3 rounded-4">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <i class="bi bi-stars text-primary fs-5"></i>
                                <span class="fw-bold text-dark fs-6">Welcome to Lapify</span>
                            </div>
                            <p class="small text-muted mb-3" style="line-height:1.5;">Direct laptop trading marketplace with zero hidden fees.</p>
                            <div class="d-grid gap-2">
                                <a href="<?= BASE_URL ?>/login.php" class="btn btn-primary btn-sm rounded-pill fw-bold py-2 d-flex align-items-center justify-content-center gap-2">
                                    <i class="bi bi-box-arrow-in-right"></i>
                                    <span>Log In to Account</span>
                                </a>
                                <a href="<?= BASE_URL ?>/register.php" class="btn btn-outline-primary btn-sm rounded-pill fw-bold py-2 d-flex align-items-center justify-content-center gap-2">
                                    <i class="bi bi-person-plus-fill"></i>
                                    <span>Create Free Account</span>
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Main Navigation Links -->
                <div class="px-3 py-2 p-lg-0 me-lg-auto d-flex flex-column flex-lg-row align-items-lg-center">
                    <div class="small text-uppercase fw-bold text-muted px-2 mb-2 d-lg-none" style="font-size:0.72rem; letter-spacing:0.05em;">Navigation</div>
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0 align-items-lg-center mobile-drawer-nav">
                        <li class="nav-item w-100 w-lg-auto">
                            <a class="nav-link <?= $current_page === 'index.php' ? 'active font-weight-bold' : '' ?>" href="<?= BASE_URL ?>/index.php">
                                <span class="nav-icon-badge bg-primary-subtle text-primary d-lg-none"><i class="bi bi-house-door-fill"></i></span>
                                <span>Home</span>
                            </a>
                        </li>
                        <li class="nav-item w-100 w-lg-auto">
                            <a class="nav-link <?= $current_page === 'buy.php' ? 'active font-weight-bold' : '' ?>" href="<?= BASE_URL ?>/buy.php">
                                <span class="nav-icon-badge bg-info-subtle text-info d-lg-none"><i class="bi bi-laptop-fill"></i></span>
                                <span>Buy Laptops</span>
                                <span class="badge bg-primary-subtle text-primary rounded-pill ms-auto d-lg-none" style="font-size:0.68rem;">Explore</span>
                            </a>
                        </li>
                        <li class="nav-item w-100 w-lg-auto">
                            <a class="nav-link <?= $current_page === 'sell.php' ? 'active font-weight-bold' : '' ?>" href="<?= BASE_URL ?>/sell.php">
                                <span class="nav-icon-badge bg-success-subtle text-success d-lg-none"><i class="bi bi-plus-circle-fill"></i></span>
                                <span>Sell Laptop</span>
                                <span class="badge bg-success-subtle text-success rounded-pill ms-auto d-lg-none" style="font-size:0.68rem;">0% Fee</span>
                            </a>
                        </li>
                        <li class="nav-item w-100 w-lg-auto">
                            <a class="nav-link <?= $current_page === 'about.php' ? 'active font-weight-bold' : '' ?>" href="<?= BASE_URL ?>/about.php">
                                <span class="nav-icon-badge bg-warning-subtle text-warning d-lg-none"><i class="bi bi-info-circle-fill"></i></span>
                                <span>About Us</span>
                            </a>
                        </li>
                        <li class="nav-item w-100 w-lg-auto">
                            <a class="nav-link <?= $current_page === 'contact.php' ? 'active font-weight-bold' : '' ?>" href="<?= BASE_URL ?>/contact.php">
                                <span class="nav-icon-badge bg-danger-subtle text-danger d-lg-none"><i class="bi bi-headset"></i></span>
                                <span>Contact</span>
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Quick Brand Categories (Mobile Drawer Only) -->
                <div class="px-3 py-2 d-lg-none border-top border-secondary-subtle">
                    <div class="small text-uppercase fw-bold text-muted px-2 mb-2" style="font-size:0.72rem; letter-spacing:0.05em;">Popular Categories</div>
                    <div class="d-flex flex-wrap gap-1.5 px-1">
                        <a href="<?= BASE_URL ?>/buy.php?brand=1" class="mobile-cat-pill"><i class="bi bi-apple text-dark"></i> MacBooks</a>
                        <a href="<?= BASE_URL ?>/buy.php?search=RTX" class="mobile-cat-pill"><i class="bi bi-controller text-primary"></i> Gaming Rigs</a>
                        <a href="<?= BASE_URL ?>/buy.php?type=New" class="mobile-cat-pill"><i class="bi bi-patch-check-fill text-success"></i> Brand New</a>
                        <a href="<?= BASE_URL ?>/buy.php?type=Old" class="mobile-cat-pill"><i class="bi bi-tag-fill text-warning"></i> Used Deals</a>
                        <a href="<?= BASE_URL ?>/buy.php?brand=2" class="mobile-cat-pill"><i class="bi bi-cpu-fill text-info"></i> Dell XPS</a>
                        <a href="<?= BASE_URL ?>/buy.php?brand=4" class="mobile-cat-pill"><i class="bi bi-briefcase-fill text-secondary"></i> ThinkPad</a>
                    </div>
                </div>

                <!-- If Logged In: Quick Account Shortcuts in Drawer (Mobile Drawer Only) -->
                <?php if ($user): ?>
                <div class="px-3 py-2 d-lg-none border-top border-secondary-subtle">
                    <div class="small text-uppercase fw-bold text-muted px-2 mb-2" style="font-size:0.72rem; letter-spacing:0.05em;">My Account</div>
                    <div class="d-flex flex-column gap-1">
                        <?php if (($user['role'] ?? '') === 'admin'): ?>
                            <a href="<?= BASE_URL ?>/admin/dashboard.php" class="mobile-drawer-acc-link text-danger fw-bold">
                                <i class="bi bi-shield-lock-fill text-danger"></i>
                                <span>Admin Dashboard</span>
                            </a>
                        <?php endif; ?>
                        <a href="<?= BASE_URL ?>/dashboard.php" class="mobile-drawer-acc-link">
                            <i class="bi bi-speedometer2 text-primary"></i>
                            <span>User Dashboard</span>
                        </a>
                        <a href="<?= BASE_URL ?>/my-listings.php" class="mobile-drawer-acc-link">
                            <i class="bi bi-laptop text-info"></i>
                            <span>My Laptop Listings</span>
                        </a>
                        <a href="<?= BASE_URL ?>/my-queries.php" class="mobile-drawer-acc-link">
                            <i class="bi bi-chat-left-text text-warning"></i>
                            <span>Inquiries & Messages</span>
                        </a>
                        <a href="<?= BASE_URL ?>/profile.php" class="mobile-drawer-acc-link">
                            <i class="bi bi-person-gear text-secondary"></i>
                            <span>Profile & Settings</span>
                        </a>
                        <a href="<?= BASE_URL ?>/logout.php" class="mobile-drawer-acc-link text-danger">
                            <i class="bi bi-box-arrow-right text-danger"></i>
                            <span>Sign Out</span>
                        </a>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Mobile Drawer Footer (Legal & Safety) -->
                <div class="mt-auto p-3 d-lg-none border-top border-secondary-subtle bg-light-subtle">
                    <div class="d-flex align-items-center justify-content-between small text-muted mb-2 px-1">
                        <a href="<?= BASE_URL ?>/privacy.php" class="text-muted text-decoration-none">Privacy Policy</a>
                        <span>&bull;</span>
                        <a href="<?= BASE_URL ?>/terms.php" class="text-muted text-decoration-none">Terms of Service</a>
                        <span>&bull;</span>
                        <a href="<?= BASE_URL ?>/about.php" class="text-muted text-decoration-none">About</a>
                    </div>
                    <div class="text-center small text-muted opacity-75" style="font-size:0.72rem;">
                        &copy; <?= date('Y') ?> Lapify &bull; Peer-to-Peer Laptop Hub
                    </div>
                </div>

                <!-- Auth Action Buttons / Dropdown (Desktop Only) -->
                <div class="d-none d-lg-flex navbar-actions align-items-lg-center ms-lg-auto">

                    <?php if ($user && ($user['role'] ?? '') === 'admin' && !empty($_SESSION['admin_id'])): ?>
                        <a href="<?= BASE_URL ?>/admin/dashboard.php" class="btn btn-admin-back d-inline-flex align-items-center gap-2 me-3" title="Back to Admin Dashboard">
                            <i class="bi bi-shield-lock-fill fs-5"></i>
                            <span class="fw-semibold">Back to Admin</span>
                        </a>
                    <?php endif; ?>

                    <?php if ($user): ?>
                        <!-- Cart Quick Icon -->
                        <a href="<?= BASE_URL ?>/cart.php" class="btn btn-light position-relative me-2 border-0 rounded-circle d-inline-flex align-items-center justify-content-center btn-quick-icon" title="My Cart">
                            <i class="bi bi-cart3"></i>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary cart-count-badge <?= $cart_count > 0 ? '' : 'd-none' ?>">
                                <?= $cart_count ?>
                            </span>
                        </a>

                        <!-- Wishlist Quick Icon -->
                        <a href="<?= BASE_URL ?>/wishlist.php" class="btn btn-light position-relative me-2 border-0 rounded-circle d-inline-flex align-items-center justify-content-center btn-quick-icon" title="Wishlist">
                            <i class="bi bi-heart-fill"></i>
                            <span id="wishlist-count" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger wishlist-count-badge <?= $wishlist_count > 0 ? '' : 'd-none' ?>">
                                <?= $wishlist_count ?>
                            </span>
                        </a>

                        <a href="<?= BASE_URL ?>/orders.php" class="btn btn-light position-relative border-0 rounded-circle d-inline-flex align-items-center justify-content-center btn-quick-icon" title="My Orders">
                            <i class="bi bi-bag-check-fill"></i>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary order-count-badge <?= $order_count > 0 ? '' : 'd-none' ?>">
                                <?= (int)$order_count ?>
                            </span>
                        </a>

                        <!-- User Dropdown Menu (avatar only) -->
                        <div class="dropdown">
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
                                    <div class="fw-bold user-menu-name"><?= escape($user['full_name']) ?></div>
                                    <div class="small user-menu-email"><?= escape($user['email']) ?></div>
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
                                    <a class="dropdown-item" href="<?= BASE_URL ?>/my-queries.php">
                                        <i class="bi bi-chat-left-text me-2"></i>My Inquiries & Replies
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="<?= BASE_URL ?>/profile.php">
                                        <i class="bi bi-person-gear me-2"></i>Profile & Settings
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item dropdown-item-logout text-danger" href="<?= BASE_URL ?>/logout.php">
                                        <i class="bi bi-box-arrow-right me-2"></i>Logout
                                    </a>
                                </li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <div class="d-flex gap-2 align-items-center">
                            <a href="<?= BASE_URL ?>/login.php" class="btn btn-navbar-login">Log In</a>
                            <a href="<?= BASE_URL ?>/register.php" class="btn btn-navbar-register">Register</a>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>
</nav>
