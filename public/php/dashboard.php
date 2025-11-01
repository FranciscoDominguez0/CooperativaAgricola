<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'config.php';

try {
    $pdo = conectarDB();
    
    if (!$pdo) {
        throw new Exception('Conexión a la base de datos fallida');
    }

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

    // Ingresos del mes actual (ventas pagadas, entregadas y pendientes)
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(total),0) as total FROM ventas WHERE (estado IS NULL OR estado IN ('pagado', 'entregado', 'pendiente')) AND fecha_venta BETWEEN ? AND ?");
    $stmt->execute([$monthStart, $monthEnd]);
    $row = $stmt->fetch();
    $stats['revenue_month'] = (float)($row['total'] ?? 0);

    // Ingresos del mes anterior para cambio
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(total),0) as total FROM ventas WHERE (estado IS NULL OR estado IN ('pagado', 'entregado', 'pendiente')) AND fecha_venta BETWEEN ? AND ?");
    $stmt->execute([$prevMonthStart, $prevMonthEnd]);
    $prev = (float)($stmt->fetch()['total'] ?? 0);
    $stats['revenue_month_change'] = $prev > 0 ? (($stats['revenue_month'] - $prev) / $prev) * 100 : 0;

    // Ventas hoy
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(total),0) as total FROM ventas WHERE (estado IS NULL OR estado IN ('pagado', 'entregado', 'pendiente')) AND fecha_venta = ?");
    $stmt->execute([$today]);
    $stats['ventas_hoy'] = (float)($stmt->fetch()['total'] ?? 0);

    // Ticket promedio del mes
    $stmt = $pdo->prepare("SELECT COALESCE(AVG(total),0) as avg_ticket FROM ventas WHERE (estado IS NULL OR estado IN ('pagado', 'entregado', 'pendiente')) AND fecha_venta BETWEEN ? AND ?");
    $stmt->execute([$monthStart, $monthEnd]);
    $stats['avg_ticket'] = (float)($stmt->fetch()['avg_ticket'] ?? 0);

    // Top producto por ventas del mes
    $stmt = $pdo->prepare("SELECT producto, SUM(total) as tot FROM ventas WHERE (estado IS NULL OR estado IN ('pagado', 'entregado', 'pendiente')) AND fecha_venta BETWEEN ? AND ? GROUP BY producto ORDER BY tot DESC LIMIT 1");
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

    // Margen bruto aproximado del mes
    $stmt = $pdo->prepare("SELECT 
            COALESCE(SUM(v.total),0) as total_sales,
            COALESCE(SUM(v.total * 0.7),0) as total_costs
        FROM ventas v
        WHERE (v.estado IS NULL OR v.estado IN ('pagado', 'entregado', 'pendiente')) AND v.fecha_venta BETWEEN ? AND ?");
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

    // ===== DATOS PARA GRÁFICOS =====
    $charts = [];

    // Gráfico de ventas por estado (últimos 6 meses)
    $stmt = $pdo->query("SELECT 
        DATE_FORMAT(fecha_venta, '%Y-%m') as mes,
        COALESCE(estado, 'pendiente') as estado,
        SUM(total) as total
    FROM ventas 
    WHERE fecha_venta >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY mes, estado
    ORDER BY mes ASC");
    $ventasPorEstado = [];
    while ($row = $stmt->fetch()) {
        $mes = $row['mes'];
        if (!isset($ventasPorEstado[$mes])) {
            $ventasPorEstado[$mes] = ['mes' => $mes, 'pagado' => 0, 'pendiente' => 0, 'entregado' => 0];
        }
        $estado = $row['estado'];
        $ventasPorEstado[$mes][$estado] = (float)$row['total'];
    }
    $charts['ventas_por_estado'] = array_values($ventasPorEstado);

    // Gráfico de distribución de cultivos (top 5)
    $stmt = $pdo->query("SELECT cultivo, COUNT(*) as cantidad FROM produccion GROUP BY cultivo ORDER BY cantidad DESC LIMIT 5");
    $cultivos = [];
    while ($row = $stmt->fetch()) {
        $cultivos[] = ['nombre' => $row['cultivo'], 'cantidad' => (int)$row['cantidad']];
    }
    $charts['distribucion_cultivos'] = $cultivos;

    // Gráfico de ingresos por tipo (ventas vs aportes últimos 6 meses)
    $stmt = $pdo->query("SELECT 
        DATE_FORMAT(fecha_venta, '%Y-%m') as mes,
        SUM(total) as ventas
    FROM ventas 
    WHERE fecha_venta >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    AND (estado IS NULL OR estado IN ('pagado', 'entregado', 'pendiente'))
    GROUP BY mes
    ORDER BY mes ASC");
    $ingresos = [];
    while ($row = $stmt->fetch()) {
        $mes = $row['mes'];
        $ingresos[$mes] = ['mes' => $mes, 'ventas' => (float)$row['ventas'], 'aportes' => 0];
    }
    
    $stmt = $pdo->query("SELECT 
        DATE_FORMAT(fecha_pago, '%Y-%m') as mes,
        SUM(monto) as aportes
    FROM pagos 
    WHERE fecha_pago >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    AND estado='confirmado'
    AND tipo IN ('aporte_mensual', 'aporte_extraordinario')
    GROUP BY mes
    ORDER BY mes ASC");
    while ($row = $stmt->fetch()) {
        $mes = $row['mes'];
        if (!isset($ingresos[$mes])) {
            $ingresos[$mes] = ['mes' => $mes, 'ventas' => 0, 'aportes' => 0];
        }
        $ingresos[$mes]['aportes'] = (float)$row['aportes'];
    }
    $charts['ingresos_por_tipo'] = array_values($ingresos);

    // Top 5 socios por producción
    $stmt = $pdo->query("SELECT 
        s.nombre,
        COUNT(p.id_produccion) as producciones,
        COALESCE(SUM(p.cantidad), 0) as total_produccion
    FROM socios s
    LEFT JOIN produccion p ON s.id_socio = p.id_socio
    WHERE s.estado = 'activo'
    GROUP BY s.id_socio, s.nombre
    ORDER BY total_produccion DESC
    LIMIT 5");
    $topSocios = [];
    while ($row = $stmt->fetch()) {
        $topSocios[] = [
            'nombre' => $row['nombre'],
            'producciones' => (int)$row['producciones'],
            'total_produccion' => (float)$row['total_produccion']
        ];
    }
    $charts['top_socios'] = $topSocios;

    echo json_encode(['success' => true, 'stats' => $stats, 'charts' => $charts]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
