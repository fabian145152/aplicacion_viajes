<?php
include_once "../../funciones/funciones.php";

$movil_id = 3; // Cambia por el móvil que quieras probar

$con = conexion();

// Obtener datos del chofer y vehículo
$sqlDatos = "SELECT 
                c.nombre AS chofer_nombre,
                c.apellido AS chofer_apellido,
                c.cel AS chofer_celular,
                ve.marca AS vehiculo_marca,
                ve.modelo AS vehiculo_modelo,
                ve.patente AS vehiculo_patente,
                ve.color AS vehiculo_color
             FROM choferes c
             LEFT JOIN vehiculos ve ON ve.id_chofer = c.id
             WHERE c.movil = :movil_id";
$stmtDatos = $con->prepare($sqlDatos);
$stmtDatos->execute([':movil_id' => $movil_id]);
$datos = $stmtDatos->fetch(PDO::FETCH_ASSOC);

echo "<pre>";
echo "DATOS DEL CHOFER Y VEHÍCULO PARA MÓVIL $movil_id:\n";
print_r($datos);
echo "</pre>";

// Obtener un viaje asignado reciente
$sqlViaje = "SELECT 
                id, 
                asignado_a, 
                estado,
                chofer_nombre_hist,
                chofer_apellido_hist,
                chofer_celular_hist,
                vehiculo_marca_hist,
                vehiculo_modelo_hist,
                vehiculo_patente_hist,
                vehiculo_color_hist
             FROM viajes_despacho 
             WHERE asignado_a = :movil_id 
             ORDER BY id DESC 
             LIMIT 1";
$stmtViaje = $con->prepare($sqlViaje);
$stmtViaje->execute([':movil_id' => $movil_id]);
$viaje = $stmtViaje->fetch(PDO::FETCH_ASSOC);

echo "<pre>";
echo "VIAJE ASIGNADO AL MÓVIL $movil_id:\n";
print_r($viaje);
echo "</pre>";
