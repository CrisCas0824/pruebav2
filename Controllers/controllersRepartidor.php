<?php
require_once __DIR__ . '/../Models/deliveryModel.php';
require_once __DIR__ . '/../Core/ajaxHelper.php';

class ControllerRepartidor {
    private $deliveryModel;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->deliveryModel = new DeliveryModel();
    }

    private function validarRepartidor() {
        if (($_SESSION['usuario_rol'] ?? '') !== 'repartidor') {
            header('Location: index.php?action=home_usuario');
            exit();
        }
    }

    public function panel() {
        $this->validarRepartidor();
        $envios = $this->deliveryModel->listarEnvios(null, (int)$_SESSION['usuario_id']);
        $enviosDisponibles = $this->deliveryModel->listarEnvios(null, null, true);
        $nombre = htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Repartidor');
        $csrfToken = $_SESSION['csrf_token'] ?? '';
        include __DIR__ . '/../Views/Repartidor/panel.php';
    }

    public function tomarEntrega() {
        $this->validarRepartidor();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit();
        }

        $csrfToken = $_POST['csrf_token'] ?? '';
        if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], (string)$csrfToken)) {
            $_SESSION['flash_error'] = 'La sesión de seguridad expiró. Recarga el panel.';
            header('Location: index.php?action=panel_repartidor');
            exit();
        }

        $idEnvio = filter_input(INPUT_POST, 'id_envio', FILTER_VALIDATE_INT);
        $ok = $idEnvio && $this->deliveryModel->tomarEnvioDisponible((int)$idEnvio, (int)$_SESSION['usuario_id']);
        $_SESSION[$ok ? 'flash_success' : 'flash_error'] = $ok
            ? 'Pedido agregado a tus entregas.'
            : 'Este pedido ya fue tomado o no está disponible.';
        header('Location: index.php?action=panel_repartidor');
        exit();
    }

    public function actualizarEntrega() {
        $this->validarRepartidor();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit();
        }

        $csrfToken = $_POST['csrf_token'] ?? '';
        if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], (string)$csrfToken)) {
            if (AjaxHelper::isAjax()) AjaxHelper::jsonError('La sesión de seguridad expiró. Recarga el panel.', 419);
            $_SESSION['flash_error'] = 'La sesión de seguridad expiró. Recarga el panel.';
            header('Location: index.php?action=panel_repartidor');
            exit();
        }

        $idEnvio = filter_input(INPUT_POST, 'id_envio', FILTER_VALIDATE_INT);
        $nuevoEstado = trim($_POST['nuevo_estado'] ?? '');
        $estadosPermitidos = ['En Ruta', 'Entregado', 'Fallido'];
        if (!$idEnvio || !in_array($nuevoEstado, $estadosPermitidos, true)) {
            if (AjaxHelper::isAjax()) AjaxHelper::jsonError('Datos de entrega no válidos.', 422);
            $_SESSION['flash_error'] = 'No se pudo actualizar la entrega.';
            header('Location: index.php?action=panel_repartidor');
            exit();
        }

        $enviosPropios = $this->deliveryModel->listarEnvios(null, (int)$_SESSION['usuario_id']);
        $envioPropio = null;
        foreach ($enviosPropios as $envio) {
            if ((int)$envio['id_envio'] === (int)$idEnvio) {
                $envioPropio = $envio;
                break;
            }
        }
        if (!$envioPropio) {
            if (AjaxHelper::isAjax()) AjaxHelper::jsonError('Esta entrega no está asignada a tu cuenta.', 403);
            $_SESSION['flash_error'] = 'Esta entrega no está asignada a tu cuenta.';
            header('Location: index.php?action=panel_repartidor');
            exit();
        }

        $ok = $this->deliveryModel->actualizarEstadoDelivery(
            (int)$idEnvio,
            $nuevoEstado,
            null,
            null,
            trim($_POST['notas'] ?? ''),
            (int)$envioPropio['id_pedido']
        );
        if (AjaxHelper::isAjax()) {
            if ($ok) AjaxHelper::jsonResponse(true, 'Entrega actualizada: ' . $nuevoEstado);
            AjaxHelper::jsonError('No se pudo actualizar la entrega.', 500);
        }

        $_SESSION[$ok ? 'flash_success' : 'flash_error'] = $ok
            ? 'Entrega actualizada: ' . $nuevoEstado
            : 'No se pudo actualizar la entrega.';
        header('Location: index.php?action=panel_repartidor');
        exit();
    }
}
