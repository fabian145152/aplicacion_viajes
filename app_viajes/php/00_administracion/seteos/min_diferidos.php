<?php
// 1. Primero cargamos los minutos reales actuales si el archivo ya existe
if (file_exists("min_diferido.php")) {
    include_once "min_diferido.php";
}

// 2. Cargamos el procesador por si el usuario le dio a algún botón "Guardar"
include_once "minutos.php";
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración Diferido</title>
    <link rel="stylesheet" href="min_diferidos.css">

    <script>
        function confirmarDiferido() {
            const minutos = document.getElementById('nuevo_min_diferido').value;
            return confirm(`⚠️ ¿Estás seguro de modificar el límite de viajes diferidos a ${minutos} minutos?`);
        }

        function confirmarAire() {
            const minutos = document.getElementById('nuevo_tiempo_aire').value;
            return confirm(`⚠️ ¿Estás seguro de modificar la alerta de tiempo en el aire a los ${minutos} minutos?`);
        }
    </script>
</head>

<body>
    <div class="main-wrapper">
        <h1>AJUSTES DE TEMPORIZADORES</h1>

        <div class="columnas-container">

            <div class="ajustes-card">
                <h3>⏱ Viajes Diferidos</h3>
                <p class="descripcion">Margen de tiempo requerido para clasificar un viaje entrante en la lista diferida.</p>

                <form method="POST" action="" onsubmit="return confirmarDiferido()">
                    <div class="input-row">
                        <label for="nuevo_min_diferido">Ajuste Diferidos:</label>
                        <div class="input-group">
                            <input type="number" id="nuevo_min_diferido" name="nuevo_min_diferido"
                                value="<?= defined('MIN_DIFERIDO') ? MIN_DIFERIDO : 60 ?>"
                                min="1" max="1440" required>
                            <span class="min-txt">min.</span>
                        </div>
                    </div>
                    <button type="submit" name="guardar_diferido" class="btn-actualizar-config btn-diferido">Guardar Diferidos</button>
                </form>
            </div>

        </div>

        <div class="footer-actions">
            <a href="inicio.php" class="btn-salir">Salir</a>
        </div>
    </div>
</body>

</html>