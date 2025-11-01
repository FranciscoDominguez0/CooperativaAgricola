<?php
// Script de prueba para verificar que dashboard.php funciona
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Dashboard</title>
</head>
<body>
    <h1>Test de Dashboard PHP</h1>
    <pre>
<?php
require_once 'php/config.php';

try {
    $pdo = conectarDB();
    if ($pdo) {
        echo "✓ Conexión a la base de datos exitosa\n\n";
        
        // Probar algunas consultas básicas
        $stmt = $pdo->query("SELECT COUNT(*) as c FROM socios WHERE estado='activo'");
        $row = $stmt->fetch();
        echo "Socios activos: " . ($row['c'] ?? 0) . "\n";
        
        $stmt = $pdo->query("SELECT COUNT(DISTINCT cultivo) as c FROM produccion");
        $row = $stmt->fetch();
        echo "Cultivos distintos: " . ($row['c'] ?? 0) . "\n";
        
        $monthStart = date('Y-m-01');
        $monthEnd = date('Y-m-t');
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(total),0) as total FROM ventas WHERE (estado IS NULL OR estado IN ('pagado', 'entregado', 'pendiente')) AND fecha_venta BETWEEN ? AND ?");
        $stmt->execute([$monthStart, $monthEnd]);
        $row = $stmt->fetch();
        echo "Ingresos del mes: $" . ($row['total'] ?? 0) . "\n";
        
        echo "\n✓ Consultas básicas funcionan correctamente\n";
    } else {
        echo "✗ Error: No se pudo conectar a la base de datos\n";
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
}
?>
    </pre>
    <h2>Probar dashboard.php directamente:</h2>
    <iframe src="php/dashboard.php" style="width: 100%; height: 400px; border: 1px solid #ccc;"></iframe>
</body>
</html>

