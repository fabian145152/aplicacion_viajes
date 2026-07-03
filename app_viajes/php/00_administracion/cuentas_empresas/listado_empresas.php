<?php
include_once "../../../funciones/funciones.php";

protegerPagina([0, 3]);

// GUARDAR
if (isset($_POST['guardar'])) {
    guardarEmpresa($_POST);
    header("Location: listado_empresas.php");
    exit;
}

// BORRAR
$mensajeError = '';

if (isset($_GET['borrar'])) {

    $resultado = borrarEmpresa($_GET['borrar']);

    if ($resultado === true) {
        header("Location: centro_de_costos.php?id_empresa=" . $_GET['borrar']);
        exit;
    } else {
        $mensajeError = $resultado;
    }
}



// EDITAR
$empresa = null;
if (isset($_GET['editar'])) {
    $empresa = obtenerEmpresaPorId($_GET['editar']);
}

// DATOS
$empresas = obtenerEmpresas();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Empresas</title>
    <link rel="stylesheet" href="../../../css/estilos.css">
</head>

<body>
    <div class="container">

        <?php if (!empty($mensajeError)): ?>
            <script>
                alert("<?= addslashes($mensajeError) ?>");
            </script>
        <?php endif; ?>

        <h2 class="text-center">
            CUENTAS CORRIENTES DE EMPRESAS
        </h2>

        <div class="card operadores-layout">

            <!-- FORMULARIO -->
            <div class="col-form">

                <h3>
                    <?= $empresa ? "Editar Empresa" : "Nueva Empresa"; ?>
                </h3>

                <form method="POST">

                    <input
                        type="hidden"
                        name="id"
                        value="<?= $empresa['id'] ?? '' ?>">

                    <input
                        type="number"
                        name="numero_cuenta"
                        placeholder="Número de Cuenta"
                        value="<?= $empresa['id_empresa'] ?? '' ?>"
                        required>

                    <input
                        type="text"
                        name="razon_social"
                        placeholder="Razón Social"
                        value="<?= $empresa['razon_social'] ?? '' ?>"
                        required>

                    <input
                        type="text"
                        name="dir"
                        placeholder="Dirección"
                        value="<?= $empresa['dir'] ?? '' ?>">

                    <input
                        type="text"
                        name="cuit"
                        placeholder="CUIT"
                        value="<?= $empresa['cuit'] ?? '' ?>">

                    <input
                        type="text"
                        name="inc_brutos"
                        placeholder="Ingresos Brutos"
                        value="<?= $empresa['inc_brutos'] ?? '' ?>">

                    <input
                        type="text"
                        name="contacto_1"
                        placeholder="Contacto"
                        value="<?= $empresa['contacto_1'] ?? '' ?>">

                    <input
                        type="text"
                        name="cel_1"
                        placeholder="Celular"
                        value="<?= $empresa['cel_1'] ?? '' ?>">



                    <button
                        type="submit"
                        name="guardar"
                        class="btn btn-success">

                        <?= $empresa ? "Actualizar" : "Crear Empresa"; ?>

                    </button>

                    <?php if ($empresa): ?>
                        <a href="listado_empresas.php" class="btn btn-gray">
                            Cancelar
                        </a>
                    <?php endif; ?>

                    <a href="../../inicio_0.php" class="btn btn-danger">
                        SALIR
                    </a>

                </form>

            </div>

            <!-- TABLA -->
            <div class="col-tabla">

                <table class="table">

                    <thead>
                        <tr>
                            <th>C/C</th>
                            <th>Razón Social</th>
                            <th>Contacto</th>
                            <th>Celular</th>
                            <th>Dirección</th>
                            <th>CUIT</th>
                            <th>Ing. Brutos</th>

                            <th>Acciones</th>
                            <th>Centro de Costos</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($empresas as $e): ?>

                            <tr>

                                <td><?= $e['id_empresa'] ?></td>

                                <td>
                                    <?= htmlspecialchars($e['razon_social']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($e['contacto_1'] ?? '-') ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($e['cel_1'] ?? '-') ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($e['dir'] ?? '-') ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($e['cuit'] ?? '-') ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($e['inc_brutos'] ?? '-') ?>
                                </td>


                                <td>

                                    <a
                                        href="?editar=<?= $e['id'] ?>"
                                        class="btn btn-warning">
                                        Editar
                                    </a>

                                    <a
                                        href="?borrar=<?= $e['id'] ?>"
                                        class="btn btn-danger"
                                        onclick="return confirm('¿Eliminar empresa?')">
                                        Borrar
                                    </a>

                                </td>

                                <td>

                                    <a
                                        href="centro_de_costos.php?id_empresa=<?= $e['id'] ?>"
                                        class="btn btn-gray">

                                        Centro de Costos

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