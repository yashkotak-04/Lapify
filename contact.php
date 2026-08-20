<?php
// contact.php - Contact Support Page
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$current_user = getCurrentUser();
$contact_name = $current_user['full_name'] ?? '';
$contact_email = $current_user['email'] ?? '';
$contact_subject = '';
$contact_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Session expired. Please try again.');
        header('Location: ' . BASE_URL . '/contact.php');
        exit();
    }

    $contact_name = trim(sanitizeInput($_POST['name'] ?? ''));
    $contact_email = trim(sanitizeInput($_POST['email'] ?? ''));
    $contact_subject = trim(sanitizeInput($_POST['subject'] ?? ''));
    $contact_message = trim(sanitizeInput($_POST['message'] ?? ''));

    $errors = [];
    if (empty($contact_name)) {
        $errors[] = 'Please enter your name.';
    }
    if (empty($contact_email) || !filter_var($contact_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (empty($contact_message)) {
        $errors[] = 'Please enter your message content.';
    }

    if (empty($errors)) {
        $conn = getDbConnection();
        $userId = $current_user ? (int)$current_user['id'] : null;

        $stmt = mysqli_prepare($conn, "INSERT INTO contact_queries (user_id, name, email, subject, message, status) VALUES (?, ?, ?, ?, ?, 'new')");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "issss", $userId, $contact_name, $contact_email, $contact_subject, $contact_message);
            if (mysqli_stmt_execute($stmt)) {
                setFlash('success', 'Your query has been submitted successfully. Our team will review it shortly.');
            } else {
                setFlash('error', 'Could not submit your inquiry. Please try again later.');
            }
            mysqli_stmt_close($stmt);
        } else {
            setFlash('error', 'Database error occurred. Please try again.');
        }

        // Post-Redirect-Get pattern to prevent duplicate submissions on page refresh
        header('Location: ' . BASE_URL . '/contact.php');
        exit();
    } else {
        setFlash('error', implode('<br>', $errors));
    }
}

// Fetch user's previous inquiries if logged in
$user_recent_queries = [];
if ($current_user) {
    $c_conn = getDbConnection();
    $c_uid = (int)$current_user['id'];
    $c_email = $current_user['email'];
    $c_stmt = mysqli_prepare($c_conn, "SELECT * FROM contact_queries WHERE user_id = ? OR email = ? ORDER BY id DESC LIMIT 5");
    if ($c_stmt) {
        mysqli_stmt_bind_param($c_stmt, "is", $c_uid, $c_email);
        mysqli_stmt_execute($c_stmt);
        $user_recent_queries = mysqli_fetch_all(mysqli_stmt_get_result($c_stmt), MYSQLI_ASSOC);
        mysqli_stmt_close($c_stmt);
    }
}

$page_title = "Contact Support | Lapify";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="text-center mb-4">
                <span class="badge bg-primary-subtle text-primary font-weight-bold px-3 py-1.5 rounded-pill mb-2">Help & Support</span>
                <h2 class="fw-bold">Get In Touch With Lapify</h2>
                <p class="text-muted">Have a question or feedback? Fill out the form below to contact our support team.</p>
            </div>

            <?php if ($current_user && !empty($user_recent_queries)): ?>
                <div class="alert alert-info border-0 rounded-4 p-3 d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-2 mb-4 shadow-sm">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-chat-square-text-fill fs-5 text-primary"></i>
                        <span class="fw-semibold">You have previous support inquiries with responses.</span>
                    </div>
                    <a href="my-queries.php" class="btn btn-sm btn-primary rounded-pill px-3 fw-bold">
                        View My Inquiries (<?= count($user_recent_queries) ?>) <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
            <?php endif; ?>

            <?php displayFlash(); ?>

            <div class="card border-0 shadow-lg rounded-4 p-4 p-md-5 mb-5">
                <form action="contact.php" method="POST" class="needs-validation" novalidate>
                    <?= renderCsrfInput() ?>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label font-weight-bold">Your Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name" class="form-control" value="<?= escape($contact_name) ?>" placeholder="e.g. John Doe" required>
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label font-weight-bold">Your Email Address <span class="text-danger">*</span></label>
                            <input type="email" name="email" id="email" class="form-control" value="<?= escape($contact_email) ?>" placeholder="e.g. john@example.com" required>
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

                    <button type="submit" class="btn btn-primary px-5 py-2.5 rounded-3 font-weight-bold shadow-sm">
                        <i class="bi bi-send-fill me-2"></i>Send Support Inquiry
                    </button>
                </form>
            </div>

            <!-- Recent Inquiries & Responses from Admin -->
            <?php if (!empty($user_recent_queries)): ?>
                <div class="ticket-card p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                        <h5 class="ticket-title mb-0"><i class="bi bi-clock-history text-primary me-2"></i>Your Recent Inquiries</h5>
                        <a href="my-queries.php" class="btn-lapify-view-more"><span>View All Inquiries</span> <i class="bi bi-arrow-right"></i></a>
                    </div>
                    <div class="d-flex flex-column gap-3">
                        <?php foreach ($user_recent_queries as $uq): 
                            $has_reply = !empty($uq['admin_reply']);
                        ?>
                            <div class="p-3.5 rounded-4 border bg-light shadow-sm">
                                <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="ticket-tag" style="font-size: 10px; padding: 0.2rem 0.5rem;">#<?= (int)$uq['id'] ?></span>
                                        <span class="fw-bold text-dark"><?= escape(!empty($uq['subject']) ? $uq['subject'] : '(General Inquiry)') ?></span>
                                    </div>
                                    <?php if ($has_reply): ?>
                                        <span class="ticket-status-pill status-resolved" style="font-size: 11px; padding: 0.2rem 0.6rem;">
                                            <span class="status-dot status-dot-green"></span>
                                            <span>Replied by Support</span>
                                        </span>
                                    <?php else: ?>
                                        <span class="ticket-status-pill status-pending" style="font-size: 11px; padding: 0.2rem 0.6rem;">
                                            <span class="status-dot status-dot-amber"></span>
                                            <span>Under Review</span>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="small text-muted mb-2 text-truncate" style="max-width: 100%;"><i class="bi bi-person-fill text-primary me-1"></i><?= escape(trim($uq['message'])) ?></div>
                                <?php if ($has_reply): ?>
                                    <div class="chat-bubble-support mt-2 p-3" style="font-size: 0.9rem;">
                                        <strong class="text-success d-block mb-1"><i class="bi bi-patch-check-fill me-1"></i>Support Team Reply:</strong>
                                        <?= nl2br(escape(trim($uq['admin_reply']))) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
