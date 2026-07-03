<?php
include_once "../funciones/funciones.php";


protegerPagina([0]); // ya valida todo

$id = $_SESSION['id_usuario'];

$sql = "SELECT * FROM usuarios WHERE id = :id LIMIT 1";
$pdo = conexion();
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);

$nombre_completo = $row['nom_apellido'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>

<body>
    <h1>Inicio Superusuario</h1>
    <p>Bienvenido <?php echo $nombre_completo; ?></p>

    <ul>
        <li>Administracion</li>
        <li><a href="00_administracion/menu_admin.php">ADMINISTRACION</a></li>
        <br>
        <li>Trafico</li>
        <li><a href="00_administracion/trafico/listado.php">UNIDADES</a></li>
        <li><a href="00_administracion/choferes/listado_choferes.php">CHOFERES</a></li>
        <br>
        <li>Cuentas Corrientes</li>
        <li><a href="00_administracion/cuentas_empresas/listado_empresas.php">CUENTAS CORRIENTES EMPRESAS</a></li>
        <br>
        <li>Despacho de Viajes</li>
        <li><a href="00_administracion/despacho_viajes/carga_viajes.php" target="_blank">NUEVOS VIAJES</a></li>
        <li><a href="00_administracion/despacho_viajes/lista_viajes.php" target="_blank">LISTADO DE VIAJES</a></li>
        <br>
        <li><a href="01_mapeo/mapa_de_viajes.php" target="_blank">MAPA DE VIAJES</a></li>
        <li><a href="01_mapeo/ver_mapa.php" target="_blank">MAPA DE UNIDADES TRABAJANDO</a></li>
        <br>
        <li>Programas de muestreo</li>
        <li><a href="../backup.php" target="_blank">BACKUP</a></li>
        <br>
        <li><a href="01_mapeo/recibir.php" target="_blank">RECIBIR</a></li>
        <li><a href="01_mapeo/obtener_recorrido.php" target="_blank">RECORRIDO</a></li>
        <li></li>
        <li><a href="logout.php">SALIR</a></li>


    </ul>
</body>

</html>