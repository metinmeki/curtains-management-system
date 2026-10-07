/* Shared UI chrome: currency toggle, themed dialogs, mobile nav.
   Loaded after api.js on every page so behaviour is identical everywhere. */

/* ── Themed confirm / alert ───────────────────────────────────────────────── */
/* Replaces native confirm()/alert(), which can't be styled, can't be
   translated by the i18n MutationObserver, and blocks the whole tab. */

function _cmsEnsureDialogStyles() {
    if (document.getElementById('cms-dialog-styles')) return;
    const style = document.createElement('style');
    style.id = 'cms-dialog-styles';
    style.textContent = `
.cms-dialog-backdrop{position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:100000;display:flex;align-items:center;justify-content:center;padding:16px}
.cms-dialog{background:#fff;border-radius:12px;max-width:420px;width:100%;padding:22px;box-shadow:0 12px 40px rgba(0,0,0,.3);font-family:inherit}
.cms-dialog-msg{font-size:15px;line-height:1.55;color:#1a2e1f;margin:0 0 20px}
.cms-dialog-actions{display:flex;gap:10px;justify-content:flex-end;flex-wrap:wrap}
.cms-dialog button{min-height:44px;padding:10px 18px;border-radius:8px;font-size:14px;font-weight:600;font-family:inherit;border:1px solid transparent;cursor:pointer}
.cms-dialog-cancel{background:#eef3ef;color:#1a2e1f;border-color:#cfe0d5!important}
.cms-dialog-ok{background:#1a3d2b;color:#fff}
.cms-dialog-ok.danger{background:#b4534a}
.cms-dialog button:focus-visible{outline:3px solid #2d6a47;outline-offset:2px}
@media (prefers-reduced-motion: no-preference){.cms-dialog{animation:cms-dialog-in .16s ease}}
@keyframes cms-dialog-in{from{opacity:0;transform:translateY(-8px)}to{opacity:1;transform:none}}`;
    document.head.appendChild(style);
}

/* Returns a promise resolving true/false. Focus is trapped to the dialog and
   restored to the triggering element on close. */
function cmsConfirm(message, options) {
    const opts = options || {};
    _cmsEnsureDialogStyles();
    const t = (s) => (typeof ta === 'function' ? ta(s) : s);

    return new Promise((resolve) => {
        const previousFocus = document.activeElement;
        const backdrop = document.createElement('div');
        backdrop.className = 'cms-dialog-backdrop';
        backdrop.setAttribute('role', 'dialog');
        backdrop.setAttribute('aria-modal', 'true');

        const box = document.createElement('div');
        box.className = 'cms-dialog';

        const msg = document.createElement('p');
        msg.className = 'cms-dialog-msg';
        msg.id = 'cms-dialog-msg-' + Date.now();
        msg.textContent = t(message);
        backdrop.setAttribute('aria-labelledby', msg.id);

        const actions = document.createElement('div');
        actions.className = 'cms-dialog-actions';

        const close = (result) => {
            backdrop.remove();
            document.removeEventListener('keydown', onKey);
            if (previousFocus && previousFocus.focus) previousFocus.focus();
            resolve(result);
        };

        let okBtn = null;
        let cancelBtn = null;

        if (!opts.alertOnly) {
            cancelBtn = document.createElement('button');
            cancelBtn.type = 'button';
            cancelBtn.className = 'cms-dialog-cancel';
            cancelBtn.textContent = t(opts.cancelText || 'Cancel');
            cancelBtn.onclick = () => close(false);
            actions.appendChild(cancelBtn);
        }

        okBtn = document.createElement('button');
        okBtn.type = 'button';
        okBtn.className = 'cms-dialog-ok' + (opts.danger ? ' danger' : '');
        okBtn.textContent = t(opts.okText || (opts.alertOnly ? 'OK' : 'Confirm'));
        okBtn.onclick = () => close(true);
        actions.appendChild(okBtn);

        function onKey(e) {
            if (e.key === 'Escape') { e.preventDefault(); close(false); return; }
            if (e.key !== 'Tab') return;
            // Trap focus inside the dialog.
            const focusable = [cancelBtn, okBtn].filter(Boolean);
            const first = focusable[0];
            const last = focusable[focusable.length - 1];
            if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
            else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
        }

        box.appendChild(msg);
        box.appendChild(actions);
        backdrop.appendChild(box);
        backdrop.addEventListener('mousedown', (e) => { if (e.target === backdrop) close(false); });
        document.addEventListener('keydown', onKey);
        document.body.appendChild(backdrop);
        okBtn.focus();
    });
}

