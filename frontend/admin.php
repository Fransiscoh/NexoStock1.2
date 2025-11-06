<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'administrador') {
    header('Location: login.php');
    exit();
}

// Cargar configuración
$configFile = __DIR__ . '/../config.json';
$config = [];
if (file_exists($configFile)) {
    $config = json_decode(file_get_contents($configFile), true);
}
$appName = $config['app_name'] ?? 'NexoStock';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($appName); ?> - Panel de Administración</title>
    <link rel="icon" type="image/jpeg" href="../NexoStock.jpg">
    <link rel="apple-touch-icon" href="../NexoStock.jpg">
    <link rel="stylesheet" href="assets/styles/index.css"> <!-- Re-use main styles -->
    <link rel="stylesheet" href="assets/styles/admin.css"> <!-- Admin specific styles -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div id="mainApp" class="main-app">
        <!-- Header -->
        <header class="app-header">
            <div class="header-content">
                <div class="header-left">
                    <h1><?php echo htmlspecialchars($appName); ?> - Panel de Administración</h1>
                    <p>Bienvenido, <span id="userName">
                        <?php echo isset($_SESSION['usuario_nombre']) ? htmlspecialchars($_SESSION['usuario_nombre']) : 'Administrador'; ?>
                    </span></p>
                </div>
                <div class="header-right">
                    
                    <div class="user-menu">
                        <button class="user-btn" onclick="toggleUserMenu()">
                            <i class="fas fa-user"></i>
                            <span id="headerUserName">
                                <?php echo isset($_SESSION['usuario_email']) ? htmlspecialchars($_SESSION['usuario_email']) : 'Administrador'; ?>
                            </span>
                        </button>
                        <div id="userDropdown" class="user-dropdown">
                            <a href="index.php">
                                <i class="fas fa-home"></i> Volver al Panel Principal
                            </a>
                        <a href="/backend/logout.php">
                                <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Admin Content -->
        <div class="admin-content">
            <div class="tabs-container">
                <div class="tabs-nav">
                    <button class="tab-nav-btn active" onclick="showAdminTab('users')">
                        <i class="fas fa-users"></i>
                        Gestión de Usuarios
                    </button>
                    <button class="tab-nav-btn" onclick="showAdminTab('products')">
                        <i class="fas fa-box-open"></i>
                        Gestión de Productos
                    </button>
                    <button class="tab-nav-btn" onclick="showAdminTab('inventory')">
                        <i class="fas fa-warehouse"></i>
                        Gestión de Inventario
                    </button>
                    <button class="tab-nav-btn" onclick="showAdminTab('sales')">
                        <i class="fas fa-chart-line"></i>
                        Ventas e Informes
                    </button>
                    <button class="tab-nav-btn" onclick="showAdminTab('suppliers')">
                        <i class="fas fa-truck"></i>
                        Gestión de Proveedores
                    </button>
                    <button class="tab-nav-btn" onclick="showAdminTab('settings')">
                        <i class="fas fa-cogs"></i>
                        Configuración
                    </button>
                </div>

                <div class="tabs-content">
                    <!-- Gestión de Usuarios Tab -->
                    <div id="usersTab" class="tab-pane active">
                        <div class="card">
                            <div class="card-header">
                                <div class="card-header-left">
                                    <h3>Usuarios del Sistema</h3>
                                    <p>Gestiona los usuarios y sus roles</p>
                                </div>
                                <div class="card-header-right">
                                    <button class="btn btn-primary" onclick="openUserModal()">
                                        <i class="fas fa-plus"></i> Añadir Usuario
                                    </button>
                                </div>
                            </div>
                            <div class="card-content">
                                <div class="table-container">
                                    <table id="usersTable">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Nombre</th>
                                                <th>Apellido</th>
                                                <th>Email</th>
                                                <th>Rol Actual</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody id="usersTableBody">
                                            <!-- Users will be loaded dynamically here -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- User Modal -->
                    <div id="userModal" class="modal">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h3 id="userModalTitle">Añadir Usuario</h3>
                                <button class="close-btn" onclick="closeUserModal()">&times;</button>
                            </div>
                            <div class="modal-body">
                                <form id="userForm">
                                    <input type="hidden" id="userId" name="id">
                                    <div class="form-group">
                                        <label for="nombre">Nombre</label>
                                        <input type="text" id="nombre" name="nombre" required autocomplete="off">
                                    </div>
                                    <div class="form-group">
                                        <label for="apellido">Apellido</label>
                                        <input type="text" id="apellido" name="apellido" required autocomplete="off">
                                    </div>
                                    <div class="form-group">
                                        <label for="email">Email</label>
                                        <input type="email" id="email" name="email" required autocomplete="off">
                                    </div>
                                    <div class="form-group">
                                        <label for="password">Contraseña</label>
                                        <input type="password" id="password" name="password" autocomplete="off">
                                        <small id="passwordHelpText" class="form-text text-muted" style="display: none;">Deja en blanco para mantener la contraseña actual.</small>
                                    </div>
                                    <div class="form-group">
                                        <label for="rol">Rol</label>
                                        <select id="rol" name="rol" class="form-control">
                                            <option value="usuario">Usuario</option>
                                            <option value="administrador">Administrador</option>
                                        </select>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="submit" class="btn btn-primary">Guardar</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Gestión de Productos Tab -->
                    <div id="productsTab" class="tab-pane">
                        <div class="card">
                            <div class="card-header">
                                <div class="card-header-left">
                                    <h3>Gestión de Productos</h3>
                                    <p>Añadir, editar y eliminar productos.</p>
                                </div>
                                <div class="card-header-right">
                                    <button class="btn btn-primary" onclick="openProductModal()">
                                        <i class="fas fa-plus"></i> Añadir Producto
                                    </button>
                                </div>
                            </div>
                            <div class="card-content">
                                <div class="table-container">
                                    <table id="productsTable">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Código</th>
                                                <th>Nombre</th>
                                                <th>Marca</th>
                                                <th>Categoría</th>
                                                <th>Stock</th>
                                                <th>Precio Venta</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody id="productsTableBody">
                                            <!-- Products will be loaded dynamically here -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card mt-4">
                            <div class="card-header">
                                <h3>Gestión de Marcas y Categorías</h3>
                            </div>
                            <div class="card-content">
                                <div class="grid-container">
                                    <!-- Marcas -->
                                    <div class="grid-item">
                                        <h4>Marcas</h4>
                                        <div class="table-container">
                                            <table id="marcasTable">
                                                <thead>
                                                    <tr>
                                                        <th>ID</th>
                                                        <th>Nombre</th>
                                                        <th>Acciones</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="marcasTableBody">
                                                    <!-- Marcas will be loaded dynamically here -->
                                                </tbody>
                                            </table>
                                        </div>
                                         <div class="mt-2">
                                            <button class="btn btn-primary" onclick="openMarcaModal()">
                                                <i class="fas fa-plus"></i> Añadir Marca
                                            </button>
                                        </div>
                                    </div>
                                    <!-- Categorías -->
                                    <div class="grid-item">
                                        <h4>Categorías</h4>
                                        <div class="table-container">
                                            <table id="categoriasTable">
                                                <thead>
                                                    <tr>
                                                        <th>ID</th>
                                                        <th>Nombre</th>
                                                        <th>Acciones</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="categoriasTableBody">
                                                    <!-- Categorías will be loaded dynamically here -->
                                                </tbody>
                                            </table>
                                        </div>
                                        <div class="mt-2">
                                            <button class="btn btn-primary" onclick="openCategoriaModal()">
                                                <i class="fas fa-plus"></i> Añadir Categoría
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Product Modal -->
                    <div id="productModal" class="modal">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h3 id="productModalTitle">Añadir Producto</h3>
                                <button class="close-btn" onclick="closeProductModal()">&times;</button>
                            </div>
                            <div class="modal-body">
                                <form id="productForm">
                                    <input type="hidden" id="productoId" name="id">
                                    <div class="form-group">
                                        <label for="productoNombre">Nombre</label>
                                        <input type="text" id="productoNombre" name="nombre" required autocomplete="off">
                                    </div>
                                    <div class="form-group">
                                        <label for="productoCodigo">Código</label>
                                        <input type="text" id="productoCodigo" name="codigo" autocomplete="off">
                                    </div>
                                    <div class="form-group">
                                        <label for="productoMarca">Marca</label>
                                        <select id="productoMarca" name="marca_id" required></select>
                                    </div>
                                    <div class="form-group">
                                        <label for="productoCategoria">Categoría</label>
                                        <select id="productoCategoria" name="categoria_id" required></select>
                                    </div>
                                    <div class="form-group">
                                        <label for="productoProveedor">Proveedor</label>
                                        <select id="productoProveedor" name="proveedor_id" required></select>
                                    </div>
                                    <div class="form-group">
                                        <label for="productoStock">Stock</label>
                                        <input type="number" id="productoStock" name="stock" value="0" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="productoStockMinimo">Stock Mínimo</label>
                                        <input type="number" id="productoStockMinimo" name="stock_minimo" value="0" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="productoPrecioCompra">Precio de Compra</label>
                                        <input type="number" step="0.01" id="productoPrecioCompra" name="precio_compra" value="0.00" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="productoPrecioVenta">Precio de Venta</label>
                                        <input type="number" step="0.01" id="productoPrecioVenta" name="precio_venta" value="0.00" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="productoTipoMedida">Tipo de Medida</label>
                                        <input type="text" id="productoTipoMedida" name="tipo_medida" autocomplete="off">
                                    </div>
                                    <div class="form-group">
                                        <label for="productoUnidad">Unidad</label>
                                        <input type="text" id="productoUnidad" name="unidad" autocomplete="off">
                                    </div>
                                    <div class="modal-footer">
                                        <button type="submit" class="btn btn-primary">Guardar</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Marca/Categoria Modal -->
                    <div id="itemModal" class="modal">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h3 id="itemModalTitle">Añadir Marca</h3>
                                <button class="close-btn" onclick="closeItemModal()">&times;</button>
                            </div>
                            <div class="modal-body">
                                <form id="itemForm">
                                    <input type="hidden" id="itemId" name="id">
                                    <input type="hidden" id="itemType" name="type">
                                    <div class="form-group">
                                        <label for="itemName">Nombre</label>
                                        <input type="text" id="itemName" name="nombre" required autocomplete="off">
                                    </div>
                                    <div class="modal-footer">
                                        <button type="submit" class="btn btn-primary">Guardar</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Gestión de Inventario Tab -->
                    <div id="inventoryTab" class="tab-pane">
                        <div class="card">
                            <div class="card-header">
                                <h3>Ajuste de Stock</h3>
                                <p>Modifica el stock de los productos de forma manual.</p>
                            </div>
                            <div class="card-content">
                                <div class="table-container">
                                    <table id="stockTable">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Producto</th>
                                                <th>Stock Actual</th>
                                                <th>Ajuste Manual</th>
                                            </tr>
                                        </thead>
                                        <tbody id="stockTableBody">
                                            <!-- Stock data will be loaded dynamically here -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="card mt-4">
                            <div class="card-header">
                                <h3>Registro de Auditoría de Inventario</h3>
                                <p>Historial de todos los movimientos de stock.</p>
                            </div>
                            <div class="card-content">
                                <div class="table-container">
                                    <table id="auditLogTable">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Fecha</th>
                                                <th>Producto</th>
                                                <th>Usuario</th>
                                                <th>Tipo de Movimiento</th>
                                                <th>Cantidad</th>
                                                <th>Stock Anterior</th>
                                                <th>Stock Nuevo</th>
                                            </tr>
                                        </thead>
                                        <tbody id="auditLogTableBody">
                                            <!-- Audit log will be loaded dynamically here -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Ventas e Informes Tab -->
                    <div id="salesTab" class="tab-pane">
                        <div class="card">
                            <div class="card-header">
                                <h3>Historial de Ventas</h3>
                                <p>Consulta todas las ventas registradas en el sistema.</p>
                            </div>
                            <div class="card-content">
                                <div class="sales-filters">
                                    <div class="form-group">
                                        <label for="salesSearch">Buscar</label>
                                        <input type="text" id="salesSearch" placeholder="Buscar por ID, total o vendedor...">
                                    </div>
                                    <div class="form-group">
                                        <label for="salesDateFrom">Desde</label>
                                        <input type="date" id="salesDateFrom">
                                    </div>
                                    <div class="form-group">
                                        <label for="salesDateTo">Hasta</label>
                                        <input type="date" id="salesDateTo">
                                    </div>
                                    <button class="btn btn-primary" onclick="applySalesFilters()">Filtrar</button>
                                </div>
                                <div class="table-container">
                                    <table id="salesTable">
                                        <thead>
                                            <tr>
                                                <th>ID Venta</th>
                                                <th>Fecha</th>
                                                <th>Total</th>
                                                <th>Ganancia</th>
                                                <th>Vendido por</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody id="salesTableBody">
                                            <!-- Sales will be loaded dynamically here -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sale Detail Modal -->
                    <div id="saleDetailModal" class="modal">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h3 id="saleDetailModalTitle">Detalles de la Venta</h3>
                                <button class="close-btn" onclick="closeSaleDetailModal()">&times;</button>
                            </div>
                            <div class="modal-body" id="saleDetailBody">
                                <div id="saleDetailContent">
                                    <!-- Sale details will be populated here -->
                                </div>
                                <h4 class="mt-4">Productos Vendidos</h4>
                                <div class="table-container">
                                    <table id="saleItemsTable">
                                        <thead>
                                            <tr>
                                                <th>Producto</th>
                                                <th>Cantidad</th>
                                                <th>Precio Unit.</th>
                                                <th>Total</th>
                                            </tr>
                                        </thead>
                                        <tbody id="saleItemsTableBody">
                                            <!-- Sale items will be populated here -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Gestión de Proveedores Tab -->
                    <div id="suppliersTab" class="tab-pane">
                        <div class="card">
                            <div class="card-header">
                                <div class="card-header-left">
                                    <h3>Gestión de Proveedores</h3>
                                    <p>Añadir, editar y eliminar proveedores.</p>
                                </div>
                                <div class="card-header-right">
                                    <button class="btn btn-primary" onclick="openSupplierModal()">
                                        <i class="fas fa-plus"></i> Añadir Proveedor
                                    </button>
                                </div>
                            </div>
                            <div class="card-content">
                                <div class="table-container">
                                    <table id="suppliersTable">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Nombre</th>
                                                <th>Contacto</th>
                                                <th>Teléfono</th>
                                                <th>Email</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody id="suppliersTableBody">
                                            <!-- Suppliers will be loaded dynamically here -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Supplier Modal -->
                    <div id="supplierModal" class="modal">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h3 id="supplierModalTitle">Añadir Proveedor</h3>
                                <button class="close-btn" onclick="closeSupplierModal()">&times;</button>
                            </div>
                            <div class="modal-body">
                                <form id="supplierForm">
                                    <input type="hidden" id="proveedorId" name="id">
                                    <div class="form-group">
                                        <label for="proveedorNombre">Nombre</label>
                                        <input type="text" id="proveedorNombre" name="nombre" required autocomplete="off">
                                    </div>
                                    <div class="form-group">
                                        <label for="proveedorContacto">Contacto</label>
                                        <input type="text" id="proveedorContacto" name="contacto" autocomplete="off">
                                    </div>
                                    <div class="form-group">
                                        <label for="proveedorTelefono">Teléfono</label>
                                        <input type="text" id="proveedorTelefono" name="telefono" autocomplete="off">
                                    </div>
                                    <div class="form-group">
                                        <label for="proveedorEmail">Email</label>
                                        <input type="email" id="proveedorEmail" name="email" autocomplete="off">
                                    </div>
                                    <div class="form-group">
                                        <label for="proveedorDireccion">Dirección</label>
                                        <input type="text" id="proveedorDireccion" name="direccion" autocomplete="off">
                                    </div>
                                    <div class="modal-footer">
                                        <button type="submit" class="btn btn-primary">Guardar</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Configuración Tab -->
                    <div id="settingsTab" class="tab-pane">
                        <div class="card">
                            <div class="card-header">
                                <h3>Copia de Seguridad y Restauración</h3>
                                <p>Realiza y gestiona copias de seguridad de la base de datos y los archivos.</p>
                            </div>
                            <div class="card-content">
                                <div class="grid-container">
                                    <div class="grid-item">
                                        <h4>Base de Datos</h4>
                                        <p>Crea una copia de seguridad completa de la base de datos.</p>
                                        <button class="btn btn-primary" onclick="createDatabaseBackup()"><i class="fas fa-database"></i> Crear Backup DB</button>
                                    </div>
                                    <div class="grid-item">
                                        <h4>Restaurar</h4>
                                        <p>Sube un archivo de backup para restaurar el sistema.</p>
                                        <input type="file" id="backupFile" class="form-control">
                                        <button class="btn btn-secondary mt-2" onclick="restoreBackup()"><i class="fas fa-upload"></i> Restaurar desde Archivo</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card mt-4">
                            <div class="card-header">
                                <h3>Configuración Global</h3>
                                <p>Modifica los parámetros globales del sistema.</p>
                            </div>
                            <div class="card-content">
                                <form id="globalSettingsForm">
                                    <div class="form-group">
                                        <label for="settingAppName">Nombre de la Aplicación</label>
                                        <input type="text" id="settingAppName" name="app_name" class="form-control" value="<?php echo htmlspecialchars($appName); ?>" autocomplete="off">
                                    </div>
                                    <div class="form-group">
                                        <label for="settingMaintenance">Modo Mantenimiento</label>
                                        <select id="settingMaintenance" name="maintenance_mode" class="form-control">
                                            <option value="0">Inactivo</option>
                                            <option value="1">Activo</option>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Guardar Configuración</button>
                                </form>
                            </div>
                        </div>

                        <div class="card mt-4">
                            <div class="card-header">
                                <h3>Registros del Sistema</h3>
                                <p>Revisa los registros de errores y eventos del sistema.</p>
                            </div>
                            <div class="card-content">
                                <div class="table-container" style="max-height: 400px; overflow-y: auto;">
                                    <table id="systemLogsTable">
                                        <thead>
                                            <tr>
                                                <th>Fecha</th>
                                                <th>Nivel</th>
                                                <th>Mensaje</th>
                                            </tr>
                                        </thead>
                                        <tbody id="systemLogsTableBody">
                                            <!-- Log entries will be loaded here -->
                                            <tr>
                                                <td colspan="3">No hay registros para mostrar.</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/common.js"></script> <!-- Re-use common JS functions like toggleTheme, toggleUserMenu -->
    <script src="assets/js/admin.js"></script> <!-- Admin specific JS -->
</body>
</html>