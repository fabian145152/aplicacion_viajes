<?php
/*
// --- BLOQUE DE PRUEBA: GUARDAR EN LOG ---
$datos_recibidos = "Fecha: " . date("Y-m-d H:i:s") . " | Lat: " . ($_POST['lat'] ?? 'null') . " | Lng: " . ($_POST['lng'] ?? 'null') . " | Movil: " . ($_POST['user_id'] ?? 'null') . " | Estado: " . ($_POST['device_id'] ?? 'null') . "\n";
file_put_contents("debug_gps.txt", $datos_recibidos, FILE_APPEND);
// ----------------------------------------
*/
// Configurar los encabezados para permitir recibir JSON y responder en el mismo formato
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *"); // Permite conexiones desde cualquier dispositivo (Celular/Emulador)
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST");

// 1. Conexión a la Base de Datos (Asegúrate de que la contraseña y nombre de BD sean correctos)
$conexion = new mysqli("localhost", "root", "belgrado", "app_viajes");

// Verificar si hay errores de conexión
if ($conexion->connect_error) {
    echo json_encode([
        "res" => "ERROR", 
        "msg" => "Error de conexión a la base de datos: " . $conexion->connect_error
    ]);
    exit;
}

// 2. Leer el flujo de datos crudo (JSON) enviado desde Flutter
$jsonCrudo = file_get_contents("php://input");

// Decodificar el JSON para convertirlo en un array asociativo de PHP
$datos = json_decode($jsonCrudo, true);

// 3. Extraer y validar los datos mapeándolos con lo que envía tu App
$lat = $datos['lat'] ?? null;
$lng = $datos['lng'] ?? null;
$user_id = $datos['usuario_id'] ?? null;  // 'usuario_id' en Flutter -> 'user_id' en la BD
$device_id = $datos['status'] ?? null;    // 'status' en Flutter -> 'device_id' en la BD

// Validar que al menos las coordenadas no vengan vacías
if ($lat === null || $lng === null) {
    echo json_encode([
        "res" => "ERROR", 
        "msg" => "Faltan datos obligatorios (latitud o longitud)",
        "recibido" => $datos
    ]);
    exit;
}

// 4. Preparar la consulta SQL de manera segura para evitar Inyección SQL
$stmt = $conexion->prepare("
    INSERT INTO ubicaciones (lat, lng, user_id, device_id)
    VALUES (?, ?, ?, ?)
");

// "ddss" significa: double (lat), double (lng), string (user_id), string (device_id)
$stmt->bind_param("ddss", $lat, $lng, $user_id, $device_id);

// 5. Ejecutar y responder a Flutter
if ($stmt->execute()) {
    echo json_encode([
        "res" => "OK",
        "msg" => "Coordenadas guardadas correctamente"
    ]);
} else {
    echo json_encode([
        "res" => "ERROR",
        "msg" => "No se pudieron insertar los datos: " . $stmt->error
    ]);
}

// 6. Cerrar conexiones activas
$stmt->close();
$conexion->close();
?>