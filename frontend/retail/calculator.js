(function () {
    'use strict';

    /* ── State ── */
    var display = '0';
    var pendingOp = null;
    var pendingVal = null;
    var waitingForOperand = false;
    var justEvaluated = false;

    /* ── Inject modal + button ── */
    document.addEventListener('DOMContentLoaded', function () {
        document.body.insertAdjacentHTML('beforeend', [
            '<div id="calcOverlay" class="calc-overlay" onclick="if(event.target===this)closeCalc()">',
            '  <div class="calc-modal" role="dialog" aria-modal="true" aria-label="Calculator">',
            '    <div class="calc-header">',
            '      <span class="calc-title">Calculator</span>',
            '      <button class="calc-close-btn" onclick="closeCalc()" aria-label="Close">&times;</button>',
            '    </div>',
            '    <div class="calc-display-wrap">',
            '      <div id="calcExpr" class="calc-expr"></div>',
            '      <div id="calcDisplay" class="calc-display">0</div>',
            '    </div>',
            '    <div class="calc-grid">',
            '      <button class="calc-btn calc-fn" onclick="calcClear()">C</button>',
            '      <button class="calc-btn calc-fn" onclick="calcToggleSign()">+/&minus;</button>',
            '      <button class="calc-btn calc-fn" onclick="calcPercent()">%</button>',
            '      <button class="calc-btn calc-op" onclick="calcOp(\'/\')">&#247;</button>',

            '      <button class="calc-btn" onclick="calcDigit(\'7\')">7</button>',
            '      <button class="calc-btn" onclick="calcDigit(\'8\')">8</button>',
            '      <button class="calc-btn" onclick="calcDigit(\'9\')">9</button>',
            '      <button class="calc-btn calc-op" onclick="calcOp(\'*\')">&#215;</button>',

            '      <button class="calc-btn" onclick="calcDigit(\'4\')">4</button>',
            '      <button class="calc-btn" onclick="calcDigit(\'5\')">5</button>',
            '      <button class="calc-btn" onclick="calcDigit(\'6\')">6</button>',
            '      <button class="calc-btn calc-op" onclick="calcOp(\'-\')">&#8722;</button>',

            '      <button class="calc-btn" onclick="calcDigit(\'1\')">1</button>',
            '      <button class="calc-btn" onclick="calcDigit(\'2\')">2</button>',
            '      <button class="calc-btn" onclick="calcDigit(\'3\')">3</button>',
            '      <button class="calc-btn calc-op" onclick="calcOp(\'+\')">+</button>',

            '      <button class="calc-btn calc-zero" onclick="calcDigit(\'0\')">0</button>',
            '      <button class="calc-btn" onclick="calcDot()">.</button>',
            '      <button class="calc-btn calc-eq" onclick="calcEquals()">=</button>',
            '    </div>',
            '  </div>',
            '</div>'
        ].join(''));

        /* Add button to topbar */
        var right = document.querySelector('.rt-topbar-right');
        if (right) {
            var btn = document.createElement('button');
            btn.className = 'calc-topbar-btn';
            btn.title = 'Calculator';
            btn.setAttribute('aria-label', 'Open calculator');
            btn.innerHTML = '&#x1F9EE;';
            btn.addEventListener('click', openCalc);
            right.insertBefore(btn, right.firstChild);
        }

        /* Keyboard support */
        document.addEventListener('keydown', function (e) {
            if (!document.getElementById('calcOverlay').classList.contains('open')) return;
            var k = e.key;
            if (k >= '0' && k <= '9') { calcDigit(k); return; }
            if (k === '.') { calcDot(); return; }
            if (k === '+') { calcOp('+'); return; }
            if (k === '-') { calcOp('-'); return; }
            if (k === '*') { calcOp('*'); return; }
            if (k === '/') { e.preventDefault(); calcOp('/'); return; }
            if (k === '%') { calcPercent(); return; }
            if (k === 'Enter' || k === '=') { calcEquals(); return; }
            if (k === 'Backspace') { calcBackspace(); return; }
            if (k === 'Escape') { closeCalc(); return; }
            if (k === 'c' || k === 'C') { calcClear(); }
        });
    });

    /* ── Public API (on window so onclick="" works) ── */
    window.openCalc = function () {
        document.getElementById('calcOverlay').classList.add('open');
    };
    window.closeCalc = function () {
        document.getElementById('calcOverlay').classList.remove('open');
    };

    function updateDisplay() {
        document.getElementById('calcDisplay').textContent = display;
    }

    function formatResult(n) {
        /* Avoid floating-point noise like 0.1+0.2=0.30000000000000004 */
        var s = parseFloat(n.toPrecision(12)).toString();
        return s;
    }

    window.calcDigit = function (d) {
        if (justEvaluated) { display = d; justEvaluated = false; waitingForOperand = false; }
        else if (waitingForOperand) { display = d; waitingForOperand = false; }
        else { display = display === '0' ? d : display + d; }
        updateDisplay();
    };

    window.calcDot = function () {
        if (justEvaluated || waitingForOperand) { display = '0.'; justEvaluated = false; waitingForOperand = false; }
        else if (display.indexOf('.') === -1) { display += '.'; }
        updateDisplay();
    };

    window.calcOp = function (op) {
        var val = parseFloat(display);
        if (pendingOp && !waitingForOperand) {
            val = applyOp(pendingVal, pendingOp, val);
            display = formatResult(val);
            updateDisplay();
        } else {
            val = parseFloat(display);
        }
        pendingVal = val;
        pendingOp = op;
        waitingForOperand = true;
        justEvaluated = false;
        var sym = { '+': '+', '-': '−', '*': '×', '/': '÷' }[op];
        document.getElementById('calcExpr').textContent = formatResult(val) + ' ' + sym;
    };

    window.calcEquals = function () {
        if (pendingOp === null) return;
        var right = parseFloat(display);
        var result = applyOp(pendingVal, pendingOp, right);
        document.getElementById('calcExpr').textContent = '';
        pendingOp = null;
        pendingVal = null;
        display = formatResult(result);
        waitingForOperand = false;
        justEvaluated = true;
        updateDisplay();
    };

    window.calcClear = function () {
        display = '0';
        pendingOp = null;
        pendingVal = null;
        waitingForOperand = false;
        justEvaluated = false;
        document.getElementById('calcExpr').textContent = '';
        updateDisplay();
    };

    window.calcToggleSign = function () {
        var v = parseFloat(display);
        if (v === 0) return;
        display = formatResult(-v);
        updateDisplay();
    };

    window.calcPercent = function () {
        var v = parseFloat(display);
        display = formatResult(v / 100);
        updateDisplay();
    };

    window.calcBackspace = function () {
        if (justEvaluated || display === '0') { display = '0'; }
        else if (display.length <= 1 || (display.length === 2 && display[0] === '-')) { display = '0'; }
        else { display = display.slice(0, -1); }
        updateDisplay();
    };

    function applyOp(a, op, b) {
        switch (op) {
            case '+': return a + b;
            case '-': return a - b;
            case '*': return a * b;
            case '/': return b !== 0 ? a / b : 0;
        }
        return b;
    }
}());
