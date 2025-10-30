<?php
// Generador de PDF profesional para reportes
// Cooperativa Agrícola La Pintada

require_once 'conexion.php';

// Configurar headers para descarga de PDF
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="reporte-cooperativa-' . date('Y-m-d') . '.pdf"');
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

try {
    $pdo = conectarDB();
    
    // Obtener parámetros
    $dateFrom = $_GET['dateFrom'] ?? date('Y-m-01');
    $dateTo = $_GET['dateTo'] ?? date('Y-m-t');
    
    // Obtener datos reales
    $reportData = obtenerDatosReporte($pdo, $dateFrom, $dateTo);
    
    // Generar PDF usando HTML (más compatible)
    $html = generarHTMLReporteCompleto($reportData);
    
    // Configurar headers para descarga
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="reporte-cooperativa-' . date('Y-m-d') . '.pdf"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    
    // Si hay wkhtmltopdf disponible, usarlo
    if (function_exists('shell_exec') && shell_exec('which wkhtmltopdf')) {
        generarPDFConWkhtmltopdf($html);
    } else {
        // Fallback: generar HTML optimizado para impresión
        generarHTMLParaImpresion($html);
    }
    
} catch (Exception $e) {
    // Si hay error, generar PDF de error
    generarPDFError($e->getMessage());
}

function obtenerDatosReporte($pdo, $dateFrom, $dateTo) {
    $data = [
        'periodo' => [
            'desde' => $dateFrom,
            'hasta' => $dateTo
        ],
        'kpis' => [],
        'ventas' => [],
        'socios' => [],
        'resumen' => []
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
        LIMIT 20
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
        LIMIT 10
    ");
    $stmt->execute([$dateFrom, $dateTo]);
    $data['socios'] = $stmt->fetchAll();
    
    return $data;
}

function generarHTMLReporteCompleto($data) {
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Reporte Cooperativa La Pintada</title>
        <style>
            @page {
                size: A4;
                margin: 1cm;
            }
            body {
                font-family: Arial, sans-serif;
                margin: 0;
                padding: 20px;
                background-color: white;
                color: #333;
                line-height: 1.4;
            }
            .header {
                text-align: center;
                border-bottom: 3px solid #2d5016;
                padding-bottom: 20px;
                margin-bottom: 30px;
            }
            .header h1 {
                color: #2d5016;
                font-size: 24px;
                margin: 0;
                font-weight: bold;
            }
            .header h2 {
                color: #666;
                font-size: 16px;
                margin: 5px 0;
            }
            .header p {
                margin: 3px 0;
                font-size: 12px;
            }
            .section {
                margin-bottom: 25px;
                page-break-inside: avoid;
            }
            .section h3 {
                color: #2d5016;
                font-size: 16px;
                border-bottom: 2px solid #4a7c59;
                padding-bottom: 5px;
                margin-bottom: 15px;
            }
            .kpi-grid {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 15px;
                margin: 15px 0;
            }
            .kpi-card {
                background: #f8f9fa;
                border: 1px solid #dee2e6;
                border-radius: 5px;
                padding: 15px;
                text-align: center;
            }
            .kpi-value {
                font-size: 20px;
                font-weight: bold;
                color: #2d5016;
                margin-bottom: 5px;
            }
            .kpi-label {
                font-size: 12px;
                color: #666;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin: 15px 0;
                font-size: 11px;
            }
            th, td {
                border: 1px solid #ddd;
                padding: 6px;
                text-align: left;
            }
            th {
                background-color: #2d5016;
                color: white;
                font-weight: bold;
                font-size: 10px;
            }
            tr:nth-child(even) {
                background-color: #f8f9fa;
            }
            .summary {
                background: #e8f5e8;
                padding: 15px;
                border-radius: 5px;
                margin: 15px 0;
                font-size: 12px;
            }
            .footer {
                text-align: center;
                margin-top: 30px;
                padding-top: 15px;
                border-top: 1px solid #ddd;
                color: #666;
                font-size: 10px;
            }
            @media print {
                body { margin: 0; }
                .section { page-break-inside: avoid; }
            }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>COOPERATIVA AGRÍCOLA LA PINTADA</h1>
            <h2>Reporte Ejecutivo de Gestión</h2>
            <p>Generado el: ' . date('d/m/Y H:i:s') . '</p>
            <p>Período: ' . date('d/m/Y', strtotime($data['periodo']['desde'])) . ' - ' . date('d/m/Y', strtotime($data['periodo']['hasta'])) . '</p>
        </div>';
    
    // KPIs
    $html .= '
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
        </div>';
    
    // Ventas
    $html .= '
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
        </div>';
    
    // Socios
    $html .= '
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
    </body>
    </html>';
    
    return $html;
}

function generarPDFConWkhtmltopdf($html) {
    // Crear archivo temporal HTML
    $tempFile = tempnam(sys_get_temp_dir(), 'reporte_') . '.html';
    file_put_contents($tempFile, $html);
    
    // Generar PDF con wkhtmltopdf
    $outputFile = tempnam(sys_get_temp_dir(), 'reporte_') . '.pdf';
    $command = "wkhtmltopdf --page-size A4 --margin-top 1cm --margin-bottom 1cm --margin-left 1cm --margin-right 1cm '$tempFile' '$outputFile'";
    
    exec($command, $output, $returnCode);
    
    if ($returnCode === 0 && file_exists($outputFile)) {
        readfile($outputFile);
        unlink($tempFile);
        unlink($outputFile);
    } else {
        generarHTMLParaImpresion($html);
    }
}

function generarHTMLParaImpresion($html) {
    // Cambiar headers para HTML
    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: inline; filename="reporte-cooperativa-' . date('Y-m-d') . '.html"');
    
    // Agregar JavaScript para impresión automática
    $html = str_replace('</body>', '
    <script>
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 1000);
        };
    </script>
    </body>', $html);
    
    echo $html;
}

function generarPDFError($mensaje) {
    // Generar HTML de error en lugar de PDF
    header('Content-Type: text/html; charset=utf-8');
    
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Error - Reporte PDF</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                text-align: center;
                padding: 50px;
                background-color: #f8f9fa;
            }
            .error-container {
                background: white;
                padding: 30px;
                border-radius: 10px;
                box-shadow: 0 0 10px rgba(0,0,0,0.1);
                max-width: 500px;
                margin: 0 auto;
            }
            .error-icon {
                font-size: 48px;
                color: #dc3545;
                margin-bottom: 20px;
            }
            .error-title {
                color: #dc3545;
                font-size: 24px;
                margin-bottom: 15px;
            }
            .error-message {
                color: #666;
                margin-bottom: 20px;
            }
            .back-button {
                background: #2d5016;
                color: white;
                padding: 10px 20px;
                border: none;
                border-radius: 5px;
                cursor: pointer;
                text-decoration: none;
                display: inline-block;
            }
        </style>
    </head>
    <body>
        <div class="error-container">
            <div class="error-icon">⚠️</div>
            <h1 class="error-title">Error al Generar PDF</h1>
            <p class="error-message">' . htmlspecialchars($mensaje) . '</p>
            <a href="javascript:history.back()" class="back-button">Volver</a>
        </div>
    </body>
    </html>';
    
    echo $html;
}
?>
