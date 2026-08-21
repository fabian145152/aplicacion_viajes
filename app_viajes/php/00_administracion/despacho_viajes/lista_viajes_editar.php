<?php
include_once "../../../funciones/funciones.php";
protegerPagina([0, 3]);

include_once '../seteos/min_diferido.php';

$viaje_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Si no hay ID, redirigir
if ($viaje_id == 0) {
    header("Location: lista_viajes.php");
    exit;
}

// Obtener datos del viaje
$conn = conexion();
$stmt = $conn->prepare("SELECT * FROM viajes_despacho WHERE id = ?");
$stmt->execute([$viaje_id]);
$viaje = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$viaje) {
    header("Location: lista_viajes.php");
    exit;
}

// Procesar edición
if (isset($_POST['editar_viaje'])) {
    $nombre_pasaj = $_POST['nombre_pasaj'];
    $cel_pasaj = $_POST['cel_pasaj'];
    $direccion_origen = $_POST['direccion_origen'];
    $direccion_destino = $_POST['direccion_destino'];
    $categoria_movil = $_POST['categoria_movil'];
    $fecha = $_POST['fecha'] ?? null;
    $hora = $_POST['hora'] ?? null;
    $obs_operador = $_POST['obs_operador'];
    $obs_pasaj = $_POST['obs_pasaj'];
    $origen_lat = $_POST['origen_lat'] ?? null;
    $origen_lng = $_POST['origen_lng'] ?? null;
    $destino_lat = $_POST['destino_lat'] ?? null;
    $destino_lng = $_POST['destino_lng'] ?? null;

    // 🔴 LÓGICA CORRECTA: 
    // Si la fecha/hora seleccionada es futura -> Diferido
    // Si es ahora o ya pasó -> Pendiente
    if ($fecha && $hora) {
        $fecha_hora_completa = $fecha . ' ' . $hora;
        $timestamp_seleccionado = strtotime($fecha_hora_completa);
        $timestamp_ahora = time();

        if ($timestamp_seleccionado > $timestamp_ahora) {
            $estado = 'Diferido';
        } else {
            $estado = 'Pendiente';
        }
    } else {
        $estado = 'Pendiente';
    }

    // Actualizar incluyendo el campo 'estado' calculado automáticamente
    $stmt = $conn->prepare("UPDATE viajes_despacho SET 
        nombre_pasaj = ?,
        cel_pasaj = ?,
        direccion_origen = ?,
        direccion_destino = ?,
        categoria_movil = ?,
        estado = ?,
        fecha = ?,
        hora = ?,
        obs_operador = ?,
        obs_pasaj = ?,
        origen_lat = ?,
        origen_lng = ?,
        destino_lat = ?,
        destino_lng = ?
        WHERE id = ?");

    $stmt->execute([
        $nombre_pasaj,
        $cel_pasaj,
        $direccion_origen,
        !empty($direccion_destino) ? $direccion_destino : null,
        $categoria_movil,
        $estado,
        $fecha,
        $hora,
        $obs_operador,
        $obs_pasaj,
        $origen_lat,
        $origen_lng,
        $destino_lat,
        $destino_lng,
        $viaje_id
    ]);

    // Cerrar el modal y recargar la página padre
    echo "<script>
        if (window.parent) {
            window.parent.postMessage('cerrar_modal', '*');
        }
    </script>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Viaje #<?= $viaje_id ?></title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #ffffff;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 700px;
            margin: 0 auto;
            padding: 0 5px 20px 5px;
        }

        h3 {
            margin-top: 0;
            color: #007bff;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
            font-size: 18px;
        }

        .form-group {
            margin: 10px 0;
        }

        .form-group label {
            display: block;
            margin-bottom: 3px;
            font-weight: bold;
            font-size: 12px;
            color: #333;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 6px 10px;
            border-radius: 4px;
            border: 1px solid #ccc;
            font-size: 13px;
            box-sizing: border-box;
            background: #fafafa;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #007bff;
            outline: none;
            background: #fff;
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.3);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 40px;
            font-family: Arial, sans-serif;
        }

        .form-row {
            display: flex;
            gap: 15px;
        }

        .form-row .form-group {
            flex: 1;
        }

        .modal-footer {
            text-align: right;
            margin-top: 15px;
            padding-top: 12px;
            border-top: 1px solid #eee;
        }

        .btn-cancelar {
            background: #6c757d;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 5px;
            font-size: 13px;
        }

        .btn-cancelar:hover {
            background: #5a6268;
        }

        .btn-guardar {
            background: #ffc107;
            color: #333;
            border: none;
            padding: 8px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            font-size: 13px;
        }

        .btn-guardar:hover {
            background: #e0a800;
        }

        @media (max-width: 600px) {
            .form-row {
                flex-direction: column;
                gap: 0;
            }

            .container {
                padding: 0 8px 15px 8px;
            }

            .modal-footer {
                text-align: center;
            }

            .btn-cancelar,
            .btn-guardar {
                width: 100%;
                margin: 3px 0;
                padding: 10px;
            }
        }
    </style>
