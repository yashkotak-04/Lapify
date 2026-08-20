/**
 * Lapify Checkout JavaScript
 * Stepper animations, quantity steppers, promo code, live totals,
 * card mockup, payment validation, and confirm page animations.
 */

(function () {
  'use strict';

  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* ---------- Toast helper (reuse global if available) ---------- */
  function toast(message, type) {
    if (typeof window.showToast === 'function') {
      window.showToast(message, type, 3500);
    }
  }

  /* ---------- Format currency (INR) ---------- */
  function formatINR(value) {
    return new Intl.NumberFormat('en-IN', {
      style: 'currency',
      currency: 'INR',
      maximumFractionDigits: 2,
    }).format(value);
  }

  /* ---------- Animate a number count-up/down ---------- */
  function animateValue(el, from, to, duration = 400) {
    if (!el) return;
    if (prefersReducedMotion) {
      el.textContent = formatINR(to);
      return;
    }
    const start = performance.now();
    const diff = to - from;
    function tick(now) {
      const progress = Math.min(1, (now - start) / duration);
      const eased = 1 - Math.pow(1 - progress, 3); // ease-out cubic
      el.textContent = formatINR(from + diff * eased);
      if (progress < 1) requestAnimationFrame(tick);
    }
    requestAnimationFrame(tick);
  }

  /* ---------- Quantity stepper ---------- */
  function initQuantitySteppers() {
    document.addEventListener('click', function (e) {
      const minusBtn = e.target.closest('.btn-qty-minus, .qty-minus, [data-qty-action="minus"]');
      const plusBtn = e.target.closest('.btn-qty-plus, .qty-plus, [data-qty-action="plus"]');

      if (minusBtn) {
        e.preventDefault();
        const stepper = minusBtn.closest('.checkout-qty');
        if (!stepper) return;
        const input = stepper.querySelector('input[type="number"]');
        if (!input) return;

        const min = parseInt(input.getAttribute('min') || '1', 10);
        const max = parseInt(input.getAttribute('max') || '99', 10);
        const current = parseInt(input.value, 10);
        const currentVal = isNaN(current) ? min : current;

        if (currentVal <= min) {
          toast('Minimum quantity is ' + min, 'info');
          return;
        }

        const newVal = Math.max(min, currentVal - 1);
        input.value = newVal;
        input.classList.remove('qty-pulse');
        void input.offsetWidth;
        input.classList.add('qty-pulse');
        updateTotals();
      }

      if (plusBtn) {
        e.preventDefault();
        const stepper = plusBtn.closest('.checkout-qty');
        if (!stepper) return;
        const input = stepper.querySelector('input[type="number"]');
        if (!input) return;

        const min = parseInt(input.getAttribute('min') || '1', 10);
        const max = parseInt(input.getAttribute('max') || '99', 10);
        const current = parseInt(input.value, 10);
        const currentVal = isNaN(current) ? min : current;

        if (currentVal >= max) {
          toast('Only ' + max + ' unit' + (max > 1 ? 's' : '') + ' available in stock for this laptop.', 'warning');
          return;
        }

        const newVal = Math.min(max, currentVal + 1);
        input.value = newVal;
        input.classList.remove('qty-pulse');
        void input.offsetWidth;
        input.classList.add('qty-pulse');
        updateTotals();
      }
    });

    document.querySelectorAll('.checkout-qty input[type="number"]').forEach(function (input) {
      const min = parseInt(input.getAttribute('min') || '1', 10);
      const max = parseInt(input.getAttribute('max') || '99', 10);

      input.addEventListener('input', function () {
        const current = parseInt(input.value, 10);
        if (!isNaN(current)) {
          updateTotals();
        }
      });

      input.addEventListener('change', function () {
        const current = parseInt(input.value, 10);
        const val = Math.max(min, Math.min(max, isNaN(current) ? min : current));
        input.value = val;
        updateTotals();
      });
    });
  }

  /* ---------- Live totals (cart & shipping pages) ---------- */
  function updateTotals() {
    const cartItems = document.querySelectorAll('.checkout-item');
    const subtotalEl = document.getElementById('checkout-subtotal');
    const discountEl = document.getElementById('checkout-discount');
    const shippingEl = document.getElementById('checkout-shipping');
    const totalEl = document.getElementById('checkout-total');

    let subtotal = 0;
    if (cartItems && cartItems.length > 0) {
      cartItems.forEach(function (item) {
        const price = parseFloat(item.getAttribute('data-price') || '0');
        const qtyInput = item.querySelector('.checkout-qty input[type="number"]');
        const qty = qtyInput ? (parseInt(qtyInput.value, 10) || 1) : 1;
        subtotal += price * qty;

        // Update individual item total price displayed in cart
        const itemPriceEl = item.querySelector('.checkout-item-price');
        if (itemPriceEl) {
          itemPriceEl.textContent = formatINR(price * qty);
        }

        // Update summary sidebar item quantity and line price
        const itemId = item.getAttribute('data-id');
        if (itemId) {
          const summaryItem = document.querySelector(`.checkout-summary-item[data-id="${itemId}"]`);
          if (summaryItem) {
            const qtyEl = summaryItem.querySelector('.item-qty');
            const lineEl = summaryItem.querySelector('.item-line');
            if (qtyEl) qtyEl.textContent = '× ' + qty;
            if (lineEl) lineEl.textContent = formatINR(price * qty);
          }
        }
      });

      if (subtotalEl) {
        animateValue(subtotalEl, parseFloat(subtotalEl.dataset.lastValue || '0'), subtotal);
        subtotalEl.dataset.lastValue = subtotal;
      }
    } else if (subtotalEl) {
      subtotal = parseFloat(subtotalEl.dataset.lastValue || subtotalEl.getAttribute('data-last-value') || '0');
    }

    if (!subtotalEl && !totalEl) return;

    const discount = parseFloat(subtotalEl ? (subtotalEl.getAttribute('data-discount') || '0') : '0');
    const shipping = parseFloat(shippingEl ? (shippingEl.getAttribute('data-shipping') || '0') : '0');
    const total = Math.max(0, subtotal - discount + shipping);

    if (discountEl) {
      discountEl.textContent = discount > 0 ? '-' + formatINR(discount) : formatINR(0);
    }
    if (shippingEl) {
      shippingEl.textContent = shipping > 0 ? formatINR(shipping) : 'Free';
    }
    if (totalEl) {
      animateValue(totalEl, parseFloat(totalEl.dataset.lastValue || '0'), total);
      totalEl.dataset.lastValue = total;
    }

    // Update the Pay button amount if present
    const payBtn = document.getElementById('pay-amount');
    if (payBtn) payBtn.textContent = formatINR(total);
  }

  /* ---------- Promo code with Smooth Animation ---------- */
  function initPromoCode() {
    const applyBtn = document.getElementById('apply-promo');
    const promoInput = document.getElementById('promo-code');
    const promoWrap = document.querySelector('.checkout-promo');
    if (!applyBtn || !promoInput) return;

    // Trigger confetti & lock input on load if page reloads with active promo code
    if (promoWrap && promoWrap.classList.contains('promo-success-glow')) {
      promoInput.disabled = true;
      promoInput.readOnly = true;
      applyBtn.disabled = true;
      if (typeof window.triggerPurchaseConfetti === 'function') {
        setTimeout(window.triggerPurchaseConfetti, 250);
      }
    }

    applyBtn.addEventListener('click', function (e) {
      e.preventDefault();
      if (applyBtn.disabled) return;

      const code = promoInput.value.trim();
      if (!code) {
        toast('Please enter a promo code.', 'warning');
        promoInput.focus();
        return;
      }

      const originalBtnContent = applyBtn.innerHTML;
      applyBtn.disabled = true;
      applyBtn.classList.add('btn-apply-loading');
      applyBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span>Applying...';

      const validCodes = {
        LAPIFY50: { type: 'percent', value: 50, cap: 10000 },
        LAPIFY10: { type: 'percent', value: 10, cap: 5000 },
        SAVE500: { type: 'flat', value: 500, cap: null },
      };
      const normalized = code.toUpperCase();
      const rule = validCodes[normalized];

      setTimeout(function () {
        applyBtn.classList.remove('btn-apply-loading');

        if (!rule) {
          applyBtn.disabled = false;
          applyBtn.innerHTML = originalBtnContent;
          toast('Invalid promo code. Try LAPIFY50', 'error');
          promoInput.classList.add('is-invalid');
          setTimeout(function () { promoInput.classList.remove('is-invalid'); }, 1500);
          return;
        }

        let subtotal = 0;
        document.querySelectorAll('.checkout-item').forEach(function (item) {
          const price = parseFloat(item.getAttribute('data-price') || '0');
          const qtyInput = item.querySelector('.checkout-qty input[type="number"]');
          const qty = qtyInput ? (parseInt(qtyInput.value, 10) || 1) : 1;
          subtotal += price * qty;
        });

        let discount = 0;
        if (rule.type === 'percent') {
          discount = subtotal * (rule.value / 100);
          if (rule.cap !== null) discount = Math.min(discount, rule.cap);
        } else {
          discount = rule.value;
        }
        discount = Math.min(discount, subtotal);

        // Apply Success Button & Input Disabled state
        applyBtn.classList.add('btn-apply-success');
        applyBtn.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> Applied';
        applyBtn.disabled = true;

        promoInput.value = normalized;
        promoInput.disabled = true;
        promoInput.readOnly = true;

        // Glow effect on promo container
        if (promoWrap) {
          promoWrap.classList.add('promo-success-glow');
        }

        // Set discount on subtotal element
        const subtotalEl = document.getElementById('checkout-subtotal');
        if (subtotalEl) {
          subtotalEl.setAttribute('data-discount', discount);
        }

        // Live recount totals with smooth count-down
        updateTotals();

        // Create / animate green success banner card
        let successCard = document.getElementById('coupon-success-banner');
        if (!successCard) {
          successCard = document.createElement('div');
          successCard.id = 'coupon-success-banner';
          successCard.className = 'coupon-success-card';
          const promoParent = promoWrap ? promoWrap.parentNode : null;
          if (promoParent) {
            const hint = promoParent.querySelector('.checkout-promo-hint');
            if (hint) {
              promoParent.insertBefore(successCard, hint);
            } else {
              promoParent.appendChild(successCard);
            }
          }
        }

        successCard.innerHTML = `
          <div class="coupon-success-badge">
            <i class="bi bi-patch-check-fill"></i>
            <span>Coupon <strong>${normalized}</strong> Applied!</span>
          </div>
          <div class="coupon-saved-text">Saved ${formatINR(discount)}</div>
        `;

        // Highlight discount row in summary sidebar
        const discountEl = document.getElementById('checkout-discount');
        if (discountEl && discountEl.parentNode) {
          discountEl.parentNode.classList.add('discount-row-highlight');
        }

        // Confetti celebration
        if (typeof window.triggerPurchaseConfetti === 'function') {
          window.triggerPurchaseConfetti();
        } else if (typeof confetti === 'function') {
          confetti({ particleCount: 140, spread: 75, origin: { y: 0.6 } });
        }

        toast('🎉 Coupon "' + normalized + '" applied successfully! Saved ' + formatINR(discount) + '.', 'success');

        // Persist session to PHP via Fetch API
        const hidden = document.getElementById('promo-code-hidden');
        if (hidden) hidden.value = normalized;

        const formData = new FormData();
        formData.append('apply_promo', '1');
        formData.append('promo_code', normalized);
        document.querySelectorAll('.checkout-item').forEach(function (item) {
          const itemId = item.getAttribute('data-id');
          const qtyInput = item.querySelector('.checkout-qty input[type="number"]');
          if (itemId && qtyInput) {
            formData.append('quantity[' + itemId + ']', qtyInput.value);
          }
        });
        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        const csrfInput = document.querySelector('input[name="csrf_token"]');
        const csrfVal = csrfMeta ? csrfMeta.getAttribute('content') : (csrfInput ? csrfInput.value : '');
        if (csrfVal) formData.append('csrf_token', csrfVal);

        fetch('checkout_cart.php', {
          method: 'POST',
          body: formData,
          headers: { 
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-Token': csrfVal
          }
        }).catch(function () {});

      }, 450);
    });
  }

  /* ---------- Shipping method selection ---------- */
  function initShippingMethods() {
    const methods = document.querySelectorAll('.shipping-method');
    if (!methods.length) return;

    function selectMethod(method) {
      const radio = method.querySelector('input[type="radio"]');
      if (!radio) return;

      radio.checked = true;
      methods.forEach(function (m) {
        m.classList.toggle('selected', m === method);
      });

      const shipping = parseFloat(radio.getAttribute('data-cost') || '0');
      const shippingEl = document.getElementById('checkout-shipping');
      if (shippingEl) {
        shippingEl.setAttribute('data-shipping', shipping);
        shippingEl.textContent = shipping > 0 ? formatINR(shipping) : 'Free';
      }
      updateTotals();
    }

    methods.forEach(function (method) {
      const radio = method.querySelector('input[type="radio"]');
      if (!radio) return;

      method.addEventListener('click', function (e) {
        selectMethod(method);
      });

      radio.addEventListener('change', function () {
        selectMethod(method);
      });
    });
  }

  /* ---------- Shipping Form Validation & Submit Helper ---------- */
  function initShippingForm() {
    const form = document.getElementById('shipping-form');
    if (!form) return;

    form.addEventListener('submit', function (e) {
      const firstName = form.querySelector('#first_name');
      const lastName = form.querySelector('#last_name');
      const address = form.querySelector('#address');
      const city = form.querySelector('#city');
      const state = form.querySelector('#state');
      const zip = form.querySelector('#zip');
      const phone = form.querySelector('#phone');

      let firstInvalid = null;

      function checkField(el, condition, errorMsg) {
        if (!el) return true;
        if (condition) {
          el.classList.remove('is-invalid');
          return true;
        } else {
          el.classList.add('is-invalid');
          if (!firstInvalid) {
            firstInvalid = el;
            toast(errorMsg, 'warning');
          }
          return false;
        }
      }

      const validFirst = checkField(firstName, firstName && firstName.value.trim() !== '', 'Please enter your first name.');
      const validLast = checkField(lastName, lastName && lastName.value.trim() !== '', 'Please enter your last name.');
      const validAddr = checkField(address, address && address.value.trim() !== '', 'Please enter your delivery street address.');
      const validCity = checkField(city, city && city.value.trim() !== '', 'Please enter your city.');
      const validState = checkField(state, state && state.value.trim() !== '', 'Please enter your state.');
      const validZip = checkField(zip, zip && /^\d{5,6}$/.test(zip.value.replace(/\D/g, '')), 'Please enter a valid 6-digit PIN code.');
      const validPhone = checkField(phone, phone && phone.value.replace(/\D/g, '').length >= 10, 'Please enter a valid 10-digit phone number.');

      if (!validFirst || !validLast || !validAddr || !validCity || !validState || !validZip || !validPhone) {
        e.preventDefault();
        e.stopPropagation();
        if (firstInvalid) {
          firstInvalid.focus();
          firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        form.dataset.submitting = 'false';
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
          submitBtn.removeAttribute('aria-busy');
          submitBtn.classList.remove('loading');
          if (submitBtn.dataset.originalHtml) {
            submitBtn.innerHTML = submitBtn.dataset.originalHtml;
          }
        }
        return false;
      }
    });

    // Remove is-invalid on input
    form.querySelectorAll('input').forEach(function (input) {
      input.addEventListener('input', function () {
        this.classList.remove('is-invalid');
      });
    });
  }

  /* ---------- Confirm page checkmark ---------- */
  function initConfirmPage() {
    const check = document.querySelector('.confirm-check');
    if (check && !prefersReducedMotion) {
      // The CSS animations handle the draw-in automatically.
    }
  }

  /* ---------- Init on DOM ready ---------- */
  function initAll() {
    initQuantitySteppers();
    initPromoCode();
    initShippingMethods();
    initShippingForm();
    initConfirmPage();
    updateTotals();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAll);
  } else {
    initAll();
  }
})();