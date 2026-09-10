<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

include_once '../../funciones/funciones.php';

$movil = $_GET['movil'] ?? '';

if (empty($movil)) {
    echo json_encode(['res' => 'ERROR', 'msg' => 'Falta el parametro movil']);
    exit;
}

$conn = conexion();

// 🔴 OBTENER EL VIAJE ACTIVO DEL MÓVIL (si tiene)
$sqlActivo = "SELECT id, estado, asignado_a, nombre_pasaj, direccion_origen, direccion_destino 
              FROM viajes_despacho 
              WHERE asignado_a = ? 
              AND estado IN ('Asignado', 'En Curso')
              LIMIT 1";
$stmtActivo = $conn->prepare($sqlActivo);
$stmtActivo->execute([$movil]);
$viajeActivo = $stmtActivo->fetch(PDO::FETCH_ASSOC);

if ($viajeActivo) {
    // Si tiene viaje activo, devolverlo como "en curso"
    echo json_encode([
        'res' => 'OK',
        'tiene_viaje_activo' => true,
        'viaje_activo' => $viajeActivo,
        'viajes' => [],
        'msg' => 'Ya tienes un viaje en curso'
    ]);
    exit;
}

// 🔴 SI NO TIENE VIAJE ACTIVO, mostrar viajes PENDIENTES disponibles
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
        WHERE estado = 'Pendiente'
        AND (asignado_a IS NULL OR asignado_a = '' OR asignado_a = '0')
        ORDER BY id DESC";

$stmt = $conn->prepare($sql);
$stmt->execute();
$viajes = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'res' => 'OK',
    'tiene_viaje_activo' => false,
    'viajes' => $viajes
]);
