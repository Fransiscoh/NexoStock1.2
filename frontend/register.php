<?php
session_start();
require_once __DIR__ . '/../backend/google_config.php';

// Comprobar si venimos de un registro con Google
$is_google_registration = isset($_SESSION['google_user_data']);
$google_user = $is_google_registration ? $_SESSION['google_user_data'] : null;

// Si no es un registro de Google, necesitamos la URL de login de Google
if (!$is_google_registration) {
    $google_login_url = $google_client->createAuthUrl();
}

// ACTIVAR ERRORES PARA DEBUG
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Crear Cuenta</title>
  <link rel="icon" type="image/jpeg" href="../NexoStock.jpg">
  <link rel="apple-touch-icon" href="../NexoStock.jpg">
  <link rel="stylesheet" href="assets/styles/register.css">
</head>
<body data-theme="dark">
  <div class="register-container">
    <div class="register-header">
      <h1 class="register-title"><?php echo $is_google_registration ? 'Completar Registro' : 'Crear Cuenta'; ?></h1>
      <p class="register-subtitle">Únete a nuestra comunidad</p>
    </div>

    <div class="register-messages">
      <!-- Los mensajes de error/éxito se manejarán por el backend o JS -->
    </div>

    <form class="register-form" id="registerForm" action="../backend/register.php" method="POST" autocomplete="off">
      
      <?php if ($is_google_registration): ?>
        <input type="hidden" name="google_id" value="<?php echo htmlspecialchars($google_user['id']); ?>">
        <input type="hidden" name="avatar_url" value="<?php echo htmlspecialchars($google_user['avatar_url']); ?>">
      <?php endif; ?>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="firstName">Nombre <span class="required">*</span></label>
          <input type="text" id="firstName" name="nombre" class="form-input" required autocomplete="off"
                 value="<?php echo $is_google_registration ? htmlspecialchars($google_user['nombre']) : ''; ?>"
                 <?php echo $is_google_registration ? 'readonly' : ''; ?>>
        </div>
        <div class="form-group">
          <label class="form-label" for="lastName">Apellido <span class="required">*</span></label>
          <input type="text" id="lastName" name="apellido" class="form-input" required autocomplete="off"
                 value="<?php echo $is_google_registration ? htmlspecialchars($google_user['apellido']) : ''; ?>"
                 <?php echo $is_google_registration ? 'readonly' : ''; ?>>
        </div>
      </div>

      <div class="form-group full-width">
        <label class="form-label" for="email">Email <span class="required">*</span></label>
        <input type="email" id="email" name="email" class="form-input" required
               value="<?php echo $is_google_registration ? htmlspecialchars($google_user['email']) : ''; ?>"
               <?php echo $is_google_registration ? 'readonly' : ''; ?>>
      </div>

      <?php if (!$is_google_registration): ?>
      <div class="form-group full-width">
        <label class="form-label" for="password">Contraseña <span class="required">*</span></label>
        <div class="password-group">
          <input type="password" id="password" name="contrasena" class="form-input" required>
          <button type="button" class="password-toggle">👁️</button>
        </div>
      </div>

      <div class="form-group full-width">
        <label class="form-label" for="confirmPassword">Confirmar Contraseña <span class="required">*</span></label>
        <input type="password" id="confirmPassword" name="confirmPassword" class="form-input" required>
        <div class="form-feedback" id="passwordMatch"></div>
      </div>
      <?php endif; ?>

      <div class="checkbox-group">
        <input type="checkbox" id="terms" name="terms" class="checkbox" required>
        <label class="checkbox-label" for="terms">
          Acepto los <a href="#" target="_blank">términos y condiciones</a> y la 
          <a href="#" target="_blank">política de privacidad</a>
        </label>
      </div>

      <button type="submit" class="register-btn">
        <?php echo $is_google_registration ? 'Completar Registro' : 'Crear Cuenta'; ?>
      </button>
    </form>

    <?php if (!$is_google_registration): ?>
    <div class="divider">
        <span>o regístrate con</span>
    </div>
    
    <div class="social-login" style="margin-bottom: 20px;">
        <a href="<?php echo htmlspecialchars($google_login_url); ?>" class="social-btn google-btn" style="text-decoration: none; color: #444; display: inline-flex; align-items: center; gap: 10px; background-color: #fff; border: 1px solid #ccc; padding: 10px 20px; border-radius: 5px;">
            <img src="https://developers.google.com/identity/images/g-logo.png" alt="Google logo" width="20" height="20">
            <span>Continuar con Google</span>
        </a>
    </div>
    <?php endif; ?>

    <div class="login-link">
      ¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a>
    </div>
  </div>

  <script src="assets/js/register.js"></script>
</body>
</html>
