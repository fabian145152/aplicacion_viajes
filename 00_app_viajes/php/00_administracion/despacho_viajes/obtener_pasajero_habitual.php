<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

include_once "../../../funciones/funciones.php";

if (!isset($_GET['celular']) || empty($_GET['celular'])) {
    echo json_encode(['error' => 'Falta el parámetro celular']);
    exit;
}

$celular = $_GET['celular'];

$conn = conexion();
$stmt = $conn->prepare("SELECT * FROM pasajeros_habituales WHERE celular = ? LIMIT 1");
$stmt->execute([$celular]);
$pasajero = $stmt->fetch(PDO::FETCH_ASSOC);

if ($pasajero) {
    echo json_encode($pasajero);
} else {
    echo json_encode(['error' => 'Pasajero no encontrado']);
}
