<?php
require_once __DIR__ . '/../Core/dataBase.php';

class DeliveryModel {
    private $conn;

    public function __construct() {
        $db = new DataBase();
        $this->conn = $db->getConnection();
    }

    public function listarEnvios($filtroEstado = null, $idRepartidor = null, $soloDisponibles = false) {
        $sql = "SELECT 
                    p.id_pedido,
                    p.codigo_pedido,
                    p.total AS pedido_total,
                    p.metodo_pago,
                    p.estado AS pedido_estado,
                    p.tipo_entrega,
                    CASE 
                        WHEN LOWER(p.tipo_entrega) = 'delivery' THEN 'delivery'
                        ELSE 'recojo'
                    END AS tipo_entrega_norm,
                    p.fecha AS pedido_fecha,
                    p.horario_entrega,
                    p.observacion,
                    COALESCE(ed.id_envio, 0) AS id_envio,
                    COALESCE(NULLIF(ed.nombre_destinatario, ''), NULLIF(TRIM(CONCAT(COALESCE(u.nombre,''), ' ', COALESCE(u.apellido,''))), ''), NULLIF(p.cliente_nombre, ''), 'Cliente') AS nombre_destinatario,
                    COALESCE(NULLIF(ed.celular_contacto, ''), NULLIF(u.telefono, ''), 'Sin cel.') AS celular_contacto,
                    COALESCE(
                        NULLIF(ed.direccion_destino, ''),
                        NULLIF(p.direccion_envio, ''),
                        CASE 
                            WHEN LOWER(p.tipo_entrega) = 'delivery' THEN 'Dirección de entrega no especificada'
                            ELSE 'Recojo en Tienda (Local Principal)'
                        END
                    ) AS direccion_destino,
                    COALESCE(ed.referencia, d.referencia, '') AS referencia,
                    COALESCE(ed.costo_delivery, p.costo_envio, 0.00) AS costo_delivery,
                    COALESCE(ed.estado_delivery, p.estado, 'Pendiente') AS estado_delivery,
                    ed.id_repartidor,
                    COALESCE(ed.cobrado_por_repartidor, 0) AS cobrado_por_repartidor,
                    COALESCE(ed.liquidado_en_caja, 0) AS liquidado_en_caja,
                    COALESCE(ed.notas_entrega, '') AS notas_entrega,
                    u.nombre AS cliente_nombre,
                    u.apellido AS cliente_apellido,
                    u.correo AS cliente_correo,
                    u.telefono AS cliente_telefono,
                    r.nombre AS repartidor_nombre,
                    r.apellido AS repartidor_apellido,
                    r.telefono AS repartidor_telefono
                FROM pedido p
                LEFT JOIN envio_delivery ed ON p.id_pedido = ed.id_pedido
                LEFT JOIN direccion d ON p.id_direccion = d.id_direccion
                LEFT JOIN usuario u ON p.id_usuario = u.id_usuario
                LEFT JOIN usuario r ON ed.id_repartidor = r.id_usuario
                WHERE (p.canal != 'POS' OR p.canal IS NULL OR ed.id_envio IS NOT NULL)";
        $params = [];
        if ($filtroEstado) {
            $sql .= " AND (ed.estado_delivery = :est OR (ed.estado_delivery IS NULL AND p.estado = :est))";
            $params[':est'] = $filtroEstado;
        }
        if ($idRepartidor !== null) {
            $sql .= " AND ed.id_repartidor = :id_repartidor";
            $params[':id_repartidor'] = (int)$idRepartidor;
        }
        if ($soloDisponibles) {
            $sql .= " AND ed.id_envio IS NOT NULL AND ed.id_repartidor IS NULL AND ed.estado_delivery = 'Pendiente' AND LOWER(p.tipo_entrega) = 'delivery'";
        }
        $sql .= " ORDER BY p.id_pedido DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarRepartidores() {
        $sql = "SELECT id_usuario, nombre, apellido, telefono, correo 
                FROM usuario 
                WHERE rol IN ('repartidor', 'cajero', 'admin') AND activo = 1 
                ORDER BY nombre ASC";
        $stmt = $this->conn->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function asignarRepartidor($idEnvio, $idRepartidor) {
        $sql = "UPDATE envio_delivery SET 
                    id_repartidor = :rep, 
                    estado_delivery = 'Asignado',
                    fecha_asignacion = NOW() 
                WHERE id_envio = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':rep', $idRepartidor, PDO::PARAM_INT);
        $stmt->bindParam(':id', $idEnvio, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function tomarEnvioDisponible($idEnvio, $idRepartidor) {
        $sql = "UPDATE envio_delivery
                SET id_repartidor = :repartidor, estado_delivery = 'Asignado', fecha_asignacion = NOW()
                WHERE id_envio = :envio AND id_repartidor IS NULL AND estado_delivery = 'Pendiente'";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':repartidor', (int)$idRepartidor, PDO::PARAM_INT);
        $stmt->bindValue(':envio', (int)$idEnvio, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() === 1;
    }

    public function actualizarEstadoDelivery($idEnvio, $nuevoEstado, $cobrado = null, $liquidado = null, $notas = '', $idPedido = 0) {
        $idEnvio = (int)$idEnvio;
        $idPedido = (int)$idPedido;
        $res = false;

        // Si existe registro en envio_delivery, actualizarlo
        if ($idEnvio > 0) {
            $sql = "UPDATE envio_delivery SET 
                        estado_delivery = :est,
                        notas_entrega = CONCAT(COALESCE(notas_entrega, ''), '\n', :notas)";
            if ($nuevoEstado === 'Entregado') {
                $sql .= ", fecha_entrega = NOW()";
            }
            if ($cobrado !== null) {
                $sql .= ", cobrado_por_repartidor = :cobrado";
            }
            if ($liquidado !== null) {
                $sql .= ", liquidado_en_caja = :liquidado";
            }
            $sql .= " WHERE id_envio = :id";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':est', $nuevoEstado);
            $stmt->bindParam(':notas', $notas);
            $stmt->bindParam(':id', $idEnvio, PDO::PARAM_INT);
            if ($cobrado !== null) $stmt->bindParam(':cobrado', $cobrado, PDO::PARAM_INT);
            if ($liquidado !== null) $stmt->bindParam(':liquidado', $liquidado, PDO::PARAM_INT);
            $res = $stmt->execute();
        }

        // Actualizar tabla pedido
        if ($idPedido > 0) {
            $estadoPedido = $nuevoEstado;
            if ($nuevoEstado === 'En Ruta' || $nuevoEstado === 'Asignado') {
                $estadoPedido = 'En Camino';
            } elseif ($nuevoEstado === 'Fallido') {
                $estadoPedido = 'Cancelado';
            }

            $sqlP = "UPDATE pedido SET estado = :est";
            if (!empty($notas)) {
                $sqlP .= ", observacion = CONCAT(COALESCE(observacion, ''), ' | ', :notas)";
            }
            $sqlP .= " WHERE id_pedido = :id_ped";
            $stmtP = $this->conn->prepare($sqlP);
            $stmtP->bindValue(':est', $estadoPedido);
            if (!empty($notas)) {
                $stmtP->bindValue(':notas', $notas);
            }
            $stmtP->bindValue(':id_ped', $idPedido, PDO::PARAM_INT);
            $res = $stmtP->execute();
        } elseif ($idEnvio > 0) {
            // Sincronizar por id_envio si no se pasó id_pedido
            if ($nuevoEstado === 'Entregado') {
                $this->conn->query("UPDATE pedido p JOIN envio_delivery ed ON p.id_pedido = ed.id_pedido SET p.estado = 'Entregado' WHERE ed.id_envio = {$idEnvio}");
            } elseif ($nuevoEstado === 'En Ruta') {
                $this->conn->query("UPDATE pedido p JOIN envio_delivery ed ON p.id_pedido = ed.id_pedido SET p.estado = 'En Camino' WHERE ed.id_envio = {$idEnvio}");
            } elseif ($nuevoEstado === 'Fallido') {
                $this->conn->query("UPDATE pedido p JOIN envio_delivery ed ON p.id_pedido = ed.id_pedido SET p.estado = 'Cancelado' WHERE ed.id_envio = {$idEnvio}");
            }
        }

        return $res;
    }
}
