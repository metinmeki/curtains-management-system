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
    { name: 'مزهرية', unit: 'meter', price: 0, affectsSaleTotal: true },
    { name: 'حبال', unit: 'meter', price: 0, affectsSaleTotal: true }
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

function money(value) {
  return `${Number(value || 0).toLocaleString('en-US')} ${DEMO_CURRENCY}`;
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
