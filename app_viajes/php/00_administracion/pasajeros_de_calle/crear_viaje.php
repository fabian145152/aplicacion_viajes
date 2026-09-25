<?php
header('Content-Type: application/json; charset=utf-8');
include_once "../../../funciones/funciones.php";

$input = file_get_contents('php://input');
$data = json_decode($input, true);

$pasajero_id = (int)($data['pasajero_id'] ?? 0);
$direccion   = trim($data['direccion'] ?? '');
$cp          = (int)($data['cp'] ?? 0);

// ---- Validaciones ----
$errores = [];

if ($pasajero_id <= 0)  $errores[] = 'Falta el ID del pasajero.';
if ($direccion === '')  $errores[] = 'Falta la dirección de origen.';

if (!empty($errores)) {
    echo json_encode(['res' => 'ERROR', 'errores' => $errores]);
    exit;
}

$pdo = conexion();

// ---- Buscar al pasajero ----
$stmt = $pdo->prepare("SELECT nombre_apellido, celular FROM pasajeros_app 
                       WHERE id = :id AND confirmado = 1 LIMIT 1");
$stmt->execute([':id' => $pasajero_id]);
$pasajero = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pasajero) {
    echo json_encode(['res' => 'ERROR', 'msg' => 'Pasajero no encontrado o no confirmado.']);
    exit;
}

// ---- Insertar ----
// fecha_realizado, fecha_cerrado y tomado_por se dejan en NULL automáticamente
// porque no los incluimos en el INSERT.
$sql = "INSERT INTO pasajeros_app_viajes
            (creado_por, nombre, direccion, cp, Celular, fecha_creado)
        VALUES
            (:creado_por, :nombre, :direccion, :cp, :celular, NOW())";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':creado_por' => $pasajero_id,
    ':nombre'     => $pasajero['nombre_apellido'],
    ':direccion'  => $direccion,
    ':cp'         => $cp,
    ':celular'    => $pasajero['celular'],
]);

$id_viaje = (int)$pdo->lastInsertId();

echo json_encode([
    'res' => 'OK',
    'id'  => $id_viaje,
    'msg' => 'Viaje creado correctamente.',
    'fecha_creado' => date('Y-m-d H:i:s'),
]);
