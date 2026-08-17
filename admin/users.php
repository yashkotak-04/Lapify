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
                        $userStmt = mysqli_prepare($conn, "INSERT INTO users (full_name, email, phone, password, profile_image, created_at) VALUES (?, ?, ?, ?, NULL, NOW())");
                        mysqli_stmt_bind_param($userStmt, 'ssss', $full_name, $email, $phone, $password_hash);
                        if (mysqli_stmt_execute($userStmt)) {
                            $newUserId = mysqli_insert_id($conn);
                            $adminStmt = mysqli_prepare($conn, "INSERT INTO admins (username, full_name, email, password, secret_key, status) VALUES (?, ?, ?, ?, '', 'active')");
                            $adminUser = $full_name ?: $email;
                            $adminPasswordHash = password_hash($password, PASSWORD_DEFAULT);
                            mysqli_stmt_bind_param($adminStmt, 'ssss', $adminUser, $full_name, $email, $adminPasswordHash);
                            mysqli_stmt_execute($adminStmt);
                            mysqli_stmt_close($adminStmt);
                            setFlash('success', 'User added successfully.');
                        } else {
                            setFlash('error', 'Failed to create user account.');
                        }
                        mysqli_stmt_close($userStmt);
                    }
                    mysqli_stmt_close($adminCheck);
                } else {
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    $userStmt = mysqli_prepare($conn, "INSERT INTO users (full_name, email, phone, password, profile_image, created_at) VALUES (?, ?, ?, ?, NULL, NOW())");
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

                if (!$is_admin) {
                    $removeAdminStmt = mysqli_prepare($conn, "DELETE FROM admins WHERE LOWER(email) = LOWER(?)");
                    mysqli_stmt_bind_param($removeAdminStmt, 's', $email);
                    mysqli_stmt_execute($removeAdminStmt);
                    mysqli_stmt_close($removeAdminStmt);
                }

                $updateSql = "UPDATE users SET full_name = ?, email = ?, phone = ?";
                $params = [$full_name, $email, $phone];
                $types = 'sss';
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

    if ($target_user_id === $current_admin['id']) {
        setFlash('error', "You cannot delete your own active administrator account.");
    } else {
        $u_stmt = mysqli_prepare($conn, "SELECT profile_image FROM users WHERE id = ?");
        mysqli_stmt_bind_param($u_stmt, "i", $target_user_id);
        mysqli_stmt_execute($u_stmt);
        mysqli_stmt_bind_result($u_stmt, $profile_img);
        mysqli_stmt_fetch($u_stmt);
        mysqli_stmt_close($u_stmt);

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
$where_sql = "1=1";
$params = [];
$param_types = "";

if (!empty($search)) {
    $where_sql .= " AND (full_name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $s = "%{$search}%";
    $params = [$s, $s, $s];
    $param_types = "sss";
}

$sql = "SELECT u.*, CASE WHEN a.email IS NOT NULL THEN 'admin' ELSE 'user' END AS role FROM users u LEFT JOIN admins a ON u.email = a.email WHERE {$where_sql} ORDER BY u.id DESC";
$stmt = mysqli_prepare($conn, $sql);
if (!empty($param_types)) {
    mysqli_stmt_bind_param($stmt, $param_types, ...$params);
}
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
                <p class="text-muted mb-0">View, search, and manage registered Lapify accounts</p>
            </div>
            <button type="button" class="btn btn-primary font-weight-bold rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addUserModal">
                <i class="bi bi-plus-lg me-1"></i>Add User
            </button>
        </div>

        <?php displayFlash(); ?>

        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-muted small text-uppercase font-weight-bold">
                            <tr>
                                <th>User Profile</th>
                                <th>Phone</th>
                                <th>Role</th>
                                <th>Joined Date</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($users_result) > 0): ?>
                                <?php while ($usr = mysqli_fetch_assoc($users_result)): 
                                    $avatar = !empty($usr['profile_image']) && file_exists(PROFILE_UPLOAD_DIR . $usr['profile_image'])
                                        ? BASE_URL . '/uploads/profiles/' . $usr['profile_image']
                                        : 'https://ui-avatars.com/api/?name=' . urlencode($usr['full_name']) . '&background=2563eb&color=fff';
                                ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                <img src="<?= escape($avatar) ?>" alt="" class="user-avatar-sm shadow-sm">
                                                <div>
                                                    <div class="fw-bold text-dark"><?= escape($usr['full_name']) ?></div>
                                                    <div class="small text-muted"><?= escape($usr['email']) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="small text-secondary"><?= escape($usr['phone'] ?? 'N/A') ?></td>
                                        <td>
                                            <span class="badge <?= $usr['role'] === 'admin' ? 'badge-admin-role' : 'badge-user-role' ?> rounded-pill text-capitalize">
                                                <?= escape($usr['role']) ?>
                                            </span>
                                        </td>
                                        <td class="small text-muted"><?= formatDate($usr['created_at']) ?></td>
                                        <td class="text-center">
                                            <div class="d-inline-flex align-items-center justify-content-center gap-2">
                                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editUserModal" data-user-id="<?= $usr['id'] ?>" data-user-name="<?= escape($usr['full_name']) ?>" data-user-email="<?= escape($usr['email']) ?>" data-user-phone="<?= escape($usr['phone'] ?? '') ?>" data-user-role="<?= escape($usr['role']) ?>" title="Edit User">
                                                    <i class="bi bi-pencil-square"></i> Edit
                                                </button>
                                                <?php if ($usr['id'] !== $current_admin['id']): ?>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                                            data-bs-toggle="modal" data-bs-target="#deleteConfirmModal" 
                                                            data-id="<?= $usr['id'] ?>" 
                                                            data-title="<?= escape($usr['full_name']) ?>" 
                                                            data-delete-url="users.php?action=delete&id=<?= $usr['id'] ?>" 
                                                            title="Delete User">
                                                        <i class="bi bi-trash"></i> Delete
                                                    </button>
                                                <?php else: ?>
                                                    <span class="badge bg-light text-muted border ms-1">Current Admin</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">No users found matching search criteria.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
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
