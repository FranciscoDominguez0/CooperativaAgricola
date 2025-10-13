<?php
// Generador de PDF simple y robusto
// Cooperativa Agrícola La Pintada

require_once 'conexion.php';

// Configurar headers
header('Content-Type: text/html; charset=utf-8');
header('Content-Disposition: inline; filename="reporte-cooperativa-' . date('Y-m-d') . '.html"');

try {
    $pdo = conectarDB();
    
    // Obtener parámetros con valores por defecto
    $dateFrom = $_GET['dateFrom'] ?? date('Y-m-01');
    $dateTo = $_GET['dateTo'] ?? date('Y-m-t');
    
    // Obtener datos de forma segura
    $data = obtenerDatosSeguros($pdo, $dateFrom, $dateTo);
    
    // Generar HTML del reporte
    $html = generarHTMLReporte($data, $dateFrom, $dateTo);
    
    // Agregar JavaScript para impresión automática
    $html = str_replace('</body>', '
    <script>
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 2000);
        };
    </script>
    </body>', $html);
    
    echo $html;
    
} catch (Exception $e) {
    // Generar página de error simple
    echo generarPaginaError($e->getMessage());
}

function obtenerDatosSeguros($pdo, $dateFrom, $dateTo) {
    $data = [
        'ingresos' => 0,
        'ventas' => 0,
        'aportes' => 0,
        'socios' => 0,
        'inventario' => 0,
        'ventas_detalle' => [],
        'socios_detalle' => []
    ];
    
    // Ventas
    try {
        $stmt = $pdo->prepare("
            SELECT 
                COALESCE(SUM(total), 0) as total_ingresos,
                COUNT(*) as total_ventas
            FROM ventas 
            WHERE fecha_venta >= ? AND fecha_venta <= ?
        ");
        $stmt->execute([$dateFrom, $dateTo]);
        $result = $stmt->fetch();
        $data['ingresos'] = $result['total_ingresos'] ?? 0;
        $data['ventas'] = $result['total_ventas'] ?? 0;
    } catch (Exception $e) {
        // Usar valores por defecto
    }
    
    // Aportes
    try {
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(monto), 0) as total_aportes
            FROM pagos 
            WHERE fecha_pago >= ? AND fecha_pago <= ?
        ");
        $stmt->execute([$dateFrom, $dateTo]);
        $result = $stmt->fetch();
        $data['aportes'] = $result['total_aportes'] ?? 0;
    } catch (Exception $e) {
        // Usar valores por defecto
    }
    
    // Socios
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as total_socios FROM socios WHERE estado = 'activo'");
        $result = $stmt->fetch();
        $data['socios'] = $result['total_socios'] ?? 0;
    } catch (Exception $e) {
        // Usar valores por defecto
    }
    
    // Inventario
    try {
        $stmt = $pdo->query("
            SELECT COALESCE(SUM(cantidad_disponible * precio_unitario), 0) as valor_inventario
            FROM insumos 
            WHERE cantidad_disponible > 0
        ");
        $result = $stmt->fetch();
        $data['inventario'] = $result['valor_inventario'] ?? 0;
    } catch (Exception $e) {
        // Usar valores por defecto
    }
    
    // Ventas detalladas
    try {
        $stmt = $pdo->prepare("
            SELECT fecha_venta, producto, cantidad, total
            FROM ventas 
            WHERE fecha_venta >= ? AND fecha_venta <= ?
            ORDER BY fecha_venta DESC
            LIMIT 10
        ");
        $stmt->execute([$dateFrom, $dateTo]);
        $data['ventas_detalle'] = $stmt->fetchAll();
    } catch (Exception $e) {
        // Usar array vacío
    }
    
    // Socios detallados
    try {
        $stmt = $pdo->query("
            SELECT nombre, apellido, telefono, fecha_ingreso
            FROM socios 
            WHERE estado = 'activo'
            ORDER BY fecha_ingreso DESC
            LIMIT 10
        ");
        $data['socios_detalle'] = $stmt->fetchAll();
    } catch (Exception $e) {
        // Usar array vacío
    }
    
    return $data;
}

function generarHTMLReporte($data, $dateFrom, $dateTo) {
    $fechaActual = date('d/m/Y H:i:s');
    
    return '
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Reporte Cooperativa La Pintada</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                margin: 0;
                padding: 20px;
                background: white;
                color: #333;
            }
            .header {
                text-align: center;
                border-bottom: 3px solid #2d5016;
                padding-bottom: 20px;
                margin-bottom: 30px;
            }
            .header h1 {
                color: #2d5016;
                margin: 0;
                font-size: 2.5rem;
            }
            .header p {
                color: #666;
                margin: 5px 0;
            }
            .periodo {
                background: #f8f9fa;
                padding: 15px;
                border-radius: 8px;
                margin-bottom: 30px;
                text-align: center;
            }
            .kpis {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 20px;
                margin-bottom: 30px;
            }
            .kpi-card {
                background: linear-gradient(135deg, #2d5016, #4a7c59);
                color: white;
                padding: 20px;
                border-radius: 10px;
                text-align: center;
            }
            .kpi-card h3 {
                font-size: 2rem;
                margin: 0 0 10px 0;
            }
            .kpi-card p {
                margin: 0;
                opacity: 0.9;
            }
            .section {
                margin-bottom: 30px;
            }
            .section h2 {
                color: #2d5016;
                border-bottom: 2px solid #2d5016;
                padding-bottom: 10px;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 15px;
            }
            th, td {
                padding: 12px;
                text-align: left;
                border-bottom: 1px solid #ddd;
            }
            th {
                background: #f8f9fa;
                font-weight: bold;
            }
            .footer {
                margin-top: 50px;
                text-align: center;
                color: #666;
                border-top: 1px solid #ddd;
                padding-top: 20px;
            }
            @media print {
                body { margin: 0; }
                .no-print { display: none; }
            }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>🌱 Cooperativa Agrícola La Pintada</h1>
            <p>Reporte de Gestión y Estadísticas</p>
            <p>Generado el: ' . $fechaActual . '</p>
        </div>
        
        <div class="periodo">
            <h3>📅 Período del Reporte</h3>
            <p><strong>Desde:</strong> ' . date('d/m/Y', strtotime($dateFrom)) . ' | <strong>Hasta:</strong> ' . date('d/m/Y', strtotime($dateTo)) . '</p>
        </div>
        
        <div class="kpis">
            <div class="kpi-card">
                <h3>$' . number_format($data['ingresos'], 2) . '</h3>
                <p>Ingresos Totales</p>
            </div>
            <div class="kpi-card">
                <h3>' . $data['ventas'] . '</h3>
                <p>Ventas Realizadas</p>
            </div>
            <div class="kpi-card">
                <h3>$' . number_format($data['aportes'], 2) . '</h3>
                <p>Aportes Recibidos</p>
            </div>
            <div class="kpi-card">
                <h3>' . $data['socios'] . '</h3>
                <p>Socios Activos</p>
            </div>
            <div class="kpi-card">
                <h3>$' . number_format($data['inventario'], 2) . '</h3>
                <p>Valor Inventario</p>
            </div>
        </div>
        
        <div class="section">
            <h2>📊 Ventas Recientes</h2>
            <table>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Producto</th>
                        <th>Cantidad</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>';
    
    if (!empty($data['ventas_detalle'])) {
        foreach ($data['ventas_detalle'] as $venta) {
            $html .= '
                    <tr>
                        <td>' . date('d/m/Y', strtotime($venta['fecha_venta'])) . '</td>
                        <td>' . htmlspecialchars($venta['producto']) . '</td>
                        <td>' . $venta['cantidad'] . '</td>
                        <td>$' . number_format($venta['total'], 2) . '</td>
                    </tr>';
        }
    } else {
        $html .= '
                    <tr>
                        <td colspan="4" style="text-align: center; color: #666;">No hay ventas en este período</td>
                    </tr>';
    }
    
    $html .= '
                </tbody>
            </table>
        </div>
        
        <div class="section">
            <h2>👥 Socios Activos</h2>
            <table>
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Apellido</th>
                        <th>Teléfono</th>
                        <th>Fecha Ingreso</th>
                    </tr>
                </thead>
                <tbody>';
    
    if (!empty($data['socios_detalle'])) {
        foreach ($data['socios_detalle'] as $socio) {
            $html .= '
                    <tr>
                        <td>' . htmlspecialchars($socio['nombre']) . '</td>
                        <td>' . htmlspecialchars($socio['apellido']) . '</td>
                        <td>' . htmlspecialchars($socio['telefono']) . '</td>
                        <td>' . date('d/m/Y', strtotime($socio['fecha_ingreso'])) . '</td>
                    </tr>';
        }
    } else {
        $html .= '
                    <tr>
                        <td colspan="4" style="text-align: center; color: #666;">No hay socios registrados</td>
                    </tr>';
    }
    
    $html .= '
                </tbody>
            </table>
        </div>
        
        <div class="footer">
            <p>Reporte generado automáticamente por el Sistema de Gestión Cooperativa</p>
            <p>Cooperativa Agrícola La Pintada - ' . date('Y') . '</p>
        </div>
    </body>
    </html>';
}

function generarPaginaError($mensaje) {
    return '
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <title>Error - Reporte Cooperativa</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                margin: 0;
                padding: 40px;
                background: #f8f9fa;
                text-align: center;
            }
            .error-container {
                background: white;
                padding: 40px;
                border-radius: 10px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.1);
                max-width: 600px;
                margin: 0 auto;
            }
            .error-icon {
                font-size: 4rem;
                color: #dc3545;
                margin-bottom: 20px;
            }
            .error-title {
                color: #dc3545;
                font-size: 1.5rem;
                margin-bottom: 15px;
            }
            .error-message {
                color: #666;
                margin-bottom: 30px;
            }
            .btn {
                background: #2d5016;
                color: white;
                padding: 12px 24px;
                border: none;
                border-radius: 6px;
                text-decoration: none;
                display: inline-block;
            }
        </style>
    </head>
    <body>
        <div class="error-container">
            <div class="error-icon">⚠️</div>
            <h1 class="error-title">Error al Generar Reporte</h1>
            <p class="error-message">No se pudieron cargar los datos para el reporte.</p>
            <p><strong>Detalles:</strong> ' . htmlspecialchars($mensaje) . '</p>
            <a href="javascript:history.back()" class="btn">← Volver</a>
        </div>
    </body>
    </html>';
}
?>
