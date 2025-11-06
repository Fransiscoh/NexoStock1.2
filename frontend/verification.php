<?php
$email = isset($_GET['email']) ? htmlspecialchars($_GET['email']) : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/jpeg" href="../NexoStock.jpg">
    <link rel="apple-touch-icon" href="../NexoStock.jpg">
    <title>Verificación de correo enviada - NexoStock</title>
    <link rel="stylesheet" href="assets/styles/register.css">
    <style>
        /* Adapt styles for this page */
        .verification-container {
            background: var(--surface-color);
            padding: 2.5rem;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 500px;
            border: 1px solid var(--border-color);
            text-align: center;
        }
        .verification-icon {
            font-size: 4rem;
            color: var(--success-color);
            margin-bottom: 1.5rem;
        }
        .verification-title {
            color: var(--text-color);
            font-size: 1.75rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        .verification-message {
            color: var(--text-muted);
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }
        .email-address {
            font-weight: bold;
            color: var(--text-color);
            word-break: break-all;
            background: var(--background-color);
            padding: 0.5rem;
            border-radius: 8px;
            margin: 0.5rem 0;
            display: inline-block;
        }
        .resend-link, .login-link {
            font-size: 0.9rem;
            color: var(--text-muted);
        }
        .resend-link {
            margin-top: 1.5rem;
        }
        .login-link {
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border-color);
        }
        a {
            color: var(--accent-color);
            text-decoration: none;
            font-weight: 600;
        }
        a:hover {
            color: var(--accent-hover);
            text-decoration: underline;
        }
    </style>
</head>
<body data-theme="dark">
    <div class="verification-container">
        <div class="verification-icon">✉️</div>
        <h1 class="verification-title">¡Correo de verificación enviado!</h1>
        
        <div class="verification-message">
            <p>Hemos enviado un correo de verificación a:</p>
            <p class="email-address"><?php echo $email; ?></p>
            <p>Por favor, revisa tu bandeja de entrada y haz clic en el enlace de verificación para activar tu cuenta.</p>
        </div>

        <div class="resend-link">
            ¿No has recibido el correo? 
            <a href="#" id="resendVerification" data-email="<?php echo htmlspecialchars($email); ?>">Reenviar correo de verificación</a>
        </div>

        <div class="login-link">
            <p>¿Ya verificaste tu correo? <a href="login.php">Inicia sesión aquí</a></p>
        </div>
    </div>

    <script>
    document.getElementById('resendVerification').addEventListener('click', function(e) {
        e.preventDefault();
        const email = this.getAttribute('data-email');
        const resendLink = this;
        const originalText = resendLink.textContent;
        
        resendLink.textContent = 'Enviando...';
        resendLink.style.pointerEvents = 'none';
        
        fetch('../backend/verification.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'email=' + encodeURIComponent(email) + '&resend=true'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Se ha enviado un nuevo correo de verificación a ' + email);
            } else {
                alert('Error: ' + (data.message || 'No se pudo reenviar el correo'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Ocurrió un error al intentar reenviar el correo');
        })
        .finally(() => {
            resendLink.textContent = originalText;
            resendLink.style.pointerEvents = 'auto';
        });
    });
    </script>
</body>
</html>