/* Informational dialog — one button, resolves when dismissed. */
function cmsAlert(message) {
    return cmsConfirm(message, { alertOnly: true });
}

/* ── Global currency toggle ───────────────────────────────────────────────── */
/* Every page that formats money gets the toggle, so the display currency is
   never a hidden mode the user can't see or undo. */

function _cmsCurrencyToggleHtml(cur) {
    return `
<span class="cms-seg__label">${typeof ta === 'function' ? ta('Display') : 'Display'}</span>
<button type="button" data-cur="IQD" aria-pressed="${cur === 'IQD'}">IQD</button>
<button type="button" data-cur="USD" aria-pressed="${cur === 'USD'}">USD</button>`;
}

function setDisplayCurrency(cur) {
    if (cur === getDisplayCurrency()) return;
    localStorage.setItem('display_currency', cur);
    document.querySelectorAll('.cms-currency-toggle button').forEach((b) => {
        b.setAttribute('aria-pressed', String(b.dataset.cur === cur));
    });
    _cmsUpdateRateWarning();

    // Pages that can redraw in place set window.cmsRedraw. Everything else
    // reloads — showing half the page in the old currency is worse.
    if (typeof window.cmsRedraw === 'function') {
        document.dispatchEvent(new CustomEvent('cms:currency-changed', { detail: { currency: cur } }));
        window.cmsRedraw();
    } else {
        window.location.reload();
    }
}

function _cmsUpdateRateWarning() {
    const warn = document.getElementById('cms-rate-warning');
    if (!warn) return;
    const show = getDisplayCurrency() === 'USD' && !isExchangeRateKnown();
    warn.style.display = show ? 'block' : 'none';
}

function mountCurrencyToggle() {
    if (document.querySelector('.cms-currency-toggle')) return;

    const host = document.querySelector('.rt-topbar-right')
        || document.querySelector('.wh-topbar-right')
        || document.querySelector('.user-section')
        || document.querySelector('.navbar-actions');
    if (!host) return;

    // Shared control styles come from js/i18n.js so both toggles stay identical.
    if (typeof cmsEnsureControlStyles === 'function') cmsEnsureControlStyles();

    if (!document.getElementById('cms-currency-styles')) {
        const style = document.createElement('style');
        style.id = 'cms-currency-styles';
        style.textContent = `
#cms-rate-warning{display:none;background:#fdf3d8;color:#7a581d;border-left:4px solid #d9a52e;border-radius:0;padding:10px 14px;font-size:13px;font-weight:600;margin:0}
@media(max-width:600px){.cms-currency-toggle .cms-seg__label{display:none}}`;
        document.head.appendChild(style);
    }

    const cur = getDisplayCurrency();
    const wrap = document.createElement('div');
    wrap.className = 'cms-seg cms-currency-toggle no-print';
    wrap.setAttribute('role', 'group');
    wrap.setAttribute('aria-label', typeof ta === 'function' ? ta('Display currency') : 'Display currency');
    wrap.innerHTML = _cmsCurrencyToggleHtml(cur);
    wrap.querySelectorAll('button').forEach((b) => {
        b.addEventListener('click', () => setDisplayCurrency(b.dataset.cur));
    });
    host.prepend(wrap);

    if (typeof cmsIsDarkSurface === 'function' && cmsIsDarkSurface(host)) {
        wrap.setAttribute('data-surface', 'dark');
    }

    // Banner shown only when USD is selected but no rate is available.
    if (!document.getElementById('cms-rate-warning')) {
        const warn = document.createElement('div');
        warn.id = 'cms-rate-warning';
        warn.className = 'no-print';
        warn.setAttribute('role', 'status');
        warn.textContent = typeof ta === 'function'
            ? ta('Exchange rate not available — USD amounts cannot be shown.')
            : 'Exchange rate not available — USD amounts cannot be shown.';
        const main = document.querySelector('.rt-main, .wh-main, main') || document.body;
        main.insertBefore(warn, main.firstChild);
    }
    _cmsUpdateRateWarning();
}

