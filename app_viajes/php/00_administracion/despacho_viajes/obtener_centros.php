<?php
require_once "../../../funciones/funciones.php";
protegerPagina([0, 3]);

header('Content-Type: application/json; charset=utf-8');

if (isset($_GET['id_empresa']) && $_GET['id_empresa'] !== '') {
    $id_empresa = $_GET['id_empresa'];

    try {
        $pdo = conexion();

        // CORRECCIÓN: Se quitó 'centro_de_costo' ya que el campo correcto es 'nombre'
        // Dentro de tu archivo obtener_centros.php cambia la consulta para que quede así:
        $sql = "SELECT id, id_centro_costo, nombre 
        FROM centros_costo 
        WHERE id_empresa = ? 
        ORDER BY nombre";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id_empresa]);

        $centros = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($centros);
    } catch (PDOException $e) {
        echo json_encode(["error" => "Error en la consulta", "detalle" => $e->getMessage()]);
    }
    exit;
}
