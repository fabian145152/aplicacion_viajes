<?php
// 🔴 HABILITAR ERRORES PARA DEBUG
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once "../../../funciones/funciones.php";
header('Content-Type: application/json');

// Obtener los datos del POST (JSON)
$data = json_decode(file_get_contents('php://input'), true);

// 🔴 VERIFICAR QUE LLEGAN DATOS
if (!$data) {
    echo json_encode(['res' => 'ERROR', 'msg' => 'No se recibieron datos']);
    exit;
}

$movil = $data['movil'] ?? 'SIN_VIAJE';
$origen = $data['origen'] ?? '';
$destino = $data['destino'] ?? '';
$origen_lat = $data['origen_lat'] ?? null;
$origen_lng = $data['origen_lng'] ?? null;
$destino_lat = $data['destino_lat'] ?? null;
$destino_lng = $data['destino_lng'] ?? null;
$distancia = $data['distancia'] ?? null;
$tiempo = $data['tiempo'] ?? null;

// Validar datos obligatorios
if (!$origen || !$destino) {
    echo json_encode(['res' => 'ERROR', 'msg' => 'Faltan origen o destino']);
    exit;
}

if (!$distancia || $distancia == 0) {
    echo json_encode(['res' => 'ERROR', 'msg' => 'La distancia no puede ser 0']);
    exit;
}

$conn = conexion();

if (!$conn) {
    echo json_encode(['res' => 'ERROR', 'msg' => 'Error de conexión a la base de datos']);
    exit;
}

try {
    // 🔴 INSERTAR EN LA TABLA recorridos_viaje
    $sql = "INSERT INTO recorridos_viaje (
        id_viaje,
        movil,
        origen,
        destino,
        origen_lat,
        origen_lng,
        destino_lat,
        destino_lng,
        distancia,
        tiempo
    ) VALUES (
        NULL,
        ?,
        ?,
        ?,
        ?,
        ?,
        ?,
        ?,
        ?,
        ?
    )";

    $stmt = $conn->prepare($sql);
    $resultado = $stmt->execute([
        $movil,
        $origen,
        $destino,
        $origen_lat,
        $origen_lng,
        $destino_lat,
        $destino_lng,
        $distancia,
        $tiempo
    ]);

    if ($resultado) {
        $id_recorrido = $conn->lastInsertId();
        echo json_encode([
            'res' => 'OK',
            'msg' => 'Recorrido guardado correctamente',
            'id_recorrido' => $id_recorrido
        ]);
    } else {
        echo json_encode(['res' => 'ERROR', 'msg' => 'Error al guardar el recorrido']);
    }
} catch (PDOException $e) {
    echo json_encode([
        'res' => 'ERROR',
        'msg' => 'Error en la base de datos: ' . $e->getMessage()
    ]);
}