/* ── Mobile drawer nav ────────────────────────────────────────────────────── */
/* Retail already had this inline; warehouse pages had no mobile nav at all. */

function cmsToggleSidebar(force) {
    const sidebar = document.querySelector('.rt-sidebar, .wh-sidebar');
    const overlay = document.getElementById('cms-sidebar-overlay');
    const btn = document.querySelector('.cms-menu-btn');
    if (!sidebar) return;
    const open = typeof force === 'boolean' ? force : !sidebar.classList.contains('open');
    sidebar.classList.toggle('open', open);
    if (overlay) overlay.classList.toggle('open', open);
    if (btn) btn.setAttribute('aria-expanded', String(open));
    if (open) {
        const firstLink = sidebar.querySelector('a, button');
        if (firstLink) firstLink.focus();
    } else if (btn) {
        btn.focus();
    }
}

function mountMobileNav() {
    const sidebar = document.querySelector('.rt-sidebar, .wh-sidebar');
    if (!sidebar) return;
    if (!sidebar.id) sidebar.id = 'cms-sidebar';

    const topbar = document.querySelector('.rt-topbar, .wh-topbar');
    const existing = topbar && topbar.querySelector('.rt-menu-btn, .cms-menu-btn');

    if (existing) {
        // Retail ships its own hamburger — give it the accessible attributes
        // it was missing rather than adding a second button.
        existing.setAttribute('aria-label', typeof ta === 'function' ? ta('Open menu') : 'Open menu');
        existing.setAttribute('aria-expanded', 'false');
        existing.setAttribute('aria-controls', sidebar.id);
        existing.classList.add('cms-menu-btn-managed');
    } else if (topbar) {
        if (typeof cmsEnsureControlStyles === 'function') cmsEnsureControlStyles();
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'cms-iconbtn cms-menu-btn';
        btn.setAttribute('aria-label', typeof ta === 'function' ? ta('Open menu') : 'Open menu');
        btn.setAttribute('aria-expanded', 'false');
        btn.setAttribute('aria-controls', sidebar.id);
        // Vector icon, not the ☰ glyph: that renders at a different weight and
        // baseline in every font and can't be sized against the other controls.
        btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true" focusable="false"><path d="M4 7h16M4 12h16M4 17h16"/></svg>';
        btn.addEventListener('click', () => cmsToggleSidebar());
        topbar.insertBefore(btn, topbar.firstChild);
        if (typeof cmsIsDarkSurface === 'function' && cmsIsDarkSurface(topbar)) {
            btn.setAttribute('data-surface', 'dark');
        }
    }

    // Reuse the existing retail overlay if the page already has one.
    if (!document.getElementById('cms-sidebar-overlay') && !document.getElementById('sidebarOverlay')) {
        const ov = document.createElement('div');
        ov.id = 'cms-sidebar-overlay';
        ov.className = 'cms-sidebar-overlay';
        ov.addEventListener('click', () => cmsToggleSidebar(false));
        document.body.appendChild(ov);
    }

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && sidebar.classList.contains('open')) cmsToggleSidebar(false);
    });

    // Keep aria-expanded truthful even when the page's own toggle ran.
    new MutationObserver(() => {
        const open = sidebar.classList.contains('open');
        document.querySelectorAll('.rt-menu-btn, .cms-menu-btn').forEach((b) => {
            b.setAttribute('aria-expanded', String(open));
        });
    }).observe(sidebar, { attributes: true, attributeFilter: ['class'] });
}

