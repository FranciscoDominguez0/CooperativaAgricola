<?php
require_once 'auth.php';

header('Content-Type: application/json');

$auth = new Auth();
$usuario = $auth->getUsuarioActual();

if ($usuario) {
    echo json_encode([
        'authenticated' => true,
        'user' => [
            'id' => $usuario['id_usuario'],
            'nombre' => $usuario['nombre'],
            'correo' => $usuario['correo'],
            'rol' => $usuario['rol'],
            'estado' => $usuario['estado']
        ]
    ]);
} else {
    echo json_encode([
        'authenticated' => false,
        'message' => 'No hay sesión activa'
    ]);
}
?>