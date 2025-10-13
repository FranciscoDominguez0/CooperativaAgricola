<?php
// Configuración de la base de datos - Versión optimizada pero compatible
$host = 'localhost';
$dbname = 'cooperativa_agricola';
$username = 'root';
$password = '12345678';

$pdo = null;

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_PERSISTENT, false); // No mantener conexiones persistentes
} catch (PDOException $e) {
    error_log("Error de conexión a la base de datos: " . $e->getMessage());
    $pdo = null;
}

// Función para conectar a la base de datos (compatible con el código existente)
function conectarDB() {
    global $pdo;
    if ($pdo === null) {
        throw new Exception("No se pudo conectar a la base de datos 'cooperativa_agricola'");
    }
    return $pdo;
}

// Función para cerrar conexiones (optimización)
function cerrarConexion() {
    global $pdo;
    $pdo = null;
}

// Cerrar conexión al finalizar script (solo si es necesario)
// register_shutdown_function('cerrarConexion');
?>
