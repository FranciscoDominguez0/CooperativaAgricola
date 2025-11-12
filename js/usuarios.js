// JavaScript para Gestión de Usuarios - Cooperativa Agrícola La Pintada

let currentUser = null;
let currentPage = 1;
let totalPages = 1;

document.addEventListener('DOMContentLoaded', function() {
    checkSession();
    setupEventListeners();
});

async function checkSession() {
    try {
        console.log('Verificando sesión...');
        updateDebugInfo('Verificando sesión...');
        
        const response = await fetch('php/verificar_sesion.php');
        const data = await response.json();
        
        console.log('Respuesta de sesión:', data);
        updateDebugInfo('Sesión verificada: ' + (data.authenticated ? 'Autenticado' : 'No autenticado'));
        
        if (data.authenticated) {
            currentUser = data.user;
            console.log('Usuario autenticado:', currentUser);
            updateDebugInfo('Cargando página de usuarios...');
            loadUsuariosPage();
        } else {
            console.log('Usuario no autenticado, redirigiendo a login');
            updateDebugInfo('Redirigiendo al login...');
            window.location.href = 'login.html';
        }
    } catch (error) {
        console.error('Error checking session:', error);
        updateDebugInfo('Error de conexión: ' + error.message);
        showToast('Error de conexión. Redirigiendo al login...', 'error');
        setTimeout(() => {
            window.location.href = 'login.html';
        }, 2000);
    }
}

function updateDebugInfo(message) {
    const debugElement = document.getElementById('debugInfo');
    if (debugElement) {
        debugElement.textContent = message;
    }
}

// ===== FUNCIONES DE ANIMACIÓN =====

function animatePageTransition(callback) {
    const tableContainer = document.querySelector('.table-container');
    const tbody = document.getElementById('usuariosTableBody');
    
    // Animación de salida
    tbody.style.animation = 'fadeOutDown 0.3s ease-in forwards';
    
    setTimeout(() => {
        callback();
        // Animación de entrada
        tbody.style.animation = 'fadeInUp 0.5s ease-out forwards';
    }, 300);
}

function animateModalOpen(modal) {
    modal.style.display = 'flex';
    modal.classList.add('modal-backdrop-enter');
    
    const modalContent = modal.querySelector('.modal-content');
    modalContent.classList.add('modal-enter');
    
    // Animar elementos del formulario
    const formElements = modal.querySelectorAll('.form-group, .modal-buttons');
    formElements.forEach((element, index) => {
        element.style.opacity = '0';
        element.style.transform = 'translateY(20px)';
        element.style.transition = 'all 0.3s ease';
        
        setTimeout(() => {
            element.style.opacity = '1';
            element.style.transform = 'translateY(0)';
        }, 200 + (index * 100));
    });
}

function animateModalClose(modal) {
    const modalContent = modal.querySelector('.modal-content');
    modalContent.classList.add('modal-exit');
    modal.classList.add('modal-backdrop-exit');
    
    setTimeout(() => {
        modal.style.display = 'none';
        modal.classList.remove('modal-backdrop-enter', 'modal-backdrop-exit');
        modalContent.classList.remove('modal-enter', 'modal-exit');
    }, 300);
}

function animateRowDelete(row) {
    row.classList.add('table-row-exit');
    
    setTimeout(() => {
        row.remove();
    }, 300);
}

function animateRowAdd(row) {
    row.style.opacity = '0';
    row.style.transform = 'translateY(20px)';
    row.style.transition = 'all 0.5s ease';
    
    setTimeout(() => {
        row.style.opacity = '1';
        row.style.transform = 'translateY(0)';
    }, 100);
}

function animateButtonClick(button) {
    button.classList.add('micro-bounce');
    setTimeout(() => {
        button.classList.remove('micro-bounce');
    }, 300);
}

function animateSearch() {
    const searchInput = document.getElementById('searchInput');
    searchInput.classList.add('pulse-animation');
    
    setTimeout(() => {
        searchInput.classList.remove('pulse-animation');
    }, 1000);
}

function animateLoading() {
    const loading = document.getElementById('loading');
    if (loading) {
        loading.style.display = 'flex';
        loading.classList.add('animate-fade-in-scale');
    }
}

function animateLoadingHide() {
    const loading = document.getElementById('loading');
    if (loading) {
        loading.classList.add('animate-fade-out-scale');
        setTimeout(() => {
            loading.style.display = 'none';
            loading.classList.remove('animate-fade-in-scale', 'animate-fade-out-scale');
        }, 300);
    }
}

