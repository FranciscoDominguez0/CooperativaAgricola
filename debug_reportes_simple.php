<?php
// Debug simple de reportes - versión simplificada
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'php/conexion.php';

try {
    $pdo = conectarDB();
    
    // Parámetros de fecha
    $dateFrom = $_GET['dateFrom'] ?? date('Y-m-01');
    $dateTo = $_GET['dateTo'] ?? date('Y-m-t');
    
    $result = [
        'success' => true,
        'debug_info' => [
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'timestamp' => date('Y-m-d H:i:s')
        ],
        'kpis' => []
    ];
    
    // 1. Verificar tablas
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $result['debug_info']['tables'] = $tables;
    
    // 2. Test de ventas - consulta simple
    if (in_array('ventas', $tables)) {
        // Contar total de ventas
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM ventas");
        $totalVentas = $stmt->fetch()['total'];
        $result['debug_info']['total_ventas_bd'] = $totalVentas;
        
        // Sumar todas las ventas sin filtro de fecha
        $stmt = $pdo->query("SELECT COALESCE(SUM(total), 0) as total_all FROM ventas");
        $totalAll = $stmt->fetch()['total_all'];
        $result['debug_info']['total_ventas_all'] = $totalAll;
        
        // Consulta con filtro de fecha
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(total), 0) as total_period 
            FROM ventas 
            WHERE fecha_venta >= ? AND fecha_venta <= ?
        ");
        $stmt->execute([$dateFrom, $dateTo]);
        $totalPeriod = $stmt->fetch()['total_period'];
        $result['debug_info']['total_ventas_period'] = $totalPeriod;
        
        // Consulta con filtro de estado
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(total), 0) as total_paid 
            FROM ventas 
            WHERE fecha_venta >= ? AND fecha_venta <= ? 
            AND (estado = 'pagado' OR estado = 'completado' OR estado = 'finalizado' OR estado IS NULL)
        ");
        $stmt->execute([$dateFrom, $dateTo]);
        $totalPaid = $stmt->fetch()['total_paid'];
        $result['debug_info']['total_ventas_paid'] = $totalPaid;
        
        // Mostrar algunas ventas de ejemplo
        $stmt = $pdo->prepare("
            SELECT fecha_venta, producto, total, estado 
            FROM ventas 
            ORDER BY fecha_venta DESC 
            LIMIT 3
        ");
        $stmt->execute();
        $sampleVentas = $stmt->fetchAll();
        $result['debug_info']['sample_ventas'] = $sampleVentas;
        
        // KPIs
        $result['kpis']['totalIncome'] = $totalPaid;
        $result['kpis']['incomeChange'] = 0; // Simplificado por ahora
        
    } else {
        $result['debug_info']['error'] = "Tabla 'ventas' no encontrada";
        $result['kpis']['totalIncome'] = 0;
        $result['kpis']['incomeChange'] = 0;
    }
    
    // 3. Test de socios
    if (in_array('socios', $tables)) {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM socios WHERE estado = 'activo'");
        $activeMembers = $stmt->fetch()['total'];
        $result['kpis']['activeMembers'] = $activeMembers;
        $result['debug_info']['active_members'] = $activeMembers;
    } else {
        $result['kpis']['activeMembers'] = 0;
    }
    
    // 4. Test de pagos
    if (in_array('pagos', $tables)) {
        $stmt = $pdo->query("SELECT COALESCE(SUM(monto), 0) as total FROM pagos WHERE estado = 'confirmado'");
        $totalContributions = $stmt->fetch()['total'];
        $result['kpis']['totalContributions'] = $totalContributions;
        $result['debug_info']['total_contributions'] = $totalContributions;
    } else {
        $result['kpis']['totalContributions'] = 0;
    }
    
    // 5. Test de insumos
    if (in_array('insumos', $tables)) {
        $stmt = $pdo->query("SELECT COALESCE(SUM(cantidad_disponible * precio_unitario), 0) as total FROM insumos WHERE estado = 'disponible'");
        $inventoryValue = $stmt->fetch()['total'];
        $result['kpis']['inventoryValue'] = $inventoryValue;
        $result['debug_info']['inventory_value'] = $inventoryValue;
        
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM insumos WHERE estado = 'disponible' AND cantidad_disponible > 0");
        $availableItems = $stmt->fetch()['total'];
        $result['kpis']['availableItems'] = $availableItems;
    } else {
        $result['kpis']['inventoryValue'] = 0;
        $result['kpis']['availableItems'] = 0;
    }
    
    $result['kpis']['grossMargin'] = 0; // Simplificado
    
    echo json_encode($result, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'debug_info' => [
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ], JSON_PRETTY_PRINT);
}
?>
