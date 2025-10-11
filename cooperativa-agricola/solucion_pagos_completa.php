<?php
// Solución completa para el módulo de pagos
header('Content-Type: text/html; charset=utf-8');

echo "<h1>🔧 Solución Completa para Módulo de Pagos</h1>";

try {
    require_once 'php/config.php';
    $pdo = conectarDB();
    
    echo "<div style='background: #d4edda; padding: 15px; margin: 10px 0; border-left: 4px solid #28a745;'>";
    echo "✅ <strong>Conexión a la base de datos exitosa</strong>";
    echo "</div>";
    
    // 1. Verificar si la tabla pagos existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'pagos'");
    $tableExists = $stmt->fetch();
    
    if (!$tableExists) {
        echo "<div style='background: #f8d7da; padding: 15px; margin: 10px 0; border-left: 4px solid #dc3545;'>";
        echo "❌ <strong>La tabla 'pagos' NO existe</strong><br>";
        echo "Creando la tabla ahora...";
        echo "</div>";
        
        // Crear la tabla pagos
        $createTableSQL = "
        CREATE TABLE pagos (
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
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (id_socio) REFERENCES socios(id_socio) ON DELETE CASCADE,
            FOREIGN KEY (id_venta) REFERENCES ventas(id_venta) ON DELETE SET NULL
        )";
        
        $pdo->exec($createTableSQL);
        
        echo "<div style='background: #d4edda; padding: 15px; margin: 10px 0; border-left: 4px solid #28a745;'>";
        echo "✅ <strong>Tabla 'pagos' creada exitosamente</strong>";
        echo "</div>";
    } else {
        echo "<div style='background: #d4edda; padding: 15px; margin: 10px 0; border-left: 4px solid #28a745;'>";
        echo "✅ <strong>La tabla 'pagos' ya existe</strong>";
        echo "</div>";
    }
    
    // 2. Verificar si hay datos
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM pagos");
    $count = $stmt->fetch();
    
    if ($count['total'] == 0) {
        echo "<div style='background: #fff3cd; padding: 15px; margin: 10px 0; border-left: 4px solid #ffc107;'>";
        echo "⚠️ <strong>La tabla está vacía. Insertando datos de ejemplo...</strong>";
        echo "</div>";
        
        // Insertar datos de ejemplo
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
        echo "✅ <strong>Datos de ejemplo insertados exitosamente</strong>";
        echo "</div>";
    } else {
        echo "<div style='background: #d4edda; padding: 15px; margin: 10px 0; border-left: 4px solid #28a745;'>";
        echo "✅ <strong>La tabla ya tiene {$count['total']} registros</strong>";
        echo "</div>";
    }
    
    // 3. Probar la API de pagos
    echo "<h2>🔌 Probando la API de pagos...</h2>";
    
    // Probar estadísticas
    $statsURL = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/php/pagos.php?action=statistics';
    echo "<p><strong>URL de estadísticas:</strong> <a href='$statsURL' target='_blank'>$statsURL</a></p>";
    
    $statsResponse = file_get_contents($statsURL);
    $statsData = json_decode($statsResponse, true);
    
    if ($statsData && $statsData['success']) {
        echo "<div style='background: #d4edda; padding: 15px; margin: 10px 0; border-left: 4px solid #28a745;'>";
        echo "✅ <strong>API de estadísticas funcionando</strong><br>";
        echo "💰 Ingresos totales: $" . number_format($statsData['statistics']['ingresos_totales'], 2) . "<br>";
        echo "⏳ Pagos pendientes: {$statsData['statistics']['pagos_pendientes']}<br>";
        echo "✅ Pagos confirmados: {$statsData['statistics']['pagos_confirmados']}<br>";
        echo "📅 Aportes mensuales: {$statsData['statistics']['aportes_mensuales']}";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; padding: 15px; margin: 10px 0; border-left: 4px solid #dc3545;'>";
        echo "❌ <strong>Error en API de estadísticas</strong><br>";
        echo "Respuesta: " . htmlspecialchars($statsResponse);
        echo "</div>";
    }
    
    // Probar lista de pagos
    $listURL = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/php/pagos.php?page=1&limit=10';
    echo "<p><strong>URL de lista:</strong> <a href='$listURL' target='_blank'>$listURL</a></p>";
    
    $listResponse = file_get_contents($listURL);
    $listData = json_decode($listResponse, true);
    
    if ($listData && $listData['success']) {
        echo "<div style='background: #d4edda; padding: 15px; margin: 10px 0; border-left: 4px solid #28a745;'>";
        echo "✅ <strong>API de lista funcionando</strong><br>";
        echo "📊 Registros en la respuesta: " . count($listData['data']) . "<br>";
        echo "📄 Páginas totales: {$listData['pagination']['total_pages']}";
        echo "</div>";
        
        // Mostrar algunos datos
        if (!empty($listData['data'])) {
            echo "<h3>📋 Datos de ejemplo:</h3>";
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr style='background: #f0f0f0;'>";
            echo "<th>ID</th><th>Socio</th><th>Tipo</th><th>Monto</th><th>Estado</th><th>Fecha</th>";
            echo "</tr>";
            
            foreach (array_slice($listData['data'], 0, 5) as $pago) {
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
    } else {
        echo "<div style='background: #f8d7da; padding: 15px; margin: 10px 0; border-left: 4px solid #dc3545;'>";
        echo "❌ <strong>Error en API de lista</strong><br>";
        echo "Respuesta: " . htmlspecialchars($listResponse);
        echo "</div>";
    }
    
    // 4. Verificar socios
    echo "<h2>👥 Verificando socios...</h2>";
    $sociosURL = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/php/socios.php';
    $sociosResponse = file_get_contents($sociosURL);
    $sociosData = json_decode($sociosResponse, true);
    
    if ($sociosData && $sociosData['success']) {
        echo "<div style='background: #d4edda; padding: 15px; margin: 10px 0; border-left: 4px solid #28a745;'>";
        echo "✅ <strong>API de socios funcionando</strong><br>";
        echo "👥 Total de socios: " . count($sociosData['data']);
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; padding: 15px; margin: 10px 0; border-left: 4px solid #dc3545;'>";
        echo "❌ <strong>Error en API de socios</strong><br>";
        echo "Respuesta: " . htmlspecialchars($sociosResponse);
        echo "</div>";
    }
    
    // 5. Resumen final
    echo "<h2>🎉 Resumen Final</h2>";
    
    if ($statsData && $statsData['success'] && $listData && $listData['success'] && $sociosData && $sociosData['success']) {
        echo "<div style='background: #d4edda; padding: 15px; margin: 10px 0; border-left: 4px solid #28a745;'>";
        echo "<h3>✅ ¡Todo funcionando correctamente!</h3>";
        echo "<p>El módulo de pagos debería funcionar ahora en el dashboard.</p>";
        echo "<p><strong>Próximos pasos:</strong></p>";
        echo "<ol>";
        echo "<li>Abre <code>dashboard.html</code></li>";
        echo "<li>Haz clic en 'Pagos' en el menú lateral</li>";
        echo "<li>Deberías ver las estadísticas y la tabla con datos</li>";
        echo "</ol>";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; padding: 15px; margin: 10px 0; border-left: 4px solid #dc3545;'>";
        echo "<h3>❌ Hay problemas que necesitan ser corregidos</h3>";
        echo "<p>Revisa los errores mostrados arriba y corrígelos.</p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; margin: 10px 0; border-left: 4px solid #dc3545;'>";
    echo "❌ <strong>Error general:</strong> " . $e->getMessage();
    echo "</div>";
}
?>
