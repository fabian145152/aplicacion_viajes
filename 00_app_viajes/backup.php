<?php
// Configuración de la base de datos
$host = "localhost";
$user = "root";
$pass = "belgrado";
$db_name = "app_viajes";

// Directorio de destino relativo a donde esté este archivo
$dir_destino = __DIR__ . '/DDBB/';

// Asegurar que la carpeta DDBB exista, si no, la crea
if (!is_dir($dir_destino)) {
    mkdir($dir_destino, 0777, true);
}

// Nombre del archivo de backup con la fecha y hora actual
$nombre_archivo = "backup_" . $db_name . "_" . date("Y-m-d_H-i-s") . ".sql";
$ruta_completa = $dir_destino . $nombre_archivo;

/**
 * BUSCADOR AUTOMÁTICO DE MYSQLDUMP
 * Definimos las rutas estándar donde suele instalarse en Windows (XAMPP, Laragon, Wamp)
 */
$rutas_posibles = [
    "C:/xampp/mysql/bin/mysqldump.exe",
    "D:/xampp/mysql/bin/mysqldump.exe",
    "C:/laragon/bin/mysql/mysql-8.0.30-winx64/bin/mysqldump.exe",
    "C:/wamp64/bin/mysql/mysql8.0.21/bin/mysqldump.exe",
    "mysqldump" // Si por casualidad está en las variables de entorno
];

$mysqldump_path = "";
foreach ($rutas_posibles as $ruta) {
    if (file_exists($ruta) || $ruta === "mysqldump") {
        $mysqldump_path = $ruta;
        break;
    }
}

// Si no encontró ninguna ruta válida en el servidor
if (empty($mysqldump_path)) {
    echo json_encode([
        "status" => "error",
        "message" => "No se pudo encontrar el archivo 'mysqldump.exe' en el servidor. Verifique la ruta de instalación de su MySQL."
    ]);
    exit;
}

// Construcción del comando limpia para Windows
$comando = "\"{$mysqldump_path}\" --host={$host} --user={$user} --password={$pass} {$db_name} > \"{$ruta_completa}\"";

// Ejecutar comando de consola
$salida = [];
$resultado = null;
exec($comando . " 2>&1", $salida, $resultado);

// Verificar si el archivo realmente se creó y tiene contenido
if (file_exists($ruta_completa) && filesize($ruta_completa) > 0) {
    echo json_encode([
        "status" => "success",
        "message" => "Backup creado con éxito: " . $nombre_archivo
    ]);
    // ... código anterior ...
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Error al generar el backup. El archivo no se creó.",
        "comando_intentado" => $comando,
        "detalles" => $salida
    ]);
}
// Asegurate de que acá abajo no haya ningún 'echo "' escrito por error
?>
echo "<br><a href='php/inicio_0.php'>SALIR</a>";