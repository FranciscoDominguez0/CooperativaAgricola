<?php
// Script de limpieza y optimización de recursos
// Cooperativa Agrícola La Pintada

echo "<h1>🧹 Limpieza y Optimización de Recursos</h1>";

// Función para limpiar archivos temporales
function limpiarArchivosTemporales() {
    $archivosTemporales = [
        'test_*.html',
        'test_*.php',
        'debug_*.php',
        '*.tmp',
        '*.log'
    ];
    
    $limpiados = 0;
    $directorio = __DIR__;
    
    foreach ($archivosTemporales as $patron) {
        $archivos = glob($directorio . '/' . $patron);
        foreach ($archivos as $archivo) {
            if (is_file($archivo) && basename($archivo) !== '') {
                if (unlink($archivo)) {
                    $limpiados++;
                    echo "<p>✅ Eliminado: " . basename($archivo) . "</p>";
                }
            }
        }
    }
    
    return $limpiados;
}

// Función para optimizar archivos JavaScript
function optimizarJavaScript() {
    $archivosJS = glob(__DIR__ . '/js/*.js');
    $optimizados = 0;
    
    foreach ($archivosJS as $archivo) {
        $contenido = file_get_contents($archivo);
        
        // Remover comentarios largos
        $contenido = preg_replace('/\/\*.*?\*\//s', '', $contenido);
        
        // Remover espacios extra
        $contenido = preg_replace('/\s+/', ' ', $contenido);
        
        // Remover líneas vacías
        $contenido = preg_replace('/^\s*$/m', '', $contenido);
        
        if (file_put_contents($archivo, $contenido)) {
            $optimizados++;
            echo "<p>✅ Optimizado: " . basename($archivo) . "</p>";
        }
    }
    
    return $optimizados;
}

// Función para cerrar conexiones de base de datos abiertas
function cerrarConexionesDB() {
    try {
        // Cerrar todas las conexiones PDO abiertas
        if (function_exists('cerrarConexion')) {
            cerrarConexion();
        }
        
        // Limpiar variables globales
        if (isset($GLOBALS['pdo'])) {
            $GLOBALS['pdo'] = null;
        }
        
        echo "<p>✅ Conexiones de base de datos cerradas</p>";
        return true;
    } catch (Exception $e) {
        echo "<p>⚠️ Error cerrando conexiones: " . $e->getMessage() . "</p>";
        return false;
    }
}

// Función para limpiar caché del navegador
function limpiarCache() {
    $archivosCache = [
        'css/cache/',
        'js/cache/',
        'images/cache/',
        'temp/',
        'cache/'
    ];
    
    $limpiados = 0;
    
    foreach ($archivosCache as $directorio) {
        if (is_dir(__DIR__ . '/' . $directorio)) {
            $archivos = glob(__DIR__ . '/' . $directorio . '*');
            foreach ($archivos as $archivo) {
                if (is_file($archivo)) {
                    if (unlink($archivo)) {
                        $limpiados++;
                    }
                }
            }
        }
    }
    
    echo "<p>✅ Cache limpiado: $limpiados archivos</p>";
    return $limpiados;
}

// Función para mostrar estadísticas de memoria
function mostrarEstadisticasMemoria() {
    $memoriaActual = memory_get_usage(true);
    $memoriaPico = memory_get_peak_usage(true);
    
    echo "<h2>📊 Estadísticas de Memoria</h2>";
    echo "<p><strong>Memoria actual:</strong> " . formatBytes($memoriaActual) . "</p>";
    echo "<p><strong>Memoria pico:</strong> " . formatBytes($memoriaPico) . "</p>";
    echo "<p><strong>Límite de memoria:</strong> " . ini_get('memory_limit') . "</p>";
}

function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

// Ejecutar limpieza
echo "<h2>🧹 Iniciando Limpieza...</h2>";

// 1. Limpiar archivos temporales
echo "<h3>📁 Limpiando archivos temporales...</h3>";
$archivosLimpiados = limpiarArchivosTemporales();
echo "<p><strong>Total archivos eliminados:</strong> $archivosLimpiados</p>";

// 2. Optimizar JavaScript
echo "<h3>⚡ Optimizando JavaScript...</h3>";
$jsOptimizados = optimizarJavaScript();
echo "<p><strong>Archivos JS optimizados:</strong> $jsOptimizados</p>";

// 3. Cerrar conexiones DB
echo "<h3>🗄️ Cerrando conexiones de base de datos...</h3>";
cerrarConexionesDB();

// 4. Limpiar cache
echo "<h3>💾 Limpiando cache...</h3>";
limpiarCache();

// 5. Mostrar estadísticas
mostrarEstadisticasMemoria();

// 6. Recomendaciones
echo "<h2>💡 Recomendaciones de Optimización</h2>";
echo "<ul>";
echo "<li>✅ Usar conexiones singleton para la base de datos</li>";
echo "<li>✅ Cerrar conexiones después de cada consulta</li>";
echo "<li>✅ Limpiar archivos temporales regularmente</li>";
echo "<li>✅ Optimizar archivos JavaScript y CSS</li>";
echo "<li>✅ Usar caché para consultas frecuentes</li>";
echo "<li>✅ Limitar el número de conexiones simultáneas</li>";
echo "</ul>";

echo "<h2>✅ Limpieza Completada</h2>";
echo "<p>La aplicación ha sido optimizada y los recursos no utilizados han sido liberados.</p>";
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 1000px;
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

