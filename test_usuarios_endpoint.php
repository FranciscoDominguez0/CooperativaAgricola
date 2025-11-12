<?php
require_once 'php/config.php';

header('Content-Type: application/json');

try {
    $pdo = conectarDB();
    
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
    if (!empty($params)) {
        $countStmt->execute($params);
    } else {
        $countStmt->execute();
    }
    $totalRecords = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($totalRecords / $limit);
    
    // Obtener usuarios con información completa
    $query = "SELECT id_usuario, nombre, correo, rol, estado, fecha_registro 
              FROM usuarios $whereClause 
              ORDER BY id_usuario DESC 
              LIMIT ? OFFSET ?";
    
    $stmt = $pdo->prepare($query);
    if (!empty($params)) {
        $params[] = $limit;
        $params[] = $offset;
    } else {
        $params = [$limit, $offset];
    }
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
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Error al cargar usuarios: ' . $e->getMessage()
    ]);
}
?>




