<?php
$host = "localhost";
$dbname = "app_viajes";
$user = "root";
$password = "belgrado";
try {
    // Aquí es donde nace $db
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $password);

    // Configuración para que PHP nos avise si hay errores de SQL
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Error de conexión: " . $e->getMessage();
    exit;
}

function conexion()
{
    $host = 'localhost';
    $db   = 'app_viajes';
    $user = 'root';          // Usuario por defecto en XAMPP/phpMyAdmin
    $pass = 'belgrado';              // Contraseña por defecto en XAMPP suele estar vacía
    // El puerto por defecto de MySQL es 3306, no es estrictamente necesario ponerlo si es el estándar

    try {
        // Cambiamos "pgsql" por "mysql"
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

    // Normalización segura
    $nombre       = $data['nombre'] ?? null;
    $nom_apellido = $data['nom_apellido'] ?? null;
    $telefono     = $data['telefono'] ?? null;
    $email        = $data['email'] ?? null;
    $id           = $data['id'] ?? null;

    // Validación mínima obligatoria
    if (!$nombre || !$nom_apellido || !$email) {
        throw new Exception("Faltan campos obligatorios");
    }

    if (!empty($id)) {
        // UPDATE
        $sql = "UPDATE usuarios 
                SET nombre=?, nom_apellido=?, telefono=?, email=? 
                WHERE id=?";

        $stmt = $db->prepare($sql);

        return $stmt->execute([
            $nombre,
            $nom_apellido,
            $telefono,
            $email,
            $id
        ]);
    } else {
        // INSERT

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

        return $stmt->execute([
            $nombre,
            $nom_apellido,
            $telefono,
            $email,
            $passHash,
            $permisos,
            $estado
        ]);
    }
}
// Eliminar
function eliminarUsuario($id)
{
    $db = conexion();
    $stmt = $db->prepare("DELETE FROM usuarios WHERE id = ?");
    return $stmt->execute([$id]);
}

function actualizarConfiguracionUsuario($data)
{
    $db = conexion();

    // 🔒 Validaciones mínimas
    if (
        !isset(
            $data['id'],
            $data['nombre'],
            $data['nom_apellido'],
            $data['email'],
            $data['permisos'],
            $data['estado']
        )
    ) {
        return false;
    }

    $id        = (int)$data['id'];
    $nombre    = trim($data['nombre']);
    $nombre_completo = trim($data['nom_apellido']);
    $telefono  = trim($data['telefono'] ?? '');
    $email     = trim($data['email']);
    $permisos  = (int)$data['permisos'];
    $estado    = $data['estado'];

    // Validar valores permitidos
    if (!in_array($estado, ['activo', 'suspendido'])) {
        return false;
    }

    if ($permisos < 0 || $permisos > 3) {
        return false;
    }

    // --- BASE DEL UPDATE ---
    $sql = "UPDATE usuarios SET 
                nombre = ?,
                nom_apellido = ?, 
                telefono = ?, 
                email = ?, 
                permisos = ?, 
                estado = ?";

    $params = [
        $nombre,
        $nombre_completo,
        $telefono,
        $email,
        $permisos,
        $estado
    ];

    // 🔒 PASSWORD (solo si viene)
    if (isset($data['password'])) {
        $sql .= ", password = ?";
        $params[] = $data['password']; // ya viene hasheado
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
        header("Location: /app_viajes/login.php"); // 👈 CAMBIAR ACA
        exit;
    }
}

/* ============================= */
/* PROTEGER PÁGINAS POR PERMISOS */
/* ============================= */
function protegerPagina($rolesPermitidos = [])
{

    // iniciar sesión si no está iniciada
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // 🔐 validar login
    if (!isset($_SESSION['logueado'])) {
        //die("No estás logueado");
?>

        <script>
            alert("No estás logueado");
            window.location.href = "../../../index.html"; // 👈 CAMBIAR ACA
        </script>
<?php
    }

    // 🔐 validar permisos
    if (!empty($rolesPermitidos)) {
        if (!isset($_SESSION['permiso']) || !in_array($_SESSION['permiso'], $rolesPermitidos)) {
            die("⛔ No tenés permisos para acceder");
        }
    }
}

/* ============================= */
/* VALIDAR PERMISOS */
/* ============================= */
function tienePermiso($rolesPermitidos)
{
    return in_array($_SESSION['permiso'], $rolesPermitidos);
}



/* ========================================================================== */
/* CRUD UNIDADES
/* ========================================================================== */

/* ============================= */
/* VEHICULOS */
/* ============================= */

function obtenerVehiculos()
{
    $pdo = conexion();

    $sql = "SELECT v.*, 
                   CONCAT(c.apellido, ' ', c.nombre) AS chofer
            FROM vehiculos v
            LEFT JOIN choferes c ON v.id_chofer = c.id
            ORDER BY v.id DESC";

    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

function obtenerVehiculoPorId($id)
{
    $pdo = conexion();

    $sql = "SELECT * FROM vehiculos WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);

    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function guardarVehiculo($data)
{
    $pdo = conexion();

    // 🔥 CLAVE: permitir NULL si no hay chofer
    $id_chofer = !empty($data['id_chofer']) ? $data['id_chofer'] : null;

    if (!empty($data['id'])) {
        // UPDATE
        $sql = "UPDATE vehiculos 
                SET categoria=?, marca=?, modelo=?, patente=?, estado=?, color=?, id_chofer=? 
                WHERE id=?";
        $stmt = $pdo->prepare($sql);

        return $stmt->execute([
            $data['categoria'],
            $data['marca'],
            $data['modelo'],
            $data['patente'],
            $data['estado'],
            $data['color'],
            $id_chofer,
            $data['id']
        ]);
    } else {
        // INSERT
        $sql = "INSERT INTO vehiculos 
                (categoria, marca, modelo, patente, estado, color, id_chofer) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);

        return $stmt->execute([
            $data['categoria'],
            $data['marca'],
            $data['modelo'],
            $data['patente'],
            $data['estado'],
            $data['color'],
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

/* ============================= */
/* CHOFERES */
/* ============================= */

function obtenerChoferes()
{
    $pdo = conexion();

    $sql = "SELECT c.*, 
                   v.patente,
                   v.marca,
                   v.modelo
            FROM choferes c
            LEFT JOIN vehiculos v ON c.id = v.id_chofer
            ORDER BY c.apellido ASC, c.nombre ASC";

    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}
function obtenerChoferPorId($id)
{
    $pdo = conexion();

    $sql = "SELECT * FROM choferes WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);

    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function guardarChofer($data)
{
    $pdo = conexion();

    if (!empty($data['id'])) {
        // UPDATE
        $sql = "UPDATE choferes 
                SET nombre=?, apellido=?, cel=?, dir=?, barrio=?, cp=?
                WHERE id=?";
        $stmt = $pdo->prepare($sql);

        return $stmt->execute([
            $data['nombre'],
            $data['apellido'],
            $data['cel'],
            $data['dir'],
            $data['barrio'],
            $data['cp'],
            $data['id']
        ]);
    } else {
        // INSERT
        $sql = "INSERT INTO choferes (nombre, apellido, cel, dir, barrio, cp) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);

        return $stmt->execute([
            $data['nombre'],
            $data['apellido'],
            $data['cel'],
            $data['dir'],
            $data['barrio'],
            $data['cp']
        ]);
    }
}

function borrarChofer($id)
{
    $pdo = conexion();

    // 🔥 IMPORTANTE: liberar vehículos antes de borrar
    $pdo->prepare("UPDATE vehiculos SET id_chofer = NULL WHERE id_chofer = ?")
        ->execute([$id]);

    $stmt = $pdo->prepare("DELETE FROM choferes WHERE id=?");
    return $stmt->execute([$id]);
}

/* ============================= */
/* CUENTA EMPRESA */
/* ============================= */

function obtenerEmpresas()
{
    $pdo = conexion();

    $sql = "SELECT * FROM cuenta_empresa ORDER BY razon_social ASC";
    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

function obtenerEmpresaPorId($id)
{
    $pdo = conexion();

    $sql = "SELECT * FROM cuenta_empresa WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);

    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function guardarEmpresa($data)
{
    $pdo = conexion();

    $limpiar = function ($valor) {
        return (isset($valor) && trim($valor) !== '')
            ? trim($valor)
            : null;
    };

    $numero_cuenta = $limpiar($data['numero_cuenta'] ?? null);
    $cuit          = $limpiar($data['cuit'] ?? null);
    $inc_brutos    = $limpiar($data['inc_brutos'] ?? null);
    $cel_1         = $limpiar($data['cel_1'] ?? null);
    $contacto_1    = $limpiar($data['contacto_1'] ?? null);
    $dir           = $limpiar($data['dir'] ?? null);
    $razon_social  = $limpiar($data['razon_social'] ?? null);

    if (!empty($data['id'])) {

        // UPDATE
        $sql = "UPDATE cuenta_empresa SET
                    id_empresa = ?,
                    razon_social = ?,
                    dir = ?,
                    cuit = ?,
                    inc_brutos = ?,
                    contacto_1 = ?,
                    cel_1 = ?
                WHERE id = ?";

        $stmt = $pdo->prepare($sql);

        return $stmt->execute([
            $numero_cuenta,
            $razon_social,
            $dir,
            $cuit,
            $inc_brutos,
            $contacto_1,
            $cel_1,
            $data['id']
        ]);
    } else {

        // INSERT
        $sql = "INSERT INTO cuenta_empresa
                (
                    id_empresa,
                    razon_social,
                    dir,
                    cuit,
                    inc_brutos,
                    contacto_1,
                    cel_1
                )
                VALUES (?, ?, ?, ?, ?, ?, ?)";

        $stmt = $pdo->prepare($sql);

        return $stmt->execute([
            $numero_cuenta,
            $razon_social,
            $dir,
            $cuit,
            $inc_brutos,
            $contacto_1,
            $cel_1
        ]);
    }
}
/*
function borrarEmpresa($id)
{
    $pdo = conexion();

    $stmt = $pdo->prepare("DELETE FROM cuenta_empresa WHERE id=?");
    return $stmt->execute([$id]);
}
*/

//---------------------------------------------------------------
//---------------------------------------------------------------


function borrarEmpresa($id)
{
    $pdo = conexion();

    // Verificar autorizantes
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM autorizantes
        WHERE id_empresa = ?
    ");
    $stmt->execute([$id]);

    if ($stmt->fetchColumn() > 0) {
        return "La empresa tiene autorizantes cargados. Debe eliminarlos antes de borrar la empresa.";
    }

    // Verificar centros de costo
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM centros_costo
        WHERE id_empresa = ?
    ");
    $stmt->execute([$id]);

    if ($stmt->fetchColumn() > 0) {
        return "La empresa tiene centros de costo cargados. Debe eliminarlos antes de borrar la empresa.";
    }

    // Borrar empresa
    $stmt = $pdo->prepare("
        DELETE FROM cuenta_empresa
        WHERE id = ?
    ");

    $stmt->execute([$id]);

    return true;
}



//---------------------------------------------------------------
//---------------------------------------------------------------

function borrarCentroCosto($id)
{
    $pdo = conexion();

    try {
        // 1. Validamos si el centro de costo tiene autorizantes asignados
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM autorizantes WHERE id = ?");
        $stmtCheck->execute([$id]);

        if ($stmtCheck->fetchColumn() > 0) {
            return "No se puede eliminar este Centro de Costo porque tiene autorizantes asociados. Elimina primero sus autorizantes.";
        }

        // 2. Si está limpio, procedemos a borrarlo
        $stmt = $pdo->prepare("DELETE FROM centros_costo WHERE id = ?");
        $stmt->execute([$id]);

        return true;
    } catch (PDOException $e) {
        return "Error en la base de datos al eliminar: " . $e->getMessage();
    }
}

function obtenerCentrosCosto($id_empresa)
{
    $con = conexion();

    $sql = "SELECT *
            FROM centro_costo
            WHERE id_empresa = ?
            ORDER BY centro_de_costo";

    $stmt = $con->prepare($sql);
    $stmt->execute([$id_empresa]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


/* ============================= */
/* AUTORIZANTES */
/* ============================= */

function obtenerAutorizantes()
{
    $pdo = conexion();

    $sql = "SELECT a.*, e.razon_social 
    FROM autorizantes a 
    INNER JOIN cuenta_empresa e ON a.id_empresa = e.id 
    ORDER BY e.razon_social ASC";

    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}





function obtenerViajes()
{
    global $db;

    $sql = "SELECT
                vd.*,
                ce.razon_social AS empresa
            FROM viajes_despacho vd
            LEFT JOIN cuenta_empresa ce
                ON vd.cc = ce.id
            ORDER BY
                CASE WHEN vd.diferido = 'No' THEN 0 ELSE 1 END,
                CASE WHEN vd.diferido = 'No' THEN vd.id END ASC,
                vd.fecha ASC,
                vd.hora ASC";

    return $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

// obtenerViajePorId: Para cargar los datos al editar
function obtenerViajePorId($id)
{
    global $db;
    $stmt = $db->prepare("SELECT * FROM viajes_despacho WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// guardarViaje: Inserta uno nuevo o actualiza uno existente

function obtenerCoordenadas($direccion)
{
    if (empty($direccion)) {
        return null;
    }

    $direccion .= ", Buenos Aires, Argentina";

    $url = "https://nominatim.openstreetmap.org/search?format=json&q=" .
        urlencode($direccion) . "&limit=1";

    $opts = [
        'http' => [
            'header' => "User-Agent: AppViajes/1.0\r\n"
        ]
    ];

    $json = @file_get_contents(
        $url,
        false,
        stream_context_create($opts)
    );

    if (!$json) {
        return null;
    }

    $resultado = json_decode($json, true);

    if (empty($resultado)) {
        return null;
    }

    return [
        'lat' => $resultado[0]['lat'],
        'lng' => $resultado[0]['lon']
    ];
}



function guardarViaje($datos)
{
    global $db;

    // Obtener coordenadas automáticamente
    $origen = obtenerCoordenadas($datos['direccion_origen']);
    $destino = obtenerCoordenadas($datos['direccion_destino']);

    $origen_lat = $origen['lat'] ?? null;
    $origen_lng = $origen['lng'] ?? null;

    $destino_lat = $destino['lat'] ?? null;
    $destino_lng = $destino['lng'] ?? null;

    // 🌟 Validar centro_de_costo: si no viene o viene vacío, le asignamos 0 por defecto
    $centro_de_costo = !empty($datos['centro_de_costo']) ? (int)$datos['centro_de_costo'] : 0;

    if (!empty($datos['id'])) {

        // ACTUALIZAR
        $sql = "UPDATE viajes_despacho SET
                    cel_pasaj=?,
                    nombre_pasaj=?,
                    direccion_origen=?,
                    direccion_destino=?,
                    origen_lat=?,
                    origen_lng=?,
                    destino_lat=?,
                    destino_lng=?,
                    obs_operador=?,
                    obs_pasaj=?,
                    estado=?,                    
                    fecha=?,
                    hora=?,
                    categoria_movil=?,
                    cc=?,
                    centro_de_costo=? -- 👈 Agregado aquí
                WHERE id=?";

        $stmt = $db->prepare($sql);

        $stmt->execute([
            $datos['cel_pasaj'],
            $datos['nombre_pasaj'],
            $datos['direccion_origen'],
            $datos['direccion_destino'],
            $origen_lat,
            $origen_lng,
            $destino_lat,
            $destino_lng,
            $datos['obs_operador'],
            $datos['obs_pasaj'],
            $datos['estado'],
            $datos['fecha'],
            $datos['hora'],
            $datos['categoria_movil'],
            $datos['cc'],
            $centro_de_costo, // 👈 Agregado aquí
            $datos['id']
        ]);
    } else {

        // INSERTAR NUEVO
        $sql = "INSERT INTO viajes_despacho
                (   cel_pasaj,
                    nombre_pasaj,
                    direccion_origen,
                    direccion_destino,
                    origen_lat,
                    origen_lng,
                    destino_lat,
                    destino_lng,
                    obs_operador,
                    obs_pasaj,
                    estado,
                    fecha,
                    hora,
                    categoria_movil,
                    cc,
                    centro_de_costo) -- 👈 Agregado aquí
                VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"; // 👈 Agregado un '?' extra (ahora son 16)

        $stmt = $db->prepare($sql);

        $stmt->execute([
            $datos['cel_pasaj'],
            $datos['nombre_pasaj'],
            $datos['direccion_origen'],
            $datos['direccion_destino'],
            $origen_lat,
            $origen_lng,
            $destino_lat,
            $destino_lng,
            $datos['obs_operador'],
            $datos['obs_pasaj'],
            $datos['estado'],
            $datos['fecha'],
            $datos['hora'],
            $datos['categoria_movil'],
            $datos['cc'],
            $centro_de_costo // 👈 Agregado aquí
        ]);
    }
}



// borrarViaje: Elimina un registro
function borrarViaje($id)
{
    global $db;
    $stmt = $db->prepare("DELETE FROM viajes_despacho WHERE id = ?");
    $stmt->execute([$id]);
}

function obtenerCentrosCostoPorEmpresa($id_empresa)
{
    $pdo = conexion();

    // CORRECCIÓN: Cambiar cc.centro_de_costo por nombre o cc.nombre
    $sql = "SELECT * FROM centros_costo WHERE id_empresa = ? ORDER BY nombre ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_empresa]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function obtenerCentroCostoPorId($id)
{
    $pdo = conexion();

    $stmt = $pdo->prepare("
        SELECT *
        FROM centros_costo
        WHERE id=?
    ");

    $stmt->execute([$id]);

    return $stmt->fetch(PDO::FETCH_ASSOC);
}



function guardarCentroCosto($data)
{
    $pdo = conexion();

    $observaciones = $data['obs'] ?? '';
    $direccion = $data['direccion'] ?? '';

    if (!empty($data['id'])) {

        // EDITAR
        $sql = "UPDATE centros_costo SET
                    nombre = ?,
                    observaciones = ?,
                    direccion = ?
                WHERE id = ?";

        $stmt = $pdo->prepare($sql);

        return $stmt->execute([
            $data['nombre'],
            $observaciones,
            $direccion,
            $data['id']
        ]);
    } else {

        // INSERTAR 
        $sql_max = "SELECT COALESCE(MAX(id_centro_costo), 0) + 1 AS nuevo FROM centros_costo WHERE id_empresa = ?";
        $stmt_max = $pdo->prepare($sql_max);
        $stmt_max->execute([$data['id_empresa']]);
        $nuevo_id_cc = $stmt_max->fetchColumn();

        $sql = "INSERT INTO centros_costo
                (
                    id_empresa,
                    id_centro_costo,
                    nombre,
                    direccion,
                    contacto_centro,
                    cel,
                    observaciones
                )
                VALUES (?, ?, ?, ?, '', 0, ?)";

        $stmt = $pdo->prepare($sql);

        return $stmt->execute([
            $data['id_empresa'],
            $nuevo_id_cc,
            $data['nombre'],
            $direccion,
            $observaciones
        ]);
    }
}

function guardarAutorizante($data)
{
    $pdo = conexion();

    if (!empty($data['id'])) {
        $sql = "UPDATE autorizantes SET
                    nombre=?, celular=?, email=?, horario=?
                WHERE id=?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            $data['nombre'],
            $data['celular'],
            $data['email'],
            $data['horario'],
            $data['id']
        ]);
    } else {
        // Corrección: La columna se llama id_centro_costo, no id_cc. 
        // Agregamos estado = 1 para que aparezca por defecto.
        $sql = "INSERT INTO autorizantes
                (id_empresa, id_centro_costo, nombre, celular, email, horario, estado)
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

function obtenerAutorizantesPorCC($id_cc)
{
    $pdo = conexion();
    // Corrección: La columna es id_centro_costo
    $sql = "SELECT * FROM autorizantes WHERE id_centro_costo=? ORDER BY nombre";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_cc]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function obtenerAutorizantePorId($id)
{
    $pdo = conexion();

    $stmt = $pdo->prepare(
        "SELECT * FROM autorizantes WHERE id=?"
    );

    $stmt->execute([$id]);

    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function borrarAutorizante($id)
{
    $pdo = conexion();

    $stmt = $pdo->prepare(
        "DELETE FROM autorizantes WHERE id=?"
    );

    return $stmt->execute([$id]);
}

function obtenerChoferesActivos()
{
    // 1. Llamamos a tu función conexion() para obtener la conexión activa
    $conn = conexion();

    // 2. Ejecutamos la consulta usando $conn
    $stmt = $conn->query("SELECT id, nombre, apellido, movil FROM choferes WHERE movil IS NOT NULL AND movil != '' ORDER BY movil ASC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
