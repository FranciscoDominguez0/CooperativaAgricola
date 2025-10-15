<?php
// Test para verificar que los reportes funcionen después de las optimizaciones
header('Content-Type: application/json');

echo "<h1>🔧 Test de Reportes - Verificación Post-Optimización</h1>";

try {
    // Test 1: Verificar conexión a la base de datos
    echo "<h2>1. Verificando conexión a la base de datos...</h2>";
    
    require_once 'php/conexion.php';
    $pdo = conectarDB();
    
    if ($pdo) {
        echo "<p>✅ Conexión a la base de datos: EXITOSA</p>";
    } else {
        echo "<p>❌ Error: No se pudo conectar a la base de datos</p>";
        exit;
    }
    
    // Test 2: Verificar tablas existentes
    echo "<h2>2. Verificando tablas...</h2>";
    
    $stmt = $pdo->query("SHOW TABLES");
    $tablas = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<p>Tablas encontradas: " . implode(', ', $tablas) . "</p>";
    
    // Test 3: Probar consulta de ventas
    echo "<h2>3. Probando consulta de ventas...</h2>";
    
    if (in_array('ventas', $tablas)) {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM ventas");
        $totalVentas = $stmt->fetch()['total'];
        echo "<p>✅ Total de ventas: $totalVentas</p>";
        
        // Probar consulta con filtros de fecha
        $dateFrom = date('Y-m-01');
        $dateTo = date('Y-m-t');
        
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(total), 0) as total_income 
            FROM ventas 
            WHERE fecha_venta >= ? AND fecha_venta <= ?
        ");
        $stmt->execute([$dateFrom, $dateTo]);
        $ingresos = $stmt->fetch()['total_income'];
        
        echo "<p>✅ Ingresos del mes: $" . number_format($ingresos) . "</p>";
    } else {
        echo "<p>⚠️ Tabla 'ventas' no encontrada</p>";
    }
    
    // Test 4: Probar endpoint de reportes
    echo "<h2>4. Probando endpoint de reportes...</h2>";
    
    $url = "php/reportes.php?action=kpis&dateFrom=$dateFrom&dateTo=$dateTo";
    echo "<p>URL: $url</p>";
    
    // Simular la petición
    $_GET['action'] = 'kpis';
    $_GET['dateFrom'] = $dateFrom;
    $_GET['dateTo'] = $dateTo;
    
    ob_start();
    include 'php/reportes.php';
    $output = ob_get_clean();
    
    $data = json_decode($output, true);
    
    if ($data && isset($data['success'])) {
        echo "<p>✅ Endpoint de reportes: FUNCIONANDO</p>";
        echo "<p>Datos KPIs: " . json_encode($data['kpis'], JSON_PRETTY_PRINT) . "</p>";
    } else {
        echo "<p>❌ Error en endpoint de reportes</p>";
        echo "<p>Respuesta: $output</p>";
    }
    
    // Test 5: Probar endpoint de debug
    echo "<h2>5. Probando endpoint de debug...</h2>";
    
    if (file_exists('debug_reportes_simple.php')) {
        $debugUrl = "debug_reportes_simple.php";
        echo "<p>✅ Archivo de debug encontrado</p>";
        
        // Simular petición al debug
        ob_start();
        include $debugUrl;
        $debugOutput = ob_get_clean();
        
        $debugData = json_decode($debugOutput, true);
        
        if ($debugData && isset($debugData['success'])) {
            echo "<p>✅ Endpoint de debug: FUNCIONANDO</p>";
        } else {
            echo "<p>⚠️ Endpoint de debug con problemas</p>";
        }
    } else {
        echo "<p>⚠️ Archivo de debug no encontrado</p>";
    }
    
    // Test 6: Verificar JavaScript
    echo "<h2>6. Verificando archivos JavaScript...</h2>";
    
    $jsFiles = ['js/reportes.js', 'js/dashboard.js'];
    foreach ($jsFiles as $jsFile) {
        if (file_exists($jsFile)) {
            echo "<p>✅ $jsFile: EXISTE</p>";
        } else {
            echo "<p>❌ $jsFile: NO ENCONTRADO</p>";
        }
    }
    
    echo "<h2>✅ Test Completado</h2>";
    echo "<p>Si todos los tests pasan, los reportes deberían funcionar correctamente.</p>";
    
} catch (Exception $e) {
    echo "<h2>❌ Error en el test:</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "<p>Archivo: " . $e->getFile() . "</p>";
    echo "<p>Línea: " . $e->getLine() . "</p>";
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 1000px;
    margin: 0 auto;
    padding: 20px;
    background-color: #f5f5f5;
}
h1, h2 {
    color: #2d5016;
}
p {
    background-color: white;
    padding: 10px;
    border-radius: 5px;
    margin: 5px 0;
}
pre {
    background-color: #f8f8f8;
    padding: 10px;
    border-radius: 5px;
    overflow-x: auto;
}
</style>
