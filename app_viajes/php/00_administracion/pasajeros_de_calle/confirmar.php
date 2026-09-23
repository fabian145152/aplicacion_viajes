<?php
include_once "../../../funciones/funciones.php";
protegerPagina([0, 3]);

$pdo = conexion();

$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$accion = isset($_GET['accion']) ? $_GET['accion'] : 'confirmar';

if ($id <= 0) {
    header('Location: listado.php?msg=error');
    exit;
}

// Traer los datos del pasajero
$stmt = $pdo->prepare("SELECT * FROM pasajeros_app WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $id]);
$pasajero = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pasajero) {
    header('Location: listado.php?msg=error');
    exit;
}

// ============================================================
// ACCIÓN: ELIMINAR
// ============================================================
if ($accion === 'eliminar') {
    $stmt = $pdo->prepare("DELETE FROM pasajeros_app WHERE id = :id");
    $stmt->execute([':id' => $id]);
    header('Location: listado.php?msg=eliminado');
    exit;
}

// ============================================================
// ACCIÓN: CONFIRMAR
// ============================================================
// 1. Actualizar en la DDBB
$stmt = $pdo->prepare("UPDATE pasajeros_app
                       SET confirmado = 1,
                           fecha_confirmacion = NOW()
                       WHERE id = :id");
$stmt->execute([':id' => $id]);

// 2. Preparar el link de WhatsApp con mensaje pre-escrito
$nombre  = $pasajero['nombre_apellido'];
$celular = preg_replace('/\D/', '', $pasajero['celular']); // solo dígitos

// Si el número no empieza con código de país, se lo agregamos
// Asumimos Argentina (54) + 9 (celular)
if (strlen($celular) <= 11 && substr($celular, 0, 2) !== '54') {
    $celular = '549' . $celular;
}

$mensaje = "¡Hola $nombre! 👋\n\n"
    . "Tu cuenta en la App de Pasajeros ya está confirmada. ✅\n\n"
    . "Ya podés iniciar sesión con:\n"
    . "📧 Email: {$pasajero['email']}\n"
    . "🔑 Contraseña: (la que elegiste al registrarte)\n\n"
    . "¡Gracias por registrarte!";

$url_whatsapp = 'https://wa.me/' . $celular . '?text=' . rawurlencode($mensaje);

// 3. Redirigir: primero al listado, pero abriendo WhatsApp en una pestaña nueva
// Lo hacemos con un HTML intermedio que ejecuta las dos cosas
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Confirmando...</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, sans-serif;
            background: #f0f2f5;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
        }

        .card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, .08);
            padding: 30px 40px;
            max-width: 500px;
            text-align: center;
        }

        .icono {
            font-size: 48px;
            margin-bottom: 10px;
        }

        h1 {
            color: #28a745;
            margin: 0 0 10px;
            font-size: 20px;
        }

        p {
            color: #666;
            font-size: 14px;
            margin: 8px 0;
            line-height: 1.5;
        }

        .btn {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 24px;
            background: #007bff;
            color: #fff;
            text-decoration: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
        }

        .btn:hover {
            background: #0056b3;
        }

        .btn-wa {
            background: #25D366;
            margin-right: 8px;
        }

        .btn-wa:hover {
            background: #1da851;
        }
    </style>
</head>

<body>

    <div class="card">
        <div class="icono">✅</div>
        <h1>Pasajero confirmado</h1>
        <p><strong><?= htmlspecialchars($nombre) ?></strong> ya puede iniciar sesión en la app.</p>
        <p>Se está abriendo WhatsApp para avisarle. Si no se abrió solo, tocá el botón verde.</p>

        <a href="<?= htmlspecialchars($url_whatsapp) ?>" target="_blank" class="btn btn-wa">
            💬 Abrir WhatsApp
        </a>
        <a href="listado.php?msg=confirmado" class="btn">← Volver al listado</a>
    </div>

    <script>
    // Intentar abrir WhatsApp automáticamente (puede ser bloqueado por el navegador)
    setTimeout(function() {
        window.open('<?= htmlspecialchars($url_whatsapp) ?>', '_blank');
    }, 400);
</script>

</body>

</html>