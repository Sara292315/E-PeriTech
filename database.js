/**
 * E-PeriTech — database.js v2 (PHP/MariaDB)
 * Todas las funciones son async/await.
 */

const DB = {

    BASE: './api',

    async _get(endpoint, action, params = {}) {
        const qs  = new URLSearchParams({ action, ...params }).toString();
        const res = await fetch(`${this.BASE}/${endpoint}.php?${qs}`);
        return res.json();
    },

    async _post(endpoint, action, body = {}) {
        const res = await fetch(`${this.BASE}/${endpoint}.php?action=${action}`, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify(body),
        });
        return res.json();
    },

    getSession() {
        try { return JSON.parse(sessionStorage.getItem('eperitech_session') || 'null'); }
        catch { return null; }
    },

    isLoggedIn()  { return !!this.getSession(); },
    isAdmin()     { return this.getSession()?.role === 'admin'; },
    isProveedor() { return this.getSession()?.role === 'proveedor'; },
    isComprador() { return this.getSession()?.role === 'comprador'; },

    requireRole(role, redirect = 'login.html') {
        const s = this.getSession();
        if (!s || s.role !== role) { window.location.href = redirect; return false; }
        return true;
    },

    requireAuth(redirect = 'login.html') {
        if (!this.isLoggedIn()) { window.location.href = redirect; return false; }
        return true;
    },

    async login(email, password) {
        const res = await this._post('auth', 'login', { email, password });
        if (res.ok) sessionStorage.setItem('eperitech_session', JSON.stringify(res.session));
        return res;
    },

    async logout() {
        await this._post('auth', 'logout').catch(() => {});
        sessionStorage.removeItem('eperitech_session');
    },

    async createUser(data) { return this._post('auth', 'register', data); },

    async getUserById(id) {
        const res = await this._get('usuarios', 'get', { id });
        return res.ok ? res.user : null;
    },

    async getUsersByRole(role) {
        const res = await this._get('usuarios', 'list', { role });
        return res.ok ? res.data : [];
    },

    async updateUser(id, changes) { return this._post('usuarios', 'update', { id, ...changes }); },
    async deleteUser(id)          { return this._post('usuarios', 'delete', { id }); },

    async getStats() {
        const res = await this._get('usuarios', 'stats');
        return res.ok ? res.data : {};
    },

    async getProducts(soloActivos = true) {
        const res = await this._get('productos', 'list', { activos: soloActivos ? '1' : '0' });
        if (!res.ok) return [];
        return res.data.map(p => ({
            ...p,
            id:       parseInt(p.id),
            name:     p.nombre,
            category: p.categoria,
            price:    parseFloat(p.precio),
            oldPrice: p.precio_viejo ? parseFloat(p.precio_viejo) : null,
            discount: parseInt(p.descuento) || 0,
            icon:     p.icono,
            activo:   !!parseInt(p.activo),
        }));
    },

    async getProductsByProveedor(proveedorId) {
        const all = await this.getProducts(false);
        return all.filter(p => p.proveedor === proveedorId || p.proveedor_id === proveedorId);
    },

    async createProduct(data, proveedorId) { return this._post('productos', 'create', { ...data, proveedorId }); },
    async updateProduct(id, data)          { return this._post('productos', 'update', { id, ...data }); },
    async deleteProduct(id)                { return this._post('productos', 'delete', { id }); },

    async getOrders() {
        const res = await this._get('ordenes', 'list');
        return res.ok ? res.data : [];
    },

    async getOrdersByUser(userId) {
        const res = await this._get('ordenes', 'byUser', { usuario_id: userId });
        return res.ok ? res.data : [];
    },

    async createOrder(compradorId, productos, total) {
        return this._post('ordenes', 'create', { compradorId, productos, total });
    },

    async updateOrderStatus(orderId, estado) {
        return this._post('ordenes', 'updateStatus', { orderId, estado });
    },

    init() { console.log('✅ E-PeriTech conectado a MariaDB vía PHP'); },
};

DB.init();
