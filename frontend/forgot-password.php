<?php
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - NexoStock</title>
    <link rel="stylesheet" href="assets/styles/login.css">
    <link rel="icon" type="image/jpeg" href="../NexoStock.jpg">
    <link rel="apple-touch-icon" href="../NexoStock.jpg">
</head>
<body data-theme="dark">
    <div class="login-container">
        <div class="login-header">
            <h1 class="login-title">Recuperar Contraseña</h1>
            <p class="login-subtitle">Ingresa tu email para recibir instrucciones</p>
        </div>

        <form class="login-form" action="../backend/forgot-password.php" method="POST">
            <div id="message-container" style="color: white; text-align: center;"></div> <!-- To show success/error messages -->

            <div class="form-group">
                <label class="form-label" for="email">Email</label>
                <input type="email" id="email" name="email" class="form-input" required>
            </div>
            
            <button type="submit" class="login-btn">Enviar Enlace de Recuperación</button>
        </form>
        
        <div class="register-link">
            ¿Recordaste tu contraseña? <a href="login.php">Inicia Sesión</a>
        </div>
    </div>

    <script>
        document.querySelector('.login-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const form = this;
            const email = form.querySelector('#email').value;
            const messageContainer = document.getElementById('message-container');
            const submitButton = form.querySelector('.login-btn');

            submitButton.textContent = 'Enviando...';
            submitButton.disabled = true;

            fetch(form.action, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'email=' + encodeURIComponent(email)
            })
            .then(response => {
                if (!response.ok) {
                    // Throw an error to be caught by the .catch block
                    throw new Error('Network response was not ok: ' + response.statusText);
                }
                return response.json();
            })
            .then(data => {
                messageContainer.style.padding = '10px';
                messageContainer.style.borderRadius = '5px';
                messageContainer.style.marginBottom = '15px';
                
                if (data.success) {
                    messageContainer.textContent = data.message || 'Se han enviado las instrucciones a tu correo.';
                    messageContainer.style.backgroundColor = 'var(--success-color)';
                    form.querySelector('#email').value = '';
                } else {
                    messageContainer.textContent = data.message || 'No se pudo procesar la solicitud.';
                    messageContainer.style.backgroundColor = 'var(--error-color)';
                }
            })
            .catch(error => {
                console.error('Fetch Error:', error);
                messageContainer.style.padding = '10px';
                messageContainer.style.borderRadius = '5px';
                messageContainer.style.marginBottom = '15px';
                messageContainer.textContent = 'Ocurrió un error al conectar con el servidor. Revisa la consola para más detalles.';
                messageContainer.style.backgroundColor = 'var(--error-color)';
            })
            .finally(() => {
                submitButton.textContent = 'Enviar Enlace de Recuperación';
                submitButton.disabled = false;
            });
        });
    </script>
</body>
</html>
