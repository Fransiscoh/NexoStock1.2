<?php 
session_start(); 

// Limpiar datos de Google de intentos de registro anteriores no completados
if (isset($_SESSION['google_user_data'])) {
    unset($_SESSION['google_user_data']);
}

require_once __DIR__ . '/../backend/google_config.php';
$google_login_url = $google_client->createAuthUrl();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión</title>
    <link rel="icon" type="image/jpeg" href="../NexoStock.jpg">
    <link rel="apple-touch-icon" href="../NexoStock.jpg">
    <link rel="stylesheet" href="assets/styles/login.css">
</head>
<body data-theme="dark">
    <div class="login-container">
        <div class="login-header">
            <h1 class="login-title">Iniciar Sesión</h1>
            <div class="logo-container" style="text-align: center; margin-bottom: 20px;">
        </div>
            <p class="login-subtitle">Accede a tu cuenta</p>
        </div> 
        
        <!-- Formulario que envía al backend -->
        <form class="login-form" action="../backend/login.php" method="POST">
            <div class="form-group">
                <label class="form-label" for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-input" required autocomplete="off">
            </div>

            <!-- Container for the Google user message -->
            <div id="google-user-message" class="alert alert-info" style="display: none; margin-top: 15px; padding: 10px; border-radius: 5px;"></div>
            
            <div id="password-group" class="form-group">
                <label class="form-label" for="password">Contraseña</label>
                <div class="password-group">
                        <input type="password" id="password" name="password" class="form-input" required autocomplete="off">
                    <button type="button" class="password-toggle">👁️</button>
                </div>
            </div>
            </form>
        
            <form class="login-form" action="../backend/login.php" method="POST" autocomplete="off">
            
            <div class="forgot-password">
                <a href="forgot-password.php">¿Olvidaste tu contraseña?</a>
            </div>
            
            <button type="submit" id="login-btn" class="login-btn">Iniciar Sesión</button>
        </form>
        
        <div class="divider">
            <span>o continúa con</span>
        </div>
        
        <div class="social-login">
            <a href="<?php echo htmlspecialchars($google_login_url); ?>" class="social-btn google-btn" style="text-decoration: none; color: #444; display: inline-flex; align-items: center; gap: 10px; background-color: #fff; border: 1px solid #ccc; padding: 10px 20px; border-radius: 5px;">
                <img src="https://developers.google.com/identity/images/g-logo.png" alt="Google logo" width="20" height="20">
                <span>Continuar con Google</span>
            </a>
        </div>
        
        <div class="register-link">
            ¿No tienes cuenta? <a href="register.php">Regístrate</a>
        </div>
    </div>
    
    <script>
    // Manejar el clic en el enlace de reenvío de verificación
    document.addEventListener('DOMContentLoaded', function() {
        const resendLinks = document.querySelectorAll('.resend-verification');
        
        resendLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const email = this.getAttribute('data-email');
                
                if (!email) return;
                
                // Mostrar mensaje de carga
                const originalText = this.textContent;
                this.textContent = 'Enviando...';
                this.style.pointerEvents = 'none';
                
                // Enviar solicitud para reenviar el correo
                fetch('../backend/verification.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'email=' + encodeURIComponent(email)
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
                    this.textContent = originalText;
                    this.style.pointerEvents = 'auto';
                });
            });
        });
    });
    </script>
    <script src="assets/js/login.js"></script>
</body>
</html>