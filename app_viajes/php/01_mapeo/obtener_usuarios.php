<?php
header("Content-Type: application/json");

$conexion = new mysqli("localhost", "root", "belgrado", "app_viajes");

if ($conexion->connect_error) {
    echo json_encode([]);
    exit;
}

$sql = "SELECT DISTINCT user_id FROM ubicaciones ORDER BY user_id";

$result = $conexion->query($sql);

$usuarios = [];

while ($row = $result->fetch_assoc()) {
    $usuarios[] = $row['user_id'];
}

echo json_encode($usuarios);

$conexion->close();
?>
