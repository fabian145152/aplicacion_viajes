<?php
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

include_once "../../funciones/funciones.php";

$input = file_get_contents('php://input');
$data = json_decode($input, true);

$viaje_id = $data['viaje_id'] ?? 0;
$movil_id = $data['movil_id'] ?? 0;

if (empty($viaje_id) || empty($movil_id)) {
    echo json_encode(['res' => 'ERROR', 'msg' => 'Datos incompletos']);
    exit;
}

try {
    $con = conexion();

    $checkSql = "SELECT id, estado, id_chofer FROM viajes_despacho WHERE id = :viaje_id";
    $checkStmt = $con->prepare($checkSql);
    $checkStmt->execute([':viaje_id' => $viaje_id]);
    $viaje = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$viaje) {
        echo json_encode(['res' => 'ERROR', 'msg' => 'El viaje no existe']);
        exit;
    }

    if ($viaje['estado'] != 'Pendiente') {
        echo json_encode(['res' => 'ERROR', 'msg' => 'El viaje ya fue asignado o cancelado']);
        exit;
    }

    if ($viaje['id_chofer'] != 0 && $viaje['id_chofer'] != null) {
        echo json_encode(['res' => 'ERROR', 'msg' => 'El viaje ya tiene un chofer asignado']);
        exit;
    }

    $sql = "UPDATE viajes_despacho 
            SET id_chofer = :movil_id,
                estado = 'Asignado'
            WHERE id = :viaje_id";

    $stmt = $con->prepare($sql);
    $stmt->execute([
        ':movil_id' => $movil_id,
        ':viaje_id' => $viaje_id
    ]);

    echo json_encode([
        'res' => 'OK',
        'msg' => 'Viaje asignado correctamente al móvil ' . $movil_id
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'res' => 'ERROR', 
        'msg' => 'Error al asignar viaje: ' . $e->getMessage()
    ]);
}
?>