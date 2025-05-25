/**
 * @plik: /public/js/tn_login_scripts.js
 * @autor: Paweł Plichta / tnApp
 * @wersja: 1.5.0
 * @app: tnApp (TN iMAG)
 */
document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.querySelector('form[action=""][method="POST"]'); 
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            if (!loginForm.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
                loginForm.classList.add('was-validated');
                return;
            }
            const button = loginForm.querySelector('button[type="submit"]');
            const spinner = button ? button.querySelector('.spinner-border') : null;
            if (button && spinner) {
                button.disabled = true;
                spinner.classList.remove('d-none');
            }
        });
    }
    const flashAlerts = document.querySelectorAll('.tn-flash-container-login .alert.alert-dismissible');
    if (flashAlerts.length > 0) {
        window.setTimeout(() => {
            flashAlerts.forEach(alert => {
                try {
                    const alertInstance = bootstrap.Alert.getOrCreateInstance(alert);
                    if (alertInstance) {
                        alertInstance.close();
                    }
                } catch (e) {
                    console.warn("Błąd zamykania alertu:", e, alert);
                }
            });
        }, 7000);
    }
    const passwordInput = document.getElementById('tn_password');
    const passwordToggle = document.getElementById('tn-toggle-password');
    if (passwordInput && passwordToggle) {
        passwordToggle.addEventListener('click', function() {
           const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            const icon = this.querySelector('i');
            if (icon) {
                icon.classList.toggle('bi-eye-slash');
                icon.classList.toggle('bi-eye');
            }
            this.setAttribute('aria-label', type === 'password' ? 'Pokaż hasło' : 'Ukryj hasło');
        });
    }
});