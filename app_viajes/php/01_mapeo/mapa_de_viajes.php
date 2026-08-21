<?php
include_once "../../funciones/funciones.php";

protegerPagina([0, 3]);

$con = conexion();

$sql = "SELECT
            id,
            nombre_pasaj,
            cel_pasaj,
            direccion_origen,
            origen_lat,
            origen_lng,
            fecha,
            hora,
            categoria_movil,
            diferido
        FROM viajes_despacho
        WHERE origen_lat IS NOT NULL
        AND origen_lng IS NOT NULL
        AND origen_lat <> ''
        AND origen_lng <> ''";

$stmt = $con->query($sql);
$viajes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Mapa de Viajes</title>

    <link rel="stylesheet"
        href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <style>
        html,
        body {
            margin: 0;
            padding: 0;
            height: 100%;
        }

        #map {
            width: 100%;
            height: 100vh;
        }

        .numero-viaje {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border: 2px solid #fff;
            text-align: center;
            line-height: 32px;
            font-weight: bold;
            font-size: 14px;
            color: #000;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .4);
        }

        .numero-viaje-multiple {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 3px solid #fff;
            text-align: center;
            line-height: 34px;
            font-weight: bold;
            font-size: 16px;
            color: #fff;
            background: #dc3545;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .4);
        }

        .leaflet-div-icon {
            background: transparent !important;
            border: none !important;
        }

        /* Estilo para los marcadores agrupados con números */
        .marker-cluster {
            background: #dc3545;
            color: white;
            border-radius: 50%;
            text-align: center;
            font-weight: bold;
            border: 3px solid white;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.4);
        }

        #leyenda {
            position: fixed;
            top: 10px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 9999;
            background: rgba(255, 255, 255, .95);
            border: 1px solid #ccc;
            border-radius: 8px;
            padding: 10px 15px;
            display: flex;
            gap: 20px;
            align-items: center;
            font-family: Arial, sans-serif;
            font-size: 14px;
            font-weight: bold;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .2);
        }

        .item-leyenda {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .color {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            border: 1px solid #555;
            display: inline-block;
        }

        .color-taxi {
            background: #ffc107;
        }

        .color-remis {
            background: #28a745;
        }

        .color-diferido {
            background: #c8a97e;
        }

        .color-multiple {
            background: #dc3545;
        }

        @media (max-width: 768px) {
            #leyenda {
                flex-direction: column;
                gap: 8px;
                font-size: 12px;
                padding: 8px;
            }
        }
    </style>
</head>

