<?php
// Archivo para verificar el estado de la tabla pagos
header('Content-Type: text/html; charset=utf-8');

echo "<h2>🔍 Verificación de la Tabla Pagos</h2>";

try {
    require_once 'php/config.php';
    $pdo = conectarDB();
    
    echo "✅ <strong>Conexión exitosa</strong><br><br>";
    
    // Verificar si la tabla existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'pagos'");
    $tableExists = $stmt->fetch();
    
    if (!$tableExists) {
        echo "❌ <strong>La tabla 'pagos' NO existe</strong><br>";
        echo "<h3>🔧 Solución:</h3>";
        echo "<ol>";
        echo "<li>Abre phpMyAdmin</li>";
        echo "<li>Selecciona la base de datos 'cooperativa_agricola'</li>";
        echo "<li>Ve a la pestaña 'SQL'</li>";
        echo "<li>Copia y pega este código:</li>";
        echo "</ol>";
        
        echo "<textarea style='width: 100%; height: 200px;'>";
        echo "CREATE TABLE IF NOT EXISTS pagos (\n";
        echo "    id_pago INT AUTO_INCREMENT PRIMARY KEY,\n";
        echo "    id_socio INT NOT NULL,\n";
        echo "    id_venta INT NULL,\n";
        echo "    monto DECIMAL(10,2) NOT NULL,\n";
        echo "    tipo ENUM('aporte_mensual', 'aporte_extraordinario', 'pago_venta', 'prestamo', 'devolucion') NOT NULL,\n";
        echo "    descripcion TEXT,\n";
        echo "    estado ENUM('pendiente', 'confirmado', 'rechazado') DEFAULT 'pendiente',\n";
        echo "    fecha_pago DATE NOT NULL,\n";
        echo "    metodo_pago ENUM('efectivo', 'transferencia', 'cheque', 'deposito') DEFAULT 'efectivo',\n";
        echo "    numero_comprobante VARCHAR(50),\n";
        echo "    observaciones TEXT,\n";
        echo "    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,\n";
        echo "    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,\n";
        echo "    FOREIGN KEY (id_socio) REFERENCES socios(id_socio) ON DELETE CASCADE,\n";
        echo "    FOREIGN KEY (id_venta) REFERENCES ventas(id_venta) ON DELETE SET NULL\n";
        echo ");";
        echo "</textarea>";
        exit();
    }
    
    echo "✅ <strong>La tabla 'pagos' existe</strong><br>";
    
    // Contar registros
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM pagos");
    $count = $stmt->fetch();
    echo "📊 <strong>Total de registros: {$count['total']}</strong><br><br>";
    
    if ($count['total'] == 0) {
        echo "⚠️ <strong>La tabla está vacía</strong><br>";
        echo "<h3>🔧 Insertar datos de ejemplo:</h3>";
        echo "<textarea style='width: 100%; height: 150px;'>";
        echo "INSERT INTO pagos (id_socio, id_venta, monto, tipo, descripcion, estado, fecha_pago, metodo_pago, numero_comprobante, observaciones) VALUES\n";
        echo "(1, 1, 50000.00, 'aporte_mensual', 'Aporte mensual enero 2024', 'confirmado', '2024-01-15', 'transferencia', 'TRF001', 'Pago puntual'),\n";
        echo "(1, NULL, 100000.00, 'aporte_extraordinario', 'Aporte para mejoras de infraestructura', 'confirmado', '2024-02-10', 'efectivo', 'EFE001', 'Contribución voluntaria'),\n";
        echo "(2, 2, 75000.00, 'pago_venta', 'Pago por venta de tomates', 'confirmado', '2024-02-15', 'transferencia', 'TRF002', 'Pago completo de venta'),\n";
        echo "(2, NULL, 45000.00, 'aporte_mensual', 'Aporte mensual febrero 2024', 'pendiente', '2024-02-20', 'cheque', 'CHQ001', 'Pendiente de cobro'),\n";
        echo "(3, NULL, 60000.00, 'aporte_mensual', 'Aporte mensual marzo 2024', 'confirmado', '2024-03-15', 'deposito', 'DEP001', 'Depósito bancario');";
        echo "</textarea>";
        exit();
    }
    
    // Mostrar datos
    echo "<h3>📋 Datos en la tabla:</h3>";
    $stmt = $pdo->query("SELECT p.*, s.nombre as nombre_socio FROM pagos p LEFT JOIN socios s ON p.id_socio = s.id_socio ORDER BY p.fecha_pago DESC");
    $pagos = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>ID</th><th>Socio</th><th>Tipo</th><th>Monto</th><th>Estado</th><th>Fecha</th><th>Método</th>";
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
        echo "<td>{$pago['metodo_pago']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Probar la API
    echo "<h3>🔌 Probando la API de pagos:</h3>";
    
    // Probar estadísticas
    $url = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/php/pagos.php?action=statistics';
    echo "<p>URL de estadísticas: <a href='$url' target='_blank'>$url</a></p>";
    
    $response = file_get_contents($url);
    $data = json_decode($response, true);
    
    if ($data && $data['success']) {
        echo "✅ <strong>API de estadísticas funcionando</strong><br>";
        echo "💰 Ingresos totales: $" . number_format($data['statistics']['ingresos_totales'], 2) . "<br>";
        echo "⏳ Pagos pendientes: {$data['statistics']['pagos_pendientes']}<br>";
        echo "✅ Pagos confirmados: {$data['statistics']['pagos_confirmados']}<br>";
        echo "📅 Aportes mensuales: {$data['statistics']['aportes_mensuales']}<br>";
    } else {
        echo "❌ <strong>Error en la API de estadísticas</strong><br>";
        echo "Respuesta: " . htmlspecialchars($response);
    }
    
    // Probar lista de pagos
    $url2 = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/php/pagos.php?page=1&limit=10';
    echo "<p>URL de lista: <a href='$url2' target='_blank'>$url2</a></p>";
    
    $response2 = file_get_contents($url2);
    $data2 = json_decode($response2, true);
    
    if ($data2 && $data2['success']) {
        echo "✅ <strong>API de lista funcionando</strong><br>";
        echo "📊 Registros en la respuesta: " . count($data2['data']) . "<br>";
    } else {
        echo "❌ <strong>Error en la API de lista</strong><br>";
        echo "Respuesta: " . htmlspecialchars($response2);
    }
    
} catch (Exception $e) {
    echo "❌ <strong>Error:</strong> " . $e->getMessage() . "<br>";
}
?>
