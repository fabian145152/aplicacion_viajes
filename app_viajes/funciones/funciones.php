<?php

// ============================================================
// CARGAR CONFIGURACIÓN DE TIEMPOS (SOLO LA CONFIGURACIÓN, NO LA INTERFAZ)
// ============================================================
$config_file = __DIR__ . '/../php/00_administracion/seteos/min_diferido_config.php';
if (file_exists($config_file)) {
    include_once $config_file;
}

// Definir valores por defecto si no existen (SOLO PARA USO INTERNO)
if (!defined('MIN_DIFERIDO')) {
    define('MIN_DIFERIDO', 60);
}
if (!defined('TIEMPO_AIR')) {
    define('TIEMPO_AIR', 30);
}

// 🔴 ESTABLECER ZONA HORARIA DE ARGENTINA PARA TODO EL SISTEMA
date_default_timezone_set('America/Argentina/Buenos_Aires');

// ... resto del código (conexion, funciones, etc.)
// ============================================================
// FUNCIÓN PARA ACTUALIZAR VIAJES DIFERIDOS A PENDIENTE
// ============================================================
function actualizarDiferidosAPendientes()
{
    $conn = conexion();

    // Usar el valor de MIN_DIFERIDO
    $min_diferido = defined('MIN_DIFERIDO') ? MIN_DIFERIDO : 60;

    // Calcular el tiempo límite (hora actual + minutos de anticipación)
    $fecha_limite = date('Y-m-d H:i:s', strtotime("+$min_diferido minutes"));

    // Actualizar viajes diferidos cuya fecha/hora está dentro del margen
    $stmt = $conn->prepare("UPDATE viajes_despacho 
                            SET estado = 'Pendiente' 
                            WHERE estado = 'Diferido' 
                            AND CONCAT(fecha, ' ', hora) <= ?");
    $stmt->execute([$fecha_limite]);

    return $stmt->rowCount();
}


// Ejecutar la actualización al cargar la página
$viajes_actualizados = actualizarDiferidosAPendientes();


function nombre_usuario()
{

    $id = $_SESSION['id_usuario'];

    $sql = "SELECT * FROM usuarios WHERE id = :id LIMIT 1";
    $pdo = conexion();
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return [
        'id' => $id,
        'nombre' => $row['nom_apellido'] ?? ''
    ];
}




function conexion()
{
    $host = 'localhost';
    $db   = 'app_viajes';
    $user = 'root';
    $pass = 'belgrado';

    try {
        $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";

        $conn = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ]);

        return $conn;
    } catch (PDOException $e) {
        echo "Error de conexión: " . $e->getMessage();
    }
}

// ================= GUARDAR RECORRIDO =================
function guardarRecorrido($id_viaje, $movil, $origen, $destino, $origen_lat, $origen_lng, $destino_lat, $destino_lng, $distancia, $tiempo)
{
    $conn = conexion();

    try {
        $sql = "INSERT INTO recorridos_viaje SET 
            id_viaje = ?,
            movil = ?,
            origen = ?,
            destino = ?,
            origen_lat = ?,
            origen_lng = ?,
            destino_lat = ?,
            destino_lng = ?,
            distancia = ?,
            tiempo = ?";

        $stmt = $conn->prepare($sql);
        return $stmt->execute([
            $id_viaje,
            $movil,
            $origen,
            $destino,
            $origen_lat,
            $origen_lng,
            $destino_lat,
            $destino_lng,
            $distancia,
            $tiempo
        ]);
    } catch (PDOException $e) {
        error_log("Error al guardar recorrido: " . $e->getMessage());
        return false;
    }
}

