<?php
// Configuración de la base de datos
$host = 'localhost';
$dbname = 'cooperativa_agricola';
$username = 'root';
$password = '12345678'; // Cambiar si tienes contraseña en MySQL

$pdo = null;

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Log del error pero no detener la ejecución
    error_log("Error de conexión a la base de datos: " . $e->getMessage());
    $pdo = null;
}

// Función para conectar a la base de datos
function conectarDB() {
    global $pdo;
    if ($pdo === null) {
        throw new Exception("No se pudo conectar a la base de datos 'cooperativa_agricola'");
    }
    return $pdo;
}
?>
