/**
 * E-PeriTech — database.js
 * ============================================================
 * --------------CLIENTE SERVIDOR-------------------------
 * GUIA #1 - Actividad 2: Capa de Presentacion y Logica
 * Este archivo es el puente entre la Capa de Presentacion
 * (HTML/CSS/JS del cliente) y la Capa de Logica de Negocio
 * (API PHP en el servidor Ubuntu). Implementa el modelo
 * de 3 capas definido en la Guia #1 Actividad 2.
 * --------------CLIENTE SERVIDOR-------------------------
 * GUIA #1 - Actividad 3: Procesos del Cliente
 * Captura de datos del usuario y envio al servidor antes
 * de la transmision. Ninguna logica critica se ejecuta
 * aqui — todo se delega al servidor via API.
 * --------------CLIENTE SERVIDOR-------------------------
 * GUIA #1 - Actividad 4: Payload de Comunicacion
 * Implementa el intercambio de datos en formato JSON
 * entre cliente y servidor (Request/Response) tal como
 * se definio en el diagrama de secuencia de la Guia #1.
 * --------------CLIENTE SERVIDOR-------------------------
 * GUIA #2 - Actividad 1: Middleware Web Services REST
 * Implementa el modelo de Web Services aplicado en
 * E-PeriTech: fetch() via HTTP, datos en JSON, REST.
 * Es la capa de transparencia de ubicacion del cliente:
 * el usuario no sabe si los datos vienen de la BD remota.
 * --------------CLIENTE SERVIDOR-------------------------
 * GUIA #2 - Actividad 3: Topologia Logica del MVP
 * BASE '../api' apunta a la Capa de Logica del servidor
 * Apache/PHP corriendo en Ubuntu (puerto 80/8080).
 * ============================================================
 */

