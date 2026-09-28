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

(() => {
    // ----- Copy share link / native share -----
    document.querySelectorAll('[data-copy]').forEach((btn) =>
        btn.addEventListener('click', async () => {
            const input = btn.parentElement.querySelector('[data-copy-source]');
            try { await navigator.clipboard.writeText(input.value); } catch (_) { input.select(); document.execCommand('copy'); }
            btn.textContent = 'Copied!';
            setTimeout(() => (btn.textContent = 'Copy'), 1500);
        })
    );
    document.querySelectorAll('[data-share]').forEach((btn) => {
        if (!navigator.share) return;
        btn.hidden = false;
        btn.addEventListener('click', () =>
            navigator.share({ title: btn.dataset.title, text: btn.dataset.text, url: btn.dataset.url }).catch(() => {})
        );
    });

    // ----- Bill editor: line items and live total -----
    const form = document.querySelector('[data-invoice-form]');
    if (!form) return;

    const list = form.querySelector('[data-items]');
    const currency = window.LEDGER_CURRENCY || '₹';
    const fmt = (n) => currency + n.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const num = (el) => parseFloat(el?.value) || 0;

    const renumber = () => list.querySelectorAll('[data-item]').forEach((row, i) =>
        row.querySelectorAll('input').forEach((input) => (input.name = input.name.replace(/items\[\d+\]/, `items[${i}]`)))
    );

    const recalc = () => {
        let subtotal = 0;
        list.querySelectorAll('[data-item]').forEach((row) => {
            const line = num(row.querySelector('[data-qty]')) * num(row.querySelector('[data-rate]'));
            row.querySelector('[data-line]').textContent = fmt(line);
            subtotal += line;
        });
        const taxable = Math.max(subtotal - num(form.querySelector('[data-discount]')), 0);
        form.querySelector('[data-total]').textContent = fmt(taxable + taxable * num(form.querySelector('[data-tax]')) / 100);
    };

    form.querySelector('[data-add-item]').addEventListener('click', () => {
        const rows = list.querySelectorAll('[data-item]');
        const row = rows[rows.length - 1].cloneNode(true);
        row.querySelectorAll('input').forEach((input) => (input.value = input.hasAttribute('data-qty') ? '1' : ''));
        list.appendChild(row);
        renumber();
        recalc();
        row.querySelector('input').focus();
    });

    list.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-remove]');
        if (!btn) return;
        const rows = list.querySelectorAll('[data-item]');
        if (rows.length === 1) {
            rows[0].querySelectorAll('input').forEach((input) => (input.value = input.hasAttribute('data-qty') ? '1' : ''));
        } else {
            btn.closest('[data-item]').remove();
            renumber();
        }
        recalc();
    });

    form.addEventListener('input', recalc);
    recalc();
})();
