<?php
include_once "../../funciones/funciones.php";
protegerPagina([0, 3]);

$conn = conexion();

$usuario = nombre_usuario();

$nombre_usuario = $usuario['nombre'];
$usuario_id = $usuario['id'];
// Obtener todos los viajes ordenados por ID descendente
$sql = "SELECT 
            vd.id,
            vd.fecha,
            vd.hora,
            vd.nombre_pasaj,
            vd.direccion_origen,
            vd.direccion_destino,
            vd.estado,
            vd.asignado_a,
            vd.fecha_asignacion,
            vd.fecha_finalizacion,
            vd.km_recorridos,
            c.nombre AS chofer_nombre,
            c.apellido AS chofer_apellido,
            ve.patente AS vehiculo_patente,
            (SELECT movil FROM recorridos_viaje WHERE id_viaje = vd.id ORDER BY id DESC LIMIT 1) AS movil_historico
        FROM viajes_despacho vd
        LEFT JOIN choferes c ON c.movil = vd.asignado_a
        LEFT JOIN vehiculos ve ON ve.id_chofer = c.id
        ORDER BY vd.id DESC";

$stmt = $conn->prepare($sql);
$stmt->execute();
$viajes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Contar viajes por estado
$estados = [];
foreach ($viajes as $v) {
    $estado = $v['estado'] ?? 'Sin estado';
    $estados[$estado] = ($estados[$estado] ?? 0) + 1;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listado Completo de Viajes</title>
    <link rel="stylesheet" href="../../../css/estilos.css">
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f0f2f5;
            padding: 20px;
            margin: 0;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: #fff;
            padding: 25px 30px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #007bff;
            padding-bottom: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #333;
        }

        .header .total {
            font-size: 16px;
            color: #555;
        }

        .header .total strong {
            color: #007bff;
        }

        .btn-volver {
            display: inline-block;
            padding: 8px 16px;
            background: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 14px;
        }

        .btn-volver:hover {
            background: #5a6268;
        }

        .filtros {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
            flex-wrap: wrap;
            align-items: center;
        }

        .filtros input[type="text"],
        .filtros select {
            padding: 8px 12px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
        }

        .filtros input[type="text"] {
            flex: 1;
            min-width: 200px;
        }

        .filtros button {
            padding: 8px 20px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }

        .filtros button:hover {
            background: #0056b3;
        }

        .badge-estado {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
            color: #fff;
            min-width: 65px;
            text-align: center;
        }

        .badge-pendiente {
            background: #ffc107;
            color: #333;
        }

        .badge-diferido {
            background: #fd7e14;
        }

        .badge-en-curso {
            background: #0d6efd;
        }

        .badge-asignado {
            background: #17a2b8;
        }

        .badge-completo {
            background: #28a745;
        }

        .badge-cancelado {
            background: #dc3545;
        }

        .badge-default {
            background: #6c757d;
        }

        .tabla-container {
            overflow-x: auto;
            margin-top: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        thead {
            background: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
        }

        thead th {
            padding: 10px 12px;
            text-align: left;
            font-weight: 600;
            color: #495057;
            white-space: nowrap;
            cursor: pointer;
            user-select: none;
        }

        thead th:hover {
            background: #e9ecef;
        }

        tbody tr {
            border-bottom: 1px solid #e9ecef;
            cursor: pointer;
            transition: background 0.15s;
        }

        tbody tr:hover {
            background: #f1f3f5;
        }

        tbody tr.clickeable {
            cursor: pointer;
        }

        tbody td {
            padding: 8px 12px;
            vertical-align: middle;
        }

        tbody td .movil-badge {
            display: inline-block;
            background: #e9ecef;
            padding: 1px 8px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: bold;
        }

        .empty-row td {
            text-align: center;
            padding: 40px;
            color: #6c757d;
            font-size: 16px;
        }

        .estadisticas {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 15px;
        }

        .estadisticas .item {
            background: #f8f9fa;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 13px;
        }

        .estadisticas .item .num {
            font-weight: bold;
            color: #007bff;
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }

            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            table {
                font-size: 12px;
            }

            thead th,
            tbody td {
                padding: 6px 8px;
            }
        }

        @media print {
            .no-print {
                display: none !important;
            }

            .container {
                box-shadow: none;
                padding: 10px;
            }

            tbody tr:hover {
                background: none !important;
            }
        }
    </style>
