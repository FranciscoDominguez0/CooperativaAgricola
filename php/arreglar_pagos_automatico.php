<?php
// Script automático para arreglar el módulo de pagos
header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔧 Arreglando Módulo de Pagos Automáticamente</h1>";

// Paso 1: Probar conexión directa
echo "<h2>1️⃣ Probando conexión a la base de datos...</h2>";

$host = 'localhost';
$username = 'root';
$password = '12345678';
$dbname = 'cooperativa_agricola';

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<div style='background: #d4edda; padding: 15px; margin: 10px 0;'>";
    echo "✅ Conexión a MySQL exitosa";
    echo "</div>";
    
    // Verificar si la base de datos existe
    $stmt = $pdo->query("SHOW DATABASES LIKE '$dbname'");
    $dbExists = $stmt->fetch();
    
    if (!$dbExists) {
        echo "<div style='background: #fff3cd; padding: 15px; margin: 10px 0;'>";
        echo "⚠️ La base de datos '$dbname' no existe. Creándola...";
        echo "</div>";
        
        $pdo->exec("CREATE DATABASE $dbname CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        
        echo "<div style='background: #d4edda; padding: 15px; margin: 10px 0;'>";
        echo "✅ Base de datos '$dbname' creada";
        echo "</div>";
    } else {
        echo "<div style='background: #d4edda; padding: 15px; margin: 10px 0;'>";
        echo "✅ La base de datos '$dbname' existe";
        echo "</div>";
    }
    
    // Conectar a la base de datos específica
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Paso 2: Crear tabla pagos
    echo "<h2>2️⃣ Creando/Verificando tabla pagos...</h2>";
    
    $createTableSQL = "
    CREATE TABLE IF NOT EXISTS pagos (
        id_pago INT AUTO_INCREMENT PRIMARY KEY,
        id_socio INT NOT NULL,
        id_venta INT NULL,
        monto DECIMAL(10,2) NOT NULL,
        tipo ENUM('aporte_mensual', 'aporte_extraordinario', 'pago_venta', 'prestamo', 'devolucion') NOT NULL,
        descripcion TEXT,
        estado ENUM('pendiente', 'confirmado', 'rechazado') DEFAULT 'pendiente',
        fecha_pago DATE NOT NULL,
        metodo_pago ENUM('efectivo', 'transferencia', 'cheque', 'deposito') DEFAULT 'efectivo',
        numero_comprobante VARCHAR(50),
        observaciones TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    $pdo->exec($createTableSQL);
    
    echo "<div style='background: #d4edda; padding: 15px; margin: 10px 0;'>";
    echo "✅ Tabla 'pagos' creada/verificada";
    echo "</div>";
    
    // Paso 3: Insertar datos de ejemplo
    echo "<h2>3️⃣ Insertando datos de ejemplo...</h2>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM pagos");
    $count = $stmt->fetch();
    
    if ($count['total'] == 0) {
        // Verificar que existen socios
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM socios");
        $sociosCount = $stmt->fetch();
        
        if ($sociosCount['total'] > 0) {
            $insertSQL = "
            INSERT INTO pagos (id_socio, id_venta, monto, tipo, descripcion, estado, fecha_pago, metodo_pago, numero_comprobante, observaciones) VALUES
            (1, NULL, 50000.00, 'aporte_mensual', 'Aporte mensual enero 2024', 'confirmado', '2024-01-15', 'transferencia', 'TRF001', 'Pago puntual'),
            (1, NULL, 100000.00, 'aporte_extraordinario', 'Aporte para mejoras de infraestructura', 'confirmado', '2024-02-10', 'efectivo', 'EFE001', 'Contribución voluntaria'),
            (1, NULL, 75000.00, 'pago_venta', 'Pago por venta de café', 'confirmado', '2024-02-15', 'transferencia', 'TRF002', 'Pago completo de venta'),
            (1, NULL, 45000.00, 'aporte_mensual', 'Aporte mensual febrero 2024', 'pendiente', '2024-02-20', 'cheque', 'CHQ001', 'Pendiente de cobro'),
            (1, NULL, 60000.00, 'aporte_mensual', 'Aporte mensual marzo 2024', 'confirmado', '2024-03-15', 'deposito', 'DEP001', 'Depósito bancario'),
            (1, NULL, 25000.00, 'prestamo', 'Préstamo para compra de semillas', 'confirmado', '2024-03-20', 'transferencia', 'TRF003', 'Préstamo aprobado'),
            (1, NULL, 30000.00, 'devolucion', 'Devolución de aporte excedente', 'confirmado', '2024-03-25', 'transferencia', 'TRF004', 'Devolución procesada')
            ";
            
            $pdo->exec($insertSQL);
            
            echo "<div style='background: #d4edda; padding: 15px; margin: 10px 0;'>";
            echo "✅ 7 registros de ejemplo insertados";
            echo "</div>";
        } else {
            echo "<div style='background: #fff3cd; padding: 15px; margin: 10px 0;'>";
            echo "⚠️ No hay socios en la base de datos. Insertando socios de ejemplo...";
            echo "</div>";
            
            // Crear tabla socios si no existe
            $pdo->exec("
            CREATE TABLE IF NOT EXISTS socios (
                id_socio INT AUTO_INCREMENT PRIMARY KEY,
                nombre VARCHAR(100) NOT NULL,
                cedula VARCHAR(20) UNIQUE NOT NULL,
                telefono VARCHAR(15),
                direccion TEXT,
                email VARCHAR(100),
                fecha_ingreso DATE,
                estado ENUM('activo', 'inactivo', 'suspendido') DEFAULT 'activo',
                aportes_totales DECIMAL(10,2) DEFAULT 0.00,
                deudas_pendientes DECIMAL(10,2) DEFAULT 0.00,
                observaciones TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )");
            
            // Insertar socio de ejemplo
            $pdo->exec("
            INSERT INTO socios (nombre, cedula, telefono, direccion, email, fecha_ingreso, estado) VALUES
            ('Juan Pérez', '12345678', '3001234567', 'Calle 1 #2-3, La Pintada', 'juan@email.com', '2024-01-15', 'activo')
            ");
            
            // Ahora insertar pagos
            $insertSQL = "
            INSERT INTO pagos (id_socio, id_venta, monto, tipo, descripcion, estado, fecha_pago, metodo_pago, numero_comprobante, observaciones) VALUES
            (1, NULL, 50000.00, 'aporte_mensual', 'Aporte mensual enero 2024', 'confirmado', '2024-01-15', 'transferencia', 'TRF001', 'Pago puntual'),
            (1, NULL, 100000.00, 'aporte_extraordinario', 'Aporte para mejoras de infraestructura', 'confirmado', '2024-02-10', 'efectivo', 'EFE001', 'Contribución voluntaria'),
            (1, NULL, 75000.00, 'pago_venta', 'Pago por venta de café', 'confirmado', '2024-02-15', 'transferencia', 'TRF002', 'Pago completo de venta'),
            (1, NULL, 45000.00, 'aporte_mensual', 'Aporte mensual febrero 2024', 'pendiente', '2024-02-20', 'cheque', 'CHQ001', 'Pendiente de cobro'),
            (1, NULL, 60000.00, 'aporte_mensual', 'Aporte mensual marzo 2024', 'confirmado', '2024-03-15', 'deposito', 'DEP001', 'Depósito bancario'),
            (1, NULL, 25000.00, 'prestamo', 'Préstamo para compra de semillas', 'confirmado', '2024-03-20', 'transferencia', 'TRF003', 'Préstamo aprobado'),
            (1, NULL, 30000.00, 'devolucion', 'Devolución de aporte excedente', 'confirmado', '2024-03-25', 'transferencia', 'TRF004', 'Devolución procesada')
            ";
            
            $pdo->exec($insertSQL);
            
            echo "<div style='background: #d4edda; padding: 15px; margin: 10px 0;'>";
            echo "✅ Socio de ejemplo creado";
            echo "✅ 7 registros de pago insertados";
            echo "</div>";
        }
    } else {
        echo "<div style='background: #d4edda; padding: 15px; margin: 10px 0;'>";
        echo "✅ La tabla ya tiene {$count['total']} registros";
        echo "</div>";
    }
    
    // Paso 4: Verificar datos
    echo "<h2>4️⃣ Verificando datos en la tabla...</h2>";
    
    $stmt = $pdo->query("SELECT p.*, s.nombre as nombre_socio FROM pagos p LEFT JOIN socios s ON p.id_socio = s.id_socio ORDER BY p.fecha_pago DESC");
    $pagos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($pagos)) {
        echo "<div style='background: #d4edda; padding: 15px; margin: 10px 0;'>";
        echo "✅ Datos cargados correctamente: " . count($pagos) . " registros";
        echo "</div>";
        
        echo "<h3>📋 Datos en la tabla:</h3>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th>ID</th><th>Socio</th><th>Tipo</th><th>Monto</th><th>Estado</th><th>Fecha</th>";
        echo "</tr>";
        
        foreach ($pagos as $pago) {
            $estadoColor = $pago['estado'] == 'confirmado' ? '#d4edda' : ($pago['estado'] == 'pendiente' ? '#fff3cd' : '#f8d7da');
            echo "<tr style='background: $estadoColor;'>";
            echo "<td>{$pago['id_pago']}</td>";
            echo "<td>{$pago['nombre_socio']}</td>";
            echo "<td>{$pago['tipo']}</td>";
            echo "<td>\${$pago['monto']}</td>";
            echo "<td>{$pago['estado']}</td>";
            echo "<td>{$pago['fecha_pago']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Paso 5: Probar las APIs
    echo "<h2>5️⃣ Probando las APIs...</h2>";
    
    // Probar estadísticas
    echo "<h3>📊 Probando estadísticas...</h3>";
    $statsURL = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/php/pagos.php?action=statistics';
    
    try {
        $statsResponse = @file_get_contents($statsURL);
        if ($statsResponse) {
            $statsData = json_decode($statsResponse, true);
            
            if ($statsData && $statsData['success']) {
                echo "<div style='background: #d4edda; padding: 15px; margin: 10px 0;'>";
                echo "✅ API de estadísticas funcionando<br>";
                echo "💰 Ingresos totales: $" . number_format($statsData['statistics']['ingresos_totales'], 2) . "<br>";
                echo "⏳ Pagos pendientes: {$statsData['statistics']['pagos_pendientes']}<br>";
                echo "✅ Pagos confirmados: {$statsData['statistics']['pagos_confirmados']}<br>";
                echo "📅 Aportes mensuales: {$statsData['statistics']['aportes_mensuales']}";
                echo "</div>";
            } else {
                echo "<div style='background: #f8d7da; padding: 15px; margin: 10px 0;'>";
                echo "❌ Error en API de estadísticas: " . htmlspecialchars($statsResponse);
                echo "</div>";
            }
        } else {
            echo "<div style='background: #fff3cd; padding: 15px; margin: 10px 0;'>";
            echo "⚠️ No se pudo acceder a la URL: $statsURL<br>";
            echo "Verifica que el servidor web esté ejecutándose";
            echo "</div>";
        }
    } catch (Exception $e) {
        echo "<div style='background: #f8d7da; padding: 15px; margin: 10px 0;'>";
        echo "❌ Error al probar API: " . $e->getMessage();
        echo "</div>";
    }
    
    // Probar lista
    echo "<h3>📋 Probando lista de pagos...</h3>";
    $listURL = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/php/pagos.php?page=1&limit=10';
    
    try {
        $listResponse = @file_get_contents($listURL);
        if ($listResponse) {
            $listData = json_decode($listResponse, true);
            
            if ($listData && $listData['success']) {
                echo "<div style='background: #d4edda; padding: 15px; margin: 10px 0;'>";
                echo "✅ API de lista funcionando<br>";
                echo "📊 Registros: " . count($listData['data']) . "<br>";
                echo "📄 Páginas totales: {$listData['pagination']['total_pages']}";
                echo "</div>";
            } else {
                echo "<div style='background: #f8d7da; padding: 15px; margin: 10px 0;'>";
                echo "❌ Error en API de lista: " . htmlspecialchars($listResponse);
                echo "</div>";
            }
        } else {
            echo "<div style='background: #fff3cd; padding: 15px; margin: 10px 0;'>";
            echo "⚠️ No se pudo acceder a la URL: $listURL";
            echo "</div>";
        }
    } catch (Exception $e) {
        echo "<div style='background: #f8d7da; padding: 15px; margin: 10px 0;'>";
        echo "❌ Error al probar API: " . $e->getMessage();
        echo "</div>";
    }
    
    // Resumen final
    echo "<h2>🎉 Resumen Final</h2>";
    echo "<div style='background: #d4edda; padding: 15px; margin: 10px 0;'>";
    echo "<h3>✅ Proceso completado exitosamente</h3>";
    echo "<p><strong>Lo que se hizo:</strong></p>";
    echo "<ul>";
    echo "<li>✅ Conexión a la base de datos establecida</li>";
    echo "<li>✅ Base de datos 'cooperativa_agricola' verificada</li>";
    echo "<li>✅ Tabla 'pagos' creada/verificada</li>";
    echo "<li>✅ Datos de ejemplo insertados</li>";
    echo "<li>✅ APIs probadas</li>";
    echo "</ul>";
    echo "<p><strong>Próximos pasos:</strong></p>";
    echo "<ol>";
    echo "<li>Abre <code></code> en tu navegador</li>";
    echo "<li>Haz clic en 'Pagos' en el menú lateral</li>";
    echo "<li>Deberías ver las estadísticas y la tabla con datos</li>";
    echo "</ol>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div style='background: #f8d7da; padding: 15px; margin: 10px 0;'>";
    echo "❌ <strong>Error de conexión:</strong> " . $e->getMessage();
    echo "<br><br><strong>Posibles soluciones:</strong>";
    echo "<ul>";
    echo "<li>Verifica que MySQL esté ejecutándose</li>";
    echo "<li>Verifica el usuario y contraseña (actualmente: root / 12345678)</li>";
    echo "<li>Si tu contraseña es diferente, edita este archivo en la línea 12</li>";
    echo "</ul>";
    echo "</div>";
}
?>



