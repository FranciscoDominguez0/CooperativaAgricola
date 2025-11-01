<?php
// Archivo para probar la conexión a la base de datos
header('Content-Type: application/json');

try {
    // Configuración de la base de datos
    $host = 'localhost';
    $dbname = 'cooperativa_agricola';
    $username = 'root';
    $password = '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Verificar tablas
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $result = [
        'success' => true,
        'message' => 'Conexión exitosa',
        'database' => $dbname,
        'tables' => $tables,
        'table_count' => count($tables)
    ];
    
    // Probar algunas consultas básicas
    if (in_array('socios', $tables)) {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM socios");
        $result['socios_count'] = $stmt->fetch()['count'];
    }
    
    if (in_array('ventas', $tables)) {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM ventas");
        $result['ventas_count'] = $stmt->fetch()['count'];
    }
    
    if (in_array('pagos', $tables)) {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM pagos");
        $result['pagos_count'] = $stmt->fetch()['count'];
    }
    
    if (in_array('insumos', $tables)) {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM insumos");
        $result['insumos_count'] = $stmt->fetch()['count'];
    }
    
    echo json_encode($result, JSON_PRETTY_PRINT);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error de conexión: ' . $e->getMessage(),
        'database' => $dbname,
        'host' => $host
    ], JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error general: ' . $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>