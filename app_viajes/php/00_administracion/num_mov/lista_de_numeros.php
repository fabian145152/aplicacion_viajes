<?php
include_once "../../../funciones/funciones.php";

// Protección de página para roles autorizados
protegerPagina([0, 3]);

// Obtenemos todos los choferes (que ya vienen con los datos del vehículo)
$todosLosChoferes = obtenerChoferes();

// Filtramos para quedarnos únicamente con los que tienen un número de móvil asignado
$moviles = array_filter($todosLosChoferes, function ($c) {
    return !empty($c['movil']);
});

// Filtro por número de móvil EXACTO y COMPLETO
$filtroMovil = trim($_GET['movil_buscar'] ?? '');
if ($filtroMovil !== '') {
    $moviles = array_filter($moviles, function ($c) use ($filtroMovil) {
        // 🔥 Coincidencia exacta: se limpia de espacios y se compara directamente
        return trim((string)($c['movil'] ?? '')) === $filtroMovil;
    });
}

// Ordenamos la lista por número de móvil de forma ascendente
usort($moviles, function ($a, $b) {
    return (int)$a['movil'] <=> (int)$b['movil'];
});
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Lista de Móviles</title>
    <link rel="stylesheet" href="../../../css/estilos.css">
</head>

<body>

    <div class="container">
        <h2 class="text-center">Listado de Numeros de Móvil Activos</h2>

        <div class="card" style="max-width: 900px; margin: 0 auto;">

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
                <form method="GET" class="filtros" style="display:flex; flex-direction:row; gap:6px; align-items:center; margin: 0; flex-wrap:nowrap;">
                    <input type="text" name="movil_buscar" placeholder="Número de móvil..."
                        style="padding:6px 10px; font-size:13px; width:150px;"
                        value="<?php echo htmlspecialchars($filtroMovil); ?>">

                    <button type="submit" class="btn btn-success" style="padding:6px 12px; font-size:13px;">Buscar</button>
                    <a href="lista_de_numeros.php" class="btn btn-gray" style="padding:6px 12px; font-size:13px;">Limpiar</a>
                </form>

                <a href="../../inicio_0.php" class="btn btn-danger" style="padding:6px 15px; font-size:13px;">Volver al Inicio</a>
            </div>

            <div class="col-tabla">
                <table class="table">
                    <thead>
                        <tr>
                            <th style="width: 15%; text-align: center;">N° Móvil</th>
                            <th style="width: 35%;">Chofer</th>
                            <th style="width: 20%;">Celular</th>
                            <th style="width: 30%;">Vehículo Asignado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($moviles) > 0): ?>
                            <?php foreach ($moviles as $m): ?>
                                <tr>
                                    <td style="text-align: center;">
                                        <strong style="font-size: 14px; color: var(--primary);">
                                            <?php echo htmlspecialchars($m['movil']); ?>
                                        </strong>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($m['apellido'] . ', ' . $m['nombre']); ?></strong>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($m['cel'] ?? '-'); ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($m['marca'])): ?>
                                            <span><?php echo htmlspecialchars($m['marca'] . ' ' . $m['modelo'] . ' (' . $m['patente'] . ')'); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted" style="font-style: italic;">Sin unidad asignada</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center" style="padding: 20px;">
                                    No se encontró el móvil ingresado.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>

</body>

</html>