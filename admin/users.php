<?php
// admin/users.php - User Management
$admin_title = "Manage Users | Lapify Admin";
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();

$conn = getDbConnection();
$current_admin = getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_user'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Session expired or invalid security token.');
    } else {
        $full_name = sanitizeInput($_POST['full_name'] ?? '');
        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        $phone = sanitizeInput($_POST['phone'] ?? '');
        $password = (string)($_POST['password'] ?? '');
        $is_admin = !empty($_POST['is_admin']);

        if ($full_name === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
            setFlash('error', 'Name, valid email, and password are required.');
        } else {
            $userCheck = mysqli_prepare($conn, "SELECT id FROM users WHERE LOWER(email) = LOWER(?) LIMIT 1");
            mysqli_stmt_bind_param($userCheck, 's', $email);
            mysqli_stmt_execute($userCheck);
            mysqli_stmt_store_result($userCheck);
            if (mysqli_stmt_num_rows($userCheck) > 0) {
                setFlash('error', 'A user with that email already exists.');
            } else {
                if ($is_admin) {
                    $adminCheck = mysqli_prepare($conn, "SELECT id FROM admins WHERE LOWER(email) = LOWER(?) LIMIT 1");
                    mysqli_stmt_bind_param($adminCheck, 's', $email);
                    mysqli_stmt_execute($adminCheck);
                    mysqli_stmt_store_result($adminCheck);
                    if (mysqli_stmt_num_rows($adminCheck) > 0) {
                        setFlash('error', 'That email is already assigned to an admin account.');
                    } else {
                        $password_hash = password_hash($password, PASSWORD_DEFAULT);
                        $role = $is_admin ? 'admin' : 'user';
                        $userStmt = mysqli_prepare($conn, "INSERT INTO users (full_name, email, phone, password, role, status, profile_image, created_at) VALUES (?, ?, ?, ?, ?, 'active', NULL, NOW())");
                        mysqli_stmt_bind_param($userStmt, 'sssss', $full_name, $email, $phone, $password_hash, $role);
                        if (mysqli_stmt_execute($userStmt)) {
                            $newUserId = mysqli_insert_id($conn);
                            if ($is_admin) {
                                $adminStmt = mysqli_prepare($conn, "INSERT INTO admins (username, full_name, email, password, secret_key, status) VALUES (?, ?, ?, ?, '', 'active')");
                                $adminUser = $full_name ?: $email;
                                $adminPasswordHash = password_hash($password, PASSWORD_DEFAULT);
                                mysqli_stmt_bind_param($adminStmt, 'ssss', $adminUser, $full_name, $email, $adminPasswordHash);
                                mysqli_stmt_execute($adminStmt);
                                mysqli_stmt_close($adminStmt);
                            }
                            setFlash('success', 'User added successfully.');
                        } else {
                            setFlash('error', 'Failed to create user account.');
                        }
                        mysqli_stmt_close($userStmt);
                    }
                    mysqli_stmt_close($adminCheck);
                } else {
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    $userStmt = mysqli_prepare($conn, "INSERT INTO users (full_name, email, phone, password, role, status, profile_image, created_at) VALUES (?, ?, ?, ?, 'user', 'active', NULL, NOW())");
                    mysqli_stmt_bind_param($userStmt, 'ssss', $full_name, $email, $phone, $password_hash);
                    if (mysqli_stmt_execute($userStmt)) {
                        setFlash('success', 'User added successfully.');
                    } else {
                        setFlash('error', 'Failed to create user account.');
                    }
                    mysqli_stmt_close($userStmt);
                }
            }
            mysqli_stmt_close($userCheck);
        }
    }
    header('Location: ' . BASE_URL . '/admin/users.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Session expired or invalid security token.');
    } else {
        $user_id = intval($_POST['user_id'] ?? 0);
        $full_name = sanitizeInput($_POST['full_name'] ?? '');
        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        $phone = sanitizeInput($_POST['phone'] ?? '');
        $password = trim((string)($_POST['password'] ?? ''));
        $is_admin = !empty($_POST['is_admin']);

        if ($user_id <= 0 || $full_name === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            setFlash('error', 'User name and valid email are required.');
        } else {
            $userCheck = mysqli_prepare($conn, "SELECT id FROM users WHERE LOWER(email) = LOWER(?) AND id != ? LIMIT 1");
            mysqli_stmt_bind_param($userCheck, 'si', $email, $user_id);
            mysqli_stmt_execute($userCheck);
            mysqli_stmt_store_result($userCheck);
            if (mysqli_stmt_num_rows($userCheck) > 0) {
                setFlash('error', 'Another user already has that email address.');
            } else {
                $currentlyAdmin = mysqli_prepare($conn, "SELECT id FROM admins WHERE LOWER(email) = LOWER(?) LIMIT 1");
                mysqli_stmt_bind_param($currentlyAdmin, 's', $email);
                mysqli_stmt_execute($currentlyAdmin);
                mysqli_stmt_store_result($currentlyAdmin);
                $adminExists = mysqli_stmt_num_rows($currentlyAdmin) > 0;
                mysqli_stmt_close($currentlyAdmin);

                if ($is_admin && $adminExists === false) {
                    $adminStmt = mysqli_prepare($conn, "INSERT INTO admins (username, full_name, email, password, secret_key, status) VALUES (?, ?, ?, ?, '', 'active')");
                    $username = $full_name ?: $email;
                    $adminPassword = password_hash($password !== '' ? $password : 'Lapify@123', PASSWORD_DEFAULT);
                    mysqli_stmt_bind_param($adminStmt, 'ssss', $username, $full_name, $email, $adminPassword);
                    mysqli_stmt_execute($adminStmt);
                    mysqli_stmt_close($adminStmt);
                }

                if (!$is_admin && $adminExists) {
                    $removeAdminStmt = mysqli_prepare($conn, "DELETE FROM admins WHERE LOWER(email) = LOWER(?)");
                    mysqli_stmt_bind_param($removeAdminStmt, 's', $email);
                    mysqli_stmt_execute($removeAdminStmt);
                    mysqli_stmt_close($removeAdminStmt);
                }

                $role = $is_admin ? 'admin' : 'user';
                $updateSql = "UPDATE users SET full_name = ?, email = ?, phone = ?, role = ?";
                $params = [$full_name, $email, $phone, $role];
                $types = 'ssss';
                if ($password !== '') {
                    $updateSql .= ", password = ?";
                    $params[] = password_hash($password, PASSWORD_DEFAULT);
                    $types .= 's';
                }
                $updateSql .= " WHERE id = ?";
                $params[] = $user_id;
                $types .= 'i';

                $userUpdate = mysqli_prepare($conn, $updateSql);
                mysqli_stmt_bind_param($userUpdate, $types, ...$params);
                if (mysqli_stmt_execute($userUpdate)) {
                    if ($is_admin) {
                        $updateAdmin = mysqli_prepare($conn, "UPDATE admins SET full_name = ?, email = ?, username = ? WHERE LOWER(email) = LOWER(?)");
                        $username = $full_name ?: $email;
                        $newPassword = $password !== '' ? password_hash($password, PASSWORD_DEFAULT) : null;
                        if ($newPassword !== null) {
                            mysqli_stmt_bind_param($updateAdmin, 'ssss', $full_name, $email, $username, $email);
                            mysqli_stmt_execute($updateAdmin);
                            $pwUpdate = mysqli_prepare($conn, "UPDATE admins SET password = ? WHERE LOWER(email) = LOWER(?)");
                            mysqli_stmt_bind_param($pwUpdate, 'ss', $newPassword, $email);
                            mysqli_stmt_execute($pwUpdate);
                            mysqli_stmt_close($pwUpdate);
                        } else {
                            mysqli_stmt_bind_param($updateAdmin, 'ssss', $full_name, $email, $username, $email);
                            mysqli_stmt_execute($updateAdmin);
                        }
                        mysqli_stmt_close($updateAdmin);
                    }
                    setFlash('success', 'User updated successfully.');
                } else {
                    setFlash('error', 'Failed to update user.');
                }
                mysqli_stmt_close($userUpdate);
            }
            mysqli_stmt_close($userCheck);
        }
    }
    header('Location: ' . BASE_URL . '/admin/users.php');
    exit();
}

