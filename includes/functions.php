<?php
// includes/functions.php - Global Helper Functions

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';

/**
 * Sanitize text input string
 */
function sanitizeInput($data) {
    if (is_null($data)) return '';
    $data = trim($data);
    $data = stripslashes($data);
    return $data;
}

/**
 * Escape HTML output for XSS protection
 */
function escape($data) {
    if (is_null($data)) return '';
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

/**
 * Validate password strength rules.
 */
function validatePasswordStrength($password) {
    $password = (string)$password;
    $rules = [
        'min_length' => strlen($password) >= 8,
        'has_uppercase' => (bool)preg_match('/[A-Z]/', $password),
        'has_lowercase' => (bool)preg_match('/[a-z]/', $password),
        'has_number' => (bool)preg_match('/[0-9]/', $password),
        'has_special' => (bool)preg_match('/[^A-Za-z0-9]/', $password),
    ];

    $isValid = $rules['min_length'] && $rules['has_uppercase'] && $rules['has_lowercase'] && $rules['has_number'] && $rules['has_special'];

    return [
        'is_valid' => $isValid,
        'rules' => $rules,
        'failed_rules' => array_keys(array_filter($rules, fn($ok) => !$ok)),
    ];
}

/**
 * Normalize brand names into simple lowercase slugs for logo filenames.
 */
function slugifyBrandName($brandName) {
    return strtolower(preg_replace('/[^a-z0-9]+/i', '', trim($brandName)));
}

/**
 * Resolve a brand logo URL if the matching file exists.
 */
function getBrandLogoUrl($brandName) {
    $slug = slugifyBrandName($brandName);
    $candidates = [];

    // Prefer uploaded brand images (often SVG) for sharper results, then packaged assets
    $exts = ['svg', 'png', 'webp'];
    foreach ($exts as $e) {
        $candidates[] = [
            'path' => __DIR__ . '/../uploads/brands/' . $slug . '.' . $e,
            'url' => BASE_URL . '/uploads/brands/' . $slug . '.' . $e
        ];
    }

    // Fallback to packaged assets
    foreach ($exts as $e) {
        $candidates[] = [
            'path' => __DIR__ . '/../assets/images/brands/' . $slug . '.' . $e,
            'url' => BASE_URL . '/assets/images/brands/' . $slug . '.' . $e
        ];
    }

    foreach ($candidates as $c) {
        if (file_exists($c['path'])) {
            return $c['url'];
        }
    }

    return null;
}

/**
 * Format currency price
 */
function formatPrice($price) {
    return '₹' . number_format((float)$price, 0);
}

/**
 * Format readable date
 */
function formatDate($dateString) {
    if (!$dateString) return 'N/A';
    return date('M j, Y', strtotime($dateString));
}

/**
 * Return a friendly, consistent order status label.
 */
function getOrderStatusLabel($status) {
    $status = strtolower((string)($status ?? 'placed'));
    $labels = [
        'placed' => 'Placed',
        'confirmed' => 'Confirmed',
        'shipped' => 'Shipped',
        'delivered' => 'Delivered',
        'cancelled' => 'Cancelled',
    ];
    return $labels[$status] ?? ucfirst($status);
}

/**
 * Return the Bootstrap badge class for an order status.
 */
function getOrderStatusBadgeClass($status) {
    $status = strtolower((string)($status ?? 'placed'));
    $classes = [
        'placed' => 'bg-secondary-subtle text-secondary',
        'confirmed' => 'bg-info-subtle text-info',
        'shipped' => 'bg-primary-subtle text-primary',
        'delivered' => 'bg-success-subtle text-success',
        'cancelled' => 'bg-danger-subtle text-danger',
    ];
    return $classes[$status] ?? 'bg-secondary-subtle text-secondary';
}

function isOrderCancellable($status) {
    $status = strtolower((string)($status ?? 'placed'));
    return in_array($status, ['placed'], true);
}

/**
 * Set flash alert message
 */
function setFlash($type, $message) {
    $_SESSION['flash_' . $type] = $message;
    $_SESSION['toast_message'] = $message;
    $_SESSION['toast_type'] = $type;
}

/**
 * Display flash alerts in Bootstrap markup
 */
function displayFlash() {
    $types = ['success' => 'success', 'error' => 'danger', 'info' => 'info', 'warning' => 'warning'];
    foreach ($types as $key => $bsClass) {
        if (isset($_SESSION['flash_' . $key])) {
            unset($_SESSION['flash_' . $key]);
        }
    }

    if (!empty($_SESSION['toast_message'])) {
        $message = addslashes($_SESSION['toast_message']);
        $type = isset($_SESSION['toast_type']) ? $_SESSION['toast_type'] : 'info';
        $safeType = in_array($type, ['success','error','warning','info'], true) ? $type : 'info';
        echo "<script>window.addEventListener('DOMContentLoaded', function () { if (typeof window.showToast === 'function') { window.showToast('{$message}', '{$safeType}', 3600); } });</script>";
        unset($_SESSION['toast_message']);
        unset($_SESSION['toast_type']);
    }

    renderAuthSuccessOverlay();
}

/**
 * Render animated success overlay for Login
 */
function renderAuthSuccessOverlay() {
    $title = '';
    $message = '';
    $redirectUrl = '';

    if (!empty($_GET['login_success']) || !empty($_SESSION['auth_login_success'])) {
        $name = htmlspecialchars($_SESSION['full_name'] ?? 'User', ENT_QUOTES, 'UTF-8');
        $title = '🎉 Successfully Logged In!';
        $message = 'Welcome back, ' . $name . '! Launching your Lapify dashboard...';
        unset($_SESSION['auth_login_success']);
    }

    if ($title !== '') {
        echo '<div id="auth-success-modal" class="auth-success-backdrop active" ' . ($redirectUrl ? 'data-redirect-url="' . htmlspecialchars($redirectUrl, ENT_QUOTES, 'UTF-8') . '"' : '') . '>
            <div class="auth-success-card">
                <div class="auth-success-icon-wrap">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <h3 class="auth-success-title">' . $title . '</h3>
                <p class="auth-success-text">' . $message . '</p>
                <div class="auth-success-progress-track">
                    <div class="auth-success-progress-bar"></div>
                </div>
            </div>
        </div>';
    }
}

/**
 * Upload image safely with MIME check, file extension whitelist, and size limit
 * @return string|bool Returns uploaded filename on success, false on failure
 */
function uploadImage($fileArray, $targetDirectory, &$errorMessage = '') {
    if (!isset($fileArray['error']) || $fileArray['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($fileArray['error'] !== UPLOAD_ERR_OK) {
        $errorMessage = "Error occurred during file upload.";
        return false;
    }

    if ($fileArray['size'] > MAX_FILE_SIZE) {
        $errorMessage = "File size exceeds 2MB limit.";
        return false;
    }

    $fileExt = strtolower(pathinfo($fileArray['name'], PATHINFO_EXTENSION));
    if (!in_array($fileExt, ALLOWED_EXTENSIONS)) {
        $errorMessage = "Invalid image format. Allowed formats: JPG, JPEG, PNG, WEBP.";
        return false;
    }

    // Verify MIME type using finfo
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $fileArray['tmp_name']);
    finfo_close($finfo);

    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($mimeType, $allowedMimes)) {
        $errorMessage = "File content is not a valid image.";
        return false;
    }

    if (!is_dir($targetDirectory)) {
        mkdir($targetDirectory, 0755, true);
    }

    $newFilename = time() . '_' . random_int(100000, 999999) . '_' . bin2hex(random_bytes(4)) . '.' . $fileExt;
    $destination = $targetDirectory . $newFilename;

    if (move_uploaded_file($fileArray['tmp_name'], $destination)) {
        return $newFilename;
    }

    $errorMessage = "Failed to move uploaded file.";
    return false;
}

