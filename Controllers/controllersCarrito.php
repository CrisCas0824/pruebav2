<?php
require_once 'Models/productModel.php';

class ControllerCarrito{
    private $productModel;

    public function __construct(){
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $this->productModel = new ProductoModel();

        if (!isset($_SESSION['carritos'])) {
            $_SESSION['carritos'] = [];
        }
    }

    private function getCarritoActual() {
        $userId = $_SESSION['usuario_id'] ?? 'invitado';
        if (!isset($_SESSION['carritos'][$userId])) {
            $_SESSION['carritos'][$userId] = [];
        }
        return $_SESSION['carritos'][$userId];
    }

    private function responderJson($success, $message, $data = [], $status = 200){
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => $success,'message' => $message, 'data' => $data]);
        exit();
    }

    public function agregar(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->responderJson(false, 'Método no permitido.', [], 405);
        }

        $idProducto = filter_input(INPUT_POST, 'id_producto', FILTER_VALIDATE_INT);
        $cantidad = filter_input(INPUT_POST, 'cantidad',FILTER_VALIDATE_INT);
        $cantidad = $cantidad ?: 1;

        if (!$idProducto || $cantidad < 1) {
            $this->responderJson(false, 'Producto o cantidad inválida.', [], 422);
        }

        $producto = $this->productModel->getProductoById($idProducto);

        if (!$producto) {
            $this->responderJson(false, 'El producto no existe.', [], 404);
        }

        if ((int)$producto['activo'] !== 1) {
            $this->responderJson(false, 'El producto no está disponible.', [], 422);
        }

        $stockDisponible = (float)$producto['stock'];
        $carrito = $this->getCarritoActual();
        $cantidadActual = isset($carrito[$idProducto])? (float)$carrito[$idProducto]['cantidad']: 0;

        $nuevaCantidad = $cantidadActual + $cantidad;

        if ($nuevaCantidad > $stockDisponible) {
            $this->responderJson(
                false,
                "Límite alcanzado. Solo quedan " . $stockDisponible . " unidades disponibles.",
                [
                    'stock_disponible' => $stockDisponible
                ],
                422
            );
        }

        $carrito[$idProducto] = [
            'id_producto' => (int)$producto['id_producto'],
            'cantidad' => $nuevaCantidad
        ];

        $userId = $_SESSION['usuario_id'] ?? 'invitado';
        $_SESSION['carritos'][$userId] = $carrito;

        $this->responderJson(
            true,
            'Producto agregado al carrito.',
            $this->obtenerResumenCarrito()
        );
    }

    public function listar(){
        $this->responderJson(true, 'Carrito cargado.', $this->obtenerResumenCarrito());
    }

    public function actualizarCantidad(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->responderJson(false, 'Método no permitido.', [], 405);
        }

        $idProducto = filter_input(INPUT_POST, 'id_producto', FILTER_VALIDATE_INT);
        $cantidad = filter_input(INPUT_POST, 'cantidad', FILTER_VALIDATE_INT);

        if (!$idProducto || $cantidad === false || $cantidad === null || $cantidad < 0) {
            $this->responderJson(false, 'Cantidad inválida.', [], 422);
        }

        $carrito = $this->getCarritoActual();

        if (!isset($carrito[$idProducto])) {
            $this->responderJson(false, 'Producto no encontrado en el carrito.', [], 404);
        }

        if ($cantidad === 0) {
            unset($carrito[$idProducto]);
            $userId = $_SESSION['usuario_id'] ?? 'invitado';
            $_SESSION['carritos'][$userId] = $carrito;
            $this->responderJson(true, 'Producto eliminado.', $this->obtenerResumenCarrito());
        }

        $producto = $this->productModel->getProductoById($idProducto);

        if (!$producto) {
            $this->responderJson(false, 'El producto no existe.', [], 404);
        }

        if ($cantidad > (float)$producto['stock']) {
            $this->responderJson(
                false,
                "Límite alcanzado. Solo quedan " . (float)$producto['stock'] . " unidades disponibles.",
                ['stock_disponible' => (float)$producto['stock']],
                422
            );
        }

        $carrito[$idProducto]['cantidad'] = $cantidad;
        $userId = $_SESSION['usuario_id'] ?? 'invitado';
        $_SESSION['carritos'][$userId] = $carrito;

        $this->responderJson(true, 'Cantidad actualizada.', $this->obtenerResumenCarrito());
    }

    public function eliminar(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->responderJson(false, 'Método no permitido.', [], 405);
        }

        $idProducto = filter_input(INPUT_POST, 'id_producto', FILTER_VALIDATE_INT);

        if (!$idProducto) {
            $this->responderJson(false, 'Producto inválido.', [], 422);
        }

        $carrito = $this->getCarritoActual();
        unset($carrito[$idProducto]);

        $userId = $_SESSION['usuario_id'] ?? 'invitado';
        $_SESSION['carritos'][$userId] = $carrito;

        $this->responderJson(
            true,
            'Producto eliminado del carrito.',
            $this->obtenerResumenCarrito()
        );
    }

    public function vaciar(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->responderJson(false, 'Método no permitido.', [], 405);
        }

        $userId = $_SESSION['usuario_id'] ?? 'invitado';
        $_SESSION['carritos'][$userId] = [];

        $this->responderJson(
            true,
            'Carrito vaciado correctamente.',
            $this->obtenerResumenCarrito()
        );
    }

    private function obtenerResumenCarrito(){
        $items = [];
        $subtotal = 0;
        $cantidadTotal = 0;

        $carrito = $this->getCarritoActual();
        
        foreach ($carrito as $item){
            $producto = $this->productModel->getProductoById(
                $item['id_producto']
            );

            if (!$producto || (int)$producto['activo'] !== 1) {
                continue;
            }

            $cantidad = (float)$item['cantidad'];
            $precio = (float)$producto['precio'];
            $subtotalItem = $precio * $cantidad;

            $items[] = [
                'id_producto' => (int)$producto['id_producto'],
                'titulo' => $producto['titulo'],
                'imagen' => $producto['imagen'],
                'precio' => $precio,
                'cantidad' => $cantidad,
                'subtotal' => $subtotalItem,
                'unidad_medida' => $producto['unidad_medida'],
                'stock' => (float)$producto['stock']
            ];

            $subtotal += $subtotalItem;
            $cantidadTotal += $cantidad;
        }

        return [
            'items' => $items,
            'cantidad_total' => $cantidadTotal,
            'subtotal' => $subtotal,
            'entrega' => 0,
            'total' => $subtotal
        ];
    }
}