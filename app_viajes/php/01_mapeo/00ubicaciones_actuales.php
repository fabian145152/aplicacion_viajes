<?php
include_once "../../funciones/funciones.php";

// Protegemos la página para operadores (3) o administrador (0)
protegerPagina([0, 3]);

$con = conexion();

// CONSULTA DEFINITIVA: Trae la última ubicación y se une con usuarios para obtener el nombre completo
$sql = "SELECT u.user_id, u.lat, u.lng, u.fecha, u.device_id AS status_unidad, us.nombre AS nombre_chofer
FROM ubicaciones u
INNER JOIN choferes us ON u.user_id = us.movil
WHERE u.id IN (SELECT MAX(id) FROM ubicaciones GROUP BY user_id)
  AND u.lat IS NOT NULL 
  AND u.lng IS NOT NULL 
  AND u.lat <> '' 
  AND u.lng <> '';";

$stmt = $con->query($sql);
$unidades = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Ubicaciones Actuales de Unidades</title>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <style>
        html,
        body {
            margin: 0;
            padding: 0;
            height: 100%;
            font-family: Arial, sans-serif;
        }

        .topbar {
            padding: 10px;
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .topbar select {
            padding: 5px 10px;
            font-size: 14px;
            border-radius: 4px;
            border: 1px solid #ccc;
        }

        #map {
            width: 100%;
            height: calc(100% - 50px);
            /* Restamos el alto de la barra superior */
        }

        .numero-viaje {
            color: white;
            font-weight: bold;
            text-align: center;
            border-radius: 50%;
            border: 2px solid white;
            box-shadow: 0 0 4px rgba(0, 0, 0, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
        }
    </style>
</head>

<body>

    <div class="topbar">
        <label for="select-usuario"><b>Buscar Unidad / Chofer:</b></label>
        <select id="select-usuario">
            <option value="todos">-- Ver todos --</option>
            <?php foreach ($unidades as $u): ?>
                <option value="<?php echo $u['user_id']; ?>">
                    ID: <?php echo $u['user_id']; ?> - <?php echo htmlspecialchars($u['nombre_chofer']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div id="map"></div>

    <script>
        // Inicializar el mapa
        const map = L.map('map').setView([-34.60, -58.38], 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        // Pasar los datos de PHP a JavaScript
        const unidades = <?php echo json_encode($unidades); ?>;

        const bounds = [];
        const markersMap = {}; // Diccionario para guardar los marcadores por user_id

        // Iterar y dibujar cada unidad en el mapa
        unidades.forEach(u => {
            const lat = parseFloat(u.lat);
            const lng = parseFloat(u.lng);

            if (isNaN(lat) || isNaN(lng)) return;

            const color = u.status_unidad === 'activo' ? '#28a745' : (u.status_unidad === 'inactivo' ? '#dc3545' : '#6c757d');
            const nombreCompleto = u.nombre_chofer || "Sin Nombre";
            const etiquetaCorta = u.user_id;

            const marker = L.marker([lat, lng], {
                icon: L.divIcon({
                    html: `
                        <div class="numero-viaje" style="background:${color}" title="${nombreCompleto}">
                            ${etiquetaCorta}
                        </div>
                    `,
                    iconSize: [40, 40],
                    iconAnchor: [20, 20]
                })
            }).addTo(map);

            // Ventana flotante informativa
            marker.bindPopup(`
                <b>Unidad / Chofer:</b> ${nombreCompleto}<br>
                <b>ID Móvil:</b> ${u.user_id}<br>
                <hr style="border:0; border-top:1px solid #eee; margin: 5px 0;">
                <b>Último Reporte:</b><br>
                Fecha: ${u.fecha}<br>
                <b>Coordenadas:</b><br>
                ${lat}, ${lng}
            `);

            // Guardamos la referencia del marcador indexado por su user_id
            markersMap[u.user_id] = marker;

            bounds.push([lat, lng]);
        });

        // Auto-zoom adaptativo inicial
        if (bounds.length > 0) {
            map.fitBounds(bounds, {
                padding: [50, 50]
            });
        }

        // Lógica de filtrado del SELECT
        document.getElementById('select-usuario').addEventListener('change', function() {
            const userId = this.value;

            if (userId === 'todos') {
                // Si elige todos, volvemos a encuadrar el mapa para ver todas las unidades
                if (bounds.length > 0) {
                    map.fitBounds(bounds, {
                        padding: [50, 50]
                    });
                }
            } else {
                // Si elige un usuario específico, buscamos su marcador
                const marker = markersMap[userId];
                if (marker) {
                    // Hacemos zoom al marcador y abrimos su Popup de información automáticamente
                    map.setView(marker.getLatLng(), 16);
                    marker.openPopup();
                }
            }
        });
    </script>

</body>

</html>