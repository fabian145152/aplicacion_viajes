<?php
include_once "../../../funciones/funciones.php";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
protegerPagina([0, 3]); // SOLO SUPERUSER Y ADMIN



$usuario_a_editar = null;

// Mapeo de permisos
$mapPermisos = [
    0 => 'Superusuario',
    1 => 'Usuario Experto',
    2 => 'Usuario Standard',
    3 => 'Administrador'
];

// Clases CSS para colores
$clasesPermisos = [
    0 => 'badge super',
    1 => 'badge experto',
    2 => 'badge standard',
    3 => 'badge admin'
];

// 1. BORRAR (SIN RESTRICCIÓN)
if (isset($_GET['borrar'])) {
    eliminarUsuario($_GET['borrar']);
    header("Location: listado.php");
    exit;
}

// 2. EDITAR
if (isset($_GET['editar'])) {
    $usuario_a_editar = obtenerUsuarioPorId($_GET['editar']);
}

// 3. GUARDAR
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar'])) {
    guardarUsuario($_POST);
    header("Location: listado.php");
    exit;
}

// 4. FILTRO
$filtro_estado = $_GET['estado'] ?? '';
$usuarios = obtenerUsuarios();

if ($filtro_estado) {
    $usuarios = array_filter($usuarios, function ($u) use ($filtro_estado) {
        return $u['estado'] === $filtro_estado;
    });
}
?>
<?php
// SUPONGO que ya tenés conexión y lógica previa
// $usuarios, $usuario_a_editar, $mapPermisos definidos
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Gestión de Operadores</title>
    <link rel="stylesheet" href="../../../css/estilos.css">
</head>

<body>


    <div class="main-content">
        <div class="container">

            <h2 class="text-center">Gestión de Operadores</h2>




            <!-- FILTROS -->
            <div class="filtros">
                <a href="listado.php">Todos</a> |
                <a href="?estado=activo">Activos</a> |
                <a href="?estado=suspendido">Inactivos</a>
            </div>

            <!-- LAYOUT 2 COLUMNAS -->
            <div class="card operadores-layout">

                <!-- FORMULARIO -->
                <div class="col-form">
                    <h3><?php echo $usuario_a_editar ? "Editar Usuario" : "Nuevo Usuario"; ?></h3>

                    <form method="POST">
                        <input type="hidden" name="id" value="<?php echo $usuario_a_editar['id'] ?? ''; ?>">

                        <input type="text" name="nombre" placeholder="Nickname"
                            value="<?php echo $usuario_a_editar['nombre'] ?? ''; ?>" required>

                        <input type="text" name="nom_apellido" placeholder="Nombre y Apellido"
                            value="<?php echo $usuario_a_editar['nom_apellido'] ?? ''; ?>" required>

                        <input type="text" name="telefono" placeholder="Teléfono"
                            value="<?php echo $usuario_a_editar['telefono'] ?? ''; ?>">

                        <input type="email" name="email" placeholder="Email"
                            value="<?php echo $usuario_a_editar['email'] ?? ''; ?>" required>

                        <?php if (!$usuario_a_editar): ?>

                            <input type="password" name="password" placeholder="Password" required>

                            <label>Permisos:</label>
                            <select name="permisos">
                                <option value="0">Superusuario</option>
                                <option value="3">Administrador</option>
                                <option value="1">Usuario Experto</option>
                                <option value="2" selected>Usuario Basico</option>
                            </select>

                            <label>Estado:</label>
                            <select name="estado">
                                <option value="activo" selected>Activo</option>
                                <option value="suspendido">Inactivo</option>
                            </select>

                        <?php endif; ?>

                        <button type="submit" name="guardar" class="btn btn-success">
                            <?php echo $usuario_a_editar ? "Actualizar" : "Crear Usuario"; ?>
                        </button>

                        <a href="../menu_admin.php" class="btn btn-gray">SALIR</a>

                        <?php if ($usuario_a_editar): ?>
                            <a href="operadores.php" class="btn btn-warning">Cancelar</a>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- TABLA -->
                <div class="col-tabla">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Nombre completo</th>
                                <th>Teléfono</th>
                                <th>Email</th>
                                <th>Estado</th>
                                <th>Permisos</th>
                                <th></th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if (count($usuarios) > 0): ?>
                                <?php foreach ($usuarios as $u): ?>
                                    <tr>
                                        <td><?php echo $u['id']; ?></td>
                                        <td><?php echo htmlspecialchars($u['nombre']); ?></td>
                                        <td><?php echo htmlspecialchars($u['nom_apellido']); ?></td>
                                        <td><?php echo htmlspecialchars($u['telefono']); ?></td>
                                        <td><?php echo htmlspecialchars($u['email']); ?></td>

                                        <!-- Estado -->
                                        <td>
                                            <?php if ($u['estado'] == 'activo'): ?>
                                                <span class="badge badge-success">Activo</span>
                                            <?php else: ?>
                                                <span class="badge badge-danger">Inactivo</span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Permisos -->
                                        <td>
                                            <span class="badge badge-primary">
                                                <?php echo $mapPermisos[$u['permisos']] ?? 'Desconocido'; ?>
                                            </span>
                                        </td>

                                        <td>
                                            <a href="editar_operador.php?id=<?php echo $u['id']; ?>" class="btn btn-warning">
                                                Editar
                                            </a>

                                            <a href="?borrar=<?php echo $u['id']; ?>"
                                                class="btn btn-danger"
                                                onclick="return confirm('¿Eliminar usuario?')">
                                                Borrar
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7">No hay usuarios.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>


            </div>
        </div>
    </div>
</body>

</html>