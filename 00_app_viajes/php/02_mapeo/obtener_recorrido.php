<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Content-Type: application/json");

// CONEXIÓN
$conexion = new mysqli("localhost", "root", "belgrado", "app_viajes");

if ($conexion->connect_error) {
    echo json_encode([
        "res" => "ERROR",
        "msg" => "Error de conexión"
    ]);
    exit;
}

// PARÁMETRO
$usuario = $_GET['user_id'] ?? '';

if (empty($usuario)) {
    echo json_encode([
        "res" => "ERROR",
        "msg" => "Falta usuario_id"
    ]);
    exit;
}

// QUERY CORREGIDA
$stmt = $conexion->prepare("
    SELECT 
        lat,
        lng,
        user_id,
        device_id,
        fecha AS fecha_registro
    FROM ubicaciones
    WHERE user_id = ?
    ORDER BY fecha ASC
");

$stmt->bind_param("s", $usuario);
$stmt->execute();

$resultado = $stmt->get_result();

$datos = [];



while ($fila = $resultado->fetch_assoc()) {
    $datos[] = $fila;
}

// RESPUESTA
echo json_encode($datos);

$stmt->close();
$conexion->close();
