<?php
// ============================================================
// CONFIGURACIÓN DE TIEMPOS PARA VIAJES DIFERIDOS
// ============================================================

// Definir la ruta completa del archivo de configuración
$config_file = __DIR__ . '/min_diferido_config.php';

// Variables por defecto
$MIN_DIFERIDO = 60;
$TIEMPO_AIR = 30;

// Si el archivo existe, cargarlo
if (file_exists($config_file)) {
    include_once $config_file;
}

// Si las variables no se definieron, usar valores por defecto
if (!isset($MIN_DIFERIDO)) $MIN_DIFERIDO = 60;
if (!isset($TIEMPO_AIR)) $TIEMPO_AIR = 30;

// ============================================================
// PROCESAR GUARDADO DE CONFIGURACIÓN
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Guardar configuración de diferidos
    if (isset($_POST['guardar_diferido']) && isset($_POST['nuevo_min_diferido'])) {
        $nuevo_valor = (int)$_POST['nuevo_min_diferido'];
        if ($nuevo_valor > 0) {
            $tiempo_air = isset($TIEMPO_AIR) ? $TIEMPO_AIR : 30;

            // Guardamos usando variables
            $contenido = "<?php\n";
            $contenido .= "// ============================================================\n";
            $contenido .= "// CONFIGURACIÓN DE TIEMPOS PARA VIAJES DIFERIDOS\n";
            $contenido .= "// Generado automáticamente - " . date('d/m/Y H:i:s') . "\n";
            $contenido .= "// ============================================================\n\n";
            $contenido .= "\$MIN_DIFERIDO = " . $nuevo_valor . ";\n";
            $contenido .= "\$TIEMPO_AIR = " . $tiempo_air . ";\n";
            $contenido .= "?>";

            if (file_put_contents($config_file, $contenido)) {
                // Actualizar variables locales
                $MIN_DIFERIDO = $nuevo_valor;
                header("Location: " . $_SERVER['PHP_SELF'] . "?guardado=1");
                exit;
            } else {
                $error = "❌ No se pudo guardar el archivo. Verifica los permisos de la carpeta.";
            }
        }
    }

    // Guardar configuración de tiempo en aire
    if (isset($_POST['guardar_aire']) && isset($_POST['nuevo_tiempo_aire'])) {
        $nuevo_valor = (int)$_POST['nuevo_tiempo_aire'];
        if ($nuevo_valor > 0) {
            $min_diferido = isset($MIN_DIFERIDO) ? $MIN_DIFERIDO : 60;

            $contenido = "<?php\n";
            $contenido .= "// ============================================================\n";
            $contenido .= "// CONFIGURACIÓN DE TIEMPOS PARA VIAJES DIFERIDOS\n";
            $contenido .= "// Generado automáticamente - " . date('d/m/Y H:i:s') . "\n";
            $contenido .= "// ============================================================\n\n";
            $contenido .= "\$MIN_DIFERIDO = " . $min_diferido . ";\n";
            $contenido .= "\$TIEMPO_AIR = " . $nuevo_valor . ";\n";
            $contenido .= "?>";

            if (file_put_contents($config_file, $contenido)) {
                $TIEMPO_AIR = $nuevo_valor;
                header("Location: " . $_SERVER['PHP_SELF'] . "?guardado=1");
                exit;
            } else {
                $error = "❌ No se pudo guardar el archivo. Verifica los permisos de la carpeta.";
            }
        }
    }
}

// Mostrar mensaje de éxito si se guardó
if (isset($_GET['guardado'])) {
    $mensaje = "✅ Configuración guardada correctamente.";
    // Recargar configuración después de guardar
    if (file_exists($config_file)) {
        include_once $config_file;
        if (isset($MIN_DIFERIDO)) {
            define('MIN_DIFERIDO', $MIN_DIFERIDO);
        }
        if (isset($TIEMPO_AIR)) {
            define('TIEMPO_AIR', $TIEMPO_AIR);
        }
    }
}

