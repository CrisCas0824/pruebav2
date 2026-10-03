<?php
require_once 'Models/pedidoModel.php';
require_once 'Core/ajaxHelper.php';

class ControllerPedido{
    private $pedidoModel;

    public function __construct(){
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->pedidoModel = new PedidoModel();
    }

    private function validarSesion(){
        if (!isset($_SESSION['usuario_id'])) {
            if (AjaxHelper::isAjax()) {
                AjaxHelper::jsonError(
                    'Tu sesión ha expirado.',
                    401
                );
            }

            header('Location: index.php?action=login');
            exit();
        }
    }

    public function listarPedidosUsuario(){
        $this->validarSesion();

        $idUsuario =
            (int)$_SESSION['usuario_id'];

        $pedidos = $this->pedidoModel
            ->getPedidosByUsuario($idUsuario);

        if (AjaxHelper::isAjax()) {
            AjaxHelper::jsonResponse(
                true,
                'Pedidos cargados correctamente.',
                [
                    'pedidos' => $pedidos
                ]
            );
        }

        include 'Views/Cuenta/partials/pedidos.php';
    }

    public function detallePedidoUsuario(){
        $this->validarSesion();

        $idUsuario =
            (int)$_SESSION['usuario_id'];

        $idPedido = filter_input(
            INPUT_GET,
            'id',
            FILTER_VALIDATE_INT
        );

        if (!$idPedido) {
            if (AjaxHelper::isAjax()) {
                AjaxHelper::jsonError(
                    'Pedido inválido.',
                    422
                );
            }

            $_SESSION['flash_error'] =
                'Pedido inválido.';

            header('Location: index.php?action=mi_cuenta');
            exit();
        }

        $pedido = $this->pedidoModel
            ->getPedidoByIdAndUsuario(
                (int)$idPedido,
                $idUsuario
            );

        if (!$pedido) {
            if (AjaxHelper::isAjax()) {
                AjaxHelper::jsonError(
                    'El pedido no existe o no te pertenece.',
                    404
                );
            }

            $_SESSION['flash_error'] =
                'Pedido no encontrado.';

            header('Location: index.php?action=mi_cuenta');
            exit();
        }

        $detalle = $this->pedidoModel
            ->getDetallePedido(
                (int)$idPedido,
                $idUsuario
            );

        if (AjaxHelper::isAjax()) {
            AjaxHelper::jsonResponse(
                true,
                'Detalle del pedido cargado.',
                [
                    'pedido' => $pedido,
                    'detalle' => $detalle
                ]
            );
        }

        include 'Views/Cuenta/detalle_pedido.php';
    }

    public function crearPedido(){
        $this->validarSesion();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->responderErrorPedido(
                'Método de solicitud no permitido.',
                405
            );
        }

        $tokenSesion =
            $_SESSION['csrf_token'] ?? '';

        $tokenRecibido =
            $_POST['csrf_token'] ?? '';

        if (
            $tokenSesion === '' ||
            $tokenRecibido === '' ||
            !hash_equals(
                $tokenSesion,
                $tokenRecibido
            )
        ) {
            $this->responderErrorPedido(
                'El token de seguridad es inválido o ha expirado.',
                419
            );
        }

        $idUsuario =
            (int)$_SESSION['usuario_id'];

        $idMetodo = filter_input(
            INPUT_POST,
            'id_metodo',
            FILTER_VALIDATE_INT
        );

        $idDireccion = filter_input(
            INPUT_POST,
            'id_direccion',
            FILTER_VALIDATE_INT
        );

        $tipoEntrega = trim(
            $_POST['tipo_entrega'] ?? ''
        );

        $observacion = trim(
            $_POST['observacion'] ?? ''
        );

        if (!in_array(
            $tipoEntrega,
            ['delivery', 'recojo'],
            true
        )) {
            $this->responderErrorPedido(
                'Selecciona un tipo de entrega válido.',
                422
            );
        }

        if (!$idMetodo) {
            $this->responderErrorPedido(
                'Selecciona un método de pago válido.',
                422
            );
        }

        if (strlen($observacion) > 255) {
            $this->responderErrorPedido(
                'La observación no puede superar los 255 caracteres.',
                422
            );
        }

