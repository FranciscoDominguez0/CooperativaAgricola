<?php
// Archivo para debuggear los datos reales
header('Content-Type: application/json');

try {
    require_once 'php/conexion.php';
    
    $pdo = conectarDB();
    
    // Verificar tablas existentes
    $stmt = $pdo->query("SHOW TABLES");
    $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $result = [
        'success' => true,
        'database' => 'cooperativa_agricola',
        'existing_tables' => $existingTables,
        'data_check' => []
    ];
    
    // Verificar datos en cada tabla
    foreach ($existingTables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
            $count = $stmt->fetch()['count'];
            
            $result['data_check'][$table] = [
                'count' => $count,
                'status' => 'OK'
            ];
            
            // Si hay datos, mostrar algunos ejemplos
            if ($count > 0) {
                $stmt = $pdo->query("SELECT * FROM $table LIMIT 3");
                $samples = $stmt->fetchAll();
                $result['data_check'][$table]['samples'] = $samples;
            }
            
        } catch (Exception $e) {
            $result['data_check'][$table] = [
                'count' => 0,
                'status' => 'ERROR',
                'error' => $e->getMessage()
            ];
        }
    }
    
    // Probar consultas específicas de reportes
    $result['report_queries'] = [];
    
    // Test ventas
    if (in_array('ventas', $existingTables)) {
        try {
            $stmt = $pdo->query("SELECT SUM(total) as total_ventas FROM ventas WHERE estado = 'pagado'");
            $ventas = $stmt->fetch();
            $result['report_queries']['ventas'] = $ventas['total_ventas'];
        } catch (Exception $e) {
            $result['report_queries']['ventas_error'] = $e->getMessage();
        }
    }
    
    // Test pagos
    if (in_array('pagos', $existingTables)) {
        try {
            $stmt = $pdo->query("SELECT SUM(monto) as total_pagos FROM pagos WHERE estado = 'confirmado'");
            $pagos = $stmt->fetch();
            $result['report_queries']['pagos'] = $pagos['total_pagos'];
        } catch (Exception $e) {
            $result['report_queries']['pagos_error'] = $e->getMessage();
        }
    }
    
    // Test socios
    if (in_array('socios', $existingTables)) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as total_socios FROM socios WHERE estado = 'activo'");
            $socios = $stmt->fetch();
            $result['report_queries']['socios'] = $socios['total_socios'];
        } catch (Exception $e) {
            $result['report_queries']['socios_error'] = $e->getMessage();
        }
    }
    
    // Test insumos
    if (in_array('insumos', $existingTables)) {
        try {
            $stmt = $pdo->query("SELECT SUM(cantidad_disponible * precio_unitario) as total_inventario FROM insumos WHERE estado = 'disponible'");
            $insumos = $stmt->fetch();
            $result['report_queries']['insumos'] = $insumos['total_inventario'];
        } catch (Exception $e) {
            $result['report_queries']['insumos_error'] = $e->getMessage();
        }
    }
    
    echo json_encode($result, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>