const API = {
    BASE: '../api',   // Ruta relativa desde /views/ hacia /api/

    async _fetch(url, method = 'GET', body = null) {
        const opts = {
            method,
            headers: { 'Content-Type': 'application/json' },
        };
        if (body) opts.body = JSON.stringify(body);
        const res = await fetch(url, opts);
        
        // Verificar si la respuesta es exitosa
        if (!res.ok) {
            // Intentar leer el JSON del error si existe
            let errorData;
            try {
                const text = await res.text();
                errorData = text ? JSON.parse(text) : { msg: `Error ${res.status}: ${res.statusText}` };
            } catch {
                errorData = { msg: `Error ${res.status}: ${res.statusText}` };
            }
            return { ok: false, msg: errorData.msg || `Error del servidor (${res.status})`, data: null };
        }
        
        // Leer el JSON de la respuesta
        let data;
        try {
            const text = await res.text();
            data = text ? JSON.parse(text) : { ok: false, msg: 'Respuesta vacía del servidor' };
        } catch (e) {
            return { ok: false, msg: 'Error al procesar la respuesta del servidor. Asegúrate de usar Apache/XAMPP, no un servidor estático.', data: null };
        }
        
        return data;
    },

    // ── USUARIOS ──────────────────────────────────────────────────────

    async registrar(datos) {
        return this._fetch(`${this.BASE}/usuarios.php?action=registro`, 'POST', datos);
    },

    async login(email, password) {
        const res = await this._fetch(`${this.BASE}/usuarios.php?action=login`, 'POST', { email, password });
        if (res.ok && res.data) {
            localStorage.setItem('eperitech_session', JSON.stringify({
                userId:  res.data.id,
                rol:     res.data.rol,
                nombre:  res.data.nombre,
                email:   res.data.email,
                avatar:  res.data.avatar,
            }));
        }
        return res;
    },

    async logout() {
        localStorage.removeItem('eperitech_session');
        return this._fetch(`${this.BASE}/usuarios.php?action=logout`, 'POST');
    },

    async getSesion() {
        return this._fetch(`${this.BASE}/usuarios.php?action=sesion`);
    },

    getSession() {
        return JSON.parse(localStorage.getItem('eperitech_session') || 'null');
    },

    isLoggedIn()  { return !!this.getSession(); },
    isAdmin()     { return this.getSession()?.rol === 'admin'; },
    isProveedor() { return this.getSession()?.rol === 'proveedor'; },
    isComprador() { return this.getSession()?.rol === 'comprador'; },

    requireAuth(redirect = 'login.html') {
        if (!this.isLoggedIn()) { window.location.href = redirect; return false; }
        return true;
    },

    requireRole(rol, redirect = 'login.html') {
        const s = this.getSession();
        if (!s || s.rol !== rol) { window.location.href = redirect; return false; }
        return true;
    },

    async listarUsuarios(rol = '') {
        const qs = rol ? `&rol=${rol}` : '';
        return this._fetch(`${this.BASE}/usuarios.php?action=listar${qs}`);
    },

    async getUsuario(id) {
        return this._fetch(`${this.BASE}/usuarios.php?action=obtener&id=${id}`);
    },

    async actualizarUsuario(id, datos) {
        return this._fetch(`${this.BASE}/usuarios.php?action=actualizar&id=${id}`, 'PUT', datos);
    },

    async eliminarUsuario(id) {
        return this._fetch(`${this.BASE}/usuarios.php?action=eliminar&id=${id}`, 'DELETE');
    },

    // ── PRODUCTOS ─────────────────────────────────────────────────────

    async getProducts(categoria = '', proveedorId = '') {
        let qs = '';
        if (categoria)   qs += `&categoria=${categoria}`;
        if (proveedorId) qs += `&proveedor_id=${proveedorId}`;
        return this._fetch(`${this.BASE}/productos.php?action=listar${qs}`);
    },

    async getProduct(id) {
        return this._fetch(`${this.BASE}/productos.php?action=obtener&id=${id}`);
    },

    async createProduct(datos) {
        return this._fetch(`${this.BASE}/productos.php?action=crear`, 'POST', datos);
    },

    async updateProduct(id, datos) {
        return this._fetch(`${this.BASE}/productos.php?action=actualizar&id=${id}`, 'PUT', datos);
    },

    async deleteProduct(id) {
        return this._fetch(`${this.BASE}/productos.php?action=eliminar&id=${id}`, 'DELETE');
    },

    // ── ÓRDENES ───────────────────────────────────────────────────────

    async getOrders(compradorId = '') {
        const qs = compradorId ? `&comprador_id=${compradorId}` : '';
        return this._fetch(`${this.BASE}/ordenes.php?action=listar${qs}`);
    },

    async getOrder(id) {
        return this._fetch(`${this.BASE}/ordenes.php?action=obtener&id=${id}`);
    },

    async createOrder(compradorId, items) {
        return this._fetch(`${this.BASE}/ordenes.php?action=crear`, 'POST', { comprador_id: compradorId, items });
    },

    async updateOrderStatus(id, estado) {
        return this._fetch(`${this.BASE}/ordenes.php?action=estado&id=${id}`, 'PUT', { estado });
    },

    // ── CARRITO (localStorage — temporal pre-compra) ──────────────────

    getCart() {
        return JSON.parse(localStorage.getItem('eperitech_cart') || '[]');
    },
    saveCart(items) {
        localStorage.setItem('eperitech_cart', JSON.stringify(items));
    },
    addToCart(producto, cantidad = 1) {
        const cart = this.getCart();
        const idx  = cart.findIndex(i => i.producto_id === producto.id);
        if (idx >= 0) {
            cart[idx].cantidad += cantidad;
        } else {
            cart.push({
                producto_id: producto.id,
                nombre:      producto.nombre || producto.name,
                precio:      producto.precio || producto.price,
                cantidad,
            });
        }
        this.saveCart(cart);
        return cart;
    },
    removeFromCart(productoId) {
        this.saveCart(this.getCart().filter(i => i.producto_id !== productoId));
    },
    clearCart() {
        localStorage.removeItem('eperitech_cart');
    },
    getCartTotal() {
        return this.getCart().reduce((s, i) => s + i.precio * i.cantidad, 0);
    },
    getCartCount() {
        return this.getCart().reduce((s, i) => s + i.cantidad, 0);
    },
};

// Alias DB → API para compatibilidad con código existente
const DB = API;
