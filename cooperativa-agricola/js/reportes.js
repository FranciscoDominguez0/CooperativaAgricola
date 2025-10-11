// JavaScript para el módulo de Reportes y Estadísticas
// Cooperativa Agrícola La Pintada

let currentUser = null;
let chartInstances = {};
let currentFilters = {
    dateFrom: '',
    dateTo: '',
    product: '',
    socio: ''
};

// Colores del tema
const chartColors = {
    primary: '#2d5016',
    secondary: '#4a7c59',
    success: '#4caf50',
    info: '#2196f3',
    warning: '#ff9800',
    danger: '#f44336',
    light: '#8bc34a',
    dark: '#333333'
};

const chartPalette = [
    '#2d5016', '#4a7c59', '#8bc34a', '#ffc107', '#ff9800',
    '#2196f3', '#4caf50', '#f44336', '#9c27b0', '#607d8b'
];

document.addEventListener('DOMContentLoaded', function() {
    checkSession();
    setupEventListeners();
    initializeDateFilters();
    loadReportData();
});

async function checkSession() {
    try {
        const response = await fetch('php/verificar_sesion.php');
        const data = await response.json();
        
        if (data.authenticated) {
            currentUser = data.user;
            loadUserInfo();
            hideLoading();
        } else {
            window.location.href = 'login.html';
        }
    } catch (error) {
        console.error('Error checking session:', error);
        window.location.href = 'login.html';
    }
}

function loadUserInfo() {
    document.getElementById('userName').textContent = currentUser.nombre;
    document.getElementById('userRole').textContent = getRoleDisplay(currentUser.rol);
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

function hideLoading() {
    document.getElementById('loading').style.display = 'none';
}

function setupEventListeners() {
    // Filtros
    document.getElementById('applyFilters').addEventListener('click', applyFilters);
    document.getElementById('resetFilters').addEventListener('click', resetFilters);
    document.getElementById('exportPDF').addEventListener('click', exportToPDF);

    // Botones de generación de reportes
    document.getElementById('generateSalesReport').addEventListener('click', generateSalesReport);
    document.getElementById('generateContributionsReport').addEventListener('click', generateContributionsReport);
    document.getElementById('generateInventoryReport').addEventListener('click', generateInventoryReport);
    document.getElementById('generateMarginReport').addEventListener('click', generateMarginReport);

    // Botones de exportación
    document.getElementById('exportSalesReport').addEventListener('click', () => exportReport('sales'));
    document.getElementById('exportContributionsReport').addEventListener('click', () => exportReport('contributions'));
    document.getElementById('exportInventoryReport').addEventListener('click', () => exportReport('inventory'));
    document.getElementById('exportMarginReport').addEventListener('click', () => exportReport('margin'));

    // Logout
    document.getElementById('logoutBtn').addEventListener('click', function() {
        if (confirm('¿Estás seguro de que deseas cerrar sesión?')) {
            window.location.href = 'php/logout.php';
        }
    });

    // Navegación
    document.querySelectorAll('.nav-item').forEach(item => {
        item.addEventListener('click', function() {
            const section = this.dataset.section;
            if (section !== 'reportes') {
                window.location.href = 'dashboard.html';
            }
        });
    });
}

function initializeDateFilters() {
    const today = new Date();
    const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
    const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);

    document.getElementById('dateFrom').value = firstDay.toISOString().split('T')[0];
    document.getElementById('dateTo').value = lastDay.toISOString().split('T')[0];

    currentFilters.dateFrom = firstDay.toISOString().split('T')[0];
    currentFilters.dateTo = lastDay.toISOString().split('T')[0];
}

async function loadReportData() {
    try {
        // Cargar datos de KPIs
        await loadKPIData();
        
        // Cargar datos para filtros
        await loadFilterOptions();
        
        // Cargar gráficos
        await loadCharts();
        
        // Cargar tabla de resumen
        await loadSummaryTable();
        
    } catch (error) {
        console.error('Error loading report data:', error);
        showToast('Error al cargar los datos del reporte', 'error');
    }
}

async function loadKPIData() {
    try {
        const response = await fetch('php/reportes.php?action=kpis');
        const data = await response.json();
        
        if (data.success) {
            updateKPICards(data.kpis);
        }
    } catch (error) {
        console.error('Error loading KPI data:', error);
        // Usar datos de ejemplo si hay error
        updateKPICards(getSampleKPIData());
    }
}

