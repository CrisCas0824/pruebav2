<?php
require_once 'Models/checkoutModel.php';

class ControllerCheckout{
    private $checkoutModel;

    public function __construct(){
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->checkoutModel = new CheckoutModel();
    }

    private function validarSesion(){
        if (!isset($_SESSION['usuario_id'])) {
            header('Location: index.php?action=login');
            exit();
        }
    }

    private function generarCsrfToken(){
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    public function mostrarCheckout(){
        $this->validarSesion();

        $idUsuario = (int)$_SESSION['usuario_id'];

        $carrito = isset($_SESSION['carritos'][$idUsuario]) && is_array($_SESSION['carritos'][$idUsuario])
                ? $_SESSION['carritos'][$idUsuario] : [];

        if (empty($carrito)){
            $_SESSION['flash_error'] = 'Tu carrito está vacío. Agrega productos antes de continuar.';

            header('Location: index.php?action=home_usuario');
            exit();
        }

        $direcciones = $this->checkoutModel->getDireccionesUsuario($idUsuario);
        $direccionSeleccionada = $this->checkoutModel->getDireccionPrincipal($idUsuario);
        $metodosPago = $this->checkoutModel->getMetodosPagoActivos();
        $resumenCarrito = $this->checkoutModel->construirResumenCarrito($carrito);
        $productosCheckout = $resumenCarrito['productos'];
        $productosNoDisponibles = $resumenCarrito['productos_no_disponibles'];
        $carritoValido = $resumenCarrito['carrito_valido'];
        $cantidadTotal = (float)$resumenCarrito['cantidad_total'];
        $subtotal = (float)$resumenCarrito['subtotal'];

        $distritos = $this->checkoutModel->getDistritosDelivery();

        $tipoEntrega = 'delivery';
        $costoEnvio = 5.00;

        // Usar el costo del distrito de la dirección principal si está disponible
        if ($direccionSeleccionada && !empty($direccionSeleccionada['costo_delivery'])) {
            $costoEnvio = (float)$direccionSeleccionada['costo_delivery'];
        }

        $total = $subtotal + $costoEnvio;
        $csrf_token = $this->generarCsrfToken();

        $nombreUsuario = trim(
            ($_SESSION['usuario_nombre'] ?? 'Usuario')
        );

        include 'Views/Checkout/checkout.php';
    }
}