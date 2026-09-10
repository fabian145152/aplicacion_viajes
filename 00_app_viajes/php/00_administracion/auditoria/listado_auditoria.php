<?php
include_once "../../../funciones/funciones.php";
protegerPagina([0, 3]);

$usuario = nombre_usuario();
$nombre_usuario = $usuario['nombre'];
$usuario_id = $usuario['id'];

// ============================================================
// FILTROS Y PARÁMETROS
// ============================================================
$filtro_tabla = isset($_GET['tabla']) ? $_GET['tabla'] : '';
$filtro_operacion = isset($_GET['operacion']) ? $_GET['operacion'] : '';
$filtro_usuario = isset($_GET['usuario']) ? (int)$_GET['usuario'] : 0;
$filtro_fecha_desde = isset($_GET['fecha_desde']) ? $_GET['fecha_desde'] : '';
$filtro_fecha_hasta = isset($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : '';
$buscar_texto = isset($_GET['buscar']) ? $_GET['buscar'] : '';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

// ============================================================
// OBTENER LISTADO DE AUDITORÍA CON FILTROS
// ============================================================
function obtenerAuditoria($filtros = [], $limit = 50, $offset = 0)
{
    $conn = conexion();

    $sql = "SELECT a.*, u.nom_apellido as usuario_nombre 
            FROM auditoria_general a
            LEFT JOIN usuarios u ON a.usuario_id = u.id
            WHERE 1=1";

    $params = [];

    if (!empty($filtros['tabla'])) {
        $sql .= " AND a.tabla = ?";
        $params[] = $filtros['tabla'];
    }

    if (!empty($filtros['operacion'])) {
        $sql .= " AND a.operacion = ?";
        $params[] = $filtros['operacion'];
    }

    if (!empty($filtros['usuario']) && $filtros['usuario'] > 0) {
        $sql .= " AND a.usuario_id = ?";
        $params[] = $filtros['usuario'];
    }

    if (!empty($filtros['fecha_desde'])) {
        $sql .= " AND DATE(a.fecha_hora) >= ?";
        $params[] = $filtros['fecha_desde'];
    }

    if (!empty($filtros['fecha_hasta'])) {
        $sql .= " AND DATE(a.fecha_hora) <= ?";
        $params[] = $filtros['fecha_hasta'];
    }

    if (!empty($filtros['buscar'])) {
        $sql .= " AND (a.tabla LIKE ? OR a.operacion LIKE ? OR a.id_registro LIKE ? OR u.nom_apellido LIKE ?)";
        $buscar = '%' . $filtros['buscar'] . '%';
        $params[] = $buscar;
        $params[] = $buscar;
        $params[] = $buscar;
        $params[] = $buscar;
    }

    $sql .= " ORDER BY a.fecha_hora DESC LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ============================================================
// CONTAR TOTAL DE REGISTROS PARA PAGINACIÓN
// ============================================================
function contarAuditoria($filtros = [])
{
    $conn = conexion();

    $sql = "SELECT COUNT(*) as total 
            FROM auditoria_general a
            LEFT JOIN usuarios u ON a.usuario_id = u.id
            WHERE 1=1";

    $params = [];

    if (!empty($filtros['tabla'])) {
        $sql .= " AND a.tabla = ?";
        $params[] = $filtros['tabla'];
    }

    if (!empty($filtros['operacion'])) {
        $sql .= " AND a.operacion = ?";
        $params[] = $filtros['operacion'];
    }

    if (!empty($filtros['usuario']) && $filtros['usuario'] > 0) {
        $sql .= " AND a.usuario_id = ?";
        $params[] = $filtros['usuario'];
    }

    if (!empty($filtros['fecha_desde'])) {
        $sql .= " AND DATE(a.fecha_hora) >= ?";
        $params[] = $filtros['fecha_desde'];
    }

    if (!empty($filtros['fecha_hasta'])) {
        $sql .= " AND DATE(a.fecha_hora) <= ?";
        $params[] = $filtros['fecha_hasta'];
    }

    if (!empty($filtros['buscar'])) {
        $sql .= " AND (a.tabla LIKE ? OR a.operacion LIKE ? OR a.id_registro LIKE ? OR u.nom_apellido LIKE ?)";
        $buscar = '%' . $filtros['buscar'] . '%';
        $params[] = $buscar;
        $params[] = $buscar;
        $params[] = $buscar;
        $params[] = $buscar;
    }

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    return $resultado ? (int)$resultado['total'] : 0;
}

// ============================================================
// OBTENER TABLAS DISPONIBLES PARA FILTRO
// ============================================================
function obtenerTablasAuditoria()
{
    $conn = conexion();
    $stmt = $conn->query("SELECT DISTINCT tabla FROM auditoria_general ORDER BY tabla");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// ============================================================
// OBTENER USUARIOS PARA FILTRO
// ============================================================
function obtenerUsuariosAuditoria()
{
    $conn = conexion();
    $stmt = $conn->query("SELECT DISTINCT a.usuario_id, u.nom_apellido 
                          FROM auditoria_general a 
                          LEFT JOIN usuarios u ON a.usuario_id = u.id 
                          WHERE a.usuario_id IS NOT NULL AND a.usuario_id > 0
                          ORDER BY u.nom_apellido");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ============================================================
// OBTENER DATOS
// ============================================================
$filtros = [
    'tabla' => $filtro_tabla,
    'operacion' => $filtro_operacion,
    'usuario' => $filtro_usuario,
    'fecha_desde' => $filtro_fecha_desde,
    'fecha_hasta' => $filtro_fecha_hasta,
    'buscar' => $buscar_texto
];

$total_registros = contarAuditoria($filtros);
$total_paginas = ceil($total_registros / $limit);
$pagina_actual = ($offset / $limit) + 1;

$auditoria = obtenerAuditoria($filtros, $limit, $offset);
$tablas_disponibles = obtenerTablasAuditoria();
$usuarios_auditoria = obtenerUsuariosAuditoria();

// ============================================================
// MAPA DE OPERACIONES PARA MOSTRAR
// ============================================================
$operaciones_map = [
    'C' => ['label' => 'CREACIÓN', 'color' => '#28a745', 'icon' => '➕'],
    'U' => ['label' => 'ACTUALIZACIÓN', 'color' => '#007bff', 'icon' => '✏️'],
    'D' => ['label' => 'ELIMINACIÓN', 'color' => '#dc3545', 'icon' => '🗑️'],
];
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auditoría General</title>
    <link rel="stylesheet" href="../../../css/estilos.css">
    <link rel="stylesheet" href="../../../css/listado_viajes.css">

    <style>
        /* ===== CONTENEDOR PRINCIPAL ===== */
        .container {
            width: 95%;
            max-width: 1400px;
            margin: 0 auto;
            padding: 10px 0;
        }

        .card {
            padding: 15px 20px 0 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            height: calc(100vh - 120px);
            /* Ocupa toda la altura disponible */
            max-height: calc(100vh - 120px);
        }

        /* ===== TITULO ===== */
        .titulo-pagina {
            font-size: 20px;
            margin-bottom: 10px;
            color: #333;
        }

        /* ===== FILTROS ===== */
        .filtros-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 15px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
            border: 1px solid #dee2e6;
            align-items: flex-end;
        }

        .filtros-container .filtro-group {
            display: flex;
            flex-direction: column;
            flex: 1;
            min-width: 130px;
        }

        .filtros-container .filtro-group label {
            font-size: 11px;
            font-weight: 700;
            color: #495057;
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .filtros-container .filtro-group input,
        .filtros-container .filtro-group select {
            padding: 8px 12px;
            border-radius: 6px;
            border: 1px solid #ced4da;
            font-size: 13px;
            background: #fff;
            height: 38px;
            transition: all 0.2s ease;
        }

        .filtros-container .filtro-group input:focus,
        .filtros-container .filtro-group select:focus {
            border-color: #007bff;
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.15);
        }

        .filtros-container .filtro-group input:hover,
        .filtros-container .filtro-group select:hover {
            border-color: #80bdff;
        }

        /* ===== BOTONES DE ACCIÓN ===== */
        .acciones-filtros {
            display: flex;
            gap: 8px;
            align-items: flex-end;
            flex-wrap: wrap;
        }

        .btn-filtrar {
            background: linear-gradient(135deg, #007bff, #0069d9);
            color: white;
            border: none;
            padding: 8px 24px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            height: 38px;
            min-width: 100px;
            font-size: 13px;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            box-shadow: 0 2px 4px rgba(0, 123, 255, 0.2);
        }

        .btn-filtrar:hover {
            background: linear-gradient(135deg, #0069d9, #0056b3);
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 123, 255, 0.3);
        }

        .btn-filtrar:active {
            transform: translateY(0px);
            box-shadow: 0 2px 4px rgba(0, 123, 255, 0.2);
        }

        .btn-limpiar {
            background: #6c757d;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            height: 38px;
            min-width: 90px;
            font-size: 13px;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .btn-limpiar:hover {
            background: #5a6268;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(108, 117, 125, 0.3);
        }

        .btn-limpiar:active {
            transform: translateY(0px);
        }

        .btn-filtros-activos {
            background: #28a745;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: default;
            height: 38px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* ===== INDICADOR DE FILTROS ACTIVOS ===== */
        .filtros-activos {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin: 10px 0 0 0;
            padding: 10px 15px;
            background: #e9ecef;
            border-radius: 6px;
            align-items: center;
            border: 1px solid #dee2e6;
        }

        .filtros-activos .label {
            font-weight: 600;
            font-size: 12px;
            color: #495057;
            margin-right: 5px;
        }

        .filtro-tag {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 10px;
            background: #007bff;
            color: white;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
        }

        .filtro-tag .remove {
            cursor: pointer;
            font-weight: bold;
            margin-left: 3px;
            opacity: 0.8;
        }

        .filtro-tag .remove:hover {
            opacity: 1;
        }

        .filtro-tag.operacion-c {
            background: #28a745;
        }

        .filtro-tag.operacion-u {
            background: #007bff;
        }

        .filtro-tag.operacion-d {
            background: #dc3545;
        }

        .filtro-tag.tabla {
            background: #6c757d;
        }

        .filtro-tag.usuario {
            background: #17a2b8;
        }

        .filtro-tag.fecha {
            background: #fd7e14;
        }

        /* ===== ESTADÍSTICAS ===== */
        .estadisticas {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            margin-bottom: 15px;
            padding: 10px 15px;
            background: #e9ecef;
            border-radius: 6px;
        }

        .estadisticas .item {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 13px;
            color: #495057;
        }

        .estadisticas .item .numero {
            font-weight: bold;
            font-size: 16px;
            color: #007bff;
        }

        /* ===== CONTENEDOR DE LA TABLA (SCROLL) ===== */
        .tabla-scroll {
            flex: 1;
            min-height: 0;
            /* Importante para que flex funcione */
            overflow-y: auto;
            overflow-x: auto;
            border: 1px solid #dee2e6;
            border-bottom: 0 !important;
            border-radius: 8px 8px 0 0;
            background: #ffffff;
            position: relative;
            margin-bottom: 0;
        }

        /* Quitar borde inferior de la tabla */
        .tabla-scroll .table {
            border-collapse: collapse;
            width: 100%;
            margin-bottom: 0;
        }

        /* Asegurar que la tabla no tenga borde inferior */
        .tabla-scroll .table tbody tr:last-child td {
            border-bottom: none !important;
        }

        /* Quitar borde inferior del contenedor */
        .tabla-scroll {
            border-bottom: 0 !important;
        }

        /* Si hay un footer o algo después, que no tenga borde */
        .tabla-scroll+* {
            border-top: none;
        }

        /* Opcional: sombra interior para efecto de desvanecimiento */
        .tabla-scroll::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 30px;
            background: linear-gradient(to bottom, transparent, rgba(255, 255, 255, 0.9));
            pointer-events: none;
            z-index: 10;
            border-radius: 0 0 8px 8px;
        }

        .table {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
            font-size: 12px;
        }

        .table td,
        .table th {
            padding: 6px 8px;
            font-size: 12px;
            vertical-align: middle;
            text-align: left;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .table tr:hover {
            background-color: #e9ecef;
        }

        .table thead th {
            position: sticky;
            top: 0;
            background: #343a40;
            color: white;
            z-index: 100;
            font-weight: bold;
            padding: 8px 8px;
            font-size: 11px;
            text-align: left;
        }

        /* ===== COLUMNAS ===== */
        .col-id-auditoria {
            width: 60px;
            text-align: center;
        }

        .col-tabla {
            width: 12%;
            min-width: 100px;
        }

        .col-id-registro {
            width: 80px;
            text-align: center;
        }

        .col-operacion {
            width: 100px;
            text-align: center;
        }

        .col-usuario {
            width: 12%;
            min-width: 120px;
        }

        .col-fecha {
            width: 150px;
        }

        .col-datos {
            width: 30%;
            min-width: 200px;
        }

        .col-acciones-auditoria {
            width: 80px;
            text-align: center;
        }

        /* ============================================================
                BADGES DE OPERACIÓN
        ============================================================ */
        .badge-operacion {
            display: inline-block;
            padding: 3px 12px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 10px;
            color: #531f1f !important;
            text-shadow: 0 1px 1px rgba(0, 0, 0, 0.2);
        }

        .badge-operacion.creacion {
            background: #28a745;
            color: #ffffff !important;
        }

        .badge-operacion.actualizacion {
            background: #007bff;
            color: #ffffff !important;
        }

        .badge-operacion.eliminacion {
            background: #dc3545;
            color: #ffffff !important;
        }

        .badge-operacion.creacion:hover,
        .badge-operacion.actualizacion:hover,
        .badge-operacion.eliminacion:hover {
            opacity: 0.85;
        }

        .badge-tabla {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 10px;
            font-weight: bold;
            font-size: 10px;
            background: #6c757d;
            color: white;
        }

        /* ===== BOTON DETALLE ===== */
        .btn-detalle {
            background: #17a2b8;
            color: white;
            border: none;
            padding: 4px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 11px;
            transition: all 0.2s ease;
        }

        .btn-detalle:hover {
            background: #138496;
            transform: scale(1.05);
        }

        /* ===== MODAL DETALLE ===== */
        .modal-detalle {
            display: none;
            position: fixed;
            z-index: 3000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            overflow: hidden;
        }

        .modal-detalle-container {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 92%;
            max-width: 800px;
            max-height: 90vh;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.4);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .modal-detalle-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 25px;
            background: #17a2b8;
            color: white;
            flex-shrink: 0;
        }

        .modal-detalle-header h3 {
            margin: 0;
            font-size: 18px;
        }

        .modal-detalle-header .close-modal-btn {
            color: white;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            line-height: 1;
            opacity: 0.8;
            transition: opacity 0.2s;
        }

        .modal-detalle-header .close-modal-btn:hover {
            opacity: 1;
        }

        .modal-detalle-body {
            padding: 20px 25px;
            overflow-y: auto;
            flex: 1;
        }

        .modal-detalle-body .detalle-item {
            display: flex;
            padding: 6px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .modal-detalle-body .detalle-item .label {
            font-weight: bold;
            width: 120px;
            color: #495057;
            flex-shrink: 0;
        }

        .modal-detalle-body .detalle-item .valor {
            color: #212529;
            word-break: break-all;
        }

        .modal-detalle-body .detalle-json {
            background: #f8f9fa;
            padding: 10px 15px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            white-space: pre-wrap;
            word-break: break-all;
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #dee2e6;
        }

        /* ===== PAGINACIÓN ===== */
        .paginacion {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 5px;
            margin-top: 15px;
            flex-wrap: wrap;
        }

        .paginacion a,
        .paginacion span {
            display: inline-block;
            padding: 6px 12px;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            text-decoration: none;
            color: #007bff;
            font-size: 13px;
            transition: all 0.2s ease;
        }

        .paginacion a:hover {
            background: #e9ecef;
            transform: translateY(-1px);
        }

        .paginacion .activo {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }

        .paginacion .disabled {
            color: #6c757d;
            pointer-events: none;
            opacity: 0.6;
        }

        /* ===== MENU SUPERIOR ===== */
        .menu-auditoria {
            display: flex;
            gap: 6px;
            width: 100%;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }

        .menu-auditoria a {
            flex: 0 0 auto;
            text-align: center;
            padding: 5px 12px;
            background: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            font-size: 11px;
            transition: all 0.2s;
        }

        .menu-auditoria a:hover {
            background: #5a6268;
            transform: translateY(-1px);
        }

        .menu-auditoria a.btn-inicio {
            background: #343a40;
            margin-left: auto;
        }

        .menu-auditoria a.btn-inicio:hover {
            background: #23272b;
        }

        .menu-auditoria a.activo {
            background: #17a2b8;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 992px) {
            .container {
                width: 100%;
                padding: 5px;
            }

            .card {
                padding: 10px;
            }

            .filtros-container {
                flex-direction: column;
                gap: 8px;
                padding: 15px;
            }

            .filtros-container .filtro-group {
                min-width: 100%;
            }

            .acciones-filtros {
                width: 100%;
            }

            .acciones-filtros .btn-filtrar,
            .acciones-filtros .btn-limpiar {
                flex: 1;
            }

            .col-datos {
                display: none;
            }

            .col-id-auditoria {
                width: 40px;
            }

            .col-id-registro {
                width: 50px;
            }

            .col-operacion {
                width: 70px;
            }

            .col-tabla {
                width: 10%;
                min-width: 70px;
            }

            .col-usuario {
                width: 10%;
                min-width: 80px;
            }

            .col-fecha {
                width: 100px;
            }

            .table td,
            .table th {
                font-size: 10px;
                padding: 4px 5px;
            }

            .filtros-activos {
                flex-direction: column;
                align-items: flex-start;
            }
        }

        @media (max-width: 768px) {
            .col-id-auditoria {
                display: none;
            }

            .col-id-registro {
                width: 40px;
            }

            .col-fecha {
                width: 80px;
                font-size: 9px;
            }

            .col-usuario {
                width: 8%;
                min-width: 60px;
            }

            .col-tabla {
                width: 8%;
                min-width: 60px;
            }

            .col-operacion {
                width: 60px;
            }

            .modal-detalle-body .detalle-item {
                flex-direction: column;
            }

            .modal-detalle-body .detalle-item .label {
                width: 100%;
                font-size: 12px;
            }

            .modal-detalle-body .detalle-item .valor {
                font-size: 12px;
                padding-left: 10px;
            }

            .paginacion a,
            .paginacion span {
                padding: 4px 8px;
                font-size: 11px;
            }
        }

        /* ===== RESALTADO DE CAMBIOS ===== */
        .detalle-cambio {
            background: #fff3cd !important;
            color: #000 !important;
            font-weight: bold !important;
            padding: 1px 4px;
            border-radius: 3px;
        }

        .detalle-sin-cambio {
            background: transparent;
            color: #212529;
        }

        .detalle-json .valor-cambiado {
            background: #fff3cd !important;
            color: #000 !important;
            font-weight: bold !important;
            padding: 1px 4px;
            border-radius: 3px;
        }

        .detalle-json .valor-creado {
            background: #d4edda !important;
            color: #000 !important;
            padding: 1px 4px;
            border-radius: 3px;
        }

        .detalle-json .valor-eliminado {
            background: #f8d7da !important;
            color: #000 !important;
            padding: 1px 4px;
            border-radius: 3px;
        }

        .leyenda-cambios {
            margin-top: 10px;
            padding: 8px 12px;
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 4px;
            font-size: 12px;
            color: #000;
        }
    </style>
</head>

<body>

    <div class="container">
        <span><strong><?php echo $nombre_usuario ?></strong></span>

        <div class="card">

            <!-- ===== MENU ===== -->
            <div class="menu-auditoria">
                <a href="listado_auditoria.php" class="activo">📋 Auditoría</a>
                <a href="../../inicio_0.php" class="btn-inicio">🏠 Inicio</a>
            </div>

            <!-- ===== TITULO ===== -->
            <h3 class="titulo-pagina">📋 Auditoría General del Sistema</h3>

            <!-- ===== FILTROS ===== -->
            <div class="filtros-container">
                <div class="filtro-group" style="flex:2; min-width:200px;">
                    <label>🔍 Buscar</label>
                    <input type="text" id="buscar" placeholder="Buscar en auditoría..." value="<?= htmlspecialchars($buscar_texto) ?>">
                </div>

                <div class="filtro-group">
                    <label>📋 Tabla</label>
                    <select id="filtro_tabla">
                        <option value="">-- Todas --</option>
                        <?php foreach ($tablas_disponibles as $tabla): ?>
                            <option value="<?= $tabla ?>" <?= ($filtro_tabla == $tabla) ? 'selected' : '' ?>>
                                <?= ucfirst($tabla) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filtro-group">
                    <label>📌 Operación</label>
                    <select id="filtro_operacion">
                        <option value="">-- Todas --</option>
                        <option value="C" <?= ($filtro_operacion == 'C') ? 'selected' : '' ?>>➕ Creación</option>
                        <option value="U" <?= ($filtro_operacion == 'U') ? 'selected' : '' ?>>✏️ Actualización</option>
                        <option value="D" <?= ($filtro_operacion == 'D') ? 'selected' : '' ?>>🗑️ Eliminación</option>
                    </select>
                </div>

                <div class="filtro-group">
                    <label>👤 Usuario</label>
                    <select id="filtro_usuario">
                        <option value="0">-- Todos --</option>
                        <?php foreach ($usuarios_auditoria as $u): ?>
                            <option value="<?= $u['usuario_id'] ?>" <?= ($filtro_usuario == $u['usuario_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($u['nom_apellido'] ?? 'Usuario ' . $u['usuario_id']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filtro-group" style="max-width:150px;">
                    <label>📅 Desde</label>
                    <input type="date" id="fecha_desde" value="<?= $filtro_fecha_desde ?>">
                </div>

                <div class="filtro-group" style="max-width:150px;">
                    <label>📅 Hasta</label>
                    <input type="date" id="fecha_hasta" value="<?= $filtro_fecha_hasta ?>">
                </div>

                <div class="acciones-filtros">
                    <button class="btn-filtrar" onclick="aplicarFiltros()">
                        🔍 Filtrar
                    </button>
                    <button class="btn-limpiar" onclick="limpiarFiltros()">
                        🗑️ Limpiar
                    </button>
                </div>
            </div>

            <!-- ===== FILTROS ACTIVOS ===== -->
            <?php if (!empty($filtro_tabla) || !empty($filtro_operacion) || $filtro_usuario > 0 || !empty($filtro_fecha_desde) || !empty($filtro_fecha_hasta) || !empty($buscar_texto)): ?>
                <div class="filtros-activos">
                    <span class="label">📌 Filtros aplicados:</span>

                    <?php if (!empty($buscar_texto)): ?>
                        <span class="filtro-tag">
                            🔍 <?= htmlspecialchars($buscar_texto) ?>
                            <span class="remove" onclick="removerFiltro('buscar')">×</span>
                        </span>
                    <?php endif; ?>

                    <?php if (!empty($filtro_tabla)): ?>
                        <span class="filtro-tag tabla">
                            📋 <?= ucfirst($filtro_tabla) ?>
                            <span class="remove" onclick="removerFiltro('tabla')">×</span>
                        </span>
                    <?php endif; ?>

                    <?php if (!empty($filtro_operacion)): ?>
                        <?php $op_label = $operaciones_map[$filtro_operacion]['label'] ?? $filtro_operacion; ?>
                        <span class="filtro-tag operacion-<?= strtolower($filtro_operacion) ?>">
                            <?= $operaciones_map[$filtro_operacion]['icon'] ?? '' ?> <?= $op_label ?>
                            <span class="remove" onclick="removerFiltro('operacion')">×</span>
                        </span>
                    <?php endif; ?>

                    <?php if ($filtro_usuario > 0): ?>
                        <?php
                        $nombre_usuario_filtro = '';
                        foreach ($usuarios_auditoria as $u) {
                            if ($u['usuario_id'] == $filtro_usuario) {
                                $nombre_usuario_filtro = $u['nom_apellido'] ?? 'Usuario ' . $filtro_usuario;
                                break;
                            }
                        }
                        ?>
                        <span class="filtro-tag usuario">
                            👤 <?= htmlspecialchars($nombre_usuario_filtro) ?>
                            <span class="remove" onclick="removerFiltro('usuario')">×</span>
                        </span>
                    <?php endif; ?>

                    <?php if (!empty($filtro_fecha_desde)): ?>
                        <span class="filtro-tag fecha">
                            📅 Desde: <?= date('d/m/Y', strtotime($filtro_fecha_desde)) ?>
                            <span class="remove" onclick="removerFiltro('fecha_desde')">×</span>
                        </span>
                    <?php endif; ?>

                    <?php if (!empty($filtro_fecha_hasta)): ?>
                        <span class="filtro-tag fecha">
                            📅 Hasta: <?= date('d/m/Y', strtotime($filtro_fecha_hasta)) ?>
                            <span class="remove" onclick="removerFiltro('fecha_hasta')">×</span>
                        </span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- ===== ESTADÍSTICAS ===== -->
            <div class="estadisticas">
                <div class="item">
                    📊 Total registros: <span class="numero"><?= number_format($total_registros) ?></span>
                </div>
                <div class="item">
                    📄 Página <span class="numero"><?= $pagina_actual ?></span> de <span class="numero"><?= $total_paginas ?: 1 ?></span>
                </div>
                <div class="item">
                    📋 Mostrando <span class="numero"><?= count($auditoria) ?></span> registros
                </div>
            </div>

            <!-- ===== TABLA ===== -->
            <div class="tabla-scroll">
                <table class="table">
                    <thead>
                        <tr>
                            <th class="col-id-auditoria">ID</th>
                            <th class="col-fecha">Fecha/Hora</th>
                            <th class="col-usuario">Usuario</th>
                            <th class="col-tabla">Tabla</th>
                            <th class="col-id-registro">N° viaje.</th>
                            <th class="col-operacion">Operación</th>
                            <th class="col-datos">Datos (resumen)</th>
                            <th class="col-acciones-auditoria">Detalle</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($auditoria)): ?>
                            <tr>
                                <td colspan="8" style="text-align:center; padding:30px; color:#999;">
                                    No hay registros de auditoría que coincidan con los filtros
                                </td>
                            </tr>
                            <?php else: foreach ($auditoria as $row): ?>
                                <?php
                                $operacion_info = $operaciones_map[$row['operacion']] ?? ['label' => $row['operacion'], 'color' => '#6c757d', 'icon' => '📌'];
                                $clase_operacion = strtolower($operacion_info['label']);

                                // Resumir datos con mejor formato
                                $resumen = '';
                                if ($row['datos_nuevos']) {
                                    $datos = json_decode($row['datos_nuevos'], true);
                                    if ($datos) {
                                        // Mostrar solo los campos más importantes
                                        $campos_importantes = ['nombre', 'apellido', 'nombre_pasaj', 'patente', 'marca', 'modelo', 'estado', 'categoria'];
                                        $items = [];
                                        $contador = 0;
                                        foreach ($datos as $clave => $valor) {
                                            if ($contador >= 4) {
                                                $items[] = '...';
                                                break;
                                            }
                                            // Mostrar solo campos importantes o los que tienen valor
                                            if (in_array($clave, $campos_importantes) || (!empty($valor) && $valor !== 'NULL')) {
                                                $valor_mostrar = is_string($valor) ? substr($valor, 0, 25) : $valor;
                                                if (strlen($valor) > 25) $valor_mostrar .= '…';
                                                $items[] = '<span style="color:#495057; font-weight:500;">' . $clave . '</span>: ' . $valor_mostrar;
                                                $contador++;
                                            }
                                        }
                                        if (empty($items)) {
                                            // Si no hay campos importantes, mostrar los primeros 3
                                            $primeros = array_slice($datos, 0, 3);
                                            foreach ($primeros as $clave => $valor) {
                                                $valor_mostrar = is_string($valor) ? substr($valor, 0, 20) : $valor;
                                                if (strlen($valor) > 20) $valor_mostrar .= '…';
                                                $items[] = '<span style="color:#495057; font-weight:500;">' . $clave . '</span>: ' . $valor_mostrar;
                                            }
                                            if (count($datos) > 3) $items[] = '…';
                                        }
                                        $resumen = implode(' | ', $items);
                                    }
                                } elseif ($row['datos_anteriores']) {
                                    $datos = json_decode($row['datos_anteriores'], true);
                                    if ($datos) {
                                        $items = [];
                                        $contador = 0;
                                        foreach ($datos as $clave => $valor) {
                                            if ($contador >= 4) {
                                                $items[] = '…';
                                                break;
                                            }
                                            $valor_mostrar = is_string($valor) ? substr($valor, 0, 20) : $valor;
                                            if (strlen($valor) > 20) $valor_mostrar .= '…';
                                            $items[] = '<span style="color:#495057; font-weight:500;">' . $clave . '</span>: ' . $valor_mostrar;
                                            $contador++;
                                        }
                                        if (count($datos) > 4) $items[] = '…';
                                        $resumen = implode(' | ', $items);
                                    }
                                }
                                ?>
                                <tr>
                                    <td class="col-id-auditoria"><?= $row['id'] ?></td>
                                    <td class="col-fecha" style="font-size:11px;">
                                        <?= date('d/m/Y H:i:s', strtotime($row['fecha_hora'])) ?>
                                    </td>
                                    <td class="col-usuario">
                                        <?= htmlspecialchars($row['usuario_nombre'] ?? 'Sistema') ?>
                                    </td>
                                    <td class="col-tabla">
                                        <span class="badge-tabla"><?= ucfirst($row['tabla']) ?></span>
                                    </td>
                                    <td class="col-id-registro"><?= $row['id_registro'] ?></td>
                                    <td class="col-operacion">
                                        <span class="badge-operacion <?= $clase_operacion ?>">
                                            <?= $operacion_info['icon'] ?> <?= $operacion_info['label'] ?>
                                        </span>
                                    </td>
                                    <td class="col-datos" style="font-size:11px; color:#666; line-height:1.6; padding:6px 8px; max-width:250px; word-wrap:break-word;">
                                        <?= $resumen ?: '-' ?>
                                    </td>
                                    <td class="col-acciones-auditoria">
                                        <button class="btn-detalle" onclick="verDetalle(<?= $row['id'] ?>)">
                                            👁️ Ver
                                        </button>
                                    </td>
                                </tr>
                        <?php endforeach;
                        endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- ===== PAGINACIÓN ===== -->
            <?php if ($total_paginas > 1): ?>
                <div class="paginacion">
                    <?php if ($pagina_actual > 1): ?>
                        <a href="#" onclick="irPagina(1)">« Primera</a>
                        <a href="#" onclick="irPagina(<?= $pagina_actual - 1 ?>)">‹ Anterior</a>
                    <?php else: ?>
                        <span class="disabled">« Primera</span>
                        <span class="disabled">‹ Anterior</span>
                    <?php endif; ?>

                    <?php
                    $rango = 5;
                    $inicio = max(1, $pagina_actual - $rango);
                    $fin = min($total_paginas, $pagina_actual + $rango);

                    if ($inicio > 1) echo '<span>...</span>';
                    for ($i = $inicio; $i <= $fin; $i++):
                    ?>
                        <a href="#" onclick="irPagina(<?= $i ?>)" class="<?= ($i == $pagina_actual) ? 'activo' : '' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                    <?php if ($fin < $total_paginas) echo '<span>...</span>'; ?>

                    <?php if ($pagina_actual < $total_paginas): ?>
                        <a href="#" onclick="irPagina(<?= $pagina_actual + 1 ?>)">Siguiente ›</a>
                        <a href="#" onclick="irPagina(<?= $total_paginas ?>)">Última »</a>
                    <?php else: ?>
                        <span class="disabled">Siguiente ›</span>
                        <span class="disabled">Última »</span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <!-- ===== MODAL DETALLE ===== -->
    <div id="modalDetalle" class="modal-detalle">
        <div class="modal-detalle-container">
            <div class="modal-detalle-header">
                <h3>📋 Detalle de Auditoría #<span id="detalle-id"></span></h3>
                <span class="close-modal-btn" onclick="cerrarDetalle()">&times;</span>
            </div>
            <div class="modal-detalle-body" id="detalle-body">
                <div class="detalle-item">
                    <span class="label">📅 Fecha/Hora:</span>
                    <span class="valor" id="detalle-fecha"></span>
                </div>
                <div class="detalle-item">
                    <span class="label">👤 Usuario:</span>
                    <span class="valor" id="detalle-usuario"></span>
                </div>
                <div class="detalle-item">
                    <span class="label">📋 Tabla:</span>
                    <span class="valor" id="detalle-tabla"></span>
                </div>
                <div class="detalle-item">
                    <span class="label">🔢 ID Registro:</span>
                    <span class="valor" id="detalle-id-registro"></span>
                </div>
                <div class="detalle-item">
                    <span class="label">📌 Operación:</span>
                    <span class="valor" id="detalle-operacion"></span>
                </div>
                <div class="detalle-item" style="flex-direction:column; align-items:flex-start; border-bottom:2px solid #dee2e6; padding-bottom:15px;">
                    <span class="label" style="width:100%;">📄 Datos Anteriores:</span>
                    <div class="detalle-json" id="detalle-anteriores">-</div>
                </div>
                <div class="detalle-item" style="flex-direction:column; align-items:flex-start;">
                    <span class="label" style="width:100%;">📄 Datos Nuevos:</span>
                    <div class="detalle-json" id="detalle-nuevos">-</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // ============================================================
        // FUNCIONES DE FILTROS
        // ============================================================
        function aplicarFiltros() {
            const params = new URLSearchParams();

            const buscar = document.getElementById('buscar').value.trim();
            if (buscar) params.set('buscar', buscar);

            const tabla = document.getElementById('filtro_tabla').value;
            if (tabla) params.set('tabla', tabla);

            const operacion = document.getElementById('filtro_operacion').value;
            if (operacion) params.set('operacion', operacion);

            const usuario = document.getElementById('filtro_usuario').value;
            if (usuario && usuario !== '0') params.set('usuario', usuario);

            const fecha_desde = document.getElementById('fecha_desde').value;
            if (fecha_desde) params.set('fecha_desde', fecha_desde);

            const fecha_hasta = document.getElementById('fecha_hasta').value;
            if (fecha_hasta) params.set('fecha_hasta', fecha_hasta);

            const limit = 50;
            params.set('limit', limit);

            window.location.href = 'listado_auditoria.php?' + params.toString();
        }

        function limpiarFiltros() {
            window.location.href = 'listado_auditoria.php';
        }

        function removerFiltro(campo) {
            const params = new URLSearchParams(window.location.search);
            params.delete(campo);
            window.location.href = 'listado_auditoria.php?' + params.toString();
        }

        function irPagina(pagina) {
            const params = new URLSearchParams(window.location.search);
            const limit = 50;
            const offset = (pagina - 1) * limit;
            params.set('offset', offset);
            window.location.href = 'listado_auditoria.php?' + params.toString();
        }

        // ============================================================
        // FUNCIONES DE DETALLE CON RESALTADO DE CAMBIOS
        // ============================================================
        function verDetalle(id) {
            fetch('obtener_detalle_auditoria.php?id=' + id)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        alert(data.error);
                        return;
                    }

                    document.getElementById('detalle-id').textContent = data.id;
                    document.getElementById('detalle-fecha').textContent = data.fecha_hora;
                    document.getElementById('detalle-usuario').textContent = data.usuario_nombre || 'Sistema';
                    document.getElementById('detalle-tabla').textContent = data.tabla;
                    document.getElementById('detalle-id-registro').textContent = data.id_registro;

                    // Operación
                    const ops = {
                        'C': '➕ CREACIÓN',
                        'U': '✏️ ACTUALIZACIÓN',
                        'D': '🗑️ ELIMINACIÓN'
                    };
                    document.getElementById('detalle-operacion').textContent = ops[data.operacion] || data.operacion;

                    const leyenda = document.getElementById('leyenda-cambios');

                    // ============================================================
                    // CAMPOS QUE NO SE DEBEN MOSTRAR (COORDENADAS)
                    // ============================================================
                    const camposOcultos = [
                        'origen_lat', 'origen_lng',
                        'destino_lat', 'destino_lng',
                        'origen_latitud', 'origen_longitud',
                        'destino_latitud', 'destino_longitud',
                        'lat', 'lng', 'latitude', 'longitude'
                    ];

                    function filtrarCoordenadas(obj) {
                        if (!obj) return {};
                        const filtrado = {};
                        for (const clave in obj) {
                            if (!camposOcultos.includes(clave.toLowerCase())) {
                                filtrado[clave] = obj[clave];
                            }
                        }
                        return filtrado;
                    }

                    // ============================================================
                    // RESALTAR CAMBIOS (FILTRANDO COORDENADAS)
                    // ============================================================
                    if (data.datos_anteriores && data.datos_nuevos) {
                        const anteriores = data.datos_anteriores;
                        const nuevos = data.datos_nuevos;

                        // Filtrar coordenadas
                        const anterioresFiltrado = filtrarCoordenadas(anteriores);
                        const nuevosFiltrado = filtrarCoordenadas(nuevos);

                        // Mostrar datos anteriores con resaltado
                        let htmlAnteriores = '<div style="font-family: \'Courier New\', monospace; font-size: 12px; white-space: pre-wrap; word-break: break-all;">';
                        let htmlNuevos = '<div style="font-family: \'Courier New\', monospace; font-size: 12px; white-space: pre-wrap; word-break: break-all;">';

                        // Recorrer todas las claves de los datos nuevos
                        const todasLasClaves = new Set([...Object.keys(anterioresFiltrado || {}), ...Object.keys(nuevosFiltrado || {})]);

                        let hayCambios = false;

                        todasLasClaves.forEach(clave => {
                            const valorAnterior = anterioresFiltrado && anterioresFiltrado[clave] !== undefined ? anterioresFiltrado[clave] : 'NULL';
                            const valorNuevo = nuevosFiltrado && nuevosFiltrado[clave] !== undefined ? nuevosFiltrado[clave] : 'NULL';
                            const sonDiferentes = String(valorAnterior) !== String(valorNuevo);

                            if (sonDiferentes) hayCambios = true;

                            // Formatear valores
                            const valAnt = (valorAnterior !== null && valorAnterior !== 'NULL' && valorAnterior !== '') ?
                                valorAnterior :
                                '<span style="color: #999; font-style: italic;">(vacío)</span>';
                            const valNue = (valorNuevo !== null && valorNuevo !== 'NULL' && valorNuevo !== '') ?
                                valorNuevo :
                                '<span style="color: #999; font-style: italic;">(vacío)</span>';

                            // Datos anteriores
                            htmlAnteriores += '<div style="padding: 2px 0; border-bottom: 1px solid #f0f0f0;">';
                            htmlAnteriores += '<span style="color: #6c757d; font-weight: bold;">' + clave + ':</span> ';
                            if (sonDiferentes) {
                                htmlAnteriores += '<span class="valor-cambiado">' + valAnt + '</span>';
                            } else {
                                htmlAnteriores += '<span>' + valAnt + '</span>';
                            }
                            htmlAnteriores += '</div>';

                            // Datos nuevos
                            htmlNuevos += '<div style="padding: 2px 0; border-bottom: 1px solid #f0f0f0;">';
                            htmlNuevos += '<span style="color: #6c757d; font-weight: bold;">' + clave + ':</span> ';
                            if (sonDiferentes) {
                                htmlNuevos += '<span class="valor-cambiado">' + valNue + '</span>';
                            } else {
                                htmlNuevos += '<span>' + valNue + '</span>';
                            }
                            htmlNuevos += '</div>';
                        });

                        htmlAnteriores += '</div>';
                        htmlNuevos += '</div>';

                        document.getElementById('detalle-anteriores').innerHTML = htmlAnteriores;
                        document.getElementById('detalle-nuevos').innerHTML = htmlNuevos;

                        // Mostrar leyenda si hay cambios
                        if (hayCambios) {
                            leyenda.style.display = 'block';
                        } else {
                            leyenda.style.display = 'none';
                        }

                    } else if (data.datos_nuevos) {
                        // Solo datos nuevos (creación) - Filtrar coordenadas
                        const nuevos = filtrarCoordenadas(data.datos_nuevos);
                        let html = '<div style="font-family: \'Courier New\', monospace; font-size: 12px; white-space: pre-wrap; word-break: break-all;">';
                        Object.keys(nuevos).forEach(clave => {
                            const valor = nuevos[clave] !== null && nuevos[clave] !== '' ? nuevos[clave] : 'NULL';
                            html += '<div style="padding: 2px 0; border-bottom: 1px solid #f0f0f0;">';
                            html += '<span style="color: #6c757d; font-weight: bold;">' + clave + ':</span> ';
                            html += '<span class="valor-creado">' + valor + '</span>';
                            html += '</div>';
                        });
                        html += '</div>';
                        document.getElementById('detalle-anteriores').textContent = '-';
                        document.getElementById('detalle-nuevos').innerHTML = html;
                        leyenda.style.display = 'none';

                    } else if (data.datos_anteriores) {
                        // Solo datos anteriores (eliminación) - Filtrar coordenadas
                        const anteriores = filtrarCoordenadas(data.datos_anteriores);
                        let html = '<div style="font-family: \'Courier New\', monospace; font-size: 12px; white-space: pre-wrap; word-break: break-all;">';
                        Object.keys(anteriores).forEach(clave => {
                            const valor = anteriores[clave] !== null && anteriores[clave] !== '' ? anteriores[clave] : 'NULL';
                            html += '<div style="padding: 2px 0; border-bottom: 1px solid #f0f0f0;">';
                            html += '<span style="color: #6c757d; font-weight: bold;">' + clave + ':</span> ';
                            html += '<span class="valor-eliminado">' + valor + '</span>';
                            html += '</div>';
                        });
                        html += '</div>';
                        document.getElementById('detalle-anteriores').innerHTML = html;
                        document.getElementById('detalle-nuevos').textContent = '-';
                        leyenda.style.display = 'none';
                    } else {
                        document.getElementById('detalle-anteriores').textContent = '-';
                        document.getElementById('detalle-nuevos').textContent = '-';
                        leyenda.style.display = 'none';
                    }

                    document.getElementById('modalDetalle').style.display = 'block';
                    document.body.style.overflow = 'hidden';
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al cargar el detalle');
                });
        }

        function cerrarDetalle() {
            document.getElementById('modalDetalle').style.display = 'none';
            document.body.style.overflow = '';
        }

        // Cerrar modal al hacer clic fuera
        window.addEventListener('click', function(event) {
            const modal = document.getElementById('modalDetalle');
            if (event.target === modal) {
                cerrarDetalle();
            }
        });

        // Enter en el campo de búsqueda
        document.addEventListener('DOMContentLoaded', function() {
            const buscarInput = document.getElementById('buscar');
            if (buscarInput) {
                buscarInput.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') {
                        aplicarFiltros();
                    }
                });
            }
        });

        // ============================================================
        // FUNCIÓN PARA RESALTAR CAMBIOS EN LOS DATOS
        // ============================================================
        function resaltarCambios(datosAnteriores, datosNuevos) {
            // Esta función se usa en el modal de detalle
            // Los datos se resaltan en el modal
        }

        // ============================================================
        // FUNCIÓN PARA MOSTRAR DETALLE CON RESALTADO
        // ============================================================
        function verDetalle(id) {
            fetch('obtener_detalle_auditoria.php?id=' + id)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        alert(data.error);
                        return;
                    }

                    document.getElementById('detalle-id').textContent = data.id;
                    document.getElementById('detalle-fecha').textContent = data.fecha_hora;
                    document.getElementById('detalle-usuario').textContent = data.usuario_nombre || 'Sistema';
                    document.getElementById('detalle-tabla').textContent = data.tabla;
                    document.getElementById('detalle-id-registro').textContent = data.id_registro;

                    // Operación
                    const ops = {
                        'C': '➕ CREACIÓN',
                        'U': '✏️ ACTUALIZACIÓN',
                        'D': '🗑️ ELIMINACIÓN'
                    };
                    document.getElementById('detalle-operacion').textContent = ops[data.operacion] || data.operacion;

                    // ============================================================
                    // RESALTAR CAMBIOS EN DATOS ANTERIORES Y NUEVOS
                    // ============================================================
                    if (data.datos_anteriores && data.datos_nuevos) {
                        const anteriores = JSON.parse(data.datos_anteriores);
                        const nuevos = JSON.parse(data.datos_nuevos);

                        // Mostrar datos anteriores con resaltado
                        let htmlAnteriores = '<div style="font-family: \'Courier New\', monospace; font-size: 12px; white-space: pre-wrap; word-break: break-all;">';
                        let htmlNuevos = '<div style="font-family: \'Courier New\', monospace; font-size: 12px; white-space: pre-wrap; word-break: break-all;">';

                        // Recorrer todas las claves de los datos nuevos
                        const todasLasClaves = new Set([...Object.keys(anteriores || {}), ...Object.keys(nuevos || {})]);

                        todasLasClaves.forEach(clave => {
                            const valorAnterior = anteriores ? (anteriores[clave] ?? 'NULL') : 'NULL';
                            const valorNuevo = nuevos ? (nuevos[clave] ?? 'NULL') : 'NULL';
                            const sonDiferentes = String(valorAnterior) !== String(valorNuevo);

                            // Datos anteriores
                            htmlAnteriores += '<div style="padding: 2px 0; border-bottom: 1px solid #f0f0f0;">';
                            htmlAnteriores += '<span style="color: #6c757d; font-weight: bold;">' + clave + ':</span> ';
                            if (sonDiferentes) {
                                htmlAnteriores += '<span style="background: #fff3cd; color: #000; padding: 1px 4px; border-radius: 3px; font-weight: bold;">' +
                                    (valorAnterior !== null && valorAnterior !== 'NULL' ? valorAnterior : '<span style="color: #999;">(vacío)</span>') +
                                    '</span>';
                            } else {
                                htmlAnteriores += '<span>' + (valorAnterior !== null && valorAnterior !== 'NULL' ? valorAnterior : '<span style="color: #999;">(vacío)</span>') + '</span>';
                            }
                            htmlAnteriores += '</div>';

                            // Datos nuevos
                            htmlNuevos += '<div style="padding: 2px 0; border-bottom: 1px solid #f0f0f0;">';
                            htmlNuevos += '<span style="color: #6c757d; font-weight: bold;">' + clave + ':</span> ';
                            if (sonDiferentes) {
                                htmlNuevos += '<span style="background: #fff3cd; color: #000; padding: 1px 4px; border-radius: 3px; font-weight: bold;">' +
                                    (valorNuevo !== null && valorNuevo !== 'NULL' ? valorNuevo : '<span style="color: #999;">(vacío)</span>') +
                                    '</span>';
                            } else {
                                htmlNuevos += '<span>' + (valorNuevo !== null && valorNuevo !== 'NULL' ? valorNuevo : '<span style="color: #999;">(vacío)</span>') + '</span>';
                            }
                            htmlNuevos += '</div>';
                        });

                        htmlAnteriores += '</div>';
                        htmlNuevos += '</div>';

                        document.getElementById('detalle-anteriores').innerHTML = htmlAnteriores;
                        document.getElementById('detalle-nuevos').innerHTML = htmlNuevos;

                        // Agregar leyenda
                        const leyenda = document.createElement('div');
                        leyenda.style.cssText = 'margin-top: 10px; padding: 8px 12px; background: #fff3cd; border: 1px solid #ffc107; border-radius: 4px; font-size: 12px; color: #000;';
                        leyenda.innerHTML = '🟡 <strong>Campos resaltados en amarillo</strong> indican que hubo un cambio en ese valor.';
                        document.getElementById('detalle-body').appendChild(leyenda);

                    } else if (data.datos_nuevos) {
                        // Solo datos nuevos (creación)
                        const nuevos = JSON.parse(data.datos_nuevos);
                        let html = '<div style="font-family: \'Courier New\', monospace; font-size: 12px; white-space: pre-wrap; word-break: break-all;">';
                        Object.keys(nuevos).forEach(clave => {
                            const valor = nuevos[clave] ?? 'NULL';
                            html += '<div style="padding: 2px 0; border-bottom: 1px solid #f0f0f0;">';
                            html += '<span style="color: #6c757d; font-weight: bold;">' + clave + ':</span> ';
                            html += '<span style="background: #d4edda; color: #000; padding: 1px 4px; border-radius: 3px;">' +
                                (valor !== null && valor !== 'NULL' ? valor : '<span style="color: #999;">(vacío)</span>') +
                                '</span>';
                            html += '</div>';
                        });
                        html += '</div>';
                        document.getElementById('detalle-anteriores').textContent = '-';
                        document.getElementById('detalle-nuevos').innerHTML = html;

                    } else if (data.datos_anteriores) {
                        // Solo datos anteriores (eliminación)
                        const anteriores = JSON.parse(data.datos_anteriores);
                        let html = '<div style="font-family: \'Courier New\', monospace; font-size: 12px; white-space: pre-wrap; word-break: break-all;">';
                        Object.keys(anteriores).forEach(clave => {
                            const valor = anteriores[clave] ?? 'NULL';
                            html += '<div style="padding: 2px 0; border-bottom: 1px solid #f0f0f0;">';
                            html += '<span style="color: #6c757d; font-weight: bold;">' + clave + ':</span> ';
                            html += '<span style="background: #f8d7da; color: #000; padding: 1px 4px; border-radius: 3px;">' +
                                (valor !== null && valor !== 'NULL' ? valor : '<span style="color: #999;">(vacío)</span>') +
                                '</span>';
                            html += '</div>';
                        });
                        html += '</div>';
                        document.getElementById('detalle-anteriores').innerHTML = html;
                        document.getElementById('detalle-nuevos').textContent = '-';
                    } else {
                        document.getElementById('detalle-anteriores').textContent = '-';
                        document.getElementById('detalle-nuevos').textContent = '-';
                    }

                    document.getElementById('modalDetalle').style.display = 'block';
                    document.body.style.overflow = 'hidden';
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al cargar el detalle');
                });
        }
    </script>

</body>

</html>