document.addEventListener('DOMContentLoaded', function () {
    // 1. Smooth Tab Switch & Page Transfer Transition (Sign In <-> Create Account)
    const authCard = document.querySelector('.auth-card');
    const authTabs = document.querySelectorAll('.auth-tabs .auth-tab, .auth-footer-row a');

    authTabs.forEach(tab => {
        tab.addEventListener('click', function (e) {
            const hrefAttr = this.getAttribute('href');
            const onclickAttr = this.getAttribute('onclick') || '';
            const match = onclickAttr.match(/window\.location\.href\s*=\s*['"]([^'"]+)['"]/);
            const targetUrl = hrefAttr || (match ? match[1] : null);

            if (targetUrl) {
                const currentPath = window.location.pathname;
                const targetFileName = targetUrl.split('/').pop().split('?')[0];
                const currentFileName = currentPath.split('/').pop().split('?')[0];

                if (targetFileName && targetFileName !== currentFileName) {
                    e.preventDefault();
                    e.stopPropagation();

                    if (authCard) {
                        authCard.classList.add('tab-exiting');
                    }

                    const label = targetFileName.includes('register') ? 'Loading Create Account…' : 'Loading Sign In…';
                    if (window.LapifyTransition && typeof window.LapifyTransition.show === 'function') {
                        window.LapifyTransition.show(label);
                    }

                    setTimeout(() => {
                        window.location.href = targetUrl;
                    }, 280);
                }
            }
        });
    });

    // 2. Success Modal Overlay Auto-Dismiss (fallback for direct page loads)
    const existingSuccessModal = document.getElementById('auth-success-modal');
    if (existingSuccessModal) {
        function dismissModal() {
            existingSuccessModal.style.opacity = '0';
            existingSuccessModal.style.pointerEvents = 'none';
            setTimeout(() => {
                existingSuccessModal.classList.remove('active');
                if (existingSuccessModal.parentNode) {
                    existingSuccessModal.parentNode.removeChild(existingSuccessModal);
                }
            }, 350);
        }

        existingSuccessModal.addEventListener('click', dismissModal);
        const redirectUrl = existingSuccessModal.getAttribute('data-redirect-url');
        if (redirectUrl) {
            setTimeout(() => {
                window.location.href = redirectUrl;
            }, 2200);
        } else {
            setTimeout(dismissModal, 2200);
        }
    }

    // 3. Smooth Login Form Interceptor & Animated Success Overlay
    const authForms = document.querySelectorAll('form.auth-form');
    authForms.forEach(form => {
        const isLoginForm = form.action.includes('login.php') || window.location.pathname.includes('login.php');
        if (!isLoginForm) return;

        form.addEventListener('submit', function (e) {
            e.preventDefault();

            const submitBtn = form.querySelector('button[type="submit"], .auth-btn');
            const originalBtnHtml = submitBtn ? submitBtn.innerHTML : '';
            const loadingText = (submitBtn && submitBtn.getAttribute('data-loading-text')) || 'Signing In…';

            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>' + loadingText;
            }

            // Remove existing alert boxes inside card
            const authCard = form.closest('.auth-card');
            if (authCard) {
                const existingAlert = authCard.querySelector('.auth-alert');
                if (existingAlert) {
                    existingAlert.remove();
                }
            }

            const formData = new FormData(form);
            formData.append('ajax', '1');

            fetch(form.action || window.location.href, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(resp => {
                return resp.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        const jsonMatch = text.match(/\{[\s\S]*\}/);
                        if (jsonMatch) {
                            try {
                                return JSON.parse(jsonMatch[0]);
                            } catch (e2) {}
                        }
                        return { success: false, message: 'Server response error. Please try again.' };
                    }
                });
            })
            .then(data => {
                if (data.success) {
                    // Create or activate the smooth animated success modal overlay
                    let successOverlay = document.getElementById('auth-success-modal');
                    if (!successOverlay) {
                        successOverlay = document.createElement('div');
                        successOverlay.id = 'auth-success-modal';
                        successOverlay.className = 'auth-success-backdrop';
                        document.body.appendChild(successOverlay);
                    }

                    // Immediately hide the background login card so it never flickers
                    if (authCard) {
                        authCard.style.opacity = '0';
                        authCard.style.pointerEvents = 'none';
                    }

                    const userName = data.full_name || 'User';
                    const targetDashboard = data.redirect || 'dashboard.php';
                    const isTargetAdmin = targetDashboard.includes('admin') || window.location.pathname.includes('admin');
                    const titleText = isTargetAdmin ? '🎉 Admin Access Granted!' : '🎉 Successfully Logged In!';
                    const subtitleText = isTargetAdmin 
                        ? `Welcome back, ${escapeHtml(userName)}! Launching Administrator Command Center...` 
                        : `Welcome back, ${escapeHtml(userName)}! Launching your Lapify dashboard...`;

                    successOverlay.innerHTML = `
                        <div class="auth-success-card">
                            <div class="auth-success-icon-wrap">
                                <i class="bi ${isTargetAdmin ? 'bi-shield-check' : 'bi-check-circle-fill'}"></i>
                            </div>
                            <h3 class="auth-success-title">${titleText}</h3>
                            <p class="auth-success-text">${subtitleText}</p>
                            <div class="auth-success-progress-track">
                                <div class="auth-success-progress-bar" id="auth-success-progress-bar"></div>
                            </div>
                        </div>
                    `;

                    requestAnimationFrame(() => {
                        successOverlay.classList.add('active');
                        setTimeout(() => {
                            const progressBar = document.getElementById('auth-success-progress-bar');
                            if (progressBar) {
                                progressBar.style.width = '100%';
                            }
                        }, 50);
                    });

                    // Launch Firecrackers Celebration Animation (both user and admin login)
                    if (typeof window.launchFirecrackers === 'function') {
                        window.launchFirecrackers();
                    } else if (typeof confetti === 'function') {
                        confetti({ particleCount: 160, spread: 85, origin: { y: 0.6 }, zIndex: 10000000 });
                        setTimeout(() => {
                            confetti({ particleCount: 100, spread: 65, origin: { y: 0.5 }, zIndex: 10000000 });
                        }, 500);
                    }

                    // Trigger toast if available
                    if (typeof window.showToast === 'function') {
                        window.showToast('🎉 Welcome back, ' + userName + '!', 'success', 4000);
                    }

                    // Direct seamless transition straight to target dashboard while celebration overlay stays active
                    setTimeout(() => {
                        window.location.href = targetDashboard;
                    }, 2200);
                } else {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalBtnHtml;
                    }

                    const errorMsg = data.message || 'Invalid email or password. Please try again.';
                    
                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'auth-alert';
                    alertDiv.setAttribute('role', 'alert');
                    alertDiv.innerHTML = `<ul><li>${escapeHtml(errorMsg)}</li></ul>`;
                    
                    if (authCard) {
                        const cardHeader = authCard.querySelector('.auth-card-header');
                        if (cardHeader) {
                            cardHeader.insertAdjacentElement('afterend', alertDiv);
                        } else {
                            form.insertAdjacentElement('beforebegin', alertDiv);
                        }
                    }

                    if (typeof window.showToast === 'function') {
                        window.showToast(errorMsg, 'error', 4000);
                    }
                }
            })
            .catch(() => {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnHtml;
                }
                if (typeof window.showToast === 'function') {
                    window.showToast('Network error, please try again.', 'error', 4000);
                }
            });
        });
    });

    // 4. Smooth Forgot Password Form Interceptor (Instant AJAX Feedback & Zero Freeze)
    const forgotForms = document.querySelectorAll('form[action*="forgot_password.php"], form.auth-form');
    forgotForms.forEach(form => {
        const isForgotForm = form.action.includes('forgot_password.php') || window.location.pathname.includes('forgot_password.php');
        if (!isForgotForm) return;

        form.addEventListener('submit', function (e) {
            e.preventDefault();

            const submitBtn = form.querySelector('button[type="submit"], .auth-btn');
            const originalBtnHtml = submitBtn ? submitBtn.innerHTML : '';
            const loadingText = (submitBtn && submitBtn.getAttribute('data-loading-text')) || 'Sending Link…';

            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>' + loadingText;
            }

            // Remove existing alert boxes inside card
            const authCard = form.closest('.auth-card');
            if (authCard) {
                const existingAlerts = authCard.querySelectorAll('.auth-alert, .alert');
                existingAlerts.forEach(al => al.remove());
            }

            const formData = new FormData(form);
            formData.append('ajax', '1');

            fetch(form.action || window.location.href, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(resp => {
                return resp.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        const jsonMatch = text.match(/\{[\s\S]*\}/);
                        if (jsonMatch) {
                            try {
                                return JSON.parse(jsonMatch[0]);
                            } catch (e2) {}
                        }
                        return { success: false, message: 'Server response error. Please try again.' };
                    }
                });
            })
            .then(data => {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnHtml;
                }

                if (data.success) {
                    let resultHtml = '';
                    if (data.is_smtp_sent) {
                        resultHtml = `
                            <div class="alert alert-success border-0 rounded-4 shadow-sm py-3 px-4 mb-4" role="status" style="background: rgba(34, 197, 94, 0.12); border: 1.5px solid rgba(34, 197, 94, 0.4) !important; color: #15803d !important;">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <i class="bi bi-check-circle-fill text-success fs-5"></i>
                                    <strong>Email Sent Successfully!</strong>
                                </div>
                                <p class="mb-0 small" style="color: #166534 !important; font-weight: 500;">${escapeHtml(data.message)}</p>
                            </div>
                        `;
                    } else {
                        resultHtml = `
                            <div class="alert alert-success border-0 rounded-4 shadow-sm py-3 px-4 mb-3" role="status" style="background: rgba(34, 197, 94, 0.12); border: 1.5px solid rgba(34, 197, 94, 0.4) !important; color: #15803d !important;">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <i class="bi bi-check-circle-fill text-success fs-5"></i>
                                    <strong>Reset Request Processed</strong>
                                </div>
                                <p class="mb-0 small" style="color: #166534 !important; font-weight: 500;">${escapeHtml(data.message)}</p>
                            </div>
                        `;
                        if (data.dev_reset_link) {
                            resultHtml += `
                                <div class="alert alert-primary border-0 rounded-4 shadow-sm p-4 mb-4 text-start" style="background: rgba(37, 99, 235, 0.08); border: 1.5px solid rgba(37, 99, 235, 0.3) !important;">
                                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                                        <span class="text-primary fw-bold fs-6">
                                            <i class="bi bi-shield-lock-fill me-1"></i> Local Development Reset Link
                                        </span>
                                        <span class="badge bg-primary text-white px-2 py-1 small rounded-pill">Localhost Mode</span>
                                    </div>
                                    <p class="small text-secondary mb-3">Open your reset link directly below:</p>
                                    <div class="d-flex gap-2 flex-wrap mb-2">
                                        <a href="${escapeHtml(data.dev_reset_link)}" class="btn btn-primary btn-sm rounded-pill px-4 py-2 font-weight-bold text-white shadow-sm">
                                            <i class="bi bi-box-arrow-up-right me-1"></i> Open Password Reset Page
                                        </a>
                                    </div>
                                </div>
                            `;
                        }
                    }

                    if (authCard) {
                        const cardHeader = authCard.querySelector('.auth-card-header');
                        const container = document.createElement('div');
                        container.innerHTML = resultHtml;
                        if (cardHeader) {
                            cardHeader.insertAdjacentElement('afterend', container);
                        } else {
                            form.insertAdjacentElement('beforebegin', container);
                        }
                    }

                    if (typeof window.showToast === 'function') {
                        window.showToast(data.message, 'success', 4000);
                    }
                } else {
                    const errorMsg = data.message || 'We could not process your request right now.';
                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'auth-alert';
                    alertDiv.setAttribute('role', 'alert');
                    alertDiv.innerHTML = `<ul><li>${escapeHtml(errorMsg)}</li></ul>`;

                    if (authCard) {
                        const cardHeader = authCard.querySelector('.auth-card-header');
                        if (cardHeader) {
                            cardHeader.insertAdjacentElement('afterend', alertDiv);
                        } else {
                            form.insertAdjacentElement('beforebegin', alertDiv);
                        }
                    }

                    if (typeof window.showToast === 'function') {
                        window.showToast(errorMsg, 'error', 4000);
                    }
                }
            })
            .catch(() => {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnHtml;
                }
                if (typeof window.showToast === 'function') {
                    window.showToast('Network error, please try again.', 'error', 4000);
                }
            });
        });
    });

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }
});
