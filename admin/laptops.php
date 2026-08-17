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
                $sqlIns = "INSERT INTO laptops (user_id, brand_id, type, model, processor, ram, storage, `condition`, price, description, image, quantity, status, approval_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending')";
                $ins = mysqli_prepare($conn, $sqlIns);
                if ($ins === false) {
                    $err = mysqli_error($conn);
                    error_log("DB prepare failed (insert_laptop): {$err} SQL: {$sqlIns}");
                    setFlash('error', 'Database error while creating listing.');
                } else {
                    $uid = $current_admin['id'] ?? intval($_SESSION['user_id'] ?? 0);
                    $priceVal = $price;
                    mysqli_stmt_bind_param($ins, 'iissssssdssi', $uid, $brand_id, $type, $model, $processor, $ram, $storage, $condition, $priceVal, $description, $image_name, $quantity);
                    if (mysqli_stmt_execute($ins)) {
                        setFlash('success', 'Laptop listing created.');
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

$sql = "SELECT l.*, b.brand_name, u.full_name AS seller_name 
        FROM laptops l 
        JOIN brands b ON l.brand_id = b.id 
        JOIN users u ON l.user_id = u.id 
        WHERE {$where_sql} 
        ORDER BY l.id DESC";

$stmt = mysqli_prepare($conn, $sql);
if (!empty($param_types)) {
    mysqli_stmt_bind_param($stmt, $param_types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Brands list for dropdown
$brands_list = mysqli_query($conn, "SELECT * FROM brands ORDER BY brand_name ASC");
?>

<div class="dashboard-wrapper">
    <?php require_once __DIR__ . '/sidebar.php'; ?>

    <div class="dashboard-content">
        <?php require_once __DIR__ . '/topbar.php'; ?>
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
            <div>
                <h3 class="fw-bold mb-1"><i class="bi bi-laptop-fill text-primary me-2"></i>Laptop Listings Management</h3>
                <p class="text-muted mb-0">Monitor, change status, and moderate all platform ads</p>
            </div>
            <button type="button" class="btn btn-primary font-weight-bold rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addLaptopModal">
                <i class="bi bi-plus-lg me-1"></i>Add Laptop
            </button>
        </div>

        <!-- Filter Bar -->
        <div class="card border-0 shadow-sm rounded-4 p-3 mb-4">
            <form action="laptops.php" method="GET" class="row g-2 align-items-center">
                <div class="col-md-5">
                    <input type="text" name="search" class="form-control form-control-sm" value="<?= escape($search) ?>" placeholder="Search model, seller name...">
                </div>
                <div class="col-md-3">
                    <select name="brand" class="form-select form-select-sm">
                        <option value="0">All Brands</option>
                        <?php while ($b = mysqli_fetch_assoc($brands_list)): ?>
                            <option value="<?= $b['id'] ?>" <?= $brand_id == $b['id'] ? 'selected' : '' ?>><?= escape($b['brand_name']) ?></option>
                        <?php endwhile; ?>
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
                    <button type="submit" class="btn btn-sm btn-primary w-100 font-weight-bold">Filter</button>
                </div>
            </form>
        </div>

        <?php displayFlash(); ?>

        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-muted small text-uppercase font-weight-bold">
                            <tr>
                                <th class="ps-4">Laptop Model</th>
                                <th>Type</th>
                                <th>Price</th>
                                <th>Seller</th>
                                <th>Status</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($result) > 0): ?>
                                <?php while ($laptop = mysqli_fetch_assoc($result)): 
                                    // Resolve laptop image using helper that handles DB filenames,
                                    // slug-based candidates, and fuzzy matches in uploads.
                                    $resolvedImg = getLaptopImageUrl($laptop);
                                    $img_src = $resolvedImg ?: 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?auto=format&fit=crop&w=200&q=80';
                                ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center gap-3">
                                                <img src="<?= escape($img_src) ?>" alt="" class="rounded-3" style="width: 50px; height: 38px; object-fit: cover;">
                                                <div>
                                                    <div class="fw-bold text-dark"><?= escape($laptop['model']) ?></div>
                                                    <div class="small text-muted"><?= escape($laptop['brand_name']) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge <?= $laptop['type'] === 'New' ? 'badge-type-new' : 'badge-type-old' ?>">
                                                <?= escape($laptop['type']) ?>
                                            </span>
                                        </td>
                                        <td class="fw-bold text-primary"><?= formatPrice($laptop['price']) ?></td>
                                        <td class="small text-secondary"><?= escape($laptop['seller_name']) ?></td>
                                        <td>
                                            <?php $listing_status = strtolower((string)($laptop['status'] ?? $laptop['approval_status'] ?? 'pending')); ?>
                                            <div class="dropdown">
                                                <button class="btn btn-sm dropdown-toggle rounded-pill border-0 <?= $listing_status === 'approved' ? 'bg-success-subtle text-success fw-bold' : ($listing_status === 'rejected' ? 'bg-danger-subtle text-danger fw-bold' : 'bg-warning-subtle text-warning fw-bold') ?>" type="button" data-bs-toggle="dropdown">
                                                    <?= escape(ucfirst($listing_status)) ?>
                                                </button>
                                                <ul class="dropdown-menu shadow-sm border-0 small">
                                                    <li><a class="dropdown-item" href="laptops.php?action=status&id=<?= $laptop['id'] ?>&status=pending">Pending</a></li>
                                                    <li><a class="dropdown-item" href="laptops.php?action=status&id=<?= $laptop['id'] ?>&status=approved">Approved</a></li>
                                                    <li><a class="dropdown-item" href="laptops.php?action=status&id=<?= $laptop['id'] ?>&status=rejected">Rejected</a></li>
                                                </ul>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-inline-flex align-items-center justify-content-center gap-2">
                                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editLaptopModal" data-laptop-id="<?= $laptop['id'] ?>" data-brand-id="<?= $laptop['brand_id'] ?>" data-type="<?= escape($laptop['type']) ?>" data-model="<?= escape($laptop['model']) ?>" data-processor="<?= escape($laptop['processor'] ?? '') ?>" data-ram="<?= escape($laptop['ram'] ?? '') ?>" data-storage="<?= escape($laptop['storage'] ?? '') ?>" data-condition="<?= escape($laptop['condition'] ?? '') ?>" data-price="<?= (float) $laptop['price'] ?>" data-description="<?= escape($laptop['description'] ?? '') ?>" data-quantity="<?= (int) $laptop['quantity'] ?>" title="Edit Listing">
                                                    <i class="bi bi-pencil-square"></i> Edit
                                                </button>
                                                <a href="<?= BASE_URL ?>/laptop-details.php?id=<?= $laptop['id'] ?>" class="btn btn-sm btn-light" target="_blank" title="View"><i class="bi bi-eye"></i></a>
                                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                                        data-bs-toggle="modal" data-bs-target="#deleteConfirmModal" 
                                                        data-id="<?= $laptop['id'] ?>" 
                                                        data-title="<?= escape($laptop['model']) ?>" 
                                                        data-delete-url="laptops.php?action=delete&id=<?= $laptop['id'] ?>" 
                                                        title="Delete Listing">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">No laptop listings found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
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
                                <?php mysqli_data_seek($brands_list, 0); while ($b = mysqli_fetch_assoc($brands_list)): ?>
                                    <option value="<?= $b['id'] ?>"><?= escape($b['brand_name']) ?></option>
                                <?php endwhile; ?>
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
                                <?php mysqli_data_seek($brands_list, 0); while ($b = mysqli_fetch_assoc($brands_list)): ?>
                                    <option value="<?= $b['id'] ?>"><?= escape($b['brand_name']) ?></option>
                                <?php endwhile; ?>
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
