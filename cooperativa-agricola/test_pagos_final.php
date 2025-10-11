<?php
// Test final para verificar que el módulo de pagos funciona
header('Content-Type: text/html; charset=utf-8');

echo "<h1>🔧 Test Final - Módulo de Pagos</h1>";

try {
    require_once 'php/config.php';
    $pdo = conectarDB();
    
    echo "<div style='background: #d4edda; padding: 15px; margin: 10px 0; border-left: 4px solid #28a745;'>";
    echo "✅ <strong>Conexión exitosa a la base de datos</strong>";
    echo "</div>";
    
    // Verificar si la tabla pagos existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'pagos'");
    $tableExists = $stmt->fetch();
    
    if (!$tableExists) {
        echo "<div style='background: #f8d7da; padding: 15px; margin: 10px 0; border-left: 4px solid #dc3545;'>";
        echo "❌ <strong>La tabla 'pagos' NO existe</strong><br>";
        echo "<strong>Solución:</strong> Ejecuta el archivo <code>crear_tabla_pagos.sql</code> en phpMyAdmin";
        echo "</div>";
        exit();
    }
    
    echo "<div style='background: #d4edda; padding: 15px; margin: 10px 0; border-left: 4px solid #28a745;'>";
    echo "✅ <strong>La tabla 'pagos' existe</strong>";
    echo "</div>";
    
    // Contar registros
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM pagos");
    $count = $stmt->fetch();
    
    echo "<div style='background: #d4edda; padding: 15px; margin: 10px 0; border-left: 4px solid #28a745;'>";
    echo "✅ <strong>Total de registros: {$count['total']}</strong>";
    echo "</div>";
    
    // Probar la API de pagos
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
    
    // Verificar socios
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
    
    // Resumen final
    echo "<h2>🎉 Resumen Final</h2>";
    
    if ($statsData && $statsData['success'] && $listData && $listData['success'] && $sociosData && $sociosData['success']) {
        echo "<div style='background: #d4edda; padding: 15px; margin: 10px 0; border-left: 4px solid #28a745;'>";
        echo "<h3>✅ ¡Todo funcionando correctamente!</h3>";
        echo "<p>El módulo de pagos está listo para usar en el dashboard.</p>";
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
