// ===== FUNCIONES PRINCIPALES =====

// Verificar sesión
async function checkSession() {
    try {
        const response = await fetch('php/verificar_sesion.php');
        const data = await response.json();
        
        if (!data.authenticated) {
            window.location.href = 'index.html';
            return;
        }
        
        document.getElementById('userName').textContent = data.user.nombre || 'Usuario';
        document.getElementById('userRole').textContent = data.user.rol || 'Miembro';
    } catch (error) {
        console.error('Error verificando sesión:', error);
        window.location.href = 'index.html';
    }
}

// Cargar página de ventas
async function loadVentasPage() {
    try {
        await checkSession();
        await loadVentasStatistics();
        await loadSociosForVentas();
        await loadVentas();
        setupEventListeners();
    } catch (error) {
        console.error('Error cargando página de ventas:', error);
        showToast('Error cargando datos', 'error');
    }
}

// Obtener display del rol
function getRoleDisplay(role) {
    const roles = {
        'productor': '🌾 Productor',
        'cliente': '🏪 Cliente',
        'contador': '📊 Contador',
        'admin': '👑 Administrador'
    };
    return roles[role] || role;
}

// ===== ESTADÍSTICAS =====

// Cargar estadísticas de ventas
async function loadVentasStatistics() {
    try {
        const response = await fetch('php/ventas.php?action=statistics');
        const data = await response.json();
        
        if (data.success) {
            updateVentasStatistics(data.statistics);
        } else {
            console.error('Error cargando estadísticas:', data.message);
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

// Actualizar estadísticas en la UI
function updateVentasStatistics(stats) {
    const statsGrid = document.getElementById('statsGrid');
    
    statsGrid.innerHTML = `
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-dollar-sign"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number">$${stats.ventas_totales || '0'}</div>
                <div class="stat-label">Ventas Totales</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number">${stats.ventas_pendientes || '0'}</div>
                <div class="stat-label">Ventas Pendientes</div>
            </div>
        </div>
        <div class="stat-card stat-card-dark">
            <div class="stat-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number">${stats.ventas_pagadas || '0'}</div>
                <div class="stat-label">Ventas Pagadas</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number">${stats.clientes_activos || '0'}</div>
                <div class="stat-label">Clientes Activos</div>
            </div>
        </div>
    `;
}

// ===== SOCIOS =====

// Cargar socios para el dropdown
async function loadSociosForVentas() {
    try {
        const response = await fetch('php/socios.php?action=list');
        const data = await response.json();
        
        if (data.success) {
            populateSociosDropdown(data.socios);
        }
    } catch (error) {
        console.error('Error cargando socios:', error);
    }
}

// Poblar dropdown de socios
function populateSociosDropdown(socios) {
    const select = document.getElementById('socioSelect');
    select.innerHTML = '<option value="">Seleccionar socio</option>';
    
    socios.forEach(socio => {
        const option = document.createElement('option');
        option.value = socio.id_socio;
        option.textContent = `${socio.nombre} (${getRoleDisplay(socio.rol)})`;
        select.appendChild(option);
    });
}

// ===== VENTAS =====

// Cargar ventas
async function loadVentas(page = 1) {
    try {
        const response = await fetch(`php/ventas.php?action=list&page=${page}`);
        const data = await response.json();
        
        if (data.success) {
            displayVentas(data.ventas);
            displayPagination(data.pagination);
        } else {
            console.error('Error cargando ventas:', data.message);
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

// Mostrar ventas en la tabla
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
                    <button class="btn btn-sm btn-secondary btn-animate hover-scale micro-bounce" onclick="editVenta(${item.id_venta})" title="Editar venta">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger btn-animate hover-scale micro-bounce" onclick="confirmDeleteVenta(${item.id_venta}, '${item.producto}')" title="Eliminar venta">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        `;
        tbody.appendChild(row);
    });
}

// Obtener display del estado
function getEstadoDisplay(estado) {
    const estados = {
        'pendiente': 'Pendiente',
        'entregado': 'Entregado',
        'pagado': 'Pagado',
        'cancelado': 'Cancelado'
    };
    return estados[estado] || estado;
}

// Formatear fecha
function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('es-ES');
}

// Mostrar paginación
function displayPagination(pagination) {
    const container = document.getElementById('pagination');
    
    if (!pagination || pagination.total_pages <= 1) {
        container.innerHTML = '';
        return;
    }
    
    let html = '<div class="pagination-controls">';
    
    // Botón anterior
    if (pagination.current_page > 1) {
        html += `<button class="btn btn-sm btn-secondary" onclick="loadVentas(${pagination.current_page - 1})">
            <i class="fas fa-chevron-left"></i> Anterior
        </button>`;
    }
    
    // Números de página
    const startPage = Math.max(1, pagination.current_page - 2);
    const endPage = Math.min(pagination.total_pages, pagination.current_page + 2);
    
    for (let i = startPage; i <= endPage; i++) {
        const activeClass = i === pagination.current_page ? 'btn-primary' : 'btn-secondary';
        html += `<button class="btn btn-sm ${activeClass}" onclick="loadVentas(${i})">${i}</button>`;
    }
    
    // Botón siguiente
    if (pagination.current_page < pagination.total_pages) {
        html += `<button class="btn btn-sm btn-secondary" onclick="loadVentas(${pagination.current_page + 1})">
            Siguiente <i class="fas fa-chevron-right"></i>
        </button>`;
    }
    
    html += '</div>';
    container.innerHTML = html;
}

// ===== EVENT LISTENERS =====

function setupEventListeners() {
    // Botón agregar venta
    document.getElementById('addVentaBtn').addEventListener('click', openVentaModal);
    
    // Modal
    document.getElementById('closeModal').addEventListener('click', closeVentaModal);
    document.getElementById('cancelBtn').addEventListener('click', closeVentaModal);
    document.getElementById('saveBtn').addEventListener('click', saveVenta);
    
    // Cerrar modal al hacer clic fuera
    document.getElementById('ventaModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeVentaModal();
        }
    });
    
    // Cálculo automático del total
    document.getElementById('cantidad').addEventListener('input', calculateTotal);
    document.getElementById('precio_unitario').addEventListener('input', calculateTotal);
    
    // Establecer fecha actual
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('fecha_venta').value = today;
}

