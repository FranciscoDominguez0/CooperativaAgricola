<?php
require_once 'php/conexion.php';

try {
    $pdo = conectarDB();
    
    echo "=== ESTRUCTURA DE LA TABLA USUARIOS ===\n";
    $stmt = $pdo->query('DESCRIBE usuarios');
    while($row = $stmt->fetch()) {
        echo $row['Field'] . ' - ' . $row['Type'] . "\n";
    }
    
    echo "\n=== DATOS DE LA TABLA USUARIOS ===\n";
    $stmt = $pdo->query('SELECT * FROM usuarios LIMIT 3');
    while($row = $stmt->fetch()) {
        echo "ID: " . $row['id'] . " - Nombre: " . $row['nombre'] . " - Email: " . $row['email'] . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
