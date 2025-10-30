// JavaScript para el módulo de Gestión de Usuarios
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
        const response = await fetch('php/usuarios.php?action=estadisticas');
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
        const response = await fetch('php/usuarios.php?action=lista');
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
        const response = await fetch('php/usuarios.php?action=crear', {
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

// Editar usuario
async function editarUsuario(idUsuario) {
    const usuario = usuarios.find(u => u.id_usuario == idUsuario);
    if (!usuario) return;
    
    // Llenar formulario de edición
    document.getElementById('editId').value = usuario.id_usuario;
    document.getElementById('editNombre').value = usuario.nombre;
    document.getElementById('editApellido').value = usuario.apellido;
    document.getElementById('editEmail').value = usuario.email;
    document.getElementById('editTelefono').value = usuario.telefono || '';
    document.getElementById('editUsername').value = usuario.username;
    document.getElementById('editRol').value = usuario.rol;
    document.getElementById('editEstado').value = usuario.estado;
    document.getElementById('editNotas').value = usuario.notas || '';
    
    document.getElementById('modalEditarUsuario').style.display = 'block';
}

// Actualizar usuario
async function actualizarUsuario(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const data = Object.fromEntries(formData.entries());
    
    try {
        const response = await fetch('php/usuarios.php?action=actualizar', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            mostrarNotificacion('Usuario actualizado exitosamente', 'success');
            cerrarModal('modalEditarUsuario');
            cargarDatos(); // Recargar datos
        } else {
            mostrarNotificacion('Error: ' + result.message, 'error');
        }
    } catch (error) {
        console.error('Error actualizando usuario:', error);
        mostrarNotificacion('Error actualizando usuario', 'error');
    }
}

// Eliminar usuario
async function eliminarUsuario(idUsuario) {
    const usuario = usuarios.find(u => u.id_usuario == idUsuario);
    if (!usuario) return;
    
    if (!confirm(`¿Estás seguro de que quieres eliminar al usuario ${usuario.nombre} ${usuario.apellido}?`)) {
        return;
    }
    
    try {
        const response = await fetch(`php/usuarios.php?action=eliminar&id=${idUsuario}`, {
            method: 'DELETE'
        });
        
        const result = await response.json();
        
        if (result.success) {
            mostrarNotificacion('Usuario eliminado exitosamente', 'success');
            cargarDatos(); // Recargar datos
        } else {
            mostrarNotificacion('Error: ' + result.message, 'error');
        }
    } catch (error) {
        console.error('Error eliminando usuario:', error);
        mostrarNotificacion('Error eliminando usuario', 'error');
    }
}

// Gestionar permisos
async function gestionarPermisos(idUsuario) {
    const usuario = usuarios.find(u => u.id_usuario == idUsuario);
    if (!usuario) return;
    
    try {
        const response = await fetch(`php/usuarios.php?action=permisos&id=${idUsuario}`);
        const data = await response.json();
        
        if (data.success) {
            mostrarModalPermisos(usuario, data.permisos);
        } else {
            mostrarNotificacion('Error cargando permisos: ' + data.message, 'error');
        }
    } catch (error) {
        console.error('Error cargando permisos:', error);
        mostrarNotificacion('Error cargando permisos', 'error');
    }
}

// Mostrar modal de permisos
function mostrarModalPermisos(usuario, permisos) {
    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.id = 'modalPermisos';
    modal.style.display = 'block';
    
    modal.innerHTML = `
        <div class="modal-content large">
            <div class="modal-header">
                <h3><i class="fas fa-shield-alt"></i> Gestión de Permisos - ${usuario.nombre} ${usuario.apellido}</h3>
                <button class="close" onclick="cerrarModal('modalPermisos')">&times;</button>
            </div>
            <div style="padding: 20px;">
                <p><strong>Rol:</strong> ${usuario.rol}</p>
                <p><strong>Estado:</strong> ${usuario.estado}</p>
                
                <h4>Permisos Actuales:</h4>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; margin: 15px 0;">
                    ${permisos.map(permiso => `
                        <div style="background: #f8f9fa; padding: 10px; border-radius: 5px; border-left: 4px solid #2d5016;">
                            <strong>${permiso.modulo}</strong><br>
                            <small>${permiso.permiso}</small>
                        </div>
                    `).join('')}
                </div>
                
                <div style="text-align: center; margin-top: 20px;">
                    <button class="btn btn-primary" onclick="cerrarModal('modalPermisos')">
                        <i class="fas fa-check"></i> Cerrar
                    </button>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
}

// Cerrar modal
function cerrarModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
        if (modalId === 'modalPermisos') {
            modal.remove();
        }
    }
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
    
    .user-info {
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .user-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: linear-gradient(135deg, #2d5016, #4a7c59);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: bold;
    }
    
    .user-details h4 {
        color: #2d5016;
        margin-bottom: 5px;
    }
    
    .user-details p {
        color: #6c757d;
        font-size: 0.9rem;
    }
    
    .role-badge {
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: capitalize;
    }
    
    .role-badge.administrador {
        background: #dc3545;
        color: white;
    }
    
    .role-badge.productor {
        background: #2d5016;
        color: white;
    }
    
    .role-badge.cliente {
        background: #17a2b8;
        color: white;
    }
    
    .role-badge.contador {
        background: #ffc107;
        color: #212529;
    }
    
    .status-badge {
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: capitalize;
    }
    
    .status-badge.activo {
        background: #d4edda;
        color: #155724;
    }
    
    .status-badge.inactivo {
        background: #f8d7da;
        color: #721c24;
    }
    
    .status-badge.suspendido {
        background: #fff3cd;
        color: #856404;
    }
    
    .table-actions {
        display: flex;
        gap: 10px;
    }
    
    .table-actions .btn {
        padding: 8px 12px;
        font-size: 12px;
    }
    
    .loading {
        text-align: center;
        padding: 40px;
        color: #6c757d;
    }
`;
document.head.appendChild(style);