<?php
// terms.php - Terms of Service Page (Static Content)
$page_title = "Terms of Service | Lapify";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<!-- Terms Hero Banner -->
<div class="policy-hero-banner mb-5">
    <div class="container text-center py-3">
        <div class="d-inline-flex align-items-center gap-2 mb-3">
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1.5 fw-bold" style="font-size: 0.82rem;">
                <i class="bi bi-file-earmark-text-fill me-1"></i> User Agreement
            </span>
            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1.5 fw-bold" style="font-size: 0.82rem;">
                <i class="bi bi-patch-check-fill me-1"></i> Effective: September 2026
            </span>
        </div>
        <h1 class="display-5 fw-extrabold mb-3 text-dark" style="letter-spacing: -0.5px;">Terms of Service</h1>
        <p class="lead mx-auto fs-5 text-muted" style="max-width: 720px; line-height: 1.8;">
            Welcome to Lapify. By accessing or using our platform, you agree to comply with these terms designed to ensure a safe, fair, and commission-free marketplace for laptop enthusiasts.
        </p>
    </div>
</div>

<div class="container pb-5">
    <div class="row g-4">
        <!-- Sticky Navigation Sidebar (Desktop) -->
        <div class="col-lg-3 d-none d-lg-block">
            <div class="policy-nav-card">
                <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom border-secondary-subtle">
                    <i class="bi bi-list-nested text-primary fs-5"></i>
                    <span class="fw-bold fs-6">Table of Contents</span>
                </div>
                <ul class="policy-nav-list">
                    <li>
                        <a href="#acceptance" class="policy-nav-link">
                            <span class="policy-nav-icon"><i class="bi bi-check2-circle"></i></span>
                            <span>1. Acceptance</span>
                        </a>
                    </li>
                    <li>
                        <a href="#user-accounts" class="policy-nav-link">
                            <span class="policy-nav-icon"><i class="bi bi-person-badge"></i></span>
                            <span>2. User Accounts</span>
                        </a>
                    </li>
                    <li>
                        <a href="#seller-rules" class="policy-nav-link">
                            <span class="policy-nav-icon"><i class="bi bi-laptop"></i></span>
                            <span>3. Seller Guidelines</span>
                        </a>
                    </li>
                    <li>
                        <a href="#buyer-rules" class="policy-nav-link">
                            <span class="policy-nav-icon"><i class="bi bi-bag-check"></i></span>
                            <span>4. Buyer Guidelines</span>
                        </a>
                    </li>
                    <li>
                        <a href="#zero-commission" class="policy-nav-link">
                            <span class="policy-nav-icon"><i class="bi bi-cash-stack"></i></span>
                            <span>5. 0% Fee Model</span>
                        </a>
                    </li>
                    <li>
                        <a href="#prohibited" class="policy-nav-link">
                            <span class="policy-nav-icon"><i class="bi bi-slash-circle"></i></span>
                            <span>6. Prohibited Acts</span>
                        </a>
                    </li>
                    <li>
                        <a href="#liability" class="policy-nav-link">
                            <span class="policy-nav-icon"><i class="bi bi-exclamation-triangle"></i></span>
                            <span>7. Disclaimers</span>
                        </a>
                    </li>
                    <li>
                        <a href="#disputes" class="policy-nav-link">
                            <span class="policy-nav-icon"><i class="bi bi-chat-heart"></i></span>
                            <span>8. Dispute Resolution</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Terms Content Area -->
        <div class="col-lg-9 col-12">
            <div class="policy-card">
                
                <!-- Section 1: Acceptance -->
                <div id="acceptance" class="policy-section">
                    <div class="policy-section-header">
                        <div class="policy-section-icon bg-primary-subtle text-primary">
                            <i class="bi bi-check2-circle"></i>
                        </div>
                        <div>
                            <h2 class="fs-4 fw-bold mb-1 text-dark">1. Acceptance of Terms & Marketplace Nature</h2>
                            <div class="small text-muted">Legal agreement between you and Lapify</div>
                        </div>
                    </div>
                    <p class="text-muted" style="line-height: 1.8;">
                        By creating an account, browsing listings, publishing laptop sales ads, or initiating purchase inquiries on Lapify ("the Platform"), you acknowledge that you have read, understood, and agreed to be bound by these Terms of Service and our Privacy Policy.
                    </p>
                    
                    <div class="policy-callout policy-callout-info">
                        <i class="bi bi-info-circle-fill fs-4 flex-shrink-0"></i>
                        <div>
                            <strong>Direct Marketplace Notice:</strong> Lapify operates as a technology platform connecting independent buyers and sellers. Lapify is not a direct retail seller of used devices listed by users, nor does it hold escrow funds.
                        </div>
                    </div>
                </div>

                <hr class="border-secondary-subtle opacity-50 my-4">

                <!-- Section 2: User Accounts -->
                <div id="user-accounts" class="policy-section">
                    <div class="policy-section-header">
                        <div class="policy-section-icon bg-success-subtle text-success">
                            <i class="bi bi-person-badge"></i>
                        </div>
                        <div>
                            <h2 class="fs-4 fw-bold mb-1 text-dark">2. User Account Responsibilities</h2>
                            <div class="small text-muted">Eligibility, security, and profile accuracy</div>
                        </div>
                    </div>
                    <p class="text-muted" style="line-height: 1.8;">
                        To unlock core interactive features such as listing a device, managing inquiries, or saving items, you must register for an account:
                    </p>

                    <div class="d-flex flex-column gap-2 mb-3">
                        <div class="policy-list-item">
                            <span class="policy-list-bullet bg-success-subtle text-success"><i class="bi bi-check-lg"></i></span>
                            <div>
                                <div class="fw-bold text-dark mb-0.5">Accurate Profile Data</div>
                                <div class="small text-muted">You agree to provide true, current, and verifiable contact information (Name, Email, Phone, City).</div>
                            </div>
                        </div>
                        <div class="policy-list-item">
                            <span class="policy-list-bullet bg-success-subtle text-success"><i class="bi bi-check-lg"></i></span>
                            <div>
                                <div class="fw-bold text-dark mb-0.5">Credential Confidentiality</div>
                                <div class="small text-muted">You are solely responsible for maintaining the confidentiality of your account password and for all activities under your account.</div>
                            </div>
                        </div>
                        <div class="policy-list-item">
                            <span class="policy-list-bullet bg-success-subtle text-success"><i class="bi bi-check-lg"></i></span>
                            <div>
                                <div class="fw-bold text-dark mb-0.5">Single Account Principle</div>
                                <div class="small text-muted">Users must not create deceptive duplicate accounts for fraudulent bidding or fake inquiries.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <hr class="border-secondary-subtle opacity-50 my-4">

                <!-- Section 3: Seller Guidelines -->
                <div id="seller-rules" class="policy-section">
                    <div class="policy-section-header">
                        <div class="policy-section-icon bg-warning-subtle text-warning">
                            <i class="bi bi-laptop"></i>
                        </div>
                        <div>
                            <h2 class="fs-4 fw-bold mb-1 text-dark">3. Laptop Listing & Seller Guidelines</h2>
                            <div class="small text-muted">Standards for authentic hardware listings</div>
                        </div>
                    </div>
                    <p class="text-muted" style="line-height: 1.8;">
                        Sellers must maintain high standards of transparency to preserve community trust:
                    </p>
                    <ul class="text-muted d-flex flex-column gap-2 mb-3 ps-3" style="line-height: 1.8;">
                        <li><strong>Honest Technical Specs:</strong> You must accurately represent the processor (CPU), graphics card (GPU), RAM capacity, storage health, battery health percentage, and cosmetic condition (Flawless, Good, Fair).</li>
                        <li><strong>Genuine Device Ownership:</strong> You must be the rightful legal owner of any laptop listed for sale. Listing stolen, leased, or company-locked hardware is strictly prohibited.</li>
                        <li><strong>Authentic Imagery:</strong> Sellers must upload authentic photos showing the actual physical state of the device, including any visible scratches, dents, or display blemishes.</li>
                        <li><strong>Listing Lifecycle:</strong> Once your laptop is sold or no longer available, you agree to mark the listing as Sold or delete it promptly.</li>
                    </ul>
                </div>

                <hr class="border-secondary-subtle opacity-50 my-4">

                <!-- Section 4: Buyer Guidelines -->
                <div id="buyer-rules" class="policy-section">
                    <div class="policy-section-header">
                        <div class="policy-section-icon bg-info-subtle text-info">
                            <i class="bi bi-bag-check"></i>
                        </div>
                        <div>
                            <h2 class="fs-4 fw-bold mb-1 text-dark">4. Buyer Guidelines & Inspection Policy</h2>
                            <div class="small text-muted">Best practices for safe laptop verification</div>
                        </div>
                    </div>
                    <p class="text-muted" style="line-height: 1.8;">
                        To ensure complete satisfaction with pre-owned hardware, buyers are encouraged to follow our recommended inspection protocol:
                    </p>

                    <div class="policy-callout policy-callout-success">
                        <i class="bi bi-shield-check fs-4 flex-shrink-0"></i>
                        <div>
                            <strong>Handover Verification Checklist:</strong> During in-person meetups or upon Cash on Delivery receipt, always verify: (1) System specs match the listing, (2) Display has no dead pixels, (3) Keyboard keys and trackpad respond, (4) Battery charges normally, (5) BIOS and iCloud/OEM accounts are completely signed out.
                        </div>
                    </div>
                </div>

                <hr class="border-secondary-subtle opacity-50 my-4">

                <!-- Section 5: 0% Fee Model -->
                <div id="zero-commission" class="policy-section">
                    <div class="policy-section-header">
                        <div class="policy-section-icon bg-success-subtle text-success">
                            <i class="bi bi-cash-stack"></i>
                        </div>
                        <div>
                            <h2 class="fs-4 fw-bold mb-1 text-dark">5. Zero Commission & Payment Facilitation</h2>
                            <div class="small text-muted">Completely free community platform</div>
                        </div>
                    </div>
                    <p class="text-muted" style="line-height: 1.8;">
                        Lapify does not charge listing creation fees, subscription costs, or transaction commissions:
                    </p>
                    <ul class="text-muted d-flex flex-column gap-2 mb-3 ps-3" style="line-height: 1.8;">
                        <li><strong>100% Free Ads:</strong> Posting ads, browsing specs, contacting sellers, and comparing prices is 100% free of charge.</li>
                        <li><strong>Direct Settlement:</strong> Payments (via Cash on Delivery, UPI, or in-person cash handoff) occur directly between buyer and seller without middleman deductions.</li>
                    </ul>
                </div>

                <hr class="border-secondary-subtle opacity-50 my-4">

                <!-- Section 6: Prohibited Activities -->
                <div id="prohibited" class="policy-section">
                    <div class="policy-section-header">
                        <div class="policy-section-icon bg-danger-subtle text-danger">
                            <i class="bi bi-slash-circle"></i>
                        </div>
                        <div>
                            <h2 class="fs-4 fw-bold mb-1 text-dark">6. Prohibited Activities & Account Penalties</h2>
                            <div class="small text-muted">Enforcing a safe trading environment</div>
                        </div>
                    </div>
                    <p class="text-muted" style="line-height: 1.8;">
                        Users who engage in the following activities face immediate listing deletion and permanent account banning:
                    </p>
                    <ul class="text-muted d-flex flex-column gap-2 mb-3 ps-3" style="line-height: 1.8;">
                        <li>Posting counterfeit, replica, stolen, or remote-locked laptop devices.</li>
                        <li>Intentionally misrepresenting hardware components (e.g. false GPU specs or disguised damage).</li>
                        <li>Sending abusive, harassing, or spam messages to buyers or sellers.</li>
                        <li>Attempting unauthorized SQL injection, CSRF attacks, scraping, or platform disruptions.</li>
                    </ul>
                </div>

                <hr class="border-secondary-subtle opacity-50 my-4">

                <!-- Section 7: Disclaimers -->
                <div id="liability" class="policy-section">
                    <div class="policy-section-header">
                        <div class="policy-section-icon bg-warning-subtle text-warning">
                            <i class="bi bi-exclamation-triangle"></i>
                        </div>
                        <div>
                            <h2 class="fs-4 fw-bold mb-1 text-dark">7. Disclaimer of Warranties & Limitation of Liability</h2>
                            <div class="small text-muted">Legal boundaries and user responsibility</div>
                        </div>
                    </div>
                    <p class="text-muted" style="line-height: 1.8;">
                        The Lapify platform is provided on an "as is" and "as available" basis. While our administration actively moderates submitted listings, we do not independently physically test or warrant hardware items sold by independent users. Any hardware warranty or refund commitments must be agreed directly between the respective buyer and seller.
                    </p>
                </div>

                <hr class="border-secondary-subtle opacity-50 my-4">

                <!-- Section 8: Dispute Resolution -->
                <div id="disputes" class="policy-section mb-0">
                    <div class="policy-section-header">
                        <div class="policy-section-icon bg-primary-subtle text-primary">
                            <i class="bi bi-chat-heart"></i>
                        </div>
                        <div>
                            <h2 class="fs-4 fw-bold mb-1 text-dark">8. Support & Dispute Assistance</h2>
                            <div class="small text-muted">Need help with a trade or transaction?</div>
                        </div>
                    </div>
                    <p class="text-muted" style="line-height: 1.8;">
                        If you encounter an uncooperative seller, inaccurate listing, or suspicious activity, report it immediately to our team:
                    </p>
                    <div class="d-flex flex-wrap gap-3 mt-3">
                        <a href="<?= BASE_URL ?>/contact.php" class="btn btn-primary rounded-pill px-4 py-2.5 fw-bold d-inline-flex align-items-center gap-2">
                            <i class="bi bi-shield-fill-check"></i>
                            <span>Report an Issue / Contact Support</span>
                        </a>
                        <a href="<?= BASE_URL ?>/privacy.php" class="btn btn-outline-secondary rounded-pill px-4 py-2.5 fw-bold d-inline-flex align-items-center gap-2">
                            <i class="bi bi-shield-lock"></i>
                            <span>Read Privacy Policy</span>
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