function loadUsuariosPage() {
    animateLoadingHide();
    document.getElementById('userName').textContent = currentUser.nombre;
    document.getElementById('userRole').textContent = getRoleDisplay(currentUser.rol);
    
    // Cargar usuarios al iniciar la página
    loadUsuarios();
}

function getRoleDisplay(role) {
    const roles = {
        'admin': 'Administrador',
        'productor': 'Productor Agrícola',
        'cliente': 'Cliente',
        'contador': 'Contador'
    };
    return roles[role] || 'Miembro';
}

async function loadUsuarios(page = 1, search = '') {
    try {
        console.log('Cargando usuarios...', { page, search });
        updateDebugInfo('Cargando datos de usuarios...');
        
        const params = new URLSearchParams({
            page: page,
            search: search
        });
        
        console.log('Parámetros de búsqueda:', params.toString());
        
        const response = await fetch(`php/usuarios.php?${params}`);
        console.log('Respuesta del servidor:', response.status);
        updateDebugInfo(`Servidor respondió: ${response.status}`);
        
        const data = await response.json();
        console.log('Datos recibidos:', data);
        
        if (data.success) {
            console.log('Usuarios cargados exitosamente:', data.data);
            updateDebugInfo(`Usuarios cargados: ${data.data.length} registros`);
            displayUsuarios(data.data);
            displayPagination(data.pagination);
            currentPage = data.pagination.current_page;
            totalPages = data.pagination.total_pages;
        } else {
            console.error('Error del servidor:', data.message);
            updateDebugInfo('Error: ' + data.message);
            showToast('Error al cargar usuarios: ' + data.message, 'error');
        }
    } catch (error) {
        console.error('Error al cargar usuarios:', error);
        updateDebugInfo('Error de conexión: ' + error.message);
        showToast('Error de conexión al cargar usuarios', 'error');
    }
}

function displayUsuarios(usuarios) {
    const tbody = document.getElementById('usuariosTableBody');
    tbody.innerHTML = '';
    
    usuarios.forEach((usuario, index) => {
        const row = document.createElement('tr');
        row.className = 'table-row-enter';
        row.style.setProperty('--row-index', index);
        row.innerHTML = `
            <td>${usuario.id_usuario}</td>
            <td>${usuario.nombre}</td>
            <td>${usuario.correo}</td>
            <td><span class="status-badge status-${usuario.rol} smooth-transition">${getRoleDisplay(usuario.rol)}</span></td>
            <td><span class="status-badge status-${usuario.estado} smooth-transition">${usuario.estado}</span></td>
            <td>${formatDate(usuario.fecha_registro)}</td>
            <td>
                <div class="actions">
                    <button class="btn btn-sm btn-secondary btn-animate hover-scale micro-bounce" onclick="editUsuario(${usuario.id_usuario})" title="Editar usuario">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger btn-animate hover-scale micro-bounce" onclick="confirmDeleteUsuario(${usuario.id_usuario}, '${usuario.nombre}')" title="Eliminar usuario">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        `;
        tbody.appendChild(row);
    });
}

function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('es-ES', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit'
    });
}

function displayPagination(pagination) {
    const paginationDiv = document.getElementById('pagination');
    paginationDiv.innerHTML = '';
    
    const prevBtn = document.createElement('button');
    prevBtn.className = 'pagination-btn btn-animate hover-lift';
    prevBtn.innerHTML = '<i class="fas fa-chevron-left"></i>';
    prevBtn.disabled = pagination.current_page === 1;
    prevBtn.onclick = () => {
        animatePageTransition(() => loadUsuarios(pagination.current_page - 1, document.getElementById('searchInput').value));
    };
    paginationDiv.appendChild(prevBtn);
    
    for (let i = 1; i <= pagination.total_pages; i++) {
        if (i === 1 || i === pagination.total_pages || (i >= pagination.current_page - 2 && i <= pagination.current_page + 2)) {
            const pageBtn = document.createElement('button');
            pageBtn.className = `pagination-btn btn-animate hover-lift ${i === pagination.current_page ? 'active' : ''}`;
            pageBtn.textContent = i;
            pageBtn.onclick = () => {
                animatePageTransition(() => loadUsuarios(i, document.getElementById('searchInput').value));
            };
            paginationDiv.appendChild(pageBtn);
        } else if (i === pagination.current_page - 3 || i === pagination.current_page + 3) {
            const dots = document.createElement('span');
            dots.textContent = '...';
            dots.style.padding = '0.4rem';
            dots.className = 'smooth-transition';
            paginationDiv.appendChild(dots);
        }
    }
    
    const nextBtn = document.createElement('button');
    nextBtn.className = 'pagination-btn btn-animate hover-lift';
    nextBtn.innerHTML = '<i class="fas fa-chevron-right"></i>';
    nextBtn.disabled = pagination.current_page === pagination.total_pages;
    nextBtn.onclick = () => {
        animatePageTransition(() => loadUsuarios(pagination.current_page + 1, document.getElementById('searchInput').value));
    };
    paginationDiv.appendChild(nextBtn);
}

