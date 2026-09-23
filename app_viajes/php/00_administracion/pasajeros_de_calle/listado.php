<?php
include_once "../../../funciones/funciones.php";
protegerPagina([0, 3]);

$pdo = conexion();

// ---- Filtros ----
$filtro  = isset($_GET['filtro']) ? $_GET['filtro'] : 'pendientes';
$buscar  = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';
$msg     = isset($_GET['msg']) ? $_GET['msg'] : '';

// ---- Construir consulta ----
$sql = "SELECT id, nombre_apellido, email, celular, codigo_confirmacion,
               confirmado, fecha_registro, fecha_confirmacion
        FROM pasajeros_app
        WHERE 1=1";
$params = [];

if ($filtro === 'pendientes') {
    $sql .= " AND confirmado = 0";
} elseif ($filtro === 'confirmados') {
    $sql .= " AND confirmado = 1";
}
// si $filtro === 'todos' no agrega nada

if ($buscar !== '') {
    $sql .= " AND (nombre_apellido LIKE :b
                   OR email LIKE :b
                   OR celular LIKE :b)";
    $params[':b'] = '%' . $buscar . '%';
}

$sql .= " ORDER BY fecha_registro DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$pasajeros = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ---- Contadores ----
$totalPendientes = (int)$pdo->query("SELECT COUNT(*) FROM pasajeros_app WHERE confirmado = 0")->fetchColumn();
$totalConfirmados = (int)$pdo->query("SELECT COUNT(*) FROM pasajeros_app WHERE confirmado = 1")->fetchColumn();
$totalTodos = $totalPendientes + $totalConfirmados;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pasajeros App - Confirmaciones</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, sans-serif; padding: 30px;
               background: #f0f2f5; margin: 0; }
        .contenedor { max-width: 1200px; margin: 0 auto; }

        h1 { color: #333; margin-bottom: 5px; font-size: 22px; }
        .subtitulo { color: #666; font-size: 13px; margin-bottom: 20px; }

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
        .btn-azul    { background: #007bff; color: #fff; }
        .btn-azul:hover { background: #0056b3; }
        .btn-gris    { background: #6c757d; color: #fff; }
        .btn-gris:hover { background: #5a6268; }
        .btn-verde   { background: #28a745; color: #fff; }
        .btn-verde:hover { background: #218838; }
        .btn-rojo    { background: #dc3545; color: #fff; }
        .btn-rojo:hover { background: #c82333; }
        .btn-mini    { padding: 4px 10px; font-size: 12px; border-radius: 5px; }

        .alerta {
            padding: 12px 18px; border-radius: 6px; margin-bottom: 15px;
            font-size: 14px; font-weight: 600;
        }
        .alerta-ok    { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alerta-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

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

        .badge {
            display: inline-block; padding: 3px 10px; border-radius: 10px;
            font-size: 11px; font-weight: 700;
        }
        .badge-pendiente  { background: #fff3cd; color: #856404; }
        .badge-confirmado { background: #d4edda; color: #155724; }

        .codigo {
            display: inline-block; padding: 3px 10px; background: #e9ecef;
            border-radius: 4px; font-family: 'Courier New', monospace;
            font-size: 13px; font-weight: 700; color: #495057;
            letter-spacing: 2px;
        }

        .acciones { display: flex; gap: 6px; }

        .obs-corta {
            max-width: 200px; display: inline-block;
            white-space: nowrap; overflow: hidden;
            text-overflow: ellipsis; vertical-align: middle;
        }

        .btn-volver {
            display: inline-block; padding: 10px 22px; background: #6c757d;
            color: #fff; text-decoration: none; border-radius: 6px;
            font-size: 14px; font-weight: 600; margin-top: 20px;
        }
        .btn-volver:hover { background: #5a6268; }
    </style>
</head>
<body>

<div class="contenedor">

    <h1>📱 Pasajeros registrados en la App</h1>
    <p class="subtitulo">Confirmá las cuentas nuevas para que puedan loguearse.</p>

    <?php if ($msg === 'confirmado'): ?>
        <div class="alerta alerta-ok">✅ Pasajero confirmado correctamente.</div>
    <?php elseif ($msg === 'eliminado'): ?>
        <div class="alerta alerta-ok">✅ Pasajero eliminado correctamente.</div>
    <?php elseif ($msg === 'error'): ?>
        <div class="alerta alerta-error">❌ Hubo un error al procesar la operación.</div>
    <?php endif; ?>

    <div class="tabs">
        <a href="?filtro=pendientes" class="<?= $filtro === 'pendientes' ? 'activo' : '' ?>">
            ⏳ Pendientes <span class="num"><?= $totalPendientes ?></span>
        </a>
        <a href="?filtro=confirmados" class="<?= $filtro === 'confirmados' ? 'activo' : '' ?>">
            ✅ Confirmados <span class="num"><?= $totalConfirmados ?></span>
        </a>
        <a href="?filtro=todos" class="<?= $filtro === 'todos' ? 'activo' : '' ?>">
            📋 Todos <span class="num"><?= $totalTodos ?></span>
        </a>
    </div>

    <form method="get" class="barra">
        <input type="hidden" name="filtro" value="<?= htmlspecialchars($filtro) ?>">
        <input type="text" name="buscar"
               placeholder="Buscar por nombre, email o celular..."
               value="<?= htmlspecialchars($buscar) ?>">
        <button type="submit" class="btn btn-azul">🔍 Buscar</button>
        <?php if ($buscar !== ''): ?>
            <a href="?filtro=<?= htmlspecialchars($filtro) ?>" class="btn btn-gris">🗑️ Limpiar</a>
        <?php endif; ?>
    </form>

    <?php if (empty($pasajeros)): ?>
        <p class="vacio">
            <?php if ($filtro === 'pendientes'): ?>
                No hay pasajeros pendientes de confirmación. 🎉
            <?php elseif ($filtro === 'confirmados'): ?>
                Todavía no hay pasajeros confirmados.
            <?php else: ?>
                No hay pasajeros registrados todavía.
            <?php endif; ?>
        </p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th style="width:50px;">ID</th>
                    <th>Nombre y apellido</th>
                    <th>Email</th>
                    <th>Celular</th>
                    <th style="width:90px;">Código</th>
                    <th>Registro</th>
                    <th>Confirmación</th>
                    <th style="width:100px;">Estado</th>
                    <th style="width:190px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pasajeros as $p): ?>
                    <tr>
                        <td><?= (int)$p['id'] ?></td>
                        <td><?= htmlspecialchars($p['nombre_apellido']) ?></td>
                        <td><?= htmlspecialchars($p['email']) ?></td>
                        <td><?= htmlspecialchars($p['celular']) ?></td>
                        <td><span class="codigo"><?= htmlspecialchars($p['codigo_confirmacion'] ?? '-') ?></span></td>
                        <td><?= date('d/m/Y H:i', strtotime($p['fecha_registro'])) ?></td>
                        <td>
                            <?= $p['fecha_confirmacion']
                                ? date('d/m/Y H:i', strtotime($p['fecha_confirmacion']))
                                : '-' ?>
                        </td>
                        <td>
                            <?php if ((int)$p['confirmado'] === 1): ?>
                                <span class="badge badge-confirmado">✅ Confirmado</span>
                            <?php else: ?>
                                <span class="badge badge-pendiente">⏳ Pendiente</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="acciones">
                                <?php if ((int)$p['confirmado'] === 0): ?>
                                    <a class="btn btn-verde btn-mini"
                                       href="confirmar.php?id=<?= (int)$p['id'] ?>"
                                       onclick="return confirm('¿Confirmar a <?= htmlspecialchars(addslashes($p['nombre_apellido'])) ?>?');">
                                       ✅ Confirmar
                                    </a>
                                <?php endif; ?>
                                <a class="btn btn-rojo btn-mini"
                                   href="confirmar.php?accion=eliminar&id=<?= (int)$p['id'] ?>"
                                   onclick="return confirm('¿Eliminar definitivamente a <?= htmlspecialchars(addslashes($p['nombre_apellido'])) ?>?');">
                                   🗑️
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <a href="../../inicio_0.php" class="btn-volver">← Volver al menú</a>

</div>

</body>
</html>