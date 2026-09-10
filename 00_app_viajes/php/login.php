<?php
session_start();

include_once "../funciones/funciones.php";

$usuario = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

$pdo = conexion();

if (!$pdo) {
    die("Error de conexión a la base de datos.");
}

// 🔎 BUSCAR USUARIO
$sql = "SELECT id, nombre, password, permisos FROM usuarios WHERE nombre = :user LIMIT 1";

$stmt = $pdo->prepare($sql);
$stmt->bindParam(':user', $usuario, PDO::PARAM_STR);
$stmt->execute();

$row = $stmt->fetch(PDO::FETCH_ASSOC);

// ❌ USUARIO NO EXISTE
if (!$row) {
    echo "Usuario o contraseña incorrectos";
    exit;
}

// ❌ PASSWORD INCORRECTO
if (!password_verify($password, $row['password'])) {
    echo "Usuario o contraseña incorrectos";
    exit;
}

// ✅ LOGIN CORRECTO
session_regenerate_id(true);

$_SESSION['nombre']     = $row['nombre'];
$_SESSION['id_usuario'] = $row['id'];
$_SESSION['logueado']   = true;
$_SESSION['permiso']    = $row['permisos'];

// 🔀 REDIRECCIÓN SEGÚN PERMISO
$permiso = $row['permisos'];

// Validación extra por seguridad
if ($permiso < 0 || $permiso > 3) {
    die("Permiso inválido");
}

// 👉 REDIRECCIONA A:
header("Location: ../php/inicio_" . $permiso . ".php");
exit;
