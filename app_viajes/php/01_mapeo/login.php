<?php
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Incluir funciones de conexión
include_once "../../funciones/funciones.php";

// Recibir datos JSON
$input = file_get_contents('php://input');
$data = json_decode($input, true);

$user = $data['user'] ?? '';
$clave = $data['clave'] ?? '';

// Validar que los campos no estén vacíos
if (empty($user) || empty($clave)) {
    echo json_encode(['res' => 'ERROR', 'msg' => 'Usuario y clave requeridos']);
    exit;
}

try {
    $con = conexion();

    // Buscar en la tabla choferes
    $sql = "SELECT id, nombre, apellido, movil, user, clave 
            FROM choferes 
            WHERE user = ? AND clave = ? AND movil IS NOT NULL AND movil != 0";

    $stmt = $con->prepare($sql);
    $stmt->execute([$user, $clave]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        echo json_encode([
            'res' => 'OK',
            'msg' => 'Login exitoso',
            'movil' => $result['movil'],
            'nombre' => $result['nombre'],
            'apellido' => $result['apellido'],
            'user' => $result['user']
        ]);
    } else {
        echo json_encode(['res' => 'ERROR', 'msg' => 'Usuario o clave incorrectos']);
    }
} catch (PDOException $e) {
    echo json_encode(['res' => 'ERROR', 'msg' => 'Error de base de datos: ' . $e->getMessage()]);
}