function setupEventListeners() {
    // Navegación
    document.querySelectorAll('.nav-item').forEach(item => {
        item.addEventListener('click', function() {
            const section = this.dataset.section;
            
            if (section === 'dashboard') {
                window.location.href = 'dashboard.html';
                return;
            }
            
            if (section === 'socios') {
                window.location.href = 'socios.html';
                return;
            }
            
            document.querySelectorAll('.nav-item').forEach(nav => nav.classList.remove('active'));
            this.classList.add('active');
        });
    });

    // Logout
    document.getElementById('logoutBtn').addEventListener('click', function() {
        document.getElementById('logoutModal').style.display = 'flex';
    });

    document.getElementById('confirmLogout').addEventListener('click', async function() {
        try {
            const response = await fetch('php/logout.php', {
                method: 'POST'
            });
            const data = await response.json();

            if (data.success) {
                window.location.href = 'index.html';
            } else {
                showToast('Error al cerrar sesión', 'error');
            }
        } catch (error) {
            console.error('Error logging out:', error);
            window.location.href = 'index.html';
        }
    });

    document.getElementById('cancelLogout').addEventListener('click', function() {
        document.getElementById('logoutModal').style.display = 'none';
    });

    // Búsqueda de usuarios
    document.getElementById('searchInput').addEventListener('input', function() {
        const searchTerm = this.value;
        animateSearch();
        loadUsuarios(1, searchTerm);
    });

    // Agregar usuario
    document.getElementById('addUsuarioBtn').addEventListener('click', function() {
        animateButtonClick(this);
        openUsuarioModal();
    });

    // Formulario de usuario
    document.getElementById('usuarioForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        await saveUsuario();
    });

    document.getElementById('cancelBtn').addEventListener('click', function() {
        closeUsuarioModal();
    });

    document.getElementById('usuarioModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeUsuarioModal();
        }
    });
}

function openUsuarioModal(usuario = null) {
    const modal = document.getElementById('usuarioModal');
    const form = document.getElementById('usuarioForm');
    const title = document.getElementById('modalTitle');
    const passwordRow = document.getElementById('passwordRow');
    const passwordInput = document.getElementById('contraseña');
    const confirmPasswordInput = document.getElementById('confirmar_contraseña');
    const passwordLabel = document.getElementById('passwordLabel');
    const confirmPasswordLabel = document.getElementById('confirmPasswordLabel');
    
    if (usuario) {
        // MODO EDICIÓN - Editar usuario existente
        title.textContent = 'Editar Usuario';
        console.log('MODO EDICIÓN - Estableciendo datos en el formulario:', usuario);
        
        // Limpiar formulario primero
        form.reset();
        
        // Establecer datos del usuario
        document.getElementById('usuarioId').value = usuario.id_usuario;
        document.getElementById('nombre').value = usuario.nombre;
        document.getElementById('correo').value = usuario.correo;
        document.getElementById('rol').value = usuario.rol;
        document.getElementById('estado').value = usuario.estado;
        
        // Hacer campos de contraseña opcionales en edición
        passwordLabel.innerHTML = 'Contraseña <small>(dejar vacío para no cambiar)</small>';
        confirmPasswordLabel.innerHTML = 'Confirmar Contraseña <small>(solo si desea cambiar)</small>';
        passwordInput.removeAttribute('required');
        confirmPasswordInput.removeAttribute('required');
        passwordInput.value = '';
        confirmPasswordInput.value = '';
    } else {
        // MODO CREACIÓN - Agregar nuevo usuario
        title.textContent = 'Agregar Nuevo Usuario';
        console.log('MODO CREACIÓN - Limpiando formulario para nuevo usuario');
        
        // Limpiar completamente el formulario
        form.reset();
        
        // FORZAR limpieza del campo ID
        const idField = document.getElementById('usuarioId');
        if (idField) {
            idField.value = '';
            idField.removeAttribute('value');
            idField.setAttribute('value', '');
            console.log('Campo ID limpiado y forzado a vacío:', idField.value);
        }
        
        // Hacer campos de contraseña obligatorios en creación
        passwordLabel.innerHTML = 'Contraseña *';
        confirmPasswordLabel.innerHTML = 'Confirmar Contraseña *';
        passwordInput.setAttribute('required', 'required');
        confirmPasswordInput.setAttribute('required', 'required');
        passwordInput.value = '';
        confirmPasswordInput.value = '';
        
        // Establecer valores por defecto
        const estadoField = document.getElementById('estado');
        if (estadoField) {
            estadoField.value = 'activo';
        }
        
        console.log('Formulario completamente limpio para nuevo usuario');
    }
    
    animateModalOpen(modal);
}

