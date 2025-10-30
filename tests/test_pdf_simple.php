<?php
// Test simple para verificar que el PDF funcione sin errores
header('Content-Type: text/html; charset=utf-8');

echo "<h1>🧪 Test de PDF Simple</h1>";

try {
    // Simular parámetros
    $_GET['dateFrom'] = '2024-01-01';
    $_GET['dateTo'] = '2024-12-31';
    
    echo "<h2>1. Probando conexión a la base de datos...</h2>";
    
    require_once 'php/conexion.php';
    $pdo = conectarDB();
    
    if ($pdo) {
        echo "<p>✅ Conexión exitosa</p>";
    } else {
        echo "<p>❌ Error de conexión</p>";
        exit;
    }
    
    echo "<h2>2. Probando generación de HTML...</h2>";
    
    // Incluir el archivo de generación de PDF
    ob_start();
    include 'php/generate_pdf_report.php';
    $output = ob_get_clean();
    
    if (!empty($output)) {
        echo "<p>✅ HTML generado exitosamente</p>";
        echo "<h3>Vista previa del reporte:</h3>";
        echo "<div style='border: 1px solid #ccc; padding: 20px; margin: 10px 0; max-height: 400px; overflow-y: auto;'>";
        echo $output;
        echo "</div>";
        
        echo "<h3>Enlaces de prueba:</h3>";
        echo "<p><a href='php/generate_pdf_report.php?dateFrom=2024-01-01&dateTo=2024-12-31' target='_blank'>🔗 Abrir PDF Profesional</a></p>";
        echo "<p><a href='php/generate_pdf_simple.php?dateFrom=2024-01-01&dateTo=2024-12-31' target='_blank'>🔗 Abrir PDF Simple</a></p>";
        
    } else {
        echo "<p>❌ No se generó contenido</p>";
    }
    
} catch (Exception $e) {
    echo "<h2>❌ Error:</h2>";
    echo "<p><strong>Mensaje:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>Archivo:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Línea:</strong> " . $e->getLine() . "</p>";
    echo "<p><strong>Stack trace:</strong></p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<h2>✅ Test Completado</h2>";
echo "<p>Si ves el contenido del reporte arriba, el sistema de PDF está funcionando correctamente.</p>";
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
    font-size: 12px;
}
a {
    color: #2d5016;
    text-decoration: none;
    background-color: #e8f5e8;
    padding: 5px 10px;
    border-radius: 3px;
    display: inline-block;
    margin: 5px;
}
a:hover {
    background-color: #4a7c59;
    color: white;
}
</style>
