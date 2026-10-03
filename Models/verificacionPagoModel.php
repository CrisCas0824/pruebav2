<?php
require_once __DIR__ . '/../Core/dataBase.php';
require_once __DIR__ . '/kardexModel.php';

class VerificacionPagoModel {
    private $conn;
    private $kardexModel;

    public function __construct() {
        $db = new DataBase();
        $this->conn = $db->getConnection();
        $this->kardexModel = new KardexModel();
    }

    public function listarVerificaciones($filtro = null) {
        $sql = "SELECT pv.*, p.codigo_pedido, p.total as pedido_total, p.estado as pedido_estado,
                       u.nombre as cliente_nombre, u.apellido as cliente_apellido, u.correo as cliente_correo,
                       mp.nombre as metodo_nombre,
                       v.nombre as verificador_nombre, v.apellido as verificador_apellido
                FROM pago_verificacion pv
                JOIN pedido p ON pv.id_pedido = p.id_pedido
                JOIN usuario u ON p.id_usuario = u.id_usuario
                JOIN metodo_pago mp ON pv.id_metodo = mp.id_metodo
                LEFT JOIN usuario v ON pv.id_usuario_verificador = v.id_usuario";
        $params = [];
        if ($filtro) {
            $sql .= " WHERE pv.estado_verificacion = :est";
            $params[':est'] = $filtro;
        }
        $sql .= " ORDER BY pv.id_verificacion DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function registrarVerificacion($idPedido, $idMetodo, $codigoOperacion, $celularRemitente, $titularOrigen, $capturaVoucher, $montoReportado) {
        $sql = "INSERT INTO pago_verificacion (id_pedido, id_metodo, codigo_operacion, celular_remitente, titular_origen, captura_voucher, monto_reportado, estado_verificacion, fecha_envio)
                VALUES (:ped, :met, :cod, :cel, :tit, :cap, :monto, 'Pendiente', NOW())";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':ped', $idPedido, PDO::PARAM_INT);
        $stmt->bindParam(':met', $idMetodo, PDO::PARAM_INT);
        $stmt->bindParam(':cod', $codigoOperacion);
        $stmt->bindParam(':cel', $celularRemitente);
        $stmt->bindParam(':tit', $titularOrigen);
        $stmt->bindParam(':cap', $capturaVoucher);
        $stmt->bindParam(':monto', $montoReportado);
        return $stmt->execute();
    }

