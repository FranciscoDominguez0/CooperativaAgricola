<?php
// Configuración alternativa de conexión
// Si la conexión principal no funciona, prueba con esta configuración

// Configuración 1: Base de datos 'pagos'
$config1 = [
    'host' => 'localhost',
    'dbname' => 'pagos',
    'username' => 'root',
    'password' => ''
];

// Configuración 2: Base de datos 'cooperativa_agricola' (si ya existe)
$config2 = [
    'host' => 'localhost',
    'dbname' => 'cooperativa_agricola',
    'username' => 'root',
    'password' => ''
];

// Configuración 3: Base de datos 'mysql' (por defecto)
$config3 = [
    'host' => 'localhost',
    'dbname' => 'mysql',
    'username' => 'root',
    'password' => ''
];

// Función para probar diferentes configuraciones
function probarConexiones() {
    global $config1, $config2, $config3;
    
    $configs = [$config1, $config2, $config3];
    $nombres = ['pagos', 'cooperativa_agricola', 'mysql'];
    
    foreach ($configs as $index => $config) {
        try {
            $pdo = new PDO(
                "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4", 
                $config['username'], 
                $config['password']
            );
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            echo "✅ Conexión exitosa a: {$nombres[$index]}<br>";
            return $pdo;
            
        } catch (PDOException $e) {
            echo "❌ Error con {$nombres[$index]}: " . $e->getMessage() . "<br>";
        }
    }
    
    return null;
}

// Función principal de conexión
function conectarDB() {
    // Primero intentar con la configuración principal
    try {
        $host = 'localhost';
        $dbname = 'pagos';
        $username = 'root';
        $password = '';
        
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        
        return $pdo;
        
    } catch (PDOException $e) {
        // Si falla, intentar crear la base de datos
        try {
            $pdo = new PDO("mysql:host=localhost;charset=utf8mb4", 'root', '');
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Crear la base de datos si no existe
            $pdo->exec("CREATE DATABASE IF NOT EXISTS pagos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE pagos");
            
            return $pdo;
            
        } catch (PDOException $e2) {
            throw new Exception("No se pudo conectar a MySQL. Error: " . $e2->getMessage());
        }
    }
}
?>

