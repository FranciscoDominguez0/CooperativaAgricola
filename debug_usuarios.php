<?php
// Debug del módulo de usuarios
// Cooperativa Agrícola La Pintada

echo "<h1>🔧 Debug del Módulo de Usuarios</h1>";

// Test 1: Verificar conexión
echo "<h2>1. Test de Conexión</h2>";
try {
    require_once 'php/conexion.php';
    $pdo = conectarDB();
    echo "<p style='color: green;'>✅ Conexión a la base de datos: EXITOSA</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error de conexión: " . $e->getMessage() . "</p>";
}

// Test 2: Verificar tabla usuarios
echo "<h2>2. Test de Tabla Usuarios</h2>";
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'usuarios'");
    if ($stmt->fetch()) {
        echo "<p style='color: green;'>✅ Tabla 'usuarios' existe</p>";
        
        // Contar usuarios
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios");
        $total = $stmt->fetch()['total'];
        echo "<p>📊 Total de usuarios: $total</p>";
        
        // Mostrar usuarios
        $stmt = $pdo->query("SELECT id_usuario, nombre, apellido, email, rol, estado FROM usuarios LIMIT 5");
        $usuarios = $stmt->fetchAll();
        
        echo "<h3>Usuarios encontrados:</h3>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Nombre</th><th>Email</th><th>Rol</th><th>Estado</th></tr>";
        foreach ($usuarios as $usuario) {
            echo "<tr>";
            echo "<td>" . $usuario['id_usuario'] . "</td>";
            echo "<td>" . $usuario['nombre'] . " " . $usuario['apellido'] . "</td>";
            echo "<td>" . $usuario['email'] . "</td>";
            echo "<td>" . $usuario['rol'] . "</td>";
            echo "<td>" . $usuario['estado'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } else {
        echo "<p style='color: orange;'>⚠️ Tabla 'usuarios' no existe. Se creará automáticamente.</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error verificando tabla: " . $e->getMessage() . "</p>";
}

// Test 3: Test del endpoint de estadísticas
echo "<h2>3. Test de Endpoint de Estadísticas</h2>";
try {
    $_GET['action'] = 'estadisticas';
    ob_start();
    include 'php/usuarios.php';
    $output = ob_get_clean();
    
    echo "<p>Respuesta del endpoint:</p>";
    echo "<pre style='background: #f0f0f0; padding: 10px; border-radius: 5px;'>";
    echo htmlspecialchars($output);
    echo "</pre>";
    
    $data = json_decode($output, true);
    if ($data && $data['success']) {
        echo "<p style='color: green;'>✅ Endpoint de estadísticas funciona correctamente</p>";
    } else {
        echo "<p style='color: red;'>❌ Error en endpoint de estadísticas</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error probando endpoint: " . $e->getMessage() . "</p>";
}

// Test 4: Test del endpoint de lista
echo "<h2>4. Test de Endpoint de Lista</h2>";
try {
    $_GET['action'] = 'lista';
    ob_start();
    include 'php/usuarios.php';
    $output = ob_get_clean();
    
    echo "<p>Respuesta del endpoint:</p>";
    echo "<pre style='background: #f0f0f0; padding: 10px; border-radius: 5px;'>";
    echo htmlspecialchars($output);
    echo "</pre>";
    
    $data = json_decode($output, true);
    if ($data && $data['success']) {
        echo "<p style='color: green;'>✅ Endpoint de lista funciona correctamente</p>";
    } else {
        echo "<p style='color: red;'>❌ Error en endpoint de lista</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error probando endpoint: " . $e->getMessage() . "</p>";
}

echo "<h2>🎯 Resumen</h2>";
echo "<p>Si todos los tests muestran ✅, el módulo de usuarios debería funcionar correctamente.</p>";
echo "<p><a href='usuarios.html' style='background: #2d5016; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🚀 Abrir Módulo de Usuarios</a></p>";
?>