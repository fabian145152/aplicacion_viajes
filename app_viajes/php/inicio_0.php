<?php
include_once "../funciones/funciones.php";

protegerPagina([0]); // ya valida todo

$id = $_SESSION['id_usuario'];

$sql = "SELECT * FROM usuarios WHERE id = :id LIMIT 1";
$pdo = conexion();
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);

$nombre_completo = $row['nom_apellido'] ?? '';
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ADMINISTRACION</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* ============================================================
                   RESET Y BASE
                   ============================================================ */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f2f5;
            min-height: 100vh;
            padding: 20px;
        }

        .dashboard {
            max-width: 1400px;
            margin: 0 auto;
        }

        /* ============================================================
                   HEADER
                   ============================================================ */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e9ecef;
        }

        .header .bienvenido {
            font-size: 16px;
            color: #6c757d;
        }

        .header .bienvenido strong {
            color: #007bff;
        }

        .header .fecha-hora {
            font-size: 14px;
            color: #6c757d;
            background: #e9ecef;
            padding: 6px 14px;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
        }

        /* ============================================================
                   CONTENEDOR DE COLUMNAS (GRID)
                   ============================================================ */
        .columnas {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        /* ============================================================
                   CADA COLUMNA (GRUPO)
                   ============================================================ */
        .columna {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            padding: 16px 14px;
            display: flex;
            flex-direction: column;
            gap: 4px;
            border: 1px solid #e9ecef;
            transition: all 0.2s ease;
        }

        .columna:hover {
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        .columna .categoria {
            font-weight: 700;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #495057;
            padding-bottom: 8px;
            border-bottom: 2px solid #e9ecef;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .columna .categoria i {
            color: #007bff;
            font-size: 15px;
            width: 20px;
            text-align: center;
        }

        /* ============================================================
                   TARJETA DENTRO DE COLUMNA
                   ============================================================ */
        .tarjeta {
            background: transparent;
            border-radius: 8px;
            padding: 10px 12px;
            text-decoration: none;
            color: #1a1a2e;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.2s ease;
            border: 1px solid transparent;
            font-size: 13px;
            font-weight: 500;
            position: relative;
        }

        .tarjeta:hover {
            background: #f8fbff;
            border-color: #007bff;
            transform: translateX(4px);
            box-shadow: 0 2px 8px rgba(0, 123, 255, 0.1);
        }

        .tarjeta i {
            font-size: 18px;
            color: #007bff;
            width: 24px;
            text-align: center;
            flex-shrink: 0;
        }

        .tarjeta .titulo {
            font-size: 13px;
            line-height: 1.3;
        }

        .tarjeta .link-externo {
            font-size: 9px;
            color: #adb5bd;
            margin-left: auto;
            white-space: nowrap;
            transition: color 0.2s;
        }

        .tarjeta:hover .link-externo {
            color: #007bff;
        }

        /* ============================================================
                   TARJETA SALIR (especial)
                   ============================================================ */
        .tarjeta.salir {
            background: #fff5f5;
            border-color: #fde8ea;
            margin-top: 4px;
        }

        .tarjeta.salir i {
            color: #dc3545;
        }

        .tarjeta.salir:hover {
            background: #fff0f0;
            border-color: #dc3545;
            transform: translateX(4px);
        }

        /* ============================================================
                   BADGE DE NUEVO
                   ============================================================ */
        .badge-nuevo {
            background: #28a745;
            color: white;
            font-size: 8px;
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 8px;
            margin-left: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* ============================================================
                   RESPONSIVE
                   ============================================================ */
        @media (max-width: 992px) {
            .columnas {
                grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
                gap: 15px;
            }

            .tarjeta {
                font-size: 12px;
                padding: 8px 10px;
            }

            .tarjeta i {
                font-size: 16px;
                width: 20px;
            }
        }

        @media (max-width: 768px) {
            body {
                padding: 15px;
            }

            .columnas {
                grid-template-columns: 1fr 1fr;
                gap: 12px;
            }

            .columna {
                padding: 12px;
            }

            .header {
                flex-direction: column;
                align-items: flex-start;
            }

            .header .fecha-hora {
                font-size: 12px;
                padding: 4px 10px;
            }

            .tarjeta {
                font-size: 11px;
                padding: 7px 8px;
                gap: 8px;
            }

            .tarjeta i {
                font-size: 14px;
                width: 18px;
            }

            .tarjeta .link-externo {
                display: none;
            }

            .columna .categoria {
                font-size: 11px;
            }

            .columna .categoria i {
                font-size: 13px;
                width: 16px;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 10px;
            }

            .columnas {
                grid-template-columns: 1fr;
                gap: 10px;
            }

            .columna {
                padding: 10px 12px;
            }

            .tarjeta {
                font-size: 12px;
                padding: 8px 10px;
            }

            .tarjeta i {
                font-size: 16px;
                width: 20px;
            }

            .header .bienvenido {
                font-size: 14px;
            }
        }
    </style>
</head>

<body>

    <div class="dashboard">

        <!-- ===== HEADER ===== -->
        <div class="header">
            <span class="bienvenido">👋 Bienvenido, <strong><?php echo $nombre_completo; ?></strong></span>
            <span class="fecha-hora" id="fecha-hora"></span>
        </div>

        <!-- ===== COLUMNAS ===== -->
        <div class="columnas">

            <!-- ===== COLUMNA: ADMINISTRACIÓN ===== -->
            <div class="columna">
                <div class="categoria"><i class="fas fa-cog"></i> Administración</div>

                <a href="00_administracion/operadores/listado.php" class="tarjeta">
                    <i class="fas fa-users-cog"></i>
                    <span class="titulo">OPERADORES</span>
                </a>

                <a href="00_administracion/seteos/min_aire.php" class="tarjeta">
                    <i class="fas fa-clock"></i>
                    <span class="titulo">AJUSTE DIFERIDOS</span>
                </a>

                <a href="../php/contaduria/lista_viajes_completa.php" class="tarjeta">
                    <i class="fas fa-file-invoice"></i>
                    <span class="titulo">DETALLE DE VIAJES</span>
                </a>

                <a href="00_administracion/cuentas_empresas/listado_empresas.php" class="tarjeta">
                    <i class="fas fa-building"></i>
                    <span class="titulo">CREAR CUENTAS CORRIENTES</span>
                </a>

                <a href="00_administracion/auditoria/listado_auditoria.php" class="tarjeta">
                    <i class="fas fa-clipboard-list"></i>
                    <span class="titulo">AUDITORÍA <span class="badge-nuevo">Nuevo</span></span>
                </a>

                <a href="00_administracion/menu_admin.php" class="tarjeta">
                    <i class="fas fa-tools"></i>
                    <span class="titulo">ADMINISTRACIÓN</span>
                </a>
            </div>

            <!-- ===== COLUMNA: FLOTA ===== -->
            <div class="columna">
                <div class="categoria"><i class="fas fa-truck"></i> Flota</div>

                <a href="00_administracion/num_mov/lista_de_numeros.php" class="tarjeta">
                    <i class="fas fa-list"></i>
                    <span class="titulo">LISTADO DE MÓVILES</span>
                </a>

                <a href="00_administracion/trafico/listado.php" class="tarjeta">
                    <i class="fas fa-car-side"></i>
                    <span class="titulo">UNIDADES (TRÁFICO)</span>
                </a>

                <a href="00_administracion/choferes/listado_choferes.php" class="tarjeta">
                    <i class="fas fa-user-tie"></i>
                    <span class="titulo">CHOFERES</span>
                </a>

            </div>

            <!-- ===== COLUMNA: DESPACHO ===== -->
            <div class="columna">
                <div class="categoria"><i class="fas fa-clipboard-list"></i> Despacho</div>

                <a href="00_administracion/despacho_viajes/carga_viajes.php" target="_blank" class="tarjeta">
                    <i class="fas fa-plus-circle"></i>
                    <span class="titulo">NUEVOS VIAJES</span>
                    <span class="link-externo">🔗</span>
                </a>

                <a href="00_administracion/despacho_viajes/lista_viajes.php" target="_blank" class="tarjeta">
                    <i class="fas fa-table"></i>
                    <span class="titulo">LISTADO DE VIAJES</span>
                    <span class="link-externo">🔗</span>
                </a>
            </div>

            <!-- ===== COLUMNA: MONITOREO ===== -->
            <div class="columna">
                <div class="categoria"><i class="fas fa-map"></i> Monitoreo</div>

                <a href="01_mapeo/mapa_de_viajes.php" target="_blank" class="tarjeta">
                    <i class="fas fa-map"></i>
                    <span class="titulo">MAPA DE VIAJES</span>
                    <span class="link-externo">🔗</span>
                </a>

                <a href="01_mapeo/ubicaciones_actuales.php" target="_blank" class="tarjeta">
                    <i class="fas fa-location-dot"></i>
                    <span class="titulo">UBICACIONES ACTUALES</span>
                    <span class="link-externo">🔗</span>
                </a>

                <a href="00_administracion/despacho_viajes/ver_recorrido_mapa.php" target="_blank" class="tarjeta">
                    <i class="fas fa-route"></i>
                    <span class="titulo">RECORRIDOS</span>
                    <span class="link-externo">🔗</span>
                </a>
            </div>

            <!-- ===== COLUMNA: UTILIDADES ===== -->
            <div class="columna">
                <div class="categoria"><i class="fas fa-toolbox"></i> Utilidades</div>

                <a href="../backup.php" target="_blank" class="tarjeta">
                    <i class="fas fa-database"></i>
                    <span class="titulo">BACKUP</span>
                    <span class="link-externo">🔗</span>
                </a>

                <a href="01_mapeo/recibir.php" target="_blank" class="tarjeta">
                    <i class="fas fa-arrow-down"></i>
                    <span class="titulo">RECIBIR</span>
                    <span class="link-externo">🔗</span>
                </a>
            </div>

            <!-- ===== COLUMNA: SALIR ===== -->
            <div class="columna">
                <div class="categoria"><i class="fas fa-sign-out-alt"></i> Salir</div>

                <a href="logout.php" class="tarjeta salir">
                    <i class="fas fa-sign-out-alt"></i>
                    <span class="titulo">SALIR</span>
                </a>
            </div>

        </div>

    </div>

    <!-- ============================================================
    RELOJ DIGITAL
    ============================================================ -->
    <script>
        function actualizarReloj() {
            const ahora = new Date();
            const opciones = {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            };
            const fechaHora = ahora.toLocaleString('es-AR', opciones);
            const elemento = document.getElementById('fecha-hora');
            if (elemento) {
                elemento.textContent = '🕒 ' + fechaHora;
            }
        }

        actualizarReloj();
        setInterval(actualizarReloj, 1000);
    </script>

</body>

</html>