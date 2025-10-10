<?php
require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $pdo = conectarDB();
        
        // Verificar si se solicitan estadísticas
        if (isset($_GET['action']) && $_GET['action'] === 'statistics') {
            // Obtener estadísticas de producción
            $stats = [];
            
            // Producción total
            $stmt = $pdo->query("SELECT SUM(cantidad) as total, unidad FROM produccion GROUP BY unidad ORDER BY total DESC LIMIT 1");
            $totalData = $stmt->fetch();
            $stats['total_produccion'] = $totalData ? number_format($totalData['total'], 2) : '0';
            $stats['unidad_principal'] = $totalData ? $totalData['unidad'] : 'quintales';
            
            // Cultivos activos
            $stmt = $pdo->query("SELECT COUNT(DISTINCT cultivo) as cultivos FROM produccion");
            $cultivosData = $stmt->fetch();
            $stats['cultivos_activos'] = $cultivosData ? $cultivosData['cultivos'] : '0';
            
            // Productores activos
            $stmt = $pdo->query("SELECT COUNT(DISTINCT id_socio) as productores FROM produccion");
            $productoresData = $stmt->fetch();
            $stats['productores_activos'] = $productoresData ? $productoresData['productores'] : '0';
            
            // Calidad premium
            $stmt = $pdo->query("SELECT 
                COUNT(CASE WHEN calidad = 'premium' THEN 1 END) as premium_count,
                COUNT(*) as total_count
                FROM produccion");
            $calidadData = $stmt->fetch();
            $premiumPercentage = $calidadData['total_count'] > 0 ? 
                round(($calidadData['premium_count'] / $calidadData['total_count']) * 100, 1) : 0;
            $stats['calidad_premium'] = $premiumPercentage;
            
            echo json_encode([
                'success' => true,
                'statistics' => $stats
            ]);
            exit();
        }
        
        // Verificar si se solicita una producción específica
        if (isset($_GET['id_produccion'])) {
            // Obtener una producción específica
            $id_produccion = (int)$_GET['id_produccion'];
            $stmt = $pdo->prepare("
                SELECT p.*, s.nombre as nombre_socio 
                FROM produccion p 
                LEFT JOIN socios s ON p.id_socio = s.id_socio 
                WHERE p.id_produccion = ?
            ");
            $stmt->execute([$id_produccion]);
            $produccion = $stmt->fetch();
            
            if ($produccion) {
                echo json_encode([
                    'success' => true,
                    'data' => $produccion
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Producción no encontrada'
                ]);
            }
        } else {
            // Obtener lista de producción
            $search = $_GET['search'] ?? '';
            $page = (int)($_GET['page'] ?? 1);
            $limit = (int)($_GET['limit'] ?? 10);
            $offset = ($page - 1) * $limit;
            
            $whereClause = '';
            $params = [];
            
            if (!empty($search)) {
                $whereClause = "WHERE p.cultivo LIKE ? OR p.variedad LIKE ? OR s.nombre LIKE ?";
                $searchTerm = "%$search%";
                $params = [$searchTerm, $searchTerm, $searchTerm];
            }
            
            // Contar total de registros
            $countQuery = "
                SELECT COUNT(*) as total 
                FROM produccion p 
                LEFT JOIN socios s ON p.id_socio = s.id_socio 
                $whereClause
            ";
            $countStmt = $pdo->prepare($countQuery);
            $countStmt->execute($params);
            $totalRecords = $countStmt->fetch()['total'];
            
            // Obtener producción con paginación
            $query = "
                SELECT p.*, s.nombre as nombre_socio 
                FROM produccion p 
                LEFT JOIN socios s ON p.id_socio = s.id_socio 
                $whereClause 
                ORDER BY p.fecha_recoleccion DESC, p.created_at DESC 
                LIMIT $limit OFFSET $offset
            ";
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $produccion = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'data' => $produccion,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => ceil($totalRecords / $limit),
                    'total_records' => $totalRecords,
                    'per_page' => $limit
                ]
            ]);
        }
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener producción: ' . $e->getMessage()]);
    }
}

elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Crear nueva producción
    error_log("POST Data received: " . print_r($_POST, true));
    
    try {
        $pdo = conectarDB();
        
        $id_socio = (int)($_POST['id_socio'] ?? 0);
        $cultivo = trim($_POST['cultivo'] ?? '');
        $variedad = trim($_POST['variedad'] ?? '');
        $cantidad = (float)($_POST['cantidad'] ?? 0);
        $unidad = $_POST['unidad'] ?? 'quintales';
        $area_cultivada = (float)($_POST['area_cultivada'] ?? 0);
        $fecha_siembra = $_POST['fecha_siembra'] ?? null;
        $fecha_recoleccion = $_POST['fecha_recoleccion'] ?? '';
        $calidad = $_POST['calidad'] ?? 'buena';
        $precio_estimado = (float)($_POST['precio_estimado'] ?? 0);
        $observaciones = trim($_POST['observaciones'] ?? '');
        
        // IMPORTANTE: Ignorar completamente id_produccion en POST (siempre crear nuevo)
        if (isset($_POST['id_produccion'])) {
            error_log("WARNING: id_produccion recibido en POST, será ignorado para crear nueva producción");
            unset($_POST['id_produccion']);
        }
        
        // Validaciones
        if (empty($id_socio) || empty($cultivo) || empty($cantidad) || empty($fecha_recoleccion)) {
            echo json_encode(['success' => false, 'message' => 'Datos obligatorios faltantes']);
            exit();
        }
        
        if ($cantidad <= 0) {
            echo json_encode(['success' => false, 'message' => 'La cantidad debe ser mayor a 0']);
            exit();
        }
        
        // Verificar que el socio existe
        $checkSocio = $pdo->prepare("SELECT id_socio FROM socios WHERE id_socio = ?");
        $checkSocio->execute([$id_socio]);
        if ($checkSocio->rowCount() === 0) {
            echo json_encode(['success' => false, 'message' => 'El socio seleccionado no existe']);
            exit();
        }
        
        // Insertar producción
        $stmt = $pdo->prepare("
            INSERT INTO produccion (
                id_socio, cultivo, variedad, cantidad, unidad, area_cultivada, 
                fecha_siembra, fecha_recoleccion, calidad, precio_estimado, observaciones
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([
            $id_socio, $cultivo, $variedad, $cantidad, $unidad, $area_cultivada,
            $fecha_siembra, $fecha_recoleccion, $calidad, $precio_estimado, $observaciones
        ]);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Producción registrada exitosamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al registrar la producción']);
        }
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()]);
    }
}

elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    // Actualizar producción
    parse_str(file_get_contents("php://input"), $data);
    
    error_log("PUT Data received: " . print_r($data, true));
    
    try {
        $pdo = conectarDB();
        
        $id_produccion = (int)($data['id_produccion'] ?? 0);
        $id_socio = (int)($data['id_socio'] ?? 0);
        $cultivo = trim($data['cultivo'] ?? '');
        $variedad = trim($data['variedad'] ?? '');
        $cantidad = (float)($data['cantidad'] ?? 0);
        $unidad = $data['unidad'] ?? 'quintales';
        $area_cultivada = (float)($data['area_cultivada'] ?? 0);
        $fecha_siembra = $data['fecha_siembra'] ?? null;
        $fecha_recoleccion = $data['fecha_recoleccion'] ?? '';
        $calidad = $data['calidad'] ?? 'buena';
        $precio_estimado = (float)($data['precio_estimado'] ?? 0);
        $observaciones = trim($data['observaciones'] ?? '');
        
        if (empty($id_produccion) || empty($id_socio) || empty($cultivo) || empty($cantidad) || empty($fecha_recoleccion)) {
            echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
            exit();
        }
        
        if ($cantidad <= 0) {
            echo json_encode(['success' => false, 'message' => 'La cantidad debe ser mayor a 0']);
            exit();
        }
        
        // Verificar que el socio existe
        $checkSocio = $pdo->prepare("SELECT id_socio FROM socios WHERE id_socio = ?");
        $checkSocio->execute([$id_socio]);
        if ($checkSocio->rowCount() === 0) {
            echo json_encode(['success' => false, 'message' => 'El socio seleccionado no existe']);
            exit();
        }
        
        $stmt = $pdo->prepare("
            UPDATE produccion SET 
                id_socio = ?, cultivo = ?, variedad = ?, cantidad = ?, unidad = ?, 
                area_cultivada = ?, fecha_siembra = ?, fecha_recoleccion = ?, 
                calidad = ?, precio_estimado = ?, observaciones = ?
            WHERE id_produccion = ?
        ");
        
        $result = $stmt->execute([
            $id_socio, $cultivo, $variedad, $cantidad, $unidad, $area_cultivada,
            $fecha_siembra, $fecha_recoleccion, $calidad, $precio_estimado, 
            $observaciones, $id_produccion
        ]);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Producción actualizada exitosamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar la producción']);
        }
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()]);
    }
}

elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    // Eliminar producción
    parse_str(file_get_contents("php://input"), $data);
    
    try {
        $pdo = conectarDB();
        $id_produccion = (int)($data['id_produccion'] ?? 0);
        
        if (empty($id_produccion)) {
            echo json_encode(['success' => false, 'message' => 'ID de producción requerido']);
            exit();
        }
        
        $stmt = $pdo->prepare("DELETE FROM produccion WHERE id_produccion = ?");
        $result = $stmt->execute([$id_produccion]);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Producción eliminada exitosamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al eliminar la producción']);
        }
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()]);
    }
}

else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}
?>