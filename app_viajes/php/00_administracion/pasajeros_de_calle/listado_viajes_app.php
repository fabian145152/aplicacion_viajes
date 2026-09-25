<?php
include_once "../../../funciones/funciones.php";
protegerPagina([0, 3]);

$pdo = conexion();

// ---- Filtros ----
$filtro  = isset($_GET['filtro']) ? $_GET['filtro'] : 'activos';
$buscar  = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';

// ---- Construir consulta ----
$sql = "SELECT v.id, v.creado_por, v.nombre, v.direccion, v.cp, v.Celular,
               v.fecha_creado, v.fecha_realizado, v.fecha_cerrado, v.tomado_por,
               p.email AS pasajero_email
        FROM pasajeros_app_viajes v
        LEFT JOIN pasajeros_app p ON p.id = v.creado_por
        WHERE 1=1";

$params = [];

if ($filtro === 'activos') {
    $sql .= " AND v.fecha_cerrado IS NULL";
} elseif ($filtro === 'cerrados') {
    $sql .= " AND v.fecha_cerrado IS NOT NULL";
}
// si $filtro === 'todos', no agrega nada

if ($buscar !== '') {
    $sql .= " AND (v.nombre LIKE :b 
                OR v.direccion LIKE :b 
                OR v.Celular LIKE :b 
                OR v.cp LIKE :b
                OR p.email LIKE :b)";
    $params[':b'] = '%' . $buscar . '%';
}

$sql .= " ORDER BY v.fecha_creado DESC, v.id DESC LIMIT 500";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$viajes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ---- Contadores ----
$totalActivos  = (int)$pdo->query("SELECT COUNT(*) FROM pasajeros_app_viajes WHERE fecha_cerrado IS NULL")->fetchColumn();
$totalCerrados = (int)$pdo->query("SELECT COUNT(*) FROM pasajeros_app_viajes WHERE fecha_cerrado IS NOT NULL")->fetchColumn();
$totalTodos    = $totalActivos + $totalCerrados;

