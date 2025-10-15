// JavaScript para el formulario de registro
// Cooperativa Agrícola La Pintada

document.addEventListener('DOMContentLoaded', function() {
    const registroForm = document.getElementById('registroForm');
    const mensajeDiv = document.getElementById('mensaje');
    const loadingDiv = document.getElementById('loading');

    // Función para mostrar mensajes
    function mostrarMensaje(mensaje, tipo = 'info') {
        mensajeDiv.className = `alert alert-${tipo}`;
        mensajeDiv.textContent = mensaje;
        mensajeDiv.style.display = 'block';
        
        // Auto-ocultar después de 5 segundos para mensajes de éxito
        if (tipo === 'success') {
            setTimeout(() => {
                mensajeDiv.style.display = 'none';
            }, 5000);
        }
    }

    // Función para ocultar mensajes
    function ocultarMensaje() {
        mensajeDiv.style.display = 'none';
    }

    // Función para mostrar/ocultar loading
    function mostrarLoading(mostrar = true) {
        loadingDiv.style.display = mostrar ? 'block' : 'none';
        registroForm.style.display = mostrar ? 'none' : 'block';
    }

    // Validación en tiempo real
    const inputs = registroForm.querySelectorAll('input');
    inputs.forEach(input => {
        input.addEventListener('input', function() {
            ocultarMensaje();
            validarCampo(this);
        });

        input.addEventListener('focus', function() {
            this.style.borderColor = '#8bc34a';
        });

        input.addEventListener('blur', function() {
            if (this.value === '') {
                this.style.borderColor = '#e0e0e0';
            } else {
                validarCampo(this);
            }
        });
    });

    // Validación del select de rol
    const rolSelect = document.getElementById('rol');
    rolSelect.addEventListener('change', function() {
        ocultarMensaje();
        if (this.value) {
            this.style.borderColor = '#4a7c59';
        } else {
            this.style.borderColor = '#f44336';
        }
    });

    // Función para validar campos individuales
    function validarCampo(campo) {
        const valor = campo.value.trim();
        let esValido = true;
        let mensaje = '';

        switch (campo.id) {
            case 'nombre':
                esValido = valor.length >= 2;
                mensaje = esValido ? '' : 'El nombre debe tener al menos 2 caracteres';
                break;
            
            case 'correo':
                esValido = isValidEmail(valor);
                mensaje = esValido ? '' : 'Formato de correo electrónico inválido';
                break;
            
            case 'contraseña':
                esValido = valor.length >= 6;
                mensaje = esValido ? '' : 'La contraseña debe tener al menos 6 caracteres';
                // Actualizar validación de confirmación si existe valor
                const confirmar = document.getElementById('confirmar_contraseña');
                if (confirmar.value) {
                    validarCampo(confirmar);
                }
                break;
            
            case 'confirmar_contraseña':
                const contraseñaOriginal = document.getElementById('contraseña').value;
                esValido = valor === contraseñaOriginal && valor.length >= 6;
                mensaje = !esValido ? 'Las contraseñas no coinciden' : '';
                break;
        }

        // Aplicar estilos visuales
        if (esValido) {
            campo.style.borderColor = '#4a7c59';
            campo.style.backgroundColor = '#f8fff8';
        } else {
            campo.style.borderColor = '#f44336';
            campo.style.backgroundColor = '#fff8f8';
        }

        return esValido;
    }

    // Manejo del formulario de registro
    registroForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // Obtener datos del formulario
        const formData = new FormData(registroForm);
        const nombre = formData.get('nombre').trim();
        const correo = formData.get('correo').trim();
        const contraseña = formData.get('contraseña');
        const confirmarContraseña = formData.get('confirmar_contraseña');
        const rol = formData.get('rol');

        // Validaciones del lado del cliente
        if (!nombre || !correo || !contraseña || !confirmarContraseña || !rol) {
            mostrarMensaje('Por favor, completa todos los campos', 'error');
            return;
        }

        if (nombre.length < 2) {
            mostrarMensaje('El nombre debe tener al menos 2 caracteres', 'error');
            document.getElementById('nombre').focus();
            return;
        }

        if (!isValidEmail(correo)) {
            mostrarMensaje('Por favor, ingresa un correo electrónico válido', 'error');
            document.getElementById('correo').focus();
            return;
        }

        if (contraseña.length < 6) {
            mostrarMensaje('La contraseña debe tener al menos 6 caracteres', 'error');
            document.getElementById('contraseña').focus();
            return;
        }

        if (contraseña !== confirmarContraseña) {
            mostrarMensaje('Las contraseñas no coinciden', 'error');
            document.getElementById('confirmar_contraseña').focus();
            return;
        }

        // Validación de fortaleza de contraseña
        if (!isStrongPassword(contraseña)) {
            mostrarMensaje('La contraseña debe contener al menos una letra y un número', 'error');
            document.getElementById('contraseña').focus();
            return;
        }

        // Mostrar loading
        mostrarLoading(true);
        ocultarMensaje();

        try {
            // Enviar datos al servidor
            const response = await fetch('php/procesar_registro.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();
            
            // Ocultar loading
            mostrarLoading(false);

            if (data.success) {
                mostrarMensaje(data.message, 'success');
                
                // Limpiar formulario
                registroForm.reset();
                
                // Redirigir al login después de 3 segundos
                setTimeout(() => {
                    window.location.href = 'login.html';
                }, 3000);
            } else {
                mostrarMensaje(data.message, 'error');
                
                // Enfocar el primer campo con error
                if (data.message.includes('correo')) {
                    document.getElementById('correo').focus();
                } else {
                    document.getElementById('nombre').focus();
                }
            }

        } catch (error) {
            console.error('Error:', error);
            mostrarLoading(false);
            mostrarMensaje('Error de conexión. Por favor, intenta nuevamente.', 'error');
        }
    });

    // Función para validar email
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    // Función para validar fortaleza de contraseña
    function isStrongPassword(password) {
        // Al menos una letra y un número
        const hasLetter = /[a-zA-Z]/.test(password);
        const hasNumber = /\d/.test(password);
        return hasLetter && hasNumber && password.length >= 6;
    }

    // Indicador visual de fortaleza de contraseña
    const contraseñaInput = document.getElementById('contraseña');
    contraseñaInput.addEventListener('input', function() {
        const password = this.value;
        const strengthDiv = document.getElementById('password-strength') || createPasswordStrengthIndicator();
        
        let strength = 0;
        let message = '';
        let color = '';

        if (password.length >= 6) strength++;
        if (/[a-z]/.test(password)) strength++;
        if (/[A-Z]/.test(password)) strength++;
        if (/\d/.test(password)) strength++;
        if (/[^a-zA-Z0-9]/.test(password)) strength++;

        switch (strength) {
            case 0:
            case 1:
                message = 'Muy débil';
                color = '#f44336';
                break;
            case 2:
                message = 'Débil';
                color = '#ff9800';
                break;
            case 3:
                message = 'Moderada';
                color = '#ffc107';
                break;
            case 4:
                message = 'Fuerte';
                color = '#4caf50';
                break;
            case 5:
                message = 'Muy fuerte';
                color = '#2e7d32';
                break;
        }

        strengthDiv.textContent = password.length > 0 ? `Fortaleza: ${message}` : '';
        strengthDiv.style.color = color;
    });

    function createPasswordStrengthIndicator() {
        const strengthDiv = document.createElement('div');
        strengthDiv.id = 'password-strength';
        strengthDiv.style.fontSize = '0.8rem';
        strengthDiv.style.marginTop = '5px';
        contraseñaInput.parentElement.appendChild(strengthDiv);
        return strengthDiv;
    }

    // Manejo de tecla Enter en campos
    inputs.forEach((input, index) => {
        input.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const allFields = [...inputs, rolSelect];
                const currentIndex = allFields.indexOf(this);
                
                if (currentIndex < allFields.length - 1) {
                    allFields[currentIndex + 1].focus();
                } else {
                    registroForm.dispatchEvent(new Event('submit'));
                }
            }
        });
    });

    // Auto-focus en el primer campo
    document.getElementById('nombre').focus();

    // Limpiar estilos al resetear
    registroForm.addEventListener('reset', function() {
        inputs.forEach(input => {
            input.style.borderColor = '#e0e0e0';
            input.style.backgroundColor = '#ffffff';
        });
        rolSelect.style.borderColor = '#e0e0e0';
        ocultarMensaje();
    });
});

// Prevenir envío múltiple del formulario
let isSubmitting = false;
document.addEventListener('submit', function(e) {
    if (isSubmitting) {
        e.preventDefault();
        return false;
    }
    isSubmitting = true;
    
    setTimeout(() => {
        isSubmitting = false;
    }, 3000);
});