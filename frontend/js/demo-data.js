const DEMO_CURRENCY = 'IQD';
const RETAIL_SALES_STORAGE_VERSION = 'v2';
const RETAIL_EXPENSES_STORAGE_VERSION = 'v1';
const RETAIL_ORDERS_STORAGE_VERSION = 'v1';

const retailDemo = {
  stats: {
    totalSales: 0,
    paidSales: 0,
    debtSales: 0,
    partialSales: 0,
    expenses: 0,
    discounts: 0,
    clients: 0
  },
  recentSales: [],
  debts: [],
  clients: [],
  expenses: [],
  materials: [
    { name: 'قماش', unit: 'meter', price: 0, affectsSaleTotal: true },
    { name: 'تول', unit: 'meter', price: 0, affectsSaleTotal: true },
    { name: 'بطانة', unit: 'meter', price: 0, affectsSaleTotal: true },
    { name: 'زبرا', unit: 'meter', price: 0, affectsSaleTotal: true },
    { name: 'خياطة', unit: 'meter', price: 1000, affectsSaleTotal: false, workerAccount: 'sewing' },
    { name: 'سكة', unit: 'meter', price: 0, affectsSaleTotal: true },
    { name: 'تركيب', unit: 'window', price: 10000, affectsSaleTotal: false, workerAccount: 'installation' },
    { name: 'مزهرية', unit: 'piece', price: 0, affectsSaleTotal: true },
    { name: 'حبال', unit: 'piece', price: 0, affectsSaleTotal: true }
  ],
  expenseCategories: ['Rent', 'Salaries', 'Delivery', 'Installation', 'Utilities', 'Repairs', 'Marketing', 'Other']
};

// ─── Retail Data — in-memory, loaded from DB on page start ───────────────────
let _storeSales    = [];
let _storeExpenses = [];
let _storeOrders   = [];

function getStoreSales()    { return _storeSales; }
function getStoreExpenses() { return _storeExpenses; }
function getStoreOrders()   { return _storeOrders; }

function saveStoreSale(sale) {
  _storeSales.unshift(sale);
  // API save handled by each page separately
}

function saveStoreExpense(expense) {
  _storeExpenses.unshift(expense);
}

function saveStoreOrder(order) {
  _storeOrders.unshift(order);
}

function updateStoreOrder(updatedOrder) {
  const idx = _storeOrders.findIndex(o => String(o.id) === String(updatedOrder.id));
  if (idx >= 0) _storeOrders[idx] = updatedOrder;
}

