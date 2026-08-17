<?php
// about.php - About Lapify & Diploma Project Overview
$page_title = "About Us | Lapify";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<!-- Hero Banner (High Contrast Light Theme) -->
<div class="py-5 mb-5" style="background: linear-gradient(135deg, #ffffff 0%, #f1f5f9 50%, #e2e8f0 100%); border-bottom: 1px solid #cbd5e1; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);">
    <div class="container text-center py-4">
        <h1 class="display-5 fw-extrabold mb-3" style="color: #0f172a !important; letter-spacing: -0.5px;">About Lapify Marketplace</h1>
        <p class="lead mx-auto fs-5" style="max-width: 680px; line-height: 1.9; color: #334155 !important;">
            A modern, direct laptop marketplace designed for seamless peer-to-peer buying and selling of new and used laptop computers.
        </p>
    </div>
</div>

<div class="container pb-5">
    <div class="row g-4 align-items-stretch mb-5">
        <!-- Left Side: Peer-to-Peer Marketplace Card (Light Theme Black Text) -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-lg rounded-4 p-4 p-md-5 h-100" style="background: #ffffff !important; border: 1px solid #e2e8f0 !important; color: #0f172a !important;">
                <h2 class="fw-bold mb-3" style="color: #0f172a !important;">Direct Peer-to-Peer Marketplace Model</h2>
                <p class="fs-6 mb-4" style="line-height: 1.85; color: #334155 !important;">
                    Lapify eliminates middleman fees, commissions, and payment gateway hassles. Buyers and sellers connect directly to agree on price, inspection, and local pickup.
                </p>
                <div class="row g-3 mt-auto">
                    <div class="col-sm-6">
                        <div class="p-4 rounded-4 h-100" style="background: #f8fafc; border: 1.5px solid #e2e8f0;">
                            <i class="bi bi-shield-check text-success fs-2 mb-2 d-block"></i>
                            <h5 class="fw-bold mb-2" style="color: #0f172a !important;">No Hidden Fees</h5>
                            <p class="small mb-0" style="color: #475569 !important; line-height: 1.6;">List and contact sellers completely free of charge.</p>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="p-4 rounded-4 h-100" style="background: #f8fafc; border: 1.5px solid #e2e8f0;">
                            <i class="bi bi-lightning-charge text-primary fs-2 mb-2 d-block"></i>
                            <h5 class="fw-bold mb-2" style="color: #0f172a !important;">Instant Inquiry</h5>
                            <p class="small mb-0" style="color: #475569 !important; line-height: 1.6;">Direct purchase flow with clear seller details and secure COD checkout.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Side: Technical Stack Card (Light Theme Black Text) -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-lg rounded-4 p-4 p-md-5 h-100" style="background: #ffffff !important; border: 1px solid #e2e8f0 !important; color: #0f172a !important;">
                <h3 class="fw-bold mb-4" style="color: #0f172a !important;"><i class="bi bi-code-slash text-primary me-2"></i>Technical Stack Overview</h3>
                <div class="d-flex flex-column gap-3">
                    <div class="d-flex justify-content-between align-items-center pb-3 border-bottom border-slate-200">
                        <span class="fw-semibold fs-6" style="color: #0f172a !important;">Frontend Layer</span>
                        <span class="badge bg-primary text-white rounded-pill px-3 py-2 fs-7">HTML5, CSS3, Bootstrap 5.3, JS</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center pb-3 border-bottom border-slate-200">
                        <span class="fw-semibold fs-6" style="color: #0f172a !important;">Backend Engine</span>
                        <span class="badge bg-success text-white rounded-pill px-3 py-2 fs-7">PHP 8 Procedural</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center pb-3 border-bottom border-slate-200">
                        <span class="fw-semibold fs-6" style="color: #0f172a !important;">Database Engine</span>
                        <span class="badge bg-warning text-dark rounded-pill px-3 py-2 fs-7 fw-bold">MySQL via MySQLi</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center pb-3 border-bottom border-slate-200">
                        <span class="fw-semibold fs-6" style="color: #0f172a !important;">Security Model</span>
                        <span class="badge bg-info text-dark rounded-pill px-3 py-2 fs-7 fw-bold">Prepared Statements & Bcrypt</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center pt-2">
                        <span class="fw-semibold fs-6" style="color: #0f172a !important;">Database Tables</span>
                        <span class="badge bg-secondary text-white rounded-pill px-3 py-2 fs-7">Exactly 5 Normalized Tables</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Meet the Team Section (Light Theme Black Text) -->
    <div class="row mt-5">
        <div class="col-12">
            <h3 class="fw-bold mb-4" style="color: #0f172a !important;">Meet the Team</h3>
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                <?php
                $team = [
                    ['name' => 'Yash Kotak', 'email' => '24020201090@darshan.ac.in'],
                    ['name' => 'Samiksha Gajera', 'email' => '24020201052@darshan.ac.in'],
                    ['name' => 'Krisha Patel', 'email' => '24020201138@darshan.ac.in'],
                    ['name' => 'Vedang Joshi', 'email' => '24020201072@darshan.ac.in'],
                    ['name' => 'Prisha Vasavada', 'email' => '24020201170@darshan.ac.in'],
                ];
                function initials($name) {
                    $parts = explode(' ', trim($name));
                    return strtoupper(substr($parts[0], 0, 1) . substr(end($parts), 0, 1));
                }
                ?>
                <?php foreach ($team as $member): ?>
                    <div class="col">
                        <div class="card p-4 shadow-sm rounded-4 h-100" style="background: #ffffff !important; border: 1px solid #e2e8f0 !important;">
                            <div class="d-flex align-items-center gap-3">
                                <div class="avatar-placeholder rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold" style="width:58px;height:58px;font-size:18px;flex-shrink:0;box-shadow: 0 4px 14px rgba(37, 99, 235, 0.4);">
                                    <?= initials($member['name']) ?>
                                </div>
                                <div>
                                    <div class="fw-semibold fs-6" style="color: #0f172a !important;"><?= escape($member['name']) ?></div>
                                    <div class="small"><a href="mailto:<?= escape($member['email']) ?>" class="text-primary text-decoration-none fw-semibold"><?= escape($member['email']) ?></a></div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
