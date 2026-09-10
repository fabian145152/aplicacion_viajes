<?php
// 🔴 CORREGIR LA RUTA DE INCLUSIÓN
include_once "../../../funciones/funciones.php";

// 🔴 COMENTAR LA PROTECCIÓN TEMPORALMENTE PARA PROBAR
// protegerPagina([0, 3]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$conn = conexion();

if (!$conn) {
    echo "<div style='padding:20px; background:#f8d7da; border:1px solid #f5c6cb; border-radius:4px; color:#721c24;'>
            ❌ Error de conexión a la base de datos
          </div>";
    exit;
}

// 🔴 OBTENER EL ID DEL RECORRIDO (si viene en la URL)
$id_recorrido = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// 🔴 VARIABLE PARA EL RECORRIDO SELECCIONADO
$recorrido = null;
$viaje = null;

// Si hay un ID, buscar el recorrido
if ($id_recorrido > 0) {
    try {
        $stmt = $conn->prepare("SELECT * FROM recorridos_viaje WHERE id = ?");
        $stmt->execute([$id_recorrido]);
        $recorrido = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Error silencioso
    }

    if ($recorrido && $recorrido['id_viaje']) {
        try {
            $stmt = $conn->prepare("SELECT * FROM viajes_despacho WHERE id = ?");
            $stmt->execute([$recorrido['id_viaje']]);
            $viaje = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Error silencioso
        }
    }
}

// 🔴 OBTENER TODOS LOS RECORRIDOS PARA EL LISTADO
$stmt = $conn->query("SELECT * FROM recorridos_viaje ORDER BY id DESC");
$recorridos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver Recorridos</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f2f5;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            background: linear-gradient(135deg, #0d6efd, #0a58ca);
            color: white;
            padding: 20px 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            box-shadow: 0 4px 15px rgba(13, 110, 253, 0.3);
        }

        .header h1 {
            font-size: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header .btn-volver {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 8px 18px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: background 0.3s;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .header .btn-volver:hover {
            background: rgba(255, 255, 255, 0.35);
        }

        .main-content {
            display: grid;
            grid-template-columns: 380px 1fr;
            gap: 25px;
            align-items: start;
        }

        .listado-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            max-height: calc(100vh - 200px);
            display: flex;
            flex-direction: column;
        }

        .listado-header {
            padding: 15px 20px;
            background: #f8f9fa;
            border-bottom: 2px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-shrink: 0;
        }

        .listado-header h3 {
            font-size: 16px;
            color: #0d6efd;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .listado-header .badge-total {
            background: #0d6efd;
            color: white;
            padding: 2px 12px;
            border-radius: 20px;
            font-size: 12px;
        }

        .listado-scroll {
            overflow-y: auto;
            flex: 1;
            padding: 5px 0;
        }

        .listado-scroll::-webkit-scrollbar {
            width: 6px;
        }

        .listado-scroll::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .listado-scroll::-webkit-scrollbar-thumb {
            background: #c1c7cd;
            border-radius: 3px;
        }

        .recorrido-item {
            padding: 12px 18px;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .recorrido-item:hover {
            background: #f0f7ff;
        }

        .recorrido-item.active {
            background: #e7f1ff;
            border-left: 4px solid #0d6efd;
        }

        .recorrido-item .info {
            flex: 1;
            min-width: 0;
        }

        .recorrido-item .info .id {
            font-weight: 700;
            color: #0d6efd;
            font-size: 14px;
        }

        .recorrido-item .info .direcciones {
            font-size: 12px;
            color: #495057;
            margin-top: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .recorrido-item .info .fecha {
            font-size: 11px;
            color: #6c757d;
            margin-top: 2px;
        }

        .recorrido-item .estado-badge {
            font-size: 11px;
            padding: 3px 10px;
            border-radius: 12px;
            font-weight: 600;
            flex-shrink: 0;
            margin-left: 10px;
        }

        .estado-badge.sin-viaje {
            background: #fff3cd;
            color: #856404;
        }

        .estado-badge.con-viaje {
            background: #d4edda;
            color: #155724;
        }

        .recorrido-item .distancia-tiempo {
            font-size: 12px;
            color: #495057;
            text-align: right;
            flex-shrink: 0;
            margin-left: 10px;
        }

        .recorrido-item .distancia-tiempo span {
            display: block;
        }

        .recorrido-item .distancia-tiempo .km {
            font-weight: 600;
            color: #0d6efd;
        }

        .empty-listado {
            padding: 40px 20px;
            text-align: center;
            color: #6c757d;
        }

        .empty-listado .icono {
            font-size: 40px;
            margin-bottom: 10px;
        }

        .empty-listado .subtitulo {
            font-size: 14px;
            color: #999;
            margin-top: 8px;
        }

        .mapa-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        #map {
            width: 100%;
            height: 500px;
            background: #e9ecef;
        }

        .mapa-placeholder {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 500px;
            background: #e9ecef;
            color: #6c757d;
            flex-direction: column;
            gap: 10px;
        }

        .mapa-placeholder .icono {
            font-size: 60px;
            opacity: 0.5;
        }

        .mapa-placeholder h3 {
            font-weight: 400;
        }

        .detalles-container {
            padding: 20px 25px;
            border-top: 1px solid #e9ecef;
        }

        .detalles-container .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px 30px;
        }

        .detalles-container .grid .item {
            display: flex;
            gap: 8px;
            font-size: 13px;
            padding: 4px 0;
            border-bottom: 1px solid #f5f5f5;
        }

        .detalles-container .grid .item .label {
            font-weight: 600;
            color: #495057;
            min-width: 70px;
        }

        .detalles-container .grid .item .valor {
            color: #212529;
            word-break: break-word;
        }

        .detalles-container .grid .item .valor.destacado {
            font-weight: 700;
            color: #0d6efd;
        }

        .btn-ver-rec {
            display: inline-block;
            padding: 6px 14px;
            background: #0d6efd;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            transition: background 0.2s;
            border: none;
            cursor: pointer;
        }

        .btn-ver-rec:hover {
            background: #0a58ca;
        }

        .btn-ver-rec.secundario {
            background: #6c757d;
        }

        .btn-ver-rec.secundario:hover {
            background: #5a6268;
        }

        @media (max-width: 992px) {
            .main-content {
                grid-template-columns: 1fr;
            }

            .listado-container {
                max-height: 300px;
            }

            #map {
                height: 350px;
            }

            .detalles-container .grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 600px) {
            .header {
                flex-direction: column;
                text-align: center;
            }

            .recorrido-item {
                flex-wrap: wrap;
                gap: 5px;
            }

            .recorrido-item .distancia-tiempo {
                flex-direction: row;
                display: flex;
                gap: 10px;
                width: 100%;
                justify-content: flex-start;
            }

            .recorrido-item .distancia-tiempo span {
                display: inline;
            }
        }
    </style>
