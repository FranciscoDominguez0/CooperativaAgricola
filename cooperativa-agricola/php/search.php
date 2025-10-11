<?php
/**
 * Enhanced Search API for Agricultural Cooperative Management
 * Provides advanced search functionality across all modules
 */

require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $pdo = conectarDB();
        
        $module = $_GET['module'] ?? '';
        $search = trim($_GET['search'] ?? '');
        $filters = $_GET['filters'] ?? '';
        $page = (int)($_GET['page'] ?? 1);
        $limit = (int)($_GET['limit'] ?? 10);
        $offset = ($page - 1) * $limit;
        
        if (empty($module)) {
            echo json_encode(['success' => false, 'message' => 'Módulo requerido']);
            exit();
        }
        
        $result = [];
        
        switch ($module) {
            case 'produccion':
                $result = searchProduccion($pdo, $search, $filters, $page, $limit, $offset);
                break;
            case 'ventas':
                $result = searchVentas($pdo, $search, $filters, $page, $limit, $offset);
                break;
            case 'pagos':
                $result = searchPagos($pdo, $search, $filters, $page, $limit, $offset);
                break;
            default:
                echo json_encode(['success' => false, 'message' => 'Módulo no válido']);
                exit();
        }
        
        echo json_encode($result);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

/**
 * Search in Production module
 */
function searchProduccion($pdo, $search, $filters, $page, $limit, $offset) {
    $whereConditions = [];
    $params = [];
    
    // Parse filters
    $filterArray = parseFilters($filters);
    
    // Build search conditions
    if (!empty($search)) {
        $searchConditions = [
            "p.cultivo LIKE :search1",
            "p.variedad LIKE :search2", 
            "p.calidad LIKE :search3",
            "s.nombre LIKE :search4"
        ];
        $whereConditions[] = "(" . implode(" OR ", $searchConditions) . ")";
        $searchTerm = "%$search%";
        $params[':search1'] = $searchTerm;
        $params[':search2'] = $searchTerm;
        $params[':search3'] = $searchTerm;
        $params[':search4'] = $searchTerm;
    }
    
    // Apply specific filters
    if (isset($filterArray['cultivo']) && !empty($filterArray['cultivo'])) {
        $whereConditions[] = "p.cultivo = :cultivo";
        $params[':cultivo'] = $filterArray['cultivo'];
    }
    
    if (isset($filterArray['variedad']) && !empty($filterArray['variedad'])) {
        $whereConditions[] = "p.variedad = :variedad";
        $params[':variedad'] = $filterArray['variedad'];
    }
    
    if (isset($filterArray['calidad']) && !empty($filterArray['calidad'])) {
        $whereConditions[] = "p.calidad = :calidad";
        $params[':calidad'] = $filterArray['calidad'];
    }
    
    if (isset($filterArray['socio']) && !empty($filterArray['socio'])) {
        $whereConditions[] = "s.nombre = :socio";
        $params[':socio'] = $filterArray['socio'];
    }
    
    $whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";
    
    // Count total records
    $countQuery = "SELECT COUNT(*) as total 
                   FROM produccion p 
                   LEFT JOIN socios s ON p.id_socio = s.id_socio 
                   $whereClause";
    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($params);
    $totalRecords = $countStmt->fetch()['total'];
    
    // Get data with pagination
    $query = "SELECT p.*, s.nombre as nombre_socio 
              FROM produccion p 
              LEFT JOIN socios s ON p.id_socio = s.id_socio 
              $whereClause
              ORDER BY p.fecha_recoleccion DESC, p.id_produccion DESC 
              LIMIT :limit OFFSET :offset";
    
    $stmt = $pdo->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'success' => true,
        'data' => $data,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => ceil($totalRecords / $limit),
            'total_records' => $totalRecords,
            'per_page' => $limit
        ],
        'search_info' => [
            'search_term' => $search,
            'filters_applied' => $filterArray,
            'results_count' => count($data)
        ]
    ];
}

/**
 * Search in Sales module
 */
