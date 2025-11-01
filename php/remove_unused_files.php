<?php
// Script para eliminar archivos no utilizados
// Cooperativa Agrícola La Pintada

echo "<h1>🗑️ Eliminando Archivos No Utilizados</h1>";

// Lista de archivos que se pueden eliminar (archivos de prueba y debug)
$archivosParaEliminar = [
    // Archivos de test
    'test_*.html',
    'test_*.php',
    'test_*.js',
    
    // Archivos de debug
    'debug_*.php',
    'debug_*.html',
    
    // Archivos temporales
    '*.tmp',
    '*.log',
    '*.cache',
    
    // Archivos de backup antiguos
    '*_backup_*.php',
    '*_old_*.php',
    '*_copy_*.php',
    
    // Archivos de desarrollo
    'dev_*.php',
    'temp_*.php',
    'tmp_*.php'
];

$eliminados = 0;
$errores = 0;

echo "<h2>📁 Archivos encontrados para eliminar:</h2>";

foreach ($archivosParaEliminar as $patron) {
    $archivos = glob(__DIR__ . '/' . $patron);
    
    foreach ($archivos as $archivo) {
        $nombreArchivo = basename($archivo);
        
        // No eliminar archivos importantes
        $archivosImportantes = [
            '',
            '',
            'config_optimized.php',
            'conexion.php',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            ''
        ];
        
        if (!in_array($nombreArchivo, $archivosImportantes)) {
            if (is_file($archivo)) {
                if (unlink($archivo)) {
                    echo "<p>✅ Eliminado: $nombreArchivo</p>";
                    $eliminados++;
                } else {
                    echo "<p>❌ Error eliminando: $nombreArchivo</p>";
                    $errores++;
                }
            }
        }
    }
}

// Limpiar directorios vacíos
function limpiarDirectoriosVacios($directorio) {
    $limpiados = 0;
    
    if (is_dir($directorio)) {
        $archivos = scandir($directorio);
        
        if (count($archivos) <= 2) { // Solo . y ..
            if (rmdir($directorio)) {
                $limpiados++;
                echo "<p>✅ Directorio vacío eliminado: " . basename($directorio) . "</p>";
            }
        }
    }
    
    return $limpiados;
}

echo "<h2>📂 Limpiando directorios vacíos...</h2>";
$directoriosVacios = limpiarDirectoriosVacios(__DIR__ . '/temp/');
$directoriosVacios += limpiarDirectoriosVacios(__DIR__ . '/cache/');

// Mostrar estadísticas
echo "<h2>📊 Estadísticas de Limpieza</h2>";
echo "<p><strong>Archivos eliminados:</strong> $eliminados</p>";
echo "<p><strong>Errores:</strong> $errores</p>";
echo "<p><strong>Directorios vacíos eliminados:</strong> $directoriosVacios</p>";

// Mostrar espacio liberado
$espacioInicial = disk_free_space(__DIR__);
echo "<p><strong>Espacio libre actual:</strong> " . formatBytes($espacioInicial) . "</p>";

// Recomendaciones
echo "<h2>💡 Recomendaciones</h2>";
echo "<ul>";
echo "<li>✅ Ejecutar este script regularmente</li>";
echo "<li>✅ Mantener solo archivos de producción</li>";
echo "<li>✅ Usar control de versiones (Git) para archivos importantes</li>";
echo "<li>✅ Hacer backup antes de eliminar archivos</li>";
echo "</ul>";

function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

echo "<h2>✅ Limpieza Completada</h2>";
echo "<p>Los archivos no utilizados han sido eliminados exitosamente.</p>";
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
ul {
    background-color: white;
    padding: 15px;
    border-radius: 5px;
}
</style>












