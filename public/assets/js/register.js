// JavaScript simple y seguro para register.php
document.addEventListener('DOMContentLoaded', function() {
    console.log('JavaScript cargado');

    // Elementos básicos (con verificación)
    const form = document.getElementById('registerForm');
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirmPassword');
    const registerBtn = document.querySelector('.register-btn');
    const termsCheckbox = document.getElementById('terms');
    const isGoogleRegistration = document.querySelector('input[name="google_id"]') !== null;

    // Solo continuar si el formulario existe
    if (!form) {
        console.log('Formulario no encontrado');
        return;
    }

    console.log('Formulario encontrado');

    // Función para validar contraseñas
    function checkPasswords() {
        if (!password || !confirmPassword) return;

        const passwordMatch = document.getElementById('passwordMatch');
        if (!passwordMatch) return;

        const pass1 = password.value;
        const pass2 = confirmPassword.value;

        if (pass2 === '') {
            passwordMatch.textContent = '';
        } else if (pass1 === pass2) {
            passwordMatch.textContent = '✅ Las contraseñas coinciden';
            passwordMatch.style.color = 'green';
        } else {
            passwordMatch.textContent = '❌ Las contraseñas no coinciden';
            passwordMatch.style.color = 'red';
        }
    }

    // Función para habilitar/deshabilitar botón
    function toggleButton() {
        if (!registerBtn) return;

        const firstName = document.getElementById('firstName')?.value.trim() || '';
        const lastName = document.getElementById('lastName')?.value.trim() || '';
        const email = document.getElementById('email')?.value.trim() || '';
        const terms = termsCheckbox?.checked || false;

        let isValid = false;
        if (isGoogleRegistration) {
            isValid = firstName && lastName && email && terms;
        } else {
            const pass1 = password?.value || '';
            const pass2 = confirmPassword?.value || '';
            isValid = firstName && lastName && email && pass1 && pass2 && 
                      (pass1 === pass2) && terms && pass1.length >= 6;
        }

        registerBtn.disabled = !isValid;
    }

    // Event listeners solo si los elementos existen
    if (password && confirmPassword) {
        password.addEventListener('input', function() {
            checkPasswords();
            toggleButton();
        });

        confirmPassword.addEventListener('input', function() {
            checkPasswords();
            toggleButton();
        });
    }

    if (termsCheckbox) {
        termsCheckbox.addEventListener('change', toggleButton);
    }

    // Validar otros campos
    ['firstName', 'lastName', 'email'].forEach(function(id) {
        const element = document.getElementById(id);
        if (element) {
            element.addEventListener('input', toggleButton);
        }
    });

    // Toggle para mostrar contraseña
    const passwordToggle = document.querySelector('.password-toggle');
    if (passwordToggle && password) {
        passwordToggle.addEventListener('click', function(e) {
            e.preventDefault();
            if (password.type === 'password') {
                password.type = 'text';
                this.textContent = '🙈';
            } else {
                password.type = 'password';
                this.textContent = '👁️';
            }
        });
    }

    // Manejar envío del formulario
    form.addEventListener('submit', function(e) {
        console.log('Formulario enviado');
        
        if (registerBtn) {
            registerBtn.disabled = true;
            registerBtn.textContent = isGoogleRegistration ? 'Completando registro...' : 'Creando cuenta...';
        }

        // El formulario se envía normalmente (sin AJAX)
    });

    // Inicializar
    toggleButton();

    console.log('JavaScript inicializado correctamente');
});
