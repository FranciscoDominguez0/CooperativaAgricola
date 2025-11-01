<?php
require_once 'php/config.php';

echo "<h2>Session Test</h2>";
echo "<p>Session ID: " . session_id() . "</p>";
echo "<p>Session Status: " . (session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Inactive') . "</p>";
echo "<h3>Session Data:</h3>";
echo "<pre>" . print_r($_SESSION, true) . "</pre>";

if (isset($_SESSION['user_id'])) {
    echo "<p style='color: green;'>✅ User ID found: " . $_SESSION['user_id'] . "</p>";
} else {
    echo "<p style='color: red;'>❌ No user_id in session</p>";
}

echo "<h3>Test Links:</h3>";
echo "<a href=''>Go to Login</a><br>";
echo "<a href=''>Go to Dashboard</a><br>";
echo "<a href='php/verificar_sesion.php'>Check Session API</a><br>";
?>


