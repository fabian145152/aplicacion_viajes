<?php
include_once "../../../funciones/funciones.php";
protegerPagina([0, 3]);

$usuario = nombre_usuario();
$nombre_usuario = $usuario['nombre'];
$usuario_id = $usuario['id'];

$id_empresa = (int)($_GET['id_empresa'] ?? 0);

if (!$id_empresa) {
    die("Empresa no válida");
}

$empresa = obtenerEmpresaPorId($id_empresa);

// ============================================================
// GUARDAR (INSERTAR O ACTUALIZAR)
// ============================================================
if (isset($_POST['guardar'])) {
    $id = isset($_POST['id']) && !empty($_POST['id']) ? (int)$_POST['id'] : 0;

    // Si es edición, obtener datos anteriores para auditoría
    $datos_anteriores = null;
    if ($id > 0) {
        $centro_actual = obtenerCentroCostoParaAuditoria($id);
        if ($centro_actual) {
            unset($centro_actual['id']);
            unset($centro_actual['id_empresa']);
            $datos_anteriores = $centro_actual;
        }
    }

    // Guardar el centro de costo
    guardarCentroCosto($_POST);

    // Obtener el ID del registro
    $conn = conexion();
    $registro_id = $id > 0 ? $id : $conn->lastInsertId();

    // Obtener los datos nuevos para auditoría
    $datos_nuevos = $_POST;
    unset($datos_nuevos['guardar']);
    unset($datos_nuevos['id']);
    unset($datos_nuevos['id_empresa']);

    // Determinar operación: 'C' (insert) o 'U' (update)
    $operacion = $id > 0 ? 'U' : 'C';
    $tabla = 'centros_costo';

    // Registrar auditoría
    registrarAuditoria(
        $usuario_id,
        $tabla,
        $operacion,
        $registro_id,
        $datos_anteriores,
        $datos_nuevos
    );

    $_SESSION['mensaje'] = $id > 0 ? 'Centro de costo actualizado correctamente' : 'Centro de costo creado correctamente';
    $_SESSION['tipo_mensaje'] = 'exito';

    header("Location: centro_de_costos.php?id_empresa=" . $id_empresa);
    exit;
}

// ============================================================
// BORRAR
// ============================================================
$mensajeError = '';
if (isset($_GET['borrar'])) {
    $id_borrar = (int)$_GET['borrar'];

    // Obtener datos antes de borrar para auditoría
    $datos_anteriores = obtenerCentroCostoParaAuditoria($id_borrar);
    if ($datos_anteriores) {
        unset($datos_anteriores['id']);
        unset($datos_anteriores['id_empresa']);
    }

    $resultado = borrarCentroCosto($id_borrar);

    if ($resultado === true) {
        // Registrar auditoría de eliminación
        registrarAuditoria(
            $usuario_id,
            'centros_costo',
            'D',
            $id_borrar,
            $datos_anteriores,
            null
        );

        $_SESSION['mensaje'] = 'Centro de costo eliminado correctamente';
        $_SESSION['tipo_mensaje'] = 'exito';
    } else {
        $mensajeError = htmlspecialchars($resultado);
    }

    header("Location: centro_de_costos.php?id_empresa=" . $id_empresa);
    exit;
}

// ============================================================
// EDITAR
// ============================================================
$editar = null;
if (isset($_GET['editar'])) {
    $editar = obtenerCentroCostoPorId((int)$_GET['editar']);
}

