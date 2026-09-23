<?php
header('Content-Type: application/json; charset=utf-8');
include_once "../../../funciones/funciones.php";

$input = file_get_contents('php://input');
$data = json_decode($input, true);

$email    = trim($data['email'] ?? '');
$password = trim($data['password'] ?? '');

if ($email === '' || $password === '') {
    echo json_encode(['res' => 'ERROR', 'msg' => 'Faltan datos.']);
    exit;
}

$pdo = conexion();

$stmt = $pdo->prepare("SELECT * FROM pasajeros_app WHERE email = :email LIMIT 1");
$stmt->execute([':email' => $email]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    echo json_encode(['res' => 'ERROR', 'msg' => 'Email o contraseña incorrectos.']);
    exit;
}

if (!password_verify($password, $row['password_hash'])) {
    echo json_encode(['res' => 'ERROR', 'msg' => 'Email o contraseña incorrectos.']);
    exit;
}

if ((int)$row['confirmado'] !== 1) {
    echo json_encode([
        'res' => 'NO_CONFIRMADO',
        'msg' => 'Todavía no confirmaste tu cuenta por WhatsApp.'
    ]);
    exit;
}

echo json_encode([
    'res'             => 'OK',
    'id'              => (int)$row['id'],
    'nombre_apellido' => $row['nombre_apellido'],
    'email'           => $row['email'],
    'celular'         => $row['celular'],
]);
