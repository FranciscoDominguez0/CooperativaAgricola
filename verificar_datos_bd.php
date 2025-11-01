<?php
// Script para verificar directamente qué datos hay en la base de datos
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/html; charset=utf-8');

echo "<html><head><meta charset='UTF-8'><title>Verificación de Datos BD</title></head><body>";
echo "<h1>Verificación Directa de Datos en Base de Datos</h1>";

// Intentar conectar
try {
    if (file_exists('php/conexion.php')) {
        require_once 'php/conexion.php';
    } elseif (file_exists('backend/conexion.php')) {
        require_once 'backend/conexion.php';
    } else {
        die("No se encontró archivo de conexión");
    }
    
    $pdo = conectarDB();
    echo "<p style='color: green;'>✓ Conexión exitosa</p>";
    
    // Verificar tabla VENTAS
    echo "<h2>1. Tabla VENTAS</h2>";
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM ventas");
        $total = $stmt->fetch()['total'];
        echo "<p>Total de registros: <strong>$total</strong></p>";
        
        if ($total > 0) {
            // Estados
            $stmt = $pdo->query("SELECT estado, COUNT(*) as count, SUM(total) as suma FROM ventas GROUP BY estado");
            $estados = $stmt->fetchAll();
            echo "<table border='1' cellpadding='5'><tr><th>Estado</th><th>Cantidad</th><th>Suma Total</th></tr>";
            foreach ($estados as $e) {
                $estado = $e['estado'] ?? 'NULL';
                echo "<tr><td>$estado</td><td>{$e['count']}</td><td>$" . number_format($e['suma'], 2) . "</td></tr>";
            }
            echo "</table>";
            
            // Últimas 10 ventas
            $stmt = $pdo->query("SELECT * FROM ventas ORDER BY fecha_venta DESC LIMIT 10");
            $ventas = $stmt->fetchAll();
            echo "<h3>Últimas 10 ventas:</h3><table border='1' cellpadding='5'><tr><th>ID</th><th>Fecha</th><th>Producto</th><th>Total</th><th>Estado</th></tr>";
            foreach ($ventas as $v) {
                echo "<tr><td>{$v['id_venta']}</td><td>{$v['fecha_venta']}</td><td>{$v['producto']}</td><td>$" . number_format($v['total'], 2) . "</td><td>" . ($v['estado'] ?? 'NULL') . "</td></tr>";
            }
            echo "</table>";
            
            // Ventas del mes actual
            $mesInicio = date('Y-m-01');
            $mesFin = date('Y-m-t');
            $stmt = $pdo->prepare("SELECT COUNT(*) as count, SUM(total) as suma FROM ventas WHERE fecha_venta >= ? AND fecha_venta <= ?");
            $stmt->execute([$mesInicio, $mesFin]);
            $mes = $stmt->fetch();
            echo "<h3>Ventas del mes actual ($mesInicio a $mesFin):</h3>";
            echo "<p>Cantidad: <strong>{$mes['count']}</strong>, Total: <strong>$" . number_format($mes['suma'], 2) . "</strong></p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    }
    
    // Verificar tabla PAGOS
    echo "<h2>2. Tabla PAGOS</h2>";
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM pagos");
        $total = $stmt->fetch()['total'];
        echo "<p>Total de registros: <strong>$total</strong></p>";
        
        if ($total > 0) {
            // Estados
            $stmt = $pdo->query("SELECT estado, COUNT(*) as count, SUM(monto) as suma FROM pagos GROUP BY estado");
            $estados = $stmt->fetchAll();
            echo "<h3>Por Estado:</h3><table border='1' cellpadding='5'><tr><th>Estado</th><th>Cantidad</th><th>Suma Total</th></tr>";
            foreach ($estados as $e) {
                echo "<tr><td>{$e['estado']}</td><td>{$e['count']}</td><td>$" . number_format($e['suma'], 2) . "</td></tr>";
            }
            echo "</table>";
            
            // Tipos
            $stmt = $pdo->query("SELECT tipo, COUNT(*) as count, SUM(monto) as suma FROM pagos GROUP BY tipo");
            $tipos = $stmt->fetchAll();
            echo "<h3>Por Tipo:</h3><table border='1' cellpadding='5'><tr><th>Tipo</th><th>Cantidad</th><th>Suma Total</th></tr>";
            foreach ($tipos as $t) {
                echo "<tr><td>{$t['tipo']}</td><td>{$t['count']}</td><td>$" . number_format($t['suma'], 2) . "</td></tr>";
            }
            echo "</table>";
            
            // Últimos 10 pagos
            $stmt = $pdo->query("SELECT * FROM pagos ORDER BY fecha_pago DESC LIMIT 10");
            $pagos = $stmt->fetchAll();
            echo "<h3>Últimos 10 pagos:</h3><table border='1' cellpadding='5'><tr><th>ID</th><th>Fecha</th><th>Tipo</th><th>Estado</th><th>Monto</th></tr>";
            foreach ($pagos as $p) {
                echo "<tr><td>{$p['id_pago']}</td><td>{$p['fecha_pago']}</td><td>{$p['tipo']}</td><td>{$p['estado']}</td><td>$" . number_format($p['monto'], 2) . "</td></tr>";
            }
            echo "</table>";
            
            // Pagos del mes actual
            $mesInicio = date('Y-m-01');
            $mesFin = date('Y-m-t');
            $stmt = $pdo->prepare("SELECT COUNT(*) as count, SUM(monto) as suma FROM pagos WHERE fecha_pago >= ? AND fecha_pago <= ?");
            $stmt->execute([$mesInicio, $mesFin]);
            $mes = $stmt->fetch();
            echo "<h3>Pagos del mes actual ($mesInicio a $mesFin):</h3>";
            echo "<p>Cantidad: <strong>{$mes['count']}</strong>, Total: <strong>$" . number_format($mes['suma'], 2) . "</strong></p>";
            
            // Aportes confirmados del mes
            $stmt = $pdo->prepare("SELECT COUNT(*) as count, SUM(monto) as suma FROM pagos WHERE fecha_pago >= ? AND fecha_pago <= ? AND tipo IN ('aporte_mensual', 'aporte_extraordinario') AND estado = 'confirmado'");
            $stmt->execute([$mesInicio, $mesFin]);
            $aportes = $stmt->fetch();
            echo "<h3>Aportes confirmados del mes:</h3>";
            echo "<p>Cantidad: <strong>{$aportes['count']}</strong>, Total: <strong>$" . number_format($aportes['suma'], 2) . "</strong></p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    }
    
    // Probar endpoint
    echo "<h2>3. Test del Endpoint backend/reportes.php</h2>";
    $_GET['action'] = 'kpis';
    $_GET['dateFrom'] = date('Y-m-01');
    $_GET['dateTo'] = date('Y-m-t');
    
    ob_start();
    include 'backend/reportes.php';
    $output = ob_get_clean();
    
    echo "<h3>Respuesta del endpoint:</h3>";
    echo "<pre style='background: #f0f0f0; padding: 10px; border: 1px solid #ccc;'>" . htmlspecialchars($output) . "</pre>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error general: " . $e->getMessage() . "</p>";
}

echo "</body></html>";
?>


