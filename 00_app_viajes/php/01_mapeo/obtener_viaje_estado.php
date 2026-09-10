<?php
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

include_once "../../funciones/funciones.php";

$viaje_id = $_GET['id'] ?? 0;

if (empty($viaje_id)) {
    echo json_encode(['res' => 'ERROR', 'msg' => 'ID de viaje requerido']);
    exit;
}

try {
    $con = conexion();

    $sql = "SELECT id, estado, asignado_a FROM viajes_despacho WHERE id = :viaje_id";
    $stmt = $con->prepare($sql);
    $stmt->execute([':viaje_id' => $viaje_id]);
    $viaje = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$viaje) {
        echo json_encode(['res' => 'ERROR', 'msg' => 'Viaje no encontrado']);
        exit;
    }

    echo json_encode([
        'res' => 'OK',
        'estado' => $viaje['estado'],
        'asignado_a' => $viaje['asignado_a'] ?? '',
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'res' => 'ERROR',
        'msg' => 'Error: ' . $e->getMessage()
    ]);
}