<body>

    <div id="leyenda">
        <div class="item-leyenda">
            <span class="color color-taxi"></span>
            Viaje taxi Inmediato
        </div>

        <div class="item-leyenda">
            <span class="color color-remis"></span>
            Viaje remis Inmediato
        </div>

        <div class="item-leyenda">
            <span class="color color-diferido"></span>
            Diferido
        </div>

        <div class="item-leyenda">
            <span class="color color-multiple"></span>
            Múltiples viajes
        </div>

        <a href="../inicio_0.php" style="margin-left: 20px; font-size: 12px; text-decoration: none; color: #007bff;">
            Volver al Menú
        </a>
    </div>

    <div id="map"></div>

    <script>
        const viajes = <?= json_encode($viajes, JSON_UNESCAPED_UNICODE); ?>;

        const map = L.map('map').setView([-34.6037, -58.3816], 11);

        L.tileLayer(
            'https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap'
            }
        ).addTo(map);

        // --- 1. Agrupar viajes por coordenadas exactas ---
        const grupos = {};

        viajes.forEach(v => {
            if (!v.origen_lat || !v.origen_lng) return;

            // Redondear coordenadas a 6 decimales para agrupar (aprox 0.1 metros)
            const lat = parseFloat(v.origen_lat).toFixed(6);
            const lng = parseFloat(v.origen_lng).toFixed(6);
            const key = `${lat},${lng}`;

            if (!grupos[key]) {
                grupos[key] = [];
            }
            grupos[key].push(v);
        });

        // --- 2. Crear marcadores para cada grupo ---
        let bounds = [];

        Object.keys(grupos).forEach(key => {
            const [lat, lng] = key.split(',').map(Number);
            const viajesEnGrupo = grupos[key];
            const cantidad = viajesEnGrupo.length;

            let color = "#28a745"; // default

            // Determinar color según el primer viaje del grupo (o el más común)
            const tipos = viajesEnGrupo.map(v => ({
                diferido: String(v.diferido).trim(),
                categoria: String(v.categoria_movil).trim().toUpperCase()
            }));

            // Si todos son del mismo tipo, usar ese color
            const todosDiferido = tipos.every(t => t.diferido === "Si");
            const todosTaxi = tipos.every(t => t.categoria === "TAXI");
            const todosRemis = tipos.every(t => t.categoria === "REMIS");

            if (todosDiferido) {
                color = "#c8a97e";
            } else if (todosTaxi && !todosDiferido) {
                color = "#ffc107";
            } else if (todosRemis && !todosDiferido) {
                color = "#28a745";
            } else {
                // Mezcla de tipos - usar color múltiple
                color = "#dc3545";
            }

            // Si hay más de un viaje en la misma ubicación
            if (cantidad > 1) {
                // Crear un marcador que muestra el número de viajes
                const marker = L.marker([lat, lng], {
                    icon: L.divIcon({
                        html: `
                            <div class="numero-viaje-multiple"
                                 style="background:${color};">
                                ${cantidad}
                            </div>
                        `,
                        iconSize: [40, 40],
                        iconAnchor: [20, 20]
                    })
                }).addTo(map);

                // Popup que muestra TODOS los viajes de esa ubicación
                let popupContent = `
                    <div style="max-height: 300px; overflow-y: auto; min-width: 250px;">
                        <h4 style="margin: 0 0 10px 0; color: #dc3545;">
                            📍 ${cantidad} viaje(s) en esta ubicación
                        </h4>
                        <hr style="margin: 5px 0;">
                `;

                viajesEnGrupo.forEach((v, index) => {
                    const tipo = String(v.diferido).trim() === "Si" ? "Diferido" : "Inmediato";
                    const colorTipo = String(v.diferido).trim() === "Si" ? "#c8a97e" :
                        String(v.categoria_movil).trim().toUpperCase() === "TAXI" ? "#ffc107" : "#28a745";

                    popupContent += `
                        <div style="border-left: 4px solid ${colorTipo}; padding: 6px 10px; margin-bottom: 8px; background: #f8f9fa; border-radius: 4px;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <strong style="color: #007bff;">Viaje #${v.id}</strong>
                                <span style="background: ${colorTipo}; padding: 2px 8px; border-radius: 12px; font-size: 11px; color: #000; font-weight: bold;">
                                    ${tipo}
                                </span>
                            </div>
                            <div style="font-size: 13px; margin-top: 4px;">
                                <strong>Pasajero:</strong> ${v.nombre_pasaj}<br>
                                <strong>Cel:</strong> ${v.cel_pasaj}<br>
                                <strong>Origen:</strong> ${v.direccion_origen}<br>
                                <strong>Fecha/Hora:</strong> ${v.fecha} ${v.hora}
                            </div>
                        </div>
                    `;
                });

                popupContent += `</div>`;
                marker.bindPopup(popupContent);

            } else {
                // Solo un viaje - marcador normal
                const v = viajesEnGrupo[0];
                let color = "#28a745";

                if (String(v.diferido).trim() === "Si") {
                    color = "#c8a97e";
                } else {
                    if (String(v.categoria_movil).trim().toUpperCase() === "TAXI") {
                        color = "#ffc107";
                    }
                    if (String(v.categoria_movil).trim().toUpperCase() === "REMIS") {
                        color = "#28a745";
                    }
                }

                const marker = L.marker([lat, lng], {
                    icon: L.divIcon({
                        html: `
                            <div class="numero-viaje"
                                 style="background:${color}">
                                ${v.id}
                            </div>
                        `,
                        iconSize: [36, 36],
                        iconAnchor: [18, 18]
                    })
                }).addTo(map);

                const tipo = String(v.diferido).trim() === "Si" ? "Diferido" : "Inmediato";
                marker.bindPopup(`
                    <b>Viaje Nº ${v.id}</b><br>
                    <b>Pasajero:</b> ${v.nombre_pasaj}<br>
                    <b>Celular:</b> ${v.cel_pasaj}<br>
                    <b>Origen:</b><br>
                    ${v.direccion_origen}<br><br>
                    <b>Fecha:</b> ${v.fecha}<br>
                    <b>Hora:</b> ${v.hora}<br>
                    <b>Categoría:</b> ${v.categoria_movil}<br>
                    <b>Tipo:</b> ${tipo}
                `);
            }

            // Agregar a bounds para ajustar el mapa
            bounds.push([lat, lng]);
        });

        // --- 3. Ajustar el mapa para mostrar todos los marcadores ---
        if (bounds.length > 0) {
            map.fitBounds(bounds, {
                padding: [50, 50]
            });
        }

        // --- 4. Función opcional para ver detalles de un viaje específico ---
        function verDetalleViaje(id) {
            window.location.href = `detalle_viaje.php?id=${id}`;
        }

        // Agregar el botón de "Ver detalles" a cada viaje en el popup si se desea
        // (se puede modificar el popupContent para incluir el botón)
    </script>

</body>

</html>