// ================= OBTENER RECORRIDO POR VIAJE =================
function obtenerRecorridoPorViaje($id_viaje)
{
    $conn = conexion();
    $stmt = $conn->prepare("SELECT * FROM recorridos_viaje WHERE id_viaje = ? ORDER BY fecha_registro DESC LIMIT 1");
    $stmt->execute([$id_viaje]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// ================= OBTENER RECORRIDOS POR MÓVIL =================
function obtenerRecorridosPorMovil($movil, $limite = 50)
{
    $conn = conexion();
    $stmt = $conn->prepare("SELECT * FROM recorridos_viaje WHERE movil = ? ORDER BY fecha_registro DESC LIMIT ?");
    $stmt->execute([$movil, $limite]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ================= GUARDAR VIAJE (VERSIÓN ÚNICA Y COMPLETA) =================
// ==============================================================
// GUARDAR VIAJE EN viajes_despacho
// ==============================================================
function guardarViaje($datos)
{
    $conn = conexion();

    // Verificar si estamos editando o creando
    if (isset($datos['id']) && !empty($datos['id'])) {
        // ========== ACTUALIZAR (EDITAR) ==========
        $sql = "UPDATE viajes_despacho SET 
            cel_pasaj = ?,
            nombre_pasaj = ?,
            direccion_origen = ?,
            direccion_destino = ?,
            obs_pasaj = ?,
            obs_operador = ?,
            estado = ?,
            fecha = ?,
            hora = ?,
            categoria_movil = ?,
            origen_lat = ?,
            origen_lng = ?,
            destino_lat = ?,
            destino_lng = ?,
            cc = ?,
            id_cc = ?,
            id_autorizante = ?
        WHERE id = ?";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $datos['cel_pasaj'],
            $datos['nombre_pasaj'],
            $datos['direccion_origen'],
            // 🔴 Si destino está vacío, guardamos NULL
            !empty($datos['direccion_destino']) ? $datos['direccion_destino'] : null,
            $datos['obs_pasaj'] ?? '',
            $datos['obs_operador'] ?? '',
            // 🔴 CAMBIO: Ahora el valor por defecto es 'Pendiente'
            $datos['estado'] ?? 'Pendiente',
            $datos['fecha'] ?? null,
            $datos['hora'] ?? null,
            $datos['categoria_movil'] ?? '',
            $datos['origen_lat'] ?? null,
            $datos['origen_lng'] ?? null,
            $datos['destino_lat'] ?? null,
            $datos['destino_lng'] ?? null,
            $datos['cc'] ?? 0,
            $datos['id_cc'] ?? 0,
            $datos['id_autorizante'] ?? 0,
            $datos['id']
        ]);
    } else {
        // ========== INSERTAR (NUEVO) ==========
        $sql = "INSERT INTO viajes_despacho (
            cel_pasaj, 
            nombre_pasaj, 
            direccion_origen, 
            direccion_destino, 
            obs_pasaj, 
            obs_operador, 
            estado, 
            fecha, 
            hora, 
            categoria_movil, 
            origen_lat, 
            origen_lng, 
            destino_lat, 
            destino_lng, 
            cc, 
            id_cc, 
            id_autorizante
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
        )";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $datos['cel_pasaj'],
            $datos['nombre_pasaj'],
            $datos['direccion_origen'],
            // 🔴 Si destino está vacío, guardamos NULL
            !empty($datos['direccion_destino']) ? $datos['direccion_destino'] : null,
            $datos['obs_pasaj'] ?? '',
            $datos['obs_operador'] ?? '',
            // 🔴 CAMBIO: Ahora el valor por defecto es 'Pendiente'
            $datos['estado'] ?? 'Pendiente',
            $datos['fecha'] ?? null,
            $datos['hora'] ?? null,
            $datos['categoria_movil'] ?? '',
            $datos['origen_lat'] ?? null,
            $datos['origen_lng'] ?? null,
            $datos['destino_lat'] ?? null,
            $datos['destino_lng'] ?? null,
            $datos['cc'] ?? 0,
            $datos['id_cc'] ?? 0,
            $datos['id_autorizante'] ?? 0
        ]);

        // 🔴 Devuelve el ID del viaje recién insertado (por si lo necesitas después)
        return $conn->lastInsertId();
    }
}

// ================= OTRAS FUNCIONES =================
// ... (todas las demás funciones que ya tenías, pero sin duplicar guardarViaje)

// Obtener todos los usuarios
function obtenerUsuarios()
{
    $db = conexion();
    return $db->query("SELECT * FROM usuarios ORDER BY id")->fetchAll();
}

