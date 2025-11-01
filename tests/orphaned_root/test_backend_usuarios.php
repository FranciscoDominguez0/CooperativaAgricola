<?php
// Test directo del backend de usuarios
// Cooperativa Agrícola La Pintada

echo "<h1>🧪 Test Backend Usuarios</h1>";

// Test 1: Estadísticas
echo "<h2>1. Test Estadísticas</h2>";
try {
    $_GET['action'] = 'estadisticas';
    ob_start();
    include 'php/usuarios.php';
    $output = ob_get_clean();
    
    echo "<p>Respuesta:</p>";
    echo "<pre style='background: #f0f0f0; padding: 10px; border-radius: 5px;'>";
    echo htmlspecialchars($output);
    echo "</pre>";
    
    $data = json_decode($output, true);
    if ($data && $data['success']) {
        echo "<p style='color: green;'>✅ Estadísticas funcionan correctamente</p>";
        echo "<p>Total usuarios: " . $data['estadisticas']['total_usuarios'] . "</p>";
        echo "<p>Usuarios activos: " . $data['estadisticas']['usuarios_activos'] . "</p>";
    } else {
        echo "<p style='color: red;'>❌ Error en estadísticas</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

// Test 2: Lista de usuarios
echo "<h2>2. Test Lista de Usuarios</h2>";
try {
    $_GET['action'] = 'lista';
    ob_start();
    include 'php/usuarios.php';
    $output = ob_get_clean();
    
    echo "<p>Respuesta:</p>";
    echo "<pre style='background: #f0f0f0; padding: 10px; border-radius: 5px;'>";
    echo htmlspecialchars($output);
    echo "</pre>";
    
    $data = json_decode($output, true);
    if ($data && $data['success']) {
        echo "<p style='color: green;'>✅ Lista de usuarios funciona correctamente</p>";
        echo "<p>Total usuarios encontrados: " . $data['total'] . "</p>";
        
        if (!empty($data['usuarios'])) {
            echo "<h3>Usuarios:</h3>";
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>ID</th><th>Nombre</th><th>Email</th><th>Rol</th><th>Estado</th></tr>";
            foreach ($data['usuarios'] as $usuario) {
                echo "<tr>";
                echo "<td>" . $usuario['id_usuario'] . "</td>";
                echo "<td>" . $usuario['nombre'] . "</td>";
                echo "<td>" . $usuario['email'] . "</td>";
                echo "<td>" . $usuario['rol'] . "</td>";
                echo "<td>" . $usuario['estado'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } else {
        echo "<p style='color: red;'>❌ Error en lista de usuarios</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<h2>🎯 Resumen</h2>";
echo "<p>Si ambos tests muestran ✅, el backend está funcionando correctamente.</p>";
echo "<p><a href='' style='background: #2d5016; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🚀 Probar Módulo de Usuarios</a></p>";
?>

