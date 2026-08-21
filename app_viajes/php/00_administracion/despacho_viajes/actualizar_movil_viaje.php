<?php
include_once "../../../funciones/funciones.php";

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

$id_viaje = $data['id_viaje'] ?? 0;
$movil = $data['movil'] ?? '';
$origen = $data['origen'] ?? '';
$destino = $data['destino'] ?? '';
$origen_lat = $data['origen_lat'] ?? null;
$origen_lng = $data['origen_lng'] ?? null;
$destino_lat = $data['destino_lat'] ?? null;
$destino_lng = $data['destino_lng'] ?? null;
$distancia = $data['distancia'] ?? null;
$tiempo = $data['tiempo'] ?? null;

if (!$id_viaje || !$movil || !$origen || !$destino) {
    echo json_encode(['res' => 'ERROR', 'msg' => 'Faltan datos obligatorios']);
    exit;
}

$resultado = guardarRecorrido($id_viaje, $movil, $origen, $destino, $origen_lat, $origen_lng, $destino_lat, $destino_lng, $distancia, $tiempo);

if ($resultado) {
    echo json_encode(['res' => 'OK', 'msg' => 'Recorrido guardado correctamente']);
} else {
    echo json_encode(['res' => 'ERROR', 'msg' => 'Error al guardar el recorrido']);
}
