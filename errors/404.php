<?php
require_once __DIR__ . '/../config/config.php';
if (!function_exists('escape')) {
    require_once __DIR__ . '/../includes/functions.php';
}

$code = isset($code) ? (int) $code : 404;
$isAdmin = !empty($_SESSION['role']) && $_SESSION['role'] === 'admin';
$homeUrl = $homeUrl ?? ($isAdmin ? BASE_URL . '/admin/dashboard.php' : BASE_URL . '/index.php');
$homeLabel = $isAdmin ? 'Admin Dashboard' : 'Back to Home';
$pageTitle = $pageTitle ?? ($code >= 500 ? 'Server Error' : 'Page Not Found');
$isServerError = $code >= 500;

$badgeText = $isServerError ? '500 · Server Error' : '404 · Page Not Found';
$badgeColor = $isServerError ? 'rgba(239, 68, 68, 0.12)' : 'rgba(56, 189, 248, 0.14)';
$badgeBorder = $isServerError ? 'rgba(239, 68, 68, 0.35)' : 'rgba(56, 189, 248, 0.35)';
$badgeTextColor = $isServerError ? '#dc2626' : '#0284c7';

$headline = $isServerError ? 'Something went wrong' : 'Page not found';
$subtext = $isServerError
    ? 'We encountered an unexpected error while processing your request. Please try again or return to the marketplace.'
    : 'The page you are looking for does not exist, may have moved, or is temporarily unavailable.';
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= escape($pageTitle) ?> | Lapify</title>
    
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <style>
        :root {
            --error-bg: #f8fafc;
            --card-bg: rgba(255, 255, 255, 0.96);
            --text-main: #0f172a;
            --text-sub: #64748b;
            --border-color: rgba(56, 189, 248, 0.3);
            --card-shadow: 0 25px 60px rgba(15, 23, 42, 0.08), 0 0 30px rgba(56, 189, 248, 0.15);
            --accent-glow: rgba(56, 189, 248, 0.25);
        }

        [data-theme="dark"] {
            --error-bg: #0b1120;
            --card-bg: rgba(15, 23, 42, 0.95);
            --text-main: #f8fafc;
            --text-sub: #94a3b8;
            --border-color: rgba(56, 189, 248, 0.25);
            --card-shadow: 0 30px 70px rgba(0, 0, 0, 0.4), 0 0 35px rgba(56, 189, 248, 0.15);
            --accent-glow: rgba(56, 189, 248, 0.15);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: var(--error-bg);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            position: relative;
            overflow-x: hidden;
        }

        /* Subtle ambient glow circles */
        .ambient-glow-1 {
            position: absolute;
            top: -15%;
            left: 20%;
            width: 450px;
            height: 450px;
            background: radial-gradient(circle, rgba(56, 189, 248, 0.22) 0%, transparent 70%);
            pointer-events: none;
            filter: blur(40px);
        }

        .ambient-glow-2 {
            position: absolute;
            bottom: -15%;
            right: 20%;
            width: 450px;
            height: 450px;
            background: radial-gradient(circle, rgba(37, 99, 235, 0.18) 0%, transparent 70%);
            pointer-events: none;
            filter: blur(40px);
        }

        .error-card-container {
            width: 100%;
            max-width: 620px;
            position: relative;
            z-index: 2;
        }

        .error-card {
            background: var(--card-bg);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
            border: 1.5px solid var(--border-color);
            border-radius: 28px;
            box-shadow: var(--card-shadow);
            padding: 3rem 2.5rem;
            text-align: center;
            position: relative;
            overflow: hidden;
            animation: cardFadeUp 0.4s ease-out;
        }

        .error-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #38bdf8, #2563eb, #38bdf8);
        }

        @keyframes cardFadeUp {
            from {
                opacity: 0;
                transform: translateY(12px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .error-brand-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.35rem 0.85rem;
            border-radius: 999px;
            background: rgba(56, 189, 248, 0.1);
            color: #0284c7;
            font-size: 0.82rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 1.5rem;
        }

        .error-code-hero {
            font-size: clamp(4.5rem, 10vw, 6.5rem);
            font-weight: 900;
            line-height: 1;
            letter-spacing: -0.04em;
            background: linear-gradient(135deg, #0284c7 0%, #2563eb 50%, #1d4ed8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
        }

        .error-status-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.4rem 1rem;
            border-radius: 999px;
            background: <?= $badgeColor ?>;
            border: 1px solid <?= $badgeBorder ?>;
            color: <?= $badgeTextColor ?>;
            font-size: 0.88rem;
            font-weight: 700;
            margin-bottom: 1.25rem;
        }

        .error-title {
            font-size: 1.65rem;
            font-weight: 800;
            color: var(--text-main);
            margin-bottom: 0.75rem;
            letter-spacing: -0.02em;
        }

        .error-desc {
            font-size: 0.98rem;
            color: var(--text-sub);
            line-height: 1.6;
            margin-bottom: 2rem;
            max-width: 480px;
            margin-left: auto;
            margin-right: auto;
        }

        .error-actions {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            flex-wrap: wrap;
            margin-bottom: 2rem;
        }

        .btn-lapify-primary {
            background: linear-gradient(135deg, #0284c7 0%, #2563eb 100%);
            color: #ffffff !important;
            border: none;
            font-weight: 600;
            padding: 0.75rem 1.6rem;
            border-radius: 999px;
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.35);
            text-decoration: none;
            transition: all 0.25s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-lapify-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.45);
            color: #ffffff !important;
        }

        .btn-lapify-secondary {
            background: transparent;
            color: var(--text-main) !important;
            border: 1.5px solid var(--border-color);
            font-weight: 600;
            padding: 0.72rem 1.5rem;
            border-radius: 999px;
            text-decoration: none;
            transition: all 0.25s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-lapify-secondary:hover {
            background: rgba(56, 189, 248, 0.08);
            transform: translateY(-2px);
            color: #0284c7 !important;
        }

        .error-quick-links {
            padding-top: 1.5rem;
            border-top: 1px solid rgba(56, 189, 248, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1.25rem;
            flex-wrap: wrap;
        }

        .error-quick-links a {
            color: var(--text-sub);
            text-decoration: none;
            font-size: 0.86rem;
            font-weight: 500;
            transition: color 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
        }

        .error-quick-links a:hover {
            color: #0284c7;
        }

        @media (max-width: 576px) {
            .error-card {
                padding: 2.25rem 1.5rem;
            }
            .error-code-hero {
                font-size: 4rem;
            }
            .error-actions {
                flex-direction: column;
                width: 100%;
            }
            .error-actions a {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="ambient-glow-1"></div>
    <div class="ambient-glow-2"></div>

    <div class="error-card-container">
        <div class="error-card">
            <!-- Brand Chip -->
            <div class="error-brand-badge">
                <i class="bi bi-laptop-fill"></i> Lapify Marketplace
            </div>

            <!-- Big Status Code -->
            <div class="error-code-hero"><?= $code ?></div>

            <!-- Status Pill -->
            <div class="error-status-pill">
                <i class="bi <?= $isServerError ? 'bi-exclamation-triangle-fill' : 'bi-compass-fill' ?>"></i>
                <?= escape($badgeText) ?>
            </div>

            <!-- Headline & Description -->
            <h1 class="error-title"><?= escape($headline) ?></h1>
            <p class="error-desc"><?= escape($subtext) ?></p>

            <!-- Action Buttons -->
            <div class="error-actions">
                <a href="<?= escape($homeUrl) ?>" class="btn-lapify-primary">
                    <i class="bi bi-house-door-fill"></i> <?= escape($homeLabel) ?>
                </a>

                <?php if ($isServerError): ?>
                    <a href="javascript:location.reload()" class="btn-lapify-secondary">
                        <i class="bi bi-arrow-clockwise"></i> Try Again
                    </a>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>/buy.php" class="btn-lapify-secondary">
                        <i class="bi bi-search"></i> Browse Laptops
                    </a>
                <?php endif; ?>

                <a href="javascript:history.back()" class="btn-lapify-secondary">
                    <i class="bi bi-arrow-left"></i> Go Back
                </a>
            </div>

            <!-- Quick Navigation Links -->
            <div class="error-quick-links">
                <a href="<?= BASE_URL ?>/buy.php"><i class="bi bi-grid"></i> Laptops</a>
                <a href="<?= BASE_URL ?>/sell.php"><i class="bi bi-tag"></i> Sell Laptop</a>
                <a href="<?= BASE_URL ?>/contact.php"><i class="bi bi-headset"></i> Support</a>
            </div>
        </div>
    </div>
</body>
</html>
