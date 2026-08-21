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

    // Verificar que el viaje existe
    $checkSql = "SELECT id, estado FROM viajes_despacho WHERE id = :viaje_id";
    $checkStmt = $con->prepare($checkSql);
    $checkStmt->execute([':viaje_id' => $viaje_id]);
    $viaje = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$viaje) {
        echo json_encode(['res' => 'ERROR', 'msg' => 'El viaje no existe']);
        exit;
    }

    // 🔴 Si ya está En Curso, responder OK
    if ($viaje['estado'] == 'En Curso') {
        echo json_encode(['res' => 'OK', 'msg' => 'Viaje ya está en curso']);
        exit;
    }

    // 🔴 Si está Pendiente o Diferido con asignado_a, cambiar a En Curso
    if ($viaje['estado'] == 'Pendiente' || $viaje['estado'] == 'Diferido') {
        // Verificar que tenga asignado_a
        $checkAsignado = "SELECT asignado_a FROM viajes_despacho WHERE id = :viaje_id AND asignado_a = :movil_id";
        $checkStmtAsignado = $con->prepare($checkAsignado);
        $checkStmtAsignado->execute([':viaje_id' => $viaje_id, ':movil_id' => $movil_id]);
        $asignado = $checkStmtAsignado->fetch(PDO::FETCH_ASSOC);

        if (!$asignado) {
            echo json_encode(['res' => 'ERROR', 'msg' => 'El viaje no está asignado a este móvil']);
            exit;
        }

        $sql = "UPDATE viajes_despacho 
                SET estado = 'En Curso'
                WHERE id = :viaje_id";
        $stmt = $con->prepare($sql);
        $stmt->execute([':viaje_id' => $viaje_id]);

        echo json_encode([
            'res' => 'OK',
            'msg' => 'Viaje en curso - Pasajero a bordo'
        ]);
        exit;
    }

    echo json_encode([
        'res' => 'ERROR',
        'msg' => 'El viaje no está pendiente, diferido o en curso. Estado actual: ' . $viaje['estado']
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'res' => 'ERROR',
        'msg' => 'Error: ' . $e->getMessage()
    ]);
}
