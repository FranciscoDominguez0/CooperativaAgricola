<?php
require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit();
}

try {
    // Verificar que el usuario esté logueado
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'No autorizado. Debes iniciar sesión.']);
        exit();
    }
    
    // Obtener datos del POST
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        // Si no viene JSON, intentar con POST normal
        $currentPassword = $_POST['currentPassword'] ?? '';
        $newPassword = $_POST['newPassword'] ?? '';
    } else {
        $currentPassword = $input['currentPassword'] ?? '';
        $newPassword = $input['newPassword'] ?? '';
    }
    
    if (empty($currentPassword) || empty($newPassword)) {
        echo json_encode(['success' => false, 'message' => 'Todos los campos son requeridos']);
        exit();
    }
    
    // Validar longitud de nueva contraseña
    if (strlen($newPassword) < 8) {
        echo json_encode(['success' => false, 'message' => 'La nueva contraseña debe tener al menos 8 caracteres']);
        exit();
    }
    
    $pdo = conectarDB();
    $userId = $_SESSION['user_id'];
    
    // Obtener contraseña actual del usuario
    $stmt = $pdo->prepare("SELECT contraseña FROM usuarios WHERE id_usuario = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
        exit();
    }
    
    // Verificar contraseña actual
    if (!password_verify($currentPassword, $user['contraseña'])) {
        echo json_encode(['success' => false, 'message' => 'La contraseña actual es incorrecta']);
        exit();
    }
    
    // Verificar que la nueva contraseña sea diferente
    if (password_verify($newPassword, $user['contraseña'])) {
        echo json_encode(['success' => false, 'message' => 'La nueva contraseña debe ser diferente a la actual']);
        exit();
    }
    
    // Encriptar nueva contraseña
    $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
    
    // Actualizar contraseña en la base de datos
    $stmt = $pdo->prepare("UPDATE usuarios SET contraseña = ? WHERE id_usuario = ?");
    $result = $stmt->execute([$passwordHash, $userId]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Contraseña cambiada exitosamente'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error al actualizar la contraseña'
        ]);
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error de base de datos: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>

