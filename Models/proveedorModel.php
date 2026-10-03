<?php
require_once __DIR__ . '/../Core/dataBase.php';
require_once __DIR__ . '/kardexModel.php';

class ProveedorModel {
    private $conn;
    private $kardexModel;

    public function __construct() {
        $db = new DataBase();
        $this->conn = $db->getConnection();
        $this->kardexModel = new KardexModel();
    }

    public function listarProveedores($soloActivos = false) {
        $sql = "SELECT p.*, 
                       (SELECT COUNT(*) FROM reposicion r WHERE r.id_proveedor = p.id_proveedor) as total_compras,
                       (SELECT COALESCE(SUM(r.cantidad * r.costo_unitario), 0) FROM reposicion r WHERE r.id_proveedor = p.id_proveedor) as monto_comprado
                FROM proveedor p";
        if ($soloActivos) {
            $sql .= " WHERE p.activo = 1";
        }
        $sql .= " ORDER BY p.activo DESC, p.razon_social ASC";
        $stmt = $this->conn->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerProveedor($id) {
        $sql = "SELECT * FROM proveedor WHERE id_proveedor = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function obtenerEstadisticasProveedores() {
        $sql = "SELECT 
                    COUNT(*) as total_proveedores,
                    COALESCE(SUM(CASE WHEN activo = 1 THEN 1 ELSE 0 END), 0) as activos,
                    COALESCE(SUM(CASE WHEN activo = 0 THEN 1 ELSE 0 END), 0) as inactivos,
                    (SELECT COUNT(*) FROM reposicion) as total_compras,
                    (SELECT COALESCE(SUM(cantidad * costo_unitario), 0) FROM reposicion) as monto_total_compras
                FROM proveedor";
        $stmt = $this->conn->query($sql);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function crearProveedor($ruc, $razonSocial, $contacto = '', $telefono = '', $correo = '', $direccion = '') {
        $sql = "INSERT INTO proveedor (ruc, razon_social, contacto_nombre, telefono, correo, direccion, activo, fecha_registro)
                VALUES (:ruc, :razon, :contacto, :tel, :correo, :dir, 1, NOW())";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':ruc', $ruc);
        $stmt->bindParam(':razon', $razonSocial);
        $stmt->bindParam(':contacto', $contacto);
        $stmt->bindParam(':tel', $telefono);
        $stmt->bindParam(':correo', $correo);
        $stmt->bindParam(':dir', $direccion);
        return $stmt->execute();
    }

    public function actualizarProveedor($id, $ruc, $razonSocial, $contacto = '', $telefono = '', $correo = '', $direccion = '') {
        $sql = "UPDATE proveedor SET 
                    ruc = :ruc, 
                    razon_social = :razon, 
                    contacto_nombre = :contacto, 
                    telefono = :tel, 
                    correo = :correo, 
                    direccion = :dir 
                WHERE id_proveedor = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':ruc', $ruc);
        $stmt->bindParam(':razon', $razonSocial);
        $stmt->bindParam(':contacto', $contacto);
        $stmt->bindParam(':tel', $telefono);
        $stmt->bindParam(':correo', $correo);
        $stmt->bindParam(':dir', $direccion);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function cambiarEstado($id, $activo) {
        $sql = "UPDATE proveedor SET activo = :activo WHERE id_proveedor = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':activo', $activo, PDO::PARAM_INT);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Registra una compra / reposición de mercadería a un proveedor
     * Permite producto existente o creación al vuelo de nuevo producto
     */
    public function registrarCompraReposicion(
        $idProducto,
        $idProveedor,
        $idUsuario,
        $cantidad,
        $costoUnit,
        $precioSugerido = null,
        $comprobante = '',
        $lote = '',
        $vencimiento = null,
        $observacion = '',
        $descontarDeCaja = false,
        $idSesionCaja = null,
        $datosNuevoProducto = null
    ) {
        $cantidad = (int)$cantidad;
        $costoUnit = (float)$costoUnit;

        if ($cantidad <= 0 || $costoUnit <= 0) {
            return ['ok' => false, 'mensaje' => 'Cantidad y costo unitario deben ser mayores a cero'];
        }

        try {
            $this->conn->beginTransaction();

            // Si se solicita crear nuevo producto al vuelo
            if ($idProducto <= 0 && !empty($datosNuevoProducto['nombre'])) {
                $nomProd = trim($datosNuevoProducto['nombre']);
                $skuProd = trim($datosNuevoProducto['sku'] ?? ('PROD-' . rand(1000, 9999)));
                $codBarras = !empty($datosNuevoProducto['codigo_barras']) ? trim($datosNuevoProducto['codigo_barras']) : null;
                $precioVenta = !empty($precioSugerido) ? (float)$precioSugerido : round($costoUnit * 1.30, 2);
                $idCat = !empty($datosNuevoProducto['id_categoria']) ? (int)$datosNuevoProducto['id_categoria'] : 1;

                $sqlNewP = "INSERT INTO producto (nombre, titulo, codigo, codigo_barras, precio_costo, precio, stock, id_categoria, activo, unidad_medida, imagen)
                            VALUES (:nom, :nom, :cod, :barras, :costo, :precio, 0, :id_cat, 1, 'UND', 'Images/productos/default.png')";
                $stmtNewP = $this->conn->prepare($sqlNewP);
                $stmtNewP->bindParam(':nom', $nomProd);
                $stmtNewP->bindParam(':cod', $skuProd);
                $stmtNewP->bindParam(':barras', $codBarras);
                $stmtNewP->bindParam(':costo', $costoUnit);
                $stmtNewP->bindParam(':precio', $precioVenta);
                $stmtNewP->bindParam(':id_cat', $idCat, PDO::PARAM_INT);
                $stmtNewP->execute();
                $idProducto = (int)$this->conn->lastInsertId();
            }

            // 1. Obtener producto actual
            $sqlP = "SELECT id_producto, stock, precio_costo, precio, nombre FROM producto WHERE id_producto = :id FOR UPDATE";
            $stmtP = $this->conn->prepare($sqlP);
            $stmtP->bindParam(':id', $idProducto, PDO::PARAM_INT);
            $stmtP->execute();
            $prod = $stmtP->fetch(PDO::FETCH_ASSOC);

            if (!$prod) {
                $this->conn->rollBack();
                return ['ok' => false, 'mensaje' => 'Producto no encontrado'];
            }

            // Obtener razón social del proveedor
            $nombreProveedor = 'Proveedor Ocasional';
            if ($idProveedor) {
                $prov = $this->obtenerProveedor($idProveedor);
                if ($prov) $nombreProveedor = $prov['razon_social'];
            }

            $stockAnterior = (int)$prod['stock'];
            $nuevoStock = $stockAnterior + $cantidad;

            // 2. Registrar en reposición
            $sqlRepo = "INSERT INTO reposicion (id_producto, id_proveedor, id_usuario, cantidad, costo_unitario, precio_venta_sugerido, proveedor, nro_comprobante, lote, fecha_vencimiento, observacion, fecha)
                        VALUES (:id_prod, :id_prov, :id_user, :cant, :costo, :sug, :prov_nom, :comp, :lote, :venc, :obs, NOW())";
            $stmtRepo = $this->conn->prepare($sqlRepo);
            $stmtRepo->bindParam(':id_prod', $idProducto, PDO::PARAM_INT);
            $stmtRepo->bindParam(':id_prov', $idProveedor, $idProveedor ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $stmtRepo->bindParam(':id_user', $idUsuario, PDO::PARAM_INT);
            $stmtRepo->bindParam(':cant', $cantidad, PDO::PARAM_INT);
            $stmtRepo->bindParam(':costo', $costoUnit);
            $stmtRepo->bindParam(':sug', $precioSugerido, $precioSugerido ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmtRepo->bindParam(':prov_nom', $nombreProveedor);
            $stmtRepo->bindParam(':comp', $comprobante);
            $stmtRepo->bindParam(':lote', $lote);
            $stmtRepo->bindParam(':venc', $vencimiento, $vencimiento ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmtRepo->bindParam(':obs', $observacion);
            $stmtRepo->execute();
            $idReposicion = $this->conn->lastInsertId();

            // 3. Actualizar producto (stock y actualizar costo)
            $sqlUpdProd = "UPDATE producto SET 
                            stock = :stk, 
                            precio_costo = :costo,
                            precio = COALESCE(:nuevo_precio, precio)
                           WHERE id_producto = :id";
            $stmtUpdProd = $this->conn->prepare($sqlUpdProd);
            $stmtUpdProd->bindParam(':stk', $nuevoStock, PDO::PARAM_INT);
            $stmtUpdProd->bindParam(':costo', $costoUnit);
            $stmtUpdProd->bindParam(':nuevo_precio', $precioSugerido, $precioSugerido ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmtUpdProd->bindParam(':id', $idProducto, PDO::PARAM_INT);
            $stmtUpdProd->execute();

            // 4. Actualizar inventario si existe
            try {
                $sqlUpdInv = "UPDATE inventario SET stock_actual = :stk WHERE id_producto = :id";
                $stmtUpdInv = $this->conn->prepare($sqlUpdInv);
                $stmtUpdInv->bindParam(':stk', $nuevoStock, PDO::PARAM_INT);
                $stmtUpdInv->bindParam(':id', $idProducto, PDO::PARAM_INT);
                $stmtUpdInv->execute();
            } catch (Exception $eInv) {
                // ignore
            }

            // 5. Registrar en Kardex
            $this->kardexModel->registrarMovimiento(
                $idProducto,
                $idUsuario,
                'ENTRADA',
                'Compra Proveedor: ' . $nombreProveedor . ($comprobante ? ' (' . $comprobante . ')' : ''),
                $cantidad,
                $costoUnit,
                $prod['precio'],
                $stockAnterior,
                $nuevoStock,
                $idReposicion,
                'reposicion'
            );

            // 6. Si se descuenta de caja chica
            if ($descontarDeCaja && $idSesionCaja) {
                $totalCompra = round($cantidad * $costoUnit, 2);
                $sqlCaja = "INSERT INTO movimiento_caja (id_sesion, id_usuario, tipo, categoria, monto, motivo, comprobante_ref, fecha)
                            VALUES (:id_sesion, :id_user, 'EGRESO', 'proveedor', :monto, :motivo, :comp, NOW())";
                $stmtCaja = $this->conn->prepare($sqlCaja);
                $motivoEgreso = "Pago Compra Proveedor: " . $nombreProveedor . " (" . $prod['nombre'] . ")";
                $stmtCaja->bindParam(':id_sesion', $idSesionCaja, PDO::PARAM_INT);
                $stmtCaja->bindParam(':id_user', $idUsuario, PDO::PARAM_INT);
                $stmtCaja->bindParam(':monto', $totalCompra);
                $stmtCaja->bindParam(':motivo', $motivoEgreso);
                $stmtCaja->bindParam(':comp', $comprobante);
                $stmtCaja->execute();

                $this->conn->query("UPDATE sesion_caja SET total_egresos = total_egresos + {$totalCompra} WHERE id_sesion = {$idSesionCaja}");
            }

            $this->conn->commit();
            return [
                'ok' => true,
                'mensaje' => 'Compra registrada con éxito. Se agregaron +' . $cantidad . ' unidades a "' . $prod['nombre'] . '" (Stock actual: ' . $nuevoStock . ')',
                'nuevo_stock' => $nuevoStock
            ];

        } catch (Exception $e) {
            $this->conn->rollBack();
            return ['ok' => false, 'mensaje' => 'Error: ' . $e->getMessage()];
        }
    }

    public function listarReposiciones($limite = 500) {
        $sql = "SELECT r.*, p.nombre as producto_nombre, p.codigo, p.codigo_barras,
                       pv.razon_social as proveedor_razon_social,
                       u.nombre as usuario_nombre, u.apellido as usuario_apellido
                FROM reposicion r
                JOIN producto p ON r.id_producto = p.id_producto
                LEFT JOIN proveedor pv ON r.id_proveedor = pv.id_proveedor
                JOIN usuario u ON r.id_usuario = u.id_usuario
                ORDER BY r.fecha DESC LIMIT :limite";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':limite', (int)$limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
