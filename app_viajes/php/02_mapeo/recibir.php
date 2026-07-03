<?php
header("Content-Type: application/json");

// CONEXION
$conexion = new mysqli("localhost", "root", "belgrado", "app_viajes");

if ($conexion->connect_error) {
    echo json_encode([
        "res" => "ERROR",
        "msg" => "Conexion fallida"
    ]);
    exit;
}

// RECIBIR DATOS
$lat = $_POST['lat'] ?? null;
$lng = $_POST['lng'] ?? null;
$user_id = $_POST['user_id'] ?? null;
$device_id = $_POST['device_id'] ?? null;

// VALIDAR
if ($lat === null || $lng === null || $user_id === null || $device_id === null) {
    echo json_encode([
        "res" => "ERROR",
        "msg" => "Faltan datos",
        "data" => $_POST
    ]);
    exit;
}

// INSERTAR
$stmt = $conexion->prepare(
    "INSERT INTO ubicaciones (lat, lng, user_id, device_id) VALUES (?, ?, ?, ?)"
);

$stmt->bind_param("ddss", $lat, $lng, $user_id, $device_id);

if ($stmt->execute()) {
    echo json_encode([
        //"res" => "OK",
        "lat" => $lat,
        "lng" => $lng,
        "user_id" => $user_id,
        "device_id" => $device_id
    ]);
} else {
    echo json_encode([
        "res" => "ERROR",
        "msg" => "No se pudo guardar"
    ]);
}

$stmt->close();
$conexion->close();

