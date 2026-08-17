<?php
// contact.php - Contact Support Page
$page_title = "Contact Support | Lapify";
require_once __DIR__ . '/includes/header.php';

$sent_success = false;
$contact_name = '';
$contact_email = '';
$contact_subject = '';
$contact_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contact_name = sanitizeInput($_POST['name'] ?? '');
    $contact_email = sanitizeInput($_POST['email'] ?? '');
    $contact_subject = sanitizeInput($_POST['subject'] ?? '');
    $contact_message = sanitizeInput($_POST['message'] ?? '');

    if (!empty($contact_name) && !empty($contact_email) && !empty($contact_message)) {
        $sent_success = true;
        setFlash('success', "Thank you for reaching out, {$contact_name}! Your message has been received.");
    }
}

require_once __DIR__ . '/includes/navbar.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="text-center mb-5">
                <span class="badge bg-primary-subtle text-primary font-weight-bold px-3 py-1.5 rounded-pill mb-2">Help & Support</span>
                <h2 class="fw-bold">Get In Touch With Lapify</h2>
                <p class="text-muted">Have a question or feedback? Fill out the form below to contact our support team.</p>
            </div>

            <?php displayFlash(); ?>

            <div class="card border-0 shadow-lg rounded-4 p-4 p-md-5">
                <form action="contact.php" method="POST" class="needs-validation" novalidate>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label font-weight-bold">Your Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name" class="form-control" value="<?= escape($contact_name) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label font-weight-bold">Your Email Address <span class="text-danger">*</span></label>
                            <input type="email" name="email" id="email" class="form-control" value="<?= escape($contact_email) ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="subject" class="form-label font-weight-bold">Subject</label>
                        <input type="text" name="subject" id="subject" class="form-control" value="<?= escape($contact_subject) ?>" placeholder="e.g. Account question, listing help...">
                    </div>

                    <div class="mb-4">
                        <label for="message" class="form-label font-weight-bold">Message Content <span class="text-danger">*</span></label>
                        <textarea name="message" id="message" rows="5" class="form-control" placeholder="Write your inquiry here..." required><?= escape($contact_message) ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary px-5 py-2.5 rounded-3 font-weight-bold">
                        <i class="bi bi-send-fill me-2"></i>Send Support Inquiry
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
