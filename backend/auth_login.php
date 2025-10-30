<?php
require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit();
}

try {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Correo y contraseña son obligatorios']);
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Formato de correo electrónico inválido']);
        exit();
    }

    $pdo = conectarDB();
    
    // Buscar usuario
    $stmt = $pdo->prepare("
        SELECT id_usuario, nombre, correo, contraseña, rol, estado 
        FROM usuarios 
        WHERE correo = ?
    ");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch();

    if (!$usuario) {
        echo json_encode(['success' => false, 'message' => 'Credenciales incorrectas']);
        exit();
    }

    // Verificar estado del usuario
    if ($usuario['estado'] !== 'activo') {
        echo json_encode(['success' => false, 'message' => 'Cuenta inactiva. Contacte al administrador']);
        exit();
    }

    // Verificar contraseña
    if (password_verify($password, $usuario['contraseña'])) {
        // Actualizar último acceso
        $stmt = $pdo->prepare("UPDATE usuarios SET ultimo_acceso = NOW() WHERE id_usuario = ?");
        $stmt->execute([$usuario['id_usuario']]);
        
        // Crear sesión
        $_SESSION['user_id'] = $usuario['id_usuario'];
        $_SESSION['user_name'] = $usuario['nombre'];
        $_SESSION['user_email'] = $usuario['correo'];
        $_SESSION['user_role'] = $usuario['rol'];
        $_SESSION['login_time'] = time();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Bienvenido/a ' . $usuario['nombre'],
            'user' => [
                'id' => $usuario['id_usuario'],
                'nombre' => $usuario['nombre'],
                'correo' => $usuario['correo'],
                'rol' => $usuario['rol']
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Credenciales incorrectas']);
    }

} catch (PDOException $e) {
    error_log("Error en login: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error del servidor. Intenta nuevamente.']);
} catch (Exception $e) {
    error_log("Error general en login: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error inesperado. Intenta nuevamente.']);
}
?>
