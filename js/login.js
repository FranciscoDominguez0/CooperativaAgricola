// JavaScript para el formulario de login
// Cooperativa Agrícola La Pintada

document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
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
        loginForm.style.display = mostrar ? 'none' : 'block';
    }

    // Validación en tiempo real
    const inputs = loginForm.querySelectorAll('input');
    inputs.forEach(input => {
        input.addEventListener('input', function() {
            // Limpiar mensajes de error al escribir
            ocultarMensaje();
            
            // Validación básica visual
            if (this.checkValidity()) {
                this.style.borderColor = '#4a7c59';
            } else {
                this.style.borderColor = '#f44336';
            }
        });

        // Limpiar estilos al hacer focus
        input.addEventListener('focus', function() {
            this.style.borderColor = '#8bc34a';
        });

        // Restaurar estilo al perder focus
        input.addEventListener('blur', function() {
            if (this.value === '') {
                this.style.borderColor = '#e0e0e0';
            }
        });
    });

    // Manejo del formulario de login
    loginForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // Obtener datos del formulario
        const formData = new FormData(loginForm);
        const correo = formData.get('correo').trim();
        const contraseña = formData.get('contraseña');

        // Validaciones del lado del cliente
        if (!correo || !contraseña) {
            mostrarMensaje('Por favor, completa todos los campos', 'error');
            return;
        }

        if (!isValidEmail(correo)) {
            mostrarMensaje('Por favor, ingresa un correo electrónico válido', 'error');
            return;
        }

        // Mostrar loading
        mostrarLoading(true);
        ocultarMensaje();

        try {
            // Enviar datos al servidor
            const response = await fetch('php/procesar_login.php', {
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
                
                // Redirigir al dashboard después de 2 segundos
                setTimeout(() => {
                    window.location.href = 'dashboard.html';
                }, 2000);
            } else {
                mostrarMensaje(data.message, 'error');
                
                // Enfocar el campo de correo para facilitar reintento
                document.getElementById('correo').focus();
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

    // Efecto de animación para los inputs
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.classList.add('focused');
        });

        input.addEventListener('blur', function() {
            if (this.value === '') {
                this.parentElement.classList.remove('focused');
            }
        });
    });

    // Manejo de tecla Enter en campos
    inputs.forEach((input, index) => {
        input.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                if (index < inputs.length - 1) {
                    // Mover al siguiente campo
                    inputs[index + 1].focus();
                } else {
                    // Enviar formulario
                    loginForm.dispatchEvent(new Event('submit'));
                }
            }
        });
    });

    // Auto-focus en el primer campo al cargar la página
    document.getElementById('correo').focus();

    // Limpiar mensajes cuando se hace clic en cualquier parte del formulario
    loginForm.addEventListener('click', function() {
        if (mensajeDiv.style.display === 'block' && mensajeDiv.classList.contains('alert-error')) {
            setTimeout(() => {
                ocultarMensaje();
            }, 100);
        }
    });
});

// Función para mostrar/ocultar contraseña (si se desea agregar esta funcionalidad)
function togglePassword() {
    const passwordInput = document.getElementById('contraseña');
    const type = passwordInput.type === 'password' ? 'text' : 'password';
    passwordInput.type = type;
}

// Prevenir envío múltiple del formulario
let isSubmitting = false;
document.addEventListener('submit', function(e) {
    if (isSubmitting) {
        e.preventDefault();
        return false;
    }
    isSubmitting = true;
    
    // Resetear después de 3 segundos
    setTimeout(() => {
        isSubmitting = false;
    }, 3000);
});