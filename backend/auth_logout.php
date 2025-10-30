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
    // Destruir la sesión
    session_destroy();
    
    // Limpiar cookies de sesión
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    echo json_encode(['success' => true, 'message' => 'Sesión cerrada exitosamente']);
    
} catch (Exception $e) {
    error_log("Error en logout: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al cerrar sesión']);
}
?>
