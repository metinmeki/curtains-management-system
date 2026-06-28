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

function getStoreSalesKey() {
  return `retail_sales_${RETAIL_SALES_STORAGE_VERSION}_store_${getStoreId() || 'demo'}`;
}

function getStoreSales() {
  return JSON.parse(localStorage.getItem(getStoreSalesKey()) || '[]');
}

function saveStoreSale(sale) {
  const key = getStoreSalesKey();
  const sales = JSON.parse(localStorage.getItem(key) || '[]');
  sales.unshift(sale);
  localStorage.setItem(key, JSON.stringify(sales));
}

function getStoreExpensesKey() {
  return `retail_expenses_${RETAIL_EXPENSES_STORAGE_VERSION}_store_${getStoreId() || 'demo'}`;
}

function getStoreExpenses() {
  return JSON.parse(localStorage.getItem(getStoreExpensesKey()) || '[]');
}

function saveStoreExpense(expense) {
  const key = getStoreExpensesKey();
  const expenses = JSON.parse(localStorage.getItem(key) || '[]');
  expenses.unshift(expense);
  localStorage.setItem(key, JSON.stringify(expenses));
}

function getStoreOrdersKey() {
  return `retail_orders_${RETAIL_ORDERS_STORAGE_VERSION}_store_${getStoreId() || 'demo'}`;
}

function getStoreOrders() {
  return JSON.parse(localStorage.getItem(getStoreOrdersKey()) || '[]');
}

function saveStoreOrder(order) {
  const key = getStoreOrdersKey();
  const orders = JSON.parse(localStorage.getItem(key) || '[]');
  orders.unshift(order);
  localStorage.setItem(key, JSON.stringify(orders));
}

function updateStoreOrder(updatedOrder) {
  const key = getStoreOrdersKey();
  const orders = JSON.parse(localStorage.getItem(key) || '[]');
  const nextOrders = orders.map(order => String(order.id) === String(updatedOrder.id) ? updatedOrder : order);
  localStorage.setItem(key, JSON.stringify(nextOrders));
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

// ─── Variant Inventory Data Model ────────────────────────────────────────────

function getInvCategories() {
  return JSON.parse(localStorage.getItem('inv_categories_v1') || '[]');
}
function saveInvCategories(arr) { localStorage.setItem('inv_categories_v1', JSON.stringify(arr)); }

function getInvItems() {
  return JSON.parse(localStorage.getItem('inv_items_v1') || '[]');
}
function saveInvItems(arr) { localStorage.setItem('inv_items_v1', JSON.stringify(arr)); }

function getInvVariants() {
  return JSON.parse(localStorage.getItem('inv_variants_v1') || '[]');
}
function saveInvVariants(arr) { localStorage.setItem('inv_variants_v1', JSON.stringify(arr)); }

function findVariantByCode(code) {
  if (!code) return null;
  return getInvVariants().find(v => v.code && v.code.toLowerCase() === String(code).toLowerCase()) || null;
}

function invStockKey(storeId) { return `inv_stock_v1_store_${storeId || 'demo'}`; }
function getStoreStock(storeId) { return JSON.parse(localStorage.getItem(invStockKey(storeId)) || '{}'); }
function saveStoreStock(storeId, stock) { localStorage.setItem(invStockKey(storeId), JSON.stringify(stock)); }

function getVariantQty(variantId, storeId) {
  return getStoreStock(storeId)[String(variantId)] || 0;
}

function setVariantQty(variantId, qty, storeId) {
  const stock = getStoreStock(storeId);
  stock[String(variantId)] = Math.max(0, Number(qty) || 0);
  saveStoreStock(storeId, stock);
}

function invMovementsKey(storeId) { return `inv_movements_v1_store_${storeId || 'demo'}`; }
function getInvMovements(storeId) { return JSON.parse(localStorage.getItem(invMovementsKey(storeId)) || '[]'); }

function addInvMovement(mov, storeId) {
  const key = invMovementsKey(storeId);
  const list = JSON.parse(localStorage.getItem(key) || '[]');
  list.unshift({ id: Date.now() + Math.random(), date: new Date().toISOString(), ...mov });
  localStorage.setItem(key, JSON.stringify(list.slice(0, 1000)));
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


