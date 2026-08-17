<?php
// admin/topbar.php - Admin Top Navigation Bar with Hamburger Menu Toggle
$current_admin = getCurrentUser();
?>
<div class="admin-top-nav d-flex align-items-center justify-content-between mb-4 pb-3 border-bottom" style="border-color: rgba(56, 189, 248, 0.2) !important;">
    <div class="d-flex align-items-center gap-3">
        <button type="button" class="btn btn-light border rounded-3 shadow-sm d-flex align-items-center justify-content-center admin-sidebar-toggle-btn" id="adminSidebarToggleBtn" title="Open Navigation Menu" style="width: 44px; height: 44px; background: rgba(255, 255, 255, 0.95); border-color: rgba(56, 189, 248, 0.4) !important; cursor: pointer;">
            <i class="bi bi-list fs-3 text-primary"></i>
        </button>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-primary rounded-pill px-2.5 py-1 text-uppercase fw-bold shadow-sm" style="font-size: 0.68rem; letter-spacing: 0.05em;">Admin Portal</span>
            <span class="fw-bold fs-5 text-dark d-none d-sm-inline">Lapify Command Center</span>
        </div>
    </div>

    <div class="d-flex align-items-center gap-2">
        <a href="<?= BASE_URL ?>/admin/profile.php" class="btn btn-sm btn-light border rounded-pill px-3 py-1.5 d-inline-flex align-items-center gap-2 shadow-sm text-dark" style="border-color: rgba(56, 189, 248, 0.3) !important;">
            <i class="bi bi-person-circle text-primary fs-6"></i> <span class="d-none d-md-inline fw-semibold"><?= escape($current_admin['full_name'] ?? 'Admin') ?></span>
        </a>
    </div>
</div>
