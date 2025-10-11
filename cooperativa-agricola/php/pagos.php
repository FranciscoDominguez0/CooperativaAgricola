<?php
require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $pdo = conectarDB();
        
        // Verificar si se solicita un pago específico
        if (isset($_GET['action']) && $_GET['action'] === 'get') {
            $id = intval($_GET['id'] ?? 0);
            
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'ID de pago requerido']);
                exit();
            }
            
            $query = "SELECT p.*, s.nombre as nombre_socio 
                     FROM pagos p 
                     LEFT JOIN socios s ON p.id_socio = s.id_socio 
                     WHERE p.id_pago = ?";
            
            $stmt = $pdo->prepare($query);
            $stmt->execute([$id]);
            $pago = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($pago) {
                // Asegurar que los campos NULL se conviertan en strings vacíos
                $pago['descripcion'] = $pago['descripcion'] ?? '';
                $pago['observaciones'] = $pago['observaciones'] ?? '';
                $pago['numero_comprobante'] = $pago['numero_comprobante'] ?? '';
                $pago['id_venta'] = $pago['id_venta'] ?? '';
                
                echo json_encode([
                    'success' => true,
                    'data' => $pago
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Pago no encontrado'
                ]);
            }
            exit();
        }
        
        // Obtener estadísticas de pagos
        if (isset($_GET['action']) && $_GET['action'] === 'statistics') {
            $stats = [];
            
            // Ingresos totales
            $stmt = $pdo->query("SELECT SUM(monto) as total FROM pagos WHERE estado = 'confirmado'");
            $totalData = $stmt->fetch();
            $stats['ingresos_totales'] = $totalData ? $totalData['total'] : '0';
            
            // Pagos pendientes
            $stmt = $pdo->query("SELECT COUNT(*) as pendientes FROM pagos WHERE estado = 'pendiente'");
            $pendientesData = $stmt->fetch();
            $stats['pagos_pendientes'] = $pendientesData ? $pendientesData['pendientes'] : '0';
            
            // Pagos confirmados
            $stmt = $pdo->query("SELECT COUNT(*) as confirmados FROM pagos WHERE estado = 'confirmado'");
            $confirmadosData = $stmt->fetch();
            $stats['pagos_confirmados'] = $confirmadosData ? $confirmadosData['confirmados'] : '0';
            
            // Aportes mensuales
            $stmt = $pdo->query("SELECT COUNT(*) as aportes FROM pagos WHERE tipo = 'aporte_mensual' AND estado = 'confirmado'");
            $aportesData = $stmt->fetch();
            $stats['aportes_mensuales'] = $aportesData ? $aportesData['aportes'] : '0';
            
            echo json_encode([
                'success' => true,
                'statistics' => $stats
            ]);
            exit();
        }
        
        // Listar pagos con paginación
        $page = intval($_GET['page'] ?? 1);
        $limit = intval($_GET['limit'] ?? 10);
        $search = $_GET['search'] ?? '';
        
        $offset = ($page - 1) * $limit;
        
        $whereClause = '';
        $params = [];
        
        if (!empty($search)) {
            $whereClause = "WHERE s.nombre LIKE ? OR p.descripcion LIKE ? OR p.numero_comprobante LIKE ?";
            $searchTerm = "%$search%";
            $params = [$searchTerm, $searchTerm, $searchTerm];
        }
        
        $query = "SELECT p.*, s.nombre as nombre_socio 
                 FROM pagos p 
                 LEFT JOIN socios s ON p.id_socio = s.id_socio 
                 $whereClause
                 ORDER BY p.fecha_pago DESC, p.created_at DESC 
                 LIMIT $limit OFFSET $offset";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $pagos = $stmt->fetchAll();
        
        // Asegurar que los campos NULL se conviertan en strings vacíos para todos los pagos
        foreach ($pagos as &$pago) {
            $pago['descripcion'] = $pago['descripcion'] ?? '';
            $pago['observaciones'] = $pago['observaciones'] ?? '';
            $pago['numero_comprobante'] = $pago['numero_comprobante'] ?? '';
            $pago['id_venta'] = $pago['id_venta'] ?? '';
            $pago['nombre_socio'] = $pago['nombre_socio'] ?? '';
        }
        
        // Contar total de registros
        $countQuery = "SELECT COUNT(*) as total 
                       FROM pagos p 
                       LEFT JOIN socios s ON p.id_socio = s.id_socio 
                       $whereClause";
        
        $countStmt = $pdo->prepare($countQuery);
        $countStmt->execute($params);
        $totalRecords = $countStmt->fetch()['total'];
        
        $totalPages = ceil($totalRecords / $limit);
        
        echo json_encode([
            'success' => true,
            'data' => $pagos,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_records' => $totalRecords,
                'limit' => $limit
            ]
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
}