/* ── Label ↔ field association ────────────────────────────────────────────── */
/* The pages use <div class="form-group"><label>X</label><input id="y"></div>
   without a for= attribute, so labels were neither clickable nor announced.
   Linking them here fixes every page at once and keeps working as pages change. */

let _cmsAutoId = 0;

function linkOrphanLabels(root) {
    const scope = root || document;
    scope.querySelectorAll('label:not([for])').forEach((label) => {
        if (label.dataset.cmsLinked) return;
        // A label that already wraps its control needs no for=.
        if (label.querySelector('input, select, textarea')) return;

        const container = label.parentElement;
        if (!container) return;
        const field = container.querySelector('input:not([type="hidden"]), select, textarea');
        if (!field) return;

        // Don't hijack a field that another label already owns.
        if (field.id && scope.querySelector('label[for="' + CSS.escape(field.id) + '"]')) return;

        if (!field.id) field.id = 'cms-field-' + (++_cmsAutoId);
        label.setAttribute('for', field.id);
        label.dataset.cmsLinked = '1';
    });
}

/* Inputs with no label at all (table cells, toolbars) fall back to their
   placeholder so they are at least announced as something. */
function labelUnlabelledFields(root) {
    const scope = root || document;
    scope.querySelectorAll('input, select, textarea').forEach((el) => {
        if (el.type === 'hidden' || el.dataset.cmsNamed) return;
        if (el.getAttribute('aria-label') || el.getAttribute('aria-labelledby')) return;
        if (el.id && scope.querySelector('label[for="' + CSS.escape(el.id) + '"]')) return;
        if (el.closest('label')) return;
        const name = el.getAttribute('placeholder') || el.getAttribute('title');
        if (name) {
            el.setAttribute('aria-label', name);
            el.dataset.cmsNamed = '1';
        }
    });
}

/* ── Keyboard support for div-based controls ──────────────────────────────── */
/* Any element with onclick that isn't natively focusable gets button
   semantics and Enter/Space activation. */

function upgradeClickableElements(root) {
    const scope = root || document;
    scope.querySelectorAll('[onclick]').forEach((el) => {
        const tag = el.tagName;
        if (tag === 'BUTTON' || tag === 'A' || tag === 'INPUT' || tag === 'SELECT' || tag === 'TEXTAREA') return;
        if (el.dataset.cmsUpgraded) return;
        // Backdrops/overlays are decorative — Escape already closes them.
        if (/overlay|backdrop/i.test(el.className || '')) return;
        el.dataset.cmsUpgraded = '1';
        if (!el.hasAttribute('tabindex')) el.setAttribute('tabindex', '0');
        if (!el.hasAttribute('role')) el.setAttribute('role', 'button');
        el.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                el.click();
            }
        });
    });
}

/* ── Boot ─────────────────────────────────────────────────────────────────── */

document.addEventListener('DOMContentLoaded', () => {
    mountMobileNav();
    // Only pages that actually format money get the toggle.
    if (typeof money === 'function' || typeof formatCurrency === 'function') {
        if (document.querySelector('.rt-topbar-right, .wh-topbar-right, .user-section, .navbar-actions')) {
            mountCurrencyToggle();
        }
    }
    upgradeClickableElements();
    linkOrphanLabels();
    labelUnlabelledFields();

    // Keep the rate fresh, then let the page redraw with real numbers.
    if (typeof loadExchangeRate === 'function') {
        loadExchangeRate().then(() => {
            _cmsUpdateRateWarning();
            document.dispatchEvent(new CustomEvent('cms:currency-changed', { detail: { currency: getDisplayCurrency() } }));
            if (typeof calculateInvoice === 'function') calculateInvoice();
        });
    }

    // Rows and cards are rendered after load, so upgrade them as they appear.
    const mo = new MutationObserver((muts) => {
        muts.forEach((m) => m.addedNodes.forEach((n) => {
            if (n.nodeType !== 1) return;
            upgradeClickableElements(n);
            linkOrphanLabels(n);
            labelUnlabelledFields(n);
        }));
    });
    mo.observe(document.body, { childList: true, subtree: true });
});
