<?php
// Test de tablas para PDF
// Cooperativa Agrícola La Pintada

require_once 'php/conexion.php';

echo "<h1>🔍 Test de Tablas para PDF</h1>";

try {
    $pdo = conectarDB();
    
    echo "<h2>1. Verificar Conexión</h2>";
    echo "<p style='color: green;'>✅ Conexión exitosa</p>";
    
    echo "<h2>2. Tablas Existentes en la Base de Datos</h2>";
    $stmt = $pdo->query("SHOW TABLES");
    $tablas = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<ul>";
    foreach ($tablas as $tabla) {
        echo "<li><strong>$tabla</strong></li>";
    }
    echo "</ul>";
    
    echo "<h2>3. Verificar Tablas Requeridas</h2>";
    $tablasRequeridas = ['ventas', 'socios', 'pagos', 'insumos'];
    
    foreach ($tablasRequeridas as $tabla) {
        if (in_array($tabla, $tablas)) {
            echo "<p style='color: green;'>✅ Tabla '$tabla' existe</p>";
        } else {
            echo "<p style='color: red;'>❌ Tabla '$tabla' NO existe</p>";
        }
    }
    
    echo "<h2>4. Test de Datos en Tablas</h2>";
    
    // Test ventas
    if (in_array('ventas', $tablas)) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM ventas");
            $result = $stmt->fetch();
            echo "<p>📊 Ventas: {$result['total']} registros</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Error en ventas: " . $e->getMessage() . "</p>";
        }
    }
    
    // Test socios
    if (in_array('socios', $tablas)) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM socios");
            $result = $stmt->fetch();
            echo "<p>👥 Socios: {$result['total']} registros</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Error en socios: " . $e->getMessage() . "</p>";
        }
    }
    
    // Test pagos
    if (in_array('pagos', $tablas)) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM pagos");
            $result = $stmt->fetch();
            echo "<p>💳 Pagos: {$result['total']} registros</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Error en pagos: " . $e->getMessage() . "</p>";
        }
    }
    
    // Test insumos
    if (in_array('insumos', $tablas)) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM insumos");
            $result = $stmt->fetch();
            echo "<p>📦 Insumos: {$result['total']} registros</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Error en insumos: " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<h2>5. Test de Generación de PDF</h2>";
    echo "<p><a href='php/generate_pdf_robust.php?dateFrom=2024-01-01&dateTo=2024-12-31' target='_blank'>🧪 Probar Generación de PDF</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>
