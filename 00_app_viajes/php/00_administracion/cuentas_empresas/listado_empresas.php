<?php
include_once "../../../funciones/funciones.php";
protegerPagina([0, 3]);

$usuario = nombre_usuario();
$nombre_usuario = $usuario['nombre'];
$usuario_id = $usuario['id'];

// ============================================================
// GUARDAR (INSERTAR O ACTUALIZAR)
// ============================================================
if (isset($_POST['guardar'])) {
    $id = isset($_POST['id']) && !empty($_POST['id']) ? (int)$_POST['id'] : 0;

    // Si es edición, obtener datos anteriores para auditoría
    $datos_anteriores = null;
    if ($id > 0) {
        $empresa_actual = obtenerEmpresaParaAuditoria($id);
        if ($empresa_actual) {
            unset($empresa_actual['id']);
            unset($empresa_actual['observaciones']);
            $datos_anteriores = $empresa_actual;
        }
    }

    // Guardar la empresa
    guardarEmpresa($_POST);

    // Obtener el ID del registro
    $conn = conexion();
    $registro_id = $id > 0 ? $id : $conn->lastInsertId();

    // Obtener los datos nuevos para auditoría
    $datos_nuevos = $_POST;
    unset($datos_nuevos['guardar']);
    unset($datos_nuevos['id']);

    // Determinar operación: 'C' (insert) o 'U' (update)
    $operacion = $id > 0 ? 'U' : 'C';
    $tabla = 'cuenta_empresa';

    // Registrar auditoría
    registrarAuditoria(
        $usuario_id,
        $tabla,
        $operacion,
        $registro_id,
        $datos_anteriores,
        $datos_nuevos
    );

    $_SESSION['mensaje'] = $id > 0 ? 'Empresa actualizada correctamente' : 'Empresa creada correctamente';
    $_SESSION['tipo_mensaje'] = 'exito';

    header("Location: listado_empresas.php");
    exit;
}

// ============================================================
// BORRAR
// ============================================================
$mensajeError = '';
if (isset($_GET['borrar'])) {
    $id_borrar = (int)$_GET['borrar'];

    // Obtener datos antes de borrar para auditoría
    $datos_anteriores = obtenerEmpresaParaAuditoria($id_borrar);
    if ($datos_anteriores) {
        unset($datos_anteriores['id']);
        unset($datos_anteriores['observaciones']);
    }

    $resultado = borrarEmpresa($id_borrar);

    if ($resultado === true) {
        // Registrar auditoría de eliminación
        registrarAuditoria(
            $usuario_id,
            'cuenta_empresa',
            'D',
            $id_borrar,
            $datos_anteriores,
            null
        );

        $_SESSION['mensaje'] = 'Empresa eliminada correctamente';
        $_SESSION['tipo_mensaje'] = 'exito';
    } else {
        $mensajeError = htmlspecialchars($resultado);
    }

    header("Location: listado_empresas.php");
    exit;
}

// ============================================================
// EDITAR
// ============================================================
$empresa = null;
if (isset($_GET['editar'])) {
    $empresa = obtenerEmpresaPorId((int)$_GET['editar']);
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
    </style>
</head>

<body>
    <div class="container">
        <span><strong><?php echo htmlspecialchars($nombre_usuario) ?></strong></span>

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

        <h2 class="text-center">CUENTAS CORRIENTES DE EMPRESAS</h2>

        <div class="card operadores-layout">

            <!-- FORMULARIO -->
            <div class="col-form">
                <h3><?= $empresa ? "Editar Empresa" : "Nueva Empresa"; ?></h3>

                <form method="POST">
                    <input type="hidden" name="id" value="<?= $empresa['id'] ?? '' ?>">

                    <input type="number" name="numero_cuenta"
                        placeholder="Número de Cuenta"
                        value="<?= $empresa['id_empresa'] ?? '' ?>" required>

                    <input type="text" name="razon_social"
                        placeholder="Razón Social"
                        value="<?= $empresa['razon_social'] ?? '' ?>" required>

                    <input type="text" name="dir"
                        placeholder="Dirección"
                        value="<?= $empresa['dir'] ?? '' ?>">

                    <input type="text" name="cuit"
                        placeholder="CUIT"
                        value="<?= $empresa['cuit'] ?? '' ?>">

                    <input type="text" name="inc_brutos"
                        placeholder="Ingresos Brutos"
                        value="<?= $empresa['inc_brutos'] ?? '' ?>">

                    <input type="text" name="contacto_1"
                        placeholder="Contacto"
                        value="<?= $empresa['contacto_1'] ?? '' ?>">

                    <input type="text" name="cel_1"
                        placeholder="Celular"
                        value="<?= $empresa['cel_1'] ?? '' ?>">

                    <button type="submit" name="guardar" class="btn btn-success">
                        <?= $empresa ? "Actualizar" : "Crear Empresa"; ?>
                    </button>

                    <?php if ($empresa): ?>
                        <a href="listado_empresas.php" class="btn-gray">Cancelar</a>
                    <?php endif; ?>

                    <a href="../../inicio_0.php" class="btn btn-danger">SALIR</a>
                </form>
            </div>

            <!-- TABLA -->
            <div class="col-tabla">
                <div class="buscador">
                    <input type="text" id="buscarEmpresa" placeholder="🔍 Buscar empresa...">
                </div>

                <table class="table" id="tablaEmpresas">
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
                        <?php if (empty($empresas)): ?>
                            <tr>
                                <td colspan="9" style="text-align:center; color:#999;">No hay empresas registradas</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($empresas as $e): ?>
                                <tr>
                                    <td><?= htmlspecialchars($e['id_empresa']) ?></td>
                                    <td><?= htmlspecialchars($e['razon_social']) ?></td>
                                    <td><?= htmlspecialchars($e['contacto_1'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($e['cel_1'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($e['dir'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($e['cuit'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($e['inc_brutos'] ?? '-') ?></td>
                                    <td>
                                        <a href="?editar=<?= $e['id'] ?>" class="btn-accion btn-warning">Editar</a>
                                        <a href="?borrar=<?= $e['id'] ?>" class="btn-accion btn-danger"
                                            onclick="return confirm('¿Eliminar empresa? Esto eliminará también sus centros de costo y autorizantes.')">Borrar</a>
                                    </td>
                                    <td>
                                        <a href="centro_de_costos.php?id_empresa=<?= $e['id'] ?>" class="btn-accion btn-gray">
                                            Centro de Costos
                                        </a>
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
        document.getElementById('buscarEmpresa').addEventListener('keyup', function() {
            const filtro = this.value.toLowerCase();
            const filas = document.querySelectorAll('#tablaEmpresas tbody tr');
            filas.forEach(tr => {
                const texto = tr.textContent.toLowerCase();
                tr.style.display = texto.includes(filtro) ? '' : 'none';
            });
        });
    </script>
</body>

</html>