// Handle User Delete Action
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $target_user_id = intval($_GET['id']);

    $u_stmt = mysqli_prepare($conn, "SELECT email, profile_image FROM users WHERE id = ?");
    mysqli_stmt_bind_param($u_stmt, "i", $target_user_id);
    mysqli_stmt_execute($u_stmt);
    $u_res = mysqli_stmt_get_result($u_stmt);
    $targetUser = mysqli_fetch_assoc($u_res);
    mysqli_stmt_close($u_stmt);

    if (!$targetUser) {
        setFlash('error', "User not found.");
    } elseif ($target_user_id === (int)($current_admin['id'] ?? 0) || strtolower($targetUser['email']) === strtolower($current_admin['email'] ?? '')) {
        setFlash('error', "You cannot delete your own active administrator account.");
    } else {
        $profile_img = $targetUser['profile_image'];

        // Clean up corresponding admin record if exists
        $delAdmin = mysqli_prepare($conn, "DELETE FROM admins WHERE LOWER(email) = LOWER(?)");
        mysqli_stmt_bind_param($delAdmin, "s", $targetUser['email']);
        mysqli_stmt_execute($delAdmin);
        mysqli_stmt_close($delAdmin);

        $l_stmt = mysqli_prepare($conn, "SELECT image FROM laptops WHERE user_id = ?");
        mysqli_stmt_bind_param($l_stmt, "i", $target_user_id);
        mysqli_stmt_execute($l_stmt);
        $l_res = mysqli_stmt_get_result($l_stmt);
        while ($row = mysqli_fetch_assoc($l_res)) {
            deleteImageFile($row['image'], LAPTOP_UPLOAD_DIR);
        }
        mysqli_stmt_close($l_stmt);

        deleteImageFile($profile_img, PROFILE_UPLOAD_DIR);

        $del_stmt = mysqli_prepare($conn, "DELETE FROM users WHERE id = ?");
        mysqli_stmt_bind_param($del_stmt, "i", $target_user_id);
        if (mysqli_stmt_execute($del_stmt)) {
            setFlash('success', "User and all associated listings were deleted successfully.");
        } else {
            setFlash('error', "Failed to delete user.");
        }
        mysqli_stmt_close($del_stmt);
    }
    header("Location: " . BASE_URL . "/admin/users.php");
    exit();
}

