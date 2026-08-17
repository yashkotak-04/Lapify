<?php
// admin/profile.php - Admin Profile & Security
$admin_title = 'Admin Profile | Lapify';
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();

$conn = getDbConnection();
$session_admin = getCurrentUser();

$admin_stmt = mysqli_prepare($conn, "SELECT * FROM admins WHERE id = ?");
mysqli_stmt_bind_param($admin_stmt, 'i', $session_admin['id']);
mysqli_stmt_execute($admin_stmt);
$admin = mysqli_fetch_assoc(mysqli_stmt_get_result($admin_stmt));
mysqli_stmt_close($admin_stmt);

if (!$admin) {
    session_destroy();
    header('Location: ' . BASE_URL . '/admin/login.php');
    exit();
}

$profile_errors = [];
$password_errors = [];

$isProfileSubmit = $_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['update_profile']) || (isset($_POST['form_action']) && $_POST['form_action'] === 'update_profile'));
if ($isProfileSubmit) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $profile_errors[] = 'Session expired or invalid security token. Please try again.';
    }

    $full_name = sanitizeInput($_POST['full_name'] ?? '');
    $email = strtolower(trim(sanitizeInput($_POST['email'] ?? '')));
    $phone = sanitizeInput($_POST['phone'] ?? '');

    if (empty($full_name)) {
        $profile_errors[] = 'Full name is required.';
    }

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $profile_errors[] = 'A valid email address is required.';
    }

    if (!empty($phone) && !preg_match('/^[0-9]{10}$/', $phone)) {
        $profile_errors[] = 'Phone number must be exactly 10 digits.';
    }

    $admin['full_name'] = $full_name;
    $admin['email'] = $email;
    $admin['phone'] = $phone;

    if (empty($profile_errors)) {
        $dup_stmt = mysqli_prepare($conn, "SELECT id FROM admins WHERE LOWER(email) = LOWER(?) AND id != ? LIMIT 1");
        mysqli_stmt_bind_param($dup_stmt, 'si', $email, $admin['id']);
        mysqli_stmt_execute($dup_stmt);
        mysqli_stmt_store_result($dup_stmt);
        if (mysqli_stmt_num_rows($dup_stmt) > 0) {
            $profile_errors[] = 'That email is already used by another admin account.';
        }
        mysqli_stmt_close($dup_stmt);
    }

    if (empty($profile_errors)) {
        $user_check = mysqli_prepare($conn, "SELECT id FROM users WHERE LOWER(email) = LOWER(?) LIMIT 1");
        mysqli_stmt_bind_param($user_check, 's', $email);
        mysqli_stmt_execute($user_check);
        mysqli_stmt_store_result($user_check);
        if (mysqli_stmt_num_rows($user_check) > 0) {
            $profile_errors[] = 'That email is already registered to a user account.';
        }
        mysqli_stmt_close($user_check);
    }

    $uploaded_image = $admin['profile_image'] ?? null;
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $up_err = '';
        $new_img = uploadImage($_FILES['profile_image'], PROFILE_UPLOAD_DIR, $up_err);
        if ($new_img === false) {
            $profile_errors[] = $up_err;
        } else {
            if (!empty($admin['profile_image'])) {
                deleteImageFile($admin['profile_image'], PROFILE_UPLOAD_DIR);
            }
            $uploaded_image = $new_img;
        }
    }

    if (empty($profile_errors)) {
        $update_stmt = mysqli_prepare($conn, "UPDATE admins SET full_name = ?, email = ?, phone = ?, profile_image = ? WHERE id = ?");
        mysqli_stmt_bind_param($update_stmt, 'ssssi', $full_name, $email, $phone, $uploaded_image, $admin['id']);

        if (mysqli_stmt_execute($update_stmt)) {
            $_SESSION['full_name']     = $full_name;
            $_SESSION['email']         = $email;
            $_SESSION['phone']         = $phone;
            $_SESSION['profile_image'] = $uploaded_image;
            getCurrentUser(true); // Refresh cached admin
            setFlash('success', 'Admin profile updated successfully.');
            header('Location: ' . BASE_URL . '/admin/profile.php');
            exit();
        } else {
            $profile_errors[] = 'Failed to update the admin profile. Please try again.';
        }

        mysqli_stmt_close($update_stmt);
    }
}

