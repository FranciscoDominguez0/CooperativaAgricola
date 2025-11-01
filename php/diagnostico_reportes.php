<?php
// Script de diagnóstico rápido para reportes
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'conexion.php';

try {
    $pdo = conectarDB();
    
    $diagnostic = [
        'success' => true,
        'database' => 'cooperativa_agricola',
        'tables' => [],
        'ventas' => [],
        'pagos' => [],
        'socios' => [],
        'insumos' => []
    ];
    
    // Obtener todas las tablas
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $diagnostic['tables'] = $tables;
    
    // Diagnóstico de tabla ventas
    if (in_array('ventas', $tables)) {
        // Total de ventas
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM ventas");
        $total = $stmt->fetch()['total'];
        
        // Estados de ventas
        $stmt = $pdo->query("SELECT estado, COUNT(*) as count FROM ventas GROUP BY estado");
        $estados = $stmt->fetchAll();
        
        // Rango de fechas
        $stmt = $pdo->query("SELECT MIN(fecha_venta) as min_date, MAX(fecha_venta) as max_date FROM ventas");
        $fechas = $stmt->fetch();
        
        // Total sin filtro de fecha
        $stmt = $pdo->query("SELECT COALESCE(SUM(total), 0) as total FROM ventas");
        $totalTodos = $stmt->fetch()['total'];
        
        // Total con estados válidos (sin filtro de fecha)
        $stmt = $pdo->query("
            SELECT COALESCE(SUM(total), 0) as total 
            FROM ventas 
            WHERE (estado IS NULL OR estado IN ('pagado', 'entregado', 'pendiente'))
        ");
        $totalValidos = $stmt->fetch()['total'];
        
        // Mes actual
        $dateFrom = date('Y-m-01');
        $dateTo = date('Y-m-t');
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count, COALESCE(SUM(total), 0) as total 
            FROM ventas 
            WHERE fecha_venta >= ? AND fecha_venta <= ?
            AND (estado IS NULL OR estado IN ('pagado', 'entregado', 'pendiente'))
        ");
        $stmt->execute([$dateFrom, $dateTo]);
        $mesActual = $stmt->fetch();
        
        $diagnostic['ventas'] = [
            'total_ventas' => $total,
            'estados' => $estados,
            'fecha_minima' => $fechas['min_date'],
            'fecha_maxima' => $fechas['max_date'],
            'total_todos' => $totalTodos,
            'total_validos' => $totalValidos,
            'mes_actual' => $mesActual,
            'mes_actual_range' => "$dateFrom a $dateTo"
        ];
    }
    
    // Diagnóstico de tabla pagos
    if (in_array('pagos', $tables)) {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM pagos");
        $total = $stmt->fetch()['total'];
        
        // Tipos y estados
        $stmt = $pdo->query("SELECT tipo, estado, COUNT(*) as count FROM pagos GROUP BY tipo, estado");
        $tiposEstados = $stmt->fetchAll();
        
        // Rango de fechas
        $stmt = $pdo->query("SELECT MIN(fecha_pago) as min_date, MAX(fecha_pago) as max_date FROM pagos");
        $fechas = $stmt->fetch();
        
        // Total de aportes confirmados sin filtro de fecha
        $stmt = $pdo->query("
            SELECT COALESCE(SUM(monto), 0) as total 
            FROM pagos 
            WHERE tipo IN ('aporte_mensual', 'aporte_extraordinario') 
            AND estado = 'confirmado'
        ");
        $totalAportes = $stmt->fetch()['total'];
        
        // Mes actual
        $dateFrom = date('Y-m-01');
        $dateTo = date('Y-m-t');
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count, COALESCE(SUM(monto), 0) as total 
            FROM pagos 
            WHERE fecha_pago >= ? AND fecha_pago <= ?
            AND tipo IN ('aporte_mensual', 'aporte_extraordinario') 
            AND estado = 'confirmado'
        ");
        $stmt->execute([$dateFrom, $dateTo]);
        $mesActual = $stmt->fetch();
        
        $diagnostic['pagos'] = [
            'total_pagos' => $total,
            'tipos_estados' => $tiposEstados,
            'fecha_minima' => $fechas['min_date'],
            'fecha_maxima' => $fechas['max_date'],
            'total_aportes_confirmados' => $totalAportes,
            'mes_actual' => $mesActual,
            'mes_actual_range' => "$dateFrom a $dateTo"
        ];
    }
    
    // Diagnóstico de tabla socios
    if (in_array('socios', $tables)) {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM socios");
        $total = $stmt->fetch()['total'];
        
        $stmt = $pdo->query("SELECT estado, COUNT(*) as count FROM socios GROUP BY estado");
        $estados = $stmt->fetchAll();
        
        $stmt = $pdo->query("SELECT COUNT(*) as activos FROM socios WHERE estado = 'activo'");
        $activos = $stmt->fetch()['activos'];
        
        $diagnostic['socios'] = [
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
        
        $diagnostic['insumos'] = [
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

