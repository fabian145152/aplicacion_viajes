<?php
// ========================================================
// ACTUALIZAR CONFIGURACIÓN DE TEMPORIZADORES EN DOS COLUMNAS
// ========================================================

// 1. Primero leemos lo que ya está guardado para no perder ningún dato
if (file_exists("min_diferido.php")) {
    include_once "min_diferido.php";
}

// 2. Definimos valores base por si el archivo está vacío o no existe aún
$current_min_diferido = isset($min_diferido) ? $min_diferido : 60;
$current_tiempo_aire = isset($tiempo_aire) ? $tiempo_aire : 30;

$hubo_cambio = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // SI SE GUARDÓ LA COLUMNA 1 (DIFERIDOS)
    if (isset($_POST['guardar_diferido']) && isset($_POST['nuevo_min_diferido'])) {
        $current_min_diferido = (int)$_POST['nuevo_min_diferido'];
        $hubo_cambio = true;
    }

    // SI SE GUARDÓ LA COLUMNA 2 (TIEMPO EN EL AIRE)
    if (isset($_POST['guardar_aire']) && isset($_POST['nuevo_tiempo_aire'])) {
        $current_tiempo_aire = (int)$_POST['nuevo_tiempo_aire'];
        $hubo_cambio = true;
    }

    // Si se procesó un cambio válido, reescribimos el archivo consolidado
    if ($hubo_cambio && $current_min_diferido > 0 && $current_tiempo_aire > 0) {
        $contenido = "<?php\n"
            . "// ==============================================================\n"
            . "// CONFIGURACIÓN GLOBAL AUTOMÁTICA DE TIEMPOS\n"
            . "// ==============================================================\n"
            . "\$min_diferido = $current_min_diferido;\n"
            . "if (!defined('MIN_DIFERIDO')) {\n"
            . "    define('MIN_DIFERIDO', \$min_diferido);\n"
            . "}\n\n"
            . "\$tiempo_aire = $current_tiempo_aire;\n"
            . "if (!defined('TIEMPO_AIRE')) {\n"
            . "    define('TIEMPO_AIRE', \$tiempo_aire);\n"
            . "}\n"
            . "?>";

        file_put_contents('min_diferido.php', $contenido);

        // Redireccionamos para limpiar el POST de la memoria del navegador
        $url_actual = $_SERVER['PHP_SELF'] . (isset($_GET['estado']) ? "?estado=" . $_GET['estado'] : "");
        header("Location: " . $url_actual);
        exit;
    }
}
