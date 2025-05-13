document.addEventListener('DOMContentLoaded', () => {
    const inicioSesionForm = document.getElementById('inicioSesionForm');
    let restaurarPasswdForm = document.getElementById('restaurarPsswdForm');
    let correo = document.getElementById('email');
    let contrasenaInput = document.getElementById('contrasena');
    let overlay = document.querySelector('.overlay');
    let popupContainer = document.querySelector('.popup-container');
    let switchFormLinks = document.querySelectorAll('.switch-form, .forgot-link');
    const registroForm = document.getElementById('registroFormElm');
    const submitButton = registroForm.querySelector('button[type="submit"]');
    const originalButtonText = submitButton.textContent;

    switchFormLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            let targetForm = link.getAttribute('data-form');
            switchForm(targetForm);
        });
    });

    function switchForm(formId) {
        let forms = document.querySelectorAll('.form');
        forms.forEach(form => form.classList.remove('active'));
        document.getElementById(formId).classList.add('active');
    }

    popupContainer.addEventListener('click', (e) => {
        e.stopPropagation();
    });

    inicioSesionForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        clearErrors(inicioSesionForm);

        const email = document.getElementById('email').value;
        const password = document.getElementById('contrasena').value;
        const remember = document.getElementById('remember').checked;

        // Validación básica del lado del cliente
        if (!email || !password) {
            showError(inicioSesionForm, 'Por favor, complete todos los campos');
            return;
        }

        try {
            const response = await fetch('http://localhost:5000/api/login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    email: email,
                    password: password
                })
            });

            const result = await response.json();

            if (result.success) {
                // Guardar datos de usuario en localStorage o sessionStorage
                if (remember) {
                    localStorage.setItem('user', JSON.stringify(result.user));
                } else {
                    sessionStorage.setItem('user', JSON.stringify(result.user));
                }

                if (result.user.Tipo === 'CLIENTE') {
                    window.location.href = 'index.html';
                }
            } else {
                showError(inicioSesionForm, result.message || 'Credenciales incorrectas');
            }
        } catch (error) {
            showError(inicioSesionForm, 'Error de conexión con el servidor');
            console.error('Error:', error);
        }
    });
    registroForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        clearErrors(registroForm);

        // Obtener valores de los campos
        const nombre = document.getElementById('nombre').value.trim();
        const apellido = document.getElementById('nomUsuario').value.trim();
        const email = document.getElementById('emailRegistro').value.trim();
        const telefono = document.getElementById('telefonoRegistro')?.value.trim() || ''; // Campo opcional
        const contrasena = document.getElementById('contrasenaRegistro').value.trim();
        const confirmarContrasena = document.getElementById('confirmarPsswd').value.trim();

        // Validaciones
        if (!nombre || !apellido || !email || !contrasena || !confirmarContrasena) {
            showError(registroForm, 'Por favor, complete todos los campos obligatorios');
            return;
        }

        if (!isValidEmail(email)) {
            showError(registroForm, 'Por favor, introduzca un correo electrónico válido');
            return;
        }

        if (contrasena.length < 6) {
            showError(registroForm, 'La contraseña debe tener al menos 6 caracteres');
            return;
        }

        if (contrasena !== confirmarContrasena) {
            showError(registroForm, 'Las contraseñas no coinciden');
            return;
        }

        // Mostrar estado de carga
        submitButton.disabled = true;
        submitButton.textContent = 'Registrando...';

        try {
            const response = await fetch('http://localhost:5000/api/register', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    nombre: nombre,
                    apellido: apellido,
                    email: email,
                    telefono: telefono || null, // Enviar null si está vacío
                    password: contrasena,
                    confirmPassword: confirmarContrasena
                })
            });

            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.message || 'Error en el registro');
            }

            if (result.success) {
                showSuccess(registroForm, '¡Registro exitoso! Redirigiendo...');
                registroForm.reset();

                // Redirigir después de 2 segundos
                setTimeout(() => {
                    window.location.href = 'login.html';
                }, 2000);
            } else {
                showError(registroForm, result.message || 'Error en el registro');
            }
        } catch (error) {
            showError(registroForm, error.message || 'Error de conexión con el servidor');
            console.error('Error:', error);
        } finally {
            submitButton.disabled = false;
            submitButton.textContent = originalButtonText;
        }
    });

    // Función para validar email
    function isValidEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }

    // Función para mostrar mensajes de éxito
    function showSuccess(form, message) {
        const successDiv = document.createElement('div');
        successDiv.className = 'success-message';
        successDiv.style.color = 'green';
        successDiv.style.margin = '10px 0';
        successDiv.style.padding = '10px';
        successDiv.style.borderRadius = '4px';
        successDiv.style.backgroundColor = '#f0fff0';
        successDiv.textContent = message;

        // Limpiar mensajes anteriores
        const oldSuccess = form.querySelector('.success-message');
        if (oldSuccess) oldSuccess.remove();

        form.insertBefore(successDiv, form.querySelector('button'));
    }
});
function isValidEmail(email) {
    // Validación simple de email
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function showSuccess(message) {
    const successDiv = document.createElement('div');
    successDiv.className = 'success-message';
    successDiv.style.color = 'green';
    successDiv.style.marginTop = '10px';
    successDiv.textContent = message;

    registroForm.appendChild(successDiv);
    setTimeout(() => successDiv.remove(), 5000);
}

function clearErrors(form) {
    const errors = form.querySelectorAll('.error-message');
    errors.forEach(error => error.remove());
}

function showError(form, message) {
    const errorDiv = document.createElement('div');
    errorDiv.className = 'error-message';
    errorDiv.style.color = 'red';
    errorDiv.style.marginTop = '10px';
    errorDiv.textContent = message;

    form.appendChild(errorDiv);

    setTimeout(() => errorDiv.remove(), 5000);
}