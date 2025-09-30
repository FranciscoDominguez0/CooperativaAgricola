// JavaScript para Dashboard - Cooperativa Agrícola La Pintada

document.addEventListener('DOMContentLoaded', function() {
    // Elementos del DOM
    const userNameElement = document.getElementById('userName');
    const userRoleElement = document.getElementById('userRole');
    const logoutBtn = document.getElementById('logoutBtn');
    const logoutModal = document.getElementById('logoutModal');
    const confirmLogoutBtn = document.getElementById('confirmLogout');
    const cancelLogoutBtn = document.getElementById('cancelLogout');

    // Verificar si el usuario está logueado
    verificarSesion();

    // Cargar información del usuario
    cargarInformacionUsuario();

    // Event listeners
    logoutBtn.addEventListener('click', mostrarModalLogout);
    confirmLogoutBtn.addEventListener('click', cerrarSesion);
    cancelLogoutBtn.addEventListener('click', ocultarModalLogout);

    // Cerrar modal al hacer clic fuera de él
    logoutModal.addEventListener('click', function(e) {
        if (e.target === logoutModal) {
            ocultarModalLogout();
        }
    });

    // Cerrar modal con tecla Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && logoutModal.style.display === 'block') {
            ocultarModalLogout();
        }
    });

    // Función para verificar si hay sesión activa
    function verificarSesion() {
        // En una implementación real, esto verificaría con el servidor
        // Por ahora, simulamos verificando si hay datos de usuario en sessionStorage
        const userData = sessionStorage.getItem('userData');
        
        if (!userData) {
            // No hay sesión, redirigir al login
            mostrarMensajeYRedirigir('Sesión expirada. Redirigiendo al login...', 'login.html');
            return;
        }

        try {
            const user = JSON.parse(userData);
            if (!user.id || !user.nombre) {
                throw new Error('Datos de usuario inválidos');
            }
        } catch (error) {
            console.error('Error parsing user data:', error);
            sessionStorage.removeItem('userData');
            mostrarMensajeYRedirigir('Error en los datos de sesión. Redirigiendo al login...', 'login.html');
        }
    }

    // Función para cargar información del usuario
    function cargarInformacionUsuario() {
        try {
            const userData = sessionStorage.getItem('userData');
            if (userData) {
                const user = JSON.parse(userData);
                
                // Actualizar nombre de usuario
                if (userNameElement && user.nombre) {
                    userNameElement.textContent = user.nombre;
                }

                // Actualizar rol con iconos
                if (userRoleElement && user.rol) {
                    const roleIcons = {
                        'admin': '👑 Administrador',
                        'miembro': '🌾 Miembro',
                        'empleado': '👨‍💼 Empleado',
                        'invitado': '👥 Invitado'
                    };
                    userRoleElement.textContent = roleIcons[user.rol] || `📋 ${user.rol}`;
                }

                // Personalizar saludo según la hora
                personalizarSaludo();
                
                // Mostrar estadísticas del usuario (si las hay)
                mostrarEstadisticasUsuario(user);
            }
        } catch (error) {
            console.error('Error cargando información del usuario:', error);
        }
    }

    // Función para personalizar saludo según la hora
    function personalizarSaludo() {
        const hora = new Date().getHours();
        const welcomeCard = document.querySelector('.welcome-card h2');
        
        if (welcomeCard) {
            let saludo = '';
            let emoji = '';
            
            if (hora >= 5 && hora < 12) {
                saludo = '¡Buenos días y bienvenido';
                emoji = '🌅';
            } else if (hora >= 12 && hora < 18) {
                saludo = '¡Buenas tardes y bienvenido';
                emoji = '☀️';
            } else {
                saludo = '¡Buenas noches y bienvenido';
                emoji = '🌙';
            }
            
            welcomeCard.innerHTML = `${emoji} ${saludo} a tu Cooperativa!`;
        }
    }

    // Función para mostrar estadísticas del usuario
    function mostrarEstadisticasUsuario(user) {
        // Aquí podrías agregar más información específica del usuario
        // Por ejemplo, último login, estadísticas de actividad, etc.
        
        // Actualizar último acceso si está disponible
        if (user.ultimo_acceso) {
            const lastLoginElement = document.getElementById('lastLogin');
            if (lastLoginElement) {
                const fecha = new Date(user.ultimo_acceso);
                lastLoginElement.textContent = `Último acceso: ${formatearFecha(fecha)}`;
            }
        }
    }

    // Función para formatear fechas
    function formatearFecha(fecha) {
        const opciones = {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        };
        return fecha.toLocaleDateString('es-ES', opciones);
    }

    // Función para mostrar modal de logout
    function mostrarModalLogout() {
        logoutModal.style.display = 'block';
        confirmLogoutBtn.focus(); // Enfocar el botón de confirmar
    }

    // Función para ocultar modal de logout
    function ocultarModalLogout() {
        logoutModal.style.display = 'none';
    }

    // Función para cerrar sesión
    async function cerrarSesion() {
        try {
            // Mostrar loading en el botón
            const originalText = confirmLogoutBtn.textContent;
            confirmLogoutBtn.innerHTML = '<span class="loading"></span> Cerrando...';
            confirmLogoutBtn.disabled = true;

            // Enviar petición al servidor para cerrar sesión
            const response = await fetch('php/logout.php', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            // Limpiar datos de sesión del cliente
            sessionStorage.removeItem('userData');
            localStorage.removeItem('userData'); // Por si se usa localStorage

            // Mostrar mensaje y redirigir
            mostrarMensajeYRedirigir('Sesión cerrada exitosamente. Hasta pronto!', 'login.html');

        } catch (error) {
            console.error('Error cerrando sesión:', error);
            
            // Incluso si hay error en el servidor, limpiar datos locales
            sessionStorage.removeItem('userData');
            localStorage.removeItem('userData');
            
            mostrarMensajeYRedirigir('Sesión cerrada. Redirigiendo...', 'login.html');
        }
    }

    // Función para mostrar mensaje temporal y redirigir
    function mostrarMensajeYRedirigir(mensaje, url, delay = 2000) {
        // Crear elemento de mensaje
        const messageDiv = document.createElement('div');
        messageDiv.style.cssText = `
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: linear-gradient(45deg, #4a7c59, #8bc34a);
            color: white;
            padding: 1rem 2rem;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            z-index: 9999;
            font-weight: 600;
            text-align: center;
            animation: slideDown 0.3s ease;
        `;
        messageDiv.textContent = mensaje;

        // Agregar animación CSS
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideDown {
                from {
                    opacity: 0;
                    transform: translateX(-50%) translateY(-20px);
                }
                to {
                    opacity: 1;
                    transform: translateX(-50%) translateY(0);
                }
            }
        `;
        document.head.appendChild(style);
        document.body.appendChild(messageDiv);

        // Redirigir después del delay
        setTimeout(() => {
            window.location.href = url;
        }, delay);
    }

    // Función para animar las tarjetas al hacer scroll
    function animarTarjetasAlScroll() {
        const tarjetas = document.querySelectorAll('.service-card, .news-card');
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.animation = 'slideInUp 0.6s ease forwards';
                }
            });
        }, {
            threshold: 0.1
        });

        tarjetas.forEach(tarjeta => {
            observer.observe(tarjeta);
        });

        // Agregar estilos de animación
        const animationStyle = document.createElement('style');
        animationStyle.textContent = `
            @keyframes slideInUp {
                from {
                    opacity: 0;
                    transform: translateY(30px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
        `;
        document.head.appendChild(animationStyle);
    }

    // Inicializar animaciones
    animarTarjetasAlScroll();

    // Función para manejar la activación de servicios (para futuras funcionalidades)
    function configurarBotonesServicios() {
        const serviceBtns = document.querySelectorAll('.service-btn:not(:disabled)');
        
        serviceBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const serviceCard = this.closest('.service-card');
                const serviceName = serviceCard.querySelector('h3').textContent;
                
                mostrarNotificacion(`${serviceName} estará disponible próximamente`, 'info');
            });
        });
    }

    // Función para mostrar notificaciones temporales
    function mostrarNotificacion(mensaje, tipo = 'info') {
        const notification = document.createElement('div');
        const colores = {
            'info': '#2196F3',
            'success': '#4CAF50',
            'warning': '#FF9800',
            'error': '#f44336'
        };

        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${colores[tipo]};
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 9999;
            animation: slideInRight 0.3s ease;
            max-width: 300px;
        `;
        notification.textContent = mensaje;

        document.body.appendChild(notification);

        // Auto-remover después de 3 segundos
        setTimeout(() => {
            notification.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 3000);

        // Agregar estilos de animación si no existen
        if (!document.querySelector('#notification-styles')) {
            const style = document.createElement('style');
            style.id = 'notification-styles';
            style.textContent = `
                @keyframes slideInRight {
                    from {
                        opacity: 0;
                        transform: translateX(100%);
                    }
                    to {
                        opacity: 1;
                        transform: translateX(0);
                    }
                }
                @keyframes slideOutRight {
                    from {
                        opacity: 1;
                        transform: translateX(0);
                    }
                    to {
                        opacity: 0;
                        transform: translateX(100%);
                    }
                }
            `;
            document.head.appendChild(style);
        }
    }

    // Configurar botones de servicios
    configurarBotonesServicios();

    // Actualizar hora cada minuto para el saludo personalizado
    setInterval(personalizarSaludo, 60000);

    // Mensaje de bienvenida al cargar completamente
    window.addEventListener('load', function() {
        setTimeout(() => {
            mostrarNotificacion('¡Dashboard cargado exitosamente!', 'success');
        }, 500);
    });
});

// Función para manejar errores globales
window.addEventListener('error', function(e) {
    console.error('Error en el dashboard:', e.error);
});

// Función para prevenir pérdida de datos en caso de cierre accidental
window.addEventListener('beforeunload', function(e) {
    // Solo mostrar advertencia si hay datos importantes sin guardar
    // Por ahora, no implementamos esta funcionalidad
    return;
});