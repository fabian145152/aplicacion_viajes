<?php
header('Content-Type: application/json; charset=utf-8');
include_once "../../../funciones/funciones.php";

$input = file_get_contents('php://input');
$data = json_decode($input, true);

$email           = trim($data['email'] ?? '');
$pass_actual     = trim($data['password_actual'] ?? '');
$pass_nueva      = trim($data['password_nueva'] ?? '');
$pass_confirmar  = trim($data['password_confirmar'] ?? '');

// ---- Validaciones ----
$errores = [];

if ($email === '')         $errores[] = 'Falta el email.';
if ($pass_actual === '')   $errores[] = 'Falta la contraseña actual.';
if ($pass_nueva === '')    $errores[] = 'Falta la contraseña nueva.';
if ($pass_confirmar === '') $errores[] = 'Falta repetir la contraseña nueva.';

if ($pass_nueva !== '' && !preg_match('/^\d{4,8}$/', $pass_nueva)) {
    $errores[] = 'La contraseña nueva debe tener entre 4 y 8 números.';
}
if ($pass_nueva !== $pass_confirmar) {
    $errores[] = 'Las contraseñas nuevas no coinciden.';
}

if (!empty($errores)) {
    echo json_encode(['res' => 'ERROR', 'errores' => $errores]);
    exit;
}

$pdo = conexion();

// ---- Buscar al pasajero ----
$stmt = $pdo->prepare("SELECT id, password_hash, confirmado 
                       FROM pasajeros_app 
                       WHERE email = :email LIMIT 1");
$stmt->execute([':email' => $email]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    echo json_encode(['res' => 'ERROR', 'msg' => 'Email no encontrado.']);
    exit;
}

if ((int)$row['confirmado'] !== 1) {
    echo json_encode(['res' => 'ERROR', 'msg' => 'Tu cuenta todavía no está confirmada.']);
    exit;
}

// ---- Verificar contraseña actual ----
if (!password_verify($pass_actual, $row['password_hash'])) {
    echo json_encode(['res' => 'ERROR', 'msg' => 'La contraseña actual no es correcta.']);
    exit;
}

// ---- Actualizar ----
$nuevo_hash = password_hash($pass_nueva, PASSWORD_BCRYPT);
$stmt = $pdo->prepare("UPDATE pasajeros_app SET password_hash = :hash WHERE id = :id");
$stmt->execute([':hash' => $nuevo_hash, ':id' => $row['id']]);

echo json_encode([
    'res' => 'OK',
    'msg' => 'Contraseña actualizada correctamente.'
]);
