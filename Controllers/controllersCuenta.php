<?php
require_once 'Models/cuentaModel.php';
require_once 'Core/ajaxHelper.php';

class ControllerCuenta
{
    private $cuentaModel;
    private $direccionModel;
    private $pedidoModel;

    public function __construct(){
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->cuentaModel = new CuentaModel();
        $this->direccionModel = new DireccionModel();
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

    private function validarCliente(){
        if (isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'admin') {
            if (AjaxHelper::isAjax()) {
                AjaxHelper::jsonError(
                    'Esta sección corresponde a clientes.',
                    403
                );
            }

            header('Location: index.php?action=home_admin');
            exit();
        }
    }

    private function validarMetodoPost(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            if (AjaxHelper::isAjax()) {
                AjaxHelper::jsonError(
                    'Método no permitido.',
                    405
                );
            }

            header('Location: index.php?action=mi_cuenta');
            exit();
        }
    }

    private function generarCsrfToken(){
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(
                random_bytes(32)
            );
        }

        return $_SESSION['csrf_token'];
    }

    private function validarCsrf(){
        $tokenSesion = $_SESSION['csrf_token'] ?? '';
        $tokenRecibido = $_POST['csrf_token'] ?? '';

        if ($tokenSesion === '' || $tokenRecibido === '' || !hash_equals($tokenSesion, $tokenRecibido)) {
            if (AjaxHelper::isAjax()) {
                AjaxHelper::jsonError(
                    'Token de seguridad inválido.',
                    419
                );
            }

            $_SESSION['flash_error'] =
                'La solicitud ha expirado.';

            header('Location: index.php?action=mi_cuenta');
            exit();
        }
    }

    public function mostrarCuenta(){
        $this->validarSesion();
        $this->validarCliente();

        $idUsuario = (int)$_SESSION['usuario_id'];

        $usuario = $this->cuentaModel->getUsuarioById($idUsuario);

        if (!$usuario) {
            session_destroy();

            header('Location: index.php?action=login');
            exit();
        }

        $direcciones = $this->direccionModel->getDireccionesByUsuario($idUsuario);
        $pedidos = $this->pedidoModel->getPedidosByUsuario($idUsuario);
        $csrf_token = $this->generarCsrfToken();

        $flash_success = $_SESSION['flash_success'] ?? null;

        $flash_error = $_SESSION['flash_error'] ?? null;

        unset($_SESSION['flash_success'], $_SESSION['flash_error']);

        $lockTime = $_SESSION['password_lock_until'] ?? 0;
        $remainingLock = max(0, $lockTime - time());

        include 'Views/Cuenta/mi_cuenta.php';
    }

    public function actualizarPerfil(){
        $this->validarSesion();
        $this->validarCliente();
        $this->validarMetodoPost();
        $this->validarCsrf();

        $idUsuario = (int)$_SESSION['usuario_id'];

        $nombre = trim($_POST['nombre'] ?? '');
        $apellido = trim($_POST['apellido'] ?? '');
        $correo = trim($_POST['correo'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');

        $errores = [];

        if ($nombre === '') {
            $errores['nombre'] = 'El nombre es obligatorio.';
        }

        if ($apellido === '') {
            $errores['apellido'] = 'El apellido es obligatorio.';
        }

        if ($correo === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $errores['correo'] = 'Ingresa un correo válido.';
        }

        if ($telefono !== '' && !preg_match('/^[0-9+\-\s]{7,15}$/', $telefono)) {
            $errores['telefono'] = 'Ingresa un teléfono válido.';
        }

        if ($correo !== '' && $this->cuentaModel->correoExisteExceptoUsuario($correo, $idUsuario)) {
            $errores['correo'] = 'Este correo ya está registrado.';
        }

        if (!empty($errores)) {
            if (AjaxHelper::isAjax()) {
                http_response_code(422);
                header('Content-Type: application/json; charset=utf-8');

                echo json_encode(['success' => false, 'message' => 'Corrige los campos indicados.',
                    'data' => [], 'errors' => $errores
                ]);

                exit();
            }

            $_SESSION['flash_error'] = 'Revisa los datos ingresados.';

            header('Location: index.php?action=mi_cuenta');
            exit();
        }

        $ok = $this->cuentaModel->actualizarPerfil($idUsuario, $nombre, $apellido, $correo, $telefono);

        if ($ok) {
            $_SESSION['usuario_nombre'] =
                trim($nombre . ' ' . $apellido);

            $_SESSION['usuario_correo'] = $correo;
        }

        if (AjaxHelper::isAjax()) {
            if ($ok) {
                AjaxHelper::jsonResponse(
                    true,
                    'Tus datos fueron actualizados.',
                    [
                        'usuario' =>
                        $this->cuentaModel
                            ->getUsuarioById($idUsuario)
                    ]
                );
            }

            AjaxHelper::jsonError(
                'No se pudieron actualizar tus datos.',
                500
            );
        }

        $_SESSION[$ok ? 'flash_success' : 'flash_error'] = $ok
            ? 'Tus datos fueron actualizados.'
            : 'No se pudieron actualizar tus datos.';

        header('Location: index.php?action=mi_cuenta');
        exit();
    }

    public function enviarPinSeguridad() {
        $this->validarSesion();
        $this->validarCliente();
        $this->validarMetodoPost();
        $this->validarCsrf();

        $idUsuario = (int)$_SESSION['usuario_id'];
        $usuario = $this->cuentaModel->getUsuarioById($idUsuario);

        if (!$usuario || empty($usuario['correo'])) {
            if (AjaxHelper::isAjax()) {
                AjaxHelper::jsonError('No se encontró una dirección de correo válida.', 400);
            }
            $_SESSION['flash_error'] = 'Correo no encontrado.';
            header('Location: index.php?action=mi_cuenta');
            exit();
        }

        $pin = str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        $_SESSION['seguridad_pin'] = $pin;
        $_SESSION['seguridad_pin_expira'] = time() + (15 * 60);

        $correo = $usuario['correo'];
        $asunto = "=?UTF-8?B?" . base64_encode("Código PIN para cambiar contraseña - Tienda La Caserita") . "?=";
        $mensaje = "Hola " . ($usuario['nombre'] ?? 'Usuario') . ",\n\n"
                 . "Has solicitado cambiar tu contraseña desde tu cuenta en Tienda \"La Caserita\".\n\n"
                 . "Tu código PIN de verificación es: {$pin}\n\n"
                 . "Este código es válido durante 15 minutos.\n"
                 . "Si tú no realizaste esta solicitud, por favor contáctanos de inmediato.\n\n"
                 . "Atentamente,\n"
                 . "Equipo de Tienda La Caserita";

        require_once __DIR__ . '/../Models/MailHelper.php';
        $enviado = MailHelper::sendEmail($correo, $asunto, $mensaje, false);
        
        $backupDir = __DIR__ . '/../scratch/emails_enviados';
        if (!is_dir($backupDir)) {
            @mkdir($backupDir, 0777, true);
        }
        $backupFile = $backupDir . '/pin_' . time() . '.txt';
        @file_put_contents($backupFile, "Para: $correo\nAsunto: $asunto\n\n$mensaje");

        $msg = "Hemos enviado un código PIN de 6 dígitos a tu correo: " . $correo;

        if (AjaxHelper::isAjax()) {
            AjaxHelper::jsonResponse(true, $msg);
        }

        $_SESSION['flash_success'] = $msg;
        header('Location: index.php?action=mi_cuenta');
        exit();
    }

    public function cambiarPassword(){
        $this->validarSesion();
        $this->validarCliente();
        $this->validarMetodoPost();
        $this->validarCsrf();

        $idUsuario = (int)$_SESSION['usuario_id'];
        $pinIngresado = trim((string)($_POST['pin'] ?? ''));
        $passwordNueva = $_POST['password_nueva'] ?? '';
        $passwordConfirmacion = $_POST['password_confirmacion'] ?? '';

        $errores = [];

        $pinGuardado = $_SESSION['seguridad_pin'] ?? '';
        $pinExpira = $_SESSION['seguridad_pin_expira'] ?? 0;

        if (empty($pinGuardado) || time() > $pinExpira) {
            $errores['pin'] = 'El código PIN ha expirado o no ha sido solicitado. Haz clic en "Enviar Código PIN".';
        } elseif ($pinIngresado !== (string)$pinGuardado) {
            $errores['pin'] = 'El código PIN ingresado es incorrecto.';
        }

        if (strlen($passwordNueva) < 8) {
            $errores['password_nueva'] = 'La nueva contraseña debe tener al menos 8 caracteres.';
        } elseif (!preg_match('/[A-Z]/', $passwordNueva)) {
            $errores['password_nueva'] = 'La nueva contraseña debe contener al menos 1 letra mayúscula.';
        } elseif (!preg_match('/[0-9]/', $passwordNueva)) {
            $errores['password_nueva'] = 'La nueva contraseña debe contener al menos 1 número.';
        } elseif (!preg_match('/[^A-Za-z0-9]/', $passwordNueva)) {
            $errores['password_nueva'] = 'La nueva contraseña debe contener al menos 1 carácter especial.';
        }

        if ($passwordNueva !== $passwordConfirmacion) {
            $errores['password_confirmacion'] = 'Las contraseñas no coinciden.';
        }

        if (!empty($errores)) {
            if (AjaxHelper::isAjax()) {
                http_response_code(422);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => false,
                    'message' => reset($errores),
                    'data' => [],
                    'errors' => $errores
                ]);
                exit();
            }

            $_SESSION['flash_error'] = reset($errores);
            header('Location: index.php?action=mi_cuenta');
            exit();
        }

        $nuevoHash = password_hash($passwordNueva, PASSWORD_BCRYPT);
        $ok = $this->cuentaModel->cambiarPassword($idUsuario, $nuevoHash);

        if ($ok) {
            unset($_SESSION['seguridad_pin'], $_SESSION['seguridad_pin_expira']);
        }

        if (AjaxHelper::isAjax()) {
            if ($ok) {
                AjaxHelper::jsonResponse(true, '¡Tu contraseña ha sido actualizada con éxito!');
            }
            AjaxHelper::jsonError('No se pudo actualizar la contraseña.', 500);
        }

        $_SESSION[$ok ? 'flash_success' : 'flash_error'] = $ok
            ? '¡Tu contraseña ha sido actualizada con éxito!'
            : 'No se pudo actualizar la contraseña.';

        header('Location: index.php?action=mi_cuenta');
        exit();
    }

    public function verificarPasswordActual(){
        if (AjaxHelper::isAjax()) {
            AjaxHelper::jsonResponse(true, 'OK');
        }
        header('Location: index.php?action=mi_cuenta');
        exit();
    }
}