</head>

<body>

    <div class="container">

        <form method="POST" action="">
            <input type="hidden" name="editar_viaje" value="1">

            <div class="form-row">
                <div class="form-group">
                    <label for="edit_nombre_pasaj">👤 Pasajero</label>
                    <input type="text" id="edit_nombre_pasaj" name="nombre_pasaj" value="<?= htmlspecialchars($viaje['nombre_pasaj']) ?>" required>
                </div>
                <div class="form-group">
                    <label for="edit_cel_pasaj">📱 Celular</label>
                    <input type="text" id="edit_cel_pasaj" name="cel_pasaj" value="<?= htmlspecialchars($viaje['cel_pasaj']) ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="edit_direccion_origen">📍 Origen</label>
                <input type="text" id="edit_direccion_origen" name="direccion_origen" value="<?= htmlspecialchars($viaje['direccion_origen']) ?>" required>
            </div>

            <div class="form-group">
                <label for="edit_direccion_destino">📍 Destino</label>
                <input type="text" id="edit_direccion_destino" name="direccion_destino" value="<?= htmlspecialchars($viaje['direccion_destino']) ?>">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="edit_categoria_movil">🚗 Categoría</label>
                    <select id="edit_categoria_movil" name="categoria_movil">
                        <option value="REMIS" <?= $viaje['categoria_movil'] == 'REMIS' ? 'selected' : '' ?>>REMIS</option>
                        <option value="TAXI" <?= $viaje['categoria_movil'] == 'TAXI' ? 'selected' : '' ?>>TAXI</option>
                        <option value="VAN" <?= $viaje['categoria_movil'] == 'VAN' ? 'selected' : '' ?>>VAN</option>
                    </select>
                </div>
            </div>

            <!-- Fecha y Hora siempre visibles y editables -->
            <div class="form-row">
                <div class="form-group">
                    <label for="edit_fecha">📅 Fecha</label>
                    <input type="date" id="edit_fecha" name="fecha" value="<?= $viaje['fecha'] ?? date('Y-m-d') ?>">
                </div>
                <div class="form-group">
                    <label for="edit_hora">🕐 Hora</label>
                    <input type="time" id="edit_hora" name="hora" value="<?= isset($viaje['hora']) ? substr($viaje['hora'], 0, 5) : date('H:i') ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="edit_obs_operador">📝 Observaciones Operador</label>
                <textarea id="edit_obs_operador" name="obs_operador" rows="2"><?= htmlspecialchars($viaje['obs_operador']) ?></textarea>
            </div>

            <div class="form-group">
                <label for="edit_obs_pasaj">📝 Observaciones Chofer</label>
                <textarea id="edit_obs_pasaj" name="obs_pasaj" rows="2"><?= htmlspecialchars($viaje['obs_pasaj']) ?></textarea>
            </div>

            <!-- Campos ocultos para coordenadas -->
            <input type="hidden" id="edit_origen_lat" name="origen_lat" value="<?= $viaje['origen_lat'] ?>">
            <input type="hidden" id="edit_origen_lng" name="origen_lng" value="<?= $viaje['origen_lng'] ?>">
            <input type="hidden" id="edit_destino_lat" name="destino_lat" value="<?= $viaje['destino_lat'] ?>">
            <input type="hidden" id="edit_destino_lng" name="destino_lng" value="<?= $viaje['destino_lng'] ?>">

            <div class="modal-footer">
                <button type="button" class="btn-cancelar" onclick="cerrarSinRecargar()">Cancelar</button>
                <button type="submit" class="btn-guardar">💾 Guardar Cambios</button>
            </div>
        </form>
    </div>

    <script>
        function cerrarSinRecargar() {
            if (window.parent) {
                window.parent.postMessage('cerrar_sin_recargar', '*');
            }
        }
    </script>

</body>

</html>