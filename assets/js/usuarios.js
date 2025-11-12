// JavaScript para el módulo de Gestión de Usuarios
// Cooperativa Agrícola La Pintada

let usuarios = [];
let estadisticas = {};
let currentPage = 1;
let totalPages = 1;
let currentSearch = '';

// Inicializar el módulo
document.addEventListener('DOMContentLoaded', function() {
    console.log('Módulo de usuarios iniciado');
    setupEventListeners();
    cargarDatos();
});

// Configurar event listeners
function setupEventListeners() {
    // Búsqueda
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            currentSearch = this.value;
            currentPage = 1;
            cargarUsuarios();
        });
    }
}

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
            // Si no hay endpoint de estadísticas, calcular desde los usuarios
            calcularEstadisticas();
        }
    } catch (error) {
        console.error('Error cargando estadísticas:', error);
        calcularEstadisticas();
    }
}

// Calcular estadísticas desde los usuarios cargados
function calcularEstadisticas() {
    const total = usuarios.length;
    const activos = usuarios.filter(u => u.estado === 'activo').length;
    const admins = usuarios.filter(u => u.rol === 'admin').length;
    const productores = usuarios.filter(u => u.rol === 'productor').length;
    
    estadisticas = {
        total_usuarios: total,
        usuarios_activos: activos,
        administradores: admins,
        productores: productores
    };
    
    actualizarEstadisticas();
}

// Actualizar la interfaz de estadísticas
function actualizarEstadisticas() {
    const totalEl = document.getElementById('totalUsuarios');
    const activosEl = document.getElementById('usuariosActivos');
    const adminsEl = document.getElementById('administradores');
    const productoresEl = document.getElementById('productores');
    
    if (totalEl) totalEl.textContent = estadisticas.total_usuarios || 0;
    if (activosEl) activosEl.textContent = estadisticas.usuarios_activos || 0;
    if (adminsEl) adminsEl.textContent = estadisticas.administradores || 0;
    if (productoresEl) productoresEl.textContent = estadisticas.productores || 0;
}