$isPasswordSubmit = $_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['change_password']) || (isset($_POST['form_action']) && $_POST['form_action'] === 'change_password'));
if ($isPasswordSubmit) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $password_errors[] = 'Session expired or invalid security token. Please try again.';
    }

    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($current_password)) {
        $password_errors[] = 'Current password is required.';
    }

    $password_rules = validatePasswordStrength($new_password);
    if (!$password_rules['is_valid']) {
        $password_errors[] = 'New password must be at least 8 characters and include uppercase, lowercase, number, and special characters.';
    }

    if ($new_password !== $confirm_password) {
        $password_errors[] = 'New password confirmation does not match.';
    }

    if (empty($password_errors)) {
        if (!password_verify($current_password, $admin['password'])) {
            $password_errors[] = 'Current password is incorrect.';
        } else {
            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $pass_stmt = mysqli_prepare($conn, "UPDATE admins SET password = ? WHERE id = ?");
            mysqli_stmt_bind_param($pass_stmt, 'si', $new_hash, $admin['id']);
            if (mysqli_stmt_execute($pass_stmt)) {
                setFlash('success', 'Password updated successfully.');
                header('Location: ' . BASE_URL . '/admin/profile.php');
                exit();
            } else {
                $password_errors[] = 'Failed to update the password. Please try again.';
            }
            mysqli_stmt_close($pass_stmt);
        }
    }
}

$avatar_url = !empty($admin['profile_image']) && file_exists(PROFILE_UPLOAD_DIR . $admin['profile_image'])
    ? BASE_URL . '/uploads/profiles/' . $admin['profile_image']
    : 'https://ui-avatars.com/api/?name=' . urlencode($admin['full_name'] ?? 'Admin') . '&background=2563eb&color=fff&size=200';

$profile_percent = 0;
if (!empty($admin['full_name'])) $profile_percent += 25;
if (!empty($admin['email'])) $profile_percent += 25;
if (!empty($admin['phone'])) $profile_percent += 25;
if (!empty($admin['profile_image'])) $profile_percent += 25;
?>

