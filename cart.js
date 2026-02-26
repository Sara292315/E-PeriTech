/**
 * E-PeriTech - Sistema de Carrito de Compras
 * Vinculado a sesión de usuario (localStorage por userId)
 */

const Cart = {

    // ─── CLAVE DE STORAGE ──────────────────────────────────────────────
    _key() {
        const s = (typeof DB !== 'undefined') ? DB.getSession() : null;
        return s ? `eperitech_cart_${s.userId}` : 'eperitech_cart_guest';
    },

    // ─── CRUD ───────────────────────────────────────────────────────────
    getItems() {
        return JSON.parse(localStorage.getItem(this._key()) || '[]');
    },

    saveItems(items) {
        localStorage.setItem(this._key(), JSON.stringify(items));
        this.updateBadge();
        this.renderPanel();
    },

    addItem(product) {
        const items = this.getItems();
        const existing = items.find(i => i.id === product.id);
        if (existing) {
            existing.cantidad++;
        } else {
            items.push({ ...product, cantidad: 1 });
        }
        this.saveItems(items);
        this.showToast(`✅ ${product.name} agregado al carrito`);
    },

    removeItem(productId) {
        const items = this.getItems().filter(i => i.id !== productId);
        this.saveItems(items);
    },

    updateQty(productId, delta) {
        const items = this.getItems();
        const item = items.find(i => i.id === productId);
        if (!item) return;
        item.cantidad = Math.max(1, item.cantidad + delta);
        this.saveItems(items);
    },

    clear() {
        localStorage.removeItem(this._key());
        this.updateBadge();
        this.renderPanel();
    },

    getTotal() {
        return this.getItems().reduce((s, i) => s + i.price * i.cantidad, 0);
    },

    getCount() {
        return this.getItems().reduce((s, i) => s + i.cantidad, 0);
    },

    // ─── BADGE ──────────────────────────────────────────────────────────
    updateBadge() {
        const badge = document.getElementById('cartBadge');
        if (!badge) return;
        const count = this.getCount();
        badge.textContent = count;
        badge.style.display = count > 0 ? 'flex' : 'none';
    },

    // ─── TOAST ──────────────────────────────────────────────────────────
    showToast(msg) {
        let toast = document.getElementById('cartToast');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'cartToast';
            document.body.appendChild(toast);
        }
        toast.textContent = msg;
        toast.className = 'cart-toast show';
        clearTimeout(this._toastTimer);
        this._toastTimer = setTimeout(() => toast.classList.remove('show'), 2800);
    },

    // ─── PANEL LATERAL ──────────────────────────────────────────────────
    togglePanel() {
        const panel = document.getElementById('cartPanel');
        if (!panel) return;
        const isOpen = panel.classList.toggle('open');
        document.getElementById('cartOverlay')?.classList.toggle('open', isOpen);
        if (isOpen) this.renderPanel();
    },

    closePanel() {
        document.getElementById('cartPanel')?.classList.remove('open');
        document.getElementById('cartOverlay')?.classList.remove('open');
    },

    renderPanel() {
        const body = document.getElementById('cartBody');
        const footer = document.getElementById('cartFooter');
        if (!body) return;

        const items = this.getItems();

        if (items.length === 0) {
            body.innerHTML = `
                <div class="cart-empty">
                    <div style="font-size:64px;margin-bottom:12px;">🛒</div>
                    <p style="color:#888;font-size:15px;">Tu carrito está vacío</p>
                    <a href="index.html" onclick="Cart.closePanel()" style="color:#667eea;font-size:14px;text-decoration:none;">Ver productos →</a>
                </div>`;
            if (footer) footer.style.display = 'none';
            return;
        }

        const fmt = n => '$' + Number(n).toLocaleString('es-CO');

        body.innerHTML = items.map(item => `
            <div class="cart-item" id="ci-${item.id}">
                <div class="ci-icon">${item.icon || '📦'}</div>
                <div class="ci-info">
                    <div class="ci-name">${item.name}</div>
                    <div class="ci-price">${fmt(item.price)}</div>
                    <div class="ci-qty">
                        <button onclick="Cart.updateQty(${item.id},-1)">−</button>
                        <span>${item.cantidad}</span>
                        <button onclick="Cart.updateQty(${item.id},+1)">+</button>
                        <button class="ci-remove" onclick="Cart.removeItem(${item.id})">🗑️</button>
                    </div>
                </div>
                <div class="ci-subtotal">${fmt(item.price * item.cantidad)}</div>
            </div>`).join('');

        if (footer) {
            footer.style.display = 'block';
            document.getElementById('cartTotal').textContent = fmt(this.getTotal());
        }
    },

    // ─── CHECKOUT ───────────────────────────────────────────────────────
    checkout() {
        const session = (typeof DB !== 'undefined') ? DB.getSession() : null;
        if (!session) {
            this.closePanel();
            window.location.href = 'login.html';
            return;
        }
        const items = this.getItems();
        if (!items.length) return;

        // Crear orden
        const productos = items.map(i => ({
            productoId: i.id,
            nombre:     i.name,
            precio:     i.price,
            cantidad:   i.cantidad,
        }));
        const result = DB.createOrder(session.userId, productos, this.getTotal());

        if (result.ok) {
            this.clear();
            this.closePanel();
            this.showToast('🎉 ¡Compra realizada con éxito!');
            // Redirigir a mis compras tras 1.5s
            setTimeout(() => {
                window.location.href = 'micuenta.html';
            }, 1500);
        }
    },

    // ─── INIT ────────────────────────────────────────────────────────────
    init() {
        this._injectUI();
        this.updateBadge();
    },

    _injectUI() {
        // Estilos
        if (document.getElementById('cartStyles')) return;
        const style = document.createElement('style');
        style.id = 'cartStyles';
        style.textContent = `
            /* ── Botón carrito en nav ── */
            .cart-nav-btn { position: relative; }
            #cartBadge {
                position: absolute; top: -6px; right: -6px;
                background: #ef4444; color: #fff; font-size: 10px; font-weight: 700;
                min-width: 18px; height: 18px; border-radius: 50%;
                display: none; align-items: center; justify-content: center;
                padding: 0 3px;
            }

            /* ── Panel lateral ── */
            #cartOverlay {
                display: none; position: fixed; inset: 0;
                background: rgba(0,0,0,.45); z-index: 1200;
            }
            #cartOverlay.open { display: block; }

            #cartPanel {
                position: fixed; top: 0; right: -420px; width: 420px; max-width: 95vw;
                height: 100vh; background: #fff; z-index: 1300;
                display: flex; flex-direction: column;
                box-shadow: -4px 0 30px rgba(0,0,0,.15);
                transition: right .3s cubic-bezier(.4,0,.2,1);
                border-radius: 18px 0 0 18px;
            }
            #cartPanel.open { right: 0; }

            .cart-panel-header {
                display: flex; align-items: center; justify-content: space-between;
                padding: 20px 22px 16px;
                border-bottom: 1px solid #f0f0f0;
            }
            .cart-panel-header h2 { font-size: 18px; font-weight: 700; color: #1a1a2e; margin: 0; }
            .cart-close-btn {
                background: #f5f5f5; border: none; border-radius: 50%;
                width: 34px; height: 34px; font-size: 16px; cursor: pointer;
                display: flex; align-items: center; justify-content: center;
                transition: background .2s;
            }
            .cart-close-btn:hover { background: #e0e0e0; }

            #cartBody {
                flex: 1; overflow-y: auto; padding: 16px 22px;
                scrollbar-width: thin;
            }

            .cart-empty {
                display: flex; flex-direction: column; align-items: center;
                justify-content: center; height: 100%; text-align: center;
            }

            .cart-item {
                display: flex; gap: 12px; align-items: flex-start;
                padding: 14px 0; border-bottom: 1px solid #f5f5f5;
            }
            .ci-icon {
                font-size: 38px; width: 54px; height: 54px; background: #f8f9ff;
                border-radius: 12px; display: flex; align-items: center;
                justify-content: center; flex-shrink: 0;
            }
            .ci-info { flex: 1; min-width: 0; }
            .ci-name { font-size: 13px; font-weight: 600; color: #333;
                white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-bottom: 3px; }
            .ci-price { font-size: 13px; color: #667eea; font-weight: 600; margin-bottom: 8px; }
            .ci-qty { display: flex; align-items: center; gap: 6px; }
            .ci-qty button {
                width: 26px; height: 26px; border: 1.5px solid #e0e0e0; background: #fff;
                border-radius: 6px; cursor: pointer; font-size: 15px; font-weight: 700;
                display: flex; align-items: center; justify-content: center;
                transition: all .15s;
            }
            .ci-qty button:hover { background: #f0f2ff; border-color: #667eea; }
            .ci-qty span { font-size: 14px; font-weight: 700; min-width: 22px; text-align: center; }
            .ci-remove { background: #fff0f0 !important; border-color: #fecaca !important; font-size: 13px !important; }
            .ci-subtotal {
                font-size: 13px; font-weight: 700; color: #1a1a2e;
                white-space: nowrap; padding-top: 2px;
            }

            /* ── Footer checkout ── */
            #cartFooter {
                padding: 16px 22px 20px; border-top: 1px solid #f0f0f0;
                background: #fff;
            }
            .cart-total-row {
                display: flex; justify-content: space-between; align-items: center;
                margin-bottom: 14px;
            }
            .cart-total-row span:first-child { font-size: 15px; color: #666; }
            #cartTotal { font-size: 22px; font-weight: 800; color: #667eea; }
            .btn-checkout {
                width: 100%; padding: 14px;
                background: linear-gradient(135deg, #667eea, #764ba2);
                color: #fff; border: none; border-radius: 12px;
                font-size: 16px; font-weight: 700; cursor: pointer;
                transition: opacity .2s, transform .2s;
            }
            .btn-checkout:hover { opacity: .92; transform: translateY(-1px); }
            .btn-clear-cart {
                width: 100%; padding: 9px; margin-top: 8px;
                background: transparent; border: 1px solid #e0e0e0; border-radius: 10px;
                font-size: 13px; color: #999; cursor: pointer;
                transition: all .2s;
            }
            .btn-clear-cart:hover { background: #fef2f2; color: #ef4444; border-color: #fecaca; }

            /* ── Toast ── */
            .cart-toast {
                position: fixed; bottom: 28px; left: 50%; transform: translateX(-50%) translateY(20px);
                background: #1a1a2e; color: #fff; padding: 12px 22px; border-radius: 30px;
                font-size: 14px; font-weight: 600; z-index: 9999;
                opacity: 0; transition: opacity .3s, transform .3s; pointer-events: none;
                white-space: nowrap;
            }
            .cart-toast.show { opacity: 1; transform: translateX(-50%) translateY(0); }

            /* ── Botón "Agregar al carrito" en producto.html ── */
            .btn-add-to-cart-main {
                flex: 1;
                background: linear-gradient(135deg, #667eea, #764ba2);
                color: #fff; padding: 18px; border: none; border-radius: 12px;
                font-size: 18px; font-weight: 700; cursor: pointer;
                transition: transform .2s, box-shadow .2s;
            }
            .btn-add-to-cart-main:hover {
                transform: translateY(-3px);
                box-shadow: 0 10px 25px rgba(102,126,234,.3);
            }
        `;
        document.head.appendChild(style);

        // Overlay
        const overlay = document.createElement('div');
        overlay.id = 'cartOverlay';
        overlay.onclick = () => Cart.closePanel();
        document.body.appendChild(overlay);

        // Panel lateral
        const panel = document.createElement('div');
        panel.id = 'cartPanel';
        panel.innerHTML = `
            <div class="cart-panel-header">
                <h2>🛒 Mi Carrito</h2>
                <button class="cart-close-btn" onclick="Cart.closePanel()">✕</button>
            </div>
            <div id="cartBody"></div>
            <div id="cartFooter" style="display:none;">
                <div class="cart-total-row">
                    <span>Total</span>
                    <span id="cartTotal">$0</span>
                </div>
                <button class="btn-checkout" onclick="Cart.checkout()">🏷️ Finalizar compra</button>
                <button class="btn-clear-cart" onclick="Cart.clear()">Vaciar carrito</button>
            </div>
        `;
        document.body.appendChild(panel);

        // Inyectar botón carrito en nav-icons (si existe y aún no está)
        const navIcons = document.querySelector('.nav-icons');
        if (navIcons && !document.getElementById('cartNavBtn')) {
            const btn = document.createElement('button');
            btn.id = 'cartNavBtn';
            btn.className = 'icon-btn cart-nav-btn';
            btn.title = 'Mi carrito';
            btn.onclick = () => Cart.togglePanel();
            btn.innerHTML = `🛒<span id="cartBadge"></span>`;
            // Insertar después del primer botón (búsqueda)
            const firstBtn = navIcons.querySelector('.icon-btn');
            if (firstBtn && firstBtn.nextSibling) {
                navIcons.insertBefore(btn, firstBtn.nextSibling);
            } else {
                navIcons.appendChild(btn);
            }
        }
    }
};

// Auto-init cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => Cart.init());
