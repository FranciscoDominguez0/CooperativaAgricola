<?php
// Generador de PDF simple sin dependencias externas
// Cooperativa Agrícola La Pintada

require_once 'conexion.php';

try {
    $pdo = conectarDB();
    
    // Obtener parámetros
    $dateFrom = $_GET['dateFrom'] ?? date('Y-m-01');
    $dateTo = $_GET['dateTo'] ?? date('Y-m-t');
    
    // Obtener datos reales
    $reportData = obtenerDatosReporte($pdo, $dateFrom, $dateTo);
    
    // Generar HTML para PDF
    $html = generarHTMLReporte($reportData);
    
    // Configurar headers para descarga
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="reporte-cooperativa-' . date('Y-m-d') . '.pdf"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    
    // Generar PDF usando HTML2PDF (alternativa)
    if (function_exists('html2pdf')) {
        echo html2pdf($html);
    } else {
        // Fallback: generar HTML que se puede imprimir como PDF
        header('Content-Type: text/html; charset=utf-8');
        header('Content-Disposition: inline; filename="reporte-cooperativa-' . date('Y-m-d') . '.html"');
        echo $html;
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

function obtenerDatosReporte($pdo, $dateFrom, $dateTo) {
    $data = [
        'periodo' => [
            'desde' => $dateFrom,
            'hasta' => $dateTo
        ],
        'kpis' => [],
        'ventas' => [],
        'socios' => []
    ];
    
    // KPIs principales
    $stmt = $pdo->prepare("
        SELECT 
            COALESCE(SUM(total), 0) as total_ingresos,
            COUNT(*) as total_ventas
        FROM ventas 
        WHERE fecha_venta >= ? AND fecha_venta <= ? AND estado = 'pagado'
    ");
    $stmt->execute([$dateFrom, $dateTo]);
    $ventas = $stmt->fetch();
    
    $data['kpis']['ingresos'] = $ventas['total_ingresos'];
    $data['kpis']['ventas'] = $ventas['total_ventas'];
    
    // Aportes
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(monto), 0) as total_aportes
        FROM pagos 
        WHERE fecha_pago >= ? AND fecha_pago <= ? 
        AND tipo IN ('aporte_mensual', 'aporte_extraordinario') 
        AND estado = 'confirmado'
    ");
    $stmt->execute([$dateFrom, $dateTo]);
    $aportes = $stmt->fetch();
    
    $data['kpis']['aportes'] = $aportes['total_aportes'];
    
    // Socios activos
    $stmt = $pdo->query("SELECT COUNT(*) as total_socios FROM socios WHERE estado = 'activo'");
    $socios = $stmt->fetch();
    
    $data['kpis']['socios'] = $socios['total_socios'];
    
    // Inventario
    $stmt = $pdo->query("
        SELECT COALESCE(SUM(cantidad_disponible * precio_unitario), 0) as valor_inventario
        FROM insumos 
        WHERE estado = 'disponible' AND cantidad_disponible > 0
    ");
    $inventario = $stmt->fetch();
    
    $data['kpis']['inventario'] = $inventario['valor_inventario'];
    
    // Ventas detalladas
    $stmt = $pdo->prepare("
        SELECT 
            fecha_venta,
            producto,
            cantidad,
            precio_unitario,
            total,
            cliente,
            estado
        FROM ventas 
        WHERE fecha_venta >= ? AND fecha_venta <= ?
        ORDER BY fecha_venta DESC
        LIMIT 15
    ");
    $stmt->execute([$dateFrom, $dateTo]);
    $data['ventas'] = $stmt->fetchAll();
    
    // Top socios
    $stmt = $pdo->prepare("
        SELECT 
            s.nombre,
            COALESCE(SUM(v.total), 0) as total_ventas,
            COUNT(v.id_venta) as numero_ventas
        FROM socios s
        LEFT JOIN ventas v ON s.id_socio = v.id_socio 
            AND v.fecha_venta >= ? AND v.fecha_venta <= ?
        WHERE s.estado = 'activo'
        GROUP BY s.id_socio, s.nombre
        ORDER BY total_ventas DESC
        LIMIT 8
    ");
    $stmt->execute([$dateFrom, $dateTo]);
    $data['socios'] = $stmt->fetchAll();
    
    return $data;
}

function generarHTMLReporte($data) {
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Reporte Cooperativa La Pintada</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                margin: 0;
                padding: 20px;
                background-color: #f5f5f5;
            }
            .report-container {
                max-width: 800px;
                margin: 0 auto;
                background: white;
                padding: 30px;
                box-shadow: 0 0 10px rgba(0,0,0,0.1);
            }
            .header {
                text-align: center;
                border-bottom: 3px solid #2d5016;
                padding-bottom: 20px;
                margin-bottom: 30px;
            }
            .header h1 {
                color: #2d5016;
                font-size: 28px;
                margin: 0;
            }
            .header h2 {
                color: #666;
                font-size: 16px;
                margin: 5px 0;
            }
            .section {
                margin-bottom: 30px;
            }
            .section h3 {
                color: #2d5016;
                font-size: 18px;
                border-bottom: 2px solid #4a7c59;
                padding-bottom: 5px;
            }
            .kpi-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 15px;
                margin: 20px 0;
            }
            .kpi-card {
                background: #f8f9fa;
                border: 1px solid #dee2e6;
                border-radius: 8px;
                padding: 15px;
                text-align: center;
            }
            .kpi-value {
                font-size: 24px;
                font-weight: bold;
                color: #2d5016;
                margin-bottom: 5px;
            }
            .kpi-label {
                font-size: 14px;
                color: #666;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin: 15px 0;
            }
            th, td {
                border: 1px solid #ddd;
                padding: 8px;
                text-align: left;
            }
            th {
                background-color: #2d5016;
                color: white;
                font-weight: bold;
            }
            tr:nth-child(even) {
                background-color: #f8f9fa;
            }
            .summary {
                background: #e8f5e8;
                padding: 15px;
                border-radius: 5px;
                margin: 20px 0;
            }
            .footer {
                text-align: center;
                margin-top: 30px;
                padding-top: 20px;
                border-top: 1px solid #ddd;
                color: #666;
                font-size: 12px;
            }
            @media print {
                body { background: white; }
                .report-container { box-shadow: none; }
            }
        </style>
    </head>
    <body>
        <div class="report-container">
            <div class="header">
                <h1>COOPERATIVA AGRÍCOLA LA PINTADA</h1>
                <h2>Reporte Ejecutivo de Gestión</h2>
                <p>Generado el: ' . date('d/m/Y H:i:s') . '</p>
                <p>Período: ' . date('d/m/Y', strtotime($data['periodo']['desde'])) . ' - ' . date('d/m/Y', strtotime($data['periodo']['hasta'])) . '</p>
            </div>
            
            <div class="section">
                <h3>📊 INDICADORES CLAVE DE RENDIMIENTO</h3>
                <div class="kpi-grid">
                    <div class="kpi-card">
                        <div class="kpi-value">$' . number_format($data['kpis']['ingresos']) . '</div>
                        <div class="kpi-label">Ingresos Totales</div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-value">$' . number_format($data['kpis']['aportes']) . '</div>
                        <div class="kpi-label">Aportes Recaudados</div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-value">' . $data['kpis']['socios'] . '</div>
                        <div class="kpi-label">Socios Activos</div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-value">$' . number_format($data['kpis']['inventario']) . '</div>
                        <div class="kpi-label">Valor de Inventario</div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-value">' . $data['kpis']['ventas'] . '</div>
                        <div class="kpi-label">Número de Ventas</div>
                    </div>
                </div>
            </div>
            
            <div class="section">
                <h3>🛒 VENTAS RECIENTES</h3>';
    
    if (count($data['ventas']) > 0) {
        $html .= '
                <table>
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Precio Unit.</th>
                            <th>Total</th>
                            <th>Cliente</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>';
        
        foreach ($data['ventas'] as $venta) {
            $html .= '
                        <tr>
                            <td>' . date('d/m/Y', strtotime($venta['fecha_venta'])) . '</td>
                            <td>' . htmlspecialchars($venta['producto']) . '</td>
                            <td>' . $venta['cantidad'] . '</td>
                            <td>$' . number_format($venta['precio_unitario']) . '</td>
                            <td>$' . number_format($venta['total']) . '</td>
                            <td>' . htmlspecialchars($venta['cliente']) . '</td>
                            <td>' . $venta['estado'] . '</td>
                        </tr>';
        }
        
        $html .= '
                    </tbody>
                </table>';
    } else {
        $html .= '<p>No hay ventas registradas en el período seleccionado.</p>';
    }
    
    $html .= '
            </div>
            
            <div class="section">
                <h3>👥 TOP SOCIOS POR VENTAS</h3>';
    
    if (count($data['socios']) > 0) {
        $html .= '
                <table>
                    <thead>
                        <tr>
                            <th>Socio</th>
                            <th>Total Ventas</th>
                            <th>N° Ventas</th>
                            <th>Promedio</th>
                        </tr>
                    </thead>
                    <tbody>';
        
        foreach ($data['socios'] as $socio) {
            $promedio = $socio['numero_ventas'] > 0 ? $socio['total_ventas'] / $socio['numero_ventas'] : 0;
            $html .= '
                        <tr>
                            <td>' . htmlspecialchars($socio['nombre']) . '</td>
                            <td>$' . number_format($socio['total_ventas']) . '</td>
                            <td>' . $socio['numero_ventas'] . '</td>
                            <td>$' . number_format($promedio) . '</td>
                        </tr>';
        }
        
        $html .= '
                    </tbody>
                </table>';
    } else {
        $html .= '<p>No hay datos de socios disponibles.</p>';
    }
    
    $html .= '
            </div>
            
            <div class="section">
                <h3>📋 RESUMEN EJECUTIVO</h3>
                <div class="summary">
                    <p><strong>La Cooperativa Agrícola La Pintada</strong> muestra un rendimiento sólido con los siguientes resultados:</p>
                    <ul>
                        <li>Ingresos totales de <strong>$' . number_format($data['kpis']['ingresos']) . '</strong> en el período analizado</li>
                        <li><strong>' . $data['kpis']['ventas'] . '</strong> transacciones de venta registradas</li>
                        <li><strong>' . $data['kpis']['socios'] . '</strong> socios activos contribuyendo al crecimiento</li>
                        <li>Valor de inventario de <strong>$' . number_format($data['kpis']['inventario']) . '</strong></li>
                        <li>Aportes recaudados por <strong>$' . number_format($data['kpis']['aportes']) . '</strong></li>
                    </ul>
                </div>
            </div>
            
            <div class="footer">
                <p>Generado automáticamente por el Sistema de Gestión de Cooperativa La Pintada</p>
                <p>Para más información, contacte al administrador del sistema</p>
            </div>
        </div>
    </body>
    </html>';
    
    return $html;
}
?>
