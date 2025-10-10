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
            populateSociosDropdown();
        }
    } catch (error) {
        console.error('Error al cargar socios:', error);
    }
}

function populateSociosDropdown() {
    const select = document.getElementById('id_socio');
    select.innerHTML = '<option value="">Seleccionar socio</option>';
    
    sociosList.forEach(socio => {
        const option = document.createElement('option');
        option.value = socio.id_socio;
        option.textContent = socio.nombre;
        select.appendChild(option);
    });
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
    prevBtn.onclick = () => loadProduccion(pagination.current_page - 1);
    paginationDiv.appendChild(prevBtn);
    
    for (let i = 1; i <= pagination.total_pages; i++) {
        if (i === 1 || i === pagination.total_pages || (i >= pagination.current_page - 2 && i <= pagination.current_page + 2)) {
            const pageBtn = document.createElement('button');
            pageBtn.textContent = i;
            pageBtn.className = i === pagination.current_page ? 'active' : '';
            pageBtn.onclick = () => loadProduccion(i);
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
    nextBtn.onclick = () => loadProduccion(pagination.current_page + 1);
    paginationDiv.appendChild(nextBtn);
}

function openProduccionModal(produccion = null) {
    const modal = document.getElementById('produccionModal');
    const form = document.getElementById('produccionForm');
    const title = document.getElementById('produccionModalTitle');
    
    if (produccion) {
        title.textContent = 'Editar Producción';
        console.log('Estableciendo datos en el formulario:', produccion);
        
        Object.keys(produccion).forEach(key => {
            let fieldId = key;
            
            if (key === 'id_produccion') {
                fieldId = 'produccionId';
            }
            
            const field = document.getElementById(fieldId);
            if (field) {
                field.value = produccion[key];
                console.log(`Campo ${key} (${fieldId}) establecido con valor:`, produccion[key]);
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
    
    // Cargar socios si no están cargados
    if (sociosList.length === 0) {
        loadSociosForProduccion();
    } else {
        populateSociosDropdown();
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
                loadProduccion(currentProduccionPage);
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
                loadProduccion(currentProduccionPage);
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
            populateSociosDropdownForVentas();
        }
    } catch (error) {
        console.error('Error cargando socios para ventas:', error);
    }
}

function populateSociosDropdownForVentas() {
    const select = document.getElementById('socioSelect');
    if (!select) return;
    
    select.innerHTML = '<option value="">Seleccionar socio</option>';
    
    sociosList.forEach(socio => {
        const option = document.createElement('option');
        option.value = socio.id_socio;
        option.textContent = socio.nombre;
        select.appendChild(option);
    });
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
                <td colspan="10" class="no-data">
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
            <td>${parseFloat(item.cantidad).toLocaleString()}</td>
            <td>$${parseFloat(item.precio_unitario).toLocaleString()}</td>
            <td>$${parseFloat(item.total).toLocaleString()}</td>
            <td>${item.cliente}</td>
            <td>${formatDate(item.fecha_venta)}</td>
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
    prevBtn.onclick = () => loadVentas(pagination.current_page - 1);
    paginationDiv.appendChild(prevBtn);
    
    for (let i = 1; i <= pagination.total_pages; i++) {
        if (i === 1 || i === pagination.total_pages || (i >= pagination.current_page - 2 && i <= pagination.current_page + 2)) {
            const pageBtn = document.createElement('button');
            pageBtn.textContent = i;
            pageBtn.className = i === pagination.current_page ? 'active' : '';
            pageBtn.onclick = () => loadVentas(i);
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
    nextBtn.onclick = () => loadVentas(pagination.current_page + 1);
    paginationDiv.appendChild(nextBtn);
}

function openVentaModal(venta = null) {
    const modal = document.getElementById('ventaModal');
    const form = document.getElementById('ventaForm');
    const title = document.getElementById('ventaModalTitle');
    
    if (venta) {
        title.textContent = 'Editar Venta';
        
        // Establecer valores específicos
        document.getElementById('ventaId').value = venta.id_venta || '';
        document.getElementById('socioSelect').value = venta.id_socio || '';
        document.getElementById('producto').value = venta.producto || '';
        document.getElementById('cantidad').value = venta.cantidad || '';
        document.getElementById('precio_unitario').value = venta.precio_unitario || '';
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
    
    // Cargar socios si no están cargados
    if (sociosList.length === 0) {
        loadSociosForVentas();
    } else {
        populateSociosDropdownForVentas();
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
                loadVentas(currentVentasPage);
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
                loadVentas(currentVentasPage);
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
    if (confirm(`¿Estás seguro de que quieres eliminar la venta de "${producto}"?`)) {
        deleteVenta(ventaId);
    }
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
});
