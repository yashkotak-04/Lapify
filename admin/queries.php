<?php
// admin/queries.php - Admin Contact Queries Management
$admin_title = "Contact Queries | Lapify Admin";
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();

$conn = getDbConnection();
$current_admin = getCurrentUser();

// Handle Status Updates & Deletions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', "Session expired or invalid security token. Please try again.");
    } else {
        $action = sanitizeInput($_POST['action'] ?? '');

        if ($action === 'update_status') {
            $query_id = intval($_POST['query_id'] ?? 0);
            $new_status = sanitizeInput($_POST['status'] ?? 'new');
            $allowed_statuses = ['new', 'read', 'resolved'];

            if ($query_id > 0 && in_array($new_status, $allowed_statuses, true)) {
                $stmt = mysqli_prepare($conn, "UPDATE contact_queries SET status = ?, updated_at = NOW() WHERE id = ?");
                mysqli_stmt_bind_param($stmt, "si", $new_status, $query_id);
                if (mysqli_stmt_execute($stmt)) {
                    setFlash('success', "Query {$query_id} status changed to " . strtoupper($new_status) . ".");
                } else {
                    setFlash('error', "Failed to update query status.");
                }
                mysqli_stmt_close($stmt);
            } else {
                setFlash('error', "Invalid query or status selected.");
            }
        } elseif ($action === 'send_reply') {
            $query_id = intval($_POST['query_id'] ?? 0);
            $admin_reply = trim(sanitizeInput($_POST['admin_reply'] ?? ''));
            $mark_resolved = !empty($_POST['mark_resolved']);
            $new_status = $mark_resolved ? 'resolved' : 'read';
            $admin_id = intval($_SESSION['admin_id'] ?? $current_admin['id'] ?? 0);

            if ($query_id > 0 && !empty($admin_reply)) {
                $stmt = mysqli_prepare($conn, "UPDATE contact_queries SET admin_reply = ?, replied_at = NOW(), replied_by = ?, status = ?, updated_at = NOW() WHERE id = ?");
                mysqli_stmt_bind_param($stmt, "sisi", $admin_reply, $admin_id, $new_status, $query_id);
                if (mysqli_stmt_execute($stmt)) {
                    setFlash('success', "Reply sent and stored for Query {$query_id} successfully.");
                } else {
                    setFlash('error', "Failed to send reply. Please try again.");
                }
                mysqli_stmt_close($stmt);
            } else {
                setFlash('error', "Reply message content cannot be empty.");
            }
        } elseif ($action === 'delete_query') {
            $query_id = intval($_POST['query_id'] ?? 0);
            if ($query_id > 0) {
                $stmt = mysqli_prepare($conn, "DELETE FROM contact_queries WHERE id = ?");
                mysqli_stmt_bind_param($stmt, "i", $query_id);
                if (mysqli_stmt_execute($stmt)) {
                    setFlash('success', "Query {$query_id} has been permanently deleted.");
                } else {
                    setFlash('error', "Failed to delete query.");
                }
                mysqli_stmt_close($stmt);
            } else {
                setFlash('error', "Invalid query ID.");
            }
        } elseif ($action === 'mark_all_read') {
            $stmt = mysqli_prepare($conn, "UPDATE contact_queries SET status = 'read', updated_at = NOW() WHERE status = 'new'");
            if (mysqli_stmt_execute($stmt)) {
                $affected = mysqli_stmt_affected_rows($stmt);
                setFlash('success', "Marked {$affected} new query/queries as Read.");
            } else {
                setFlash('error', "Failed to update queries.");
            }
            mysqli_stmt_close($stmt);
        }
    }

    $redirect_url = BASE_URL . "/admin/queries.php";
    if (!empty($_GET)) {
        $redirect_url .= '?' . http_build_query($_GET);
    }
    header("Location: " . $redirect_url);
    exit();
}

