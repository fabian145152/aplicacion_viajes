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

// --- SECCIÓN DE VEHÍCULOS ADAPTADA (Se incluye 'tipo') ---
$vehiculos = obtenerVehiculos(); // Obtenemos todos los vehículos
$vehiculoPorChofer = [];
foreach ($vehiculos as $v) {
    if (!empty($v['id_chofer'])) {
        // Guardamos los datos del vehículo usando el ID del chofer como clave
        $vehiculoPorChofer[$v['id_chofer']] = [
            'marca'   => $v['marca'] ?? '',
            'modelo'  => $v['modelo'] ?? '',
            'patente' => $v['patente'] ?? '',
            'tipo'    => $v['tipo'] ?? '' // <- Agregado aquí
        ];
    }
}
// -----------------------------

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

            <div class="col-form">
                <h3><?php echo $chofer_a_editar ? "Editar Chofer" : "Nuevo Chofer"; ?></h3>

                <form method="POST">

                    <input type="hidden" name="id" value="<?php echo $chofer_a_editar['id'] ?? ''; ?>">

                    <div class="fila">
                        <label for="movil">Número de Móvil:</label>
                        <input type="number" id="movil" name="movil"
                            value="<?php echo $chofer_a_editar['movil'] ?? ''; ?>">
                    </div>

                    <div class="fila">
                        <label for="nombre">Nombre:</label>
                        <input type="text" id="nombre" name="nombre"
                            value="<?php echo $chofer_a_editar['nombre'] ?? ''; ?>" required>
                    </div>

                    <div class="fila">
                        <label for="apellido">Apellido:</label>
                        <input type="text" id="apellido" name="apellido"
                            value="<?php echo $chofer_a_editar['apellido'] ?? ''; ?>" required>
                    </div>

                    <div class="fila">
                        <label for="user">DNI:</label>
                        <input type="text" id="user" name="user"
                            value="<?php echo $chofer_a_editar['user'] ?? ''; ?>" required>
                    </div>

                    <div class="fila">
                        <label for="clave">Clave:</label>
                        <input type="text" id="clave" name="clave"
                            value="<?php echo $chofer_a_editar['clave'] ?? ''; ?>" required>
                    </div>

                    <div class="fila">
                        <label for="cel">Celular:</label>
                        <input type="text" id="cel" name="cel"
                            value="<?php echo $chofer_a_editar['cel'] ?? ''; ?>" required>
                    </div>

                    <div class="fila">
                        <label for="dir">Dirección:</label>
                        <input type="text" id="dir" name="dir"
                            value="<?php echo $chofer_a_editar['dir'] ?? ''; ?>" required>
                    </div>

                    <div class="fila">
                        <label for="barrio">Barrio:</label>
                        <input type="text" id="barrio" name="barrio"
                            value="<?php echo $chofer_a_editar['barrio'] ?? ''; ?>" required>
                    </div>

                    <div class="fila">
                        <label for="cp">Código Postal:</label>
                        <input type="text" id="cp" name="cp"
                            value="<?php echo $chofer_a_editar['cp'] ?? ''; ?>" required>
                    </div>

                    <div class="botones">
                        <button type="submit" name="guardar" class="btn btn-success">
                            <?php echo $chofer_a_editar ? "Actualizar" : "Crear Chofer"; ?>
                        </button>

                        <?php if ($chofer_a_editar): ?>
                            <a href="listado_choferes.php" class="btn btn-gray">Cancelar</a>
                        <?php endif; ?>

                        <a href="../../inicio_0.php" class="btn btn-danger">SALIR</a>
                    </div>

                </form>
            </div>

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
                            <th>Vehículo</th>
                            <th>Tipo</th>
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
                                    <?php $vehiculoAsignado = $vehiculoPorChofer[$c['id']] ?? null;?>
                                    <td>
                                        <?php if ($vehiculoAsignado): ?>
                                            <?php echo htmlspecialchars($vehiculoAsignado['marca'] . ' ' . $vehiculoAsignado['modelo'] . ' (' . $vehiculoAsignado['patente'] . ')'); ?>
                                        <?php else: ?>
                                            <span class="text-muted">Sin vehículo</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?php if ($vehiculoAsignado && !empty($vehiculoAsignado['tipo'])): ?>
                                            <strong><?php echo htmlspecialchars(ucfirst($vehiculoAsignado['tipo'])); ?></strong>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?php if ($vehiculoAsignado): ?>
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
                                <td colspan="14" class="text-center">No hay choferes.</td>
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