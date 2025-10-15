<?php
require_once 'auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit();
}

$auth = new Auth();

$nombre = trim($_POST['nombre'] ?? '');
$correo = trim($_POST['correo'] ?? '');
$contraseña = $_POST['contraseña'] ?? '';
$confirmar_contraseña = $_POST['confirmar_contraseña'] ?? '';
$rol = $_POST['rol'] ?? 'productor';

// Validar que las contraseñas coincidan
if ($contraseña !== $confirmar_contraseña) {
    echo json_encode(['success' => false, 'message' => 'Las contraseñas no coinciden']);
    exit();
}

$resultado = $auth->registrar($nombre, $correo, $contraseña, $rol);

echo json_encode($resultado);
?>