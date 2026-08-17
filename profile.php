<?php
// profile.php - User Profile & Password Security
$page_title = "My Profile & Settings | Lapify";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin();

$conn = getDbConnection();
$session_user = getCurrentUser();

// Fetch fresh user record
$stmt = mysqli_prepare($conn, "SELECT u.*, CASE WHEN a.email IS NOT NULL THEN 'admin' ELSE 'user' END AS role FROM users u LEFT JOIN admins a ON u.email = a.email WHERE u.id = ?");
mysqli_stmt_bind_param($stmt, "i", $session_user['id']);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$user) {
    session_destroy();
    header("Location: " . BASE_URL . "/login.php");
    exit();
}

if (!empty($user['phone']) && !preg_match('/^[0-9]{10}$/', $user['phone'])) {
    $user['phone'] = '';
}

$profile_errors = [];
$password_errors = [];
$createdAt = $user['created_at'] ?? 'N/A';

// Handle Profile Info Update
$isProfileSubmit = $_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['update_profile']) || (isset($_POST['form_action']) && $_POST['form_action'] === 'update_profile'));
if ($isProfileSubmit) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $profile_errors[] = "Session expired or invalid security token. Please try again.";
    }

    $full_name = sanitizeInput($_POST['full_name'] ?? '');
    $email = strtolower(trim(sanitizeInput($_POST['email'] ?? '')));
    $phone = sanitizeInput($_POST['phone'] ?? '');

    if (empty($full_name)) {
        $profile_errors[] = "Full Name is required.";
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $profile_errors[] = "A valid email address is required.";
    }
    if (!empty($phone) && !preg_match('/^[0-9]{10}$/', $phone)) {
        $profile_errors[] = "Phone number must be exactly 10 digits.";
    }

    // Preserve submitted profile values when validation fails
    $user['full_name'] = $full_name;
    $user['email'] = $email;
    $user['phone'] = $phone;

    if (empty($profile_errors)) {
        $check_stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE LOWER(email) = LOWER(?) AND id != ? LIMIT 1");
        mysqli_stmt_bind_param($check_stmt, "si", $email, $user['id']);
        mysqli_stmt_execute($check_stmt);
        mysqli_stmt_store_result($check_stmt);
        if (mysqli_stmt_num_rows($check_stmt) > 0) {
            $profile_errors[] = "That email address is already in use by another user.";
        }
        mysqli_stmt_close($check_stmt);
    }

    if (empty($profile_errors)) {
        $check_admin = mysqli_prepare($conn, "SELECT id FROM admins WHERE LOWER(email) = LOWER(?) LIMIT 1");
        mysqli_stmt_bind_param($check_admin, "s", $email);
        mysqli_stmt_execute($check_admin);
        mysqli_stmt_store_result($check_admin);
        if (mysqli_stmt_num_rows($check_admin) > 0) {
            $profile_errors[] = "That email address is already reserved by an administrator account.";
        }
        mysqli_stmt_close($check_admin);
    }

    $uploaded_image = $user['profile_image'];
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $up_err = '';
        $new_img = uploadImage($_FILES['profile_image'], PROFILE_UPLOAD_DIR, $up_err);
        if ($new_img === false) {
            $profile_errors[] = $up_err;
        } else {
            if (!empty($user['profile_image'])) {
                deleteImageFile($user['profile_image'], PROFILE_UPLOAD_DIR);
            }
            $uploaded_image = $new_img;
        }
    }

    if (empty($profile_errors)) {
        $up_stmt = mysqli_prepare($conn, "UPDATE users SET full_name = ?, email = ?, phone = ?, profile_image = ? WHERE id = ?");
        mysqli_stmt_bind_param($up_stmt, "ssssi", $full_name, $email, $phone, $uploaded_image, $user['id']);
        if (mysqli_stmt_execute($up_stmt)) {
            $_SESSION['full_name']     = $full_name;
            $_SESSION['email']         = $email;
            $_SESSION['phone']         = $phone;
            $_SESSION['profile_image'] = $uploaded_image;
            getCurrentUser(true); // Force cache refresh
            setFlash('success', "Profile details updated successfully!");
            header("Location: " . BASE_URL . "/profile.php");
            exit();
        } else {
            $profile_errors[] = "Failed to update profile details.";
        }
        mysqli_stmt_close($up_stmt);
    }
}

// Handle Password Change
$isPasswordSubmit = $_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['change_password']) || (isset($_POST['form_action']) && $_POST['form_action'] === 'change_password'));
if ($isPasswordSubmit) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $password_errors[] = "Session expired or invalid security token. Please try again.";
    }

    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($current_password)) {
        $password_errors[] = "Current password is required.";
    }

    $passwordValidation = validatePasswordStrength($new_password);
    if (!$passwordValidation['is_valid']) {
        $password_errors[] = "New password must be at least 8 characters long and include uppercase, lowercase, number, and special characters.";
    }
    if ($new_password !== $confirm_password) {
        $password_errors[] = "New password confirmation does not match.";
    }

    if (empty($password_errors)) {
        if (password_verify($current_password, $user['password'])) {
            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $pass_stmt = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE id = ?");
            mysqli_stmt_bind_param($pass_stmt, "si", $new_hash, $user['id']);
            if (mysqli_stmt_execute($pass_stmt)) {
                setFlash('success', "Password changed successfully!");
                header("Location: " . BASE_URL . "/profile.php");
                exit();
            } else {
                $password_errors[] = "Failed to change password. Please try again.";
            }
            mysqli_stmt_close($pass_stmt);
        } else {
            $password_errors[] = "Current password is incorrect.";
        }
    }
}