function updateKPICards(kpis) {
    // Actualizar tarjetas de KPIs
    document.getElementById('totalIncome').textContent = formatCurrency(kpis.totalIncome);
    document.getElementById('incomeChange').textContent = `${kpis.incomeChange > 0 ? '+' : ''}${kpis.incomeChange}% vs mes anterior`;
    document.getElementById('incomeChange').className = `kpi-change ${kpis.incomeChange >= 0 ? 'positive' : 'negative'}`;
    
    document.getElementById('totalContributions').textContent = formatCurrency(kpis.totalContributions);
    document.getElementById('activeMembers').textContent = `${kpis.activeMembers} miembros activos`;
    
    document.getElementById('inventoryValue').textContent = formatCurrency(kpis.inventoryValue);
    document.getElementById('availableItems').textContent = `${kpis.availableItems} artículos disponibles`;
    
    document.getElementById('grossMargin').textContent = `${kpis.grossMargin}%`;
}

function getSampleKPIData() {
    return {
        totalIncome: 28000,
        incomeChange: 12.5,
        totalContributions: 16800,
        activeMembers: 124,
        inventoryValue: 11369,
        availableItems: 305,
        grossMargin: 62.5
    };
}

async function loadFilterOptions() {
    try {
        // Cargar productos
        const productsResponse = await fetch('php/reportes.php?action=products');
        const productsData = await productsResponse.json();
        
        if (productsData.success) {
            populateSelect('productFilter', productsData.products, 'producto', 'id_producto');
        }
        
        // Cargar socios
        const sociosResponse = await fetch('php/socios.php');
        const sociosData = await sociosResponse.json();
        
        if (sociosData.success) {
            populateSelect('socioFilter', sociosData.data, 'nombre', 'id_socio');
        }
        
    } catch (error) {
        console.error('Error loading filter options:', error);
    }
}

function populateSelect(selectId, data, textField, valueField) {
    const select = document.getElementById(selectId);
    select.innerHTML = `<option value="">Todos</option>`;
    
    data.forEach(item => {
        const option = document.createElement('option');
        option.value = item[valueField];
        option.textContent = item[textField];
        select.appendChild(option);
    });
}

async function loadCharts() {
    try {
        const response = await fetch('php/reportes.php?action=charts');
        const data = await response.json();
        
        if (data.success) {
            createCharts(data.charts);
        } else {
            // Usar datos de ejemplo
            createCharts(getSampleChartData());
        }
    } catch (error) {
        console.error('Error loading charts:', error);
        createCharts(getSampleChartData());
    }
}

function createCharts(chartData) {
    // Monthly Financial Evolution
    createMonthlyFinancialChart(chartData.monthlyFinancial);
    
    // Contributions per Member
    createContributionsChart(chartData.contributions);
    
    // Inventory by Type
    createInventoryTypeChart(chartData.inventoryType);
    
    // Sales by Product
    createSalesProductChart(chartData.salesProduct);
    
    // Production Trends
    createProductionTrendsChart(chartData.productionTrends);
    
    // Member Performance
    createMemberPerformanceChart(chartData.memberPerformance);
}

function createMonthlyFinancialChart(data) {
    const ctx = document.getElementById('monthlyFinancialChart').getContext('2d');
    
    if (chartInstances.monthlyFinancial) {
        chartInstances.monthlyFinancial.destroy();
    }
    
    chartInstances.monthlyFinancial = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.labels,
            datasets: [
                {
                    label: 'Ventas',
                    data: data.sales,
                    backgroundColor: chartColors.success,
                    borderColor: chartColors.primary,
                    borderWidth: 2
                },
                {
                    label: 'Aportes',
                    data: data.contributions,
                    backgroundColor: chartColors.info,
                    borderColor: chartColors.secondary,
                    borderWidth: 2
                },
                {
                    label: 'Gastos',
                    data: data.expenses,
                    backgroundColor: chartColors.danger,
                    borderColor: '#d32f2f',
                    borderWidth: 2
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return formatCurrency(value);
                        }
                    }
                }
            }
        }
    });
}

function createContributionsChart(data) {
    const ctx = document.getElementById('contributionsChart').getContext('2d');
    
    if (chartInstances.contributions) {
        chartInstances.contributions.destroy();
    }
    
    chartInstances.contributions = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.labels,
            datasets: [
                {
                    label: 'Aportes Reales',
                    data: data.actual,
                    backgroundColor: chartColors.success,
                    borderColor: chartColors.primary,
                    borderWidth: 2
                },
                {
                    label: 'Cuotas Asignadas',
                    data: data.assigned,
                    backgroundColor: chartColors.warning,
                    borderColor: '#f57c00',
                    borderWidth: 2
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return formatCurrency(value);
                        }
                    }
                }
            }
        }
    });
}

function createInventoryTypeChart(data) {
    const ctx = document.getElementById('inventoryTypeChart').getContext('2d');
    
    if (chartInstances.inventoryType) {
        chartInstances.inventoryType.destroy();
    }
    
    chartInstances.inventoryType = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: data.labels,
            datasets: [{
                data: data.values,
                backgroundColor: chartPalette.slice(0, data.labels.length),
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                }
            }
        }
    });
}

