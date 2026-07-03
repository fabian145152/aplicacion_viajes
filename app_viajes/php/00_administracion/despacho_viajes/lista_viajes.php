<?php
include_once "../../../funciones/funciones.php";
protegerPagina([0, 3]);

// Pasar diferidos a pendientes
// Pasar diferidos a inmediatos
$conn = conexion();
include_once "../seteos/min_diferido.php";

// Nota: Asegúrate de que dentro de 'min_diferido.php' se le asigne un valor numérico a $min_diferido (ej: $min_diferido = 15;)

echo "Minutos Diferido: " . $min_diferido . "<br>";

$min_diferido = $min_diferido - 1;



echo "<strong>Hora del Servidor: </strong>" . date('H:i:s');
//echo "<strong>Zona Horaria PHP:</strong> " . date_default_timezone_get();


$sql = "UPDATE viajes_despacho
SET estado = CASE
    WHEN TIMESTAMPDIFF(MINUTE, NOW(), TIMESTAMP(fecha, hora)) > ? THEN 'Diferido'
    ELSE 'Inmediato'
END
WHERE estado IN ('Diferido', 'Inmediato')
";

$stmt = $conn->prepare($sql);
$stmt->execute([$min_diferido]);


// BORRAR DIRECTO (Si todavía usás el botón clásico por GET)
if (isset($_GET['borrar'])) {
    borrarViaje((int)$_GET['borrar']);
    header("Location: lista_viajes.php");
    exit;
}

// ========================================================
// PROCESAR ENTRADAS DE MODALES (POST)
// ========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['viaje_id'])) {
    $idViaje = (int)$_POST['viaje_id'];
    $conn = conexion();

    // CASO A: ASIGNAR MÓVIL
    if (isset($_POST['id_chofer'])) {
        $idChofer = (int)$_POST['id_chofer'];
        $stmt = $conn->prepare("UPDATE viajes_despacho SET estado = 'Asignado', id_chofer = ? WHERE id = ?");
        $stmt->execute([$idChofer, $idViaje]);
    }

    // CASO B: CANCELAR VIAJE CON OBSERVACIÓN
    if (isset($_POST['obs_viaje'])) {
        $obsViaje = trim($_POST['obs_viaje']);

        $stmt = $conn->prepare("UPDATE viajes_despacho SET estado = 'Cancelado', obs_viaje = ? WHERE id = ?");
        $stmt->execute([$obsViaje, $idViaje]);
    }

    header("Location: lista_viajes.php" . (isset($_GET['estado']) ? "?estado=" . $_GET['estado'] : ""));
    exit;
}

$filtro = $_GET['estado'] ?? '';
$todosLosViajes = obtenerViajes();
$choferes = obtenerChoferesActivos();

/* CONTADORES */
$contadores = [
    'inmediato' => 0,
    'pendiente' => 0,
    'asignado' => 0,
    'en curso' => 0,
    'diferido' => 0,
    'completado' => 0,
    'cancelado' => 0
];

foreach ($todosLosViajes as $v) {
    $estado = strtolower(trim($v['estado']));

    if (isset($contadores[$estado])) {
        $contadores[$estado]++;
    }
}

/* FILTROS */
if ($filtro != '') {
    // Mostrar solamente el estado seleccionado
    $viajes = array_filter($todosLosViajes, function ($v) use ($filtro) {
        return strtolower(trim($v['estado'])) == strtolower(trim($filtro));
    });
} else {
    // Vista principal
    $viajes = array_filter($todosLosViajes, function ($v) {
        $estado = strtolower(trim($v['estado']));
        return in_array($estado, [
            'inmediato',
            'pendiente',
            'asignado',
            'en curso'
        ]);
    });
}

/* ORDENAMIENTO */
usort($viajes, function ($a, $b) {
    $aDif = strtolower(trim($a['estado'])) == 'diferido';
    $bDif = strtolower(trim($b['estado'])) == 'diferido';

    if ($aDif != $bDif) {
        return $aDif ? 1 : -1;
    }

    if ($aDif && $bDif) {
        $fechaA = strtotime($a['fecha'] . ' ' . $a['hora']);
        $fechaB = strtotime($b['fecha'] . ' ' . $b['hora']);
        return $fechaA <=> $fechaB;
    }

    return $b['id'] <=> $a['id'];
});
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Listado de Viajes</title>
    <link rel="stylesheet" href="../../../css/estilos.css">
    <link rel="stylesheet" href="../../../css/listado_viajes.css">
    <link rel="stylesheet" href="lista_viajes.css">
    <meta http-equiv="refresh" content="30">
