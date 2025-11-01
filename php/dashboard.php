<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'conexion.php';

try {
    $pdo = conectarDB();

    $today = date('Y-m-d');
    $monthStart = date('Y-m-01');
    $monthEnd = date('Y-m-t');
    $prevMonthStart = date('Y-m-01', strtotime('first day of last month'));
    $prevMonthEnd = date('Y-m-t', strtotime('last day of last month'));

    $stats = [
        'total_members' => 0,
        'total_crops' => 0,
        'revenue_month' => 0,
        'revenue_month_change' => 0,
        'pending_tasks' => 0,
        'ventas_hoy' => 0,
        'avg_ticket' => 0,
        'top_product' => null,
        'aportes_mes' => 0,
        'inventario_valor' => 0,
        'productores_activos' => 0,
        'clientes_activos' => 0,
        'pagos_pendientes' => 0,
        'pagos_confirmados' => 0,
        'margen_bruto' => 0
    ];

    // Socios activos
    $stmt = $pdo->query("SELECT COUNT(*) as c FROM socios WHERE estado='activo'");
    $row = $stmt->fetch();
    $stats['total_members'] = (int)($row['c'] ?? 0);

    // Cultivos registrados (distintos en producción)
    $stmt = $pdo->query("SELECT COUNT(DISTINCT cultivo) as c FROM produccion");
    $row = $stmt->fetch();
    $stats['total_crops'] = (int)($row['c'] ?? 0);

    // Ingresos del mes actual (ventas pagadas)
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(total),0) as total FROM ventas WHERE estado='pagado' AND fecha_venta BETWEEN ? AND ?");
    $stmt->execute([$monthStart, $monthEnd]);
    $row = $stmt->fetch();
    $stats['revenue_month'] = (float)($row['total'] ?? 0);

    // Ingresos del mes anterior para cambio
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(total),0) as total FROM ventas WHERE estado='pagado' AND fecha_venta BETWEEN ? AND ?");
    $stmt->execute([$prevMonthStart, $prevMonthEnd]);
    $prev = (float)($stmt->fetch()['total'] ?? 0);
    $stats['revenue_month_change'] = $prev > 0 ? (($stats['revenue_month'] - $prev) / $prev) * 100 : 0;

    // Ventas hoy
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(total),0) as total FROM ventas WHERE estado='pagado' AND fecha_venta = ?");
    $stmt->execute([$today]);
    $stats['ventas_hoy'] = (float)($stmt->fetch()['total'] ?? 0);

    // Ticket promedio del mes
    $stmt = $pdo->prepare("SELECT COALESCE(AVG(total),0) as avg_ticket FROM ventas WHERE estado='pagado' AND fecha_venta BETWEEN ? AND ?");
    $stmt->execute([$monthStart, $monthEnd]);
    $stats['avg_ticket'] = (float)($stmt->fetch()['avg_ticket'] ?? 0);

    // Top producto por ventas del mes
    $stmt = $pdo->prepare("SELECT producto, SUM(total) as tot FROM ventas WHERE estado='pagado' AND fecha_venta BETWEEN ? AND ? GROUP BY producto ORDER BY tot DESC LIMIT 1");
    $stmt->execute([$monthStart, $monthEnd]);
    $row = $stmt->fetch();
    $stats['top_product'] = $row ? ['nombre' => $row['producto'], 'total' => (float)$row['tot']] : null;

    // Aportes del mes (pagos confirmados de tipo aporte)
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(monto),0) as total FROM pagos WHERE estado='confirmado' AND tipo IN ('aporte_mensual','aporte_extraordinario') AND fecha_pago BETWEEN ? AND ?");
    $stmt->execute([$monthStart, $monthEnd]);
    $stats['aportes_mes'] = (float)($stmt->fetch()['total'] ?? 0);

    // Inventario (insumos disponibles)
    $stmt = $pdo->query("SELECT COALESCE(SUM(cantidad_disponible * precio_unitario),0) as val FROM insumos WHERE estado='disponible' AND cantidad_disponible > 0");
    $stats['inventario_valor'] = (float)($stmt->fetch()['val'] ?? 0);

    // Productores activos (con producción en últimos 3 meses)
    $stmt = $pdo->query("SELECT COUNT(DISTINCT id_socio) as c FROM produccion WHERE fecha_recoleccion >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)");
    $stats['productores_activos'] = (int)($stmt->fetch()['c'] ?? 0);

    // Clientes activos (distintos con ventas en 3 meses)
    $stmt = $pdo->query("SELECT COUNT(DISTINCT cliente) as c FROM ventas WHERE fecha_venta >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)");
    $stats['clientes_activos'] = (int)($stmt->fetch()['c'] ?? 0);

    // Pagos pendientes/confirmados del mes
    $stmt = $pdo->prepare("SELECT SUM(estado='pendiente') as pen, SUM(estado='confirmado') as conf FROM pagos WHERE fecha_pago BETWEEN ? AND ?");
    $stmt->execute([$monthStart, $monthEnd]);
    $row = $stmt->fetch();
    $stats['pagos_pendientes'] = (int)($row['pen'] ?? 0);
    $stats['pagos_confirmados'] = (int)($row['conf'] ?? 0);

    // Margen bruto aproximado del mes (ventas - costos estimados de producción asociada)
    $stmt = $pdo->prepare("SELECT 
            COALESCE(SUM(v.total),0) as total_sales,
            COALESCE(SUM(p.cantidad * COALESCE(p.precio_estimado,0)),0) as total_costs
        FROM ventas v
        LEFT JOIN produccion p ON v.id_socio = p.id_socio 
            AND p.fecha_recoleccion BETWEEN DATE_SUB(v.fecha_venta, INTERVAL 3 MONTH) AND v.fecha_venta
        WHERE v.estado='pagado' AND v.fecha_venta BETWEEN ? AND ?");
    $stmt->execute([$monthStart, $monthEnd]);
    $fin = $stmt->fetch();
    $sales = (float)($fin['total_sales'] ?? 0);
    $costs = (float)($fin['total_costs'] ?? 0);
    $stats['margen_bruto'] = $sales > 0 ? (($sales - $costs) / $sales) * 100 : 0;

    // Tareas pendientes: combinación simple (ventas pendientes + pagos pendientes)
    $stmt = $pdo->query("SELECT COUNT(*) as c FROM ventas WHERE estado='pendiente'");
    $vp = (int)($stmt->fetch()['c'] ?? 0);
    $stmt = $pdo->query("SELECT COUNT(*) as c FROM pagos WHERE estado='pendiente'");
    $pp = (int)($stmt->fetch()['c'] ?? 0);
    $stats['pending_tasks'] = $vp + $pp;

    echo json_encode(['success' => true, 'stats' => $stats]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>



