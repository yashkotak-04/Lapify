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
  // Delete Confirm Modal
  // ==========================================
  const deleteModal = document.getElementById('deleteConfirmModal');
  if (deleteModal) {
    deleteModal.addEventListener('show.bs.modal', function (event) {
      const button = event.relatedTarget;
      const itemTitle = button.getAttribute('data-title') || 'item';
      const deleteUrl = button.getAttribute('data-delete-url');

      const modalTitle = deleteModal.querySelector('.modal-item-title');
      const confirmBtn = deleteModal.querySelector('.btn-confirm-delete');

      if (modalTitle) modalTitle.textContent = itemTitle;
      if (confirmBtn && deleteUrl) {
        confirmBtn.setAttribute('href', deleteUrl);
      }
    });
  }

  // ==========================================
  // Edit User Modal
  // ==========================================
  const editUserModal = document.getElementById('editUserModal');
  if (editUserModal) {
    editUserModal.addEventListener('show.bs.modal', function (event) {
      const button = event.relatedTarget;
      document.getElementById('edit_user_id').value = button.getAttribute('data-user-id') || '';
      document.getElementById('edit_full_name').value = button.getAttribute('data-user-name') || '';
      document.getElementById('edit_email').value = button.getAttribute('data-user-email') || '';
      document.getElementById('edit_phone').value = button.getAttribute('data-user-phone') || '';
      const role = button.getAttribute('data-user-role') || 'user';
      document.getElementById('edit_is_admin').checked = role === 'admin';
      document.getElementById('edit_password').value = '';
    });
  }

  // ==========================================
  // Edit Brand Modal
  // ==========================================
  const editBrandModal = document.getElementById('editBrandModal');
  if (editBrandModal) {
    editBrandModal.addEventListener('show.bs.modal', function (event) {
      const button = event.relatedTarget;
      document.getElementById('edit_brand_id').value = button.getAttribute('data-brand-id') || '';
      document.getElementById('edit_brand_name').value = button.getAttribute('data-brand-name') || '';
    });
  }

  // ==========================================
  // Edit Laptop Modal
  // ==========================================
  const editLaptopModal = document.getElementById('editLaptopModal');
  if (editLaptopModal) {
    editLaptopModal.addEventListener('show.bs.modal', function (event) {
      const button = event.relatedTarget;
      document.getElementById('edit_laptop_id').value = button.getAttribute('data-laptop-id') || '';
      document.getElementById('edit_brand_id').value = button.getAttribute('data-brand-id') || '';
      document.getElementById('edit_type').value = button.getAttribute('data-type') || 'New';
      document.getElementById('edit_model').value = button.getAttribute('data-model') || '';
      document.getElementById('edit_processor').value = button.getAttribute('data-processor') || '';
      document.getElementById('edit_ram').value = button.getAttribute('data-ram') || '';
      document.getElementById('edit_storage').value = button.getAttribute('data-storage') || '';
      document.getElementById('edit_condition').value = button.getAttribute('data-condition') || '';
      document.getElementById('edit_price').value = button.getAttribute('data-price') || '';
      document.getElementById('edit_description').value = button.getAttribute('data-description') || '';
      document.getElementById('edit_quantity').value = button.getAttribute('data-quantity') || '1';
      document.getElementById('edit_image').value = '';
    });
  }
});