// ---- Estadísticas del día ----
$hoy = date('Y-m-d');
$stmt = $pdo->prepare("SELECT COUNT(*) FROM pasajeros_app_viajes WHERE DATE(fecha_creado) = :hoy");
$stmt->execute([':hoy' => $hoy]);
$totalHoy = (int)$stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Viajes desde la App</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, sans-serif; padding: 30px;
               background: #f0f2f5; margin: 0; }
        .contenedor { max-width: 1400px; margin: 0 auto; }

        h1 { color: #333; margin-bottom: 5px; font-size: 22px; }
        .subtitulo { color: #666; font-size: 13px; margin-bottom: 20px; }

        /* ===== TABS ===== */
        .tabs {
            display: flex; gap: 8px; margin-bottom: 15px; flex-wrap: wrap;
        }
        .tabs a {
            padding: 8px 18px; background: #fff; color: #495057;
            text-decoration: none; border-radius: 6px; font-size: 13px;
            font-weight: 600; border: 1px solid #dee2e6;
            transition: all .15s;
        }
        .tabs a:hover { background: #e9ecef; }
        .tabs a.activo {
            background: #007bff; color: #fff; border-color: #007bff;
        }
        .tabs a .num {
            display: inline-block; background: rgba(255,255,255,.25);
            padding: 0 6px; border-radius: 8px; margin-left: 5px; font-size: 11px;
        }
        .tabs a.activo .num { background: rgba(255,255,255,.3); }

        /* ===== BARRA DE BÚSQUEDA ===== */
        .barra {
            display: flex; gap: 10px; margin-bottom: 15px;
            align-items: center; flex-wrap: wrap;
        }
        .barra input {
            flex: 1; min-width: 200px; padding: 8px 12px;
            border: 1px solid #ced4da; border-radius: 6px; font-size: 13px;
        }
        .btn {
            padding: 8px 16px; border: none; border-radius: 6px;
            font-weight: 600; font-size: 13px; cursor: pointer;
            text-decoration: none; display: inline-block;
            transition: background .15s;
        }
        .btn-azul  { background: #007bff; color: #fff; }
        .btn-azul:hover { background: #0056b3; }
        .btn-gris  { background: #6c757d; color: #fff; }
        .btn-gris:hover { background: #5a6268; }

        /* ===== ESTADÍSTICAS ===== */
        .stats {
            display: flex; gap: 15px; margin-bottom: 20px; flex-wrap: wrap;
        }
        .stat {
            background: #fff; padding: 12px 20px; border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0,0,0,.06); flex: 1;
            min-width: 150px; border-left: 4px solid #007bff;
        }
        .stat .numero {
            font-size: 24px; font-weight: 800; color: #333; display: block;
        }
        .stat .label {
            font-size: 11px; color: #666; text-transform: uppercase;
            letter-spacing: .5px; margin-top: 2px;
        }
        .stat.naranja { border-left-color: #fd7e14; }
        .stat.verde   { border-left-color: #28a745; }
        .stat.gris    { border-left-color: #6c757d; }

        /* ===== TABLA ===== */
        table {
            width: 100%; border-collapse: collapse; background: #fff;
            box-shadow: 0 2px 8px rgba(0,0,0,.08); border-radius: 8px;
            overflow: hidden; font-size: 13px;
        }
        th, td {
            padding: 8px 12px; text-align: left;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }
        th {
            background: #343a40; color: #fff;
            font-size: 11px; text-transform: uppercase;
            letter-spacing: .5px;
        }
        tr:hover td { background: #f8fbff; }

        .vacio {
            text-align: center; color: #888; padding: 50px;
            background: #fff; border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,.08); font-size: 15px;
        }

        /* ===== BADGES ===== */
        .badge {
            display: inline-block; padding: 3px 10px; border-radius: 10px;
            font-size: 11px; font-weight: 700;
        }
        .badge-pendiente  { background: #fff3cd; color: #856404; }
        .badge-asignado   { background: #cfe2ff; color: #084298; }
        .badge-completo   { background: #d4edda; color: #155724; }

        .id-viaje {
            font-family: 'Courier New', monospace; font-weight: 700;
            color: #495057;
        }

        .direccion {
            max-width: 250px; overflow: hidden;
            text-overflow: ellipsis; white-space: nowrap;
        }

        .acciones { display: flex; gap: 6px; }
        .btn-mini { padding: 4px 10px; font-size: 11px; border-radius: 4px; }

        .btn-volver {
            display: inline-block; padding: 10px 22px; background: #6c757d;
            color: #fff; text-decoration: none; border-radius: 6px;
            font-size: 14px; font-weight: 600; margin-top: 20px;
        }
        .btn-volver:hover { background: #5a6268; }

        .contador {
            font-size: 13px; color: #666; margin-bottom: 10px;
        }
    </style>
</head>
<body>

<div class="contenedor">

    <h1>🚕 Viajes pedidos desde la App</h1>
    <p class="subtitulo">Listado de todos los viajes cargados por los pasajeros en el celular.</p>

    <!-- ESTADÍSTICAS -->
    <div class="stats">
        <div class="stat naranja">
            <span class="numero"><?= number_format($totalHoy) ?></span>
            <span class="label">Viajes hoy</span>
        </div>
        <div class="stat">
            <span class="numero"><?= number_format($totalActivos) ?></span>
            <span class="label">Activos (sin cerrar)</span>
        </div>
        <div class="stat verde">
            <span class="numero"><?= number_format($totalCerrados) ?></span>
            <span class="label">Completados</span>
        </div>
        <div class="stat gris">
            <span class="numero"><?= number_format($totalTodos) ?></span>
            <span class="label">Total histórico</span>
        </div>
    </div>

    <!-- TABS -->
    <div class="tabs">
        <a href="?filtro=activos" class="<?= $filtro === 'activos' ? 'activo' : '' ?>">
            🟠 Activos <span class="num"><?= $totalActivos ?></span>
        </a>
        <a href="?filtro=cerrados" class="<?= $filtro === 'cerrados' ? 'activo' : '' ?>">
            ✅ Cerrados <span class="num"><?= $totalCerrados ?></span>
        </a>
        <a href="?filtro=todos" class="<?= $filtro === 'todos' ? 'activo' : '' ?>">
            📋 Todos <span class="num"><?= $totalTodos ?></span>
        </a>
    </div>

    <!-- BUSCADOR -->
    <form method="get" class="barra">
        <input type="hidden" name="filtro" value="<?= htmlspecialchars($filtro) ?>">
        <input type="text" name="buscar"
               placeholder="Buscar por nombre, dirección, celular, CP o email..."
               value="<?= htmlspecialchars($buscar) ?>">
        <button type="submit" class="btn btn-azul">🔍 Buscar</button>
        <?php if ($buscar !== ''): ?>
            <a href="?filtro=<?= htmlspecialchars($filtro) ?>" class="btn btn-gris">🗑️ Limpiar</a>
        <?php endif; ?>
    </form>

    <!-- TABLA -->
    <?php if (empty($viajes)): ?>
        <p class="vacio">
            <?php if ($filtro === 'activos'): ?>
                No hay viajes activos. 🎉
            <?php elseif ($filtro === 'cerrados'): ?>
                Todavía no hay viajes completados.
            <?php else: ?>
                No hay viajes registrados todavía.
            <?php endif; ?>
        </p>
    <?php else: ?>
        <p class="contador">
            Mostrando <strong><?= count($viajes) ?></strong> viaje(s).
        </p>
        <table>
            <thead>
                <tr>
                    <th style="width:60px;">N°</th>
                    <th>Pasajero</th>
                    <th>Celular</th>
                    <th>Dirección</th>
                    <th style="width:70px;">CP</th>
                    <th>Creado</th>
                    <th>Realizado</th>
                    <th>Cerrado</th>
                    <th>Móvil</th>
                    <th style="width:100px;">Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($viajes as $v): ?>
                    <?php
                        $estado = 'PENDIENTE';
                        $clase = 'badge-pendiente';
                        if ($v['fecha_cerrado'] != null) {
                            $estado = 'COMPLETO';
                            $clase = 'badge-completo';
                        } elseif ($v['tomado_por'] != null && $v['tomado_por'] != 0) {
                            $estado = 'ASIGNADO';
                            $clase = 'badge-asignado';
                        }
                    ?>
                    <tr>
                        <td><span class="id-viaje">#<?= (int)$v['id'] ?></span></td>
                        <td>
                            <strong><?= htmlspecialchars($v['nombre']) ?></strong>
                            <?php if (!empty($v['pasajero_email'])): ?>
                                <br><small style="color:#888;font-size:11px;">
                                    <?= htmlspecialchars($v['pasajero_email']) ?>
                                </small>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($v['Celular']) ?></td>
                        <td>
                            <span class="direccion" title="<?= htmlspecialchars($v['direccion']) ?>">
                                <?= htmlspecialchars($v['direccion']) ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($v['cp']) ?></td>
                        <td><?= $v['fecha_creado'] ? date('d/m/Y H:i', strtotime($v['fecha_creado'])) : '-' ?></td>
                        <td><?= $v['fecha_realizado'] ? date('d/m/Y', strtotime($v['fecha_realizado'])) : '-' ?></td>
                        <td><?= $v['fecha_cerrado'] ? date('d/m/Y', strtotime($v['fecha_cerrado'])) : '-' ?></td>
                        <td>
                            <?php if ($v['tomado_por'] != null && $v['tomado_por'] != 0): ?>
                                <span class="id-viaje"><?= (int)$v['tomado_por'] ?></span>
                            <?php else: ?>
                                <span style="color:#aaa;">—</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge <?= $clase ?>"><?= $estado ?></span></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <a href="../../inicio_0.php" class="btn-volver">← Volver al menú</a>

</div>

</body>
</html>