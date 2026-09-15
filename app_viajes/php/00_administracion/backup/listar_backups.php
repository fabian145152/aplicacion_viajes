<?php
$dir = __DIR__ . '/DDBB/';

$archivos = glob($dir . 'backup_*.sql');
usort($archivos, function ($a, $b) {
    return filemtime($b) - filemtime($a);
});
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Backups</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, sans-serif;
            padding: 30px;
            background: #f0f2f5;
            margin: 0;
        }

        .contenedor {
            max-width: 900px;
            margin: 0 auto;
        }

        h1 {
            color: #333;
            margin-bottom: 20px;
            font-size: 22px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .08);
            border-radius: 8px;
            overflow: hidden;
        }

        th,
        td {
            padding: 12px 16px;
            text-align: left;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        }

        th {
            background: #007bff;
            color: #fff;
        }

        tr:hover td {
            background: #f8fbff;
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
            display: flex;
            gap: 10px;
        }

        .btn {
            display: inline-block;
            padding: 10px 22px;
            background: #6c757d;
            color: #fff;
            text-decoration: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            transition: background .2s;
        }

        .btn:hover {
            background: #5a6268;
        }

        .btn.azul {
            background: #007bff;
        }

        .btn.azul:hover {
            background: #0056b3;
        }
    </style>
</head>

<body>

    <div class="contenedor">

        <h1>📦 Backups disponibles</h1>

        <?php if (empty($archivos)): ?>
            <p class="vacio">No hay backups generados todavía.</p>
        <?php else: ?>
            <table>
                <tr>
                    <th>Archivo</th>
                    <th>Tamaño</th>
                    <th>Fecha</th>
                </tr>
                <?php foreach ($archivos as $f): ?>
                    <?php $nombre = basename($f); ?>
                    <tr>
                        <td><?= htmlspecialchars($nombre) ?></td>
                        <td><?= number_format(filesize($f) / 1024, 1) ?> KB</td>
                        <td><?= date('d/m/Y H:i:s', filemtime($f)) ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>

        <div class="acciones">
            <a href="backup_descarga.php" class="btn azul">💾 Hacer nuevo backup</a>
            <a href="../../inicio_0.php" class="btn">← Volver al menú</a>
        </div>

    </div>

</body>

</html>