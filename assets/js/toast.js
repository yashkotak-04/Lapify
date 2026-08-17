/**
 * Lapify Toast Notification System
 * Usage:
 *   showToast('Saved successfully.', 'success');
 *   showToast('Something went wrong.', 'error', 4500);
 */
(function () {
    const TOAST_CONTAINER_ID = 'lapify-toast-container';
    let container = null;

    function ensureContainer() {
        if (container) {
            return container;
        }

        container = document.getElementById(TOAST_CONTAINER_ID);
        if (!container) {
            container = document.createElement('div');
            container.id = TOAST_CONTAINER_ID;
            container.setAttribute('role', 'region');
            container.setAttribute('aria-live', 'polite');
            container.setAttribute('aria-label', 'Notifications');
            document.body.appendChild(container);
        }

        return container;
    }

    function getIcon(type) {
        switch (type) {
            case 'error':
                return '✕';
            case 'warning':
                return '⚠';
            case 'info':
                return 'ℹ';
            case 'success':
            default:
                return '✓';
        }
    }

    function getTypeClass(type) {
        switch (type) {
            case 'error':
                return 'toast-error';
            case 'warning':
                return 'toast-warning';
            case 'info':
                return 'toast-info';
            case 'success':
            default:
                return 'toast-success';
        }
    }

    window.showToast = function showToast(message, type = 'success', duration = 3500) {
        if (!message) {
            return;
        }

        const containerEl = ensureContainer();
        const toast = document.createElement('div');
        toast.className = 'lapify-toast ' + getTypeClass(type);
        toast.setAttribute('role', 'status');
        toast.innerHTML = [
            '<div class="lapify-toast__icon">' + getIcon(type) + '</div>',
            '<div class="lapify-toast__content">',
            '<div class="lapify-toast__title">' + escapeHtml(type.charAt(0).toUpperCase() + type.slice(1)) + '</div>',
            '<div class="lapify-toast__message">' + escapeHtml(message) + '</div>',
            '</div>',
            '<button type="button" class="lapify-toast__close" aria-label="Close notification">×</button>'
        ].join('');

        const closeBtn = toast.querySelector('.lapify-toast__close');
        const dismiss = () => {
            toast.classList.add('is-leaving');
            setTimeout(() => toast.remove(), 220);
        };

        closeBtn.addEventListener('click', dismiss);
        containerEl.appendChild(toast);

        requestAnimationFrame(() => {
            toast.classList.add('is-visible');
        });

        setTimeout(dismiss, Math.max(1800, duration));
    };

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }
})();
