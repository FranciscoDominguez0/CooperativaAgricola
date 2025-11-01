<?php
// Archivo simple para probar el módulo de pagos
header('Content-Type: text/html; charset=utf-8');

echo "<h2>🔧 Prueba del Módulo de Pagos</h2>";

try {
    require_once 'php/config.php';
    $pdo = conectarDB();
    
    echo "✅ <strong>Conexión a la base de datos exitosa</strong><br>";
    
    // Verificar si la tabla pagos existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'pagos'");
    $tableExists = $stmt->fetch();
    
    if (!$tableExists) {
        echo "❌ <strong>La tabla 'pagos' no existe</strong><br>";
        echo "<p><strong>Solución:</strong> Ejecuta el archivo <code></code> en phpMyAdmin</p>";
        echo "<ol>";
        echo "<li>Abre phpMyAdmin</li>";
        echo "<li>Selecciona la base de datos 'cooperativa_agricola'</li>";
        echo "<li>Ve a la pestaña 'SQL'</li>";
        echo "<li>Copia y pega el contenido de <code></code></li>";
        echo "<li>Haz clic en 'Ejecutar'</li>";
        echo "</ol>";
        exit();
    }
    
    echo "✅ <strong>La tabla 'pagos' existe</strong><br>";
    
    // Contar registros
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM pagos");
    $count = $stmt->fetch();
    echo "✅ <strong>Total de pagos: {$count['total']}</strong><br>";
    
    // Probar las estadísticas
    echo "<h3>📊 Probando estadísticas...</h3>";
    
    $stats = [];
    
    // Ingresos totales
    $stmt = $pdo->query("SELECT SUM(monto) as total FROM pagos WHERE estado = 'confirmado'");
    $totalData = $stmt->fetch();
    $stats['ingresos_totales'] = $totalData ? $totalData['total'] : '0';
    echo "💰 Ingresos totales: $" . number_format($stats['ingresos_totales'], 2) . "<br>";
    
    // Pagos pendientes
    $stmt = $pdo->query("SELECT COUNT(*) as pendientes FROM pagos WHERE estado = 'pendiente'");
    $pendientesData = $stmt->fetch();
    $stats['pagos_pendientes'] = $pendientesData ? $pendientesData['pendientes'] : '0';
    echo "⏳ Pagos pendientes: {$stats['pagos_pendientes']}<br>";
    
    // Pagos confirmados
    $stmt = $pdo->query("SELECT COUNT(*) as confirmados FROM pagos WHERE estado = 'confirmado'");
    $confirmadosData = $stmt->fetch();
    $stats['pagos_confirmados'] = $confirmadosData ? $confirmadosData['confirmados'] : '0';
    echo "✅ Pagos confirmados: {$stats['pagos_confirmados']}<br>";
    
    // Aportes mensuales
    $stmt = $pdo->query("SELECT COUNT(*) as aportes FROM pagos WHERE tipo = 'aporte_mensual' AND estado = 'confirmado'");
    $aportesData = $stmt->fetch();
    $stats['aportes_mensuales'] = $aportesData ? $aportesData['aportes'] : '0';
    echo "📅 Aportes mensuales: {$stats['aportes_mensuales']}<br>";
    
    // Mostrar algunos datos
    echo "<h3>📋 Datos de ejemplo:</h3>";
    $stmt = $pdo->query("SELECT p.*, s.nombre as nombre_socio FROM pagos p LEFT JOIN socios s ON p.id_socio = s.id_socio LIMIT 5");
    $pagos = $stmt->fetchAll();
    
    if (!empty($pagos)) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Socio</th><th>Tipo</th><th>Monto</th><th>Estado</th><th>Fecha</th></tr>";
        foreach ($pagos as $pago) {
            echo "<tr>";
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
    
    echo "<hr>";
    echo "<h3>🎉 ¡Módulo de pagos funcionando correctamente!</h3>";
    echo "<p>Ahora puedes usar el módulo de pagos en <code></code></p>";
    
} catch (Exception $e) {
    echo "❌ <strong>Error:</strong> " . $e->getMessage() . "<br>";
    echo "<h3>🔧 Solución:</h3>";
    echo "<ol>";
    echo "<li>Verifica que MySQL esté ejecutándose</li>";
    echo "<li>Verifica la configuración en <code>php/config.php</code></li>";
    echo "<li>Ejecuta el archivo <code></code> en phpMyAdmin</li>";
    echo "</ol>";
}
?>




