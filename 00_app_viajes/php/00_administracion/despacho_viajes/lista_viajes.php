<?php
include_once "../../../funciones/funciones.php";

// ===== CARGAR CONFIGURACIÓN DE TIEMPOS (SOLO LECTURA, NO MOSTRAR) =====
$config_file = __DIR__ . '/../seteos/min_diferido_config.php';
if (file_exists($config_file)) {
    include_once $config_file;
}

// Definir valores por defecto si no existen (SOLO PARA USO INTERNO)
if (!defined('MIN_DIFERIDO')) {
    define('MIN_DIFERIDO', 60);
}
if (!defined('TIEMPO_AIR')) {
    define('TIEMPO_AIR', 30);
}

protegerPagina([0, 3]);

// ===== EJECUTAR ACTUALIZACIÓN DE VIAJES DIFERIDOS (SIN MOSTRAR NADA) =====
// La función ya está definida en funciones.php
$viajes_actualizados = actualizarDiferidosAPendientes();

$conn = conexion();

$usuario = nombre_usuario();

$nombre_usuario = $usuario['nombre'];
$usuario_id = $usuario['id'];
// ... resto del código


// --- 1. RECIBIR EL FILTRO DESDE LA URL ---
$filtro_estado = isset($_GET['filtro']) ? $_GET['filtro'] : 'pendiente';

// --- 2. ACCIONES DE ASIGNAR / DESASIGNAR / CANCELAR ---
if (isset($_POST['asignar_movil'])) {
    $viaje_id = $_POST['viaje_id'];
    $movil_id = $_POST['movil_id'];
    $filtro_actual = $_POST['filtro_actual'] ?? 'pendiente';

    // Obtener datos antes de asignar
    $datos_anteriores = obtenerRegistroParaAuditoria('viajes_despacho', $viaje_id);

    $conn = conexion();
    $stmt = $conn->prepare("UPDATE viajes_despacho SET asignado_a = ?, estado = 'En Curso', fecha_asignacion = NOW() WHERE id = ?");
    $resultado = $stmt->execute([$movil_id, $viaje_id]);

    if ($resultado && $datos_anteriores) {
        $datos_nuevos = $datos_anteriores;
        $datos_nuevos['asignado_a'] = $movil_id;
        $datos_nuevos['estado'] = 'En Curso';
        $datos_nuevos['fecha_asignacion'] = date('Y-m-d H:i:s');
        registrarAuditoria('viajes_despacho', $viaje_id, 'U', $datos_anteriores, $datos_nuevos);
    }

    header("Location: lista_viajes.php?filtro=" . $filtro_actual);
    exit;
}

