<?php
// Monitor de recursos en tiempo real
// Cooperativa Agrícola La Pintada

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Función para obtener estadísticas del sistema
function obtenerEstadisticasSistema() {
    $estadisticas = [
        'timestamp' => date('Y-m-d H:i:s'),
        'memoria' => [
            'actual' => memory_get_usage(true),
            'pico' => memory_get_peak_usage(true),
            'limite' => ini_get('memory_limit')
        ],
        'tiempo' => [
            'ejecucion' => microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'],
            'limite' => ini_get('max_execution_time')
        ],
        'conexiones' => [
            'estado' => connection_status(),
            'abiertas' => 0
        ],
        'archivos' => [
            'abiertos' => count(get_included_files()),
            'tamaño_total' => 0
        ],
        'procesos' => [
            'pid' => getmypid(),
            'usuario' => get_current_user()
        ]
    ];
    
    // Calcular tamaño total de archivos incluidos
    foreach (get_included_files() as $archivo) {
        if (file_exists($archivo)) {
            $estadisticas['archivos']['tamaño_total'] += filesize($archivo);
        }
    }
    
    return $estadisticas;
}

// Función para detectar recursos no cerrados
function detectarRecursosAbiertos() {
    $recursos = [];
    
    // Verificar conexiones de base de datos
    if (isset($GLOBALS['pdo']) && $GLOBALS['pdo'] !== null) {
        $recursos[] = 'Conexión PDO abierta';
    }
    
    // Verificar buffers de salida
    if (ob_get_level() > 0) {
        $recursos[] = 'Buffer de salida activo';
    }
    
    // Verificar sesiones
    if (session_status() === PHP_SESSION_ACTIVE) {
        $recursos[] = 'Sesión activa';
    }
    
    return $recursos;
}

// Función para limpiar recursos automáticamente
function limpiarRecursosAutomaticamente() {
    $limpiados = [];
    
    // Cerrar conexiones PDO
    if (isset($GLOBALS['pdo'])) {
        $GLOBALS['pdo'] = null;
        $limpiados[] = 'Conexión PDO cerrada';
    }
    
    // Limpiar buffers
    while (ob_get_level() > 0) {
        ob_end_clean();
        $limpiados[] = 'Buffer limpiado';
    }
    
    // Limpiar variables globales
    unset($GLOBALS['pdo']);
    $limpiados[] = 'Variables globales limpiadas';
    
    return $limpiados;
}

// Función para obtener recomendaciones
function obtenerRecomendaciones($estadisticas) {
    $recomendaciones = [];
    
    // Verificar memoria
    $memoriaMB = $estadisticas['memoria']['actual'] / (1024 * 1024);
    if ($memoriaMB > 50) {
        $recomendaciones[] = 'Alto uso de memoria: ' . round($memoriaMB, 2) . 'MB';
    }
    
    // Verificar tiempo de ejecución
    if ($estadisticas['tiempo']['ejecucion'] > 5) {
        $recomendaciones[] = 'Tiempo de ejecución alto: ' . round($estadisticas['tiempo']['ejecucion'], 2) . 's';
    }
    
    // Verificar archivos abiertos
    if ($estadisticas['archivos']['abiertos'] > 20) {
        $recomendaciones[] = 'Muchos archivos abiertos: ' . $estadisticas['archivos']['abiertos'];
    }
    
    return $recomendaciones;
}

// Procesar solicitud
$accion = $_GET['action'] ?? 'status';

switch ($accion) {
    case 'status':
        $estadisticas = obtenerEstadisticasSistema();
        $recursosAbiertos = detectarRecursosAbiertos();
        $recomendaciones = obtenerRecomendaciones($estadisticas);
        
        echo json_encode([
            'success' => true,
            'estadisticas' => $estadisticas,
            'recursos_abiertos' => $recursosAbiertos,
            'recomendaciones' => $recomendaciones
        ], JSON_PRETTY_PRINT);
        break;
        
    case 'cleanup':
        $limpiados = limpiarRecursosAutomaticamente();
        $estadisticas = obtenerEstadisticasSistema();
        
        echo json_encode([
            'success' => true,
            'limpiados' => $limpiados,
            'estadisticas' => $estadisticas
        ], JSON_PRETTY_PRINT);
        break;
        
    case 'memory':
        $memoria = [
            'actual' => formatBytes(memory_get_usage(true)),
            'pico' => formatBytes(memory_get_peak_usage(true)),
            'limite' => ini_get('memory_limit')
        ];
        
        echo json_encode([
            'success' => true,
            'memoria' => $memoria
        ], JSON_PRETTY_PRINT);
        break;
        
    default:
        echo json_encode([
            'success' => false,
            'message' => 'Acción no válida'
        ]);
}

function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}
?>