$centros = obtenerCentrosCostoPorEmpresa($id_empresa);
$nombre_empresa = htmlspecialchars($empresa['razon_social']);
$id_empresa_display = htmlspecialchars($empresa['id_empresa']);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Centros de Costos</title>
    <link rel="stylesheet" href="../../../css/estilos.css">
    <style>
        .mensaje-exito {
            background: #d4edda;
            color: #155724;
            padding: 10px 15px;
            border-radius: 4px;
            border: 1px solid #c3e6cb;
            margin-bottom: 15px;
        }

        .mensaje-error {
            background: #f8d7da;
            color: #721c24;
            padding: 10px 15px;
            border-radius: 4px;
            border: 1px solid #f5c6cb;
            margin-bottom: 15px;
        }

        .buscador {
            margin-bottom: 15px;
        }

        .buscador input {
            width: 100%;
            padding: 8px 12px;
            border-radius: 4px;
            border: 1px solid #ccc;
            font-size: 14px;
        }

        .btn-accion {
            display: inline-block;
            padding: 4px 10px;
            margin: 2px;
            border-radius: 3px;
            text-decoration: none;
            font-size: 12px;
        }

        .btn-gray {
            background: #6c757d;
            color: white;
            padding: 8px 16px;
            border-radius: 4px;
            text-decoration: none;
            border: none;
            cursor: pointer;
            display: inline-block;
        }

        .btn-gray:hover {
            background: #5a6268;
        }

        .form-2cols {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .form-2cols input {
            width: 100%;
        }

        .form-full {
            grid-column: 1 / -1;
        }
    </style>
</head>

<body>
    <div class="container">
        <span><strong><?= htmlspecialchars($nombre_usuario) ?></strong></span>

        <?php if (!empty($mensajeError)): ?>
            <div class="mensaje-error"><?= $mensajeError ?></div>
        <?php endif; ?>

        <?php if (isset($_SESSION['mensaje'])): ?>
            <div class="mensaje-<?= $_SESSION['tipo_mensaje'] ?>">
                <?= $_SESSION['mensaje'] ?>
            </div>
            <?php unset($_SESSION['mensaje']);
            unset($_SESSION['tipo_mensaje']); ?>
        <?php endif; ?>

        <h2>CUENTA N° <?= $id_empresa_display ?> - CENTRO DE COSTOS DE LA EMPRESA: <?= $nombre_empresa ?></h2>

        <div class="card operadores-layout">
            <div class="col-form">
                <h3><?= $editar ? 'Editar Centro' : 'Nuevo Centro' ?></h3>

                <form method="POST">
                    <input type="hidden" name="id" value="<?= $editar['id'] ?? '' ?>">
                    <input type="hidden" name="id_empresa" value="<?= $id_empresa ?>">

                    <div class="form-2cols">
                        <input type="text" name="id_centro_costo"
                            placeholder="Código del Centro de Costo"
                            value="<?= $editar['id_centro_costo'] ?? '' ?>">

                        <input type="text" name="nombre"
                            placeholder="Nombre del Centro de Costo" required
                            value="<?= $editar['nombre'] ?? '' ?>">
                    </div>

                    <input type="text" name="direccion"
                        placeholder="Dirección"
                        value="<?= $editar['direccion'] ?? '' ?>">

                    <textarea name="obs" placeholder="Observaciones" rows="3"><?= htmlspecialchars($editar['observaciones'] ?? '') ?></textarea>

                    <button type="submit" name="guardar" class="btn btn-success">Guardar</button>
                    <a href="listado_empresas.php" class="btn btn-danger">Volver</a>
                </form>
            </div>

            <div class="col-tabla">
                <div class="buscador">
                    <input type="text" id="buscarCentro" placeholder="🔍 Buscar centro de costo...">
                </div>

                <table class="table" id="tablaCentros">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Nombre</th>
                            <th>Dirección</th>
                            <th>Observaciones</th>
                            <th>Acciones</th>
                            <th>Autorizantes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($centros)): ?>
                            <tr>
                                <td colspan="6" style="text-align:center; color:#999;">No hay centros de costo registrados</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($centros as $c): ?>
                                <tr>
                                    <td><?= htmlspecialchars($c['id_centro_costo'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($c['nombre']) ?></td>
                                    <td><?= htmlspecialchars($c['direccion'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($c['observaciones'] ?? '') ?></td>
                                    <td>
                                        <a href="?id_empresa=<?= $id_empresa ?>&editar=<?= $c['id'] ?>" class="btn-accion btn-warning">Editar</a>
                                        <a href="?id_empresa=<?= $id_empresa ?>&borrar=<?= $c['id'] ?>" class="btn-accion btn-danger"
                                            onclick="return confirm('¿Eliminar este centro de costo?')">Borrar</a>
                                    </td>
                                    <td>
                                        <a href="autorizantes_cc.php?id_empresa=<?= $id_empresa ?>&id_cc=<?= $c['id'] ?>" class="btn-accion btn-success">Autorizantes</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('buscarCentro').addEventListener('keyup', function() {
            const filtro = this.value.toLowerCase();
            const filas = document.querySelectorAll('#tablaCentros tbody tr');
            filas.forEach(tr => {
                const texto = tr.textContent.toLowerCase();
                tr.style.display = texto.includes(filtro) ? '' : 'none';
            });
        });
    </script>
</body>

</html>