// Crear o actualizar pago
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = conectarDB();
        
        $action = $_GET['action'] ?? '';
        
        if ($action === 'create') {
            // Crear nuevo pago
            $id_socio = intval($_POST['id_socio'] ?? 0);
            $id_venta = !empty($_POST['id_venta']) ? intval($_POST['id_venta']) : null;
            $monto = floatval($_POST['monto'] ?? 0);
            $tipo = $_POST['tipo'] ?? '';
            $descripcion = $_POST['descripcion'] ?? '';
            $estado = $_POST['estado'] ?? 'pendiente';
            $fecha_pago = $_POST['fecha_pago'] ?? '';
            $metodo_pago = $_POST['metodo_pago'] ?? 'efectivo';
            $numero_comprobante = $_POST['numero_comprobante'] ?? '';
            $observaciones = $_POST['observaciones'] ?? '';
            
            // Validaciones
            if (!$id_socio || !$monto || !$tipo || !$fecha_pago) {
                echo json_encode(['success' => false, 'message' => 'Todos los campos requeridos deben ser completados']);
                exit();
            }
            
            $query = "INSERT INTO pagos (id_socio, id_venta, monto, tipo, descripcion, estado, fecha_pago, metodo_pago, numero_comprobante, observaciones) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $pdo->prepare($query);
            $result = $stmt->execute([
                $id_socio, $id_venta, $monto, $tipo, $descripcion, $estado, $fecha_pago, $metodo_pago, $numero_comprobante, $observaciones
            ]);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Pago registrado exitosamente',
                    'id' => $pdo->lastInsertId()
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Error al registrar el pago'
                ]);
            }
        } else if ($action === 'update') {
            // Actualizar pago existente
            $id = intval($_GET['id'] ?? 0);
            $id_socio = intval($_POST['id_socio'] ?? 0);
            $id_venta = !empty($_POST['id_venta']) ? intval($_POST['id_venta']) : null;
            $monto = floatval($_POST['monto'] ?? 0);
            $tipo = $_POST['tipo'] ?? '';
            $descripcion = $_POST['descripcion'] ?? '';
            $estado = $_POST['estado'] ?? 'pendiente';
            $fecha_pago = $_POST['fecha_pago'] ?? '';
            $metodo_pago = $_POST['metodo_pago'] ?? 'efectivo';
            $numero_comprobante = $_POST['numero_comprobante'] ?? '';
            $observaciones = $_POST['observaciones'] ?? '';
            
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'ID de pago requerido']);
                exit();
            }
            
            // Validaciones
            if (!$id_socio || !$monto || !$tipo || !$fecha_pago) {
                echo json_encode(['success' => false, 'message' => 'Todos los campos requeridos deben ser completados']);
                exit();
            }
            
            $query = "UPDATE pagos SET 
                     id_socio = ?, id_venta = ?, monto = ?, tipo = ?, descripcion = ?, 
                     estado = ?, fecha_pago = ?, metodo_pago = ?, numero_comprobante = ?, observaciones = ?
                     WHERE id_pago = ?";
            
            $stmt = $pdo->prepare($query);
            $result = $stmt->execute([
                $id_socio, $id_venta, $monto, $tipo, $descripcion, $estado, $fecha_pago, $metodo_pago, $numero_comprobante, $observaciones, $id
            ]);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Pago actualizado exitosamente'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Error al actualizar el pago'
                ]);
            }
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
}

// Eliminar pago
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    try {
        $pdo = conectarDB();
        
        $id = intval($_GET['id'] ?? 0);
        
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'ID de pago requerido']);
            exit();
        }
        
        $query = "DELETE FROM pagos WHERE id_pago = ?";
        $stmt = $pdo->prepare($query);
        $result = $stmt->execute([$id]);
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Pago eliminado exitosamente'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Error al eliminar el pago'
            ]);
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
}

// Si no coincide con ningún método
if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}
?>