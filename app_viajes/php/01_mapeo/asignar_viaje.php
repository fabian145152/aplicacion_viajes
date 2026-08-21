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

    // Verificar que el viaje existe y está pendiente o diferido
    $checkSql = "SELECT id, estado FROM viajes_despacho WHERE id = :viaje_id";
    $checkStmt = $con->prepare($checkSql);
    $checkStmt->execute([':viaje_id' => $viaje_id]);
    $viaje = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$viaje) {
        echo json_encode(['res' => 'ERROR', 'msg' => 'El viaje no existe']);
        exit;
    }

    if ($viaje['estado'] != 'Pendiente' && $viaje['estado'] != 'Diferido') {
        echo json_encode(['res' => 'ERROR', 'msg' => 'El viaje no está pendiente o diferido']);
        exit;
    }

    // 🔴 OBTENER DATOS DEL CHOFER Y VEHÍCULO
    $sqlDatos = "SELECT 
                    c.nombre AS chofer_nombre,
                    c.apellido AS chofer_apellido,
                    c.cel AS chofer_celular,
                    ve.marca AS vehiculo_marca,
                    ve.modelo AS vehiculo_modelo,
                    ve.patente AS vehiculo_patente,
                    ve.color AS vehiculo_color
                 FROM choferes c
                 LEFT JOIN vehiculos ve ON ve.id_chofer = c.id
                 WHERE c.movil = :movil_id";
    $stmtDatos = $con->prepare($sqlDatos);
    $stmtDatos->execute([':movil_id' => $movil_id]);
    $datos = $stmtDatos->fetch(PDO::FETCH_ASSOC);

    // 🔴 ASIGNAR VIAJE Y GUARDAR HISTÓRICO
    $sql = "UPDATE viajes_despacho 
            SET id_chofer = :movil_id,
                asignado_a = :movil_id,
                estado = 'En Curso',
                fecha_asignacion = NOW(),
                chofer_nombre_hist = :chofer_nombre,
                chofer_apellido_hist = :chofer_apellido,
                chofer_celular_hist = :chofer_celular,
                vehiculo_marca_hist = :vehiculo_marca,
                vehiculo_modelo_hist = :vehiculo_modelo,
                vehiculo_patente_hist = :vehiculo_patente,
                vehiculo_color_hist = :vehiculo_color
            WHERE id = :viaje_id";

    $stmt = $con->prepare($sql);
    $result = $stmt->execute([
        ':movil_id' => $movil_id,
        ':viaje_id' => $viaje_id,
        ':chofer_nombre' => $datos['chofer_nombre'] ?? null,
        ':chofer_apellido' => $datos['chofer_apellido'] ?? null,
        ':chofer_celular' => $datos['chofer_celular'] ?? null,
        ':vehiculo_marca' => $datos['vehiculo_marca'] ?? null,
        ':vehiculo_modelo' => $datos['vehiculo_modelo'] ?? null,
        ':vehiculo_patente' => $datos['vehiculo_patente'] ?? null,
        ':vehiculo_color' => $datos['vehiculo_color'] ?? null,
    ]);

    // 🔴 VERIFICAR QUE SE ACTUALIZÓ
    if (!$result) {
        echo json_encode(['res' => 'ERROR', 'msg' => 'Error al actualizar el viaje']);
        exit;
    }

    // Actualizar tabla choferes para marcar como activo
    $sqlChofer = "UPDATE choferes SET activo = 1 WHERE movil = :movil_id";
    $stmtChofer = $con->prepare($sqlChofer);
    $stmtChofer->execute([':movil_id' => $movil_id]);

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
