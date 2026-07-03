<?php

/**
 * RECEPTOR DE COORDENADAS GPS - POSTGRESQL + POSTGIS
 * Ubicación sugerida: C:\xampp\htdocs\app_viajes\recibir.php
 */

// 1. Configuración de cabeceras (CORS) para que Flutter no tenga bloqueos
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

// Manejo de peticiones preflight (peticiones de seguridad del navegador/app)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit;
}

// 2. Conexión a la base de datos
// Puedes usar tu include_once "funciones/funciones.php"; si ya lo tienes configurado.
// Si no, usa este bloque:
$host = "localhost";
$db   = "app_viajes";
$user = "postgres";
$pass = "belgrado"; // Tu contraseña configurada
$port = "5432";

try {
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(["res" => "error", "mensaje" => "Error de conexión: " . $e->getMessage()]);
    exit;
}

// 3. Lectura de los datos JSON enviados por Flutter
$json = file_get_contents('php://input');
$datos = json_decode($json, true);

// 4. Validación de datos obligatorios
if (
    !$datos ||
    !isset($datos['lat']) ||
    !isset($datos['lng']) ||
    !isset($datos['usuario_id'])
) {
    http_response_code(400);
    echo json_encode([
        "res" => "error",
        "mensaje" => "Datos incompletos. Se requiere lat, lng y usuario_id"
    ]);
    exit;
}

// 5. Inserción en la base de datos
try {
    // Definimos el estado por defecto si no viene en el JSON
    $status = isset($datos['status']) ? $datos['status'] : 'activo';
    $usuario_id = $datos['usuario_id'];
    $lat = (float)$datos['lat'];
    $lng = (float)$datos['lng'];

    // Usamos las funciones de PostGIS para crear el punto geográfico
    $sql = "INSERT INTO ubicaciones (posicion, status, usuario_id) 
            VALUES (ST_SetSRID(ST_MakePoint(:lng, :lat), 4326), :status, :usuario_id)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'lng'        => $lng,
        'lat'        => $lat,
        'status'     => $status,
        'usuario_id' => $usuario_id
    ]);

    // 6. Monitor de depuración (Opcional, escribe en un .txt para ver si llegan datos)
    $logEntry = "[" . date("Y-m-d H:i:s") . "] ID: $usuario_id | Lat: $lat | Lng: $lng | Status: $status\n";
    file_put_contents("monitor.txt", $logEntry, FILE_APPEND);

    // 7. Respuesta de éxito
    echo json_encode([
        "res" => "OK",
        "mensaje" => "Coordenada registrada correctamente para el usuario: " . $usuario_id
    ]);
} catch (Exception $e) {
    // Respuesta en caso de error en el SQL o PostGIS
    http_response_code(500);
    echo json_encode([
        "res" => "error",
        "mensaje" => "Error al insertar en DB: " . $e->getMessage()
    ]);
}
