<?php
// Archivo simple para probar la conexión a MySQL
header('Content-Type: text/html; charset=utf-8');

echo "<h2>🔧 Prueba de Conexión a MySQL</h2>";

// Configuración de la base de datos
$host = 'localhost';
$dbname = 'pagos';
$username = 'root';
$password = '';

echo "<h3>Configuración:</h3>";
echo "<ul>";
echo "<li><strong>Host:</strong> $host</li>";
echo "<li><strong>Base de datos:</strong> $dbname</li>";
echo "<li><strong>Usuario:</strong> $username</li>";
echo "<li><strong>Contraseña:</strong> " . (empty($password) ? '(vacía)' : '(configurada)') . "</li>";
echo "</ul>";

try {
    // Intentar conectar sin especificar base de datos primero
    echo "<h3>1. Probando conexión a MySQL...</h3>";
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ <strong>Conexión a MySQL exitosa</strong><br>";
    
    // Verificar si la base de datos existe
    echo "<h3>2. Verificando si la base de datos '$dbname' existe...</h3>";
    $stmt = $pdo->query("SHOW DATABASES LIKE '$dbname'");
    $dbExists = $stmt->fetch();
    
    if ($dbExists) {
        echo "✅ <strong>La base de datos '$dbname' existe</strong><br>";
        
        // Conectar a la base de datos específica
        echo "<h3>3. Conectando a la base de datos '$dbname'...</h3>";
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo "✅ <strong>Conexión a la base de datos '$dbname' exitosa</strong><br>";
        
        // Verificar tablas
        echo "<h3>4. Verificando tablas...</h3>";
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($tables)) {
            echo "⚠️ <strong>No hay tablas en la base de datos</strong><br>";
            echo "<p><strong>Solución:</strong> Ejecuta el archivo <code>crear_base_datos.sql</code> en phpMyAdmin</p>";
        } else {
            echo "✅ <strong>Tablas encontradas:</strong><br>";
            echo "<ul>";
            foreach ($tables as $table) {
                echo "<li>$table</li>";
            }
            echo "</ul>";
            
            // Verificar tabla pagos específicamente
            if (in_array('pagos', $tables)) {
                echo "<h3>5. Verificando tabla 'pagos'...</h3>";
                $stmt = $pdo->query("SELECT COUNT(*) as total FROM pagos");
                $count = $stmt->fetch();
                echo "✅ <strong>Tabla 'pagos' encontrada con {$count['total']} registros</strong><br>";
                
                // Mostrar algunos datos
                $stmt = $pdo->query("SELECT * FROM pagos LIMIT 3");
                $pagos = $stmt->fetchAll();
                
                if (!empty($pagos)) {
                    echo "<h4>Datos de ejemplo:</h4>";
                    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
                    echo "<tr><th>ID</th><th>Socio</th><th>Tipo</th><th>Monto</th><th>Estado</th></tr>";
                    foreach ($pagos as $pago) {
                        echo "<tr>";
                        echo "<td>{$pago['id_pago']}</td>";
                        echo "<td>{$pago['id_socio']}</td>";
                        echo "<td>{$pago['tipo']}</td>";
                        echo "<td>\${$pago['monto']}</td>";
                        echo "<td>{$pago['estado']}</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                }
            } else {
                echo "❌ <strong>La tabla 'pagos' no existe</strong><br>";
                echo "<p><strong>Solución:</strong> Ejecuta el archivo <code>crear_base_datos.sql</code> en phpMyAdmin</p>";
            }
        }
        
    } else {
        echo "❌ <strong>La base de datos '$dbname' NO existe</strong><br>";
        echo "<h3>🔧 Solución:</h3>";
        echo "<ol>";
        echo "<li>Abre phpMyAdmin</li>";
        echo "<li>Ve a la pestaña 'SQL'</li>";
        echo "<li>Copia y pega este código:</li>";
        echo "</ol>";
        echo "<textarea style='width: 100%; height: 100px;'>CREATE DATABASE pagos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;</textarea>";
        echo "<p>O ejecuta el archivo <code>crear_base_datos.sql</code> completo</p>";
    }
    
} catch (PDOException $e) {
    echo "❌ <strong>Error de conexión:</strong> " . $e->getMessage() . "<br>";
    echo "<h3>🔧 Posibles soluciones:</h3>";
    echo "<ul>";
    echo "<li>Verifica que MySQL esté ejecutándose</li>";
    echo "<li>Verifica el usuario y contraseña en el archivo de conexión</li>";
    echo "<li>Verifica que el puerto 3306 esté disponible</li>";
    echo "<li>Si usas XAMPP/WAMP, asegúrate de que esté iniciado</li>";
    echo "</ul>";
}

echo "<hr>";
echo "<h3>📋 Próximos pasos:</h3>";
echo "<ol>";
echo "<li>Si hay errores, corrígelos según las sugerencias</li>";
echo "<li>Ejecuta el archivo <code>crear_base_datos.sql</code> en phpMyAdmin</li>";
echo "<li>Prueba el módulo de pagos en <code>dashboard.html</code></li>";
echo "</ol>";
?>

