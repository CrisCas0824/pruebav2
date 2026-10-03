<?php

require_once 'Core/dataBase.php';

class CheckoutModel
{
    private $conn;

    public function __construct(){
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function getDireccionesUsuario(int $idUsuario): array
    {
        $sql = "SELECT
                    d.id_direccion,
                    d.id_usuario,
                    d.departamento,
                    d.provincia,
                    d.distrito,
                    d.calle,
                    COALESCE(d.numero, '') AS numero,
                    COALESCE(d.lote_manzana, '') AS lote_manzana,
                    d.referencia,
                    COALESCE(d.id_distrito, 0) AS id_distrito,
                    COALESCE(dd.costo_delivery, 5.00) AS costo_delivery,
                    d.es_principal,
                    d.activo
                FROM direccion d
                LEFT JOIN distrito_delivery dd ON dd.id_distrito = d.id_distrito
                WHERE d.id_usuario = :id_usuario
                  AND d.activo = 1
                ORDER BY d.es_principal DESC, d.id_direccion DESC";

        $stmt = $this->conn->prepare($sql);

        $stmt->bindValue(
            ':id_usuario',
            $idUsuario,
            PDO::PARAM_INT
        );

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDireccionPrincipal(int $idUsuario)
    {
        $sql = "SELECT
                    d.id_direccion,
                    d.id_usuario,
                    d.departamento,
                    d.provincia,
                    d.distrito,
                    d.calle,
                    COALESCE(d.numero, '') AS numero,
                    COALESCE(d.lote_manzana, '') AS lote_manzana,
                    d.referencia,
                    COALESCE(d.id_distrito, 0) AS id_distrito,
                    COALESCE(dd.costo_delivery, 5.00) AS costo_delivery,
                    d.es_principal,
                    d.activo
                FROM direccion d
                LEFT JOIN distrito_delivery dd ON dd.id_distrito = d.id_distrito
                WHERE d.id_usuario = :id_usuario
                  AND d.activo = 1
                ORDER BY d.es_principal DESC, d.id_direccion DESC
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);

        $stmt->bindValue(
            ':id_usuario',
            $idUsuario,
            PDO::PARAM_INT
        );

        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function direccionPerteneceUsuario(
        int $idDireccion,
        int $idUsuario
    ): bool {
        $sql = "SELECT COUNT(*)
                FROM direccion
                WHERE id_direccion = :id_direccion
                  AND id_usuario = :id_usuario
                  AND activo = 1";

        $stmt = $this->conn->prepare($sql);

        $stmt->bindValue(
            ':id_direccion',
            $idDireccion,
            PDO::PARAM_INT
        );

        $stmt->bindValue(
            ':id_usuario',
            $idUsuario,
            PDO::PARAM_INT
        );

        $stmt->execute();

        return (int)$stmt->fetchColumn() > 0;
    }

    public function getMetodosPagoActivos(): array
    {
        $sql = "SELECT
                    id_metodo,
                    nombre,
                    descripcion,
                    activo,
                    COALESCE(codigo_qr, '') AS codigo_qr,
                    COALESCE(numero_cuenta, '') AS numero_cuenta,
                    COALESCE(titular, '') AS titular,
                    COALESCE(requiere_comprobante, 0) AS requiere_comprobante
                FROM metodo_pago
                WHERE activo = 1
                ORDER BY id_metodo ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDistritosDelivery(): array
    {
        $sql = "SELECT
                    id_distrito,
                    nombre,
                    costo_delivery
                FROM distrito_delivery
                WHERE activo = 1
                ORDER BY orden ASC, nombre ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getProductoCheckout(int $idProducto)
    {
        $sql = "SELECT
                    p.id_producto,
                    p.titulo,
                    p.descripcion,
                    p.precio,
                    p.imagen,
                    p.unidad_medida,
                    p.activo,
                    p.stock AS stock_producto,

                    i.stock_actual,
                    i.stock_minimo,
                    i.ubicacion,
                    COALESCE(i.stock_actual, p.stock) AS stock_disponible

                FROM producto p

                LEFT JOIN inventario i
                    ON i.id_producto = p.id_producto

                WHERE p.id_producto = :id_producto
                  AND p.activo = 1

                LIMIT 1";

        $stmt = $this->conn->prepare($sql);

        $stmt->bindValue(
            ':id_producto',
            $idProducto,
            PDO::PARAM_INT
        );

        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function construirResumenCarrito(array $carrito): array
    {
        $productos = [];
        $subtotal = 0.00;
        $cantidadTotal = 0.00;
        $productosNoDisponibles = [];

        foreach ($carrito as $item) {
            $idProducto = isset($item['id_producto'])
                ? (int)$item['id_producto']
                : 0;

            $cantidad = isset($item['cantidad'])
                ? (float)$item['cantidad']
                : 0;

            if ($idProducto <= 0 || $cantidad <= 0) {
                continue;
            }

            $producto = $this->getProductoCheckout($idProducto);

            if (!$producto) {
                $productosNoDisponibles[] = [
                    'id_producto' => $idProducto,
                    'motivo' => 'El producto ya no está disponible.'
                ];

                continue;
            }

            $stockActual = isset($producto['stock_disponible'])
                ? (float)$producto['stock_disponible']
                : 0.00;

            $precio = (float)$producto['precio'];
            $subtotalProducto = $precio * $cantidad;

            $tieneStock = $cantidad <= $stockActual;

            if (!$tieneStock) {
                $productosNoDisponibles[] = [
                    'id_producto' => $idProducto,
                    'titulo' => $producto['titulo'],
                    'cantidad_solicitada' => $cantidad,
                    'stock_disponible' => $stockActual,
                    'motivo' => 'Stock insuficiente.'
                ];
            }

            $productos[] = [
                'id_producto' => (int)$producto['id_producto'],
                'titulo' => $producto['titulo'],
                'descripcion' => $producto['descripcion'],
                'imagen' => $producto['imagen'],
                'unidad_medida' => $producto['unidad_medida'],
                'precio' => $precio,
                'cantidad' => $cantidad,
                'subtotal' => $subtotalProducto,
                'stock_actual'    => $stockActual,
                'tiene_stock'     => $tieneStock
            ];

            $subtotal += $subtotalProducto;
            $cantidadTotal += $cantidad;
        }

        return [
            'productos' => $productos,
            'cantidad_total' => $cantidadTotal,
            'subtotal' => round($subtotal, 2),
            'productos_no_disponibles' => $productosNoDisponibles,
            'carrito_valido' =>
                !empty($productos) &&
                empty($productosNoDisponibles)
        ];
    }
}