<?php
// admin/brands.php - Brand Management
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();

$conn = getDbConnection();

// Ensure brand upload directory exists
if (!is_dir(BRAND_UPLOAD_DIR)) {
    @mkdir(BRAND_UPLOAD_DIR, 0777, true);
}

// Ensure logo_path column exists in brands table safely
$checkCol = mysqli_query($conn, "SHOW COLUMNS FROM brands LIKE 'logo_path'");
if ($checkCol && mysqli_num_rows($checkCol) === 0) {
    @mysqli_query($conn, "ALTER TABLE brands ADD COLUMN logo_path VARCHAR(255) DEFAULT NULL AFTER brand_name");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_brand'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', "Session expired or invalid security token. Please try again.");
    } else {
        $brand_name = sanitizeInput($_POST['brand_name'] ?? '');
        if (empty($brand_name)) {
            setFlash('error', "Brand name cannot be empty.");
        } else {
            $chk_stmt = mysqli_prepare($conn, "SELECT id FROM brands WHERE LOWER(brand_name) = LOWER(?)");
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

// Calculate overall stats
$total_brands_res = mysqli_query($conn, "SELECT COUNT(*) as total, SUM(CASE WHEN status='active' THEN 1 ELSE 0 END) as active_count FROM brands");
$total_brands_data = mysqli_fetch_assoc($total_brands_res);
$total_brands = (int)($total_brands_data['total'] ?? 0);
$active_brands = (int)($total_brands_data['active_count'] ?? 0);

$total_listings_res = mysqli_query($conn, "SELECT COUNT(*) as total FROM laptops");
$total_listings_data = mysqli_fetch_assoc($total_listings_res);
$total_listings = (int)($total_listings_data['total'] ?? 0);

// Filter & Search
$search = sanitizeInput($_GET['search'] ?? '');
$status_filter = sanitizeInput($_GET['status_filter'] ?? '');

$where_clauses = ["1=1"];
$params = [];
$param_types = "";

if (!empty($search)) {
    $where_clauses[] = "b.brand_name LIKE ?";
    $params[] = "%{$search}%";
    $param_types .= "s";
}

if (!empty($status_filter) && in_array($status_filter, ['active', 'inactive'])) {
    $where_clauses[] = "b.status = ?";
    $params[] = $status_filter;
    $param_types .= "s";
}

$where_sql = implode(" AND ", $where_clauses);

$sql = "SELECT b.*, COUNT(l.id) AS laptop_count 
        FROM brands b 
        LEFT JOIN laptops l ON b.id = l.brand_id 
        WHERE {$where_sql}
        GROUP BY b.id 
        ORDER BY b.brand_name ASC";

if (!empty($params)) {
    $stmt_filter = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt_filter, $param_types, ...$params);
    mysqli_stmt_execute($stmt_filter);
    $brands_res = mysqli_stmt_get_result($stmt_filter);
} else {
    $brands_res = mysqli_query($conn, $sql);
}

$showing_count = mysqli_num_rows($brands_res);

// Include admin header only after all redirects/POST actions are processed
$admin_title = "Manage Brands | Lapify Admin";
require_once __DIR__ . '/header.php';
?>

<div class="dashboard-wrapper">
    <?php require_once __DIR__ . '/sidebar.php'; ?>

    <div class="dashboard-content">
        <?php require_once __DIR__ . '/topbar.php'; ?>

        <!-- Page Header -->
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
            <div>
                <h3 class="fw-bold mb-1"><i class="bi bi-tags-fill text-primary me-2"></i>Brand Management</h3>
                <p class="text-muted mb-0">Manage manufacturer brand catalog, logos, and active catalog inventory</p>
            </div>
            <button type="button" class="btn btn-primary font-weight-bold rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#addBrandModal">
                <i class="bi bi-plus-lg me-1.5"></i>Add New Brand
            </button>
        </div>

        <?php displayFlash(); ?>

        <!-- Search Bar & Status Filter Pills Toolbar -->
        <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
            <!-- Left: Search Input -->
            <div class="brand-search-input-wrap flex-grow-1" style="max-width: 480px;">
                <i class="bi bi-search search-icon"></i>
                <input type="text" name="search" id="brandSearchInput" class="form-control brand-search-input" value="<?= escape($search) ?>" placeholder="Search brands by name (e.g. Apple, Dell, Asus, HP)..." autocomplete="off">
                <?php if (!empty($search)): ?>
                    <a href="brands.php<?= !empty($status_filter) ? '?status_filter=' . urlencode($status_filter) : '' ?>" class="brand-search-clear-btn" title="Clear Search"><i class="bi bi-x-circle-fill"></i></a>
                <?php endif; ?>
            </div>

            <!-- Right: Status Filter Pills -->
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <a href="brands.php<?= !empty($search) ? '?search=' . urlencode($search) : '' ?>" class="brand-filter-pill <?= empty($status_filter) ? 'active' : '' ?>">
                    <span>All Brands</span>
                    <span class="pill-count"><?= $total_brands ?></span>
                </a>
                <a href="brands.php?status_filter=active<?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" class="brand-filter-pill <?= $status_filter === 'active' ? 'active' : '' ?>">
                    <span class="status-indicator-dot bg-success me-1"></span>
                    <span>Active</span>
                    <span class="pill-count"><?= $active_brands ?></span>
                </a>
                <a href="brands.php?status_filter=inactive<?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" class="brand-filter-pill <?= $status_filter === 'inactive' ? 'active' : '' ?>">
                    <span class="status-indicator-dot bg-secondary me-1"></span>
                    <span>Inactive</span>
                    <span class="pill-count"><?= $total_brands - $active_brands ?></span>
                </a>
            </div>
        </div>

        <!-- Brands Card Container -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-transparent p-4 border-bottom d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-grid-fill text-primary fs-5"></i>
                    <h5 class="fw-bold mb-0 text-dark">Manufacturer Brands</h5>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2.5 py-1 small fw-semibold">
                        <?= number_format($showing_count) ?> <?= $showing_count === 1 ? 'Brand' : 'Brands' ?>
                    </span>
                </div>
                <?php if (!empty($search) || !empty($status_filter)): ?>
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-muted small">Filtered Results</span>
                        <a href="brands.php" class="btn btn-sm btn-light border rounded-pill px-2.5 py-0.5 text-muted small" title="Reset All Filters"><i class="bi bi-x-lg me-1"></i>Reset</a>
                    </div>
                <?php endif; ?>
            </div>

            <div class="card-body p-3.5 p-md-4">
                <?php if ($showing_count > 0): ?>
                    <div class="d-flex flex-column gap-3 brand-cards-list">
                        <?php while ($b = mysqli_fetch_assoc($brands_res)): 
                            $brandLogo = getBrandLogoUrl($b);
                            $isActive = ($b['status'] === 'active');
                        ?>
                            <div class="brand-item-card p-3.5 p-md-4 rounded-4 border d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 gap-md-4" data-brand-name="<?= escape(strtolower($b['brand_name'])) ?>">
                                <!-- Left: Logo & Brand Info with ample spacing -->
                                <div class="d-flex align-items-center gap-4" style="min-width: 0;">
                                    <div class="brand-logo-tile flex-shrink-0">
                                        <?php if (!empty($brandLogo)): ?>
                                            <img src="<?= escape($brandLogo) ?>" alt="<?= escape($b['brand_name']) ?> logo">
                                        <?php else: ?>
                                            <i class="bi bi-image text-muted fs-5"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="d-flex flex-column gap-1.5 ps-2" style="min-width: 0;">
                                        <h5 class="fw-bold mb-0 text-dark brand-name-title" style="font-size: 1.18rem; letter-spacing: -0.01em;" title="<?= escape($b['brand_name']) ?>">
                                            <?= escape($b['brand_name']) ?>
                                        </h5>
                                        <div class="d-flex align-items-center gap-2 text-muted small">
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1 fw-semibold" style="font-size: 0.78rem;">
                                                <i class="bi bi-laptop me-1"></i><?= number_format($b['laptop_count']) ?> <?= $b['laptop_count'] === 1 ? 'laptop' : 'laptops' ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Right: Status Toggle & Action Buttons -->
                                <div class="d-flex align-items-center justify-content-between justify-content-md-end gap-3 ms-md-auto flex-shrink-0">
                                    <!-- Status Badge -->
                                    <a href="brands.php?action=toggle_status&id=<?= $b['id'] ?>&status=<?= $isActive ? 'inactive' : 'active' ?>" 
                                       class="badge rounded-pill px-3 py-2 text-decoration-none d-inline-flex align-items-center gap-1.5 <?= $isActive ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-secondary-subtle text-secondary border border-secondary-subtle' ?> brand-status-toggle" 
                                       title="Click to toggle status (currently <?= ucfirst($b['status']) ?>)">
                                        <span class="status-indicator-dot <?= $isActive ? 'bg-success' : 'bg-secondary' ?>"></span>
                                        <span class="fw-semibold"><?= ucfirst($b['status']) ?></span>
                                    </a>

                                    <!-- Action Buttons -->
                                    <div class="d-flex align-items-center gap-2">
                                        <button type="button" class="btn btn-outline-primary rounded-pill px-3.5 py-1.5 fw-semibold d-inline-flex align-items-center gap-1.5 shadow-2xs" data-bs-toggle="modal" data-bs-target="#editBrandModal" data-brand-id="<?= $b['id'] ?>" data-brand-name="<?= escape($b['brand_name']) ?>" title="Edit Brand">
                                            <i class="bi bi-pencil-square"></i>
                                            <span>Edit</span>
                                        </button>
                                        <button type="button" class="btn btn-light border text-danger rounded-circle d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;"
                                                data-bs-toggle="modal" data-bs-target="#deleteConfirmModal" 
                                                data-id="<?= $b['id'] ?>" 
                                                data-title="<?= escape($b['brand_name']) ?>" 
                                                data-delete-url="brands.php?action=delete&id=<?= $b['id'] ?>" 
                                                title="Delete Brand">
                                            <i class="bi bi-trash3"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center mb-3" style="width: 64px; height: 64px;">
                            <i class="bi bi-tags text-muted fs-2"></i>
                        </div>
                        <h6 class="fw-bold text-dark mb-1">No Brands Found</h6>
                        <p class="text-muted small mb-3">No laptop brands matched your search criteria.</p>
                        <a href="brands.php" class="btn btn-sm btn-outline-primary rounded-pill px-3">Clear Search Filters</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Add Brand Modal -->
<div class="modal fade" id="addBrandModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow-lg overflow-hidden">
            <form action="brands.php" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                <?= renderCsrfInput() ?>
                <div class="modal-header border-bottom py-3 px-4 bg-light-subtle">
                    <h5 class="modal-title fw-bold mb-0 text-dark"><i class="bi bi-plus-circle-fill text-primary me-2"></i>Add New Laptop Brand</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3.5">
                        <label for="brand_name" class="form-label fw-bold small text-muted text-uppercase" style="letter-spacing: 0.04em;">Brand Name <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-tag"></i></span>
                            <input type="text" name="brand_name" id="brand_name" class="form-control border-start-0 ps-0" placeholder="e.g. Razer, Microsoft Surface, Gigabyte" required>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label for="brand_logo" class="form-label fw-bold small text-muted text-uppercase" style="letter-spacing: 0.04em;">Brand Logo Image</label>
                        <input type="file" name="brand_logo" id="brand_logo" class="form-control" accept="image/png,image/jpeg,image/webp,image/svg+xml">
                        <div class="form-text small text-muted mt-1.5"><i class="bi bi-info-circle me-1"></i>PNG, SVG, or transparent WEBP recommended for best look.</div>
                    </div>
                </div>
                <div class="modal-footer border-top py-3 px-4 bg-light-subtle">
                    <button type="button" class="btn btn-light rounded-3 px-4 fw-semibold border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_brand" class="btn btn-primary rounded-3 px-4 fw-semibold shadow-sm">Save Brand</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Brand Modal -->
<div class="modal fade" id="editBrandModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow-lg overflow-hidden">
            <form action="brands.php" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                <?= renderCsrfInput() ?>
                <input type="hidden" name="brand_id" id="edit_brand_id">
                <div class="modal-header border-bottom py-3 px-4 bg-light-subtle">
                    <h5 class="modal-title fw-bold mb-0 text-dark"><i class="bi bi-pencil-square text-primary me-2"></i>Edit Brand</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3.5">
                        <label for="edit_brand_name" class="form-label fw-bold small text-muted text-uppercase" style="letter-spacing: 0.04em;">Brand Name <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-tag"></i></span>
                            <input type="text" name="brand_name" id="edit_brand_name" class="form-control border-start-0 ps-0" required>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label for="edit_brand_logo" class="form-label fw-bold small text-muted text-uppercase" style="letter-spacing: 0.04em;">Update Logo Image</label>
                        <input type="file" name="brand_logo" id="edit_brand_logo" class="form-control" accept="image/png,image/jpeg,image/webp,image/svg+xml">
                        <div class="form-text small text-muted mt-1.5"><i class="bi bi-info-circle me-1"></i>Leave empty to preserve existing logo.</div>
                    </div>
                </div>
                <div class="modal-footer border-top py-3 px-4 bg-light-subtle">
                    <button type="button" class="btn btn-light rounded-3 px-4 fw-semibold border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_brand" class="btn btn-primary rounded-3 px-4 fw-semibold shadow-sm">Update Brand</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirm Modal -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow-lg overflow-hidden">
            <div class="modal-header border-bottom py-3 px-4 bg-danger-subtle text-danger">
                <h5 class="modal-title fw-bold mb-0"><i class="bi bi-exclamation-triangle-fill me-2"></i>Delete Brand</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <div class="rounded-circle bg-danger-subtle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px; color: #ef4444;">
                    <i class="bi bi-trash3-fill fs-3"></i>
                </div>
                <p class="mb-1 text-dark fs-6">Are you sure you want to delete brand <strong class="modal-item-title text-danger"></strong>?</p>
                <p class="text-muted small mb-0">This action will remove the brand from the catalog. Brands with active listings cannot be deleted.</p>
            </div>
            <div class="modal-footer border-top py-3 px-4 bg-light-subtle">
                <button type="button" class="btn btn-light rounded-3 px-4 fw-semibold border" data-bs-dismiss="modal">Cancel</button>
                <a href="#" class="btn btn-danger btn-confirm-delete rounded-3 px-4 fw-semibold shadow-sm">Delete Brand</a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('brandSearchInput');
    const cardsList = document.querySelector('.brand-cards-list');
    if (!searchInput || !cardsList) return;

    searchInput.addEventListener('input', function () {
        const query = this.value.trim().toLowerCase();
        const cards = cardsList.querySelectorAll('.brand-item-card');

        cards.forEach(card => {
            const brandName = card.getAttribute('data-brand-name') || '';
            if (query === '' || brandName.includes(query)) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });
    });
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
