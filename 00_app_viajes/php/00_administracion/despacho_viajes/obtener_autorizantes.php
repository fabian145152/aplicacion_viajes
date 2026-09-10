<?php
require_once "../../../funciones/funciones.php";
protegerPagina([0, 3]);

header('Content-Type: application/json; charset=utf-8');

if (isset($_GET['id_cc']) && $_GET['id_cc'] !== '') {
    $id_cc = $_GET['id_cc'];
    $id_empresa = $_GET['id_empresa'] ?? null;

    try {
        $pdo = conexion();

        if ($id_empresa) {
            // Corrección: Usamos id_centro_costo
            $sql = "SELECT id, nombre, celular 
                    FROM autorizantes 
                    WHERE id_centro_costo = ? AND id_empresa = ? AND estado = 1 
                    ORDER BY nombre";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id_cc, $id_empresa]);
        } else {
            $sql = "SELECT id, nombre, celular 
                    FROM autorizantes 
                    WHERE id_centro_costo = ? AND estado = 1 
                    ORDER BY nombre";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id_cc]);
        }

        $autorizantes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($autorizantes);
    } catch (PDOException $e) {
        echo json_encode(["error" => "Error en la consulta", "detalle" => $e->getMessage()]);
    }
    exit;
}
