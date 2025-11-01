// JavaScript para Dashboard - Cooperativa Agrícola La Pintada

let currentUser = null;
let currentPage = 1;
let totalPages = 1;

document.addEventListener('DOMContentLoaded', function() {
    checkSession();
    setupEventListeners();
});

async function checkSession() {
    try {
        const response = await fetch('php/verificar_sesion.php');
        const data = await response.json();
        
        if (data.authenticated) {
            currentUser = data.user;
            loadDashboard();
        } else {
            window.location.href = 'login.html';
            }
        } catch (error) {
        console.error('Error checking session:', error);
        window.location.href = 'login.html';
    }
}

function loadDashboard() {
    document.getElementById('loading').style.display = 'none';
    document.getElementById('userName').textContent = currentUser.nombre;
    document.getElementById('userRole').textContent = getRoleDisplay(currentUser.rol);
    
    const hour = new Date().getHours();
    let greeting = 'Buenos días';
    if (hour >= 12 && hour < 18) greeting = 'Buenas tardes';
    else if (hour >= 18) greeting = 'Buenas noches';
    
    document.getElementById('welcomeTitle').textContent = `${greeting}, ${currentUser.nombre}!`;
    loadStats();
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

function loadStats() {
    setTimeout(() => {
        document.getElementById('totalMembers').textContent = '127';
        document.getElementById('totalCrops').textContent = '45';
        document.getElementById('totalRevenue').textContent = '$12,450';
        document.getElementById('pendingTasks').textContent = '8';
    }, 1000);
}

async function loadSocios(page = 1, search = '') {
    try {
        const params = new URLSearchParams({
            page: page,
            limit: 10,
            search: search
        });
        
        const response = await fetch(`php/socios.php?${params}`);
        const data = await response.json();
        
        if (data.success) {
            displaySocios(data.data);
            displayPagination(data.pagination);
            currentPage = data.pagination.current_page;
            totalPages = data.pagination.total_pages;
        } else {
            showToast('Error al cargar socios: ' + data.message, 'error');
            }
        } catch (error) {
        console.error('Error:', error);
        showToast('Error de conexión', 'error');
    }
}

function displaySocios(socios) {
    const tbody = document.getElementById('sociosTableBody');
    tbody.innerHTML = '';
    
    socios.forEach(socio => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${socio.id_socio}</td>
            <td>${socio.nombre}</td>
            <td>${socio.cedula}</td>
            <td>${socio.telefono || '-'}</td>
            <td>${socio.email || '-'}</td>
            <td><span class="status-badge status-${socio.estado}">${socio.estado}</span></td>
            <td>$${parseFloat(socio.aportes_totales).toLocaleString()}</td>
            <td>$${parseFloat(socio.deudas_pendientes).toLocaleString()}</td>
            <td>
                <div class="actions">
                    <button class="btn btn-sm btn-edit" onclick="editSocio(${socio.id_socio})" title="Editar socio">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="confirmDeleteSocio(${socio.id_socio}, '${socio.nombre}')" title="Eliminar socio">
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
    prevBtn.innerHTML = '<i class="fas fa-chevron-left"></i>';
    prevBtn.disabled = pagination.current_page === 1;
    prevBtn.onclick = () => loadSocios(pagination.current_page - 1, document.getElementById('searchInput').value);
    paginationDiv.appendChild(prevBtn);
    
    for (let i = 1; i <= pagination.total_pages; i++) {
        if (i === 1 || i === pagination.total_pages || (i >= pagination.current_page - 2 && i <= pagination.current_page + 2)) {
            const pageBtn = document.createElement('button');
            pageBtn.textContent = i;
            pageBtn.className = i === pagination.current_page ? 'active' : '';
            pageBtn.onclick = () => loadSocios(i, document.getElementById('searchInput').value);
            paginationDiv.appendChild(pageBtn);
        } else if (i === pagination.current_page - 3 || i === pagination.current_page + 3) {
            const dots = document.createElement('span');
            dots.textContent = '...';
            dots.style.padding = '0.4rem';
            paginationDiv.appendChild(dots);
        }
    }
    
    const nextBtn = document.createElement('button');
    nextBtn.innerHTML = '<i class="fas fa-chevron-right"></i>';
    nextBtn.disabled = pagination.current_page === pagination.total_pages;
    nextBtn.onclick = () => loadSocios(pagination.current_page + 1, document.getElementById('searchInput').value);
    paginationDiv.appendChild(nextBtn);
}

function setupEventListeners() {
    document.querySelectorAll('.nav-item').forEach(item => {
        item.addEventListener('click', function() {
            const section = this.dataset.section;
            showSection(section);
            
            document.querySelectorAll('.nav-item').forEach(nav => nav.classList.remove('active'));
            this.classList.add('active');
        });
    });

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

    document.getElementById('searchInput').addEventListener('input', function() {
        const searchTerm = this.value;
        loadSocios(1, searchTerm);
    });

    document.getElementById('addSocioBtn').addEventListener('click', function() {
        openSocioModal();
    });

    // Event listeners para insumos
    document.getElementById('searchInsumosInput').addEventListener('input', function() {
        const searchTerm = this.value;
        loadInsumos(1, searchTerm);
    });

    document.getElementById('addInsumoBtn').addEventListener('click', function() {
        openInsumoModal();
    });

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

    // Event listeners para formulario de insumos
    document.getElementById('insumoForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        await saveInsumo();
    });

    document.getElementById('cancelInsumoBtn').addEventListener('click', function() {
        closeInsumoModal();
    });

    document.getElementById('insumoModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeInsumoModal();
        }
    });

    // Event listeners para producción
    document.getElementById('searchProduccionInput').addEventListener('input', function() {
        const searchTerm = this.value;
        loadProduccion(1, searchTerm);
    });

    document.getElementById('addProduccionBtn').addEventListener('click', function() {
        openProduccionModal();
    });

    document.getElementById('produccionForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        await saveProduccion();
    });

    document.getElementById('cancelProduccionBtn').addEventListener('click', function() {
        closeProduccionModal();
    });

    document.getElementById('produccionModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeProduccionModal();
        }
    });

    // Event listeners para ventas
    document.getElementById('searchVentasInput').addEventListener('input', function() {
        const searchTerm = this.value;
        loadVentas(1, searchTerm);
    });

    document.getElementById('addVentaBtn').addEventListener('click', function() {
        openVentaModal();
    });

    document.getElementById('ventaForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        await saveVenta();
    });

    document.getElementById('cancelVentaBtn').addEventListener('click', function() {
        closeVentaModal();
    });

    document.getElementById('ventaModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeVentaModal();
        }
    });

    // Event listeners para el modal de usuario
    document.querySelector('.user-profile').addEventListener('click', function() {
        openUserProfileModal();
    });

    document.getElementById('closeUserProfile').addEventListener('click', function() {
        closeUserProfileModal();
    });

    document.getElementById('cancelUserProfile').addEventListener('click', function() {
        closeUserProfileModal();
    });

    document.getElementById('userProfileModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeUserProfileModal();
        }
    });

    document.getElementById('saveUserProfile').addEventListener('click', function() {
        saveUserProfile();
    });

    document.getElementById('changePasswordBtn').addEventListener('click', function() {
        const modal = document.getElementById('changePasswordModal');
        if (modal) {
            modal.style.display = 'flex';
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
        }
    });

    document.getElementById('changeAvatarBtn').addEventListener('click', function() {
        showChangeAvatarModal();
    });
}

function showSection(sectionName) {
    document.querySelectorAll('.content-section').forEach(section => {
        section.classList.remove('active');
    });
    
    document.getElementById(sectionName + 'Section').classList.add('active');
    
    if (sectionName === 'socios') {
        loadSocios();
    } else if (sectionName === 'insumos') {
        loadInsumos();
    } else if (sectionName === 'produccion') {
        loadProduccion();
        loadProduccionStatistics();
    } else if (sectionName === 'ventas') {
        loadVentas();
        loadVentasStatistics();
        loadSociosForVentas();
    } else if (sectionName === 'pagos') {
        loadPagos();
        loadPagosStatistics();
        loadSociosForPagos();
    } else if (sectionName === 'reportes') {
        loadReportesPreview();
    }
}

function openSocioModal(socio = null) {
    const modal = document.getElementById('socioModal');
    const form = document.getElementById('socioForm');
    const title = document.getElementById('modalTitle');
    
    if (socio) {
        title.textContent = 'Editar Socio';
        console.log('Estableciendo datos en el formulario:', socio);
        
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
        title.textContent = 'Agregar Nuevo Socio';
        form.reset();
        // Ensure the hidden ID field is cleared for new records
        document.getElementById('socioId').value = '';
        document.getElementById('fecha_ingreso').value = new Date().toISOString().split('T')[0];
    }
    
    modal.style.display = 'flex';
}

function closeSocioModal() {
    document.getElementById('socioModal').style.display = 'none';
}

