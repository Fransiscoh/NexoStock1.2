<?php
session_start();
require_once 'db_connect.php';

// Manejar la verificación de usuario para la UI de la página de login
if (isset($_POST['action']) && $_POST['action'] === 'check_user') {
    header('Content-Type: application/json');

    if (isset($_POST['email'])) {
        $email = trim($_POST['email']);

        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            try {
                $stmt = $conn->prepare("SELECT google_id, contrasena FROM usuarios WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user) {
                    if (!empty($user['google_id']) && empty($user['contrasena'])) {
                        echo json_encode(['status' => 'google_user']);
                    } else {
                        echo json_encode(['status' => 'standard_user']);
                    }
                } else {
                    echo json_encode(['status' => 'not_found']);
                }
            } catch (PDOException $e) {
                echo json_encode(['status' => 'error', 'message' => 'Database error.']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid email format.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No email provided.']);
    }
    exit; // Detener la ejecución del script después de la verificación
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']); // Añadido trim()

    try {
        $stmt = $conn->prepare(" 
            SELECT id, nombre, apellido, email, contrasena, email_verificado, rol 
            FROM usuarios 
            WHERE email = :email 
            LIMIT 1
        ");
        $stmt->execute([':email' => $email]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verificar si el usuario existe
        if ($usuario) {
            // FIX: Si el rol está vacío, asignar 'usuario' por defecto y actualizar la BD
            if (empty($usuario['rol'])) {
                $usuario['rol'] = 'usuario';
                try {
                    $update_stmt = $conn->prepare("UPDATE usuarios SET rol = ? WHERE id = ?");
                    $update_stmt->execute([$usuario['rol'], $usuario['id']]);
                } catch (PDOException $e) {
                    // No es un error crítico, pero se puede registrar si es necesario
                }
            }
            // Si la contraseña es NULL, es un usuario de Google
            if ($usuario['contrasena'] === null) {
                header("Location: ../frontend/login.php?error=google_user&email=" . urlencode($email));
                exit;
            }

            // Verificar la contraseña
            if (password_verify($password, $usuario['contrasena'])) {
                // La contraseña es correcta, ahora verificar si el correo está activado
                if ($usuario['email_verificado'] != 1) {
                    header("Location: ../frontend/login.php?error=email_not_verified&email=" . urlencode($email));
                    exit;
                }

                // Iniciar sesión: guardar datos en la sesión
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['usuario_nombre'] = $usuario['nombre'];
                $_SESSION['usuario_email'] = $usuario['email'];
                $_SESSION['usuario_verificado'] = true;
                $_SESSION['usuario_rol'] = $usuario['rol'];

                // Redirigir al panel principal
                header("Location: ../frontend/index.php");
                exit;
            } else {
                // Si la contraseña es incorrecta, lanzar la misma excepción
                throw new Exception('Credenciales inválidas');
            }
        } else {
            // Si el usuario no existe, redirigir al registro
            header("Location: ../frontend/register.php?email=" . urlencode($email));
            exit;
        }

    } catch (PDOException $e) {
        // Error de base de datos
        header("Location: ../frontend/login.php?error=db_error");
        exit;
    } catch (Exception $e) {
        // Capturar la excepción de credenciales inválidas
        header("Location: ../frontend/login.php?error=invalid_credentials");
        exit;
    }
}
?>
