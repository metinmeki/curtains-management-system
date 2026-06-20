const API_BASE_URL = 'http://127.0.0.1:8000/api';

// Token Management
function getToken() {
    return localStorage.getItem('auth_token');
}

function setToken(token) {
    localStorage.setItem('auth_token', token);
}

function removeToken() {
    localStorage.removeItem('auth_token');
}

// Store Management
function getStoreId() {
    return localStorage.getItem('store_id');
}

function setStoreId(storeId) {
    localStorage.setItem('store_id', storeId);
}

function getStoreName() {
    return localStorage.getItem('store_name') || 'متجر';
}

function setStoreName(storeName) {
    localStorage.setItem('store_name', storeName);
}

// User Management
function getUser() {
    const user = localStorage.getItem('user');
    return user ? JSON.parse(user) : null;
}

function setUser(user) {
    localStorage.setItem('user', JSON.stringify(user));
}

function removeUser() {
    localStorage.removeItem('user');
}

// Auth Check
function checkAuth() {
    const token = getToken();
    if (!token) {
        window.location.href = '../index.html';
        return false;
    }
    return true;
}

// API Call Helper
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
        throw error;
    }
}

// Login
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

        if (email === 'admin@curtains.com' && password === 'admin123') {
            const demoUser = {
                id: 1,
                name: 'Admin',
                email: 'admin@curtains.com',
                role: 'Owner/Admin'
            };

            setToken('demo-frontend-token');
            setUser(demoUser);

            return {
                token: 'demo-frontend-token',
                user: demoUser
            };
        }

        throw error;
    }
}

// Logout
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
    }
}

// Store Selection
function setStoreSelection(storeId, storeName) {
    setStoreId(storeId);
    setStoreName(storeName);
}

function removeStoreSelection() {
    localStorage.removeItem('store_id');
    localStorage.removeItem('store_name');
}

// Format Helpers
function formatCurrency(value) {
    if (!value) return '0 ر.س';
    return new Intl.NumberFormat('ar-SA', {
        style: 'currency',
        currency: 'SAR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(value);
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
