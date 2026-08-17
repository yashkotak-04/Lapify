/**
 * Lapify Main JavaScript - Interactivity & Wishlist AJAX
 */

function initCheckoutRedirect() {
  const checkoutOverlay = document.getElementById('checkout-success-overlay');
  const checkoutUrl = window.__lapifyCheckoutRedirect || (checkoutOverlay && checkoutOverlay.dataset.redirectUrl);
  if (!checkoutUrl) return;

  if (checkoutOverlay) {
    checkoutOverlay.classList.add('active');
  }

  if (typeof triggerCelebration === 'function') {
    triggerCelebration(window.__lapifyCelebrationMessage || '✅ Checkout completed successfully!');
  }

  setTimeout(() => {
    try {
      window.location.replace(checkoutUrl);
    } catch (e) {
      window.location.href = checkoutUrl;
    }
  }, 1800);
}

document.addEventListener('DOMContentLoaded', function () {
  initAutoDismissAlerts();
  initCheckoutRedirect();

  // Auth Success Modal auto-dismiss handler (global)
  const authSuccessModal = document.getElementById('auth-success-modal');
  if (authSuccessModal) {
    const dismissModal = () => {
      authSuccessModal.style.opacity = '0';
      authSuccessModal.style.transition = 'opacity 0.25s ease';
      authSuccessModal.style.pointerEvents = 'none';
      setTimeout(() => {
        if (authSuccessModal.parentNode) authSuccessModal.parentNode.removeChild(authSuccessModal);
      }, 250);
    };
    authSuccessModal.addEventListener('click', dismissModal);
    setTimeout(dismissModal, 850);
  }

  if (window.__lapifyCelebrationMessage && !window.__lapifyCheckoutRedirect) {
    triggerCelebration(window.__lapifyCelebrationMessage);
  }

  function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
  }

  // Wishlist AJAX Toggle Handler
  const wishlistButtons = document.querySelectorAll('.btn-wishlist-toggle');
  wishlistButtons.forEach(button => {
    if (button.disabled) return; // Skip disabled (e.g. seller's own listing)
    button.addEventListener('click', function (e) {
      e.preventDefault();
      const laptopId = this.getAttribute('data-laptop-id');
      const heartIcon = this.querySelector('i');
      
      if (!laptopId) return;

      const token = getCsrfToken();
      fetch('wishlist.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-Token': token
        },
        body: `action=toggle&laptop_id=${encodeURIComponent(laptopId)}&csrf_token=${encodeURIComponent(token)}`
      })
      .then(response => response.json())
      .then(data => {
        if (data.status === 'added') {
          this.classList.add('active');
          if (heartIcon) {
            heartIcon.classList.remove('bi-heart');
            heartIcon.classList.add('bi-heart-fill');
            heartIcon.style.color = '#ef4444';
          }
          if (typeof data.wishlist_count !== 'undefined') {
            updateWishlistBadge(data.wishlist_count);
          }
          showToast('Added to Wishlist!', 'success');
        } else if (data.status === 'removed') {
          this.classList.remove('active');
          if (heartIcon) {
            heartIcon.classList.remove('bi-heart-fill');
            heartIcon.classList.add('bi-heart');
            heartIcon.style.color = '';
          }
          if (typeof data.wishlist_count !== 'undefined') {
            updateWishlistBadge(data.wishlist_count);
          }
          showToast('Removed from Wishlist.', 'info');
          // If on wishlist page, remove the parent card
          const cardCol = this.closest('.wishlist-card-col');
          if (cardCol) {
            cardCol.remove();
          }
        } else if (data.status === 'unauthorized') {
          window.location.href = 'login.php';
        } else {
          showToast(data.message || 'Something went wrong.', 'warning');
        }
      })
      .catch(error => {
        console.error('Wishlist error:', error);
      });
    });
  });

  // Phone Number 10-Digit Enforcement JS Layer
  const phoneInputs = document.querySelectorAll('input[type="tel"], input[name="phone"]');
  phoneInputs.forEach(input => {
    input.addEventListener('input', function () {
      this.value = this.value.replace(/\D/g, '').slice(0, 10);
    });
  });

  // Cart AJAX Toggle Handler
  const cartButtons = document.querySelectorAll('.btn-cart-toggle');
  cartButtons.forEach(button => {
    if (button.disabled) return; // Skip disabled (e.g. seller's own listing)
    button.addEventListener('click', function (e) {
      e.preventDefault();
      const laptopId = this.getAttribute('data-laptop-id');
      if (!laptopId) return;

      const token = getCsrfToken();
      fetch('cart.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-Token': token
        },
        body: `action=toggle&laptop_id=${encodeURIComponent(laptopId)}&csrf_token=${encodeURIComponent(token)}`
      })
      .then(response => response.json())
      .then(data => {
        const isIconOnly = this.classList.contains('btn-product-icon') || this.classList.contains('btn-quick-icon') || this.getBoundingClientRect().width <= 56;

        if (data.status === 'added') {
          // Icon-only buttons: just switch the icon to checked
          if (isIconOnly) {
            this.classList.add('btn-success');
            this.classList.remove('btn-outline-primary', 'btn-soft-primary');
            this.innerHTML = '<i class="bi bi-cart-check-fill"></i>';
          } else {
            // Larger buttons: show a clear Remove action so next click removes
            this.classList.add('btn-outline-danger');
            this.classList.remove('btn-primary', 'btn-soft-primary');
            this.innerHTML = '<i class="bi bi-trash me-1"></i>Remove';
          }
          showToast(data.message || 'Added to Cart!', 'success');
          updateCartBadge(data.cart_count);

        } else if (data.status === 'removed') {
          if (isIconOnly) {
            this.classList.remove('btn-success');
            this.classList.add('btn-soft-primary');
            this.innerHTML = '<i class="bi bi-cart-plus"></i>';
          } else {
            this.classList.remove('btn-outline-danger');
            this.classList.add('btn-soft-primary');
            this.innerHTML = '<i class="bi bi-cart-plus me-1"></i>Add to Cart';
          }
          showToast(data.message || 'Removed from Cart.', 'info');
          updateCartBadge(data.cart_count);
          // If on cart page, remove row
          const cartRow = this.closest('.cart-item-row');
          if (cartRow) {
            cartRow.remove();
            if (data.cart_count === 0) {
              window.location.reload();
            }
          }

        } else if (data.status === 'unauthorized') {
          window.location.href = 'login.php';
        } else {
          showToast(data.message || 'Something went wrong.', 'warning');
        }
      })
      .catch(error => {
        console.error('Cart error:', error);
      });
    });
  });

  // Password Visibility Toggle (delegated, robust fallback)
  function handlePasswordToggleEvent(e) {
    // Walk up from the event target to find a toggle button element
    let el = e.target;
    while (el && el !== document) {
      if (el.nodeType === 1) {
        const cls = el.classList;
        if (cls && (cls.contains('toggle-password') || cls.contains('auth-visibility-toggle'))) break;
      }
      el = el.parentNode;
    }
    const btn = (el && el !== document) ? el : null;
    if (!btn) return;
    e.preventDefault();

    const targetId = btn.getAttribute && btn.getAttribute('data-target');
    let input = targetId ? document.getElementById(targetId) : null;

    if (!input) {
      const wrap = btn.closest && btn.closest('.auth-input-wrap');
      if (wrap) input = wrap.querySelector('input[type="password"], input[type="text"]');
    }

    if (!input) return;
    const isPassword = input.type === 'password';
    input.type = isPassword ? 'text' : 'password';

    const icon = btn.querySelector && btn.querySelector('i');
    if (icon) {
      icon.classList.toggle('bi-eye');
      icon.classList.toggle('bi-eye-slash');
    } else {
      btn.classList.toggle('bi-eye');
      btn.classList.toggle('bi-eye-slash');
    }
  }

  // Use event delegation so clicks aren't lost if elements move or overlays exist
  document.addEventListener('click', handlePasswordToggleEvent, false);
  document.addEventListener('touchstart', handlePasswordToggleEvent, { passive: false });

  // Expose a global fallback function so inline `onclick` can call it if needed
  window.togglePassword = function (btn) {
    try {
      // reuse logic from handler
      let input = null;
      const targetId = btn.getAttribute && btn.getAttribute('data-target');
      if (targetId) input = document.getElementById(targetId);
      if (!input) {
        const wrap = btn.closest && btn.closest('.auth-input-wrap');
        if (wrap) input = wrap.querySelector('input[type="password"], input[type="text"]');
      }
      if (!input) return;
      const isPassword = input.type === 'password';
      input.type = isPassword ? 'text' : 'password';
      const icon = btn.querySelector && btn.querySelector('i');
      if (icon) {
        icon.classList.toggle('bi-eye');
        icon.classList.toggle('bi-eye-slash');
      } else {
        btn.classList.toggle('bi-eye');
        btn.classList.toggle('bi-eye-slash');
      }
    } catch (e) {
      // silent fallback
    }
  };

  // Password strength validation: do not show the floating rules box.
  // Instead, apply `password-valid` class to the input when it passes all checks.
  const passwordInput = document.getElementById('password');
  if (passwordInput) {
    const checkPasswordRules = (val) => {
      const rules = {
        min_length: val.length >= 8,
        has_upper: /[A-Z]/.test(val),
        has_lower: /[a-z]/.test(val),
        has_number: /\d/.test(val),
        has_special: /[^A-Za-z0-9]/.test(val)
      };
      return Object.values(rules).every(Boolean);
    };

    passwordInput.addEventListener('input', function () {
      const val = this.value || '';
      const allGood = checkPasswordRules(val);
      if (allGood) {
        this.classList.add('password-valid');
        this.setAttribute('aria-invalid', 'false');
      } else {
        this.classList.remove('password-valid');
        this.setAttribute('aria-invalid', 'true');
      }
    });

    // initialize state
    const initialAllGood = checkPasswordRules(passwordInput.value || '');
    if (initialAllGood) passwordInput.classList.add('password-valid');
  }

  // Image Upload Live Preview
  // The invoice download link now opens the generated PDF in a new tab.
  // Remove any legacy print handler bound to `download-invoice-btn` to
  // avoid hijacking the normal download/navigation behavior.
  const invoiceDownloadBtn = document.getElementById('download-invoice-btn');
  if (invoiceDownloadBtn) {
    invoiceDownloadBtn.removeEventListener && invoiceDownloadBtn.removeEventListener('click', function () {});
  }

  const imageInputs = document.querySelectorAll('.image-preview-input');
  imageInputs.forEach(input => {
    input.addEventListener('change', function () {
      const previewId = this.getAttribute('data-preview-target');
      const previewImg = document.getElementById(previewId);
      if (previewImg && this.files && this.files[0]) {
        const reader = new FileReader();
        reader.onload = function (e) {
          previewImg.src = e.target.result;
          previewImg.classList.remove('d-none');
        };
        reader.readAsDataURL(this.files[0]);
      }
    });
  });
});

