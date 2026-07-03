<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Content-Type: application/json");

// 📥 Leer JSON
$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(["res" => "ERROR", "msg" => "No se recibieron datos"]);
    exit;
}

// 📌 Datos
$lat = $data['lat'] ?? null;
$lng = $data['lng'] ?? null;
$status = $data['status'] ?? null;
$usuario = $data['usuario_id'] ?? null;

// Validación básica
if (!$lat || !$lng || !$usuario) {
    echo json_encode(["res" => "ERROR", "msg" => "Datos incompletos"]);
    exit;
}

// 🔌 Conexión MariaDB
$conexion = new mysqli("localhost", "root", "belgrado", "app_viajes");

if ($conexion->connect_error) {
    echo json_encode(["res" => "ERROR", "msg" => "Error conexión DB"]);
    exit;
}

// 💾 Insert
$stmt = $conexion->prepare("
    INSERT INTO ubicaciones (lat, lng, status, usuario_id)
    VALUES (?, ?, ?, ?)
");

$stmt->bind_param("ddss", $lat, $lng, $status, $usuario);

if ($stmt->execute()) {
    echo json_encode([
        "res" => "OK",
        "msg" => "Guardado correctamente"
    ]);
} else {
    echo json_encode([
        "res" => "ERROR",
        "msg" => $stmt->error
    ]);
}

$stmt->close();
$conexion->close();
