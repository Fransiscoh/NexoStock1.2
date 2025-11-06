document.addEventListener('DOMContentLoaded', function() {
    // Apply saved theme
    const savedTheme = localStorage.getItem('theme') || 'light';
    document.body.setAttribute('data-theme', savedTheme);
    const themeIcon = document.querySelector('.theme-toggle i');
    if (themeIcon) {
        themeIcon.className = savedTheme === 'light' ? 'fas fa-moon' : 'fas fa-sun';
    }
    
    updateUserSessionUI();
    // Aquí puedes llamar a otras funciones que necesiten ejecutarse al inicio,
    // como fetchData() si es que existe.
    if (window.fetchData) {
        window.fetchData();
    }

    // Limpiar facturador al iniciar
    clearInvoice();

    window.fetchData();
    setupEventListeners();
});

let products = [];
let brands = [];
let categories = [];
let providers = [];
let sales = [];
let userRole = 'usuario';
let invoiceItems = [];
let mixItems = [];
let currentFractioningProduct = null;

// Función para limpiar completamente el facturador
function clearInvoice() {
    invoiceItems = [];
    updateInvoiceDisplay();
    console.log('Facturador reiniciado');
}

const unitConversions = {
    'kg': { 'g': 1000 },
    'g': { 'kg': 0.001 },
    'L': { 'mL': 1000 },
    'mL': { 'L': 0.001 },
    'm': { 'cm': 100 },
    'cm': { 'm': 0.01 },
};

window.fetchData = async function(options = { displaySales: true }) {
    try {
        const bootstrapResponse = await fetch('../backend/index.php?accion=bootstrap');
        const data = await bootstrapResponse.json();

        if (data.error) {
            showNotification(data.error, 'error');
            return;
        }
        const sessionResponse = await fetch('../backend/api/get_user_session.php');
        if (!response.ok) {
            throw new Error('La respuesta de la red no fue correcta.');
        }
        const session = await response.json();

        products = data.productos || [];
        brands = data.marcas || [];
        categories = data.categorias || [];
        providers = data.proveedores || [];
        sales = data.ventas || [];
        userRole = data.user_role || 'usuario';
        const userNameElement = document.getElementById('user-name');
        const navElement = document.getElementById('main-nav');

        updateDashboard();
        loadProductsTable();
        loadAvailableProducts();
        loadMixAvailableProducts();
        loadFractionableProducts();
        loadFractionedProducts();
        loadBrandsAndCategories();
        loadProvidersForForms(); // This is on the providers tab
        renderProvidersForIndex(providers);

        loadSalesData(); // Always load sales data for dashboard cards

        updateInvoiceDisplay();
    } catch (error) {
        showNotification('Error de conexión al cargar los datos iniciales.', 'error');
        console.error(error);
    }
};

function setupEventListeners() {
    document.getElementById('addProductForm').addEventListener('submit', handleAddProduct);
}

function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    document.body.appendChild(notification);
    setTimeout(() => notification.remove(), 3000);
}

async function handleAddProduct(e) {
    e.preventDefault();

    const newProduct = {
        nombre: document.getElementById('productName').value,
        codigo: document.getElementById('productCode').value,
        marca_id: document.getElementById('productBrand').value,
        categoria_id: document.getElementById('productCategory').value,
        proveedor_id: document.getElementById('productProvider').value,
        stock: document.getElementById('productStock').value,
        stock_minimo: document.getElementById('productMinStock').value,
        precio_compra: document.getElementById('productPurchasePrice').value,
        precio_venta: document.getElementById('productSellingPrice').value,
        tipo_medida: document.getElementById('productMeasurement').value,
        unidad: document.getElementById('productUnit').value,
    };

    try {
        const response = await fetch('../backend/index.php?accion=agregar_producto', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(newProduct)
        });
        const product = await response.json();
        if (product.error) {
            showNotification(product.error, 'error');
            return;
        if (userNameElement) {
            userNameElement.textContent = session.userName;
        }

        products.push(product);
        updateDashboard();
        loadProductsTable();
        loadAvailableProducts();
        e.target.reset();
        showNotification('Producto agregado exitosamente');

    } catch (error) {
        showNotification('Error al agregar el producto.', 'error');
        console.error(error);
    }
}

async function addBrand() {
    const newBrandName = document.getElementById('newBrand').value.trim();
    if (!newBrandName) return;

    try {
        const response = await fetch('../backend/index.php?accion=agregar_marca', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ nombre: newBrandName })
        });
        const brand = await response.json();
        if (brand.error) {
            showNotification(brand.error, 'error');
            return;
        }

        brands.push(brand);
        loadBrandsAndCategories();
        document.getElementById('newBrand').value = '';
        showNotification('Marca agregada exitosamente');

    } catch (error) {
        showNotification('Error al agregar la marca.', 'error');
        console.error(error);
    }
}

async function addCategory() {
    const newCategoryName = document.getElementById('newCategory').value.trim();
    if (!newCategoryName) return;

    try {
        const response = await fetch('../backend/index.php?accion=agregar_categoria', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ nombre: newCategoryName })
        });
        const category = await response.json();
        if (category.error) {
            showNotification(category.error, 'error');
            return;
        }

        categories.push(category);
        loadBrandsAndCategories();
        document.getElementById('newCategory').value = '';
        showNotification('Categoría agregada exitosamente');

    } catch (error) {
        showNotification('Error al agregar la categoría.', 'error');
        console.error(error);
    }
}

async function processInvoice() {
    if (invoiceItems.length === 0) {
        showNotification('No hay items en la factura', 'warning');
        return;
    }

    const saleData = {
        items: invoiceItems,
        total: invoiceItems.reduce((sum, item) => sum + item.total, 0),
        costo: invoiceItems.reduce((sum, item) => sum + (item.cost * item.quantity), 0),
        ganancia: invoiceItems.reduce((sum, item) => sum + item.profit, 0),
    };

    try {
        const response = await fetch('../backend/index.php?accion=procesar_venta', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(saleData)
        });
        const result = await response.json();
        if (result.error) {
            showNotification(result.error, 'error');
            return;
        }

        clearInvoice();
        // Recargar todos los datos para reflejar el nuevo stock y la venta
        await window.fetchData();
        showNotification('Venta procesada exitosamente');
    } catch (error) {
        showNotification('Error al procesar la venta.', 'error');
        console.error(error);
    }
}