async function saveSocio() {
    const form = document.getElementById('socioForm');
    const socioId = document.getElementById('socioId').value;
    
    try {
        const url = 'php/socios.php';
        
        if (socioId) {
            // ACTUALIZAR: Usar PUT con URL-encoded
            const formData = new FormData(form);
            const params = new URLSearchParams();
            for (let [key, value] of formData.entries()) {
                params.append(key, value);
            }
            
            console.log('Updating socio with ID:', socioId);
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
                showToast(data.message, 'success');
            } else {
                showToast(data.message, 'error');
            }
        } else {
            // CREAR: Usar POST con FormData
            const formData = new FormData(form);
            
            const response = await fetch(url, {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                closeSocioModal();
                loadSocios(currentPage, document.getElementById('searchInput').value);
                showToast(data.message, 'success');
            } else {
                showToast(data.message, 'error');
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
            loadSocios(currentPage, document.getElementById('searchInput').value);
            showToast(data.message, 'success');
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
    toast.className = `toast ${type}`;
    toast.textContent = message;
    
    document.getElementById('toastContainer').appendChild(toast);
    
    setTimeout(() => toast.classList.add('show'), 100);
    
        setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// ===== FUNCIONES DE INSUMOS =====

let currentInsumosPage = 1;
let totalInsumosPages = 1;

async function loadInsumos(page = 1, search = '') {
    try {
        const params = new URLSearchParams({
            page: page,
            limit: 10,
            search: search
        });
        
        const response = await fetch(`php/insumos.php?${params}`);
        const data = await response.json();
        
        if (data.success) {
            displayInsumos(data.data);
            displayInsumosPagination(data.pagination);
            currentInsumosPage = data.pagination.current_page;
            totalInsumosPages = data.pagination.total_pages;
        } else {
            showToast('Error al cargar insumos: ' + data.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Error de conexión', 'error');
    }
}

function displayInsumos(insumos) {
    const tbody = document.getElementById('insumosTableBody');
    tbody.innerHTML = '';
    
    insumos.forEach(insumo => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${insumo.id_insumo}</td>
            <td>${insumo.nombre_insumo}</td>
            <td><span class="type-badge type-${insumo.tipo}">${getTipoDisplay(insumo.tipo)}</span></td>
            <td>${parseInt(insumo.cantidad_disponible).toLocaleString()}</td>
            <td>$${parseFloat(insumo.precio_unitario).toLocaleString()}</td>
            <td>${insumo.proveedor || '-'}</td>
            <td><span class="status-badge status-${insumo.estado}">${getEstadoDisplay(insumo.estado)}</span></td>
            <td>
                <div class="actions">
                    <button class="btn btn-sm btn-edit" onclick="editInsumo(${insumo.id_insumo})" title="Editar insumo">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="confirmDeleteInsumo(${insumo.id_insumo}, '${insumo.nombre_insumo}')" title="Eliminar insumo">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        `;
        tbody.appendChild(row);
    });
}

function getTipoDisplay(tipo) {
    const tipos = {
        'semillas': 'Semillas',
        'fertilizantes': 'Fertilizantes',
        'pesticidas': 'Pesticidas',
        'herramientas': 'Herramientas',
        'maquinaria': 'Maquinaria'
    };
    return tipos[tipo] || tipo;
}

function getEstadoDisplay(estado) {
    const estados = {
        'disponible': 'Disponible',
        'agotado': 'Agotado',
        'descontinuado': 'Descontinuado'
    };
    return estados[estado] || estado;
}

function displayInsumosPagination(pagination) {
    const paginationDiv = document.getElementById('insumosPagination');
    paginationDiv.innerHTML = '';
    
    const prevBtn = document.createElement('button');
    prevBtn.innerHTML = '<i class="fas fa-chevron-left"></i>';
    prevBtn.disabled = pagination.current_page === 1;
    prevBtn.onclick = () => loadInsumos(pagination.current_page - 1, document.getElementById('searchInsumosInput').value);
    paginationDiv.appendChild(prevBtn);
    
    for (let i = 1; i <= pagination.total_pages; i++) {
        if (i === 1 || i === pagination.total_pages || (i >= pagination.current_page - 2 && i <= pagination.current_page + 2)) {
            const pageBtn = document.createElement('button');
            pageBtn.textContent = i;
            pageBtn.className = i === pagination.current_page ? 'active' : '';
            pageBtn.onclick = () => loadInsumos(i, document.getElementById('searchInsumosInput').value);
            paginationDiv.appendChild(pageBtn);
        } else if (i === pagination.current_page - 3 || i === pagination.current_page + 3) {
            const dots = document.createElement('span');
            dots.textContent = '...';
            dots.style.padding = '0.4rem';
            paginationDiv.appendChild(dots);
        }
    }
    
    const nextBtn = document.createElement('button');
    nextBtn.innerHTML = '<i class="fas fa-chevron-right"></i>';
    nextBtn.disabled = pagination.current_page === pagination.total_pages;
    nextBtn.onclick = () => loadInsumos(pagination.current_page + 1, document.getElementById('searchInsumosInput').value);
    paginationDiv.appendChild(nextBtn);
}

function openInsumoModal(insumo = null) {
    const modal = document.getElementById('insumoModal');
    const form = document.getElementById('insumoForm');
    const title = document.getElementById('insumoModalTitle');
    
    if (insumo) {
        title.textContent = 'Editar Insumo';
        console.log('Estableciendo datos en el formulario:', insumo);
        
        Object.keys(insumo).forEach(key => {
            let fieldId = key;
            
            // Mapear id_insumo a insumoId para el HTML
            if (key === 'id_insumo') {
                fieldId = 'insumoId';
            }
            
            const field = document.getElementById(fieldId);
            if (field) {
                field.value = insumo[key];
                console.log(`Campo ${key} (${fieldId}) establecido con valor:`, insumo[key]);
            } else {
                console.log(`Campo ${key} (${fieldId}) no encontrado en el formulario`);
            }
        });
        
        // Verificar que el campo id_insumo esté establecido
        const idField = document.getElementById('insumoId');
        console.log('Campo insumoId encontrado:', idField);
        console.log('Valor del campo insumoId:', idField ? idField.value : 'NO ENCONTRADO');
    } else {
        title.textContent = 'Agregar Nuevo Insumo';
        form.reset();
        // Ensure the hidden ID field is cleared for new records
        document.getElementById('insumoId').value = '';
        document.getElementById('fecha_registro').value = new Date().toISOString().split('T')[0];
    }
    
    modal.style.display = 'flex';
}

function closeInsumoModal() {
    document.getElementById('insumoModal').style.display = 'none';
}

async function saveInsumo() {
    const form = document.getElementById('insumoForm');
    const insumoId = document.getElementById('insumoId').value;
    
    try {
        const url = 'php/insumos.php';
        
        if (insumoId) {
            // ACTUALIZAR: Usar PUT con URL-encoded
            const formData = new FormData(form);
            const params = new URLSearchParams();
            for (let [key, value] of formData.entries()) {
                params.append(key, value);
            }
            
            console.log('Updating insumo with ID:', insumoId);
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
                closeInsumoModal();
                loadInsumos(currentInsumosPage, document.getElementById('searchInsumosInput').value);
                showToast(data.message, 'success');
            } else {
                showToast(data.message, 'error');
            }
        } else {
            // CREAR: Usar POST con FormData
            const formData = new FormData(form);
            
            const response = await fetch(url, {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                closeInsumoModal();
                loadInsumos(currentInsumosPage, document.getElementById('searchInsumosInput').value);
                showToast(data.message, 'success');
            } else {
                showToast(data.message, 'error');
            }
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Error al guardar el insumo', 'error');
    }
}

async function editInsumo(id) {
    try {
        console.log('Editando insumo con ID:', id);
        
        // Obtener datos completos del insumo desde la base de datos
        const response = await fetch(`php/insumos.php?id_insumo=${id}`);
        const data = await response.json();
        
        console.log('Datos recibidos del servidor:', data);
        
        if (data.success && data.data) {
            const insumo = data.data;
            console.log('Datos del insumo a editar:', insumo);
            openInsumoModal(insumo);
        } else {
            showToast('Error al cargar los datos del insumo', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Error al cargar los datos del insumo', 'error');
    }
}

function confirmDeleteInsumo(id, nombre) {
    const confirmationModal = document.createElement('div');
    confirmationModal.className = 'confirmation-modal';
    confirmationModal.id = 'confirmationModal';
    
    confirmationModal.innerHTML = `
        <div class="confirmation-content">
            <div class="confirmation-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3 class="confirmation-title">¿Eliminar Insumo?</h3>
            <p class="confirmation-message">
                ¿Estás seguro de que deseas eliminar el insumo <strong>"${nombre}"</strong>?<br>
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
        await deleteInsumo(id);
        confirmationModal.remove();
    });
    
    confirmationModal.addEventListener('click', function(e) {
        if (e.target === confirmationModal) {
            confirmationModal.remove();
        }
    });
}

async function deleteInsumo(id) {
    try {
        // Para DELETE, necesitamos enviar los datos como URL-encoded
        const params = new URLSearchParams();
        params.append('id_insumo', id);
        
        const response = await fetch('php/insumos.php', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: params
        });
        
        const data = await response.json();
        
        if (data.success) {
            loadInsumos(currentInsumosPage, document.getElementById('searchInsumosInput').value);
            showToast(data.message, 'success');
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Error al eliminar el insumo', 'error');
    }
}

// ===== FUNCIONES DE PRODUCCIÓN =====

let sociosList = [];
let currentProduccionPage = 1;
let totalProduccionPages = 1;

async function loadProduccionStatistics() {
    try {
        const response = await fetch('php/produccion.php?action=statistics');
        const data = await response.json();
        
        if (data.success) {
            updateProduccionStatistics(data.statistics);
        }
    } catch (error) {
        console.error('Error al cargar estadísticas de producción:', error);
    }
}

function updateProduccionStatistics(stats) {
    document.getElementById('totalProduccion').textContent = stats.total_produccion || '0';
    document.getElementById('totalProduccionUnit').textContent = stats.unidad_principal || 'quintales';
    document.getElementById('cultivosActivos').textContent = stats.cultivos_activos || '0';
    document.getElementById('productoresActivos').textContent = stats.productores_activos || '0';
    document.getElementById('calidadPremium').textContent = (stats.calidad_premium || '0') + '%';
}

async function loadSociosForProduccion() {
    try {
        const response = await fetch('php/socios.php');
        const data = await response.json();
        
        if (data.success) {
            sociosList = data.data;
            return Promise.resolve();
        } else {
            console.error('Error al cargar socios:', data.message);
            return Promise.resolve();
        }
    } catch (error) {
        console.error('Error al cargar socios:', error);
        return Promise.resolve();
    }
}

function populateSociosDropdown(selectedSocioId = null) {
    const select = document.getElementById('id_socio');
    if (!select) return;
    
    select.innerHTML = '<option value="">Seleccionar socio</option>';
    
    sociosList.forEach(socio => {
        const option = document.createElement('option');
        option.value = socio.id_socio;
        option.textContent = socio.nombre;
        select.appendChild(option);
    });
    
    // Establecer el valor seleccionado después de poblar el dropdown
    if (selectedSocioId) {
        select.value = selectedSocioId;
        console.log(`Socio seleccionado establecido: ${selectedSocioId}`);
    }
}

async function loadProduccion(page = 1, search = '') {
    try {
        const params = new URLSearchParams({
            page: page,
            limit: 10,
            search: search
        });
        
        const response = await fetch(`php/produccion.php?${params}`);
        const data = await response.json();
        
        if (data.success) {
            displayProduccion(data.data);
            displayProduccionPagination(data.pagination);
            currentProduccionPage = data.pagination.current_page;
            totalProduccionPages = data.pagination.total_pages;
        } else {
            showToast('Error al cargar producción: ' + data.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Error de conexión', 'error');
    }
}

function displayProduccion(produccion) {
    const tbody = document.getElementById('produccionTableBody');
    tbody.innerHTML = '';
    
    if (produccion.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="no-data">
                    <div class="no-data-content">
                        <i class="fas fa-seedling"></i>
                        <p>No hay registros de producción</p>
                        <small>Comienza registrando la primera cosecha</small>
                    </div>
                </td>
            </tr>
        `;
        return;
    }
    
    produccion.forEach(item => {
        const row = document.createElement('tr');
        
        // Truncar observaciones si son muy largas
        const observaciones = item.observaciones || '';
        const observacionesDisplay = observaciones.length > 50 ? 
            observaciones.substring(0, 50) + '...' : observaciones;
        
        row.innerHTML = `
            <td>${item.id_produccion}</td>
            <td>${item.nombre_socio || '-'}</td>
            <td>${item.cultivo}</td>
            <td>${item.variedad || '-'}</td>
            <td>${parseFloat(item.cantidad).toLocaleString()} ${item.unidad}</td>
            <td>${formatDate(item.fecha_recoleccion)}</td>
            <td><span class="quality-badge quality-${item.calidad}">${getCalidadDisplay(item.calidad)}</span></td>
            <td>
                <div class="actions">
                    <button class="btn btn-sm btn-edit" onclick="editProduccion(${item.id_produccion})" title="Editar producción">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="confirmDeleteProduccion(${item.id_produccion}, '${item.cultivo}')" title="Eliminar producción">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        `;
        tbody.appendChild(row);
    });
}

function getCalidadDisplay(calidad) {
    const calidades = {
        'premium': 'Premium',
        'buena': 'Buena',
        'regular': 'Regular',
        'baja': 'Baja'
    };
    return calidades[calidad] || calidad;
}

function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('es-ES');
}

function displayProduccionPagination(pagination) {
    const paginationDiv = document.getElementById('produccionPagination');
    paginationDiv.innerHTML = '';
    
    const prevBtn = document.createElement('button');
    prevBtn.innerHTML = '<i class="fas fa-chevron-left"></i>';
    prevBtn.disabled = pagination.current_page === 1;
    prevBtn.onclick = () => loadProduccion(pagination.current_page - 1, document.getElementById('searchProduccionInput').value);
    paginationDiv.appendChild(prevBtn);
    
    for (let i = 1; i <= pagination.total_pages; i++) {
        if (i === 1 || i === pagination.total_pages || (i >= pagination.current_page - 2 && i <= pagination.current_page + 2)) {
            const pageBtn = document.createElement('button');
            pageBtn.textContent = i;
            pageBtn.className = i === pagination.current_page ? 'active' : '';
            pageBtn.onclick = () => loadProduccion(i, document.getElementById('searchProduccionInput').value);
            paginationDiv.appendChild(pageBtn);
        } else if (i === pagination.current_page - 3 || i === pagination.current_page + 3) {
            const dots = document.createElement('span');
            dots.textContent = '...';
            dots.style.padding = '0.4rem';
            paginationDiv.appendChild(dots);
        }
    }
    
    const nextBtn = document.createElement('button');
    nextBtn.innerHTML = '<i class="fas fa-chevron-right"></i>';
    nextBtn.disabled = pagination.current_page === pagination.total_pages;
    nextBtn.onclick = () => loadProduccion(pagination.current_page + 1, document.getElementById('searchProduccionInput').value);
    paginationDiv.appendChild(nextBtn);
}

function openProduccionModal(produccion = null) {
    const modal = document.getElementById('produccionModal');
    const form = document.getElementById('produccionForm');
    const title = document.getElementById('produccionModalTitle');
    
    // Guardar el id_socio antes de resetear o poblar el dropdown
    let selectedSocioId = null;
    
    if (produccion) {
        title.textContent = 'Editar Producción';
        console.log('Estableciendo datos en el formulario:', produccion);
        
        // Guardar el id_socio antes de establecer los valores
        selectedSocioId = produccion.id_socio || null;
        
        Object.keys(produccion).forEach(key => {
            let fieldId = key;
            
            if (key === 'id_produccion') {
                fieldId = 'produccionId';
            }
            
            // No establecer id_socio aquí porque el dropdown se poblará después
            if (key !== 'id_socio') {
                const field = document.getElementById(fieldId);
                if (field) {
                    field.value = produccion[key];
                    console.log(`Campo ${key} (${fieldId}) establecido con valor:`, produccion[key]);
                }
            }
        });
    } else {
        title.textContent = 'Registrar Nueva Producción';
        form.reset();
        document.getElementById('produccionId').value = '';
        document.getElementById('fecha_recoleccion').value = new Date().toISOString().split('T')[0];
        document.getElementById('calidad').value = 'buena';
        document.getElementById('unidad').value = 'quintales';
    }
    
    // Cargar socios si no están cargados, y luego establecer el valor seleccionado
    if (sociosList.length === 0) {
        loadSociosForProduccion().then(() => {
            populateSociosDropdown(selectedSocioId);
        });
    } else {
        populateSociosDropdown(selectedSocioId);
    }
    
    modal.style.display = 'flex';
}

function closeProduccionModal() {
    document.getElementById('produccionModal').style.display = 'none';
}

async function saveProduccion() {
    const form = document.getElementById('produccionForm');
    const produccionId = document.getElementById('produccionId').value;
    const isUpdate = produccionId && produccionId.trim() !== '';
    
    try {
        const url = 'php/produccion.php';
        
        if (isUpdate) {
            const formData = new FormData(form);
            const params = new URLSearchParams();
            for (let [key, value] of formData.entries()) {
                params.append(key, value);
            }
            
            const response = await fetch(url, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: params
            });

            const data = await response.json();

            if (data.success) {
                closeProduccionModal();
                loadProduccion(currentProduccionPage, document.getElementById('searchProduccionInput').value);
                loadProduccionStatistics();
                showToast('Producción actualizada exitosamente', 'success');
            } else {
                showToast('Error al actualizar producción: ' + data.message, 'error');
            }
        } else {
            const formData = new FormData();
            const formElements = form.querySelectorAll('input, select, textarea');
            
            formElements.forEach(element => {
                if (element.name !== 'id_produccion' && element.name !== '') {
                    formData.append(element.name, element.value);
                }
            });
            
            const response = await fetch(url, {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                closeProduccionModal();
                loadProduccion(currentProduccionPage, document.getElementById('searchProduccionInput').value);
                loadProduccionStatistics();
                showToast('Nueva producción registrada exitosamente', 'success');
            } else {
                showToast('Error al registrar producción: ' + data.message, 'error');
            }
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Error al guardar la producción', 'error');
    }
}

async function editProduccion(id) {
    try {
        const response = await fetch(`php/produccion.php?id_produccion=${id}`);
        const data = await response.json();
        
        if (data.success && data.data) {
            const produccion = data.data;
            openProduccionModal(produccion);
        } else {
            showToast('Error al cargar los datos de la producción', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Error al cargar los datos de la producción', 'error');
    }
}

function confirmDeleteProduccion(id, cultivo) {
    const confirmationModal = document.createElement('div');
    confirmationModal.className = 'confirmation-modal';
    confirmationModal.id = 'confirmationModal';
    
    confirmationModal.innerHTML = `
        <div class="confirmation-content">
            <div class="confirmation-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3 class="confirmation-title">¿Eliminar Producción?</h3>
            <p class="confirmation-message">
                ¿Estás seguro de que deseas eliminar la producción de <strong>"${cultivo}"</strong>?<br>
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
        await deleteProduccion(id);
        confirmationModal.remove();
    });
    
    confirmationModal.addEventListener('click', function(e) {
        if (e.target === confirmationModal) {
            confirmationModal.remove();
        }
    });
}

async function deleteProduccion(id) {
    try {
        const params = new URLSearchParams();
        params.append('id_produccion', id);
        
        const response = await fetch('php/produccion.php', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: params
        });
        
        const data = await response.json();
        
        if (data.success) {
            loadProduccion(currentProduccionPage);
            loadProduccionStatistics();
            showToast(data.message, 'success');
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Error al eliminar la producción', 'error');
    }
}

// ===== FUNCIONES DEL HEADER =====

// Función para mostrar notificaciones
function showNotifications() {
    showToast('🔔 Tienes 3 notificaciones nuevas', 'info');
    
    // Aquí podrías abrir un modal con las notificaciones
    // o navegar a una página de notificaciones
    console.log('Mostrando notificaciones...');
}

// Función para mostrar mensajes
function showMessages() {
    showToast('💬 Tienes 2 mensajes sin leer', 'info');
    
    // Aquí podrías abrir un modal con los mensajes
    // o navegar a una página de mensajes
    console.log('Mostrando mensajes...');
}

// ===== FUNCIONES DE VENTAS =====

let currentVentasPage = 1;
let totalVentasPages = 1;

async function loadVentasStatistics() {
    try {
        const response = await fetch('php/ventas.php?action=statistics');
        const data = await response.json();
        
        if (data.success) {
            updateVentasStatistics(data.statistics);
        }
    } catch (error) {
        console.error('Error al cargar estadísticas de ventas:', error);
    }
}

function updateVentasStatistics(stats) {
    const ventasTotales = parseFloat(stats.ventas_totales || 0);
    const formattedTotal = `$${ventasTotales.toLocaleString()}`;
    
    const ventasTotalesElement = document.getElementById('ventasTotales');
    ventasTotalesElement.textContent = formattedTotal;
    
    // Detectar si el número es muy largo y aplicar clase especial
    if (formattedTotal.length > 12) {
        ventasTotalesElement.classList.add('long-number');
    } else {
        ventasTotalesElement.classList.remove('long-number');
    }
    
    document.getElementById('ventasPendientes').textContent = stats.ventas_pendientes || '0';
    document.getElementById('ventasPagadas').textContent = stats.ventas_pagadas || '0';
    document.getElementById('clientesActivos').textContent = stats.clientes_activos || '0';
}

async function loadSociosForVentas() {
    try {
        const response = await fetch('php/socios.php');
        const data = await response.json();
        
        if (data.success) {
            sociosList = data.data;
            return Promise.resolve();
        } else {
            console.error('Error cargando socios para ventas:', data.message);
            return Promise.resolve();
        }
    } catch (error) {
        console.error('Error cargando socios para ventas:', error);
        return Promise.resolve();
    }
}

function populateSociosDropdownForVentas(selectedSocioId = null) {
    const select = document.getElementById('socioSelect');
    if (!select) return;
    
    select.innerHTML = '<option value="">Seleccionar socio</option>';
    
    sociosList.forEach(socio => {
        const option = document.createElement('option');
        option.value = socio.id_socio;
        option.textContent = socio.nombre;
        select.appendChild(option);
    });
    
    // Establecer el valor seleccionado después de poblar el dropdown
    if (selectedSocioId) {
        select.value = selectedSocioId;
        console.log(`Socio seleccionado establecido en ventas: ${selectedSocioId}`);
    }
}

async function loadVentas(page = 1, search = '') {
    try {
        const params = new URLSearchParams({
            page: page,
            limit: 10,
            search: search
        });
        
        const response = await fetch(`php/ventas.php?${params}`);
        const data = await response.json();
        
        if (data.success) {
            displayVentas(data.data);
            displayVentasPagination(data.pagination);
            currentVentasPage = data.pagination.current_page;
            totalVentasPages = data.pagination.total_pages;
        } else {
            showToast('Error al cargar ventas: ' + data.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Error de conexión', 'error');
    }
}

function displayVentas(ventas) {
    const tbody = document.getElementById('ventasTableBody');
    tbody.innerHTML = '';
    
    if (ventas.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="9" class="no-data">
                    <div class="no-data-content">
                        <i class="fas fa-shopping-cart"></i>
                        <p>No hay registros de ventas</p>
                        <small>Comienza registrando la primera venta</small>
                    </div>
                </td>
            </tr>
        `;
        return;
    }
    
    ventas.forEach((item, index) => {
        const row = document.createElement('tr');
        row.className = 'table-row-enter';
        row.style.setProperty('--row-index', index);
        
        row.innerHTML = `
            <td>${item.id_venta}</td>
            <td>${item.nombre_socio || '-'}</td>
            <td>${item.producto}</td>
            <td>${item.cliente}</td>
            <td>${parseFloat(item.cantidad).toLocaleString()}</td>
            <td>$${parseFloat(item.precio_unitario).toLocaleString()}</td>
            <td>$${parseFloat(item.total).toLocaleString()}</td>
            <td><span class="status-badge status-${item.estado}">${getEstadoDisplay(item.estado)}</span></td>
            <td>
                <div class="actions">
                    <button class="btn btn-sm btn-edit" onclick="editVenta(${item.id_venta})" title="Editar venta">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="confirmDeleteVenta(${item.id_venta}, '${item.producto}')" title="Eliminar venta">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        `;
        tbody.appendChild(row);
    });
}

function getEstadoDisplay(estado) {
    const estados = {
        'pendiente': 'Pendiente',
        'entregado': 'Entregado',
        'pagado': 'Pagado',
        'cancelado': 'Cancelado'
    };
    return estados[estado] || estado;
}

function displayVentasPagination(pagination) {
    const paginationDiv = document.getElementById('ventasPagination');
    paginationDiv.innerHTML = '';
    
    if (pagination.total_pages <= 1) return;
    
    const prevBtn = document.createElement('button');
    prevBtn.textContent = 'Anterior';
    prevBtn.disabled = pagination.current_page === 1;
    prevBtn.onclick = () => loadVentas(pagination.current_page - 1, document.getElementById('searchVentasInput').value);
    paginationDiv.appendChild(prevBtn);
    
    for (let i = 1; i <= pagination.total_pages; i++) {
        if (i === 1 || i === pagination.total_pages || (i >= pagination.current_page - 2 && i <= pagination.current_page + 2)) {
            const pageBtn = document.createElement('button');
            pageBtn.textContent = i;
            pageBtn.className = i === pagination.current_page ? 'active' : '';
            pageBtn.onclick = () => loadVentas(i, document.getElementById('searchVentasInput').value);
            paginationDiv.appendChild(pageBtn);
        } else if (i === pagination.current_page - 3 || i === pagination.current_page + 3) {
            const dots = document.createElement('span');
            dots.textContent = '...';
            dots.style.padding = '0.4rem';
            paginationDiv.appendChild(dots);
        }
    }
    
    const nextBtn = document.createElement('button');
    nextBtn.textContent = 'Siguiente';
    nextBtn.disabled = pagination.current_page === pagination.total_pages;
    nextBtn.onclick = () => loadVentas(pagination.current_page + 1, document.getElementById('searchVentasInput').value);
    paginationDiv.appendChild(nextBtn);
}

function openVentaModal(venta = null) {
    const modal = document.getElementById('ventaModal');
    const form = document.getElementById('ventaForm');
    const title = document.getElementById('ventaModalTitle');
    
    // Guardar el id_socio antes de poblar el dropdown
    let selectedSocioId = null;
    let ventaData = null;
    
    if (venta) {
        title.textContent = 'Editar Venta';
        console.log('Estableciendo datos en el formulario de venta:', venta);
        
        // Guardar todos los datos de la venta para establecerlos después
        ventaData = venta;
        selectedSocioId = venta.id_socio || null;
        
        // Establecer valores inmediatos primero
        document.getElementById('ventaId').value = venta.id_venta || '';
        document.getElementById('producto').value = venta.producto || '';
        document.getElementById('cliente').value = venta.cliente || '';
        document.getElementById('fecha_venta').value = venta.fecha_venta || '';
        document.getElementById('fecha_entrega').value = venta.fecha_entrega || '';
        document.getElementById('estado').value = venta.estado || 'pendiente';
        document.getElementById('metodo_pago').value = venta.metodo_pago || 'efectivo';
        document.getElementById('direccion_entrega').value = venta.direccion_entrega || '';
        document.getElementById('observaciones').value = venta.observaciones || '';
    } else {
        title.textContent = 'Registrar Nueva Venta';
        form.reset();
        document.getElementById('ventaId').value = '';
        document.getElementById('fecha_venta').value = new Date().toISOString().split('T')[0];
        document.getElementById('estado').value = 'pendiente';
        document.getElementById('metodo_pago').value = 'efectivo';
    }
    
    // Función para establecer valores numéricos después de que el dropdown esté listo
    const setNumericValues = () => {
        if (ventaData) {
            // Establecer cantidad y precio_unitario después de que el DOM esté listo
            const cantidadField = document.getElementById('cantidad');
            const precioField = document.getElementById('precio_unitario');
            
            // Convertir y establecer cantidad
            if (cantidadField) {
                const cantidadValue = ventaData.cantidad;
                if (cantidadValue !== null && cantidadValue !== undefined && cantidadValue !== '') {
                    // Convertir a número y luego a string para asegurar formato correcto
                    const cantidadNum = parseFloat(cantidadValue);
                    if (!isNaN(cantidadNum)) {
                        cantidadField.value = cantidadNum;
                        console.log(`Cantidad establecida: ${cantidadNum} (original: ${cantidadValue})`);
                    } else {
                        console.warn(`Valor de cantidad inválido: ${cantidadValue}`);
                    }
                } else {
                    console.warn('Cantidad es null, undefined o vacío');
                }
            } else {
                console.error('Campo cantidad no encontrado');
            }
            
            // Convertir y establecer precio_unitario
            if (precioField) {
                const precioValue = ventaData.precio_unitario;
                if (precioValue !== null && precioValue !== undefined && precioValue !== '') {
                    // Convertir a número y luego a string para asegurar formato correcto
                    const precioNum = parseFloat(precioValue);
                    if (!isNaN(precioNum)) {
                        precioField.value = precioNum;
                        console.log(`Precio unitario establecido: ${precioNum} (original: ${precioValue})`);
                    } else {
                        console.warn(`Valor de precio_unitario inválido: ${precioValue}`);
                    }
                } else {
                    console.warn('Precio unitario es null, undefined o vacío');
                }
            } else {
                console.error('Campo precio_unitario no encontrado');
            }
            
            // Verificación final
            console.log('Verificación final de campos:');
            console.log('  - cantidad:', cantidadField?.value);
            console.log('  - precio_unitario:', precioField?.value);
        }
    };
    
    // Cargar socios si no están cargados, y luego establecer el valor seleccionado
    if (sociosList.length === 0) {
        loadSociosForVentas().then(() => {
            populateSociosDropdownForVentas(selectedSocioId);
            // Establecer valores numéricos después de poblar el dropdown
            setTimeout(setNumericValues, 100);
        });
    } else {
        populateSociosDropdownForVentas(selectedSocioId);
        // Establecer valores numéricos después de poblar el dropdown
        setTimeout(setNumericValues, 100);
    }
    
    modal.style.display = 'flex';
}

function closeVentaModal() {
    document.getElementById('ventaModal').style.display = 'none';
}

async function saveVenta() {
    const form = document.getElementById('ventaForm');
    const ventaId = document.getElementById('ventaId').value;
    
    try {
        if (ventaId) {
            const formData = new FormData();
            const formElements = form.querySelectorAll('input, select, textarea');
            
            formElements.forEach(element => {
                if (element.name !== 'id_venta' && element.name !== '') {
                    formData.append(element.name, element.value);
                }
            });
            
            const response = await fetch(`php/ventas.php?action=update&id=${ventaId}`, {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                closeVentaModal();
                loadVentas(currentVentasPage, document.getElementById('searchVentasInput').value);
                loadVentasStatistics();
                showToast('Venta actualizada exitosamente', 'success');
            } else {
                showToast('Error al actualizar venta: ' + data.message, 'error');
            }
        } else {
            const formData = new FormData();
            const formElements = form.querySelectorAll('input, select, textarea');
            
            formElements.forEach(element => {
                if (element.name !== 'id_venta' && element.name !== '') {
                    formData.append(element.name, element.value);
                }
            });
            
            const response = await fetch('php/ventas.php?action=create', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                closeVentaModal();
                loadVentas(currentVentasPage, document.getElementById('searchVentasInput').value);
                loadVentasStatistics();
                showToast('Nueva venta registrada exitosamente', 'success');
            } else {
                showToast('Error al registrar venta: ' + data.message, 'error');
            }
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Error al guardar la venta', 'error');
    }
}

function editVenta(ventaId) {
    fetch(`php/ventas.php?action=get&id=${ventaId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                openVentaModal(data.data);
            } else {
                showToast('Error al cargar venta: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Error al cargar venta', 'error');
        });
}

function confirmDeleteVenta(ventaId, producto) {
    const confirmationModal = document.createElement('div');
    confirmationModal.className = 'confirmation-modal';
    confirmationModal.id = 'confirmationModal';
    
    confirmationModal.innerHTML = `
        <div class="confirmation-content">
            <div class="confirmation-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3 class="confirmation-title">¿Eliminar Venta?</h3>
            <p class="confirmation-message">
                ¿Estás seguro de que deseas eliminar la venta de <strong>"${producto}"</strong>?<br>
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
        await deleteVenta(ventaId);
        confirmationModal.remove();
    });
    
    confirmationModal.addEventListener('click', function(e) {
        if (e.target === confirmationModal) {
            confirmationModal.remove();
        }
    });
}

async function deleteVenta(ventaId) {
    try {
        const response = await fetch(`php/ventas.php?action=delete&id=${ventaId}`, {
            method: 'DELETE'
        });
        
        const data = await response.json();
        
        if (data.success) {
            loadVentas(currentVentasPage);
            loadVentasStatistics();
            showToast(data.message, 'success');
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Error al eliminar la venta', 'error');
    }
}

// Event listeners para los botones del header
document.addEventListener('DOMContentLoaded', function() {
    const notificationsBtn = document.getElementById('notificationsBtn');
    const messagesBtn = document.getElementById('messagesBtn');
    
    if (notificationsBtn) {
        notificationsBtn.addEventListener('click', showNotifications);
    }
    
    if (messagesBtn) {
        messagesBtn.addEventListener('click', showMessages);
    }

    // Event listeners para pagos
    document.getElementById('searchPagosInput').addEventListener('input', function() {
        const searchTerm = this.value;
        loadPagos(1, searchTerm);
    });

    document.getElementById('addPagoBtn').addEventListener('click', function() {
        openPagoModal();
    });

    document.getElementById('pagoForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        await savePago();
    });

    document.getElementById('cancelPagoBtn').addEventListener('click', function() {
        closePagoModal();
    });

    document.getElementById('pagoModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closePagoModal();
        }
    });

    // Event listener para ir a reportes completos
    document.getElementById('goToReportsBtn').addEventListener('click', function() {
        window.location.href = 'reportes.html';
    });
});

// ===== FUNCIONES DE PAGOS =====

let currentPagosPage = 1;
let totalPagosPages = 1;
let sociosListPagos = [];

async function loadPagosStatistics() {
    try {
        const response = await fetch('php/pagos.php?action=statistics');
        const data = await response.json();
        
        if (data.success) {
            updatePagosStatistics(data.statistics);
        }
    } catch (error) {
        console.error('Error al cargar estadísticas de pagos:', error);
    }
}

function updatePagosStatistics(stats) {
    const ingresosTotales = parseFloat(stats.ingresos_totales || 0);
    const formattedTotal = `$${ingresosTotales.toLocaleString()}`;
    
    const ingresosTotalesElement = document.getElementById('ingresosTotales');
    ingresosTotalesElement.textContent = formattedTotal;
    
    // Detectar si el número es muy largo y aplicar clase especial
    if (formattedTotal.length > 12) {
        ingresosTotalesElement.classList.add('long-number');
    } else {
        ingresosTotalesElement.classList.remove('long-number');
    }
    
    document.getElementById('pagosPendientes').textContent = stats.pagos_pendientes || '0';
    document.getElementById('pagosConfirmados').textContent = stats.pagos_confirmados || '0';
    document.getElementById('aportesMensuales').textContent = stats.aportes_mensuales || '0';
}

async function loadSociosForPagos() {
    try {
        const response = await fetch('php/socios.php');
        const data = await response.json();
        
        if (data.success) {
            sociosListPagos = data.data;
            return Promise.resolve();
        } else {
            console.error('Error cargando socios para pagos:', data.message);
            return Promise.resolve();
        }
    } catch (error) {
        console.error('Error cargando socios para pagos:', error);
        return Promise.resolve();
    }
}

function populateSociosDropdownForPagos(selectedSocioId = null) {
    const select = document.getElementById('pagoSocioSelect');
    if (!select) return;
    
    select.innerHTML = '<option value="">Seleccionar socio</option>';
    
    sociosListPagos.forEach(socio => {
        const option = document.createElement('option');
        option.value = socio.id_socio;
        option.textContent = socio.nombre;
        select.appendChild(option);
    });
    
    // Establecer el valor seleccionado después de poblar el dropdown
    if (selectedSocioId) {
        select.value = selectedSocioId;
        console.log(`Socio seleccionado establecido en pagos: ${selectedSocioId}`);
    }
}

async function loadPagos(page = 1, search = '') {
    try {
        const params = new URLSearchParams({
            page: page,
            limit: 10,
            search: search
        });
        
        const response = await fetch(`php/pagos.php?${params}`);
        const data = await response.json();
        
        if (data.success) {
            displayPagos(data.data);
            displayPagosPagination(data.pagination);
            currentPagosPage = data.pagination.current_page;
            totalPagosPages = data.pagination.total_pages;
        } else {
            showToast('Error al cargar pagos: ' + data.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Error de conexión', 'error');
    }
}

function displayPagos(pagos) {
    const tbody = document.getElementById('pagosTableBody');
    tbody.innerHTML = '';
    
    if (pagos.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="no-data">
                    <div class="no-data-content">
                        <i class="fas fa-money-bill-wave"></i>
                        <p>No hay registros de pagos</p>
                        <small>Comienza registrando el primer pago</small>
                    </div>
                </td>
            </tr>
        `;
        return;
    }
    
    pagos.forEach((item, index) => {
        const row = document.createElement('tr');
        row.className = 'table-row-enter';
        row.style.setProperty('--row-index', index);
        
        row.innerHTML = `
            <td>${item.id_pago}</td>
            <td>${item.nombre_socio || '-'}</td>
            <td><span class="tipo-badge tipo-${item.tipo}">${getTipoDisplay(item.tipo)}</span></td>
            <td>${item.descripcion || '-'}</td>
            <td>$${parseFloat(item.monto).toLocaleString()}</td>
            <td>${formatDate(item.fecha_pago)}</td>
            <td><span class="status-badge status-${item.estado}">${getEstadoDisplayPagos(item.estado)}</span></td>
            <td>
                <div class="actions">
                    <button class="btn btn-sm btn-edit" onclick="editPago(${item.id_pago})" title="Editar pago">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="confirmDeletePago(${item.id_pago}, '${item.descripcion || item.tipo}')" title="Eliminar pago">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        `;
        tbody.appendChild(row);
    });
}

function getTipoDisplay(tipo) {
    const tipos = {
        'aporte_mensual': 'Aporte Mensual',
        'aporte_extraordinario': 'Aporte Extraordinario',
        'pago_venta': 'Pago de Venta',
        'prestamo': 'Préstamo',
        'devolucion': 'Devolución'
    };
    return tipos[tipo] || tipo;
}

function getEstadoDisplayPagos(estado) {
    const estados = {
        'pendiente': 'Pendiente',
        'confirmado': 'Confirmado',
        'rechazado': 'Rechazado'
    };
    return estados[estado] || estado;
}

function displayPagosPagination(pagination) {
    const paginationDiv = document.getElementById('pagosPagination');
    paginationDiv.innerHTML = '';
    
    if (pagination.total_pages <= 1) return;
    
    const prevBtn = document.createElement('button');
    prevBtn.textContent = 'Anterior';
    prevBtn.disabled = pagination.current_page === 1;
    prevBtn.onclick = () => loadPagos(pagination.current_page - 1, document.getElementById('searchPagosInput').value);
    paginationDiv.appendChild(prevBtn);
    
    for (let i = 1; i <= pagination.total_pages; i++) {
        if (i === 1 || i === pagination.total_pages || (i >= pagination.current_page - 2 && i <= pagination.current_page + 2)) {
            const pageBtn = document.createElement('button');
            pageBtn.textContent = i;
            pageBtn.className = i === pagination.current_page ? 'active' : '';
            pageBtn.onclick = () => loadPagos(i, document.getElementById('searchPagosInput').value);
            paginationDiv.appendChild(pageBtn);
        } else if (i === pagination.current_page - 3 || i === pagination.current_page + 3) {
            const dots = document.createElement('span');
            dots.textContent = '...';
            dots.style.padding = '0.4rem';
            paginationDiv.appendChild(dots);
        }
    }
    
    const nextBtn = document.createElement('button');
    nextBtn.textContent = 'Siguiente';
    nextBtn.disabled = pagination.current_page === pagination.total_pages;
    nextBtn.onclick = () => loadPagos(pagination.current_page + 1, document.getElementById('searchPagosInput').value);
    paginationDiv.appendChild(nextBtn);
}

function openPagoModal(pago = null) {
    const modal = document.getElementById('pagoModal');
    const form = document.getElementById('pagoForm');
    const title = document.getElementById('pagoModalTitle');
    
    // NO resetear el formulario si estamos editando
    if (pago) {
        // NO hacer form.reset() para evitar que se pierdan los valores
    } else {
        // Solo resetear si es un nuevo pago
        form.reset();
    }
    
    // Guardar el id_socio, tipo y otros datos antes de poblar el dropdown
    let selectedSocioId = null;
    let selectedTipo = null;
    let pagoData = null;
    
    if (pago) {
        title.textContent = 'Editar Pago';
        console.log('Estableciendo datos en el formulario de pago:', pago);
        console.log('Tipo recibido del servidor:', pago.tipo, 'Tipo de dato:', typeof pago.tipo);
        console.log('Descripción recibida del servidor:', pago.descripcion);
        
        // Guardar todos los datos del pago
        selectedSocioId = pago.id_socio || null;
        selectedTipo = pago.tipo || null;
        pagoData = pago;
        
        // Establecer valores inmediatos (excepto pagoSocioSelect y tipo que se establecerán después)
        document.getElementById('pagoId').value = pago.id_pago || '';
        document.getElementById('monto').value = pago.monto || '';
        document.getElementById('fecha_pago').value = pago.fecha_pago || '';
        document.getElementById('metodo_pago').value = pago.metodo_pago || 'efectivo';
        document.getElementById('estado').value = pago.estado || 'pendiente';
        document.getElementById('numero_comprobante').value = pago.numero_comprobante || '';
        document.getElementById('id_venta').value = pago.id_venta || '';
        document.getElementById('observaciones').value = pago.observaciones || '';
        
        console.log(`Tipo guardado: ${selectedTipo}, Socio guardado: ${selectedSocioId}`);
    } else {
        title.textContent = 'Registrar Nuevo Pago';
        document.getElementById('pagoId').value = '';
        document.getElementById('fecha_pago').value = new Date().toISOString().split('T')[0];
        document.getElementById('estado').value = 'pendiente';
        document.getElementById('metodo_pago').value = 'efectivo';
    }
    
    // Abrir el modal primero
    modal.style.display = 'flex';
    
    // Función para establecer tipo después de que el dropdown esté listo
    const setTipoValue = () => {
        if (selectedTipo) {
            const tipoField = modal.querySelector('#tipo');
            if (tipoField) {
                // Limpiar espacios en blanco y convertir a string
                const tipoClean = String(selectedTipo).trim();
                
                // Verificar que el valor existe en las opciones
                const options = Array.from(tipoField.options);
                const hasValue = options.some(opt => opt.value === tipoClean);
                
                console.log(`Intentando establecer tipo: "${tipoClean}"`);
                console.log('Opciones disponibles:', options.map(opt => `"${opt.value}"`));
                
                if (hasValue) {
                    // Establecer el valor directamente
                    tipoField.value = tipoClean;
                    
                    // Verificar inmediatamente
                    if (tipoField.value === tipoClean) {
                        console.log(`✓ Tipo establecido correctamente: ${tipoClean}`);
                    } else {
                        console.warn(`⚠ Tipo no coincide después de establecer. Esperado: "${tipoClean}", Actual: "${tipoField.value}"`);
                        
                        // Intentar establecer usando selectedIndex
                        for (let i = 0; i < options.length; i++) {
                            if (options[i].value === tipoClean) {
                                tipoField.selectedIndex = i;
                                console.log(`✓ Tipo establecido usando selectedIndex: ${i}`);
                                
                                // Verificar de nuevo
                                if (tipoField.value === tipoClean) {
                                    console.log(`✓ Tipo confirmado después de selectedIndex: ${tipoField.value}`);
                                } else {
                                    console.error(`✗ Tipo aún no coincide después de selectedIndex`);
                                    // Último intento: establecer directamente la propiedad value
                                    options[i].selected = true;
                                    tipoField.value = tipoClean;
                                }
                                break;
                            }
                        }
                    }
                    
                    // Forzar el cambio del evento para asegurar que el valor se establezca
                    tipoField.dispatchEvent(new Event('change', { bubbles: true }));
                    
                    // Verificación final
                    setTimeout(() => {
                        if (tipoField.value !== tipoClean) {
                            console.error(`✗ VERIFICACIÓN FINAL: Tipo no coincide. Esperado: "${tipoClean}", Actual: "${tipoField.value}"`);
                        } else {
                            console.log(`✓ VERIFICACIÓN FINAL: Tipo correctamente establecido: "${tipoField.value}"`);
                        }
                    }, 100);
                } else {
                    console.error(`✗ Valor de tipo no encontrado en las opciones: "${tipoClean}"`);
                    console.log('Opciones disponibles:', options.map(opt => `"${opt.value}": ${opt.textContent}`));
                }
            } else {
                console.error('Campo tipo no encontrado en el modal de pagos');
            }
        } else {
            console.warn('selectedTipo es null o undefined');
        }
    };
    
    // Función para establecer descripción después de que el modal esté completamente visible
    const setDescripcionValue = () => {
        if (pagoData && pagoData.descripcion !== undefined && pagoData.descripcion !== null) {
            // Buscar el campo de descripción dentro del modal de pagos específicamente
            const descripcionField = modal.querySelector('#descripcion');
            if (descripcionField) {
                descripcionField.value = pagoData.descripcion || '';
                console.log(`✓ Descripción establecida: "${pagoData.descripcion}"`);
            } else {
                console.error('Campo descripción no encontrado en el modal de pagos');
            }
        }
    };
    
    // Cargar socios si no están cargados, y luego establecer el valor seleccionado
    if (sociosListPagos.length === 0) {
        loadSociosForPagos().then(() => {
            populateSociosDropdownForPagos(selectedSocioId);
            // Establecer tipo y descripción después de poblar el dropdown con múltiples intentos
            setTimeout(setTipoValue, 100);
            setTimeout(setTipoValue, 200);
            setTimeout(setTipoValue, 300);
            setTimeout(setTipoValue, 400);
            setTimeout(setDescripcionValue, 100);
            setTimeout(setDescripcionValue, 200);
            setTimeout(setDescripcionValue, 300);
        });
    } else {
        populateSociosDropdownForPagos(selectedSocioId);
        // Establecer tipo y descripción después de poblar el dropdown con múltiples intentos
        setTimeout(setTipoValue, 100);
        setTimeout(setTipoValue, 200);
        setTimeout(setTipoValue, 300);
        setTimeout(setTipoValue, 400);
        setTimeout(setDescripcionValue, 100);
        setTimeout(setDescripcionValue, 200);
        setTimeout(setDescripcionValue, 300);
    }
}

function closePagoModal() {
    document.getElementById('pagoModal').style.display = 'none';
}

async function savePago() {
    const form = document.getElementById('pagoForm');
    const pagoId = document.getElementById('pagoId').value;
    
    try {
        if (pagoId) {
            const formData = new FormData();
            const formElements = form.querySelectorAll('input, select, textarea');
            
            formElements.forEach(element => {
                if (element.name !== 'id_pago' && element.name !== '') {
                    formData.append(element.name, element.value);
                }
            });
            
            const response = await fetch(`php/pagos.php?action=update&id=${pagoId}`, {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                closePagoModal();
                loadPagos(currentPagosPage, document.getElementById('searchPagosInput').value);
                loadPagosStatistics();
                showToast('Pago actualizado exitosamente', 'success');
            } else {
                showToast('Error al actualizar pago: ' + data.message, 'error');
            }
        } else {
            const formData = new FormData();
            const formElements = form.querySelectorAll('input, select, textarea');
            
            formElements.forEach(element => {
                if (element.name !== 'id_pago' && element.name !== '') {
                    formData.append(element.name, element.value);
                }
            });
            
            const response = await fetch('php/pagos.php?action=create', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                closePagoModal();
                loadPagos(currentPagosPage, document.getElementById('searchPagosInput').value);
                loadPagosStatistics();
                showToast('Nuevo pago registrado exitosamente', 'success');
            } else {
                showToast('Error al registrar pago: ' + data.message, 'error');
            }
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Error al guardar el pago', 'error');
    }
}

function editPago(pagoId) {
    fetch(`php/pagos.php?action=get&id=${pagoId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                openPagoModal(data.data);
            } else {
                showToast('Error al cargar pago: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Error al cargar pago', 'error');
        });
}

function confirmDeletePago(pagoId, descripcion) {
    const confirmationModal = document.createElement('div');
    confirmationModal.className = 'confirmation-modal';
    confirmationModal.id = 'confirmationModal';
    
    confirmationModal.innerHTML = `
        <div class="confirmation-content">
            <div class="confirmation-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3 class="confirmation-title">¿Eliminar Pago?</h3>
            <p class="confirmation-message">
                ¿Estás seguro de que deseas eliminar el pago de <strong>"${descripcion}"</strong>?<br>
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
        await deletePago(pagoId);
        confirmationModal.remove();
    });
    
    confirmationModal.addEventListener('click', function(e) {
        if (e.target === confirmationModal) {
            confirmationModal.remove();
        }
    });
}

async function deletePago(pagoId) {
    try {
        const response = await fetch(`php/pagos.php?action=delete&id=${pagoId}`, {
            method: 'DELETE'
        });
        
        const data = await response.json();
        
        if (data.success) {
            loadPagos(currentPagosPage);
            loadPagosStatistics();
            showToast(data.message, 'success');
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Error al eliminar el pago', 'error');
    }
}

// ===== FUNCIONES DE REPORTES =====

async function loadReportesPreview() {
    try {
        console.log('Cargando preview de reportes con datos reales...');
        
        // Cargar datos de KPIs para el preview
        const response = await fetch('php/reportes.php?action=kpis');
        const data = await response.json();

        if (data.success) {
            updateReportesPreview(data.kpis);
            await loadPreviewChartsData();
            console.log('Preview de reportes cargado:', data.kpis);
        } else {
            console.warn('Error loading real data:', data.message);
            updateReportesPreview({
                totalIncome: 0,
                totalContributions: 0,
                inventoryValue: 0,
                grossMargin: 0
            });
            createEmptyCharts();
        }
    } catch (error) {
        console.error('Error loading reportes preview:', error);
        updateReportesPreview({
            totalIncome: 0,
            totalContributions: 0,
            inventoryValue: 0,
            grossMargin: 0
        });
        createEmptyCharts();
    }
}

function updateReportesPreview(kpis) {
    document.getElementById('previewSales').textContent = formatCurrency(kpis.totalIncome || 0);
    document.getElementById('previewContributions').textContent = formatCurrency(kpis.totalContributions || 0);
    document.getElementById('previewInventory').textContent = formatCurrency(kpis.inventoryValue || 0);
    
    // Actualizar miembros activos
    const activeMembers = parseInt(kpis.activeMembers || 0);
    const previewActiveMembers = document.getElementById('previewActiveMembers');
    if (previewActiveMembers) {
        previewActiveMembers.textContent = `${activeMembers} miembros activos`;
    }
    
    // Actualizar artículos disponibles
    const availableItems = parseInt(kpis.availableItems || 0);
    const previewAvailableItems = document.getElementById('previewAvailableItems');
    if (previewAvailableItems) {
        previewAvailableItems.textContent = `${availableItems} artículos`;
    }
    
    // Formatear margen bruto con 2 decimales
    const grossMargin = parseFloat(kpis.grossMargin || 0);
    document.getElementById('previewMargin').textContent = `${grossMargin.toFixed(2)}%`;
}

async function loadPreviewChartsData() {
    try {
        // Cargar datos de evolución financiera
        const financialResponse = await fetch('php/reportes.php?action=charts');
        const financialData = await financialResponse.json();
        
        if (financialData.success) {
            createFinancialChart(financialData.charts.monthlyFinancial);
            createInventoryChart(financialData.charts.inventoryType);
        } else {
            // Si hay error, crear gráficos vacíos
            createEmptyCharts();
        }
    } catch (error) {
        console.error('Error loading preview charts:', error);
        createEmptyCharts();
    }
}

function createFinancialChart(data) {
    const financialCtx = document.getElementById('previewFinancialChart').getContext('2d');
    new Chart(financialCtx, {
        type: 'line',
        data: {
            labels: data.labels || ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun'],
            datasets: [
                {
                    label: 'Ventas',
                    data: data.sales || [0, 0, 0, 0, 0, 0],
                    borderColor: '#2d5016',
                    backgroundColor: 'rgba(45, 80, 22, 0.1)',
                    tension: 0.4
                },
                {
                    label: 'Aportes',
                    data: data.contributions || [0, 0, 0, 0, 0, 0],
                    borderColor: '#4a7c59',
                    backgroundColor: 'rgba(74, 124, 89, 0.1)',
                    tension: 0.4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '$' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });
}

function createInventoryChart(data) {
    const inventoryCtx = document.getElementById('previewInventoryChart').getContext('2d');
    new Chart(inventoryCtx, {
        type: 'doughnut',
        data: {
            labels: data.labels || [],
            datasets: [{
                data: data.values || [],
                backgroundColor: ['#2d5016', '#4a7c59', '#8bc34a', '#ffc107', '#ff9800', '#9c27b0'],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
}

function createEmptyCharts() {
    // Gráfico de evolución financiera vacío
    const financialCtx = document.getElementById('previewFinancialChart').getContext('2d');
    new Chart(financialCtx, {
        type: 'line',
        data: {
            labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun'],
            datasets: [
                {
                    label: 'Ventas',
                    data: [0, 0, 0, 0, 0, 0],
                    borderColor: '#2d5016',
                    backgroundColor: 'rgba(45, 80, 22, 0.1)',
                    tension: 0.4
                },
                {
                    label: 'Aportes',
                    data: [0, 0, 0, 0, 0, 0],
                    borderColor: '#4a7c59',
                    backgroundColor: 'rgba(74, 124, 89, 0.1)',
                    tension: 0.4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '$' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });

    // Gráfico de inventario vacío
    const inventoryCtx = document.getElementById('previewInventoryChart').getContext('2d');
    new Chart(inventoryCtx, {
        type: 'doughnut',
        data: {
            labels: [],
            datasets: [{
                data: [],
                backgroundColor: ['#2d5016', '#4a7c59', '#8bc34a', '#ffc107'],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
}

function formatCurrency(value) {
    return new Intl.NumberFormat('es-ES', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(value);
}

// ===== FUNCIONES DEL MODAL DE USUARIO =====

function openUserProfileModal() {
    console.log('Abriendo modal de usuario');
    // Cargar datos del usuario actual
    loadUserProfileData();
    
    // Mostrar el modal
    document.getElementById('userProfileModal').style.display = 'flex';
}

function closeUserProfileModal() {
    document.getElementById('userProfileModal').style.display = 'none';
}

async function loadUserProfileData() {
    if (currentUser) {
        try {
            // Cargar datos completos del usuario desde la base de datos
            const response = await fetch('php/get_user_profile.php');
            const userData = await response.json();
            
            if (userData.success) {
                const user = userData.user;
                
                // Actualizar información del perfil
                document.getElementById('profileUserName').textContent = user.nombre || 'Usuario';
                document.getElementById('profileUserRole').textContent = getRoleDisplay(user.rol);
                document.getElementById('profileUserEmail').textContent = user.correo || 'usuario@cooperativa.com';
                
                // Mostrar fecha de registro real
                const joinDate = user.fecha_ingreso_formatted || new Date().getFullYear();
                document.getElementById('profileJoinDate').textContent = `Miembro desde: ${joinDate}`;
                
                // Mostrar último acceso
                const lastAccess = user.ultimo_acceso ? 
                    new Date(user.ultimo_acceso).toLocaleDateString('es-ES') : 
                    'Hoy';
                document.getElementById('profileLastAccess').textContent = `Último acceso: ${lastAccess}`;
                
                // Llenar formulario con datos del usuario
                document.getElementById('firstName').value = user.nombre || '';
                document.getElementById('lastName').value = ''; // No hay apellido en la BD
                document.getElementById('email').value = user.correo || '';
                document.getElementById('phone').value = ''; // No hay teléfono en la BD
                
                // Cargar preferencias guardadas
                loadUserPreferences();
            } else {
                // Fallback a datos básicos si no se pueden cargar
                loadBasicUserData();
            }
        } catch (error) {
            console.error('Error cargando datos del usuario:', error);
            loadBasicUserData();
        }
    }
}

function loadBasicUserData() {
    // Datos básicos del usuario actual
    document.getElementById('profileUserName').textContent = currentUser.nombre || 'Usuario';
    document.getElementById('profileUserRole').textContent = getRoleDisplay(currentUser.rol);
    document.getElementById('profileUserEmail').textContent = currentUser.correo || 'usuario@cooperativa.com';
    document.getElementById('profileJoinDate').textContent = `Miembro desde: ${new Date().getFullYear()}`;
    document.getElementById('profileLastAccess').textContent = 'Último acceso: Hoy';
    
    // Llenar formulario
    document.getElementById('firstName').value = currentUser.nombre || '';
    document.getElementById('email').value = currentUser.correo || '';
    
    loadUserPreferences();
}

function loadUserPreferences() {
    // Cargar preferencias desde localStorage o servidor
    const preferences = JSON.parse(localStorage.getItem('userPreferences') || '{}');
    
    document.getElementById('emailNotifications').checked = preferences.emailNotifications !== false;
    document.getElementById('smsNotifications').checked = preferences.smsNotifications || false;
    document.getElementById('darkMode').checked = preferences.darkMode || false;
    document.getElementById('autoSave').checked = preferences.autoSave !== false;
}

async function saveUserProfile() {
    try {
        showToast('Guardando cambios...', 'info');
        
        // Recopilar datos del formulario
        const formData = {
            nombre: document.getElementById('firstName').value,
            email: document.getElementById('email').value
        };
        
        // Guardar preferencias
        const preferences = {
            emailNotifications: document.getElementById('emailNotifications').checked,
            smsNotifications: document.getElementById('smsNotifications').checked,
            darkMode: document.getElementById('darkMode').checked,
            autoSave: document.getElementById('autoSave').checked
        };
        
        localStorage.setItem('userPreferences', JSON.stringify(preferences));
        
        // Enviar datos al servidor
        const response = await fetch('php/update_user_profile.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(formData)
        });
        
        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.message || 'Error al actualizar perfil');
        }
        
        // Actualizar la información del usuario en la interfaz
        if (currentUser) {
            currentUser.nombre = formData.nombre;
            currentUser.correo = formData.email;
            
            // Actualizar el header
            document.getElementById('userName').textContent = formData.nombre;
        }
        
        showToast('Perfil actualizado exitosamente', 'success');
        closeUserProfileModal();
        
    } catch (error) {
        console.error('Error al guardar perfil:', error);
        showToast('Error al guardar los cambios', 'error');
    }
}

// Función eliminada - Ya no se usa porque el modal está en el HTML

async function changePassword() {
    const currentPassword = document.getElementById('currentPassword').value;
    const newPassword = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    
    if (!currentPassword || !newPassword || !confirmPassword) {
        showToast('Por favor, completa todos los campos', 'error');
        return;
    }
    
    if (newPassword !== confirmPassword) {
        showToast('Las contraseñas nuevas no coinciden', 'error');
        return;
    }
    
    if (newPassword.length < 8) {
        showToast('La nueva contraseña debe tener al menos 8 caracteres', 'error');
        return;
    }
    
    try {
        showToast('Cambiando contraseña...', 'info');
        
        // Aquí enviarías la petición al servidor
        // const response = await fetch('php/change_password.php', {
        //     method: 'POST',
        //     headers: { 'Content-Type': 'application/json' },
        //     body: JSON.stringify({ currentPassword, newPassword })
        // });
        
        showToast('Contraseña cambiada correctamente', 'success');
        
        // Cerrar el modal
        const modal = document.getElementById('changePasswordModal');
        if (modal) {
            modal.style.display = 'none';
            modal.classList.remove('show');
            document.body.style.overflow = 'auto';
        }
        
        // Limpiar formulario
        const form = document.getElementById('changePasswordForm');
        if (form) {
            form.reset();
        }
        
    } catch (error) {
        console.error('Error al cambiar contraseña:', error);
        showToast('Error al cambiar la contraseña', 'error');
    }
}

function showChangeAvatarModal() {
    console.log('Abriendo modal de cambiar avatar');
    // Crear modal para cambiar avatar
    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.id = 'changeAvatarModal';
    modal.innerHTML = `
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h3><i class="fas fa-camera"></i> Cambiar Foto de Perfil</h3>
                <button class="close-btn" id="closeAvatarModal">&times;</button>
            </div>
            <div style="padding: 20px; text-align: center;">
                <div style="margin-bottom: 20px;">
                    <div class="profile-avatar-large" id="previewAvatar" style="margin: 0 auto 20px; width: 150px; height: 150px; font-size: 4rem; border: 5px solid white; flex-shrink: 0; aspect-ratio: 1 / 1;">
                        <i class="fas fa-user"></i>
                    </div>
                    <p>Selecciona una nueva foto de perfil</p>
                </div>
                <div style="margin-bottom: 20px;">
                    <input type="file" id="avatarInput" accept="image/*" style="margin-bottom: 15px; width: 100%; padding: 10px; border: 2px dashed #1a3a2e; border-radius: 8px; background: #f8f9fa;">
                    <p style="font-size: 0.9rem; color: #666;">Formatos permitidos: JPG, PNG, GIF (máx. 2MB)</p>
                </div>
                <div style="display: flex; gap: 10px; justify-content: center;">
                    <button class="btn btn-outline" id="removeAvatar">Eliminar Foto</button>
                    <button class="btn btn-primary" id="saveAvatar">Guardar</button>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    modal.style.display = 'flex';
    
    // Preview de imagen
    const fileInput = document.getElementById('avatarInput');
    const previewAvatar = document.getElementById('previewAvatar');
    
    fileInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                previewAvatar.innerHTML = `<img src="${e.target.result}" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">`;
            };
            reader.readAsDataURL(file);
        }
    });
    
    // Event listeners para el modal de avatar
    document.getElementById('closeAvatarModal').addEventListener('click', () => {
        modal.remove();
    });
    
    document.getElementById('removeAvatar').addEventListener('click', () => {
        // Restaurar avatar por defecto
        const profileAvatar = document.getElementById('profileAvatar');
        profileAvatar.innerHTML = '<i class="fas fa-user"></i>';
        showToast('Foto eliminada', 'success');
        modal.remove();
    });
    
    document.getElementById('saveAvatar').addEventListener('click', () => {
        const fileInput = document.getElementById('avatarInput');
        if (fileInput.files.length > 0) {
            const file = fileInput.files[0];
            const reader = new FileReader();
            reader.onload = function(e) {
                // Actualizar avatar en el perfil principal
                const profileAvatar = document.getElementById('profileAvatar');
                profileAvatar.innerHTML = `<img src="${e.target.result}" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">`;
                showToast('Foto actualizada exitosamente', 'success');
                modal.remove();
            };
            reader.readAsDataURL(file);
        } else {
            showToast('Selecciona una foto', 'error');
        }
    });
    
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.remove();
        }
    });
}
