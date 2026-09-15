<?php
include_once "../../../funciones/funciones.php";
protegerPagina([0]);

$id_viaje = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$pdo = conexion();

// Datos del viaje
$stmt = $pdo->prepare("SELECT * FROM viajes_despacho WHERE id = :id LIMIT 1");
$stmt->bindParam(':id', $id_viaje, PDO::PARAM_INT);
$stmt->execute();
$viaje = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$viaje) {
    exit("Viaje no encontrado.");
}

// Ubicaciones del viaje
$stmt = $pdo->prepare("SELECT id, lat, lng, device_id, fecha, status, movil
                       FROM ubicaciones
                       WHERE viaje_id = :id
                       ORDER BY fecha ASC, id ASC");
$stmt->bindParam(':id', $id_viaje, PDO::PARAM_INT);
$stmt->execute();
$ubicaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Primer y último punto con coordenadas válidas
$puntos_validos = array_values(array_filter($ubicaciones, function ($u) {
    return $u['lat'] != 0 && $u['lng'] != 0;
}));

$primero = $puntos_validos[0] ?? null;
$ultimo  = end($puntos_validos) ?: null;

// Centro del mapa
$centro_lat = $primero['lat'] ?? ($viaje['origen_lat'] ?: -34.61);
$centro_lng = $primero['lng'] ?? ($viaje['origen_lng'] ?: -58.42);

// Convertimos a JSON para el JS
$ubicaciones_json = json_encode($ubicaciones, JSON_UNESCAPED_UNICODE);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Mapa viaje #<?= (int)$viaje['id'] ?></title>

    <!-- Leaflet -->
    <link rel="stylesheet"
        href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <style>
        body {
            font-family: 'Segoe UI', Tahoma, sans-serif;
            margin: 0;
            padding: 0;
            background: #f0f2f5;
        }

        .header {
            background: #fff;
            padding: 15px 25px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .08);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .header h1 {
            margin: 0;
            font-size: 18px;
            color: #333;
        }

        .header .info {
            font-size: 13px;
            color: #666;
        }

        .header .info strong {
            color: #007bff;
        }

        .btn-volver {
            padding: 8px 18px;
            background: #6c757d;
            color: #fff;
            text-decoration: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
        }

        .btn-volver:hover {
            background: #5a6268;
        }

        #map {
            height: calc(100vh - 70px);
            width: 100%;
        }

        .info-box {
            background: #fff;
            padding: 12px 16px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .1);
            font-size: 12px;
            line-height: 1.6;
            max-height: 350px;
            overflow-y: auto;
        }

        .info-box h4 {
            margin: 0 0 8px 0;
            font-size: 13px;
            color: #333;
        }

        .info-box .stat {
            display: flex;
            justify-content: space-between;
            padding: 3px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .info-box .stat:last-child {
            border-bottom: none;
        }

        .leyenda {
            background: #fff;
            padding: 10px 14px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .1);
            font-size: 12px;
        }

        .leyenda .item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 2px 0;
        }

        .leyenda .dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }
    </style>
</head>

