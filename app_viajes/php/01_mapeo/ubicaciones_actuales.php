<?php
include_once "../../funciones/funciones.php";

// Protegemos la página para operadores (3) o administrador (0)
protegerPagina([0, 3]);

$con = conexion();

// ==========================================================
// CONSULTA: ÚLTIMA UBICACIÓN POR MÓVIL (SIN LÍMITE DE TIEMPO)
// Solo móviles con logeado = 1
// Incluye información del viaje activo (si existe)
// ==========================================================
$sql = "SELECT 
    u.movil AS user_id,
    u.lat,
    u.lng,
    u.fecha,
    u.status AS ubicacion_status,
    c.nombre AS nombre_chofer,
    c.apellido AS apellido_chofer,
    COALESCE(c.activo, 0) AS activo,
    COALESCE(c.logeado, 0) AS logeado,
    v.patente,
    vd.id AS viaje_id,
    vd.nombre_pasaj AS nombre_pasajero,
    vd.direccion_origen,
    vd.direccion_destino
FROM ubicaciones u
INNER JOIN (
    SELECT movil, MAX(fecha) AS max_fecha
    FROM ubicaciones
    WHERE lat IS NOT NULL AND lng IS NOT NULL
    GROUP BY movil
) AS ultima ON u.movil = ultima.movil AND u.fecha = ultima.max_fecha
LEFT JOIN choferes c ON u.movil = c.movil
LEFT JOIN vehiculos v ON c.id = v.id_chofer
LEFT JOIN (
    SELECT 
        vd.*,
        ROW_NUMBER() OVER (PARTITION BY vd.asignado_a ORDER BY vd.id DESC) AS rn
    FROM viajes_despacho vd
    WHERE vd.estado NOT IN ('Completo', 'Cancelado') 
      AND vd.fecha_finalizacion IS NULL
) vd ON vd.asignado_a = u.movil AND vd.rn = 1
WHERE c.logeado = 1
ORDER BY u.fecha DESC";

$stmt = $con->query($sql);
$unidades = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ==========================================================
// DEDUPLICAR: Agrupar por user_id (por si acaso)
// ==========================================================
$unicos = [];
foreach ($unidades as $u) {
    $id = $u['user_id'];
    if (!isset($unicos[$id])) {
        $unicos[$id] = $u;
    }
}
$unidades = array_values($unicos);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>UBICACION UNIDADES</title>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <!-- Leaflet.markercluster CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css">

    <!-- Leaflet.markercluster JS -->
    <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>

    <style>
        html,
        body {
            margin: 0;
            padding: 0;
            height: 100%;
            font-family: Arial, sans-serif;
        }

        .topbar {
            padding: 10px 15px;
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .topbar-fila1 {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .btn-salir {
            margin-left: auto;
            padding: 6px 14px;
            background: #dc3545;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 13px;
            font-weight: bold;
        }

        .btn-salir:hover {
            background: #c82333;
        }

        .topbar select {
            padding: 5px 10px;
            font-size: 14px;
            border-radius: 4px;
            border: 1px solid #ccc;
            min-width: 200px;
        }

        #map {
            width: 100%;
            height: calc(100% - 80px);
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
            font-size: 11px;
            width: 36px;
            height: 36px;
        }

        .pulse {
            animation: pulse-animation 2s infinite;
        }

        @keyframes pulse-animation {
            0% {
                transform: scale(1);
                box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.7);
            }

            70% {
                transform: scale(1.05);
                box-shadow: 0 0 0 10px rgba(40, 167, 69, 0);
            }

            100% {
                transform: scale(1);
                box-shadow: 0 0 0 0 rgba(40, 167, 69, 0);
            }
        }

        .stats {
            display: flex;
            gap: 15px;
            font-size: 13px;
            color: #495057;
            padding: 4px 10px;
            background: white;
            border-radius: 4px;
            border: 1px solid #dee2e6;
        }

        .stats span {
            font-weight: bold;
        }

        @media (max-width: 768px) {
            .topbar-fila1 {
                flex-direction: column;
                align-items: stretch;
            }

            .btn-salir {
                margin-left: 0;
                text-align: center;
            }

            .stats {
                flex-wrap: wrap;
                justify-content: center;
            }
        }
    </style>
</head>