function updateDashboard() {
    const totalProducts = products.length;
    const lowStockProducts = products.filter(p => parseFloat(p.stock) <= parseFloat(p.stock_minimo)).length;
    
    document.getElementById('totalProducts').textContent = totalProducts;
    document.getElementById('lowStock').textContent = lowStockProducts;
}

function formatStock(quantity, unit) {
    const value = parseFloat(quantity);
    if (isNaN(value)) {
        return `${quantity} ${unit}`;
    }

    // Helper to format with thousand separators and optional decimals
    const formatNumber = (num, minDecimals = 0, maxDecimals = 2) => {
        return new Intl.NumberFormat('es-ES', {
            minimumFractionDigits: minDecimals,
            maximumFractionDigits: maxDecimals,
        }).format(num);
    };

    switch (unit ? unit.toLowerCase() : '') {
        case 'kg':
            if (value >= 1) {
                // If it's a whole number, no decimals. Otherwise, up to 3 for precision.
                const maxDecimals = value % 1 === 0 ? 0 : 3;
                return `${new Intl.NumberFormat('es-ES', { maximumFractionDigits: maxDecimals }).format(value)} kg`;
            }
            return `${formatNumber(value * 1000)} g`;
            if (session.isLoggedIn) {
                navElement.innerHTML = `
                    <a href="dashboard.html">Mi Panel</a>
                    <a href="../backend/logout.php">Cerrar Sesión</a>
                `;
            } else {
                return `${formatNumber(value * 1000, 0, 0)} g`;
                navElement.innerHTML = `
                    <a href="login.html">Iniciar Sesión</a>
                    <a href="register.html">Registrarse</a>
                `;
            }
        case 'l':
            if (value >= 1) {
                const maxDecimals = value % 1 === 0 ? 0 : 3;
                return `${new Intl.NumberFormat('es-ES', { maximumFractionDigits: maxDecimals }).format(value)} L`;
            } else {
                return `${formatNumber(value * 1000, 0, 0)} ml`;
            }
        case 'm':
            if (value >= 1) {
                const maxDecimals = value % 1 === 0 ? 0 : 2;
                return `${new Intl.NumberFormat('es-ES', { maximumFractionDigits: maxDecimals }).format(value)} m`;
            } else {
                return `${formatNumber(value * 100, 0, 0)} cm`;
            }
        case 'u':
        case 'uds':
            return `${formatNumber(value, 0, 0)} uds`;
        default:
            return `${formatNumber(value, 0, 2)} ${unit || ''}`;
    }
}

function loadProductsTable(productsData = products) {
    const tbody = document.getElementById('productsTableBody');
    tbody.innerHTML = '';
    productsData.forEach(product => {
        const row = document.createElement('tr');
        const stockStatus = getStockStatus(product);
        row.innerHTML = `
            <td>${product.codigo}</td>
            <td>${product.nombre}</td>
            <td>${product.marca}</td>
            <td>${product.categoria}</td>
            <td>${product.proveedor || 'N/A'}</td>
            <td>
                <span>${formatStock(product.stock, product.unidad)}</span>
            </td>
            <td>$${product.precio_compra}</td>
            <td>$${product.precio_venta}</td>
            <td>${(((product.precio_venta - product.precio_compra) / product.precio_compra) * 100).toFixed(2)}%</td>
            <td><span class="badge ${stockStatus.class}">${stockStatus.text}</span></td>
        `;
        tbody.appendChild(row);
    });
}

function filterProducts() {
    const searchTerm = document.getElementById('searchProducts').value.toLowerCase();
    const filteredProducts = products.filter(product => {
        const productName = product.nombre.toLowerCase();
        const productCode = product.codigo.toLowerCase();
        return productName.includes(searchTerm) || productCode.includes(searchTerm);
    });
    loadProductsTable(filteredProducts);
}

function filterInvoiceProducts() {
    const searchTerm = document.getElementById('searchInvoiceProducts').value.toLowerCase();
    // Primero, filtramos por stock > 0
    const inStockProducts = products.filter(p => parseFloat(p.stock) > 0);
    // Luego, filtramos por el término de búsqueda
    const filteredProducts = inStockProducts.filter(product => {
        const productName = product.nombre.toLowerCase();
        const productCode = product.codigo.toLowerCase();
        return productName.includes(searchTerm) || productCode.includes(searchTerm);
    });
    loadAvailableProducts(filteredProducts); // Pasamos la lista ya filtrada por stock y búsqueda
}

function loadAvailableProducts(productsData = products) {
    const container = document.getElementById('availableProducts');
    container.innerHTML = '';
    // Usamos la lista de productos proporcionada (o la global por defecto) y la filtramos para mostrar solo los que tienen stock.
    const inStockProducts = productsData.filter(p => parseFloat(p.stock) > 0);

    inStockProducts.forEach(product => {
        const item = createAvailableProductItem(product);
        container.appendChild(item);
    });
}

function createAvailableProductItem(product) {
    const div = document.createElement('div');
    div.className = `product-item`;
    div.innerHTML = `
        <div class="product-info">
            <h4>${product.nombre}</h4>
            <p>Stock: ${formatStock(product.stock, product.unidad)} - $${product.precio_venta}</p>
        </div>
        <div class="product-actions">
            <button class="btn-sm" onclick="addToInvoice(${product.id})" ${product.stock <= 0 ? 'disabled' : ''}>
                <i class="fas fa-plus"></i>
            </button>
        </div>
    `;
    return div;
}

function addToInvoice(productId) {
    const product = products.find(p => p.id == productId);
    if (!product || product.stock <= 0) return;

    const existingItem = invoiceItems.find(item => item.producto_id == productId);

    if (existingItem) {
        if (existingItem.quantity < product.stock) {
            existingItem.quantity++;
            existingItem.total = existingItem.quantity * existingItem.price;
            existingItem.profit = existingItem.quantity * (existingItem.price - product.precio_compra);
        } else {
            showNotification('No hay suficiente stock', 'warning');
        }
    } else {
        invoiceItems.push({
            producto_id: product.id,
            productName: product.nombre,
            quantity: 1,
            price: parseFloat(product.precio_venta),
            cost: parseFloat(product.precio_compra),
            total: parseFloat(product.precio_venta),
            profit: parseFloat(product.precio_venta) - parseFloat(product.precio_compra),
        });
    }
    updateInvoiceDisplay();
}

function removeFromInvoice(productId) {
    invoiceItems = invoiceItems.filter(item => item.producto_id != productId);
    updateInvoiceDisplay();
}

