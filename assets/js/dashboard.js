/**
 * Lapify Dashboard & Modal Event Handlers
 */

document.addEventListener('DOMContentLoaded', function () {
  // ==========================================
  // Admin Hamburger Sidebar Toggle (Closed by Default)
  // ==========================================
  const sidebar = document.getElementById('adminSidebar');
  const overlay = document.getElementById('adminSidebarOverlay');
  const toggleBtns = document.querySelectorAll('#adminSidebarToggleBtn, .admin-sidebar-toggle-btn');
  const closeBtns = document.querySelectorAll('#adminSidebarCloseBtn, .admin-sidebar-close-btn');

  function openAdminSidebar() {
    if (sidebar) sidebar.classList.add('show');
    if (overlay) overlay.classList.add('show');
    document.body.classList.add('admin-sidebar-open');
  }

  function closeAdminSidebar() {
    if (sidebar) sidebar.classList.remove('show');
    if (overlay) overlay.classList.remove('show');
    document.body.classList.remove('admin-sidebar-open');
  }

  function toggleAdminSidebar() {
    if (sidebar && sidebar.classList.contains('show')) {
      closeAdminSidebar();
    } else {
      openAdminSidebar();
    }
  }

  toggleBtns.forEach(btn => {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      toggleAdminSidebar();
    });
  });

  closeBtns.forEach(btn => {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      closeAdminSidebar();
    });
  });

  if (overlay) {
    overlay.addEventListener('click', function () {
      closeAdminSidebar();
    });
  }

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && sidebar && sidebar.classList.contains('show')) {
      closeAdminSidebar();
    }
  });

  // ==========================================
  // Global Modal Attachment (Prevents stacking context / black overlay bug)
  // ==========================================
  document.querySelectorAll('.modal').forEach(function (modal) {
    if (modal.parentElement && modal.parentElement !== document.body) {
      document.body.appendChild(modal);
    }
  });

  // ==========================================
  // Delete Confirm Modal
  // ==========================================
  const deleteModal = document.getElementById('deleteConfirmModal');
  if (deleteModal) {
    deleteModal.addEventListener('show.bs.modal', function (event) {
      const button = event.relatedTarget;
      if (!button) return;

      const itemTitle = button.getAttribute('data-title') || 'this item';
      const deleteUrl = button.getAttribute('data-delete-url') || button.getAttribute('href');

      const modalTitle = deleteModal.querySelector('.modal-item-title');
      const confirmBtn = deleteModal.querySelector('.btn-confirm-delete');

      if (modalTitle) modalTitle.textContent = itemTitle;
      if (confirmBtn && deleteUrl) {
        confirmBtn.setAttribute('href', deleteUrl);
      }
    });

    // Ensure backdrop cleanup on hide
    deleteModal.addEventListener('hidden.bs.modal', function () {
      document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
      document.body.classList.remove('modal-open');
      document.body.style.overflow = '';
      document.body.style.paddingRight = '';
    });
  }

  // ==========================================
  // Edit User Modal
  // ==========================================
  const editUserModal = document.getElementById('editUserModal');
  if (editUserModal) {
    editUserModal.addEventListener('show.bs.modal', function (event) {
      const button = event.relatedTarget;
      if (!button) return;

      const userIdEl = document.getElementById('edit_user_id');
      const fullNameEl = document.getElementById('edit_full_name');
      const emailEl = document.getElementById('edit_email');
      const phoneEl = document.getElementById('edit_phone');
      const isAdminEl = document.getElementById('edit_is_admin');
      const passwordEl = document.getElementById('edit_password');

      if (userIdEl) userIdEl.value = button.getAttribute('data-user-id') || '';
      if (fullNameEl) fullNameEl.value = button.getAttribute('data-user-name') || '';
      if (emailEl) emailEl.value = button.getAttribute('data-user-email') || '';
      if (phoneEl) phoneEl.value = button.getAttribute('data-user-phone') || '';
      const role = button.getAttribute('data-user-role') || 'user';
      if (isAdminEl) isAdminEl.checked = role === 'admin';
      if (passwordEl) passwordEl.value = '';
    });
  }

  // ==========================================
  // Edit Brand Modal
  // ==========================================
  const editBrandModal = document.getElementById('editBrandModal');
  if (editBrandModal) {
    editBrandModal.addEventListener('show.bs.modal', function (event) {
      const button = event.relatedTarget;
      if (!button) return;

      const brandIdEl = document.getElementById('edit_brand_id');
      const brandNameEl = document.getElementById('edit_brand_name');
      const brandLogoEl = document.getElementById('edit_brand_logo');

      if (brandIdEl) brandIdEl.value = button.getAttribute('data-brand-id') || '';
      if (brandNameEl) brandNameEl.value = button.getAttribute('data-brand-name') || '';
      if (brandLogoEl) brandLogoEl.value = '';
    });
  }

  // ==========================================
  // Edit Laptop Modal
  // ==========================================
  const editLaptopModal = document.getElementById('editLaptopModal');
  if (editLaptopModal) {
    editLaptopModal.addEventListener('show.bs.modal', function (event) {
      const button = event.relatedTarget;
      if (!button) return;

      const laptopIdEl = document.getElementById('edit_laptop_id');
      const brandIdEl = document.getElementById('edit_brand_id');
      const typeEl = document.getElementById('edit_type');
      const modelEl = document.getElementById('edit_model');
      const processorEl = document.getElementById('edit_processor');
      const ramEl = document.getElementById('edit_ram');
      const storageEl = document.getElementById('edit_storage');
      const conditionEl = document.getElementById('edit_condition');
      const priceEl = document.getElementById('edit_price');
      const descriptionEl = document.getElementById('edit_description');
      const quantityEl = document.getElementById('edit_quantity');
      const imageEl = document.getElementById('edit_image');

      if (laptopIdEl) laptopIdEl.value = button.getAttribute('data-laptop-id') || '';
      if (brandIdEl) brandIdEl.value = button.getAttribute('data-brand-id') || '';
      if (typeEl) typeEl.value = button.getAttribute('data-type') || 'New';
      if (modelEl) modelEl.value = button.getAttribute('data-model') || '';
      if (processorEl) processorEl.value = button.getAttribute('data-processor') || '';
      if (ramEl) ramEl.value = button.getAttribute('data-ram') || '';
      if (storageEl) storageEl.value = button.getAttribute('data-storage') || '';
      if (conditionEl) conditionEl.value = button.getAttribute('data-condition') || '';
      if (priceEl) priceEl.value = button.getAttribute('data-price') || '';
      if (descriptionEl) descriptionEl.value = button.getAttribute('data-description') || '';
      if (quantityEl) quantityEl.value = button.getAttribute('data-quantity') || '1';
      if (imageEl) imageEl.value = '';
    });
  }
});