// Obtener un usuario específico
function obtenerUsuarioPorId($id)
{
    $db = conexion();
    $stmt = $db->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function guardarUsuario($data)
{
    $db = conexion();

    $nombre       = $data['nombre'] ?? null;
    $nom_apellido = $data['nom_apellido'] ?? null;
    $telefono     = $data['telefono'] ?? null;
    $email        = $data['email'] ?? null;
    $id           = $data['id'] ?? null;

    if (!$nombre || !$nom_apellido || !$email) {
        throw new Exception("Faltan campos obligatorios");
    }

    if (!empty($id)) {
        $sql = "UPDATE usuarios 
                SET nombre=?, nom_apellido=?, telefono=?, email=? 
                WHERE id=?";

        $stmt = $db->prepare($sql);
        return $stmt->execute([$nombre, $nom_apellido, $telefono, $email, $id]);
    } else {
        $password = $data['password'] ?? null;
        $permisos = $data['permisos'] ?? 0;
        $estado   = $data['estado'] ?? 'activo';

        if (!$password) {
            throw new Exception("La contraseña es obligatoria");
        }

        $passHash = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO usuarios 
                (nombre, nom_apellido, telefono, email, password, permisos, estado) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";

        $stmt = $db->prepare($sql);
        return $stmt->execute([$nombre, $nom_apellido, $telefono, $email, $passHash, $permisos, $estado]);
    }
}

function eliminarUsuario($id)
{
    $db = conexion();
    $stmt = $db->prepare("DELETE FROM usuarios WHERE id = ?");
    return $stmt->execute([$id]);
}

function actualizarConfiguracionUsuario($data)
{
    $db = conexion();

    if (!isset($data['id'], $data['nombre'], $data['nom_apellido'], $data['email'], $data['permisos'], $data['estado'])) {
        return false;
    }

    $id        = (int)$data['id'];
    $nombre    = trim($data['nombre']);
    $nombre_completo = trim($data['nom_apellido']);
    $telefono  = trim($data['telefono'] ?? '');
    $email     = trim($data['email']);
    $permisos  = (int)$data['permisos'];
    $estado    = $data['estado'];

    if (!in_array($estado, ['activo', 'suspendido'])) {
        return false;
    }

    if ($permisos < 0 || $permisos > 3) {
        return false;
    }

    $sql = "UPDATE usuarios SET 
                nombre = ?,
                nom_apellido = ?, 
                telefono = ?, 
                email = ?, 
                permisos = ?, 
                estado = ?";

    $params = [$nombre, $nombre_completo, $telefono, $email, $permisos, $estado];

    if (isset($data['password'])) {
        $sql .= ", password = ?";
        $params[] = $data['password'];
    }

    $sql .= " WHERE id = ?";
    $params[] = $id;

    $stmt = $db->prepare($sql);
    return $stmt->execute($params);
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function verificarLogin()
{
    if (!isset($_SESSION['permiso'])) {
        header("Location: /app_viajes/login.php");
        exit;
    }
}

function protegerPagina($rolesPermitidos = [])
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['logueado'])) {
?>
        <script>
            alert("No estás logueado");
            window.location.href = "../../../index.html";
        </script>
<?php
    }

    if (!empty($rolesPermitidos)) {
        if (!isset($_SESSION['permiso']) || !in_array($_SESSION['permiso'], $rolesPermitidos)) {
            die("⛔ No tenés permisos para acceder");
        }
    }
}

function tienePermiso($rolesPermitidos)
{
    return in_array($_SESSION['permiso'], $rolesPermitidos);
}