</head>

<body>

    <div class="container">
        <div class="header">
            <h1><span>🗺️</span> Recorridos Guardados</h1>
            <div>
                <a href="carga_viajes.php" class="btn-volver" style="margin-right:10px;">➕ Nuevo Viaje</a>
                <a href="lista_viajes.php" class="btn-volver">↩ Volver</a>
            </div>
        </div>

        <div class="main-content">
            <!-- COLUMNA IZQUIERDA: LISTADO -->
            <div class="listado-container">
                <div class="listado-header">
                    <h3>
                        📋 Recorridos
                        <span class="badge-total"><?= count($recorridos) ?></span>
                    </h3>
                    <div style="display:flex; gap:8px;">
                        <a href="?id=0" class="btn-ver-rec secundario" style="font-size:11px; padding:4px 10px;">⟳ Limpiar</a>
                    </div>
                </div>

                <div class="listado-scroll">
                    <?php if (empty($recorridos)): ?>
                        <div class="empty-listado">
                            <div class="icono">🗺️</div>
                            <p>No hay recorridos guardados</p>
                            <p class="subtitulo">
                                Ve a <a href="carga_viajes.php" style="color:#0d6efd;">carga de viajes</a>,
                                ingresa Origen y Destino y presiona "RECORRIDO"
                            </p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($recorridos as $r): ?>
                            <a href="?id=<?= $r['id'] ?>" style="text-decoration:none; color:inherit; display:block;">
                                <div class="recorrido-item <?= ($id_recorrido == $r['id']) ? 'active' : '' ?>">
                                    <div class="info">
                                        <div class="id">#<?= $r['id'] ?></div>
                                        <div class="direcciones">
                                            <?= htmlspecialchars(substr($r['origen'], 0, 35)) ?>...
                                            <span style="color:#999;">→</span>
                                            <?= htmlspecialchars(substr($r['destino'], 0, 35)) ?>...
                                        </div>
                                        <div class="fecha">📅 <?= date('d/m/Y H:i', strtotime($r['fecha_registro'])) ?></div>
                                    </div>
                                    <div class="distancia-tiempo">
                                        <span class="km"><?= $r['distancia'] ?> km</span>
                                        <span style="color:#6c757d;">⏱️ <?= $r['tiempo'] ?> min</span>
                                    </div>
                                    <span class="estado-badge <?= $r['id_viaje'] ? 'con-viaje' : 'sin-viaje' ?>">
                                        <?= $r['id_viaje'] ? '✅ Viaje #' . $r['id_viaje'] : '⏳ Sin viaje' ?>
                                    </span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- COLUMNA DERECHA: MAPA + DETALLES -->
            <div class="mapa-container">
                <?php if ($id_recorrido > 0 && $recorrido): ?>
                    <!-- MAPA -->
                    <div id="map"></div>

                    <!-- DETALLES DEL RECORRIDO -->
                    <div class="detalles-container">
                        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; margin-bottom:12px;">
                            <h3 style="color:#0d6efd;">📋 Recorrido #<?= $id_recorrido ?></h3>
                            <?php if ($recorrido['id_viaje']): ?>
                                <a href="lista_viajes.php?editar=<?= $recorrido['id_viaje'] ?>" class="btn-ver-rec">
                                    📝 Ver Viaje #<?= $recorrido['id_viaje'] ?>
                                </a>
                            <?php endif; ?>
                        </div>

                        <div class="grid">
                            <div class="item">
                                <span class="label">📍 Origen:</span>
                                <span class="valor"><?= htmlspecialchars($recorrido['origen']) ?></span>
                            </div>
                            <div class="item">
                                <span class="label">📍 Destino:</span>
                                <span class="valor"><?= htmlspecialchars($recorrido['destino']) ?></span>
                            </div>
                            <div class="item">
                                <span class="label">📏 Distancia:</span>
                                <span class="valor destacado"><?= $recorrido['distancia'] ?> km</span>
                            </div>
                            <div class="item">
                                <span class="label">⏱️ Tiempo:</span>
                                <span class="valor destacado"><?= $recorrido['tiempo'] ?> min</span>
                            </div>
                            <div class="item">
                                <span class="label">🚗 Móvil:</span>
                                <span class="valor"><?= $recorrido['movil'] ?></span>
                            </div>
                            <div class="item">
                                <span class="label">📅 Fecha:</span>
                                <span class="valor"><?= date('d/m/Y H:i', strtotime($recorrido['fecha_registro'])) ?></span>
                            </div>
                            <div class="item" style="grid-column: 1 / -1;">
                                <span class="label">📌 Estado:</span>
                                <span class="valor">
                                    <?php if ($recorrido['id_viaje']): ?>
                                        <span style="color:#28a745; font-weight:600;">✅ Asignado al viaje #<?= $recorrido['id_viaje'] ?></span>
                                    <?php else: ?>
                                        <span style="color:#856404; font-weight:600;">⏳ Sin viaje asignado</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                            <?php if ($viaje): ?>
                                <div class="item" style="grid-column: 1 / -1; background: #d4edda; padding: 8px 12px; border-radius: 6px;">
                                    <span class="label">📝 Viaje:</span>
                                    <span class="valor">
                                        #<?= $viaje['id'] ?> - <?= htmlspecialchars($viaje['nombre_pasaj']) ?>
                                        (<?= $viaje['estado'] ?>)
                                        <?php if ($viaje['asignado_a']): ?>
                                            - Móvil <?= $viaje['asignado_a'] ?>
                                        <?php endif; ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                <?php else: ?>
                    <!-- SIN RECORRIDO SELECCIONADO -->
                    <div class="mapa-placeholder">
                        <div class="icono">🗺️</div>
                        <h3>Selecciona un recorrido de la lista</h3>
                        <p style="color:#999; font-size:14px;">Haz clic en cualquier recorrido para verlo en el mapa</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if ($id_recorrido > 0 && $recorrido): ?>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                const recorrido = <?= json_encode($recorrido) ?>;

                const latO = parseFloat(recorrido.origen_lat);
                const lngO = parseFloat(recorrido.origen_lng);
                const latD = parseFloat(recorrido.destino_lat);
                const lngD = parseFloat(recorrido.destino_lng);

                if (!latO || !lngO || !latD || !lngD) {
                    document.getElementById('map').innerHTML = `
                <div style="display:flex; align-items:center; justify-content:center; height:100%; color:#6c757d; flex-direction:column;">
                    <span style="font-size:40px; margin-bottom:15px;">🗺️</span>
                    <span>No hay coordenadas disponibles para este recorrido</span>
                </div>
            `;
                    return;
                }

                // Inicializar mapa
                const map = L.map('map').setView([(latO + latD) / 2, (lngO + lngD) / 2], 13);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap'
                }).addTo(map);

                // Marcadores
                const markerO = L.marker([latO, lngO])
                    .addTo(map)
                    .bindPopup('📍 <b>Origen</b><br>' + recorrido.origen)
                    .openPopup();

                const markerD = L.marker([latD, lngD])
                    .addTo(map)
                    .bindPopup('📍 <b>Destino</b><br>' + recorrido.destino);

                // Línea directa (sombra)
                const polyline = L.polyline([
                    [latO, lngO],
                    [latD, lngD]
                ], {
                    color: '#0d6efd',
                    weight: 3,
                    opacity: 0.4,
                    dashArray: '5, 10'
                }).addTo(map);

                // 🔴 OBTENER LA RUTA REAL CON OSRM
                const url = `https://router.project-osrm.org/route/v1/driving/${lngO},${latO};${lngD},${latD}?overview=full&geometries=geojson`;

                fetch(url)
                    .then(response => {
                        if (!response.ok) throw new Error('HTTP ' + response.status);
                        return response.json();
                    })
                    .then(data => {
                        if (data.routes && data.routes.length > 0) {
                            const route = data.routes[0];
                            const layer = L.geoJSON(route.geometry, {
                                style: {
                                    color: '#0d6efd',
                                    weight: 5,
                                    opacity: 0.8
                                }
                            }).addTo(map);
                            map.fitBounds(layer.getBounds());
                        } else {
                            map.fitBounds(polyline.getBounds());
                        }
                    })
                    .catch(error => {
                        console.error('Error al obtener la ruta:', error);
                        map.fitBounds(polyline.getBounds());
                    });
            });
        </script>
    <?php endif; ?>

</body>

</html>