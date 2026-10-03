<?php
require_once __DIR__ . '/../Core/dataBase.php';

class ReportesModel {
    private $conn;

    public function __construct() {
        $db = new DataBase();
        $this->conn = $db->getConnection();
    }

    public function obtenerMetricasFinancieras() {
        // 1. Total Ventas (excluyendo cancelados)
        $sqlVentas = "SELECT 
                        COALESCE(SUM(total), 0) as total_ventas,
                        COUNT(*) as total_pedidos,
                        COALESCE(SUM(CASE WHEN canal = 'POS' THEN total ELSE 0 END), 0) as ventas_pos,
                        COALESCE(SUM(CASE WHEN canal = 'WEB' THEN total ELSE 0 END), 0) as ventas_web,
                        COALESCE(SUM(CASE WHEN DATE(fecha) = CURDATE() THEN total ELSE 0 END), 0) as ventas_hoy
                      FROM pedido 
                      WHERE estado NOT IN ('Cancelado')";
        $ventas = $this->conn->query($sqlVentas)->fetch(PDO::FETCH_ASSOC);

        // 2. Costo de Mercadería Vendida (COGS)
        $sqlCogs = "SELECT 
                        COALESCE(SUM(dp.cantidad * COALESCE(NULLIF(dp.costo_unitario, 0), p.precio_costo, 0)), 0) as cogs_total
                    FROM detalle_pedido dp
                    JOIN pedido ped ON dp.id_pedido = ped.id_pedido
                    JOIN producto p ON dp.id_producto = p.id_producto
                    WHERE ped.estado NOT IN ('Cancelado')";
        $cogsData = $this->conn->query($sqlCogs)->fetch(PDO::FETCH_ASSOC);
        $cogs = (float)$cogsData['cogs_total'];

        // 3. Gastos Operativos y Egresos de Caja Chica
        $sqlEgresos = "SELECT 
                        COALESCE(SUM(monto), 0) as total_egresos,
                        COALESCE(SUM(CASE WHEN categoria = 'insumos' THEN monto ELSE 0 END), 0) as egresos_insumos,
                        COALESCE(SUM(CASE WHEN categoria = 'servicios' THEN monto ELSE 0 END), 0) as egresos_servicios,
                        COALESCE(SUM(CASE WHEN categoria = 'proveedor' THEN monto ELSE 0 END), 0) as egresos_proveedor,
                        COALESCE(SUM(CASE WHEN categoria = 'otro' THEN monto ELSE 0 END), 0) as egresos_otros
                       FROM movimiento_caja 
                       WHERE tipo = 'EGRESO'";
        $egresosData = $this->conn->query($sqlEgresos)->fetch(PDO::FETCH_ASSOC);
        $egresos = (float)$egresosData['total_egresos'];

        // 4. Ganancia Bruta y Ganancia Neta
        $totalVentas = (float)$ventas['total_ventas'];
        $gananciaBruta = $totalVentas - $cogs;
        $gananciaNeta = $gananciaBruta - $egresos;
        $margenNeto = $totalVentas > 0 ? round(($gananciaNeta / $totalVentas) * 100, 1) : 0;

        // 5. Ventas por Método de Pago
        $sqlMetodos = "SELECT 
                        COALESCE(NULLIF(metodo_pago, ''), 'Efectivo') as metodo,
                        COUNT(*) as cantidad_transacciones,
                        COALESCE(SUM(total), 0) as total_recaudado
                       FROM pedido
                       WHERE estado NOT IN ('Cancelado')
                       GROUP BY metodo
                       ORDER BY total_recaudado DESC";
        $metodos = $this->conn->query($sqlMetodos)->fetchAll(PDO::FETCH_ASSOC);

        // 6. Ventas por Empleado / Cajero
        $sqlCajeros = "SELECT 
                        u.id_usuario,
                        CONCAT(u.nombre, ' ', COALESCE(u.apellido, '')) as empleado_nombre,
                        u.rol,
                        COUNT(p.id_pedido) as tickets_atendidos,
                        COALESCE(SUM(p.total), 0) as total_vendido
                       FROM usuario u
                       LEFT JOIN pedido p ON (p.id_cajero = u.id_usuario OR (p.canal = 'POS' AND p.id_usuario = u.id_usuario)) AND p.estado NOT IN ('Cancelado')
                       WHERE u.rol IN ('cajero', 'admin')
                       GROUP BY u.id_usuario, u.nombre, u.apellido, u.rol
                       ORDER BY total_vendido DESC";
        $cajeros = $this->conn->query($sqlCajeros)->fetchAll(PDO::FETCH_ASSOC);

        // 7. Top 5 Productos más vendidos y rentables
        $sqlTop = "SELECT 
                    p.id_producto,
                    p.nombre,
                    p.codigo,
                    p.precio_costo,
                    p.precio,
                    SUM(dp.cantidad) as total_unidades_vendidas,
                    SUM(dp.subtotal) as total_ingresos,
                    SUM(dp.cantidad * (dp.precio_unitario - COALESCE(NULLIF(dp.costo_unitario, 0), p.precio_costo, 0))) as ganancia_aportada
                   FROM detalle_pedido dp
                   JOIN pedido ped ON dp.id_pedido = ped.id_pedido
                   JOIN producto p ON dp.id_producto = p.id_producto
                   WHERE ped.estado NOT IN ('Cancelado')
                   GROUP BY p.id_producto, p.nombre, p.codigo, p.precio_costo, p.precio
                   ORDER BY total_unidades_vendidas DESC LIMIT 5";
        $topProductos = $this->conn->query($sqlTop)->fetchAll(PDO::FETCH_ASSOC);

        return [
            'total_ventas' => $totalVentas,
            'total_pedidos' => (int)$ventas['total_pedidos'],
            'ventas_pos' => (float)$ventas['ventas_pos'],
            'ventas_web' => (float)$ventas['ventas_web'],
            'ventas_hoy' => (float)$ventas['ventas_hoy'],
            'cogs' => $cogs,
            'ganancia_bruta' => $gananciaBruta,
            'total_egresos' => $egresos,
            'egresos_detalle' => $egresosData,
            'ganancia_neta' => $gananciaNeta,
            'margen_neto' => $margenNeto,
            'metodos_pago' => $metodos,
            'ventas_cajeros' => $cajeros,
            'top_productos' => $topProductos
        ];
    }
}