if (isset($_GET['desasignar'])) {
    $viaje_id = (int)$_GET['desasignar'];
    $filtro_actual = $_GET['filtro'] ?? 'pendiente';

    // Obtener datos antes de desasignar
    $datos_anteriores = obtenerRegistroParaAuditoria('viajes_despacho', $viaje_id);

    $conn = conexion();
    $stmt = $conn->prepare("UPDATE viajes_despacho
                            SET asignado_a = NULL,
                                fecha_asignacion = NULL,
                                estado = 'Pendiente'
                            WHERE id = ?");
    $resultado = $stmt->execute([$viaje_id]);

    if ($resultado && $datos_anteriores) {
        $datos_nuevos = $datos_anteriores;
        $datos_nuevos['asignado_a'] = null;
        $datos_nuevos['fecha_asignacion'] = null;
        $datos_nuevos['estado'] = 'Pendiente';
        registrarAuditoria('viajes_despacho', $viaje_id, 'U', $datos_anteriores, $datos_nuevos);
    }

    header("Location: lista_viajes.php?filtro=" . $filtro_actual);
    exit;
}

if (isset($_GET['cancelar'])) {
    $viaje_id = (int)$_GET['cancelar'];
    $filtro_actual = $_GET['filtro'] ?? 'pendiente';

    // Obtener datos antes de cancelar
    $datos_anteriores = obtenerRegistroParaAuditoria('viajes_despacho', $viaje_id);

    $conn = conexion();
    $stmt = $conn->prepare("UPDATE viajes_despacho SET estado = 'Cancelado' WHERE id = ?");
    $resultado = $stmt->execute([$viaje_id]);

    if ($resultado && $datos_anteriores) {
        $datos_nuevos = $datos_anteriores;
        $datos_nuevos['estado'] = 'Cancelado';
        registrarAuditoria('viajes_despacho', $viaje_id, 'U', $datos_anteriores, $datos_nuevos);
    }

    header("Location: lista_viajes.php?filtro=" . $filtro_actual);
    exit;
}

// 🔴 Función para obtener viajes filtrados
function obtenerViajesFiltrados($filtro = 'pendiente')
{
    $conn = conexion();

    $sql = "SELECT * FROM viajes_despacho WHERE 1=1";

    switch ($filtro) {
        case 'pendiente':
            $sql .= " AND estado = 'Pendiente'";
            break;
        case 'diferidos':
            $sql .= " AND estado = 'Diferido'";
            break;
        // 🔴 ELIMINADO: case 'asignado':
        case 'en_curso':
            $sql .= " AND estado = 'En Curso'";
            break;
        case 'completos':
            $sql .= " AND estado = 'Completo'";
            break;
        case 'cancelados':
            $sql .= " AND estado = 'Cancelado'";
            break;
        case 'todos':
        default:
            // 🔴 EXCLUIR completos y cancelados de "Todos"
            $sql .= " AND estado NOT IN ('Completo', 'Cancelado')";
            break;
    }

    $sql .= " ORDER BY fecha DESC, hora DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// --- 3. OBTENER VIAJES SEGÚN EL FILTRO ---
$viajes = obtenerViajesFiltrados($filtro_estado);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Despacho de Viajes</title>

    <link rel="stylesheet" href="../../../css/estilos.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <link rel="stylesheet" href="../../../css/listado_viajes.css">

    <style>
        /* ===== CONTENEDOR PRINCIPAL ===== */
        .container {
            width: 85%;
            max-width: 1400px;
            margin: 0 auto;
            padding: 10px 0;
        }

        .card {
            padding: 15px 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        /* ===== MENU CON FILTROS ===== */
        .menu-viajes {
            display: flex;
            gap: 6px;
            width: 100%;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }

        .menu-viajes a {
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
            border: 1px solid transparent;
        }

        .menu-viajes a:hover {
            background: #5a6268;
            transform: translateY(-1px);
        }

        .menu-viajes a.filtro-activo {
            background: #28a745;
            border-color: #1e7e34;
            box-shadow: 0 2px 5px rgba(40, 167, 69, 0.3);
        }

        .menu-viajes a.filtro-activo:hover {
            background: #218838;
        }

        .menu-viajes a.btn-nuevo {
            background: #007bff;
            margin-right: 10px;
        }

        .menu-viajes a.btn-nuevo:hover {
            background: #0056b3;
        }

        .menu-viajes a.btn-inicio {
            background: #343a40;
            margin-left: auto;
        }

        .menu-viajes a.btn-inicio:hover {
            background: #23272b;
        }

        .menu-viajes .separador {
            width: 1px;
            background: #ddd;
            margin: 0 5px;
        }

        /* ===== TITULO ===== */
        .titulo-pagina {
            font-size: 18px;
            margin-bottom: 10px;
            color: #333;
        }

        .reloj-panel {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            background-color: #212529;
            color: #00ffcc;
            padding: 6px 18px;
            font-family: 'Courier New', Courier, monospace;
            font-size: 18px;
            font-weight: bold;
            border-radius: 5px;
            margin-bottom: 12px;
            letter-spacing: 1px;
            gap: 5px;
        }

        .reloj-icono {
            margin-right: 8px;
            font-size: 16px;
            color: #adb5bd;
        }

        /* ===== TABLA ===== */
        .tabla-scroll {
            height: calc(100vh - 230px);
            overflow-y: auto;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .table {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
            font-size: 11px;
        }

        .table td,
        .table th {
            padding: 4px 6px;
            font-size: 11px;
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
            padding: 6px 6px;
            font-size: 11px;
            text-align: left;
        }

        /* ===== COLUMNAS CON ANCHO FIJO ===== */
        .col-id {
            width: 40px;
            text-align: center;
        }

        .col-pasajero {
            width: 18%;
            min-width: 120px;
            max-width: 200px;
        }

        .col-direccion {
            width: 11%;
            min-width: 60px;
            max-width: 130px;
        }

        .col-cat {
            width: 80px;
            text-align: center;
        }

        .col-tipo {
            width: 80px;
            text-align: center;
        }

        .col-tiempo {
            width: 60px;
            text-align: center;
            font-weight: bold;
        }

        .col-asignado {
            width: 12%;
            min-width: 110px;
            max-width: 160px;
        }

        .col-acciones {
            width: 15%;
            min-width: 130px;
            max-width: 170px;
            text-align: center;
        }

        /* ===== TEXTO EN PASAJERO ===== */
        .col-pasajero strong {
            font-size: 11px;
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .col-pasajero small {
            font-size: 10px;
            color: #666;
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        /* ===== DIRECCIONES ===== */
        .col-direccion {
            font-size: 10px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .col-direccion:hover {
            white-space: normal;
            background: #fff;
            position: relative;
            z-index: 10;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            padding: 4px 6px;
            border-radius: 3px;
        }

        /* ===== SELECT DE ACCIONES ===== */
        select.acciones-select {
            padding: 2px 4px;
            font-size: 10px;
            border-radius: 3px;
            border: 1px solid #ccc;
            background: white;
            width: 100%;
            max-width: 120px;
            cursor: pointer;
        }

        select.acciones-select:hover {
            border-color: #007bff;
        }

        /* ===== BADGE ASIGNADO ===== */
        .badge-asignado {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 10px;
            font-weight: bold;
            font-size: 10px;
            line-height: 1.4;
            max-width: 100%;
            overflow: hidden;
        }

        .badge-asignado.si {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .badge-asignado.no {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .badge-asignado .movil {
            font-size: 12px;
            display: block;
        }

        .badge-asignado .chofer {
            font-size: 9px;
            font-weight: normal;
            display: block;
            color: #155724;
            margin-top: 1px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .badge-asignado.no .chofer {
            color: #721c24;
        }

        /* ===== MODALES ===== */
        .modal-asignar {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-asignar-content {
            background-color: #ffffff;
            margin: 15% auto;
            padding: 20px;
            border-radius: 8px;
            width: 90%;
            max-width: 400px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
            font-size: 13px;
            color: #333;
        }

        .close-modal {
            color: #aaa;
            float: right;
            font-size: 24px;
            font-weight: bold;
            cursor: pointer;
            line-height: 20px;
        }

        .close-modal:hover {
            color: #000;
        }

        .modal-asignar .form-group {
            margin: 15px 0;
        }

        .modal-asignar .form-group label {
            display: block;
            margin-bottom: 7px;
            font-weight: bold;
        }

        .modal-asignar .form-group select {
            width: 100%;
            padding: 8px;
            border-radius: 4px;
            border: 1px solid #ccc;
            font-size: 13px;
            background-color: #fff;
        }

        .modal-footer {
            text-align: right;
            margin-top: 20px;
        }

        .btn-modal-cancelar {
            background: #6c757d;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 5px;
        }

        .btn-modal-guardar {
            background: #28a745;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }

        .btn-modal-guardar:hover {
            background: #1e7e34;
        }

        .btn-modal-cancelar:hover {
            background: #5a6268;
        }

        /* ===== MODAL EDITAR ===== */
        .modal-editar-overlay {
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

        .modal-editar-container {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 92%;
            max-width: 750px;
            max-height: 90vh;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.4);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .modal-editar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 25px;
            background: #007bff;
            color: white;
            flex-shrink: 0;
        }

        .modal-editar-header h3 {
            margin: 0;
            font-size: 18px;
        }

        .modal-editar-header .close-modal-btn {
            color: white;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            line-height: 1;
            opacity: 0.8;
            transition: opacity 0.2s;
        }

        .modal-editar-header .close-modal-btn:hover {
            opacity: 1;
        }

        .modal-editar-body {
            padding: 20px 25px;
            overflow-y: auto;
            flex: 1;
        }

        .modal-editar-body iframe {
            width: 100%;
            height: 100%;
            min-height: 500px;
            border: none;
            border-radius: 0;
        }

        /* ===== ESTADOS COLORES ===== */
        .fila-pendiente {
            background: #fff3cd !important;
        }

        .fila-asignado {
            background: #d4edda !important;
        }

        .fila-en_curso {
            background: #d4edda !important;
        }

        .fila-diferido {
            background: #d37239 !important;
            color: #ffffff !important;
        }

        .fila-diferido small,
        .fila-diferido .col-direccion {
            color: #ffffff !important;
        }

        .fila-cancelado {
            background: #dc3545 !important;
            color: #ffffff !important;
        }

        .fila-cancelado small,
        .fila-cancelado .col-direccion {
            color: #ffffff !important;
        }

        .fila-completo {
            background: #e2e3e5 !important;
            color: #383d41;
        }

        /* ===== MENSAJE VACIO ===== */
        .mensaje-vacio {
            text-align: center;
            padding: 30px;
            color: #999;
            font-size: 14px;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 1200px) {
            .container {
                width: 95%;
            }

            .col-direccion {
                width: 14%;
                min-width: 70px;
                max-width: 140px;
            }

            .col-pasajero {
                width: 18%;
                min-width: 120px;
                max-width: 200px;
            }
        }

        @media (max-width: 992px) {
            .col-direccion {
                width: 12%;
                min-width: 60px;
                max-width: 100px;
                font-size: 9px;
            }

            .col-pasajero {
                width: 18%;
                min-width: 120px;
                max-width: 200px;
            }

            .col-pasajero strong {
                font-size: 10px;
            }

            .col-pasajero small {
                font-size: 9px;
            }

            .col-asignado {
                width: 16%;
                min-width: 100px;
                max-width: 140px;
            }

            .col-acciones {
                width: 16%;
                min-width: 100px;
                max-width: 140px;
            }

            select.acciones-select {
                max-width: 90px;
                font-size: 9px;
            }

            .badge-asignado {
                font-size: 9px;
            }

            .badge-asignado .movil {
                font-size: 10px;
            }

            .menu-viajes a {
                font-size: 10px;
                padding: 4px 8px;
            }

            .reloj-panel {
                font-size: 14px;
                padding: 4px 12px;
            }

            .modal-editar-container {
                width: 95%;
                max-height: 95vh;
            }

            .modal-editar-body iframe {
                min-height: 400px;
            }
        }

        @media (max-width: 768px) {
            .container {
                width: 100%;
                padding: 5px;
            }

            .card {
                padding: 10px;
            }

            .col-direccion {
                display: none;
            }

            .col-id {
                width: 30px;
            }

            .col-pasajero {
                width: 18%;
                min-width: 120px;
                max-width: 200px;
            }

            .col-asignado {
                width: 20%;
                min-width: 80px;
                max-width: 120px;
            }

            .col-acciones {
                width: 20%;
                min-width: 80px;
                max-width: 120px;
            }

            select.acciones-select {
                max-width: 70px;
                font-size: 8px;
                padding: 1px 2px;
            }

            .badge-asignado .chofer {
                display: none;
            }

            .badge-asignado {
                font-size: 8px;
                padding: 1px 4px;
            }

            .badge-asignado .movil {
                font-size: 9px;
            }

            .menu-viajes a {
                font-size: 9px;
                padding: 3px 5px;
            }

            .reloj-panel {
                font-size: 12px;
                padding: 3px 8px;
            }

            .titulo-pagina {
                font-size: 14px;
            }

            .modal-editar-container {
                width: 98%;
                max-height: 98vh;
                border-radius: 8px;
            }

            .modal-editar-header {
                padding: 10px 15px;
            }

            .modal-editar-header h3 {
                font-size: 15px;
            }

            .modal-editar-body {
                padding: 10px 12px;
            }

            .modal-editar-body iframe {
                min-height: 350px;
            }
        }

        @media (max-width: 480px) {
            .col-pasajero strong {
                font-size: 9px;
            }

            .col-pasajero small {
                display: none;
            }

            .col-cat,
            .col-tipo {
                width: 30px;
                font-size: 8px;
            }

            .col-id {
                width: 25px;
                font-size: 9px;
            }

            .col-asignado {
                min-width: 60px;
                max-width: 80px;
            }

            .col-acciones {
                min-width: 60px;
                max-width: 80px;
            }

            select.acciones-select {
                max-width: 55px;
                font-size: 7px;
                padding: 1px 2px;
            }

            .menu-viajes a {
                font-size: 8px;
                padding: 2px 4px;
            }

            .modal-editar-body iframe {
                min-height: 300px;
            }
        }

        .col-fe-tiempo {
            width: 70px;
            text-align: center;
            font-weight: bold;
        }

        .col-obs {
            width: 50px;
            text-align: center;
            font-size: 16px;
            cursor: pointer;
        }

        .icon-obs {
            display: inline-block;
            font-weight: bold;
        }

        .tooltip-obs {
            position: fixed;
            background: #333;
            color: #fff;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 13px;
            max-width: 300px;
            word-wrap: break-word;
            z-index: 9999;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
            pointer-events: none;
            display: none;
        }

        .tooltip-obs-gris {
            background: #7e4444 !important;
            color: #ffffff !important;
        }

        .col-puntos-paso {
            width: 15%;
            min-width: 100px;
            max-width: 200px;
            font-size: 10px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
    </style>
    <script>
        // ============================================================
        // VERIFICAR VIAJES DIFERIDOS CADA 30 SEGUNDOS
        // ============================================================
        function verificarDiferidos() {
            fetch('verificar_diferidos.php')
                .then(response => response.json())
                .then(data => {
                    if (data.actualizados > 0) {
                        // Recargar la página para mostrar los cambios
                        location.reload();
                    }
                })
                .catch(error => console.error('Error verificando diferidos:', error));
        }

        // Iniciar la verificación cada 30 segundos
        setInterval(verificarDiferidos, 30000);



        // ================= LÓGICA JAVASCRIPT =================

        function evaluarAccion(selectElement, viajeId) {
            const filtroActual = "<?php echo $filtro_estado; ?>";

            if (selectElement.value === 'asignar_movil') {
                document.getElementById('modal-id-viaje').textContent = viajeId;
                document.getElementById('input-modal-viaje-id').value = viajeId;
                document.getElementById('modalAsignar').style.display = 'block';
                cargarVehiculos();
            } else if (selectElement.value === 'desasignar_movil') {
                if (confirm("¿Desea desasignar el móvil de este viaje?")) {
                    window.location = "lista_viajes.php?desasignar=" + viajeId + "&filtro=" + filtroActual;
                } else {
                    restablecerSelects();
                }
            } else if (selectElement.value === 'cancelar_viaje') {
                if (confirm("¿Está seguro de cancelar este viaje?")) {
                    window.location = "lista_viajes.php?cancelar=" + viajeId + "&filtro=" + filtroActual;
                } else {
                    restablecerSelects();
                }
            } else if (selectElement.value === 'editar_viaje') {
                abrirEdicion(viajeId);
                restablecerSelects();
            }
        }

        // ================= ABRIR EDICIÓN =================
        function abrirEdicion(viajeId) {
            const modal = document.getElementById('modalEditarOverlay');
            const iframe = document.getElementById('iframeEditar');

            document.getElementById('modal-editar-id-viaje').textContent = viajeId;

            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
            iframe.src = 'lista_viajes_editar.php?id=' + viajeId;
        }

        function cerrarModalEditar() {
            const modal = document.getElementById('modalEditarOverlay');
            modal.style.display = 'none';
            document.body.style.overflow = '';
            window.location.reload();
        }

        function cerrarModalEditarSinRecargar() {
            const modal = document.getElementById('modalEditarOverlay');
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }

        function restablecerSelects() {
            const dropdowns = document.querySelectorAll('select[name="acciones_viaje"]');
            dropdowns.forEach(d => d.selectedIndex = 0);
        }

        // ================= CARGAR VEHÍCULOS =================
        function cargarVehiculos() {
            fetch('obtener_vehiculos.php')
                .then(response => response.json())
                .then(data => {
                    const select = document.getElementById('movil_select');
                    select.innerHTML = '<option value="">-- Seleccionar Móvil --</option>';

                    if (data.error) {
                        select.innerHTML = '<option value="">Error: ' + data.error + '</option>';
                        return;
                    }
                    if (data.length === 0) {
                        select.innerHTML = '<option value="">No hay móviles activos</option>';
                        return;
                    }
                    data.forEach(vehiculo => {
                        const option = document.createElement('option');
                        option.value = vehiculo.movil;
                        let texto = `🚗 Móvil ${vehiculo.movil} - ${vehiculo.descripcion}`;
                        if (vehiculo.chofer) {
                            texto += ` (${vehiculo.chofer})`;
                        }
                        option.textContent = texto;
                        select.appendChild(option);
                    });
                })
                .catch(error => {
                    console.error('Error cargando vehículos:', error);
                    document.getElementById('movil_select').innerHTML = '<option value="">Error al cargar móviles</option>';
                });
        }

        function cerrarModalAsignar() {
            document.getElementById('modalAsignar').style.display = 'none';
            restablecerSelects();
        }

        // ================= CERRAR MODALES CLIC FUERA =================
        window.addEventListener('click', function(event) {
            const modalAsignar = document.getElementById('modalAsignar');
            const modalEditar = document.getElementById('modalEditarOverlay');
            if (event.target === modalAsignar) {
                cerrarModalAsignar();
            }
        });

        // ================= ESCUCHAR IFRAME =================
        window.addEventListener('message', function(event) {
            if (event.data === 'cerrar_modal') {
                cerrarModalEditar();
            }
            if (event.data === 'cerrar_sin_recargar') {
                cerrarModalEditarSinRecargar();
            }
        });

        // ================= RELOJ Y FECHA =================
        function iniciarReloj() {
            const reloj = document.getElementById('reloj-digital');
            const fecha = document.getElementById('fecha-digital');
            if (!reloj || !fecha) return;

            function actualizar() {
                const ahora = new Date();

                // Hora
                const horas = String(ahora.getHours()).padStart(2, '0');
                const minutos = String(ahora.getMinutes()).padStart(2, '0');
                const segundos = String(ahora.getSeconds()).padStart(2, '0');
                reloj.textContent = `${horas}:${minutos}:${segundos}`;

                // Fecha
                const dia = String(ahora.getDate()).padStart(2, '0');
                const mes = String(ahora.getMonth() + 1).padStart(2, '0');
                const anio = ahora.getFullYear();
                fecha.textContent = `${dia}/${mes}/${anio}`;
            }
            actualizar();
            setInterval(actualizar, 1000);
        }

        // ================= INICIALIZACIÓN =================
        document.addEventListener('DOMContentLoaded', function() {
            iniciarReloj();
        });

        // ================= TOOLTIP PARA OBSERVACIONES =================
        document.addEventListener('DOMContentLoaded', function() {
            const tooltip = document.createElement('div');
            tooltip.className = 'tooltip-obs';
            document.body.appendChild(tooltip);

            const celdasObs = document.querySelectorAll('.text-obs');

            celdasObs.forEach(celda => {
                celda.addEventListener('mouseenter', function(e) {
                    const texto = this.getAttribute('data-texto');
                    if (texto && texto.trim() !== '') {
                        tooltip.textContent = texto;

                        if (this.classList.contains('tooltip-gris')) {
                            tooltip.className = 'tooltip-obs tooltip-obs-gris';
                        } else {
                            tooltip.className = 'tooltip-obs';
                        }

                        tooltip.style.display = 'block';
                        tooltip.style.left = (e.clientX + 15) + 'px';
                        tooltip.style.top = (e.clientY + 15) + 'px';
                    }
                });

                celda.addEventListener('mousemove', function(e) {
                    if (tooltip.style.display === 'block') {
                        tooltip.style.left = (e.clientX + 15) + 'px';
                        tooltip.style.top = (e.clientY + 15) + 'px';
                    }
                });

                celda.addEventListener('mouseleave', function() {
                    tooltip.style.display = 'none';
                });
            });
        });
    </script>

</head>

<body>

    <div class="container">
        <span <strong><?php echo $nombre_usuario ?></strong></span>

        <div class="card">

            <!-- ===== MENU CON FILTROS ===== -->
            <!-- ===== MENU CON FILTROS ===== -->
            <div class="menu-viajes">
                <a href="carga_viajes.php" class="btn-nuevo">📝 Nuevo Viaje</a>

                <div class="separador"></div>

                <a href="lista_viajes.php?filtro=todos" class="<?= ($filtro_estado == 'todos') ? 'filtro-activo' : '' ?>">📋 Todos</a>
                <a href="lista_viajes.php?filtro=pendiente" class="<?= ($filtro_estado == 'pendiente') ? 'filtro-activo' : '' ?>" style="background:#ffc107; color:#212529;">⏳ Pendiente</a>
                <a href="lista_viajes.php?filtro=diferidos" class="<?= ($filtro_estado == 'diferidos') ? 'filtro-activo' : '' ?>" style="background:#fd7e14;">⏰ Diferidos</a>
                <!-- 🔴 ELIMINADO: <a href="lista_viajes.php?filtro=asignado" class="...">📌 Asignado</a> -->
                <a href="lista_viajes.php?filtro=en_curso" class="<?= ($filtro_estado == 'en_curso') ? 'filtro-activo' : '' ?>" style="background:#0d6efd;">🚗 En Curso</a>
                <a href="lista_viajes.php?filtro=completos" class="<?= ($filtro_estado == 'completos') ? 'filtro-activo' : '' ?>" style="background:#6c757d;">✅ Completos</a>
                <a href="lista_viajes.php?filtro=cancelados" class="<?= ($filtro_estado == 'cancelados') ? 'filtro-activo' : '' ?>" style="background:#dc3545;">❌ Cancelados</a>

                <div class="separador"></div>
                <a href="../../inicio_0.php" class="btn-inicio">🏠 Inicio</a>
            </div>

            <!-- TITULO -->
            <h3 class="titulo-pagina">📋 Listado de Viajes <?php echo ($filtro_estado != 'todos') ? '(' . ucfirst($filtro_estado) . ')' : 'Activos'; ?></h3>

            <!-- RELOJ Y FECHA -->
            <div class="reloj-panel">
                <span class="reloj-icono">🕒</span>
                <span id="reloj-digital">00:00:00</span>
                <span style="margin: 0 10px; color: #6c757d;">|</span>
                <span class="reloj-icono">📅</span>
                <span id="fecha-digital">00/00/0000</span>
            </div>

            <!-- TABLA -->
            <div class="tabla-scroll">
                <table class="table">
                    <thead>
                        <tr>
                            <th class="col-id">Viaje N°</th>
                            <th class="col-pasajero">Pasajero</th>
                            <th class="col-fe-tiempo">Fecha</th>
                            <th class="col-tiempo">Hora</th>
                            <th class="col-direccion">Origen</th>
                            <th class="col-puntos-paso">Puntos de Paso</th>
                            <th class="col-direccion">Destino</th>
                            <th class="col-cat">Categoría</th>
                            <th class="col-tipo">Estado</th>
                            <th class="col-obs">Obs. chofer</th>
                            <th class="col-obs">Obs. Oper</th>
                            <th class="col-asignado">Móvil / Chofer</th>
                            <th class="col-acciones">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($viajes)): ?>
                            <tr>
                                <td colspan="12" class="mensaje-vacio">No hay viajes en esta categoría</td>
                            </tr>
                            <?php else: foreach ($viajes as $v): ?>
                                <?php
                                $clase_fila = '';
                                if ($v['estado'] == 'Pendiente') {
                                    $clase_fila = 'fila-pendiente';
                                } elseif ($v['estado'] == 'Diferido') {
                                    $clase_fila = 'fila-diferido';
                                } elseif ($v['estado'] == 'Asignado') {
                                    $clase_fila = 'fila-asignado';
                                } elseif ($v['estado'] == 'En Curso') {
                                    $clase_fila = 'fila-asignado';
                                } elseif ($v['estado'] == 'Completo') {
                                    $clase_fila = 'fila-completo';
                                } elseif ($v['estado'] == 'Cancelado') {
                                    $clase_fila = 'fila-cancelado';
                                }
                                ?>
                                <tr class="<?= $clase_fila ?>">
                                    <td class="col-id"><?= $v['id'] ?></td>
                                    <td class="col-pasajero">
                                        <strong title="<?= htmlspecialchars($v['nombre_pasaj']) ?>">
                                            <?= htmlspecialchars($v['nombre_pasaj']) ?>
                                        </strong>
                                        <small title="<?= htmlspecialchars($v['cel_pasaj']) ?>">
                                            <?= htmlspecialchars($v['cel_pasaj']) ?>
                                        </small>
                                    </td>

                                    <td class="col-fe-tiempo" style="font-weight:bold; font-size:13px; text-align:center;">
                                        <?= date('d-m', strtotime($v['fecha'])) ?>
                                    </td>

                                    <td class="col-tiempo" style="font-weight:bold; font-size:13px; text-align:center;">
                                        <?= substr($v['hora'], 0, 5) ?>
                                    </td>
                                    <td class="col-direccion" title="<?= htmlspecialchars($v['direccion_origen']) ?>">
                                        <?= htmlspecialchars($v['direccion_origen']) ?>
                                    </td>
                                    <td class="col-puntos-paso" style="font-size:10px; color:#666; max-width:150px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"
                                        title="<?= htmlspecialchars($v['puntos_paso'] ?? '') ?>">
                                        <?= $v['puntos_paso'] ? '📍 ' . htmlspecialchars($v['puntos_paso']) : '-' ?>
                                    </td>
                                    <td class="col-direccion" title="<?= htmlspecialchars($v['direccion_destino']) ?>">
                                        <?= htmlspecialchars($v['direccion_destino']) ?>
                                    </td>
                                    <td class="col-cat"><?= $v['categoria_movil'] ?></td>
                                    <td class="col-tipo"><?= $v['estado'] ?></td>

                                    <td class="col-obs text-obs tooltip-gris" data-texto="<?= htmlspecialchars($v['obs_pasaj'] ?? '') ?>">
                                        <?php if (!empty($v['obs_pasaj'])): ?>
                                            <span class="icon-obs">✔</span>
                                        <?php else: ?>
                                            <span class="icon-obs">-</span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="col-obs text-obs" data-texto="<?= htmlspecialchars($v['obs_operador'] ?? '') ?>">
                                        <?php if (!empty($v['obs_operador'])): ?>
                                            <span class="icon-obs">✔</span>
                                        <?php else: ?>
                                            <span class="icon-obs">-</span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="col-asignado">
                                        <?php if (!empty($v['asignado_a'])): ?>
                                            <?php $chofer = obtenerChoferPorMovil($v['asignado_a']); ?>
                                            <?php $nombreChofer = ($chofer) ? $chofer['nombre'] . ' ' . $chofer['apellido'] : ''; ?>
                                            <span class="badge-asignado si">
                                                <span class="movil">🚗 <?= $v['asignado_a'] ?></span>
                                                <?php if ($nombreChofer): ?>
                                                    <span class="chofer">👤 <?= $nombreChofer ?></span>
                                                <?php endif; ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge-asignado no">
                                                <span class="movil">⚠️ Sin asignar</span>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="col-acciones">
                                        <?php if ($filtro_estado !== 'completos' && $filtro_estado !== 'cancelados'): ?>
                                            <select name="acciones_viaje" class="acciones-select" onchange="evaluarAccion(this, <?= $v['id'] ?>)">
                                                <option value="">Acciones</option>
                                                <option value="editar_viaje">✏️ Editar Viaje</option>
                                                <option value="asignar_movil">📌 Asignar</option>
                                                <?php if (!empty($v['asignado_a'])): ?>
                                                    <option value="desasignar_movil">🔓 Desasignar</option>
                                                <?php endif; ?>
                                                <option value="cancelar_viaje">❌ Cancelar</option>
                                            </select>
                                        <?php else: ?>
                                            <span style="color:#999; font-size:10px;">---</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                        <?php endforeach;
                        endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>

    <!-- ===== MODAL ASIGNAR ===== -->
    <div id="modalAsignar" class="modal-asignar">
        <div class="modal-asignar-content">
            <span class="close-modal" onclick="cerrarModalAsignar()">&times;</span>
            <h4>📌 Asignar Móvil al Viaje #<span id="modal-id-viaje"></span></h4>

            <form method="POST" action="lista_viajes.php">
                <input type="hidden" name="viaje_id" id="input-modal-viaje-id">
                <input type="hidden" name="filtro_actual" value="<?php echo $filtro_estado; ?>">

                <div class="form-group">
                    <label for="movil_select">Seleccionar Móvil:</label>
                    <select name="movil_id" id="movil_select" required>
                        <option value="">-- Cargando móviles activos --</option>
                    </select>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-modal-cancelar" onclick="cerrarModalAsignar()">Cancelar</button>
                    <button type="submit" name="asignar_movil" class="btn-modal-guardar">✅ Asignar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===== MODAL EDITAR ===== -->
    <div id="modalEditarOverlay" class="modal-editar-overlay">
        <div class="modal-editar-container">
            <div class="modal-editar-header">
                <h3>✏️ Editar Viaje #<span id="modal-editar-id-viaje"></span></h3>
                <span class="close-modal-btn" onclick="cerrarModalEditar()">&times;</span>
            </div>
            <div class="modal-editar-body">
                <iframe id="iframeEditar" src=""></iframe>
            </div>
        </div>
    </div>
    <script>
        // ======================================================
        // 🔄 RECARGA AUTOMÁTICA DE LA PÁGINA CADA 30 SEGUNDOS
        // ======================================================
        setTimeout(function() {
            location.reload();
        }, 30000);
    </script>
</body>

</html>