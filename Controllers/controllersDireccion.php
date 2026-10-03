<?php
require_once 'Models/direccionModel.php';
require_once 'Core/ajaxHelper.php';

class ControllerDireccion
{
    private $direccionModel;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->direccionModel =
            new DireccionModel();
    }

    private function validarSesion()
    {
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

    private function validarMetodoPost()
    {
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

    private function validarCsrf()
    {
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

    private function validarDatosDireccion($departamento, $provincia, $distrito, $calle) {
        $errores = [];

        if ($departamento === '') {
            $errores['departamento'] ='El departamento es obligatorio.';
        }

        if ($provincia === '') {
            $errores['provincia'] = 'La provincia es obligatoria.';
        }

        if ($distrito === '') {
            $errores['distrito'] = 'El distrito es obligatorio.';
        }

        if ($calle === '') {
            $errores['calle'] = 'La dirección es obligatoria.';
        }

        if (strlen($calle) > 200) {
            $errores['calle'] = 'La dirección es demasiado extensa.';
        }

        return $errores;
    }

    private function responderValidacion($mensaje, $errores) {
        if (AjaxHelper::isAjax()) {
            http_response_code(422);
            header('Content-Type: application/json; charset=utf-8');

            echo json_encode([
                'success' => false,
                'message' => $mensaje,
                'data' => [],
                'errors' => $errores
            ]);

            exit();
        }

        $_SESSION['flash_error'] = $mensaje;

        header('Location: index.php?action=mi_cuenta');
        exit();
    }

    private function responderResultado($ok, $mensajeExito, $mensajeError, $idUsuario) {
        if (AjaxHelper::isAjax()) {
            if ($ok) {
                AjaxHelper::jsonResponse(
                    true,
                    $mensajeExito,
                    [
                        'direcciones' =>
                        $this->direccionModel
                            ->getDireccionesByUsuario(
                                $idUsuario
                            )
                    ]
                );
            }

            AjaxHelper::jsonError(
                $mensajeError,
                500
            );
        }

        $_SESSION[$ok ? 'flash_success' : 'flash_error'] = $ok ? $mensajeExito : $mensajeError;

        header('Location: index.php?action=mi_cuenta');
        exit();
    }

    public function crearDireccion(){
        $this->validarSesion();
        $this->validarMetodoPost();
        $this->validarCsrf();

        $idUsuario = (int)$_SESSION['usuario_id'];
        $departamento = trim($_POST['departamento'] ?? '');
        $provincia = trim($_POST['provincia'] ?? '');
        $distrito = trim($_POST['distrito'] ?? '');
        $calle = trim($_POST['calle'] ?? '');
        $referencia = trim($_POST['referencia'] ?? '');
        $esPrincipal = isset($_POST['es_principal']) ? 1 : 0;

        $errores = $this->validarDatosDireccion(
            $departamento,
            $provincia,
            $distrito,
            $calle
        );

        if (!empty($errores)) {
            $this->responderValidacion(
                'Completa correctamente la dirección.',
                $errores
            );
        }

        $direccionesActuales =
            $this->direccionModel
            ->getDireccionesByUsuario(
                $idUsuario
            );

        if (empty($direccionesActuales)) {
            $esPrincipal = 1;
        }

        $ok = $this->direccionModel
            ->crearDireccion(
                $idUsuario,
                $departamento,
                $provincia,
                $distrito,
                $calle,
                $referencia,
                $esPrincipal
            );

        $this->responderResultado(
            $ok,
            'Dirección registrada correctamente.',
            'No se pudo registrar la dirección.',
            $idUsuario
        );
    }

    public function actualizarDireccion()
    {
        $this->validarSesion();
        $this->validarMetodoPost();
        $this->validarCsrf();

        $idUsuario =
            (int)$_SESSION['usuario_id'];

        $idDireccion = filter_input(
            INPUT_POST,
            'id_direccion',
            FILTER_VALIDATE_INT
        );

        if (!$idDireccion) {
            $this->responderValidacion(
                'La dirección seleccionada no es válida.',
                [
                    'id_direccion' =>
                    'Identificador inválido.'
                ]
            );
        }

        $direccion =
            $this->direccionModel
            ->getDireccionById(
                (int)$idDireccion,
                $idUsuario
            );

        if (!$direccion) {
            if (AjaxHelper::isAjax()) {
                AjaxHelper::jsonError(
                    'La dirección no existe o no te pertenece.',
                    404
                );
            }

            $_SESSION['flash_error'] =
                'Dirección no encontrada.';

            header('Location: index.php?action=mi_cuenta');
            exit();
        }

        $departamento =
            trim($_POST['departamento'] ?? '');

        $provincia =
            trim($_POST['provincia'] ?? '');

        $distrito =
            trim($_POST['distrito'] ?? '');

        $calle =
            trim($_POST['calle'] ?? '');

        $referencia =
            trim($_POST['referencia'] ?? '');

        $esPrincipal =
            isset($_POST['es_principal'])
            ? 1
            : 0;

        $errores = $this->validarDatosDireccion(
            $departamento,
            $provincia,
            $distrito,
            $calle
        );

        if (!empty($errores)) {
            $this->responderValidacion(
                'Completa correctamente la dirección.',
                $errores
            );
        }

        $ok = $this->direccionModel
            ->actualizarDireccion(
                (int)$idDireccion,
                $idUsuario,
                $departamento,
                $provincia,
                $distrito,
                $calle,
                $referencia,
                $esPrincipal
            );

        $this->responderResultado(
            $ok,
            'Dirección actualizada correctamente.',
            'No se pudo actualizar la dirección.',
            $idUsuario
        );
    }

    public function marcarPrincipal()
    {
        $this->validarSesion();
        $this->validarMetodoPost();
        $this->validarCsrf();

        $idUsuario =
            (int)$_SESSION['usuario_id'];

        $idDireccion = filter_input(
            INPUT_POST,
            'id_direccion',
            FILTER_VALIDATE_INT
        );

        if (!$idDireccion) {
            $this->responderValidacion(
                'Selecciona una dirección válida.',
                [
                    'id_direccion' =>
                    'Dirección no válida.'
                ]
            );
        }

        $direccion =
            $this->direccionModel
            ->getDireccionById(
                (int)$idDireccion,
                $idUsuario
            );

        if (!$direccion) {
            if (AjaxHelper::isAjax()) {
                AjaxHelper::jsonError(
                    'La dirección no existe o no te pertenece.',
                    404
                );
            }

            $_SESSION['flash_error'] =
                'Dirección no encontrada.';

            header('Location: index.php?action=mi_cuenta');
            exit();
        }

        $ok = $this->direccionModel
            ->marcarComoPrincipal(
                (int)$idDireccion,
                $idUsuario
            );

        $this->responderResultado(
            $ok,
            'Dirección principal actualizada.',
            'No se pudo actualizar la dirección principal.',
            $idUsuario
        );
    }

    public function desactivarDireccion()
    {
        $this->validarSesion();
        $this->validarMetodoPost();
        $this->validarCsrf();

        $idUsuario =
            (int)$_SESSION['usuario_id'];

        $idDireccion = filter_input(
            INPUT_POST,
            'id_direccion',
            FILTER_VALIDATE_INT
        );

        if (!$idDireccion) {
            $this->responderValidacion(
                'Selecciona una dirección válida.',
                [
                    'id_direccion' =>
                    'Dirección no válida.'
                ]
            );
        }

        $direccion =
            $this->direccionModel
            ->getDireccionById(
                (int)$idDireccion,
                $idUsuario
            );

        if (!$direccion) {
            if (AjaxHelper::isAjax()) {
                AjaxHelper::jsonError(
                    'La dirección no existe o no te pertenece.',
                    404
                );
            }

            $_SESSION['flash_error'] =
                'Dirección no encontrada.';

            header('Location: index.php?action=mi_cuenta');
            exit();
        }

        $eraPrincipal =
            (int)$direccion['es_principal'] === 1;

        $ok = $this->direccionModel
            ->desactivarDireccion(
                (int)$idDireccion,
                $idUsuario
            );

        if ($ok && $eraPrincipal) {
            $restantes =
                $this->direccionModel
                ->getDireccionesByUsuario(
                    $idUsuario
                );

            if (!empty($restantes)) {
                $this->direccionModel
                    ->marcarComoPrincipal(
                        (int)$restantes[0]['id_direccion'],
                        $idUsuario
                    );
            }
        }

        $this->responderResultado(
            $ok,
            'Dirección eliminada de tu cuenta.',
            'No se pudo eliminar la dirección.',
            $idUsuario
        );
    }
}