function closeUsuarioModal() {
    const modal = document.getElementById('usuarioModal');
    
    // Limpiar completamente el formulario al cerrar
    const form = document.getElementById('usuarioForm');
    if (form) {
        form.reset();
        
        // Limpiar específicamente el campo ID
        const idField = document.getElementById('usuarioId');
        if (idField) {
            idField.value = '';
            idField.removeAttribute('value');
        }
        
        console.log('Formulario limpiado al cerrar modal');
    }
    
    animateModalClose(modal);
}

async function saveUsuario() {
    const form = document.getElementById('usuarioForm');
    const usuarioId = document.getElementById('usuarioId').value;
    
    // VERIFICACIÓN ESTRICTA - Si hay ID es actualización, si no hay ID es creación
    const isUpdate = usuarioId && usuarioId.trim() !== '';
    
    console.log('=== VERIFICACIÓN DE ACCIÓN ===');
    console.log('UsuarioId value:', usuarioId);
    console.log('Is Update:', isUpdate);
    console.log('Acción:', isUpdate ? 'ACTUALIZAR usuario existente' : 'CREAR nuevo usuario');
    
    try {
        const url = 'php/usuarios.php';
        
        if (isUpdate) {
            // ACTUALIZAR: Usar PUT con URL-encoded
            const formData = new FormData(form);
            const params = new URLSearchParams();
            for (let [key, value] of formData.entries()) {
                // Solo agregar contraseña si se proporcionó
                if ((key === 'contraseña' || key === 'confirmar_contraseña') && !value) {
                    continue;
                }
                params.append(key, value);
            }
            
            console.log('ACTUALIZANDO usuario con ID:', usuarioId);
            console.log('Params being sent:', params.toString());
            
            const response = await fetch(url, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: params
            });

            const data = await response.json();

            if (data.success) {
                closeUsuarioModal();
                loadUsuarios(currentPage, document.getElementById('searchInput').value);
                showToast('✏️ Usuario actualizado exitosamente', 'update');
            } else {
                showToast('🚨 Error al actualizar usuario: ' + data.message, 'error');
            }
        } else {
            // CREAR: Usar POST con FormData
            console.log('CREANDO NUEVO USUARIO - No hay ID, es un usuario nuevo');
            
            // CREAR FormData SIN el campo id_usuario
            const formData = new FormData();
            const formElements = form.querySelectorAll('input, select, textarea');
            
            formElements.forEach(element => {
                // EXCLUIR completamente el campo id_usuario
                if (element.name !== 'id_usuario' && element.name !== '' && element.value) {
                    formData.append(element.name, element.value);
                    console.log(`Agregando campo: ${element.name} = ${element.value}`);
                }
            });
            
            console.log('FormData contents (SIN id_usuario):');
            for (let [key, value] of formData.entries()) {
                console.log(`${key}: ${value}`);
            }
            
            const response = await fetch(url, {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                closeUsuarioModal();
                loadUsuarios(currentPage, document.getElementById('searchInput').value);
                showToast('🌱 Nuevo usuario agregado exitosamente', 'create');
            } else {
                showToast('🚨 Error al agregar usuario: ' + data.message, 'error');
            }
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Error al guardar el usuario', 'error');
    }
}

async function editUsuario(id) {
    try {
        console.log('Editando usuario con ID:', id);
        
        // Obtener datos completos del usuario desde la base de datos
        const response = await fetch(`php/usuarios.php?id_usuario=${id}`);
        const data = await response.json();
        
        console.log('Datos recibidos del servidor:', data);
        
        if (data.success && data.data) {
            const usuario = data.data;
            console.log('Datos del usuario a editar:', usuario);
            openUsuarioModal(usuario);
        } else {
            showToast('Error al cargar los datos del usuario', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Error al cargar los datos del usuario', 'error');
    }
}

function confirmDeleteUsuario(id, nombre) {
    const confirmationModal = document.createElement('div');
    confirmationModal.className = 'confirmation-modal';
    confirmationModal.id = 'confirmationModal';
    
    confirmationModal.innerHTML = `
        <div class="confirmation-content">
            <div class="confirmation-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3 class="confirmation-title">¿Eliminar Usuario?</h3>
            <p class="confirmation-message">
                ¿Estás seguro de que deseas eliminar al usuario <strong>"${nombre}"</strong>?<br>
                Esta acción no se puede deshacer y se perderán todos los datos asociados.
            </p>
            <div class="confirmation-buttons">
                <button class="btn btn-secondary" id="cancelDeleteBtn">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button class="btn btn-danger" id="confirmDeleteBtn">
                    <i class="fas fa-trash"></i> Sí, Eliminar
                </button>
            </div>
        </div>
    `;
    
    document.body.appendChild(confirmationModal);
    confirmationModal.style.display = 'flex';
    
    document.getElementById('cancelDeleteBtn').addEventListener('click', function() {
        confirmationModal.remove();
    });
    
    document.getElementById('confirmDeleteBtn').addEventListener('click', async function() {
        await deleteUsuario(id);
        confirmationModal.remove();
    });
    
    confirmationModal.addEventListener('click', function(e) {
        if (e.target === confirmationModal) {
            confirmationModal.remove();
        }
    });
}

async function deleteUsuario(id) {
    try {
        // Encontrar la fila a eliminar para animarla
        const rows = document.querySelectorAll('#usuariosTableBody tr');
        const rowToDelete = Array.from(rows).find(row => 
            row.querySelector('td:first-child').textContent === id.toString()
        );
        
        if (rowToDelete) {
            animateRowDelete(rowToDelete);
        }
        
        // Para DELETE, usar URL-encoded (como socios.php)
        const params = new URLSearchParams();
        params.append('id_usuario', id);
        
        const response = await fetch('php/usuarios.php', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: params
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Recargar datos después de la animación
            setTimeout(() => {
                loadUsuarios(currentPage, document.getElementById('searchInput').value);
            }, 300);
            showToast('🗑️ Usuario eliminado exitosamente', 'delete');
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Error al eliminar el usuario', 'error');
    }
}

function showToast(message, type) {
    const toast = document.createElement('div');
    toast.className = `toast ${type} toast-enter`;
    toast.textContent = message;
    
    // Agregar barra de progreso
    const progressBar = document.createElement('div');
    progressBar.className = 'toast-progress';
    toast.appendChild(progressBar);
    
    // Agregar efectos especiales según el tipo
    if (type === 'success' || type === 'create') {
        toast.classList.add('glow');
    } else if (type === 'error') {
        toast.classList.add('pulse');
    } else if (type === 'warning' || type === 'update') {
        toast.classList.add('shimmer');
    } else if (type === 'delete') {
        toast.classList.add('pulse');
    }
    
    document.getElementById('toastContainer').appendChild(toast);
    
    // Animación de entrada con bounce
    setTimeout(() => {
        toast.classList.add('show', 'toast-bounce');
    }, 100);
    
    // Efecto hover para pausar el timer
    toast.addEventListener('mouseenter', () => {
        toast.style.animationPlayState = 'paused';
        toast.style.transform = 'translateX(-5px) scale(1.02)';
    });
    
    toast.addEventListener('mouseleave', () => {
        toast.style.animationPlayState = 'running';
        toast.style.transform = 'translateX(0) scale(1)';
    });
    
    // Click para cerrar manualmente
    toast.addEventListener('click', () => {
        closeToast(toast);
    });
    
    // Auto-close después de 4 segundos
    setTimeout(() => {
        if (toast.parentNode) {
            closeToast(toast);
        }
    }, 4000);
}

function closeToast(toast) {
    toast.classList.remove('show', 'toast-bounce');
    toast.classList.add('toast-exit');
    
    setTimeout(() => {
        if (toast.parentNode) {
            toast.remove();
        }
    }, 300);
}
