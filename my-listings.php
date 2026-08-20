<?php
// my-listings.php - User Listings Management (CRUD)
$page_title = "My Listings | Lapify";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin();

$conn = getDbConnection();
$user = getCurrentUser();

// Handle Resubmission (GET action)
if (isset($_GET['action']) && $_GET['action'] === 'resubmit' && isset($_GET['id'])) {
    $laptop_id = intval($_GET['id']);
    $stmt = mysqli_prepare($conn, "UPDATE laptops SET status = 'pending', approval_status = 'pending', reviewed_by = NULL, reviewed_at = NULL, rejection_reason = NULL WHERE id = ? AND user_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $laptop_id, $user['id']);
    if (mysqli_stmt_execute($stmt)) {
        setFlash('success', 'Your listing has been resubmitted for review.');
    }
    mysqli_stmt_close($stmt);
    header("Location: " . BASE_URL . "/my-listings.php");
    exit();
}

// Handle Delete Listing (GET action)
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $laptop_id = intval($_GET['id']);

    // Fetch image filename first to delete file from disk
    $img_stmt = mysqli_prepare($conn, "SELECT image FROM laptops WHERE id = ? AND user_id = ?");
    mysqli_stmt_bind_param($img_stmt, "ii", $laptop_id, $user['id']);
    mysqli_stmt_execute($img_stmt);
    mysqli_stmt_bind_result($img_stmt, $filename);
    mysqli_stmt_fetch($img_stmt);
    mysqli_stmt_close($img_stmt);

    // Delete record from DB
    $del_stmt = mysqli_prepare($conn, "DELETE FROM laptops WHERE id = ? AND user_id = ?");
    mysqli_stmt_bind_param($del_stmt, "ii", $laptop_id, $user['id']);
    if (mysqli_stmt_execute($del_stmt)) {
        deleteImageFile($filename, LAPTOP_UPLOAD_DIR);
        setFlash('success', "Listing deleted successfully.");
    } else {
        setFlash('error', "Failed to delete listing.");
    }
    mysqli_stmt_close($del_stmt);
    header("Location: " . BASE_URL . "/my-listings.php");
    exit();
}

// Fetch all listings posted by logged-in user
$query = "SELECT l.*, b.brand_name 
          FROM laptops l 
          JOIN brands b ON l.brand_id = b.id 
          WHERE l.user_id = ? 
          ORDER BY l.id DESC";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $user['id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

require_once __DIR__ . '/includes/navbar.php';
?>

