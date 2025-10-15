<?php
// Test para verificar consultas con datos reales
require_once 'php/conexion.php';

echo "<h1>🔍 Test de Consultas con Datos Reales</h1>";

try {
    $pdo = conectarDB();
    
    // Test 1: Verificar datos de ventas de octubre
    echo "<h2>📊 Datos de Ventas de Octubre</h2>";
    
    $stmt = $pdo->prepare("
        SELECT 
            v.id_venta,
            v.fecha_venta,
            v.producto,
            v.cantidad,
            v.precio_unitario,
            v.total,
            v.cliente,
            v.estado,
            v.metodo_pago
        FROM ventas v
        WHERE MONTH(v.fecha_venta) = 10 
          AND YEAR(v.fecha_venta) = YEAR(CURDATE())
        ORDER BY v.fecha_venta DESC
        LIMIT 10
    ");
    $stmt->execute();
    $ventas = $stmt->fetchAll();
    
    if (count($ventas) > 0) {
        echo "<p>✅ Se encontraron " . count($ventas) . " ventas en octubre</p>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Fecha</th><th>Producto</th><th>Cantidad</th><th>Total</th><th>Estado</th></tr>";
        foreach ($ventas as $venta) {
            echo "<tr>";
            echo "<td>{$venta['id_venta']}</td>";
            echo "<td>{$venta['fecha_venta']}</td>";
            echo "<td>{$venta['producto']}</td>";
            echo "<td>{$venta['cantidad']}</td>";
            echo "<td>$" . number_format($venta['total']) . "</td>";
            echo "<td>{$venta['estado']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>⚠️ No se encontraron ventas en octubre</p>";
    }
    
    // Test 2: Resumen total de octubre
    echo "<h2>💰 Resumen Total de Octubre</h2>";
    
    $stmt = $pdo->prepare("
        SELECT 
            'TOTAL OCTUBRE' as concepto,
            COUNT(*) as numero_ventas,
            SUM(total) as monto_total
        FROM ventas
        WHERE MONTH(fecha_venta) = 10 
          AND YEAR(fecha_venta) = YEAR(CURDATE())
    ");
    $stmt->execute();
    $resumen = $stmt->fetch();
    
    echo "<p><strong>Número de ventas:</strong> {$resumen['numero_ventas']}</p>";
    echo "<p><strong>Monto total:</strong> $" . number_format($resumen['monto_total']) . "</p>";
    
    // Test 3: Probar consulta de KPIs como en reportes.php
    echo "<h2>📈 Test de KPIs (como en reportes.php)</h2>";
    
    $dateFrom = date('Y-m-01'); // Primer día del mes actual
    $dateTo = date('Y-m-t');    // Último día del mes actual
    
    echo "<p>Período: $dateFrom a $dateTo</p>";
    
    // Total de ingresos del período
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(total), 0) as total_income 
        FROM ventas 
        WHERE fecha_venta >= ? AND fecha_venta <= ? AND estado = 'pagado'
    ");
    $stmt->execute([$dateFrom, $dateTo]);
    $ingresos = $stmt->fetch();
    
    echo "<p><strong>Ingresos del mes actual:</strong> $" . number_format($ingresos['total_income']) . "</p>";
    
    // Test 4: Verificar todas las tablas
    echo "<h2>🗄️ Verificar Estructura de Base de Datos</h2>";
    
    $stmt = $pdo->query("SHOW TABLES");
    $tablas = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<p>Tablas encontradas:</p><ul>";
    foreach ($tablas as $tabla) {
        echo "<li>$tabla</li>";
    }
    echo "</ul>";
    
    // Test 5: Verificar estructura de tabla ventas
    echo "<h2>🔍 Estructura de Tabla Ventas</h2>";
    
    $stmt = $pdo->query("DESCRIBE ventas");
    $columnas = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columnas as $columna) {
        echo "<tr>";
        echo "<td>{$columna['Field']}</td>";
        echo "<td>{$columna['Type']}</td>";
        echo "<td>{$columna['Null']}</td>";
        echo "<td>{$columna['Key']}</td>";
        echo "<td>{$columna['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
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
h1, h2 {
    color: #2d5016;
}
table {
    background-color: white;
    margin: 10px 0;
}
th, td {
    padding: 8px;
    text-align: left;
}
th {
    background-color: #2d5016;
    color: white;
}
</style>
