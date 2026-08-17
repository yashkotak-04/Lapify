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
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-muted small text-uppercase font-weight-bold">
                            <tr>
                                <th class="ps-4">Laptop</th>
                                <th>Seller</th>
                                <th>Price</th>
                                <th>Submitted</th>
                                <th class="pe-4 text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($result) > 0): ?>
                                <?php while ($listing = mysqli_fetch_assoc($result)): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="fw-bold text-dark"><?= escape($listing['model']) ?></div>
                                            <div class="small text-muted"><?= escape($listing['brand_name']) ?></div>
                                        </td>
                                        <td><?= escape($listing['seller_name']) ?></td>
                                        <td class="fw-bold text-primary"><?= formatPrice($listing['price']) ?></td>
                                        <td class="small text-muted"><?= formatDate($listing['created_at']) ?></td>
                                        <td class="pe-4 text-end">
                                            <a href="pending_listings.php?action=approve&id=<?= (int)$listing['id'] ?>" class="btn btn-sm btn-success me-2">Approve</a>
                                            <a href="pending_listings.php?action=reject&id=<?= (int)$listing['id'] ?>&reason=Needs%20revision" class="btn btn-sm btn-outline-danger">Reject</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">No listings are waiting for approval.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
mysqli_stmt_close($stmt);
require_once __DIR__ . '/footer.php';
?>
