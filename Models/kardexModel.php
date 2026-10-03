<?php
require_once __DIR__ . '/../Core/dataBase.php';

class KardexModel {
    private $conn;

    public function __construct() {
        $db = new DataBase();
        $this->conn = $db->getConnection();
    }

    /**
     * Registra un movimiento en el Kardex
     */
    public function registrarMovimiento(
        $idProducto,
        $idUsuario,
        $tipo,
        $motivo,
        $cantidad,
        $costoUnit,
        $precioUnit,
        $stockAnterior,
        $stockActual,
        $refId = null,
        $refTipo = null
    ) {
        $sql = "INSERT INTO kardex (id_producto, id_usuario, tipo_movimiento, motivo, cantidad, costo_unitario, precio_unitario, stock_anterior, stock_actual, referencia_id, referencia_tipo, fecha)
                VALUES (:id_producto, :id_usuario, :tipo, :motivo, :cantidad, :costo, :precio, :stock_ant, :stock_act, :ref_id, :ref_tipo, NOW())";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id_producto', $idProducto, PDO::PARAM_INT);
        $stmt->bindParam(':id_usuario', $idUsuario, PDO::PARAM_INT);
        $stmt->bindParam(':tipo', $tipo);
        $stmt->bindParam(':motivo', $motivo);
        $stmt->bindParam(':cantidad', $cantidad, PDO::PARAM_INT);
        $stmt->bindParam(':costo', $costoUnit);
        $stmt->bindParam(':precio', $precioUnit);
        $stmt->bindParam(':stock_ant', $stockAnterior, PDO::PARAM_INT);
        $stmt->bindParam(':stock_act', $stockActual, PDO::PARAM_INT);
        $stmt->bindParam(':ref_id', $refId, $refId ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindParam(':ref_tipo', $refTipo, $refTipo ? PDO::PARAM_STR : PDO::PARAM_NULL);
        return $stmt->execute();
    }

    /**
     * Lista movimientos con detalle de producto y usuario
     */
    public function listarMovimientos($idProducto = null, $limite = 500, $offset = 0, $fechaDesde = null, $fechaHasta = null, $orden = 'DESC') {
        $sql = "SELECT k.*, p.nombre as producto_nombre, p.codigo, p.codigo_barras, p.unidad_medida,
                       u.nombre as usuario_nombre, u.apellido as usuario_apellido, u.rol as usuario_rol
                FROM kardex k
                JOIN producto p ON k.id_producto = p.id_producto
                JOIN usuario u ON k.id_usuario = u.id_usuario";
        $conditions = [];
        $params = [];
        if ($idProducto !== null && $idProducto > 0) {
            $conditions[] = 'k.id_producto = :id_prod';
            $params[':id_prod'] = $idProducto;
        }
        if ($fechaDesde) {
            $conditions[] = 'k.fecha >= :fecha_desde';
            $params[':fecha_desde'] = $fechaDesde . ' 00:00:00';
        }
        if ($fechaHasta) {
            $conditions[] = 'k.fecha <= :fecha_hasta';
            $params[':fecha_hasta'] = $fechaHasta . ' 23:59:59';
        }
        if ($conditions) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }
        $orden = strtoupper($orden) === 'ASC' ? 'ASC' : 'DESC';
        $sql .= " ORDER BY k.fecha $orden, k.id_kardex $orden LIMIT :limite OFFSET :offset";

        $stmt = $this->conn->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->bindValue(':limite', (int)$limite, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function contarMovimientos($idProducto = null, $fechaDesde = null, $fechaHasta = null) {
        $conditions = [];
        $params = [];
        if ($idProducto !== null && $idProducto > 0) {
            $conditions[] = 'id_producto = :id_prod';
            $params[':id_prod'] = $idProducto;
        }
        if ($fechaDesde) {
            $conditions[] = 'fecha >= :fecha_desde';
            $params[':fecha_desde'] = $fechaDesde . ' 00:00:00';
        }
        if ($fechaHasta) {
            $conditions[] = 'fecha <= :fecha_hasta';
            $params[':fecha_hasta'] = $fechaHasta . ' 23:59:59';
        }
        $sql = 'SELECT COUNT(*) FROM kardex';
        if ($conditions) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }
        $stmt = $this->conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    /**
     * Ajuste flexible de inventario (Entrada, Salida o Ajuste Físico)
     */
    public function ajustarStockFlexible($idProducto, $tipoOperacion, $cantidad, $nuevoStock, $motivo, $idUsuario) {
        $idProducto = (int)$idProducto;
        $cantidad = (int)$cantidad;
        $idUsuario = (int)$idUsuario;
        $tipoOperacion = strtolower(trim($tipoOperacion));

        try {
            $this->conn->beginTransaction();

            $sqlP = "SELECT id_producto, nombre, stock, precio_costo, precio FROM producto WHERE id_producto = :id FOR UPDATE";
            $stmtP = $this->conn->prepare($sqlP);
            $stmtP->bindParam(':id', $idProducto, PDO::PARAM_INT);
            $stmtP->execute();
            $prod = $stmtP->fetch(PDO::FETCH_ASSOC);

            if (!$prod) {
                $this->conn->rollBack();
                return ['ok' => false, 'mensaje' => 'Producto no encontrado'];
            }

            $stockAnterior = (int)$prod['stock'];
            $stockFinal = $stockAnterior;
            $tipoMovimiento = 'AJUSTE';
            $cantMovimiento = 0;

            if ($tipoOperacion === 'entrada' || $tipoOperacion === 'ingreso') {
                if ($cantidad <= 0) {
                    $this->conn->rollBack();
                    return ['ok' => false, 'mensaje' => 'La cantidad a ingresar debe ser mayor a 0'];
                }
                $stockFinal = $stockAnterior + $cantidad;
                $tipoMovimiento = 'ENTRADA';
                $cantMovimiento = $cantidad;
                $motivoFinal = 'Ingreso manual: ' . $motivo . ' (+' . $cantidad . ')';
            } elseif ($tipoOperacion === 'salida') {
                if ($cantidad <= 0) {
                    $this->conn->rollBack();
                    return ['ok' => false, 'mensaje' => 'La cantidad de salida debe ser mayor a 0'];
                }
                if ($cantidad > $stockAnterior) {
                    $this->conn->rollBack();
                    return ['ok' => false, 'mensaje' => 'Stock insuficiente. Stock actual disponible: ' . $stockAnterior];
                }
                $stockFinal = $stockAnterior - $cantidad;
                $tipoMovimiento = 'SALIDA';
                $cantMovimiento = $cantidad;
                $motivoFinal = 'Salida manual: ' . $motivo . ' (-' . $cantidad . ')';
            } else {
                // Ajuste físico
                $nuevoStock = (int)$nuevoStock;
                if ($nuevoStock < 0) {
                    $this->conn->rollBack();
                    return ['ok' => false, 'mensaje' => 'El stock no puede ser negativo'];
                }
                $diferencia = $nuevoStock - $stockAnterior;
                if ($diferencia === 0) {
                    $this->conn->rollBack();
                    return ['ok' => false, 'mensaje' => 'El nuevo stock es idéntico al stock actual (' . $stockAnterior . ')'];
                }
                $stockFinal = $nuevoStock;
                $tipoMovimiento = $diferencia > 0 ? 'ENTRADA' : 'SALIDA';
                $cantMovimiento = abs($diferencia);
                $motivoFinal = 'Ajuste físico: ' . $motivo . ' (' . ($diferencia > 0 ? '+' : '') . $diferencia . ')';
            }

            // Actualizar tabla producto
            $sqlUpdProd = "UPDATE producto SET stock = :stk WHERE id_producto = :id";
            $stmtUpdProd = $this->conn->prepare($sqlUpdProd);
            $stmtUpdProd->bindParam(':stk', $stockFinal, PDO::PARAM_INT);
            $stmtUpdProd->bindParam(':id', $idProducto, PDO::PARAM_INT);
            $stmtUpdProd->execute();

            // Actualizar tabla inventario si existe
            try {
                $sqlUpdInv = "UPDATE inventario SET stock_actual = :stk WHERE id_producto = :id";
                $stmtUpdInv = $this->conn->prepare($sqlUpdInv);
                $stmtUpdInv->bindParam(':stk', $stockFinal, PDO::PARAM_INT);
                $stmtUpdInv->bindParam(':id', $idProducto, PDO::PARAM_INT);
                $stmtUpdInv->execute();
            } catch (Exception $eInv) {
                // ignore
            }

            // Registrar en Kardex
            $this->registrarMovimiento(
                $idProducto,
                $idUsuario,
                $tipoMovimiento,
                $motivoFinal,
                $cantMovimiento,
                $prod['precio_costo'] ?? 0,
                $prod['precio'] ?? 0,
                $stockAnterior,
                $stockFinal,
                null,
                'ajuste_manual'
            );

            $this->conn->commit();
            return ['ok' => true, 'mensaje' => 'Movimiento registrado correctamente. Stock final: ' . $stockFinal . ' unidades'];

        } catch (Exception $e) {
            $this->conn->rollBack();
            return ['ok' => false, 'mensaje' => 'Error: ' . $e->getMessage()];
        }
    }

    public function ajustarStockManual($idProducto, $nuevoStock, $motivo, $idUsuario) {
        return $this->ajustarStockFlexible($idProducto, 'ajuste', 0, $nuevoStock, $motivo, $idUsuario);
    }

    /**
     * Resumen de valorización total del inventario
     */
    public function obtenerValorizacionInventario() {
        $sql = "SELECT 
                    COUNT(*) as total_productos,
                    COALESCE(SUM(stock), 0) as total_unidades,
                    COALESCE(SUM(stock * precio_costo), 0) as valor_costo_total,
                    COALESCE(SUM(stock * precio), 0) as valor_venta_total,
                    COALESCE(SUM(stock * (precio - precio_costo)), 0) as ganancia_potencial
                FROM producto 
                WHERE activo = 1";
        $stmt = $this->conn->query($sql);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
