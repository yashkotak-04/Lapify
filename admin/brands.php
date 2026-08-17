<?php
// admin/brands.php - Brand Management
$admin_title = "Manage Brands | Lapify Admin";
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();

$conn = getDbConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_brand'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', "Session expired or invalid security token. Please try again.");
    } else {
        $brand_name = sanitizeInput($_POST['brand_name'] ?? '');
        if (empty($brand_name)) {
            setFlash('error', "Brand name cannot be empty.");
        } else {
            $chk_stmt = mysqli_prepare($conn, "SELECT id FROM brands WHERE brand_name = ?");
            mysqli_stmt_bind_param($chk_stmt, "s", $brand_name);
            mysqli_stmt_execute($chk_stmt);
            mysqli_stmt_store_result($chk_stmt);

            if (mysqli_stmt_num_rows($chk_stmt) > 0) {
                setFlash('error', "A brand named '{$brand_name}' already exists.");
            } else {
                $logo_path = null;
                if (!empty($_FILES['brand_logo']) && $_FILES['brand_logo']['error'] === UPLOAD_ERR_OK) {
                    $upload_err = '';
                    $uploaded = uploadImage($_FILES['brand_logo'], BRAND_UPLOAD_DIR, $upload_err);
                    if ($uploaded === false) {
                        setFlash('error', 'Logo upload failed: ' . $upload_err);
                    } else {
                        $logo_path = 'uploads/brands/' . $uploaded;
                    }
                }

                if (!isset($_SESSION['flash_error'])) {
                    $ins_stmt = mysqli_prepare($conn, "INSERT INTO brands (brand_name, logo_path, status) VALUES (?, ?, 'active')");
                    mysqli_stmt_bind_param($ins_stmt, "ss", $brand_name, $logo_path);
                    if (mysqli_stmt_execute($ins_stmt)) {
                        setFlash('success', "Brand '{$brand_name}' added successfully.");
                    } else {
                        setFlash('error', "Failed to add brand.");
                    }
                    mysqli_stmt_close($ins_stmt);
                }
            }
            mysqli_stmt_close($chk_stmt);
        }
    }
    header("Location: " . BASE_URL . "/admin/brands.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_brand'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Session expired or invalid security token.');
    } else {
        $brand_id = intval($_POST['brand_id'] ?? 0);
        $brand_name = sanitizeInput($_POST['brand_name'] ?? '');
        if ($brand_id <= 0 || $brand_name === '') {
            setFlash('error', 'Brand name is required.');
        } else {
            $current = mysqli_prepare($conn, "SELECT logo_path FROM brands WHERE id = ? LIMIT 1");
            mysqli_stmt_bind_param($current, 'i', $brand_id);
            mysqli_stmt_execute($current);
            mysqli_stmt_bind_result($current, $current_logo);
            mysqli_stmt_fetch($current);
            mysqli_stmt_close($current);

            $logo_path = $current_logo;
            if (!empty($_FILES['brand_logo']) && $_FILES['brand_logo']['error'] === UPLOAD_ERR_OK) {
                $upload_err = '';
                $uploaded = uploadImage($_FILES['brand_logo'], BRAND_UPLOAD_DIR, $upload_err);
                if ($uploaded === false) {
                    setFlash('error', 'Logo upload failed: ' . $upload_err);
                } else {
                    $logo_path = 'uploads/brands/' . $uploaded;
                    if (!empty($current_logo) && $current_logo !== $logo_path) {
                        deleteImageFile(basename($current_logo), BRAND_UPLOAD_DIR);
                    }
                }
            }

            if (!isset($_SESSION['flash_error'])) {
                $upd_stmt = mysqli_prepare($conn, "UPDATE brands SET brand_name = ?, logo_path = ? WHERE id = ?");
                mysqli_stmt_bind_param($upd_stmt, 'ssi', $brand_name, $logo_path, $brand_id);
                if (mysqli_stmt_execute($upd_stmt)) {
                    setFlash('success', 'Brand updated successfully.');
                } else {
                    setFlash('error', 'Failed to update brand.');
                }
                mysqli_stmt_close($upd_stmt);
            }
        }
    }
    header('Location: ' . BASE_URL . '/admin/brands.php');
    exit();
}