/**
 * Update Cart Badge Count in Navbar
 */
function updateCartBadge(count) {
  const badges = document.querySelectorAll('.cart-count-badge');
  badges.forEach(badge => {
    badge.textContent = count;
    if (count > 0) {
      badge.classList.remove('d-none');
    } else {
      badge.classList.add('d-none');
    }
  });
}

function updateWishlistBadge(count) {
  const badges = document.querySelectorAll('.wishlist-count-badge');
  badges.forEach(badge => {
    badge.textContent = count;
    if (count > 0) {
      badge.classList.remove('d-none');
    } else {
      badge.classList.add('d-none');
    }
  });
}


function triggerCelebration(message = '🎉 Order placed successfully!') {
  if (window.__lapifyCelebrationTriggered) return;
  window.__lapifyCelebrationTriggered = true;

  if (typeof confetti === 'function') {
    confetti({
      particleCount: 120,
      spread: 90,
      origin: { y: 0.2 },
      colors: ['#2563eb', '#f59e0b', '#22c55e', '#ef4444', '#ffffff']
    });
  }

  showToast(message, 'success');
}

/**
 * Helper to display dynamic lightweight toast feedback
 */
function initAutoDismissAlerts() {
  const alerts = document.querySelectorAll('.auth-alert, .alert, .flash-message');
  alerts.forEach(alertEl => {
    if (alertEl.dataset.stay === 'true') return;
    const delayMs = Number(alertEl.dataset.autoDismiss) || 4000;
    
    setTimeout(() => {
      if (!alertEl.isConnected) return;
      alertEl.style.transition = 'opacity 0.5s ease, transform 0.5s ease, max-height 0.5s ease, margin 0.5s ease, padding 0.5s ease';
      alertEl.style.opacity = '0';
      alertEl.style.transform = 'translateY(-10px)';
      
      setTimeout(() => {
        if (typeof bootstrap !== 'undefined' && bootstrap.Alert) {
          try {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alertEl);
            bsAlert.close();
            return;
          } catch (e) {}
        }
        if (alertEl.parentNode) {
          alertEl.parentNode.removeChild(alertEl);
        }
      }, 500);
    }, delayMs);
  });
}

function showToast(message, type = 'info') {
  if (typeof window.showToast === 'function' && window.showToast !== showToast) {
    window.showToast(message, type, 3500);
    return;
  }

  let toastContainer = document.getElementById('lapify-toast-container');
  if (!toastContainer) {
    toastContainer = document.createElement('div');
    toastContainer.id = 'lapify-toast-container';
    toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
    toastContainer.style.zIndex = '1090';
    document.body.appendChild(toastContainer);
  }

  const toastHtml = `
    <div class="toast align-items-center toast-theme border-0 show shadow" role="alert" aria-live="assertive" aria-atomic="true">
      <div class="d-flex">
        <div class="toast-body font-weight-medium">
          ${message}
        </div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
    </div>
  `;

  const div = document.createElement('div');
  div.innerHTML = toastHtml;
  const toastEl = div.firstElementChild;
  toastContainer.appendChild(toastEl);

  setTimeout(() => {
    toastEl.remove();
  }, 3500);
}
