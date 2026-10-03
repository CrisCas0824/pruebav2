<?php
include_once 'Core/dataBase.php';

class InventarioModel {
    private $conn;

    public function __construct() {
        $database = new dataBase();
        $this->conn = $database->getConnection();
    }

    public function getInventario() {
        $query = "SELECT COALESCE(i.id_inventario, 0) as id_inventario,
                         p.id_producto,
                         COALESCE(i.stock_actual, p.stock, 0) as stock_actual,
                         COALESCE(i.stock_minimo, 5) as stock_minimo,
                         COALESCE(i.ubicacion, 'Almacén principal') as ubicacion,
                         COALESCE(i.ultima_act, NOW()) as ultima_act,
                         p.codigo, p.codigo_barras, p.precio_anterior, p.es_sugerencia, p.imagen_2, p.imagen_3, p.imagen_4,
                         p.titulo, p.descripcion, p.imagen, p.precio, p.precio_costo, p.unidad_medida, p.activo, p.id_categoria, p.id_grupo,
                         c.nombre AS categoria_nombre,
                         gm.nombre AS grupo_nombre,
                         gm.id_momento,
                         mc.nombre AS momento_nombre,
                         (SELECT COALESCE(SUM(dp.cantidad), 0) FROM detalle_pedido dp WHERE dp.id_producto = p.id_producto) AS ventas_count,
                         (SELECT COUNT(*) FROM detalle_pedido dp WHERE dp.id_producto = p.id_producto) AS ventas_registradas,
                         (SELECT COUNT(*) FROM kardex k WHERE k.id_producto = p.id_producto) AS movimientos_count,
                         (SELECT COUNT(*) FROM reposicion r WHERE r.id_producto = p.id_producto) AS reposiciones_count
                  FROM producto p
                  LEFT JOIN inventario i ON p.id_producto = i.id_producto
                  LEFT JOIN categoria c ON p.id_categoria = c.id_categoria
                  LEFT JOIN grupo_momento gm ON p.id_grupo = gm.id_grupo
                  LEFT JOIN momento_consumo mc ON gm.id_momento = mc.id_momento
                  ORDER BY p.titulo ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getMasVendidos($limit = 5) {
        $query = "SELECT p.id_producto, p.titulo, p.imagen, p.precio, p.unidad_medida,
                         COALESCE(SUM(dp.cantidad), 0) AS total_vendido
                  FROM producto p
                  INNER JOIN detalle_pedido dp ON p.id_producto = dp.id_producto
                  WHERE p.activo = 1
                  GROUP BY p.id_producto, p.titulo, p.imagen, p.precio, p.unidad_medida
                  HAVING total_vendido > 0
                  ORDER BY total_vendido DESC
                  LIMIT :lim";

        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':lim', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getMenosVendidos($limit = 5) {
        $query = "SELECT p.id_producto, p.titulo, p.imagen, p.precio, p.unidad_medida,
                         COALESCE(SUM(dp.cantidad), 0) AS total_vendido
                  FROM producto p
                  LEFT JOIN detalle_pedido dp ON p.id_producto = dp.id_producto
                  WHERE p.activo = 1
                  GROUP BY p.id_producto, p.titulo, p.imagen, p.precio, p.unidad_medida
                  ORDER BY total_vendido ASC, p.titulo ASC
                  LIMIT :lim";

        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':lim', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getProductoInventario($id_producto) {
        $query = "SELECT i.*, p.titulo, p.imagen, p.unidad_medida
                  FROM inventario i
                  INNER JOIN producto p ON i.id_producto = p.id_producto
                  WHERE i.id_producto = :id_producto
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_producto', $id_producto, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function reponerStock($id_producto, $id_usuario, $cantidad, $costo_unitario, $proveedor, $observacion) {
        try {
            $this->conn->beginTransaction();

            $queryRepo = "INSERT INTO reposicion (id_producto, id_usuario, cantidad, costo_unitario, proveedor, observacion, fecha)
                VALUES (:id_producto, :id_usuario, :cantidad, :costo_unitario, :proveedor, :observacion, NOW())";

            $stmtRepo = $this->conn->prepare($queryRepo);
            $stmtRepo->bindParam(':id_producto', $id_producto, PDO::PARAM_INT);
            $stmtRepo->bindParam(':id_usuario', $id_usuario, PDO::PARAM_INT);
            $stmtRepo->bindParam(':cantidad', $cantidad);
            $stmtRepo->bindParam(':costo_unitario', $costo_unitario);
            $stmtRepo->bindParam(':proveedor', $proveedor);
            $stmtRepo->bindParam(':observacion', $observacion);
            $stmtRepo->execute();

            $queryInv = "UPDATE inventario
                         SET stock_actual = stock_actual + :cantidad,
                             ultima_act = NOW()
                         WHERE id_producto = :id_producto";

            $stmtInv = $this->conn->prepare($queryInv);
            $stmtInv->bindParam(':cantidad', $cantidad);
            $stmtInv->bindParam(':id_producto', $id_producto, PDO::PARAM_INT);
            $stmtInv->execute();

            $queryProd = "UPDATE producto SET stock = stock + :cantidad";
            if ((float)$costo_unitario > 0) {
                $queryProd .= ", precio_costo = :costo_unitario";
            }
            $queryProd .= " WHERE id_producto = :id_producto";

            $stmtProd = $this->conn->prepare($queryProd);
            $stmtProd->bindParam(':cantidad', $cantidad);
            if ((float)$costo_unitario > 0) $stmtProd->bindParam(':costo_unitario', $costo_unitario);
            $stmtProd->bindParam(':id_producto', $id_producto, PDO::PARAM_INT);
            $stmtProd->execute();

            $this->conn->commit();
            return true;

        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    public function getHistorialReposicion() {
        $query = "SELECT r.*, p.titulo, p.imagen, p.unidad_medida, u.nombre AS usuario_nombre
                  FROM reposicion r
                  INNER JOIN producto p ON r.id_producto = p.id_producto
                  INNER JOIN usuario u ON r.id_usuario = u.id_usuario
                  ORDER BY r.fecha DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>