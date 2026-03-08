// Productos: se leen desde database.js (API) para reflejar cambios de proveedores
let products = [];

async function loadProducts() {
    if (typeof DB !== 'undefined' && DB.getProducts) {
        try {
            const response = await DB.getProducts();
            if (response && response.ok && response.data) {
                // Mapear campos de la API a la estructura esperada por el frontend
                products = response.data.map(p => ({
                    id: p.id,
                    name: p.nombre,
                    price: parseFloat(p.precio),
                    oldPrice: p.precio_anterior ? parseFloat(p.precio_anterior) : null,
                    discount: p.descuento || 0,
                    icon: p.icono || '📦',
                    category: p.categoria_slug || '',
                    categoryName: p.categoria_nombre || '',
                    description: p.descripcion || '',
                    activo: p.activo !== 0
                })).filter(p => p.activo !== false);
            } else {
                console.error('Error al cargar productos:', response?.msg || 'Respuesta inválida');
                products = [];
            }
        } catch (error) {
            console.error('Error al cargar productos:', error);
            products = [];
        }
    } else {
        console.warn('DB.getProducts no está disponible');
        products = [];
    }
}

// Format price to COP
function formatPrice(price) {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        minimumFractionDigits: 0
    }).format(price);
}

// Render products
function renderProducts(filteredProducts = products) {
    const grid = document.getElementById('productsGrid');
    if (!grid) return;
    
    grid.innerHTML = '';
    
    if (filteredProducts.length === 0) {
        grid.innerHTML = `
            <div style="grid-column: 1 / -1; text-align: center; padding: 40px; color: #888;">
                <h3>😔 No hay productos disponibles</h3>
                <p>No se encontraron productos en la base de datos.</p>
            </div>
        `;
        return;
    }
    
    filteredProducts.forEach(product => {
        const productCard = document.createElement('div');
        productCard.className = 'product-card';
        
        productCard.innerHTML = `
            <div class="product-image" onclick="viewProduct(${product.id})">${product.icon}</div>
            <div class="product-info">
                <h3 class="product-title">${product.name}</h3>
                <div class="product-price">
                    <span class="price-current">${formatPrice(product.price)}</span>
                    ${product.oldPrice ? `<span class="price-old">${formatPrice(product.oldPrice)}</span>` : ''}
                </div>
                ${product.discount > 0 ? `<span class="discount-badge">-${product.discount}% OFF</span>` : ''}
                <div class="product-actions">
                    <button class="btn-add-cart" onclick="viewProduct(${product.id})">
                        Ver Detalles
                    </button>
                    <button class="btn-compare-quick" onclick="addToCartFromGrid(${product.id})" title="Agregar al carrito">
                        🛒
                    </button>
                    <button class="btn-compare-quick" onclick="addToCompare(${product.id})" title="Agregar a comparación">
                        ⚖️
                    </button>
                </div>
            </div>
        `;
        
        grid.appendChild(productCard);
    });
}

// View product details - NUEVA FUNCIÓN
function viewProduct(productId) {
    console.log('🔍 Seleccionando producto ID:', productId);
    // Guardar el ID en localStorage
    localStorage.setItem('selectedProduct', productId);
    console.log('💾 Producto guardado en localStorage');
    // Navegar a la página de detalles
    window.location.href = 'producto.html';
}

// Filter by category
function filterCategory(category) {
    const filtered = products.filter(p => p.category === category);
    renderProducts(filtered);
    
    // Scroll to products
    document.getElementById('productos').scrollIntoView({ behavior: 'smooth' });
}

// Add to cart desde la grilla
function addToCartFromGrid(productId) {
    const product = products.find(p => p.id === productId);
    if (!product) return;
    if (typeof Cart !== 'undefined') {
        Cart.addItem(product);
    }
}

