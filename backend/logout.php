<?php
require_once 'auth.php';

$auth = new Auth();
$resultado = $auth->logout();

header('Content-Type: application/json');
echo json_encode($resultado);
?>