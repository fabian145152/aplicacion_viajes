<?php
include_once "../../../funciones/funciones.php";

protegerPagina([0, 3]);

$id_empresa = (int)($_GET['id_empresa'] ?? 0);


if (!$id_empresa) {
    die("Empresa no válida");
}

$empresa = obtenerEmpresaPorId($id_empresa);

if (isset($_POST['guardar'])) {
    guardarCentroCosto($_POST);

    header("Location: centro_de_costos.php?id_empresa=" . $id_empresa);
    exit;
}

$mensajeError = '';

if (isset($_GET['borrar'])) {

    $resultado = borrarCentroCosto($_GET['borrar']);

    if ($resultado === true) {

        header("Location: centro_de_costos.php?id_empresa=" . $id_empresa);
        exit;
    } else {

        $mensajeError = $resultado;
    }
}

$editar = null;

if (isset($_GET['editar'])) {
    $editar = obtenerCentroCostoPorId($_GET['editar']);
}

$centros = obtenerCentrosCostoPorEmpresa($id_empresa);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Centros de Costos</title>
    <link rel="stylesheet" href="../../../css/estilos.css">
</head>

<body>

    <div class="container">
        <?php if (!empty($mensajeError)): ?>
            <script>
                alert("<?= addslashes($mensajeError) ?>");
            </script>
        <?php endif; ?>
        <h2>CUENTA N° <?= $empresa['id_empresa'] ?> -
            CENTRO DE COSTOS DE LA EMPRESA:
            <?= $empresa['razon_social'] ?>
        </h2>

        <div class="card operadores-layout">

            <div class="col-form">

                <h3>
                    <?= $editar ? 'Editar Centro' : 'Nuevo Centro' ?>
                </h3>

                <form method="POST">

                    <input type="hidden" name="id"
                        value="<?= $editar['id'] ?? '' ?>">

                    <input type="hidden" name="id_empresa"
                        value="<?= $id_empresa ?>">

                    <input
                        type="text"
                        name="nombre"
                        placeholder="Nombre del Centro de Costo"
                        required
                        value="<?= $editar['nombre'] ?? '' ?>">

                    <input
                        type="text"
                        name="direccion"
                        placeholder="Dirección"
                        value="<?= $editar['direccion'] ?? '' ?>">

                    <textarea
                        name="obs"
                        placeholder="Observaciones"><?= htmlspecialchars($editar['observaciones'] ?? '') ?></textarea>



                    <button
                        type="submit"
                        name="guardar"
                        class="btn btn-success">
                        Guardar
                    </button>

                    <a href="listado_empresas.php"
                        class="btn btn-danger">
                        Volver
                    </a>

                </form>

            </div>

            <div class="col-tabla">

                <table class="table">

                    <thead>
                        <tr>

                            <th>Empresa</th>
                            <th>Nombre</th>
                            <th>Dirección</th>
                            <th>Observaciones</th>
                            <th>Acciones</th>
                            <th>Autorizantes</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($centros as $c): ?>

                            <tr>
                                <td><?= htmlspecialchars($empresa['razon_social']) ?></td>
                                <td><?= htmlspecialchars($c['nombre']) ?></td>
                                <td><?= htmlspecialchars($c['direccion'] ?? '') ?></td>
                                <td><?= htmlspecialchars($c['observaciones'] ?? '') ?></td>

                                <td>
                                    <a href="?id_empresa=<?= $id_empresa ?>&editar=<?= $c['id'] ?>" class="btn btn-warning">
                                        Editar
                                    </a>
                                    <a href="?id_empresa=<?= $id_empresa ?>&borrar=<?= $c['id'] ?>" class="btn btn-danger" onclick="return confirm('¿Eliminar?')">
                                        Borrar
                                    </a>
                                </td>

                                <td>
                                    <a href="autorizantes_cc.php?id_empresa=<?= $id_empresa ?>&id_cc=<?= $c['id'] ?>" class="btn btn-success">
                                        Autorizantes
                                    </a>
                                </td>
                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</body>

</html>