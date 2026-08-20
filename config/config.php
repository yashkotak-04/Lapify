<?php
// config/config.php - Global Application Configuration
ob_start(); // Project-wide output-buffering safety net to avoid "headers already sent" corrupting downloads/redirects
date_default_timezone_set('Asia/Kolkata');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load .env file if present in project root
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $envLines = @file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($envLines !== false) {
        foreach ($envLines as $envLine) {
            $envLine = trim($envLine);
            if ($envLine === '' || str_starts_with($envLine, '#')) {
                continue;
            }
            if (strpos($envLine, '=') !== false) {
                list($envKey, $envVal) = explode('=', $envLine, 2);
                $envKey = trim($envKey);
                $envVal = trim($envVal, " \t\n\r\0\x0B\"'");
                if ($envKey !== '' && getenv($envKey) === false) {
                    putenv("{$envKey}={$envVal}");
                    $_ENV[$envKey] = $envVal;
                    $_SERVER[$envKey] = $envVal;
                }
            }
        }
    }
}

define('APP_NAME', 'Lapify');
define('APP_TAGLINE', 'Buy New, Buy Used, & Sell Laptops Safely');

// Base URL configuration (auto-detect or fallback)
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = trim($_SERVER['HTTP_HOST'] ?? 'localhost', ". ");
if ($host === '') {
    $host = 'localhost';
}

$script_name = $_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'] ?? $_SERVER['REQUEST_URI'] ?? '';
$script_path = parse_url($script_name, PHP_URL_PATH) ?: '';
$script_dir = str_replace('\\', '/', dirname($script_path));
$base_path = preg_replace('#/(admin|includes|config|uploads|assets)(?:/.*)?$#i', '', $script_dir);
$base_path = rtrim($base_path, '/');
if ($base_path === '.' || $base_path === '/') {
    $base_path = '';
}

define('BASE_URL', $protocol . '://' . $host . ($base_path !== '' ? $base_path : ''));

// File Upload Paths & Constraints
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('LAPTOP_UPLOAD_DIR', UPLOAD_DIR . 'laptops/');
define('PROFILE_UPLOAD_DIR', UPLOAD_DIR . 'profiles/');
define('BRAND_UPLOAD_DIR', UPLOAD_DIR . 'brands/');

define('MAX_FILE_SIZE', 2 * 1024 * 1024); // 2 MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'webp']);

$localHosts = ['localhost', '127.0.0.1', '::1'];
$hostForCheck = $_SERVER['HTTP_HOST'] ?? 'localhost';
$isLocalEnvironment = in_array(strtolower($hostForCheck), array_map('strtolower', $localHosts), true) || stripos($hostForCheck, 'localhost') !== false;
if ($isLocalEnvironment) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
} else {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    ini_set('log_errors', '1');
}

function renderErrorPage($code = 404, $title = 'Page not found', $message = 'The page you requested could not be found.') {
    if (PHP_SAPI === 'cli') {
        echo "Error {$code}: {$title} - {$message}" . PHP_EOL;
        exit(1);
    }

    if (!headers_sent()) {
        http_response_code((int) $code);
    }

    $isAdmin = !empty($_SESSION['role']) && $_SESSION['role'] === 'admin';
    $homeUrl = $isAdmin ? BASE_URL . '/admin/dashboard.php' : BASE_URL . '/index.php';
    $pageTitle = $title;
    $pageMessage = $message;
    require __DIR__ . '/../errors/404.php';
    exit;
}

function lapifyErrorHandler($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) {
        return false;
    }

    $message = "PHP Error [$errno]: {$errstr} in {$errfile} on line {$errline}";
    error_log($message);

    if (PHP_SAPI === 'cli') {
        return false;
    }

    // Only render error page for severe user/recoverable errors, not minor notices/deprecations
    if (in_array($errno, [E_USER_ERROR, E_RECOVERABLE_ERROR], true)) {
        renderErrorPage(500, 'Something went wrong', 'The site hit a temporary problem. Please return to the homepage.');
    }
    return true;
}

function lapifyExceptionHandler(Throwable $e) {
    error_log('Unhandled exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
    if (PHP_SAPI === 'cli') {
        echo 'Exception: ' . $e->getMessage() . PHP_EOL;
        return;
    }
    renderErrorPage(500, 'Something went wrong', 'The site hit a temporary problem. Please return to the homepage.');
}

function lapifyShutdownHandler() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true)) {
        error_log('Fatal error: ' . $error['message'] . ' in ' . $error['file'] . ' on line ' . $error['line']);
        if (PHP_SAPI === 'cli') {
            echo 'Fatal error: ' . $error['message'] . PHP_EOL;
            return;
        }
        renderErrorPage(500, 'Something went wrong', 'The site hit a temporary problem. Please return to the homepage.');
    }
}

set_error_handler('lapifyErrorHandler');
set_exception_handler('lapifyExceptionHandler');
register_shutdown_function('lapifyShutdownHandler');