<div class="container py-5">
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
        <div>
            <h2 class="fw-bold mb-1"><i class="bi bi-laptop text-primary me-2"></i>My Laptop Listings</h2>
            <p class="text-muted mb-0">Track pending, approved, and rejected laptop advertisements</p>
        </div>
        <a href="sell.php" class="btn btn-primary font-weight-bold rounded-pill px-4">
            <i class="bi bi-plus-lg me-2"></i>Post New Laptop
        </a>
    </div>

    <?php displayFlash(); ?>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-body p-3.5 p-md-4">
            <?php if (mysqli_num_rows($result) > 0): ?>
                <div class="d-flex flex-column gap-3">
                    <?php while ($laptop = mysqli_fetch_assoc($result)): 
                        $img_src = getLaptopImageUrl($laptop) ?: 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?auto=format&fit=crop&w=300&q=80';
                        $listing_state = strtolower((string)($laptop['status'] ?? $laptop['approval_status'] ?? 'pending'));
                        $is_new = (strtolower((string)($laptop['type'] ?? '')) === 'new');
                        
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
                        <div class="posting-item-card p-3.5 p-md-4 rounded-4 border d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-4">
                            <!-- Left: Thumbnail & Laptop Info with generous spacing -->
                            <div class="d-flex align-items-center" style="min-width: 0;">
                                <div class="posting-thumb-wrapper me-3.5 me-md-4 flex-shrink-0">
                                    <img src="<?= escape($img_src) ?>" alt="<?= escape($laptop['model']) ?>" class="posting-thumb rounded-3 border shadow-2xs" style="width: 82px; height: 60px; object-fit: cover;">
                                </div>
                                <div class="d-flex flex-column gap-1" style="min-width: 0;">
                                    <h5 class="fw-bold mb-0 text-dark posting-title text-truncate" style="font-size: 1.1rem;" title="<?= escape($laptop['model']) ?>">
                                        <?= escape($laptop['model']) ?>
                                    </h5>
                                    <div class="d-flex flex-wrap align-items-center gap-2 text-muted small" style="font-size: 0.85rem;">
                                        <span class="fw-semibold text-secondary"><i class="bi bi-tag-fill text-primary me-1"></i><?= escape($laptop['brand_name'] ?? 'Laptop') ?></span>
                                        <span class="text-slate-300">•</span>
                                        <span><i class="bi bi-clock me-1"></i><?= formatDate($laptop['created_at']) ?></span>
                                        <span class="text-slate-300">•</span>
                                        <span class="badge rounded-pill px-2.5 py-0.5 <?= $is_new ? 'badge-type-new' : 'badge-type-used' ?>">
                                            <?= $is_new ? 'Brand New' : 'Pre-Owned' ?>
                                        </span>
                                        <?php if (!empty($laptop['condition'])): ?>
                                            <span class="text-slate-300">•</span>
                                            <span class="text-slate-600"><?= escape($laptop['condition']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($listing_state === 'rejected' && !empty($laptop['rejection_reason'])): ?>
                                        <div class="small text-danger mt-1">
                                            <i class="bi bi-exclamation-circle me-1"></i>Reason: <?= escape($laptop['rejection_reason']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Right: Price, Status, Stock, and Actions with generous spacing -->
                            <div class="d-flex flex-wrap align-items-center justify-content-between justify-content-lg-end gap-4 ms-lg-auto flex-shrink-0">
                                <!-- Price & Stock -->
                                <div class="text-start text-lg-end">
                                    <div class="fw-bold text-primary" style="font-size: 1.25rem;">
                                        <?= formatPrice($laptop['price']) ?>
                                    </div>
                                    <div class="text-muted small" style="font-size: 0.8rem;">
                                        <?= ((int)($laptop['quantity'] ?? 1) > 0 ? '<span class="text-success fw-semibold"><i class="bi bi-check2"></i> In Stock</span>' : '<span class="text-danger fw-semibold"><i class="bi bi-x"></i> Out of Stock</span>') ?>
                                    </div>
                                </div>

                                <!-- Status Badge -->
                                <div>
                                    <span class="status-pill <?= $status_class ?>">
                                        <span class="status-dot"></span>
                                        <span><?= escape($status_label) ?></span>
                                    </span>
                                </div>

                                <!-- Action Buttons with generous spacing -->
                                <div class="d-flex align-items-center gap-2">
                                    <?php if ($listing_state === 'rejected'): ?>
                                        <a href="my-listings.php?action=resubmit&id=<?= $laptop['id'] ?>" class="btn btn-sm btn-outline-warning rounded-pill px-3 py-2 fw-semibold shadow-sm">
                                            <i class="bi bi-arrow-repeat me-1"></i>Resubmit
                                        </a>
                                    <?php endif; ?>
                                    <a href="laptop-details.php?id=<?= $laptop['id'] ?>" class="btn btn-outline-primary rounded-pill px-4 py-2 fw-semibold d-inline-flex align-items-center gap-2 shadow-sm" title="View Details">
                                        <i class="bi bi-eye"></i>
                                        <span>View</span>
                                    </a>
                                    <a href="sell.php?edit_id=<?= $laptop['id'] ?>" class="btn btn-light border rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;" title="Edit Listing">
                                        <i class="bi bi-pencil text-secondary"></i>
                                    </a>
                                    <button type="button" class="btn btn-light border text-danger rounded-circle d-flex align-items-center justify-content-center btn-delete-listing" style="width: 40px; height: 40px;"
                                            data-bs-toggle="modal" data-bs-target="#deleteConfirmModal" 
                                            data-id="<?= $laptop['id'] ?>" 
                                            data-title="<?= escape($laptop['model']) ?>" 
                                            data-delete-url="my-listings.php?action=delete&id=<?= $laptop['id'] ?>" 
                                            title="Delete Listing">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-5 px-4">
                    <div class="bg-primary-subtle text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 64px; height: 64px;">
                        <i class="bi bi-laptop fs-2"></i>
                    </div>
                    <h5 class="fw-bold mb-1">No Laptop Listings Yet</h5>
                    <p class="text-muted small mb-3 mx-auto" style="max-width: 380px;">You haven't published any laptop listings for sale yet.</p>
                    <a href="sell.php" class="btn btn-primary rounded-pill px-4 py-2.5 fw-bold shadow-sm d-inline-flex align-items-center gap-2">
                        <i class="bi bi-plus-lg"></i>
                        <span>Post Your First Laptop</span>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Confirmation for Delete -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true" style="z-index: 1065;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow-lg overflow-hidden">
            <div class="modal-header border-0 pb-0 pt-4 px-4">
                <h5 class="modal-title fw-bold text-danger d-flex align-items-center gap-2" id="deleteConfirmModalLabel">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <span>Delete Listing</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-3 px-4">
                <p class="mb-0 text-secondary">
                    Are you sure you want to permanently delete <strong class="modal-item-title text-dark">this listing</strong>? This action cannot be undone and will remove the listing from Lapify.
                </p>
            </div>
            <div class="modal-footer border-0 pt-2 pb-4 px-4 gap-2">
                <button type="button" class="btn btn-light rounded-pill px-4 fw-semibold border" data-bs-dismiss="modal">Cancel</button>
                <a href="#" class="btn btn-danger btn-confirm-delete rounded-pill px-4 fw-bold shadow-sm">
                    <i class="bi bi-trash me-1"></i> Yes, Delete
                </a>
            </div>
        </div>
    </div>
</div>

<?php 
mysqli_stmt_close($stmt);
require_once __DIR__ . '/includes/footer.php'; 
?>
