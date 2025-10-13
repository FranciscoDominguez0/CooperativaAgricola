<?php
// Archivo para probar los datos de gráficos
header('Content-Type: application/json');

try {
    require_once 'php/conexion.php';
    
    $pdo = conectarDB();
    
    // Datos para evolución financiera mensual
    $monthlyFinancial = [
        'labels' => [],
        'sales' => [],
        'contributions' => [],
        'expenses' => []
    ];
    
    // Generar últimos 6 meses
    for ($i = 5; $i >= 0; $i--) {
        $date = date('Y-m-01', strtotime("-$i month"));
        $nextDate = date('Y-m-01', strtotime("-$i month +1 month"));
        $monthlyFinancial['labels'][] = date('M', strtotime($date));
        
        // Ventas del mes
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(total), 0) as sales 
            FROM ventas 
            WHERE fecha_venta >= ? AND fecha_venta < ? AND estado = 'pagado'
        ");
        $stmt->execute([$date, $nextDate]);
        $monthlyFinancial['sales'][] = $stmt->fetch()['sales'];
        
        // Aportes del mes
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(monto), 0) as contributions 
            FROM pagos 
            WHERE fecha_pago >= ? AND fecha_pago < ? 
            AND tipo IN ('aporte_mensual', 'aporte_extraordinario') 
            AND estado = 'confirmado'
        ");
        $stmt->execute([$date, $nextDate]);
        $monthlyFinancial['contributions'][] = $stmt->fetch()['contributions'];
        
        // Gastos del mes (estimados)
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(mi.cantidad * i.precio_unitario), 0) as expenses 
            FROM movimientos_inventario mi
            JOIN insumos i ON mi.id_insumo = i.id_insumo
            WHERE mi.fecha_movimiento >= ? AND mi.fecha_movimiento < ?
            AND mi.tipo_movimiento = 'salida'
        ");
        $stmt->execute([$date, $nextDate]);
        $monthlyFinancial['expenses'][] = $stmt->fetch()['expenses'];
    }
    
    // Datos para inventario por tipo
    $inventoryType = [
        'labels' => [],
        'values' => []
    ];
    
    $stmt = $pdo->prepare("
        SELECT tipo, SUM(cantidad_disponible * precio_unitario) as value
        FROM insumos 
        WHERE estado = 'disponible' AND cantidad_disponible > 0
        GROUP BY tipo
        ORDER BY value DESC
        LIMIT 6
    ");
    $stmt->execute();
    $inventoryResults = $stmt->fetchAll();
    
    foreach ($inventoryResults as $row) {
        $inventoryType['labels'][] = ucfirst($row['tipo']);
        $inventoryType['values'][] = $row['value'];
    }
    
    $result = [
        'success' => true,
        'charts' => [
            'monthlyFinancial' => $monthlyFinancial,
            'inventoryType' => $inventoryType
        ]
    ];
    
    echo json_encode($result, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>

