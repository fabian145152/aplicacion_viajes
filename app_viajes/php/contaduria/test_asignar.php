<?php
include_once "../../funciones/funciones.php";

$movil_id = 3; // Cambia por el móvil que quieras probar
$viaje_id = 120; // Cambia por un ID de viaje pendiente

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

echo "<h2>Datos obtenidos del chofer y vehículo:</h2>";
echo "<pre>";
print_r($datos);
echo "</pre>";

// Actualizar el viaje con los datos históricos
$sql = "UPDATE viajes_despacho 
        SET id_chofer = :movil_id,
            asignado_a = :movil_id,
            estado = 'En Curso',
            fecha_asignacion = NOW(),
            chofer_nombre_hist = :chofer_nombre,
            chofer_apellido_hist = :chofer_apellido,
            chofer_celular_hist = :chofer_celular,
            vehiculo_marca_hist = :vehiculo_marca,
            vehiculo_modelo_hist = :vehiculo_modelo,
            vehiculo_patente_hist = :vehiculo_patente,
            vehiculo_color_hist = :vehiculo_color
        WHERE id = :viaje_id";

$stmt = $con->prepare($sql);
$result = $stmt->execute([
    ':movil_id' => $movil_id,
    ':viaje_id' => $viaje_id,
    ':chofer_nombre' => $datos['chofer_nombre'] ?? null,
    ':chofer_apellido' => $datos['chofer_apellido'] ?? null,
    ':chofer_celular' => $datos['chofer_celular'] ?? null,
    ':vehiculo_marca' => $datos['vehiculo_marca'] ?? null,
    ':vehiculo_modelo' => $datos['vehiculo_modelo'] ?? null,
    ':vehiculo_patente' => $datos['vehiculo_patente'] ?? null,
    ':vehiculo_color' => $datos['vehiculo_color'] ?? null,
]);

echo "<h2>Resultado de la actualización:</h2>";
if ($result) {
    echo "✅ Viaje actualizado correctamente";
} else {
    echo "❌ Error al actualizar";
}

// Verificar los datos guardados
$sqlCheck = "SELECT 
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
             WHERE id = :viaje_id";
$stmtCheck = $con->prepare($sqlCheck);
$stmtCheck->execute([':viaje_id' => $viaje_id]);
$viaje = $stmtCheck->fetch(PDO::FETCH_ASSOC);

echo "<h2>Datos guardados en el viaje:</h2>";
echo "<pre>";
print_r($viaje);
echo "</pre>";
