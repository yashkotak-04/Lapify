/**
 * Lapify Premium Page Transition System
 * Centralized navigation interception, top progress bar, loader overlay, and non-blocking form submit states.
 * Preserves all POST submitter names/values and works seamlessly across Light and Dark modes.
 */
(function () {
  'use strict';

  const OVERLAY_ID = 'page-transition-overlay';
  const TOP_PROGRESS_ID = 'lapify-top-progress';
  const LOADER_TEXT_ID = 'lapify-loader-text';
  const MAX_LOADER_MS = 6000; // safety: never leave loader stuck

  let overlayEl = null;
  let topProgressEl = null;
  let loaderTextEl = null;
  let transitionTimer = null;
  let isTransitioning = false;

  // ------------------------------------------------------------------
  // 1. Top Progress Bar
  // ------------------------------------------------------------------
  function ensureTopProgress() {
    if (topProgressEl && document.body.contains(topProgressEl)) return topProgressEl;
    topProgressEl = document.getElementById(TOP_PROGRESS_ID);
    if (!topProgressEl) {
      topProgressEl = document.createElement('div');
      topProgressEl.id = TOP_PROGRESS_ID;
      document.body.appendChild(topProgressEl);
    }
    return topProgressEl;
  }

  function startTopProgress() {
    const el = ensureTopProgress();
    el.classList.remove('completed');
    el.classList.add('active');
    el.style.width = '0%';
    // Trigger smooth incremental progress
    requestAnimationFrame(function () {
      el.style.width = '25%';
      setTimeout(function () {
        if (el.classList.contains('active') && !el.classList.contains('completed')) {
          el.style.width = '70%';
        }
      }, 100);
      setTimeout(function () {
        if (el.classList.contains('active') && !el.classList.contains('completed')) {
          el.style.width = '85%';
        }
      }, 350);
    });
  }

  function finishTopProgress() {
    if (!topProgressEl) return;
    topProgressEl.style.width = '100%';
    topProgressEl.classList.add('completed');
    topProgressEl.classList.remove('active');
    setTimeout(function () {
      if (topProgressEl) {
        topProgressEl.style.width = '0%';
        topProgressEl.classList.remove('completed');
      }
    }, 450);
  }

  // ------------------------------------------------------------------
  // 2. Build the overlay + loader DOM
  // ------------------------------------------------------------------
  function ensureOverlay() {
    if (overlayEl && document.body.contains(overlayEl)) return overlayEl;

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

  function showLoader(text) {
    ensureOverlay();
    startTopProgress();
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
    if (overlayEl) {
      overlayEl.classList.remove('active');
      overlayEl.setAttribute('aria-hidden', 'true');
    }
    finishTopProgress();
    if (document.body) {
      document.body.classList.remove('lapify-page-leaving');
    }
    isTransitioning = false;
    clearTimeout(transitionTimer);
  }

  // ------------------------------------------------------------------
  // 3. Smooth Page-to-Page Navigation Interceptor (Links & Auth Tab Buttons)
  // ------------------------------------------------------------------
  // Intercept button.auth-tab clicks to provide smooth transition with loader
  document.addEventListener('click', function (e) {
    const authTabBtn = e.target.closest('button.auth-tab');
    if (!authTabBtn) return;

    const onclickStr = authTabBtn.getAttribute('onclick') || '';
    const match = onclickStr.match(/window\.location\.href\s*=\s*['"]([^'"]+)['"]/);
    if (match && match[1]) {
      const targetUrl = match[1];
      // Check if not already on this page
      if (window.location.pathname.endsWith(targetUrl) && !window.location.search) {
        e.preventDefault();
        e.stopImmediatePropagation();
        return;
      }
      e.preventDefault();
      e.stopImmediatePropagation();
      showLoader('Loading…');
      document.body.classList.add('lapify-page-leaving');
      setTimeout(function () {
        window.location.href = targetUrl;
      }, 320);
    }
  }, true);

  document.addEventListener('click', function (e) {
    const link = e.target.closest('a');
    if (!link) return;

    const href = link.getAttribute('href');
    if (!href) return;

    // Ignore non-navigation schemes and actions
    if (
      href.startsWith('#') ||
      href.startsWith('javascript:') ||
      href.startsWith('mailto:') ||
      href.startsWith('tel:') ||
      link.target === '_blank' ||
      link.hasAttribute('download') ||
      href.includes('download_pdf=1') ||
      href.includes('download=') ||
      link.getAttribute('data-bs-toggle') ||
      link.getAttribute('data-bs-target') ||
      link.getAttribute('data-no-transition') !== null ||
      e.ctrlKey ||
      e.metaKey ||
      e.shiftKey ||
      e.altKey ||
      e.button !== 0
    ) {
      return;
    }

    try {
      const url = new URL(link.href, window.location.href);
      if (url.origin !== window.location.origin) return; // External link
      if (url.pathname === window.location.pathname && url.search === window.location.search) {
        if (url.hash) return; // Same page anchor jump
      }

      e.preventDefault();

      const isAuthSwitch =
        link.closest('.auth-tabs') !== null ||
        link.classList.contains('auth-tab') ||
        link.classList.contains('auth-link') ||
        link.closest('.auth-card') !== null ||
        document.body.classList.contains('auth-page') ||
        /login\.php|register\.php|forgot_password\.php/i.test(link.href);

      const isSidebarLink = link.closest('.admin-sidebar') !== null;
      if (isSidebarLink) {
        document.querySelectorAll('.admin-sidebar .nav-link').forEach(function (nl) {
          nl.classList.remove('active');
        });
        link.classList.add('active');
      }

      const delayMs = isAuthSwitch ? 320 : 160;

      if (isAuthSwitch) {
        showLoader('Loading…');
      } else {
        startTopProgress();
      }

      document.body.classList.add('lapify-page-leaving');

      setTimeout(function () {
        window.location.href = link.href;
      }, delayMs);
    } catch (err) {
      // Fallback to default browser behavior
    }
  }, false);

  // ------------------------------------------------------------------
  // 4. Form submissions handler (Non-blocking & Submit-Name preserving)
  // ------------------------------------------------------------------
  document.addEventListener('submit', function (e) {
    const form = e.target;
    if (!form || form.tagName !== 'FORM') return;
    if (form.getAttribute('data-no-transition') !== null) return;

    // Skip AJAX / fetch handled forms
    if (form.id === 'sell-form' || form.getAttribute('data-ajax') === '1' || form.classList.contains('auth-form')) return;

    // Double submit protection
    if (form.dataset.submitting === 'true') {
      e.preventDefault();
      return;
    }
    form.dataset.submitting = 'true';

    startTopProgress();

    // Determine the submit button
    const submitBtn = e.submitter || form.querySelector('button[type="submit"]:focus') || form.querySelector('button[type="submit"], .auth-btn');
    const label = (submitBtn && submitBtn.getAttribute('data-loading-text')) || 'Processing…';

    if (submitBtn) {
      submitBtn.setAttribute('aria-busy', 'true');
      submitBtn.classList.add('loading');
      if (!submitBtn.dataset.originalHtml) {
        submitBtn.dataset.originalHtml = submitBtn.innerHTML;
      }
      submitBtn.innerHTML = '<span class="btn-spinner"></span> ' + label;
    }

    // For file uploads show smooth loader
    const hasFile = form.querySelector('input[type="file"]');
    if (hasFile) {
      showLoader(label);
    }

    // Safety timeout: automatically restore button if form didn't navigate within 4s
    setTimeout(function () {
      if (form.dataset.submitting === 'true') {
        form.dataset.submitting = 'false';
        if (submitBtn) {
          submitBtn.removeAttribute('aria-busy');
          submitBtn.classList.remove('loading');
          if (submitBtn.dataset.originalHtml) {
            submitBtn.innerHTML = submitBtn.dataset.originalHtml;
          }
        }
      }
    }, 4000);
  }, false);

  // Restore button state if browser HTML5 validation fails
  document.addEventListener('invalid', function (e) {
    const form = e.target && e.target.form;
    if (form) {
      form.dataset.submitting = 'false';
      const submitBtn = form.querySelector('button[type="submit"].loading, .auth-btn.loading, .btn-checkout.loading');
      if (submitBtn) {
        submitBtn.removeAttribute('aria-busy');
        submitBtn.classList.remove('loading');
        if (submitBtn.dataset.originalHtml) {
          submitBtn.innerHTML = submitBtn.dataset.originalHtml;
        }
      }
      finishTopProgress();
      hideLoader();
    }
  }, true);

  // ------------------------------------------------------------------
  // 5. Restore buttons & state on pageshow / load (handles back button)
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

  window.addEventListener('pageshow', function () {
    restoreFormButtons();
  });

  window.addEventListener('popstate', function () {
    restoreFormButtons();
  });

  window.addEventListener('load', function () {
    finishTopProgress();
    hideLoader();
  });

  document.addEventListener('DOMContentLoaded', function () {
    ensureTopProgress();
    finishTopProgress();
    hideLoader();
  });

  // ------------------------------------------------------------------
  // 6. Expose global API
  // ------------------------------------------------------------------
  window.LapifyTransition = {
    show: showLoader,
    hide: hideLoader,
    startProgress: startTopProgress,
    finishProgress: finishTopProgress,
    restoreButtons: restoreFormButtons,
    isTransitioning: function () { return isTransitioning; }
  };
})();