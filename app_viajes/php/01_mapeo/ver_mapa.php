<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Mapa GPS</title>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

    <style>
        #map {
            height: 600px;
        }

        .topbar {
            margin-bottom: 10px;
        }

        .label-usuario {
            background: rgba(0, 0, 0, 0.7);
            color: white;
            border-radius: 6px;
            padding: 2px 6px;
            font-size: 12px;
        }
    </style>
</head>

<body>

    <h2>Mapa en tiempo real</h2>

    <div class="topbar">
        <select id="usuario"></select>
        <button id="btnTodos">Ver todas</button>
    </div>

    <div id="map"></div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
        const map = L.map('map').setView([-34.60, -58.38], 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

        let markers = [];
        let polyline = null;
        let modoTodos = false;

        // -------------------------
        function limpiarMapa() {
            if (polyline) {
                map.removeLayer(polyline);
                polyline = null;
            }

            markers.forEach(m => map.removeLayer(m));
            markers = [];
        }

        // -------------------------
        async function cargarUsuarios() {
            try {
                const res = await fetch("obtener_usuarios.php");
                const data = await res.json();

                const select = document.getElementById("usuario");
                select.innerHTML = "";

                data.forEach(u => {
                    let option = document.createElement("option");
                    option.value = u;
                    option.text = u;
                    select.appendChild(option);
                });

                if (data.length > 0) {
                    select.value = data[0];
                }

            } catch (e) {
                console.error("Error usuarios:", e);
            }
        }

        // -------------------------
        async function cargarIndividual() {

            let user = document.getElementById("usuario").value;
            if (!user) return;

            try {
                const res = await fetch("obtener_recorrido.php?user_id=" + user);
                const data = await res.json();

                if (!Array.isArray(data) || data.length === 0) return;

                limpiarMapa();

                let coords = [];

                data.forEach(p => {
                    let lat = parseFloat(p.lat);
                    let lng = parseFloat(p.lng);

                    if (!isNaN(lat) && !isNaN(lng)) {
                        coords.push([lat, lng]);
                    }
                });

                if (coords.length === 0) return;

                // línea recorrido
                polyline = L.polyline(coords, {
                    color: 'blue'
                }).addTo(map);

                let last = coords[coords.length - 1];

                let m = L.marker(last)
                    .addTo(map)
                    .bindTooltip(user, {
                        permanent: true,
                        direction: 'top',
                        offset: [0, -20],
                        className: 'label-usuario'
                    });

                markers.push(m);

                map.setView(last, 16);

            } catch (e) {
                console.error("Error individual:", e);
            }
        }

        // -------------------------
        async function cargarTodos() {

            try {
                const resUsuarios = await fetch("obtener_usuarios.php");
                const usuarios = await resUsuarios.json();

                limpiarMapa();

                let bounds = [];

                for (const user of usuarios) {

                    const res = await fetch("obtener_recorrido.php?user_id=" + user);
                    const data = await res.json();

                    if (!Array.isArray(data) || data.length === 0) continue;

                    let last = data[data.length - 1];

                    let lat = parseFloat(last.lat);
                    let lng = parseFloat(last.lng);

                    if (isNaN(lat) || isNaN(lng)) continue;

                    let m = L.marker([lat, lng])
                        .addTo(map)
                        .bindTooltip(user, {
                            permanent: true,
                            direction: 'top',
                            offset: [0, -20],
                            className: 'label-usuario'
                        });

                    markers.push(m);
                    bounds.push([lat, lng]);
                }

                if (bounds.length > 0) {
                    map.fitBounds(bounds);
                }

            } catch (e) {
                console.error("Error todos:", e);
            }
        }

        // -------------------------
        function actualizar() {
            if (modoTodos) {
                cargarTodos();
            } else {
                cargarIndividual();
            }
        }

        // -------------------------
        document.addEventListener("DOMContentLoaded", async () => {

            await cargarUsuarios();

            actualizar();

            document.getElementById("usuario").addEventListener("change", () => {
                modoTodos = false;
                actualizar();
            });

            document.getElementById("btnTodos").addEventListener("click", () => {
                modoTodos = true;
                actualizar();
            });

            setInterval(actualizar, 5000);
        });
    </script>

</body>

</html>