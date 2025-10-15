<?php
// Archivo de prueba para verificar la consulta de perfil de usuario
// Cooperativa Agrícola La Pintada

require_once 'php/conexion.php';

try {
    $pdo = conectarDB();
    
    // Simular un usuario ID (en producción vendría de la sesión)
    $userId = 1; // Cambia este ID por uno que exista en tu base de datos
    
    // Consulta para obtener datos del usuario
    $stmt = $pdo->prepare("
        SELECT 
            id_usuario,
            nombre,
            correo,
            rol,
            estado,
            fecha_registro,
            ultimo_acceso,
            created_at,
            updated_at
        FROM usuarios 
        WHERE id_usuario = ? AND estado = 'activo'
    ");
    
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "<h2>Datos del Usuario Encontrado:</h2>";
        echo "<pre>";
        print_r($user);
        echo "</pre>";
        
        // Calcular tiempo como miembro
        $joinDate = new DateTime($user['fecha_registro']);
        $now = new DateTime();
        $interval = $joinDate->diff($now);
        
        echo "<h3>Información Calculada:</h3>";
        echo "<p><strong>Fecha de registro:</strong> " . $user['fecha_registro'] . "</p>";
        echo "<p><strong>Último acceso:</strong> " . ($user['ultimo_acceso'] ?: 'Nunca') . "</p>";
        
        if ($interval->y > 0) {
            echo "<p><strong>Tiempo como miembro:</strong> " . $interval->y . " año" . ($interval->y > 1 ? 's' : '') . "</p>";
        } elseif ($interval->m > 0) {
            echo "<p><strong>Tiempo como miembro:</strong> " . $interval->m . " mes" . ($interval->m > 1 ? 'es' : '') . "</p>";
        } else {
            echo "<p><strong>Tiempo como miembro:</strong> " . $interval->d . " día" . ($interval->d > 1 ? 's' : '') . "</p>";
        }
        
    } else {
        echo "<h2>No se encontró usuario con ID: $userId</h2>";
        echo "<p>Verifica que el ID exista en tu base de datos.</p>";
        
        // Mostrar todos los usuarios disponibles
        $stmt = $pdo->query("SELECT id_usuario, nombre, correo, rol FROM usuarios LIMIT 10");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Usuarios disponibles en la base de datos:</h3>";
        echo "<pre>";
        print_r($users);
        echo "</pre>";
    }
    
} catch (Exception $e) {
    echo "<h2>Error:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