function searchVentas($pdo, $search, $filters, $page, $limit, $offset) {
    $whereConditions = [];
    $params = [];
    
    // Parse filters
    $filterArray = parseFilters($filters);
    
    // Build search conditions
    if (!empty($search)) {
        $searchConditions = [
            "v.producto LIKE :search1",
            "v.cliente LIKE :search2",
            "v.estado LIKE :search3",
            "v.metodo_pago LIKE :search4",
            "s.nombre LIKE :search5"
        ];
        $whereConditions[] = "(" . implode(" OR ", $searchConditions) . ")";
        $searchTerm = "%$search%";
        $params[':search1'] = $searchTerm;
        $params[':search2'] = $searchTerm;
        $params[':search3'] = $searchTerm;
        $params[':search4'] = $searchTerm;
        $params[':search5'] = $searchTerm;
    }
    
    // Apply specific filters
    if (isset($filterArray['producto']) && !empty($filterArray['producto'])) {
        $whereConditions[] = "v.producto = :producto";
        $params[':producto'] = $filterArray['producto'];
    }
    
    if (isset($filterArray['cliente']) && !empty($filterArray['cliente'])) {
        $whereConditions[] = "v.cliente = :cliente";
        $params[':cliente'] = $filterArray['cliente'];
    }
    
    if (isset($filterArray['estado']) && !empty($filterArray['estado'])) {
        $whereConditions[] = "v.estado = :estado";
        $params[':estado'] = $filterArray['estado'];
    }
    
    if (isset($filterArray['metodo_pago']) && !empty($filterArray['metodo_pago'])) {
        $whereConditions[] = "v.metodo_pago = :metodo_pago";
        $params[':metodo_pago'] = $filterArray['metodo_pago'];
    }
    
    $whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";
    
    // Count total records
    $countQuery = "SELECT COUNT(*) as total 
                   FROM ventas v 
                   LEFT JOIN socios s ON v.id_socio = s.id_socio 
                   $whereClause";
    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($params);
    $totalRecords = $countStmt->fetch()['total'];
    
    // Get data with pagination
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
    
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'success' => true,
        'data' => $data,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => ceil($totalRecords / $limit),
            'total_records' => $totalRecords,
            'per_page' => $limit
        ],
        'search_info' => [
            'search_term' => $search,
            'filters_applied' => $filterArray,
            'results_count' => count($data)
        ]
    ];
}

/**
 * Search in Payments module
 */
function searchPagos($pdo, $search, $filters, $page, $limit, $offset) {
    $whereConditions = [];
    $params = [];
    
    // Parse filters
    $filterArray = parseFilters($filters);
    
    // Build search conditions
    if (!empty($search)) {
        $searchConditions = [
            "p.tipo LIKE :search1",
            "p.estado LIKE :search2",
            "p.metodo_pago LIKE :search3",
            "p.numero_comprobante LIKE :search4",
            "p.descripcion LIKE :search5",
            "s.nombre LIKE :search6"
        ];
        $whereConditions[] = "(" . implode(" OR ", $searchConditions) . ")";
        $searchTerm = "%$search%";
        $params[':search1'] = $searchTerm;
        $params[':search2'] = $searchTerm;
        $params[':search3'] = $searchTerm;
        $params[':search4'] = $searchTerm;
        $params[':search5'] = $searchTerm;
        $params[':search6'] = $searchTerm;
    }
    
    // Apply specific filters
    if (isset($filterArray['tipo']) && !empty($filterArray['tipo'])) {
        $whereConditions[] = "p.tipo = :tipo";
        $params[':tipo'] = $filterArray['tipo'];
    }
    
    if (isset($filterArray['estado']) && !empty($filterArray['estado'])) {
        $whereConditions[] = "p.estado = :estado";
        $params[':estado'] = $filterArray['estado'];
    }
    
    if (isset($filterArray['metodo_pago']) && !empty($filterArray['metodo_pago'])) {
        $whereConditions[] = "p.metodo_pago = :metodo_pago";
        $params[':metodo_pago'] = $filterArray['metodo_pago'];
    }
    
    if (isset($filterArray['comprobante']) && !empty($filterArray['comprobante'])) {
        $whereConditions[] = "p.numero_comprobante = :comprobante";
        $params[':comprobante'] = $filterArray['comprobante'];
    }
    
    $whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";
    
    // Count total records
    $countQuery = "SELECT COUNT(*) as total 
                   FROM pagos p 
                   LEFT JOIN socios s ON p.id_socio = s.id_socio 
                   $whereClause";
    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($params);
    $totalRecords = $countStmt->fetch()['total'];
    
    // Get data with pagination
    $query = "SELECT p.*, s.nombre as nombre_socio 
              FROM pagos p 
              LEFT JOIN socios s ON p.id_socio = s.id_socio 
              $whereClause
              ORDER BY p.fecha_pago DESC, p.id_pago DESC 
              LIMIT :limit OFFSET :offset";
    
    $stmt = $pdo->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Ensure NULL fields are converted to empty strings
    foreach ($data as &$pago) {
        $pago['descripcion'] = $pago['descripcion'] ?? '';
        $pago['observaciones'] = $pago['observaciones'] ?? '';
        $pago['numero_comprobante'] = $pago['numero_comprobante'] ?? '';
        $pago['id_venta'] = $pago['id_venta'] ?? '';
        $pago['nombre_socio'] = $pago['nombre_socio'] ?? '';
    }
    
    return [
        'success' => true,
        'data' => $data,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => ceil($totalRecords / $limit),
            'total_records' => $totalRecords,
            'per_page' => $limit
        ],
        'search_info' => [
            'search_term' => $search,
            'filters_applied' => $filterArray,
            'results_count' => count($data)
        ]
    ];
}

