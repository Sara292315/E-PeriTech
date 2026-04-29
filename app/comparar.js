// ============================================================
//  E-PeriTech — comparar.js
// ============================================================
// --------------CLIENTE SERVIDOR-------------------------
//  GUIA #1 - Actividad 1: Escenario de Software
//  Implementa la funcionalidad de comparacion de productos
//  que justifica la arquitectura distribuida: "Dificultad
//  para comparar productos de forma clara" (problematica).
// --------------CLIENTE SERVIDOR-------------------------
//  GUIA #1 - Actividad 3: Procesos del Cliente
//  Visualizacion comparativa ejecutada en el cliente
//  con datos obtenidos remotamente desde el servidor PHP.
// ============================================================

// Obtener lista de comparación
function getCompareList() {
    return JSON.parse(localStorage.getItem('compareList') || '[]');
}

// Guardar lista de comparación
function saveCompareList(list) {
    localStorage.setItem('compareList', JSON.stringify(list));
}

// Renderizar comparación
function renderComparison() {
    const compareList = getCompareList();
    const container = document.getElementById('compareContent');
    
    if (compareList.length === 0) {
        container.innerHTML = `
            <div class="empty-compare">
                <div class="empty-icon">⚖️</div>
                <h2>No hay productos para comparar</h2>
                <p>Agrega productos desde la tienda para compararlos aquí</p>
                <a href="index.html" class="btn-shop">Ir a la tienda</a>
            </div>
        `;
        return;
    }
    
    const productsToCompare = compareList.map(id => productsData[id]).filter(p => p);
    
    if (productsToCompare.length === 0) {
        container.innerHTML = `
            <div class="empty-compare">
                <div class="empty-icon">❌</div>
                <h2>Productos no encontrados</h2>
                <p>Los productos seleccionados no están disponibles</p>
                <button class="btn-shop" onclick="clearComparison()">Limpiar comparación</button>
            </div>
        `;
        return;
    }
    
    // Crear tabla de comparación
    let tableHTML = `
        <div class="compare-table-wrapper">
            <table class="compare-table">
                <thead>
                    <tr class="compare-header">
                        <th style="width: 200px;"></th>
                        ${productsToCompare.map(p => `
                            <th>
                                <div class="product-compare-card">
                                    <div class="product-compare-image">${p.icon}</div>
                                    <div class="product-compare-name">${p.name}</div>
                                    <div class="product-compare-price">${formatPrice(p.price)}</div>
                                    <button class="btn-remove-compare" onclick="removeFromComparison(${p.id})">
                                        Eliminar
                                    </button>
                                </div>
                            </th>
                        `).join('')}
                    </tr>
                </thead>
                <tbody>
                    <tr class="compare-row">
                        <td class="spec-label">Marca</td>
                        ${productsToCompare.map(p => `<td class="spec-value">${p.brand}</td>`).join('')}
                    </tr>
                    <tr class="compare-row">
                        <td class="spec-label">Categoría</td>
                        ${productsToCompare.map(p => `<td class="spec-value">${p.category}</td>`).join('')}
                    </tr>
                    <tr class="compare-row">
                        <td class="spec-label">Precio Regular</td>
                        ${productsToCompare.map(p => `<td class="spec-value">${p.oldPrice ? formatPrice(p.oldPrice) : formatPrice(p.price)}</td>`).join('')}
                    </tr>
                    <tr class="compare-row">
                        <td class="spec-label">Precio Oferta</td>
                        ${productsToCompare.map(p => `<td class="spec-value highlight">${formatPrice(p.price)}</td>`).join('')}
                    </tr>
                    <tr class="compare-row">
                        <td class="spec-label">Descuento</td>
                        ${productsToCompare.map(p => `<td class="spec-value">${p.discount > 0 ? p.discount + '% OFF' : 'Sin descuento'}</td>`).join('')}
                    </tr>
                    <tr class="compare-row">
                        <td class="spec-label">SKU</td>
                        ${productsToCompare.map(p => `<td class="spec-value">${p.sku}</td>`).join('')}
                    </tr>
                    <tr class="compare-row">
                        <td class="spec-label">Características</td>
                        ${productsToCompare.map(p => `
                            <td class="spec-value">
                                ${p.features.slice(0, 4).map(f => `• ${f}<br>`).join('')}
                            </td>
                        `).join('')}
                    </tr>
                    <tr class="compare-row">
                        <td class="spec-label">Disponibilidad</td>
                        ${productsToCompare.map(() => `<td class="spec-value highlight">✓ En stock</td>`).join('')}
                    </tr>
                    <tr class="compare-row">
                        <td class="spec-label">Acción</td>
                        ${productsToCompare.map(p => `
                            <td>
                                <button class="btn-add-cart" onclick="goToProductDetail(${p.id})">
                                    Ver Detalles
                                </button>
                            </td>
                        `).join('')}
                    </tr>
                </tbody>
            </table>
        </div>
        <button class="btn-clear-all" onclick="clearComparison()">
            🗑️ Limpiar Comparación
        </button>
    `;
    
    container.innerHTML = tableHTML;
}

