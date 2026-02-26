// Sample products data
const products = [
    {
        id: 1,
        name: "Mouse Gamer Inalámbrico RGB Pro",
        category: "mouse",
        price: 319000,
        oldPrice: 399000,
        discount: 20,
        icon: "🖱️"
    },
    {
        id: 2,
        name: "Teclado Mecánico RGB Gaming",
        category: "teclado",
        price: 225000,
        oldPrice: null,
        discount: 0,
        icon: "⌨️"
    },
    {
        id: 3,
        name: "Monitor Gamer 25'' 200Hz IPS",
        category: "monitor",
        price: 589000,
        oldPrice: 635000,
        discount: 7,
        icon: "🖥️"
    },
    {
        id: 4,
        name: "Audífonos Gaming 7.1 Surround",
        category: "audifonos",
        price: 185000,
        oldPrice: 220000,
        discount: 16,
        icon: "🎧"
    },
    {
        id: 5,
        name: "Mouse Pad XXL RGB Extended",
        category: "mouse",
        price: 89000,
        oldPrice: null,
        discount: 0,
        icon: "🎨"
    },
    {
        id: 6,
        name: "Webcam 4K Pro Streaming",
        category: "otros",
        price: 349000,
        oldPrice: 420000,
        discount: 17,
        icon: "📹"
    },
    {
        id: 7,
        name: "SSD M.2 NVMe 1TB Ultra Fast",
        category: "otros",
        price: 259000,
        oldPrice: null,
        discount: 0,
        icon: "💾"
    },
    {
        id: 8,
        name: "Silla Gamer Ergonómica Pro",
        category: "otros",
        price: 899000,
        oldPrice: 1100000,
        discount: 18,
        icon: "🪑"
    }
];

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
    grid.innerHTML = '';
    
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
                        Ver Detalles 🛒
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

// Add to cart (opcional)
function addToCart(productId) {
    const product = products.find(p => p.id === productId);
    alert(`✅ ${product.name} agregado al carrito`);
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
document.addEventListener('DOMContentLoaded', function() {
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