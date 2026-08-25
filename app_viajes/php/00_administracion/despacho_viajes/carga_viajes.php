<?php
include_once "../../../funciones/funciones.php";
protegerPagina([0, 3]);

// ============================================================
// DETERMINAR ESTADO DEL VIAJE AL GUARDAR
// ============================================================
function determinarEstadoViaje($fecha, $hora)
{
    // Si no hay fecha u hora, el viaje es Pendiente por defecto
    if (empty($fecha) || empty($hora)) {
        return 'Pendiente';
    }

    $fecha_hora_completa = $fecha . ' ' . $hora;
    $timestamp_seleccionado = strtotime($fecha_hora_completa);
    $timestamp_ahora = time();

    // Si la fecha/hora es futura, el viaje es Diferido
    if ($timestamp_seleccionado > $timestamp_ahora) {
        return 'Diferido';
    } else {
        return 'Pendiente';
    }
}

$usuario = nombre_usuario();
$nombre_usuario = $usuario['nombre'];
$usuario_id = $usuario['id'];

// ============================================================
// PROCESAR GUARDADO DEL VIAJE
// ============================================================
if (isset($_POST['guardar'])) {
    // Obtener fecha y hora con validación
    $fecha = isset($_POST['fecha']) && !empty($_POST['fecha']) ? $_POST['fecha'] : date('Y-m-d');
    $hora = isset($_POST['hora']) && !empty($_POST['hora']) ? $_POST['hora'] : date('H:i');

    // Determinar estado automáticamente
    $estado = determinarEstadoViaje($fecha, $hora);

    // Agregar los valores al POST
    $_POST['fecha'] = $fecha;
    $_POST['hora'] = $hora;
    $_POST['estado'] = $estado;

    // Guardar el viaje
    guardarViaje($_POST);
    header("Location: lista_viajes.php");
    exit;
}

// Guardar recorrido por separado (AJAX)
if (isset($_POST['guardar_recorrido'])) {
    $id_viaje = $_POST['id_viaje'] ?? 0;
    $movil = $_POST['movil'] ?? '';
    $origen = $_POST['origen'] ?? '';
    $destino = $_POST['destino'] ?? '';
    $origen_lat = $_POST['origen_lat'] ?? null;
    $origen_lng = $_POST['origen_lng'] ?? null;
    $destino_lat = $_POST['destino_lat'] ?? null;
    $destino_lng = $_POST['destino_lng'] ?? null;
    $distancia = $_POST['distancia'] ?? null;
    $tiempo = $_POST['tiempo'] ?? null;

    $resultado = guardarRecorrido($id_viaje, $movil, $origen, $destino, $origen_lat, $origen_lng, $destino_lat, $destino_lng, $distancia, $tiempo);

    if ($resultado) {
        echo json_encode(['res' => 'OK', 'msg' => 'Recorrido guardado correctamente']);
    } else {
        echo json_encode(['res' => 'ERROR', 'msg' => 'Error al guardar el recorrido']);
    }
    exit;
}

$viaje = null;
if (isset($_GET['editar'])) {
    $viaje = obtenerViajePorId((int)$_GET['editar']);
}

$empresas = obtenerEmpresas();

