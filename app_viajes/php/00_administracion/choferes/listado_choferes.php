<?php
include_once "../../../funciones/funciones.php";

protegerPagina([0, 3]);

// GUARDAR
if (isset($_POST['guardar'])) {
    $resultado = guardarChofer($_POST);

    if ($resultado === 'movil_duplicado') {
        $idVolver = $_POST['id'] ?? '';
        header("Location: listado_choferes.php?error=movil_duplicado" . ($idVolver ? "&editar=$idVolver" : ""));
    } else {
        header("Location: listado_choferes.php");
    }
    exit;
}

// BORRAR
if (isset($_GET['borrar'])) {
    borrarChofer($_GET['borrar']);
    header("Location: listado_choferes.php");
    exit;
}

// EDITAR
$chofer_a_editar = null;
if (isset($_GET['editar'])) {
    $chofer_a_editar = obtenerChoferPorId($_GET['editar']);
}

// DATOS
$choferes = obtenerChoferes();

// FILTRO por número de móvil
$filtroMovil = trim($_GET['movil_buscar'] ?? '');
if ($filtroMovil !== '') {
    $choferes = array_values(array_filter($choferes, function ($c) use ($filtroMovil) {
        return strpos((string)($c['movil'] ?? ''), $filtroMovil) !== false;
    }));
}

// ORDEN
$orden = $_GET['orden'] ?? 'movil_asc';

usort($choferes, function ($a, $b) use ($orden) {

    switch ($orden) {

        case 'movil_desc':
            return (int)$b['movil'] <=> (int)$a['movil'];

        case 'apellido_asc':
            return strcasecmp($a['apellido'] ?? '', $b['apellido'] ?? '');

        case 'apellido_desc':
            return strcasecmp($b['apellido'] ?? '', $a['apellido'] ?? '');

        default: // movil_asc
            return (int)$a['movil'] <=> (int)$b['movil'];
    }
});
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Choferes</title>
    <link rel="stylesheet" href="../../../css/estilos.css">
</head>

