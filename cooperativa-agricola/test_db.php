<?php
// Archivo de prueba de conexión a la base de datos
echo "<h1>🌱 Test de Conexión - Cooperativa Agrícola</h1>";

// Configuración de la base de datos
$host = 'localhost';
$user = 'root';
$pass = '12345678';
$database = 'cooperativa_agricola';
$port = 3306;

echo "<h2>📊 Datos de Conexión:</h2>";
echo "<ul>";
echo "<li><strong>Host:</strong> $host</li>";
echo "<li><strong>Puerto:</strong> $port</li>";
echo "<li><strong>Usuario:</strong> $user</li>";
echo "<li><strong>Base de datos:</strong> $database</li>";
echo "</ul>";

echo "<h2>🔗 Probando Conexión...</h2>";

try {
    // Intentar conexión con PDO
    $dsn = "mysql:host=$host;port=$port;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p style='color: green;'>✅ <strong>Conexión a MySQL exitosa!</strong></p>";
    
    // Verificar si la base de datos existe
    $stmt = $pdo->query("SHOW DATABASES LIKE '$database'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✅ <strong>Base de datos '$database' encontrada!</strong></p>";
        
        // Conectar a la base de datos específica
        $dsn = "mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass);
        
        // Verificar si la tabla usuarios existe
        $stmt = $pdo->query("SHOW TABLES LIKE 'usuarios'");
        if ($stmt->rowCount() > 0) {
            echo "<p style='color: green;'>✅ <strong>Tabla 'usuarios' encontrada!</strong></p>";
            
            // Mostrar estructura de la tabla
            $stmt = $pdo->query("DESCRIBE usuarios");
            $columns = $stmt->fetchAll();
            
            echo "<h3>📋 Estructura de la tabla 'usuarios':</h3>";
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr style='background-color: #f0f0f0;'>";
            echo "<th>Campo</th><th>Tipo</th><th>Nulo</th><th>Clave</th><th>Default</th>";
            echo "</tr>";
            
            foreach ($columns as $column) {
                echo "<tr>";
                echo "<td>{$column['Field']}</td>";
                echo "<td>{$column['Type']}</td>";
                echo "<td>{$column['Null']}</td>";
                echo "<td>{$column['Key']}</td>";
                echo "<td>{$column['Default']}</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            // Contar usuarios
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios");
            $result = $stmt->fetch();
            echo "<p><strong>👥 Total de usuarios:</strong> {$result['total']}</p>";
            
            if ($result['total'] > 0) {
                echo "<h3>👤 Usuarios en la base de datos:</h3>";
                $stmt = $pdo->query("SELECT id_usuario, nombre, correo, rol, estado FROM usuarios");
                $usuarios = $stmt->fetchAll();
                
                echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
                echo "<tr style='background-color: #f0f0f0;'>";
                echo "<th>ID</th><th>Nombre</th><th>Correo</th><th>Rol</th><th>Estado</th>";
                echo "</tr>";
                
                foreach ($usuarios as $usuario) {
                    echo "<tr>";
                    echo "<td>{$usuario['id_usuario']}</td>";
                    echo "<td>{$usuario['nombre']}</td>";
                    echo "<td>{$usuario['correo']}</td>";
                    echo "<td>{$usuario['rol']}</td>";
                    echo "<td>{$usuario['estado']}</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
            
        } else {
            echo "<p style='color: red;'>❌ <strong>Tabla 'usuarios' NO encontrada!</strong></p>";
            echo "<p>🔧 <strong>Solución:</strong> Ejecuta el script database_setup.sql en Workbench</p>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ <strong>Base de datos '$database' NO encontrada!</strong></p>";
        echo "<p>🔧 <strong>Solución:</strong> Crea la base de datos ejecutando:</p>";
        echo "<code>CREATE DATABASE cooperativa_agricola;</code>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ <strong>Error de conexión:</strong> " . $e->getMessage() . "</p>";
    echo "<h3>🔧 Posibles soluciones:</h3>";
    echo "<ul>";
    echo "<li>Verificar que MySQL esté ejecutándose</li>";
    echo "<li>Verificar usuario y contraseña</li>";
    echo "<li>Verificar que el puerto 3306 esté disponible</li>";
    echo "</ul>";
}

echo "<hr>";
echo "<h2>🚀 Enlaces de la Aplicación:</h2>";
echo "<ul>";
echo "<li><a href='login.html'>🔐 Página de Login</a></li>";
echo "<li><a href='registro.html'>📝 Página de Registro</a></li>";
echo "<li><a href='dashboard.html'>📊 Dashboard</a></li>";
echo "</ul>";

echo "<style>";
echo "body { font-family: Arial, sans-serif; margin: 20px; }";
echo "table { margin: 10px 0; }";
echo "th, td { padding: 8px; text-align: left; }";
echo "code { background-color: #f0f0f0; padding: 2px 4px; }";
echo "</style>";
?>