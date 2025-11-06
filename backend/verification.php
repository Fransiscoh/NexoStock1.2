            $expiracion = date("Y-m-d H:i:s", strtotime("+1 day"));

            $update_stmt = $conn->prepare("UPDATE usuarios SET verification_token = ?, token_expiracion = ? WHERE id = ?");
            $update_stmt->execute([$token, $expiracion, $user['id']]);

            $mail = configurarMailer();
            if (!$mail) {
                throw new Exception('No se pudo configurar el servicio de correo.');
            }

            $mail->addAddress($email, $user['nombre']);
            $mail->isHTML(true);
            $mail->Subject = "Reenvío de verificación de correo - NexoStock";
            $host = isset($_ENV['VERCEL_URL']) ? 'https://' . $_ENV['VERCEL_URL'] : 'http://' . $_SERVER['HTTP_HOST'];
            $verification_link = "{$host}/backend/verification.php?token=$token";
            $mail->Body = "                <h2>¡Hola {$user['nombre']}!</h2>                <p>Hemos recibido una solicitud para reenviar tu correo de verificación.</p>                <p>Haz clic en el siguiente enlace para verificar tu correo:</p>                <a href='$verification_link'>Verificar Email</a>                <br><br>                <small>Este enlace expira en 24 horas.</small>            ";
            $mail->send();

            $response = ['success' => true];
        } else {
            $response['message'] = 'El correo no existe o la cuenta ya ha sido verificada.';
        }
    } catch (Exception $e) {
        // error_log($e->getMessage());
        $response['message'] = 'Ocurrió un error en el servidor. Por favor, inténtalo más tarde.';
    }

    echo json_encode($response);
    exit;
}