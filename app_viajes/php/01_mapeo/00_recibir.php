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

// Validar que el JSON llegó bien
if (!$datos) {
    echo json_encode(["res" => "ERROR", "msg" => "JSON vacío o inválido"]);
    exit;
}

// Extraer los datos del celular
$lat = $datos['lat'] ?? null;
$lng = $datos['lng'] ?? null;
$movil = $datos['movil'] ?? '';        // El número de móvil va a la columna 'movil'
$status = $datos['status'] ?? 'activo'; // El estado va a la columna 'device_id' o 'status'

// Validar que haya coordenadas
if ($lat === null || $lng === null) {
    echo json_encode(["res" => "ERROR", "msg" => "Faltan coordenadas"]);
    exit;
}

try {
    // 🔴 CORRECCIÓN: Usamos los nombres de columna EXACTOS de tu captura
    $sql = "INSERT INTO ubicaciones (lat, lng, movil, device_id, status) 
            VALUES (:lat, :lng, :movil, :device_id, :status)";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        throw new Exception("Error al preparar la consulta SQL");
    }

    // Vinculamos los parámetros
    $stmt->bindParam(':lat', $lat);
    $stmt->bindParam(':lng', $lng);
    $stmt->bindParam(':movil', $movil);
    $stmt->bindParam(':device_id', $status);  // Guardamos 'activo/inactivo' en device_id
    $stmt->bindParam(':status', $status);     // Guardamos 'activo/inactivo' en status también

    $resultado = $stmt->execute();

    if ($resultado) {
        echo json_encode(["res" => "OK", "msg" => "Coordenadas guardadas correctamente"]);
    } else {
        echo json_encode(["res" => "ERROR", "msg" => "Error al ejecutar la inserción"]);
    }
} catch (Exception $e) {
    echo json_encode(["res" => "ERROR", "msg" => "Excepción: " . $e->getMessage()]);
}
