<?php
session_start();
require 'db_connect.php';
require __DIR__ . '/mail.config.php'; // carga la config

// Función para verificar el rol del usuario
function checkRole($allowedRoles) {
    if (!isset($_SESSION['usuario_rol'])) {
        http_response_code(403);
        echo json_encode(['error' => 'Acceso denegado: Rol no definido.']);
        exit();
    }

    if (!in_array($_SESSION['usuario_rol'], $allowedRoles)) {
        http_response_code(403);
        echo json_encode(['error' => 'Acceso denegado: No tienes los permisos necesarios.']);
        exit();
    }
}

header('Content-Type: application/json');

// Verificar autenticación para todas las acciones excepto login y registro
$accion = $_GET['accion'] ?? '';
if (!isset($_SESSION['usuario_id']) && $accion !== 'login' && $accion !== 'register') {
    echo json_encode(['error' => 'No autenticado']);
    exit();
}

$id_usuario = $_SESSION['usuario_id'] ?? null;

try {
    switch ($accion) {
        case 'bootstrap':
            checkRole(['administrador', 'usuario']);

            $user_role = $_SESSION['usuario_rol'] ?? null;

            if ($user_role === 'usuario') {
                $stmt_productos = $pdo->prepare("SELECT p.*, m.nombre as marca, c.nombre as categoria, pr.nombre as proveedor FROM productos p LEFT JOIN marcas m ON p.marca_id = m.id LEFT JOIN categorias c ON p.categoria_id = c.id LEFT JOIN proveedores pr ON p.proveedor_id = pr.id WHERE p.creado_por = ?");
                $stmt_productos->execute([$id_usuario]);
                $productos = $stmt_productos->fetchAll(PDO::FETCH_ASSOC);
                $stmt_marcas = $pdo->prepare("SELECT * FROM marcas WHERE creado_por = ?");
                $stmt_marcas->execute([$id_usuario]);
                $marcas = $stmt_marcas->fetchAll(PDO::FETCH_ASSOC);
                $stmt_categorias = $pdo->prepare("SELECT * FROM categorias WHERE creado_por = ?");
                $stmt_categorias->execute([$id_usuario]);
                $categorias = $stmt_categorias->fetchAll(PDO::FETCH_ASSOC);
                $stmt_proveedores = $pdo->prepare("SELECT * FROM proveedores WHERE creado_por = ?");
                $stmt_proveedores->execute([$id_usuario]);
                $proveedores = $stmt_proveedores->fetchAll(PDO::FETCH_ASSOC);
                $stmt_ventas = $pdo->prepare("SELECT * FROM ventas WHERE creado_por = ? ORDER BY fecha_venta DESC");
                $stmt_ventas->execute([$id_usuario]);
                $ventas = $stmt_ventas->fetchAll(PDO::FETCH_ASSOC);

                $stmt_items = $pdo->prepare("SELECT vp.*, p.nombre FROM venta_productos vp JOIN productos p ON vp.producto_id = p.id WHERE vp.venta_id = ?");

                foreach ($ventas as &$venta) {
                    $stmt_items->execute([$venta['id']]);
                    $venta['items'] = $stmt_items->fetchAll(PDO::FETCH_ASSOC);
                }
                unset($venta);
            } else {
                $productos = $pdo->query("SELECT p.*, m.nombre as marca, c.nombre as categoria, pr.nombre as proveedor FROM productos p LEFT JOIN marcas m ON p.marca_id = m.id LEFT JOIN categorias c ON p.categoria_id = c.id LEFT JOIN proveedores pr ON p.proveedor_id = pr.id")->fetchAll(PDO::FETCH_ASSOC);
                $marcas = $pdo->query("SELECT * FROM marcas")->fetchAll(PDO::FETCH_ASSOC);
                $categorias = $pdo->query("SELECT * FROM categorias")->fetchAll(PDO::FETCH_ASSOC);
                $proveedores = $pdo->query("SELECT * FROM proveedores")->fetchAll(PDO::FETCH_ASSOC);
                $ventas = $pdo->query("SELECT v.*, u.nombre as vendido_por FROM ventas v JOIN usuarios u ON v.creado_por = u.id ORDER BY v.fecha_venta DESC")->fetchAll(PDO::FETCH_ASSOC);
                
                $stmt_items = $pdo->prepare("SELECT vp.*, p.nombre as nombre_producto FROM venta_productos vp JOIN productos p ON vp.producto_id = p.id WHERE vp.venta_id = ?");

                foreach ($ventas as &$venta) {
                    $stmt_items->execute([$venta['id']]);
                    $venta['items'] = $stmt_items->fetchAll(PDO::FETCH_ASSOC);
                }
                unset($venta);
            }

            echo json_encode([
                'productos' => $productos,
                'marcas' => $marcas,
                'categorias' => $categorias,
                'proveedores' => $proveedores,
                'ventas' => $ventas,
                'user_role' => $user_role // Add user role
            ]);
            break;

        case 'agregar_producto':
            checkRole(['administrador', 'usuario']);
            $data = json_decode(file_get_contents('php://input'), true);
            
            $proveedor_id = isset($data['proveedor_id']) && !empty($data['proveedor_id']) ? $data['proveedor_id'] : null;

            $stmt = $pdo->prepare("INSERT INTO productos (nombre, codigo, marca_id, categoria_id, proveedor_id, stock, stock_minimo, precio_compra, precio_venta, tipo_medida, unidad, creado_por) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $data['nombre'],
                $data['codigo'],
                $data['marca_id'],
                $data['categoria_id'],
                $proveedor_id,
                $data['stock'],
                $data['stock_minimo'],
                $data['precio_compra'],
                $data['precio_venta'],
                $data['tipo_medida'],
                $data['unidad'],
                $id_usuario
            ]);
            
            $id_producto = $pdo->lastInsertId();
            $producto = $pdo->query("SELECT p.*, m.nombre as marca, c.nombre as categoria, pr.nombre as proveedor FROM productos p LEFT JOIN marcas m ON p.marca_id = m.id LEFT JOIN categorias c ON p.categoria_id = c.id LEFT JOIN proveedores pr ON p.proveedor_id = pr.id WHERE p.id = $id_producto")->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode($producto);
            break;

        case 'editar_producto':
            checkRole(['administrador', 'usuario']);
            $data = json_decode(file_get_contents('php://input'), true);
            $id_producto = $data['id'];

            $proveedor_id = isset($data['proveedor_id']) && !empty($data['proveedor_id']) ? $data['proveedor_id'] : null;

            $stmt = $pdo->prepare("UPDATE productos SET nombre = ?, codigo = ?, marca_id = ?, categoria_id = ?, proveedor_id = ?, stock = ?, stock_minimo = ?, precio_compra = ?, precio_venta = ?, tipo_medida = ?, unidad = ? WHERE id = ?");
            $stmt->execute([
                $data['nombre'],
                $data['codigo'],
                $data['marca_id'],
                $data['categoria_id'],
                $proveedor_id,
                $data['stock'],
                $data['stock_minimo'],
                $data['precio_compra'],
                $data['precio_venta'],
                $data['tipo_medida'],
                $data['unidad'],
                $id_producto
            ]);

            $producto = $pdo->query("SELECT p.*, m.nombre as marca, c.nombre as categoria, pr.nombre as proveedor FROM productos p LEFT JOIN marcas m ON p.marca_id = m.id LEFT JOIN categorias c ON p.categoria_id = c.id LEFT JOIN proveedores pr ON p.proveedor_id = pr.id WHERE p.id = $id_producto")->fetch(PDO::FETCH_ASSOC);
            echo json_encode($producto);
            break;

        case 'eliminar_producto':
            checkRole(['administrador', 'usuario']);
            $data = json_decode(file_get_contents('php://input'), true);
            $id_producto = $data['id'];

            // Check if product is in any sale
            $stmt_check_sales = $pdo->prepare("SELECT COUNT(*) FROM venta_productos WHERE producto_id = ?");
            $stmt_check_sales->execute([$id_producto]);
            if ($stmt_check_sales->fetchColumn() > 0) {
                http_response_code(400);
                echo json_encode(['error' => 'No se puede eliminar el producto porque está asociado a ventas existentes.']);
                exit();
            }

            // Check if product is a component in any mix
            $stmt_check_mix = $pdo->prepare("SELECT COUNT(*) FROM mix_componentes WHERE componente_id = ?");
            $stmt_check_mix->execute([$id_producto]);
            if ($stmt_check_mix->fetchColumn() > 0) {
                http_response_code(400);
                echo json_encode(['error' => 'No se puede eliminar el producto porque es un componente en un mix existente.']);
                exit();
            }

            // If the product is a mix, delete its components first
            $stmt_delete_components = $pdo->prepare("DELETE FROM mix_componentes WHERE mix_id = ?");
            $stmt_delete_components->execute([$id_producto]);

            // Finally, delete the product
            $stmt = $pdo->prepare("DELETE FROM productos WHERE id = ?");
            $stmt->execute([$id_producto]);

            echo json_encode(['success' => true]);
            break;

        case 'ajustar_stock':
            checkRole(['administrador', 'usuario']);
            $data = json_decode(file_get_contents('php://input'), true);
            $id_producto = $data['id'];
            $cantidad = $data['cantidad'];
            $tipo = $data['tipo']; // 'add' or 'remove'
            $motivo = $data['motivo'] ?? 'Ajuste manual'; // Reason for adjustment

            $pdo->beginTransaction();

            // Get current stock
            $stmt_get_stock = $pdo->prepare("SELECT stock FROM productos WHERE id = ? FOR UPDATE");
            $stmt_get_stock->execute([$id_producto]);
            $stock_anterior = $stmt_get_stock->fetchColumn();

            $stock_nuevo = 0;
            if ($tipo === 'remove') {
                if ($stock_anterior < $cantidad) {
                    $pdo->rollBack();
                    http_response_code(400);
                    echo json_encode(['error' => 'No se puede reducir el stock a un valor negativo.']);
                    exit();
                }
                $stock_nuevo = $stock_anterior - $cantidad;
                $stmt_update = $pdo->prepare("UPDATE productos SET stock = ? WHERE id = ?");
                $stmt_update->execute([$stock_nuevo, $id_producto]);
            } else { // add
                $stock_nuevo = $stock_anterior + $cantidad;
                $stmt_update = $pdo->prepare("UPDATE productos SET stock = ? WHERE id = ?");
                $stmt_update->execute([$stock_nuevo, $id_producto]);
            }

            // Log the stock change
            $stmt_log = $pdo->prepare(
                "INSERT INTO historial_stock (producto_id, usuario_id, tipo_movimiento, cantidad, stock_anterior, stock_nuevo, motivo) 
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt_log->execute([
                $id_producto,
                $id_usuario,
                $tipo,
                $cantidad,
                $stock_anterior,
                $stock_nuevo,
                $motivo
            ]);

            $pdo->commit();

            $producto = $pdo->query("SELECT p.*, m.nombre as marca, c.nombre as categoria FROM productos p LEFT JOIN marcas m ON p.marca_id = m.id LEFT JOIN categorias c ON p.categoria_id = c.id WHERE p.id = $id_producto")->fetch(PDO::FETCH_ASSOC);
            echo json_encode($producto);
            break;

        case 'listar_historial_stock':
            checkRole(['administrador', 'usuario']);
            $stmt = $pdo->query(
                "SELECT h.*, p.nombre as producto_nombre, u.nombre as usuario_nombre 
                 FROM historial_stock h 
                 JOIN productos p ON h.producto_id = p.id 
                 JOIN usuarios u ON h.usuario_id = u.id 
                 ORDER BY h.fecha DESC"
            );
            $historial = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($historial);
            break;

        case 'agregar_marca':
            checkRole(['administrador', 'usuario']);
            $data = json_decode(file_get_contents('php://input'), true);
            $stmt = $pdo->prepare("INSERT INTO marcas (nombre, creado_por) VALUES (?, ?)");
            $stmt->execute([$data['nombre'], $id_usuario]);
            $id_marca = $pdo->lastInsertId();
            $marca = $pdo->query("SELECT * FROM marcas WHERE id = $id_marca")->fetch(PDO::FETCH_ASSOC);
            echo json_encode($marca);
            break;

        case 'agregar_categoria':
            checkRole(['administrador', 'usuario']);
            $data = json_decode(file_get_contents('php://input'), true);
            $stmt = $pdo->prepare("INSERT INTO categorias (nombre, creado_por) VALUES (?, ?)");
            $stmt->execute([$data['nombre'], $id_usuario]);
            $id_categoria = $pdo->lastInsertId();
            $categoria = $pdo->query("SELECT * FROM categorias WHERE id = $id_categoria")->fetch(PDO::FETCH_ASSOC);
            echo json_encode($categoria);
            break;

        case 'editar_marca':
            checkRole(['administrador', 'usuario']);
            $data = json_decode(file_get_contents('php://input'), true);
            $stmt = $pdo->prepare("UPDATE marcas SET nombre = ? WHERE id = ?");
            $stmt->execute([$data['nombre'], $data['id']]);
            $marca = $pdo->query("SELECT * FROM marcas WHERE id = " . $data['id'])->fetch(PDO::FETCH_ASSOC);
            echo json_encode($marca);
            break;

        case 'eliminar_marca':
            checkRole(['administrador', 'usuario']);
            $data = json_decode(file_get_contents('php://input'), true);
            $id_marca = $data['id'];

            // Check if the brand is associated with any products
            $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM productos WHERE marca_id = ?");
            $stmt_check->execute([$id_marca]);
            if ($stmt_check->fetchColumn() > 0) {
                http_response_code(400);
                echo json_encode(['error' => 'No se puede eliminar la marca porque está asociada a productos existentes.']);
                exit();
            }

            $stmt = $pdo->prepare("DELETE FROM marcas WHERE id = ?");
            $stmt->execute([$id_marca]);
            echo json_encode(['success' => true]);
            break;

        case 'editar_categoria':
            checkRole(['administrador', 'usuario']);
            $data = json_decode(file_get_contents('php://input'), true);
            $stmt = $pdo->prepare("UPDATE categorias SET nombre = ? WHERE id = ?");
            $stmt->execute([$data['nombre'], $data['id']]);
            $categoria = $pdo->query("SELECT * FROM categorias WHERE id = " . $data['id'])->fetch(PDO::FETCH_ASSOC);
            echo json_encode($categoria);
            break;

        case 'eliminar_categoria':
            checkRole(['administrador', 'usuario']);
            $data = json_decode(file_get_contents('php://input'), true);
            $id_categoria = $data['id'];

            // Check if the category is associated with any products
            $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM productos WHERE categoria_id = ?");
            $stmt_check->execute([$id_categoria]);
            if ($stmt_check->fetchColumn() > 0) {
                http_response_code(400);
                echo json_encode(['error' => 'No se puede eliminar la categoría porque está asociada a productos existentes.']);
                exit();
            }

            $stmt = $pdo->prepare("DELETE FROM categorias WHERE id = ?");
            $stmt->execute([$id_categoria]);
            echo json_encode(['success' => true]);
            break;

        case 'agregar_proveedor':
            checkRole(['administrador', 'usuario']);
            $data = json_decode(file_get_contents('php://input'), true);
            $stmt = $pdo->prepare("INSERT INTO proveedores (nombre, contacto, telefono, email, direccion, creado_por) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$data['nombre'], $data['contacto'], $data['telefono'], $data['email'], $data['direccion'], $id_usuario]);
            $id_proveedor = $pdo->lastInsertId();
            $proveedor = $pdo->query("SELECT * FROM proveedores WHERE id = $id_proveedor")->fetch(PDO::FETCH_ASSOC);
            echo json_encode($proveedor);
            break;

        case 'editar_proveedor':
            checkRole(['administrador', 'usuario']);
            $data = json_decode(file_get_contents('php://input'), true);
            $id_proveedor = $data['id'];
            $stmt = $pdo->prepare("UPDATE proveedores SET nombre = ?, contacto = ?, telefono = ?, email = ?, direccion = ? WHERE id = ?");
            $stmt->execute([$data['nombre'], $data['contacto'], $data['telefono'], $data['email'], $data['direccion'], $id_proveedor]);
            $proveedor = $pdo->query("SELECT * FROM proveedores WHERE id = $id_proveedor")->fetch(PDO::FETCH_ASSOC);
            echo json_encode($proveedor);
            break;

        case 'eliminar_proveedor':
            checkRole(['administrador', 'usuario']);

            $data = json_decode(file_get_contents('php://input'), true);
            $id_proveedor = $data['id'];

            // Check if the supplier is associated with any products
            $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM productos WHERE proveedor_id = ?");
            $stmt_check->execute([$id_proveedor]);
            if ($stmt_check->fetchColumn() > 0) {
                http_response_code(400);
                echo json_encode(['error' => 'No se puede eliminar el proveedor porque está asociado a productos existentes.']);
                exit();
            }

            $stmt = $pdo->prepare("DELETE FROM proveedores WHERE id = ?");
            $stmt->execute([$id_proveedor]);
            echo json_encode(['success' => true]);
            break;

        case 'listar_ventas':
            checkRole(['administrador']);
            $stmt_ventas = $pdo->query("SELECT v.*, u.nombre as vendido_por FROM ventas v JOIN usuarios u ON v.creado_por = u.id ORDER BY v.fecha_venta DESC");
            $ventas = $stmt_ventas->fetchAll(PDO::FETCH_ASSOC);

            $stmt_items = $pdo->prepare("SELECT vp.*, p.nombre as nombre_producto FROM venta_productos vp JOIN productos p ON vp.producto_id = p.id WHERE vp.venta_id = ?");

            foreach ($ventas as &$venta) {
                $stmt_items->execute([$venta['id']]);
                $venta['items'] = $stmt_items->fetchAll(PDO::FETCH_ASSOC);
            }
            unset($venta); // Break the reference with the last element

            echo json_encode($ventas);
            break;

        case 'obtener_detalle_venta':
            checkRole(['administrador']);
            $id_venta = $_GET['id'] ?? null;

            if (!$id_venta) {
                http_response_code(400);
                echo json_encode(['error' => 'ID de venta no proporcionado.']);
                exit();
            }

            $stmt_venta = $pdo->prepare("SELECT v.*, u.nombre as vendido_por FROM ventas v JOIN usuarios u ON v.creado_por = u.id WHERE v.id = ?");
            $stmt_venta->execute([$id_venta]);
            $venta = $stmt_venta->fetch(PDO::FETCH_ASSOC);

            if (!$venta) {
                http_response_code(404);
                echo json_encode(['error' => 'Venta no encontrada.']);
                exit();
            }

            $stmt_items = $pdo->prepare("SELECT vp.*, p.nombre as nombre_producto FROM venta_productos vp JOIN productos p ON vp.producto_id = p.id WHERE vp.venta_id = ?");
            $stmt_items->execute([$id_venta]);
            $venta['items'] = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode($venta);
            break;

        case 'procesar_venta':
            $data = json_decode(file_get_contents('php://input'), true);
            
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("INSERT INTO ventas (total, costo_total, ganancia_total, creado_por) VALUES (?, ?, ?, ?)");
            $stmt->execute([$data['total'], $data['costo'], $data['ganancia'], $id_usuario]);
            $id_venta = $pdo->lastInsertId();
            
            $stmt_item = $pdo->prepare("INSERT INTO venta_productos (venta_id, producto_id, cantidad, precio_unitario, costo_unitario, total, ganancia) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt_update_stock = $pdo->prepare("UPDATE productos SET stock = stock - ? WHERE id = ?");
            $stmt_get_stock = $pdo->prepare("SELECT stock FROM productos WHERE id = ? FOR UPDATE");
            $stmt_log = $pdo->prepare(
                "INSERT INTO historial_stock (producto_id, usuario_id, tipo_movimiento, cantidad, stock_anterior, stock_nuevo, motivo) 
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );

            foreach ($data['items'] as $item) {
                // Get current stock
                $stmt_get_stock->execute([$item['producto_id']]);
                $stock_anterior = $stmt_get_stock->fetchColumn();
                $stock_nuevo = $stock_anterior - $item['quantity'];

                if ($stock_nuevo < 0) {
                    $pdo->rollBack();
                    http_response_code(400);
                    echo json_encode(['error' => 'Stock insuficiente para el producto ID: ' . $item['producto_id']]);
                    exit();
                }

                $stmt_item->execute([
                    $id_venta,
                    $item['producto_id'],
                    $item['quantity'],
                    $item['price'],
                    $item['cost'],
                    $item['total'],
                    $item['profit']
                ]);
                $stmt_update_stock->execute([$item['quantity'], $item['producto_id']]);

                // Log the stock change
                $stmt_log->execute([
                    $item['producto_id'],
                    $id_usuario,
                    'venta',
                    $item['quantity'],
                    $stock_anterior,
                    $stock_nuevo,
                    'Venta ID: ' . $id_venta
                ]);
            }
            
            $pdo->commit();
            
            echo json_encode(['success' => true, 'id_venta' => $id_venta]);
            break;
        
        case 'cerrar_caja':
            checkRole(['administrador', 'usuario']);
            $today = date('Y-m-d');
            $stmt = $pdo->prepare(
                "UPDATE ventas 
                 SET caja_cerrada = 1 
                 WHERE DATE(fecha_venta) = ? AND creado_por = ? AND caja_cerrada = 0"
            );
            $stmt->execute([$today, $id_usuario]);
            $affectedRows = $stmt->rowCount();
            echo json_encode(['success' => true, 'message' => "Caja cerrada. Se marcaron $affectedRows ventas."]);
            break;

        case 'fraccionar_producto':
            $data = json_decode(file_get_contents('php://input'), true);

            $pdo->beginTransaction();

            // Get original product
            $stmt_get = $pdo->prepare("SELECT * FROM productos WHERE id = ?");
            $stmt_get->execute([$data['original_product_id']]);
            $original_product = $stmt_get->fetch(PDO::FETCH_ASSOC);

            if (!$original_product) {
                throw new Exception("Producto original no encontrado.");
            }

            if ($original_product['stock'] < $data['amount_to_reduce']) {
                throw new Exception("Stock insuficiente en el producto original.");
            }

            // Update original product stock
            $stmt_update = $pdo->prepare("UPDATE productos SET stock = stock - ? WHERE id = ?");
            $stmt_update->execute([$data['amount_to_reduce'], $data['original_product_id']]);
            
            $new_product = $data['new_product'];
            $new_product_id = null;

            // Check if a product with the same name already exists for this user
            $stmt_check_existing = $pdo->prepare("SELECT id FROM productos WHERE nombre = ? AND creado_por = ?");
            $stmt_check_existing->execute([$new_product['nombre'], $id_usuario]);
            $existing_product = $stmt_check_existing->fetch(PDO::FETCH_ASSOC);

            if ($existing_product) {
                // Product exists, update its stock
                $new_product_id = $existing_product['id'];
                $stmt_update_existing = $pdo->prepare("UPDATE productos SET stock = stock + ? WHERE id = ?");
                $stmt_update_existing->execute([$new_product['stock'], $new_product_id]);
            } else {
                // Product does not exist, create it
                $stmt_insert = $pdo->prepare(
                    "INSERT INTO productos (nombre, codigo, marca_id, categoria_id, stock, stock_minimo, precio_compra, precio_venta, tipo_medida, unidad, creado_por) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                );
                $stmt_insert->execute([
                    $new_product['nombre'],
                    $new_product['codigo'],
                    $new_product['marca_id'],
                    $new_product['categoria_id'],
                    $new_product['stock'],
                    $new_product['stock_minimo'],
                    $new_product['precio_compra'],
                    $new_product['precio_venta'],
                    $new_product['tipo_medida'],
                    $new_product['unidad'],
                    $id_usuario
                ]);
                $new_product_id = $pdo->lastInsertId();
            }

            $pdo->commit();

            // Fetch the product (either newly created or updated)
            $stmt_get_new = $pdo->prepare("SELECT p.*, m.nombre as marca, c.nombre as categoria FROM productos p LEFT JOIN marcas m ON p.marca_id = m.id LEFT JOIN categorias c ON p.categoria_id = c.id WHERE p.id = ?");
            $stmt_get_new->execute([$new_product_id]);
            $newly_created_product = $stmt_get_new->fetch(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'new_product' => $newly_created_product]);
            break;
            
        case 'listar_usuarios':
            checkRole(['administrador']);
            $stmt = $pdo->query("SELECT id, nombre, apellido, email, rol FROM usuarios");
            $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($usuarios);
            break;

        case 'actualizar_rol_usuario':
            checkRole(['administrador']);
            $data = json_decode(file_get_contents('php://input'), true);
            $id_usuario_a_actualizar = $data['id'];
            $nuevo_rol = $data['rol'];

            // Basic validation for role
            $allowed_roles = ['administrador', 'usuario'];
            if (!in_array($nuevo_rol, $allowed_roles)) {
                http_response_code(400);
                echo json_encode(['error' => 'Rol no válido.']);
                exit();
            }

            $stmt = $pdo->prepare("UPDATE usuarios SET rol = ? WHERE id = ?");
            $stmt->execute([$nuevo_rol, $id_usuario_a_actualizar]);
            echo json_encode(['success' => true, 'message' => 'Rol actualizado con éxito.']);
            break;

        case 'crear_usuario':
            checkRole(['administrador']);
            
            // Basic validation
            if (empty($_POST['nombre']) || empty($_POST['apellido']) || empty($_POST['email']) || empty($_POST['password']) || empty($_POST['rol'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Todos los campos son obligatorios.']);
                exit();
            }

            // Check if email already exists
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE email = ?");
            $stmt->execute([$_POST['email']]);
            if ($stmt->fetchColumn() > 0) {
                http_response_code(400);
                echo json_encode(['error' => 'El correo electrónico ya está en uso.']);
                exit();
            }

            $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);

            $token = bin2hex(random_bytes(32));
            $expiracion = date("Y-m-d H:i:s", strtotime("+1 day"));

            $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, apellido, email, contrasena, rol, verification_token, token_expiracion, email_verificado) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $_POST['nombre'],
                $_POST['apellido'],
                $_POST['email'],
                $password_hash,
                $_POST['rol'],
                $token,
                $expiracion,
                0 // Set email_verificado to 0 (false) initially
            ]);

            $mail = configurarMailer();
            if (!$mail) {
                throw new Exception("No se pudo configurar el servicio de correo.");
            }

            $host = isset($_ENV['VERCEL_URL']) ? 'https://' . $_ENV['VERCEL_URL'] : 'http://' . $_SERVER['HTTP_HOST'];
            $verification_link = "{$host}/backend/verification.php?token=$token";

            $mail->addAddress($_POST['email'], $_POST['nombre']);
            $mail->isHTML(true);
            $mail->Subject = "Verificación de correo - NexoStock";
            $mail->Body = "
                <h2>¡Hola " . $_POST['nombre'] . "!</h2>
                <p>Tu cuenta ha sido creada por un administrador en <b>NexoStock</b>.</p>
                <p>Haz clic en el siguiente enlace para verificar tu correo:</p>
                <a href='$verification_link'>Verificar Email</a>
                <br><br>
                <small>Este enlace expira en 24 horas.</small>
            ";
            $mail->send();

            echo json_encode(['success' => true, 'message' => 'Usuario creado con éxito. Se ha enviado un correo de verificación.']);
            break;

        case 'actualizar_usuario':
            checkRole(['administrador']);

            if (empty($_POST['id']) || empty($_POST['nombre']) || empty($_POST['apellido']) || empty($_POST['email']) || empty($_POST['rol'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Faltan datos para actualizar.']);
                exit();
            }

            $id_usuario_a_actualizar = $_POST['id'];

            // Check if email is used by another user
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE email = ? AND id != ?");
            $stmt->execute([$_POST['email'], $id_usuario_a_actualizar]);
            if ($stmt->fetchColumn() > 0) {
                http_response_code(400);
                echo json_encode(['error' => 'El correo electrónico ya está en uso por otro usuario.']);
                exit();
            }

            if (!empty($_POST['password'])) {
                // Update with new password
                $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE usuarios SET nombre = ?, apellido = ?, email = ?, rol = ?, contrasena = ? WHERE id = ?");
                $stmt->execute([
                    $_POST['nombre'],
                    $_POST['apellido'],
                    $_POST['email'],
                    $_POST['rol'],
                    $password_hash,
                    $id_usuario_a_actualizar
                ]);
            } else {
                // Update without changing password
                $stmt = $pdo->prepare("UPDATE usuarios SET nombre = ?, apellido = ?, email = ?, rol = ? WHERE id = ?");
                $stmt->execute([
                    $_POST['nombre'],
                    $_POST['apellido'],
                    $_POST['email'],
                    $_POST['rol'],
                    $id_usuario_a_actualizar
                ]);
            }

            echo json_encode(['success' => true, 'message' => 'Usuario actualizado con éxito.']);
            break;

        case 'eliminar_usuario':
            checkRole(['administrador']);

            if (empty($_POST['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'ID de usuario no proporcionado.']);
                exit();
            }

            $id_usuario_a_eliminar = $_POST['id'];

            // Prevent admin from deleting themselves
            if ($id_usuario_a_eliminar == $_SESSION['usuario_id']) {
                http_response_code(400);
                echo json_encode(['error' => 'No puedes eliminar tu propia cuenta.']);
                exit();
            }

            $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
            $stmt->execute([$id_usuario_a_eliminar]);

            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'Usuario eliminado con éxito.']);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'El usuario no fue encontrado.']);
            }
            break;

        case 'backup_db':
            http_response_code(501);
            echo json_encode(['error' => 'La función de backup no es compatible con el entorno de Vercel. Considere usar las herramientas de su proveedor de base de datos en la nube.']);
            break;


        case 'restaurar_db':
            http_response_code(501);
            echo json_encode(['error' => 'La función de restauración no es compatible con el entorno de Vercel.']);
            break;

        case 'guardar_configuracion':
            checkRole(['administrador']);
            $settings = json_decode(file_get_contents('php://input'), true);
            
            // Vercel has an ephemeral file system, writing to config.json won't persist.
            // This feature should be re-architected, e.g., by storing settings in the database.
            http_response_code(501);
            echo json_encode(['error' => 'Guardar la configuración en un archivo no es compatible con Vercel.']);
            break;

        case 'obtener_configuracion':
            checkRole(['administrador']);
            $configFile = __DIR__ . '/../config.json';
            if (file_exists($configFile)) {
                $settings = file_get_contents($configFile);
                echo $settings;
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Archivo de configuración no encontrado.']);
            }
            break;

        case 'listar_logs':
            checkRole(['administrador']);
            
            // Assuming logs are in error.log in the project root
            $logFile = __DIR__ . '/../../error.log'; // Corrected path to be in the project root
            $logs = [];

            if (file_exists($logFile)) {
                $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($lines as $line) {
                    // Simple parsing, assuming format [YYYY-MM-DD HH:MM:SS] level: message
                    if (preg_match('/^\[(.*?)\] (.*?): (.*)$/', $line, $matches)) {
                        $logs[] = [
                            'fecha' => $matches[1],
                            'nivel' => $matches[2],
                            'mensaje' => $matches[3]
                        ];
                    }
                }
                // Reverse to show latest logs first
                $logs = array_reverse($logs);
            }

            echo json_encode($logs);
            break;

        case 'cancelar_venta':
            checkRole(['administrador']);
            $data = json_decode(file_get_contents('php://input'), true);
            $id_venta = $data['id'];

            $pdo->beginTransaction();

            try {
                // Get sale items
                $stmt_items = $pdo->prepare("SELECT * FROM venta_productos WHERE venta_id = ?");
                $stmt_items->execute([$id_venta]);
                $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

                // Restore stock for each product
                $stmt_update_stock = $pdo->prepare("UPDATE productos SET stock = stock + ? WHERE id = ?");
                foreach ($items as $item) {
                    $stmt_update_stock->execute([$item['cantidad'], $item['producto_id']]);
                }

                // Delete sale items
                $stmt_delete_items = $pdo->prepare("DELETE FROM venta_productos WHERE venta_id = ?");
                $stmt_delete_items->execute([$id_venta]);

                // Delete sale
                $stmt_delete_venta = $pdo->prepare("DELETE FROM ventas WHERE id = ?");
                $stmt_delete_venta->execute([$id_venta]);

                $pdo->commit();
                echo json_encode(['success' => true]);
            } catch (Exception $e) {
                $pdo->rollBack();
                http_response_code(500);
                echo json_encode(['error' => 'Error al anular la venta: ' . $e->getMessage()]);
            }
            break;

        case 'crear_mix':
            checkRole(['administrador', 'usuario']);
            $data = json_decode(file_get_contents('php://input'), true);
            $new_product_data = $data['product'];
            $components = $data['components'];

            if (empty($new_product_data) || empty($components)) {
                http_response_code(400);
                echo json_encode(['error' => 'Datos de mix incompletos.']);
                exit();
            }

            $pdo->beginTransaction();

            try {
                // 1. Insert the mix as a new product
                $stmt = $pdo->prepare(
                    "INSERT INTO productos (nombre, codigo, marca_id, categoria_id, proveedor_id, stock, stock_minimo, precio_compra, precio_venta, tipo_medida, unidad, creado_por) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                );
                $stmt->execute([
                    $new_product_data['nombre'],
                    $new_product_data['codigo'],
                    $new_product_data['marca_id'],
                    $new_product_data['categoria_id'],
                    $new_product_data['proveedor_id'],
                    $new_product_data['stock'],
                    $new_product_data['stock_minimo'],
                    $new_product_data['precio_compra'],
                    $new_product_data['precio_venta'],
                    $new_product_data['tipo_medida'],
                    $new_product_data['unidad'],
                    $id_usuario
                ]);
                
                $mix_id = $pdo->lastInsertId();

                // 2. Insert the components into mix_componentes
                $stmt_component = $pdo->prepare(
                    "INSERT INTO mix_componentes (mix_id, componente_id, cantidad) VALUES (?, ?, ?)"
                );
                foreach ($components as $component) {
                    $stmt_component->execute([
                        $mix_id,
                        $component['producto_id'],
                        $component['quantity']
            ]);

            // 3. Reduce stock of component products
            $stmt_stock = $pdo->prepare("UPDATE productos SET stock = stock - ? WHERE id = ?");
            $stmt_stock->execute([
                $component['quantity'],
                $component['producto_id']
                    ]);
                }

                $pdo->commit();

                // 4. Return the newly created product
                $producto = $pdo->query("SELECT p.*, m.nombre as marca, c.nombre as categoria, pr.nombre as proveedor FROM productos p LEFT JOIN marcas m ON p.marca_id = m.id LEFT JOIN categorias c ON p.categoria_id = c.id LEFT JOIN proveedores pr ON p.proveedor_id = pr.id WHERE p.id = $mix_id")->fetch(PDO::FETCH_ASSOC);

                echo json_encode(['success' => true, 'new_product' => $producto]);

            } catch (Exception $e) {
                $pdo->rollBack();
                http_response_code(500);
                // For debugging, send the actual error message
                echo json_encode(['error' => 'Error al crear el mix en la base de datos.', 'details' => $e->getMessage()]);
            }
            break;

        default:
            echo json_encode(['error' => 'Acción no válida']);
            break;
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error' => 'Error en la base de datos.']);
}
?>