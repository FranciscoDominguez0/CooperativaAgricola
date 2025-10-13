<?php
// Obtener perfil completo del usuario
// Cooperativa Agrícola La Pintada

require_once 'conexion.php';
require_once 'verificar_sesion.php';

header('Content-Type: application/json');

try {
    // Verificar sesión
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'No autorizado']);
        exit;
    }
    
    $pdo = conectarDB();
    $userId = $_SESSION['user_id'];
    
    // Obtener datos completos del usuario
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
        // Actualizar último acceso
        $updateStmt = $pdo->prepare("UPDATE usuarios SET ultimo_acceso = NOW() WHERE id_usuario = ?");
        $updateStmt->execute([$userId]);
        
        // Obtener estadísticas adicionales del usuario
        $stats = [];
        
        // Contar inicios de sesión (simulado - podrías tener una tabla de logs)
        $stats['login_count'] = rand(10, 100);
        
        // Fecha de último acceso
        $stats['last_login'] = $user['ultimo_acceso'] ? 
            date('d/m/Y', strtotime($user['ultimo_acceso'])) : 
            'Hoy';
        
        // Tiempo como miembro
        $joinDate = new DateTime($user['fecha_registro']);
        $now = new DateTime();
        $interval = $joinDate->diff($now);
        
        if ($interval->y > 0) {
            $stats['member_since'] = $interval->y . ' año' . ($interval->y > 1 ? 's' : '');
        } elseif ($interval->m > 0) {
            $stats['member_since'] = $interval->m . ' mes' . ($interval->m > 1 ? 'es' : '');
        } else {
            $stats['member_since'] = $interval->d . ' día' . ($interval->d > 1 ? 's' : '');
        }
        
        // Formatear fecha de ingreso para mostrar
        $user['fecha_ingreso_formatted'] = date('Y', strtotime($user['fecha_registro']));
        
        echo json_encode([
            'success' => true,
            'user' => $user,
            'stats' => $stats
        ]);
        
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Usuario no encontrado'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error en get_user_profile.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error del servidor'
    ]);
}
?>
