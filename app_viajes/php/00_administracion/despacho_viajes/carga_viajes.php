<?php
include_once "../../../funciones/funciones.php";
protegerPagina([0, 3]);

if (isset($_POST['guardar'])) {
    guardarViaje($_POST);
    header("Location: lista_viajes.php");
    exit;
}

$viaje = null;
if (isset($_GET['editar'])) {
    $viaje = obtenerViajePorId((int)$_GET['editar']);
}

$empresas = obtenerEmpresas();
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
    <script src="listado_viajes.js" defer></script>

    <style>
        /* 🎨 ESTILOS PARA LOS BOTONES DE ESTADO (MODALIDAD) */
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

        /* 🚗 🚀 ESTILOS COMPACTOS Y ALINEADOS EN FILA PARA CATEGORÍAS */
        .grid-categorias {
            display: flex;
            /* Cambiado a flex para alinearlos uno al lado del otro */
            gap: 8px;
            /* Espaciado más corto entre tarjetas */
            margin-top: 5px;
            flex-wrap: nowrap;
            /* Fuerza a que se mantengan siempre en la misma línea */
        }

        .tarjeta-categoria {
            flex: 1;
            max-width: 105px;
            /* Limita el ancho máximo para hacer el recuadro más chico */
            border: 2px solid #ddd;
            border-radius: 6px;
            padding: 5px 4px;
            /* Reducido al mínimo para achicar el recuadro */
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
            /* Conserva exactamente el tamaño de tu imagen */
            height: 55px;
            object-fit: contain;
            margin-bottom: 2px;
            /* Pegamos un poco más el texto a la imagen */
        }

        .tarjeta-categoria span {
            font-weight: bold;
            font-size: 11px;
            /* Texto sutilmente más chico para acompañar la caja */
            color: #495057;
            text-transform: uppercase;
        }

        /* Estado Seleccionado / Activo */
        .tarjeta-categoria.activa {
            border-color: #0d6efd;
            background-color: #e7f1ff;
            box-shadow: 0 0 6px rgba(13, 110, 253, 0.4);
        }

        .tarjeta-categoria.activa span {
            color: #0d6efd;
        }
    </style>

    <script>
        const editandoViaje = <?= isset($viaje['id']) ? 'true' : 'false' ?>;

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

            inputEstado.value = estado;

            if (estado === 'Diferido') {
                btnDiferido.classList.add('activo-diferido');
                btnInmediato.classList.remove('activo-inmediato');
                contenedorFechaHora.style.display = 'flex';
                if (fecha && hora) {
                    fecha.readOnly = false;
                    hora.readOnly = false;
                }
            } else {
                btnInmediato.classList.add('activo-inmediato');
                btnDiferido.classList.remove('activo-diferido');
                contenedorFechaHora.style.display = 'none';
                if (!editandoViaje) {
                    fechaActual();
                }
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

        document.addEventListener("DOMContentLoaded", function() {
            const inputEstado = document.getElementById("estado_oculto");
            if (inputEstado) {
                seleccionarEstado(inputEstado.value);
            }

            const inputCategoria = document.getElementById("categoria_movil_oculto");
            if (inputCategoria && inputCategoria.value) {
                seleccionarCategoria(inputCategoria.value);
            }
        });

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
    </script>
</head>

<body>

    <div class="container">
        <div class="card">

            <h3><?= $viaje ? "Editar Viaje" : "Nuevo Viaje de Cuenta corriente"; ?></h3>

            <div style="text-align:left; margin-bottom:15px;">
                <a href="carga_viajes.php" class="btn btn-primary" style="margin-right:10px;">
                    Viaje de Cta Cte
                </a>
                <a href="carga_viajes_efectivo.php" class="btn btn-success">
                    Viaje Efectivo
                </a>
            </div>

            <form method="POST" class="form-2cols">
                <input type="hidden" name="id" value="<?= $viaje['id'] ?? '' ?>">

                <div class="col">
                    <div class="form-group">
                        <label>Empresa</label>
                        <select name="cc" id="cc" required>
                            <option value="">-- Seleccione Empresa --</option>
                            <?php foreach ($empresas as $empresa): ?>
                                <option value="<?= $empresa['id'] ?>" <?= (($viaje['cc'] ?? '') == $empresa['id']) ? 'selected' : '' ?>>
                                    <?= $empresa['id_empresa'] ?> - <?= htmlspecialchars($empresa['razon_social']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group" id="contenedor_cc" style="display: <?= !empty($viaje['id_cc']) ? 'block' : 'none' ?>;">
                        <label>Centro de Costo</label>
                        <select name="id_cc" id="id_cc">
                            <?php if (!empty($viaje['id_cc'])): ?>
                                <option value="<?= $viaje['id_cc'] ?>" selected>Cargando centro guardado...</option>
                            <?php else: ?>
                                <option value="">Seleccione Centro de Costo</option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="form-group" id="contenedor_autorizante" style="display: <?= !empty($viaje['id_autorizante']) ? 'block' : 'none' ?>;">
                        <label>Autorizante</label>
                        <select name="id_autorizante" id="id_autorizante">
                            <?php if (!empty($viaje['id_autorizante'])): ?>
                                <option value="<?= $viaje['id_autorizante'] ?>" selected>Cargando autorizante guardado...</option>
                            <?php else: ?>
                                <option value="">Seleccione Autorizante</option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Nombre del Pasajero</label>
                        <input type="text" name="nombre_pasaj" id="nombre_pasaj" value="<?= htmlspecialchars($viaje['nombre_pasaj'] ?? '') ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Celular del Pasajero</label>
                        <input type="text" name="cel_pasaj" id="cel_pasaj" value="<?= htmlspecialchars($viaje['cel_pasaj'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Observaciones Operador</label>
                        <textarea name="obs_operador" rows="3"><?= $viaje['obs_operador'] ?? '' ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>Observaciones Pasajero, datos del viaje</label>
                        <textarea name="obs_pasaj" rows="3"><?= $viaje['obs_pasaj'] ?? '' ?></textarea>
                    </div>
                </div>

                <div class="col">
                    <div class="form-group">
                        <label>Estado / Modalidad del Viaje</label>
                        <div class="grupo-botones-estado">
                            <button type="button" id="btn_inmediato" class="btn-switch" onclick="seleccionarEstado('Inmediato')">
                                ⚡ Inmediato
                            </button>
                            <button type="button" id="btn_diferido" class="btn-switch" onclick="seleccionarEstado('Diferido')">
                                📅 Diferido
                            </button>
                        </div>
                        <input type="hidden" name="estado" id="estado_oculto" value="<?= $viaje['estado'] ?? 'Inmediato' ?>">
                    </div>

                    <div class="form-group">
                        <label>Origen</label>
                        <div class="input-mapa">
                            <input type="text" id="dir_origen" name="direccion_origen" value="<?= htmlspecialchars($viaje['direccion_origen'] ?? '') ?>" onkeyup="autocomplete(this)">
                            <button type="button" class="btn-map btn-origen" onclick="verMapa('dir_origen')">📍ORIGEN</button>
                        </div>
                        <div id="dir_origen_list" class="autocomplete-box"></div>
                        <input type="hidden" name="origen_lat" id="dir_origen_lat" value="<?= $viaje['origen_lat'] ?? '' ?>">
                        <input type="hidden" name="origen_lng" id="dir_origen_lng" value="<?= $viaje['origen_lng'] ?? '' ?>">
                    </div>

                    <div class="form-group">
                        <label>Destino</label>
                        <div class="input-mapa">
                            <input type="text" id="dir_destino" name="direccion_destino" value="<?= htmlspecialchars($viaje['direccion_destino'] ?? '') ?>" onkeyup="autocomplete(this)">
                            <div style="display:flex; gap:5px;">
                                <button type="button" class="btn-map btn-destino" onclick="verMapa('dir_destino')">🟢 DESTINO</button>
                                <button type="button" class="btn-map btn-recorrido" onclick="verRecorrido(document.getElementById('dir_origen').value, document.getElementById('dir_destino').value)">➡️ RECORRIDO</button>
                            </div>
                        </div>
                        <div id="dir_destino_list" class="autocomplete-box"></div>
                        <input type="hidden" name="destino_lat" id="dir_destino_lat" value="<?= $viaje['destino_lat'] ?? '' ?>">
                        <input type="hidden" name="destino_lng" id="dir_destino_lng" value="<?= $viaje['destino_lng'] ?? '' ?>">
                    </div>

                    <div class="fecha-hora" id="contenedor_fecha_hora">
                        <input type="date" name="fecha" id="fecha" value="<?= $viaje['fecha'] ?? date('Y-m-d') ?>">
                        <input type="time" name="hora" id="hora" value="<?= isset($viaje['hora']) ? substr($viaje['hora'], 0, 5) : date('H:i') ?>">
                    </div>

                    <div class="form-group">
                        <label>Categoría de Móvil</label>
                        <div class="grid-categorias">
                            <div class="tarjeta-categoria" data-categoria="REMIS" onclick="seleccionarCategoria('REMIS')">
                                <img src="../../../img/sedan.png" alt="Sedán">
                                <span>Sedán</span>
                            </div>

                            <div class="tarjeta-categoria" data-categoria="TAXI" onclick="seleccionarCategoria('TAXI')">
                                <img src="../../../img/taxi.png" alt="Taxi">
                                <span>Taxi</span>
                            </div>

                            <div class="tarjeta-categoria" data-categoria="VAN" onclick="seleccionarCategoria('VAN')">
                                <img src="../../../img/van.png" alt="Van">
                                <span>Van</span>
                            </div>

                            <div class="tarjeta-categoria" data-categoria="UTILITARIO" onclick="seleccionarCategoria('UTILITARIO')">
                                <img src="../../../img/utilitario.png" alt="Utilitario">
                                <span>Utilitario</span>
                            </div>
                        </div>

                        <input type="hidden" name="categoria_movil" id="categoria_movil_oculto" value="<?= $viaje['categoria_movil'] ?? '' ?>" required>
                    </div>
                    <!-- 
                    <div id="bloque_tarifa" style="display:flex; gap:10px; align-items:center; margin-top:10px;">
                        <button type="button" class="btn-map btn-tarifa" style="min-width:200px;" onclick="calcularTarifa()">💲 CALCULAR VIAJE</button>
                        <input type="text" id="tarifa_resultado" placeholder="Importe $" readonly style="flex:1;">
                    </div>
                </div>
                            -->
                    <div class="form-full acciones-form">
                        <button type="submit" name="guardar" class="btn-guardar">💾 Guardar Viaje</button>
                        <a href="lista_viajes.php" class="btn-volver">↩ Listado de viajes</a>
                        <a href="../../inicio_0.php" class="btn-volver">↩ Salir</a>
                    </div>

            </form>

        </div>
    </div>

    <div id="mapModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:#000000aa;z-index:9999;">
        <div style="width:80%;height:80%;margin:5% auto;background:white;padding:10px;border-radius:8px;">
            <button onclick="cerrarMapa()" style="float:right; margin-bottom:10px;">Cerrar</button>
            <div id="map" style="width:100%;height:90%;"></div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            let empresaPrevia = document.getElementById('cc').value;
            let ccPrevio = "<?= $viaje['id_cc'] ?? '' ?>";
            let autPrevio = "<?= $viaje['id_autorizante'] ?? '' ?>";

            if (empresaPrevia) {
                cargarCentros(empresaPrevia, ccPrevio, autPrevio);
                cargarAutorizantes(ccPrevio, empresaPrevia, autPrevio);
            }
        });

        document.getElementById('cc').addEventListener('change', function() {
            let empresa = this.value;
            cargarCentros(empresa, null, null);
            cargarAutorizantes(null, empresa, null);
        });

        function cargarCentros(empresa, ccPreseleccionado, autPreseleccionado) {
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
                    return response.text();
                })
                .then(texto => {
                    try {
                        let datos = JSON.parse(texto);

                        if (datos.error) {
                            comboCC.innerHTML = '<option value="">Error SQL: ' + datos.error + '</option>';
                            return;
                        }

                        comboCC.innerHTML = '<option value="">-- Seleccione Centro de Costo (Opcional) --</option>';
                        if (Array.isArray(datos)) {
                            if (datos.length === 0) {
                                comboCC.innerHTML = '<option value="">La empresa no registra centros</option>';
                                return;
                            }
                            datos.forEach(cc => {
                                let esSelected = (ccPreseleccionado == cc.id) ? 'selected' : '';
                                let codigoCentro = (cc.centro_de_costo !== undefined && cc.centro_de_costo !== null) ? cc.centro_de_costo + ' - ' : '';
                                let nombreCentro = cc.nombre || '';

                                comboCC.innerHTML += '<option value="' + cc.id + '" ' + esSelected + '>' + codigoCentro + nombreCentro + '</option>';
                            });
                        } else {
                            comboCC.innerHTML = '<option value="">Error: Datos corruptos</option>';
                        }
                    } catch (e) {
                        console.error("Respuesta inválida de centros:", texto);
                        comboCC.innerHTML = '<option value="">Error al cargar centros</option>';
                    }
                })
                .catch(error => {
                    comboCC.innerHTML = '<option value="">Error de Red</option>';
                });
        }

        document.getElementById('id_cc').addEventListener('change', function() {
            let id_cc = this.value;
            let empresa = document.getElementById('cc').value;
            cargarAutorizantes(id_cc, empresa, null);
        });

        function cargarAutorizantes(id_cc, empresa, autPreseleccionado) {
            let contenedorAut = document.getElementById('contenedor_autorizante');
            let comboAut = document.getElementById('id_autorizante');

            if (!empresa) {
                contenedorAut.style.display = 'none';
                comboAut.innerHTML = '<option value="">Seleccione Autorizante</option>';
                return;
            }

            contenedorAut.style.display = 'block';
            comboAut.innerHTML = '<option value="">Cargando autorizantes...</option>';

            fetch('obtener_autorizantes.php?id_cc=' + (id_cc || '') + '&id_empresa=' + empresa)
                .then(response => {
                    if (!response.ok) throw new Error('HTTP Status ' + response.status);
                    return response.text();
                })
                .then(texto => {
                    try {
                        let datos = JSON.parse(texto);

                        if (datos.error) {
                            comboAut.innerHTML = '<option value="">Error SQL: ' + datos.error + '</option>';
                            return;
                        }

                        comboAut.innerHTML = '<option value="">-- Seleccione Autorizante --</option>';
                        if (Array.isArray(datos)) {
                            if (datos.length === 0) {
                                comboAut.innerHTML = '<option value="">No hay autorizantes activos</option>';
                                return;
                            }

                            window.autorizantesCargados = datos;

                            datos.forEach(a => {
                                let esSelected = (autPreseleccionado == a.id) ? 'selected' : '';
                                let tel = a.celular ? ' - ' + formatearCelular(a.celular) : '';
                                comboAut.innerHTML += '<option value="' + a.id + '" ' + esSelected + '>' + a.nombre + tel + '</option>';
                            });
                        } else {
                            comboAut.innerHTML = '<option value="">Error: Datos corruptos</option>';
                        }
                    } catch (e) {
                        console.error("Respuesta inválida de autorizantes:", texto);
                        comboAut.innerHTML = '<option value="">Error al cargar autorizantes</option>';
                    }
                })
                .catch(error => {
                    comboAut.innerHTML = '<option value="">Error de Red</option>';
                });
        }

        document.getElementById('id_autorizante').addEventListener('change', function() {
            let idSeleccionado = this.value;
            if (idSeleccionado && window.autorizantesCargados) {
                let autorizante = window.autorizantesCargados.find(a => a.id == idSeleccionado);
                if (autorizante) {
                    document.getElementById('nombre_pasaj').value = autorizante.nombre;
                    document.getElementById('cel_pasaj').value = autorizante.celular || '';

                    if (autorizante.id_cc && !document.getElementById('id_cc').value) {
                        document.getElementById('id_cc').value = autorizante.id_cc;
                    }
                }
            }
        });
    </script>
</body>

</html>