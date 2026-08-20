<?php
$admin_title = 'Pending Listings | Lapify Admin';
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();
$current_admin = getCurrentUser();
$conn = getDbConnection();

if (isset($_GET['action']) && isset($_GET['id'])) {
    $laptop_id = intval($_GET['id']);
    $action = sanitizeInput($_GET['action'] ?? '');
    if (in_array($action, ['approve', 'reject'], true)) {
        $reviewed_by = intval($_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? 0);
        $new_status = $action === 'approve' ? 'approved' : 'rejected';
        $rejection_reason = $action === 'reject' ? sanitizeInput($_GET['reason'] ?? 'Needs revision.') : null;
        $stmt = mysqli_prepare($conn, "UPDATE laptops SET status = ?, approval_status = ?, reviewed_by = ?, reviewed_at = NOW(), rejection_reason = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "ssisi", $new_status, $new_status, $reviewed_by, $rejection_reason, $laptop_id);
        if (mysqli_stmt_execute($stmt)) {
            setFlash('success', 'Listing marked as ' . ucfirst($new_status) . '.');
        }
        mysqli_stmt_close($stmt);
    }
    header('Location: ' . BASE_URL . '/admin/pending_listings.php');
    exit();
}

$sql = "SELECT l.*, b.brand_name, u.full_name AS seller_name
        FROM laptops l
        JOIN brands b ON l.brand_id = b.id
        JOIN users u ON l.user_id = u.id
        WHERE l.status = 'pending' OR l.approval_status = 'pending'
        ORDER BY l.id DESC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<div class="dashboard-wrapper">
    <?php require_once __DIR__ . '/sidebar.php'; ?>

    <div class="dashboard-content">
        <?php require_once __DIR__ . '/topbar.php'; ?>
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h3 class="fw-bold mb-1"><i class="bi bi-hourglass-split text-warning me-2"></i>Pending Listings</h3>
                <p class="text-muted mb-0">Review and approve new laptop listings before they go live.</p>
            </div>
        </div>

        <?php displayFlash(); ?>

        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-body p-3.5 p-md-4">
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <div class="d-flex flex-column gap-3.5">
                        <?php while ($listing = mysqli_fetch_assoc($result)): 
                            $img_src = getLaptopImageUrl($listing) ?: 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?auto=format&fit=crop&w=300&q=80';
                            $is_new = (strtolower((string)($listing['type'] ?? '')) === 'new');
                        ?>
                            <div class="posting-item-card p-3.5 p-md-4 rounded-4 border d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-4">
                                <!-- Left: Thumbnail & Laptop Info -->
                                <div class="d-flex align-items-center gap-3 gap-md-4" style="min-width: 0;">
                                    <img src="<?= escape($img_src) ?>" alt="<?= escape($listing['model']) ?>" loading="lazy" decoding="async" class="posting-thumb rounded-3 border flex-shrink-0 me-3" style="width: 76px; height: 56px; object-fit: cover;">
                                    <div class="d-flex flex-column gap-1 ps-1" style="min-width: 0;">
                                        <h5 class="fw-bold mb-0 text-dark posting-title text-truncate" style="font-size: 1.08rem;" title="<?= escape($listing['model']) ?>">
                                            <?= escape($listing['model']) ?>
                                        </h5>
                                        <div class="d-flex flex-wrap align-items-center gap-2 text-muted small" style="font-size: 0.85rem;">
                                            <span class="fw-semibold text-secondary"><i class="bi bi-tag-fill text-primary me-1"></i><?= escape($listing['brand_name'] ?? 'Laptop') ?></span>
                                            <span class="text-slate-300">•</span>
                                            <span class="text-dark fw-medium"><i class="bi bi-person-fill text-primary me-1"></i><?= escape($listing['seller_name'] ?? 'Seller') ?></span>
                                            <span class="text-slate-300">•</span>
                                            <span><i class="bi bi-clock me-1"></i><?= formatDate($listing['created_at']) ?></span>
                                            <span class="text-slate-300">•</span>
                                            <span class="badge rounded-pill px-2.5 py-1 <?= $is_new ? 'bg-primary-subtle text-primary' : 'bg-secondary-subtle text-secondary' ?>" style="font-size: 0.72rem; font-weight: 600;">
                                                <?= $is_new ? 'Brand New' : 'Pre-Owned' ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Right: Price, Status, and Action Buttons with generous spacing -->
                                <div class="d-flex flex-wrap align-items-center justify-content-between justify-content-lg-end gap-4 ms-lg-auto flex-shrink-0">
                                    <!-- Price -->
                                    <div class="text-start text-lg-end">
                                        <div class="fw-bold text-primary" style="font-size: 1.25rem;">
                                            <?= formatPrice($listing['price']) ?>
                                        </div>
                                    </div>

                                    <!-- Status Badge -->
                                    <div>
                                        <span class="status-pill status-pill-pending">
                                            <span class="status-dot"></span>
                                            <span>Pending Approval</span>
                                        </span>
                                    </div>

                                    <!-- Action Buttons with generous spacing -->
                                    <div class="d-flex align-items-center gap-2">
                                        <a href="../laptop-details.php?id=<?= (int)$listing['id'] ?>" target="_blank" class="btn btn-outline-primary rounded-pill px-3.5 py-2 fw-semibold d-inline-flex align-items-center gap-1.5 shadow-sm" title="Preview Listing">
                                            <i class="bi bi-eye"></i>
                                            <span>Preview</span>
                                        </a>
                                        <a href="pending_listings.php?action=approve&id=<?= (int)$listing['id'] ?>" class="btn btn-success rounded-pill px-3.5 py-2 fw-semibold d-inline-flex align-items-center gap-1.5 shadow-sm">
                                            <i class="bi bi-check-lg"></i>
                                            <span>Approve</span>
                                        </a>
                                        <a href="pending_listings.php?action=reject&id=<?= (int)$listing['id'] ?>&reason=Needs%20revision" class="btn btn-outline-danger rounded-pill px-3.5 py-2 fw-semibold d-inline-flex align-items-center gap-1.5 shadow-sm" onclick="return confirm('Are you sure you want to reject this listing?');">
                                            <i class="bi bi-x-lg"></i>
                                            <span>Reject</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5 px-4">
                        <div class="bg-success-subtle text-success rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 64px; height: 64px;">
                            <i class="bi bi-check-circle fs-2"></i>
                        </div>
                        <h5 class="fw-bold mb-1">All Caught Up!</h5>
                        <p class="text-muted small mb-0 mx-auto" style="max-width: 380px;">There are currently no pending laptop listings waiting for approval.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
mysqli_stmt_close($stmt);
require_once __DIR__ . '/footer.php';
?>