// ================= VEHICULOS =================
function obtenerVehiculos()
{
    $pdo = conexion();
    $sql = "SELECT v.*, 
                   CONCAT(c.apellido, ' ', c.nombre) AS chofer
            FROM vehiculos v
            LEFT JOIN choferes c ON v.id_chofer = c.id
            ORDER BY v.patente ASC";
    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

function obtenerVehiculoPorId($id)
{
    $pdo = conexion();
    $stmt = $pdo->prepare("SELECT * FROM vehiculos WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function guardarVehiculo($data)
{
    $pdo = conexion();
    $id_chofer = !empty($data['id_chofer']) ? $data['id_chofer'] : null;
    $tipo = !empty($data['tipo']) ? trim($data['tipo']) : null;

    if (!empty($data['id'])) {
        $sql = "UPDATE vehiculos 
                SET categoria=?, marca=?, modelo=?, patente=?, estado=?, color=?, tipo=?, id_chofer=?
                WHERE id=?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            $data['categoria'],
            $data['marca'],
            $data['modelo'],
            $data['patente'],
            $data['estado'],
            $data['color'],
            $tipo,
            $id_chofer,
            $data['id']
        ]);
    } else {
        $sql = "INSERT INTO vehiculos 
                (categoria, marca, modelo, patente, estado, color, tipo, id_chofer)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            $data['categoria'],
            $data['marca'],
            $data['modelo'],
            $data['patente'],
            $data['estado'],
            $data['color'],
            $tipo,
            $id_chofer
        ]);
    }
}

function borrarVehiculo($id)
{
    $pdo = conexion();
    $stmt = $pdo->prepare("DELETE FROM vehiculos WHERE id=?");
    return $stmt->execute([$id]);
}

