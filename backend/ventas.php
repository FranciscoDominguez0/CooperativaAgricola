<?php
require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $pdo = conectarDB();
        
        // Verificar si se solicita una venta específica
        if (isset($_GET['action']) && $_GET['action'] === 'get') {
            $id = intval($_GET['id'] ?? 0);
            
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'ID de venta requerido']);
                exit();
            }
            
            $query = "SELECT v.*, s.nombre as nombre_socio 
                     FROM ventas v 
                     LEFT JOIN socios s ON v.id_socio = s.id_socio 
                     WHERE v.id_venta = ?";
            
            $stmt = $pdo->prepare($query);
            $stmt->execute([$id]);
            $venta = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($venta) {
                echo json_encode([
                    'success' => true,
                    'data' => $venta
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Venta no encontrada'
                ]);
            }
            exit();
        }
        
        // Verificar si se solicitan estadísticas
        if (isset($_GET['action']) && $_GET['action'] === 'statistics') {
            // Obtener estadísticas de ventas
            $stats = [];
            
            // Ventas totales
            $stmt = $pdo->query("SELECT SUM(total) as total FROM ventas");
            $totalData = $stmt->fetch();
            $stats['ventas_totales'] = $totalData ? $totalData['total'] : '0';
            
            // Ventas pendientes
            $stmt = $pdo->query("SELECT COUNT(*) as pendientes FROM ventas WHERE estado = 'pendiente'");
            $pendientesData = $stmt->fetch();
            $stats['ventas_pendientes'] = $pendientesData ? $pendientesData['pendientes'] : '0';
            
            // Ventas pagadas
            $stmt = $pdo->query("SELECT COUNT(*) as pagadas FROM ventas WHERE estado = 'pagado'");
            $pagadasData = $stmt->fetch();
            $stats['ventas_pagadas'] = $pagadasData ? $pagadasData['pagadas'] : '0';
            
            // Clientes activos
            $stmt = $pdo->query("SELECT COUNT(DISTINCT cliente) as clientes FROM ventas");
            $clientesData = $stmt->fetch();
            $stats['clientes_activos'] = $clientesData ? $clientesData['clientes'] : '0';
            
            echo json_encode([
                'success' => true,
                'statistics' => $stats
            ]);
            exit();
        }
        
        // Obtener parámetros de paginación
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $offset = ($page - 1) * $limit;
        
        // Construir consulta base
        $whereClause = '';
        $params = [];
        
        if (!empty($search)) {
            $whereClause = "WHERE (v.producto LIKE :search OR s.nombre LIKE :search OR v.cliente LIKE :search)";
            $params[':search'] = "%$search%";
        }
        
        // Contar total de registros
        $countQuery = "SELECT COUNT(*) as total FROM ventas v 
                      LEFT JOIN socios s ON v.id_socio = s.id_socio 
                      $whereClause";
        $countStmt = $pdo->prepare($countQuery);
        $countStmt->execute($params);
        $totalRecords = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        $totalPages = ceil($totalRecords / $limit);
        
        // Obtener ventas con información del socio
        $query = "SELECT v.*, s.nombre as nombre_socio 
                 FROM ventas v 
                 LEFT JOIN socios s ON v.id_socio = s.id_socio 
                 $whereClause
                 ORDER BY v.fecha_venta DESC, v.id_venta DESC 
                 LIMIT :limit OFFSET :offset";
        
        $stmt = $pdo->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => $ventas,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_records' => $totalRecords,
                'limit' => $limit
            ]
        ]);
        
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error de base de datos: ' . $e->getMessage()
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error del servidor: ' . $e->getMessage()
        ]);
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = conectarDB();
        
        $action = $_GET['action'] ?? '';
        
        if ($action === 'create') {
            // Crear nueva venta
            $id_socio = intval($_POST['id_socio'] ?? 0);
            $producto = trim($_POST['producto'] ?? '');
            $cantidad = floatval($_POST['cantidad'] ?? 0);
            $precio_unitario = floatval($_POST['precio_unitario'] ?? 0);
            $cliente = trim($_POST['cliente'] ?? '');
            $fecha_venta = $_POST['fecha_venta'] ?? '';
            $fecha_entrega = $_POST['fecha_entrega'] ?? null;
            $estado = $_POST['estado'] ?? 'pendiente';
            $metodo_pago = $_POST['metodo_pago'] ?? 'efectivo';
            $direccion_entrega = trim($_POST['direccion_entrega'] ?? '');
            $observaciones = trim($_POST['observaciones'] ?? '');
            
            // Validaciones
            if (!$id_socio || !$producto || !$cantidad || !$precio_unitario || !$cliente || !$fecha_venta) {
                echo json_encode(['success' => false, 'message' => 'Todos los campos requeridos deben ser completados']);
                exit();
            }
            
            $query = "INSERT INTO ventas (id_socio, producto, cantidad, precio_unitario, cliente, fecha_venta, fecha_entrega, estado, metodo_pago, direccion_entrega, observaciones) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $pdo->prepare($query);
            $stmt->execute([
                $id_socio, $producto, $cantidad, $precio_unitario, $cliente, $fecha_venta, 
                $fecha_entrega, $estado, $metodo_pago, $direccion_entrega, $observaciones
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Venta registrada exitosamente'
            ]);
            
        } else if ($action === 'update') {
            // Actualizar venta existente
            $id = intval($_GET['id'] ?? 0);
            $id_socio = intval($_POST['id_socio'] ?? 0);
            $producto = trim($_POST['producto'] ?? '');
            $cantidad = floatval($_POST['cantidad'] ?? 0);
            $precio_unitario = floatval($_POST['precio_unitario'] ?? 0);
            $cliente = trim($_POST['cliente'] ?? '');
            $fecha_venta = $_POST['fecha_venta'] ?? '';
            $fecha_entrega = $_POST['fecha_entrega'] ?? null;
            $estado = $_POST['estado'] ?? 'pendiente';
            $metodo_pago = $_POST['metodo_pago'] ?? 'efectivo';
            $direccion_entrega = trim($_POST['direccion_entrega'] ?? '');
            $observaciones = trim($_POST['observaciones'] ?? '');
            
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'ID de venta requerido']);
                exit();
            }
            
            // Validaciones
            if (!$id_socio || !$producto || !$cantidad || !$precio_unitario || !$cliente || !$fecha_venta) {
                echo json_encode(['success' => false, 'message' => 'Todos los campos requeridos deben ser completados']);
                exit();
            }
            
            $query = "UPDATE ventas SET 
                     id_socio = ?, producto = ?, cantidad = ?, precio_unitario = ?, 
                     cliente = ?, fecha_venta = ?, fecha_entrega = ?, estado = ?, 
                     metodo_pago = ?, direccion_entrega = ?, observaciones = ?
                     WHERE id_venta = ?";
            
            $stmt = $pdo->prepare($query);
            $stmt->execute([
                $id_socio, $producto, $cantidad, $precio_unitario, $cliente, $fecha_venta,
                $fecha_entrega, $estado, $metodo_pago, $direccion_entrega, $observaciones, $id
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Venta actualizada exitosamente'
            ]);
        }
        
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error de base de datos: ' . $e->getMessage()
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error del servidor: ' . $e->getMessage()
        ]);
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    try {
        $pdo = conectarDB();
        
        $id = intval($_GET['id'] ?? 0);
        
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'ID de venta requerido']);
            exit();
        }
        
        $query = "DELETE FROM ventas WHERE id_venta = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$id]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Venta eliminada exitosamente'
        ]);
        
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error de base de datos: ' . $e->getMessage()
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error del servidor: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
}
?>