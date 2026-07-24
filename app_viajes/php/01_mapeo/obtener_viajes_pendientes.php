<?php
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

include_once "../../funciones/funciones.php";

try {
    $con = conexion();

    // Definir valor por defecto para los minutos de anticipación
    $min_diferido = 10; // Valor por defecto

    // Intentar incluir el archivo de configuración si existe
    $rutaSeteos = "../../00_administracion/seteos/min_diferido.php";
    if (file_exists($rutaSeteos)) {
        include_once $rutaSeteos;
    } else {
        // Si no existe, intentar otra ruta
        $rutaSeteos = "../seteos/min_diferido.php";
        if (file_exists($rutaSeteos)) {
            include_once $rutaSeteos;
        }
    }

    // =============================================
    // CONVERTIR pre_viaje a Diferido cuando falta poco tiempo
    // =============================================
    $sql = "UPDATE viajes_despacho
            SET estado = 'Diferido'
            WHERE estado = 'pre_viaje'
            AND TIMESTAMPDIFF(MINUTE, NOW(), TIMESTAMP(fecha, hora)) <= ?
            AND TIMESTAMPDIFF(MINUTE, NOW(), TIMESTAMP(fecha, hora)) > 0";

    $stmt = $con->prepare($sql);
    $stmt->execute([$min_diferido]);

    // =============================================
    // CONVERTIR Diferido a Pendiente si ya pasó la hora
    // =============================================
    $sql = "UPDATE viajes_despacho
            SET estado = 'Pendiente'
            WHERE estado = 'Diferido'
            AND TIMESTAMPDIFF(MINUTE, NOW(), TIMESTAMP(fecha, hora)) <= 0
            AND (id_chofer = 0 OR id_chofer IS NULL)";

    $stmt = $con->prepare($sql);
    $stmt->execute();

    // =============================================
    // RESTABLECER pre_viaje que están en Diferido pero aún no deberían
    // =============================================
    $sql = "UPDATE viajes_despacho
            SET estado = 'pre_viaje'
            WHERE estado = 'Diferido'
            AND TIMESTAMPDIFF(MINUTE, NOW(), TIMESTAMP(fecha, hora)) > ?";

    $stmt = $con->prepare($sql);
    $stmt->execute([$min_diferido]);

    // =============================================
    // OBTENER VIAJES PARA EL CELULAR
    // =============================================
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
                e.id_empresa as numero_cuenta,
                e.razon_social as empresa_nombre
            FROM viajes_despacho v
            LEFT JOIN cuenta_empresa e ON v.cc = e.id
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
        'msg' => 'Error de base de datos: ' . $e->getMessage()
    ]);
}
?>