<body>

    <div class="topbar">
        <div class="topbar-fila1">
            <label for="select-usuario"><b>Buscar Unidad / Chofer:</b></label>
            <select id="select-usuario">
                <option value="todos">-- Ver todos --</option>
                <?php foreach ($unidades as $u): ?>
                    <option value="<?php echo $u['user_id']; ?>">
                        Móvil <?php echo $u['user_id']; ?> - <?php echo htmlspecialchars($u['nombre_chofer'] . ' ' . $u['apellido_chofer']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <div class="stats" id="stats">
                <span>🟢 Activos: <span id="count-activo">0</span></span>
                <span>🔴 Inactivos: <span id="count-inactivo">0</span></span>
                <span>🔵 Con viaje: <span id="count-viaje">0</span></span>
                <span>📱 Total: <span id="count-total">0</span></span>
            </div>

            <a href="../inicio_0.php" class="btn-salir">SALIR</a>
        </div>
    </div>

    <div id="map"></div>

    <script>
        const map = L.map('map').setView([-34.60, -58.38], 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        const unidades = <?php echo json_encode($unidades); ?>;

        // Almacenamos todos los marcadores para poder filtrarlos
        let todosLosMarcadores = [];
        const markersMap = {};
        let stats = {
            activo: 0,
            inactivo: 0,
            viaje: 0,
            total: 0
        };

        // ---------- CREAR CLÚSTER ----------
        const markerClusterGroup = L.markerClusterGroup({
            spiderfyOnMaxZoom: true,
            showCoverageOnHover: true,
            zoomToBoundsOnClick: true,
            iconCreateFunction: function(cluster) {
                const childCount = cluster.getChildCount();
                let color = '#6c757d';
                if (childCount > 10) color = '#dc3545';
                else if (childCount > 5) color = '#ff8c00';
                else color = '#28a745';
                return L.divIcon({
                    html: `<div style="background:${color}; color:white; border-radius:50%; width:40px; height:40px; display:flex; align-items:center; justify-content:center; font-weight:bold; box-shadow: 0 0 4px rgba(0,0,0,0.4);">${childCount}</div>`,
                    iconSize: [40, 40],
                    iconAnchor: [20, 20]
                });
            }
        });

        // ---------- FUNCIONES DE ESTADO Y COLOR ----------
        function getEstadoUnidad(unidad) {
            const activo = Number(unidad.activo);
            const logeado = Number(unidad.logeado);

            if (logeado === 1 && activo === 1) return 'activo';
            if (logeado === 1 && activo === 0) return 'inactivo';
            return 'desconectado';
        }

        function getColorByEstado(estado) {
            switch (estado) {
                case 'activo':
                    return '#28a745';
                case 'inactivo':
                    return '#dc3545';
                default:
                    return '#6c757d';
            }
        }

        function getTextoEstado(estado) {
            switch (estado) {
                case 'activo':
                    return '🟢 Activo (logeado=1, activo=1)';
                case 'inactivo':
                    return '🔴 Inactivo (logeado=1, activo=0)';
                default:
                    return 'Desconocido';
            }
        }

        function getIconoEstado(estado) {
            switch (estado) {
                case 'activo':
                    return '✅';
                case 'inactivo':
                    return '❌';
                default:
                    return '❓';
            }
        }

        // ---------- RECORRER UNIDADES Y CREAR MARCADORES ----------
        unidades.forEach(u => {
            const lat = parseFloat(u.lat);
            const lng = parseFloat(u.lng);
            if (isNaN(lat) || isNaN(lng)) return;

            const estado = getEstadoUnidad(u);
            const color = getColorByEstado(estado);
            const nombreCompleto = (u.nombre_chofer || "Sin Nombre") + (u.apellido_chofer ? " " + u.apellido_chofer : "");
            const etiquetaCorta = u.user_id;
            const tieneViaje = (u.viaje_id !== null && u.viaje_id !== undefined);
            const nombrePasajero = u.nombre_pasajero || '';

            // Contamos estadísticas
            stats[estado] = (stats[estado] || 0) + 1;
            if (tieneViaje) stats.viaje++;
            stats.total++;

            // Crear el marcador
            let html = `
        <div class="numero-viaje" style="background:${color}" title="${nombreCompleto}">
            ${etiquetaCorta}
        </div>
    `;
            if (estado === 'activo') {
                html = `
            <div class="numero-viaje pulse" style="background:${color}" title="${nombreCompleto}">
                ${etiquetaCorta}
            </div>
        `;
            }

            // Añadir un borde azul si tiene viaje activo
            if (tieneViaje) {
                html = `
            <div class="numero-viaje pulse" style="background:${color}; border: 3px solid #007bff !important;" title="${nombreCompleto}">
                ${etiquetaCorta}
            </div>
        `;
            }

            const marker = L.marker([lat, lng], {
                icon: L.divIcon({
                    html: html,
                    iconSize: [36, 36],
                    iconAnchor: [18, 18]
                }),
                userId: u.user_id
            });

            // Popup (sin estado, activo, logeado)
            const fechaObj = new Date(u.fecha);
            const fechaLegible = fechaObj.toLocaleString('es-AR', {
                timeZone: 'America/Argentina/Buenos_Aires'
            });

            let infoHTML = `
        <div style="min-width: 220px;">
            <h4 style="margin: 0 0 8px 0; color: #007bff;">
                ${getIconoEstado(estado)} Unidad Móvil ${u.user_id}
                ${tieneViaje ? ' 🚗' : ''}
            </h4>
            <hr style="margin: 5px 0;">
            <div style="font-size: 13px;">
                <p style="margin: 4px 0;">
                    <strong>Chofer:</strong> ${nombreCompleto}
                </p>
                <p style="margin: 4px 0;">
                    <strong>Patente:</strong> <b>${u.patente || 'Sin patente'}</b>
                </p>
    `;

            // Si tiene viaje, mostramos información del pasajero
            if (tieneViaje) {
                infoHTML += `
                <p style="margin: 4px 0;">
                    <strong>Pasajero:</strong> ${nombrePasajero || 'No especificado'}
                </p>
                <p style="margin: 4px 0;">
                    <strong>Origen:</strong> ${u.direccion_origen || 'No especificado'}
                </p>
                <p style="margin: 4px 0;">
                    <strong>Destino:</strong> ${u.direccion_destino || 'No especificado'}
                </p>
        `;
            }

            infoHTML += `
                <hr style="margin: 5px 0;">
                <p style="margin: 4px 0;">
                    <strong>Última ubicación:</strong><br>
                    ${fechaLegible}
                </p>
                <p style="margin: 4px 0;">
                    <strong>Coordenadas:</strong><br>
                    ${lat}, ${lng}
                </p>
            </div>
        </div>
    `;
            marker.bindPopup(infoHTML);

            markersMap[u.user_id] = marker;
            todosLosMarcadores.push(marker);
        });

        // Función para actualizar el mapa con los marcadores filtrados
        function actualizarMapa(filtroUserId) {
            // Limpiar el cluster
            markerClusterGroup.clearLayers();

            // Si es 'todos', agregamos todos
            if (filtroUserId === 'todos') {
                todosLosMarcadores.forEach(m => markerClusterGroup.addLayer(m));
                // Ajustar bounds a todos
                if (todosLosMarcadores.length > 0) {
                    const groupBounds = L.featureGroup(todosLosMarcadores).getBounds();
                    map.fitBounds(groupBounds, {
                        padding: [50, 50]
                    });
                }
            } else {
                // Buscar el marcador correspondiente
                const marker = markersMap[filtroUserId];
                if (marker) {
                    markerClusterGroup.addLayer(marker);
                    map.setView(marker.getLatLng(), 16);
                    marker.openPopup();
                } else {
                    alert('No se encontró la unidad seleccionada');
                }
            }
            // Actualizar estadísticas según lo visible
            actualizarEstadisticas(filtroUserId);
        }

        // Función para actualizar las estadísticas según el filtro
        function actualizarEstadisticas(filtroUserId) {
            let activo = 0,
                inactivo = 0,
                viaje = 0,
                total = 0;
            let lista = [];
            if (filtroUserId === 'todos') {
                lista = unidades;
            } else {
                const unidad = unidades.find(u => u.user_id == filtroUserId);
                if (unidad) lista = [unidad];
            }
            lista.forEach(u => {
                const estado = getEstadoUnidad(u);
                if (estado === 'activo') activo++;
                else if (estado === 'inactivo') inactivo++;
                if (u.viaje_id !== null && u.viaje_id !== undefined) viaje++;
                total++;
            });
            document.getElementById('count-activo').textContent = activo;
            document.getElementById('count-inactivo').textContent = inactivo;
            document.getElementById('count-viaje').textContent = viaje;
            document.getElementById('count-total').textContent = total;
        }

        // ---------- AÑADIR CLUSTER AL MAPA ----------
        map.addLayer(markerClusterGroup);

        // ---------- MOSTRAR TODOS INICIALMENTE ----------
        actualizarMapa('todos');

        // ---------- EVENTO DEL SELECT ----------
        document.getElementById('select-usuario').addEventListener('change', function() {
            const userId = this.value;
            actualizarMapa(userId);
        });

        // ---------- ESTADÍSTICAS INICIALES ----------
        // Ya se actualizan con actualizarMapa('todos')

        console.log('📊 Estadísticas de unidades:', stats);
        console.log('📌 Detalle de unidades:', unidades);
    </script>

</body>

</html>