<?php
// privacy.php - Privacy Policy Page (Static Content)
$page_title = "Privacy Policy | Lapify";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<!-- Policy Hero Banner -->
<div class="policy-hero-banner mb-5">
    <div class="container text-center py-3">
        <div class="d-inline-flex align-items-center gap-2 mb-3">
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1.5 fw-bold" style="font-size: 0.82rem;">
                <i class="bi bi-shield-lock-fill me-1"></i> Trust & Data Transparency
            </span>
            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1.5 fw-bold" style="font-size: 0.82rem;">
                <i class="bi bi-calendar-check me-1"></i> Last Updated: September 2026
            </span>
        </div>
        <h1 class="display-5 fw-extrabold mb-3 text-dark" style="letter-spacing: -0.5px;">Privacy Policy</h1>
        <p class="lead mx-auto fs-5 text-muted" style="max-width: 720px; line-height: 1.8;">
            At Lapify, your privacy is fundamental. We facilitate direct peer-to-peer laptop transactions with zero hidden fees and strictly protect your personal information.
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
                        <a href="#overview" class="policy-nav-link">
                            <span class="policy-nav-icon"><i class="bi bi-info-circle"></i></span>
                            <span>1. Overview</span>
                        </a>
                    </li>
                    <li>
                        <a href="#data-collected" class="policy-nav-link">
                            <span class="policy-nav-icon"><i class="bi bi-database-check"></i></span>
                            <span>2. Data We Collect</span>
                        </a>
                    </li>
                    <li>
                        <a href="#how-we-use" class="policy-nav-link">
                            <span class="policy-nav-icon"><i class="bi bi-gear-wide-connected"></i></span>
                            <span>3. How We Use Data</span>
                        </a>
                    </li>
                    <li>
                        <a href="#p2p-privacy" class="policy-nav-link">
                            <span class="policy-nav-icon"><i class="bi bi-shield-check"></i></span>
                            <span>4. P2P Contact Safety</span>
                        </a>
                    </li>
                    <li>
                        <a href="#cookies-storage" class="policy-nav-link">
                            <span class="policy-nav-icon"><i class="bi bi-cookie"></i></span>
                            <span>5. Cookies & Sessions</span>
                        </a>
                    </li>
                    <li>
                        <a href="#security" class="policy-nav-link">
                            <span class="policy-nav-icon"><i class="bi bi-lock-fill"></i></span>
                            <span>6. Data Security</span>
                        </a>
                    </li>
                    <li>
                        <a href="#user-rights" class="policy-nav-link">
                            <span class="policy-nav-icon"><i class="bi bi-person-check-fill"></i></span>
                            <span>7. Your Rights</span>
                        </a>
                    </li>
                    <li>
                        <a href="#contact" class="policy-nav-link">
                            <span class="policy-nav-icon"><i class="bi bi-envelope-fill"></i></span>
                            <span>8. Contact & Queries</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Policy Content Area -->
        <div class="col-lg-9 col-12">
            <div class="policy-card">
                
                <!-- Section 1: Overview -->
                <div id="overview" class="policy-section">
                    <div class="policy-section-header">
                        <div class="policy-section-icon bg-primary-subtle text-primary">
                            <i class="bi bi-info-circle-fill"></i>
                        </div>
                        <div>
                            <h2 class="fs-4 fw-bold mb-1 text-dark">1. Overview & Scope</h2>
                            <div class="small text-muted">Understanding Lapify's role as a direct marketplace</div>
                        </div>
                    </div>
                    <p class="text-muted" style="line-height: 1.8;">
                        Lapify ("we", "our", or "the platform") is a peer-to-peer online marketplace engineered to connect buyers and sellers of new, certified, and pre-owned laptops. This Privacy Policy explains what personal information we collect when you visit our website, register an account, publish laptop listings, or submit buyer inquiries, and how that information is handled securely.
                    </p>
                    
                    <div class="policy-callout policy-callout-info">
                        <i class="bi bi-info-circle-fill fs-4 flex-shrink-0"></i>
                        <div>
                            <strong>Zero Data Monetization Guarantee:</strong> We do not sell, rent, or trade your personal contact details, browsing logs, or search history to third-party ad networks or data brokers.
                        </div>
                    </div>
                </div>

                <hr class="border-secondary-subtle opacity-50 my-4">

                <!-- Section 2: Data We Collect -->
                <div id="data-collected" class="policy-section">
                    <div class="policy-section-header">
                        <div class="policy-section-icon bg-success-subtle text-success">
                            <i class="bi bi-database-check"></i>
                        </div>
                        <div>
                            <h2 class="fs-4 fw-bold mb-1 text-dark">2. Information We Collect</h2>
                            <div class="small text-muted">What data is required to operate our marketplace</div>
                        </div>
                    </div>
                    <p class="text-muted" style="line-height: 1.8;">
                        We collect information directly provided by you during your use of Lapify, as well as minimal technical session parameters necessary for system functionality:
                    </p>

                    <div class="d-flex flex-column gap-2 mb-3">
                        <div class="policy-list-item">
                            <span class="policy-list-bullet bg-primary-subtle text-primary">1</span>
                            <div>
                                <div class="fw-bold text-dark mb-0.5">Account & Profile Information</div>
                                <div class="small text-muted">Your full name, verified email address, phone number, location/city, and optional profile image provided upon registration.</div>
                            </div>
                        </div>
                        <div class="policy-list-item">
                            <span class="policy-list-bullet bg-primary-subtle text-primary">2</span>
                            <div>
                                <div class="fw-bold text-dark mb-0.5">Laptop Listing Details</div>
                                <div class="small text-muted">Specifications (Brand, Model, CPU, GPU, RAM, Storage, Condition, Warranty), asking prices, and authentic uploaded hardware images.</div>
                            </div>
                        </div>
                        <div class="policy-list-item">
                            <span class="policy-list-bullet bg-primary-subtle text-primary">3</span>
                            <div>
                                <div class="fw-bold text-dark mb-0.5">Inquiries & Communication Records</div>
                                <div class="small text-muted">Messages sent between prospective buyers and sellers regarding pricing, specs, meetup coordination, or Cash on Delivery details.</div>
                            </div>
                        </div>
                        <div class="policy-list-item">
                            <span class="policy-list-bullet bg-primary-subtle text-primary">4</span>
                            <div>
                                <div class="fw-bold text-dark mb-0.5">Support & Feedback Queries</div>
                                <div class="small text-muted">Information submitted via our contact forms to resolve technical questions, listing reports, or account support.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <hr class="border-secondary-subtle opacity-50 my-4">

                <!-- Section 3: How We Use Data -->
                <div id="how-we-use" class="policy-section">
                    <div class="policy-section-header">
                        <div class="policy-section-icon bg-warning-subtle text-warning">
                            <i class="bi bi-gear-wide-connected"></i>
                        </div>
                        <div>
                            <h2 class="fs-4 fw-bold mb-1 text-dark">3. How We Use Your Information</h2>
                            <div class="small text-muted">Legitimate platform purposes</div>
                        </div>
                    </div>
                    <p class="text-muted" style="line-height: 1.8;">
                        Your data is utilized exclusively to provide, maintain, and enhance the Lapify ecosystem:
                    </p>
                    <ul class="text-muted d-flex flex-column gap-2 mb-3 ps-3" style="line-height: 1.8;">
                        <li>Publishing your laptop advertisements so relevant buyers can search, filter, and discover your hardware.</li>
                        <li>Facilitating direct inquiries and notification alerts when an interested buyer contacts you.</li>
                        <li>Managing user dashboards, saved wishlists, cart inquiries, and active listing statuses.</li>
                        <li>Detecting and preventing fraudulent listings, unauthorized automated scraping, and spam activities.</li>
                        <li>Improving search accuracy and responsive performance across mobile and desktop devices.</li>
                    </ul>
                </div>

                <hr class="border-secondary-subtle opacity-50 my-4">

                <!-- Section 4: P2P Privacy & Contact Protection -->
                <div id="p2p-privacy" class="policy-section">
                    <div class="policy-section-header">
                        <div class="policy-section-icon bg-primary-subtle text-primary">
                            <i class="bi bi-shield-check"></i>
                        </div>
                        <div>
                            <h2 class="fs-4 fw-bold mb-1 text-dark">4. Peer-to-Peer Privacy & Contact Sharing</h2>
                            <div class="small text-muted">How your contact information is shared between trading parties</div>
                        </div>
                    </div>
                    <p class="text-muted" style="line-height: 1.8;">
                        Because Lapify is a direct peer-to-peer platform, certain contact details are exchanged to allow buyers and sellers to agree upon terms:
                    </p>

                    <div class="policy-callout policy-callout-success">
                        <i class="bi bi-check-circle-fill fs-4 flex-shrink-0"></i>
                        <div>
                            <strong>Controlled Sharing:</strong> Your email address is never published openly on search engine index pages. Buyer-seller communication is initiated via our authenticated inquiry channels to prevent harvesting by spam bots.
                        </div>
                    </div>

                    <p class="text-muted" style="line-height: 1.8;">
                        When you submit a purchase request or seller inquiry, your verified name and selected communication details are shared solely with the respective listing owner to finalize physical inspection, payment method, or Cash on Delivery.
                    </p>
                </div>

                <hr class="border-secondary-subtle opacity-50 my-4">

                <!-- Section 5: Cookies & Sessions -->
                <div id="cookies-storage" class="policy-section">
                    <div class="policy-section-header">
                        <div class="policy-section-icon bg-info-subtle text-info">
                            <i class="bi bi-cookie"></i>
                        </div>
                        <div>
                            <h2 class="fs-4 fw-bold mb-1 text-dark">5. Cookies & Local Session Storage</h2>
                            <div class="small text-muted">Essential browser storage mechanisms</div>
                        </div>
                    </div>
                    <p class="text-muted" style="line-height: 1.8;">
                        Lapify employs strictly functional session cookies and local storage tokens:
                    </p>
                    <div class="d-flex flex-column gap-2 mb-3">
                        <div class="policy-list-item">
                            <span class="policy-list-bullet bg-info-subtle text-info"><i class="bi bi-key-fill"></i></span>
                            <div>
                                <div class="fw-bold text-dark mb-0.5">Authentication & CSRF Protection Tokens</div>
                                <div class="small text-muted">Secures your active login state and protects forms against Cross-Site Request Forgery attacks.</div>
                            </div>
                        </div>
                        <div class="policy-list-item">
                            <span class="policy-list-bullet bg-info-subtle text-info"><i class="bi bi-moon-stars-fill"></i></span>
                            <div>
                                <div class="fw-bold text-dark mb-0.5">Theme Preferences</div>
                                <div class="small text-muted">Remembers your chosen Light or Dark display theme across browser tabs.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <hr class="border-secondary-subtle opacity-50 my-4">

                <!-- Section 6: Security -->
                <div id="security" class="policy-section">
                    <div class="policy-section-header">
                        <div class="policy-section-icon bg-danger-subtle text-danger">
                            <i class="bi bi-lock-fill"></i>
                        </div>
                        <div>
                            <h2 class="fs-4 fw-bold mb-1 text-dark">6. Data Security Practices</h2>
                            <div class="small text-muted">How we protect your stored account data</div>
                        </div>
                    </div>
                    <p class="text-muted" style="line-height: 1.8;">
                        We implement industry-standard administrative, physical, and technical safeguards:
                    </p>
                    <ul class="text-muted d-flex flex-column gap-2 mb-3 ps-3" style="line-height: 1.8;">
                        <li><strong>Strong Password Hashing:</strong> User passwords are never saved in plaintext; they are securely hashed using strong modern algorithms (<code class="text-primary">bcrypt</code> / <code class="text-primary">PASSWORD_DEFAULT</code>).</li>
                        <li><strong>Prepared Database Queries:</strong> All database operations utilize parameterized SQL statements to safeguard against SQL Injection vulnerabilities.</li>
                        <li><strong>Secure File Handling:</strong> Uploaded laptop and profile images are sanitized and validated against malicious executable payloads.</li>
                    </ul>
                </div>

                <hr class="border-secondary-subtle opacity-50 my-4">

                <!-- Section 7: User Rights -->
                <div id="user-rights" class="policy-section">
                    <div class="policy-section-header">
                        <div class="policy-section-icon bg-success-subtle text-success">
                            <i class="bi bi-person-check-fill"></i>
                        </div>
                        <div>
                            <h2 class="fs-4 fw-bold mb-1 text-dark">7. Your Rights & Data Control</h2>
                            <div class="small text-muted">Managing, editing, and deleting your data</div>
                        </div>
                    </div>
                    <p class="text-muted" style="line-height: 1.8;">
                        You maintain control over your personal data on Lapify:
                    </p>
                    <ul class="text-muted d-flex flex-column gap-2 mb-3 ps-3" style="line-height: 1.8;">
                        <li><strong>Access & Modify:</strong> You can edit your profile details, location, phone number, and password at any time from your <a href="<?= BASE_URL ?>/profile.php" class="text-primary fw-semibold">Profile Settings</a>.</li>
                        <li><strong>Manage Listings:</strong> You can update specs, adjust prices, mark items as sold, or permanently remove your laptop ads from your <a href="<?= BASE_URL ?>/my-listings.php" class="text-primary fw-semibold">My Listings</a> page.</li>
                        <li><strong>Account Removal:</strong> You may request complete account closure and listing deletion by submitting a request via our Contact page.</li>
                    </ul>
                </div>

                <hr class="border-secondary-subtle opacity-50 my-4">

                <!-- Section 8: Contact -->
                <div id="contact" class="policy-section mb-0">
                    <div class="policy-section-header">
                        <div class="policy-section-icon bg-primary-subtle text-primary">
                            <i class="bi bi-envelope-fill"></i>
                        </div>
                        <div>
                            <h2 class="fs-4 fw-bold mb-1 text-dark">8. Contact Us & Grievances</h2>
                            <div class="small text-muted">Have questions about your privacy?</div>
                        </div>
                    </div>
                    <p class="text-muted" style="line-height: 1.8;">
                        If you have any questions, concerns, or feedback regarding this Privacy Policy or our platform data practices, please reach out directly:
                    </p>
                    <div class="d-flex flex-wrap gap-3 mt-3">
                        <a href="<?= BASE_URL ?>/contact.php" class="btn btn-primary rounded-pill px-4 py-2.5 fw-bold d-inline-flex align-items-center gap-2">
                            <i class="bi bi-chat-dots-fill"></i>
                            <span>Contact Support Team</span>
                        </a>
                        <a href="<?= BASE_URL ?>/about.php" class="btn btn-outline-secondary rounded-pill px-4 py-2.5 fw-bold d-inline-flex align-items-center gap-2">
                            <i class="bi bi-laptop"></i>
                            <span>About Lapify</span>
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
