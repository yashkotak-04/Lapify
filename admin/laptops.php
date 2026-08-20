<?php
// admin/laptops.php - Master Laptop Listings Management
$admin_title = "Manage Laptops | Lapify Admin";
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();
$current_admin = getCurrentUser();

$conn = getDbConnection();

// Status Update Action
if (isset($_GET['action']) && $_GET['action'] === 'status' && isset($_GET['id'])) {
    $laptop_id = intval($_GET['id']);
    $new_status = sanitizeInput($_GET['status'] ?? 'pending');
    if (in_array($new_status, ['pending', 'approved', 'rejected'], true)) {
        $reviewed_by = intval($_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? 0);
        $stmt = mysqli_prepare($conn, "UPDATE laptops SET status = ?, approval_status = ?, reviewed_by = ?, reviewed_at = NOW() WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "ssii", $new_status, $new_status, $reviewed_by, $laptop_id);
        if (mysqli_stmt_execute($stmt)) {
            $toastType = $new_status === 'rejected' ? 'error' : ($new_status === 'approved' ? 'success' : 'info');
            $toastMessage = $new_status === 'approved'
                ? 'Your laptop listing has been approved and is now live!'
                : ($new_status === 'rejected'
                    ? 'Your listing was rejected.'
                    : 'Listing status updated.');
            setFlash($toastType, $toastMessage);
        }
        mysqli_stmt_close($stmt);
    }
    header("Location: " . BASE_URL . "/admin/laptops.php");
    exit();
}

// Delete Listing Action
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $laptop_id = intval($_GET['id']);

    // Get image
    $img_stmt = mysqli_prepare($conn, "SELECT image FROM laptops WHERE id = ?");
    mysqli_stmt_bind_param($img_stmt, "i", $laptop_id);
    mysqli_stmt_execute($img_stmt);
    mysqli_stmt_bind_result($img_stmt, $filename);
    mysqli_stmt_fetch($img_stmt);
    mysqli_stmt_close($img_stmt);

    $del_stmt = mysqli_prepare($conn, "DELETE FROM laptops WHERE id = ?");
    mysqli_stmt_bind_param($del_stmt, "i", $laptop_id);
    if (mysqli_stmt_execute($del_stmt)) {
        deleteImageFile($filename, LAPTOP_UPLOAD_DIR);
        setFlash('success', "Laptop listing deleted successfully.");
    } else {
        setFlash('error', "Failed to delete laptop listing.");
    }
    mysqli_stmt_close($del_stmt);
    header("Location: " . BASE_URL . "/admin/laptops.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_laptop'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Session expired or invalid security token.');
    } else {
        $brand_id = intval($_POST['brand_id'] ?? 0);
        $type = sanitizeInput($_POST['type'] ?? 'New');
        $model = sanitizeInput($_POST['model'] ?? '');
        $processor = sanitizeInput($_POST['processor'] ?? '');
        $ram = sanitizeInput($_POST['ram'] ?? '');
        $storage = sanitizeInput($_POST['storage'] ?? '');
        $condition = sanitizeInput($_POST['condition'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $quantity = intval($_POST['quantity'] ?? 1);
        $description = sanitizeInput($_POST['description'] ?? '');

        if ($brand_id <= 0 || empty($model) || $price <= 0) {
            setFlash('error', 'Brand, model, and price are required.');
        } else {
            $image_name = null;
            if (!empty($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_err = '';
                $uploaded = uploadImage($_FILES['image'], LAPTOP_UPLOAD_DIR, $upload_err);
                if ($uploaded === false) {
                    setFlash('error', 'Image upload failed: ' . $upload_err);
                } else {
                    $image_name = $uploaded;
                }
            }

            if (!isset($_SESSION['flash_error'])) {
                // Ensure a valid user_id exists in the users table
                $uid = 0;
                $adminEmail = $current_admin['email'] ?? '';
                if (!empty($adminEmail)) {
                    $uCheck = mysqli_prepare($conn, "SELECT id FROM users WHERE LOWER(email) = LOWER(?) LIMIT 1");
                    mysqli_stmt_bind_param($uCheck, 's', $adminEmail);
                    mysqli_stmt_execute($uCheck);
                    $uRes = mysqli_stmt_get_result($uCheck);
                    if ($uRow = mysqli_fetch_assoc($uRes)) {
                        $uid = (int)$uRow['id'];
                    }
                    mysqli_stmt_close($uCheck);
                }

                if ($uid === 0 && !empty($current_admin['id'])) {
                    $adminId = (int)$current_admin['id'];
                    $uCheckId = mysqli_prepare($conn, "SELECT id FROM users WHERE id = ? LIMIT 1");
                    mysqli_stmt_bind_param($uCheckId, 'i', $adminId);
                    mysqli_stmt_execute($uCheckId);
                    $uResId = mysqli_stmt_get_result($uCheckId);
                    if ($uRowId = mysqli_fetch_assoc($uResId)) {
                        $uid = (int)$uRowId['id'];
                    }
                    mysqli_stmt_close($uCheckId);
                }

                if ($uid === 0) {
                    $adminName = $current_admin['full_name'] ?? 'Administrator';
                    $adminEmail = !empty($current_admin['email']) ? $current_admin['email'] : 'admin@lapify.com';
                    $adminPhone = $current_admin['phone'] ?? '';
                    $tempPass = password_hash('Lapify@Admin123', PASSWORD_DEFAULT);
                    $uIns = mysqli_prepare($conn, "INSERT INTO users (full_name, email, phone, password, role, status, created_at) VALUES (?, ?, ?, ?, 'admin', 'active', NOW())");
                    mysqli_stmt_bind_param($uIns, 'ssss', $adminName, $adminEmail, $adminPhone, $tempPass);
                    if (mysqli_stmt_execute($uIns)) {
                        $uid = mysqli_insert_id($conn);
                    } else {
                        $fallback = mysqli_query($conn, "SELECT id FROM users LIMIT 1");
                        if ($fbRow = mysqli_fetch_assoc($fallback)) {
                            $uid = (int)$fbRow['id'];
                        }
                    }
                    mysqli_stmt_close($uIns);
                }

                $sqlIns = "INSERT INTO laptops (user_id, brand_id, type, model, processor, ram, storage, `condition`, price, description, image, quantity, status, approval_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'approved', 'approved')";
                $ins = mysqli_prepare($conn, $sqlIns);
                if ($ins === false) {
                    $err = mysqli_error($conn);
                    error_log("DB prepare failed (insert_laptop): {$err} SQL: {$sqlIns}");
                    setFlash('error', 'Database error while creating listing.');
                } else {
                    $priceVal = $price;
                    mysqli_stmt_bind_param($ins, 'iissssssdssi', $uid, $brand_id, $type, $model, $processor, $ram, $storage, $condition, $priceVal, $description, $image_name, $quantity);
                    if (mysqli_stmt_execute($ins)) {
                        setFlash('success', 'Laptop listing created and published successfully.');
                    } else {
                        $err = mysqli_stmt_error($ins);
                        error_log("DB execute failed (insert_laptop): {$err}");
                        setFlash('error', 'Failed to create listing.');
                    }
                    mysqli_stmt_close($ins);
                }
            }
        }
    }
    header('Location: ' . BASE_URL . '/admin/laptops.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_laptop'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Session expired or invalid security token.');
    } else {
        $laptop_id = intval($_POST['laptop_id'] ?? 0);
        $brand_id = intval($_POST['brand_id'] ?? 0);
        $type = sanitizeInput($_POST['type'] ?? 'New');
        $model = sanitizeInput($_POST['model'] ?? '');
        $processor = sanitizeInput($_POST['processor'] ?? '');
        $ram = sanitizeInput($_POST['ram'] ?? '');
        $storage = sanitizeInput($_POST['storage'] ?? '');
        $condition = sanitizeInput($_POST['condition'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $quantity = intval($_POST['quantity'] ?? 1);
        $description = sanitizeInput($_POST['description'] ?? '');

        if ($laptop_id <= 0 || $brand_id <= 0 || empty($model) || $price <= 0) {
            setFlash('error', 'Missing required fields.');
        } else {
            $curImg = null;
            $s = mysqli_prepare($conn, "SELECT image FROM laptops WHERE id = ?");
            mysqli_stmt_bind_param($s, 'i', $laptop_id);
            mysqli_stmt_execute($s);
            mysqli_stmt_bind_result($s, $curImg);
            mysqli_stmt_fetch($s);
            mysqli_stmt_close($s);

            $newImage = $curImg;
            if (!empty($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
                $upload_err = '';
                $uploaded = uploadImage($_FILES['image'], LAPTOP_UPLOAD_DIR, $upload_err);
                if ($uploaded === false) {
                    setFlash('error', 'Image upload failed: ' . $upload_err);
                } else {
                    $newImage = $uploaded;
                }
            }

            if (!isset($_SESSION['flash_error'])) {
                $sqlUp = "UPDATE laptops SET brand_id = ?, type = ?, model = ?, processor = ?, ram = ?, storage = ?, `condition` = ?, price = ?, description = ?, image = ?, quantity = ? WHERE id = ?";
                $up = mysqli_prepare($conn, $sqlUp);
                if ($up === false) {
                    $err = mysqli_error($conn);
                    error_log("DB prepare failed (update_laptop): {$err} SQL: {$sqlUp}");
                    setFlash('error', 'Database error while updating listing.');
                } else {
                    $priceVal = $price;
                    mysqli_stmt_bind_param($up, 'issssssdssii', $brand_id, $type, $model, $processor, $ram, $storage, $condition, $priceVal, $description, $newImage, $quantity, $laptop_id);
                    if (mysqli_stmt_execute($up)) {
                        if ($newImage !== $curImg && !empty($curImg)) {
                            deleteImageFile($curImg, LAPTOP_UPLOAD_DIR);
                        }
                        setFlash('success', 'Laptop updated successfully.');
                    } else {
                        $err = mysqli_stmt_error($up);
                        error_log("DB execute failed (update_laptop): {$err}");
                        if ($newImage !== $curImg && !empty($newImage)) {
                            deleteImageFile($newImage, LAPTOP_UPLOAD_DIR);
                        }
                        setFlash('error', 'Failed to update laptop.');
                    }
                    mysqli_stmt_close($up);
                }
            }
        }
    }
    header('Location: ' . BASE_URL . '/admin/laptops.php');
    exit();
}

// Search & Filtering
$search = sanitizeInput($_GET['search'] ?? '');
$brand_id = intval($_GET['brand'] ?? 0);
$type = sanitizeInput($_GET['type'] ?? '');
$per_page = intval($_GET['per_page'] ?? 15);
if (!in_array($per_page, [10, 15, 25, 50, 100], true)) {
    $per_page = 15;
}
$page = max(1, intval($_GET['page'] ?? 1));

$where_clauses = ["1=1"];
$params = [];
$param_types = "";

if (!empty($search)) {
    $where_clauses[] = "(l.model LIKE ? OR u.full_name LIKE ? OR b.brand_name LIKE ?)";
    $s = "%{$search}%";
    $params[] = $s; $params[] = $s; $params[] = $s;
    $param_types .= "sss";
}

if ($brand_id > 0) {
    $where_clauses[] = "l.brand_id = ?";
    $params[] = $brand_id;
    $param_types .= "i";
}

if (!empty($type) && in_array($type, ['New', 'Old'])) {
    $where_clauses[] = "l.type = ?";
    $params[] = $type;
    $param_types .= "s";
}

$where_sql = implode(" AND ", $where_clauses);

// Count total laptops
$count_sql = "SELECT COUNT(*) 
              FROM laptops l 
              JOIN brands b ON l.brand_id = b.id 
              JOIN users u ON l.user_id = u.id 
              WHERE {$where_sql}";
$count_stmt = mysqli_prepare($conn, $count_sql);
if (!empty($param_types)) {
    mysqli_stmt_bind_param($count_stmt, $param_types, ...$params);
}
mysqli_stmt_execute($count_stmt);
mysqli_stmt_bind_result($count_stmt, $total_laptops);
mysqli_stmt_fetch($count_stmt);
mysqli_stmt_close($count_stmt);
$total_laptops = (int)($total_laptops ?? 0);
$total_pages = max(1, (int)ceil($total_laptops / $per_page));
$page = min($page, $total_pages);
$offset = ($page - 1) * $per_page;

$sql = "SELECT l.*, b.brand_name, u.full_name AS seller_name 
        FROM laptops l 
        JOIN brands b ON l.brand_id = b.id 
        JOIN users u ON l.user_id = u.id 
        WHERE {$where_sql} 
        ORDER BY l.id DESC
        LIMIT ? OFFSET ?";

$list_params = $params;
$list_params[] = $per_page;
$list_params[] = $offset;
$list_types = $param_types . "ii";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, $list_types, ...$list_params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Brands list for dropdown
$all_brands = [];
$brands_list = mysqli_query($conn, "SELECT * FROM brands ORDER BY brand_name ASC");
if ($brands_list) {
    while ($b = mysqli_fetch_assoc($brands_list)) {
        $all_brands[] = $b;
    }
}
?>

<div class="dashboard-wrapper">
    <?php require_once __DIR__ . '/sidebar.php'; ?>

    <div class="dashboard-content">
        <?php require_once __DIR__ . '/topbar.php'; ?>
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
            <div>
                <h3 class="fw-bold mb-1"><i class="bi bi-laptop-fill text-primary me-2"></i>Laptop Listings Management</h3>
                <p class="text-muted mb-0">Monitor, change status, and moderate all platform ads (<?= $total_laptops ?> total)</p>
            </div>
            <button type="button" class="btn btn-primary font-weight-bold rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addLaptopModal">
                <i class="bi bi-plus-lg me-1"></i>Add Laptop
            </button>
        </div>

        <!-- Filter Bar -->
        <div class="card border-0 shadow-sm rounded-4 p-3 mb-4">
            <form action="laptops.php" method="GET" class="row g-2 align-items-center">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control form-control-sm" value="<?= escape($search) ?>" placeholder="Search model, seller name...">
                </div>
                <div class="col-md-3">
                    <select name="brand" class="form-select form-select-sm">
                        <option value="0">All Brands</option>
                        <?php foreach ($all_brands as $b): ?>
                            <option value="<?= $b['id'] ?>" <?= $brand_id == $b['id'] ? 'selected' : '' ?>><?= escape($b['brand_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="type" class="form-select form-select-sm">
                        <option value="">All Types</option>
                        <option value="New" <?= $type === 'New' ? 'selected' : '' ?>>New</option>
                        <option value="Old" <?= $type === 'Old' ? 'selected' : '' ?>>Old</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="per_page" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="15" <?= $per_page == 15 ? 'selected' : '' ?>>15 per page</option>
                        <option value="25" <?= $per_page == 25 ? 'selected' : '' ?>>25 per page</option>
                        <option value="50" <?= $per_page == 50 ? 'selected' : '' ?>>50 per page</option>
                        <option value="100" <?= $per_page == 100 ? 'selected' : '' ?>>100 per page</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-sm btn-primary w-100 font-weight-bold">Filter</button>
                </div>
            </form>
        </div>

        <?php displayFlash(); ?>

        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-body p-3.5 p-md-4">
                <?php if ($total_laptops > 0): ?>
                    <div class="d-flex align-items-center justify-content-between mb-3 px-1">
                        <div class="text-muted small">
                            Showing <strong class="text-dark"><?= $offset + 1 ?>–<?= min($offset + $per_page, $total_laptops) ?></strong> of <strong class="text-dark"><?= $total_laptops ?></strong> laptops
                        </div>
                    </div>
                    <div class="d-flex flex-column gap-3.5">
                        <?php while ($laptop = mysqli_fetch_assoc($result)): 
                            $resolvedImg = getLaptopImageUrl($laptop);
                            $img_src = $resolvedImg ?: 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?auto=format&fit=crop&w=300&q=80';
                            $is_new = (strtolower((string)($laptop['type'] ?? '')) === 'new');
                            $listing_status = strtolower((string)($laptop['status'] ?? $laptop['approval_status'] ?? 'pending'));
                        ?>
                            <div class="posting-item-card p-3.5 p-md-4 rounded-4 border d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-4">
                                <!-- Left: Thumbnail & Laptop Info -->
                                <div class="d-flex align-items-center gap-3 gap-md-4" style="min-width: 0;">
                                    <img src="<?= escape($img_src) ?>" alt="<?= escape($laptop['model']) ?>" loading="lazy" decoding="async" class="posting-thumb rounded-3 border flex-shrink-0 me-3" style="width: 76px; height: 56px; object-fit: cover;">
                                    <div class="d-flex flex-column gap-1 ps-1" style="min-width: 0;">
                                        <h5 class="fw-bold mb-0 text-dark posting-title text-truncate" style="font-size: 1.08rem;" title="<?= escape($laptop['model']) ?>">
                                            <?= escape($laptop['model']) ?>
                                        </h5>
                                        <div class="d-flex flex-wrap align-items-center gap-2 text-muted small" style="font-size: 0.85rem;">
                                            <span class="fw-semibold text-secondary"><i class="bi bi-tag-fill text-primary me-1"></i><?= escape($laptop['brand_name'] ?? 'Laptop') ?></span>
                                            <span class="text-slate-300">•</span>
                                            <span class="text-dark fw-medium"><i class="bi bi-person-fill text-primary me-1"></i><?= escape($laptop['seller_name'] ?? 'Seller') ?></span>
                                            <span class="text-slate-300">•</span>
                                            <span class="badge rounded-pill px-2.5 py-1 <?= $is_new ? 'bg-primary-subtle text-primary' : 'bg-secondary-subtle text-secondary' ?>" style="font-size: 0.72rem; font-weight: 600;">
                                                <?= $is_new ? 'Brand New' : 'Pre-Owned' ?>
                                            </span>
                                            <?php if (!empty($laptop['condition'])): ?>
                                                <span class="text-slate-300">•</span>
                                                <span class="text-slate-600"><?= escape($laptop['condition']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Right: Price, Status, and Actions with generous spacing -->
                                <div class="d-flex flex-wrap align-items-center justify-content-between justify-content-lg-end gap-4 ms-lg-auto flex-shrink-0">
                                    <!-- Price -->
                                    <div class="text-start text-lg-end">
                                        <div class="fw-bold text-primary" style="font-size: 1.25rem;">
                                            <?= formatPrice($laptop['price']) ?>
                                        </div>
                                    </div>

                                    <!-- Status Select (Clean, Non-Overlapping) -->
                                    <?php
                                    $status_slug = in_array($listing_status, ['approved', 'active']) ? 'approved' : ($listing_status === 'rejected' ? 'rejected' : 'pending');
                                    ?>
                                    <div class="d-inline-block">
                                        <select class="status-select-pill status-select-<?= $status_slug ?>" onchange="location.href='laptops.php?action=status&id=<?= $laptop['id'] ?>&status=' + this.value;" title="Change Listing Status">
                                            <option value="pending" <?= $status_slug === 'pending' ? 'selected' : '' ?>>Pending</option>
                                            <option value="approved" <?= $status_slug === 'approved' ? 'selected' : '' ?>>Approved</option>
                                            <option value="rejected" <?= $status_slug === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                                        </select>
                                    </div>

                                    <!-- Action Buttons with generous spacing -->
                                    <div class="d-flex align-items-center gap-2">
                                        <button type="button" class="btn btn-outline-primary rounded-pill px-4 py-2 fw-bold d-inline-flex align-items-center gap-2 shadow-2xs" data-bs-toggle="modal" data-bs-target="#editLaptopModal" data-laptop-id="<?= $laptop['id'] ?>" data-brand-id="<?= $laptop['brand_id'] ?>" data-type="<?= escape($laptop['type']) ?>" data-model="<?= escape($laptop['model']) ?>" data-processor="<?= escape($laptop['processor'] ?? '') ?>" data-ram="<?= escape($laptop['ram'] ?? '') ?>" data-storage="<?= escape($laptop['storage'] ?? '') ?>" data-condition="<?= escape($laptop['condition'] ?? '') ?>" data-price="<?= (float) $laptop['price'] ?>" data-description="<?= escape($laptop['description'] ?? '') ?>" data-quantity="<?= (int) $laptop['quantity'] ?>" title="Edit Listing">
                                            <i class="bi bi-pencil-square"></i>
                                            <span>Edit</span>
                                        </button>
                                        <a href="<?= BASE_URL ?>/laptop-details.php?id=<?= $laptop['id'] ?>" class="btn btn-light border rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;" target="_blank" title="View Listing">
                                            <i class="bi bi-eye text-secondary"></i>
                                        </a>
                                        <button type="button" class="btn btn-light border text-danger rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;"
                                                data-bs-toggle="modal" data-bs-target="#deleteConfirmModal" 
                                                data-id="<?= $laptop['id'] ?>" 
                                                data-title="<?= escape($laptop['model']) ?>" 
                                                data-delete-url="laptops.php?action=delete&id=<?= $laptop['id'] ?>" 
                                                title="Delete Listing">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>

                    <?php if ($total_pages > 1): ?>
                        <nav class="mt-4 d-flex justify-content-center" aria-label="Laptops pagination">
                            <ul class="pagination pagination-sm gap-1">
                                <?php
                                $query_params = [
                                    'search' => $search,
                                    'brand' => $brand_id > 0 ? $brand_id : '',
                                    'type' => $type,
                                    'per_page' => $per_page,
                                ];
                                $query_params = array_filter($query_params, fn($v) => $v !== '' && $v !== null && $v !== 0);
                                
                                $prev_url = 'laptops.php?' . http_build_query(array_merge($query_params, ['page' => max(1, $page - 1)]));
                                $next_url = 'laptops.php?' . http_build_query(array_merge($query_params, ['page' => min($total_pages, $page + 1)]));
                                ?>
                                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                    <a class="page-link rounded-3" href="<?= $prev_url ?>"><i class="bi bi-chevron-left me-1"></i>Previous</a>
                                </li>
                                <?php 
                                $start_p = max(1, $page - 2);
                                $end_p = min($total_pages, $page + 2);
                                if ($start_p > 1) {
                                    echo '<li class="page-item"><a class="page-link rounded-3" href="laptops.php?' . http_build_query(array_merge($query_params, ['page' => 1])) . '">1</a></li>';
                                    if ($start_p > 2) echo '<li class="page-item disabled"><span class="page-link border-0">…</span></li>';
                                }
                                for ($p = $start_p; $p <= $end_p; $p++): 
                                    $p_url = 'laptops.php?' . http_build_query(array_merge($query_params, ['page' => $p]));
                                ?>
                                    <li class="page-item <?= $page === $p ? 'active' : '' ?>">
                                        <a class="page-link rounded-3 <?= $page === $p ? 'fw-bold' : '' ?>" href="<?= $p_url ?>"><?= $p ?></a>
                                    </li>
                                <?php endfor; 
                                if ($end_p < $total_pages) {
                                    if ($end_p < $total_pages - 1) echo '<li class="page-item disabled"><span class="page-link border-0">…</span></li>';
                                    echo '<li class="page-item"><a class="page-link rounded-3" href="laptops.php?' . http_build_query(array_merge($query_params, ['page' => $total_pages])) . '">' . $total_pages . '</a></li>';
                                }
                                ?>
                                <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                                    <a class="page-link rounded-3" href="<?= $next_url ?>">Next<i class="bi bi-chevron-right ms-1"></i></a>
                                </li>
                            </ul>
                        </nav>
                    <?php endif; ?>

                <?php else: ?>
                    <div class="text-center py-5 px-4">
                        <div class="bg-primary-subtle text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 64px; height: 64px;">
                            <i class="bi bi-laptop fs-2"></i>
                        </div>
                        <h5 class="fw-bold mb-1">No Laptop Listings Found</h5>
                        <p class="text-muted small mb-0">Try changing your search or filter parameters.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addLaptopModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <form action="laptops.php" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                <?= renderCsrfInput() ?>
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle text-primary me-2"></i>Add Laptop</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="add_brand_id" class="form-label font-weight-bold">Brand <span class="text-danger">*</span></label>
                            <select name="brand_id" id="add_brand_id" class="form-select" required>
                                <option value="">Select brand</option>
                                <?php foreach ($all_brands as $b): ?>
                                    <option value="<?= $b['id'] ?>"><?= escape($b['brand_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="add_type" class="form-label font-weight-bold">Type <span class="text-danger">*</span></label>
                            <select name="type" id="add_type" class="form-select" required>
                                <option value="New">New</option>
                                <option value="Old">Old</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="add_model" class="form-label font-weight-bold">Model <span class="text-danger">*</span></label>
                            <input type="text" name="model" id="add_model" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label for="add_processor" class="form-label font-weight-bold">Processor</label>
                            <input type="text" name="processor" id="add_processor" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label for="add_ram" class="form-label font-weight-bold">RAM</label>
                            <input type="text" name="ram" id="add_ram" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label for="add_storage" class="form-label font-weight-bold">Storage</label>
                            <input type="text" name="storage" id="add_storage" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label for="add_condition" class="form-label font-weight-bold">Condition</label>
                            <input type="text" name="condition" id="add_condition" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label for="add_price" class="form-label font-weight-bold">Price <span class="text-danger">*</span></label>
                            <input type="number" name="price" id="add_price" class="form-control" min="1" step="0.01" required>
                        </div>
                        <div class="col-md-4">
                            <label for="add_quantity" class="form-label font-weight-bold">Quantity</label>
                            <input type="number" name="quantity" id="add_quantity" class="form-control" min="1" value="1">
                        </div>
                        <div class="col-md-4">
                            <label for="add_image" class="form-label font-weight-bold">Image</label>
                            <input type="file" name="image" id="add_image" class="form-control" accept="image/png,image/jpeg,image/webp,image/svg+xml">
                        </div>
                        <div class="col-12">
                            <label for="add_description" class="form-label font-weight-bold">Description</label>
                            <textarea name="description" id="add_description" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-3 px-4 font-weight-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="create_laptop" class="btn btn-primary rounded-3 px-4 font-weight-bold">Save Laptop</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editLaptopModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <form action="laptops.php" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                <?= renderCsrfInput() ?>
                <input type="hidden" name="laptop_id" id="edit_laptop_id">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square text-primary me-2"></i>Edit Laptop</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="edit_brand_id" class="form-label font-weight-bold">Brand <span class="text-danger">*</span></label>
                            <select name="brand_id" id="edit_brand_id" class="form-select" required>
                                <option value="">Select brand</option>
                                <?php foreach ($all_brands as $b): ?>
                                    <option value="<?= $b['id'] ?>"><?= escape($b['brand_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_type" class="form-label font-weight-bold">Type <span class="text-danger">*</span></label>
                            <select name="type" id="edit_type" class="form-select" required>
                                <option value="New">New</option>
                                <option value="Old">Old</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_model" class="form-label font-weight-bold">Model <span class="text-danger">*</span></label>
                            <input type="text" name="model" id="edit_model" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_processor" class="form-label font-weight-bold">Processor</label>
                            <input type="text" name="processor" id="edit_processor" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label for="edit_ram" class="form-label font-weight-bold">RAM</label>
                            <input type="text" name="ram" id="edit_ram" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label for="edit_storage" class="form-label font-weight-bold">Storage</label>
                            <input type="text" name="storage" id="edit_storage" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label for="edit_condition" class="form-label font-weight-bold">Condition</label>
                            <input type="text" name="condition" id="edit_condition" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label for="edit_price" class="form-label font-weight-bold">Price <span class="text-danger">*</span></label>
                            <input type="number" name="price" id="edit_price" class="form-control" min="1" step="0.01" required>
                        </div>
                        <div class="col-md-4">
                            <label for="edit_quantity" class="form-label font-weight-bold">Quantity</label>
                            <input type="number" name="quantity" id="edit_quantity" class="form-control" min="1">
                        </div>
                        <div class="col-md-4">
                            <label for="edit_image" class="form-label font-weight-bold">Replace Image</label>
                            <input type="file" name="image" id="edit_image" class="form-control" accept="image/png,image/jpeg,image/webp,image/svg+xml">
                        </div>
                        <div class="col-12">
                            <label for="edit_description" class="form-label font-weight-bold">Description</label>
                            <textarea name="description" id="edit_description" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-3 px-4 font-weight-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_laptop" class="btn btn-primary rounded-3 px-4 font-weight-bold">Update Laptop</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Confirmation for Delete -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i>Delete Laptop Listing</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-4">
                Are you sure you want to delete listing <strong class="modal-item-title text-dark"></strong>?
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light rounded-3 px-4 font-weight-bold" data-bs-dismiss="modal">Cancel</button>
                <a href="#" class="btn btn-danger btn-confirm-delete rounded-3 px-4 font-weight-bold">Delete Listing</a>
            </div>
        </div>
    </div>
</div>

<?php 
mysqli_stmt_close($stmt);
require_once __DIR__ . '/footer.php'; 
?>
