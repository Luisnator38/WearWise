document.addEventListener('DOMContentLoaded', () => {
    let inicioSesionForm = document.getElementById('inicioSesionForm');
    let registroForm = document.getElementById('registroFormElm');
    let restaurarPasswdForm = document.getElementById('restaurarPsswdForm');
    let correo = document.getElementById('email');
    let contrasenaInput = document.getElementById('contrasena');
    let overlay = document.querySelector('.overlay');
    let popupContainer = document.querySelector('.popup-container');
    let switchFormLinks = document.querySelectorAll('.switch-form, .forgot-link');

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

    inicioSesionForm.addEventListener('submit', (e) => {
        e.preventDefault();

        let email = correo.value.trim();
        let contrasena = contrasenaInput.value.trim();
        let remember = document.getElementById('remember').checked;

        if (!email || !contrasena) {
            showError('Por favor, rellene todos los campos');
            return;
        }

        if (!isValidEmail(email)) {
            showError('Por favor, introduzca un correo electrónico válido');
            return;
        }

        if (contrasena.length < 6) {
            showError('La contraseña debe tener como mínimo 6 caracteres');
            return;
        }

        console.log('Formulario de inicio de sesión enviado: ', { email, contrasena, remember });
        showSuccess('¡Sesión iniciada con éxito!');
        inicioSesionForm.reset();
        setTimeout(() => {
            window.location.href = 'index.html';
        }, 1500);
    });

    registroForm.addEventListener('submit', (e) => {
        e.preventDefault();

        let nombre = document.getElementById('nombre').value.trim();
        let nomUsuario = document.getElementById('nomUsuario').value.trim();
        let email = document.getElementById('emailRegistro').value.trim();
        let contrasenaRegistro = document.getElementById('contrasenaRegistro').value.trim();
        let confirmarPsswd = document.getElementById('confirmarPsswd').value.trim();

        if (!nombre || !nomUsuario || !email || !contrasenaRegistro || !confirmarPsswd) {
            showError('Por favor, rellene todos los campos');
            return;
        }

        if (!isValidEmail(email)) {
            showError('Por favor, introduzca un correo electrónico válido');
            return;
        }

        if (contrasenaRegistro.length < 6) {
            showError('La contraseña debe tener como mínimo 6 caracteres');
            return;
        }

        if (contrasenaRegistro !== confirmarPsswd) {
            showError('Las contraseñas no coinciden');
            return;
        }

        console.log('Formulario de registro enviado:', { nombre, nomUsuario, email, psswd });
        showSuccess('¡Cuenta creada con éxito!');
        registroForm.reset();
        setTimeout(() => {
            switchForm('inicioSesionForm');
        }, 2000);
    });

    restaurarPasswdForm.addEventListener('submit', (e) => {
        e.preventDefault();
        let email = document.getElementById('restaurarCorreo').value.trim();

        if (!email) {
            showError('Por favor, introduzca un correo electrónico');
            return;
        }

        if (!isValidEmail(email)) {
            showError('Por favor, introduzca un correo electrónico válido');
            return;
        }

        console.log('Solicitud de recuperación enviada por:', email);
        showSuccess('El link de recuperación ha sido enviado a la dirección de correo proporcionada');
        restaurarPasswdForm.reset();
        setTimeout(() => {
            switchForm(inicioSesionForm);
        }, 1000);
    });

    function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    document.querySelectorAll('input').forEach(input => {
        let inputGroup = input.parentElement;

        input.addEventListener('focus', () => {
            inputGroup.classList.add('focused');
        });

        input.addEventListener('blur', () => {
            inputGroup.classList.remove('focused');
            if (input.value) {
                inputGroup.classList.add('filled');
            } else {
                inputGroup.classList.remove('filled');
            }
        });
    });

    function showError(message) {
        let messageDiv = document.createElement('div');
        messageDiv.className = 'message error';
        messageDiv.textContent = message;
        insertMessage(messageDiv);
    }

    function showSuccess(message) {
        let messageDiv = document.createElement('div');
        messageDiv.className = 'message success';
        messageDiv.textContent = message;
        insertMessage(messageDiv);
    }

    function insertMessage(messageDiv) {
        let existingMessage = document.querySelector('.message');
        if (existingMessage) existingMessage.remove();

        let activeForm = document.querySelector('.form.active');
        let submitBtn = activeForm.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.parentElement.insertBefore(messageDiv, submitBtn);
        }

        setTimeout(() => messageDiv.remove(), 3000);
    }
});