// Read Filter and Search parameters
$status_filter = sanitizeInput($_GET['status'] ?? 'all');
$search = sanitizeInput($_GET['search'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Count by Status for Stat Cards
$counts = ['total' => 0, 'new' => 0, 'read' => 0, 'resolved' => 0];
$stat_res = mysqli_query($conn, "SELECT status, COUNT(*) as count FROM contact_queries GROUP BY status");
if ($stat_res) {
    while ($row = mysqli_fetch_assoc($stat_res)) {
        $st = strtolower($row['status']);
        if (isset($counts[$st])) {
            $counts[$st] = (int)$row['count'];
        }
        $counts['total'] += (int)$row['count'];
    }
    mysqli_free_result($stat_res);
}

// Build WHERE query
$where_clauses = ["1=1"];
$params = [];
$param_types = "";

if ($status_filter !== 'all' && in_array($status_filter, ['new', 'read', 'resolved'], true)) {
    $where_clauses[] = "cq.status = ?";
    $params[] = $status_filter;
    $param_types .= "s";
}

if (!empty($search)) {
    $where_clauses[] = "(cq.name LIKE ? OR cq.email LIKE ? OR cq.subject LIKE ? OR cq.message LIKE ?)";
    $like = "%{$search}%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $param_types .= "ssss";
}

$where_sql = implode(" AND ", $where_clauses);

// Count matching records
$count_sql = "SELECT COUNT(*) FROM contact_queries cq WHERE {$where_sql}";
$count_stmt = mysqli_prepare($conn, $count_sql);
if (!empty($param_types)) {
    mysqli_stmt_bind_param($count_stmt, $param_types, ...$params);
}
mysqli_stmt_execute($count_stmt);
mysqli_stmt_bind_result($count_stmt, $total_matching);
mysqli_stmt_fetch($count_stmt);
mysqli_stmt_close($count_stmt);

$total_matching = (int)($total_matching ?? 0);
$total_pages = max(1, (int)ceil($total_matching / $per_page));
$page = min($page, $total_pages);
$offset = ($page - 1) * $per_page;

// Fetch queries with optional user details
$list_sql = "SELECT cq.*, u.full_name AS reg_user_name, u.email AS reg_user_email 
             FROM contact_queries cq 
             LEFT JOIN users u ON cq.user_id = u.id 
             WHERE {$where_sql} 
             ORDER BY cq.id DESC 
             LIMIT ? OFFSET ?";

$list_params = $params;
$list_params[] = $per_page;
$list_params[] = $offset;
$list_types = $param_types . "ii";

$list_stmt = mysqli_prepare($conn, $list_sql);
mysqli_stmt_bind_param($list_stmt, $list_types, ...$list_params);
mysqli_stmt_execute($list_stmt);
$result = mysqli_stmt_get_result($list_stmt);
$queries = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($list_stmt);
?>

<div class="dashboard-wrapper">
    <?php require_once __DIR__ . '/sidebar.php'; ?>

    <div class="dashboard-content">
        <?php require_once __DIR__ . '/topbar.php'; ?>

        <!-- Page Header -->
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
            <div>
                <h3 class="fw-bold mb-1"><i class="bi bi-chat-left-text text-primary me-2"></i>Contact Queries</h3>
                <p class="text-muted mb-0">View, search, and manage customer inquiries submitted through the contact page.</p>
            </div>
            <?php if ($counts['new'] > 0): ?>
                <form method="POST" action="queries.php">
                    <?= renderCsrfInput() ?>
                    <input type="hidden" name="action" value="mark_all_read">
                    <button type="submit" class="btn btn-outline-primary btn-sm rounded-pill px-3 fw-semibold shadow-sm">
                        <i class="bi bi-check2-all me-1"></i> Mark All New as Read
                    </button>
                </form>
            <?php endif; ?>
        </div>

        <?php displayFlash(); ?>

        <!-- Summary Stat Cards -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <a href="queries.php" class="card border-0 shadow-sm rounded-4 p-3 text-decoration-none h-100 <?= $status_filter === 'all' ? 'border border-2 border-primary' : '' ?>">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="small text-muted font-weight-bold text-uppercase">Total Inquiries</div>
                            <div class="fs-4 fw-bold text-dark mt-1"><?= number_format($counts['total']) ?></div>
                        </div>
                        <div class="rounded-circle bg-primary bg-opacity-10 text-primary p-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="bi bi-inbox fs-4"></i>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-6 col-md-3">
                <a href="queries.php?status=new" class="card border-0 shadow-sm rounded-4 p-3 text-decoration-none h-100 <?= $status_filter === 'new' ? 'border border-2 border-danger' : '' ?>">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="small text-muted font-weight-bold text-uppercase">New / Unread</div>
                            <div class="fs-4 fw-bold text-danger mt-1"><?= number_format($counts['new']) ?></div>
                        </div>
                        <div class="rounded-circle bg-danger bg-opacity-10 text-danger p-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="bi bi-bell fs-4"></i>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-6 col-md-3">
                <a href="queries.php?status=read" class="card border-0 shadow-sm rounded-4 p-3 text-decoration-none h-100 <?= $status_filter === 'read' ? 'border border-2 border-info' : '' ?>">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="small text-muted font-weight-bold text-uppercase">Read</div>
                            <div class="fs-4 fw-bold text-info mt-1"><?= number_format($counts['read']) ?></div>
                        </div>
                        <div class="rounded-circle bg-info bg-opacity-10 text-info p-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="bi bi-envelope-open fs-4"></i>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-6 col-md-3">
                <a href="queries.php?status=resolved" class="card border-0 shadow-sm rounded-4 p-3 text-decoration-none h-100 <?= $status_filter === 'resolved' ? 'border border-2 border-success' : '' ?>">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="small text-muted font-weight-bold text-uppercase">Resolved</div>
                            <div class="fs-4 fw-bold text-success mt-1"><?= number_format($counts['resolved']) ?></div>
                        </div>
                        <div class="rounded-circle bg-success bg-opacity-10 text-success p-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="bi bi-check-circle fs-4"></i>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        <!-- Filter & Search Controls -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-3">
                <form action="queries.php" method="GET" class="row g-2 align-items-center">
                    <div class="col-md-3">
                        <label for="status" class="form-label small text-muted font-weight-bold mb-1">Status Filter</label>
                        <select name="status" id="status" class="form-select form-select-sm rounded-3" onchange="this.form.submit()">
                            <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>All Statuses (<?= $counts['total'] ?>)</option>
                            <option value="new" <?= $status_filter === 'new' ? 'selected' : '' ?>>New / Unread (<?= $counts['new'] ?>)</option>
                            <option value="read" <?= $status_filter === 'read' ? 'selected' : '' ?>>Read (<?= $counts['read'] ?>)</option>
                            <option value="resolved" <?= $status_filter === 'resolved' ? 'selected' : '' ?>>Resolved (<?= $counts['resolved'] ?>)</option>
                        </select>
                    </div>
                    <div class="col-md-7">
                        <label for="search" class="form-label small text-muted font-weight-bold mb-1">Search Inquiries</label>
                        <div class="input-group input-group-sm">
                            <input type="text" name="search" id="search" class="form-control rounded-start-3" value="<?= escape($search) ?>" placeholder="Search by name, email, subject, or message...">
                            <button type="submit" class="btn btn-primary rounded-end-3 px-3"><i class="bi bi-search me-1"></i> Search</button>
                        </div>
                    </div>
                    <div class="col-md-2 text-md-end mt-3 mt-md-4">
                        <a href="queries.php" class="btn btn-sm btn-outline-secondary rounded-3 w-100">
                            <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Queries Table -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-queries align-middle mb-0">
                        <thead class="bg-light text-muted small text-uppercase font-weight-bold">
                            <tr>
                                <th class="ps-4" style="width: 75px;">#ID</th>
                                <th style="min-width: 220px;">Sender</th>
                                <th style="min-width: 160px;">Subject</th>
                                <th style="min-width: 200px;">Message</th>
                                <th style="min-width: 140px;">Status</th>
                                <th style="min-width: 130px;">Date</th>
                                <th class="pe-4 text-end" style="width: 180px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($queries)): ?>
                                <?php foreach ($queries as $q): 
                                    $is_new = strtolower($q['status']) === 'new';
                                    $is_read = strtolower($q['status']) === 'read';
                                    $is_resolved = strtolower($q['status']) === 'resolved';
                                    
                                    $status_class = 'status-new';
                                    if ($is_read) $status_class = 'status-read';
                                    elseif ($is_resolved) $status_class = 'status-resolved';

                                    $clean_subject = !empty($q['subject']) ? $q['subject'] : '(No Subject)';
                                    $msg_snippet = mb_strimwidth(trim($q['message']), 0, 60, '...');
                                    $initial = strtoupper(substr($q['name'] ?? 'U', 0, 1));
                                ?>
                                    <tr class="<?= $is_new ? 'table-danger-subtle' : '' ?>">
                                        <td class="ps-4">
                                            <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-1 fw-bold">#<?= (int)$q['id'] ?></span>
                                        </td>
                                        <td>
                                            <div class="admin-table-sender-wrap">
                                                <div class="admin-table-avatar"><?= $initial ?></div>
                                                <div class="admin-table-sender-info">
                                                    <div class="fw-bold text-dark mb-0"><?= escape($q['name']) ?></div>
                                                    <div class="small text-muted d-flex align-items-center gap-2">
                                                        <a href="mailto:<?= escape($q['email']) ?>" class="text-decoration-none text-muted" title="<?= escape($q['email']) ?>">
                                                            <?= escape($q['email']) ?>
                                                        </a>
                                                        <?php if (!empty($q['user_id'])): ?>
                                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2 py-0.5" style="font-size: 9px;">User #<?= (int)$q['user_id'] ?></span>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary-subtle text-secondary rounded-pill px-2 py-0.5" style="font-size: 9px;">Guest</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="text-dark fw-bold text-truncate" style="max-width: 175px;" title="<?= escape($clean_subject) ?>">
                                                <?= escape($clean_subject) ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="text-muted small text-truncate fst-italic" style="max-width: 210px;" title="<?= escape(trim($q['message'])) ?>">
                                                "<?= escape($msg_snippet) ?>"
                                            </div>
                                        </td>
                                        <td>
                                            <!-- Inline Quick Status Dropdown -->
                                            <form method="POST" action="queries.php" class="d-inline m-0">
                                                <?= renderCsrfInput() ?>
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="query_id" value="<?= (int)$q['id'] ?>">
                                                <select name="status" class="form-select form-select-sm table-status-select <?= $status_class ?>" onchange="this.form.submit()" title="Click to quickly change status">
                                                    <option value="new" <?= $is_new ? 'selected' : '' ?>>🔴 NEW</option>
                                                    <option value="read" <?= $is_read ? 'selected' : '' ?>>🟡 READ</option>
                                                    <option value="resolved" <?= $is_resolved ? 'selected' : '' ?>>🟢 RESOLVED</option>
                                                </select>
                                            </form>
                                        </td>
                                        <td class="small text-muted text-nowrap">
                                            <i class="bi bi-calendar3 me-1 text-muted"></i><?= formatDate($q['created_at']) ?>
                                        </td>
                                        <td class="pe-4 text-end text-nowrap">
                                            <!-- View Modal Trigger Button -->
                                            <button type="button" class="btn btn-admin-action-view me-1.5" data-bs-toggle="modal" data-bs-target="#viewQueryModal<?= (int)$q['id'] ?>" title="View Complete Query & Reply">
                                                <i class="bi bi-eye-fill me-1"></i> View
                                            </button>

                                            <!-- Delete Modal Trigger Button -->
                                            <button type="button" class="btn btn-admin-action-delete" data-bs-toggle="modal" data-bs-target="#deleteQueryModal<?= (int)$q['id'] ?>" title="Delete Query">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <div class="fs-1 text-muted mb-2"><i class="bi bi-inbox"></i></div>
                                        <h5 class="fw-bold mb-1">No Contact Queries Found</h5>
                                        <p class="small text-muted mb-3">No submitted queries matched your current search or filter criteria.</p>
                                        <?php if (!empty($search) || $status_filter !== 'all'): ?>
                                            <a href="queries.php" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                                <i class="bi bi-arrow-counterclockwise me-1"></i> Clear Filters
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <nav aria-label="Query pagination" class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="queries.php?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">Previous</a>
                        </li>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="queries.php?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="queries.php?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">Next</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>

    </div>
</div>

<!-- ======================================================== -->
<!-- MODALS PLACED SAFELY OUTSIDE THE TABLE CONTAINER         -->
<!-- ======================================================== -->
<?php if (!empty($queries)): ?>
    <?php foreach ($queries as $q): 
        $is_new = strtolower($q['status']) === 'new';
        $is_read = strtolower($q['status']) === 'read';
        $is_resolved = strtolower($q['status']) === 'resolved';

        $badge_class = 'bg-secondary';
        if ($is_new) $badge_class = 'bg-danger text-white';
        elseif ($is_read) $badge_class = 'bg-info text-dark';
        elseif ($is_resolved) $badge_class = 'bg-success text-white';

        $clean_subject = !empty($q['subject']) ? $q['subject'] : '(No Subject)';
    ?>
        <!-- View Query Modal for Query #<?= (int)$q['id'] ?> -->
        <div class="modal fade admin-query-modal" id="viewQueryModal<?= (int)$q['id'] ?>" tabindex="-1" aria-labelledby="viewQueryLabel<?= (int)$q['id'] ?>" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header px-4 py-3">
                        <div class="d-flex align-items-center gap-2.5">
                            <span class="badge <?= $badge_class ?> rounded-pill px-3 py-1.5 text-uppercase fw-bold" style="font-size: 0.78rem; letter-spacing: 0.04em;">
                                <?= escape(strtoupper($q['status'])) ?>
                            </span>
                            <h5 class="modal-title fw-bold mb-0 text-dark" id="viewQueryLabel<?= (int)$q['id'] ?>">Support Ticket #<?= (int)$q['id'] ?></h5>
                        </div>
                        <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    
                    <div class="modal-body p-4 d-flex flex-column gap-3">
                        <!-- 1. Customer Profile Banner with Integrated Status Switcher -->
                        <div class="admin-customer-banner">
                            <div class="admin-table-avatar" style="width: 46px; height: 46px; font-size: 1.15rem;"><?= strtoupper(substr($q['name'] ?? 'U', 0, 1)) ?></div>
                            <div class="flex-grow-1 min-width-0">
                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-1">
                                    <div class="d-flex align-items-center flex-wrap gap-2">
                                        <h5 class="fw-bold text-dark mb-0"><?= escape($q['name']) ?></h5>
                                        <?php if (!empty($q['user_id'])): ?>
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2.5 py-0.5 small fw-bold">
                                                <i class="bi bi-person-check-fill me-1"></i>User #<?= (int)$q['user_id'] ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary-subtle text-secondary rounded-pill px-2.5 py-0.5 small">
                                                <i class="bi bi-person-dash me-1"></i>Guest
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <!-- Quick Status Form -->
                                    <form method="POST" action="queries.php" class="d-flex align-items-center gap-1.5 m-0">
                                        <?= renderCsrfInput() ?>
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="query_id" value="<?= (int)$q['id'] ?>">
                                        <select name="status" class="form-select form-select-sm table-status-select <?= $status_class ?>" onchange="this.form.submit()" title="Click to change ticket status">
                                            <option value="new" <?= $is_new ? 'selected' : '' ?>>🔴 NEW</option>
                                            <option value="read" <?= $is_read ? 'selected' : '' ?>>🟡 READ (In Review)</option>
                                            <option value="resolved" <?= $is_resolved ? 'selected' : '' ?>>🟢 RESOLVED</option>
                                        </select>
                                    </form>
                                </div>
                                <div class="d-flex align-items-center flex-wrap gap-3 small text-muted">
                                    <a href="mailto:<?= escape($q['email']) ?>" class="text-decoration-none fw-semibold text-primary d-inline-flex align-items-center gap-1">
                                        <i class="bi bi-envelope-fill"></i> <?= escape($q['email']) ?>
                                    </a>
                                    <span>•</span>
                                    <span><i class="bi bi-calendar3 me-1"></i><?= formatDate($q['created_at']) ?> (<?= date('h:i A', strtotime($q['created_at'])) ?>)</span>
                                </div>
                            </div>
                        </div>

                        <!-- 2. Client Inquiry Message Box -->
                        <div class="admin-inquiry-box">
                            <div class="d-flex align-items-center justify-content-between mb-2 pb-1 border-bottom border-light-subtle flex-wrap gap-2">
                                <div class="admin-subject-pill m-0">
                                    <i class="bi bi-tag-fill me-1"></i> Subject: <?= escape($clean_subject) ?>
                                </div>
                                <span class="small text-muted"><i class="bi bi-chat-left-text me-1 text-primary"></i>Client Message</span>
                            </div>
                            <div class="admin-inquiry-text"><?= nl2br(escape(trim($q['message']))) ?></div>
                        </div>

                        <!-- 3. Current Support Reply (if already replied) -->
                        <?php if (!empty($q['admin_reply'])): ?>
                            <div class="chat-bubble-support animate-msg-appear">
                                <div class="d-flex align-items-center justify-content-between mb-2 pb-1 border-bottom border-success border-opacity-25 flex-wrap gap-2">
                                    <div class="fw-bold text-success d-flex align-items-center gap-2">
                                        <i class="bi bi-patch-check-fill fs-5"></i>
                                        <span>Current Support Reply (Sent to Client)</span>
                                    </div>
                                    <?php if (!empty($q['replied_at'])): ?>
                                        <div class="small text-muted"><i class="bi bi-clock me-1"></i><?= formatDate($q['replied_at']) ?> (<?= date('h:i A', strtotime($q['replied_at'])) ?>)</div>
                                    <?php endif; ?>
                                </div>
                                <div class="text-dark fw-medium" style="font-size: 0.96rem; line-height: 1.65;"><?= nl2br(escape(trim($q['admin_reply']))) ?></div>
                            </div>
                        <?php endif; ?>

                        <!-- 4. In-App Reply Composer Box for Admin -->
                        <div class="query-reply-card">
                            <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-2">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-reply-fill text-primary fs-5"></i>
                                    <h6 class="fw-bold text-dark mb-0">
                                        <?= !empty($q['admin_reply']) ? 'Update In-App Reply' : 'Send In-App Reply to Client' ?>
                                    </h6>
                                </div>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2.5 py-1" style="font-size: 11px;">
                                    <i class="bi bi-eye-fill me-1"></i>Live on User Support Portal
                                </span>
                            </div>
                            <p class="small text-muted mb-3">This reply is displayed directly to <?= escape($q['name']) ?> on their Lapify support dashboard.</p>

                            <form method="POST" action="queries.php">
                                <?= renderCsrfInput() ?>
                                <input type="hidden" name="action" value="send_reply">
                                <input type="hidden" name="query_id" value="<?= (int)$q['id'] ?>">

                                <div class="mb-3">
                                    <textarea name="admin_reply" rows="4" class="form-control query-reply-textarea w-100" placeholder="Type your official response message to <?= escape($q['name']) ?>..." required><?= escape($q['admin_reply'] ?? '') ?></textarea>
                                </div>

                                <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3">
                                    <div class="form-check">
                                        <input class="form-check-input cursor-pointer" type="checkbox" name="mark_resolved" value="1" id="markResolved<?= (int)$q['id'] ?>" <?= $is_resolved || empty($q['admin_reply']) ? 'checked' : '' ?>>
                                        <label class="form-check-label small text-muted cursor-pointer" for="markResolved<?= (int)$q['id'] ?>">
                                            Automatically mark inquiry as <strong class="text-success">Resolved</strong>
                                        </label>
                                    </div>
                                    <button type="submit" class="btn btn-send-reply">
                                        <i class="bi bi-send-fill"></i>
                                        <span><?= !empty($q['admin_reply']) ? 'Update & Send Reply' : 'Send Reply to Client' ?></span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="modal-footer bg-light px-4 py-3 d-flex justify-content-between align-items-center">
                        <div>
                            <a href="mailto:<?= escape($q['email']) ?>?subject=Re: <?= urlencode($q['subject'] ?? 'Support Inquiry') ?>" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                                <i class="bi bi-envelope me-1"></i> Open External Email
                            </a>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <?php if (!$is_read): ?>
                                <form method="POST" action="queries.php" class="d-inline m-0">
                                    <?= renderCsrfInput() ?>
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="query_id" value="<?= (int)$q['id'] ?>">
                                    <input type="hidden" name="status" value="read">
                                    <button type="submit" class="btn btn-info text-dark btn-sm rounded-pill px-3 fw-semibold">
                                        <i class="bi bi-envelope-open me-1"></i> Mark as Read
                                    </button>
                                </form>
                            <?php endif; ?>
                            <?php if (!$is_resolved): ?>
                                <form method="POST" action="queries.php" class="d-inline m-0">
                                    <?= renderCsrfInput() ?>
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="query_id" value="<?= (int)$q['id'] ?>">
                                    <input type="hidden" name="status" value="resolved">
                                    <button type="submit" class="btn btn-success btn-sm rounded-pill px-3 fw-semibold">
                                        <i class="bi bi-check-circle me-1"></i> Mark as Resolved
                                    </button>
                                </form>
                            <?php endif; ?>
                            <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Query Modal for Query #<?= (int)$q['id'] ?> -->
        <div class="modal fade" id="deleteQueryModal<?= (int)$q['id'] ?>" tabindex="-1" aria-labelledby="deleteQueryLabel<?= (int)$q['id'] ?>" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                    <div class="modal-header bg-danger text-white px-4 py-3">
                        <h5 class="modal-title fw-bold" id="deleteQueryLabel<?= (int)$q['id'] ?>"><i class="bi bi-exclamation-triangle-fill me-2"></i>Delete Contact Query</h5>
                        <button type="button" class="btn-close btn-close-white shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <p class="mb-2">Are you sure you want to permanently delete Query <strong>#<?= (int)$q['id'] ?></strong> from <strong><?= escape($q['name']) ?></strong>?</p>
                        <div class="p-3 bg-light rounded-3 border small text-muted mb-0">
                            <strong>Subject:</strong> <?= escape($clean_subject) ?>
                        </div>
                    </div>
                    <div class="modal-footer bg-light px-4 py-3">
                        <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                        <form method="POST" action="queries.php">
                            <?= renderCsrfInput() ?>
                            <input type="hidden" name="action" value="delete_query">
                            <input type="hidden" name="query_id" value="<?= (int)$q['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm rounded-pill px-3">
                                <i class="bi bi-trash me-1"></i> Permanently Delete
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/footer.php'; ?>
