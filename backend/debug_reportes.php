<?php
// Script de diagnóstico para reportes
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'conexion.php';

try {
    $pdo = conectarDB();
    
    $diagnostic = [
        'success' => true,
        'database' => 'cooperativa_agricola',
        'tables' => [],
        'data_check' => [],
        'date_range' => [],
        'issues' => []
    ];
    
    // Obtener todas las tablas
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $diagnostic['tables'] = $tables;
    
    // Verificar rango de fechas por defecto
    $dateFrom = date('Y-m-01'); // Primer día del mes actual
    $dateTo = date('Y-m-t');    // Último día del mes actual
    $diagnostic['date_range'] = [
        'from' => $dateFrom,
        'to' => $dateTo,
        'current_date' => date('Y-m-d')
    ];
    
    // Diagnóstico de tabla ventas
    if (in_array('ventas', $tables)) {
        // Contar total de ventas
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM ventas");
        $total = $stmt->fetch()['total'];
        
        // Verificar estados
        $stmt = $pdo->query("SELECT estado, COUNT(*) as count FROM ventas GROUP BY estado");
        $estados = $stmt->fetchAll();
        
        // Verificar rango de fechas
        $stmt = $pdo->query("SELECT MIN(fecha_venta) as min_date, MAX(fecha_venta) as max_date FROM ventas");
        $fechas = $stmt->fetch();
        
        // Verificar ventas en el rango del mes actual
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count, COALESCE(SUM(total), 0) as total_income 
            FROM ventas 
            WHERE fecha_venta >= ? AND fecha_venta <= ?
        ");
        $stmt->execute([$dateFrom, $dateTo]);
        $mesActual = $stmt->fetch();
        
        // Verificar ventas con estados válidos en el rango
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count, COALESCE(SUM(total), 0) as total_income 
            FROM ventas 
            WHERE fecha_venta >= ? AND fecha_venta <= ?
            AND (estado IS NULL OR estado IN ('pagado', 'entregado', 'pendiente'))
        ");
        $stmt->execute([$dateFrom, $dateTo]);
        $mesActualValido = $stmt->fetch();
        
        // Verificar ventas sin filtro de fecha
        $stmt = $pdo->query("
            SELECT COUNT(*) as count, COALESCE(SUM(total), 0) as total_income 
            FROM ventas 
            WHERE (estado IS NULL OR estado IN ('pagado', 'entregado', 'pendiente'))
        ");
        $todasValidas = $stmt->fetch();
        
        $diagnostic['data_check']['ventas'] = [
            'total_ventas' => $total,
            'estados' => $estados,
            'fecha_minima' => $fechas['min_date'],
            'fecha_maxima' => $fechas['max_date'],
            'mes_actual_todas' => $mesActual,
            'mes_actual_validas' => $mesActualValido,
            'todas_validas_sin_fecha' => $todasValidas
        ];
        
        // Identificar problemas
        if ($total == 0) {
            $diagnostic['issues'][] = "No hay ventas en la base de datos";
        } elseif ($mesActualValido['count'] == 0) {
            if ($fechas['min_date'] && $fechas['max_date']) {
                $diagnostic['issues'][] = "Las ventas están fuera del rango del mes actual. Fechas en BD: {$fechas['min_date']} a {$fechas['max_date']}";
            }
            if ($mesActual['count'] > 0 && $mesActualValido['count'] == 0) {
                $diagnostic['issues'][] = "Hay ventas en el mes actual pero con estados inválidos (probablemente 'cancelado')";
            }
        }
    } else {
        $diagnostic['issues'][] = "Tabla 'ventas' no existe";
    }
    
    // Diagnóstico de tabla pagos
    if (in_array('pagos', $tables)) {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM pagos");
        $total = $stmt->fetch()['total'];
        
        // Verificar tipos y estados
        $stmt = $pdo->query("SELECT tipo, estado, COUNT(*) as count FROM pagos GROUP BY tipo, estado");
        $tiposEstados = $stmt->fetchAll();
        
        // Verificar rango de fechas
        $stmt = $pdo->query("SELECT MIN(fecha_pago) as min_date, MAX(fecha_pago) as max_date FROM pagos");
        $fechas = $stmt->fetch();
        
        // Verificar aportes en el rango del mes actual
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count, COALESCE(SUM(monto), 0) as total_contributions 
            FROM pagos 
            WHERE fecha_pago >= ? AND fecha_pago <= ?
            AND tipo IN ('aporte_mensual', 'aporte_extraordinario')
            AND estado = 'confirmado'
        ");
        $stmt->execute([$dateFrom, $dateTo]);
        $mesActual = $stmt->fetch();
        
        // Verificar todos los aportes sin filtro de fecha
        $stmt = $pdo->query("
            SELECT COUNT(*) as count, COALESCE(SUM(monto), 0) as total_contributions 
            FROM pagos 
            WHERE tipo IN ('aporte_mensual', 'aporte_extraordinario')
            AND estado = 'confirmado'
        ");
        $todosValidos = $stmt->fetch();
        
        $diagnostic['data_check']['pagos'] = [
            'total_pagos' => $total,
            'tipos_estados' => $tiposEstados,
            'fecha_minima' => $fechas['min_date'],
            'fecha_maxima' => $fechas['max_date'],
            'mes_actual_aportes' => $mesActual,
            'todos_aportes_validos' => $todosValidos
        ];
        
        if ($total == 0) {
            $diagnostic['issues'][] = "No hay pagos en la base de datos";
        } elseif ($mesActual['count'] == 0) {
            if ($fechas['min_date'] && $fechas['max_date']) {
                $diagnostic['issues'][] = "Los pagos están fuera del rango del mes actual. Fechas en BD: {$fechas['min_date']} a {$fechas['max_date']}";
            }
            if ($todosValidos['count'] == 0) {
                $diagnostic['issues'][] = "No hay pagos con tipo 'aporte_mensual' o 'aporte_extraordinario' y estado 'confirmado'";
            }
        }
    } else {
        $diagnostic['issues'][] = "Tabla 'pagos' no existe";
    }
    
    // Diagnóstico de tabla socios
    if (in_array('socios', $tables)) {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM socios");
        $total = $stmt->fetch()['total'];
        
        $stmt = $pdo->query("SELECT estado, COUNT(*) as count FROM socios GROUP BY estado");
        $estados = $stmt->fetchAll();
        
        $stmt = $pdo->query("SELECT COUNT(*) as activos FROM socios WHERE estado = 'activo'");
        $activos = $stmt->fetch()['activos'];
        
        $diagnostic['data_check']['socios'] = [
            'total_socios' => $total,
            'estados' => $estados,
            'activos' => $activos
        ];
    }
    
    // Diagnóstico de tabla insumos
    if (in_array('insumos', $tables)) {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM insumos");
        $total = $stmt->fetch()['total'];
        
        $stmt = $pdo->query("
            SELECT COALESCE(SUM(cantidad_disponible * precio_unitario), 0) as valor 
            FROM insumos 
            WHERE estado = 'disponible' AND cantidad_disponible > 0
        ");
        $valor = $stmt->fetch()['valor'];
        
        $stmt = $pdo->query("
            SELECT COUNT(*) as items 
            FROM insumos 
            WHERE estado = 'disponible' AND cantidad_disponible > 0
        ");
        $items = $stmt->fetch()['items'];
        
        $diagnostic['data_check']['insumos'] = [
            'total_insumos' => $total,
            'valor_inventario' => $valor,
            'items_disponibles' => $items
        ];
    }
    
    echo json_encode($diagnostic, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_PRETTY_PRINT);
}
?>