/**
 * Check whether a file exists and can be treated as a supported image.
 */
function isReadableLaptopImageFile(string $path): bool {
    if ($path === '' || !is_file($path) || !is_readable($path)) {
        return false;
    }

    $info = @getimagesize($path);
    if ($info === false) {
        return false;
    }

    $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];
    return in_array($info['mime'] ?? '', $allowedMimeTypes, true);
}

/**
 * Resolve a file path to a clean browser URL under /uploads/laptops/.
 * Returns null if the file is missing so callers can fall back.
 */
function lapifyResolveLaptopUrl(string $path): ?string {
    if (!is_file($path) || !is_readable($path)) {
        return null;
    }
    // Only expose the filename portion; the file must live in LAPTOP_UPLOAD_DIR.
    $realBase = realpath(LAPTOP_UPLOAD_DIR);
    $realFile = realpath($path);
    if ($realBase === false || $realFile === false || strpos($realFile . DIRECTORY_SEPARATOR, $realBase . DIRECTORY_SEPARATOR) !== 0) {
        return null;
    }
    $name = basename($realFile);
    return BASE_URL . '/uploads/laptops/' . rawurlencode($name);
}

/**
 * Resolve a laptop image source from uploads by checking the recorded filename,
 * common filename patterns (slug + extension), and by attempting a fuzzy
 * match against the model name in the uploads directory.
 *
 * NOTE: returns a clean /uploads/laptops/... URL (not a base64 data URI).
 * Reading + base64-encoding every image on every page load is the single
 * biggest reason the site felt extremely slow. Callers get a URL instead.
 */
