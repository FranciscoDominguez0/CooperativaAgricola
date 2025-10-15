<?php
// Archivo para probar diferentes configuraciones de MySQL
header('Content-Type: application/json');

$configs = [
    ['host' => 'localhost', 'user' => 'root', 'pass' => ''],
    ['host' => 'localhost', 'user' => 'root', 'pass' => 'root'],
    ['host' => 'localhost', 'user' => 'root', 'pass' => 'password'],
    ['host' => '127.0.0.1', 'user' => 'root', 'pass' => ''],
    ['host' => '127.0.0.1', 'user' => 'root', 'pass' => 'root'],
];

$result = ['success' => false, 'configs_tested' => []];

foreach ($configs as $i => $config) {
    try {
        $dsn = "mysql:host={$config['host']};charset=utf8mb4";
        $pdo = new PDO($dsn, $config['user'], $config['pass']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Probar si existe la base de datos
        $stmt = $pdo->query("SHOW DATABASES LIKE 'cooperativa_agricola'");
        $db_exists = $stmt->fetch();
        
        $result['configs_tested'][] = [
            'config' => $i + 1,
            'host' => $config['host'],
            'user' => $config['user'],
            'pass' => $config['pass'],
            'status' => 'SUCCESS',
            'database_exists' => $db_exists ? 'YES' : 'NO'
        ];
        
        if ($db_exists) {
            // Probar conexión a la base de datos específica
            $dsn_db = "mysql:host={$config['host']};dbname=cooperativa_agricola;charset=utf8mb4";
            $pdo_db = new PDO($dsn_db, $config['user'], $config['pass']);
            $pdo_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $stmt = $pdo_db->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $result['success'] = true;
            $result['working_config'] = [
                'host' => $config['host'],
                'user' => $config['user'],
                'pass' => $config['pass'],
                'database' => 'cooperativa_agricola',
                'tables' => $tables
            ];
            break;
        }
        
    } catch (PDOException $e) {
        $result['configs_tested'][] = [
            'config' => $i + 1,
            'host' => $config['host'],
            'user' => $config['user'],
            'pass' => $config['pass'],
            'status' => 'FAILED',
            'error' => $e->getMessage()
        ];
    }
}

echo json_encode($result, JSON_PRETTY_PRINT);
?>