// ================= CHOFERES =================
function obtenerChoferes()
{
    $pdo = conexion();
    $sql = "SELECT c.*, v.patente, v.marca, v.modelo
            FROM choferes c
            LEFT JOIN vehiculos v ON c.id = v.id_chofer
            ORDER BY c.apellido ASC, c.nombre ASC";
    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

function obtenerChoferPorId($id)
{
    $pdo = conexion();
    $stmt = $pdo->prepare("SELECT * FROM choferes WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function obtenerChoferPorMovil($movil)
{
    $pdo = conexion();
    $stmt = $pdo->prepare("SELECT nombre, apellido FROM choferes WHERE movil = ?");
    $stmt->execute([$movil]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function guardarChofer($data)
{
    $pdo = conexion();
    $movil = (isset($data['movil']) && $data['movil'] !== '') ? $data['movil'] : null;
    $id = $data['id'] ?? null;

    if ($movil !== null) {
        if (!empty($id)) {
            $stmtCheck = $pdo->prepare("SELECT id FROM choferes WHERE movil = ? AND id <> ?");
            $stmtCheck->execute([$movil, $id]);
        } else {
            $stmtCheck = $pdo->prepare("SELECT id FROM choferes WHERE movil = ?");
            $stmtCheck->execute([$movil]);
        }

        if ($stmtCheck->fetch()) {
            return 'movil_duplicado';
        }
    }

    if (!empty($data['id'])) {
        $sql = "UPDATE choferes 
                SET nombre=?, apellido=?, cel=?, dir=?, barrio=?, cp=?, movil=?, user=?, clave=?
                WHERE id=?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            $data['nombre'],
            $data['apellido'],
            $data['cel'],
            $data['dir'],
            $data['barrio'],
            $data['cp'],
            $movil,
            $data['user'],
            $data['clave'],
            $data['id']
        ]);
    } else {
        $sql = "INSERT INTO choferes (nombre, apellido, cel, dir, barrio, cp, movil, user, clave) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            $data['nombre'],
            $data['apellido'],
            $data['cel'],
            $data['dir'],
            $data['barrio'],
            $data['cp'],
            $movil,
            $data['user'],
            $data['clave']
        ]);
    }
}

function borrarChofer($id)
{
    $pdo = conexion();
    $pdo->prepare("UPDATE vehiculos SET id_chofer = NULL WHERE id_chofer = ?")->execute([$id]);
    $stmt = $pdo->prepare("DELETE FROM choferes WHERE id=?");
    return $stmt->execute([$id]);
}

function obtenerChoferesActivos()
{
    $conn = conexion();
    $stmt = $conn->query("SELECT id, nombre, apellido, movil FROM choferes WHERE movil IS NOT NULL AND movil != '' ORDER BY movil ASC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ================= CUENTA EMPRESA =================
function obtenerEmpresas()
{
    $pdo = conexion();
    $sql = "SELECT * FROM cuenta_empresa ORDER BY razon_social ASC";
    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

function obtenerEmpresaPorId($id)
{
    $pdo = conexion();
    $stmt = $pdo->prepare("SELECT * FROM cuenta_empresa WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function guardarEmpresa($data)
{
    $pdo = conexion();
    $limpiar = function ($valor) {
        return (isset($valor) && trim($valor) !== '') ? trim($valor) : null;
    };

    $numero_cuenta = $limpiar($data['numero_cuenta'] ?? null);
    $cuit          = $limpiar($data['cuit'] ?? null);
    $inc_brutos    = $limpiar($data['inc_brutos'] ?? null);
    $cel_1         = $limpiar($data['cel_1'] ?? null);
    $contacto_1    = $limpiar($data['contacto_1'] ?? null);
    $dir           = $limpiar($data['dir'] ?? null);
    $razon_social  = $limpiar($data['razon_social'] ?? null);

    if (!empty($data['id'])) {
        $sql = "UPDATE cuenta_empresa SET
                    id_empresa = ?, razon_social = ?, dir = ?, cuit = ?,
                    inc_brutos = ?, contacto_1 = ?, cel_1 = ?
                WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$numero_cuenta, $razon_social, $dir, $cuit, $inc_brutos, $contacto_1, $cel_1, $data['id']]);
    } else {
        $sql = "INSERT INTO cuenta_empresa (id_empresa, razon_social, dir, cuit, inc_brutos, contacto_1, cel_1)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$numero_cuenta, $razon_social, $dir, $cuit, $inc_brutos, $contacto_1, $cel_1]);
    }
}

function borrarEmpresa($id)
{
    $pdo = conexion();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM autorizantes WHERE id_empresa = ?");
    $stmt->execute([$id]);
    if ($stmt->fetchColumn() > 0) {
        return "La empresa tiene autorizantes cargados. Debe eliminarlos antes de borrar la empresa.";
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM centros_costo WHERE id_empresa = ?");
    $stmt->execute([$id]);
    if ($stmt->fetchColumn() > 0) {
        return "La empresa tiene centros de costo cargados. Debe eliminarlos antes de borrar la empresa.";
    }

    $stmt = $pdo->prepare("DELETE FROM cuenta_empresa WHERE id = ?");
    $stmt->execute([$id]);
    return true;
}

// ================= CENTROS DE COSTO =================
function obtenerCentrosCosto($id_empresa)
{
    $con = conexion();
    $sql = "SELECT * FROM centros_costo WHERE id_empresa = ? ORDER BY nombre";
    $stmt = $con->prepare($sql);
    $stmt->execute([$id_empresa]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function obtenerCentrosCostoPorEmpresa($id_empresa)
{
    $pdo = conexion();
    $sql = "SELECT * FROM centros_costo WHERE id_empresa = ? ORDER BY nombre ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_empresa]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function obtenerCentroCostoPorId($id)
{
    $pdo = conexion();
    $stmt = $pdo->prepare("SELECT * FROM centros_costo WHERE id=?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function guardarCentroCosto($data)
{
    $pdo = conexion();
    $observaciones = $data['obs'] ?? '';
    $direccion = $data['direccion'] ?? '';

    if (!empty($data['id'])) {
        $sql = "UPDATE centros_costo SET nombre = ?, observaciones = ?, direccion = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$data['nombre'], $observaciones, $direccion, $data['id']]);
    } else {
        $sql_max = "SELECT COALESCE(MAX(id_centro_costo), 0) + 1 AS nuevo FROM centros_costo WHERE id_empresa = ?";
        $stmt_max = $pdo->prepare($sql_max);
        $stmt_max->execute([$data['id_empresa']]);
        $nuevo_id_cc = $stmt_max->fetchColumn();

        $sql = "INSERT INTO centros_costo (id_empresa, id_centro_costo, nombre, direccion, contacto_centro, cel, observaciones)
                VALUES (?, ?, ?, ?, '', 0, ?)";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$data['id_empresa'], $nuevo_id_cc, $data['nombre'], $direccion, $observaciones]);
    }
}

function borrarCentroCosto($id)
{
    $pdo = conexion();
    try {
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM autorizantes WHERE id_centro_costo = ?");
        $stmtCheck->execute([$id]);
        if ($stmtCheck->fetchColumn() > 0) {
            return "No se puede eliminar este Centro de Costo porque tiene autorizantes asociados.";
        }

        $stmt = $pdo->prepare("DELETE FROM centros_costo WHERE id = ?");
        $stmt->execute([$id]);
        return true;
    } catch (PDOException $e) {
        return "Error en la base de datos al eliminar: " . $e->getMessage();
    }
}

// ================= AUTORIZANTES =================
function obtenerAutorizantes()
{
    $pdo = conexion();
    $sql = "SELECT a.*, e.razon_social 
            FROM autorizantes a 
            INNER JOIN cuenta_empresa e ON a.id_empresa = e.id 
            ORDER BY e.razon_social ASC";
    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

function obtenerAutorizantesPorCC($id_cc)
{
    $pdo = conexion();
    $sql = "SELECT * FROM autorizantes WHERE id_centro_costo=? ORDER BY nombre";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_cc]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function obtenerAutorizantePorId($id)
{
    $pdo = conexion();
    $stmt = $pdo->prepare("SELECT * FROM autorizantes WHERE id=?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function guardarAutorizante($data)
{
    $pdo = conexion();

    if (!empty($data['id'])) {
        $sql = "UPDATE autorizantes SET nombre=?, celular=?, email=?, horario=? WHERE id=?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$data['nombre'], $data['celular'], $data['email'], $data['horario'], $data['id']]);
    } else {
        $sql = "INSERT INTO autorizantes (id_empresa, id_centro_costo, nombre, celular, email, horario, estado)
                VALUES (?, ?, ?, ?, ?, ?, 1)";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            $data['id_empresa'],
            $data['id_cc'],
            $data['nombre'],
            $data['celular'],
            $data['email'],
            $data['horario']
        ]);
    }
}

function borrarAutorizante($id)
{
    $pdo = conexion();
    $stmt = $pdo->prepare("DELETE FROM autorizantes WHERE id=?");
    return $stmt->execute([$id]);
}

// ================= VIAJES =================
function obtenerViajes($filtro = 'todos')
{
    $conn = conexion();

    $sql = "SELECT * FROM viajes_despacho WHERE 1=1";

    switch ($filtro) {
        case 'asignado':
            $sql .= " AND estado = 'Asignado'";
            break;
        case 'pendiente':
            $sql .= " AND estado = 'Pendiente'";
            break;
        case 'diferidos':
            $sql .= " AND estado = 'Diferido'";
            break;
        case 'en_curso':
            $sql .= " AND estado = 'En Curso'";
            break;
        case 'completos':
            $sql .= " AND estado = 'Completo'";
            break;
        case 'cancelados':
            $sql .= " AND estado = 'Cancelado'";
            break;
        case 'todos':

        default:
            // 🔴 EXCLUIR completos y cancelados
            $sql .= " AND estado NOT IN ('Completo', 'Cancelado')";
            break;
    }

    $sql .= " ORDER BY fecha DESC, hora DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function obtenerViajePorId($id)
{
    global $db;
    $stmt = $db->prepare("SELECT * FROM viajes_despacho WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function borrarViaje($id)
{
    global $db;
    $stmt = $db->prepare("DELETE FROM viajes_despacho WHERE id = ?");
    $stmt->execute([$id]);
}

function obtenerCoordenadas($direccion)
{
    if (empty($direccion)) {
        return null;
    }

    $direccion .= ", Buenos Aires, Argentina";
    $url = "https://nominatim.openstreetmap.org/search?format=json&q=" . urlencode($direccion) . "&limit=1";

    $opts = ['http' => ['header' => "User-Agent: AppViajes/1.0\r\n"]];
    $json = @file_get_contents($url, false, stream_context_create($opts));

    if (!$json) return null;
    $resultado = json_decode($json, true);
    if (empty($resultado)) return null;

    return ['lat' => $resultado[0]['lat'], 'lng' => $resultado[0]['lon']];
}
