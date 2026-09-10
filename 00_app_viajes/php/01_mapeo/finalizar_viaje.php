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
$km = $data['km'] ?? 0;
$peajes = $data['peajes'] ?? 0;
$tiempo_espera = $data['tiempo_espera'] ?? 0;
$observaciones = $data['observaciones'] ?? '';
$estado = $data['estado'] ?? 'Completo';

if (empty($viaje_id) || empty($movil_id)) {
    echo json_encode(['res' => 'ERROR', 'msg' => 'Datos incompletos']);
    exit;
}

try {
    $con = conexion();

    // Verificar que el viaje existe y está en curso
    $checkSql = "SELECT id, estado, id_chofer, asignado_a FROM viajes_despacho WHERE id = :viaje_id";
    $checkStmt = $con->prepare($checkSql);
    $checkStmt->execute([':viaje_id' => $viaje_id]);
    $viaje = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$viaje) {
        echo json_encode(['res' => 'ERROR', 'msg' => 'El viaje no existe']);
        exit;
    }

    if ($viaje['estado'] != 'En Curso') {
        echo json_encode(['res' => 'ERROR', 'msg' => 'El viaje no está en curso. Estado actual: ' . $viaje['estado']]);
        exit;
    }

    // 🔴 Obtener datos del chofer y vehículo ANTES de liberarlos
    $sqlDatosHistoricos = "SELECT 
                                c.nombre AS chofer_nombre,
                                c.apellido AS chofer_apellido,
                                c.cel AS chofer_celular,
                                c.dir AS chofer_direccion,
                                c.barrio AS chofer_barrio,
                                c.cp AS chofer_cp,
                                ve.marca AS vehiculo_marca,
                                ve.modelo AS vehiculo_modelo,
                                ve.patente AS vehiculo_patente,
                                ve.color AS vehiculo_color,
                                ve.categoria AS vehiculo_categoria,
                                ve.estado AS vehiculo_estado,
                                ve.tipo AS vehiculo_tipo,
                                ve.id AS vehiculo_id
                            FROM viajes_despacho vd
                            LEFT JOIN choferes c ON c.movil = vd.asignado_a
                            LEFT JOIN vehiculos ve ON ve.id_chofer = c.id
                            WHERE vd.id = :viaje_id";
    $stmtDatosHistoricos = $con->prepare($sqlDatosHistoricos);
    $stmtDatosHistoricos->execute([':viaje_id' => $viaje_id]);
    $datosHistoricos = $stmtDatosHistoricos->fetch(PDO::FETCH_ASSOC);

    // Guardar en recorridos_viaje para histórico
    $sqlRecorrido = "INSERT INTO recorridos_viaje (id_viaje, movil, origen, destino, 
                      origen_lat, origen_lng, destino_lat, destino_lng, distancia, tiempo, fecha_registro)
                      VALUES (
                          :viaje_id,
                          :movil_id,
                          (SELECT direccion_origen FROM viajes_despacho WHERE id = :viaje_id),
                          (SELECT direccion_destino FROM viajes_despacho WHERE id = :viaje_id),
                          (SELECT origen_lat FROM viajes_despacho WHERE id = :viaje_id),
                          (SELECT origen_lng FROM viajes_despacho WHERE id = :viaje_id),
                          (SELECT destino_lat FROM viajes_despacho WHERE id = :viaje_id),
                          (SELECT destino_lng FROM viajes_despacho WHERE id = :viaje_id),
                          :km,
                          (SELECT TIMESTAMPDIFF(MINUTE, fecha_asignacion, NOW()) 
                           FROM viajes_despacho WHERE id = :viaje_id),
                          NOW()
                      )";
    $stmtRecorrido = $con->prepare($sqlRecorrido);
    $stmtRecorrido->execute([
        ':viaje_id' => $viaje_id,
        ':movil_id' => $movil_id,
        ':km' => $km
    ]);

    // 🔴 Finalizar el viaje GUARDANDO los datos históricos antes de liberar
    $sql = "UPDATE viajes_despacho 
            SET estado = :estado,
                id_chofer = 0,
                asignado_a = NULL,
                fecha_asignacion = NULL,
                km_recorridos = :km,
                peajes = :peajes,
                tiempo_espera = :tiempo_espera,
                observaciones_finalizacion = :observaciones,
                fecha_finalizacion = CURDATE(),
                chofer_nombre_hist = :chofer_nombre,
                chofer_apellido_hist = :chofer_apellido,
                chofer_celular_hist = :chofer_celular,
                vehiculo_marca_hist = :vehiculo_marca,
                vehiculo_modelo_hist = :vehiculo_modelo,
                vehiculo_patente_hist = :vehiculo_patente,
                vehiculo_color_hist = :vehiculo_color
            WHERE id = :viaje_id";

    $stmt = $con->prepare($sql);
    $stmt->execute([
        ':estado' => $estado,
        ':km' => $km,
        ':peajes' => $peajes,
        ':tiempo_espera' => $tiempo_espera,
        ':observaciones' => $observaciones,
        ':viaje_id' => $viaje_id,
        ':chofer_nombre' => $datosHistoricos['chofer_nombre'] ?? null,
        ':chofer_apellido' => $datosHistoricos['chofer_apellido'] ?? null,
        ':chofer_celular' => $datosHistoricos['chofer_celular'] ?? null,
        ':vehiculo_marca' => $datosHistoricos['vehiculo_marca'] ?? null,
        ':vehiculo_modelo' => $datosHistoricos['vehiculo_modelo'] ?? null,
        ':vehiculo_patente' => $datosHistoricos['vehiculo_patente'] ?? null,
        ':vehiculo_color' => $datosHistoricos['vehiculo_color'] ?? null,
    ]);

    // Liberar el móvil
    $sqlChofer = "UPDATE choferes SET activo = 1, logeado = 1 WHERE movil = :movil_id";
    $stmtChofer = $con->prepare($sqlChofer);
    $stmtChofer->execute([':movil_id' => $movil_id]);

    echo json_encode([
        'res' => 'OK',
        'msg' => 'Viaje finalizado correctamente. Datos históricos guardados. Unidad liberada.',
        'viaje_id' => $viaje_id,
        'movil_guardado_en_historico' => $movil_id
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'res' => 'ERROR',
        'msg' => 'Error al finalizar viaje: ' . $e->getMessage()
    ]);
}