$avatar_url = !empty($user['profile_image']) && file_exists(PROFILE_UPLOAD_DIR . $user['profile_image'])
    ? BASE_URL . '/uploads/profiles/' . $user['profile_image']
    : 'https://ui-avatars.com/api/?name=' . urlencode($user['full_name'] ?? 'User') . '&background=2563eb&color=fff&size=200';

$profile_percent = calculateProfileCompletion($user);
$createdAt = $user['created_at'] ?? 'N/A';

require_once __DIR__ . '/includes/navbar.php';
?>

<div class="container py-5">
    <div class="row g-4">
        <!-- Sidebar Profile Card -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 text-center p-4">
                <div class="position-relative d-inline-block mx-auto mb-3">
                    <img src="<?= escape($avatar_url) ?>" alt="" class="user-avatar-lg shadow" style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover;">
                </div>
                <h4 class="fw-bold mb-1"><?= escape($user['full_name']) ?></h4>
                <p class="text-muted small mb-3"><?= escape($user['email']) ?></p>
                
                <span class="badge bg-primary-subtle text-primary fw-semibold px-3 py-1.5 rounded-pill mb-4 text-capitalize">
                    <?= escape($user['role'] ?? 'user') ?> Account
                </span>

                <!-- Profile Completion Indicator -->
                <div class="p-3 bg-light rounded-3 text-start mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="small font-weight-bold">Profile Completion</span>
                        <span class="small font-weight-bold text-primary"><?= $profile_percent ?>%</span>
                    </div>
                    <div class="progress progress-thin mb-0">
                        <div class="progress-bar bg-primary" role="progressbar" style="width: <?= $profile_percent ?>%;"></div>
                    </div>
                </div>

                <div class="text-start small text-muted">
                    <div class="mb-2"><i class="bi bi-calendar3 me-2"></i>Member Since <?= formatDate($createdAt) ?></div>
                    <div><i class="bi bi-shield-check me-2 text-success"></i>Email Verified</div>
                </div>
            </div>
        </div>

        <!-- Forms Column -->
        <div class="col-lg-8">
            <?php displayFlash(); ?>

            <!-- Edit Personal Info Card -->
            <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 mb-4">
                <h4 class="fw-bold mb-4"><i class="bi bi-person-gear text-primary me-2"></i>Personal Information</h4>

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
                            <input type="text" name="full_name" id="full_name" class="form-control" value="<?= escape($user['full_name']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label font-weight-bold">Email Address <span class="text-danger">*</span></label>
                            <input type="email" name="email" id="email" class="form-control" value="<?= escape($user['email']) ?>" required>
                            <div class="form-text">You can update your email address here.</div>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="phone" class="form-label font-weight-bold">Phone Number (10 digits)</label>
                            <input type="tel" name="phone" id="phone" class="form-control" value="<?= escape($user['phone'] ?? '') ?>" placeholder="9876543210" maxlength="10" pattern="[0-9]{10}" inputmode="numeric">
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

                    <button type="submit" name="update_profile" class="btn btn-primary rounded-3 font-weight-bold px-4">
                        <i class="bi bi-save me-2"></i>Save Info Changes
                    </button>
                </form>
            </div>

            <!-- Change Password Card -->
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
                            <input type="password" name="current_password" id="current_password" class="form-control border-end-0" placeholder="Current Password" required>
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
                            <label for="confirm_password" class="form-label font-weight-bold">Confirm New Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" name="confirm_password" id="confirm_password" class="form-control border-end-0" placeholder="Confirm New Password" required>
                                <span class="input-group-text bg-light cursor-pointer"><i class="bi bi-eye auth-visibility-toggle" data-target="confirm_password"></i></span>
                            </div>
                        </div>
                    </div>

                    <button type="submit" name="change_password" class="btn btn-primary rounded-3 font-weight-bold px-4">
                        <i class="bi bi-key-fill me-2"></i>Update Password
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const fileInput = document.getElementById('profile_image');
        const fileNameDisplay = document.getElementById('file-name-display');
        const avatarImg = document.querySelector('.user-avatar-lg, .profile-avatar');

        if (fileInput && fileNameDisplay) {
            fileInput.addEventListener('change', function () {
                if (this.files && this.files.length > 0) {
                    const file = this.files[0];
                    fileNameDisplay.textContent = file.name;
                    fileNameDisplay.classList.remove('text-muted');
                    fileNameDisplay.classList.add('text-primary', 'fw-bold');

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

<?php require_once __DIR__ . '/includes/footer.php'; ?>
