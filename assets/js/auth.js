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

    // 2. Success Modal Overlay Auto-Dismiss / Redirect
    const successModal = document.getElementById('auth-success-modal');
    if (successModal) {
        function dismissModal() {
            successModal.style.opacity = '0';
            successModal.style.pointerEvents = 'none';
            setTimeout(() => {
                successModal.classList.remove('active');
                if (successModal.parentNode) {
                    successModal.parentNode.removeChild(successModal);
                }
            }, 400);
        }

        // Click to dismiss immediately
        successModal.addEventListener('click', dismissModal);

        const redirectUrl = successModal.getAttribute('data-redirect-url');
        if (redirectUrl) {
            setTimeout(() => {
                window.location.href = redirectUrl;
            }, 700);
        } else {
            // Auto dismiss swiftly after 0.8 seconds
            setTimeout(dismissModal, 800);
        }
    }
});
