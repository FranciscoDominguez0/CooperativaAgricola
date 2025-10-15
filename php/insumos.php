<?php
require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $pdo = conectarDB();
        
        // Verificar si se solicita un insumo específico
        if (isset($_GET['id_insumo'])) {
            // Obtener un insumo específico
            $id_insumo = (int)$_GET['id_insumo'];
            $stmt = $pdo->prepare("SELECT * FROM insumos WHERE id_insumo = ?");
            $stmt->execute([$id_insumo]);
            $insumo = $stmt->fetch();
            
            if ($insumo) {
                echo json_encode([
                    'success' => true,
                    'data' => $insumo
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Insumo no encontrado'
                ]);
            }
        } else {
            // Obtener lista de insumos
            $search = $_GET['search'] ?? '';
            $page = (int)($_GET['page'] ?? 1);
            $limit = (int)($_GET['limit'] ?? 10);
            $offset = ($page - 1) * $limit;
            
            $whereClause = '';
            $params = [];
            
            if (!empty($search)) {
                $whereClause = "WHERE nombre_insumo LIKE ? OR tipo LIKE ? OR proveedor LIKE ? OR ubicacion_almacen LIKE ?";
                $searchTerm = "%$search%";
                $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
            }
            
            // Contar total de registros
            $countQuery = "SELECT COUNT(*) as total FROM insumos $whereClause";
            $countStmt = $pdo->prepare($countQuery);
            $countStmt->execute($params);
            $totalRecords = $countStmt->fetch()['total'];
            
            // Obtener insumos con paginación
            $query = "SELECT * FROM insumos $whereClause ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $insumos = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'data' => $insumos,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => ceil($totalRecords / $limit),
                    'total_records' => $totalRecords,
                    'per_page' => $limit
                ]
            ]);
        }
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener insumos: ' . $e->getMessage()]);
    }
}

elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Crear nuevo insumo
    try {
        $pdo = conectarDB();
        
        $nombre_insumo = trim($_POST['nombre_insumo'] ?? '');
        $tipo = trim($_POST['tipo'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $cantidad_disponible = (int)($_POST['cantidad_disponible'] ?? 0);
        $cantidad_minima = (int)($_POST['cantidad_minima'] ?? 0);
        $precio_unitario = (float)($_POST['precio_unitario'] ?? 0);
        $proveedor = trim($_POST['proveedor'] ?? '');
        $fecha_registro = $_POST['fecha_registro'] ?? date('Y-m-d');
        $ubicacion_almacen = trim($_POST['ubicacion_almacen'] ?? '');
        $estado = $_POST['estado'] ?? 'disponible';
        
        // Validaciones
        if (empty($nombre_insumo) || empty($tipo)) {
            echo json_encode(['success' => false, 'message' => 'Nombre y tipo son obligatorios']);
            exit();
        }
        
        if ($precio_unitario <= 0) {
            echo json_encode(['success' => false, 'message' => 'El precio unitario debe ser mayor a 0']);
            exit();
        }
        
        if ($cantidad_disponible < 0) {
            echo json_encode(['success' => false, 'message' => 'La cantidad disponible no puede ser negativa']);
            exit();
        }
        
        // Insertar insumo
        $stmt = $pdo->prepare("
            INSERT INTO insumos (nombre_insumo, tipo, descripcion, cantidad_disponible, cantidad_minima, precio_unitario, proveedor, fecha_registro, ubicacion_almacen, estado) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([
            $nombre_insumo, $tipo, $descripcion, $cantidad_disponible, $cantidad_minima, 
            $precio_unitario, $proveedor, $fecha_registro, $ubicacion_almacen, $estado
        ]);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Insumo creado exitosamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al crear el insumo']);
        }
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()]);
    }
}

elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    // Actualizar insumo
    parse_str(file_get_contents("php://input"), $data);
    
    try {
        $pdo = conectarDB();
        
        $id_insumo = $data['id_insumo'] ?? 0;
        $nombre_insumo = trim($data['nombre_insumo'] ?? '');
        $tipo = trim($data['tipo'] ?? '');
        $descripcion = trim($data['descripcion'] ?? '');
        $cantidad_disponible = (int)($data['cantidad_disponible'] ?? 0);
        $cantidad_minima = (int)($data['cantidad_minima'] ?? 0);
        $precio_unitario = (float)($data['precio_unitario'] ?? 0);
        $proveedor = trim($data['proveedor'] ?? '');
        $fecha_registro = $data['fecha_registro'] ?? '';
        $ubicacion_almacen = trim($data['ubicacion_almacen'] ?? '');
        $estado = $data['estado'] ?? 'disponible';
        
        if (empty($id_insumo) || empty($nombre_insumo) || empty($tipo)) {
            echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
            exit();
        }
        
        if ($precio_unitario <= 0) {
            echo json_encode(['success' => false, 'message' => 'El precio unitario debe ser mayor a 0']);
            exit();
        }
        
        if ($cantidad_disponible < 0) {
            echo json_encode(['success' => false, 'message' => 'La cantidad disponible no puede ser negativa']);
            exit();
        }
        
        $stmt = $pdo->prepare("
            UPDATE insumos SET 
                nombre_insumo = ?, tipo = ?, descripcion = ?, cantidad_disponible = ?, cantidad_minima = ?, 
                precio_unitario = ?, proveedor = ?, fecha_registro = ?, ubicacion_almacen = ?, estado = ?
            WHERE id_insumo = ?
        ");
        
        $result = $stmt->execute([
            $nombre_insumo, $tipo, $descripcion, $cantidad_disponible, $cantidad_minima, 
            $precio_unitario, $proveedor, $fecha_registro, $ubicacion_almacen, $estado, $id_insumo
        ]);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Insumo actualizado exitosamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar el insumo']);
        }
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()]);
    }
}

elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    // Eliminar insumo
    parse_str(file_get_contents("php://input"), $data);
    
    try {
        $pdo = conectarDB();
        $id_insumo = $data['id_insumo'] ?? 0;
        
        if (empty($id_insumo)) {
            echo json_encode(['success' => false, 'message' => 'ID de insumo requerido']);
            exit();
        }
        
        $stmt = $pdo->prepare("DELETE FROM insumos WHERE id_insumo = ?");
        $result = $stmt->execute([$id_insumo]);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Insumo eliminado exitosamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al eliminar el insumo']);
        }
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()]);
    }
}

else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}
?>
