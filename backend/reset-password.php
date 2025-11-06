<?php
header('Content-Type: application/json');

require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/mail.config.php';

$response = ['success' => false, 'message' => 'Un error inesperado ha ocurrido.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // --- Validation ---
    if (empty($token) || empty($password) || empty($confirm_password)) {
        $response['message'] = 'Por favor, completa todos los campos.';
        echo json_encode($response);
        exit;
    }

    if ($password !== $confirm_password) {
        $response['message'] = 'Las contraseñas no coinciden.';
        echo json_encode($response);
        exit;
    }

    if (strlen($password) < 8) {
        $response['message'] = 'La contraseña debe tener al menos 8 caracteres.';
        echo json_encode($response);
        exit;
    }

    try {
        // --- Token Verification (SQL controla expiración de 1 hora) ---
        $token_hash = hash('sha256', $token);

        $stmt = $conn->prepare("
            SELECT email 
            FROM password_resets 
            WHERE token = ? 
            AND created_at >= (NOW() - INTERVAL 1 HOUR)
        ");
        $stmt->execute([$token_hash]);
        $reset_request = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$reset_request) {
            $response['message'] = 'El enlace de recuperación es inválido o ha expirado.';
            echo json_encode($response);
            exit;
        }

        $email = $reset_request['email'];

        // --- Password Update ---
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("UPDATE usuarios SET contrasena = ? WHERE email = ?");
        $stmt->execute([$password_hash, $email]);

        // --- Cleanup ---
        $stmt = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
        $stmt->execute([$email]);

        // --- Confirmation Email ---
        $mail = configurarMailer();
        if ($mail) {
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'Tu contraseña de NexoStock ha sido actualizada';

            $host = isset($_ENV['VERCEL_URL']) ? 'https://' . $_ENV['VERCEL_URL'] : 'http://' . $_SERVER['HTTP_HOST'];
            $loginLink = "{$host}/login.php";

            $mailBody = <<<EOT
<div style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;">
        <h2 style="text-align: center; color: #1f2937;">Contraseña Actualizada</h2>
        <p>Hola,</p>
        <p>Te confirmamos que la contraseña de tu cuenta en NexoStock ha sido actualizada exitosamente.</p>
        <p>Si no realizaste este cambio, por favor, contacta a nuestro soporte inmediatamente.</p>
        <p>Puedes iniciar sesión con tu nueva contraseña aquí:</p>
        <p style="text-align: center;">
            <a href="{$loginLink}" style="background-color: #10b981; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; display: inline-block; font-weight: bold;">Iniciar Sesión</a>
        </p>
        <br>
        <p>Gracias,</p>
        <p><strong>El equipo de NexoStock</strong></p>
    </div>
</div>
EOT;

            $mail->Body = $mailBody;
            $mail->send();
        }

        $response['success'] = true;
        $response['message'] = '¡Contraseña actualizada con éxito!';

    } catch (PDOException $e) {
        error_log("Database Error: " . $e->getMessage());
        $response['message'] = 'Error de base de datos. Por favor, contacta al administrador.';
    } catch (Exception $e) {
        error_log("Mailer Error: " . $e->getMessage());
        $response['success'] = true;
        $response['message'] = 'Contraseña actualizada, pero no se pudo enviar el correo de confirmación.';
    }

    echo json_encode($response);
}
?>
