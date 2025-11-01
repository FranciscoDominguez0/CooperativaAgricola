<?php
// Test directo del sistema de reportes
require_once 'php/conexion.php';

echo "<h1>🧪 Test Directo del Sistema de Reportes</h1>";

try {
    $pdo = conectarDB();
    
    // Simular parámetros como en reportes.php
    $dateFrom = $_GET['dateFrom'] ?? date('Y-m-01');
    $dateTo = $_GET['dateTo'] ?? date('Y-m-t');
    
    echo "<h2>📅 Período de consulta: $dateFrom a $dateTo</h2>";
    
    // Test 1: Verificar tablas existentes
    $stmt = $pdo->query("SHOW TABLES");
    $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h3>📊 Tablas disponibles:</h3><ul>";
    foreach ($existingTables as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul>";
    
    // Test 2: Probar consulta de KPIs
    echo "<h2>💰 Test de KPIs</h2>";
    
    $kpis = [
        'totalIncome' => 0,
        'incomeChange' => 0,
        'totalContributions' => 0,
        'activeMembers' => 0,
        'inventoryValue' => 0,
        'availableItems' => 0,
        'grossMargin' => 0
    ];
    
    // Total de ingresos del período (tabla ventas)
    if (in_array('ventas', $existingTables)) {
        echo "<h3>🛒 Procesando ventas...</h3>";
        
        // Verificar total de ventas
        $stmt = $pdo->query("SELECT COUNT(*) as total_ventas FROM ventas");
        $totalVentas = $stmt->fetch()['total_ventas'];
        echo "<p>Total de ventas en BD: <strong>$totalVentas</strong></p>";
        
        // Consulta de ingresos del período
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(total), 0) as total_income 
            FROM ventas 
            WHERE fecha_venta >= ? AND fecha_venta <= ? AND estado = 'pagado'
        ");
        $stmt->execute([$dateFrom, $dateTo]);
        $result = $stmt->fetch();
        $kpis['totalIncome'] = $result['total_income'];
        
        echo "<p>Ingresos del período: <strong>$" . number_format($kpis['totalIncome']) . "</strong></p>";
        
        // Mostrar algunas ventas del período para verificar
        $stmt = $pdo->prepare("
            SELECT fecha_venta, producto, total, estado 
            FROM ventas 
            WHERE fecha_venta >= ? AND fecha_venta <= ?
            ORDER BY fecha_venta DESC
            LIMIT 5
        ");
        $stmt->execute([$dateFrom, $dateTo]);
        $ventasPeriodo = $stmt->fetchAll();
        
        if (count($ventasPeriodo) > 0) {
            echo "<h4>Ventas del período:</h4>";
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>Fecha</th><th>Producto</th><th>Total</th><th>Estado</th></tr>";
            foreach ($ventasPeriodo as $venta) {
                echo "<tr>";
                echo "<td>{$venta['fecha_venta']}</td>";
                echo "<td>{$venta['producto']}</td>";
                echo "<td>$" . number_format($venta['total']) . "</td>";
                echo "<td>{$venta['estado']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>⚠️ No hay ventas en el período seleccionado</p>";
        }
        
        // Cambio porcentual vs período anterior
        $periodLength = (strtotime($dateTo) - strtotime($dateFrom)) / (60 * 60 * 24);
        $previousStart = date('Y-m-d', strtotime($dateFrom) - $periodLength);
        $previousEnd = date('Y-m-d', strtotime($dateFrom) - 1);
        
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(total), 0) as previous_income 
            FROM ventas 
            WHERE fecha_venta >= ? AND fecha_venta <= ? AND estado = 'pagado'
        ");
        $stmt->execute([$previousStart, $previousEnd]);
        $previousIncome = $stmt->fetch()['previous_income'];
        
        $kpis['incomeChange'] = $previousIncome > 0 ? 
            (($kpis['totalIncome'] - $previousIncome) / $previousIncome) * 100 : 0;
            
        echo "<p>Ingresos período anterior ($previousStart a $previousEnd): <strong>$" . number_format($previousIncome) . "</strong></p>";
        echo "<p>Cambio porcentual: <strong>" . round($kpis['incomeChange'], 2) . "%</strong></p>";
        
    } else {
        echo "<p>⚠️ Tabla 'ventas' no encontrada</p>";
    }
    
    // Test 3: Probar endpoint de reportes.php
    echo "<h2>🔗 Test del endpoint reportes.php</h2>";
    
    $url = "php/reportes.php?action=kpis&dateFrom=$dateFrom&dateTo=$dateTo";
    echo "<p>URL de prueba: <a href='$url' target='_blank'>$url</a></p>";
    
    // Hacer la petición
    $response = file_get_contents($url);
    $data = json_decode($response, true);
    
    if ($data) {
        echo "<h3>Respuesta del endpoint:</h3>";
        echo "<pre>" . json_encode($data, JSON_PRETTY_PRINT) . "</pre>";
    } else {
        echo "<p>❌ Error al obtener respuesta del endpoint</p>";
    }
    
    // Test 4: Verificar datos de octubre específicamente
    echo "<h2>🍂 Datos de Octubre (como en tu ejemplo)</h2>";
    
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as numero_ventas,
            SUM(total) as monto_total
        FROM ventas
        WHERE MONTH(fecha_venta) = 10 
          AND YEAR(fecha_venta) = YEAR(CURDATE())
    ");
    $stmt->execute();
    $octubre = $stmt->fetch();
    
    echo "<p>Ventas en octubre: <strong>{$octubre['numero_ventas']}</strong></p>";
    echo "<p>Monto total octubre: <strong>$" . number_format($octubre['monto_total']) . "</strong></p>";
    
} catch (Exception $e) {
    echo "<h2>❌ Error:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    background-color: #f5f5f5;
}
h1, h2, h3 {
    color: #2d5016;
}
table {
    background-color: white;
    margin: 10px 0;
    width: 100%;
}
th, td {
    padding: 8px;
    text-align: left;
    border: 1px solid #ddd;
}
th {
    background-color: #2d5016;
    color: white;
}
pre {
    background-color: #f8f8f8;
    padding: 10px;
    border-radius: 5px;
    overflow-x: auto;
}
</style>
