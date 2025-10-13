<?php
// Archivo de debug para verificar la conexión y datos de pagos
header('Content-Type: application/json');

try {
    // Incluir la conexión
    require_once 'php/conexion.php';
    
    // Intentar conectar
    $pdo = conectarDB();
    
    // Verificar si la conexión es exitosa
    if (!$pdo) {
        throw new Exception("No se pudo conectar a la base de datos");
    }
    
    // Verificar si la tabla existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'pagos'");
    $tableExists = $stmt->fetch();
    
    if (!$tableExists) {
        echo json_encode([
            'success' => false,
            'error' => 'La tabla "pagos" no existe',
            'suggestion' => 'Ejecuta el archivo create_pagos_table.sql en tu base de datos MySQL'
        ]);
        exit();
    }
    
    // Contar registros
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM pagos");
    $count = $stmt->fetch();
    
    // Obtener algunos registros
    $stmt = $pdo->query("SELECT * FROM pagos LIMIT 3");
    $pagos = $stmt->fetchAll();
    
    // Probar las estadísticas
    $stats = [];
    
    // Ingresos totales
    $stmt = $pdo->query("SELECT SUM(monto) as total FROM pagos WHERE estado = 'confirmado'");
    $totalData = $stmt->fetch();
    $stats['ingresos_totales'] = $totalData ? $totalData['total'] : '0';
    
    // Pagos pendientes
    $stmt = $pdo->query("SELECT COUNT(*) as pendientes FROM pagos WHERE estado = 'pendiente'");
    $pendientesData = $stmt->fetch();
    $stats['pagos_pendientes'] = $pendientesData ? $pendientesData['pendientes'] : '0';
    
    // Pagos confirmados
    $stmt = $pdo->query("SELECT COUNT(*) as confirmados FROM pagos WHERE estado = 'confirmado'");
    $confirmadosData = $stmt->fetch();
    $stats['pagos_confirmados'] = $confirmadosData ? $confirmadosData['confirmados'] : '0';
    
    // Aportes mensuales
    $stmt = $pdo->query("SELECT COUNT(*) as aportes FROM pagos WHERE tipo = 'aporte_mensual' AND estado = 'confirmado'");
    $aportesData = $stmt->fetch();
    $stats['aportes_mensuales'] = $aportesData ? $aportesData['aportes'] : '0';
    
    echo json_encode([
        'success' => true,
        'message' => 'Conexión exitosa',
        'database' => 'pagos',
        'table_exists' => true,
        'total_records' => $count['total'],
        'sample_data' => $pagos,
        'statistics' => $stats
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>