// ===== MODAL =====

// Abrir modal de venta
async function openVentaModal(ventaId = null) {
    const modal = document.getElementById('ventaModal');
    const form = document.getElementById('ventaForm');
    const modalTitle = document.getElementById('modalTitle');
    
    if (ventaId) {
        console.log('🔄 Abriendo modal para EDITAR venta ID:', ventaId);
        modalTitle.textContent = 'Editar Venta';
        
        // Mostrar modal primero
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
        
        // Luego cargar los datos
        await loadVentaData(ventaId);
    } else {
        console.log('🔄 Abriendo modal para CREAR nueva venta');
        modalTitle.textContent = 'Registrar Nueva Venta';
        form.reset();
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('fecha_venta').value = today;
        
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

// Cerrar modal
function closeVentaModal() {
    const modal = document.getElementById('ventaModal');
    modal.classList.remove('active');
    document.body.style.overflow = '';
    
    // Limpiar formulario
    document.getElementById('ventaForm').reset();
}

// Cargar datos de venta para editar
async function loadVentaData(ventaId) {
    try {
        console.log('🔄 Cargando datos de venta ID:', ventaId);
        const response = await fetch(`php/ventas.php?action=get&id=${ventaId}`);
        const data = await response.json();
        
        console.log('📡 Respuesta completa del servidor:', data);
        
        if (data.success) {
            const venta = data.data;
            console.log('📦 Datos de la venta recibidos:', venta);
            console.log('🔍 Valores específicos:');
            console.log('  - id_venta:', venta.id_venta, '(tipo:', typeof venta.id_venta, ')');
            console.log('  - cantidad:', venta.cantidad, '(tipo:', typeof venta.cantidad, ')');
            console.log('  - precio_unitario:', venta.precio_unitario, '(tipo:', typeof venta.precio_unitario, ')');
            console.log('  - producto:', venta.producto);
            console.log('  - cliente:', venta.cliente);
            
            // Esperar más tiempo para asegurar que el DOM esté completamente listo
            await new Promise(resolve => setTimeout(resolve, 200));
            
            // Usar la nueva función robusta para llenar campos
            await llenarCamposVenta(venta);
            
        } else {
            console.error('❌ Error:', data.message);
            showToast('Error cargando datos: ' + data.message, 'error');
        }
    } catch (error) {
        console.error('❌ Error cargando datos de venta:', error);
        showToast('Error cargando datos', 'error');
    }
}

// Nueva función robusta para llenar campos de venta
async function llenarCamposVenta(venta) {
    console.log('🔄 LLENANDO CAMPOS DE VENTA');
    
    // Función auxiliar para establecer valor de forma robusta
    function setFieldValue(fieldId, value, fieldName) {
        const field = document.getElementById(fieldId);
        if (!field) {
            console.error(`❌ Campo ${fieldName} (${fieldId}) no encontrado`);
            return false;
        }
        
        console.log(`📝 Estableciendo ${fieldName}:`, value);
        
        // Múltiples métodos para asegurar que el valor se establezca
        field.value = value;
        field.setAttribute('value', value);
        field.defaultValue = value;
        
        // Para campos de tipo number, asegurar que se muestre correctamente
        if (field.type === 'number') {
            field.step = '0.01';
            field.min = '0';
        }
        
        // Disparar eventos para notificar cambios
        field.dispatchEvent(new Event('input', { bubbles: true }));
        field.dispatchEvent(new Event('change', { bubbles: true }));
        
        // Verificar que se estableció correctamente
        const finalValue = field.value;
        console.log(`✅ ${fieldName} establecido:`, finalValue);
        
        return finalValue === String(value);
    }
    
    // Llenar todos los campos
    const campos = [
        { id: 'id_venta', value: venta.id_venta || '', name: 'ID Venta' },
        { id: 'socioSelect', value: venta.id_socio || '', name: 'Socio' },
        { id: 'producto', value: venta.producto || '', name: 'Producto' },
        { id: 'cantidad', value: venta.cantidad || '', name: 'Cantidad' },
        { id: 'precio_unitario', value: venta.precio_unitario || '', name: 'Precio Unitario' },
        { id: 'cliente', value: venta.cliente || '', name: 'Cliente' },
        { id: 'fecha_venta', value: venta.fecha_venta || '', name: 'Fecha Venta' },
        { id: 'fecha_entrega', value: venta.fecha_entrega || '', name: 'Fecha Entrega' },
        { id: 'estado', value: venta.estado || 'pendiente', name: 'Estado' },
        { id: 'metodo_pago', value: venta.metodo_pago || 'efectivo', name: 'Método Pago' },
        { id: 'direccion_entrega', value: venta.direccion_entrega || '', name: 'Dirección' },
        { id: 'observaciones', value: venta.observaciones || '', name: 'Observaciones' }
    ];
    
    // Llenar cada campo
    for (const campo of campos) {
        setFieldValue(campo.id, campo.value, campo.name);
    }
    
    // Verificación especial para campos problemáticos
    console.log('🔍 VERIFICACIÓN ESPECIAL:');
    const cantidadField = document.getElementById('cantidad');
    const precioField = document.getElementById('precio_unitario');
    
    console.log('  - Cantidad final:', cantidadField?.value);
    console.log('  - Precio final:', precioField?.value);
    
    // Solución específica para campos numéricos problemáticos
    if (cantidadField && venta.cantidad) {
        console.log('🔧 SOLUCIÓN ESPECÍFICA PARA CANTIDAD');
        console.log('  - Valor original:', venta.cantidad);
        console.log('  - Tipo:', typeof venta.cantidad);
        
        // Limpiar el campo completamente
        cantidadField.value = '';
        cantidadField.removeAttribute('value');
        cantidadField.removeAttribute('placeholder');
        
        // Esperar un momento
        await new Promise(resolve => setTimeout(resolve, 50));
        
        // Establecer el valor de múltiples formas
        const cantidadValue = parseFloat(venta.cantidad);
        console.log('  - Valor parseado:', cantidadValue);
        
        cantidadField.value = cantidadValue;
        cantidadField.setAttribute('value', cantidadValue);
        cantidadField.defaultValue = cantidadValue;
        
        // Forzar actualización del DOM
        cantidadField.dispatchEvent(new Event('input', { bubbles: true }));
        cantidadField.dispatchEvent(new Event('change', { bubbles: true }));
        
        // Verificar resultado
        console.log('  - Valor final cantidad:', cantidadField.value);
    }
    
    if (precioField && venta.precio_unitario) {
        console.log('🔧 SOLUCIÓN ESPECÍFICA PARA PRECIO');
        console.log('  - Valor original:', venta.precio_unitario);
        console.log('  - Tipo:', typeof venta.precio_unitario);
        
        // Limpiar el campo completamente
        precioField.value = '';
        precioField.removeAttribute('value');
        precioField.removeAttribute('placeholder');
        
        // Esperar un momento
        await new Promise(resolve => setTimeout(resolve, 50));
        
        // Establecer el valor de múltiples formas
        const precioValue = parseFloat(venta.precio_unitario);
        console.log('  - Valor parseado:', precioValue);
        
        precioField.value = precioValue;
        precioField.setAttribute('value', precioValue);
        precioField.defaultValue = precioValue;
        
        // Forzar actualización del DOM
        precioField.dispatchEvent(new Event('input', { bubbles: true }));
        precioField.dispatchEvent(new Event('change', { bubbles: true }));
        
        // Verificar resultado
        console.log('  - Valor final precio:', precioField.value);
    }
    
    // Verificación final absoluta
    console.log('🔍 VERIFICACIÓN FINAL ABSOLUTA:');
    const cantidadFinal = document.getElementById('cantidad')?.value;
    const precioFinal = document.getElementById('precio_unitario')?.value;
    
    console.log('  - Cantidad final:', cantidadFinal);
    console.log('  - Precio final:', precioFinal);
    
    // Si aún están vacíos, usar método de fuerza bruta
    if (cantidadFinal === '' || cantidadFinal === '0' || cantidadFinal === '0.00') {
        console.log('🚨 FORZANDO CANTIDAD CON MÉTODO DE FUERZA BRUTA');
        const cantidadField = document.getElementById('cantidad');
        if (cantidadField && venta.cantidad) {
            cantidadField.value = '';
            setTimeout(() => {
                cantidadField.value = venta.cantidad;
                cantidadField.dispatchEvent(new Event('input', { bubbles: true }));
            }, 100);
        }
    }
    
    if (precioFinal === '' || precioFinal === '0' || precioFinal === '0.00') {
        console.log('🚨 FORZANDO PRECIO CON MÉTODO DE FUERZA BRUTA');
        const precioField = document.getElementById('precio_unitario');
        if (precioField && venta.precio_unitario) {
            precioField.value = '';
            setTimeout(() => {
                precioField.value = venta.precio_unitario;
                precioField.dispatchEvent(new Event('input', { bubbles: true }));
            }, 100);
        }
    }
    
    console.log('✅ Llenado de campos completado');
}

// Calcular total automáticamente
function calculateTotal() {
    const cantidad = parseFloat(document.getElementById('cantidad').value) || 0;
    const precio = parseFloat(document.getElementById('precio_unitario').value) || 0;
    const total = cantidad * precio;
    
    // Mostrar el total calculado (solo visual, no se envía)
    const totalDisplay = document.querySelector('.total-display');
    if (totalDisplay) {
        totalDisplay.textContent = `Total: $${total.toLocaleString()}`;
    }
}

// ===== CRUD OPERATIONS =====

// Guardar venta
async function saveVenta() {
    const form = document.getElementById('ventaForm');
    const formData = new FormData(form);
    
    // Validaciones
    if (!form.checkValidity()) {
        showToast('Por favor, completa todos los campos requeridos', 'error');
        return;
    }
    
    const ventaId = document.getElementById('id_venta').value;
    console.log('💾 Guardando venta. ID:', ventaId);
    
    const url = ventaId ? `php/ventas.php?action=update&id=${ventaId}` : 'php/ventas.php?action=create';
    const method = ventaId ? 'POST' : 'POST'; // Ambos usan POST según el PHP
    
    console.log('📡 URL:', url, 'Método:', method);
    
    try {
        const response = await fetch(url, {
            method: method,
            body: formData
        });
        
        const data = await response.json();
        console.log('📡 Respuesta:', data);
        
        if (data.success) {
            showToast(data.message, 'success');
            closeVentaModal();
            loadVentas();
            loadVentasStatistics();
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        console.error('❌ Error:', error);
        showToast('Error al guardar la venta', 'error');
    }
}

// Editar venta
function editVenta(ventaId) {
    openVentaModal(ventaId);
}

// Confirmar eliminación
function confirmDeleteVenta(ventaId, producto) {
    if (confirm(`¿Estás seguro de que quieres eliminar la venta de "${producto}"?`)) {
        deleteVenta(ventaId);
    }
}

// Eliminar venta
async function deleteVenta(ventaId) {
    try {
        const response = await fetch(`php/ventas.php?action=delete&id=${ventaId}`, {
            method: 'DELETE'
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast(data.message, 'success');
            loadVentas();
            loadVentasStatistics();
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Error al eliminar la venta', 'error');
    }
}

// ===== TOAST NOTIFICATIONS =====

function showToast(message, type = 'info') {
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
        <div class="toast-content">
            <i class="fas fa-${getToastIcon(type)}"></i>
            <span>${message}</span>
        </div>
    `;
    
    container.appendChild(toast);
    
    // Animar entrada
    setTimeout(() => toast.classList.add('show'), 100);
    
    // Remover después de 3 segundos
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => container.removeChild(toast), 300);
    }, 3000);
}

function getToastIcon(type) {
    const icons = {
        'success': 'check-circle',
        'error': 'exclamation-circle',
        'warning': 'exclamation-triangle',
        'info': 'info-circle'
    };
    return icons[type] || 'info-circle';
}

// ===== INICIALIZACIÓN =====

document.addEventListener('DOMContentLoaded', function() {
    loadVentasPage();
});
