<?php
include_once "../../../funciones/funciones.php";
protegerPagina([0, 3]);

// ACCIONES
if (isset($_POST['guardar'])) {
    guardarViaje($_POST);
    header("Location: listado_viajes.php");
    exit;
}

if (isset($_GET['borrar'])) {
    borrarViaje((int)$_GET['borrar']);
    header("Location: listado_viajes.php");
    exit;
}

$viaje = null;
if (isset($_GET['editar'])) {
    $viaje = obtenerViajePorId((int)$_GET['editar']);
}

$viajes = obtenerViajes();
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
    <script src="../../../js/listado_viajes.js"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const select = document.getElementById("diferido");
            const campos = document.getElementById("campos_diferido");

            function toggleCampos() {
                if (select.value === "Si") {
                    campos.style.display = "block";
                } else {
                    campos.style.display = "none";
                }
            }

            // Ejecutar al cargar (por si viene editando)
            toggleCampos();

            // Ejecutar al cambiar
            select.addEventListener("change", toggleCampos);
        });
    </script>


    <script>
        let map;
        let markers = [];
        let line;

        function limpiarMapa() {
            markers.forEach(m => map.removeLayer(m));
            markers = [];
            if (line) map.removeLayer(line);
        }

        function verMapa(direccion) {
            document.getElementById("mapModal").style.display = "block";

            fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${direccion+', Buenos Aires, Argentina'}`)
                .then(r => r.json())
                .then(data => {
                    if (!data.length) {
                        alert("Dirección no encontrada");
                        return;
                    }

                    let lat = data[0].lat;
                    let lon = data[0].lon;

                    if (!map) {
                        map = L.map('map').setView([lat, lon], 15);
                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
                    } else {
                        map.setView([lat, lon], 15);
                    }

                    limpiarMapa();

                    let m = L.marker([lat, lon]).addTo(map).bindPopup(direccion).openPopup();
                    markers.push(m);
                });
        }

        function verRecorrido(origen, destino) {
            document.getElementById("mapModal").style.display = "block";

            Promise.all([
                    fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${origen+', Buenos Aires, Argentina'}`).then(r => r.json()),
                    fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${destino+', Buenos Aires, Argentina'}`).then(r => r.json())
                ])
                .then(([o, d]) => {
                    if (!o.length || !d.length) {
                        alert("Dirección no encontrada");
                        return;
                    }

                    let lat1 = o[0].lat,
                        lon1 = o[0].lon;
                    let lat2 = d[0].lat,
                        lon2 = d[0].lon;

                    if (!map) {
                        map = L.map('map').setView([lat1, lon1], 13);
                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
                    }

                    limpiarMapa();

                    let m1 = L.marker([lat1, lon1]).addTo(map).bindPopup("Origen");
                    let m2 = L.marker([lat2, lon2]).addTo(map).bindPopup("Destino");
                    markers.push(m1, m2);

                    let rutas = [];

                    fetch(`https://router.project-osrm.org/route/v1/driving/${lon1},${lat1};${lon2},${lat2}?overview=full&geometries=geojson&alternatives=true`)
                        .then(r => r.json())
                        .then(data => {

                            if (!data.routes.length) {
                                alert("Sin ruta");
                                return;
                            }

                            // limpiar rutas anteriores
                            rutas.forEach(r => map.removeLayer(r));
                            rutas = [];

                            data.routes.forEach((route, index) => {

                                let layer = L.geoJSON(route.geometry, {
                                    style: {
                                        color: index === 0 ? 'blue' : 'gray',
                                        weight: index === 0 ? 6 : 4,
                                        opacity: index === 0 ? 1 : 0.5
                                    }
                                }).addTo(map);

                                // CLICK para seleccionar ruta
                                layer.on('click', function() {

                                    rutas.forEach(r => r.setStyle({
                                        color: 'gray',
                                        weight: 4,
                                        opacity: 0.5
                                    }));

                                    this.setStyle({
                                        color: 'red',
                                        weight: 6,
                                        opacity: 1
                                    });

                                    console.log("Ruta seleccionada:", route);
                                });

                                rutas.push(layer);
                            });

                            map.fitBounds(rutas[0].getBounds());
                        });
                });
        }

        function cerrarMapa() {
            document.getElementById("mapModal").style.display = "none";
        }
    </script>
</head>

