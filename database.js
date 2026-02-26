/**
 * E-PeriTech - Base de Datos (localStorage simulando backend)
 * Roles: admin, proveedor, comprador
 */

const DB = {

    // ─── KEYS ──────────────────────────────────────────────────────────
    KEYS: {
        USERS:       'eperitech_users',
        SESSION:     'eperitech_session',
        PRODUCTS:    'eperitech_products',
        ORDERS:      'eperitech_orders',
        CATALOG:     'eperitech_catalog_requests',
    },

    // ─── INIT ──────────────────────────────────────────────────────────
    init() {
        if (!localStorage.getItem(this.KEYS.USERS)) {
            this._seed();
        }
    },

    _seed() {
        const users = [
            // ADMIN
            {
                id: 'USR-ADMIN-001',
                role: 'admin',
                nombre: 'Administrador',
                apellido: 'E-PeriTech',
                email: 'admin@eperitech.com',
                password: this._hash('Admin2025!'),
                telefono: '+57 3185838072',
                createdAt: new Date('2024-01-01').toISOString(),
                activo: true,
                avatar: '👨‍💼',
            },
            // PROVEEDOR 1
            {
                id: 'USR-PROV-001',
                role: 'proveedor',
                nombre: 'Carlos',
                apellido: 'Ramírez',
                email: 'proveedor@techgear.com',
                password: this._hash('Prov2025!'),
                empresa: 'TechGear Pro',
                nit: '900.123.456-7',
                telefono: '+57 3001234567',
                direccion: 'Cra 5 # 10-20, Cúcuta',
                categorias: ['mouse', 'teclado'],
                createdAt: new Date('2024-02-15').toISOString(),
                activo: true,
                avatar: '🏭',
                verificado: true,
            },
            // PROVEEDOR 2
            {
                id: 'USR-PROV-002',
                role: 'proveedor',
                nombre: 'Luisa',
                apellido: 'Torres',
                email: 'proveedor@visionmax.com',
                password: this._hash('Prov2025!'),
                empresa: 'VisionMax',
                nit: '800.987.654-3',
                telefono: '+57 3109876543',
                direccion: 'Cl 12 # 3-45, Bogotá',
                categorias: ['monitor'],
                createdAt: new Date('2024-03-10').toISOString(),
                activo: true,
                avatar: '🏭',
                verificado: true,
            },
            // COMPRADOR 1
            {
                id: 'USR-COMP-001',
                role: 'comprador',
                nombre: 'Andrés',
                apellido: 'Morales',
                email: 'andres@gmail.com',
                password: this._hash('User2025!'),
                telefono: '+57 3156789012',
                direccion: 'Cl 7 # 2-10, Cúcuta',
                createdAt: new Date('2024-06-01').toISOString(),
                activo: true,
                avatar: '👤',
                wishlist: [1, 3],
                historialCompras: [],
            },
        ];

        localStorage.setItem(this.KEYS.USERS, JSON.stringify(users));

        // Órdenes iniciales de ejemplo
        const orders = [
            {
                id: 'ORD-001',
                compradorId: 'USR-COMP-001',
                productos: [{ productoId: 1, nombre: 'Mouse Gamer Inalámbrico RGB Pro', precio: 319000, cantidad: 1 }],
                total: 319000,
                estado: 'entregado',
                fecha: new Date('2025-03-15').toISOString(),
            },
        ];
        localStorage.setItem(this.KEYS.ORDERS, JSON.stringify(orders));
    },

    // ─── HASH SIMPLE (no usar en producción real) ──────────────────────
    _hash(str) {
        let h = 0;
        for (let i = 0; i < str.length; i++) {
            h = ((h << 5) - h) + str.charCodeAt(i);
            h |= 0;
        }
        return 'H' + Math.abs(h).toString(16).toUpperCase();
    },

    // ─── USUARIOS ──────────────────────────────────────────────────────
    getUsers()       { return JSON.parse(localStorage.getItem(this.KEYS.USERS) || '[]'); },
    saveUsers(users) { localStorage.setItem(this.KEYS.USERS, JSON.stringify(users)); },

    getUserById(id)    { return this.getUsers().find(u => u.id === id) || null; },
    getUserByEmail(em) { return this.getUsers().find(u => u.email === em.toLowerCase().trim()) || null; },

    createUser(data) {
        const users = this.getUsers();
        if (users.find(u => u.email === data.email.toLowerCase().trim())) {
            return { ok: false, msg: 'El correo ya está registrado.' };
        }

        const role  = data.role || 'comprador';
        const newId = `USR-${role.toUpperCase().slice(0,4)}-${String(Date.now()).slice(-5)}`;

        const user = {
            id:        newId,
            role,
            nombre:    data.nombre.trim(),
            apellido:  data.apellido.trim(),
            email:     data.email.toLowerCase().trim(),
            password:  this._hash(data.password),
            telefono:  data.telefono || '',
            createdAt: new Date().toISOString(),
            activo:    true,
            avatar:    role === 'proveedor' ? '🏭' : '👤',
            // extras proveedor
            ...(role === 'proveedor' ? {
                empresa:    data.empresa || '',
                nit:        data.nit || '',
                categorias: data.categorias || [],
                verificado: false,
            } : {}),
            // extras comprador
            ...(role === 'comprador' ? {
                direccion:       data.direccion || '',
                wishlist:        [],
                historialCompras:[],
            } : {}),
        };

        users.push(user);
        this.saveUsers(users);
        return { ok: true, user };
    },

    updateUser(id, changes) {
        const users = this.getUsers();
        const idx   = users.findIndex(u => u.id === id);
        if (idx === -1) return { ok: false, msg: 'Usuario no encontrado.' };
        if (changes.password) changes.password = this._hash(changes.password);
        users[idx] = { ...users[idx], ...changes, id, role: users[idx].role };
        this.saveUsers(users);
        return { ok: true, user: users[idx] };
    },

    deleteUser(id) {
        const users = this.getUsers().filter(u => u.id !== id);
        this.saveUsers(users);
        return { ok: true };
    },

    getUsersByRole(role) { return this.getUsers().filter(u => u.role === role); },

    // ─── AUTH ──────────────────────────────────────────────────────────
    login(email, password) {
        const user = this.getUserByEmail(email);
        if (!user)                           return { ok: false, msg: 'Correo no registrado.' };
        if (!user.activo)                    return { ok: false, msg: 'Cuenta desactivada.' };
        if (user.password !== this._hash(password)) return { ok: false, msg: 'Contraseña incorrecta.' };

        const session = {
            userId:    user.id,
            role:      user.role,
            nombre:    user.nombre,
            email:     user.email,
            avatar:    user.avatar,
            loginAt:   new Date().toISOString(),
        };
        localStorage.setItem(this.KEYS.SESSION, JSON.stringify(session));
        return { ok: true, user, session };
    },

    logout() {
        localStorage.removeItem(this.KEYS.SESSION);
    },

    getSession() {
        return JSON.parse(localStorage.getItem(this.KEYS.SESSION) || 'null');
    },

    isLoggedIn()   { return !!this.getSession(); },
    isAdmin()      { return this.getSession()?.role === 'admin'; },
    isProveedor()  { return this.getSession()?.role === 'proveedor'; },
    isComprador()  { return this.getSession()?.role === 'comprador'; },

    requireRole(role, redirect = 'login.html') {
        const s = this.getSession();
        if (!s || s.role !== role) { window.location.href = redirect; return false; }
        return true;
    },

    requireAuth(redirect = 'login.html') {
        if (!this.isLoggedIn()) { window.location.href = redirect; return false; }
        return true;
    },

    // ─── ÓRDENES ──────────────────────────────────────────────────────
    getOrders()       { return JSON.parse(localStorage.getItem(this.KEYS.ORDERS) || '[]'); },
    saveOrders(o)     { localStorage.setItem(this.KEYS.ORDERS, JSON.stringify(o)); },

    getOrdersByUser(userId)  { return this.getOrders().filter(o => o.compradorId === userId); },

    createOrder(compradorId, productos, total) {
        const orders = this.getOrders();
        const order  = {
            id:          `ORD-${Date.now()}`,
            compradorId,
            productos,
            total,
            estado:      'pendiente',
            fecha:       new Date().toISOString(),
        };
        orders.push(order);
        this.saveOrders(orders);
        return { ok: true, order };
    },

    updateOrderStatus(orderId, estado) {
        const orders = this.getOrders();
        const idx    = orders.findIndex(o => o.id === orderId);
        if (idx === -1) return { ok: false };
        orders[idx].estado = estado;
        this.saveOrders(orders);
        return { ok: true };
    },

    // ─── STATS (admin) ─────────────────────────────────────────────────
    getStats() {
        const users   = this.getUsers();
        const orders  = this.getOrders();
        return {
            totalUsuarios:    users.length,
            totalCompradores: users.filter(u => u.role === 'comprador').length,
            totalProveedores: users.filter(u => u.role === 'proveedor').length,
            totalAdmins:      users.filter(u => u.role === 'admin').length,
            totalOrdenes:     orders.length,
            ordenesPendientes:orders.filter(o => o.estado === 'pendiente').length,
            ingresoTotal:     orders.reduce((s, o) => s + (o.total || 0), 0),
        };
    },
};

// Auto-init
DB.init();