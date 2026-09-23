<?php
include_once "../../../funciones/funciones.php";

$input = file_get_contents('php://input');
$data = json_decode($input, true);

$email = trim($data['email'] ?? '');

if ($email === '') {
    echo json_encode(['res' => 'ERROR', 'msg' => 'Falta el email.']);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['res' => 'ERROR', 'msg' => 'Email inválido.']);
    exit;
}

$pdo = conexion();

// Buscar al pasajero
$stmt = $pdo->prepare("SELECT id, nombre_apellido, email, celular, confirmado
                       FROM pasajeros_app
                       WHERE email = :email LIMIT 1");
$stmt->execute([':email' => $email]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

// Por seguridad, no revelamos si el email existe o no.
// Siempre devolvemos OK pero solo si existe hacemos el reset.
if (!$row) {
    echo json_encode([
        'res' => 'OK',
        'msg' => 'Si el email está registrado, te vamos a contactar.'
    ]);
    exit;
}

if ((int)$row['confirmado'] !== 1) {
    echo json_encode([
        'res' => 'ERROR',
        'msg' => 'Tu cuenta todavía no está confirmada. Contactá a la empresa.'
    ]);
    exit;
}

// Generar contraseña temporal de 4 dígitos
$nueva_pass = str_pad((string)random_int(0, 9999), 4, '0', STR_PAD_LEFT);

// Hashear y guardar
$hash = password_hash($nueva_pass, PASSWORD_BCRYPT);
$stmt = $pdo->prepare("UPDATE pasajeros_app SET password_hash = :hash WHERE id = :id");
$stmt->execute([':hash' => $hash, ':id' => $row['id']]);

// Devolver datos para que la app arme el WhatsApp
echo json_encode([
    'res'             => 'OK',
    'id'              => (int)$row['id'],
    'nombre_apellido' => $row['nombre_apellido'],
    'email'           => $row['email'],
    'celular'         => $row['celular'],
    'nueva_password'  => $nueva_pass,   // la temporal
    'msg'             => 'Se generó una contraseña temporal. Enviá el WhatsApp.'
]);