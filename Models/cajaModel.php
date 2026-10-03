<?php
require_once __DIR__ . '/../Core/dataBase.php';

class CajaModel {
    private $conn;

    public function __construct() {
        $db = new DataBase();
        $this->conn = $db->getConnection();
    }

    /**
     * Obtiene la sesión de caja actualmente abierta para el usuario o caja
     */
    public function obtenerSesionActiva($idUsuario = null, $cajaId = 'CAJA-01') {
        $sql = "SELECT s.*, u.nombre as cajero_nombre, u.apellido as cajero_apellido 
                FROM sesion_caja s
                JOIN usuario u ON s.id_usuario = u.id_usuario
                WHERE s.estado = 'abierta'";
        $params = [];
        if ($idUsuario !== null) {
            $sql .= " AND s.id_usuario = :id_usuario";
            $params[':id_usuario'] = $idUsuario;
        } else {
            $sql .= " AND s.caja_id = :caja_id";
            $params[':caja_id'] = $cajaId;
        }
        $sql .= " ORDER BY s.id_sesion DESC LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Apertura de nueva sesión de caja
     */
    public function abrirCaja($idUsuario, $montoApertura, $cajaId = 'CAJA-01') {
        // Verificar si ya hay una abierta
        $activa = $this->obtenerSesionActiva(null, $cajaId);
        if ($activa) {
            return ['ok' => false, 'mensaje' => 'Ya existe una sesión de caja abierta (' . $activa['caja_id'] . '). Debe cerrarla primero.'];
        }

        $token = bin2hex(random_bytes(16));
        $sql = "INSERT INTO sesion_caja (caja_id, id_usuario, token_emparejamiento, estado_conexion, monto_apertura, total_venta, estado, fecha_apertura) 
                VALUES (:caja_id, :id_usuario, :token, 'esperando', :monto_apertura, 0.00, 'abierta', NOW())";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':caja_id', $cajaId);
        $stmt->bindParam(':id_usuario', $idUsuario, PDO::PARAM_INT);
        $stmt->bindParam(':token', $token);
        $stmt->bindParam(':monto_apertura', $montoApertura);
        
        if ($stmt->execute()) {
            return ['ok' => true, 'id_sesion' => $this->conn->lastInsertId(), 'mensaje' => 'Caja aperturada exitosamente'];
        }
        return ['ok' => false, 'mensaje' => 'Error al aperturar la caja en la base de datos'];
    }

    /**
     * Registra un movimiento de caja chica (Ingreso o Egreso)
     */
    public function registrarMovimiento($idSesion, $idUsuario, $tipo, $categoria, $monto, $motivo, $comprobante = '') {
        $monto = (float)$monto;
        if ($monto <= 0) {
            return ['ok' => false, 'mensaje' => 'El monto debe ser mayor a 0'];
        }

        $sql = "INSERT INTO movimiento_caja (id_sesion, id_usuario, tipo, categoria, monto, motivo, comprobante_ref, fecha)
                VALUES (:id_sesion, :id_usuario, :tipo, :categoria, :monto, :motivo, :comprobante, NOW())";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id_sesion', $idSesion, PDO::PARAM_INT);
        $stmt->bindParam(':id_usuario', $idUsuario, PDO::PARAM_INT);
        $stmt->bindParam(':tipo', $tipo);
        $stmt->bindParam(':categoria', $categoria);
        $stmt->bindParam(':monto', $monto);
        $stmt->bindParam(':motivo', $motivo);
        $stmt->bindParam(':comprobante', $comprobante);

        if ($stmt->execute()) {
            // Actualizar acumuladores en la sesión
            if ($tipo === 'INGRESO') {
                $upd = "UPDATE sesion_caja SET total_ingresos = total_ingresos + :monto WHERE id_sesion = :id";
            } else {
                $upd = "UPDATE sesion_caja SET total_egresos = total_egresos + :monto WHERE id_sesion = :id";
            }
            $stmtUpd = $this->conn->prepare($upd);
            $stmtUpd->bindParam(':monto', $monto);
            $stmtUpd->bindParam(':id', $idSesion, PDO::PARAM_INT);
            $stmtUpd->execute();

            return ['ok' => true, 'mensaje' => 'Movimiento de ' . $tipo . ' registrado con éxito'];
        }
        return ['ok' => false, 'mensaje' => 'No se pudo registrar el movimiento'];
    }

    /**
     * Lista los movimientos de una sesión
     */
    public function listarMovimientos($idSesion) {
        $sql = "SELECT m.*, u.nombre as usuario_nombre, u.apellido as usuario_apellido 
                FROM movimiento_caja m
                JOIN usuario u ON m.id_usuario = u.id_usuario
                WHERE m.id_sesion = :id_sesion
                ORDER BY m.id_movimiento DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id_sesion', $idSesion, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Calcula el arqueo esperado de la sesión de caja
     */
    public function calcularResumenCaja($idSesion) {
        $sqlSesion = "SELECT * FROM sesion_caja WHERE id_sesion = :id";
        $stmt = $this->conn->prepare($sqlSesion);
        $stmt->bindParam(':id', $idSesion, PDO::PARAM_INT);
        $stmt->execute();
        $sesion = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$sesion) return null;

        $cierreCondition = !empty($sesion['fecha_cierre']) ? " AND fecha <= :fecha_cierre" : "";
        $sqlVentas = "SELECT 
                        COALESCE(SUM(CASE WHEN LOWER(metodo_pago) LIKE '%efectivo%' THEN total ELSE 0 END), 0) as efectivo,
                        COALESCE(SUM(CASE WHEN LOWER(metodo_pago) LIKE '%yape%' THEN total ELSE 0 END), 0) as yape,
                        COALESCE(SUM(CASE WHEN LOWER(metodo_pago) LIKE '%plin%' THEN total ELSE 0 END), 0) as plin,
                        COALESCE(SUM(CASE WHEN LOWER(metodo_pago) LIKE '%tarjeta%' THEN total ELSE 0 END), 0) as tarjeta,
                        COALESCE(SUM(total), 0) as total_ventas,
                        COUNT(*) as total_tickets
                      FROM pedido 
                      WHERE (id_sesion_caja = :id OR (canal = 'POS' AND fecha >= :fecha_apertura {$cierreCondition}))
                        AND estado NOT IN ('Cancelado')";
        $stmtV = $this->conn->prepare($sqlVentas);
        $stmtV->bindParam(':id', $idSesion, PDO::PARAM_INT);
        $stmtV->bindParam(':fecha_apertura', $sesion['fecha_apertura']);
        if (!empty($sesion['fecha_cierre'])) {
            $stmtV->bindParam(':fecha_cierre', $sesion['fecha_cierre']);
        }
        $stmtV->execute();
        $ventas = $stmtV->fetch(PDO::FETCH_ASSOC);

        // Movimientos
        $sqlMov = "SELECT 
                    COALESCE(SUM(CASE WHEN tipo = 'INGRESO' THEN monto ELSE 0 END), 0) as ingresos_extra,
                    COALESCE(SUM(CASE WHEN tipo = 'EGRESO' THEN monto ELSE 0 END), 0) as egresos
                   FROM movimiento_caja WHERE id_sesion = :id";
        $stmtM = $this->conn->prepare($sqlMov);
        $stmtM->bindParam(':id', $idSesion, PDO::PARAM_INT);
        $stmtM->execute();
        $movs = $stmtM->fetch(PDO::FETCH_ASSOC);

        $apertura = (float)$sesion['monto_apertura'];
        $ventasEfectivo = (float)$ventas['efectivo'];
        $ingresosExtra = (float)$movs['ingresos_extra'];
        $egresos = (float)$movs['egresos'];

        // Saldo esperado en efectivo físico en gaveta:
        $efectivoEsperado = $apertura + $ventasEfectivo + $ingresosExtra - $egresos;

        return [
            'sesion' => $sesion,
            'apertura' => $apertura,
            'ventas_efectivo' => $ventasEfectivo,
            'ventas_yape' => (float)$ventas['yape'],
            'ventas_plin' => (float)$ventas['plin'],
            'ventas_tarjeta' => (float)$ventas['tarjeta'],
            'total_ventas' => (float)$ventas['total_ventas'],
            'total_tickets' => (int)($ventas['total_tickets'] ?? 0),
            'ingresos_extra' => $ingresosExtra,
            'egresos' => $egresos,
            'efectivo_esperado' => $efectivoEsperado
        ];
    }

    /**
     * Cierre de caja con Arqueo
     */
    public function cerrarCaja($idSesion, $montoRealContado) {
        $resumen = $this->calcularResumenCaja($idSesion);
        if (!$resumen) {
            return ['ok' => false, 'mensaje' => 'Sesión no encontrada'];
        }

        $esperado = $resumen['efectivo_esperado'];
        $diferencia = (float)$montoRealContado - $esperado;

        $sql = "UPDATE sesion_caja SET
                    monto_cierre_esperado = :esperado,
                    monto_cierre_real = :real,
                    diferencia = :diferencia,
                    total_ventas_efectivo = :v_efectivo,
                    total_ventas_yape = :v_yape,
                    total_ventas_plin = :v_plin,
                    total_ventas_tarjeta = :v_tarjeta,
                    total_ingresos = :ingresos,
                    total_egresos = :egresos,
                    estado = 'cerrada',
                    estado_conexion = 'cerrado',
                    fecha_cierre = NOW()
                WHERE id_sesion = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':esperado', $esperado);
        $stmt->bindParam(':real', $montoRealContado);
        $stmt->bindParam(':diferencia', $diferencia);
        $stmt->bindParam(':v_efectivo', $resumen['ventas_efectivo']);
        $stmt->bindParam(':v_yape', $resumen['ventas_yape']);
        $stmt->bindParam(':v_plin', $resumen['ventas_plin']);
        $stmt->bindParam(':v_tarjeta', $resumen['ventas_tarjeta']);
        $stmt->bindParam(':ingresos', $resumen['ingresos_extra']);
        $stmt->bindParam(':egresos', $resumen['egresos']);
        $stmt->bindParam(':id', $idSesion, PDO::PARAM_INT);

        if ($stmt->execute()) {
            return [
                'ok' => true,
                'mensaje' => 'Caja cerrada exitosamente',
                'esperado' => $esperado,
                'real' => $montoRealContado,
                'diferencia' => $diferencia
            ];
        }
        return ['ok' => false, 'mensaje' => 'Error al cerrar la sesión de caja'];
    }

    /**
     * Historial de sesiones de caja
     */
    public function listarHistorialSesiones($limite = 20) {
        $sql = "SELECT s.*, u.nombre as cajero_nombre, u.apellido as cajero_apellido 
                FROM sesion_caja s
                JOIN usuario u ON s.id_usuario = u.id_usuario
                ORDER BY s.id_sesion DESC LIMIT :limite";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':limite', (int)$limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarHistorialSesionesUsuario($idUsuario, $limite = 20) {
        $sql = "SELECT s.*, u.nombre AS cajero_nombre, u.apellido AS cajero_apellido
                FROM sesion_caja s
                JOIN usuario u ON s.id_usuario = u.id_usuario
                WHERE s.id_usuario = :id_usuario
                ORDER BY s.id_sesion DESC
                LIMIT :limite";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id_usuario', (int)$idUsuario, PDO::PARAM_INT);
        $stmt->bindValue(':limite', (int)$limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