</head>

<body>

    <div class="container">
        <span <strong><?php echo $nombre_usuario ?></strong></span>

        <div class="header">
            <div>
                <h1>📋 Listado de Viajes</h1>
                <span class="total">Total: <strong><?= count($viajes) ?></strong> viajes</span>
            </div>
            <div class="no-print">
                <a href="../inicio_0.php" class="btn-volver">← Volver al Panel</a>
                <button class="btn-volver" onclick="window.print()" style="background:#28a745;">🖨️ Imprimir</button>
            </div>
        </div>

        <!-- Estadísticas -->
        <div class="estadisticas no-print">
            <?php foreach ($estados as $estado => $cantidad): ?>
                <span class="item">
                    <span class="badge-estado badge-<?= strtolower(str_replace(' ', '-', $estado)) ?>">
                        <?= $estado ?>
                    </span>
                    <span class="num"><?= $cantidad ?></span>
                </span>
            <?php endforeach; ?>
        </div>

        <!-- Filtros -->
        <div class="filtros no-print">
            <input type="text" id="buscar" placeholder="🔍 Buscar por pasajero, origen, destino o móvil...">
            <select id="filtro_estado">
                <option value="">Todos los estados</option>
                <?php foreach (array_keys($estados) as $estado): ?>
                    <option value="<?= $estado ?>"><?= $estado ?></option>
                <?php endforeach; ?>
            </select>
            <button onclick="aplicarFiltros()">Filtrar</button>
            <button onclick="resetFiltros()" style="background:#6c757d;">Limpiar</button>
        </div>

        <!-- Tabla -->
        <div class="tabla-container">
            <table>
                <thead>
                    <tr>
                        <th onclick="ordenarTabla(0)">#ID</th>
                        <th onclick="ordenarTabla(1)">Fecha</th>
                        <th onclick="ordenarTabla(2)">Hora</th>
                        <th onclick="ordenarTabla(3)">Pasajero</th>
                        <th onclick="ordenarTabla(4)">Origen</th>
                        <th onclick="ordenarTabla(5)">Destino</th>
                        <th onclick="ordenarTabla(6)">Estado</th>
                        <th onclick="ordenarTabla(7)">Móvil</th>
                        <th onclick="ordenarTabla(8)">Chofer</th>
                        <th onclick="ordenarTabla(9)">Patente</th>
                        <th onclick="ordenarTabla(10)">Km</th>
                    </tr>
                </thead>
                <tbody id="tabla-body">
                    <?php if (empty($viajes)): ?>
                        <tr class="empty-row">
                            <td colspan="11">No hay viajes registrados</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($viajes as $v): ?>
                            <?php
                            $estadoClass = strtolower(str_replace(' ', '-', $v['estado'] ?? 'default'));
                            $movil = $v['asignado_a'];
                            if ($v['estado'] == 'Completo' && !empty($v['movil_historico'])) {
                                $movil = $v['movil_historico'];
                            }
                            $chofer = ($v['chofer_nombre'] ?? '') . ' ' . ($v['chofer_apellido'] ?? '');
                            $chofer = trim($chofer) ?: 'N/A';
                            ?>
                            <tr class="clickeable" onclick="verDetalle(<?= $v['id'] ?>)">
                                <td><strong>#<?= $v['id'] ?></strong></td>
                                <td><?= htmlspecialchars($v['fecha'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($v['hora'] ?? '-') ?></td>
                                <td><strong><?= htmlspecialchars($v['nombre_pasaj'] ?? 'N/A') ?></strong></td>
                                <td><?= htmlspecialchars($v['direccion_origen'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($v['direccion_destino'] ?? '-') ?></td>
                                <td>
                                    <span class="badge-estado badge-<?= $estadoClass ?>">
                                        <?= $v['estado'] ?? 'N/A' ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="movil-badge"><?= $movil ?: 'N/A' ?></span>
                                </td>
                                <td><?= htmlspecialchars($chofer) ?></td>
                                <td><?= htmlspecialchars($v['vehiculo_patente'] ?? '-') ?></td>
                                <td><?= $v['km_recorridos'] ? number_format($v['km_recorridos'], 1) . ' km' : '-' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>

    <script>
        // Función para ver el detalle del viaje
        function verDetalle(id) {
            window.location.href = 'detalle_de_viajes.php?id=' + id;
        }

        // Función para filtrar la tabla
        function aplicarFiltros() {
            const buscar = document.getElementById('buscar').value.toLowerCase();
            const estado = document.getElementById('filtro_estado').value;
            const rows = document.querySelectorAll('#tabla-body tr:not(.empty-row)');
            let visibles = 0;

            rows.forEach(row => {
                const texto = row.textContent.toLowerCase();
                const estadoCelda = row.querySelector('.badge-estado');
                const estadoTexto = estadoCelda ? estadoCelda.textContent.trim() : '';

                let mostrar = true;
                if (buscar && !texto.includes(buscar)) mostrar = false;
                if (estado && estadoTexto !== estado) mostrar = false;

                row.style.display = mostrar ? '' : 'none';
                if (mostrar) visibles++;
            });

            // Mostrar mensaje si no hay resultados
            const emptyRow = document.querySelector('.empty-row');
            if (emptyRow) {
                if (visibles === 0) {
                    emptyRow.style.display = '';
                    emptyRow.innerHTML = '<td colspan="11" style="text-align:center;padding:30px;color:#6c757d;">No se encontraron viajes con esos filtros</td>';
                } else {
                    emptyRow.style.display = 'none';
                }
            }
        }

        // Función para resetear filtros
        function resetFiltros() {
            document.getElementById('buscar').value = '';
            document.getElementById('filtro_estado').value = '';
            document.querySelectorAll('#tabla-body tr:not(.empty-row)').forEach(row => {
                row.style.display = '';
            });
            const emptyRow = document.querySelector('.empty-row');
            if (emptyRow) emptyRow.style.display = 'none';
        }

        // Función para ordenar la tabla (simple)
        let ordenAsc = true;
        let ultimaColumna = -1;

        function ordenarTabla(columna) {
            const tbody = document.getElementById('tabla-body');
            const rows = Array.from(tbody.querySelectorAll('tr:not(.empty-row)'));
            if (rows.length === 0) return;

            if (ultimaColumna === columna) {
                ordenAsc = !ordenAsc;
            } else {
                ordenAsc = true;
                ultimaColumna = columna;
            }

            rows.sort((a, b) => {
                const aVal = a.cells[columna]?.textContent.trim() || '';
                const bVal = b.cells[columna]?.textContent.trim() || '';
                return ordenAsc ? aVal.localeCompare(bVal) : bVal.localeCompare(aVal);
            });

            rows.forEach(row => tbody.appendChild(row));

            // Actualizar indicadores de orden (opcional)
            const headers = document.querySelectorAll('thead th');
            headers.forEach((th, i) => {
                th.textContent = th.textContent.replace(/ [▲▼]/, '');
                if (i === columna) {
                    th.textContent += ordenAsc ? ' ▲' : ' ▼';
                }
            });
        }

        // Enter para filtrar
        document.getElementById('buscar').addEventListener('keyup', function(e) {
            if (e.key === 'Enter') aplicarFiltros();
        });

        // Click en el estado del filtro
        document.getElementById('filtro_estado').addEventListener('change', aplicarFiltros);

        // Inicializar el orden por ID descendente (ya viene así de la base de datos)
        // Mostrar todas las filas
        resetFiltros();

        console.log('📋 Listado de viajes cargado correctamente');
    </script>

</body>

</html>