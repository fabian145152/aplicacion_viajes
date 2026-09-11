<?php
// Cabeceras para permitir conexión desde el celular
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST");

// Incluir el archivo de funciones PDO de tu sistema
include_once "../../funciones/funciones.php";

// Obtener la conexión PDO
$conn = conexion();

if (!$conn) {
    echo json_encode(["res" => "ERROR", "msg" => "Error de conexión a la base de datos"]);
    exit;
}

// Leer el JSON que envía Flutter
$jsonCrudo = file_get_contents("php://input");
$datos = json_decode($jsonCrudo, true);

error_log("📥 JSON recibido: " . $jsonCrudo);

if (!$datos) {
    echo json_encode(["res" => "ERROR", "msg" => "JSON vacío o inválido"]);
    exit;
}

// Extraer datos del celular
$lat = $datos['lat'] ?? null;
$lng = $datos['lng'] ?? null;
$movil = $datos['movil'] ?? '';
$status = $datos['status'] ?? 'activo';
$viaje_id = $datos['viaje_id'] ?? 0;

// Validar
if ($lat === null || $lng === null) {
    echo json_encode(["res" => "ERROR", "msg" => "Faltan coordenadas"]);
    exit;
}

// 🔥 REGLA DE NEGOCIO: Forzar viaje_id = 0 en ciertos estados
// CERRANDO, ASIGNADO y A BORDO respetan el viaje_id que envía la app
$status_upper = strtoupper($status);

$estados_sin_viaje = ['ACTIVO', 'INACTIVO', 'DESLOGUEADO', 'LOGUEADO'];

if (in_array($status_upper, $estados_sin_viaje)) {
    // 🔥 En estos estados NO debe haber viaje asignado
    $viaje_id = 0;
    error_log("🧹 Servidor forzó viaje_id = 0 (estado: $status)");
} else {
    // ASIGNADO, A BORDO, CERRANDO → mantener el viaje_id que envía la app
    $viaje_id = intval($viaje_id);
    error_log("🚕 Respetando viaje_id = $viaje_id (estado: $status)");
}

try {
    $sql = "INSERT INTO ubicaciones (lat, lng, movil, device_id, status, viaje_id) 
            VALUES (:lat, :lng, :movil, :device_id, :status, :viaje_id)";
    
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        throw new Exception("Error al preparar la consulta SQL");
    }

    $stmt->bindParam(':lat', $lat);
    $stmt->bindParam(':lng', $lng);
    $stmt->bindParam(':movil', $movil);
    $stmt->bindParam(':device_id', $status);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':viaje_id', $viaje_id, PDO::PARAM_INT);

    error_log("🟢 Insertando - movil: $movil | status: $status | viaje_id: $viaje_id");

    $resultado = $stmt->execute();

    if ($resultado) {
        echo json_encode([
            "res" => "OK", 
            "msg" => "Coordenadas guardadas",
            "viaje_id_guardado" => $viaje_id,
            "status" => $status
        ]);
    } else {
        echo json_encode(["res" => "ERROR", "msg" => "Error al insertar"]);
    }
} catch (Exception $e) {
    error_log("❌ Excepción: " . $e->getMessage());
    echo json_encode(["res" => "ERROR", "msg" => "Excepción: " . $e->getMessage()]);
}
?>