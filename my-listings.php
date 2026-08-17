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
        <div class="card-body p-0">
            <?php if (mysqli_num_rows($result) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-muted small text-uppercase font-weight-bold">
                            <tr>
                                <th class="ps-4">Laptop</th>
                                <th>Type</th>
                                <th>Price</th>
                                <th>Condition</th>
                                <th>Stock</th>
                                <th>Status</th>
                                <th>Date Posted</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($laptop = mysqli_fetch_assoc($result)): 
                                $img_src = getLaptopImageUrl($laptop) ?: 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?auto=format&fit=crop&w=200&q=80';
                            ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center gap-3">
                                            <img src="<?= escape($img_src) ?>" alt="" class="rounded-3" style="width: 56px; height: 42px; object-fit: cover;">
                                            <div>
                                                <div class="fw-bold text-white"><?= escape($laptop['model']) ?></div>
                                                <div class="small text-muted"><?= escape($laptop['brand_name']) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge <?= $laptop['type'] === 'New' ? 'badge-type-new' : 'badge-type-old' ?>">
                                            <?= escape($laptop['type']) ?>
                                        </span>
                                    </td>
                                    <td class="fw-bold text-primary">
                                        <?= formatPrice($laptop['price']) ?>
                                    </td>
                                    <td class="small text-secondary">
                                        <?= escape($laptop['condition'] ?? 'N/A') ?>
                                    </td>
                                    <td>
                                        <span class="badge <?= ((int)($laptop['quantity'] ?? 1) > 0 ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger') ?> rounded-pill">
                                            <?= ((int)($laptop['quantity'] ?? 1) > 0 ? 'In Stock' : 'Out of Stock') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php $listing_state = strtolower((string)($laptop['status'] ?? $laptop['approval_status'] ?? 'pending')); ?>
                                        <div class="d-flex flex-column gap-2">
                                            <span class="badge rounded-pill <?= $listing_state === 'approved' ? 'bg-success-subtle text-success' : ($listing_state === 'rejected' ? 'bg-danger-subtle text-danger' : 'bg-warning-subtle text-warning') ?>">
                                                <?= escape(ucfirst($listing_state)) ?>
                                            </span>
                                            <?php if ($listing_state === 'rejected' && !empty($laptop['rejection_reason'])): ?>
                                                <small class="text-muted">Reason: <?= escape($laptop['rejection_reason']) ?></small>
                                            <?php endif; ?>
                                            <?php if ($listing_state === 'rejected'): ?>
                                                <a href="my-listings.php?action=resubmit&id=<?= $laptop['id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill">Resubmit</a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="small text-muted">
                                        <?= formatDate($laptop['created_at']) ?>
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="btn-group">
                                            <a href="sell.php?edit_id=<?= $laptop['id'] ?>" class="btn btn-sm btn-light" title="Edit Listing"><i class="bi bi-pencil"></i></a>
                                            <a href="laptop-details.php?id=<?= $laptop['id'] ?>" class="btn btn-sm btn-light" title="View Listing"><i class="bi bi-eye"></i></a>
                                            <button type="button" class="btn btn-sm btn-light text-danger" 
                                                    data-bs-toggle="modal" data-bs-target="#deleteConfirmModal" 
                                                    data-id="<?= $laptop['id'] ?>" 
                                                    data-title="<?= escape($laptop['model']) ?>" 
                                                    data-delete-url="my-listings.php?action=delete&id=<?= $laptop['id'] ?>" 
                                                    title="Delete Listing">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <div class="fs-1 text-muted mb-3"><i class="bi bi-laptop-fill"></i></div>
                    <h5 class="fw-bold">No Laptop Listings Yet</h5>
                    <p class="text-muted">You haven't published any laptop listings for sale.</p>
                    <a href="sell.php" class="btn btn-primary rounded-pill px-4">Post Your First Laptop</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Confirmation for Delete -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i>Delete Confirmation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-4">
                Are you sure you want to delete the listing <strong class="modal-item-title text-dark"></strong>? This action cannot be undone.
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light rounded-3 px-4 font-weight-bold" data-bs-dismiss="modal">Cancel</button>
                <a href="#" class="btn btn-danger btn-confirm-delete rounded-3 px-4 font-weight-bold">Yes, Delete</a>
            </div>
        </div>
    </div>
</div>

<?php 
mysqli_stmt_close($stmt);
require_once __DIR__ . '/includes/footer.php'; 
?>
