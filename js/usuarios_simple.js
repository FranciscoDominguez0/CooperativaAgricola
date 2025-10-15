// JavaScript simplificado para el módulo de Gestión de Usuarios
// Cooperativa Agrícola La Pintada

let usuarios = [];
let estadisticas = {};

// Inicializar el módulo
document.addEventListener('DOMContentLoaded', function() {
    console.log('Módulo de usuarios iniciado');
    cargarDatos();
});

// Cargar datos iniciales
async function cargarDatos() {
    try {
        await Promise.all([
            cargarEstadisticas(),
            cargarUsuarios()
        ]);
    } catch (error) {
        console.error('Error cargando datos:', error);
        mostrarNotificacion('Error cargando datos del módulo', 'error');
    }
}

// Cargar estadísticas de usuarios
async function cargarEstadisticas() {
    try {
        const response = await fetch('php/usuarios_simple.php?action=estadisticas');
        const data = await response.json();
        
        if (data.success) {
            estadisticas = data.estadisticas;
            actualizarEstadisticas();
        } else {
            console.error('Error cargando estadísticas:', data.message);
            mostrarNotificacion('Error cargando estadísticas: ' + data.message, 'error');
        }
    } catch (error) {
        console.error('Error cargando estadísticas:', error);
        mostrarNotificacion('Error cargando estadísticas', 'error');
    }
}

// Actualizar la interfaz de estadísticas
function actualizarEstadisticas() {
    document.getElementById('totalUsuarios').textContent = estadisticas.total_usuarios || 0;
    document.getElementById('usuariosActivos').textContent = estadisticas.usuarios_activos || 0;
    document.getElementById('administradores').textContent = estadisticas.administradores || 0;
    document.getElementById('productores').textContent = estadisticas.productores || 0;
}

// Cargar lista de usuarios
async function cargarUsuarios() {
    try {
        const response = await fetch('php/usuarios_simple.php?action=lista');
        const data = await response.json();
        
        if (data.success) {
            usuarios = data.usuarios;
            mostrarUsuarios(usuarios);
        } else {
            console.error('Error cargando usuarios:', data.message);
            mostrarNotificacion('Error cargando usuarios: ' + data.message, 'error');
            mostrarUsuarios([]);
        }
    } catch (error) {
        console.error('Error cargando usuarios:', error);
        mostrarNotificacion('Error cargando usuarios', 'error');
        mostrarUsuarios([]);
    }
}