function updateInvoiceDisplay() {
    const container = document.getElementById('invoiceItems');
    const totalDiv = document.getElementById('invoiceTotal');

    if (invoiceItems.length === 0) {
        container.innerHTML = '<p class="empty-state">No hay items en la factura</p>';
        totalDiv.style.display = 'none';
        return;
    }

    container.innerHTML = '';
    let subtotal = 0;
    let profit = 0;

    invoiceItems.forEach(item => {
        const div = document.createElement('div');
        div.className = 'product-item';
        div.innerHTML = `
            <div class="product-info">
                <h4>${item.productName}</h4>
                <p>${item.quantity} x $${item.price.toFixed(2)} = $${item.total.toFixed(2)}</p>
            </div>
            <div class="product-actions">
                <button class="btn-sm" onclick="removeFromInvoice(${item.producto_id})">
                    <i class="fas fa-minus"></i>
                </button>
            </div>
        `;
        container.appendChild(div);
        subtotal += item.total;
        profit += item.profit;
    });

    document.getElementById('subtotalAmount').textContent = subtotal.toFixed(2);
    document.getElementById('profitAmount').textContent = profit.toFixed(2);
    document.getElementById('totalAmount').textContent = subtotal.toFixed(2);
    totalDiv.style.display = 'block';
}

function loadBrandsAndCategories() {
    const brandSelects = document.querySelectorAll('#productBrand, #editProductBrand');
    const categorySelects = document.querySelectorAll('#productCategory, #editProductCategory');
    const brandsList = document.getElementById('brandsList');
    const categoriesList = document.getElementById('categoriesList');

    brandSelects.forEach(select => {
        select.innerHTML = '<option value="">Seleccionar marca</option>';
        brands.forEach(brand => {
            select.innerHTML += `<option value="${brand.id}">${brand.nombre}</option>`;
        });
    });

    categorySelects.forEach(select => {
        select.innerHTML = '<option value="">Seleccionar categoría</option>';
        categories.forEach(category => {
            select.innerHTML += `<option value="${category.id}">${category.nombre}</option>`;
        });
    });

    if (brandsList) {
        brandsList.innerHTML = '';
        brands.forEach(brand => {
            const itemDiv = document.createElement('div');
            itemDiv.className = 'item-tag';
            let content = `<span>${brand.nombre}</span>`;
            if (userRole === 'administrador') {
                content += `<button class="btn-sm btn-danger" style="margin-left: 8px;" onclick="deleteItem('marca', ${brand.id})"><i class="fas fa-trash"></i></button>`;
            }
            itemDiv.innerHTML = content;
            brandsList.appendChild(itemDiv);
        });
    }

    if (categoriesList) {
        categoriesList.innerHTML = '';
        categories.forEach(category => {
            const itemDiv = document.createElement('div');
            itemDiv.className = 'item-tag';
            let content = `<span>${category.nombre}</span>`;
            if (userRole === 'administrador') {
                content += `<button class="btn-sm btn-danger" style="margin-left: 8px;" onclick="deleteItem('categoria', ${category.id})"><i class="fas fa-trash"></i></button>`;
            }
            itemDiv.innerHTML = content;
            categoriesList.appendChild(itemDiv);
        });
    }
}

async function deleteItem(type, id) {
    if (!confirm(`¿Estás seguro de que quieres eliminar est${type === 'marca' ? 'a' : 'a'} ${type}?`)) return;

    const action = `eliminar_${type}`;
    try {
        const response = await fetch(`../backend/index.php?accion=${action}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id })
        });
        const data = await response.json();
        if (data.error) {
            showNotification(`Error al eliminar: ${data.error}`, 'error');
        } else {
            showNotification(`${type.charAt(0).toUpperCase() + type.slice(1)} eliminad${type === 'marca' ? 'a' : 'a'} con éxito.`, 'success');
            fetchData(); // Reload all data
        }
    } catch (error) {
        showNotification(`Error de conexión al eliminar ${type}.`, 'error');
        console.error(`Error deleting ${type}:`, error);
        console.error('Error al obtener la sesión del usuario:', error);
        // Opcional: mostrar un mensaje de error en la UI
    }
}

function loadProvidersForForms() {
    const providerSelects = document.querySelectorAll('#productProvider, #editProductProvider');

    providerSelects.forEach(select => {
        select.innerHTML = '<option value="">Seleccionar proveedor</option>';
        providers.forEach(provider => {
            select.innerHTML += `<option value="${provider.id}">${provider.nombre}</option>`;
        });
    });
}

function renderProvidersForIndex(providersData) {
    const tbody = document.getElementById('providersTableBody');
    if (tbody) {
        tbody.innerHTML = '';
        providersData.forEach(provider => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${provider.nombre || '-'}</td>
                <td>${provider.contacto || '-'}</td>
                <td>${provider.telefono || '-'}</td>
                <td>${provider.email || '-'}</td>
                <td>
                    <button class="btn-sm" onclick="editProvider(${provider.id})"><i class="fas fa-edit"></i></button>
                    <button class="btn-sm btn-danger" onclick="deleteProvider(${provider.id})"><i class="fas fa-trash"></i></button>
                </td>
            `;
            tbody.appendChild(row);
        });
    }
}