        if ($tipoEntrega === 'delivery') {
            require_once 'Models/direccionModel.php';
            $direccionModel = new DireccionModel();

            $calle = trim($_POST['calle'] ?? '');
            $distrito = trim($_POST['distrito'] ?? '');
            $numero = trim($_POST['numero'] ?? '');
            $loteManzana = trim($_POST['lote_manzana'] ?? '');
            $referencia = trim($_POST['referencia'] ?? '');
            $idDistrito = filter_input(INPUT_POST, 'id_distrito', FILTER_VALIDATE_INT) ?: null;

            // Si el usuario envió los campos de dirección directamente en el formulario
            if (!$idDireccion && !empty($calle) && !empty($distrito)) {
                $idNueva = $direccionModel->crearDireccionCompleta(
                    $idUsuario,
                    'Lambayeque',
                    'Chiclayo',
                    $distrito,
                    $calle,
                    $referencia,
                    1,
                    $numero,
                    $loteManzana,
                    $idDistrito
                );
                if ($idNueva) {
                    $idDireccion = $idNueva;
                }
            }

            if (!$idDireccion) {
                $this->responderErrorPedido(
                    'Ingresa o selecciona una dirección de entrega válida.',
                    422
                );
            }

            $direccion = $direccionModel->getDireccionById((int)$idDireccion, $idUsuario);

            if (!$direccion) {
                $this->responderErrorPedido(
                    'La dirección no existe o no pertenece a tu cuenta.',
                    403
                );
            }
        } else {
            $idDireccion = null;
        }

        $carrito =
            isset($_SESSION['carritos'][$idUsuario]) &&
            is_array($_SESSION['carritos'][$idUsuario])
                ? $_SESSION['carritos'][$idUsuario]
                : [];

        if (empty($carrito)) {
            $this->responderErrorPedido(
                'Tu carrito está vacío.',
                422
            );
        }

        // Verificar si el método de pago requiere comprobante (Yape / Plin / Transferencia)
        $metodoInfo = $this->pedidoModel->getMetodoPagoById((int)$idMetodo);
        $nombreMet = strtolower(trim($metodoInfo['nombre'] ?? ''));
        $esDigital = (strpos($nombreMet, 'yape') !== false || strpos($nombreMet, 'plin') !== false || strpos($nombreMet, 'transferencia') !== false);
        $requiereVoucher = !empty($metodoInfo['requiere_comprobante']) || $esDigital;