// Cargar lista de usuarios
async function cargarUsuarios(page = 1) {
    try {
        currentPage = page;
        const params = new URLSearchParams({
            page: page,
            limit: 10,
            search: currentSearch
        });
        
        const response = await fetch(`php/usuarios.php?${params}`);
        const data = await response.json();
        
        if (data.success) {
            usuarios = data.data;
            mostrarUsuarios(usuarios);
            displayPagination(data.pagination);
            calcularEstadisticas(); // Recalcular estadísticas
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
                <td colspan="7" class="loading">
                    <i class="fas fa-info-circle"></i> No hay usuarios registrados
                </td>
            </tr>
        `;
        return;
    }
    
    tbody.innerHTML = listaUsuarios.map(usuario => {
        const iniciales = usuario.nombre ? usuario.nombre.charAt(0).toUpperCase() : 'U';
        const rolDisplay = getRolDisplay(usuario.rol);
        const estadoClass = usuario.estado || 'activo';
        const fechaRegistro = usuario.fecha_registro ? 
            new Date(usuario.fecha_registro).toLocaleDateString('es-ES') : 
            'N/A';
        
        return `
            <tr>
                <td>${usuario.id_usuario}</td>
                <td>
                    <div class="user-info">
                        <div class="user-avatar">
                            ${iniciales}
                        </div>
                        <div class="user-details">
                            <h4>${usuario.nombre || 'Sin nombre'}</h4>
                        </div>
                    </div>
                </td>
                <td>${usuario.correo || '-'}</td>
                <td>
                    <span class="role-badge ${usuario.rol}">${rolDisplay}</span>
                </td>
                <td>
                    <span class="status-badge ${estadoClass}">${estadoClass}</span>
                </td>
                <td>${fechaRegistro}</td>
                <td>
                    <div class="table-actions">
                        <button class="btn btn-info" onclick="editarUsuario(${usuario.id_usuario})" title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-danger" onclick="eliminarUsuario(${usuario.id_usuario})" title="Eliminar">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

// Obtener display del rol
function getRolDisplay(rol) {
    const roles = {
        'admin': 'Administrador',
        'productor': 'Productor',
        'cliente': 'Cliente',
        'contador': 'Contador'
    };
    return roles[rol] || rol;
}

// Mostrar paginación
function displayPagination(pagination) {
    const paginationDiv = document.getElementById('pagination');
    if (!paginationDiv) return;
    
    paginationDiv.innerHTML = '';
    
    if (pagination.total_pages <= 1) return;
    
    const prevBtn = document.createElement('button');
    prevBtn.className = 'pagination-btn';
    prevBtn.innerHTML = '<i class="fas fa-chevron-left"></i>';
    prevBtn.disabled = pagination.current_page === 1;
    prevBtn.onclick = () => cargarUsuarios(pagination.current_page - 1);
    paginationDiv.appendChild(prevBtn);
    
    for (let i = 1; i <= pagination.total_pages; i++) {
        if (i === 1 || i === pagination.total_pages || 
            (i >= pagination.current_page - 2 && i <= pagination.current_page + 2)) {
            const pageBtn = document.createElement('button');
            pageBtn.className = `pagination-btn ${i === pagination.current_page ? 'active' : ''}`;
            pageBtn.textContent = i;
            pageBtn.onclick = () => cargarUsuarios(i);
            paginationDiv.appendChild(pageBtn);
        } else if (i === pagination.current_page - 3 || i === pagination.current_page + 3) {
            const dots = document.createElement('span');
            dots.textContent = '...';
            dots.style.padding = '0.4rem';
            paginationDiv.appendChild(dots);
        }
    }
    
    const nextBtn = document.createElement('button');
    nextBtn.className = 'pagination-btn';
    nextBtn.innerHTML = '<i class="fas fa-chevron-right"></i>';
    nextBtn.disabled = pagination.current_page === pagination.total_pages;
    nextBtn.onclick = () => cargarUsuarios(pagination.current_page + 1);
    paginationDiv.appendChild(nextBtn);
}

// Filtrar usuarios
function filtrarUsuarios() {
    const filtroRol = document.getElementById('filtroRol').value;
    const filtroEstado = document.getElementById('filtroEstado').value;
    
    // Recargar con filtros
    currentPage = 1;
    cargarUsuarios();
}

// Mostrar modal de nuevo usuario
function mostrarModalNuevoUsuario() {
    const modal = document.getElementById('modalNuevoUsuario');
    if (modal) {
        modal.style.display = 'block';
        document.getElementById('formNuevoUsuario').reset();
    }
}

// Crear nuevo usuario
async function crearUsuario(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    
    // Validar contraseñas
    const contraseña = formData.get('contraseña');
    const confirmar_contraseña = formData.get('confirmar_contraseña');
    
    if (contraseña !== confirmar_contraseña) {
        mostrarNotificacion('Las contraseñas no coinciden', 'error');
        return;
    }
    
    try {
        const response = await fetch('php/usuarios.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            mostrarNotificacion('Usuario creado exitosamente', 'success');
            cerrarModal('modalNuevoUsuario');
            cargarDatos();
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
    try {
        const response = await fetch(`php/usuarios.php?id_usuario=${idUsuario}`);
        const data = await response.json();
        
        if (data.success && data.data) {
            const usuario = data.data;
            
            // Llenar formulario de edición
            document.getElementById('editId').value = usuario.id_usuario;
            document.getElementById('editNombre').value = usuario.nombre || '';
            document.getElementById('editCorreo').value = usuario.correo || '';
            document.getElementById('editRol').value = usuario.rol || 'productor';
            document.getElementById('editEstado').value = usuario.estado || 'activo';
            document.getElementById('editContraseña').value = '';
            document.getElementById('editConfirmarContraseña').value = '';
            
            document.getElementById('modalEditarUsuario').style.display = 'block';
        } else {
            mostrarNotificacion('Error al cargar datos del usuario', 'error');
        }
    } catch (error) {
        console.error('Error cargando usuario:', error);
        mostrarNotificacion('Error al cargar datos del usuario', 'error');
    }
}

// Actualizar usuario
async function actualizarUsuario(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    
    // Validar contraseñas si se proporcionan
    const contraseña = formData.get('contraseña');
    const confirmar_contraseña = formData.get('confirmar_contraseña');
    
    if (contraseña && contraseña !== confirmar_contraseña) {
        mostrarNotificacion('Las contraseñas no coinciden', 'error');
        return;
    }
    
    // Si no hay contraseña, remover del formData
    if (!contraseña) {
        formData.delete('contraseña');
        formData.delete('confirmar_contraseña');
    }
    
    try {
        // Convertir FormData a URL-encoded para PUT
        const params = new URLSearchParams();
        for (let [key, value] of formData.entries()) {
            params.append(key, value);
        }
        
        const response = await fetch('php/usuarios.php', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: params
        });
        
        const result = await response.json();
        
        if (result.success) {
            mostrarNotificacion('Usuario actualizado exitosamente', 'success');
            cerrarModal('modalEditarUsuario');
            cargarDatos();
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
    if (!usuario) {
        // Intentar obtener el usuario
        try {
            const response = await fetch(`php/usuarios.php?id_usuario=${idUsuario}`);
            const data = await response.json();
            if (data.success && data.data) {
                usuario = data.data;
            }
        } catch (error) {
            console.error('Error obteniendo usuario:', error);
        }
    }
    
    const nombre = usuario ? usuario.nombre : 'este usuario';
    
    if (!confirm(`¿Estás seguro de que quieres eliminar a ${nombre}?`)) {
        return;
    }
    
    try {
        const params = new URLSearchParams();
        params.append('id_usuario', idUsuario);
        
        const response = await fetch('php/usuarios.php', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: params
        });
        
        const result = await response.json();
        
        if (result.success) {
            mostrarNotificacion('Usuario eliminado exitosamente', 'success');
            cargarDatos();
        } else {
            mostrarNotificacion('Error: ' + result.message, 'error');
        }
    } catch (error) {
        console.error('Error eliminando usuario:', error);
        mostrarNotificacion('Error eliminando usuario', 'error');
    }
}

// Cerrar modal
function cerrarModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
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
    const notification = document.createElement('div');
    notification.className = `notification ${tipo}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas fa-${tipo === 'success' ? 'check-circle' : tipo === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
            <span>${mensaje}</span>
        </div>
    `;
    
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
    
    document.body.appendChild(notification);
    
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
        font-size: 14px;
    }
    
    .user-details h4 {
        color: #2d5016;
        margin-bottom: 5px;
        font-size: 14px;
    }
    
    .role-badge {
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: capitalize;
    }
    
    .role-badge.admin {
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