$search = sanitizeInput($_GET['search'] ?? '');
$per_page = intval($_GET['per_page'] ?? 15);
if (!in_array($per_page, [10, 15, 25, 50, 100], true)) {
    $per_page = 15;
}
$page = max(1, intval($_GET['page'] ?? 1));

$where_sql = "1=1";
$params = [];
$param_types = "";

if (!empty($search)) {
    $where_sql .= " AND (u.full_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
    $s = "%{$search}%";
    $params = [$s, $s, $s];
    $param_types = "sss";
}

$count_sql = "SELECT COUNT(*) FROM users u WHERE {$where_sql}";
$count_stmt = mysqli_prepare($conn, $count_sql);
if (!empty($param_types)) {
    mysqli_stmt_bind_param($count_stmt, $param_types, ...$params);
}
mysqli_stmt_execute($count_stmt);
mysqli_stmt_bind_result($count_stmt, $total_users);
mysqli_stmt_fetch($count_stmt);
mysqli_stmt_close($count_stmt);
$total_users = (int)($total_users ?? 0);
$total_pages = max(1, (int)ceil($total_users / $per_page));
$page = min($page, $total_pages);
$offset = ($page - 1) * $per_page;

$sql = "SELECT u.*, CASE WHEN a.email IS NOT NULL THEN 'admin' ELSE 'user' END AS role 
        FROM users u 
        LEFT JOIN admins a ON u.email = a.email 
        WHERE {$where_sql} 
        ORDER BY u.id DESC
        LIMIT ? OFFSET ?";

$list_params = $params;
$list_params[] = $per_page;
$list_params[] = $offset;
$list_types = $param_types . "ii";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, $list_types, ...$list_params);
mysqli_stmt_execute($stmt);
$users_result = mysqli_stmt_get_result($stmt);
?>

