<?php
include_once "../../../funciones/funciones.php";

protegerPagina([0, 3]);

$id_empresa = (int)($_GET['id_empresa'] ?? 0);
$id_cc      = (int)($_GET['id_cc'] ?? 0);

if (!$id_empresa || !$id_cc) {
    die("Datos inválidos");
}

if (isset($_POST['guardar'])) {
    guardarAutorizante($_POST);

    header("Location: autorizantes_cc.php?id_empresa={$id_empresa}&id_cc={$id_cc}");
    exit;
}

if (isset($_GET['borrar'])) {
    borrarAutorizante($_GET['borrar']);

    header("Location: autorizantes_cc.php?id_empresa={$id_empresa}&id_cc={$id_cc}");
    exit;
}

$editar = null;

if (isset($_GET['editar'])) {
    $editar = obtenerAutorizantePorId($_GET['editar']);
}

$empresa = obtenerEmpresaPorId($id_empresa);

$autorizantes = obtenerAutorizantesPorCC($id_cc);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Autorizantes</title>
    <link rel="stylesheet" href="../../../css/estilos.css">
</head>

<body>

    <div class="container">

        <h2>
            CUENTA N° <?= $empresa['id_empresa'] ?> - AUTORIZANTES DE LA EMPRESA: <?= htmlspecialchars($empresa['razon_social']) ?>
        </h2>

        <div class="card operadores-layout">

            <!-- FORMULARIO -->
            <div class="col-form">

                <h3>
                    <?= $editar ? 'Editar Autorizante' : 'Nuevo Autorizante' ?>
                </h3>

                <form method="POST">

                    <input
                        type="hidden"
                        name="id"
                        value="<?= $editar['id'] ?? '' ?>">

                    <input
                        type="hidden"
                        name="id_empresa"
                        value="<?= $id_empresa ?>">

                    <input
                        type="hidden"
                        name="id_cc"
                        value="<?= $id_cc ?>">

                    <input
                        type="text"
                        name="nombre"
                        placeholder="Nombre"
                        required
                        value="<?= $editar['nombre'] ?? '' ?>">

                    <input
                        type="text"
                        name="celular"
                        placeholder="Celular"
                        value="<?= $editar['celular'] ?? '' ?>">

                    <input
                        type="text"
                        name="email"
                        placeholder="Email"
                        value="<?= $editar['email'] ?? '' ?>">


                    <input
                        type="text"
                        name="horario"
                        placeholder="Horario"
                        value="<?= $editar['horario'] ?? '' ?>">

                    <button
                        type="submit"
                        name="guardar"
                        class="btn btn-success">
                        Guardar
                    </button>

                    <a
                        href="centro_de_costos.php?id_empresa=<?= $id_empresa ?>"
                        class="btn btn-danger">
                        Volver
                    </a>

                </form>

            </div>

            <!-- TABLA -->
            <div class="col-tabla">

                <table class="table">

                    <thead>
                        <tr>

                            <th>Nombre</th>
                            <th>Celular</th>
                            <th>Email</th>
                            <th>Horario</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($autorizantes as $a): ?>

                            <tr>

                                <!-- <td><?= $a['id'] ?></td> -->

                                <td>
                                    <?= htmlspecialchars($a['nombre']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($a['celular']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($a['email']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($a['horario']) ?>
                                </td>

                                <td>

                                    <a
                                        href="?id_empresa=<?= $id_empresa ?>&id_cc=<?= $id_cc ?>&editar=<?= $a['id'] ?>"
                                        class="btn btn-warning">
                                        Editar
                                    </a>

                                    <a
                                        href="?id_empresa=<?= $id_empresa ?>&id_cc=<?= $id_cc ?>&borrar=<?= $a['id'] ?>"
                                        class="btn btn-danger"
                                        onclick="return confirm('¿Eliminar autorizante?')">
                                        Borrar
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