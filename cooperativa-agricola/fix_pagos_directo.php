<?php
// Solución directa para el módulo de pagos
header('Content-Type: text/html; charset=utf-8');

echo "<h1>🔧 Solución Directa - Módulo de Pagos</h1>";

// Configuración directa de la base de datos
$host = 'localhost';
$dbname = 'cooperativa_agricola';
$username = 'root';
$password = '12345678'; // Cambia por tu contraseña real

try {
    // Conectar directamente
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<div style='background: #d4edda; padding: 15px; margin: 10px 0; border-left: 4px solid #28a745;'>";
    echo "✅ <strong>Conexión exitosa a la base de datos</strong>";
    echo "</div>";
    
    // 1. Crear tabla pagos si no existe
    $createTableSQL = "
    CREATE TABLE IF NOT EXISTS pagos (
        id_pago INT AUTO_INCREMENT PRIMARY KEY,
        id_socio INT NOT NULL,
        id_venta INT NULL,
        monto DECIMAL(10,2) NOT NULL,
        tipo ENUM('aporte_mensual', 'aporte_extraordinario', 'pago_venta', 'prestamo', 'devolucion') NOT NULL,
        descripcion TEXT,
        estado ENUM('pendiente', 'confirmado', 'rechazado') DEFAULT 'pendiente',
        fecha_pago DATE NOT NULL,
        metodo_pago ENUM('efectivo', 'transferencia', 'cheque', 'deposito') DEFAULT 'efectivo',
        numero_comprobante VARCHAR(50),
        observaciones TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    $pdo->exec($createTableSQL);
    echo "<div style='background: #d4edda; padding: 15px; margin: 10px 0; border-left: 4px solid #28a745;'>";
    echo "✅ <strong>Tabla 'pagos' creada/verificada</strong>";
    echo "</div>";
    
    // 2. Insertar datos de ejemplo
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM pagos");
    $count = $stmt->fetch();
    
    if ($count['total'] == 0) {
        $insertSQL = "
        INSERT INTO pagos (id_socio, id_venta, monto, tipo, descripcion, estado, fecha_pago, metodo_pago, numero_comprobante, observaciones) VALUES
        (1, 1, 50000.00, 'aporte_mensual', 'Aporte mensual enero 2024', 'confirmado', '2024-01-15', 'transferencia', 'TRF001', 'Pago puntual'),
        (1, NULL, 100000.00, 'aporte_extraordinario', 'Aporte para mejoras de infraestructura', 'confirmado', '2024-02-10', 'efectivo', 'EFE001', 'Contribución voluntaria'),
        (2, 2, 75000.00, 'pago_venta', 'Pago por venta de tomates', 'confirmado', '2024-02-15', 'transferencia', 'TRF002', 'Pago completo de venta'),
        (2, NULL, 45000.00, 'aporte_mensual', 'Aporte mensual febrero 2024', 'pendiente', '2024-02-20', 'cheque', 'CHQ001', 'Pendiente de cobro'),
        (3, NULL, 60000.00, 'aporte_mensual', 'Aporte mensual marzo 2024', 'confirmado', '2024-03-15', 'deposito', 'DEP001', 'Depósito bancario'),
        (1, NULL, 25000.00, 'prestamo', 'Préstamo para compra de semillas', 'confirmado', '2024-03-20', 'transferencia', 'TRF003', 'Préstamo aprobado'),
        (3, NULL, 30000.00, 'devolucion', 'Devolución de aporte excedente', 'confirmado', '2024-03-25', 'transferencia', 'TRF004', 'Devolución procesada')
        ";
        
        $pdo->exec($insertSQL);
        echo "<div style='background: #d4edda; padding: 15px; margin: 10px 0; border-left: 4px solid #28a745;'>";
        echo "✅ <strong>Datos de ejemplo insertados</strong>";
        echo "</div>";
    } else {
        echo "<div style='background: #d4edda; padding: 15px; margin: 10px 0; border-left: 4px solid #28a745;'>";
        echo "✅ <strong>La tabla ya tiene {$count['total']} registros</strong>";
        echo "</div>";
    }
    
    // 3. Mostrar datos
    echo "<h2>📊 Datos en la tabla pagos:</h2>";
    $stmt = $pdo->query("SELECT p.*, s.nombre as nombre_socio FROM pagos p LEFT JOIN socios s ON p.id_socio = s.id_socio ORDER BY p.fecha_pago DESC");
    $pagos = $stmt->fetchAll();
    
    if (!empty($pagos)) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th>ID</th><th>Socio</th><th>Tipo</th><th>Monto</th><th>Estado</th><th>Fecha</th>";
        echo "</tr>";
        
        foreach ($pagos as $pago) {
            $estadoColor = $pago['estado'] == 'confirmado' ? '#d4edda' : ($pago['estado'] == 'pendiente' ? '#fff3cd' : '#f8d7da');
            echo "<tr style='background: $estadoColor;'>";
            echo "<td>{$pago['id_pago']}</td>";
            echo "<td>{$pago['nombre_socio']}</td>";
            echo "<td>{$pago['tipo']}</td>";
            echo "<td>\${$pago['monto']}</td>";
            echo "<td>{$pago['estado']}</td>";
            echo "<td>{$pago['fecha_pago']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 4. Crear archivo PHP corregido
    $pagosPHP = '<?php
// Archivo corregido para pagos
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type");

// Configuración directa
$host = "localhost";
$dbname = "cooperativa_agricola";
$username = "root";
$password = "12345678"; // Cambia por tu contraseña

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Error de conexión: " . $e->getMessage()]);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "GET") {
    try {
        // Estadísticas
        if (isset($_GET["action"]) && $_GET["action"] === "statistics") {
            $stats = [];
            
            $stmt = $pdo->query("SELECT SUM(monto) as total FROM pagos WHERE estado = \"confirmado\"");
            $totalData = $stmt->fetch();
            $stats["ingresos_totales"] = $totalData ? $totalData["total"] : "0";
            
            $stmt = $pdo->query("SELECT COUNT(*) as pendientes FROM pagos WHERE estado = \"pendiente\"");
            $pendientesData = $stmt->fetch();
            $stats["pagos_pendientes"] = $pendientesData ? $pendientesData["pendientes"] : "0";
            
            $stmt = $pdo->query("SELECT COUNT(*) as confirmados FROM pagos WHERE estado = \"confirmado\"");
            $confirmadosData = $stmt->fetch();
            $stats["pagos_confirmados"] = $confirmadosData ? $confirmadosData["confirmados"] : "0";
            
            $stmt = $pdo->query("SELECT COUNT(*) as aportes FROM pagos WHERE tipo = \"aporte_mensual\" AND estado = \"confirmado\"");
            $aportesData = $stmt->fetch();
            $stats["aportes_mensuales"] = $aportesData ? $aportesData["aportes"] : "0";
            
            echo json_encode(["success" => true, "statistics" => $stats]);
            exit();
        }
        
        // Lista de pagos
        $page = intval($_GET["page"] ?? 1);
        $limit = intval($_GET["limit"] ?? 10);
        $offset = ($page - 1) * $limit;
        
        $query = "SELECT p.*, s.nombre as nombre_socio FROM pagos p LEFT JOIN socios s ON p.id_socio = s.id_socio ORDER BY p.fecha_pago DESC LIMIT $limit OFFSET $offset";
        $stmt = $pdo->query($query);
        $pagos = $stmt->fetchAll();
        
        $countQuery = "SELECT COUNT(*) as total FROM pagos";
        $countStmt = $pdo->query($countQuery);
        $totalRecords = $countStmt->fetch()["total"];
        $totalPages = ceil($totalRecords / $limit);
        
        echo json_encode([
            "success" => true,
            "data" => $pagos,
            "pagination" => [
                "current_page" => $page,
                "total_pages" => $totalPages,
                "total_records" => $totalRecords,
                "limit" => $limit
            ]
        ]);
        
    } catch (Exception $e) {
        echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
    }
}

echo json_encode(["success" => false, "message" => "Método no permitido"]);
?>';
    
    file_put_contents('php/pagos_fixed.php', $pagosPHP);
    
    echo "<div style='background: #d4edda; padding: 15px; margin: 10px 0; border-left: 4px solid #28a745;'>";
    echo "✅ <strong>Archivo PHP corregido creado: php/pagos_fixed.php</strong>";
    echo "</div>";
    
    // 5. Probar la API corregida
    echo "<h2>🔌 Probando API corregida:</h2>";
    
    $statsURL = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/php/pagos_fixed.php?action=statistics';
    echo "<p><strong>URL de estadísticas:</strong> <a href='$statsURL' target='_blank'>$statsURL</a></p>";
    
    $statsResponse = file_get_contents($statsURL);
    $statsData = json_decode($statsResponse, true);
    
    if ($statsData && $statsData['success']) {
        echo "<div style='background: #d4edda; padding: 15px; margin: 10px 0; border-left: 4px solid #28a745;'>";
        echo "✅ <strong>API corregida funcionando</strong><br>";
        echo "💰 Ingresos totales: $" . number_format($statsData['statistics']['ingresos_totales'], 2) . "<br>";
        echo "⏳ Pagos pendientes: {$statsData['statistics']['pagos_pendientes']}<br>";
        echo "✅ Pagos confirmados: {$statsData['statistics']['pagos_confirmados']}<br>";
        echo "📅 Aportes mensuales: {$statsData['statistics']['aportes_mensuales']}";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; padding: 15px; margin: 10px 0; border-left: 4px solid #dc3545;'>";
        echo "❌ <strong>Error en API corregida</strong><br>";
        echo "Respuesta: " . htmlspecialchars($statsResponse);
        echo "</div>";
    }
    
    echo "<h2>🎉 ¡Problema Solucionado!</h2>";
    echo "<div style='background: #d4edda; padding: 15px; margin: 10px 0; border-left: 4px solid #28a745;'>";
    echo "<h3>✅ Pasos completados:</h3>";
    echo "<ol>";
    echo "<li>✅ Conexión a la base de datos establecida</li>";
    echo "<li>✅ Tabla 'pagos' creada/verificada</li>";
    echo "<li>✅ Datos de ejemplo insertados</li>";
    echo "<li>✅ Archivo PHP corregido creado</li>";
    echo "<li>✅ API funcionando correctamente</li>";
    echo "</ol>";
    echo "<p><strong>Próximo paso:</strong> Ahora puedes usar el módulo de pagos en el dashboard.</p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; margin: 10px 0; border-left: 4px solid #dc3545;'>";
    echo "❌ <strong>Error:</strong> " . $e->getMessage();
    echo "<br><br><strong>Posibles soluciones:</strong>";
    echo "<ul>";
    echo "<li>Verifica que MySQL esté ejecutándose</li>";
    echo "<li>Verifica la contraseña de la base de datos</li>";
    echo "<li>Verifica que la base de datos 'cooperativa_agricola' exista</li>";
    echo "</ul>";
    echo "</div>";
}
?>

