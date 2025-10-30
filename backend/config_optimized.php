<?php
// Configuración optimizada para la aplicación
// Cooperativa Agrícola La Pintada

// Configuración de memoria
ini_set('memory_limit', '128M');
ini_set('max_execution_time', 30);

// Configuración de sesiones
ini_set('session.gc_maxlifetime', 3600); // 1 hora
ini_set('session.cookie_lifetime', 3600);
ini_set('session.use_strict_mode', 1);

// Configuración de errores
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/error.log');
ini_set('display_errors', 0); // No mostrar errores en producción

// Configuración de caché
ini_set('opcache.enable', 1);
ini_set('opcache.memory_consumption', 64);
ini_set('opcache.max_accelerated_files', 2000);
ini_set('opcache.revalidate_freq', 60);

// Configuración de compresión
if (extension_loaded('zlib') && !ob_get_level()) {
    ob_start('ob_gzhandler');
}

// Configuración de headers de seguridad
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Función para limpiar recursos al finalizar
function limpiarRecursosAlFinalizar() {
    // Cerrar conexiones de base de datos
    if (function_exists('cerrarConexion')) {
        cerrarConexion();
    }
    
    // Limpiar buffer de salida
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Limpiar variables globales
    unset($GLOBALS['pdo']);
}

// Registrar función de limpieza
register_shutdown_function('limpiarRecursosAlFinalizar');

// Función para optimizar consultas
function optimizarConsulta($pdo, $query, $params = []) {
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("Error en consulta optimizada: " . $e->getMessage());
        return false;
    }
}

// Función para limpiar caché
function limpiarCache() {
    $directoriosCache = [
        __DIR__ . '/cache/',
        __DIR__ . '/temp/',
        __DIR__ . '/logs/'
    ];
    
    foreach ($directoriosCache as $directorio) {
        if (is_dir($directorio)) {
            $archivos = glob($directorio . '*');
            foreach ($archivos as $archivo) {
                if (is_file($archivo) && (time() - filemtime($archivo)) > 3600) {
                    unlink($archivo);
                }
            }
        }
    }
}

// Función para mostrar estadísticas de rendimiento
function mostrarEstadisticasRendimiento() {
    $memoria = memory_get_usage(true);
    $memoriaPico = memory_get_peak_usage(true);
    $tiempo = microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'];
    
    return [
        'memoria_actual' => formatBytes($memoria),
        'memoria_pico' => formatBytes($memoriaPico),
        'tiempo_ejecucion' => round($tiempo, 4) . 's',
        'conexiones_activas' => connection_status()
    ];
}

function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

// Crear directorios necesarios si no existen
$directoriosNecesarios = [
    __DIR__ . '/logs/',
    __DIR__ . '/cache/',
    __DIR__ . '/temp/'
];

foreach ($directoriosNecesarios as $directorio) {
    if (!is_dir($directorio)) {
        mkdir($directorio, 0755, true);
    }
}

// Limpiar caché cada hora
if (rand(1, 100) === 1) {
    limpiarCache();
}

echo "<!-- Configuración optimizada cargada -->";
?>
