<?php
session_start();
$token = isset($_GET['token']) ? htmlspecialchars($_GET['token']) : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Contraseña - NexoStock</title>
    <link rel="stylesheet" href="assets/styles/login.css">
    <link rel="icon" type="image/jpeg" href="../NexoStock.jpg">
    <link rel="apple-touch-icon" href="../NexoStock.jpg">
</head>
<body data-theme="dark">
    <div class="login-container">
        <div class="login-header">
            <h1 class="login-title">Restablecer Contraseña</h1>
            <p class="login-subtitle">Crea una nueva contraseña para tu cuenta</p>
        </div>

        <?php if (empty($token)): ?>
            <div style="text-align: center; padding: 20px; background-color: var(--error-color); color: white; border-radius: 8px;">
                Token inválido o no proporcionado. Por favor, solicita un nuevo enlace de recuperación.
            </div>
        <?php else: ?>
            <form class="login-form" action="../backend/reset-password.php" method="POST">
                <div id="message-container" style="color: white; text-align: center;"></div>

                <input type="hidden" name="token" value="<?php echo $token; ?>">

                <div class="form-group">
                    <label class="form-label" for="password">Nueva Contraseña</label>
                    <input type="password" id="password" name="password" class="form-input" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="confirm_password">Confirmar Nueva Contraseña</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-input" required>
                </div>
                
                <button type="submit" class="login-btn">Restablecer Contraseña</button>
            </form>
        <?php endif; ?>
    </div>

    <script>
        const form = document.querySelector('.login-form');
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const messageContainer = document.getElementById('message-container');
                const submitButton = form.querySelector('.login-btn');
                const formData = new FormData(form);

                submitButton.textContent = 'Guardando...';
                submitButton.disabled = true;

                fetch(form.action, {
                    method: 'POST',
                    body: new URLSearchParams(formData)
                })
                .then(response => response.json())
                .then(data => {
                    messageContainer.style.padding = '10px';
                    messageContainer.style.borderRadius = '5px';
                    messageContainer.style.marginBottom = '15px';
                    
                    if (data.success) {
                        messageContainer.textContent = data.message || '¡Contraseña actualizada con éxito! Redirigiendo al login...';
                        messageContainer.style.backgroundColor = 'var(--success-color)';
                        form.reset();
                        setTimeout(() => {
                            window.location.href = 'login.php';
                        }, 3000);
                    } else {
                        messageContainer.textContent = data.message || 'No se pudo restablecer la contraseña.';
                        messageContainer.style.backgroundColor = 'var(--error-color)';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    messageContainer.textContent = 'Ocurrió un error de red.';
                    messageContainer.style.backgroundColor = 'var(--error-color)';
                })
                .finally(() => {
                    submitButton.textContent = 'Restablecer Contraseña';
                    submitButton.disabled = false;
                });
            });
        }
    </script>
</body>
</html>
