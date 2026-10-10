/* Business calculator for the retail shell.
 *
 * Beyond a phone calculator it carries the three things counter staff actually
 * need: a visible tape so a total can be re-checked without redoing the sum,
 * memory keys for holding a running figure across entries, and a percent key
 * that behaves the way invoicing does (200 + 10% = 220, not 200.1).
 */
(function () {
    'use strict';

    var MAX_TAPE = 40;

    /* ── State ── */
    var entry = '0';            // raw digits the user typed, unformatted
    var pendingOp = null;
    var pendingVal = null;
    var waitingForOperand = false;
    var justEvaluated = false;
    var memory = 0;
    var errored = false;
    var tape = [];
    var lastFocus = null;

    /* ── Icons (inline SVG — glyph fonts render inconsistently across OSes) ── */
    var ICON = {
        calc: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><rect x="4" y="2" width="16" height="20" rx="2"/><path d="M8 6h8M8 11h.01M12 11h.01M16 11h.01M8 15h.01M12 15h.01M16 15h.01M8 19h4"/></svg>',
        close: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true" focusable="false"><path d="M6 6l12 12M18 6L6 18"/></svg>',
        tape: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M4 5h16v11a3 3 0 0 1-3 3H6a2 2 0 0 1-2-2z"/><path d="M8 9h8M8 13h5"/></svg>',
        back: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M21 5H9l-6 7 6 7h12a1 1 0 0 0 1-1V6a1 1 0 0 0-1-1z"/><path d="M17 9l-5 6M12 9l5 6"/></svg>',
        copy: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15V5a2 2 0 0 1 2-2h10"/></svg>',
        trash: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M4 7h16M10 11v6M14 11v6"/><path d="M6 7l1 13h10l1-13M9 7V4h6v3"/></svg>'
    };

    function t(s) { return typeof ta === 'function' ? ta(s) : s; }

    /* ── Number helpers ── */

    // Grouped for reading, but only the integer part — grouping a half-typed
    // decimal would fight the user mid-entry.
    function group(intPart) {
        return intPart.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    function formatEntry(raw) {
        if (raw === '' || raw === '-') return raw || '0';
        var neg = raw.charAt(0) === '-';
        var body = neg ? raw.slice(1) : raw;
        var bits = body.split('.');
        var out = group(bits[0] || '0') + (bits.length > 1 ? '.' + bits[1] : '');
        return (neg ? '-' : '') + out;
    }

    // toPrecision(12) keeps 0.1 + 0.2 from showing as 0.30000000000000004.
    function clean(n) {
        if (!isFinite(n)) return null;
        return parseFloat(n.toPrecision(12));
    }

    function formatResult(n) {
        var v = clean(n);
        if (v === null) return null;
        var s = Math.abs(v) >= 1e15 ? v.toExponential(6) : String(v);
        if (s.indexOf('e') !== -1) return s;
        var neg = s.charAt(0) === '-';
        var body = neg ? s.slice(1) : s;
        var bits = body.split('.');
        return (neg ? '-' : '') + group(bits[0]) + (bits[1] ? '.' + bits[1] : '');
    }

    function currentValue() { return parseFloat(entry) || 0; }

    function opSymbol(op) { return { '+': '+', '-': '−', '*': '×', '/': '÷' }[op]; }

    /* ── Rendering ── */

    function render() {
        var d = document.getElementById('calcDisplay');
        if (!d) return;
        d.textContent = errored ? entry : formatEntry(entry);
        d.classList.toggle('error', errored);
        document.getElementById('calcMemFlag').classList.toggle('on', memory !== 0);
        document.querySelectorAll('.calc-btn.calc-op').forEach(function (b) {
            b.classList.toggle('active', !errored && waitingForOperand && b.dataset.op === pendingOp);
        });
    }

    function renderExpr() {
        var e = document.getElementById('calcExpr');
        if (!e) return;
        e.textContent = (pendingOp === null || errored)
            ? ''
            : formatResult(pendingVal) + ' ' + opSymbol(pendingOp);
    }

    function renderTape() {
        var box = document.getElementById('calcTape');
        if (!box) return;
        if (!tape.length) {
            box.innerHTML = '<div class="calc-tape-empty">' + t('No calculations yet.') + '</div>';
            return;
        }
        box.innerHTML = tape.map(function (row, i) {
            return '<button type="button" class="calc-tape-row" data-tape="' + i + '">' +
                   '<span class="calc-tape-expr">' + row.expr + '</span>' +
                   '<span class="calc-tape-val">' + row.val + '</span></button>';
        }).join('');
    }

    function pushTape(expr, val) {
        tape.unshift({ expr: expr, val: val });
        if (tape.length > MAX_TAPE) tape.pop();
        renderTape();
    }

    function toast(msg) {
        var el = document.getElementById('calcToast');
        if (!el) return;
        el.textContent = msg;
        el.classList.add('show');
        clearTimeout(toast._t);
        toast._t = setTimeout(function () { el.classList.remove('show'); }, 1600);
    }

    function fail(msg) {
        errored = true;
        entry = msg;
        pendingOp = null;
        pendingVal = null;
        waitingForOperand = false;
        justEvaluated = false;
        renderExpr();
        render();
    }

    /* ── Arithmetic ── */

    function applyOp(a, op, b) {
        switch (op) {
            case '+': return a + b;
            case '-': return a - b;
            case '*': return a * b;
            case '/': return b === 0 ? null : a / b;
        }
        return b;
    }

    /* ── Public API (window, so inline onclick in the markup resolves) ── */

    window.calcDigit = function (d) {
        if (errored) window.calcClear();
        if (justEvaluated || waitingForOperand) {
            entry = d;
            waitingForOperand = false;
            justEvaluated = false;
        } else {
            entry = (entry === '0') ? d : entry + d;
        }
        render();
    };

    window.calcDot = function () {
        if (errored) window.calcClear();
        if (justEvaluated || waitingForOperand) {
            entry = '0.';
            waitingForOperand = false;
            justEvaluated = false;
        } else if (entry.indexOf('.') === -1) {
            entry += '.';
        }
        render();
    };

    window.calcOp = function (op) {
        if (errored) return;
        var val = currentValue();
        if (pendingOp !== null && !waitingForOperand) {
            var r = applyOp(pendingVal, pendingOp, val);
            if (r === null) return fail(t('Cannot divide by zero'));
            val = clean(r);
            entry = String(val);
        }
        pendingVal = val;
        pendingOp = op;
        waitingForOperand = true;
        justEvaluated = false;
        renderExpr();
        render();
    };

    window.calcEquals = function () {
        if (errored || pendingOp === null) return;
        var right = currentValue();
        var result = applyOp(pendingVal, pendingOp, right);
        if (result === null) return fail(t('Cannot divide by zero'));
        var expr = formatResult(pendingVal) + ' ' + opSymbol(pendingOp) + ' ' + formatResult(right);
        result = clean(result);
        pushTape(expr, formatResult(result));
        pendingOp = null;
        pendingVal = null;
        entry = String(result);
        waitingForOperand = false;
        justEvaluated = true;
        renderExpr();
        render();
    };

    /* Percent follows invoicing convention: with a pending + or -, the percent
       is taken of the first operand, so 200 + 10% is 220. Standalone it is a
       plain divide-by-100. */
    window.calcPercent = function () {
        if (errored) return;
        var v = currentValue();
        if (pendingOp === '+' || pendingOp === '-') {
            entry = String(clean(pendingVal * v / 100));
        } else {
            entry = String(clean(v / 100));
        }
        justEvaluated = false;
        render();
    };

    window.calcClear = function () {
        entry = '0';
        pendingOp = null;
        pendingVal = null;
        waitingForOperand = false;
        justEvaluated = false;
        errored = false;
        renderExpr();
        render();
    };

    window.calcToggleSign = function () {
        if (errored) return;
        if (entry === '0' || entry === '') return;
        entry = entry.charAt(0) === '-' ? entry.slice(1) : '-' + entry;
        render();
    };

    window.calcBackspace = function () {
        if (errored) return window.calcClear();
        if (justEvaluated || waitingForOperand) return;
        if (entry.length <= 1 || (entry.length === 2 && entry.charAt(0) === '-')) entry = '0';
        else entry = entry.slice(0, -1);
        render();
    };

    window.calcMem = function (action) {
        if (errored) return;
        if (action === 'c') { memory = 0; toast(t('Memory cleared')); }
        else if (action === 'r') { entry = String(memory); justEvaluated = true; waitingForOperand = false; }
        else if (action === '+') { memory = clean(memory + currentValue()); toast(t('Added to memory')); }
        else if (action === '-') { memory = clean(memory - currentValue()); toast(t('Subtracted from memory')); }
        render();
    };

    window.calcCopy = function () {
        if (errored) return;
        var text = formatEntry(entry).replace(/,/g, '');
        var done = function () { toast(t('Copied')); };
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(done, function () { toast(t('Copy failed')); });
        } else {
            // execCommand is deprecated but is the only fallback on http:// origins.
            var ta_ = document.createElement('textarea');
            ta_.value = text;
            ta_.style.cssText = 'position:fixed;opacity:0';
            document.body.appendChild(ta_);
            ta_.select();
            try { document.execCommand('copy'); done(); } catch (e) { toast(t('Copy failed')); }
            ta_.remove();
        }
    };

    window.calcToggleTape = function () {
        var box = document.getElementById('calcTape');
        var btn = document.getElementById('calcTapeBtn');
        var open = box.classList.toggle('open');
        btn.setAttribute('aria-pressed', String(open));
        btn.setAttribute('aria-label', open ? t('Hide tape') : t('Show tape'));
    };

    window.calcClearTape = function () {
        tape = [];
        renderTape();
        toast(t('Tape cleared'));
    };

    window.openCalc = function () {
        lastFocus = document.activeElement;
        document.getElementById('calcOverlay').classList.add('open');
        var first = document.querySelector('#calcOverlay .calc-hbtn');
        if (first) first.focus();
    };

    window.closeCalc = function () {
        document.getElementById('calcOverlay').classList.remove('open');
        if (lastFocus && lastFocus.focus) lastFocus.focus();
    };

    /* ── Mount ── */

    document.addEventListener('DOMContentLoaded', function () {
        var memRow = [
            ['c', 'MC'], ['r', 'MR'], ['+', 'M+'], ['-', 'M−']
        ].map(function (m) {
            return '<button type="button" class="calc-btn calc-mem" onclick="calcMem(\'' + m[0] + '\')">' + m[1] + '</button>';
        }).join('');

        document.body.insertAdjacentHTML('beforeend', [
            '<div id="calcOverlay" class="calc-overlay" onclick="if(event.target===this)closeCalc()">',
            '  <div class="calc-modal" role="dialog" aria-modal="true" aria-labelledby="calcTitle">',
            '    <div class="calc-header">',
            '      <span class="calc-title" id="calcTitle">' + t('Calculator') + '</span>',
            '      <span class="calc-memflag" id="calcMemFlag" aria-live="polite">M</span>',
            '      <button type="button" class="calc-hbtn" id="calcTapeBtn" aria-pressed="false" aria-label="' + t('Show tape') + '" onclick="calcToggleTape()">' + ICON.tape + '</button>',
            '      <button type="button" class="calc-hbtn" aria-label="' + t('Close') + '" onclick="closeCalc()">' + ICON.close + '</button>',
            '    </div>',
            '    <div class="calc-display-wrap">',
            '      <div id="calcExpr" class="calc-expr" aria-hidden="true"></div>',
            '      <div id="calcDisplay" class="calc-display" role="status" aria-live="polite">0</div>',
            '    </div>',
            '    <div id="calcTape" class="calc-tape"></div>',
            '    <div class="calc-grid">',
            memRow,
            '      <button type="button" class="calc-btn calc-fn" onclick="calcClear()">C</button>',
            '      <button type="button" class="calc-btn calc-fn" aria-label="' + t('Backspace') + '" onclick="calcBackspace()">' + ICON.back + '</button>',
            '      <button type="button" class="calc-btn calc-fn" onclick="calcPercent()">%</button>',
            '      <button type="button" class="calc-btn calc-op" data-op="/" aria-label="' + t('Divide') + '" onclick="calcOp(\'/\')">÷</button>',

            '      <button type="button" class="calc-btn" onclick="calcDigit(\'7\')">7</button>',
            '      <button type="button" class="calc-btn" onclick="calcDigit(\'8\')">8</button>',
            '      <button type="button" class="calc-btn" onclick="calcDigit(\'9\')">9</button>',
            '      <button type="button" class="calc-btn calc-op" data-op="*" aria-label="' + t('Multiply') + '" onclick="calcOp(\'*\')">×</button>',

            '      <button type="button" class="calc-btn" onclick="calcDigit(\'4\')">4</button>',
            '      <button type="button" class="calc-btn" onclick="calcDigit(\'5\')">5</button>',
            '      <button type="button" class="calc-btn" onclick="calcDigit(\'6\')">6</button>',
            '      <button type="button" class="calc-btn calc-op" data-op="-" aria-label="' + t('Subtract') + '" onclick="calcOp(\'-\')">−</button>',

            '      <button type="button" class="calc-btn" onclick="calcDigit(\'1\')">1</button>',
            '      <button type="button" class="calc-btn" onclick="calcDigit(\'2\')">2</button>',
            '      <button type="button" class="calc-btn" onclick="calcDigit(\'3\')">3</button>',
            '      <button type="button" class="calc-btn calc-op" data-op="+" aria-label="' + t('Plus') + '" onclick="calcOp(\'+\')">+</button>',

            '      <button type="button" class="calc-btn" onclick="calcToggleSign()">±</button>',
            '      <button type="button" class="calc-btn" onclick="calcDigit(\'0\')">0</button>',
            '      <button type="button" class="calc-btn" onclick="calcDot()">.</button>',
            '      <button type="button" class="calc-btn calc-eq" aria-label="' + t('Equals') + '" onclick="calcEquals()">=</button>',
            '    </div>',
            '    <div class="calc-foot">',
            '      <button type="button" onclick="calcCopy()">' + ICON.copy + '<span>' + t('Copy') + '</span></button>',
            '      <button type="button" onclick="calcClearTape()">' + ICON.trash + '<span>' + t('Clear tape') + '</span></button>',
            '    </div>',
            '    <div class="calc-toast" id="calcToast" role="status" aria-live="polite"></div>',
            '  </div>',
            '</div>'
        ].join(''));

        renderTape();

        /* Reuse a tape row as the current entry. */
        document.getElementById('calcTape').addEventListener('click', function (e) {
            var row = e.target.closest('[data-tape]');
            if (!row) return;
            entry = tape[Number(row.dataset.tape)].val.replace(/,/g, '');
            justEvaluated = true;
            waitingForOperand = false;
            errored = false;
            render();
        });

        /* Topbar launcher — uses the shared icon-button style so it lines up
           with the language and currency controls. */
        var host = document.querySelector('.rt-topbar-right') || document.querySelector('.wh-topbar-right');
        if (host) {
            if (typeof cmsEnsureControlStyles === 'function') cmsEnsureControlStyles();
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'cms-iconbtn no-print';
            btn.title = t('Calculator');
            btn.setAttribute('aria-label', t('Calculator'));
            btn.innerHTML = ICON.calc;
            btn.addEventListener('click', openCalc);
            host.insertBefore(btn, host.firstChild);
            if (typeof cmsIsDarkSurface === 'function' && cmsIsDarkSurface(host)) {
                btn.setAttribute('data-surface', 'dark');
            }
        }

        document.addEventListener('keydown', function (e) {
            var overlay = document.getElementById('calcOverlay');
            if (!overlay || !overlay.classList.contains('open')) return;
            var k = e.key;
            if (k >= '0' && k <= '9') { calcDigit(k); return; }
            if (k === '.' || k === ',') { calcDot(); return; }
            if (k === '+' || k === '-' || k === '*' || k === '/') { e.preventDefault(); calcOp(k); return; }
            if (k === '%') { calcPercent(); return; }
            if (k === 'Enter' || k === '=') { e.preventDefault(); calcEquals(); return; }
            if (k === 'Backspace') { e.preventDefault(); calcBackspace(); return; }
            if (k === 'Escape') { closeCalc(); return; }
            if (k === 'Delete' || k === 'c' || k === 'C') { calcClear(); }
        });
    });
}());
