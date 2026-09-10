<?php
header('Content-Type: application/json');

include_once "../../../funciones/funciones.php";
protegerPagina([0, 3]);

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id === 0) {
    echo json_encode(['error' => 'ID no válido']);
    exit;
}

$conn = conexion();

$sql = "SELECT a.*, u.nom_apellido as usuario_nombre 
        FROM auditoria_general a
        LEFT JOIN usuarios u ON a.usuario_id = u.id
        WHERE a.id = ?";

$stmt = $conn->prepare($sql);
$stmt->execute([$id]);
$resultado = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$resultado) {
    echo json_encode(['error' => 'Registro no encontrado']);
    exit;
}

// Formatear fecha
$resultado['fecha_hora'] = date('d/m/Y H:i:s', strtotime($resultado['fecha_hora']));

echo json_encode($resultado);
?>