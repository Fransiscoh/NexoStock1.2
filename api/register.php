<?php
session_start();
include __DIR__ . '/db_connect.php';
require __DIR__ . '/mail.config.php'; // carga la config

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $is_google_registration = isset($_POST['google_id']);

    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $email = $_POST['email'];
    $default_role = 'usuario'; // Rol por defecto para todos los nuevos usuarios

    try {
        if ($is_google_registration) {
            // --- Registro con Google ---
            $google_id = $_POST['google_id'];
            $avatar_url = $_POST['avatar_url'];

            $sql = "INSERT INTO usuarios (nombre, apellido, email, google_id, avatar_url, email_verificado, rol) 
                    VALUES (?, ?, ?, ?, ?, 1, ?)";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([$nombre, $apellido, $email, $google_id, $avatar_url, $default_role]);
            $user_id = $conn->lastInsertId();

            // Enviar email de bienvenida
            $mail = configurarMailer();
            if ($mail) {
                $mail->addAddress($email, $nombre);
                $mail->isHTML(true);
                $mail->Subject = "¡Bienvenido a NexoStock!";
                $mail->Body = "
                    <h2>¡Hola $nombre!</h2>
                    <p>Te damos la bienvenida a <b>NexoStock</b>.</p>
                    <p>Tu cuenta ha sido creada y verificada exitosamente a través de Google.</p>
                    <p>¡Ya puedes empezar a usar la aplicación!</p>
                ";
                try {
                    $mail->send();
                } catch (Exception $e) {
                    error_log("Error al enviar email de bienvenida a usuario de Google: " . $mail->ErrorInfo);
                }
            }

            // Limpiar datos de sesión de Google
            unset($_SESSION['google_user_data']);

            // Iniciar sesión directamente
            $_SESSION['usuario_id'] = $user_id;
            $_SESSION['usuario_nombre'] = $nombre;
            $_SESSION['usuario_email'] = $email;
            $_SESSION['usuario_rol'] = $default_role; // FIX: Añadir rol a la sesión
            $_SESSION['usuario_verificado'] = true;

            header("Location: ../frontend/index.php");
            exit;

        } else {
            // --- Registro Estándar ---
            $contrasena = $_POST['contrasena'];
            $hashed_password = password_hash($contrasena, PASSWORD_BCRYPT);

            $token = bin2hex(random_bytes(32));
            $expiracion = date("Y-m-d H:i:s", strtotime("+1 day"));

            $sql = "INSERT INTO usuarios (nombre, apellido, email, contrasena, verification_token, token_expiracion, rol) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([$nombre, $apellido, $email, $hashed_password, $token, $expiracion, $default_role]);

            $mail = configurarMailer();
            if (!$mail) {
                throw new Exception("No se pudo configurar el servicio de correo.");
            }

            $host = isset($_ENV['VERCEL_URL']) ? 'https://' . $_ENV['VERCEL_URL'] : 'http://' . $_SERVER['HTTP_HOST'];
            $verification_link = "{$host}/backend/verification.php?token=$token";

            $mail->addAddress($email, $nombre);
            $mail->isHTML(true);
            $mail->Subject = "Bienvenido a NexoStock";
            $mail->Body = "
                <h2>¡Hola $nombre!</h2>
                <p>Gracias por registrarte en <b>NexoStock</b>.</p>
                <p>Haz clic en el siguiente enlace para verificar tu correo:</p>
                <a href='$verification_link'>Verificar Email</a>
                <br><br>
                <small>Este enlace expira en 24 horas.</small>
            ";
            $mail->send();

            header("Location: ../frontend/verification.php?email=" . urlencode($email));
            exit;
        }

    } catch (PDOException $e) {
        error_log("Error de base de datos en register.php: " . $e->getMessage());
        if ($e->errorInfo[1] == 1062) {
            header("Location: ../frontend/register.php?error=email_exists");
        } else {
            header("Location: ../frontend/register.php?error=db_error");
        }
        exit;
    } catch (Exception $e) {
        error_log("Error en register.php: " . $e->getMessage());
        if (isset($mail)) {
            error_log("PHPMailer Error: " . $mail->ErrorInfo);
        }
        header("Location: ../frontend/register.php?error=mail_error");
        exit;
    }

    // Cerrar conexión
    $stmt = null;
    $conn = null;
}