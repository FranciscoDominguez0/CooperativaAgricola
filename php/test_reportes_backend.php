<?php
// Script de prueba rápida para verificar que el backend devuelve datos
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'conexion.php';

try {
    $pdo = conectarDB();
    
    // Simular los mismos parámetros que usa el frontend
    $dateFrom = $_GET['dateFrom'] ?? date('Y-m-01');
    $dateTo = $_GET['dateTo'] ?? date('Y-m-t');
    
    // Probar directamente la función getKPIData
    $_GET['dateFrom'] = $dateFrom;
    $_GET['dateTo'] = $dateTo;
    $_GET['action'] = 'kpis';
    
    ob_start();
    include 'reportes.php';
    $output = ob_get_clean();
    
    $data = json_decode($output, true);
    
    if ($data && isset($data['success'])) {
        echo json_encode([
            'success' => true,
            'message' => 'Backend funcionando correctamente',
            'data' => $data,
            'test_params' => [
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo
            ]
        ], JSON_PRETTY_PRINT);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Error en el backend',
            'raw_output' => $output
        ], JSON_PRETTY_PRINT);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_PRETTY_PRINT);
}
?>

