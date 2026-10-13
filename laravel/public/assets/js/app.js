/**
 * Shared helpers used across pages.
 */

/**
 * Light / dark / auto theme. The <head> script applies the saved choice before paint;
 * this wires up the switcher and follows the device setting while on "auto".
 */
(function () {
    const icons = { light: 'bi-sun', dark: 'bi-moon-stars', auto: 'bi-circle-half' };
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)');

    function apply(choice) {
        const dark = choice === 'dark' || (choice === 'auto' && prefersDark.matches);
        document.documentElement.setAttribute('data-bs-theme', dark ? 'dark' : 'light');
        document.documentElement.dataset.themeChoice = choice;
        document.querySelectorAll('[data-theme-icon]').forEach(icon => {
            icon.className = 'bi ' + icons[choice];
        });
        document.querySelectorAll('[data-theme-value]').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.themeValue === choice);
        });
    }

    function save(choice) {
        try { localStorage.setItem('sj-theme', choice); } catch (e) { /* private mode: theme just won't persist */ }
        apply(choice);
    }

    document.addEventListener('click', e => {
        const option = e.target.closest('[data-theme-value]');
        if (option) save(option.dataset.themeValue);

        if (e.target.closest('[data-theme-cycle]')) {
            const order = ['light', 'dark', 'auto'];
            const current = document.documentElement.dataset.themeChoice || 'auto';
            save(order[(order.indexOf(current) + 1) % order.length]);
        }
    });

    prefersDark.addEventListener('change', () => {
        if ((document.documentElement.dataset.themeChoice || 'auto') === 'auto') apply('auto');
    });

    document.addEventListener('DOMContentLoaded', () => apply(document.documentElement.dataset.themeChoice || 'auto'));
})();

// fetch() wrapper that sends the CSRF token and asks Laravel for JSON responses.
window.sjFetch = function (url, options = {}) {
    options.headers = Object.assign({
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
    }, options.headers || {});
    return fetch(url, options);
};

// Show a message in the shared status modal (partials/modals).
window.showStatus = function (message) {
    const body = document.getElementById('statusModalBody');
    if (!body) { alert(message); return; }
    body.textContent = message;
    bootstrap.Modal.getOrCreateInstance(document.getElementById('statusModal')).show();
};

/**
 * Forms marked data-ajax-form are submitted with fetch(). The controller answers
 * {status, message, redirect}; validation errors come back as {message, errors}.
 */
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('form[data-ajax-form]').forEach(form => {
        form.addEventListener('submit', e => {
            e.preventDefault();
            if (typeof form.beforeAjaxSubmit === 'function') form.beforeAjaxSubmit();

            const btn = form.querySelector('[type="submit"]');
            if (btn) btn.disabled = true;

            sjFetch(form.action, { method: 'POST', body: new FormData(form) })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        if (form.dataset.ajaxForm === 'redirect') {
                            window.location.href = data.redirect;
                            return;
                        }
                        showStatus(data.message);
                        setTimeout(() => { window.location.href = data.redirect; }, 1500);
                    } else {
                        showStatus(data.message || 'Something went wrong. Please check the form and try again.');
                        if (btn) btn.disabled = false;
                    }
                })
                .catch(() => {
                    showStatus('An unexpected error occurred. Please try again.');
                    if (btn) btn.disabled = false;
                });
        });
    });
});
