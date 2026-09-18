<?php
$dir     = __DIR__ . '/DDBB/';
$archivo = basename($_GET['archivo'] ?? '');   // basename evita path traversal
$ruta    = $dir . $archivo;

if ($archivo === '' || !is_file($ruta)) {
    http_response_code(404);
    exit('Archivo no encontrado.');
}

// Limpiar cualquier buffer previo
if (ob_get_level()) {
    ob_end_clean();
}

header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $archivo . '"');
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
header('Content-Length: ' . filesize($ruta));

readfile($ruta);
exit;
