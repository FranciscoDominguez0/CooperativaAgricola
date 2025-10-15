<?php
header('Content-Type: application/json');

require_once 'conexion.php';

try {
    $pdo = conectarDB();
    
    // Probar conexión
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM ventas");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'message' => 'Conexión exitosa',
        'total_ventas' => $result['total']
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
