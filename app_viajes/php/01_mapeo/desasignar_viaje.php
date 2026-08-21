<?php
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

include_once "../../funciones/funciones.php";

$input = file_get_contents('php://input');
$data = json_decode($input, true);
if (!$data) $data = $_POST;

$viaje_id = $data['viaje_id'] ?? $data['id'] ?? 0;
$movil_id = $data['movil_id'] ?? $data['movil'] ?? 0;

if (empty($viaje_id)) {
    echo json_encode(['res' => 'ERROR', 'msg' => 'Falta el ID del viaje']);
    exit;
}

try {
    $con = conexion();

    // Verificar que el viaje existe
    $checkSql = "SELECT id, estado FROM viajes_despacho WHERE id = :viaje_id";
    $checkStmt = $con->prepare($checkSql);
    $checkStmt->execute([':viaje_id' => $viaje_id]);
    $viaje = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$viaje) {
        echo json_encode(['res' => 'ERROR', 'msg' => 'El viaje no existe']);
        exit;
    }

    // 🔴 Si está Completo o Cancelado, no se puede desasignar
    if ($viaje['estado'] == 'Completo' || $viaje['estado'] == 'Cancelado') {
        echo json_encode(['res' => 'ERROR', 'msg' => 'No se puede desasignar un viaje completo o cancelado']);
        exit;
    }

    // 🔴 Desasignar: volver a Pendiente
    $sql = "UPDATE viajes_despacho 
            SET id_chofer = 0,
                asignado_a = NULL,
                fecha_asignacion = NULL,
                estado = 'Pendiente'
            WHERE id = :viaje_id";

    $stmt = $con->prepare($sql);
    $stmt->execute([':viaje_id' => $viaje_id]);

    if (!empty($movil_id)) {
        $sqlChofer = "UPDATE choferes SET activo = 0 WHERE movil = :movil_id";
        $stmtChofer = $con->prepare($sqlChofer);
        $stmtChofer->execute([':movil_id' => $movil_id]);
    }

    echo json_encode([
        'res' => 'OK',
        'msg' => 'Viaje desasignado, vuelve a Pendiente'
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'res' => 'ERROR',
        'msg' => 'Error: ' . $e->getMessage()
    ]);
}