function getLaptopImageUrl(array $laptop) {
    static $imageIndexCache = null;
    static $fuzzyFoundMap = [];

    $imageField = trim((string)($laptop['image'] ?? ''));

    // 1) If DB stores exact filename, use it
    if ($imageField !== '') {
        // Clean stored filename (strip any path prefixes the DB may have)
        $cleanName = basename(str_replace('\\', '/', $imageField));
        if ($cleanName !== '') {
            $candidate = LAPTOP_UPLOAD_DIR . $cleanName;
            if ($url = lapifyResolveLaptopUrl($candidate)) {
                return $url;
            }
        }
    }

    // 2) Try slugified model name with common extensions
    $model = trim((string)($laptop['model'] ?? ''));
    if ($model !== '') {
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', trim($model)));
        $exts = ['webp','jpg','jpeg','png'];
        foreach ($exts as $e) {
            $fn = $slug . '.' . $e;
            $candidate = LAPTOP_UPLOAD_DIR . $fn;
            if ($url = lapifyResolveLaptopUrl($candidate)) {
                return $url;
            }
        }

        // 2b) Per-model memoisation: we do the directory scan at most once per
        //     request AND skip re-scanning for models we already resolved.
        if (isset($fuzzyFoundMap[$model])) {
            return $fuzzyFoundMap[$model];
        }

        // 3) Fuzzy search in uploads folder — pick the best candidate by scoring.
        if (is_dir(LAPTOP_UPLOAD_DIR)) {
            if ($imageIndexCache === null) {
                $imageIndexCache = array_values(array_diff(scandir(LAPTOP_UPLOAD_DIR), ['.', '..']));
            }
            $files = $imageIndexCache;
            $modelNorm = strtolower(preg_replace('/[^a-z0-9]+/i', ' ', $model));
            $parts = array_values(array_filter(array_map('trim', explode(' ', $modelNorm))));
            $best = null;
            $bestScore = 0;
            $preferredExtOrder = ['webp' => 3, 'jpg' => 2, 'jpeg' => 2, 'png' => 1];

            foreach ($files as $f) {
                $lf = strtolower($f);
                $nameOnly = strtolower(pathinfo($f, PATHINFO_FILENAME));
                $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));

                // Skip non-image files
                if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) continue;

                $candidate = LAPTOP_UPLOAD_DIR . $f;
                if (!isReadableLaptopImageFile($candidate)) {
                    continue;
                }

                // Score: exact filename (without ext) match => high score
                if ($nameOnly === strtolower(preg_replace('/[^a-z0-9]+/i', '-', $model))) {
                    $score = 100 + ($preferredExtOrder[$ext] ?? 0);
                } elseif ($nameOnly === strtolower(preg_replace('/[^\s]+/','', $model))) {
                    // model stripped of spaces
                    $score = 90 + ($preferredExtOrder[$ext] ?? 0);
                } else {
                    // token match scoring
                    $score = 0;
                    foreach ($parts as $p) {
                        if ($p === '') continue;
                        if (strpos($lf, $p) !== false) $score += 5;
                    }
                    // bonus if filename starts with first token
                    if (!empty($parts) && strpos($nameOnly, $parts[0]) === 0) $score += 3;
                    // small bonus for preferred extensions
                    $score += ($preferredExtOrder[$ext] ?? 0);
                }

                if ($score > $bestScore) {
                    $bestScore = $score;
                    $best = $f;
                } elseif ($score === $bestScore && $best !== null) {
                    // tie-breaker: prefer shorter filename, then lexicographically
                    if (strlen($f) < strlen($best) || (strlen($f) === strlen($best) && strcmp($f, $best) < 0)) {
                        $best = $f;
                    }
                }
            }

            if ($best !== null && $bestScore > 0) {
                $bestCandidate = LAPTOP_UPLOAD_DIR . $best;
                if ($url = lapifyResolveLaptopUrl($bestCandidate)) {
                    $fuzzyFoundMap[$model] = $url;
                    return $url;
                }
            }
        }

        $fuzzyFoundMap[$model] = null;
    }

    return null;
}

