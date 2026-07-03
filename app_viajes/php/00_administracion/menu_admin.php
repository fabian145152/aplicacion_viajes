<?php
include_once "../../funciones/funciones.php";

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
    <div>
        <h1>Administración</h1>
        <p>Bienvenido <?php echo $nombre_completo; ?></p>
    </div>
    <div>
        <ul>
            <li><a href="operadores/listado.php">OPERADORES</a></li>
            <br>
            <li><a href="seteos/inicio.php">SETEOS DE TIEMPOS</a></li>
            <br>
            <li><a href="">APP PASAJEROS</a></li>
            <br>
            <li><a href="">RANGOS</a></li>
            <br>
            <li><a href="../inicio_0.php">SALIR</a></li>
        </ul>
    </div>
</body>

</html>