function loadSalesData() {
    const thisMonth = new Date().toISOString().slice(0, 7);

    // Calculate monthly stats based ONLY on closed sales
    const monthlySales = sales.filter(s => s.fecha_venta.startsWith(thisMonth) && s.caja_cerrada);

    const totalMonthlySales = monthlySales.reduce((sum, s) => sum + parseFloat(s.total), 0);
    const totalMonthlyProfit = monthlySales.reduce((sum, s) => sum + parseFloat(s.ganancia_total), 0);
    const monthlyTransactions = monthlySales.length;

    // "Control de Ventas" -> "Ventas del Mes" card
    document.getElementById('monthlySales').textContent = `$${totalMonthlySales.toFixed(2)}`;
    document.getElementById('monthlyTransactions').textContent = `${monthlyTransactions} transacciones`;
    document.getElementById('monthlyProfit').textContent = `$${totalMonthlyProfit.toFixed(2)}`;

    // "Control de Ventas" -> "Historial de ventas"
    const salesHistoryContainer = document.getElementById('salesHistory');
    salesHistoryContainer.innerHTML = '';
    // FIX: Mostrar todas las ventas recientes, no solo las de caja cerrada.
    const recentSales = sales.slice(0, 20);
    if (recentSales.length > 0) {
        recentSales.forEach(sale => {
            const div = document.createElement('div');
            div.className = 'sale-item';

            const itemsHtml = sale.items && sale.items.length > 0 
                ? sale.items.map(item => `
                    <div class="sale-item-detail">
                        <span>${item.nombre_producto || item.nombre}</span>
                        <span>${item.cantidad} u.</span>
                    </div>
                `).join('')
                : '<div class="sale-item-detail"><span>No hay detalle de productos.</span></div>';

            let cancelButtonHtml = '';
            if (userRole === 'administrador') {
                cancelButtonHtml = `
                <div class="sale-actions" style="margin-top: 10px; text-align: right;">
                    <button class="btn-sm btn-danger" onclick="cancelSale(${sale.id})">
                        <i class="fas fa-times-circle"></i> Anular Venta
                    </button>
                </div>`;
            }

            div.innerHTML = `
                <div class="sale-item-header" onclick="this.parentElement.classList.toggle('expanded')">
                    <div class="sale-info">
                        <h5>Venta #${sale.id}</h5>
                        <p>${new Date(sale.fecha_venta).toLocaleString()}</p>
                    </div>
                    <div class="sale-amount">
                        <span>$${parseFloat(sale.total).toFixed(2)}</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                </div>
                <div class="sale-item-details">
                    ${itemsHtml}
                    ${cancelButtonHtml}
                </div>
            `;
            salesHistoryContainer.appendChild(div);
        });
    } else {
        salesHistoryContainer.innerHTML = '<p class="empty-state">No hay ventas registradas.</p>';
    }

    // Llamar a updateDailySummary DESPUÉS de que los datos de ventas se hayan cargado y procesado.
    // Esto asegura que todos los cálculos de ventas diarias/mensuales se hagan con datos frescos.
    updateDailySummary(); 
}

function getStockStatus(product) {
    if (parseFloat(product.stock) <= parseFloat(product.stock_minimo)) {
        return { class: 'badge-danger', text: 'Stock Bajo' };
    } else if (parseFloat(product.stock) <= parseFloat(product.stock_minimo) * 2) {
        return { class: 'badge-warning', text: 'Stock Medio' };
    } else {
        return { class: 'badge-success', text: 'Stock OK' };
    }
}

function calculateSellingPrice() {
  const purchasePrice = Number.parseFloat(document.getElementById("productPurchasePrice").value) || 0;
  const sellingPrice = purchasePrice * 1.3;
  document.getElementById("productSellingPrice").value = sellingPrice.toFixed(2);
}

function showMainTab(tabName) {
  document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
  document.getElementById(tabName + 'Tab').classList.add('active');
  document.querySelectorAll('.tab-nav-btn').forEach(b => b.classList.remove('active'));
  event.currentTarget.classList.add('active');
  
  // Limpiar facturador cada vez que se cambia a esa pestaña
  if (tabName === 'invoice') {
      // Limpiar la factura para empezar una nueva venta de cero.
      clearInvoice();
      loadAvailableProducts(); // Asegurarse de que la lista de productos esté actualizada.
  }
}

function updateUnitOptions() {
    const measurementTypes = {
        weight: [{ value: 'kg', label: 'Kilogramo (kg)' }, { value: 'g', label: 'Gramo (g)' }],
        volume: [{ value: 'L', label: 'Litro (L)' }, { value: 'mL', label: 'Mililitro (mL)' }],
        length: [{ value: 'm', label: 'Metro (m)' }, { value: 'cm', label: 'Centímetro (cm)' }],
        quantity: [{ value: 'u', label: 'Unidad (u)' }]
    };

    const measurementSelect = document.getElementById('productMeasurement');
    const unitSelect = document.getElementById('productUnit');
    const selectedType = measurementSelect.value;

    unitSelect.innerHTML = '';

    if (selectedType && measurementTypes[selectedType]) {
        measurementTypes[selectedType].forEach(unit => {
            const option = document.createElement('option');
            option.value = unit.value;
            option.textContent = unit.label;
            unitSelect.appendChild(option);
        });
    } else {
        unitSelect.innerHTML = '<option value="">Selecciona un tipo de medida</option>';
    }
}

function closeCashRegister() {
    const today = new Date().toISOString().slice(0, 10);
    const dailySales = sales.filter(s => s.fecha_venta.startsWith(today) && !s.caja_cerrada);
    const totalDailySales = dailySales.reduce((sum, s) => sum + parseFloat(s.total), 0);
    const totalDailyProfit = dailySales.reduce((sum, s) => sum + parseFloat(s.ganancia_total), 0);
    const totalDailyCosts = dailySales.reduce((sum, s) => sum + parseFloat(s.costo_total), 0);
    const dailyTransactions = dailySales.length;

    const dailySoldProducts = {};
    dailySales.forEach(sale => {
        if(sale.items){
            sale.items.forEach(item => {
                const productName = item.nombre || item.nombre_producto;
                if (productName && productName !== 'undefined') {
                    if (dailySoldProducts[productName]) {
                        dailySoldProducts[productName] += parseInt(item.cantidad);
                    } else {
                        dailySoldProducts[productName] = parseInt(item.cantidad);
                    }
                }
            });
        }
    });

    let productsSummaryHtml = '';
    if (Object.keys(dailySoldProducts).length > 0) {
        productsSummaryHtml = Object.entries(dailySoldProducts).map(([name, qty]) => {
            if (name === 'undefined') return '';
            return `
            <div class="product-summary-row">
                <div class="product-info">
                    <strong>${name}</strong>
                </div>
                <div class="product-quantity">${qty} u.</div>
            </div>
        `}).join('');
    } else {
        productsSummaryHtml = '<p class="empty-state">No se vendieron productos hoy.</p>';
    }

    const modalHTML = `
        <div id="cashClosureModal" class="modal-overlay">
            <div class="modal-content">
                <div class="modal-header">
                    <h3><i class="fas fa-cash-register"></i> Cierre de Caja del Día</h3>
                    <button class="modal-close" onclick="document.getElementById('cashClosureModal').remove()">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="closure-summary">
                        <div class="summary-section">
                            <h4>Resumen de Ventas</h4>
                            <div class="summary-grid">
                                <div class="summary-item">
                                    <span>Ventas Totales</span>
                                    <span class="amount">$${totalDailySales.toFixed(2)}</span>
                                </div>
                                <div class="summary-item">
                                    <span>Costo de Mercadería</span>
                                    <span class="amount">$${totalDailyCosts.toFixed(2)}</span>
                                </div>
                                <div class="summary-item total">
                                    <span>Ganancia Neta</span>
                                    <span class="amount text-green">$${totalDailyProfit.toFixed(2)}</span>
                                </div>
                                <div class="summary-item">
                                    <span>Transacciones</span>
                                    <span class="amount">${dailyTransactions}</span>
                                </div>
                            </div>
                        </div>
                        <div class="summary-section">
                            <h4>Productos Vendidos</h4>
                            <div class="products-summary">
                                ${productsSummaryHtml}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn-primary" onclick="confirmCashClosure()">Confirmar Cierre</button>
                </div>
            </div>
        </div>
    `;

    document.body.insertAdjacentHTML('beforeend', modalHTML);
}

