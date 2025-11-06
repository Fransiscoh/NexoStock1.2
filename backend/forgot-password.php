<?php
header('Content-Type: application/json');

require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/mail.config.php';

$response = ['success' => false, 'message' => 'Un error inesperado ha ocurrido.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);

    if (!$email) {
        $response['message'] = 'Por favor, introduce una dirección de correo electrónico válida.';
        echo json_encode($response);
        exit;
    }

    try {
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() === 0) {
            $response['success'] = true;
            $response['message'] = 'Si tu correo está en nuestro sistema, recibirás un enlace para restablecer tu contraseña.';
            echo json_encode($response);
            exit;
        }

        // --- Garbage Collection: Delete all expired tokens ---
        $stmt = $conn->prepare("DELETE FROM password_resets WHERE created_at < (NOW() - INTERVAL 1 HOUR)");
        $stmt->execute();

        // Generar un token seguro
        $token = bin2hex(random_bytes(32));
        $token_hash = hash('sha256', $token);

        // Guardar token en password_resets
        $stmt = $conn->prepare("REPLACE INTO password_resets (email, token, created_at) VALUES (?, ?, NOW())");
        $stmt->execute([$email, $token_hash]);

        // Construir enlace de recuperación
        $host = isset($_ENV['VERCEL_URL']) ? 'https://' . $_ENV['VERCEL_URL'] : 'http://' . $_SERVER['HTTP_HOST'];
        $resetLink = "{$host}/reset-password.php?token=" . $token;

        // Configurar mailer
        $mail = configurarMailer();
        if ($mail) {
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'Restablece tu contraseña de NexoStock';

            // Usar heredoc normal para que sí interprete $resetLink
            $mailBody = <<<EOT
<div style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;">
        <h2 style="text-align: center; color: #1f2937;">Solicitud de Restablecimiento de Contraseña</h2>
        <p>Hola,</p>
        <p>Hemos recibido una solicitud para restablecer la contraseña de tu cuenta en NexoStock.</p>
        <p>Haz clic en el siguiente enlace para crear una nueva contraseña:</p>
        <p style="text-align: center;">
            <a href="{$resetLink}" style="background-color: #10b981; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; display: inline-block; font-weight: bold;">Restablecer Contraseña</a>
        </p>
        <p>Este enlace expirará en 1 hora. Si no solicitaste este cambio, puedes ignorar este correo de forma segura.</p>
        <hr style="border: none; border-top: 1px solid #eee;">
        <p style="font-size: 0.9em; color: #777;">Si tienes problemas con el botón, copia y pega la siguiente URL en tu navegador:</p>
        <p style="font-size: 0.8em; color: #999; word-break: break-all;">{$resetLink}</p>
        <br>
        <p>Gracias,</p>
        <p><strong>El equipo de NexoStock</strong></p>
    </div>
</div>
EOT;

            $mail->Body = $mailBody;
            $mail->send();

            $response['success'] = true;
            $response['message'] = 'Si tu correo está en nuestro sistema, recibirás un enlace para restablecer tu contraseña.';
        } else {
            $response['message'] = 'No se pudo configurar el servicio de correo.';
        }

    } catch (PDOException $e) {
        error_log("Database Error: " . $e->getMessage());
        $response['message'] = 'Error de base de datos. Por favor, contacta al administrador.';
    } catch (Exception $e) {
        error_log("Mailer Error: " . $e->getMessage());
        $response['success'] = true;
        $response['message'] = 'Si tu correo está en nuestro sistema, recibirás un enlace para restablecer tu contraseña.';
    }

    echo json_encode($response);
}
?>
