<?php
// Backend simplificado para el módulo de Gestión de Usuarios
// Cooperativa Agrícola La Pintada

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // Incluir conexión
    require_once 'conexion.php';
    
    $action = $_GET['action'] ?? 'estadisticas';
    
    $pdo = conectarDB();
    
    switch ($action) {
        case 'estadisticas':
            echo json_encode(obtenerEstadisticas($pdo));
            break;
            
        case 'lista':
            echo json_encode(obtenerListaUsuarios($pdo));
            break;
            
        case 'crear':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $data = json_decode(file_get_contents('php://input'), true);
                echo json_encode(crearUsuario($pdo, $data));
            } else {
                echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

function obtenerEstadisticas($pdo) {
    try {
        // Verificar si la tabla existe
        $stmt = $pdo->query("SHOW TABLES LIKE 'usuarios'");
        if (!$stmt->fetch()) {
            // Crear tabla si no existe
            crearTablaUsuarios($pdo);
        }
        
        $estadisticas = [];
        
        // Total de usuarios
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios");
        $estadisticas['total_usuarios'] = $stmt->fetch()['total'];
        
        // Usuarios activos
        $stmt = $pdo->query("SELECT COUNT(*) as activos FROM usuarios WHERE estado = 'activo'");
        $estadisticas['usuarios_activos'] = $stmt->fetch()['activos'];
        
        // Administradores
        $stmt = $pdo->query("SELECT COUNT(*) as administradores FROM usuarios WHERE rol = 'administrador' AND estado = 'activo'");
        $estadisticas['administradores'] = $stmt->fetch()['administradores'];
        
        // Productores
        $stmt = $pdo->query("SELECT COUNT(*) as productores FROM usuarios WHERE rol = 'productor' AND estado = 'activo'");
        $estadisticas['productores'] = $stmt->fetch()['productores'];
        
        // Clientes
        $stmt = $pdo->query("SELECT COUNT(*) as clientes FROM usuarios WHERE rol = 'cliente' AND estado = 'activo'");
        $estadisticas['clientes'] = $stmt->fetch()['clientes'];
        
        // Contadores
        $stmt = $pdo->query("SELECT COUNT(*) as contadores FROM usuarios WHERE rol = 'contador' AND estado = 'activo'");
        $estadisticas['contadores'] = $stmt->fetch()['contadores'];
        
        return [
            'success' => true,
            'estadisticas' => $estadisticas,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Error obteniendo estadísticas: ' . $e->getMessage()
        ];
    }
}

function obtenerListaUsuarios($pdo) {
    try {
        // Verificar si la tabla existe
        $stmt = $pdo->query("SHOW TABLES LIKE 'usuarios'");
        if (!$stmt->fetch()) {
            // Crear tabla si no existe
            crearTablaUsuarios($pdo);
        }
        
        $stmt = $pdo->query("
            SELECT 
                id_usuario,
                nombre,
                apellido,
                email,
                telefono,
                username,
                rol,
                estado,
                fecha_registro,
                ultimo_acceso
            FROM usuarios 
            ORDER BY fecha_registro DESC
        ");
        
        $usuarios = $stmt->fetchAll();
        
        return [
            'success' => true,
            'usuarios' => $usuarios,
            'total' => count($usuarios)
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Error obteniendo lista de usuarios: ' . $e->getMessage()
        ];
    }
}

function crearUsuario($pdo, $data) {
    try {
        // Validar datos requeridos
        $camposRequeridos = ['nombre', 'apellido', 'email', 'username', 'password', 'rol'];
        foreach ($camposRequeridos as $campo) {
            if (empty($data[$campo])) {
                return ['success' => false, 'message' => "El campo $campo es requerido"];
            }
        }
        
        // Verificar si el email ya existe
        $stmt = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE email = ?");
        $stmt->execute([$data['email']]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'El email ya está registrado'];
        }
        
        // Verificar si el username ya existe
        $stmt = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE username = ?");
        $stmt->execute([$data['username']]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'El nombre de usuario ya está en uso'];
        }
        
        // Encriptar contraseña
        $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);
        
        // Insertar usuario
        $stmt = $pdo->prepare("
            INSERT INTO usuarios (nombre, apellido, email, telefono, username, password, rol, estado, notas) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $data['nombre'],
            $data['apellido'],
            $data['email'],
            $data['telefono'] ?? null,
            $data['username'],
            $passwordHash,
            $data['rol'],
            $data['estado'] ?? 'activo',
            $data['notas'] ?? null
        ]);
        
        $idUsuario = $pdo->lastInsertId();
        
        return [
            'success' => true,
            'message' => 'Usuario creado exitosamente',
            'id_usuario' => $idUsuario
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Error creando usuario: ' . $e->getMessage()
        ];
    }
}

function crearTablaUsuarios($pdo) {
    try {
        // Crear tabla usuarios
        $sql = "
        CREATE TABLE IF NOT EXISTS usuarios (
            id_usuario INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(100) NOT NULL,
            apellido VARCHAR(100) NOT NULL,
            email VARCHAR(150) UNIQUE NOT NULL,
            telefono VARCHAR(20),
            username VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            rol ENUM('administrador', 'productor', 'cliente', 'contador') NOT NULL DEFAULT 'cliente',
            estado ENUM('activo', 'inactivo', 'suspendido') NOT NULL DEFAULT 'activo',
            fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            ultimo_acceso TIMESTAMP NULL,
            avatar VARCHAR(255) DEFAULT NULL,
            notas TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        
        $pdo->exec($sql);
        
        // Insertar usuario administrador por defecto
        $passwordHash = password_hash('password', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            INSERT INTO usuarios (nombre, apellido, email, username, password, rol, estado) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            'Administrador', 
            'Sistema', 
            'admin@cooperativa.com', 
            'admin', 
            $passwordHash,
            'administrador', 
            'activo'
        ]);
        
        // Insertar algunos usuarios de ejemplo
        $usuariosEjemplo = [
            ['Juan', 'Pérez', 'juan.perez@email.com', 'jperez', 'productor'],
            ['María', 'González', 'maria.gonzalez@email.com', 'mgonzalez', 'productor'],
            ['Carlos', 'López', 'carlos.lopez@email.com', 'clopez', 'cliente'],
            ['Ana', 'Martínez', 'ana.martinez@email.com', 'amartinez', 'contador']
        ];
        
        foreach ($usuariosEjemplo as $usuario) {
            $stmt = $pdo->prepare("
                INSERT INTO usuarios (nombre, apellido, email, username, password, rol, estado) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $usuario[0], 
                $usuario[1], 
                $usuario[2], 
                $usuario[3], 
                $passwordHash,
                $usuario[4], 
                'activo'
            ]);
        }
        
    } catch (Exception $e) {
        error_log("Error creando tabla usuarios: " . $e->getMessage());
    }
}
?>
