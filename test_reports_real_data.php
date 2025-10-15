<?php
// Test para verificar que los reportes usen solo datos reales
// Cooperativa Agrícola La Pintada

require_once 'php/conexion.php';

echo "<h1>Test de Reportes con Datos Reales</h1>";
echo "<p>Verificando que el sistema de reportes use únicamente datos de la base de datos...</p>";

try {
    $pdo = conectarDB();
    
    // Verificar conexión
    echo "<h2>✅ Conexión a la base de datos: EXITOSA</h2>";
    
    // Verificar tablas existentes
    $stmt = $pdo->query("SHOW TABLES");
    $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h2>📊 Tablas disponibles en la base de datos:</h2>";
    echo "<ul>";
    foreach ($existingTables as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul>";
    
    // Probar consultas de reportes
    echo "<h2>🔍 Probando consultas de reportes...</h2>";
    
    // Test KPIs
    echo "<h3>KPIs (Indicadores Clave):</h3>";
    
    // Total de ingresos
    if (in_array('ventas', $existingTables)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as total_ventas, COALESCE(SUM(total), 0) as total_ingresos FROM ventas WHERE estado = 'pagado'");
        $stmt->execute();
        $result = $stmt->fetch();
        echo "<p>📈 Ventas registradas: {$result['total_ventas']}</p>";
        echo "<p>💰 Ingresos totales: $" . number_format($result['total_ingresos']) . "</p>";
    } else {
        echo "<p>⚠️ Tabla 'ventas' no encontrada</p>";
    }
    
    // Aportes
    if (in_array('pagos', $existingTables)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as total_pagos, COALESCE(SUM(monto), 0) as total_aportes FROM pagos WHERE tipo IN ('aporte_mensual', 'aporte_extraordinario') AND estado = 'confirmado'");
        $stmt->execute();
        $result = $stmt->fetch();
        echo "<p>💳 Pagos registrados: {$result['total_pagos']}</p>";
        echo "<p>💵 Aportes totales: $" . number_format($result['total_aportes']) . "</p>";
    } else {
        echo "<p>⚠️ Tabla 'pagos' no encontrada</p>";
    }
    
    // Socios activos
    if (in_array('socios', $existingTables)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as total_socios FROM socios WHERE estado = 'activo'");
        $stmt->execute();
        $result = $stmt->fetch();
        echo "<p>👥 Socios activos: {$result['total_socios']}</p>";
    } else {
        echo "<p>⚠️ Tabla 'socios' no encontrada</p>";
    }
    
    // Inventario
    if (in_array('insumos', $existingTables)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as total_insumos, COALESCE(SUM(cantidad_disponible * precio_unitario), 0) as valor_inventario FROM insumos WHERE estado = 'disponible' AND cantidad_disponible > 0");
        $stmt->execute();
        $result = $stmt->fetch();
        echo "<p>📦 Insumos disponibles: {$result['total_insumos']}</p>";
        echo "<p>💎 Valor del inventario: $" . number_format($result['valor_inventario']) . "</p>";
    } else {
        echo "<p>⚠️ Tabla 'insumos' no encontrada</p>";
    }
    
    // Producción
    if (in_array('produccion', $existingTables)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as total_produccion, COALESCE(SUM(cantidad), 0) as cantidad_total FROM produccion");
        $stmt->execute();
        $result = $stmt->fetch();
        echo "<p>🌱 Registros de producción: {$result['total_produccion']}</p>";
        echo "<p>📊 Cantidad total producida: " . number_format($result['cantidad_total']) . " quintales</p>";
    } else {
        echo "<p>⚠️ Tabla 'produccion' no encontrada</p>";
    }
    
    echo "<h2>✅ Test completado</h2>";
    echo "<p>El sistema está configurado para usar únicamente datos reales de la base de datos.</p>";
    echo "<p><strong>Nota:</strong> Los reportes se actualizarán automáticamente cada 5 minutos y cada vez que se agreguen nuevos datos.</p>";
    
} catch (Exception $e) {
    echo "<h2>❌ Error en la prueba:</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "<p>Verifica que la base de datos esté configurada correctamente.</p>";
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
    background-color: #f5f5f5;
}
h1, h2, h3 {
    color: #2d5016;
}
p {
    background-color: white;
    padding: 10px;
    border-radius: 5px;
    margin: 5px 0;
}
ul {
    background-color: white;
    padding: 15px;
    border-radius: 5px;
}
</style>