// Agregar a comparación
function addToCompare(productId) {
    let compareList = JSON.parse(localStorage.getItem('compareList') || '[]');
    
    if (compareList.includes(productId)) {
        alert('⚠️ Este producto ya está en tu lista de comparación');
        return;
    }
    
    if (compareList.length >= 4) {
        alert('⚠️ Solo puedes comparar hasta 4 productos a la vez');
        return;
    }
    
    compareList.push(productId);
    localStorage.setItem('compareList', JSON.stringify(compareList));
    
    const product = products.find(p => p.id === productId);
    alert(`✅ ${product.name} agregado a comparación\n\nProductos en comparación: ${compareList.length}/4`);
    
    // Actualizar contador
    updateCompareBadge();
}

// Actualizar badge de comparación
function updateCompareBadge() {
    const badge = document.getElementById('compareBadge');
    if (badge) {
        const compareList = JSON.parse(localStorage.getItem('compareList') || '[]');
        badge.textContent = compareList.length;
        badge.style.display = compareList.length > 0 ? 'flex' : 'none';
    }
}

// Scroll to products
function scrollToProducts() {
    document.getElementById('productos').scrollIntoView({ behavior: 'smooth' });
}

// Toggle mobile menu
function toggleMenu() {
    const navLinks = document.getElementById('navLinks');
    navLinks.classList.toggle('active');
}

// Toggle search bar
function toggleSearch() {
    const searchContainer = document.getElementById('searchContainer');
    const searchInput = document.getElementById('searchInput');
    searchContainer.classList.toggle('active');
    
    if (searchContainer.classList.contains('active')) {
        searchInput.focus();
    } else {
        searchInput.value = '';
        renderProducts();
        updateSearchInfo('');
    }
}

// Search products
function searchProducts() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase().trim();
    
    if (searchTerm === '') {
        renderProducts();
        updateSearchInfo('');
        return;
    }
    
    const filtered = products.filter(product => 
        product.name.toLowerCase().includes(searchTerm) ||
        product.category.toLowerCase().includes(searchTerm)
    );
    
    renderProducts(filtered);
    updateSearchInfo(searchTerm, filtered.length);
}

// Update search info
function updateSearchInfo(searchTerm, resultsCount) {
    const searchInfo = document.getElementById('searchInfo');
    
    if (searchTerm === '') {
        searchInfo.innerHTML = '';
        return;
    }
    
    if (resultsCount === 0) {
        searchInfo.innerHTML = `
            <div class="no-results">
                <h3>😔 No se encontraron resultados</h3>
                <p>No encontramos productos para "<strong>${searchTerm}</strong>"</p>
                <button class="btn-secondary" onclick="clearSearch()">Ver todos los productos</button>
            </div>
        `;
    } else {
        searchInfo.innerHTML = `
            <p>Se encontraron <strong>${resultsCount}</strong> producto${resultsCount !== 1 ? 's' : ''} para "<strong>${searchTerm}</strong>"</p>
        `;
    }
}

// Clear search
function clearSearch() {
    document.getElementById('searchInput').value = '';
    renderProducts();
    updateSearchInfo('');
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', async function() {
    await loadProducts();
    renderProducts();
});
// ─── AUTH INTEGRATION ───────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
    renderNavAuth();
    updateCompareBadge();
});

function renderNavAuth() {
    if (typeof DB === 'undefined') return;
    const session = DB.getSession();
    const navIcons = document.querySelector('.nav-icons');
    if (!navIcons) return;

    // Remove old user btn if present
    const old = document.getElementById('navUserBtn');
    if (old) old.remove();

    const btn = document.createElement('button');
    btn.id = 'navUserBtn';
    btn.className = 'icon-btn';
    btn.style.cssText = 'position:relative;';

    if (session) {
        btn.innerHTML = `<span style="font-size:20px;">${session.avatar || '👤'}</span>`;
        btn.title = session.nombre;
        btn.onclick = () => {
            if (session.role === 'admin')          window.location.href = 'admin.html';
            else if (session.role === 'proveedor') window.location.href = 'proveedor.html';
            else                                   window.location.href = 'micuenta.html';
        };
    } else {
        btn.innerHTML = '👤';
        btn.title = 'Iniciar sesión';
        btn.onclick = () => window.location.href = 'login.html';
    }

    navIcons.appendChild(btn);
}