<body>

    <div class="container">
        <h2 class="text-center">Gestión de Choferes</h2>

        <div class="card operadores-layout">

            <!-- FORM -->
            <div class="col-form">
                <h3><?php echo $chofer_a_editar ? "Editar Chofer" : "Nuevo Chofer"; ?></h3>

                <form method="POST">

                    <input type="hidden" name="id" value="<?php echo $chofer_a_editar['id'] ?? ''; ?>">

                    <input type="number" name="movil" placeholder="Movil"
                        value="<?php echo $chofer_a_editar['movil'] ?? ''; ?>">

                    <input type="text" name="nombre" placeholder="Nombre"
                        value="<?php echo $chofer_a_editar['nombre'] ?? ''; ?>" required>

                    <input type="text" name="apellido" placeholder="Apellido"
                        value="<?php echo $chofer_a_editar['apellido'] ?? ''; ?>" required>

                    <input type="text" name="cp" placeholder="DNI"
                        value="<?php echo $chofer_a_editar['user'] ?? ''; ?>" required>

                    <input type="text" name="cp" placeholder="Clave"
                        value="<?php echo $chofer_a_editar['clave'] ?? ''; ?>" required>

                    <input type="text" name="cel" placeholder="Celular"
                        value="<?php echo $chofer_a_editar['cel'] ?? ''; ?>" required>

                    <input type="text" name="dir" placeholder="Dirección"
                        value="<?php echo $chofer_a_editar['dir'] ?? ''; ?>" required>

                    <input type="text" name="barrio" placeholder="Barrio"
                        value="<?php echo $chofer_a_editar['barrio'] ?? ''; ?>" required>

                    <input type="text" name="cp" placeholder="Código Postal"
                        value="<?php echo $chofer_a_editar['cp'] ?? ''; ?>" required>

                    <button type="submit" name="guardar" class="btn btn-success">
                        <?php echo $chofer_a_editar ? "Actualizar" : "Crear Chofer"; ?>
                    </button>

                    <?php if ($chofer_a_editar): ?>
                        <a href="listado_choferes.php" class="btn btn-gray">Cancelar</a>
                    <?php endif; ?>
                    <a href="../../inicio_0.php" class="btn btn-danger">SALIR</a>
                </form>
            </div>

            <!-- TABLA -->
            <div class="col-tabla">

                <form method="GET" class="filtros" style="display:flex; flex-direction:row; gap:6px; align-items:center; justify-content:flex-start; margin-bottom:15px; flex-wrap:nowrap;">
                    <input type="text" name="movil_buscar" placeholder="Buscar por N° de móvil"
                        style="padding:5px 8px; font-size:12px; width:160px;"
                        value="<?php echo htmlspecialchars($filtroMovil); ?>">

                    <select name="orden" style="padding:5px 6px; font-size:12px;">
                        <option value="movil_asc" <?php if ($orden === 'movil_asc') echo 'selected'; ?>>Móvil ↑</option>
                        <option value="movil_desc" <?php if ($orden === 'movil_desc') echo 'selected'; ?>>Móvil ↓</option>
                        <option value="apellido_asc" <?php if ($orden === 'apellido_asc') echo 'selected'; ?>>Apellido A-Z</option>
                        <option value="apellido_desc" <?php if ($orden === 'apellido_desc') echo 'selected'; ?>>Apellido Z-A</option>
                    </select>

                    <button type="submit" class="btn btn-success" style="padding:5px 10px; font-size:12px;">Filtrar</button>
                    <a href="listado_choferes.php" class="btn btn-gray" style="padding:5px 10px; font-size:12px;">Limpiar</a>
                </form>

                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Movil</th>
                            <th>Nombre</th>
                            <th>Apellido</th>
                            <th>DNI</th>
                            <th>Clave</th>
                            <th>Vehículos</th>
                            <th>Estado</th>
                            <th>Celular</th>
                            <th>Direccion</th>
                            <th>Barrio</th>
                            <th>CP</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (count($choferes) > 0): ?>
                            <?php foreach ($choferes as $c): ?>
                                <tr>
                                    <td><?php echo $c['id']; ?></td>
                                    <td><?php echo htmlspecialchars($c['movil']); ?></td>
                                    <td><?php echo htmlspecialchars($c['nombre']); ?></td>
                                    <td><?php echo htmlspecialchars($c['apellido']); ?></td>
                                    <td><?php echo htmlspecialchars($c['user']); ?></td>
                                    <td><?php echo htmlspecialchars($c['clave']); ?></td>
                                    <td>
                                        <?php if (!empty($c['patente'])): ?>
                                            <?php echo htmlspecialchars($c['marca'] . ' ' . $c['modelo'] . ' (' . $c['patente'] . ')'); ?>
                                        <?php else: ?>
                                            Sin vehículo
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?php if (!empty($c['patente'])): ?>
                                            <span class="badge badge-success">Asignado</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">Libre</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($c['cel']); ?></td>
                                    <td><?php echo htmlspecialchars($c['dir']); ?></td>
                                    <td><?php echo htmlspecialchars($c['barrio']); ?></td>
                                    <td><?php echo htmlspecialchars($c['cp']); ?></td>


                                    <?php
                                    // contar vehículos asignados
                                    $pdo = conexion();
                                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM vehiculos WHERE id_chofer = ?");
                                    $stmt->execute([$c['id']]);
                                    $stmt->fetchColumn();
                                    ?>


                                    <td>
                                        <a href="?editar=<?php echo $c['id']; ?>" class="btn btn-warning">Editar</a>

                                        <a href="?borrar=<?php echo $c['id']; ?>"
                                            class="btn btn-danger"
                                            onclick="return confirm('¿Eliminar chofer?')">
                                            Borrar
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5">No hay choferes.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>

    <?php if (($_GET['error'] ?? '') === 'movil_duplicado'): ?>
        <script>
            alert('El móvil ya existe, asignado a otro chofer. Elegí otro número.');
        </script>
    <?php endif; ?>

</body>

</html>