async function confirmCashClosure() {
    try {
        const response = await fetch('../backend/index.php?accion=cerrar_caja', {
            method: 'POST'
        });
        const result = await response.json();

        if (result.error) {
            showNotification(result.error, 'error');
        } else {
            showNotification('Caja cerrada exitosamente. Las estadísticas de ventas diarias se han reiniciado.', 'success');
            document.getElementById('cashClosureModal').remove();
            clearInvoice(); // Reiniciar el facturador
            fetchData(); // Recargar datos
        }
    } catch (error) {
        showNotification('Error al cerrar la caja.', 'error');
        console.error(error);
    }
}

function loadMixAvailableProducts() {
    const container = document.getElementById('mixAvailableProducts');
    container.innerHTML = '';
    // Filtrar para mostrar solo productos con stock > 0.
    const inStockProducts = products.filter(p => parseFloat(p.stock) > 0);

    inStockProducts.forEach(product => {
        const item = createAvailableMixProductItem(product);
        container.appendChild(item);
    });
}

function createAvailableMixProductItem(product) {
    const div = document.createElement('div');
    div.className = `product-item`;
    div.innerHTML = `
        <div class="product-info">
            <h4>${product.nombre}</h4>
            <p>Costo: ${product.precio_compra}</p>
        </div>
        <div class="product-actions">
            <button class="btn-sm" onclick="addToMix(${product.id})">
                <i class="fas fa-plus"></i>
            </button>
        </div>
    `;
    return div;
}

function addToMix(productId) {
    const product = products.find(p => p.id == productId);
    if (!product) return;

    const existingItem = mixItems.find(item => item.producto_id == productId);

    if (existingItem) {
        existingItem.quantity++;
        existingItem.total_cost = existingItem.quantity * existingItem.cost;
    } else {
        mixItems.push({
            producto_id: product.id,
            productName: product.nombre,
            quantity: 1,
            cost: parseFloat(product.precio_compra),
            total_cost: parseFloat(product.precio_compra),
        });
    }
    updateMixDisplay();
}

function removeFromMix(productId) {
    mixItems = mixItems.filter(item => item.producto_id != productId);
    updateMixDisplay();
}

function updateMixDisplay() {
    const container = document.getElementById('mixItems');
    const formDiv = document.getElementById('mixForm');

    if (mixItems.length === 0) {
        container.innerHTML = '<p class="empty-state">No hay productos en el mix</p>';
        formDiv.style.display = 'none';
        return;
    }

    container.innerHTML = '';
    let totalCost = 0;

    mixItems.forEach(item => {
        const div = document.createElement('div');
        div.className = 'product-item';
        div.innerHTML = `
            <div class="product-info">
                <h4>${item.productName}</h4>
                <p>${item.quantity} x ${item.cost.toFixed(2)} = ${item.total_cost.toFixed(2)}</p>
            </div>
            <div class="product-actions">
                <button class="btn-sm" onclick="removeFromMix(${item.producto_id})">
                    <i class="fas fa-minus"></i>
                </button>
            </div>
        `;
        container.appendChild(div);
        totalCost += item.total_cost;
    });

    document.getElementById('mixPurchasePrice').value = totalCost.toFixed(2);
    calculateMixSellingPrice();
    formDiv.style.display = 'block';
}

function calculateMixSellingPrice() {
    const purchasePrice = parseFloat(document.getElementById('mixPurchasePrice').value) || 0;
    const sellingPrice = purchasePrice * 1.30; // 30% markup
    document.getElementById('mixSellingPrice').value = sellingPrice.toFixed(2);
}

async function createMix() {
    const mixName = document.getElementById('mixName').value.trim();
    if (!mixName) {
        showNotification('Por favor, dale un nombre al mix.', 'warning');
        return;
    }

    if (mixItems.length === 0) {
        showNotification('No hay productos en el mix.', 'warning');
        return;
    }

    let brandId = brands.length > 0 ? brands[0].id : null;
    let categoryId = categories.length > 0 ? categories[0].id : null;

    const generalBrand = brands.find(b => b.nombre.toLowerCase() === 'general');
    if(generalBrand) brandId = generalBrand.id;
    const generalCategory = categories.find(c => c.nombre.toLowerCase() === 'mix' || c.nombre.toLowerCase() === 'varios');
    if(generalCategory) categoryId = generalCategory.id;

    if (!brandId || !categoryId) {
        showNotification('No se encontró una marca o categoría para el mix. Por favor, crea una marca (ej. "General") y una categoría (ej. "Mix").', 'error');
        return;
    }

    const mixData = {
        product: {
            nombre: mixName,
            codigo: `MIX-${Date.now().toString().slice(-6)}`,
            marca_id: brandId,
            categoria_id: categoryId,
            proveedor_id: null,
            stock: 0,
            stock_minimo: 0,
            precio_compra: document.getElementById('mixPurchasePrice').value,
            precio_venta: document.getElementById('mixSellingPrice').value,
            tipo_medida: 'quantity',
            unidad: 'u',
        },
        components: mixItems
    };

    try {
        const response = await fetch('../backend/index.php?accion=crear_mix', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(mixData)
        });
        const result = await response.json();
        if (result.error) {
            showNotification(result.error, 'error');
...
            return;
        }

        mixItems = [];
        updateMixDisplay();
        document.getElementById('mixName').value = '';

        showNotification('Mix creado exitosamente como un nuevo producto.');
        // Recargar todos los datos para asegurar que el nuevo mix y el stock actualizado de los componentes se reflejen en toda la aplicación.
        await window.fetchData();

    } catch (error) {
        showNotification('Error al crear el mix.', 'error');
        console.error(error);
    }
}

