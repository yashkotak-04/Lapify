/**
 * Lapify Premium Page Transition System
 * Centralized navigation interception, loader overlay, and non-blocking form submit states.
 * Preserves all POST submitter names/values and works seamlessly across Light and Dark modes.
 */
(function () {
  'use strict';

  const OVERLAY_ID = 'page-transition-overlay';
  const LOADER_TEXT_ID = 'lapify-loader-text';
  const TRANSITION_MS = 300;
  const MAX_LOADER_MS = 5000; // safety: never leave loader stuck

  let overlayEl = null;
  let loaderTextEl = null;
  let transitionTimer = null;
  let isTransitioning = false;

  // ------------------------------------------------------------------
  // 1. Build the overlay + loader DOM (injected once, reused everywhere)
  // ------------------------------------------------------------------
  function ensureOverlay() {
    if (overlayEl) return overlayEl;

    overlayEl = document.getElementById(OVERLAY_ID);
    if (!overlayEl) {
      overlayEl = document.createElement('div');
      overlayEl.id = OVERLAY_ID;
      overlayEl.setAttribute('aria-hidden', 'true');
      overlayEl.innerHTML =
        '<div class="lapify-loader" role="status" aria-live="polite">' +
          '<div class="lapify-spinner"></div>' +
          '<div class="lapify-loader-text" id="' + LOADER_TEXT_ID + '">Loading…</div>' +
        '</div>';
      document.body.appendChild(overlayEl);
    }
    loaderTextEl = document.getElementById(LOADER_TEXT_ID);
    return overlayEl;
  }

  // ------------------------------------------------------------------
  // 2. Show / hide the loader overlay
  // ------------------------------------------------------------------
  function showLoader(text) {
    ensureOverlay();
    if (loaderTextEl && text) loaderTextEl.textContent = text;
    if (overlayEl) {
      overlayEl.classList.add('active');
      overlayEl.setAttribute('aria-hidden', 'false');
    }
    isTransitioning = true;

    clearTimeout(transitionTimer);
    transitionTimer = setTimeout(hideLoader, MAX_LOADER_MS);
  }

  function hideLoader() {
    if (!overlayEl) return;
    overlayEl.classList.remove('active');
    overlayEl.setAttribute('aria-hidden', 'true');
    isTransitioning = false;
    clearTimeout(transitionTimer);
  }

  function isSameOrigin(url) {
    try {
      const a = document.createElement('a');
      a.href = url;
      return a.origin === window.location.origin;
    } catch (e) {
      return false;
    }
  }

  // ------------------------------------------------------------------
  // 3. Form submissions handler (Non-blocking & Submit-Name preserving)
  // ------------------------------------------------------------------
  document.addEventListener('submit', function (e) {
    const form = e.target;
    if (!form || form.tagName !== 'FORM') return;
    if (form.getAttribute('data-no-transition') !== null) return;

    // Skip AJAX / fetch handled forms
    if (form.id === 'sell-form' || form.getAttribute('data-ajax') === '1') return;

    // Double submit protection at form level
    if (form.dataset.submitting === 'true') {
      e.preventDefault();
      return;
    }

    // Mark form as submitting to prevent rapid duplicate clicks
    form.dataset.submitting = 'true';

    // Determine the actual submit button that triggered this submission
    const submitBtn = e.submitter || form.querySelector('button[type="submit"]:focus') || form.querySelector('button[type="submit"], .auth-btn');
    const label = (submitBtn && submitBtn.getAttribute('data-loading-text')) || 'Processing…';

    if (submitBtn) {
      submitBtn.setAttribute('aria-busy', 'true');
      submitBtn.classList.add('loading');
      // DO NOT set submitBtn.disabled = true; doing so removes the button's name & value from $_POST
      if (!submitBtn.dataset.originalHtml) {
        submitBtn.dataset.originalHtml = submitBtn.innerHTML;
      }
      submitBtn.innerHTML = '<span class="btn-spinner"></span> ' + label;
    }

    // If form contains files, show loader without blocking standard multipart submit
    const hasFile = form.querySelector('input[type="file"]');
    if (hasFile) {
      showLoader('Submitting…');
      return;
    }

    // Show smooth overlay
    showLoader(label);
  }, false);

  // ------------------------------------------------------------------
  // 4. Restore buttons & state on pageshow / load (handles back button)
  // ------------------------------------------------------------------
  function restoreFormButtons() {
    hideLoader();
    document.querySelectorAll('form[data-submitting="true"]').forEach(function (form) {
      form.dataset.submitting = 'false';
    });
    document.querySelectorAll('button.loading, .auth-btn.loading').forEach(function (btn) {
      btn.removeAttribute('aria-busy');
      btn.classList.remove('loading');
      if (btn.dataset.originalHtml) {
        btn.innerHTML = btn.dataset.originalHtml;
      }
    });
  }

  window.addEventListener('pageshow', function (e) {
    restoreFormButtons();
  });

  window.addEventListener('load', function () {
    hideLoader();
  });

  document.addEventListener('DOMContentLoaded', function () {
    ensureOverlay();
    setTimeout(hideLoader, 50);
  });

  // ------------------------------------------------------------------
  // 5. Expose global API
  // ------------------------------------------------------------------
  window.LapifyTransition = {
    show: showLoader,
    hide: hideLoader,
    restoreButtons: restoreFormButtons,
    isTransitioning: function () { return isTransitioning; }
  };
})();