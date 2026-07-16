<?php
include_once "../../../funciones/funciones.php";

protegerPagina([0, 3]);

// GUARDAR
if (isset($_POST['guardar'])) {
    guardarVehiculo($_POST);
    header("Location: listado.php");
    exit;
}

// BORRAR
if (isset($_GET['borrar'])) {
    borrarVehiculo((int)$_GET['borrar']);
    header("Location: listado.php");
    exit;
}

// EDITAR
$vehiculo_a_editar = null;
if (isset($_GET['editar'])) {
    $vehiculo_a_editar = obtenerVehiculoPorId((int)$_GET['editar']);
}

// DATOS
$vehiculos = obtenerVehiculos();
$choferes  = obtenerChoferes();

// Diccionario id_chofer -> número de móvil, para mostrarlo en la tabla
$movilPorChofer = [];
foreach ($choferes as $c) {
    $movilPorChofer[$c['id']] = $c['movil'] ?? '-';
}

// Choferes que ya están asignados a OTRO vehículo (para no ofrecerlos de nuevo en el desplegable)
$choferesOcupados = [];
foreach ($vehiculos as $veh) {
    $esElMismoQueEditamos = $vehiculo_a_editar && $veh['id'] == $vehiculo_a_editar['id'];
    if (!empty($veh['id_chofer']) && !$esElMismoQueEditamos) {
        $choferesOcupados[] = $veh['id_chofer'];
    }
}

// 🔥 NORMALIZACIÓN DEL TIPO PARA EL FORMULARIO
// Convertimos a minúsculas y limpiamos espacios para que coincida siempre con el select
$tipo_actual = '';
if ($vehiculo_a_editar && !empty($vehiculo_a_editar['tipo'])) {
    $tipo_actual = strtolower(trim($vehiculo_a_editar['tipo']));
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vehículos</title>
    <link rel="stylesheet" href="../../../css/estilos.css">
</head>

<body>

    <div class="container">
        <h2 class="text-center">Gestión de Vehículos</h2>

        <div class="card operadores-layout">

            <div class="col-form">
                <h3><?php echo $vehiculo_a_editar ? "Editar Vehículo" : "Nuevo Vehículo"; ?></h3>

                <form method="POST">

                    <input type="hidden" name="id" value="<?php echo $vehiculo_a_editar['id'] ?? ''; ?>">

                    <label>Categoría:</label>
                    <select name="categoria" required>
                        <option value="">Seleccione categoria...</option>
                        <option value="TAXI" <?php if (($vehiculo_a_editar['categoria'] ?? '') == 'TAXI') echo 'selected'; ?>>TAXI</option>
                        <option value="REMIS" <?php if (($vehiculo_a_editar['categoria'] ?? '') == 'REMIS') echo 'selected'; ?>>REMIS</option>
                    </select>

                    <input type="text" name="marca" placeholder="Marca (Ej: Chevrolet)"
                        value="<?php echo htmlspecialchars($vehiculo_a_editar['marca'] ?? ''); ?>" required>

                    <input type="text" name="modelo" placeholder="Modelo (Ej: Spin)"
                        value="<?php echo htmlspecialchars($vehiculo_a_editar['modelo'] ?? ''); ?>">

                    <input type="text" name="patente" placeholder="Patente"
                        value="<?php echo htmlspecialchars($vehiculo_a_editar['patente'] ?? ''); ?>">

                    <input type="text" name="color" placeholder="Color"
                        value="<?php echo htmlspecialchars($vehiculo_a_editar['color'] ?? ''); ?>">

                    <label>Tipo de Unidad:</label>
                    <select name="tipo" required>
                        <option value="">Seleccione tipo...</option>
                        <option value="SEDAN" <?php if ($tipo_actual === 'sedan') echo 'selected'; ?>>Sedán</option>
                        <option value="VAN" <?php if ($tipo_actual === 'van') echo 'selected'; ?>>Van</option>
                        <option value="UTILITARIO" <?php if ($tipo_actual === 'utilitario') echo 'selected'; ?>>Utilitario</option>
                    </select>

                    <label>Estado:</label>
                    <select name="estado">
                        <option value="disponible"
                            <?php if (($vehiculo_a_editar['estado'] ?? '') == 'disponible') echo 'selected'; ?>>
                            Libre
                        </option>
                        <option value="con_chofer"
                            <?php if (($vehiculo_a_editar['estado'] ?? '') == 'con_chofer') echo 'selected'; ?>>
                            Con Chofer
                        </option>
                        <option value="taller"
                            <?php if (($vehiculo_a_editar['estado'] ?? '') == 'taller') echo 'selected'; ?>>
                            Taller
                        </option>
                    </select>

                    <label>Chofer:</label>
                    <select name="id_chofer">
                        <option value="">Sin asignar</option>
                        <?php foreach ($choferes as $c): ?>
                            <?php if (in_array($c['id'], $choferesOcupados)) continue; ?>
                            <option value="<?php echo $c['id']; ?>"
                                <?php if (($vehiculo_a_editar['id_chofer'] ?? '') == $c['id']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($c['apellido'] . ' ' . $c['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <div style="margin-top: 20px;">
                        <button type="submit" name="guardar" class="btn btn-success">
                            <?php echo $vehiculo_a_editar ? "Actualizar" : "Crear Vehículo"; ?>
                        </button>

                        <?php if ($vehiculo_a_editar): ?>
                            <a href="listado.php" class="btn btn-gray">Cancelar</a>
                        <?php endif; ?>

                        <a href="../../inicio_0.php" class="btn btn-danger">SALIR</a>
                    </div>

                </form>
            </div>

            <div class="col-tabla">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Móvil</th>
                            <th>Categoría</th>
                            <th>Marca/Modelo</th>
                            <th>Color</th>
                            <th>Tipo</th>
                            <th>Patente</th>
                            <th>Estado</th>
                            <th>Chofer</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (count($vehiculos) > 0): ?>
                            <?php foreach ($vehiculos as $v): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($movilPorChofer[$v['id_chofer'] ?? null] ?? '-'); ?></strong></td>
                                    <td><strong><?php echo htmlspecialchars($v['categoria']); ?></strong></td>
                                    <td>
                                        <?php echo htmlspecialchars($v['marca']); ?><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($v['modelo'] ?? ''); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($v['color']); ?></td>
                                    <td><strong><?php echo htmlspecialchars(ucfirst($v['tipo'] ?? '-')); ?></strong></td>
                                    <td><strong><?php echo htmlspecialchars($v['patente']); ?></strong></td>

                                    <td>
                                        <?php if ($v['estado'] == 'disponible'): ?>
                                            <span class="badge badge-warning">Libre</span>
                                        <?php elseif ($v['estado'] == 'con_chofer'): ?>
                                            <span class="badge badge-success">Con Chofer</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">Taller</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?php echo htmlspecialchars($v['chofer'] ?? 'Sin asignar'); ?>
                                    </td>

                                    <td>
                                        <a href="?editar=<?php echo $v['id']; ?>" class="btn btn-warning btn-sm">Editar</a>
                                        <a href="?borrar=<?php echo $v['id']; ?>"
                                            class="btn btn-danger btn-sm"
                                            onclick="return confirm('¿Eliminar vehículo?')">
                                            Borrar
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center">No hay vehículos registrados.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>

</body>

</html>