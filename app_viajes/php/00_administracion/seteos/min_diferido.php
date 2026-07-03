<?php
// ==============================================================
// CONFIGURACIÓN GLOBAL AUTOMÁTICA DE TIEMPOS
// ==============================================================
$min_diferido = 10;
if (!defined('MIN_DIFERIDO')) {
    define('MIN_DIFERIDO', $min_diferido);
}

$tiempo_aire = 10;
if (!defined('TIEMPO_AIRE')) {
    define('TIEMPO_AIRE', $tiempo_aire);
}
?>