async function loadRetailDataFromDB(storeId) {
  const token = typeof getToken === 'function' ? getToken() : localStorage.getItem('auth_token');
  if (!token || token === 'demo-frontend-token' || !storeId) return;
  const h = { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json' };
  try {
    const [sRes, eRes, oRes] = await Promise.all([
      fetch(API_BASE_URL + '/retail/sales/'    + storeId, { headers: h }),
      fetch(API_BASE_URL + '/retail/expenses/' + storeId, { headers: h }),
      fetch(API_BASE_URL + '/retail/orders/'   + storeId, { headers: h }),
    ]);
    if (sRes.ok) { const r = await sRes.json(); if (Array.isArray(r.data)) _storeSales    = r.data; }
    if (eRes.ok) { const r = await eRes.json(); if (Array.isArray(r.data)) _storeExpenses = r.data; }
    if (oRes.ok) { const r = await oRes.json(); if (Array.isArray(r.data)) _storeOrders   = r.data; }
  } catch(e) {}
}

function getWorkerAccountTotals() {
  const sales = getStoreSales();
  const accounts = {
    sewing: { label: 'خياطة account', total: 0, rows: [] },
    installation: { label: 'تركيب account', total: 0, rows: [] }
  };

  sales.forEach(sale => {
    (sale.workerAccounts || []).forEach(row => {
      if (!accounts[row.account]) return;
      accounts[row.account].total += row.total;
      accounts[row.account].rows.push({
        saleId: sale.id,
        date: sale.date,
        clientName: sale.clientName || '-',
        material: row.material,
        quantity: row.quantity,
        unit: row.unit,
        price: row.price,
        total: row.total
      });
    });
  });

  return accounts;
}

const inventoryDemo = {
  stats: {
    sales: 0,
    paid: 0,
    debts: 0,
    balances: 0,
    clients: 0
  },
  categories: [],
  clients: []
};

function esc(v) {
  return String(v == null ? '' : v).replace(/[&<>"']/g, function(c) {
    return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
  });
}

function money(value) {
  return `${Math.round(Number(value || 0)).toLocaleString('en-US').replace(/,/g, '.')} ${DEMO_CURRENCY}`;
}

function statusBadge(status) {
  const tone = status === 'Paid' ? 'success' : status === 'Partial' ? 'warning' : 'danger';
  return `<span class="badge badge-${tone}">${status}</span>`;
}

function loadShell(sectionLabel) {
  const storeName = getStoreName ? getStoreName() : 'Store';
  const user = getUser ? getUser() : null;
  const sidebarStore = document.getElementById('sidebarStoreName');
  const userName = document.getElementById('userName');
  const avatar = document.getElementById('userAvatar');

  if (sidebarStore) sidebarStore.textContent = sectionLabel || storeName;
  if (userName) userName.textContent = user && user.name ? user.name : 'Admin';
  if (avatar) avatar.textContent = user && user.name ? user.name.charAt(0).toUpperCase() : 'A';
}

// ── Sidebar toggle ────────────────────────────────────────────────────────────
(function initSidebarToggle() {
  function setup() {
    const layout = document.querySelector('.layout');
    const navbar = document.querySelector('.navbar');
    if (!layout || !navbar || !document.querySelector('.sidebar')) return;

    // restore saved state
    if (localStorage.getItem('sidebar_collapsed') === '1') {
      layout.classList.add('sidebar-collapsed');
    }

    // backdrop for mobile drawer
    const backdrop = document.createElement('div');
    backdrop.className = 'sidebar-backdrop';
    backdrop.addEventListener('click', function () {
      closeDrawer();
    });
    layout.appendChild(backdrop);

    // inject button
    const btn = document.createElement('button');
    btn.className = 'sidebar-toggle-btn';
    btn.title = 'Toggle sidebar';
    btn.setAttribute('aria-label', 'Toggle sidebar');
    btn.textContent = '☰';
    function getSidebar() { return document.querySelector('.sidebar'); }

    function openDrawer() {
      layout.classList.add('sidebar-open');
      const s = getSidebar();
      if (s) s.style.left = '0';
    }

    function closeDrawer() {
      layout.classList.remove('sidebar-open');
      const s = getSidebar();
      if (s) s.style.left = '';
    }

    btn.addEventListener('click', function () {
      if (window.innerWidth <= 768) {
        layout.classList.contains('sidebar-open') ? closeDrawer() : openDrawer();
      } else {
        const now = layout.classList.toggle('sidebar-collapsed');
        localStorage.setItem('sidebar_collapsed', now ? '1' : '0');
      }
    });

    // Close mobile drawer on resize to desktop
    window.addEventListener('resize', function () {
      if (window.innerWidth > 768) closeDrawer();
    });

    navbar.insertBefore(btn, navbar.firstChild);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', setup);
  } else {
    setup();
  }
})();

// ── Dark mode toggle ──────────────────────────────────────────────────────────
(function initDarkMode() {
  function applyDark(on) {
    document.body.classList.toggle('dark', on);
    const btn = document.getElementById('darkModeBtn');
    if (btn) btn.textContent = on ? '☀️' : '🌙';
  }

  function setup() {
    const navbar = document.querySelector('.navbar');
    if (!navbar) return;

    const saved = localStorage.getItem('dark_mode') === '1';
    applyDark(saved);

    const btn = document.createElement('button');
    btn.id = 'darkModeBtn';
    btn.className = 'dark-mode-btn';
    btn.title = 'Toggle dark mode';
    btn.setAttribute('aria-label', 'Toggle dark mode');
    btn.textContent = saved ? '☀️' : '🌙';
    btn.addEventListener('click', function () {
      const on = !document.body.classList.contains('dark');
      applyDark(on);
      localStorage.setItem('dark_mode', on ? '1' : '0');
    });

    navbar.appendChild(btn);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', setup);
  } else {
    setup();
  }
})();

// ── Cashier mode restriction ──────────────────────────────────────────────────
(function initCashierMode() {
  function setup() {
    const raw = localStorage.getItem('cms_active_cashier');
    if (!raw) return;
    let cashier;
    try { cashier = JSON.parse(raw); } catch(e) { return; }

    // Hide all sidebar links except POS and Debts
    document.querySelectorAll('.sidebar .menu-item a, .sidebar li a').forEach(function(link) {
      const href = (link.getAttribute('href') || '').toLowerCase();
      const allowed = href.includes('pos.html') || href.includes('debts.html');
      if (!allowed) {
        const li = link.closest('li');
        if (li) li.style.display = 'none';
      }
    });

    // Show cashier badge in navbar and a logout button
    const navbar = document.querySelector('.navbar');
    if (navbar) {
      const badge = document.createElement('div');
      badge.className = 'cashier-mode-badge';
      const loginPath = (window.location.pathname.includes('/retail/') || window.location.pathname.includes('/inventory/')) ? '../index.html' : 'index.html';
      badge.innerHTML = `<span>👤 ${cashier.name}</span><button onclick="localStorage.removeItem('cms_active_cashier');localStorage.removeItem('auth_token');localStorage.removeItem('user');window.location.href='${loginPath}'">✕ Exit</button>`;
      navbar.appendChild(badge);
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', setup);
  } else {
    setup();
  }
})();

// ─── Variant Inventory Data Model (DB-backed, no localStorage) ───────────────

// In-memory state — populated by loadInvFromDB() on page start
let _invCategories = [];
let _invItems      = [];
let _invVariants   = [];
let _invStock      = {}; // { [storeId]: { [variantId]: qty } }
let _invMovements  = {}; // { [storeId]: [...] }

function _invApiHeaders() {
  const token = typeof getToken === 'function' ? getToken() : null;
  return { 'Authorization': 'Bearer ' + token, 'Content-Type': 'application/json', 'Accept': 'application/json' };
}
function _invStoreId() { return typeof getStoreId === 'function' ? getStoreId() : null; }
function _invToken()   { const t = typeof getToken === 'function' ? getToken() : null; return t && t !== 'demo-frontend-token' ? t : null; }

function getInvCategories() { return _invCategories; }
function saveInvCategories(arr) {
  _invCategories = arr;
  const sid = _invStoreId(), tok = _invToken();
  if (tok && sid) fetch(API_BASE_URL + '/retail/kv/' + sid + '/categories', { method: 'POST', headers: _invApiHeaders(), body: JSON.stringify(arr) }).catch(() => {});
}

function getInvItems() { return _invItems; }
function saveInvItems(arr) {
  _invItems = arr;
  const sid = _invStoreId(), tok = _invToken();
  if (tok && sid) fetch(API_BASE_URL + '/retail/kv/' + sid + '/items', { method: 'POST', headers: _invApiHeaders(), body: JSON.stringify(arr) }).catch(() => {});
}

function getInvVariants() { return _invVariants; }
function saveInvVariants(arr) {
  _invVariants = arr;
  const sid = _invStoreId(), tok = _invToken();
  if (tok && sid) fetch(API_BASE_URL + '/retail/kv/' + sid + '/variants', { method: 'POST', headers: _invApiHeaders(), body: JSON.stringify(arr) }).catch(() => {});
}

function findVariantByCode(code) {
  if (!code) return null;
  return _invVariants.find(v => v.code && v.code.toLowerCase() === String(code).toLowerCase()) || null;
}

function getStoreStock(storeId) { return _invStock[String(storeId)] || {}; }
function saveStoreStock(storeId, stock) {
  _invStock[String(storeId)] = stock;
  const tok = _invToken();
  if (tok && storeId) fetch(API_BASE_URL + '/retail/kv/' + storeId + '/stock', { method: 'POST', headers: _invApiHeaders(), body: JSON.stringify(stock) }).catch(() => {});
}

function getVariantQty(variantId, storeId) {
  return (getStoreStock(storeId))[String(variantId)] || 0;
}

function setVariantQty(variantId, qty, storeId) {
  const stock = getStoreStock(storeId);
  stock[String(variantId)] = Math.max(0, Number(qty) || 0);
  saveStoreStock(storeId, stock);
}

// Load retail inventory data from DB into memory on page start
async function loadInvFromDB(storeId, callback) {
  const tok = _invToken();
  if (!tok || !storeId) { if (callback) callback(); return; }
  try {
    const h = { 'Authorization': 'Bearer ' + tok, 'Accept': 'application/json' };
    const [vRes, iRes, sRes, cRes] = await Promise.all([
      fetch(API_BASE_URL + '/retail/kv/' + storeId + '/variants',   { headers: h }),
      fetch(API_BASE_URL + '/retail/kv/' + storeId + '/items',      { headers: h }),
      fetch(API_BASE_URL + '/retail/kv/' + storeId + '/stock',      { headers: h }),
      fetch(API_BASE_URL + '/retail/kv/' + storeId + '/categories', { headers: h }),
    ]);
    if (vRes.ok) { const r = await vRes.json(); if (Array.isArray(r.data)) _invVariants = r.data; }
    if (iRes.ok) { const r = await iRes.json(); if (Array.isArray(r.data)) _invItems = r.data; }
    if (sRes.ok) { const r = await sRes.json(); if (r.data && typeof r.data === 'object') _invStock[String(storeId)] = r.data; }
    if (cRes.ok) { const r = await cRes.json(); if (Array.isArray(r.data)) _invCategories = r.data; }
  } catch(e) {}
  if (callback) callback();
}

function getInvMovements(storeId) { return _invMovements[String(storeId)] || []; }

function addInvMovement(mov, storeId) {
  const sid = String(storeId);
  if (!_invMovements[sid]) _invMovements[sid] = [];
  _invMovements[sid].unshift({ id: Date.now() + Math.random(), date: new Date().toISOString(), ...mov });
  _invMovements[sid] = _invMovements[sid].slice(0, 1000);
}

function deductVariantStock(variantId, qty, storeId, reason, saleRef) {
  const stock = getStoreStock(storeId);
  const current = stock[String(variantId)] || 0;
  const next = Math.max(0, current - (Number(qty) || 0));
  stock[String(variantId)] = next;
  saveStoreStock(storeId, stock);

  const variant = getInvVariants().find(v => String(v.id) === String(variantId));
  addInvMovement({
    variantId: String(variantId),
    variantCode: variant ? variant.code : '',
    variantName: variant ? variant.name : '',
    type: 'sale',
    delta: -(Number(qty) || 0),
    prevQty: current,
    nextQty: next,
    reason: reason || 'Sale',
    ref: saleRef || ''
  }, storeId);
}



// Page transition overlay
(function() {
    const style = document.createElement('style');
    style.textContent = `
        #page-transition-overlay {
            position: fixed; inset: 0; z-index: 99999;
            background: linear-gradient(135deg, #1a3d2e 0%, #2f7d5b 50%, #214238 100%);
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            opacity: 0; pointer-events: none;
            transition: opacity 0.18s ease;
        }
        #page-transition-overlay.visible { opacity: 1; pointer-events: all; }
        #page-transition-overlay .brand-name {
            color: #f7f3e8; font-size: 2rem; font-weight: 800; letter-spacing: 2px;
            opacity: 0; transform: translateY(10px);
            transition: opacity 0.2s ease 0.1s, transform 0.2s ease 0.1s;
        }
        #page-transition-overlay.visible .brand-name { opacity: 1; transform: translateY(0); }
        #page-transition-overlay .brand-sub {
            color: rgba(247,243,232,0.55); font-size: 0.85rem; margin-top: 8px; letter-spacing: 4px;
            text-transform: uppercase;
        }
    `;
    document.head.appendChild(style);

    const overlay = document.createElement('div');
    overlay.id = 'page-transition-overlay';
    overlay.innerHTML = '<div class="brand-name">دلوفان پەردە</div><div class="brand-sub">Dilovan Parda</div>';
    document.body.appendChild(overlay);

    // Fade out overlay on page load
    requestAnimationFrame(() => {
        overlay.classList.remove('visible');
    });

    document.addEventListener('click', function(e) {
        const link = e.target.closest('a[href]');
        if (!link) return;
        const href = link.getAttribute('href');
        if (!href || href.startsWith('#') || href.startsWith('javascript') || link.target === '_blank') return;
        if (e.ctrlKey || e.metaKey || e.shiftKey) return;
        e.preventDefault();
        overlay.classList.add('visible');
        setTimeout(() => { window.location.href = href; }, 320);
    });
})();