function loadFractionableProducts() {
    const container = document.getElementById('fractionProducts');
    container.innerHTML = '';
    // Filtrar productos que no son por unidad y que tienen stock
    const fractionableProducts = products.filter(p => p.tipo_medida !== 'quantity' && parseFloat(p.stock) > 0);

    if (fractionableProducts.length === 0) {
        container.innerHTML = '<p class="empty-state">No hay productos para fraccionar. Agrega productos medidos por peso, volumen o longitud.</p>';
        return;
    }

    fractionableProducts.forEach(product => {
        const card = document.createElement('div');
        card.className = 'fraction-card';
        card.innerHTML = `
            <div class="fraction-card-header">
                <h4>${product.nombre}</h4>
                <p>Stock actual: ${formatStock(product.stock, product.unidad)}</p>
            </div>
            <div class="fraction-card-content">
                <div class="price-grid">
                    <div class="price-item">
                        <div class="price-label">Precio Compra / ${product.unidad}</div>
                        <div class="price-value">${product.precio_compra}</div>
                    </div>
                    <div class="price-item">
                        <div class="price-label">Precio Venta / ${product.unidad}</div>
                        <div class="price-value">${product.precio_venta}</div>
                    </div>
                </div>
                <button class="btn-primary" onclick="openFractionModal(${product.id})">
                    <i class="fas fa-cut"></i> Fraccionar
                </button>
            </div>
        `;
        container.appendChild(card);
    });
}

function openFractionModal(productId) {
    const product = products.find(p => p.id == productId);
    if (!product) return;

    currentFractioningProduct = product;

    let smallerUnitsOptions = '';
    if (product.unidad === 'kg') smallerUnitsOptions = '<option value="g">Gramos (g)</option>';
    if (product.unidad === 'L') smallerUnitsOptions = '<option value="mL">Mililitros (mL)</option>';
    if (product.unidad === 'm') smallerUnitsOptions = '<option value="cm">Centímetros (cm)</option>';
    
    if (!smallerUnitsOptions) {
        showNotification('Este producto no se puede fraccionar en unidades más pequeñas.', 'warning');
        return;
    }

    const modalHTML = `
    <div id="fractionModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Fraccionar: ${product.nombre}</h3>
                <button class="modal-close" onclick="document.getElementById('fractionModal').remove()">&times;</button>
            </div>
            <div class="modal-body">
                <p>Stock disponible: <strong>${formatStock(product.stock, product.unidad)}</strong></p>
                <div class="form-group">
                    <label>Cantidad de stock a fraccionar (en ${product.unidad})</label>
                    <input type="number" id="fractionAmount" placeholder="Ej: 1" min="0" max="${product.stock}" step="any">
                </div>
                <hr style="margin: 1rem 0;">
                <h4>Nuevo Producto Fraccionado</h4>
                <div class="form-group">
                    <label>Nombre del nuevo producto</label>
                    <input type="text" id="newFractionedProductName" placeholder="Ej: ${product.nombre} 500g">
                </div>
                <div class="grid-2">
                    <div class="form-group">
                        <label>Cantidad por paquete</label>
                        <input type="number" id="newUnitQuantity" placeholder="Ej: 500" min="0">
                    </div>
                    <div class="form-group">
                        <label>Unidad del nuevo paquete</label>
                        <select id="newUnit">${smallerUnitsOptions}</select>
                    </div>
                </div>
                <div id="fractionSummary" style="margin-top: 1rem;"></div>
            </div>
            <div class="modal-footer">
                <button class="btn-primary" onclick="fractionProduct(${product.id})">Confirmar Fraccionamiento</button>
            </div>
        </div>
    </div>
    `;
    document.body.insertAdjacentHTML('beforeend', modalHTML);

    document.getElementById('fractionAmount').addEventListener('input', updateFractionSummary);
    document.getElementById('newUnitQuantity').addEventListener('input', updateFractionSummary);
    document.getElementById('newUnit').addEventListener('change', updateFractionSummary);
}

function updateFractionSummary() {
    const summaryDiv = document.getElementById('fractionSummary');
    try {
        const fractionAmount = parseFloat(document.getElementById('fractionAmount').value);
        const newUnitQuantity = parseFloat(document.getElementById('newUnitQuantity').value);
        
        if (isNaN(fractionAmount) || isNaN(newUnitQuantity) || fractionAmount <= 0 || newUnitQuantity <= 0) {
            summaryDiv.innerHTML = '';
            return;
        }

        const product = currentFractioningProduct;
        const newUnit = document.getElementById('newUnit').value;
        const conversionFactor = unitConversions[product.unidad][newUnit];
        
        const totalNewUnits = (fractionAmount * conversionFactor) / newUnitQuantity;

        summaryDiv.innerHTML = `
            <p>Se crearán <strong>${Math.floor(totalNewUnits)}</strong> paquetes de <strong>${newUnitQuantity} ${newUnit}</strong>.</p>
            <p>Se consumirá <strong>${fractionAmount} ${product.unidad}</strong> del stock original.</p>
        `;
    } catch (e) {
        summaryDiv.innerHTML = '<p class="text-red">Error en los valores ingresados.</p>';
    }
}

async function fractionProduct(productId) {
    const product = products.find(p => p.id == productId);
    const fractionAmount = parseFloat(document.getElementById('fractionAmount').value);
    const newProductName = document.getElementById('newFractionedProductName').value.trim();
    const newUnitQuantity = parseFloat(document.getElementById('newUnitQuantity').value);
    const newUnit = document.getElementById('newUnit').value;

    if (!newProductName || isNaN(fractionAmount) || isNaN(newUnitQuantity) || fractionAmount <= 0 || newUnitQuantity <= 0) {
        showNotification('Por favor, completa todos los campos correctamente.', 'warning');
        return;
    }

    if (fractionAmount > product.stock) {
        showNotification('No hay suficiente stock para fraccionar la cantidad indicada.', 'error');
        return;
    }

    const conversionFactor = unitConversions[product.unidad][newUnit];
    const totalNewUnits = Math.floor((fractionAmount * conversionFactor) / newUnitQuantity);
    
    const costPerOriginalUnit = product.precio_compra / conversionFactor;
    const newPurchasePrice = costPerOriginalUnit * newUnitQuantity;
    const newSellingPrice = newPurchasePrice * 1.30;

    const fractionData = {
        original_product_id: product.id,
        amount_to_reduce: fractionAmount,
        new_product: {
            nombre: newProductName,
            codigo: `FRAC-${Date.now().toString().slice(-6)}`,
            marca_id: product.marca_id,
            categoria_id: product.categoria_id,
            stock: totalNewUnits,
            stock_minimo: 1,
            precio_compra: newPurchasePrice.toFixed(4),
            precio_venta: newSellingPrice.toFixed(2),
            tipo_medida: 'quantity', // El nuevo producto es por unidad
            unidad: 'u', // La unidad es 'unidad'
        }
    };

    try {
        const response = await fetch('../backend/index.php?accion=fraccionar_producto', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(fractionData)
        });
        const result = await response.json();

        if (result.error) {
            showNotification(result.error, 'error');
            return;
        }

        showNotification('Producto fraccionado exitosamente.');
        document.getElementById('fractionModal').remove();
        fetchData();

    } catch (error) {
        showNotification('Error al fraccionar el producto.', 'error');
        console.error(error);
    }
}