<div class="dashboard-wrapper">
    <?php require_once __DIR__ . '/sidebar.php'; ?>

    <div class="dashboard-content">
        <?php require_once __DIR__ . '/topbar.php'; ?>
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
            <div>
                <h3 class="fw-bold mb-1"><i class="bi bi-people-fill text-primary me-2"></i>User Management</h3>
                <p class="text-muted mb-0">View, search, and manage registered Lapify accounts (<?= $total_users ?> total)</p>
            </div>
            <button type="button" class="btn btn-primary font-weight-bold rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addUserModal">
                <i class="bi bi-plus-lg me-1"></i>Add User
            </button>
        </div>

        <!-- Filter Bar -->
        <div class="card border-0 shadow-sm rounded-4 p-3 mb-4">
            <form action="users.php" method="GET" class="row g-2 align-items-center">
                <div class="col-md-7">
                    <input type="text" name="search" class="form-control form-control-sm" value="<?= escape($search) ?>" placeholder="Search name, email, phone...">
                </div>
                <div class="col-md-3">
                    <select name="per_page" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="15" <?= $per_page == 15 ? 'selected' : '' ?>>15 per page</option>
                        <option value="25" <?= $per_page == 25 ? 'selected' : '' ?>>25 per page</option>
                        <option value="50" <?= $per_page == 50 ? 'selected' : '' ?>>50 per page</option>
                        <option value="100" <?= $per_page == 100 ? 'selected' : '' ?>>100 per page</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-sm btn-primary w-100 font-weight-bold">Filter</button>
                </div>
            </form>
        </div>

        <?php displayFlash(); ?>

        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-body p-3.5 p-md-4">
                <?php if ($total_users > 0): ?>
                    <div class="d-flex align-items-center justify-content-between mb-3 px-1">
                        <div class="text-muted small">
                            Showing <strong class="text-dark"><?= $offset + 1 ?>–<?= min($offset + $per_page, $total_users) ?></strong> of <strong class="text-dark"><?= $total_users ?></strong> users
                        </div>
                    </div>
                    <div class="d-flex flex-column gap-3.5">
                        <?php while ($usr = mysqli_fetch_assoc($users_result)): 
                            $avatar = !empty($usr['profile_image']) && file_exists(PROFILE_UPLOAD_DIR . $usr['profile_image'])
                                ? BASE_URL . '/uploads/profiles/' . $usr['profile_image']
                                : 'https://ui-avatars.com/api/?name=' . urlencode($usr['full_name']) . '&background=2563eb&color=fff&bold=true';
                            $is_admin = ($usr['role'] === 'admin');
                        ?>
                            <div class="posting-item-card p-3.5 p-md-4 rounded-4 border d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-4">
                                <!-- Left: Avatar & User Info -->
                                <div class="d-flex align-items-center gap-3 gap-md-4" style="min-width: 0;">
                                    <img src="<?= escape($avatar) ?>" alt="<?= escape($usr['full_name']) ?>" loading="lazy" decoding="async" class="rounded-circle border flex-shrink-0 shadow-2xs me-3" style="width: 52px; height: 52px; object-fit: cover;">
                                    <div class="d-flex flex-column gap-1 ps-1" style="min-width: 0;">
                                        <div class="d-flex align-items-center gap-2">
                                            <h5 class="fw-bold mb-0 text-dark posting-title text-truncate" style="font-size: 1.08rem;" title="<?= escape($usr['full_name']) ?>">
                                                <?= escape($usr['full_name']) ?>
                                            </h5>
                                            <span class="badge rounded-pill px-2.5 py-0.5 fw-semibold text-capitalize <?= $is_admin ? 'bg-primary-subtle text-primary border border-primary-subtle' : 'bg-secondary-subtle text-secondary' ?>" style="font-size: 0.72rem;">
                                                <?= escape($usr['role']) ?>
                                            </span>
                                        </div>
                                        <div class="d-flex flex-wrap align-items-center gap-2 text-muted small" style="font-size: 0.85rem;">
                                            <span class="text-slate-600"><i class="bi bi-envelope me-1"></i><?= escape($usr['email']) ?></span>
                                            <?php if (!empty($usr['phone'])): ?>
                                                <span class="text-slate-300">•</span>
                                                <span><i class="bi bi-telephone me-1"></i><?= escape($usr['phone']) ?></span>
                                            <?php endif; ?>
                                            <span class="text-slate-300">•</span>
                                            <span><i class="bi bi-calendar-event me-1"></i>Joined <?= formatDate($usr['created_at']) ?></span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Right: Action Buttons -->
                                <div class="d-flex align-items-center gap-2 ms-lg-auto flex-shrink-0">
                                    <button type="button" class="btn btn-outline-primary rounded-pill px-3.5 py-2 fw-semibold d-inline-flex align-items-center gap-1.5 shadow-sm" data-bs-toggle="modal" data-bs-target="#editUserModal" data-user-id="<?= $usr['id'] ?>" data-user-name="<?= escape($usr['full_name']) ?>" data-user-email="<?= escape($usr['email']) ?>" data-user-phone="<?= escape($usr['phone'] ?? '') ?>" data-user-role="<?= escape($usr['role']) ?>" title="Edit User">
                                        <i class="bi bi-pencil-square"></i>
                                        <span>Edit</span>
                                    </button>
                                    <?php if ($usr['id'] !== $current_admin['id']): ?>
                                        <button type="button" class="btn btn-light border text-danger rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;" 
                                                data-bs-toggle="modal" data-bs-target="#deleteConfirmModal" 
                                                data-id="<?= $usr['id'] ?>" 
                                                data-title="<?= escape($usr['full_name']) ?>" 
                                                data-delete-url="users.php?action=delete&id=<?= $usr['id'] ?>" 
                                                title="Delete User">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted border px-3 py-2 rounded-pill">Current Admin</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>

                    <?php if ($total_pages > 1): ?>
                        <nav class="mt-4 d-flex justify-content-center" aria-label="Users pagination">
                            <ul class="pagination pagination-sm gap-1">
                                <?php
                                $query_params = [
                                    'search' => $search,
                                    'per_page' => $per_page,
                                ];
                                $query_params = array_filter($query_params, fn($v) => $v !== '' && $v !== null);
                                
                                $prev_url = 'users.php?' . http_build_query(array_merge($query_params, ['page' => max(1, $page - 1)]));
                                $next_url = 'users.php?' . http_build_query(array_merge($query_params, ['page' => min($total_pages, $page + 1)]));
                                ?>
                                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                    <a class="page-link rounded-3" href="<?= $prev_url ?>"><i class="bi bi-chevron-left me-1"></i>Previous</a>
                                </li>
                                <?php 
                                $start_p = max(1, $page - 2);
                                $end_p = min($total_pages, $page + 2);
                                if ($start_p > 1) {
                                    echo '<li class="page-item"><a class="page-link rounded-3" href="users.php?' . http_build_query(array_merge($query_params, ['page' => 1])) . '">1</a></li>';
                                    if ($start_p > 2) echo '<li class="page-item disabled"><span class="page-link border-0">…</span></li>';
                                }
                                for ($p = $start_p; $p <= $end_p; $p++): 
                                    $p_url = 'users.php?' . http_build_query(array_merge($query_params, ['page' => $p]));
                                ?>
                                    <li class="page-item <?= $page === $p ? 'active' : '' ?>">
                                        <a class="page-link rounded-3 <?= $page === $p ? 'fw-bold' : '' ?>" href="<?= $p_url ?>"><?= $p ?></a>
                                    </li>
                                <?php endfor; 
                                if ($end_p < $total_pages) {
                                    if ($end_p < $total_pages - 1) echo '<li class="page-item disabled"><span class="page-link border-0">…</span></li>';
                                    echo '<li class="page-item"><a class="page-link rounded-3" href="users.php?' . http_build_query(array_merge($query_params, ['page' => $total_pages])) . '">' . $total_pages . '</a></li>';
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
                            <i class="bi bi-people fs-2"></i>
                        </div>
                        <h5 class="fw-bold mb-1">No Users Found</h5>
                        <p class="text-muted small mb-0">No user accounts matched your search criteria.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <form action="users.php" method="POST" class="needs-validation" novalidate>
                <?= renderCsrfInput() ?>
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold"><i class="bi bi-person-plus text-primary me-2"></i>Add User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-4">
                    <div class="mb-3">
                        <label for="add_full_name" class="form-label font-weight-bold">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" id="add_full_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="add_email" class="form-label font-weight-bold">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" id="add_email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="add_phone" class="form-label font-weight-bold">Phone</label>
                        <input type="text" name="phone" id="add_phone" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label for="add_password" class="form-label font-weight-bold">Password <span class="text-danger">*</span></label>
                        <input type="password" name="password" id="add_password" class="form-control" required>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="is_admin" id="add_is_admin" class="form-check-input">
                        <label for="add_is_admin" class="form-check-label">Grant admin privileges</label>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-3 px-4 font-weight-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="create_user" class="btn btn-primary rounded-3 px-4 font-weight-bold">Save User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <form action="users.php" method="POST" class="needs-validation" novalidate>
                <?= renderCsrfInput() ?>
                <input type="hidden" name="user_id" id="edit_user_id">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square text-primary me-2"></i>Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-4">
                    <div class="mb-3">
                        <label for="edit_full_name" class="form-label font-weight-bold">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" id="edit_full_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_email" class="form-label font-weight-bold">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" id="edit_email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_phone" class="form-label font-weight-bold">Phone</label>
                        <input type="text" name="phone" id="edit_phone" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label for="edit_password" class="form-label font-weight-bold">New Password</label>
                        <input type="password" name="password" id="edit_password" class="form-control" placeholder="Leave blank to keep current password">
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="is_admin" id="edit_is_admin" class="form-check-input">
                        <label for="edit_is_admin" class="form-check-label">Admin access</label>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-3 px-4 font-weight-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_user" class="btn btn-primary rounded-3 px-4 font-weight-bold">Update User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i>Delete User Account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-4">
                Are you sure you want to delete user <strong class="modal-item-title text-dark"></strong>? This will permanently delete their account, all published laptop listings, wishlist items, and orders.
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light rounded-3 px-4 font-weight-bold" data-bs-dismiss="modal">Cancel</button>
                <a href="#" class="btn btn-danger btn-confirm-delete rounded-3 px-4 font-weight-bold">Delete User</a>
            </div>
        </div>
    </div>
</div>

<?php 
mysqli_stmt_close($stmt);
require_once __DIR__ . '/footer.php'; 
?>
