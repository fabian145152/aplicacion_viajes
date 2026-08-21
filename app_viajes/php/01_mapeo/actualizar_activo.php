<?php
/**
 * actualizar_activo.php
 * Endpoint para actualizar el estado ACTIVO de un chofer
 * Recibe: { "movil": "3034", "activo": 1 }
 * 
 * LOGICA:
 * - activo = 1 → El chofer ha activado el seguimiento GPS
 * - activo = 0 → El chofer ha desactivado el seguimiento GPS
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Incluir funciones de conexión
include_once "../../funciones/funciones.php";

// Conectar a la base de datos
$conn = conexion();

if (!$conn) {
    echo json_encode([
        'res' => 'ERROR', 
        'msg' => 'Error de conexión a la base de datos'
    ]);
    exit;
}

// Obtener datos del POST (JSON)
$data = json_decode(file_get_contents('php://input'), true);

// Validar datos
if (!$data) {
    echo json_encode([
        'res' => 'ERROR', 
        'msg' => 'Datos JSON inválidos'
    ]);
    exit;
}

$movil = $data['movil'] ?? '';
$activo = $data['activo'] ?? 0;

// Validar que el móvil no esté vacío
if (empty($movil)) {
    echo json_encode([
        'res' => 'ERROR', 
        'msg' => 'Número de móvil no proporcionado'
    ]);
    exit;
}

// Validar que activo sea 0 o 1
if (!in_array($activo, [0, 1])) {
    echo json_encode([
        'res' => 'ERROR', 
        'msg' => 'Valor de activo inválido (debe ser 0 o 1)'
    ]);
    exit;
}

try {
    // Preparar la consulta UPDATE usando PDO (igual que en tu otro archivo)
    $stmt = $conn->prepare("UPDATE choferes SET activo = ? WHERE movil = ?");
    $stmt->execute([$activo, $movil]);
    
    // Verificar cuántas filas se actualizaron
    $affected = $stmt->rowCount();
    
    if ($affected > 0) {
        $estado = $activo == 1 ? 'activado' : 'desactivado';
        echo json_encode([
            'res' => 'OK', 
            'msg' => "Seguimiento $estado correctamente",
            'movil' => $movil,
            'activo' => $activo
        ]);
    } else {
        // Si no se actualizó, verificar si el chofer existe
        $stmtCheck = $conn->prepare("SELECT id FROM choferes WHERE movil = ?");
        $stmtCheck->execute([$movil]);
        $existe = $stmtCheck->fetch();
        
        if ($existe) {
            // El chofer existe pero ya tenía el mismo estado
            echo json_encode([
                'res' => 'OK', 
                'msg' => "El chofer ya tenía el seguimiento " . ($activo == 1 ? 'activado' : 'desactivado'),
                'movil' => $movil,
                'activo' => $activo
            ]);
        } else {
            echo json_encode([
                'res' => 'ERROR', 
                'msg' => 'No se encontró un chofer con el móvil: ' . $movil
            ]);
        }
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'res' => 'ERROR', 
        'msg' => 'Error en la base de datos: ' . $e->getMessage()
    ]);
    exit;
}
?>