function loadFractionedProducts() {
    const container = document.getElementById('fractionedProducts');
    const section = document.getElementById('fractionedProductsSection');
    const fractioned = products.filter(p => p.codigo.startsWith('FRAC-'));

    if (fractioned.length === 0) {
        section.style.display = 'none';
        return;
    }

    container.innerHTML = '';
    section.style.display = 'block';

    fractioned.forEach(product => {
        const item = document.createElement('div');
        item.className = 'product-item fractioned';
        item.innerHTML = `
            <div class="product-info">
                <h4>${product.nombre}</h4>
                <p>Stock: ${formatStock(product.stock, product.unidad)} | Precio: ${product.precio_venta}</p>
            </div>
        `;
        container.appendChild(item);
    });
}

async function deleteProduct(productId) {
    if (!confirm('¿Estás seguro de que quieres eliminar este producto? Esta acción no se puede deshacer.')) {
        return;
    }

    try {
        const response = await fetch('../backend/index.php?accion=eliminar_producto', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: productId })
        });

        if (!response.ok) {
            const errorData = await response.json();
            showNotification(errorData.error, 'error');
            return;
        }

        const result = await response.json();

        if (result.error) {
            showNotification(result.error, 'error');
        } else {
            showNotification('Producto eliminado exitosamente.');
            fetchData(); // Recargar datos
        }
    } catch (error) {
        showNotification('Error al eliminar el producto.', 'error');
        console.error(error);
    }
}

function openEditProductModal(productId) {
    const product = products.find(p => p.id == productId);
    if (!product) return;

    const modalHTML = `
    <div id="editProductModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Editando: ${product.nombre}</h3>
                <button class="modal-close" onclick="document.getElementById('editProductModal').remove()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="editProductForm">
                    <div class="form-group">
                        <label for="editProductName">Nombre del Producto</label>
                        <input type="text" id="editProductName" value="${product.nombre}" required>
                    </div>
                    <div class="form-group">
                        <label for="editProductCode">Código</label>
                        <input type="text" id="editProductCode" value="${product.codigo}" required>
                    </div>
                     <div class="grid-2">
                        <div class="form-group">
                            <label for="editProductBrand">Marca</label>
                            <select id="editProductBrand" required></select>
                        </div>
                        <div class="form-group">
                            <label for="editProductCategory">Categoría</label>
                            <select id="editProductCategory" required></select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="editProductProvider">Proveedor</label>
                        <select id="editProductProvider" required></select>
                    </div>
                    <div class="grid-2">
                        <div class="form-group">
                            <label for="editProductStock">Stock</label>
                            <input type="number" id="editProductStock" value="${product.stock}" min="0" required>
                        </div>
                        <div class="form-group">
                            <label for="editProductMinStock">Stock Mínimo</label>
                            <input type="number" id="editProductMinStock" value="${product.stock_minimo}" min="0" required>
                        </div>
                    </div>
                    <div class="grid-2">
                        <div class="form-group">
                            <label for="editProductPurchasePrice">Precio de Compra</label>
                            <input type="number" id="editProductPurchasePrice" value="${product.precio_compra}" step="0.01" min="0" required>
                        </div>
                        <div class="form-group">
                            <label for="editProductSellingPrice">Precio de Venta</label>
                            <input type="number" id="editProductSellingPrice" value="${product.precio_venta}" step="0.01" min="0" required>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn-primary" onclick="updateProduct(${product.id})">Guardar Cambios</button>
            </div>
        </div>
    </div>
    `;
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    
    // Populate selects
    const brandSelect = document.getElementById('editProductBrand');
    brands.forEach(brand => {
        brandSelect.innerHTML += `<option value="${brand.id}" ${brand.id == product.marca_id ? 'selected' : ''}>${brand.nombre}</option>`;
    });

    const categorySelect = document.getElementById('editProductCategory');
    categories.forEach(category => {
        categorySelect.innerHTML += `<option value="${category.id}" ${category.id == product.categoria_id ? 'selected' : ''}>${category.nombre}</option>`;
    });

    const providerSelect = document.getElementById('editProductProvider');
    providers.forEach(provider => {
        providerSelect.innerHTML += `<option value="${provider.id}" ${provider.id == product.proveedor_id ? 'selected' : ''}>${provider.nombre}</option>`;
    });
}

async function updateProduct(productId) {
    const updatedProduct = {
        id: productId,
        nombre: document.getElementById('editProductName').value,
        codigo: document.getElementById('editProductCode').value,
        marca_id: document.getElementById('editProductBrand').value,
        categoria_id: document.getElementById('editProductCategory').value,
        proveedor_id: document.getElementById('editProductProvider').value,
        stock: document.getElementById('editProductStock').value,
        stock_minimo: document.getElementById('editProductMinStock').value,
        precio_compra: document.getElementById('editProductPurchasePrice').value,
        precio_venta: document.getElementById('editProductSellingPrice').value,
        // tipo_medida and unidad are not editable for now to keep it simple
        tipo_medida: products.find(p => p.id == productId).tipo_medida,
        unidad: products.find(p => p.id == productId).unidad,
    };

    try {
        const response = await fetch('../backend/index.php?accion=editar_producto', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(updatedProduct)
        });
        const result = await response.json();

        if (result.error) {
            showNotification(result.error, 'error');
        } else {
            showNotification('Producto actualizado exitosamente.');
            document.getElementById('editProductModal').remove();
            fetchData();
        }
    } catch (error) {
        showNotification('Error al actualizar el producto.', 'error');
        console.error(error);
    }
}

