document.addEventListener("DOMContentLoaded", () => {
  const passwordInput = document.getElementById("password");
  const toggleBtn = document.querySelector(".password-toggle");
  if (toggleBtn && passwordInput) {
    toggleBtn.addEventListener("click", () => {
      passwordInput.type = passwordInput.type === "password" ? "text" : "password";
    });
  }

  const emailInput = document.getElementById('email');
  const passwordGroup = document.getElementById('password-group');
  const loginBtn = document.getElementById('login-btn');
  const googleUserMessage = document.getElementById('google-user-message');
  const forgotPasswordLink = document.querySelector('.forgot-password');

  if (emailInput) {
    emailInput.addEventListener('blur', function() {
      console.log('Blur event triggered');
      const email = this.value;

      if (!email || !/^\S+@\S+\.\S+$/.test(email)) {
        return;
      }

      googleUserMessage.style.display = 'none';
      passwordGroup.style.display = 'block';
      forgotPasswordLink.style.display = 'block';
      loginBtn.disabled = false;

      fetch('../backend/login.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=check_user&email=' + encodeURIComponent(email)
      })
      .then(response => response.json())
      .then(data => {
        console.log('Data received:', data);
        if (data.status === 'google_user') {
          console.log('Google user detected');
          passwordGroup.style.display = 'none';
          forgotPasswordLink.style.display = 'none';
          loginBtn.disabled = true;
          googleUserMessage.innerHTML = 'Parece que te registraste con Google. Por favor, usa el botón <strong>"Continuar con Google"</strong> para iniciar sesión.';
          googleUserMessage.style.display = 'block';
        } else {
          console.log('Standard user or not found');
          passwordGroup.style.display = 'block';
          forgotPasswordLink.style.display = 'block';
          loginBtn.disabled = false;
          googleUserMessage.style.display = 'none';
        }
      })
      .catch(error => {
        console.error('Error al verificar el usuario:', error);
        passwordGroup.style.display = 'block';
        forgotPasswordLink.style.display = 'block';
        loginBtn.disabled = false;
        googleUserMessage.style.display = 'none';
      });
    });
  }
});