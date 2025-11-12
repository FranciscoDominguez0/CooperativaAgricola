<?php
require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $pdo = conectarDB();
        
        // Verificar si se solicita un usuario específico
        if (isset($_GET['id_usuario'])) {
            // Obtener un usuario específico
            $id_usuario = (int)$_GET['id_usuario'];
            $stmt = $pdo->prepare("SELECT id_usuario, nombre, correo, rol, estado, fecha_registro FROM usuarios WHERE id_usuario = ?");
            $stmt->execute([$id_usuario]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($usuario) {
                echo json_encode([
                    'success' => true,
                    'data' => $usuario
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ]);
            }
        } else {
            // Obtener lista de usuarios con paginación y búsqueda
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $search = isset($_GET['search']) ? trim($_GET['search']) : '';
            $limit = 10;
            $offset = ($page - 1) * $limit;
            
            // Construir consulta con búsqueda
            $whereClause = '';
            $params = [];
            
            if (!empty($search)) {
                $whereClause = "WHERE nombre LIKE ? OR correo LIKE ? OR rol LIKE ?";
                $searchTerm = "%$search%";
                $params = [$searchTerm, $searchTerm, $searchTerm];
            }
            
            // Contar total de registros
            $countQuery = "SELECT COUNT(*) as total FROM usuarios $whereClause";
            $countStmt = $pdo->prepare($countQuery);
            $countStmt->execute($params);
            $totalRecords = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            $totalPages = ceil($totalRecords / $limit);
            
            // Obtener usuarios
            $query = "SELECT id_usuario, nombre, correo, rol, estado, fecha_registro 
                      FROM usuarios $whereClause 
                      ORDER BY fecha_registro DESC 
                      LIMIT $limit OFFSET $offset";
            
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $usuarios,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => $totalPages,
                    'total_records' => $totalRecords,
                    'limit' => $limit
                ]
            ]);
        }
        
    } catch (PDOException $e) {
        error_log("Error en GET usuarios: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error al cargar usuarios']);
    }
}

elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Crear nuevo usuario
    try {
        $pdo = conectarDB();
        
        $nombre = trim($_POST['nombre'] ?? '');
        $correo = trim($_POST['correo'] ?? '');
        $rol = $_POST['rol'] ?? '';
        $estado = $_POST['estado'] ?? 'activo';
        $contraseña = $_POST['contraseña'] ?? '';
        $confirmar_contraseña = $_POST['confirmar_contraseña'] ?? '';
        
        // Validaciones
        if (empty($nombre) || empty($correo) || empty($rol) || empty($contraseña)) {
            echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios']);
            exit();
        }
        
        if (strlen($nombre) < 2) {
            echo json_encode(['success' => false, 'message' => 'El nombre debe tener al menos 2 caracteres']);
            exit();
        }
        
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Formato de correo electrónico inválido']);
            exit();
        }
        
        if (strlen($contraseña) < 6) {
            echo json_encode(['success' => false, 'message' => 'La contraseña debe tener al menos 6 caracteres']);
            exit();
        }
        
        if ($contraseña !== $confirmar_contraseña) {
            echo json_encode(['success' => false, 'message' => 'Las contraseñas no coinciden']);
            exit();
        }
        
        // Validar rol
        $rolesValidos = ['admin', 'productor', 'cliente', 'contador'];
        if (!in_array($rol, $rolesValidos)) {
            echo json_encode(['success' => false, 'message' => 'Rol de usuario inválido']);
            exit();
        }
        
        // Verificar si el correo ya existe
        $checkStmt = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE correo = ?");
        $checkStmt->execute([$correo]);
        if ($checkStmt->rowCount() > 0) {
            echo json_encode(['success' => false, 'message' => 'El correo electrónico ya está registrado']);
            exit();
        }
        
        // Encriptar contraseña
        $passwordHash = password_hash($contraseña, PASSWORD_DEFAULT);
        
        // Insertar usuario
        $stmt = $pdo->prepare("
            INSERT INTO usuarios (nombre, correo, contraseña, rol, estado, fecha_registro) 
            VALUES (?, ?, ?, ?, ?, CURDATE())
        ");
        
        $result = $stmt->execute([$nombre, $correo, $passwordHash, $rol, $estado]);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Usuario creado exitosamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al crear usuario']);
        }
        
    } catch (PDOException $e) {
        error_log("Error en POST usuarios: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error del servidor al crear usuario']);
    }
}

elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    // Actualizar usuario existente
    try {
        $pdo = conectarDB();
        
        // Obtener datos del PUT request
        parse_str(file_get_contents('php://input'), $putData);
        
        $id_usuario = $putData['id_usuario'] ?? '';
        $nombre = trim($putData['nombre'] ?? '');
        $correo = trim($putData['correo'] ?? '');
        $rol = $putData['rol'] ?? '';
        $estado = $putData['estado'] ?? 'activo';
        $contraseña = $putData['contraseña'] ?? '';
        $confirmar_contraseña = $putData['confirmar_contraseña'] ?? '';
        
        // Validaciones
        if (empty($id_usuario) || empty($nombre) || empty($correo) || empty($rol)) {
            echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios']);
            exit();
        }
        
        if (strlen($nombre) < 2) {
            echo json_encode(['success' => false, 'message' => 'El nombre debe tener al menos 2 caracteres']);
            exit();
        }
        
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Formato de correo electrónico inválido']);
            exit();
        }
        
        // Validar rol
        $rolesValidos = ['admin', 'productor', 'cliente', 'contador'];
        if (!in_array($rol, $rolesValidos)) {
            echo json_encode(['success' => false, 'message' => 'Rol de usuario inválido']);
            exit();
        }
        
        // Verificar si el usuario existe
        $checkStmt = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE id_usuario = ?");
        $checkStmt->execute([$id_usuario]);
        if ($checkStmt->rowCount() === 0) {
            echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
            exit();
        }
        
        // Verificar si el correo ya existe en otro usuario
        $emailCheckStmt = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE correo = ? AND id_usuario != ?");
        $emailCheckStmt->execute([$correo, $id_usuario]);
        if ($emailCheckStmt->rowCount() > 0) {
            echo json_encode(['success' => false, 'message' => 'El correo electrónico ya está registrado por otro usuario']);
            exit();
        }
        
        // Si se proporciona contraseña, validarla
        if (!empty($contraseña)) {
            if (strlen($contraseña) < 6) {
                echo json_encode(['success' => false, 'message' => 'La contraseña debe tener al menos 6 caracteres']);
                exit();
            }
            
            if ($contraseña !== $confirmar_contraseña) {
                echo json_encode(['success' => false, 'message' => 'Las contraseñas no coinciden']);
                exit();
            }
            
            // Encriptar nueva contraseña
            $passwordHash = password_hash($contraseña, PASSWORD_DEFAULT);
            
            // Actualizar con nueva contraseña
            $stmt = $pdo->prepare("
                UPDATE usuarios 
                SET nombre = ?, correo = ?, rol = ?, estado = ?, contraseña = ?
                WHERE id_usuario = ?
            ");
            $result = $stmt->execute([$nombre, $correo, $rol, $estado, $passwordHash, $id_usuario]);
        } else {
            // Actualizar sin cambiar contraseña
            $stmt = $pdo->prepare("
                UPDATE usuarios 
                SET nombre = ?, correo = ?, rol = ?, estado = ?
                WHERE id_usuario = ?
            ");
            $result = $stmt->execute([$nombre, $correo, $rol, $estado, $id_usuario]);
        }
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Usuario actualizado exitosamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar usuario']);
        }
        
    } catch (PDOException $e) {
        error_log("Error en PUT usuarios: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error del servidor al actualizar usuario']);
    }
}

elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    // Eliminar usuario
    try {
        $pdo = conectarDB();
        
        // Intentar obtener datos de URL-encoded primero (como socios.php)
        parse_str(file_get_contents('php://input'), $data);
        $id_usuario = $data['id_usuario'] ?? '';
        
        // Si no se encontró en URL-encoded, intentar JSON
        if (empty($id_usuario)) {
            $input = json_decode(file_get_contents('php://input'), true);
            $id_usuario = $input['id_usuario'] ?? '';
        }
        
        if (empty($id_usuario)) {
            echo json_encode(['success' => false, 'message' => 'ID de usuario requerido']);
            exit();
        }
        
        // Verificar si el usuario existe
        $checkStmt = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE id_usuario = ?");
        $checkStmt->execute([$id_usuario]);
        if ($checkStmt->rowCount() === 0) {
            echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
            exit();
        }
        
        // Eliminar usuario
        $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id_usuario = ?");
        $result = $stmt->execute([$id_usuario]);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Usuario eliminado exitosamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al eliminar usuario']);
        }
        
    } catch (PDOException $e) {
        error_log("Error en DELETE usuarios: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error del servidor al eliminar usuario']);
    }
}

else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}
?>
