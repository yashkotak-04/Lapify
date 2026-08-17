<?php
// includes/footer.php - Global Page Footer
?>
    </main>

    <!-- Global Site Footer -->
    <footer>
        <div class="container">
            <div class="row g-4 mb-4">
                <div class="col-lg-4 col-md-6">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <i class="bi bi-laptop-fill text-primary fs-3"></i>
                        <span class="fs-4 font-weight-bold">Lapify</span>
                    </div>
                    <p class="mb-3" style="line-height: 1.6;">
                        Your trusted direct marketplace to buy brand new laptops, discover certified used deals, and sell pre-owned devices with zero hidden commission.
                    </p>
                    <div class="d-flex gap-3 fs-5">
                        <a href="#"><i class="bi bi-facebook"></i></a>
                        <a href="#"><i class="bi bi-twitter-x"></i></a>
                        <a href="#"><i class="bi bi-instagram"></i></a>
                        <a href="#"><i class="bi bi-linkedin"></i></a>
                    </div>
                </div>

                <div class="col-lg-2 col-md-6">
                    <h5 class="mb-3 fs-6 font-weight-bold text-uppercase">Quick Links</h5>
                    <ul class="list-unstyled d-flex flex-column gap-2">
                        <li><a href="<?= BASE_URL ?>/index.php">Home</a></li>
                        <li><a href="<?= BASE_URL ?>/buy.php">Buy Laptops</a></li>
                        <li><a href="<?= BASE_URL ?>/sell.php">Sell Laptop</a></li>
                        <li><a href="<?= BASE_URL ?>/about.php">About Lapify</a></li>
                        <li><a href="<?= BASE_URL ?>/contact.php">Contact & Support</a></li>
                    </ul>
                </div>

                <div class="col-lg-3 col-md-6">
                    <h5 class="mb-3 fs-6 font-weight-bold text-uppercase">Top Brands</h5>
                    <ul class="list-unstyled d-flex flex-column gap-2">
                        <li><a href="<?= BASE_URL ?>/buy.php?brand=1">Apple MacBooks</a></li>
                        <li><a href="<?= BASE_URL ?>/buy.php?brand=2">Dell XPS & Inspiron</a></li>
                        <li><a href="<?= BASE_URL ?>/buy.php?brand=3">HP Spectre & Pavilion</a></li>
                        <li><a href="<?= BASE_URL ?>/buy.php?brand=4">Lenovo ThinkPad</a></li>
                        <li><a href="<?= BASE_URL ?>/buy.php?brand=5">Asus ROG & Zenbook</a></li>
                    </ul>
                </div>

                <div class="col-lg-3 col-md-6">
                    <h5 class="mb-3 fs-6 font-weight-bold text-uppercase">Safety & Trust</h5>
                    <p class="small mb-2"><i class="bi bi-shield-check text-success me-2 fs-5"></i>Verified buyer and seller listings.</p>
                    <p class="small mb-2"><i class="bi bi-patch-check text-primary me-2 fs-5"></i>Secure checkout requests and support.</p>
                    <p class="small"><i class="bi bi-info-circle text-warning me-2 fs-5"></i>No online payment gateway needed.</p>
                </div>
            </div>

            <hr class="border-secondary opacity-25">

            <div class="d-flex flex-column flex-md-row align-items-center justify-content-between pt-2 pb-3 small">
                <div>&copy; <?= date('Y') ?> Lapify. All rights reserved.</div>
                <div class="d-flex gap-3 mt-2 mt-md-0">
                    <a href="<?= BASE_URL ?>/about.php">Privacy Policy</a>
                    <span>&bull;</span>
                    <a href="<?= BASE_URL ?>/about.php">Terms of Service</a>
                    <span>&bull;</span>
                    <a href="<?= BASE_URL ?>/admin/login.php">Admin Access</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5.3 Bundle JS (with Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <!-- Optional: Confetti lib for purchase celebration -->
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js" crossorigin="anonymous"></script>
    <!-- Custom Application Scripts -->
    <script src="<?= BASE_URL ?>/assets/js/toast.js"></script>
    <script src="<?= BASE_URL ?>/assets/js/main.js"></script>
    <script src="<?= BASE_URL ?>/assets/js/auth.js"></script>
    <script src="<?= BASE_URL ?>/assets/js/validation.js"></script>
    <script src="<?= BASE_URL ?>/assets/js/dashboard.js"></script>
    <script src="<?= BASE_URL ?>/assets/js/transitions.js"></script>
    <script src="<?= BASE_URL ?>/assets/js/checkout.js"></script>
        <script>
            // Confetti library helper - ensure not to block if unavailable
            function triggerPurchaseConfetti() {
                try {
                    if (typeof confetti === 'function') {
                        confetti({ particleCount: 150, spread: 70, origin: { y: 0.6 } });
                    }
                } catch (e) {
                    console.warn('Confetti failed:', e);
                }
            }
        </script>
    <script>
    // Add project team quick info to footer for accessibility
    (function(){
        try {
            var footer = document.querySelector('footer .container');
            if (footer) {
                var contact = document.createElement('div');
                contact.className = 'mt-3 small';
                contact.innerHTML = '<strong>Project Team:</strong> Yash Kotak <span class="fw-bold">•</span> Krisha Patel <span class="fw-bold">•</span> Samiksha Gajera <span class="fw-bold">•</span> Prisha Vasavada <span class="fw-bold">•</span> Vedang Joshi';
                footer.appendChild(contact);
            }
        } catch(e){}
    })();
    </script>
</body>
</html>
