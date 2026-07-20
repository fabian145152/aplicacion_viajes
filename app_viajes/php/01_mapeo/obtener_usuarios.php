<?php
include_once "../../funciones/funciones.php";

// Protegemos la página para operadores (3) o administrador (0)
protegerPagina([0, 3]);

$con = conexion();

// Obtener usuarios únicos de la tabla ubicaciones que tengan relación con choferes
$sql = "SELECT DISTINCT u.user_id 
        FROM ubicaciones u 
        INNER JOIN choferes c ON u.user_id = c.movil
        WHERE u.user_id IS NOT NULL AND u.user_id != ''
        ORDER BY u.user_id ASC";

$stmt = $con->query($sql);
$usuarios = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo json_encode($usuarios);
