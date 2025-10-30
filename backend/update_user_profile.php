<?php
// Actualizar perfil del usuario
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
    
    // Obtener datos del POST
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
        exit;
    }
    
    $pdo = conectarDB();
    $userId = $_SESSION['user_id'];
    
    // Validar datos
    $nombre = trim($input['nombre'] ?? '');
    $correo = trim($input['email'] ?? '');
    
    if (empty($nombre) || empty($correo)) {
        echo json_encode(['success' => false, 'message' => 'Nombre y correo son requeridos']);
        exit;
    }
    
    // Validar formato de email
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Correo inválido']);
        exit;
    }
    
    // Verificar si el correo ya existe en otro usuario
    $stmt = $pdo->prepare("
        SELECT id_usuario FROM usuarios 
        WHERE correo = ? AND id_usuario != ? AND estado = 'activo'
    ");
    $stmt->execute([$correo, $userId]);
    
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'El correo ya está en uso']);
        exit;
    }
    
    // Actualizar datos del usuario
    $stmt = $pdo->prepare("
        UPDATE usuarios 
        SET 
            nombre = ?,
            correo = ?,
            updated_at = NOW()
        WHERE id_usuario = ? AND estado = 'activo'
    ");
    
    $result = $stmt->execute([$nombre, $correo, $userId]);
    
    if ($result) {
        // Actualizar datos en la sesión
        $_SESSION['user_name'] = $nombre;
        $_SESSION['user_email'] = $correo;
        
        echo json_encode([
            'success' => true,
            'message' => 'Perfil actualizado exitosamente'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error al actualizar el perfil'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error en update_user_profile.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error del servidor'
    ]);
}
?>
