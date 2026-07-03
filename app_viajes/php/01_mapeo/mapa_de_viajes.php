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

        .leaflet-div-icon {
            background: transparent !important;
            border: none !important;
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
        <a href="../inicio_0.php" style="margin-left: 20px; font-size: 12px; text-decoration: none; color: #007bff;">
            Volver al Menú
        </a>
    </div>

    <div id="map"></div>
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

        let bounds = [];

        viajes.forEach(v => {

            if (!v.origen_lat || !v.origen_lng) {
                return;
            }

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

            const marker = L.marker(
                [
                    parseFloat(v.origen_lat),
                    parseFloat(v.origen_lng)
                ], {
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
                }
            ).addTo(map);

            marker.bindPopup(`
                <b>Viaje Nº ${v.id}</b><br>
                <b>Pasajero:</b> ${v.nombre_pasaj}<br>
                <b>Celular:</b> ${v.cel_pasaj}<br>
                <b>Origen:</b><br>
                ${v.direccion_origen}<br><br>

                <b>Fecha:</b> ${v.fecha}<br>
                <b>Hora:</b> ${v.hora}<br>
                <b>Categoría:</b> ${v.categoria_movil}<br>
                <b>Tipo:</b> ${v.diferido === 'Si' ? 'Diferido' : 'Inmediato'}
            `);

            bounds.push([
                parseFloat(v.origen_lat),
                parseFloat(v.origen_lng)
            ]);
        });

        if (bounds.length > 0) {
            map.fitBounds(bounds);
        }
    </script>

</body>

</html>