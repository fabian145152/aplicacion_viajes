<?php
header("Content-Type: application/json");

$conexion = new mysqli("localhost", "root", "belgrado", "app_viajes");

if ($conexion->connect_error) {
    echo json_encode([]);
    exit;
}

$user_id = $_GET['user_id'] ?? '';

if ($user_id == '') {
    echo json_encode([]);
    exit;
}

$sql = "
SELECT lat, lng, user_id, device_id, fecha
FROM ubicaciones
WHERE user_id = ?
ORDER BY fecha ASC
";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("s", $user_id);
$stmt->execute();

$result = $stmt->get_result();

$data = [];

while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);

$stmt->close();
$conexion->close();
