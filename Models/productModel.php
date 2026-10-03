<?php
include_once 'Core/dataBase.php';

class ProductoModel{
    private $conn;

    public function __construct(){
        $dataBase = new DataBase();
        $this->conn = $dataBase->getConnection();
    }

    public function existeCodigoBarras($codigoBarras, $excluirId = null) {
        $codigoBarras = trim((string)$codigoBarras);
        if ($codigoBarras === '') return false;

        $sql = "SELECT 1 FROM producto WHERE codigo_barras = :codigo_barras";
        $params = [':codigo_barras' => $codigoBarras];
        if ($excluirId !== null) {
            $sql .= " AND id_producto <> :id_producto";
            $params[':id_producto'] = (int)$excluirId;
        }
        $sql .= " LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return (bool)$stmt->fetchColumn();
    }

    public function getProduct(){
        $query = "SELECT * FROM producto";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerTodos(){
        return $this->getProduct();
    }

    public function createProduct($id_categoria, $id_grupo, $titulo, $descripcion, $precio, $unidad_medida, $stock, $imagen, $es_sugerencia, $stock_minimo = 5, $ubicacion = 'Almacén principal', $codigo = null, $precio_anterior = null, $imagen_2 = null, $imagen_3 = null, $imagen_4 = null, $codigo_barras = null){
        try {
            $this->conn->beginTransaction();

            $query = "INSERT INTO producto (id_categoria, id_grupo, titulo, descripcion, precio, precio_anterior, unidad_medida, stock, imagen, imagen_2, imagen_3, imagen_4, es_sugerencia, codigo, codigo_barras, activo) 
                VALUES (:id_categoria, :id_grupo, :titulo, :descripcion, :precio, :precio_anterior, :unidad_medida, :stock, :imagen, :imagen_2, :imagen_3, :imagen_4, :es_sugerencia, :codigo, :codigo_barras, 1)";

            $stmt = $this->conn->prepare($query);

            $stmt->bindValue(':id_categoria', $id_categoria ?: null, PDO::PARAM_INT);
            $stmt->bindValue(':id_grupo', $id_grupo ?: null, PDO::PARAM_INT);
            $stmt->bindParam(':titulo', $titulo);
            $stmt->bindParam(':descripcion', $descripcion);
            $stmt->bindParam(':precio', $precio);
            $stmt->bindValue(':precio_anterior', ($precio_anterior !== null && $precio_anterior !== '') ? $precio_anterior : null);
            $stmt->bindParam(':unidad_medida', $unidad_medida);
            $stmt->bindParam(':stock', $stock);
            $stmt->bindParam(':imagen', $imagen);
            $stmt->bindValue(':imagen_2', $imagen_2 ?: null);
            $stmt->bindValue(':imagen_3', $imagen_3 ?: null);
            $stmt->bindValue(':imagen_4', $imagen_4 ?: null);
            $stmt->bindParam(':es_sugerencia', $es_sugerencia, PDO::PARAM_INT);
            $stmt->bindValue(':codigo', ($codigo !== null && $codigo !== '') ? $codigo : null);
            $valCodigoBarras = ($codigo_barras !== null && trim($codigo_barras) !== '') ? trim($codigo_barras) : null;
            $stmt->bindValue(':codigo_barras', $valCodigoBarras);
            $stmt->execute();

            $id_producto = $this->conn->lastInsertId();

            if (empty($codigo)) {
                $autoSku = 'PROD-' . str_pad($id_producto, 4, '0', STR_PAD_LEFT);
                $stmtSku = $this->conn->prepare("UPDATE producto SET codigo = :sku WHERE id_producto = :id");
                $stmtSku->execute([':sku' => $autoSku, ':id' => $id_producto]);
            }

            $queryInv = "INSERT INTO inventario
                (id_producto, stock_actual, stock_minimo, ubicacion, ultima_act)
                VALUES
                (:id_producto, :stock_actual, :stock_minimo, :ubicacion, NOW())";

            $stmtInv = $this->conn->prepare($queryInv);
            $stmtInv->bindParam(':id_producto', $id_producto, PDO::PARAM_INT);
            $stmtInv->bindParam(':stock_actual', $stock);
            $stmtInv->bindParam(':stock_minimo', $stock_minimo);
            $stmtInv->bindParam(':ubicacion', $ubicacion);
            $stmtInv->execute();

            $this->conn->commit();
            return true;

        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    public function getProductofull(){
        $query = "SELECT p.*, c.nombre AS categoria_nombre, gm.nombre AS grupo_nombre, gm.color AS grupo_color, gm.imagen AS grupo_imagen,
                mc.nombre AS momento_nombre, mc.id_momento
                FROM producto p
                LEFT JOIN categoria c ON p.id_categoria = c.id_categoria
                LEFT JOIN grupo_momento gm ON p.id_grupo = gm.id_grupo
                LEFT JOIN momento_consumo mc ON gm.id_momento = mc.id_momento
                WHERE p.activo = 1
                ORDER BY p.fecha_creacion DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function deleteProduct($id_producto) {
        try {
            $this->conn->beginTransaction();

            $stmtProducto = $this->conn->prepare(
                "SELECT imagen FROM producto WHERE id_producto = :id_producto FOR UPDATE"
            );
            $stmtProducto->bindValue(':id_producto', $id_producto, PDO::PARAM_INT);
            $stmtProducto->execute();
            $producto = $stmtProducto->fetch(PDO::FETCH_ASSOC);

            if (!$producto) {
                $this->conn->rollBack();
                return ['ok' => false, 'mensaje' => 'El producto ya no existe.'];
            }

            $stmtReferencias = $this->conn->prepare(
                "SELECT
                    (SELECT COUNT(*) FROM detalle_pedido WHERE id_producto = :id_ventas) AS ventas,
                    (SELECT COUNT(*) FROM kardex WHERE id_producto = :id_kardex) AS movimientos,
                    (SELECT COUNT(*) FROM reposicion WHERE id_producto = :id_reposicion) AS reposiciones"
            );
            $stmtReferencias->execute([
                ':id_ventas' => $id_producto,
                ':id_kardex' => $id_producto,
                ':id_reposicion' => $id_producto
            ]);
            $referencias = $stmtReferencias->fetch(PDO::FETCH_ASSOC);

            if ((int)$referencias['ventas'] > 0) {
                $this->conn->rollBack();
                return ['ok' => false, 'mensaje' => 'El producto tiene ventas registradas y no se puede eliminar.'];
            }
            if ((int)$referencias['movimientos'] > 0 || (int)$referencias['reposiciones'] > 0) {
                $this->conn->rollBack();
                return ['ok' => false, 'mensaje' => 'El producto tiene movimientos de inventario o reposiciones. Inactívalo para conservar su historial.'];
            }

            $stmtEliminar = $this->conn->prepare(
                "DELETE FROM producto WHERE id_producto = :id_producto"
            );
            $stmtEliminar->bindValue(':id_producto', $id_producto, PDO::PARAM_INT);
            $stmtEliminar->execute();
            $this->conn->commit();

            $rutaImagen = $producto['imagen'] ?? '';
            if ($rutaImagen !== '' && strpos($rutaImagen, 'http') !== 0 && is_file($rutaImagen)) {
                unlink($rutaImagen);
            }

            return ['ok' => true, 'mensaje' => 'Producto eliminado correctamente.'];
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            error_log('Error al eliminar producto: ' . $e->getMessage());
            return ['ok' => false, 'mensaje' => 'No se pudo eliminar el producto. Si tiene historial, puedes inactivarlo.'];
        }
    }

    public function getProductosRelacionados($id_producto, $id_categoria) {
        $query = "SELECT * FROM producto WHERE activo = 1
                AND id_producto != :id_producto
                AND id_categoria = :id_categoria
                LIMIT 10";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_producto', $id_producto, PDO::PARAM_INT);
        $stmt->bindParam(':id_categoria', $id_categoria, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getProductosComplementarios($id_producto, $id_grupo) {
        $query = "SELECT * FROM producto WHERE activo = 1
                AND id_producto != :id_producto
                AND id_grupo = :id_grupo
                LIMIT 10";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_producto', $id_producto, PDO::PARAM_INT);
        $stmt->bindParam(':id_grupo', $id_grupo, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getProductosStockBajo(){
        $query = "SELECT p.id_producto, p.titulo, p.imagen, p.unidad_medida, i.stock_actual, i.stock_minimo, i.ubicacion
                FROM inventario i
                INNER JOIN producto p ON i.id_producto = p.id_producto
                WHERE p.activo = 1
                AND i.stock_actual <= i.stock_minimo
                ORDER BY i.stock_actual ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateProducto($id_producto, $id_categoria, $id_grupo, $titulo, $descripcion, $precio, $unidad_medida, $stock, $imagen, $es_sugerencia, $stock_minimo = null, $ubicacion = null, $codigo = null, $precio_anterior = null, $imagen_2 = null, $imagen_3 = null, $imagen_4 = null, $codigo_barras = null) {
        try {
            $this->conn->beginTransaction();

            $query = "UPDATE producto SET id_categoria = :id_categoria, id_grupo = :id_grupo, titulo = :titulo,
                        descripcion = :descripcion, precio = :precio, precio_anterior = :precio_anterior, unidad_medida = :unidad_medida, stock = :stock,
                        imagen = :imagen, imagen_2 = :imagen_2, imagen_3 = :imagen_3, imagen_4 = :imagen_4, es_sugerencia = :es_sugerencia, codigo = :codigo, codigo_barras = :codigo_barras WHERE id_producto = :id_producto";

            $stmt = $this->conn->prepare($query);

            $stmt->bindValue(':id_categoria', $id_categoria ?: null, PDO::PARAM_INT);
            $stmt->bindValue(':id_grupo', $id_grupo ?: null, PDO::PARAM_INT);
            $stmt->bindParam(':titulo', $titulo);
            $stmt->bindParam(':descripcion', $descripcion);
            $stmt->bindParam(':precio', $precio);
            $stmt->bindValue(':precio_anterior', ($precio_anterior !== null && $precio_anterior !== '') ? $precio_anterior : null);
            $stmt->bindParam(':unidad_medida', $unidad_medida);
            $stmt->bindParam(':stock', $stock);
            $stmt->bindParam(':imagen', $imagen);
            $stmt->bindValue(':imagen_2', $imagen_2 ?: null);
            $stmt->bindValue(':imagen_3', $imagen_3 ?: null);
            $stmt->bindValue(':imagen_4', $imagen_4 ?: null);
            $stmt->bindParam(':es_sugerencia', $es_sugerencia, PDO::PARAM_INT);
            $stmt->bindValue(':codigo', ($codigo !== null && $codigo !== '') ? $codigo : null);
            $valCodigoBarras = ($codigo_barras !== null && trim($codigo_barras) !== '') ? trim($codigo_barras) : null;
            $stmt->bindValue(':codigo_barras', $valCodigoBarras);
            $stmt->bindParam(':id_producto', $id_producto, PDO::PARAM_INT);
            $stmt->execute();

            // Synchronize with inventario table
            $queryInv = "UPDATE inventario SET stock_actual = :stock, ultima_act = NOW()";
            $paramsInv = [':stock' => $stock, ':id_producto' => $id_producto];
            if ($stock_minimo !== null && $stock_minimo !== '') {
                $queryInv .= ", stock_minimo = :stock_minimo";
                $paramsInv[':stock_minimo'] = $stock_minimo;
            }
            if ($ubicacion !== null && trim($ubicacion) !== '') {
                $queryInv .= ", ubicacion = :ubicacion";
                $paramsInv[':ubicacion'] = trim($ubicacion);
            }
            $queryInv .= " WHERE id_producto = :id_producto";
            $stmtInv = $this->conn->prepare($queryInv);
            $stmtInv->execute($paramsInv);

            // If product was not in inventario yet, create row
            $chk = $this->conn->prepare("SELECT id_producto FROM inventario WHERE id_producto = :id_producto");
            $chk->execute([':id_producto' => $id_producto]);
            if (!$chk->fetch()) {
                $ins = $this->conn->prepare("INSERT INTO inventario (id_producto, stock_actual, stock_minimo, ubicacion, ultima_act) VALUES (:id_producto, :stock, :min, :ubi, NOW())");
                $ins->execute([
                    ':id_producto' => $id_producto,
                    ':stock' => $stock,
                    ':min' => ($stock_minimo !== null && $stock_minimo !== '') ? $stock_minimo : 5,
                    ':ubi' => ($ubicacion !== null && trim($ubicacion) !== '') ? trim($ubicacion) : 'Almacén principal'
                ]);
            }

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            return false;
        }
    }

    public function getProductoById($idProducto){
        $query = "SELECT p.*, c.nombre AS categoria_nombre, c.nombre AS nombre_categoria, gm.nombre AS grupo_nombre,
                         gm.id_momento, mc.nombre AS momento_nombre
                  FROM producto p 
                  LEFT JOIN categoria c ON p.id_categoria = c.id_categoria 
                  LEFT JOIN grupo_momento gm ON p.id_grupo = gm.id_grupo 
                  LEFT JOIN momento_consumo mc ON gm.id_momento = mc.id_momento
                  WHERE p.id_producto = :id_producto LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':id_producto', $idProducto, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getVentasCount($id_producto) {
        $query = "SELECT COUNT(*) FROM detalle_pedido WHERE id_producto = :id_producto";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':id_producto', $id_producto, PDO::PARAM_INT);
        $stmt->execute();
        return (float)$stmt->fetchColumn();
    }

    public function toggleStatus($id_producto, $estado = null) {
        if ($estado === null) {
            $query = "UPDATE producto SET activo = IF(activo = 1, 0, 1) WHERE id_producto = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':id', $id_producto, PDO::PARAM_INT);
            return $stmt->execute();
        } else {
            $query = "UPDATE producto SET activo = :est WHERE id_producto = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':est', (int)$estado, PDO::PARAM_INT);
            $stmt->bindValue(':id', $id_producto, PDO::PARAM_INT);
            return $stmt->execute();
        }
    }
}
?>