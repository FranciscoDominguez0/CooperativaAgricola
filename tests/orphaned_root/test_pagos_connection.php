<?php
// Archivo de prueba para verificar la conexión a la base de datos de pagos
header('Content-Type: application/json');

try {
    require_once 'php/conexion.php';
    $pdo = conectarDB();
    
    // Verificar si la tabla pagos existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'pagos'");
    $tableExists = $stmt->fetch();
    
    if (!$tableExists) {
        echo json_encode([
            'success' => false,
            'message' => 'La tabla "pagos" no existe en la base de datos',
            'suggestion' => 'Ejecuta el archivo  para crear la tabla'
        ]);
        exit();
    }
    
    // Contar registros en la tabla pagos
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM pagos");
    $count = $stmt->fetch();
    
    // Obtener algunos registros de ejemplo
    $stmt = $pdo->query("SELECT * FROM pagos LIMIT 5");
    $pagos = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'message' => 'Conexión exitosa a la base de datos',
        'database' => 'pagos',
        'table_exists' => true,
        'total_records' => $count['total'],
        'sample_data' => $pagos
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error de conexión: ' . $e->getMessage(),
        'database' => 'pagos'
    ]);
}
?>



