<?php
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

include_once "../../funciones/funciones.php";

try {
    $con = conexion();

    // Solo devolver viajes con estado 'Pendiente' o 'Diferido'
    $sql = "SELECT 
                v.id,
                v.nombre_pasaj,
                v.cel_pasaj,
                v.direccion_origen,
                v.direccion_destino,
                v.origen_lat,
                v.origen_lng,
                v.destino_lat,
                v.destino_lng,
                v.obs_operador,
                v.obs_pasaj,
                v.categoria_movil,
                v.estado,
                v.fecha,
                v.hora,
                v.cc,
                e.razon_social as empresa_nombre
            FROM viajes_despacho v
            LEFT JOIN cuenta_empresa e ON v.cc = e.id_empresa
            WHERE v.estado IN ('Pendiente', 'Diferido')
            AND (v.id_chofer = 0 OR v.id_chofer IS NULL)
            ORDER BY 
                CASE 
                    WHEN v.estado = 'Diferido' THEN 1 
                    ELSE 0 
                END,
                v.fecha ASC, 
                v.hora ASC";

    $stmt = $con->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'res' => 'OK',
        'viajes' => $result
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'res' => 'ERROR',
        'msg' => 'Error de base datos: ' . $e->getMessage()
    ]);
}
