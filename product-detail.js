// product-detail.js — E-PeriTech (versión API/MariaDB)

function formatPrice(price) {
    return '$' + Number(price).toLocaleString('es-CO');
}

document.addEventListener('DOMContentLoaded', async function () {

    const storedId = localStorage.getItem('selectedProduct');
    if (!storedId || storedId === 'null') {
        alert('⚠️ No se seleccionó ningún producto.');
        window.location.href = 'index.html';
        return;
    }

    const productId = parseInt(storedId);
    let product = null;
    window._currentProduct = null;

    try {
        const res  = await fetch(`./api/productos.php?action=get&id=${productId}`);
        const data = await res.json();
        if (data.ok && data.product) {
            const p = data.product;
            window._currentProduct = product = {
                id:          parseInt(p.id),
                name:        p.nombre   || p.name,
                category:    p.categoria|| p.category,
                price:       parseFloat(p.precio   || p.price),
                oldPrice:    p.precio_viejo ? parseFloat(p.precio_viejo) : null,
                discount:    parseInt(p.descuento || p.discount) || 0,
                icon:        p.icono    || p.icon || '📦',
                description: p.descripcion || p.description || '',
                brand:       p.proveedor || 'E-PeriTech',
                sku:         'SKU-' + String(p.id).padStart(3, '0'),
            };
        }
    } catch (e) {
        console.error('Error API:', e);
    }

    if (!product) {
        alert('❌ Producto no encontrado (ID: ' + productId + ')');
        window.location.href = 'index.html';
        return;
    }

    const set     = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val; };
    const setHTML = (id, val) => { const el = document.getElementById(id); if (el) el.innerHTML   = val; };

    set('breadcrumbProduct',  product.name);
    set('productImage',       product.icon);
    set('productTitle',       product.name);
    set('productPrice',       formatPrice(product.price));
    set('productDescription', product.description || 'Sin descripción disponible.');
    set('productCategory',    product.category);
    set('productBrand',       product.brand || '—');
    set('productSku',         product.sku);

    if (product.oldPrice) {
        setHTML('priceInfo', `
            <span class="old-price">${formatPrice(product.oldPrice)}</span>
            <span class="savings">Ahorras ${formatPrice(product.oldPrice - product.price)} (-${product.discount}%)</span>
        `);
    }

    const fl = document.getElementById('featuresList');
    if (fl) {
        const items = product.description
            ? product.description.split('.').filter(s => s.trim()).map(s => s.trim() + '.')
            : ['Sin especificaciones detalladas.'];
        fl.innerHTML = items.map(f => `<li>${f}</li>`).join('');
    }

    loadRelatedProducts(product.category, product.id);
    checkAdminActions(product);
});

async function loadRelatedProducts(category, currentId) {
    const container = document.getElementById('relatedProducts');
    if (!container) return;
    try {
        const res  = await fetch(`./api/productos.php?action=list&activos=1&categoria=${encodeURIComponent(category)}`);
        const data = await res.json();
        const related = (data.data || [])
            .filter(p => parseInt(p.id) !== currentId)
            .slice(0, 4);

        if (!related.length) {
            container.innerHTML = '<p style="text-align:center;color:#666;">No hay productos relacionados</p>';
            return;
        }
        container.innerHTML = related.map(p => `
            <div class="product-card" onclick="viewProductDetail(${p.id})" style="cursor:pointer;">
                <div class="product-image">${p.icono || p.icon || '📦'}</div>
                <div class="product-info">
                    <h3 class="product-title">${p.nombre || p.name}</h3>
                    <div class="product-price">
                        <span class="price-current">${formatPrice(p.precio || p.price)}</span>
                        ${p.precio_viejo ? `<span class="price-old">${formatPrice(p.precio_viejo)}</span>` : ''}
                    </div>
                    ${parseInt(p.descuento||0) > 0 ? `<span class="discount-badge">-${p.descuento}% OFF</span>` : ''}
                </div>
            </div>`).join('');
    } catch (e) {
        container.innerHTML = '<p style="text-align:center;color:#aaa;">No se pudieron cargar productos relacionados</p>';
    }
}

function viewProductDetail(productId) {
    localStorage.setItem('selectedProduct', productId);
    window.location.reload();
}

function contactSeller() {
    const id  = localStorage.getItem('selectedProduct');
    const msg = `Hola, estoy interesado en el producto ID: ${id}`;
    window.open(`https://wa.me/573185838072?text=${encodeURIComponent(msg)}`, '_blank');
}

function addToCompare() {
    const id   = parseInt(localStorage.getItem('selectedProduct'));
    let list   = JSON.parse(localStorage.getItem('compareList') || '[]');
    if (list.includes(id))  { alert('⚠️ Ya está en comparación');       return; }
    if (list.length >= 4)   { alert('⚠️ Máximo 4 productos a comparar'); return; }
    list.push(id);
    localStorage.setItem('compareList', JSON.stringify(list));
    alert('✅ Producto agregado a comparación');
}

// ── Mostrar botón eliminar si es admin o proveedor dueño
async function checkAdminActions(product) {
    if (typeof DB === 'undefined') return;
    const session = DB.getSession();
    if (!session) return;

    const esAdmin     = session.role === 'admin';
    const esProveedor = session.role === 'proveedor';

    // Obtener proveedor_id del producto desde la API
    let proveedorId = null;
    try {
        const res  = await fetch(`./api/productos.php?action=get&id=${product.id}`);
        const data = await res.json();
        proveedorId = data.product?.proveedor_id || null;
    } catch(e) {}

    const esDueno = esProveedor && String(proveedorId) === String(session.userId);

    if (esAdmin || esDueno) {
        const btn = document.getElementById('adminActions');
        if (btn) btn.style.display = 'block';
    }
}

async function deleteCurrentProduct() {
    const id = parseInt(localStorage.getItem('selectedProduct'));
    if (!confirm('¿Eliminar este producto? Esta acción no se puede deshacer.')) return;
    try {
        const res  = await fetch(`./api/productos.php?action=delete`, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ id }),
        });
        const data = await res.json();
        if (data.ok) {
            alert('✅ Producto eliminado correctamente.');
            window.location.href = 'index.html';
        } else {
            alert('❌ Error: ' + (data.msg || 'No se pudo eliminar.'));
        }
    } catch(e) {
        alert('❌ Error de conexión.');
    }
}