<?php
include_once "../../../funciones/funciones.php";
header('Content-Type: application/json');

$id_viaje = isset($_GET['id_viaje']) ? (int)$_GET['id_viaje'] : 0;

if (!$id_viaje) {
    echo json_encode(null);
    exit;
}

$recorrido = obtenerRecorridoPorViaje($id_viaje);
echo json_encode($recorrido);
