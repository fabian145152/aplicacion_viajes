<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// ============================================
// CONFIGURACIÓN DE LA BASE DE DATOS
// ============================================
$host = 'localhost';
$dbname = 'app_viajes';
$username = 'root';
$password = 'belgrado';
// ============================================

ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        echo json_encode(['res' => 'ERROR', 'msg' => 'No se recibieron datos']);
        exit;
    }

    $movil = $data['movil'] ?? '';
    $deviceId = $data['device_id'] ?? '';
    $estado = $data['estado'] ?? 'deslogueado';  // ← recibimos 'estado' del app
    $latitud = $data['latitud'] ?? '0';
    $longitud = $data['longitud'] ?? '0';
    $timestamp = $data['timestamp'] ?? date('Y-m-d H:i:s');
    $nombreChofer = $data['nombre_chofer'] ?? '';

    if (empty($deviceId)) {
        echo json_encode(['res' => 'ERROR', 'msg' => 'Falta device_id']);
        exit;
    }

    // 🔥 CAMBIADO: Usamos 'status' en lugar de 'estado'
    $sqlCheck = "SELECT id FROM ubicaciones WHERE device_id = :device_id";
    $stmtCheck = $pdo->prepare($sqlCheck);
    $stmtCheck->execute([':device_id' => $deviceId]);
    $existe = $stmtCheck->fetch();

    if ($existe) {
        // 🔥 ACTUALIZAR: usamos 'status' y 'estado' para guardar el valor
        $sql = "UPDATE ubicaciones 
                SET movil = :movil, 
                    status = :estado,     ← 'status' es la columna que existe
                    lat = :latitud, 
                    lng = :longitud, 
                    fecha = :timestamp
                WHERE device_id = :device_id";
    } else {
        // 🔥 INSERTAR: usamos 'status' (la columna que existe)
        $sql = "INSERT INTO ubicaciones (movil, device_id, status, lat, lng, fecha) 
                VALUES (:movil, :device_id, :estado, :latitud, :longitud, :timestamp)";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':movil' => $movil,
        ':device_id' => $deviceId,
        ':estado' => $estado,  // ← 'estado' se guarda en la columna 'status'
        ':latitud' => $latitud,
        ':longitud' => $longitud,
        ':timestamp' => $timestamp,
    ]);

    echo json_encode([
        'res' => 'OK', 
        'msg' => 'Ubicación guardada correctamente',
        'device_id' => $deviceId,
        'estado' => $estado
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'res' => 'ERROR', 
        'msg' => 'Error de base de datos: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'res' => 'ERROR', 
        'msg' => 'Error: ' . $e->getMessage()
    ]);
}
?>