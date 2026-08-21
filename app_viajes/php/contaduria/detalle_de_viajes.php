<?php
include_once "../../funciones/funciones.php";
protegerPagina([0, 3]);

$conn = conexion();

$viaje_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($viaje_id == 0) {
    echo "<!DOCTYPE html>
    <html lang='es'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Detalle de Viaje</title>
        <link rel='stylesheet' href='../../../css/estilos.css'>
        <style>
            body { font-family: Arial, sans-serif; background: #f4f4f4; padding: 20px; }
            .container { max-width: 900px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .error { color: #dc3545; text-align: center; padding: 40px; }
            .error h2 { font-size: 24px; }
            .btn-volver { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin-top: 20px; }
            .btn-volver:hover { background: #0056b3; }
            .buscar { margin: 20px 0; }
            .buscar input[type='number'] { padding: 10px; width: 200px; border: 1px solid #ccc; border-radius: 5px; font-size: 16px; }
            .buscar button { padding: 10px 20px; background: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
            .buscar button:hover { background: #1e7e34; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='error'>
                <h2>⚠️ ID de Viaje no especificado</h2>
                <p>Por favor, especifica el número de viaje en la URL: <strong>detalle_de_viajes.php?id=123</strong></p>
                <div class='buscar'>
                    <form method='GET' action=''>
                        <input type='number' name='id' placeholder='Ingrese N° de Viaje' required>
                        <button type='submit'>🔍 Buscar</button>
                    </form>
                </div>
                <a href='../inicio_0.php' class='btn-volver'>← Volver al Listado</a>
            </div>
        </div>
    </body>
    </html>";
    exit;
}

// 🔴 FUNCIÓN mostrarDato
function mostrarDato($valorHistorico, $valorActual, $default = 'N/A')
{
    if (!empty($valorHistorico) && $valorHistorico !== null && $valorHistorico !== '') {
        return htmlspecialchars($valorHistorico);
    }
    if (!empty($valorActual) && $valorActual !== null && $valorActual !== '') {
        return htmlspecialchars($valorActual);
    }
    return $default;
}

function formatear($valor, $default = 'N/A')
{
    return ($valor !== null && $valor !== '') ? htmlspecialchars($valor) : $default;
}

function estadoConColor($estado)
{
    $colores = [
        'Pendiente' => '#ffc107',
        'Diferido' => '#fd7e14',
        'En Curso' => '#0d6efd',
        'Asignado' => '#17a2b8',
        'Completo' => '#28a745',
        'Cancelado' => '#dc3545'
    ];
    $color = $colores[$estado] ?? '#6c757d';
    return "<span style='background: $color; color: #fff; padding: 4px 12px; border-radius: 12px; font-size: 13px; font-weight: bold;'>$estado</span>";
}

function estadoChofer($activo, $logeado)
{
    if ($activo == 1 && $logeado == 1) {
        return "<span style='background: #28a745; color: #fff; padding: 2px 10px; border-radius: 10px; font-size: 12px;'>🟢 Conectado</span>";
    } elseif ($activo == 1 && $logeado == 0) {
        return "<span style='background: #ffc107; color: #333; padding: 2px 10px; border-radius: 10px; font-size: 12px;'>🟡 Activo (sin app)</span>";
    } else {
        return "<span style='background: #dc3545; color: #fff; padding: 2px 10px; border-radius: 10px; font-size: 12px;'>🔴 Desconectado</span>";
    }
}

// 🔴 CONSULTA SQL CON TODOS LOS CAMPOS
$sql = "SELECT 
            vd.*,
            c.id AS chofer_id,
            c.nombre AS chofer_nombre,
            c.apellido AS chofer_apellido,
            c.cel AS chofer_celular,
            c.dir AS chofer_direccion,
            c.barrio AS chofer_barrio,
            c.cp AS chofer_cp,
            c.movil AS chofer_movil,
            c.user AS chofer_user,
            c.logeado AS chofer_logeado,
            c.activo AS chofer_activo,
            ve.id AS vehiculo_id,
            ve.marca AS vehiculo_marca,
            ve.modelo AS vehiculo_modelo,
            ve.patente AS vehiculo_patente,
            ve.color AS vehiculo_color,
            ve.categoria AS vehiculo_categoria,
            ve.estado AS vehiculo_estado,
            ve.tipo AS vehiculo_tipo,
            cc.nombre AS centro_costo_nombre,
            cc.direccion AS centro_costo_direccion,
            cc.contacto_centro AS centro_costo_contacto,
            cc.cel AS centro_costo_celular,
            a.nombre AS autorizante_nombre,
            a.celular AS autorizante_celular,
            a.email AS autorizante_email,
            a.horario AS autorizante_horario,
            emp.razon_social AS empresa_razon_social,
            emp.dir AS empresa_direccion,
            emp.cuit AS empresa_cuit,
            emp.inc_brutos AS empresa_inc_brutos,
            emp.contacto_1 AS empresa_contacto,
            emp.cel_1 AS empresa_celular,
            (SELECT movil FROM recorridos_viaje WHERE id_viaje = vd.id ORDER BY id DESC LIMIT 1) AS movil_historico
        FROM viajes_despacho vd
        LEFT JOIN choferes c ON c.movil = vd.asignado_a
        LEFT JOIN vehiculos ve ON ve.id_chofer = c.id
        LEFT JOIN centros_costo cc ON cc.id = vd.id_cc
        LEFT JOIN autorizantes a ON a.id = vd.id_autorizante
        LEFT JOIN cuenta_empresa emp ON emp.id = vd.cc
        WHERE vd.id = :viaje_id";

$stmt = $conn->prepare($sql);
$stmt->execute([':viaje_id' => $viaje_id]);
$viaje = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$viaje) {
    echo "<!DOCTYPE html>
    <html lang='es'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Detalle de Viaje</title>
        <link rel='stylesheet' href='../../../css/estilos.css'>
        <style>
            body { font-family: Arial, sans-serif; background: #f4f4f4; padding: 20px; }
            .container { max-width: 900px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .error { color: #dc3545; text-align: center; padding: 40px; }
            .error h2 { font-size: 24px; }
            .btn-volver { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin-top: 20px; }
            .btn-volver:hover { background: #0056b3; }
            .buscar { margin: 20px 0; }
            .buscar input[type='number'] { padding: 10px; width: 200px; border: 1px solid #ccc; border-radius: 5px; font-size: 16px; }
            .buscar button { padding: 10px 20px; background: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
            .buscar button:hover { background: #1e7e34; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='error'>
                <h2>❌ Viaje no encontrado</h2>
                <p>No se encontró ningún viaje con el ID <strong>#$viaje_id</strong></p>
                <div class='buscar'>
                    <form method='GET' action=''>
                        <input type='number' name='id' placeholder='Ingrese N° de Viaje' required>
                        <button type='submit'>🔍 Buscar</button>
                    </form>
                </div>
                <a href='../inicio_0.php' class='btn-volver'>← Volver al Listado</a>
            </div>
        </div>
    </body>
    </html>";
    exit;
}

// 🔴 Si el viaje está completado y tenemos movil_historico, buscar los datos completos del chofer
if ($viaje['estado'] == 'Completo' && !empty($viaje['movil_historico'])) {
    // Buscar el chofer por el móvil histórico
    $sqlChoferHistorico = "SELECT 
                                id, nombre, apellido, cel, dir, barrio, cp, movil, user, logeado, activo
                            FROM choferes 
                            WHERE movil = :movil_historico";
    $stmtChoferHistorico = $conn->prepare($sqlChoferHistorico);
    $stmtChoferHistorico->execute([':movil_historico' => $viaje['movil_historico']]);
    $choferHistorico = $stmtChoferHistorico->fetch(PDO::FETCH_ASSOC);

    if ($choferHistorico) {
        // Sobrescribir los datos del chofer con los históricos
        $viaje['chofer_nombre'] = $choferHistorico['nombre'];
        $viaje['chofer_apellido'] = $choferHistorico['apellido'];
        $viaje['chofer_celular'] = $choferHistorico['cel'];
        $viaje['chofer_direccion'] = $choferHistorico['dir'];
        $viaje['chofer_barrio'] = $choferHistorico['barrio'];
        $viaje['chofer_cp'] = $choferHistorico['cp'];
        $viaje['chofer_movil'] = $choferHistorico['movil'];
        $viaje['chofer_user'] = $choferHistorico['user'];
        $viaje['chofer_logeado'] = $choferHistorico['logeado'];
        $viaje['chofer_activo'] = $choferHistorico['activo'];
        $viaje['chofer_id'] = $choferHistorico['id'];

        // 🔴 Buscar el vehículo asociado a este chofer
        $sqlVehiculoHistorico = "SELECT 
                                    id, marca, modelo, patente, color, categoria, estado, tipo
                                FROM vehiculos 
                                WHERE id_chofer = :chofer_id";
        $stmtVehiculoHistorico = $conn->prepare($sqlVehiculoHistorico);
        $stmtVehiculoHistorico->execute([':chofer_id' => $choferHistorico['id']]);
        $vehiculoHistorico = $stmtVehiculoHistorico->fetch(PDO::FETCH_ASSOC);

        if ($vehiculoHistorico) {
            // Sobrescribir los datos del vehículo con los históricos
            $viaje['vehiculo_marca'] = $vehiculoHistorico['marca'];
            $viaje['vehiculo_modelo'] = $vehiculoHistorico['modelo'];
            $viaje['vehiculo_patente'] = $vehiculoHistorico['patente'];
            $viaje['vehiculo_color'] = $vehiculoHistorico['color'];
            $viaje['vehiculo_categoria'] = $vehiculoHistorico['categoria'];
            $viaje['vehiculo_estado'] = $vehiculoHistorico['estado'];
            $viaje['vehiculo_tipo'] = $vehiculoHistorico['tipo'];
            $viaje['vehiculo_id'] = $vehiculoHistorico['id'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle del Viaje #<?= $viaje_id ?></title>
    <link rel="stylesheet" href="../../../css/estilos.css">
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f0f2f5;
            padding: 20px;
            margin: 0;
        }

        .container {
            max-width: 1100px;
            margin: 0 auto;
            background: #fff;
            padding: 30px 35px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #007bff;
            padding-bottom: 15px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #333;
        }

        .header .estado {
            font-size: 16px;
        }

        .btn-volver {
            display: inline-block;
            padding: 8px 16px;
            background: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 14px;
        }

        .btn-volver:hover {
            background: #5a6268;
        }

        .btn-buscar {
            display: inline-block;
            padding: 8px 16px;
            background: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 14px;
            margin-left: 10px;
        }

        .btn-buscar:hover {
            background: #1e7e34;
        }

        .btn-imprimir {
            display: inline-block;
            padding: 8px 16px;
            background: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 14px;
            margin-left: 10px;
        }

        .btn-imprimir:hover {
            background: #5a6268;
        }

        .seccion {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px 20px;
            margin-bottom: 20px;
            border-left: 4px solid #007bff;
        }

        .seccion h3 {
            margin: 0 0 12px 0;
            font-size: 16px;
            color: #007bff;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .seccion .icono {
            font-size: 20px;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px 30px;
        }

        .grid-3 {
            grid-template-columns: 1fr 1fr 1fr;
        }

        .grid-item {
            display: flex;
            padding: 4px 0;
            font-size: 14px;
        }

        .grid-item .label {
            font-weight: 600;
            color: #555;
            min-width: 130px;
            flex-shrink: 0;
        }

        .grid-item .value {
            color: #222;
            word-break: break-word;
        }

        .grid-item .value .badge-vehiculo {
            display: inline-block;
            background: #e9ecef;
            padding: 2px 10px;
            border-radius: 10px;
            font-size: 12px;
            margin: 1px 0;
        }

        .separador {
            border-top: 1px dashed #dee2e6;
            margin: 18px 0;
        }

        .row-acciones {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .btn-accion {
            padding: 10px 25px;
            border: none;
            border-radius: 5px;
            font-weight: 600;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
        }

        .btn-accion.editar {
            background: #ffc107;
            color: #333;
        }

        .btn-accion.editar:hover {
            background: #e0a800;
        }

        .btn-accion.imprimir {
            background: #6c757d;
            color: #fff;
        }

        .btn-accion.imprimir:hover {
            background: #5a6268;
        }

        .btn-accion.volver {
            background: #007bff;
            color: #fff;
        }

        .btn-accion.volver:hover {
            background: #0056b3;
        }

        .buscar-container {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .buscar-container input[type="number"] {
            padding: 8px 12px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
            width: 180px;
        }

        .buscar-container button {
            padding: 8px 20px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }

        .buscar-container button:hover {
            background: #1e7e34;
        }

        .seccion-viaje {
            border-left-color: #007bff;
        }

        .seccion-pasajero {
            border-left-color: #28a745;
        }

        .seccion-chofer {
            border-left-color: #17a2b8;
        }

        .seccion-vehiculo {
            border-left-color: #fd7e14;
        }

        .seccion-empresa {
            border-left-color: #6f42c1;
        }

        .seccion-autorizante {
            border-left-color: #e83e8c;
        }

        .seccion-finalizacion {
            border-left-color: #28a745;
            background: #f0fff0;
        }

        .seccion-coordenadas {
            border-left-color: #6c757d;
            background: #f8f9fa;
        }

        .historico-badge {
            display: inline-block;
            background: #17a2b8;
            color: #fff;
            padding: 1px 8px;
            border-radius: 10px;
            font-size: 10px;
            margin-left: 5px;
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }

            .grid {
                grid-template-columns: 1fr;
            }

            .grid-3 {
                grid-template-columns: 1fr;
            }

            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .grid-item .label {
                min-width: 100px;
            }
        }

        @media print {
            .no-print {
                display: none !important;
            }

            body {
                background: #fff !important;
                padding: 3px !important;
                margin: 0 !important;
                font-size: 8px !important;
                line-height: 1.1 !important;
            }

            .container {
                max-width: 100% !important;
                padding: 3px !important;
                margin: 0 !important;
                box-shadow: none !important;
                border-radius: 0 !important;
            }

            .header {
                padding: 2px 0 3px 0 !important;
                margin-bottom: 3px !important;
                border-bottom: 1px solid #333 !important;
            }

            .header h1 {
                font-size: 11px !important;
                margin: 0 !important;
            }

            .header .estado {
                font-size: 9px !important;
            }

            .header .estado span {
                padding: 1px 4px !important;
                font-size: 8px !important;
            }

            .seccion {
                break-inside: avoid !important;
                page-break-inside: avoid !important;
                margin-bottom: 2px !important;
                padding: 2px 4px !important;
                border-left-width: 2px !important;
                border-left-color: #333 !important;
                background: #fafafa !important;
            }

            .seccion h3 {
                font-size: 9px !important;
                margin: 0 0 1px 0 !important;
                padding: 0 !important;
            }

            .seccion h3 .icono {
                font-size: 10px !important;
            }

            .historico-badge {
                font-size: 6px !important;
                padding: 0 2px !important;
                background: #666 !important;
                color: #fff !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .badge-vehiculo {
                font-size: 7px !important;
                padding: 0 3px !important;
                background: #e9ecef !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .grid {
                display: grid !important;
                grid-template-columns: 1fr 1fr !important;
                gap: 0 6px !important;
            }

            .grid-3 {
                grid-template-columns: 1fr 1fr 1fr !important;
                gap: 0 4px !important;
            }

            .grid-item {
                display: flex !important;
                padding: 0 !important;
                font-size: 7.5px !important;
                line-height: 1.1 !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }

            .grid-item .label {
                min-width: 50px !important;
                font-size: 7.5px !important;
                font-weight: 600 !important;
                color: #333 !important;
                flex-shrink: 0 !important;
            }

            .grid-item .value {
                font-size: 7.5px !important;
                word-break: break-word !important;
            }

            .grid-item .value strong {
                font-size: 7.5px !important;
                font-weight: 700 !important;
            }

            .buscar-container {
                display: none !important;
            }

            .row-acciones {
                display: none !important;
            }

            .btn-volver,
            .btn-buscar,
            .btn-imprimir {
                display: none !important;
            }

            .seccion-finalizacion {
                background: #f0f8f0 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .seccion-coordenadas {
                background: #f8f9fa !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .seccion-viaje {
                border-left-color: #333 !important;
            }

            .seccion-pasajero {
                border-left-color: #333 !important;
            }

            .seccion-chofer {
                border-left-color: #333 !important;
            }

            .seccion-vehiculo {
                border-left-color: #333 !important;
            }

            .seccion-empresa {
                border-left-color: #333 !important;
            }

            .seccion-autorizante {
                border-left-color: #333 !important;
            }

            .seccion-finalizacion {
                border-left-color: #333 !important;
            }

            .seccion-coordenadas {
                border-left-color: #333 !important;
            }

            .estado span {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                font-size: 7px !important;
                padding: 0 3px !important;
            }

            @page {
                margin: 3mm 3mm !important;
                size: A4 portrait !important;
            }

            .container>*:last-child {
                margin-bottom: 0 !important;
            }

            .separador {
                display: none !important;
            }
        }
    </style>
</head>

<body>

    <div class="container">

        <div class="header">
            <div>
                <h1>🧾 Detalle del Viaje #<?= $viaje_id ?></h1>
                <span class="estado">Estado: <?= estadoConColor($viaje['estado']) ?></span>
            </div>
            <div class="no-print">
                <a href="../inicio_0.php" class="btn-volver">← Volver</a>
                <a href="detalle_de_viajes.php?id=<?= $viaje_id - 1 > 0 ? $viaje_id - 1 : 1 ?>" class="btn-buscar">◀ Anterior</a>
                <a href="detalle_de_viajes.php?id=<?= $viaje_id + 1 ?>" class="btn-buscar">▶ Siguiente</a>
                <button class="btn-imprimir" onclick="window.print()">🖨️ Imprimir</button>
            </div>
        </div>

        <div class="buscar-container no-print">
            <form method="GET" action="" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                <label for="buscar_id" style="font-weight: 600;">🔍 Buscar Viaje N°:</label>
                <input type="number" id="buscar_id" name="id" value="<?= $viaje_id ?>" placeholder="Ingrese N° de Viaje">
                <button type="submit">Buscar</button>
            </form>
        </div>

        <!-- ===== DATOS DEL VIAJE ===== -->
        <div class="seccion seccion-viaje">
            <h3><span class="icono">📋</span> Datos del Viaje</h3>
            <div class="grid">
                <div class="grid-item"><span class="label">ID Viaje:</span><span class="value">#<?= $viaje['id'] ?></span></div>
                <div class="grid-item"><span class="label">Estado:</span><span class="value"><?= estadoConColor($viaje['estado']) ?></span></div>
                <div class="grid-item"><span class="label">Categoría:</span><span class="value"><?= formatear($viaje['categoria_movil']) ?></span></div>
                <div class="grid-item"><span class="label">Fecha:</span><span class="value"><?= formatear($viaje['fecha']) ?></span></div>
                <div class="grid-item"><span class="label">Hora:</span><span class="value"><?= formatear($viaje['hora']) ?></span></div>
                <div class="grid-item"><span class="label">Diferido:</span><span class="value"><?= $viaje['diferido'] == 'Si' ? '✅ Sí' : '❌ No' ?></span></div>
                <div class="grid-item">
                    <span class="label">Fecha Asignación:</span>
                    <span class="value">
                        <?php
                        if ($viaje['estado'] == 'Completo' && !empty($viaje['fecha_finalizacion'])) {
                            echo formatear($viaje['fecha_finalizacion']);
                        } else {
                            echo formatear($viaje['fecha_asignacion']);
                        }
                        ?>
                    </span>
                </div>
                <div class="grid-item">
                    <span class="label">Móvil Asignado:</span>
                    <span class="value">
                        <strong>
                            <?php
                            if ($viaje['estado'] == 'Completo' && !empty($viaje['movil_historico'])) {
                                echo formatear($viaje['movil_historico']);
                            } else {
                                echo formatear($viaje['asignado_a']);
                            }
                            ?>
                        </strong>
                    </span>
                </div>
            </div>
        </div>

        <!-- ===== PASAJERO ===== -->
        <div class="seccion seccion-pasajero">
            <h3><span class="icono">👤</span> Datos del Pasajero</h3>
            <div class="grid">
                <div class="grid-item"><span class="label">Nombre:</span><span class="value"><strong><?= formatear($viaje['nombre_pasaj']) ?></strong></span></div>
                <div class="grid-item"><span class="label">Celular:</span><span class="value"><?= formatear($viaje['cel_pasaj']) ?></span></div>
                <div class="grid-item"><span class="label">📍 Origen:</span><span class="value"><?= formatear($viaje['direccion_origen']) ?></span></div>
                <div class="grid-item"><span class="label">📍 Destino:</span><span class="value"><?= formatear($viaje['direccion_destino']) ?></span></div>
                <div class="grid-item"><span class="label">Obs. Chofer:</span><span class="value larga"><?= formatear($viaje['obs_pasaj']) ?></span></div>
                <div class="grid-item"><span class="label">Obs. Operador:</span><span class="value larga"><?= formatear($viaje['obs_operador']) ?></span></div>
            </div>
        </div>

        <!-- ===== CHOFER (HISTÓRICO) ===== -->
        <div class="seccion seccion-chofer">
            <h3><span class="icono">🧑‍✈️</span> Datos del Chofer <span class="historico-badge">📌 Histórico</span></h3>
            <div class="grid">
                <div class="grid-item">
                    <span class="label">Nombre:</span>
                    <span class="value"><strong>
                            <?php
                            if ($viaje['estado'] == 'Completo' && !empty($viaje['movil_historico'])) {
                                echo formatear($viaje['chofer_nombre'] ?? '') . ' ' . formatear($viaje['chofer_apellido'] ?? '');
                            } else {
                                echo mostrarDato($viaje['chofer_nombre_hist'] ?? '', $viaje['chofer_nombre'] ?? '') . ' ' .
                                    mostrarDato($viaje['chofer_apellido_hist'] ?? '', $viaje['chofer_apellido'] ?? '');
                            }
                            ?>
                        </strong></span>
                </div>
                <div class="grid-item">
                    <span class="label">Estado:</span>
                    <span class="value"><?= estadoChofer($viaje['chofer_activo'] ?? 0, $viaje['chofer_logeado'] ?? 0) ?></span>
                </div>
                <div class="grid-item">
                    <span class="label">Móvil:</span>
                    <span class="value"><strong>
                            <?php
                            if ($viaje['estado'] == 'Completo' && !empty($viaje['movil_historico'])) {
                                echo formatear($viaje['movil_historico']);
                            } else {
                                echo formatear($viaje['asignado_a']);
                            }
                            ?>
                        </strong></span>
                </div>
                <div class="grid-item">
                    <span class="label">Celular:</span>
                    <span class="value">
                        <?php
                        if ($viaje['estado'] == 'Completo' && !empty($viaje['movil_historico'])) {
                            echo formatear($viaje['chofer_celular'] ?? '');
                        } else {
                            echo mostrarDato($viaje['chofer_celular_hist'] ?? '', $viaje['chofer_celular'] ?? '');
                        }
                        ?>
                    </span>
                </div>
                <div class="grid-item">
                    <span class="label">Dirección:</span>
                    <span class="value">
                        <?php
                        if ($viaje['estado'] == 'Completo' && !empty($viaje['movil_historico'])) {
                            echo formatear($viaje['chofer_direccion'] ?? '');
                        } else {
                            echo formatear($viaje['chofer_direccion'] ?? '');
                        }
                        ?>
                    </span>
                </div>
                <div class="grid-item">
                    <span class="label">Barrio:</span>
                    <span class="value">
                        <?php
                        if ($viaje['estado'] == 'Completo' && !empty($viaje['movil_historico'])) {
                            echo formatear($viaje['chofer_barrio'] ?? '');
                        } else {
                            echo formatear($viaje['chofer_barrio'] ?? '');
                        }
                        ?>
                    </span>
                </div>
                <div class="grid-item">
                    <span class="label">Código Postal:</span>
                    <span class="value">
                        <?php
                        if ($viaje['estado'] == 'Completo' && !empty($viaje['movil_historico'])) {
                            echo formatear($viaje['chofer_cp'] ?? '');
                        } else {
                            echo formatear($viaje['chofer_cp'] ?? '');
                        }
                        ?>
                    </span>
                </div>
                <div class="grid-item">
                    <span class="label">Usuario:</span>
                    <span class="value">
                        <?php
                        if ($viaje['estado'] == 'Completo' && !empty($viaje['movil_historico'])) {
                            echo formatear($viaje['chofer_user'] ?? '');
                        } else {
                            echo formatear($viaje['chofer_user'] ?? '');
                        }
                        ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- ===== VEHÍCULO / UNIDAD (HISTÓRICO) ===== -->
        <div class="seccion seccion-vehiculo">
            <h3><span class="icono">🚗</span> Datos de la Unidad <span class="historico-badge">📌 Histórico</span></h3>
            <div class="grid">
                <div class="grid-item">
                    <span class="label">Marca:</span>
                    <span class="value"><strong>
                            <?php
                            if ($viaje['estado'] == 'Completo' && !empty($viaje['movil_historico'])) {
                                echo formatear($viaje['vehiculo_marca'] ?? '');
                            } else {
                                echo mostrarDato($viaje['vehiculo_marca_hist'] ?? '', $viaje['vehiculo_marca'] ?? '');
                            }
                            ?>
                        </strong></span>
                </div>
                <div class="grid-item">
                    <span class="label">Modelo:</span>
                    <span class="value"><strong>
                            <?php
                            if ($viaje['estado'] == 'Completo' && !empty($viaje['movil_historico'])) {
                                echo formatear($viaje['vehiculo_modelo'] ?? '');
                            } else {
                                echo mostrarDato($viaje['vehiculo_modelo_hist'] ?? '', $viaje['vehiculo_modelo'] ?? '');
                            }
                            ?>
                        </strong></span>
                </div>
                <div class="grid-item">
                    <span class="label">Patente:</span>
                    <span class="value"><span class="badge-vehiculo">
                            <?php
                            if ($viaje['estado'] == 'Completo' && !empty($viaje['movil_historico'])) {
                                echo formatear($viaje['vehiculo_patente'] ?? '');
                            } else {
                                echo mostrarDato($viaje['vehiculo_patente_hist'] ?? '', $viaje['vehiculo_patente'] ?? '');
                            }
                            ?>
                        </span></span>
                </div>
                <div class="grid-item">
                    <span class="label">Color:</span>
                    <span class="value">
                        <?php
                        if ($viaje['estado'] == 'Completo' && !empty($viaje['movil_historico'])) {
                            echo formatear($viaje['vehiculo_color'] ?? '');
                        } else {
                            echo mostrarDato($viaje['vehiculo_color_hist'] ?? '', $viaje['vehiculo_color'] ?? '');
                        }
                        ?>
                    </span>
                </div>
                <div class="grid-item">
                    <span class="label">Categoría:</span>
                    <span class="value">
                        <?php
                        if ($viaje['estado'] == 'Completo' && !empty($viaje['movil_historico'])) {
                            echo formatear($viaje['vehiculo_categoria'] ?? '');
                        } else {
                            echo formatear($viaje['vehiculo_categoria'] ?? '');
                        }
                        ?>
                    </span>
                </div>
                <div class="grid-item">
                    <span class="label">Tipo:</span>
                    <span class="value">
                        <?php
                        if ($viaje['estado'] == 'Completo' && !empty($viaje['movil_historico'])) {
                            echo formatear($viaje['vehiculo_tipo'] ?? '');
                        } else {
                            echo formatear($viaje['vehiculo_tipo'] ?? '');
                        }
                        ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- ===== EMPRESA ===== -->
        <div class="seccion seccion-empresa">
            <h3><span class="icono">🏢</span> Datos de la Empresa</h3>
            <div class="grid">
                <div class="grid-item"><span class="label">Empresa:</span><span class="value"><strong><?= formatear($viaje['empresa_razon_social'] ?? '') ?></strong></span></div>
                <div class="grid-item"><span class="label">Dirección:</span><span class="value"><?= formatear($viaje['empresa_direccion'] ?? '') ?></span></div>
                <div class="grid-item"><span class="label">CUIT:</span><span class="value"><?= formatear($viaje['empresa_cuit'] ?? '') ?></span></div>
                <div class="grid-item"><span class="label">Ingresos Brutos:</span><span class="value"><?= formatear($viaje['empresa_inc_brutos'] ?? '') ?></span></div>
                <div class="grid-item"><span class="label">Contacto:</span><span class="value"><?= formatear($viaje['empresa_contacto'] ?? '') ?></span></div>
                <div class="grid-item"><span class="label">Celular:</span><span class="value"><?= formatear($viaje['empresa_celular'] ?? '') ?></span></div>
                <div class="grid-item"><span class="label">Centro de Costo:</span><span class="value"><?= formatear($viaje['centro_costo_nombre'] ?? '') ?></span></div>
                <div class="grid-item"><span class="label">Dirección CC:</span><span class="value"><?= formatear($viaje['centro_costo_direccion'] ?? '') ?></span></div>
                <div class="grid-item"><span class="label">Contacto CC:</span><span class="value"><?= formatear($viaje['centro_costo_contacto'] ?? '') ?></span></div>
                <div class="grid-item"><span class="label">Celular CC:</span><span class="value"><?= formatear($viaje['centro_costo_celular'] ?? '') ?></span></div>
            </div>
        </div>

        <!-- ===== AUTORIZANTE ===== -->
        <div class="seccion seccion-autorizante">
            <h3><span class="icono">✍️</span> Datos del Autorizante</h3>
            <div class="grid">
                <div class="grid-item"><span class="label">Nombre:</span><span class="value"><strong><?= formatear($viaje['autorizante_nombre'] ?? '') ?></strong></span></div>
                <div class="grid-item"><span class="label">Celular:</span><span class="value"><?= formatear($viaje['autorizante_celular'] ?? '') ?></span></div>
                <div class="grid-item"><span class="label">Email:</span><span class="value"><?= formatear($viaje['autorizante_email'] ?? '') ?></span></div>
                <div class="grid-item"><span class="label">Horario:</span><span class="value"><?= formatear($viaje['autorizante_horario'] ?? '') ?></span></div>
            </div>
        </div>

        <!-- ===== FINALIZACIÓN ===== -->
        <?php if ($viaje['estado'] == 'Completo'): ?>
            <div class="seccion seccion-finalizacion">
                <h3><span class="icono">✅</span> Datos de Finalización</h3>
                <div class="grid">
                    <div class="grid-item"><span class="label">Kilómetros:</span><span class="value"><strong><?= formatear($viaje['km_recorridos'] ?? '') ?> km</strong></span></div>
                    <div class="grid-item"><span class="label">Peajes:</span><span class="value">$ <?= formatear($viaje['peajes'] ?? '') ?></span></div>
                    <div class="grid-item"><span class="label">Tiempo de espera:</span><span class="value"><?= formatear($viaje['tiempo_espera'] ?? '') ?> min</span></div>
                    <div class="grid-item"><span class="label">Fecha finalización:</span><span class="value"><?= formatear($viaje['fecha_finalizacion'] ?? '') ?></span></div>
                    <div class="grid-item" style="grid-column: 1 / -1;"><span class="label">Observaciones:</span><span class="value larga"><?= formatear($viaje['observaciones_finalizacion'] ?? '') ?></span></div>
                </div>
            </div>
        <?php endif; ?>

        <!-- ===== DIRECCIONES ===== -->
        <div class="seccion seccion-coordenadas">
            <h3><span class="icono">📍</span> Direcciones del Viaje</h3>
            <div class="grid">
                <div class="grid-item">
                    <span class="label">Origen:</span>
                    <span class="value"><strong><?= formatear($viaje['direccion_origen']) ?></strong></span>
                </div>
                <div class="grid-item">
                    <span class="label">Destino:</span>
                    <span class="value"><strong><?= formatear($viaje['direccion_destino']) ?></strong></span>
                </div>
                <div class="grid-item" style="grid-column: 1 / -1; margin-top: 3px; padding-top: 3px; border-top: 1px dashed #dee2e6;">
                    <span class="label" style="font-size: 10px; color: #6c757d;">Coordenadas:</span>
                    <span class="value" style="font-size: 10px; color: #6c757d;">
                        Origen: <?= formatear($viaje['origen_lat']) ?>, <?= formatear($viaje['origen_lng']) ?> |
                        Destino: <?= formatear($viaje['destino_lat']) ?>, <?= formatear($viaje['destino_lng']) ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- ===== BOTONES ===== -->
        <div class="row-acciones no-print">
            <!-- <a href="lista_viajes_editar.php?id=<?= $viaje_id ?>" class="btn-accion editar" target="_blank">✏️ Editar Viaje</a> -->
            <a href="../inicio_0.php" class="btn-accion volver">📋 Volver al Listado</a>
            <button class="btn-accion imprimir" onclick="window.print()">🖨️ Imprimir</button>
        </div>

    </div>

</body>

</html>