    public function aprobarPago($idVerificacion, $idUsuarioVerificador) {
        try {
            $this->conn->beginTransaction();

            $sqlV = "SELECT * FROM pago_verificacion WHERE id_verificacion = :id FOR UPDATE";
            $stmtV = $this->conn->prepare($sqlV);
            $stmtV->bindParam(':id', $idVerificacion, PDO::PARAM_INT);
            $stmtV->execute();
            $verif = $stmtV->fetch(PDO::FETCH_ASSOC);

            if (!$verif || $verif['estado_verificacion'] !== 'Pendiente') {
                $this->conn->rollBack();
                return ['ok' => false, 'mensaje' => 'La verificación no está en estado Pendiente'];
            }

            // 1. Actualizar estado de verificación
            $sqlUpdV = "UPDATE pago_verificacion SET 
                            estado_verificacion = 'Aprobado',
                            id_usuario_verificador = :user,
                            fecha_verificacion = NOW()
                        WHERE id_verificacion = :id";
            $stmtUpdV = $this->conn->prepare($sqlUpdV);
            $stmtUpdV->bindParam(':user', $idUsuarioVerificador, PDO::PARAM_INT);
            $stmtUpdV->bindParam(':id', $idVerificacion, PDO::PARAM_INT);
            $stmtUpdV->execute();

            // 2. Actualizar estado del pedido a 'En Preparacion'
            $idPedido = $verif['id_pedido'];
            $sqlUpdP = "UPDATE pedido SET estado = 'En Preparacion' WHERE id_pedido = :ped";
            $stmtUpdP = $this->conn->prepare($sqlUpdP);
            $stmtUpdP->bindParam(':ped', $idPedido, PDO::PARAM_INT);
            $stmtUpdP->execute();

            // 3. Descontar stock y registrar salidas en Kardex
            $sqlDet = "SELECT dp.*, p.stock, p.precio_costo, p.nombre 
                       FROM detalle_pedido dp
                       JOIN producto p ON dp.id_producto = p.id_producto
                       WHERE dp.id_pedido = :ped";
            $stmtDet = $this->conn->prepare($sqlDet);
            $stmtDet->bindParam(':ped', $idPedido, PDO::PARAM_INT);
            $stmtDet->execute();
            $items = $stmtDet->fetchAll(PDO::FETCH_ASSOC);

            foreach ($items as $item) {
                $cant = (int)$item['cantidad'];
                $stockAnt = (int)$item['stock'];
                $stockNuevo = max(0, $stockAnt - $cant);

                // Actualizar producto
                $this->conn->query("UPDATE producto SET stock = {$stockNuevo} WHERE id_producto = {$item['id_producto']}");
                $this->conn->query("UPDATE inventario SET stock_actual = {$stockNuevo} WHERE id_producto = {$item['id_producto']}");

                // Registrar en Kardex
                $this->kardexModel->registrarMovimiento(
                    $item['id_producto'],
                    $idUsuarioVerificador,
                    'SALIDA',
                    'Venta Web Aprobada (Pago Digital) #' . $idPedido,
                    $cant,
                    $item['precio_costo'],
                    $item['precio_unitario'],
                    $stockAnt,
                    $stockNuevo,
                    $idPedido,
                    'pedido'
                );
            }

            $this->conn->commit();
            return ['ok' => true, 'mensaje' => 'Pago validado y aprobado con éxito. Pedido pasó a "En Preparación"'];

        } catch (Exception $e) {
            $this->conn->rollBack();
            return ['ok' => false, 'mensaje' => 'Error: ' . $e->getMessage()];
        }
    }

    public function rechazarPago($idVerificacion, $idUsuarioVerificador, $motivo) {
        try {
            $this->conn->beginTransaction();

            $sqlV = "SELECT * FROM pago_verificacion WHERE id_verificacion = :id FOR UPDATE";
            $stmtV = $this->conn->prepare($sqlV);
            $stmtV->bindParam(':id', $idVerificacion, PDO::PARAM_INT);
            $stmtV->execute();
            $verif = $stmtV->fetch(PDO::FETCH_ASSOC);

            if (!$verif || $verif['estado_verificacion'] !== 'Pendiente') {
                $this->conn->rollBack();
                return ['ok' => false, 'mensaje' => 'La verificación no está en estado Pendiente'];
            }

            // 1. Actualizar estado de verificación
            $sqlUpdV = "UPDATE pago_verificacion SET 
                            estado_verificacion = 'Rechazado',
                            id_usuario_verificador = :user,
                            motivo_rechazo = :motivo,
                            fecha_verificacion = NOW()
                        WHERE id_verificacion = :id";
            $stmtUpdV = $this->conn->prepare($sqlUpdV);
            $stmtUpdV->bindParam(':user', $idUsuarioVerificador, PDO::PARAM_INT);
            $stmtUpdV->bindParam(':motivo', $motivo);
            $stmtUpdV->bindParam(':id', $idVerificacion, PDO::PARAM_INT);
            $stmtUpdV->execute();

            // 2. Marcar pedido como cancelado
            $sqlUpdP = "UPDATE pedido SET estado = 'Cancelado', observacion = CONCAT(COALESCE(observacion,''), ' [Rechazo comprobante: ', :motivo, ']') WHERE id_pedido = :ped";
            $stmtUpdP = $this->conn->prepare($sqlUpdP);
            $stmtUpdP->bindParam(':motivo', $motivo);
            $stmtUpdP->bindParam(':ped', $verif['id_pedido'], PDO::PARAM_INT);
            $stmtUpdP->execute();

            $this->conn->commit();
            return ['ok' => true, 'mensaje' => 'Pago rechazado correctamente. El pedido fue marcado como Cancelado.'];

        } catch (Exception $e) {
            $this->conn->rollBack();
            return ['ok' => false, 'mensaje' => 'Error: ' . $e->getMessage()];
        }
    }
}