function openAdjustStockModal(productId, type) {
    const product = products.find(p => p.id == productId);
    if (!product) return;

    const modalHTML = `
    <div id="adjustStockModal" class="modal-overlay">
        <div class="modal-content" style="max-width: 400px;">
            <div class="modal-header">
                <h3>Ajustar Stock: ${product.nombre}</h3>
                <button class="modal-close" onclick="document.getElementById('adjustStockModal').remove()">&times;</button>
            </div>
            <div class="modal-body">
                <p>Stock actual: <strong>${formatStock(product.stock, product.unidad)}</strong></p>
                <div class="form-group">
                    <label for="adjustQuantity">Cantidad a ${type === 'add' ? 'agregar' : 'quitar'}</label>
                    <input type="number" id="adjustQuantity" min="0" ${type === 'remove' ? `max="${product.stock}"` : ''} required>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-primary" onclick="adjustStock(${product.id}, '${type}')">Confirmar Ajuste</button>
            </div>
        </div>
    </div>
    `;
    document.body.insertAdjacentHTML('beforeend', modalHTML);
}

async function adjustStock(productId, type) {
    const quantity = parseFloat(document.getElementById('adjustQuantity').value);
    if (isNaN(quantity) || quantity <= 0) {
        showNotification('Por favor, ingresa una cantidad válida.', 'warning');
        return;
    }

    try {
        const response = await fetch('../backend/index.php?accion=ajustar_stock', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: productId, cantidad: quantity, tipo: type })
        });

        const result = await response.json();

        if (result.error) {
            showNotification(result.error, 'error');
        } else {
            showNotification('Stock ajustado exitosamente.');
            document.getElementById('adjustStockModal').remove();
            fetchData();
        }
    } catch (error) {
        showNotification('Error al ajustar el stock.', 'error');
        console.error(error);
    }
}

// Funciones de UI (toggleTheme, toggleUserMenu) pueden permanecer igual
function toggleTheme() {
    const currentTheme = document.body.getAttribute('data-theme');
    const newTheme = currentTheme === 'light' ? 'dark' : 'light';
    document.body.setAttribute('data-theme', newTheme);
    localStorage.setItem('theme', newTheme);

    const themeIcon = document.querySelector('.theme-toggle i');
    if (themeIcon) {
        themeIcon.className = newTheme === 'light' ? 'fas fa-moon' : 'fas fa-sun';
    }
}

function toggleUserMenu() {
    const userDropdown = document.getElementById('userDropdown');
    if (userDropdown) {
        userDropdown.classList.toggle('show');
    }
}

async function cancelSale(saleId) {
    if (!confirm(`¿Estás seguro de que quieres anular la Venta #${saleId}? Esta acción es irreversible y devolverá los productos al stock.`)) {
        return;
    }

    try {
        const response = await fetch('../backend/index.php?accion=cancelar_venta', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: saleId })
        });

        const result = await response.json();

        if (result.error) {
            showNotification(result.error, 'error');
        } else {
            showNotification('Venta anulada exitosamente.');
            fetchData(); // Reload all data to reflect the change
        }
    } catch (error) {
        showNotification('Error de conexión al anular la venta.', 'error');
        console.error('Error cancelling sale:', error);
    }
}

function updateDailySummary() {
    const today = new Date().toISOString().slice(0, 10);
    const dailySales = sales.filter(s => s.fecha_venta.startsWith(today) && !s.caja_cerrada);

    const totalDailySales = dailySales.reduce((sum, s) => sum + parseFloat(s.total), 0);
    const totalDailyProfit = dailySales.reduce((sum, s) => sum + parseFloat(s.ganancia_total), 0);
    const totalDailyCosts = dailySales.reduce((sum, s) => sum + parseFloat(s.costo_total), 0);
    const dailyTransactions = dailySales.length;

    // Top dashboard cards for TODAY
    document.getElementById('salesToday').textContent = `$${totalDailySales.toFixed(2)}`;
    document.getElementById('profitToday').textContent = `$${totalDailyProfit.toFixed(2)}`;

    // "Control de Ventas" -> "Ventas del Dia" card
    document.getElementById('dailySales').textContent = `$${totalDailySales.toFixed(2)}`;
    document.getElementById('dailyTransactions').textContent = `${dailyTransactions} transacciones`;
    document.getElementById('dailyProfit').textContent = `$${totalDailyProfit.toFixed(2)}`;

    // "Control de Ventas" -> "Cierre de caja" summary
    document.getElementById('closeDailySales').textContent = `$${totalDailySales.toFixed(2)}`;
    document.getElementById('closeDailyCosts').textContent = `$${totalDailyCosts.toFixed(2)}`;
    document.getElementById('closeDailyProfit').textContent = `$${totalDailyProfit.toFixed(2)}`;
    document.getElementById('closeDailyTransactions').textContent = dailyTransactions;

    // "Control de Ventas" -> "Productos vendidos hoy"
    const dailyProductsSummaryContainer = document.getElementById('dailyProductsSummary');
    dailyProductsSummaryContainer.innerHTML = '';
    const dailySoldProducts = {};
    dailySales.forEach(sale => {
        if (sale.items) {
            sale.items.forEach(item => {
                const productName = item.nombre || item.nombre_producto;
                if (productName && productName !== 'undefined') {
                    if (dailySoldProducts[productName]) {
                        dailySoldProducts[productName] += parseInt(item.cantidad);
                    } else {
                        dailySoldProducts[productName] = parseInt(item.cantidad);
                    }
                }
            });
        }
    });

}

window.addBrand = addBrand;
window.addCategory = addCategory;
window.processInvoice = processInvoice;
window.showMainTab = showMainTab;
window.updateUnitOptions = updateUnitOptions;
window.calculateSellingPrice = calculateSellingPrice;

window.addToInvoice = addToInvoice;
window.filterProducts = filterProducts;
window.filterInvoiceProducts = filterInvoiceProducts;
window.closeCashRegister = closeCashRegister;
window.confirmCashClosure = confirmCashClosure;
window.addToMix = addToMix;
window.removeFromMix = removeFromMix;
window.calculateMixSellingPrice = calculateMixSellingPrice;
window.createMix = createMix;
window.openFractionModal = openFractionModal;
window.fractionProduct = fractionProduct;
window.updateFractionSummary = updateFractionSummary;
window.openEditProductModal = openEditProductModal;
window.updateProduct = updateProduct;
window.deleteProduct = deleteProduct;
window.openAdjustStockModal = openAdjustStockModal;
window.adjustStock = adjustStock;
window.toggleTheme = toggleTheme;
window.toggleUserMenu = toggleUserMenu;
window.deleteItem = deleteItem;
window.cancelSale = cancelSale;
window.clearInvoice = clearInvoice;