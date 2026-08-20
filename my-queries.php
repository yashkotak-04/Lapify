<?php
// my-queries.php - User Support Inquiries & Admin Responses
$page_title = "My Support Inquiries | Lapify";
require_once __DIR__ . '/includes/auth.php';
requireLogin();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

$conn = getDbConnection();
$current_user = getCurrentUser();
$user_id = (int)$current_user['id'];
$user_email = $current_user['email'];

// Fetch all queries for this user
$stmt = mysqli_prepare($conn, "SELECT * FROM contact_queries WHERE user_id = ? OR email = ? ORDER BY id DESC");
mysqli_stmt_bind_param($stmt, "is", $user_id, $user_email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$my_queries = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

// Counts
$total_inquiries = count($my_queries);
$replied_count = 0;
$pending_count = 0;

foreach ($my_queries as $q) {
    if (!empty($q['admin_reply'])) {
        $replied_count++;
    } else {
        $pending_count++;
    }
}
?>

<div class="container py-5">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb" class="mb-2">
                <ol class="breadcrumb small mb-0">
                    <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">My Inquiries</li>
                </ol>
            </nav>
            <h2 class="fw-bold mb-1"><i class="bi bi-chat-left-text text-primary me-2"></i>Support Inquiries & Replies</h2>
            <p class="text-muted mb-0">View all messages submitted to support and track direct responses from the Lapify team.</p>
        </div>
        <div>
            <a href="contact.php" class="btn btn-primary rounded-pill px-4 py-2 font-weight-bold shadow-sm">
                <i class="bi bi-plus-circle me-1.5"></i> Ask a New Question
            </a>
        </div>
    </div>

    <?php displayFlash(); ?>

    <!-- Stat Overview -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="small text-muted font-weight-bold text-uppercase">Total Inquiries</div>
                        <div class="fs-4 fw-bold text-dark mt-1"><?= (int)$total_inquiries ?></div>
                    </div>
                    <div class="rounded-circle bg-primary bg-opacity-10 text-primary p-3 d-flex align-items-center justify-content-center" style="width: 46px; height: 46px;">
                        <i class="bi bi-inbox fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="small text-muted font-weight-bold text-uppercase">Support Replied</div>
                        <div class="fs-4 fw-bold text-success mt-1"><?= (int)$replied_count ?></div>
                    </div>
                    <div class="rounded-circle bg-success bg-opacity-10 text-success p-3 d-flex align-items-center justify-content-center" style="width: 46px; height: 46px;">
                        <i class="bi bi-patch-check-fill fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="small text-muted font-weight-bold text-uppercase">Awaiting Reply</div>
                        <div class="fs-4 fw-bold text-warning mt-1"><?= (int)$pending_count ?></div>
                    </div>
                    <div class="rounded-circle bg-warning bg-opacity-10 text-warning p-3 d-flex align-items-center justify-content-center" style="width: 46px; height: 46px;">
                        <i class="bi bi-hourglass-split fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Inquiry Cards List -->
    <?php if (!empty($my_queries)): ?>
        <div class="d-flex flex-column gap-4">
            <?php foreach ($my_queries as $q): 
                $has_reply = !empty($q['admin_reply']);
                $clean_subject = !empty($q['subject']) ? $q['subject'] : '(General Inquiry)';
            ?>
                <div class="ticket-card animate-msg-appear">
                    <!-- Ticket Header -->
                    <div class="ticket-header">
                        <div class="d-flex align-items-center gap-2.5 flex-wrap">
                            <span class="ticket-tag">#TICKET-<?= (int)$q['id'] ?></span>
                            <h5 class="ticket-title mb-0"><?= escape($clean_subject) ?></h5>
                        </div>
                        <div class="d-flex align-items-center gap-3 flex-wrap">
                            <?php if ($has_reply): ?>
                                <div class="ticket-status-pill status-resolved">
                                    <span class="status-dot status-dot-green"></span>
                                    <span>Answered by Support</span>
                                </div>
                            <?php else: ?>
                                <div class="ticket-status-pill status-pending">
                                    <span class="status-dot status-dot-amber"></span>
                                    <span>Under Review</span>
                                </div>
                            <?php endif; ?>
                            <span class="ticket-date"><i class="bi bi-calendar3 me-1.5"></i><?= formatDate($q['created_at']) ?></span>
                        </div>
                    </div>

                    <!-- Conversation Thread -->
                    <div class="chat-thread-container">
                        <!-- 1. Customer Message -->
                        <div class="chat-item">
                            <div class="chat-avatar user-avatar-badge" title="<?= escape($current_user['full_name']) ?>">
                                <?= strtoupper(substr($current_user['full_name'] ?? 'U', 0, 1)) ?>
                            </div>
                            <div class="chat-content-wrap">
                                <div class="chat-meta">
                                    <div>
                                        <span class="chat-author-name"><?= escape($current_user['full_name']) ?></span>
                                        <span class="chat-role-badge">You</span>
                                    </div>
                                    <span class="chat-time"><i class="bi bi-clock me-1"></i><?= date('M d, Y • h:i A', strtotime($q['created_at'])) ?></span>
                                </div>
                                <div class="chat-bubble-user"><?= nl2br(escape(trim($q['message']))) ?></div>
                            </div>
                        </div>

                        <!-- 2. Support Response or Live Status -->
                        <?php if ($has_reply): ?>
                            <div class="chat-item animate-msg-appear">
                                <div class="chat-avatar support-avatar-badge" title="Lapify Customer Care">
                                    <i class="bi bi-headset"></i>
                                </div>
                                <div class="chat-content-wrap">
                                    <div class="chat-meta">
                                        <div class="d-flex align-items-center flex-wrap gap-1">
                                            <span class="chat-author-name text-success">Lapify Customer Support</span>
                                            <span class="badge bg-success text-white rounded-pill px-2 py-0.5 ms-1" style="font-size:10px;">
                                                <i class="bi bi-patch-check-fill me-1"></i>Official Response
                                            </span>
                                        </div>
                                        <?php if (!empty($q['replied_at'])): ?>
                                            <span class="chat-time"><i class="bi bi-clock me-1"></i><?= date('M d, Y • h:i A', strtotime($q['replied_at'])) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="chat-bubble-support"><?= nl2br(escape(trim($q['admin_reply']))) ?></div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="chat-item">
                                <div class="chat-avatar support-avatar-pulse" title="Support Representative">
                                    <i class="bi bi-headset"></i>
                                </div>
                                <div class="chat-content-wrap">
                                    <div class="chat-bubble-waiting">
                                        <div class="flex-grow-1">
                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                <strong class="text-dark font-weight-bold">Support Agent Assigned</strong>
                                                <div class="typing-dots">
                                                    <span></span><span></span><span></span>
                                                </div>
                                            </div>
                                            <div class="small opacity-85">
                                                Our customer support team is currently reviewing your inquiry. Your official response will appear right here as soon as an agent responds.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white">
            <div class="fs-1 text-muted mb-3"><i class="bi bi-chat-left-dots"></i></div>
            <h4 class="fw-bold text-dark mb-2">No Inquiries Found</h4>
            <p class="text-muted mb-4">You haven't submitted any support queries yet. Need assistance with a laptop or an order?</p>
            <div>
                <a href="contact.php" class="btn btn-primary rounded-pill px-4 py-2 font-weight-bold shadow-sm">
                    <i class="bi bi-send-fill me-1.5"></i> Contact Support Now
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
