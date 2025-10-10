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
