<?php
// Archivo de prueba para reportes sin verificación de sesión
header('Content-Type: application/json');

try {
    require_once 'php/conexion.php';
    
    // Obtener parámetros de filtro
    $dateFrom = $_GET['dateFrom'] ?? date('Y-m-01');
    $dateTo = $_GET['dateTo'] ?? date('Y-m-t');
    $action = $_GET['action'] ?? 'kpis';
    
    $pdo = conectarDB();
    
    // Verificar tablas existentes
    $stmt = $pdo->query("SHOW TABLES");
    $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $result = [
        'success' => true,
        'database' => 'cooperativa_agricola',
        'existing_tables' => $existingTables,
        'date_from' => $dateFrom,
        'date_to' => $dateTo
    ];
    
    if ($action === 'kpis') {
        $kpis = [
            'totalIncome' => 0,
            'incomeChange' => 0,
            'totalContributions' => 0,
            'activeMembers' => 0,
            'inventoryValue' => 0,
            'availableItems' => 0,
            'grossMargin' => 0
        ];
        
        // Total de ingresos del período
        if (in_array('ventas', $existingTables)) {
            try {
                $stmt = $pdo->prepare("
                    SELECT COALESCE(SUM(total), 0) as total_income 
                    FROM ventas 
                    WHERE fecha_venta >= ? AND fecha_venta <= ? AND estado = 'pagado'
                ");
                $stmt->execute([$dateFrom, $dateTo]);
                $kpis['totalIncome'] = $stmt->fetch()['total_income'];
                
                // Cambio porcentual vs período anterior
                $periodLength = (strtotime($dateTo) - strtotime($dateFrom)) / (60 * 60 * 24);
                $previousStart = date('Y-m-d', strtotime($dateFrom) - $periodLength);
                $previousEnd = date('Y-m-d', strtotime($dateFrom) - 1);
                
                $stmt = $pdo->prepare("
                    SELECT COALESCE(SUM(total), 0) as previous_income 
                    FROM ventas 
                    WHERE fecha_venta >= ? AND fecha_venta <= ? AND estado = 'pagado'
                ");
                $stmt->execute([$previousStart, $previousEnd]);
                $previousIncome = $stmt->fetch()['previous_income'];
                
                $kpis['incomeChange'] = $previousIncome > 0 ? 
                    (($kpis['totalIncome'] - $previousIncome) / $previousIncome) * 100 : 0;
                    
                $result['ventas_debug'] = [
                    'current_period' => $kpis['totalIncome'],
                    'previous_period' => $previousIncome,
                    'change' => $kpis['incomeChange']
                ];
            } catch (Exception $e) {
                $result['ventas_error'] = $e->getMessage();
            }
        }
        
        // Aportes recaudados
        if (in_array('pagos', $existingTables)) {
            try {
                $stmt = $pdo->prepare("
                    SELECT COALESCE(SUM(monto), 0) as total_contributions 
                    FROM pagos 
                    WHERE fecha_pago >= ? AND fecha_pago <= ? 
                    AND tipo IN ('aporte_mensual', 'aporte_extraordinario') 
                    AND estado = 'confirmado'
                ");
                $stmt->execute([$dateFrom, $dateTo]);
                $kpis['totalContributions'] = $stmt->fetch()['total_contributions'];
                
                $result['pagos_debug'] = [
                    'total_contributions' => $kpis['totalContributions']
                ];
            } catch (Exception $e) {
                $result['pagos_error'] = $e->getMessage();
            }
        }
        
        // Miembros activos
        if (in_array('socios', $existingTables)) {
            try {
                $stmt = $pdo->prepare("
                    SELECT COUNT(DISTINCT s.id_socio) as active_members 
                    FROM socios s
                    WHERE s.estado = 'activo'
                ");
                $stmt->execute();
                $kpis['activeMembers'] = $stmt->fetch()['active_members'];
                
                $result['socios_debug'] = [
                    'active_members' => $kpis['activeMembers']
                ];
            } catch (Exception $e) {
                $result['socios_error'] = $e->getMessage();
            }
        }
        
        // Valor de inventario
        if (in_array('insumos', $existingTables)) {
            try {
                $stmt = $pdo->prepare("
                    SELECT COALESCE(SUM(cantidad_disponible * precio_unitario), 0) as inventory_value 
                    FROM insumos 
                    WHERE estado = 'disponible' AND cantidad_disponible > 0
                ");
                $stmt->execute();
                $kpis['inventoryValue'] = $stmt->fetch()['inventory_value'];
                
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) as available_items 
                    FROM insumos 
                    WHERE estado = 'disponible' AND cantidad_disponible > 0
                ");
                $stmt->execute();
                $kpis['availableItems'] = $stmt->fetch()['available_items'];
                
                $result['insumos_debug'] = [
                    'inventory_value' => $kpis['inventoryValue'],
                    'available_items' => $kpis['availableItems']
                ];
            } catch (Exception $e) {
                $result['insumos_error'] = $e->getMessage();
            }
        }
        
        $result['kpis'] = $kpis;
    }
    
    echo json_encode($result, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>