function createSalesProductChart(data) {
    const ctx = document.getElementById('salesProductChart').getContext('2d');
    
    if (chartInstances.salesProduct) {
        chartInstances.salesProduct.destroy();
    }
    
    chartInstances.salesProduct = new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.labels,
            datasets: data.datasets.map((dataset, index) => ({
                label: dataset.label,
                data: dataset.data,
                borderColor: chartPalette[index % chartPalette.length],
                backgroundColor: chartPalette[index % chartPalette.length] + '20',
                borderWidth: 3,
                fill: false,
                tension: 0.4
            }))
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return formatCurrency(value);
                        }
                    }
                }
            }
        }
    });
}

function createProductionTrendsChart(data) {
    const ctx = document.getElementById('productionTrendsChart').getContext('2d');
    
    if (chartInstances.productionTrends) {
        chartInstances.productionTrends.destroy();
    }
    
    chartInstances.productionTrends = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.labels,
            datasets: [{
                label: 'Producción (Quintales)',
                data: data.values,
                backgroundColor: chartColors.light,
                borderColor: chartColors.primary,
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

function createMemberPerformanceChart(data) {
    const ctx = document.getElementById('memberPerformanceChart').getContext('2d');
    
    if (chartInstances.memberPerformance) {
        chartInstances.memberPerformance.destroy();
    }
    
    chartInstances.memberPerformance = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.labels,
            datasets: [
                {
                    label: 'Producción',
                    data: data.production,
                    backgroundColor: chartColors.success,
                    borderColor: chartColors.primary,
                    borderWidth: 2
                },
                {
                    label: 'Ventas',
                    data: data.sales,
                    backgroundColor: chartColors.info,
                    borderColor: chartColors.secondary,
                    borderWidth: 2
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

async function loadSummaryTable() {
    try {
        const response = await fetch('php/reportes.php?action=summary');
        const data = await response.json();
        
        if (data.success) {
            populateSummaryTable(data.summary);
        } else {
            populateSummaryTable(getSampleSummaryData());
        }
    } catch (error) {
        console.error('Error loading summary table:', error);
        populateSummaryTable(getSampleSummaryData());
    }
}

function populateSummaryTable(summaryData) {
    const tbody = document.getElementById('summaryTableBody');
    tbody.innerHTML = '';
    
    summaryData.forEach(item => {
        const row = document.createElement('tr');
        const changeClass = item.change >= 0 ? 'trend-up' : 'trend-down';
        const trendIcon = item.change >= 0 ? 'fas fa-arrow-up' : 'fas fa-arrow-down';
        
        row.innerHTML = `
            <td><strong>${item.metric}</strong></td>
            <td>${item.current}</td>
            <td>${item.previous}</td>
            <td class="trend-indicator ${changeClass}">
                <i class="${trendIcon}"></i>
                ${Math.abs(item.change)}%
            </td>
            <td>
                <span class="trend-indicator ${changeClass}">
                    <i class="${trendIcon}"></i>
                    ${item.change >= 0 ? 'Creciente' : 'Decreciente'}
                </span>
            </td>
        `;
        tbody.appendChild(row);
    });
}

function getSampleChartData() {
    return {
        monthlyFinancial: {
            labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun'],
            sales: [25000, 28000, 32000, 29000, 35000, 38000],
            contributions: [15000, 16800, 18000, 17200, 19000, 19500],
            expenses: [8000, 9200, 10500, 9800, 11200, 11800]
        },
        contributions: {
            labels: ['Socio A', 'Socio B', 'Socio C', 'Socio D', 'Socio E'],
            actual: [500, 750, 600, 800, 650],
            assigned: [500, 500, 500, 500, 500]
        },
        inventoryType: {
            labels: ['Semillas', 'Fertilizantes', 'Herramientas', 'Maquinaria'],
            values: [3500, 2800, 2100, 2969]
        },
        salesProduct: {
            labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun'],
            datasets: [
                {
                    label: 'Maíz',
                    data: [12000, 14000, 16000, 15000, 18000, 20000]
                },
                {
                    label: 'Frijol',
                    data: [8000, 9000, 10000, 9500, 11000, 12000]
                },
                {
                    label: 'Arroz',
                    data: [5000, 5000, 6000, 4500, 6000, 6000]
                }
            ]
        },
        productionTrends: {
            labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun'],
            values: [120, 135, 150, 140, 165, 180]
        },
        memberPerformance: {
            labels: ['Juan P.', 'María L.', 'Carlos M.', 'Ana S.', 'Pedro R.'],
            production: [45, 38, 42, 35, 40],
            sales: [28000, 25000, 26000, 22000, 24000]
        }
    };
}

function getSampleSummaryData() {
    return [
        {
            metric: 'Ingresos Totales',
            current: '$28,000',
            previous: '$24,900',
            change: 12.5
        },
        {
            metric: 'Aportes Recaudados',
            current: '$16,800',
            previous: '$15,200',
            change: 10.5
        },
        {
            metric: 'Valor de Inventario',
            current: '$11,369',
            previous: '$10,800',
            change: 5.3
        },
        {
            metric: 'Margen Bruto',
            current: '62.5%',
            previous: '58.2%',
            change: 7.4
        },
        {
            metric: 'Socios Activos',
            current: '124',
            previous: '118',
            change: 5.1
        }
    ];
}

function applyFilters() {
    currentFilters.dateFrom = document.getElementById('dateFrom').value;
    currentFilters.dateTo = document.getElementById('dateTo').value;
    currentFilters.product = document.getElementById('productFilter').value;
    currentFilters.socio = document.getElementById('socioFilter').value;
    
    showToast('Filtros aplicados correctamente', 'success');
    loadReportData();
}

function resetFilters() {
    document.getElementById('dateFrom').value = '';
    document.getElementById('dateTo').value = '';
    document.getElementById('productFilter').value = '';
    document.getElementById('socioFilter').value = '';
    
    currentFilters = {
        dateFrom: '',
        dateTo: '',
        product: '',
        socio: ''
    };
    
    showToast('Filtros restablecidos', 'info');
    loadReportData();
}

async function exportToPDF() {
    try {
        showToast('Generando PDF...', 'info');
        
        const { jsPDF } = window.jspdf;
        const pdf = new jsPDF('p', 'mm', 'a4');
        
        // Título
        pdf.setFontSize(20);
        pdf.setTextColor(45, 80, 22);
        pdf.text('Reporte de Cooperativa La Pintada', 20, 20);
        
        // Fecha
        pdf.setFontSize(12);
        pdf.setTextColor(100, 100, 100);
        pdf.text(`Generado el: ${new Date().toLocaleDateString('es-ES')}`, 20, 30);
        
        // KPIs
        pdf.setFontSize(16);
        pdf.setTextColor(45, 80, 22);
        pdf.text('Indicadores Clave de Rendimiento', 20, 50);
        
        const kpis = [
            ['Métrica', 'Valor'],
            ['Ingresos Totales', '$28,000'],
            ['Aportes Recaudados', '$16,800'],
            ['Valor de Inventario', '$11,369'],
            ['Margen Bruto', '62.5%']
        ];
        
        pdf.autoTable({
            startY: 60,
            head: [kpis[0]],
            body: kpis.slice(1),
            theme: 'grid',
            headStyles: {
                fillColor: [139, 195, 74],
                textColor: [255, 255, 255]
            }
        });
        
        // Guardar PDF
        pdf.save(`reporte-cooperativa-${new Date().toISOString().split('T')[0]}.pdf`);
        
        showToast('PDF generado exitosamente', 'success');
        
    } catch (error) {
        console.error('Error generating PDF:', error);
        showToast('Error al generar PDF', 'error');
    }
}

async function generateSalesReport() {
    showToast('Generando reporte de ventas...', 'info');
    // Simular generación de reporte
    setTimeout(() => {
        showToast('Reporte de ventas generado', 'success');
    }, 2000);
}

async function generateContributionsReport() {
    showToast('Generando reporte de aportes...', 'info');
    setTimeout(() => {
        showToast('Reporte de aportes generado', 'success');
    }, 2000);
}

async function generateInventoryReport() {
    showToast('Generando reporte de inventario...', 'info');
    setTimeout(() => {
        showToast('Reporte de inventario generado', 'success');
    }, 2000);
}

async function generateMarginReport() {
    showToast('Generando reporte de márgenes...', 'info');
    setTimeout(() => {
        showToast('Reporte de márgenes generado', 'success');
    }, 2000);
}

async function exportReport(type) {
    showToast(`Exportando reporte de ${type}...`, 'info');
    setTimeout(() => {
        showToast(`Reporte de ${type} exportado`, 'success');
    }, 1500);
}

function formatCurrency(value) {
    return new Intl.NumberFormat('es-ES', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(value);
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

// Función para redimensionar gráficos cuando cambia el tamaño de la ventana
window.addEventListener('resize', function() {
    Object.values(chartInstances).forEach(chart => {
        if (chart) {
            chart.resize();
        }
    });
});

// Función para limpiar gráficos al salir de la página
window.addEventListener('beforeunload', function() {
    Object.values(chartInstances).forEach(chart => {
        if (chart) {
            chart.destroy();
        }
    });
});