// Asegurar que las constantes estén definidas para el resto del sistema
if (!defined('MIN_DIFERIDO')) {
    define('MIN_DIFERIDO', isset($MIN_DIFERIDO) ? $MIN_DIFERIDO : 60);
}
if (!defined('TIEMPO_AIR')) {
    define('TIEMPO_AIR', isset($TIEMPO_AIR) ? $TIEMPO_AIR : 30);
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración Diferido</title>
    <link rel="stylesheet" href="min_diferidos.css">

    <script>
        function confirmarDiferido() {
            const minutos = document.getElementById('nuevo_min_diferido').value;
            return confirm(`⚠️ ¿Estás seguro de modificar el límite de viajes diferidos a ${minutos} minutos?`);
        }

        function confirmarAire() {
            const minutos = document.getElementById('nuevo_tiempo_aire').value;
            return confirm(`⚠️ ¿Estás seguro de modificar la alerta de tiempo en el aire a los ${minutos} minutos?`);
        }
    </script>
</head>

<body>
    <div class="main-wrapper">
        <h1>AJUSTES DE TEMPORIZADORES</h1>
        <p style="color:#666; margin-bottom:20px;">Tiempo mínimo para que un viaje sea considerado "Diferido" (en minutos antes de la hora programada)</p>

        <?php if (isset($mensaje)): ?>
            <div style="background:#d4edda; color:#155724; padding:12px 20px; border-radius:5px; margin-bottom:15px; border:1px solid #c3e6cb;">
                <?= $mensaje ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div style="background:#f8d7da; color:#721c24; padding:12px 20px; border-radius:5px; margin-bottom:15px; border:1px solid #f5c6cb;">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <div class="columnas-container">

            <div class="ajustes-card">
                <h3>⏱ Viajes Diferidos</h3>
                <p class="descripcion">Un viaje se considera "Diferido" cuando está programado para más de <strong>X minutos</strong> en el futuro. Cuando falten menos de X minutos, pasará a "Pendiente".</p>
                <p class="descripcion" style="color:#0d6efd; font-weight:bold;">
                    Valor actual: <strong><?= MIN_DIFERIDO ?></strong> minutos
                </p>

                <form method="POST" action="" onsubmit="return confirmarDiferido()">
                    <div class="input-row">
                        <label for="nuevo_min_diferido">Nuevo valor (minutos):</label>
                        <div class="input-group">
                            <input type="number" id="nuevo_min_diferido" name="nuevo_min_diferido"
                                value="<?= MIN_DIFERIDO ?>"
                                min="1" max="1440" required>
                            <span class="min-txt">min.</span>
                        </div>
                    </div>
                    <button type="submit" name="guardar_diferido" class="btn-actualizar-config btn-diferido">Guardar Diferidos</button>
                </form>
            </div>

            <div class="ajustes-card">
                <h3>⏰ Alerta de Viajes Vencidos</h3>
                <p class="descripcion">Tiempo en minutos para alertar cuando un viaje superó la hora programada sin ser asignado o completado.</p>
                <p class="descripcion" style="color:#dc3545; font-weight:bold;">
                    Valor actual: <strong><?= TIEMPO_AIR ?></strong> minutos
                </p>

                <form method="POST" action="" onsubmit="return confirmarAire()">
                    <div class="input-row">
                        <label for="nuevo_tiempo_aire">Nuevo valor (minutos):</label>
                        <div class="input-group">
                            <input type="number" id="nuevo_tiempo_aire" name="nuevo_tiempo_aire"
                                value="<?= TIEMPO_AIR ?>"
                                min="1" max="1440" required>
                            <span class="min-txt">min.</span>
                        </div>
                    </div>
                    <button type="submit" name="guardar_aire" class="btn-actualizar-config btn-aire">Guardar Alerta</button>
                </form>
            </div>

        </div>

        <div class="footer-actions">
            <a href="../../inicio_0.php" class="btn-salir">🏠 Inicio</a>
        </div>
    </div>
</body>

</html>