<body>

    <div class="header">
        <div>
            <h1>🗺️ Viaje #<?= (int)$viaje['id'] ?> — <?= htmlspecialchars($viaje['nombre_pasaj']) ?></h1>
            <div class="info">
                Móvil <strong><?= htmlspecialchars($viaje['movil'] ?? $ubicaciones[0]['movil'] ?? '—') ?></strong>
                · <?= htmlspecialchars($viaje['fecha']) ?> <?= htmlspecialchars($viaje['hora']) ?>
                · Estado: <strong><?= htmlspecialchars($viaje['estado']) ?></strong>
            </div>
        </div>
        <a href="ver_rutas.php" class="btn-volver">← Volver al listado</a>
    </div>

    <div id="map"></div>

    <script>
        // ============================================================
        // Datos desde PHP
        // ============================================================
        const viaje = <?= json_encode($viaje, JSON_UNESCAPED_UNICODE) ?>;
        const ubicaciones = <?= $ubicaciones_json ?>;

        // ============================================================
        // Mapa base
        // ============================================================
        const map = L.map('map').setView([<?= (float)$centro_lat ?>, <?= (float)$centro_lng ?>], 15);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        // ============================================================
        // Colores por status
        // ============================================================
        const colores = {
            'ACTIVO': '#007bff',
            'ASIGNADO': '#ff9900',
            'A BORDO': '#28a745',
            'CERRANDO': '#dc3545'
        };

        // ============================================================
        // Filtrar puntos válidos
        // ============================================================
        const puntos = ubicaciones.filter(u => u.lat != 0 && u.lng != 0);
        const latlngs = puntos.map(u => [parseFloat(u.lat), parseFloat(u.lng)]);

        // ============================================================
        // Polyline (el recorrido)
        // ============================================================
        if (latlngs.length > 1) {
            L.polyline(latlngs, {
                color: '#007bff',
                weight: 4,
                opacity: 0.7
            }).addTo(map);
        }

        // ============================================================
        // Marcadores de cada punto
        // ============================================================
        puntos.forEach((u, i) => {
            const color = colores[u.status] || '#6c757d';
            const esPrimero = (i === 0);
            const esUltimo = (i === puntos.length - 1);

            let radio = 5;
            if (esPrimero || esUltimo) radio = 9;

            const marker = L.circleMarker([parseFloat(u.lat), parseFloat(u.lng)], {
                radius: radio,
                fillColor: esPrimero ? '#000' : (esUltimo ? '#dc3545' : color),
                color: '#fff',
                weight: 2,
                opacity: 1,
                fillOpacity: 0.85
            }).addTo(map);

            const etiqueta = esPrimero ? '🟢 INICIO' : (esUltimo ? '🔴 FIN' : u.status);
            marker.bindPopup(`
        <strong>${etiqueta}</strong><br>
        Móvil: <b>${u.movil}</b><br>
        Estado: ${u.status}<br>
        Fecha: ${u.fecha}<br>
        Lat: ${u.lat}, Lng: ${u.lng}
    `);
        });

        // ============================================================
        // Marcadores de origen/destino del viaje (si tiene coords)
        // ============================================================
        if (viaje.origen_lat && viaje.origen_lat != 0) {
            L.marker([parseFloat(viaje.origen_lat), parseFloat(viaje.origen_lng)], {
                title: 'Origen'
            }).addTo(map).bindPopup('<b>Origen</b><br>' + (viaje.direccion_origen || ''));
        }

        if (viaje.destino_lat && viaje.destino_lat != 0) {
            L.marker([parseFloat(viaje.destino_lat), parseFloat(viaje.destino_lng)], {
                title: 'Destino'
            }).addTo(map).bindPopup('<b>Destino</b><br>' + (viaje.direccion_destino || ''));
        }

        // ============================================================
        // Panel de info (arriba a la derecha)
        // ============================================================
        const infoBox = L.control({
            position: 'topright'
        });
        infoBox.onAdd = function() {
            const div = L.DomUtil.create('div', 'info-box');
            let html = '<h4>📊 Resumen del viaje</h4>';
            html += `<div class="stat"><span>Total puntos:</span><b>${puntos.length}</b></div>`;
            if (puntos.length > 0) {
                html += `<div class="stat"><span>Primero:</span><b>${puntos[0].fecha.split(' ')[1] || puntos[0].fecha}</b></div>`;
                html += `<div class="stat"><span>Último:</span><b>${puntos[puntos.length-1].fecha.split(' ')[1] || puntos[puntos.length-1].fecha}</b></div>`;
            }
            html += `<div class="stat"><span>Origen:</span></div>`;
            html += `<div style="font-size:11px;color:#666;margin-bottom:6px;">${viaje.direccion_origen || '—'}</div>`;
            html += `<div class="stat"><span>Destino:</span></div>`;
            html += `<div style="font-size:11px;color:#666;">${viaje.direccion_destino || '—'}</div>`;
            div.innerHTML = html;
            return div;
        };
        infoBox.addTo(map);

        // ============================================================
        // Leyenda (abajo a la izquierda)
        // ============================================================
        const leyenda = L.control({
            position: 'bottomleft'
        });
        leyenda.onAdd = function() {
            const div = L.DomUtil.create('div', 'leyenda');
            div.innerHTML = `
        <div style="font-weight:700;margin-bottom:6px;">Leyenda</div>
        <!-- <div class="item"><div class="dot" style="background:#000;"></div> Inicio</div> 
        <div class="item"><div class="dot" style="background:#dc3545;"></div> Fin</div> 
        <div class="item"><div class="dot" style="background:#007bff;"></div> ACTIVO</div> -->
        <!-- <div class="item"><div class="dot" style="background:#ff9900;"></div> ASIGNADO</div> -->
        <div class="item"><div class="dot" style="background:#28a745;"></div> A BORDO</div>
        <div class="item"><div class="dot" style="background:#dc3545;"></div> CERRANDO</div>
    `;
            return div;
        };
        leyenda.addTo(map);

        // ============================================================
        // Ajustar vista a todos los puntos
        // ============================================================
        if (latlngs.length > 1) {
            map.fitBounds(latlngs, {
                padding: [50, 50]
            });
        } else if (latlngs.length === 1) {
            map.setView(latlngs[0], 17);
        }
    </script>

</body>

</html>