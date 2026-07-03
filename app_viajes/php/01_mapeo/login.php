<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST");

// 1. Conexión a la Base de Datos
$conexion = new mysqli("localhost", "root", "belgrado", "app_viajes");

if ($conexion->connect_error) {
    echo json_encode(["res" => "ERROR", "msg" => "Error de conexión a la base de datos"]);
    exit;
}

// 2. Leer el JSON enviado desde Flutter
$jsonCrudo = file_get_contents("php://input");
$datos = json_decode($jsonCrudo, true);

$user = $datos['user'] ?? null;
$clave = $datos['clave'] ?? null;

if ($user === null || $clave === null) {
    echo json_encode(["res" => "ERROR", "msg" => "Usuario y contraseña requeridos"]);
    exit;
}

// 3. Consultar en la tabla 'choferes' buscando coincidencia exacta
// Modificado: Ahora seleccionamos también el campo 'movil' de la tabla choferes
$stmt = $conexion->prepare("SELECT id, movil FROM choferes WHERE user = ? AND clave = ? LIMIT 1");
$stmt->bind_param("ss", $user, $clave);

$stmt->execute();
$resultado = $stmt->get_result();

// 4. Validar si se encontró el registro
if ($resultado->num_rows > 0) {
    $chofer = $resultado->fetch_assoc();

    echo json_encode([
        "res" => "OK",
        "msg" => "Login correcto",
        "usuario_id" => $chofer['id'],   // Conservamos esto por si lo necesitas
        "movil" => $chofer['movil']       // <-- ¡ESTO ES LO QUE FLUTTER ESTABA ESPERANDO!
    ]);
} else {
    echo json_encode([
        "res" => "ERROR",
        "msg" => "Usuario o contraseña incorrectos"
    ]);
}

$stmt->close();
$conexion->close();
