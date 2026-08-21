<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

include_once '../../funciones/funciones.php';

if (!isset($_GET['movil']) || empty($_GET['movil'])) {
    echo json_encode(['res' => 'ERROR', 'msg' => 'Falta el parametro movil']);
    exit;
}

$movil = $_GET['movil'];

$conn = conexion();

// 🔴 Buscar el viaje asignado a este móvil que NO esté completo ni cancelado
$sql = "SELECT 
            id, 
            nombre_pasaj, 
            cel_pasaj, 
            direccion_origen, 
            direccion_destino, 
            estado, 
            fecha, 
            hora, 
            categoria_movil, 
            obs_operador,
            obs_pasaj,
            origen_lat,
            origen_lng,
            destino_lat,
            destino_lng,
            cc,
            id_cc,
            id_autorizante,
            asignado_a
        FROM viajes_despacho 
        WHERE asignado_a = ?
        AND estado NOT IN ('Completo', 'Cancelado')
        ORDER BY id DESC
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->execute([$movil]);
$viaje = $stmt->fetch(PDO::FETCH_ASSOC);

if ($viaje) {
    // Tiene un viaje asignado
    echo json_encode([
        'res' => 'OK',
        'viajes' => [$viaje],
        'msg' => 'Viaje asignado encontrado'
    ]);
} else {
    // No tiene viaje asignado
    echo json_encode([
        'res' => 'OK',
        'viajes' => [],
        'msg' => 'No hay viajes asignados para este móvil'
    ]);
}
