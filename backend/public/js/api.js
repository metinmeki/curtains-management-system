const API_BASE_URL = 'http://127.0.0.1:8000/api';

// ── Cookie helpers ────────────────────────────────────────────────────────────
function getCookie(name) {
    const match = document.cookie.match(new RegExp('(?:^|; )' + name.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '=([^;]*)'));
    return match ? decodeURIComponent(match[1]) : null;
}

function setCookie(name, value, days) {
    const d = new Date();
    d.setTime(d.getTime() + (days || 7) * 86400000);
    document.cookie = name + '=' + encodeURIComponent(value) + '; expires=' + d.toUTCString() + '; path=/; SameSite=Lax';
}

function deleteCookie(name) {
    document.cookie = name + '=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/; SameSite=Lax';
}

// ── Token Management ──────────────────────────────────────────────────────────
function getToken() {
    return localStorage.getItem('auth_token');
}

function setToken(token) {
    localStorage.setItem('auth_token', token);
}

function removeToken() {
    localStorage.removeItem('auth_token');
}

// ── Store Management ──────────────────────────────────────────────────────────
function getStoreId() {
    return localStorage.getItem('store_id');
}

function setStoreId(storeId) {
    localStorage.setItem('store_id', String(storeId));
}

function getStoreName() {
    return localStorage.getItem('store_name') || 'متجر';
}

function setStoreName(storeName) {
    localStorage.setItem('store_name', storeName);
}

// ── User Management ───────────────────────────────────────────────────────────
function getUser() {
    const user = localStorage.getItem('user');
    try { return user ? JSON.parse(user) : null; } catch(e) { return null; }
}

function setUser(user) {
    localStorage.setItem('user', JSON.stringify(user));
}

function removeUser() {
    localStorage.removeItem('user');
}

// ── Cashier Session ───────────────────────────────────────────────────────────
function getActiveCashier() {
    const raw = localStorage.getItem('cms_active_cashier');
    try { return raw ? JSON.parse(raw) : null; } catch(e) { return null; }
}

function setActiveCashier(cashier) {
    localStorage.setItem('cms_active_cashier', JSON.stringify(cashier));
}

function removeActiveCashier() {
    localStorage.removeItem('cms_active_cashier');
}

// ── Auth Check ────────────────────────────────────────────────────────────────
function checkAuth() {
    const token = getToken();
    if (!token) {
        window.location.href = '../index.html';
        return false;
    }
    return true;
}

// ── Toast Notifications ───────────────────────────────────────────────────────
// Lightweight, self-contained toast so save failures are never silent.
function showToast(message, type = 'info', duration) {
    if (typeof document === 'undefined' || !document.body) return;
    let container = document.getElementById('cms-toast-container');
    if (!container) {
        const style = document.createElement('style');
        style.textContent = '@keyframes cms-toast-in{from{opacity:0;transform:translateY(-8px)}to{opacity:1;transform:none}}';
        document.head.appendChild(style);
        container = document.createElement('div');
        container.id = 'cms-toast-container';
        const rtl = document.documentElement.getAttribute('dir') === 'rtl' || (document.body.classList && document.body.classList.contains('rtl'));
        container.style.cssText = 'position:fixed;top:16px;z-index:99999;display:flex;flex-direction:column;gap:8px;max-width:min(380px,90vw);' + (rtl ? 'left:16px;' : 'right:16px;');
        document.body.appendChild(container);
    }
    const palette = {
        error:   { bg: '#b91c1c', icon: '⚠️' },
        success: { bg: '#1a7d4b', icon: '✓' },
        info:    { bg: '#1a3d2b', icon: 'ℹ️' }
    };
    const c = palette[type] || palette.info;
    // Dedupe: if an identical message is already on screen, don't stack another.
    for (const existing of container.children) {
        if (existing.dataset && existing.dataset.msg === message) return;
    }
    const toast = document.createElement('div');
    toast.dataset.msg = message;
    toast.style.cssText = 'background:' + c.bg + ';color:#fff;padding:12px 16px;border-radius:8px;box-shadow:0 4px 16px rgba(0,0,0,.25);font-size:14px;font-weight:600;display:flex;align-items:flex-start;gap:10px;animation:cms-toast-in .2s ease;cursor:pointer;line-height:1.4;';
    const iconSpan = document.createElement('span');
    iconSpan.style.cssText = 'font-size:16px;flex-shrink:0;';
    iconSpan.textContent = c.icon;
    const msgSpan = document.createElement('span');
    msgSpan.textContent = message; // textContent = no HTML injection
    toast.appendChild(iconSpan);
    toast.appendChild(msgSpan);
    toast.onclick = () => toast.remove();
    container.appendChild(toast);
    const ms = duration || (type === 'error' ? 9000 : 3500);
    setTimeout(() => {
        toast.style.transition = 'opacity .3s';
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 300);
    }, ms);
}

// Standard "your save did not persist" message. Pass a short context label.
function showSaveError(context, detail) {
    console.error('Save failed' + (context ? ' [' + context + ']' : ''), detail || '');
    const what = context ? context + ' was not saved.' : 'Not saved.';
    showToast(what + ' The server rejected it or is unreachable — your change was NOT stored.', 'error');
}

// ── API Call Helper ───────────────────────────────────────────────────────────
async function apiCall(method, endpoint, data = null) {
    const token = getToken();

    if (!token) {
        removeToken();
        removeUser();
        window.location.href = '../index.html';
        return null;
    }

    const options = {
        method: method,
        headers: {
            'Authorization': `Bearer ${token}`,
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        }
    };

    if (data && (method === 'POST' || method === 'PUT' || method === 'PATCH')) {
        options.body = JSON.stringify(data);
    }

    try {
        const response = await fetch(`${API_BASE_URL}${endpoint}`, options);

        if (response.status === 401 || response.status === 403) {
            removeToken();
            removeUser();
            window.location.href = '../index.html';
            return null;
        }

        const result = await response.json();

        if (!response.ok) {
            throw new Error(result.message || 'API Error');
        }

        return result;
    } catch (error) {
        console.error('API Error:', error);
        // Surface write failures so a rejected save is never silent.
        if (method === 'POST' || method === 'PUT' || method === 'PATCH' || method === 'DELETE') {
            showSaveError(null, error);
        }
        throw error;
    }
}

// ── Login ─────────────────────────────────────────────────────────────────────
async function login(email, password) {
    try {
        const response = await fetch(`${API_BASE_URL}/auth/login`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ email, password })
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.message || 'Login failed');
        }

        setToken(data.data.token);
        setUser(data.data.user);

        return data.data;
    } catch (error) {
        console.error('Login error:', error);

        throw error;
    }
}

// ── Logout ────────────────────────────────────────────────────────────────────
async function logout() {
    try {
        const token = getToken();
        if (token) {
            await fetch(`${API_BASE_URL}/auth/logout`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'application/json'
                }
            });
        }
    } catch (error) {
        console.error('Logout error:', error);
    } finally {
        removeToken();
        removeUser();
        removeStoreSelection();
        removeActiveCashier();
    }
}

// ── Store Selection ───────────────────────────────────────────────────────────
function setStoreSelection(storeId, storeName) {
    setStoreId(storeId);
    setStoreName(storeName);
}

function removeStoreSelection() {
    localStorage.removeItem('store_id');
    localStorage.removeItem('store_name');
}

// ── Format Helpers ────────────────────────────────────────────────────────────
function formatCurrency(value) {
    if (!value) return '0 IQD';
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(value) + ' IQD';
}

function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('ar-SA');
}

function formatDateTime(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('ar-SA') + ' ' + date.toLocaleTimeString('ar-SA');
}
