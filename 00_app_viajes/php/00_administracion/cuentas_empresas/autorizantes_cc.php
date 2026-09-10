<?php
include_once "../../../funciones/funciones.php";
protegerPagina([0, 3]);

$usuario = nombre_usuario();
$nombre_usuario = $usuario['nombre'];
$usuario_id = $usuario['id'];

$id_empresa = (int)($_GET['id_empresa'] ?? 0);
$id_cc = (int)($_GET['id_cc'] ?? 0);

if (!$id_empresa || !$id_cc) {
    die("Datos inválidos");
}

// ============================================================
// GUARDAR (INSERTAR O ACTUALIZAR)
// ============================================================
if (isset($_POST['guardar'])) {
    $id = isset($_POST['id']) && !empty($_POST['id']) ? (int)$_POST['id'] : 0;

    // Si es edición, obtener datos anteriores para auditoría
    $datos_anteriores = null;
    if ($id > 0) {
        $autorizante_actual = obtenerAutorizanteParaAuditoria($id);
        if ($autorizante_actual) {
            unset($autorizante_actual['id']);
            unset($autorizante_actual['id_empresa']);
            unset($autorizante_actual['id_centro_costo']);
            unset($autorizante_actual['estado']);
            $datos_anteriores = $autorizante_actual;
        }
    }

    // Guardar el autorizante
    guardarAutorizante($_POST);

    // Obtener el ID del registro
    $conn = conexion();
    $registro_id = $id > 0 ? $id : $conn->lastInsertId();

    // Obtener los datos nuevos para auditoría
    $datos_nuevos = $_POST;
    unset($datos_nuevos['guardar']);
    unset($datos_nuevos['id']);
    unset($datos_nuevos['id_empresa']);
    unset($datos_nuevos['id_cc']);

    // Determinar operación: 'C' (insert) o 'U' (update)
    $operacion = $id > 0 ? 'U' : 'C';
    $tabla = 'autorizantes';

    // Registrar auditoría
    registrarAuditoria(
        $usuario_id,
        $tabla,
        $operacion,
        $registro_id,
        $datos_anteriores,
        $datos_nuevos
    );

    $_SESSION['mensaje'] = $id > 0 ? 'Autorizante actualizado correctamente' : 'Autorizante creado correctamente';
    $_SESSION['tipo_mensaje'] = 'exito';

    header("Location: autorizantes_cc.php?id_empresa={$id_empresa}&id_cc={$id_cc}");
    exit;
}

// ============================================================
// BORRAR
// ============================================================
if (isset($_GET['borrar'])) {
    $id_borrar = (int)$_GET['borrar'];

    // Obtener datos antes de borrar para auditoría
    $datos_anteriores = obtenerAutorizanteParaAuditoria($id_borrar);
    if ($datos_anteriores) {
        unset($datos_anteriores['id']);
        unset($datos_anteriores['id_empresa']);
        unset($datos_anteriores['id_centro_costo']);
        unset($datos_anteriores['estado']);
    }

    $resultado = borrarAutorizante($id_borrar);

    // Registrar auditoría de eliminación
    registrarAuditoria(
        $usuario_id,
        'autorizantes',
        'D',
        $id_borrar,
        $datos_anteriores,
        null
    );

    $_SESSION['mensaje'] = 'Autorizante eliminado correctamente';
    $_SESSION['tipo_mensaje'] = 'exito';

    header("Location: autorizantes_cc.php?id_empresa={$id_empresa}&id_cc={$id_cc}");
    exit;
}

// ============================================================
// EDITAR
// ============================================================
$editar = null;
if (isset($_GET['editar'])) {
    $editar = obtenerAutorizantePorId((int)$_GET['editar']);
}

$empresa = obtenerEmpresaPorId($id_empresa);
$autorizantes = obtenerAutorizantesPorCC($id_cc);
$nombre_empresa = htmlspecialchars($empresa['razon_social']);
$id_empresa_display = htmlspecialchars($empresa['id_empresa']);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Autorizantes</title>
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

        <?php if (isset($_SESSION['mensaje'])): ?>
            <div class="mensaje-<?= $_SESSION['tipo_mensaje'] ?>">
                <?= $_SESSION['mensaje'] ?>
            </div>
            <?php unset($_SESSION['mensaje']);
            unset($_SESSION['tipo_mensaje']); ?>
        <?php endif; ?>

        <h2>CUENTA N° <?= $id_empresa_display ?> - AUTORIZANTES DE LA EMPRESA: <?= $nombre_empresa ?></h2>

        <div class="card operadores-layout">
            <div class="col-form">
                <h3><?= $editar ? 'Editar Autorizante' : 'Nuevo Autorizante' ?></h3>

                <form method="POST">
                    <input type="hidden" name="id" value="<?= $editar['id'] ?? '' ?>">
                    <input type="hidden" name="id_empresa" value="<?= $id_empresa ?>">
                    <input type="hidden" name="id_cc" value="<?= $id_cc ?>">

                    <div class="form-2cols">
                        <input type="text" name="nombre"
                            placeholder="Nombre" required
                            value="<?= $editar['nombre'] ?? '' ?>">

                        <input type="text" name="celular"
                            placeholder="Celular"
                            value="<?= $editar['celular'] ?? '' ?>">

                        <input type="email" name="email"
                            placeholder="Email"
                            value="<?= $editar['email'] ?? '' ?>">

                        <input type="text" name="horario"
                            placeholder="Horario"
                            value="<?= $editar['horario'] ?? '' ?>">
                    </div>

                    <div class="form-full" style="margin-top:10px;">
                        <button type="submit" name="guardar" class="btn btn-success">Guardar</button>
                        <a href="centro_de_costos.php?id_empresa=<?= $id_empresa ?>" class="btn btn-danger">Volver</a>
                    </div>
                </form>
            </div>

            <div class="col-tabla">
                <div class="buscador">
                    <input type="text" id="buscarAutorizante" placeholder="🔍 Buscar autorizante...">
                </div>

                <table class="table" id="tablaAutorizantes">
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
                        <?php if (empty($autorizantes)): ?>
                            <tr>
                                <td colspan="5" style="text-align:center; color:#999;">No hay autorizantes registrados</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($autorizantes as $a): ?>
                                <tr>
                                    <td><?= htmlspecialchars($a['nombre']) ?></td>
                                    <td><?= htmlspecialchars($a['celular']) ?></td>
                                    <td><?= htmlspecialchars($a['email']) ?></td>
                                    <td><?= htmlspecialchars($a['horario']) ?></td>
                                    <td>
                                        <a href="?id_empresa=<?= $id_empresa ?>&id_cc=<?= $id_cc ?>&editar=<?= $a['id'] ?>" class="btn-accion btn-warning">Editar</a>
                                        <a href="?id_empresa=<?= $id_empresa ?>&id_cc=<?= $id_cc ?>&borrar=<?= $a['id'] ?>" class="btn-accion btn-danger" onclick="return confirm('¿Eliminar autorizante?')">Borrar</a>
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
        document.getElementById('buscarAutorizante').addEventListener('keyup', function() {
            const filtro = this.value.toLowerCase();
            const filas = document.querySelectorAll('#tablaAutorizantes tbody tr');
            filas.forEach(tr => {
                const texto = tr.textContent.toLowerCase();
                tr.style.display = texto.includes(filtro) ? '' : 'none';
            });
        });
    </script>
</body>

</html>