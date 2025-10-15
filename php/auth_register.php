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
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';

    // Validaciones
    if (empty($name) || empty($email) || empty($role) || empty($password) || empty($confirmPassword)) {
        echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios']);
        exit();
    }

    if (strlen($name) < 2) {
        echo json_encode(['success' => false, 'message' => 'El nombre debe tener al menos 2 caracteres']);
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Formato de correo electrónico inválido']);
        exit();
    }

    if (strlen($password) < 6) {
        echo json_encode(['success' => false, 'message' => 'La contraseña debe tener al menos 6 caracteres']);
        exit();
    }

    if ($password !== $confirmPassword) {
        echo json_encode(['success' => false, 'message' => 'Las contraseñas no coinciden']);
        exit();
    }

    // Validar rol
    $rolesValidos = ['productor', 'cliente', 'contador'];
    if (!in_array($role, $rolesValidos)) {
        echo json_encode(['success' => false, 'message' => 'Rol de usuario inválido']);
        exit();
    }

    $pdo = conectarDB();
    
    // Verificar si el correo ya existe
    $stmt = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE correo = ?");
    $stmt->execute([$email]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'El correo electrónico ya está registrado']);
        exit();
    }

    // Encriptar contraseña
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    
    // Insertar usuario
    $stmt = $pdo->prepare("
        INSERT INTO usuarios (nombre, correo, contraseña, rol, estado, fecha_registro) 
        VALUES (?, ?, ?, ?, 'activo', CURDATE())
    ");
    
    $result = $stmt->execute([$name, $email, $passwordHash, $role]);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Usuario registrado exitosamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al registrar usuario']);
    }

} catch (PDOException $e) {
    error_log("Error en registro: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error del servidor. Intenta nuevamente.']);
} catch (Exception $e) {
    error_log("Error general en registro: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error inesperado. Intenta nuevamente.']);
}
?>