/**
 * Delete image file from server if exists
 */
function deleteImageFile($filename, $directory) {
    if (!empty($filename)) {
        $filePath = $directory . $filename;
        if (file_exists($filePath) && is_file($filePath)) {
            @unlink($filePath);
        }
    }
}

/**
 * Check whether a logged-in user owns the given laptop listing.
 * Used to disable buy/wishlist/cart actions on a seller's own listing.
 */
function isOwnListing($userId, $ownerId) {
    if (!$userId || !$ownerId) return false;
    return (int)$userId === (int)$ownerId;
}

/**
 * Check if a laptop is in user's wishlist
 */
function isWishlisted($conn, $userId, $laptopId) {
    if (!$userId || !$laptopId) return false;
    $stmt = mysqli_prepare($conn, "SELECT id FROM wishlist WHERE user_id = ? AND laptop_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $userId, $laptopId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    $exists = mysqli_stmt_num_rows($stmt) > 0;
    mysqli_stmt_close($stmt);
    return $exists;
}

/**
 * Check if a laptop is in user's cart
 */
function isInCart($conn, $userId, $laptopId) {
    if (!$userId || !$laptopId) return false;
    $stmt = mysqli_prepare($conn, "SELECT id FROM cart WHERE user_id = ? AND laptop_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $userId, $laptopId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    $exists = mysqli_stmt_num_rows($stmt) > 0;
    mysqli_stmt_close($stmt);
    return $exists;
}

/**
 * Get item count in user's cart
 */
function getCartCount($conn, $userId) {
    if (!$userId) return 0;
    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) FROM cart WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $count);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);
    return $count ?? 0;
}

/**
 * Get count of items in user's wishlist
 */
function getWishlistCount($conn, $userId) {
    if (!$userId) return 0;
    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) FROM wishlist WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $count);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);
    return $count ?? 0;
}

/**
 * Calculate user profile completion percentage
 */
function calculateProfileCompletion($user) {
    $points = 0;
    if (!empty($user['full_name'])) $points += 25;
    if (!empty($user['email'])) $points += 25;
    if (!empty($user['phone'])) $points += 25;
    if (!empty($user['profile_image'])) $points += 25;
    return $points;
}

/**
 * CSRF Protection Helpers
 */
function getCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function renderCsrfInput() {
    $token = getCsrfToken();
    return '<input type="hidden" name="csrf_token" value="' . escape($token) . '">';
}

require_once __DIR__ . '/brand-logo.php';

function verifyCsrfToken($token = null) {
    if ($token === null) {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    }
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