<div class="dashboard-wrapper">
    <?php require_once __DIR__ . '/sidebar.php'; ?>

    <div class="dashboard-content admin-profile-page">
        <?php require_once __DIR__ . '/topbar.php'; ?>
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
            <div>
                <h3 class="fw-bold mb-1"><i class="bi bi-person-gear text-primary me-2"></i>Admin Profile</h3>
                <p class="text-muted mb-0">Manage your account details and security settings</p>
            </div>
            <a href="<?= BASE_URL ?>/admin/dashboard.php" class="btn btn-outline-secondary rounded-pill px-3 py-2 font-weight-bold d-inline-flex align-items-center gap-1.5 shadow-sm">
                <i class="bi bi-arrow-left"></i> Back to Dashboard
            </a>
        </div>

        <?php displayFlash(); ?>

        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card profile-summary-card border-0 shadow-sm rounded-4 text-center p-4">
                    <div class="position-relative d-inline-block mx-auto mb-3">
                        <img src="<?= escape($avatar_url) ?>" alt="Admin avatar" class="profile-avatar shadow" style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover;">
                    </div>
                    <h4 class="fw-bold mb-1"><?= escape($admin['full_name'] ?? 'Admin') ?></h4>
                    <p class="text-muted small mb-3"><?= escape($admin['email'] ?? '') ?></p>
                    <span class="badge bg-primary-subtle text-primary fw-semibold px-3 py-2 rounded-pill mb-4 text-capitalize">Administrator</span>

                    <div class="p-3 bg-light rounded-3 text-start mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="small fw-bold">Profile Completion</span>
                            <span class="small fw-bold text-primary"><?= $profile_percent ?>%</span>
                        </div>
                        <div class="progress progress-thin mb-0">
                            <div class="progress-bar bg-primary" role="progressbar" style="width: <?= $profile_percent ?>%;"></div>
                        </div>
                    </div>

                    <div class="profile-meta text-start small text-muted">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-calendar3 text-primary"></i>
                            <span>Joined <?= formatDate($admin['created_at'] ?? '') ?></span>
                        </div>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-telephone text-primary"></i>
                            <span><?= !empty($admin['phone']) ? escape($admin['phone']) : 'No phone number added' ?></span>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-shield-check text-success"></i>
                            <span>Security verified</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 mb-4">
                    <h4 class="fw-bold mb-4"><i class="bi bi-person-lines-fill text-primary me-2"></i>Personal Information</h4>

                    <?php if (!empty($profile_errors)): ?>
                        <div class="alert alert-danger border-0 rounded-3 shadow-sm mb-4">
                            <ul class="mb-0 ps-3">
                                <?php foreach ($profile_errors as $err): ?>
                                    <li><?= escape($err) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form action="profile.php" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                        <?= renderCsrfInput() ?>
                        <input type="hidden" name="form_action" value="update_profile">

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="full_name" class="form-label font-weight-bold">Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="full_name" id="full_name" class="form-control" value="<?= escape($admin['full_name'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label font-weight-bold">Email Address <span class="text-danger">*</span></label>
                                <input type="email" name="email" id="email" class="form-control" value="<?= escape($admin['email'] ?? '') ?>" required>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="phone" class="form-label font-weight-bold">Phone Number (10 digits)</label>
                                <input type="tel" name="phone" id="phone" class="form-control" value="<?= escape($admin['phone'] ?? '') ?>" placeholder="9876543210" maxlength="10" pattern="[0-9]{10}" inputmode="numeric">
                            </div>
                            <div class="col-md-6">
                                <label for="profile_image" class="form-label font-weight-bold">Profile Photo (Max 2MB)</label>
                                <div class="custom-file-upload-wrapper">
                                    <input type="file" name="profile_image" id="profile_image" class="d-none" accept="image/jpeg,image/png,image/webp">
                                    <label for="profile_image" class="form-control d-flex align-items-center justify-content-between px-3 cursor-pointer rounded-3 shadow-none border" style="height: 42px; background: var(--surface-card, #ffffff);">
                                        <span class="btn btn-sm btn-primary rounded-pill px-3 font-weight-bold d-inline-flex align-items-center gap-1 shadow-sm" style="font-size: 0.8rem; padding: 0.25rem 0.75rem;">
                                            <i class="bi bi-cloud-arrow-up-fill"></i> Choose Photo
                                        </span>
                                        <span id="file-name-display" class="small text-muted text-truncate ms-2" style="max-width: 200px; font-size: 0.85rem;">No photo selected</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <button type="submit" name="update_profile" class="btn btn-primary rounded-pill px-4 py-2.5 font-weight-bold d-inline-flex align-items-center gap-2 shadow-sm text-white">
                            <i class="bi bi-check-circle-fill"></i> Save Profile
                        </button>
                    </form>
                </div>

                <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5">
                    <h4 class="fw-bold mb-4"><i class="bi bi-shield-lock text-primary me-2"></i>Change Password</h4>

                    <?php if (!empty($password_errors)): ?>
                        <div class="alert alert-danger border-0 rounded-3 shadow-sm mb-4">
                            <ul class="mb-0 ps-3">
                                <?php foreach ($password_errors as $err): ?>
                                    <li><?= escape($err) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form action="profile.php" method="POST" class="needs-validation" novalidate>
                        <?= renderCsrfInput() ?>
                        <input type="hidden" name="form_action" value="change_password">

                        <div class="mb-3">
                            <label for="current_password" class="form-label font-weight-bold">Current Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" name="current_password" id="current_password" class="form-control border-end-0" placeholder="Enter current password" required>
                                <span class="input-group-text bg-light cursor-pointer"><i class="bi bi-eye auth-visibility-toggle" data-target="current_password"></i></span>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="new_password" class="form-label font-weight-bold">New Password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="password" name="new_password" id="new_password" class="form-control border-end-0" placeholder="Min. 8 chars, uppercase, number & symbol" required>
                                    <span class="input-group-text bg-light cursor-pointer"><i class="bi bi-eye auth-visibility-toggle" data-target="new_password"></i></span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="confirm_password" class="form-label font-weight-bold">Confirm Password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="password" name="confirm_password" id="confirm_password" class="form-control border-end-0" placeholder="Confirm new password" required>
                                    <span class="input-group-text bg-light cursor-pointer"><i class="bi bi-eye auth-visibility-toggle" data-target="confirm_password"></i></span>
                                </div>
                            </div>
                        </div>

                        <button type="submit" name="change_password" class="btn btn-primary rounded-pill px-4 py-2.5 font-weight-bold d-inline-flex align-items-center gap-2 shadow-sm text-white">
                            <i class="bi bi-key-fill"></i> Update Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const fileInput = document.getElementById('profile_image');
        const fileNameDisplay = document.getElementById('file-name-display');
        const avatarImg = document.querySelector('.profile-avatar');

        if (fileInput && fileNameDisplay) {
            fileInput.addEventListener('change', function () {
                if (this.files && this.files.length > 0) {
                    const file = this.files[0];
                    fileNameDisplay.textContent = file.name;
                    fileNameDisplay.classList.remove('text-muted');
                    fileNameDisplay.classList.add('text-primary', 'fw-bold');

                    // Live avatar preview
                    if (avatarImg && file.type.startsWith('image/')) {
                        const reader = new FileReader();
                        reader.onload = function (e) {
                            avatarImg.src = e.target.result;
                        };
                        reader.readAsDataURL(file);
                    }
                } else {
                    fileNameDisplay.textContent = 'No photo selected';
                    fileNameDisplay.classList.remove('text-primary', 'fw-bold');
                    fileNameDisplay.classList.add('text-muted');
                }
            });
        }
    });
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
