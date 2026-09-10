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

// 🔴 LOG: Mostrar qué llega al servidor
error_log("📥 JSON recibido: " . $jsonCrudo);

// Validar que el JSON llegó bien
if (!$datos) {
    echo json_encode(["res" => "ERROR", "msg" => "JSON vacío o inválido"]);
    exit;
}

// Extraer los datos del celular
$lat = $datos['lat'] ?? null;
$lng = $datos['lng'] ?? null;
$movil = $datos['movil'] ?? '';
$status = $datos['status'] ?? 'activo';
$viaje_id = $datos['viaje_id'] ?? 0;  // 🔥 default 0

// 🔥 NUEVO: device_id puede venir o no desde la app
$device_id = $datos['device_id'] ?? $movil; // Si no viene, usar el móvil

// Validar que haya coordenadas
if ($lat === null || $lng === null) {
    echo json_encode(["res" => "ERROR", "msg" => "Faltan coordenadas"]);
    exit;
}

try {
    // 🔥 Siempre incluimos viaje_id (con default 0)
    $sql = "INSERT INTO ubicaciones (lat, lng, movil, device_id, status, viaje_id) 
            VALUES (:lat, :lng, :movil, :device_id, :status, :viaje_id)";
    
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        throw new Exception("Error al preparar la consulta SQL");
    }

    // Vincular parámetros correctamente
    $stmt->bindParam(':lat', $lat);
    $stmt->bindParam(':lng', $lng);
    $stmt->bindParam(':movil', $movil);
    $stmt->bindParam(':device_id', $device_id);  // 🔥 AHORA ES EL DEVICE_ID REAL
    $stmt->bindParam(':status', $status);
    
    // 🔥 viaje_id siempre con valor (0 si no hay)
    $viaje_id_num = intval($viaje_id);
    $stmt->bindParam(':viaje_id', $viaje_id_num, PDO::PARAM_INT);

    error_log("🟢 Insertando - movil: $movil | status: $status | viaje_id: $viaje_id_num | device_id: $device_id");

    $resultado = $stmt->execute();

    if ($resultado) {
        echo json_encode([
            "res" => "OK", 
            "msg" => "Coordenadas guardadas correctamente",
            "movil" => $movil,
            "status" => $status,
            "viaje_id" => $viaje_id_num
        ]);
    } else {
        echo json_encode(["res" => "ERROR", "msg" => "Error al ejecutar la inserción"]);
    }
} catch (Exception $e) {
    error_log("❌ Excepción en recibir.php: " . $e->getMessage());
    echo json_encode(["res" => "ERROR", "msg" => "Excepción: " . $e->getMessage()]);
}
?>