<body>

    <div class="container">
        <div class="card operadores-layout">

            <!-- FORMULARIO COMPLETO -->
            <div class="col-form">
                <h3><?= $viaje ? "Editar Despacho" : "Nuevo Viaje"; ?></h3>

                <form method="POST">
                    <input type="hidden" name="id" value="<?= $viaje['id'] ?? ''; ?>">

                    <div class="form-group">
                        <label>Celular Pasajero</label>
                        <input type="number" name="cel_pasaj" value="<?= htmlspecialchars($viaje['cel_pasaj'] ?? '') ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Nombre Pasajero</label>
                        <input type="text" name="nombre_pasaj" value="<?= htmlspecialchars($viaje['nombre_pasaj'] ?? '') ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Dirección Origen</label>
                        <div style="display:flex; gap:5px;">
                            <input type="text" id="dir_origen" name="direccion_origen"
                                value="<?= htmlspecialchars($viaje['direccion_origen'] ?? '') ?>" required>

                            <button type="button" class="btn-map btn-origen"
                                onclick="verMapa(document.getElementById('dir_origen').value)">
                                O
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Dirección Destino</label>
                        <div style="display:flex; gap:5px;">
                            <input type="text" id="dir_destino" name="direccion_destino"
                                value="<?= htmlspecialchars($viaje['direccion_destino'] ?? '') ?>">

                            <!-- Ver destino -->
                            <button type="button" class="btn-map btn-destino"
                                title="Ver destino"
                                onclick="verMapa(document.getElementById('dir_destino').value)">
                                D
                            </button>

                            <!-- Ver recorrido -->
                            <button type="button" class="btn-map btn-recorrido"
                                title="Ver recorrido"
                                onclick="verRecorrido(
                document.getElementById('dir_origen').value,
                document.getElementById('dir_destino').value
            )">
                                R
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Observaciones Operador</label>
                        <textarea name="obs_operador"><?= htmlspecialchars($viaje['obs_operador'] ?? '') ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>Observaciones Pasajero</label>
                        <textarea name="obs_pasaj"><?= htmlspecialchars($viaje['obs_pasaj'] ?? '') ?></textarea>
                    </div>

                    <!--

                    <div class="form-group">
                        <label>Tipo de Viaje</label>
                        <select name="diferido">
                            <option value="No" <?= (isset($viaje['diferido']) && $viaje['diferido'] == "No") ? 'selected' : ''; ?>>Inmediato</option>
                            <option value="Si" <?= (isset($viaje['diferido']) && $viaje['diferido'] == "Si") ? 'selected' : ''; ?>>Diferido</option>
                        </select>
                    </div>
-->
                    <div class="form-group">
                        <label>Tipo de Viaje</label>
                        <select name="diferido" id="diferido">
                            <option value="No" <?= (isset($viaje['diferido']) && $viaje['diferido'] == "No") ? 'selected' : ''; ?>>Inmediato</option>
                            <option value="Si" <?= (isset($viaje['diferido']) && $viaje['diferido'] == "Si") ? 'selected' : ''; ?>>Diferido</option>
                        </select>
                    </div>

                    <div id="campos_diferido" style="display: none;">
                        <div class="form-group">
                            <label>Fecha</label>
                            <input type="date" name="fecha" value="<?= $viaje['fecha'] ?? '' ?>">
                        </div>

                        <div class="form-group">
                            <label>Hora</label>
                            <input type="time" name="hora" value="<?= $viaje['hora'] ?? '' ?>">
                        </div>
                    </div>



                    <div class="form-group">
                        <label>Categoría</label>
                        <select name="categoria_movil">
                            <option value="REMIS" <?= (isset($viaje['categoria_movil']) && $viaje['categoria_movil'] == "REMIS") ? 'selected' : ''; ?>>Remis</option>
                            <option value="TAXI" <?= (isset($viaje['categoria_movil']) && $viaje['categoria_movil'] == "TAXI") ? 'selected' : ''; ?>>Taxi</option>
                        </select>
                    </div>

                    <div class="actions">
                        <button type="submit" name="guardar">Guardar</button>
                        <a href="../../inicio_0.php">Salir</a>
                    </div>

                </form>
            </div>

            <!-- TABLA -->
            <div class="col-tabla">
                <h3>Listado de Viajes Activos</h3>

                <div><strong>Referencias:</strong> 🔵 Origen | 🟢 Destino | 🔴 Recorrido</div>

                <table class="table">
                    <thead>
                        <tr>
                            <th class="col-id">ID</th>
                            <th class="col-pasajero">Pasajero</th>
                            <th class="col-direccion">Origen</th>
                            <th class="col-direccion">Destino</th>
                            <th class="col-cat">Categoría</th>
                            <th class="col-tipo">Tipo</th>
                            <th class="col-tipo">Fecha</th>
                            <th class="col-tipo">Hora</th>
                            <th class="col-acciones">Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (empty($viajes)): ?>
                            <tr>
                                <td colspan="8">No hay viajes</td>
                            </tr>
                            <?php else: foreach ($viajes as $v): ?>

                                <tr style="<?= $v['diferido'] == "Si" ? 'background:#d4edda;' : '' ?>">

                                    <td class="col-id"><?= $v['id'] ?></td>

                                    <td class="col-pasajero">
                                        <strong><?= htmlspecialchars($v['nombre_pasaj']) ?></strong><br>
                                        <small><?= htmlspecialchars($v['cel_pasaj']) ?></small>
                                    </td>

                                    <td class="col-direccion">
                                        <?= htmlspecialchars($v['direccion_origen']) ?>
                                    </td>

                                    <td class="col-direccion">
                                        <?= htmlspecialchars($v['direccion_destino']) ?>
                                    </td>

                                    <td class="col-cat"><?= $v['categoria_movil'] ?></td>
                                    <td class="col-tipo"><?= $v['diferido'] ?></td>

                                    <td class="col-tipo"><?= $v['fecha'] ?></td>
                                    <td class="col-tipo"><?= $v['hora'] ?></td>



                                    <td class="col-acciones">
                                        <a href="?editar=<?= $v['id'] ?>">Editar</a>
                                        <a href="?borrar=<?= $v['id'] ?>" onclick="return confirm('¿Eliminar?')">Borrar</a>
                                    </td>

                                </tr>

                        <?php endforeach;
                        endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>

    <!-- MAPA -->
    <div id="mapModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:#000000aa;">
        <div style="width:80%;height:80%;margin:5% auto;background:white;">
            <button onclick="cerrarMapa()">Cerrar</button>
            <div id="map" style="width:100%;height:90%;"></div>
        </div>
    </div>

</body>

</html>