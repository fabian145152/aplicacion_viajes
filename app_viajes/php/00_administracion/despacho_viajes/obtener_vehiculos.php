<?php
include_once "../../../funciones/funciones.php";

header('Content-Type: application/json');

$conn = conexion();

if (!$conn) {
    echo json_encode(['error' => 'Error de conexión a la base de datos']);
    exit;
}

try {
    // ✅ SOLO MÓVILES CON CHOFER LOGEADO Y ACTIVO
    //    Y QUE NO ESTÉN ASIGNADOS A UN VIAJE EN CURSO
    $query = "SELECT DISTINCT 
                v.id, 
                v.marca, 
                v.modelo, 
                v.patente, 
                v.categoria,
                v.estado,
                v.id_chofer,
                c.nombre as nombre_chofer,
                c.apellido as apellido_chofer,
                c.movil,
                c.logeado,
                c.activo
              FROM vehiculos v
              INNER JOIN choferes c ON v.id_chofer = c.id
              WHERE v.estado != 'taller'
              AND v.id_chofer IS NOT NULL
              AND c.logeado = 1      -- 🔴 CHOFER LOGEADO
              AND c.activo = 1       -- 🔴 CHOFER ACTIVO (NUEVO)
              AND c.movil NOT IN (
                  SELECT DISTINCT asignado_a 
                  FROM viajes_despacho 
                  WHERE asignado_a IS NOT NULL 
                  AND asignado_a != '' 
                  AND estado NOT IN ('Completo', 'Cancelado')
              )
              ORDER BY c.movil ASC";

    $stmt = $conn->query($query);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $vehiculos = [];
    foreach ($resultados as $row) {
        $nombreChofer = '';
        if (!empty($row['nombre_chofer']) && !empty($row['apellido_chofer'])) {
            $nombreChofer = $row['nombre_chofer'] . ' ' . $row['apellido_chofer'];
        }

        $movil = $row['movil'] ?? 'Sin móvil';

        $vehiculos[] = [
            'id' => $row['id'],
            'movil' => $movil,
            'descripcion' => trim($row['marca'] . ' ' . $row['modelo'] . ' (' . $row['patente'] . ')'),
            'categoria' => $row['categoria'],
            'estado' => $row['estado'],
            'chofer' => $nombreChofer,
            'id_chofer' => $row['id_chofer'],
            'logeado' => $row['logeado'],
            'activo'  => $row['activo']
        ];
    }

    echo json_encode($vehiculos);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Error en la consulta: ' . $e->getMessage()]);
}
