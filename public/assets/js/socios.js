// JavaScript para Gestión de Socios - Cooperativa Agrícola La Pintada

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
            updateDebugInfo('Cargando página de socios...');
            loadSociosPage();
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
    const tbody = document.getElementById('sociosTableBody');
    
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

function loadSociosPage() {
    animateLoadingHide();
    document.getElementById('userName').textContent = currentUser.nombre;
    document.getElementById('userRole').textContent = getRoleDisplay(currentUser.rol);
    
    // Cargar socios al iniciar la página
    loadSocios();
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

async function loadSocios(page = 1, search = '') {
    try {
        console.log('Cargando socios...', { page, search });
        updateDebugInfo('Cargando datos de socios...');
        
        const params = new URLSearchParams({
            page: page,
            limit: 10,
            search: search
        });
        
        console.log('Parámetros de búsqueda:', params.toString());
        
        const response = await fetch(`php/socios.php?${params}`);
        console.log('Respuesta del servidor:', response.status);
        updateDebugInfo(`Servidor respondió: ${response.status}`);
        
        const data = await response.json();
        console.log('Datos recibidos:', data);
        
        if (data.success) {
            console.log('Socios cargados exitosamente:', data.data);
            updateDebugInfo(`Socios cargados: ${data.data.length} registros`);
            displaySocios(data.data);
            displayPagination(data.pagination);
            currentPage = data.pagination.current_page;
            totalPages = data.pagination.total_pages;
        } else {
            console.error('Error del servidor:', data.message);
            updateDebugInfo('Error: ' + data.message);
            showToast('Error al cargar socios: ' + data.message, 'error');
        }
    } catch (error) {
        console.error('Error al cargar socios:', error);
        updateDebugInfo('Error de conexión: ' + error.message);
        showToast('Error de conexión al cargar socios', 'error');
    }
}

function displaySocios(socios) {
    const tbody = document.getElementById('sociosTableBody');
    tbody.innerHTML = '';
    
    socios.forEach((socio, index) => {
        const row = document.createElement('tr');
        row.className = 'table-row-enter';
        row.style.setProperty('--row-index', index);
        row.innerHTML = `
            <td>${socio.id_socio}</td>
            <td>${socio.nombre}</td>
            <td>${socio.cedula}</td>
            <td>${socio.telefono || '-'}</td>
            <td>${socio.email || '-'}</td>
            <td><span class="status-badge status-${socio.estado} smooth-transition">${socio.estado}</span></td>
            <td>$${parseFloat(socio.aportes_totales).toLocaleString()}</td>
            <td>$${parseFloat(socio.deudas_pendientes).toLocaleString()}</td>
            <td>
                <div class="actions">
                    <button class="btn btn-sm btn-secondary btn-animate hover-scale micro-bounce" onclick="editSocio(${socio.id_socio})" title="Editar socio">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger btn-animate hover-scale micro-bounce" onclick="confirmDeleteSocio(${socio.id_socio}, '${socio.nombre}')" title="Eliminar socio">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        `;
        tbody.appendChild(row);
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
        animatePageTransition(() => loadSocios(pagination.current_page - 1, document.getElementById('searchInput').value));
    };
    paginationDiv.appendChild(prevBtn);
    
    for (let i = 1; i <= pagination.total_pages; i++) {
        if (i === 1 || i === pagination.total_pages || (i >= pagination.current_page - 2 && i <= pagination.current_page + 2)) {
            const pageBtn = document.createElement('button');
            pageBtn.className = `pagination-btn btn-animate hover-lift ${i === pagination.current_page ? 'active' : ''}`;
            pageBtn.textContent = i;
            pageBtn.onclick = () => {
                animatePageTransition(() => loadSocios(i, document.getElementById('searchInput').value));
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
        animatePageTransition(() => loadSocios(pagination.current_page + 1, document.getElementById('searchInput').value));
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

    // Búsqueda de socios
    document.getElementById('searchInput').addEventListener('input', function() {
        const searchTerm = this.value;
        animateSearch();
        loadSocios(1, searchTerm);
    });

    // Agregar socio
    document.getElementById('addSocioBtn').addEventListener('click', function() {
        animateButtonClick(this);
        openSocioModal();
    });


    // Formulario de socio
    document.getElementById('socioForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        await saveSocio();
    });

    document.getElementById('cancelBtn').addEventListener('click', function() {
        closeSocioModal();
    });

    document.getElementById('socioModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeSocioModal();
        }
    });
}

function openSocioModal(socio = null) {
    const modal = document.getElementById('socioModal');
    const form = document.getElementById('socioForm');
    const title = document.getElementById('modalTitle');
    
    if (socio) {
        // MODO EDICIÓN - Editar socio existente
        title.textContent = 'Editar Socio';
        console.log('MODO EDICIÓN - Estableciendo datos en el formulario:', socio);
        
        // Limpiar formulario primero
        form.reset();
        
        // Establecer datos del socio
        Object.keys(socio).forEach(key => {
            let fieldId = key;
            
            // Mapear id_socio a socioId para el HTML
            if (key === 'id_socio') {
                fieldId = 'socioId';
            }
            
            const field = document.getElementById(fieldId);
            if (field) {
                field.value = socio[key];
                console.log(`Campo ${key} (${fieldId}) establecido con valor:`, socio[key]);
            } else {
                console.log(`Campo ${key} (${fieldId}) no encontrado en el formulario`);
            }
        });
        
        // Verificar que el campo id_socio esté establecido
        const idField = document.getElementById('socioId');
        console.log('Campo socioId encontrado:', idField);
        console.log('Valor del campo socioId:', idField ? idField.value : 'NO ENCONTRADO');
    } else {
        // MODO CREACIÓN - Agregar nuevo socio
        title.textContent = 'Agregar Nuevo Socio';
        console.log('MODO CREACIÓN - Limpiando formulario para nuevo socio');
        
        // Limpiar completamente el formulario
        form.reset();
        
        // FORZAR limpieza del campo ID - CRÍTICO
        const idField = document.getElementById('socioId');
        if (idField) {
            idField.value = '';
            idField.removeAttribute('value');
            idField.setAttribute('value', '');
            console.log('Campo ID limpiado y forzado a vacío:', idField.value);
        }
        
        // Limpiar también el campo hidden
        const hiddenIdField = document.querySelector('input[name="id_socio"]');
        if (hiddenIdField) {
            hiddenIdField.value = '';
            hiddenIdField.removeAttribute('value');
            hiddenIdField.setAttribute('value', '');
            console.log('Campo hidden ID limpiado:', hiddenIdField.value);
        }
        
        // VERIFICACIÓN FINAL: Asegurar que no hay ID residual
        setTimeout(() => {
            const finalCheck = document.getElementById('socioId');
            console.log('VERIFICACIÓN FINAL - Campo ID value:', finalCheck ? finalCheck.value : 'NO ENCONTRADO');
            console.log('VERIFICACIÓN FINAL - Campo ID empty?', finalCheck ? (finalCheck.value === '' || finalCheck.value === null || finalCheck.value === undefined) : 'NO ENCONTRADO');
        }, 100);
        
        // Establecer fecha actual
        const fechaField = document.getElementById('fecha_ingreso');
        if (fechaField) {
            fechaField.value = new Date().toISOString().split('T')[0];
            console.log('Fecha de ingreso establecida:', fechaField.value);
        }
        
        // Establecer valores por defecto
        const estadoField = document.getElementById('estado');
        if (estadoField) {
            estadoField.value = 'activo';
        }
        
        // Limpiar aportes y deudas
        const aportesField = document.getElementById('aportes_totales');
        if (aportesField) {
            aportesField.value = '0';
        }
        
        const deudasField = document.getElementById('deudas_pendientes');
        if (deudasField) {
            deudasField.value = '0';
        }
        
        console.log('Formulario completamente limpio para nuevo socio');
        console.log('ID Field value:', document.getElementById('socioId').value);
    }
    
    animateModalOpen(modal);
}

function closeSocioModal() {
    const modal = document.getElementById('socioModal');
    
    // Limpiar completamente el formulario al cerrar
    const form = document.getElementById('socioForm');
    if (form) {
        form.reset();
        
        // Limpiar específicamente el campo ID
        const idField = document.getElementById('socioId');
        if (idField) {
            idField.value = '';
            idField.removeAttribute('value');
        }
        
        // Limpiar también el campo hidden
        const hiddenIdField = document.querySelector('input[name="id_socio"]');
        if (hiddenIdField) {
            hiddenIdField.value = '';
            hiddenIdField.removeAttribute('value');
        }
        
        console.log('Formulario limpiado al cerrar modal');
    }
    
    animateModalClose(modal);
}

async function saveSocio() {
    const form = document.getElementById('socioForm');
    const socioId = document.getElementById('socioId').value;
    
    // VERIFICACIÓN ESTRICTA - Si hay ID es actualización, si no hay ID es creación
    const isUpdate = socioId && socioId.trim() !== '';
    
    console.log('=== VERIFICACIÓN DE ACCIÓN ===');
    console.log('SocioId value:', socioId);
    console.log('Is Update:', isUpdate);
    console.log('Acción:', isUpdate ? 'ACTUALIZAR socio existente' : 'CREAR nuevo socio');
    
    try {
        const url = 'php/socios.php';
        
        if (isUpdate) {
            // ACTUALIZAR: Usar PUT con URL-encoded
            const formData = new FormData(form);
            const params = new URLSearchParams();
            for (let [key, value] of formData.entries()) {
                params.append(key, value);
            }
            
            console.log('ACTUALIZANDO socio con ID:', socioId);
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
                closeSocioModal();
                loadSocios(currentPage, document.getElementById('searchInput').value);
                showToast('✏️ Socio actualizado exitosamente', 'update');
            } else {
                showToast('🚨 Error al actualizar socio: ' + data.message, 'error');
            }
        } else {
            // CREAR: Usar POST con FormData
            console.log('CREANDO NUEVO SOCIO - No hay ID, es un socio nuevo');
            
            // CREAR FormData SIN el campo id_socio
            const formData = new FormData();
            const formElements = form.querySelectorAll('input, select, textarea');
            
            formElements.forEach(element => {
                // EXCLUIR completamente el campo id_socio
                if (element.name !== 'id_socio' && element.name !== '') {
                    formData.append(element.name, element.value);
                    console.log(`Agregando campo: ${element.name} = ${element.value}`);
                }
            });
            
            console.log('FormData contents (SIN id_socio):');
            for (let [key, value] of formData.entries()) {
                console.log(`${key}: ${value}`);
            }
            
            const response = await fetch(url, {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                closeSocioModal();
                loadSocios(currentPage, document.getElementById('searchInput').value);
                showToast('🌱 Nuevo socio agregado exitosamente', 'create');
            } else {
                showToast('🚨 Error al agregar socio: ' + data.message, 'error');
            }
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Error al guardar el socio', 'error');
    }
}

async function editSocio(id) {
    try {
        console.log('Editando socio con ID:', id);
        
        // Obtener datos completos del socio desde la base de datos
        const response = await fetch(`php/socios.php?id_socio=${id}`);
        const data = await response.json();
        
        console.log('Datos recibidos del servidor:', data);
        
        if (data.success && data.data) {
            const socio = data.data;
            console.log('Datos del socio a editar:', socio);
            openSocioModal(socio);
        } else {
            showToast('Error al cargar los datos del socio', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Error al cargar los datos del socio', 'error');
    }
}

function confirmDeleteSocio(id, nombre) {
    const confirmationModal = document.createElement('div');
    confirmationModal.className = 'confirmation-modal';
    confirmationModal.id = 'confirmationModal';
    
    confirmationModal.innerHTML = `
        <div class="confirmation-content">
            <div class="confirmation-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3 class="confirmation-title">¿Eliminar Socio?</h3>
            <p class="confirmation-message">
                ¿Estás seguro de que deseas eliminar al socio <strong>"${nombre}"</strong>?<br>
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
        await deleteSocio(id);
        confirmationModal.remove();
    });
    
    confirmationModal.addEventListener('click', function(e) {
        if (e.target === confirmationModal) {
            confirmationModal.remove();
        }
    });
}

async function deleteSocio(id) {
    try {
        // Encontrar la fila a eliminar para animarla
        const rows = document.querySelectorAll('#sociosTableBody tr');
        const rowToDelete = Array.from(rows).find(row => 
            row.querySelector('td:first-child').textContent === id.toString()
        );
        
        if (rowToDelete) {
            animateRowDelete(rowToDelete);
        }
        
        // Para DELETE, necesitamos enviar los datos como URL-encoded
        const params = new URLSearchParams();
        params.append('id_socio', id);
        
        const response = await fetch('php/socios.php', {
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
                loadSocios(currentPage, document.getElementById('searchInput').value);
            }, 300);
            showToast('🗑️ Socio eliminado exitosamente', 'delete');
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Error al eliminar el socio', 'error');
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
    if (type === 'success') {
        toast.classList.add('glow');
    } else if (type === 'error') {
        toast.classList.add('pulse');
    } else if (type === 'warning') {
        toast.classList.add('shimmer');
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