</head>

<body>

    <div class="container">
        <div class="card">

            <div class="reloj-panel">
                <span class="reloj-icono">🕒</span>
                <span id="reloj-digital">00:00:00</span>
            </div>

            <h3>Estado de los Viajes</h3>

            <div class="menu-viajes">
                <a href="carga_viajes.php">+ Nuevo Viaje</a>
                <a href="../../01_mapeo/mapa_de_viajes.php" target="_blank">Mapa</a>
                <a href="lista_viajes.php">Todos (<?= $contadores['inmediato'] + $contadores['pendiente'] + $contadores['asignado'] + $contadores['en curso'] ?>)</a>
                <a href="?estado=inmediato" style="background: #e2e3e5; color:#000;">Inmediatos (<?= $contadores['inmediato'] ?>)</a>
                <a href="?estado=pendiente" style="background: #fff3cd; color:#000;">Pendientes (<?= $contadores['pendiente'] ?>)</a>
                <a href="?estado=asignado" style="background: #d1ecf1; color:#000;">Asignados (<?= $contadores['asignado'] ?>)</a>
                <a href="?estado=en curso" style="background: #d4edda; color:#000;">En curso (<?= $contadores['en curso'] ?>)</a>
                <a href="?estado=diferido" style="background: #e2d6c3; color:#000;">Diferidos (<?= $contadores['diferido'] ?>)</a>
                <a href="?estado=completado" style="background: #d6e9ff; color:#000;">Completados (<?= $contadores['completado'] ?>)</a>
                <a href="?estado=cancelado" style="background: #f8d7da; color:#000;">Cancelados (<?= $contadores['cancelado'] ?>)</a>
            </div>

            <div class="tabla-scroll">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Acciones</th>
                            <th>N° viaje</th>
                            <th>Pasajero</th>
                            <th>Celular</th>
                            <th>Origen</th>
                            <th>Destino</th>
                            <th>Obs operador</th>
                            <th>Obs chofer</th>
                            <th>Estado</th>
                            <?php if (strtolower($filtro) === 'cancelado'): ?>
                                <th style="background-color: #dc3545; color: white;">Motivo Cancelación</th>
                            <?php endif; ?>
                            <th>Fecha</th>
                            <th>Hora</th>
                            <th>Cat</th>
                            <th>Gps.</th>
                            <th>C/C</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($viajes as $v): ?>
                            <?php
                            $fondo = '#ffffff';
                            switch (strtolower(trim($v['estado']))) {
                                case 'inmediato':
                                    $fondo = '#e2e3e5';
                                    break;
                                case 'pendiente':
                                    $fondo = '#fff3cd';
                                    break;
                                case 'asignado':
                                    $fondo = '#d1ecf1';
                                    break;
                                case 'en curso':
                                    $fondo = '#d4edda';
                                    break;
                                case 'diferido':
                                    $fondo = '#e2d6c3';
                                    break;
                                case 'completado':
                                    $fondo = '#d6e9ff';
                                    break;
                                case 'cancelado':
                                    $fondo = '#f8d7da';
                                    break;
                            }
                            ?>

                            <tr style="background-color: <?= $fondo ?>;">
                                <td>
                                    <select name="acciones_viaje" onchange="evaluarAccion(this, <?= $v['id'] ?>)">
                                        <option disabled selected>Opciones</option>
                                        <option value="cerrar_en_base">Cerrar en base</option>
                                        <option value="asignar_movil">Asignar móvil</option>
                                        <option value="cancelar_viaje">Cancelar viaje</option>
                                    </select>
                                </td>
                                <td><?= $v['id'] ?></td>
                                <td><?= htmlspecialchars($v['nombre_pasaj']) ?></td>
                                <td><?= htmlspecialchars($v['cel_pasaj']) ?></td>
                                <td><?= htmlspecialchars($v['direccion_origen']) ?></td>
                                <td><?= htmlspecialchars($v['direccion_destino']) ?></td>
                                <td class="col-observaciones"><?= htmlspecialchars($v['obs_operador']) ?></td>
                                <td class="col-observaciones"><?= htmlspecialchars($v['obs_pasaj']) ?></td>
                                <td>
                                    <?php
                                    $color = '#000';
                                    switch (strtolower(trim($v['estado']))) {
                                        case 'inmediato':
                                            $color = '#6c757d';
                                            break;
                                        case 'pendiente':
                                            $color = '#ffc107';
                                            break;
                                        case 'asignado':
                                            $color = '#17a2b8';
                                            break;
                                        case 'en curso':
                                            $color = '#28a745';
                                            break;
                                        case 'diferido':
                                            $color = '#8b7355';
                                            break;
                                        case 'completado':
                                            $color = '#007bff';
                                            break;
                                        case 'cancelado':
                                            $color = '#dc3545';
                                            break;
                                    }
                                    ?>
                                    <span style="font-weight:bold;color:<?= $color ?>;">
                                        <?= htmlspecialchars($v['estado']) ?>
                                    </span>
                                </td>

                                <?php if (strtolower($filtro) === 'cancelado'): ?>
                                    <td class="col-observaciones" style="color: #dc3545; font-style: italic; font-weight: 500;">
                                        <?= htmlspecialchars($v['obs_viaje'] ?: 'Sin especificar') ?>
                                    </td>
                                <?php endif; ?>

                                <td><?= ($v['fecha'] === '0000-00-00' || empty($v['fecha'])) ? '' : htmlspecialchars($v['fecha']) ?></td>
                                <td><?= empty($v['hora']) ? '' : htmlspecialchars($v['hora']) ?></td>
                                <td><?= htmlspecialchars($v['categoria_movil']) ?></td>
                                <td style="text-align:center;">
                                    <?php $gps_ok = ($v['origen_lat'] < 0) && ($v['origen_lng'] < 0); ?>
                                    <?php if ($gps_ok): ?>
                                        <span style="color:green;font-size:20px;">✔</span>
                                    <?php else: ?>
                                        <span style="color:red;font-size:20px;">✘</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($v['empresa'] ?: 'VIAJE DE CALLE') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>

    <div id="modalAsignar" class="modal-asignar">
        <div class="modal-asignar-content">
            <span class="close-modal" onclick="cerrarModalAsignar()">&times;</span>
            <h3 style="margin-top:0; margin-bottom:15px;">Asignar Móvil - Viaje N° <span id="modal-id-viaje"></span></h3>

            <form method="POST" action="">
                <input type="hidden" name="viaje_id" id="input-modal-viaje-id">
                <div class="form-group">
                    <label for="id_chofer">Seleccione Unidad por Número:</label>
                    <select name="id_chofer" id="id_chofer" required>
                        <option value="" disabled selected>-- Seleccione un móvil --</option>
                        <?php if (!empty($choferes)): ?>
                            <?php foreach ($choferes as $c): ?>
                                <option value="<?= $c['id'] ?>">
                                    Móvil: <?= htmlspecialchars($c['movil']) ?> — <?= htmlspecialchars($c['nombre'] . " " . $c['apellido']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-modal-cancelar" onclick="cerrarModalAsignar()">Cancelar</button>
                    <button type="submit" class="btn-modal-guardar">Confirmar</button>
                </div>
            </form>
        </div>
    </div>

    <div id="modalCancelar" class="modal-asignar">
        <div class="modal-asignar-content" style="border-top: 5px solid #dc3545;">
            <span class="close-modal" onclick="cerrarModalCancelar()">&times;</span>
            <h3 style="margin-top:0; margin-bottom:15px; color: #dc3545;">Cancelar Viaje N° <span id="modal-cancelar-id-viaje"></span></h3>

            <form method="POST" action="" onsubmit="return confirmarCancelacion()">
                <input type="hidden" name="viaje_id" id="input-modal-cancelar-viaje-id">
                <div class="form-group">
                    <label for="obs_viaje">Motivo / Observación de la Cancelación:</label>
                    <textarea name="obs_viaje" id="obs_viaje" rows="4" style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #ccc; font-size: 13px; font-family: sans-serif; resize: vertical;" required placeholder="Escriba aquí el motivo por el cual se cancela el viaje..."></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-modal-cancelar" onclick="cerrarModalCancelar()">Volver</button>
                    <button type="submit" class="btn-modal-guardar" style="background: #dc3545;">Confirmar Cancelación</button>
                </div>
            </form>
        </div>
    </div>

    <script>

    </script>
    <script src="lista_viajes.js"></script>
</body>

</html>