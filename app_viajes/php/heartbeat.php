<?php
header('Content-Type: application/json; charset=utf-8');
include_once "../funciones/funciones.php";

$movil = 0;
if (isset($_POST['movil'])) {
    $movil = (int)$_POST['movil'];
} elseif (isset($_GET['movil'])) {
    $movil = (int)$_GET['movil'];
}

if ($movil <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Falta movil']);
    exit;
}

$pdo = conexion();

$sql = "UPDATE choferes 
        SET ultima_actividad = NOW() 
        WHERE movil = :movil";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':movil', $movil, PDO::PARAM_INT);
$stmt->execute();

$limpiados = limpiar_choferes_inactivos($pdo, 90);

echo json_encode([
    'status'    => 'ok',
    'movil'     => $movil,
    'limpiados' => $limpiados,
    'ts'        => date('Y-m-d H:i:s')
]);