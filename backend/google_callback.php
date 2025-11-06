<?php
ob_start();
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
session_start();
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/google_config.php';
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/mail.config.php';

if (isset($_GET['code'])) {
    error_log('Google callback iniciado');
    try {
        error_log('Intentando obtener datos de Google');
        $token = $google_client->fetchAccessTokenWithAuthCode($_GET['code']);
        if (isset($token['error'])) {
            throw new Exception('Error al obtener el token de acceso: ' . $token['error_description']);
        }
        $google_client->setAccessToken($token['access_token']);

        // Obtener la información del usuario de Google
        $google_service = new Google\Service\Oauth2($google_client);
        $data = $google_service->userinfo->get();

        // --- Lógica de la base de datos ---

        // 1. Buscar si el usuario ya existe por su google_id
        $stmt = $conn->prepare("SELECT * FROM usuarios WHERE google_id = ?");
        $stmt->execute([$data['id']]);
        $user = $stmt->fetch();

        if ($user) {
            // El usuario ya existe, simplemente lo logueamos
            $user_id = $user['id'];
        } else {
            // El usuario no existe con este google_id, puede que exista con ese email
            $stmt = $conn->prepare("SELECT * FROM usuarios WHERE email = ?");
            $stmt->execute([$data['email']]);
            $user_by_email = $stmt->fetch();

            if ($user_by_email) {
                // El email existe, pero no tiene google_id. Lo actualizamos y marcamos como verificado.
                $user_id = $user_by_email['id'];
                $update_stmt = $conn->prepare("UPDATE usuarios SET google_id = ?, avatar_url = ?, email_verificado = 1 WHERE id = ?");
                $update_stmt->execute([$data['id'], $data['picture'], $user_id]);
            } else {
                // El usuario no existe. Guardar datos en sesión y redirigir.
                $_SESSION['google_user_data'] = [
                    'id' => $data['id'],
                    'email' => $data['email'],
                    'nombre' => $data['givenName'],
                    'apellido' => $data['familyName'] ?? '',
                    'avatar_url' => $data['picture']
                ];
                header('Location: ../frontend/register.php');
                exit();
            }
        }

        // --- Iniciar Sesión ---
        // Volvemos a pedir los datos del usuario para tener la info más actualizada
        $stmt = $conn->prepare("SELECT * FROM usuarios WHERE id = ?");
        $stmt->execute([$user_id]);
        $final_user = $stmt->fetch();

        if ($final_user) {
            // FIX: Si el rol está vacío, asignar 'usuario' por defecto y actualizar la BD
            if (empty($final_user['rol'])) {
                $final_user['rol'] = 'usuario';
                try {
                    $update_stmt = $conn->prepare("UPDATE usuarios SET rol = ? WHERE id = ?");
                    $update_stmt->execute([$final_user['rol'], $final_user['id']]);
                } catch (PDOException $e) {
                    // No es un error crítico, pero se puede registrar si es necesario
                }
            }
        }

        $_SESSION['usuario_id'] = $final_user['id'];
        $_SESSION['usuario_nombre'] = $final_user['nombre'];
        $_SESSION['usuario_email'] = $final_user['email'];
        $_SESSION['usuario_verificado'] = true; // Verificado por Google
        $_SESSION['usuario_rol'] = $final_user['rol'];

        // Redirigir al panel principal
        header('Location: ../frontend/index.php');
        exit();

    } catch (Exception $e) {
    // Manejo de errores
    error_log('Entrando al catch de Google login');
    error_log('Google login error: ' . $e->getMessage());
    header('Location: ../frontend/login.php?error=google_login_failed');
    exit();
    }
} else {
    // Si no hay código, redirigir al login
    header('Location: ../frontend/login.php');
    exit();
}

?>
