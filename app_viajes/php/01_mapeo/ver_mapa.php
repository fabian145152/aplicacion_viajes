<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Mapa GPS</title>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px;
            background: #f5f7fa;
        }

        h2 {
            color: #2c3e50;
            margin-bottom: 15px;
        }

        #map {
            height: 600px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .topbar {
            margin-bottom: 15px;
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
            background: white;
            padding: 12px 20px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .topbar select,
        .topbar button {
            padding: 8px 16px;
            border-radius: 8px;
            border: 1px solid #ddd;
            font-size: 14px;
            background: white;
            transition: all 0.3s;
        }

        .topbar select {
            min-width: 150px;
            cursor: pointer;
        }

        .topbar select:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        }

        .topbar button {
            border: none;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #007bff;
            color: white;
        }

        .btn-primary:hover {
            background: #0056b3;
            transform: translateY(-1px);
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-success:hover {
            background: #1e7e34;
            transform: translateY(-1px);
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
            transform: translateY(-1px);
        }

        .btn-outline {
            background: transparent;
            color: #333;
            border: 1px solid #ddd;
        }

        .btn-outline:hover {
            background: #f0f0f0;
        }

        .leyenda {
            margin-top: 15px;
            display: flex;
            gap: 25px;
            align-items: center;
            flex-wrap: wrap;
            background: white;
            padding: 10px 20px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .leyenda-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }

        .leyenda-color {
            width: 18px;
            height: 18px;
            border-radius: 50%;
            display: inline-block;
            border: 2px solid white;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.2);
        }

        .leyenda-color.verde {
            background: #28a745;
        }

        .leyenda-color.rojo {
            background: #dc3545;
        }

        .estado-badge {
            display: inline-block;
            padding: 3px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            color: white;
        }

        .estado-badge.activo {
            background: #28a745;
        }

        .estado-badge.inactivo {
            background: #dc3545;
        }

        .info-usuario {
            margin: 0;
            padding: 4px 12px;
            font-size: 14px;
            color: #555;
        }

        .info-usuario strong {
            color: #2c3e50;
        }

        .label-usuario {
            background: rgba(0, 0, 0, 0.8);
            color: white;
            border-radius: 8px;
            padding: 4px 12px;
            font-size: 13px;
            font-weight: bold;
            white-space: nowrap;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }

        .label-usuario.activo {
            border-left: 4px solid #28a745;
        }

        .label-usuario.inactivo {
            border-left: 4px solid #dc3545;
        }

        .ultima-actualizacion {
            font-size: 12px;
            color: #999;
            margin-left: auto;
        }

        .punto {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 4px;
        }

        .punto.verde {
            background: #28a745;
        }

        .punto.rojo {
            background: #dc3545;
        }

        .custom-marker {
            background: transparent;
            border: none;
        }

        .leaflet-popup-content {
            font-size: 13px;
        }

        @media (max-width: 768px) {
            .topbar {
                flex-direction: column;
                align-items: stretch;
            }

            .topbar select,
            .topbar button {
                width: 100%;
            }

            .leyenda {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
        }
    </style>
</head>

<body>

    <h2>📍 Mapa en tiempo real</h2>

    <div class="topbar">
        <select id="usuario"></select>
        <button id="btnTodos" class="btn-primary">👥 Ver todas</button>
        <button id="btnActualizar" class="btn-success">🔄 Actualizar</button>
        <span id="ultimaActualizacion" class="ultima-actualizacion"></span>
    </div>

    <div class="leyenda">
        <span class="leyenda-item">
            <span class="leyenda-color verde"></span> Activo
        </span>
        <span class="leyenda-item">
            <span class="leyenda-color rojo"></span> Inactivo
        </span>
        <span id="infoUsuario" class="info-usuario">
            👤 Selecciona un usuario
        </span>
    </div>

    <div id="map"></div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js">
    </script>

    <script>
        const map = L.map('map').setView([-34.60, -58.38], 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap'
        }).addTo(map);

        let markers = [];
        let polyline = null;
        let modoTodos = false;
        let usuariosData = {};
        let intervalId = null;

        // -------------------------
        function limpiarMapa() {
            if (polyline) {
                map.removeLayer(polyline);
                polyline = null;
            }

            markers.forEach(m => {
                try {
                    map.removeLayer(m);
                } catch (e) {}
            });
            markers = [];
        }

        // -------------------------
        function crearIcono(estado) {
            const color = estado === 'activo' ? '#28a745' : '#dc3545';
            const size = 34;

            // Si está inactivo, hacerlo un poco más pequeño y semi-transparente
            const opacity = estado === 'activo' ? 1 : 0.7;
            const pulse = estado === 'activo' ? 'box-shadow: 0 0 20px rgba(40,167,69,0.5);' : '';

            return L.divIcon({
                className: 'custom-marker',
                html: `
                    <div style="
                        width: ${size}px;
                        height: ${size}px;
                        background: ${color};
                        border-radius: 50%;
                        border: 3px solid white;
                        ${pulse}
                        opacity: ${opacity};
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        color: white;
                        font-size: 16px;
                        font-weight: bold;
                        transition: all 0.3s;
                    ">
                        ${estado === 'activo' ? '●' : '○'}
                    </div>
                `,
                iconSize: [size, size],
                iconAnchor: [size / 2, size / 2],
                popupAnchor: [0, -size / 2]
            });
        }

        // -------------------------
        async function cargarUsuarios() {
            try {
                const res = await fetch("obtener_usuarios.php");
                const data = await res.json();

                const select = document.getElementById("usuario");
                select.innerHTML = "";

                if (Array.isArray(data) && data.length > 0) {
                    data.forEach(u => {
                        let option = document.createElement("option");
                        option.value = u;
                        option.text = u;
                        select.appendChild(option);
                    });

                    select.value = data[0];
                    await cargarDatosUsuario(data[0]);
                    actualizar();
                } else {
                    select.innerHTML = '<option value="">No hay usuarios</option>';
                    document.getElementById('infoUsuario').textContent = '⚠️ No hay usuarios con ubicaciones';
                }

            } catch (e) {
                console.error("Error usuarios:", e);
                document.getElementById('infoUsuario').textContent = '❌ Error al cargar usuarios';
            }
        }

        // -------------------------
        async function cargarDatosUsuario(user) {
            try {
                const res = await fetch("obtener_recorrido.php?user_id=" + user);
                const data = await res.json();

                if (Array.isArray(data) && data.length > 0) {
                    usuariosData[user] = data;
                } else {
                    usuariosData[user] = [];
                }
                return data;
            } catch (e) {
                console.error("Error cargando datos:", e);
                usuariosData[user] = [];
                return null;
            }
        }

        // -------------------------
        function obtenerUltimoEstado(data) {
            if (!data || data.length === 0) return 'inactivo';
            const ultimo = data[data.length - 1];
            return ultimo.ultimo_status || ultimo.status || 'inactivo';
        }

        // -------------------------
        async function cargarIndividual() {
            let user = document.getElementById("usuario").value;
            if (!user) return;

            // Si no tenemos los datos del usuario, cargarlos
            if (!usuariosData[user]) {
                await cargarDatosUsuario(user);
            }

            const data = usuariosData[user];
            if (!data || data.length === 0) {
                limpiarMapa();
                document.getElementById('infoUsuario').innerHTML = `
                    👤 <strong>${user}</strong>
                    <span style="color:#999;">Sin datos de ubicación</span>
                `;
                return;
            }

            limpiarMapa();

            let coords = [];
            let ultimoStatus = 'inactivo';

            data.forEach(p => {
                let lat = parseFloat(p.lat);
                let lng = parseFloat(p.lng);

                if (!isNaN(lat) && !isNaN(lng) && lat !== 0 && lng !== 0) {
                    coords.push([lat, lng]);
                    if (p.ultimo_status) ultimoStatus = p.ultimo_status;
                }
            });

            if (coords.length === 0) {
                document.getElementById('infoUsuario').innerHTML = `
                    👤 <strong>${user}</strong>
                    <span style="color:#999;">Sin coordenadas válidas</span>
                `;
                return;
            }

            // Línea de recorrido
            const colorLinea = ultimoStatus === 'activo' ? '#28a745' : '#dc3545';
            polyline = L.polyline(coords, {
                color: colorLinea,
                weight: 3,
                opacity: 0.7,
                smoothFactor: 1
            }).addTo(map);

            // Último punto
            let last = coords[coords.length - 1];
            const icon = crearIcono(ultimoStatus);

            let m = L.marker(last, {
                    icon: icon
                })
                .addTo(map)
                .bindTooltip(`
                    <strong>${user}</strong><br>
                    Estado: <span style="color:${ultimoStatus === 'activo' ? '#28a745' : '#dc3545'}; font-weight:bold;">
                        ${ultimoStatus.toUpperCase()}
                    </span><br>
                    📍 ${coords.length} puntos
                `, {
                    permanent: true,
                    direction: 'top',
                    offset: [0, -25],
                    className: `label-usuario ${ultimoStatus}`
                });

            markers.push(m);

            // Centrar en el último punto
            map.setView(last, 16);

            // Actualizar info
            const estadoBadge = ultimoStatus === 'activo' ?
                '<span class="estado-badge activo">🟢 ACTIVO</span>' :
                '<span class="estado-badge inactivo">🔴 INACTIVO</span>';

            document.getElementById('infoUsuario').innerHTML = `
                👤 <strong>${user}</strong>
                ${estadoBadge}
                <span style="color:#999; font-size:12px; margin-left:10px;">
                    📍 ${coords.length} puntos
                </span>
            `;

            actualizarHora();
        }

        // -------------------------
        async function cargarTodos() {
            try {
                const resUsuarios = await fetch("obtener_usuarios.php");
                const usuarios = await resUsuarios.json();

                if (!Array.isArray(usuarios) || usuarios.length === 0) {
                    limpiarMapa();
                    document.getElementById('infoUsuario').innerHTML = '⚠️ No hay usuarios activos';
                    return;
                }

                limpiarMapa();

                let bounds = [];
                let infoHTML = '';
                let activos = 0;
                let inactivos = 0;

                for (const user of usuarios) {
                    if (!usuariosData[user]) {
                        await cargarDatosUsuario(user);
                    }

                    const data = usuariosData[user];
                    if (!data || data.length === 0) continue;

                    let ultimoStatus = obtenerUltimoEstado(data);
                    let last = data[data.length - 1];

                    let lat = parseFloat(last.lat);
                    let lng = parseFloat(last.lng);

                    if (isNaN(lat) || isNaN(lng) || lat === 0 || lng === 0) continue;

                    // Contar estados
                    if (ultimoStatus === 'activo') activos++;
                    else inactivos++;

                    const icon = crearIcono(ultimoStatus);

                    let m = L.marker([lat, lng], {
                            icon: icon
                        })
                        .addTo(map)
                        .bindTooltip(`
                            <strong>${user}</strong><br>
                            Estado: <span style="color:${ultimoStatus === 'activo' ? '#28a745' : '#dc3545'}; font-weight:bold;">
                                ${ultimoStatus.toUpperCase()}
                            </span>
                        `, {
                            permanent: true,
                            direction: 'top',
                            offset: [0, -25],
                            className: `label-usuario ${ultimoStatus}`
                        });

                    markers.push(m);
                    bounds.push([lat, lng]);

                    const dot = ultimoStatus === 'activo' ?
                        '<span class="punto verde"></span>' :
                        '<span class="punto rojo"></span>';

                    infoHTML += `${dot} ${user} `;
                }

                if (bounds.length > 0) {
                    map.fitBounds(bounds);
                }

                document.getElementById('infoUsuario').innerHTML = `
                    👥 <strong>Todos los usuarios</strong>
                    <span class="estado-badge activo" style="margin-left:10px;">🟢 ${activos} activos</span>
                    <span class="estado-badge inactivo">🔴 ${inactivos} inactivos</span>
                    <span style="color:#999; font-size:12px; margin-left:10px;">
                        ${infoHTML}
                    </span>
                `;

                actualizarHora();

            } catch (e) {
                console.error("Error todos:", e);
            }
        }

        // -------------------------
        function actualizarHora() {
            const ahora = new Date();
            const hora = ahora.toLocaleTimeString('es-AR');
            document.getElementById('ultimaActualizacion').textContent = `🕐 ${hora}`;
        }

        // -------------------------
        async function actualizar() {
            // Limpiar caché de datos para forzar recarga
            usuariosData = {};

            if (modoTodos) {
                await cargarTodos();
            } else {
                await cargarIndividual();
            }
        }

        // -------------------------
        async function actualizarSuave() {
            // Recargar solo los datos que se están mostrando
            if (modoTodos) {
                await cargarTodos();
            } else {
                let user = document.getElementById("usuario").value;
                if (user) {
                    await cargarDatosUsuario(user);
                    await cargarIndividual();
                }
            }
        }

        // -------------------------
        document.addEventListener("DOMContentLoaded", async () => {

            await cargarUsuarios();

            // Evento cambio de usuario
            document.getElementById("usuario").addEventListener("change", () => {
                if (modoTodos) {
                    modoTodos = false;
                    document.getElementById("btnTodos").textContent = '👥 Ver todas';
                    document.getElementById("btnTodos").className = 'btn-primary';
                }
                cargarIndividual();
            });

            // Botón "Ver todas"
            document.getElementById("btnTodos").addEventListener("click", () => {
                modoTodos = !modoTodos;

                if (modoTodos) {
                    document.getElementById("btnTodos").textContent = '⬅ Volver';
                    document.getElementById("btnTodos").className = 'btn-danger';
                    cargarTodos();
                } else {
                    document.getElementById("btnTodos").textContent = '👥 Ver todas';
                    document.getElementById("btnTodos").className = 'btn-primary';
                    cargarIndividual();
                }
            });

            // Botón actualizar manual (forzar recarga completa)
            document.getElementById("btnActualizar").addEventListener("click", () => {
                usuariosData = {};
                actualizar();
            });

            // Actualizar cada 5 segundos (solo si la pestaña está visible)
            setInterval(() => {
                if (!document.hidden) {
                    actualizarSuave();
                }
            }, 5000);

            // Actualizar cuando la pestaña vuelve a ser visible
            document.addEventListener('visibilitychange', () => {
                if (!document.hidden) {
                    actualizarSuave();
                }
            });
        });
    </script>

</body>

</html>