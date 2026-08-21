<?php
/**
 * actualizar_login.php
 * Endpoint para actualizar el estado de login de un chofer
 * Recibe: { "movil": "3034", "logeado": 1 }
 * 
 * LOGICA:
 * - logeado = 1 → El chofer ha iniciado sesión en la app
 * - logeado = 0 → El chofer ha cerrado sesión
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
$logeado = $data['logeado'] ?? 0;

// Validar que el móvil no esté vacío
if (empty($movil)) {
    echo json_encode([
        'res' => 'ERROR', 
        'msg' => 'Número de móvil no proporcionado'
    ]);
    exit;
}

// Validar que logeado sea 0 o 1
if (!in_array($logeado, [0, 1])) {
    echo json_encode([
        'res' => 'ERROR', 
        'msg' => 'Valor de logeado inválido (debe ser 0 o 1)'
    ]);
    exit;
}

try {
    // Preparar la consulta UPDATE
    $stmt = $conn->prepare("UPDATE choferes SET logeado = ? WHERE movil = ?");
    $stmt->execute([$logeado, $movil]);
    
    // Verificar cuántas filas se actualizaron
    $affected = $stmt->rowCount();
    
    if ($affected > 0) {
        $estado = $logeado == 1 ? 'logueado' : 'deslogueado';
        echo json_encode([
            'res' => 'OK', 
            'msg' => "Chofer $estado correctamente",
            'movil' => $movil,
            'logeado' => $logeado
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
                'msg' => "El chofer ya estaba " . ($logeado == 1 ? 'logueado' : 'deslogueado'),
                'movil' => $movil,
                'logeado' => $logeado
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