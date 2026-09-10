<?php
include_once "../../../funciones/funciones.php";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Validar ID
if (!isset($_GET['id'])) {
    header("Location: listado.php");
    exit;
}

$id = (int) $_GET['id'];
$usuario = obtenerUsuarioPorId($id);

if (!$usuario) {
    die("Usuario no encontrado.");
}

$error = "";

// ACTUALIZAR
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar'])) {

    // 🔒 Manejo de contraseña
    if (!empty($_POST['password'])) {

        if ($_POST['password'] !== $_POST['confirmar_password']) {
            $error = "Las contraseñas no coinciden";
        } else {
            $_POST['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
        }
    } else {
        unset($_POST['password']); // no modificar contraseña
    }

    // Guardar si no hay error
    if (!$error) {
        actualizarConfiguracionUsuario($_POST);
        header("Location: listado.php?msj=editado");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Configuración de Usuario</title>
    <link rel="stylesheet" href="../../../css/estilos.css">

    <style>
        .password-group {
            display: flex;
            flex-direction: column;
            gap: 12px;
            max-width: 300px;
            margin: 0 auto;
        }
    </style>

</head>

<body>
    <div class="container">

        <h2>Configuración de Operador: <?php echo htmlspecialchars($usuario['nombre']); ?></h2>

        <a href="listado.php" style="display:inline-block; margin-bottom:20px;">
            ← Volver a la lista
        </a>

        <?php if ($error): ?>
            <div style="color:red; margin-bottom:15px;">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" onsubmit="return confirm('¿Guardar cambios en este usuario?');">
            <input type="hidden" name="id" value="<?php echo $usuario['id']; ?>">

            <div class="config-grid">

                <!-- DATOS -->
                <div class="card">
                    <h3 class="section-title">Datos Personales</h3>

                    <label>Nombre:</label>
                    <input type="text" name="nombre"
                        value="<?php echo htmlspecialchars($usuario['nombre']); ?>" required>

                    <label>Nombre Completo:</label>
                    <input type="text" name="nom_apellido"
                        value="<?php echo htmlspecialchars($usuario['nom_apellido']); ?>" required>                        

                    <label>Teléfono:</label>
                    <input type="text" name="telefono"
                        value="<?php echo htmlspecialchars($usuario['telefono']); ?>">

                    <label>Email:</label>
                    <input type="email" name="email"
                        value="<?php echo htmlspecialchars($usuario['email']); ?>" required>
                </div>

                <!-- AJUSTES -->
                <div class="card">
                    <h3 class="section-title">Ajustes de Sistema</h3>

                    <label>Nivel de Permisos:</label>
                    <select name="permisos">
                        <option value="0" <?php echo $usuario['permisos'] == 0 ? 'selected' : ''; ?>>Superusuario</option>
                        <option value="1" <?php echo $usuario['permisos'] == 1 ? 'selected' : ''; ?>>Usuario Experto</option>
                        <option value="2" <?php echo $usuario['permisos'] == 2 ? 'selected' : ''; ?>>Usuario Standard</option>
                        <option value="3" <?php echo $usuario['permisos'] == 3 ? 'selected' : ''; ?>>Administrador</option>
                    </select>

                    <label style="display:block; margin-top:15px;">Estado de Cuenta:</label>
                    <select name="estado">
                        <option value="activo" <?php echo $usuario['estado'] == 'activo' ? 'selected' : ''; ?>>Activo</option>
                        <option value="suspendido" <?php echo $usuario['estado'] == 'suspendido' ? 'selected' : ''; ?>>Inactivo</option>
                    </select>

                    <!-- 🔒 CONTRASEÑA -->
                    <hr style="margin:20px 0;">

                    <h3 class="section-title">Seguridad</h3>

                    <div class="password-group">

                        <div>
                            <label>Nueva contraseña:</label>
                            <input type="password" name="password" placeholder="Dejar vacío para no cambiar">
                        </div>

                        <div>
                            <label>Confirmar contraseña:</label>
                            <input type="password" name="confirmar_password">
                        </div>

                    </div>

                </div>

            </div>

            <div style="margin-top: 20px; text-align: center;">
                <button type="submit" name="actualizar" class="btn btn-add"
                    style="background:#007bff; padding: 15px 40px;">
                    Guardar Cambios
                </button>
            </div>

        </form>
    </div>
</body>

</html>