if (isset($_GET['action']) && $_GET['action'] === 'toggle_status' && isset($_GET['id'])) {
    $brand_id = intval($_GET['id']);
    $new_status = sanitizeInput($_GET['status'] ?? 'active');
    if (in_array($new_status, ['active', 'inactive'])) {
        $stmt = mysqli_prepare($conn, "UPDATE brands SET status = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "si", $new_status, $brand_id);
        if (mysqli_stmt_execute($stmt)) {
            setFlash('success', "Brand status updated.");
        }
        mysqli_stmt_close($stmt);
    }
    header("Location: " . BASE_URL . "/admin/brands.php");
    exit();
}

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $brand_id = intval($_GET['id']);

    $ref_stmt = mysqli_prepare($conn, "SELECT COUNT(*) FROM laptops WHERE brand_id = ?");
    mysqli_stmt_bind_param($ref_stmt, "i", $brand_id);
    mysqli_stmt_execute($ref_stmt);
    mysqli_stmt_bind_result($ref_stmt, $ref_count);
    mysqli_stmt_fetch($ref_stmt);
    mysqli_stmt_close($ref_stmt);

    if ($ref_count > 0) {
        setFlash('error', "Cannot delete this brand because {$ref_count} laptop listing(s) are currently referencing it.");
    } else {
        $brandInfo = mysqli_prepare($conn, "SELECT logo_path FROM brands WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($brandInfo, 'i', $brand_id);
        mysqli_stmt_execute($brandInfo);
        mysqli_stmt_bind_result($brandInfo, $brand_logo);
        mysqli_stmt_fetch($brandInfo);
        mysqli_stmt_close($brandInfo);

        $del_stmt = mysqli_prepare($conn, "DELETE FROM brands WHERE id = ?");
        mysqli_stmt_bind_param($del_stmt, "i", $brand_id);
        if (mysqli_stmt_execute($del_stmt)) {
            if (!empty($brand_logo)) {
                deleteImageFile(basename($brand_logo), BRAND_UPLOAD_DIR);
            }
            setFlash('success', "Brand deleted successfully.");
        } else {
            setFlash('error', "Failed to delete brand.");
        }
        mysqli_stmt_close($del_stmt);
    }
    header("Location: " . BASE_URL . "/admin/brands.php");
    exit();
}

$sql = "SELECT b.*, COUNT(l.id) AS laptop_count 
        FROM brands b 
        LEFT JOIN laptops l ON b.id = l.brand_id 
        GROUP BY b.id 
        ORDER BY b.brand_name ASC";
$brands_res = mysqli_query($conn, $sql);
?>

<div class="dashboard-wrapper">
    <?php require_once __DIR__ . '/sidebar.php'; ?>

    <div class="dashboard-content">
        <?php require_once __DIR__ . '/topbar.php'; ?>
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
            <div>
                <h3 class="fw-bold mb-1"><i class="bi bi-tags-fill text-primary me-2"></i>Brand Management</h3>
                <p class="text-muted mb-0">Manage laptop manufacturer brand list</p>
            </div>
            <button type="button" class="btn btn-primary font-weight-bold rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addBrandModal">
                <i class="bi bi-plus-lg me-1"></i>Add New Brand
            </button>
        </div>

        <?php displayFlash(); ?>

        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-muted small text-uppercase font-weight-bold">
                            <tr>
                                <th>Brand Name</th>
                                <th>Logo</th>
                                <th>Active Listings</th>
                                <th>Status</th>
                                <th class="pe-4 text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($b = mysqli_fetch_assoc($brands_res)): 
                                // Prefer explicit DB `logo_path` when the file exists; otherwise
                                // fall back to `getBrandLogoUrl()` which checks uploads and packaged assets
                                $brandLogo = null;
                                if (!empty($b['logo_path'])) {
                                    $possible = __DIR__ . '/../' . ltrim($b['logo_path'], '/');
                                    if (file_exists($possible)) {
                                        $brandLogo = BASE_URL . '/' . ltrim($b['logo_path'], '/');
                                    }
                                }
                                if (empty($brandLogo)) {
                                    $resolved = getBrandLogoUrl($b['brand_name']);
                                    if ($resolved) $brandLogo = $resolved;
                                }
                            ?>
                                <tr>
                                    <td class="fw-bold text-dark fs-6"><?= escape($b['brand_name']) ?></td>
                                    <td>
                                        <?php if (!empty($brandLogo)): ?>
                                            <img src="<?= escape($brandLogo) ?>" alt="<?= escape($b['brand_name']) ?> logo" style="height: 32px; width: auto; max-width: 60px; object-fit: contain;">
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted border"><i class="bi bi-image"></i></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge bg-light text-dark border px-3"><?= number_format($b['laptop_count']) ?> laptops</span></td>
                                    <td>
                                        <a href="brands.php?action=toggle_status&id=<?= $b['id'] ?>&status=<?= $b['status'] === 'active' ? 'inactive' : 'active' ?>" class="badge text-decoration-none <?= $b['status'] === 'active' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' ?> rounded-pill">
                                            <?= ucfirst($b['status']) ?>
                                        </a>
                                    </td>
                                    <td class="pe-4 text-end">
                                        <button type="button" class="btn btn-sm btn-outline-primary me-2" data-bs-toggle="modal" data-bs-target="#editBrandModal" data-brand-id="<?= $b['id'] ?>" data-brand-name="<?= escape($b['brand_name']) ?>" title="Edit Brand">
                                            <i class="bi bi-pencil-square"></i> Edit
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                                data-bs-toggle="modal" data-bs-target="#deleteConfirmModal" 
                                                data-id="<?= $b['id'] ?>" 
                                                data-title="<?= escape($b['brand_name']) ?>" 
                                                data-delete-url="brands.php?action=delete&id=<?= $b['id'] ?>" 
                                                title="Delete Brand">
                                            <i class="bi bi-trash"></i> Delete
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addBrandModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <form action="brands.php" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                <?= renderCsrfInput() ?>
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle text-primary me-2"></i>Add New Laptop Brand</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-4">
                    <div class="mb-3">
                        <label for="brand_name" class="form-label font-weight-bold">Brand Name <span class="text-danger">*</span></label>
                        <input type="text" name="brand_name" id="brand_name" class="form-control" placeholder="e.g. Razer, Microsoft Surface, Gigabyte" required>
                    </div>
                    <div class="mb-3">
                        <label for="brand_logo" class="form-label font-weight-bold">Brand Logo</label>
                        <input type="file" name="brand_logo" id="brand_logo" class="form-control" accept="image/png,image/jpeg,image/webp,image/svg+xml">
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-3 px-4 font-weight-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_brand" class="btn btn-primary rounded-3 px-4 font-weight-bold">Save Brand</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editBrandModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <form action="brands.php" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                <?= renderCsrfInput() ?>
                <input type="hidden" name="brand_id" id="edit_brand_id">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square text-primary me-2"></i>Edit Brand</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-4">
                    <div class="mb-3">
                        <label for="edit_brand_name" class="form-label font-weight-bold">Brand Name <span class="text-danger">*</span></label>
                        <input type="text" name="brand_name" id="edit_brand_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_brand_logo" class="form-label font-weight-bold">Brand Logo</label>
                        <input type="file" name="brand_logo" id="edit_brand_logo" class="form-control" accept="image/png,image/jpeg,image/webp,image/svg+xml">
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-3 px-4 font-weight-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_brand" class="btn btn-primary rounded-3 px-4 font-weight-bold">Update Brand</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i>Delete Brand</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-4">
                Are you sure you want to delete brand <strong class="modal-item-title text-dark"></strong>?
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light rounded-3 px-4 font-weight-bold" data-bs-dismiss="modal">Cancel</button>
                <a href="#" class="btn btn-danger btn-confirm-delete rounded-3 px-4 font-weight-bold">Delete Brand</a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
