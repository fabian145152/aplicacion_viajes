<?php
// ============================================================
// Configuración
// ============================================================
$host     = "localhost";
$user     = "root";
$pass     = "belgrado";
$db_name  = "app_viajes";

$dir_destino = __DIR__ . '/DDBB/';

if (!is_dir($dir_destino)) {
    mkdir($dir_destino, 0777, true);
}

$nombre_archivo = "backup_" . $db_name . "_" . date("Y-m-d_H-i-s") . ".sql";
$ruta_completa  = $dir_destino . $nombre_archivo;

// ============================================================
// Buscador de mysqldump
// ============================================================
$rutas_posibles = [
    "C:/xampp/mysql/bin/mysqldump.exe",
    "D:/xampp/mysql/bin/mysqldump.exe",
    "C:/laragon/bin/mysql/mysql-8.0.30-winx64/bin/mysqldump.exe",
    "C:/wamp64/bin/mysql/mysql8.0.21/bin/mysqldump.exe",
    "mysqldump"
];

$mysqldump_path = "";
foreach ($rutas_posibles as $ruta) {
    if (file_exists($ruta) || $ruta === "mysqldump") {
        $mysqldump_path = $ruta;
        break;
    }
}

// ============================================================
// Ejecutar el backup
// ============================================================
$ok        = false;
$mensaje   = "";
$detalles  = "";

if (empty($mysqldump_path)) {
    $mensaje = "No se pudo encontrar 'mysqldump.exe' en el servidor.";
} else {
    $comando = "\"{$mysqldump_path}\" --host={$host} --user={$user} --password={$pass} {$db_name} > \"{$ruta_completa}\"";

    $salida    = [];
    $resultado = null;
    exec($comando . " 2>&1", $salida, $resultado);

    if (file_exists($ruta_completa) && filesize($ruta_completa) > 0) {
        $ok      = true;
        $mensaje = "Backup creado con éxito.";
        $detalles = $nombre_archivo . " (" . number_format(filesize($ruta_completa) / 1024, 1) . " KB)";
    } else {
        $mensaje  = "Error al generar el backup. El archivo no se creó.";
        $detalles = implode("\n", $salida);
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Backup</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, sans-serif;
            background: #f0f2f5;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
        }

        .card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, .08);
            padding: 30px 40px;
            max-width: 520px;
            width: 100%;
            text-align: center;
        }

        .icono {
            font-size: 48px;
            margin-bottom: 10px;
        }

        .ok {
            color: #28a745;
        }

        .error {
            color: #dc3545;
        }

        h1 {
            font-size: 20px;
            color: #333;
            margin: 0 0 10px;
        }

        p {
            color: #666;
            font-size: 14px;
            margin: 8px 0;
            word-break: break-all;
        }

        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 13px;
        }

        pre {
            background: #f8f8f8;
            padding: 10px;
            border-radius: 6px;
            text-align: left;
            font-size: 12px;
            overflow-x: auto;
            color: #555;
        }

        .btn {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 22px;
            background: #007bff;
            color: #fff;
            text-decoration: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
        }

        .btn:hover {
            background: #0056b3;
        }
    </style>
</head>

<body>

    <div class="card">
        <?php if ($ok): ?>
            <div class="icono ok">✅</div>
            <h1 class="ok"><?= htmlspecialchars($mensaje) ?></h1>
            <p><?= htmlspecialchars($detalles) ?></p>
        <?php else: ?>
            <div class="icono error">❌</div>
            <h1 class="error"><?= htmlspecialchars($mensaje) ?></h1>
            <?php if ($detalles): ?>
                <pre><?= htmlspecialchars($detalles) ?></pre>
            <?php endif; ?>
        <?php endif; ?>

        <a href="listar_backups.php" class="btn">📦 Ver backups</a>
        <a href="../../inicio_0.php" class="btn" style="background:#6c757d;">← Volver</a>
    </div>

</body>

</html>