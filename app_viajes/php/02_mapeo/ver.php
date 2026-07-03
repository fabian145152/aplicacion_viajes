<?php
// Refrescar la página cada 2 segundos automáticamente
header("Refresh: 2");
header("Content-Type: text/plain"); // Esto hace que se vea como texto plano

if (file_exists("monitor.txt")) {
    echo file_get_contents("monitor.txt");
} else {
    echo "Esperando datos del celular...";
}
