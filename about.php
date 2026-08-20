<?php
// about.php - About Lapify & Diploma Project Overview
$page_title = "About Us | Lapify";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<!-- Hero Banner (Lapify Theme) -->
<div class="about-hero-banner mb-5">
    <div class="container text-center py-3">
        <h1 class="display-5 fw-extrabold mb-3 text-dark" style="letter-spacing: -0.5px;">About Lapify Marketplace</h1>
        <p class="lead mx-auto fs-5 text-muted" style="max-width: 680px; line-height: 1.8;">
            A modern, direct laptop marketplace designed for seamless peer-to-peer buying and selling of new, certified, and pre-owned laptops.
        </p>
    </div>
</div>

<div class="container pb-5">
    <div class="row g-4 align-items-stretch mb-5">
        <!-- Left Side: Peer-to-Peer Marketplace Model Card -->
        <div class="col-lg-6">
            <div class="about-card">
                <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1.5 fw-bold" style="font-size: 0.78rem;">
                        <i class="bi bi-arrows-angle-contract me-1"></i> Direct P2P Model
                    </span>
                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1.5 fw-bold" style="font-size: 0.78rem;">
                        <i class="bi bi-percent me-1"></i> 0% Commission
                    </span>
                </div>

                <h3 class="fw-bold mb-3 text-dark">Direct Peer-to-Peer Marketplace</h3>
                <p class="fs-6 mb-4 text-muted" style="line-height: 1.8;">
                    Lapify eliminates middleman markups and confusing commissions. Buyers and sellers connect directly to agree on price, inspect devices, and choose local pickup or Cash on Delivery.
                </p>

                <div class="d-flex flex-column gap-2.5 mb-4">
                    <!-- Feature Row 1 -->
                    <div class="about-feature-row">
                        <div class="about-feature-icon bg-primary-subtle text-primary">
                            <i class="bi bi-shield-check"></i>
                        </div>
                        <div>
                            <div class="fw-bold text-dark mb-0.5">Zero Hidden Platform Fees</div>
                            <div class="small text-muted">List your laptop, contact sellers, and complete inquiries completely free of charge.</div>
                        </div>
                    </div>

                    <!-- Feature Row 2 -->
                    <div class="about-feature-row">
                        <div class="about-feature-icon bg-success-subtle text-success">
                            <i class="bi bi-chat-dots-fill"></i>
                        </div>
                        <div>
                            <div class="fw-bold text-dark mb-0.5">Direct Buyer-Seller Communication</div>
                            <div class="small text-muted">Send inquiries, negotiate pricing, and clarify device condition before purchase.</div>
                        </div>
                    </div>

                    <!-- Feature Row 3 -->
                    <div class="about-feature-row">
                        <div class="about-feature-icon bg-warning-subtle text-warning">
                            <i class="bi bi-bag-check-fill"></i>
                        </div>
                        <div>
                            <div class="fw-bold text-dark mb-0.5">Seamless COD & Local Handoff</div>
                            <div class="small text-muted">Enjoy peace of mind with transparent checkout and convenient cash on delivery.</div>
                        </div>
                    </div>
                </div>

                <div class="mt-auto pt-2">
                    <a href="<?= BASE_URL ?>/sell.php" class="btn btn-outline-primary rounded-pill px-4 py-2.5 fw-bold w-100 d-flex align-items-center justify-content-center gap-2">
                        <i class="bi bi-plus-circle"></i>
                        <span>List Your Laptop for Free</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Right Side: Premium Laptop & Trust Showcase Card -->
        <div class="col-lg-6">
            <div class="about-card">
                <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1.5 fw-bold" style="font-size: 0.78rem;">
                        <i class="bi bi-laptop me-1"></i> Premier Tech Hub
                    </span>
                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1.5 fw-bold" style="font-size: 0.78rem;">
                        <i class="bi bi-shield-check me-1"></i> 100% Verified
                    </span>
                </div>

                <h3 class="fw-bold mb-3 text-dark">Find Your Next Powerhouse</h3>
                <p class="fs-6 mb-3 text-muted" style="line-height: 1.8;">
                    From top-tier creator workstations and high-FPS gaming rigs to lightweight student ultrabooks, Lapify provides verified benchmark specs and authentic condition reports.
                </p>

                <!-- Laptop Showcase Visual -->
                <div class="position-relative rounded-4 p-3 mb-3 text-center overflow-hidden" style="background: rgba(255, 255, 255, 0.75); border: 1px solid rgba(56, 189, 248, 0.3); box-shadow: 0 4px 15px rgba(56, 189, 248, 0.08);">
                    <img src="<?= BASE_URL ?>/uploads/laptops/MacBook%20Pro%2016%20M3%20Max.jpg" alt="Featured Laptop" class="img-fluid rounded-3" style="max-height: 160px; object-fit: contain; filter: drop-shadow(0 6px 14px rgba(0,0,0,0.12));" onerror="this.src='<?= BASE_URL ?>/uploads/laptops/Asus%20ROG%20Zephyrus%20G16.jpg'">
                </div>

                <!-- Trust Points Grid -->
                <div class="row g-2 mb-3">
                    <div class="col-sm-6">
                        <div class="about-trust-tile">
                            <i class="bi bi-patch-check-fill text-success fs-5"></i>
                            <span class="small fw-bold text-dark">Verified Hardware</span>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="about-trust-tile">
                            <i class="bi bi-cash-coin text-warning fs-5"></i>
                            <span class="small fw-bold text-dark">Zero Commission</span>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="about-trust-tile">
                            <i class="bi bi-truck text-info fs-5"></i>
                            <span class="small fw-bold text-dark">Direct COD Delivery</span>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="about-trust-tile">
                            <i class="bi bi-headset text-primary fs-5"></i>
                            <span class="small fw-bold text-dark">Dedicated Support</span>
                        </div>
                    </div>
                </div>

                <!-- Action Button -->
                <div class="mt-auto pt-2">
                    <a href="<?= BASE_URL ?>/buy.php" class="btn btn-primary rounded-pill px-4 py-2.5 fw-bold shadow w-100 d-flex align-items-center justify-content-center gap-2" style="background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); border: none;">
                        <span>Explore Laptop Deals</span>
                        <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Meet the Team Section -->
    <div class="row mt-5">
        <div class="col-12 mb-4 text-center text-md-start">
            <div class="d-inline-flex align-items-center gap-2 mb-2 px-3 py-1 rounded-pill border bg-light">
                <i class="bi bi-people-fill text-primary"></i>
                <span class="small fw-bold text-dark">PROJECT TEAM</span>
            </div>
            <h3 class="fw-bold text-dark mb-1">Meet the Innovators Behind Lapify</h3>
            <p class="text-muted small">Semester 5 Diploma Project Team at Darshan University</p>
        </div>

        <div class="col-12">
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                <?php
                $team = [
                    ['name' => 'Yash Kotak', 'email' => '24020201090@darshan.ac.in', 'role' => 'Lead Developer & Architect'],
                    ['name' => 'Samiksha Gajera', 'email' => '24020201052@darshan.ac.in', 'role' => 'UI/UX & Frontend Lead'],
                    ['name' => 'Krisha Patel', 'email' => '24020201138@darshan.ac.in', 'role' => 'QA & Database Engineer'],
                    ['name' => 'Vedang Joshi', 'email' => '24020201072@darshan.ac.in', 'role' => 'Backend & Auth Engineer'],
                    ['name' => 'Prisha Vasavada', 'email' => '24020201170@darshan.ac.in', 'role' => 'Documentation & Testing'],
                ];
                function initials($name) {
                    $parts = explode(' ', trim($name));
                    return strtoupper(substr($parts[0], 0, 1) . substr(end($parts), 0, 1));
                }
                ?>
                <?php foreach ($team as $member): ?>
                    <div class="col">
                        <div class="about-team-card h-100">
                            <div class="d-flex align-items-center gap-3">
                                <div class="about-team-avatar">
                                    <?= initials($member['name']) ?>
                                </div>
                                <div class="min-width-0 flex-grow-1">
                                    <div class="fw-bold fs-6 text-dark mb-0.5 text-truncate"><?= escape($member['name']) ?></div>
                                    <div class="badge bg-primary-subtle text-primary rounded-pill mb-1.5 px-2 py-0.5" style="font-size: 10px;"><?= escape($member['role']) ?></div>
                                    <div>
                                        <a href="mailto:<?= escape($member['email']) ?>" class="small text-muted text-decoration-none d-inline-flex align-items-center gap-1">
                                            <i class="bi bi-envelope-fill text-primary"></i>
                                            <span class="text-truncate" style="max-width: 170px;"><?= escape($member['email']) ?></span>
                                        </a>
                                    </div>
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