/**
 * Parse filters from query string
 */
function parseFilters($filters) {
    if (empty($filters)) {
        return [];
    }
    
    $filterArray = [];
    $filterPairs = explode('&', $filters);
    
    foreach ($filterPairs as $pair) {
        $parts = explode('=', $pair, 2);
        if (count($parts) === 2) {
            $key = urldecode($parts[0]);
            $value = urldecode($parts[1]);
            if (!empty($value)) {
                $filterArray[$key] = $value;
            }
        }
    }
    
    return $filterArray;
}

/**
 * Get search suggestions for autocomplete
 */
function getSearchSuggestions($pdo, $module, $search) {
    if (strlen($search) < 2) {
        return [];
    }
    
    $suggestions = [];
    $searchTerm = "%$search%";
    
    switch ($module) {
        case 'produccion':
            // Get unique cultivos
            $stmt = $pdo->prepare("SELECT DISTINCT cultivo FROM produccion WHERE cultivo LIKE ? ORDER BY cultivo LIMIT 5");
            $stmt->execute([$searchTerm]);
            $cultivos = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Get unique variedades
            $stmt = $pdo->prepare("SELECT DISTINCT variedad FROM produccion WHERE variedad LIKE ? AND variedad IS NOT NULL ORDER BY variedad LIMIT 5");
            $stmt->execute([$searchTerm]);
            $variedades = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Get unique calidades
            $stmt = $pdo->prepare("SELECT DISTINCT calidad FROM produccion WHERE calidad LIKE ? ORDER BY calidad LIMIT 5");
            $stmt->execute([$searchTerm]);
            $calidades = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $suggestions = array_merge($cultivos, $variedades, $calidades);
            break;
            
        case 'ventas':
            // Get unique productos
            $stmt = $pdo->prepare("SELECT DISTINCT producto FROM ventas WHERE producto LIKE ? ORDER BY producto LIMIT 5");
            $stmt->execute([$searchTerm]);
            $productos = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Get unique clientes
            $stmt = $pdo->prepare("SELECT DISTINCT cliente FROM ventas WHERE cliente LIKE ? ORDER BY cliente LIMIT 5");
            $stmt->execute([$searchTerm]);
            $clientes = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Get unique estados
            $stmt = $pdo->prepare("SELECT DISTINCT estado FROM ventas WHERE estado LIKE ? ORDER BY estado LIMIT 5");
            $stmt->execute([$searchTerm]);
            $estados = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $suggestions = array_merge($productos, $clientes, $estados);
            break;
            
        case 'pagos':
            // Get unique tipos
            $stmt = $pdo->prepare("SELECT DISTINCT tipo FROM pagos WHERE tipo LIKE ? ORDER BY tipo LIMIT 5");
            $stmt->execute([$searchTerm]);
            $tipos = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Get unique estados
            $stmt = $pdo->prepare("SELECT DISTINCT estado FROM pagos WHERE estado LIKE ? ORDER BY estado LIMIT 5");
            $stmt->execute([$searchTerm]);
            $estados = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Get unique métodos de pago
            $stmt = $pdo->prepare("SELECT DISTINCT metodo_pago FROM pagos WHERE metodo_pago LIKE ? ORDER BY metodo_pago LIMIT 5");
            $stmt->execute([$searchTerm]);
            $metodos = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $suggestions = array_merge($tipos, $estados, $metodos);
            break;
    }
    
    return array_unique($suggestions);
}

// Handle suggestions request
if (isset($_GET['action']) && $_GET['action'] === 'suggestions') {
    try {
        $pdo = conectarDB();
        $module = $_GET['module'] ?? '';
        $search = trim($_GET['search'] ?? '');
        
        if (empty($module) || empty($search)) {
            echo json_encode(['success' => false, 'message' => 'Módulo y término de búsqueda requeridos']);
            exit();
        }
        
        $suggestions = getSearchSuggestions($pdo, $module, $search);
        
        echo json_encode([
            'success' => true,
            'suggestions' => $suggestions
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit();
}

?>
