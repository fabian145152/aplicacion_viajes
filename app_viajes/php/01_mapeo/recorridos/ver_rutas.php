<?php
include_once "../../../funciones/funciones.php";
protegerPagina([0]);

$pdo = conexion();

$sql = "SELECT v.id,
               v.nombre_pasaj,
               v.fecha,
               v.hora,
               v.estado,
               v.direccion_origen,
               v.direccion_destino,
               COUNT(u.id)  AS total_ubicaciones,
               MIN(u.fecha) AS primera,
               MAX(u.fecha) AS ultima,
               MAX(u.movil) AS movil
        FROM viajes_despacho v
        INNER JOIN ubicaciones u ON u.viaje_id = v.id
        WHERE u.viaje_id > 0
        GROUP BY v.id
        ORDER BY MAX(u.fecha) DESC
        LIMIT 300";

$stmt = $pdo->query($sql);
$viajes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Ubicaciones por viaje</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, sans-serif;
            background: #f0f2f5;
            padding: 30px;
            margin: 0;
        }

        .contenedor {
            max-width: 1200px;
            margin: 0 auto;
        }

        h1 {
            color: #333;
            font-size: 22px;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .08);
            border-radius: 8px;
            overflow: hidden;
            font-size: 13px;
        }

        th,
        td {
            padding: 10px 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background: #007bff;
            color: #fff;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .5px;
        }

        tr:hover td {
            background: #f8fbff;
        }

        .btn {
            display: inline-block;
            padding: 6px 14px;
            background: #28a745;
            color: #fff;
            text-decoration: none;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
        }

        .btn:hover {
            background: #218838;
        }

        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: 600;
        }

        .badge-completo {
            background: #d4edda;
            color: #155724;
        }

        .badge-curso {
            background: #fff3cd;
            color: #856404;
        }

        .badge-cancel {
            background: #f8d7da;
            color: #721c24;
        }

        .badge-default {
            background: #e2e3e5;
            color: #383d41;
        }

        .vacio {
            text-align: center;
            color: #888;
            padding: 40px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .08);
        }

        .acciones {
            margin-top: 20px;
        }

        .volver {
            display: inline-block;
            padding: 10px 22px;
            background: #6c757d;
            color: #fff;
            text-decoration: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
        }

        .volver:hover {
            background: #5a6268;
        }

        .movil {
            font-family: 'Courier New', monospace;
            font-weight: 700;
        }
    </style>
</head>

<body>

    <div class="contenedor">

        <h1>🗺️ Ubicaciones registradas por viaje</h1>

        <?php if (empty($viajes)): ?>
            <p class="vacio">Todavía no hay ubicaciones asociadas a viajes.</p>
        <?php else: ?>
            <table>
                <tr>
                    <th>Viaje</th>
                    <th>Pasajero</th>
                    <th>Móvil</th>
                    <th>Fecha del viaje</th>
                    <th>Puntos</th>
                    <th>Primer punto</th>
                    <th>Último punto</th>
                    <th>Estado</th>
                    <th></th>
                </tr>
                <?php foreach ($viajes as $v): ?>
                    <?php
                    $estado = strtolower($v['estado'] ?? '');
                    if ($estado === 'completo')       $cls = 'badge-completo';
                    elseif ($estado === 'en curso')   $cls = 'badge-curso';
                    elseif ($estado === 'cancelado')  $cls = 'badge-cancel';
                    else                              $cls = 'badge-default';
                    ?>
                    <tr>
                        <td>#<?= (int)$v['id'] ?></td>
                        <td><?= htmlspecialchars($v['nombre_pasaj']) ?></td>
                        <td class="movil"><?= htmlspecialchars($v['movil']) ?></td>
                        <td><?= htmlspecialchars($v['fecha']) ?> <?= htmlspecialchars($v['hora']) ?></td>
                        <td><strong><?= (int)$v['total_ubicaciones'] ?></strong></td>
                        <td><?= htmlspecialchars($v['primera']) ?></td>
                        <td><?= htmlspecialchars($v['ultima']) ?></td>
                        <td><span class="badge <?= $cls ?>"><?= htmlspecialchars($v['estado']) ?></span></td>
                        <td>
                            <a class="btn"
                                href="mapa_ubicaciones_viaje.php?id=<?= (int)$v['id'] ?>">
                                🗺️ Ver mapa
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>

        <div class="acciones">
            <a href="../../inicio_0.php" class="volver">← Volver al menú</a>
        </div>

    </div>

</body>

</html>