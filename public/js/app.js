(() => {
    // ----- Mobile navigation drawer -----
    document.querySelectorAll('[data-nav-toggle]').forEach((el) =>
        el.addEventListener('click', () => document.body.classList.toggle('nav-open'))
    );

    // ----- Service worker -----
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js').catch(() => {}));

        document.querySelectorAll('form[action$="/logout"]').forEach((form) =>
            form.addEventListener('submit', () => navigator.serviceWorker.controller?.postMessage('clear-pages'))
        );
    }

    // ----- Install prompt -----
    let deferred = null;
    const banner = document.getElementById('install-banner');
    const dismissedAt = Number(localStorage.getItem('install-dismissed') || 0);

    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferred = e;
        if (banner && Date.now() - dismissedAt > 7 * 864e5) banner.classList.add('show');
    });
    document.getElementById('install-btn')?.addEventListener('click', async () => {
        banner.classList.remove('show');
        deferred?.prompt();
        deferred = null;
    });
    document.getElementById('install-dismiss')?.addEventListener('click', () => {
        banner.classList.remove('show');
        try { localStorage.setItem('install-dismissed', String(Date.now())); } catch (_) {}
    });

    // ----- Transaction form: show fields relevant to income vs expense -----
    const form = document.querySelector('[data-txn-form]');
    if (form) {
        const category = form.querySelector('select[name="category"]');
        const project = form.querySelector('select[name="project_id"]');
        const hint = form.querySelector('[data-share-hint]');

        const sync = () => {
            const type = form.querySelector('input[name="type"]:checked')?.value || 'expense';

            category.querySelectorAll('optgroup').forEach((group) => {
                const active = group.dataset.type === type;
                group.hidden = !active;
                group.disabled = !active;
            });
            if (category.selectedOptions[0]?.parentElement.dataset.type !== type) {
                category.value = category.querySelector(`optgroup[data-type="${type}"] option`).value;
            }

            form.querySelectorAll('[data-show]').forEach((el) => {
                el.closest('label').hidden = el.dataset.show !== type;
            });

            const opt = project.selectedOptions[0];
            const share = Number(opt?.dataset.share || 0);
            hint.textContent = type === 'income' && share > 0
                ? `Ad revenue here is split ${share}% to ${opt.dataset.partner}.`
                : '';
        };

        form.querySelectorAll('input[name="type"]').forEach((r) => r.addEventListener('change', sync));
        project.addEventListener('change', sync);
        sync();
    }
})();