// Mostrar usuarios en la tabla
function mostrarUsuarios(listaUsuarios) {
    const tbody = document.getElementById('usuariosTableBody');
    
    if (listaUsuarios.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="5" class="loading">
                    <i class="fas fa-info-circle"></i> No hay usuarios registrados
                </td>
            </tr>
        `;
        return;
    }
    
    tbody.innerHTML = listaUsuarios.map(usuario => `
        <tr>
            <td>
                <div class="user-info">
                    <div class="user-avatar">
                        ${usuario.nombre.charAt(0).toUpperCase()}${usuario.apellido.charAt(0).toUpperCase()}
                    </div>
                    <div class="user-details">
                        <h4>${usuario.nombre} ${usuario.apellido}</h4>
                        <p>${usuario.email}</p>
                    </div>
                </div>
            </td>
            <td>
                <span class="role-badge ${usuario.rol}">${usuario.rol}</span>
            </td>
            <td>
                <span class="status-badge ${usuario.estado}">${usuario.estado}</span>
            </td>
            <td>
                ${usuario.ultimo_acceso ? 
                    new Date(usuario.ultimo_acceso).toLocaleDateString('es-ES') : 
                    'Nunca'
                }
            </td>
            <td>
                <div class="table-actions">
                    <button class="btn btn-info" onclick="editarUsuario(${usuario.id_usuario})" title="Editar">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-warning" onclick="gestionarPermisos(${usuario.id_usuario})" title="Permisos">
                        <i class="fas fa-shield-alt"></i>
                    </button>
                    <button class="btn btn-danger" onclick="eliminarUsuario(${usuario.id_usuario})" title="Eliminar">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

// Filtrar usuarios
function filtrarUsuarios() {
    const filtroRol = document.getElementById('filtroRol').value;
    const filtroEstado = document.getElementById('filtroEstado').value;
    
    let usuariosFiltrados = usuarios;
    
    if (filtroRol) {
        usuariosFiltrados = usuariosFiltrados.filter(usuario => usuario.rol === filtroRol);
    }
    
    if (filtroEstado) {
        usuariosFiltrados = usuariosFiltrados.filter(usuario => usuario.estado === filtroEstado);
    }
    
    mostrarUsuarios(usuariosFiltrados);
}

// Mostrar modal de nuevo usuario
function mostrarModalNuevoUsuario() {
    document.getElementById('modalNuevoUsuario').style.display = 'block';
    document.getElementById('formNuevoUsuario').reset();
}

// Crear nuevo usuario
async function crearUsuario(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const data = Object.fromEntries(formData.entries());
    
    try {
        const response = await fetch('php/usuarios_simple.php?action=crear', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            mostrarNotificacion('Usuario creado exitosamente', 'success');
            cerrarModal('modalNuevoUsuario');
            cargarDatos(); // Recargar datos
        } else {
            mostrarNotificacion('Error: ' + result.message, 'error');
        }
    } catch (error) {
        console.error('Error creando usuario:', error);
        mostrarNotificacion('Error creando usuario', 'error');
    }
}

// Editar usuario (función básica)
function editarUsuario(idUsuario) {
    const usuario = usuarios.find(u => u.id_usuario == idUsuario);
    if (!usuario) return;
    
    mostrarNotificacion('Función de edición en desarrollo', 'info');
}

// Eliminar usuario (función básica)
function eliminarUsuario(idUsuario) {
    const usuario = usuarios.find(u => u.id_usuario == idUsuario);
    if (!usuario) return;
    
    if (!confirm(`¿Estás seguro de que quieres eliminar al usuario ${usuario.nombre} ${usuario.apellido}?`)) {
        return;
    }
    
    mostrarNotificacion('Función de eliminación en desarrollo', 'info');
}

// Gestionar permisos (función básica)
function gestionarPermisos(idUsuario) {
    const usuario = usuarios.find(u => u.id_usuario == idUsuario);
    if (!usuario) return;
    
    mostrarNotificacion('Función de permisos en desarrollo', 'info');
}

// Cerrar modal
function cerrarModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

// Actualizar datos
async function actualizarDatos() {
    mostrarNotificacion('Actualizando datos...', 'info');
    await cargarDatos();
    mostrarNotificacion('Datos actualizados', 'success');
}

// Mostrar notificación
function mostrarNotificacion(mensaje, tipo = 'info') {
    // Crear elemento de notificación
    const notification = document.createElement('div');
    notification.className = `notification ${tipo}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas fa-${tipo === 'success' ? 'check-circle' : tipo === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
            <span>${mensaje}</span>
        </div>
    `;
    
    // Estilos para la notificación
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${tipo === 'success' ? '#d4edda' : tipo === 'error' ? '#f8d7da' : '#d1ecf1'};
        color: ${tipo === 'success' ? '#155724' : tipo === 'error' ? '#721c24' : '#0c5460'};
        padding: 15px 20px;
        border-radius: 8px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        z-index: 10000;
        animation: slideInRight 0.3s ease;
        max-width: 400px;
    `;
    
    // Agregar al DOM
    document.body.appendChild(notification);
    
    // Remover después de 3 segundos
    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 3000);
}

// Cerrar modal al hacer clic fuera
window.onclick = function(event) {
    const modales = document.querySelectorAll('.modal');
    modales.forEach(modal => {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
}

// Agregar estilos para las animaciones de notificaciones
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
    
    .notification-content {
        display: flex;
        align-items: center;
        gap: 10px;
    }
`;
document.head.appendChild(style);
