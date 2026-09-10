<?php
include_once "../funciones/funciones.php";

protegerPagina([1]); // ya valida todo

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
    <h1>Inicio Usuario basico</h1>
    <p>Bienvenido <?php echo $nombre_completo; ?></p>




    <ul>
        <li><a href="00_administracion/operadores/listado.php">OPERADORES</a></li>
        <li><a href="logout.php">SALIR</a></li>
        <li></li>
    </ul>
</body>

</html>