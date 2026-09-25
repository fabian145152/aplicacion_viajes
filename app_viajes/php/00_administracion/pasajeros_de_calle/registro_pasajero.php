<?php
header('Content-Type: application/json; charset=utf-8');
include_once "../../../funciones/funciones.php";

$input = file_get_contents('php://input');
$data = json_decode($input, true);

$nombre_apellido = trim($data['nombre_apellido'] ?? '');
$email           = trim($data['email'] ?? '');
$password        = trim($data['password'] ?? '');
$celular         = trim($data['celular'] ?? '');

// ---- Validaciones ----
$errores = [];

if ($nombre_apellido === '') $errores[] = 'Falta nombre y apellido.';
if ($email === '')           $errores[] = 'Falta el email.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errores[] = 'Email inválido.';
if ($password === '')        $errores[] = 'Falta la contraseña.';
if (!preg_match('/^\d{4,8}$/', $password)) $errores[] = 'La contraseña debe tener entre 4 y 8 números.';
if ($celular === '')         $errores[] = 'Falta el celular.';
if (!preg_match('/^\d{8,15}$/', $celular)) $errores[] = 'Celular inválido (solo números, 8 a 15 dígitos).';

if (!empty($errores)) {
    echo json_encode(['res' => 'ERROR', 'errores' => $errores]);
    exit;
}

$pdo = conexion();

// ---- ¿El email ya existe? ----
$stmt = $pdo->prepare("SELECT id FROM pasajeros_app WHERE email = :email LIMIT 1");
$stmt->execute([':email' => $email]);
if ($stmt->fetch()) {
    echo json_encode(['res' => 'ERROR', 'errores' => ['Ese email ya está registrado.']]);
    exit;
}

// ---- Generar código de 4 dígitos ----
$codigo = str_pad((string)random_int(0, 9999), 4, '0', STR_PAD_LEFT);

// ---- Hash de la contraseña ----
$hash = password_hash($password, PASSWORD_BCRYPT);

// ---- Insertar ----
$sql = "INSERT INTO pasajeros_app
            (nombre_apellido, email, password_hash, celular, codigo_confirmacion, confirmado)
        VALUES
            (:nombre, :email, :hash, :celular, :codigo, 0)";
$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':nombre'  => $nombre_apellido,
    ':email'   => $email,
    ':hash'    => $hash,
    ':celular' => $celular,
    ':codigo'  => $codigo,
]);

$id = $pdo->lastInsertId();

// ---- Devolver OK + código ----
echo json_encode([
    'res'    => 'OK',
    'id'     => (int)$id,
    'codigo' => $codigo,
    'msg'    => 'Registro creado. Confirmá por WhatsApp.'
]);