// Obtener el móvil del viaje si está asignado
$movil_asignado = '';
if ($viaje && !empty($viaje['asignado_a'])) {
    $movil_asignado = $viaje['asignado_a'];
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>NUEVOS VIAJES</title>
    <link rel="stylesheet" href="../../../css/estilos.css">
    <link rel="stylesheet" href="../../../css/listado_viajes.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="../../../css/carga_viajes.css">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="carga_viajes.js" defer></script>

    <style>
        /* CONTENEDOR */
        .container {
            width: 100%;
            margin: 0 auto;
        }

        /* MENU SUPERIOR */
        .menu-viajes {
            display: flex;
            gap: 10px;
            width: 100%;
            margin-bottom: 20px;
        }

        .menu-viajes a {
            flex: 1;
            text-align: center;
            padding: 5px 8px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            font-size: 13px;
        }

        .menu-viajes a:hover {
            background: #0056b3;
        }

        /* BOTONES DE ESTADO */
        .grupo-botones-estado {
            display: flex;
            gap: 10px;
            margin-bottom: 5px;
        }

        .btn-switch {
            flex: 1;
            padding: 12px 15px;
            font-weight: bold;
            font-size: 14px;
            border: 2px solid #ccc;
            background-color: #f8f9fa;
            color: #495057;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
            text-transform: uppercase;
        }

        .btn-switch:hover {
            background-color: #e2e6ea;
        }

        .btn-switch.activo-inmediato {
            background-color: #0d6efd;
            color: white;
            border-color: #0a58ca;
            box-shadow: 0 0 5px rgba(13, 110, 253, 0.5);
        }

        .btn-switch.activo-diferido {
            background-color: #fd7e14;
            color: white;
            border-color: #e46a06;
            box-shadow: 0 0 5px rgba(253, 126, 20, 0.5);
        }

        /* CATEGORIAS */
        .grid-categorias {
            display: flex;
            gap: 8px;
            margin-top: 5px;
            flex-wrap: nowrap;
        }

        .tarjeta-categoria {
            flex: 1;
            max-width: 105px;
            border: 2px solid #ddd;
            border-radius: 6px;
            padding: 5px 4px;
            text-align: center;
            background: #fff;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .tarjeta-categoria:hover {
            border-color: #bbb;
            background-color: #f1f3f5;
            transform: scale(1.02);
        }

        .tarjeta-categoria img {
            width: 85px;
            height: 55px;
            object-fit: contain;
            margin-bottom: 2px;
        }

        .tarjeta-categoria span {
            font-weight: bold;
            font-size: 11px;
            color: #495057;
            text-transform: uppercase;
        }

        .tarjeta-categoria.activa {
            border-color: #0d6efd;
            background-color: #e7f1ff;
            box-shadow: 0 0 6px rgba(13, 110, 253, 0.4);
        }

        .tarjeta-categoria.activa span {
            color: #0d6efd;
        }

        /* RESULTADO RECORRIDO */
        .resultado-recorrido {
            display: flex;
            gap: 30px;
            margin: 10px 0 15px 0;
            padding: 12px 20px;
            background: #f0f7ff;
            border-radius: 8px;
            border: 2px solid #0d6efd;
            justify-content: center;
        }

        .resultado-recorrido .item {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .resultado-recorrido .item .icono {
            font-size: 24px;
        }

        .resultado-recorrido .item .valor {
            font-weight: bold;
            font-size: 18px;
            color: #0d6efd;
        }

        .resultado-recorrido .item .etiqueta {
            font-size: 12px;
            color: #6c757d;
        }

        /* INPUT MAPA */
        .input-mapa {
            display: flex;
            gap: 5px;
        }

        .input-mapa input {
            flex: 1;
        }

        .btn-map {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            font-weight: bold;
            cursor: pointer;
            font-size: 12px;
            white-space: nowrap;
        }

        .btn-origen {
            background: #0d6efd;
            color: white;
        }

        .btn-origen:hover {
            background: #0a58ca;
        }

        .btn-destino {
            background: #198754;
            color: white;
        }

        .btn-destino:hover {
            background: #157347;
        }

        .btn-recorrido {
            background: #6f42c1;
            color: white;
        }

        .btn-recorrido:hover {
            background: #5c36a6;
        }

        .autocomplete-box {
            background: white;
            border: 1px solid #ccc;
            border-top: none;
            max-height: 150px;
            overflow-y: auto;
            position: relative;
            z-index: 100;
            display: none;
        }

        .autocomplete-box.active {
            display: block;
        }

        .fecha-hora {
            display: none;
            gap: 10px;
            margin: 10px 0;
        }

        .fecha-hora input {
            flex: 1;
            padding: 6px 10px;
            border-radius: 4px;
            border: 1px solid #ccc;
        }

        .form-full {
            grid-column: 1 / -1;
        }

        .acciones-form {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #dee2e6;
        }

        .acciones-form .btn-guardar {
            background: #28a745;
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 4px;
            font-weight: bold;
            cursor: pointer;
            font-size: 14px;
        }

        .acciones-form .btn-guardar:hover {
            background: #1e7e34;
        }

        .acciones-form .btn-volver {
            background: #6c757d;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            display: inline-block;
        }

        .acciones-form .btn-volver:hover {
            background: #5a6268;
        }

        .btn-volver {
            background: #6c757d;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            display: inline-block;
        }

        .btn-volver:hover {
            background: #5a6268;
        }

        .form-2cols {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
        }

        .form-group {
            margin-bottom: 12px;
        }

        .form-group label {
            display: block;
            font-weight: bold;
            font-size: 13px;
            margin-bottom: 3px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 6px 10px;
            border-radius: 4px;
            border: 1px solid #ccc;
            font-size: 13px;
            box-sizing: border-box;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 50px;
        }

        .form-group small {
            color: #6c757d;
            font-size: 11px;
        }

        /* ===== BOTON GUARDAR RECORRIDO ===== */
        .btn-guardar-recorrido {
            background: #28a745;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            font-weight: bold;
            cursor: pointer;
            font-size: 14px;
            width: 100%;
            transition: background 0.2s;
        }

        .btn-guardar-recorrido:hover {
            background: #1e7e34;
        }

        .btn-guardar-recorrido:disabled {
            background: #6c757d;
            cursor: not-allowed;
            opacity: 0.6;
        }

        .btn-guardar-recorrido.guardado {
            background: #0d6efd;
        }

        .btn-guardar-recorrido.guardado:hover {
            background: #0a58ca;
        }

        .mensaje-recorrido {
            padding: 8px 12px;
            border-radius: 4px;
            margin-top: 8px;
            font-size: 13px;
            display: none;
        }

        .mensaje-recorrido.exito {
            display: block;
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .mensaje-recorrido.error {
            display: block;
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .mensaje-recorrido.info {
            display: block;
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        .recorrido-guardado-box {
            margin-top: 10px;
            padding: 12px 15px;
            background: #d4edda;
            border-radius: 4px;
            border: 1px solid #c3e6cb;
            display: none;
        }

        .recorrido-guardado-box .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .recorrido-guardado-box .header .titulo {
            font-weight: bold;
            color: #155724;
        }

        .recorrido-guardado-box .header .fecha {
            font-size: 12px;
            color: #155724;
        }

        .recorrido-guardado-box .datos {
            display: flex;
            gap: 20px;
            margin-top: 5px;
            flex-wrap: wrap;
        }

        .recorrido-guardado-box .datos .item {
            display: flex;
            gap: 5px;
            align-items: center;
            font-size: 13px;
        }

        .recorrido-guardado-box .datos .item .label {
            color: #155724;
        }

        .recorrido-guardado-box .datos .item .valor {
            font-weight: bold;
            color: #0d6efd;
        }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            .form-2cols {
                grid-template-columns: 1fr;
                gap: 0;
            }

            .resultado-recorrido {
                flex-direction: column;
                gap: 8px;
                align-items: center;
                padding: 10px;
            }

            .input-mapa {
                flex-wrap: wrap;
            }

            .input-mapa button {
                flex: 1;
                min-width: 60px;
            }

            .acciones-form {
                flex-direction: column;
            }

            .acciones-form .btn-guardar,
            .acciones-form .btn-volver {
                width: 100%;
                text-align: center;
            }

            .grid-categorias {
                flex-wrap: wrap;
                justify-content: center;
            }

            .tarjeta-categoria {
                max-width: 80px;
            }

            .tarjeta-categoria img {
                width: 60px;
                height: 40px;
            }

            .grupo-botones-estado {
                flex-direction: column;
            }

            .recorrido-guardado-box .datos {
                flex-direction: column;
                gap: 5px;
            }
        }

        @media (max-width: 480px) {
            .tarjeta-categoria {
                max-width: 65px;
                padding: 3px 2px;
            }

            .tarjeta-categoria img {
                width: 50px;
                height: 35px;
            }

            .tarjeta-categoria span {
                font-size: 9px;
            }

            .resultado-recorrido .item .valor {
                font-size: 15px;
            }
        }
    </style>

    <script>
        const editandoViaje = <?= isset($viaje['id']) ? 'true' : 'false' ?>;
        const idViajeActual = <?= $viaje['id'] ?? 0 ?>;

        function fechaActual() {
            const fecha = document.getElementById("fecha");
            const hora = document.getElementById("hora");
            if (!fecha || !hora) return;

            const ahora = new Date();
            const yyyy = ahora.getFullYear();
            const mm = String(ahora.getMonth() + 1).padStart(2, '0');
            const dd = String(ahora.getDate()).padStart(2, '0');
            const hh = String(ahora.getHours()).padStart(2, '0');
            const mi = String(ahora.getMinutes()).padStart(2, '0');

            fecha.value = `${yyyy}-${mm}-${dd}`;
            hora.value = `${hh}:${mi}`;
        }

        function seleccionarEstado(estado) {
            const inputEstado = document.getElementById("estado_oculto");
            const btnInmediato = document.getElementById("btn_inmediato");
            const btnDiferido = document.getElementById("btn_diferido");
            const contenedorFechaHora = document.getElementById("contenedor_fecha_hora");
            const fecha = document.getElementById("fecha");
            const hora = document.getElementById("hora");

            if (!inputEstado || !btnInmediato || !btnDiferido || !contenedorFechaHora) return;

            if (estado === 'Diferido') {
                inputEstado.value = 'Diferido';
                btnDiferido.classList.add('activo-diferido');
                btnInmediato.classList.remove('activo-inmediato');
                contenedorFechaHora.style.display = 'flex';
                if (fecha && hora) {
                    fecha.readOnly = false;
                    hora.readOnly = false;
                    if (!fecha.value) {
                        fechaActual();
                    }
                }
            } else {
                inputEstado.value = 'Pendiente';
                btnInmediato.classList.add('activo-inmediato');
                btnDiferido.classList.remove('activo-diferido');
                contenedorFechaHora.style.display = 'none';
                fechaActual();
                if (fecha && hora) {
                    fecha.readOnly = true;
                    hora.readOnly = true;
                }
            }
        }

        function seleccionarCategoria(categoria) {
            const inputCategoria = document.getElementById("categoria_movil_oculto");
            if (!inputCategoria) return;

            inputCategoria.value = categoria;

            document.querySelectorAll('.tarjeta-categoria').forEach(tarjeta => {
                tarjeta.classList.remove('activa');
            });

            const tarjetaSeleccionada = document.querySelector(`.tarjeta-categoria[data-categoria="${categoria}"]`);
            if (tarjetaSeleccionada) {
                tarjetaSeleccionada.classList.add('activa');
            }
        }

        function formatearCelular(cel) {
            if (!cel) return '';
            cel = cel.toString().replace(/\D/g, '');
            if (cel.length === 10) {
                return cel.substring(0, 2) + '-' +
                    cel.substring(2, 6) + '-' +
                    cel.substring(6);
            }
            return cel;
        }

        // ================= CARGAR CENTROS DE COSTO =================
        function cargarCentros(empresa, ccPreseleccionado) {
            let contenedorCC = document.getElementById('contenedor_cc');
            let comboCC = document.getElementById('id_cc');

            if (!empresa) {
                contenedorCC.style.display = 'none';
                comboCC.innerHTML = '<option value="">Seleccione Centro de Costo</option>';
                return;
            }

            contenedorCC.style.display = 'block';
            comboCC.innerHTML = '<option value="">Cargando centros...</option>';

            fetch('obtener_centros.php?id_empresa=' + empresa)
                .then(response => {
                    if (!response.ok) throw new Error('HTTP Status ' + response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('Centros de costo cargados:', data);
                    comboCC.innerHTML = '<option value="">-- Seleccione Centro de Costo --</option>';

                    if (Array.isArray(data) && data.length > 0) {
                        data.forEach(cc => {
                            let esSelected = (ccPreseleccionado == cc.id) ? 'selected' : '';
                            let nombre = cc.nombre || 'Sin nombre';
                            let codigo = cc.id_centro_costo ? cc.id_centro_costo + ' - ' : '';
                            comboCC.innerHTML += '<option value="' + cc.id + '" ' + esSelected + '>' + codigo + nombre + '</option>';
                        });
                    } else {
                        comboCC.innerHTML = '<option value="">No hay centros de costo para esta empresa</option>';
                    }
                })
                .catch(error => {
                    console.error('Error en fetch centros:', error);
                    comboCC.innerHTML = '<option value="">Error al cargar centros</option>';
                });
        }

        // ================= CARGAR AUTORIZANTES =================
        function cargarAutorizantes(id_cc, empresa, autPreseleccionado) {
            let contenedorAut = document.getElementById('contenedor_autorizante');
            let comboAut = document.getElementById('id_autorizante');

            if (!empresa) {
                contenedorAut.style.display = 'none';
                comboAut.innerHTML = '<option value="">Seleccione Autorizante</option>';
                return;
            }

            let url = 'obtener_autorizantes.php?id_empresa=' + empresa;
            if (id_cc) {
                url += '&id_cc=' + id_cc;
            }

            contenedorAut.style.display = 'block';
            comboAut.innerHTML = '<option value="">Cargando autorizantes...</option>';

            fetch(url)
                .then(response => {
                    if (!response.ok) throw new Error('HTTP Status ' + response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('Autorizantes cargados:', data);
                    comboAut.innerHTML = '<option value="">-- Seleccione Autorizante --</option>';

                    if (Array.isArray(data) && data.length > 0) {
                        window.autorizantesCargados = data;
                        data.forEach(a => {
                            let esSelected = (autPreseleccionado == a.id) ? 'selected' : '';
                            let tel = a.celular ? ' - ' + formatearCelular(a.celular) : '';
                            comboAut.innerHTML += '<option value="' + a.id + '" ' + esSelected + '>' + a.nombre + tel + '</option>';
                        });
                    } else {
                        comboAut.innerHTML = '<option value="">No hay autorizantes para esta empresa</option>';
                    }
                })
                .catch(error => {
                    console.error('Error en fetch autorizantes:', error);
                    comboAut.innerHTML = '<option value="">Error al cargar autorizantes</option>';
                });
        }

        // ================= GUARDAR RECORRIDO =================
        function guardarRecorrido() {
            const idViaje = idViajeActual;
            const movil = 'SIN_MOVIL';
            const origen = document.getElementById('dir_origen').value;
            const destino = document.getElementById('dir_destino').value;
            const origen_lat = document.getElementById('dir_origen_lat').value;
            const origen_lng = document.getElementById('dir_origen_lng').value;
            const destino_lat = document.getElementById('dir_destino_lat').value;
            const destino_lng = document.getElementById('dir_destino_lng').value;
            const distancia = document.getElementById('distancia_recorrido').value;
            const tiempo = document.getElementById('tiempo_recorrido').value;

            if (!idViaje) {
                mostrarMensaje('❌ Primero guarda el viaje', 'error');
                return;
            }

            if (!origen || !destino) {
                mostrarMensaje('❌ Completa origen y destino', 'error');
                return;
            }

            if (!distancia || parseFloat(distancia) === 0) {
                mostrarMensaje('❌ Calcula el recorrido primero (➡️ RECORRIDO)', 'error');
                return;
            }

            const btn = document.getElementById('btnGuardarRecorrido');
            btn.disabled = true;
            btn.textContent = '⏳ Guardando...';

            const data = {
                id_viaje: idViaje,
                movil: movil,
                origen: origen,
                destino: destino,
                origen_lat: origen_lat || null,
                origen_lng: origen_lng || null,
                destino_lat: destino_lat || null,
                destino_lng: destino_lng || null,
                distancia: distancia,
                tiempo: tiempo
            };

            console.log('📤 Guardando recorrido:', data);

            fetch('guardar_recorrido.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                })
                .then(response => response.json())
                .then(resultado => {
                    if (resultado.res === 'OK') {
                        mostrarMensaje('✅ ' + resultado.msg, 'exito');
                        btn.textContent = '✅ Recorrido guardado';
                        btn.classList.add('guardado');
                        btn.disabled = true;

                        document.getElementById('recorridoGuardado').style.display = 'block';
                        document.getElementById('recorridoDistancia').textContent = distancia + ' km';
                        document.getElementById('recorridoTiempo').textContent = tiempo + ' min';
                        document.getElementById('recorridoMovil').textContent = movil;
                        document.getElementById('fechaRecorrido').textContent = '📅 ' + new Date().toLocaleString();
                    } else {
                        mostrarMensaje('❌ ' + resultado.msg, 'error');
                        btn.disabled = false;
                        btn.textContent = '💾 Guardar Recorrido';
                    }
                })
                .catch(error => {
                    mostrarMensaje('❌ Error al guardar el recorrido', 'error');
                    btn.disabled = false;
                    btn.textContent = '💾 Guardar Recorrido';
                    console.error(error);
                });
        }

        // ================= MOSTRAR MENSAJE =================
        function mostrarMensaje(texto, tipo) {
            const mensaje = document.getElementById('mensajeRecorrido');
            mensaje.textContent = texto;
            mensaje.className = 'mensaje-recorrido ' + tipo;
            setTimeout(() => {
                mensaje.className = 'mensaje-recorrido';
            }, 5000);
        }

        // ================= VERIFICAR SI SE PUEDE GUARDAR RECORRIDO =================
        function verificarGuardarRecorrido() {
            const distancia = document.getElementById('distancia_recorrido').value;
            const idViaje = idViajeActual;
            const btn = document.getElementById('btnGuardarRecorrido');
            const guardado = document.getElementById('recorridoGuardado').style.display === 'block';

            if (distancia && parseFloat(distancia) > 0 && idViaje > 0 && !guardado) {
                btn.disabled = false;
                btn.title = 'Click para guardar el recorrido';
            } else {
                btn.disabled = true;
                if (!idViaje) btn.title = 'Primero guarda el viaje';
                else if (!distancia || parseFloat(distancia) === 0) btn.title = 'Calcula el recorrido primero';
                else if (guardado) btn.title = 'Recorrido ya guardado';
            }
        }

        // ================= VERIFICAR RECORRIDO GUARDADO AL CARGAR =================
        function verificarRecorridoGuardado(idViaje) {
            if (!idViaje) return;

            fetch('obtener_recorrido.php?id_viaje=' + idViaje)
                .then(response => response.json())
                .then(data => {
                    if (data && data.id) {
                        document.getElementById('recorridoGuardado').style.display = 'block';
                        document.getElementById('recorridoDistancia').textContent = data.distancia + ' km';
                        document.getElementById('recorridoTiempo').textContent = data.tiempo + ' min';
                        document.getElementById('recorridoMovil').textContent = data.movil || '-';
                        document.getElementById('fechaRecorrido').textContent = '📅 ' + data.fecha_registro;

                        document.getElementById('btnGuardarRecorrido').disabled = true;
                        document.getElementById('btnGuardarRecorrido').textContent = '✅ Recorrido ya guardado';
                        document.getElementById('btnGuardarRecorrido').classList.add('guardado');
                    }
                })
                .catch(error => console.error('Error verificando recorrido:', error));
        }

        // ================= VALIDAR FORMULARIO =================
        function validarFormulario() {
            const nombre = document.getElementById('nombre_pasaj');
            const origen = document.getElementById('dir_origen');
            const categoria = document.getElementById('categoria_movil_oculto');

            if (!nombre.value.trim()) {
                alert("❌ El campo 'Nombre del Pasajero' es obligatorio");
                nombre.focus();
                return false;
            }

            if (!origen.value.trim()) {
                alert("❌ El campo 'Origen' es obligatorio");
                origen.focus();
                return false;
            }

            if (!categoria.value) {
                alert("❌ Selecciona una categoría de móvil");
                return false;
            }

            return true;
        }

        // ================= INICIALIZACIÓN =================
        document.addEventListener("DOMContentLoaded", function() {
            console.log('🚀 Inicializando carga_viajes.php');

            // Estado del viaje
            const inputEstado = document.getElementById("estado_oculto");
            if (inputEstado) {
                if (!editandoViaje) {
                    inputEstado.value = 'Pendiente';
                    seleccionarEstado('Pendiente');
                    fechaActual();
                } else {
                    seleccionarEstado(inputEstado.value);
                }
            }

            // Categoría
            const inputCategoria = document.getElementById("categoria_movil_oculto");
            if (inputCategoria && inputCategoria.value) {
                seleccionarCategoria(inputCategoria.value);
            }

            // Cargar centros y autorizantes
            let empresaSeleccionada = document.getElementById('cc').value;
            let ccPrevio = "<?= $viaje['id_cc'] ?? '' ?>";
            let autPrevio = "<?= $viaje['id_autorizante'] ?? '' ?>";

            console.log('Empresa seleccionada:', empresaSeleccionada);
            console.log('CC previo:', ccPrevio);
            console.log('Aut previo:', autPrevio);

            if (empresaSeleccionada) {
                cargarCentros(empresaSeleccionada, ccPrevio);
                setTimeout(function() {
                    cargarAutorizantes(ccPrevio || null, empresaSeleccionada, autPrevio);
                }, 300);
            }

            // Verificar si ya hay recorrido guardado
            if (idViajeActual > 0) {
                verificarRecorridoGuardado(idViajeActual);
            }

            // Escuchar cambios en distancia
            const distanciaInput = document.getElementById('distancia_recorrido');
            if (distanciaInput) {
                distanciaInput.addEventListener('change', verificarGuardarRecorrido);
                distanciaInput.addEventListener('input', verificarGuardarRecorrido);
            }

            // Verificar estado inicial después de 500ms
            setTimeout(verificarGuardarRecorrido, 500);

            // Evento: Cambio de empresa
            document.getElementById('cc').addEventListener('change', function() {
                let empresa = this.value;
                console.log('Cambio de empresa a:', empresa);
                document.getElementById('id_cc').innerHTML = '<option value="">Seleccione Centro de Costo</option>';
                document.getElementById('id_autorizante').innerHTML = '<option value="">Seleccione Autorizante</option>';

                if (empresa) {
                    cargarCentros(empresa, null);
                    setTimeout(function() {
                        cargarAutorizantes(null, empresa, null);
                    }, 300);
                } else {
                    document.getElementById('contenedor_cc').style.display = 'none';
                    document.getElementById('contenedor_autorizante').style.display = 'none';
                }
            });

            // Evento: Cambio de centro de costo
            document.getElementById('id_cc').addEventListener('change', function() {
                let id_cc = this.value;
                let empresa = document.getElementById('cc').value;
                console.log('Cambio de CC a:', id_cc);
                if (empresa) {
                    cargarAutorizantes(id_cc || null, empresa, null);
                }
            });

            // Evento: Selección de autorizante
            document.getElementById('id_autorizante').addEventListener('change', function() {
                let idSeleccionado = this.value;
                console.log('Autorizante seleccionado:', idSeleccionado);
                if (idSeleccionado && window.autorizantesCargados) {
                    let autorizante = window.autorizantesCargados.find(a => a.id == idSeleccionado);
                    if (autorizante) {
                        document.getElementById('nombre_pasaj').value = autorizante.nombre || '';
                        document.getElementById('cel_pasaj').value = autorizante.celular || '';
                    }
                }
            });
        });
    </script>

</head>

<body>

    <div class="container">
        <span><strong><?php echo $nombre_usuario ?></strong></span>
        <div class="card">

            <h3><?= $viaje ? "Editar Viaje" : "Nuevo Viaje de Cuenta Corriente"; ?></h3>

            <div style="text-align:left; margin-bottom:15px;">
                <a href="carga_viajes.php" class="btn btn-primary" style="margin-right:10px; padding:8px 16px; background:#0d6efd; color:white; text-decoration:none; border-radius:4px;">
                    📝 Viaje de Cta Cte
                </a>
                <a href="carga_viajes_efectivo.php" class="btn btn-success" style="padding:8px 16px; background:#198754; color:white; text-decoration:none; border-radius:4px;">
                    💰 Viaje Efectivo
                </a>
            </div>

            <form method="POST" class="form-2cols" onsubmit="return validarFormulario()">
                <input type="hidden" name="id" value="<?= $viaje['id'] ?? '' ?>">

                <!-- ================= COLUMNA IZQUIERDA ================= -->
                <div class="col">

                    <!-- 🔴 EMPRESA -->
                    <div class="form-group">
                        <label>🏢 Empresa</label>
                        <select name="cc" id="cc" required>
                            <option value="">-- Seleccione Empresa --</option>
                            <?php foreach ($empresas as $empresa): ?>
                                <option value="<?= $empresa['id'] ?>" <?= (($viaje['cc'] ?? '') == $empresa['id']) ? 'selected' : '' ?>>
                                    <?= $empresa['id_empresa'] ?> - <?= htmlspecialchars($empresa['razon_social']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- 🔴 CENTRO DE COSTO -->
                    <div class="form-group" id="contenedor_cc" style="display: <?= !empty($viaje['id_cc']) ? 'block' : 'none' ?>;">
                        <label>📍 Centro de Costo</label>
                        <select name="id_cc" id="id_cc">
                            <?php if (!empty($viaje['id_cc'])): ?>
                                <option value="<?= $viaje['id_cc'] ?>" selected>Cargando centro guardado...</option>
                            <?php else: ?>
                                <option value="">Seleccione Centro de Costo</option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <!-- 🔴 AUTORIZANTE -->
                    <div class="form-group" id="contenedor_autorizante" style="display: <?= !empty($viaje['id_autorizante']) ? 'block' : 'none' ?>;">
                        <label>👤 Autorizante</label>
                        <select name="id_autorizante" id="id_autorizante">
                            <?php if (!empty($viaje['id_autorizante'])): ?>
                                <option value="<?= $viaje['id_autorizante'] ?>" selected>Cargando autorizante guardado...</option>
                            <?php else: ?>
                                <option value="">Seleccione Autorizante</option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>👤 Nombre del Pasajero</label>
                        <input type="text" name="nombre_pasaj" id="nombre_pasaj" value="<?= htmlspecialchars($viaje['nombre_pasaj'] ?? '') ?>" required>
                    </div>

                    <div class="form-group">
                        <label>📱 Celular del Pasajero</label>
                        <input type="text" name="cel_pasaj" id="cel_pasaj" value="<?= htmlspecialchars($viaje['cel_pasaj'] ?? '') ?>" required>
                    </div>

                    <div class="form-group">
                        <label>📝 Observaciones (las ve solo el Chofer)</label>
                        <textarea name="obs_pasaj" rows="3"><?= $viaje['obs_pasaj'] ?? '' ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>📝 Observaciones del viaje (las ve solo el Operador)</label>
                        <textarea name="obs_operador" rows="3"><?= $viaje['obs_operador'] ?? '' ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>📌 Estado / Modalidad del Viaje</label>
                        <div class="grupo-botones-estado">
                            <button type="button" id="btn_inmediato" class="btn-switch" onclick="seleccionarEstado('Pendiente')">
                                ⚡ Pendiente
                            </button>
                            <button type="button" id="btn_diferido" class="btn-switch" onclick="seleccionarEstado('Diferido')">
                                📅 Diferido
                            </button>
                        </div>
                        <input type="hidden" name="estado" id="estado_oculto" value="<?= $viaje['estado'] ?? 'Pendiente' ?>">
                    </div>

                </div>

                <!-- ================= COLUMNA DERECHA ================= -->
                <div class="col">

                    <!-- CAMPO OCULTO PARA EL MÓVIL (se obtiene del viaje) -->
                    <input type="hidden" name="movil_asignado" id="movil_asignado" value="<?= $movil_asignado ?>">

                    <div class="form-group">
                        <label>📍 Origen</label>
                        <div class="input-mapa">
                            <input type="text" id="dir_origen" name="direccion_origen" value="<?= htmlspecialchars($viaje['direccion_origen'] ?? '') ?>" onkeyup="autocomplete(this)" required>
                            <button type="button" class="btn-map btn-origen" onclick="verMapa('dir_origen')">📍 ORIGEN</button>
                        </div>
                        <div id="dir_origen_list" class="autocomplete-box"></div>
                        <input type="hidden" name="origen_lat" id="dir_origen_lat" value="<?= $viaje['origen_lat'] ?? '' ?>">
                        <input type="hidden" name="origen_lng" id="dir_origen_lng" value="<?= $viaje['origen_lng'] ?? '' ?>">
                    </div>

                    <div class="form-group">
                        <label>📍 Destino</label>
                        <div class="input-mapa">
                            <input type="text" id="dir_destino" name="direccion_destino" value="<?= htmlspecialchars($viaje['direccion_destino'] ?? '') ?>" onkeyup="autocomplete(this)">
                            <div style="display:flex; gap:5px; flex-wrap:wrap;">
                                <button type="button" class="btn-map btn-destino" onclick="verMapa('dir_destino')">🟢 DESTINO</button>
                                <button type="button" class="btn-map btn-recorrido" onclick="verRecorrido()">➡️ RECORRIDO</button>
                            </div>
                        </div>
                        <div id="dir_destino_list" class="autocomplete-box"></div>
                        <input type="hidden" name="destino_lat" id="dir_destino_lat" value="<?= $viaje['destino_lat'] ?? '' ?>">
                        <input type="hidden" name="destino_lng" id="dir_destino_lng" value="<?= $viaje['destino_lng'] ?? '' ?>">
                    </div>

                    <!-- RESULTADO DEL RECORRIDO (DISTANCIA Y TIEMPO) -->
                    <div class="resultado-recorrido" id="resultado_recorrido">
                        <div class="item">
                            <span class="icono">📏</span>
                            <div>
                                <div class="etiqueta">Distancia</div>
                                <span class="valor" id="resultado_distancia"><?= $viaje['distancia'] ?? '0' ?> km</span>
                            </div>
                        </div>
                        <div class="item">
                            <span class="icono">⏱️</span>
                            <div>
                                <div class="etiqueta">Tiempo estimado</div>
                                <span class="valor" id="resultado_tiempo"><?= $viaje['tiempo'] ?? '0' ?> min</span>
                            </div>
                        </div>
                    </div>

                    <!-- CAMPOS OCULTOS PARA GUARDAR DISTANCIA Y TIEMPO -->
                    <input type="hidden" name="distancia" id="distancia_recorrido" value="<?= $viaje['distancia'] ?? '' ?>">
                    <input type="hidden" name="tiempo" id="tiempo_recorrido" value="<?= $viaje['tiempo'] ?? '' ?>">

                    <!-- BOTÓN GUARDAR RECORRIDO -->
                    <div class="form-group">
                        <button type="button" id="btnGuardarRecorrido" class="btn-guardar-recorrido" onclick="guardarRecorrido()" disabled>
                            💾 Guardar Recorrido
                        </button>
                        <div id="mensajeRecorrido" class="mensaje-recorrido"></div>
                    </div>

                    <!-- RECUADRO DE RECORRIDO GUARDADO -->
                    <div id="recorridoGuardado" class="recorrido-guardado-box">
                        <div class="header">
                            <span class="titulo">✅ Recorrido guardado</span>
                            <span class="fecha" id="fechaRecorrido"></span>
                        </div>
                        <div class="datos">
                            <div class="item">
                                <span class="label">📏 Distancia:</span>
                                <span class="valor" id="recorridoDistancia">0 km</span>
                            </div>
                            <div class="item">
                                <span class="label">⏱️ Tiempo:</span>
                                <span class="valor" id="recorridoTiempo">0 min</span>
                            </div>
                            <div class="item">
                                <span class="label">🚗 Móvil:</span>
                                <span class="valor" id="recorridoMovil">-</span>
                            </div>
                        </div>
                    </div>

                    <div class="fecha-hora" id="contenedor_fecha_hora">
                        <input type="date" name="fecha" id="fecha" value="<?= $viaje['fecha'] ?? date('Y-m-d') ?>">
                        <input type="time" name="hora" id="hora" value="<?= isset($viaje['hora']) ? substr($viaje['hora'], 0, 5) : date('H:i') ?>">
                    </div>

                    <div class="form-group">
                        <label>🚗 Categoría de Móvil</label>
                        <div class="grid-categorias">
                            <div class="tarjeta-categoria" data-categoria="REMIS" onclick="seleccionarCategoria('REMIS')">
                                <img src="../../../img/sedan.png" alt="Sedán" onerror="this.style.display='none'">
                                <span>Sedán</span>
                            </div>

                            <div class="tarjeta-categoria" data-categoria="TAXI" onclick="seleccionarCategoria('TAXI')">
                                <img src="../../../img/taxi.png" alt="Taxi" onerror="this.style.display='none'">
                                <span>Taxi</span>
                            </div>

                            <div class="tarjeta-categoria" data-categoria="VAN" onclick="seleccionarCategoria('VAN')">
                                <img src="../../../img/van.png" alt="Van" onerror="this.style.display='none'">
                                <span>Van</span>
                            </div>

                            <div class="tarjeta-categoria" data-categoria="UTILITARIO" onclick="seleccionarCategoria('UTILITARIO')">
                                <img src="../../../img/utilitario.png" alt="Utilitario" onerror="this.style.display='none'">
                                <span>Utilitario</span>
                            </div>
                        </div>

                        <input type="hidden" name="categoria_movil" id="categoria_movil_oculto" value="<?= $viaje['categoria_movil'] ?? '' ?>" required>
                    </div>

                    <div class="form-full acciones-form">
                        <button type="submit" name="guardar" class="btn-guardar">💾 Guardar Viaje</button>
                        <a href="lista_viajes.php" class="btn-volver">↩ Listado de viajes</a>
                        <a href="../../inicio_0.php" class="btn-volver">↩ Salir</a>
                    </div>

                </div>
            </form>

        </div>
    </div>

    <!-- MODAL MAPA -->
    <div id="mapModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.7);z-index:9999;">
        <div style="width:85%;height:85%;margin:3% auto;background:white;padding:15px;border-radius:10px;box-shadow:0 5px 30px rgba(0,0,0,0.5);">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                <h3 style="margin:0;">🗺️ Mapa</h3>
                <button onclick="cerrarMapa()" style="padding:8px 20px; background:#dc3545; color:white; border:none; border-radius:5px; cursor:pointer; font-weight:bold;">Cerrar</button>
            </div>
            <div id="map" style="width:100%;height:90%;border-radius:5px;"></div>
        </div>
    </div>

</body>

</html>