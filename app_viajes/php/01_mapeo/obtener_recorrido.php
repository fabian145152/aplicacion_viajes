<?php
include_once "../../funciones/funciones.php";

// Protegemos la página para operadores (3) o administrador (0)
protegerPagina([0, 3]);

$con = conexion();

$user_id = isset($_GET['user_id']) ? $_GET['user_id'] : '';

if (empty($user_id)) {
    echo json_encode(['error' => 'user_id requerido']);
    exit;
}

// Obtener todas las ubicaciones del usuario con su estado y el último estado
$sql = "SELECT u1.lat, u1.lng, u1.fecha, u1.device_id as status,
        (SELECT device_id FROM ubicaciones u2 WHERE u2.user_id = u1.user_id ORDER BY u2.fecha DESC LIMIT 1) as ultimo_status
        FROM ubicaciones u1
        WHERE u1.user_id = ?
        ORDER BY u1.fecha ASC";

$stmt = $con->prepare($sql);
$stmt->execute([$user_id]);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Asegurar que los campos tengan valores por defecto
foreach ($data as &$row) {
    $row['status'] = $row['status'] ?? 'inactivo';
    $row['ultimo_status'] = $row['ultimo_status'] ?? 'inactivo';
}

echo json_encode($data);