// Eliminar de comparación
function removeFromComparison(productId) {
    let compareList = getCompareList();
    compareList = compareList.filter(id => id !== productId);
    saveCompareList(compareList);
    renderComparison();
    renderQuickAdd();
}

// Limpiar comparación
function clearComparison() {
    if (confirm('¿Estás seguro de limpiar toda la comparación?')) {
        localStorage.removeItem('compareList');
        renderComparison();
        renderQuickAdd();
    }
}

// Agregar a comparación desde quick add
function quickAddToCompare(productId) {
    let compareList = getCompareList();
    
    if (compareList.includes(productId)) {
        alert('⚠️ Este producto ya está en tu lista de comparación');
        return;
    }
    
    if (compareList.length >= 4) {
        alert('⚠️ Solo puedes comparar hasta 4 productos a la vez');
        return;
    }
    
    compareList.push(productId);
    saveCompareList(compareList);
    renderComparison();
    renderQuickAdd();
}

// Renderizar productos para agregar rápido
function renderQuickAdd() {
    const compareList = getCompareList();
    const container = document.getElementById('quickAddProducts');
    
    // Filtrar productos que no están en la comparación
    const availableProducts = Object.values(productsData).filter(p => !compareList.includes(p.id));
    
    if (availableProducts.length === 0) {
        container.innerHTML = '<p style="text-align: center; grid-column: 1/-1; color: #666;">Todos los productos están en comparación</p>';
        return;
    }
    
    container.innerHTML = availableProducts.slice(0, 8).map(product => `
        <div class="quick-add-card" onclick="quickAddToCompare(${product.id})">
            <div class="quick-add-icon">${product.icon}</div>
            <div class="quick-add-name">${product.name}</div>
            <div class="quick-add-price">${formatPrice(product.price)}</div>
        </div>
    `).join('');
}

// Función para ver detalles desde productos relacionados
function viewProductDetail(productId) {
    localStorage.setItem('selectedProduct', productId);
    window.location.href = 'producto.html';
}

// Mapa de productos por ID (se llena al cargar la página)
let productsData = {};

// Inicializar
document.addEventListener('DOMContentLoaded', async function() {
    // Cargar productos desde la API para construir productsData
    if (typeof DB !== 'undefined' && DB.getProducts) {
        try {
            const response = await DB.getProducts();
            if (response && response.ok && response.data) {
                response.data.forEach(p => {
                    productsData[p.id] = {
                        id: p.id,
                        name: p.nombre,
                        price: parseFloat(p.precio),
                        oldPrice: p.precio_viejo ? parseFloat(p.precio_viejo) : null,
                        discount: p.descuento || 0,
                        icon: p.icono || '📦',
                        category: p.categoria_slug || '',
                        categoryName: p.categoria_nombre || '',
                        description: p.descripcion || '',
                        brand: p.marca || 'Sin marca',
                        sku: p.id,
                        features: p.descripcion ? [p.descripcion] : [],
                    };
                });
            }
        } catch (e) {
            console.error('Error cargando productos para comparación:', e);
        }
    }
    renderComparison();
    renderQuickAdd();
});

// Función para ir a la página de detalles del producto
function goToProductDetail(productId) {
    localStorage.setItem('selectedProduct', productId);
    window.location.href = 'producto.html';
}