        $datosVoucher = [];
        if ($requiereVoucher) {
            $celularRemitente = trim($_POST['celular_remitente'] ?? '');
            $codigoOperacion = trim($_POST['codigo_operacion'] ?? '');
            $titularOrigen = trim($_POST['titular_origen'] ?? '');

            if (empty($celularRemitente) || strlen($celularRemitente) < 8) {
                $this->responderErrorPedido('Ingresa el número celular emisor del pago digital.', 422);
            }
            if (empty($codigoOperacion) || strlen($codigoOperacion) < 3) {
                $this->responderErrorPedido('Ingresa el código de operación que figura en tu constancia.', 422);
            }

            if (!isset($_FILES['captura_voucher']) || $_FILES['captura_voucher']['error'] !== UPLOAD_ERR_OK) {
                $this->responderErrorPedido('Debes adjuntar la captura de pantalla o foto del comprobante de pago.', 422);
            }

            $file = $_FILES['captura_voucher'];
            $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mime, $allowedMimes, true)) {
                $this->responderErrorPedido('El comprobante debe ser una imagen válida (JPG, PNG o WEBP).', 422);
            }

            $extensiones = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
            $ext = $extensiones[$mime] ?? 'jpg';
            $nombreArchivo = 'voucher_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $dirDestino = __DIR__ . '/../uploads/vouchers/';
            if (!is_dir($dirDestino)) {
                mkdir($dirDestino, 0777, true);
            }

            $rutaCompleta = $dirDestino . $nombreArchivo;
            if (!move_uploaded_file($file['tmp_name'], $rutaCompleta)) {
                $this->responderErrorPedido('Error al guardar el archivo del voucher en el servidor.', 500);
            }

            $datosVoucher = [
                'celular' => $celularRemitente,
                'codigo' => $codigoOperacion,
                'titular' => $titularOrigen,
                'voucher' => 'uploads/vouchers/' . $nombreArchivo,
                'requiere' => true
            ];
        }

        // Horario de entrega (inmediato o programado)
        $horarioTipo = trim($_POST['horario_tipo'] ?? 'inmediato');
        $fechaProgramada = null;
        $horarioTexto = 'Lo antes posible (30-45 min)';

        if ($horarioTipo === 'programado') {
            $fechaInput = trim($_POST['fecha_programada'] ?? '');
            if (!empty($fechaInput)) {
                $ts = strtotime($fechaInput);
                if ($ts && $ts > time()) {
                    $fechaProgramada = date('Y-m-d H:i:s', $ts);
                    $horarioTexto = 'Programado para ' . date('d/m/Y H:i', $ts);
                } else {
                    $fechaProgramada = date('Y-m-d H:i:s', strtotime('+1 hour'));
                    $horarioTexto = 'Programado para ' . date('d/m/Y H:i', strtotime($fechaProgramada));
                }
            } else {
                $horarioTexto = 'Programado para después';
            }
        }

        $resultado =
            $this->pedidoModel->crearPedido(
                $idUsuario,
                (int)$idMetodo,
                $idDireccion !== null
                    ? (int)$idDireccion
                    : null,
                $tipoEntrega,
                $observacion,
                $carrito,
                $datosVoucher,
                $horarioTexto,
                $fechaProgramada
            );

        if (
            !isset($resultado['success']) ||
            !$resultado['success']
        ) {
            $this->responderErrorPedido(
                $resultado['message']
                    ?? 'No se pudo realizar el pedido.',
                422
            );
        }

        $_SESSION['carritos'][$idUsuario] = [];

        // Notificación por correo con banner y resumen de compra (plantilla del video)
        try {
            require_once 'Models/notificacionEmailModel.php';
            $notifEmail = new NotificacionEmailModel();
            $notifEmail->enviarConfirmacionCompra((int)$resultado['id_pedido']);
        } catch (Throwable $e) {
            error_log("Error enviando correo de confirmación de pedido: " . $e->getMessage());
        }

        $redirect =
            'index.php?action=pedido_confirmado&id=' .
            (int)$resultado['id_pedido'];

        if (AjaxHelper::isAjax()) {
            AjaxHelper::jsonResponse(
                true,
                'Pedido realizado correctamente.',
                [
                    'redirect' => $redirect,
                    'pedido'   => $resultado
                ]
            );
        }

        $_SESSION['flash_success'] =
            'Pedido realizado correctamente.';

        header('Location: ' . $redirect);
        exit();
    }

    private function responderErrorPedido(
        $mensaje,
        $status = 422
    ) {
        if (AjaxHelper::isAjax()) {
            AjaxHelper::jsonError(
                $mensaje,
                $status
            );
        }

        $_SESSION['flash_error'] = $mensaje;

        header('Location: index.php?action=checkout');
        exit();
    }

    public function pedidoConfirmado(){
        $this->validarSesion();

        $idPedido = filter_input(
            INPUT_GET,
            'id',
            FILTER_VALIDATE_INT
        );

        if (!$idPedido) {

            $_SESSION['flash_error'] =
                'Pedido inválido.';

            header(
                'Location: index.php?action=home_usuario'
            );

            exit();
        }

        $pedido =
            $this->pedidoModel
                ->getPedidoConfirmado(
                    $idPedido,
                    $_SESSION['usuario_id']
                );

        if (!$pedido) {

            $_SESSION['flash_error'] =
                'El pedido no existe.';

            header(
                'Location: index.php?action=home_usuario'
            );

            exit();
        }

        $detalle =
            $this->pedidoModel
                ->getDetallePedido(
                    $idPedido,
                    $_SESSION['usuario_id']
                );

        include
            'Views/Pedido/pedido_confirmado.php';
    }

    private function validarAdmin()
    {
        $this->validarSesion();
        if (($_SESSION['usuario_rol'] ?? '') !== 'admin') {
            if (AjaxHelper::isAjax()) {
                AjaxHelper::jsonError('Acceso no autorizado.', 403);
            }
            header('Location: index.php?action=home_usuario');
            exit();
        }
    }

    public function listarVentasAdmin()
    {
        $this->validarAdmin();
        $ventas = $this->pedidoModel->getAllPedidosAdmin();
        $nombre = htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Admin');
        include 'Views/Admin/ventas_admin.php';
    }

    public function detallePedidoAdmin()
    {
        $this->validarAdmin();

        $idPedido = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

        if (!$idPedido) {
            AjaxHelper::jsonError('ID de pedido inválido.', 422);
        }

        $pedido  = $this->pedidoModel->getPedidoByIdAdmin($idPedido);
        $detalle = $this->pedidoModel->getDetallePedidoAdmin($idPedido);

        if (!$pedido) {
            AjaxHelper::jsonError('Pedido no encontrado.', 404);
        }

        AjaxHelper::jsonResponse(true, 'Detalle cargado.', [
            'pedido'  => $pedido,
            'detalle' => $detalle
        ]);
    }

    public function actualizarEstadoPedido()
    {
        $this->validarAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            AjaxHelper::jsonError('Método no permitido.', 405);
        }

        $idPedido    = filter_input(INPUT_POST, 'id_pedido', FILTER_VALIDATE_INT);
        $nuevoEstado = trim($_POST['estado'] ?? '');

        if (!$idPedido || empty($nuevoEstado)) {
            AjaxHelper::jsonError('Datos incompletos.', 422);
        }

        $ok = $this->pedidoModel->updateEstadoPedido($idPedido, $nuevoEstado);

        if ($ok) {
            AjaxHelper::jsonResponse(true, 'Estado actualizado correctamente.', [
                'id_pedido' => $idPedido,
                'estado'    => $nuevoEstado
            ]);
        } else {
            AjaxHelper::jsonError('No se pudo actualizar el estado.', 500);
        }
    }
}