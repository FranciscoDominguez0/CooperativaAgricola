<?php
require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $pdo = conectarDB();
        
        // Verificar si se solicita un socio específico
        if (isset($_GET['id_socio'])) {
            // Obtener un socio específico
            $id_socio = (int)$_GET['id_socio'];
            $stmt = $pdo->prepare("SELECT * FROM socios WHERE id_socio = ?");
            $stmt->execute([$id_socio]);
            $socio = $stmt->fetch();
            
            if ($socio) {
                echo json_encode([
                    'success' => true,
                    'data' => $socio
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Socio no encontrado'
                ]);
            }
        } else {
            // Obtener lista de socios
            $search = $_GET['search'] ?? '';
            $page = (int)($_GET['page'] ?? 1);
            $limit = (int)($_GET['limit'] ?? 10);
            $offset = ($page - 1) * $limit;
            
            $whereClause = '';
            $params = [];
            
            if (!empty($search)) {
                $whereClause = "WHERE nombre LIKE ? OR cedula LIKE ? OR email LIKE ?";
                $searchTerm = "%$search%";
                $params = [$searchTerm, $searchTerm, $searchTerm];
            }
            
            // Contar total de registros
            $countQuery = "SELECT COUNT(*) as total FROM socios $whereClause";
            $countStmt = $pdo->prepare($countQuery);
            $countStmt->execute($params);
            $totalRecords = $countStmt->fetch()['total'];
            
            // Obtener socios con paginación
            $query = "SELECT * FROM socios $whereClause ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $socios = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'data' => $socios,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => ceil($totalRecords / $limit),
                    'total_records' => $totalRecords,
                    'per_page' => $limit
                ]
            ]);
        }
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener socios: ' . $e->getMessage()]);
    }
}

elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Crear nuevo socio
    // Debug: Log what we're receiving
    error_log("POST Data received: " . print_r($_POST, true));
    
    try {
        $pdo = conectarDB();
        
        $nombre = trim($_POST['nombre'] ?? '');
        $cedula = trim($_POST['cedula'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $direccion = trim($_POST['direccion'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $fecha_ingreso = $_POST['fecha_ingreso'] ?? date('Y-m-d');
        $estado = $_POST['estado'] ?? 'activo';
        $aportes_totales = (float)($_POST['aportes_totales'] ?? 0);
        $deudas_pendientes = (float)($_POST['deudas_pendientes'] ?? 0);
        $observaciones = trim($_POST['observaciones'] ?? '');
        
        // IMPORTANTE: Ignorar completamente id_socio en POST (siempre crear nuevo)
        if (isset($_POST['id_socio'])) {
            error_log("WARNING: id_socio recibido en POST, será ignorado para crear nuevo socio");
            unset($_POST['id_socio']);
        }
        
        // VERIFICACIÓN ADICIONAL: Asegurar que no hay id_socio en POST
        if (array_key_exists('id_socio', $_POST)) {
            error_log("CRITICAL: id_socio encontrado en POST, removiendo completamente");
            unset($_POST['id_socio']);
        }
        
        // Validaciones
        if (empty($nombre) || empty($cedula)) {
            echo json_encode(['success' => false, 'message' => 'Nombre y cédula son obligatorios']);
            exit();
        }
        
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Formato de email inválido']);
            exit();
        }
        
        // Verificar si la cédula ya existe
        $checkStmt = $pdo->prepare("SELECT id_socio FROM socios WHERE cedula = ?");
        $checkStmt->execute([$cedula]);
        if ($checkStmt->rowCount() > 0) {
            echo json_encode(['success' => false, 'message' => 'Ya existe un socio con esta cédula']);
            exit();
        }
        
        // LOG FINAL: Verificar que no hay id_socio antes de insertar
        error_log("FINAL CHECK: id_socio en POST: " . (isset($_POST['id_socio']) ? $_POST['id_socio'] : 'NOT SET'));
        error_log("FINAL CHECK: Creando NUEVO socio con cédula: " . $cedula);
        
        // Insertar socio
        $stmt = $pdo->prepare("
            INSERT INTO socios (nombre, cedula, telefono, direccion, email, fecha_ingreso, estado, aportes_totales, deudas_pendientes, observaciones) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([
            $nombre, $cedula, $telefono, $direccion, $email, $fecha_ingreso, $estado, 
            $aportes_totales, $deudas_pendientes, $observaciones
        ]);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Socio creado exitosamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al crear el socio']);
        }
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()]);
    }
}

elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    // Actualizar socio
    parse_str(file_get_contents("php://input"), $data);
    
    // Debug: Log what we're receiving
    error_log("PUT Data received: " . print_r($data, true));
    
    try {
        $pdo = conectarDB();
        
        $id_socio = $data['id_socio'] ?? 0;
        $nombre = trim($data['nombre'] ?? '');
        $cedula = trim($data['cedula'] ?? '');
        $telefono = trim($data['telefono'] ?? '');
        $direccion = trim($data['direccion'] ?? '');
        $email = trim($data['email'] ?? '');
        $fecha_ingreso = $data['fecha_ingreso'] ?? '';
        $estado = $data['estado'] ?? 'activo';
        $aportes_totales = (float)($data['aportes_totales'] ?? 0);
        $deudas_pendientes = (float)($data['deudas_pendientes'] ?? 0);
        $observaciones = trim($data['observaciones'] ?? '');
        
        if (empty($id_socio) || empty($nombre) || empty($cedula)) {
            echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
            exit();
        }
        
        // Verificar si la cédula ya existe en otro socio
        $checkStmt = $pdo->prepare("SELECT id_socio FROM socios WHERE cedula = ? AND id_socio != ?");
        $checkStmt->execute([$cedula, $id_socio]);
        if ($checkStmt->rowCount() > 0) {
            echo json_encode(['success' => false, 'message' => 'Ya existe otro socio con esta cédula']);
            exit();
        }
        
        $stmt = $pdo->prepare("
            UPDATE socios SET 
                nombre = ?, cedula = ?, telefono = ?, direccion = ?, email = ?, 
                fecha_ingreso = ?, estado = ?, aportes_totales = ?, deudas_pendientes = ?, observaciones = ?
            WHERE id_socio = ?
        ");
        
        $result = $stmt->execute([
            $nombre, $cedula, $telefono, $direccion, $email, $fecha_ingreso, 
            $estado, $aportes_totales, $deudas_pendientes, $observaciones, $id_socio
        ]);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Socio actualizado exitosamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar el socio']);
        }
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()]);
    }
}

elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    // Eliminar socio
    parse_str(file_get_contents("php://input"), $data);
    
    try {
        $pdo = conectarDB();
        $id_socio = $data['id_socio'] ?? 0;
        
        if (empty($id_socio)) {
            echo json_encode(['success' => false, 'message' => 'ID de socio requerido']);
            exit();
        }
        
        $stmt = $pdo->prepare("DELETE FROM socios WHERE id_socio = ?");
        $result = $stmt->execute([$id_socio]);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Socio eliminado exitosamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al eliminar el socio']);
        }
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()]);
    }
}

else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}
?>
