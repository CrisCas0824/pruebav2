<?php
include_once 'Models/userModel.php';
include_once 'Models/categoryModel.php';
include_once 'Models/momentConModel.php';
include_once 'Models/grupoMomentModel.php';
include_once 'Models/productModel.php';

function validarTelefono($telefono){
    return preg_match('/^[0-9]{1,9}$/', $telefono);
}

class UserController{
    private $model;

    public function __construct(){
        if(session_status() == PHP_SESSION_NONE){
            session_start();
        }
        $this->model = new UserModel();
    }

    public function generarCsrfToken(){
        if(!isset($_SESSION['csrf_token'])){
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public function listarUsers(){
        $users = $this->model->getUser();
    }

    public function createUser(): void{
        if($_SERVER['REQUEST_METHOD'] == 'POST'){
            if(!isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']){
                die("Token CSRF invalido");
            }

            $nombre = filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_SPECIAL_CHARS);
            $apellido = filter_input(INPUT_POST, 'apellido', FILTER_SANITIZE_SPECIAL_CHARS);
            $correo = filter_input(INPUT_POST, 'correo', FILTER_SANITIZE_EMAIL);
            $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_SPECIAL_CHARS);
            $telefono = filter_input(INPUT_POST, 'telefono', FILTER_SANITIZE_NUMBER_INT);
            $rol = filter_input(INPUT_POST, 'rol', FILTER_SANITIZE_STRING);
            $fecha_registro = filter_input(INPUT_POST, 'fecha_registro', FILTER_DEFAULT);

            if(empty($telefono) || !validarTelefono($telefono)){
                $_SESSION['flash_message'] = 'Teléfono. Solo se permiten números';
                header('Location: index.php');
                exit();
            }

            if($nombre && $apellido && $correo && $password && $telefono && $rol && $fecha_registro){
                $this->model->createUser($nombre, $apellido, $correo, $password, $telefono, $rol, $fecha_registro);
                echo "USUARIO REGISTRADO";
                exit();
            }else{
                echo "ERROR AL REGISTRAR USUARIO";
            }
        }else{
            $csrf_token = $this->generarCsrfToken();
            include "Views/Login/registro.php";
        }
    }

    public function login(): void{
        $esAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
                  (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false);

        if($_SERVER['REQUEST_METHOD'] == 'POST'){
            if (empty($_SESSION['csrf_token'])) {
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            }

            $tokenPost = $_POST['csrf_token'] ?? '';
            if(empty($tokenPost) || !hash_equals($_SESSION['csrf_token'], (string)$tokenPost)){
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                $errorMsg = "Tu sesión de seguridad ha expirado. Por favor, intenta de nuevo.";
                if ($esAjax) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode([
                        'ok' => false, 
                        'success' => false, 
                        'mensaje' => $errorMsg,
                        'nuevo_csrf_token' => $_SESSION['csrf_token']
                    ]);
                    exit();
                }
                $_SESSION['auth_error'] = $errorMsg;
                header('Location: index.php?action=home_usuario&open_auth=1');
                exit();
            }

            $correo = filter_input(INPUT_POST, 'correo', FILTER_SANITIZE_EMAIL);
            $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_SPECIAL_CHARS);

            if($correo && $password){
                $usuario = $this->model->getUserByEmail($correo);

                if($usuario){
                    $bloqueo    = $usuario['bloqueo_until'] ?? null;
                    $intentos   = (int)($usuario['intentos_fallidos'] ?? 0);

                    if ($bloqueo && strtotime($bloqueo) > time()) {
                        $restante = strtotime($bloqueo) - time();
                        $errorMsg = "Cuenta bloqueada temporalmente por demasiados intentos. Intente en {$restante} s.";
                        if ($esAjax) {
                            header('Content-Type: application/json; charset=utf-8');
                            echo json_encode([
                                'ok' => false, 
                                'success' => false, 
                                'mensaje' => $errorMsg,
                                'bloqueado' => true,
                                'restante' => $restante
                            ]);
                            exit();
                        }
                        $_SESSION['lock_expires'] = strtotime($bloqueo);
                        $_SESSION['auth_error'] = $errorMsg;
                        $_SESSION['form_data'] = $_POST;
                        header('Location: index.php?action=home_usuario&open_auth=1');
                        exit();
                    }

                    // Si ya expiró el bloqueo, resetear intentos
                    if ($bloqueo && strtotime($bloqueo) <= time() && $intentos >= 3) {
                        $this->model->resetFailedAttempts($usuario['id_usuario']);
                        $intentos = 0;
                    }

                    if(password_verify($password, $usuario['password'])){
                        if(isset($usuario['activo']) && $usuario['activo'] == 0){
                            $errorMsg = "Tu cuenta ha sido desactivada. Por favor, contacta a un administrador.";
                            if ($esAjax) {
                                header('Content-Type: application/json; charset=utf-8');
                                echo json_encode(['ok' => false, 'success' => false, 'mensaje' => $errorMsg]);
                                exit();
                            }
                            $_SESSION['auth_error'] = $errorMsg;
                            $_SESSION['form_data'] = $_POST;
                            header('Location: index.php?action=home_usuario&open_auth=1');
                            exit();
                        }

                        $this->model->resetFailedAttempts($usuario['id_usuario']);

                        $_SESSION['usuario_id'] = $usuario['id_usuario'];
                        $_SESSION['usuario_nombre'] = $usuario['nombre'];
                        $_SESSION['usuario_correo'] = $usuario['correo'];
                        $_SESSION['usuario_rol'] = $usuario['rol'];

                        // Transferir carrito de invitado a usuario logueado
                        if ($usuario['rol'] === 'cliente' && isset($_SESSION['carritos']['invitado']) && !empty($_SESSION['carritos']['invitado'])) {
                            if (!isset($_SESSION['carritos'][$usuario['id_usuario']])) {
                                $_SESSION['carritos'][$usuario['id_usuario']] = [];
                            }
                            foreach ($_SESSION['carritos']['invitado'] as $idProd => $item) {
                                if (isset($_SESSION['carritos'][$usuario['id_usuario']][$idProd])) {
                                    $_SESSION['carritos'][$usuario['id_usuario']][$idProd]['cantidad'] += $item['cantidad'];
                                } else {
                                    $_SESSION['carritos'][$usuario['id_usuario']][$idProd] = $item;
                                }
                            }
                            unset($_SESSION['carritos']['invitado']);
                        }

                        session_regenerate_id(true);

                        $redirect = 'index.php?action=home_usuario';

                        if ($esAjax) {
                            header('Content-Type: application/json; charset=utf-8');
                            echo json_encode([
                                'ok' => true,
                                'success' => true,
                                'redirect' => $redirect,
                                'rol' => $usuario['rol'],
                                'nombre' => $usuario['nombre']
                            ]);
                            exit();
                        }

                        header('Location: ' . $redirect);
                        exit();
                    }else{
                        $newAttempts = $intentos + 1;
                        if ($newAttempts >= 3) {
                            $bloqueoHasta = date('Y-m-d H:i:s', time() + 30);
                            $this->model->setLock($usuario['id_usuario'], $bloqueoHasta);
                            $errorMsg = "Has superado los 3 intentos permitidos. Cuenta bloqueada por 30 segundos.";
                        } else {
                            $this->model->setAttempts($usuario['id_usuario'], $newAttempts);
                            $intentosRestantes = 3 - $newAttempts;
                            if ($intentosRestantes === 1) {
                                $errorMsg = "Contraseña incorrecta. Te queda 1 de 3 intentos.";
                            } else {
                                $errorMsg = "Contraseña incorrecta. Te quedan {$intentosRestantes} de 3 intentos.";
                            }
                        }

                        if ($esAjax) {
                            header('Content-Type: application/json; charset=utf-8');
                            echo json_encode([
                                'ok' => false, 
                                'success' => false, 
                                'mensaje' => $errorMsg,
                                'intentos_fallidos' => $newAttempts,
                                'intentos_restantes' => max(0, 3 - $newAttempts)
                            ]);
                            exit();
                        }

                        $_SESSION['auth_error'] = $errorMsg;
                        $_SESSION['form_data'] = $_POST;
                        header('Location: index.php?action=home_usuario&open_auth=1');
                        exit();
                    }
                }else{
                    $errorMsg = "Correo o contraseña incorrectos.";
                    if ($esAjax) {
                        header('Content-Type: application/json; charset=utf-8');
                        echo json_encode(['ok' => false, 'success' => false, 'mensaje' => $errorMsg]);
                        exit();
                    }
                    $_SESSION['auth_error'] = $errorMsg;
                    $_SESSION['form_data'] = $_POST;
                    header('Location: index.php?action=home_usuario&open_auth=1');
                    exit();
                }
            }else{
                $errorMsg = "Por favor completa todos los campos requeridos.";
                if ($esAjax) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['ok' => false, 'success' => false, 'mensaje' => $errorMsg]);
                    exit();
                }
                $_SESSION['auth_error'] = $errorMsg;
                $_SESSION['form_data'] = $_POST;
                header('Location: index.php?action=home_usuario&open_auth=1');
                exit();
            }
        }else{
            // Redirigir siempre a la tienda e-commerce y abrir modal
            header('Location: index.php?action=home_usuario&open_auth=1');
            exit();
        }
    }

    public function registro(): void{
        $esAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
                  (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false);

        if($_SERVER['REQUEST_METHOD'] == 'POST'){
            if (empty($_SESSION['csrf_token'])) {
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            }

            $tokenPost = $_POST['csrf_token'] ?? '';
            if(empty($tokenPost) || !hash_equals($_SESSION['csrf_token'], (string)$tokenPost)){
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                $errorMsg = "Tu sesión de seguridad ha expirado. Por favor, intenta de nuevo.";
                if ($esAjax) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode([
                        'ok' => false, 
                        'success' => false, 
                        'mensaje' => $errorMsg,
                        'nuevo_csrf_token' => $_SESSION['csrf_token']
                    ]);
                    exit();
                }
                $_SESSION['auth_error'] = $errorMsg;
                header('Location: index.php?action=home_usuario&open_register=1');
                exit();
            }

            $nombre = filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_SPECIAL_CHARS);
            $correo = filter_input(INPUT_POST, 'correo', FILTER_SANITIZE_EMAIL);
            $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_SPECIAL_CHARS);
            $confirm_password = filter_input(INPUT_POST, 'confirm_password', FILTER_SANITIZE_SPECIAL_CHARS);
            $acepto_terminos = isset($_POST['acepto_terminos']);

            if($nombre && $correo && $password && $confirm_password){
                if (!$acepto_terminos) {
                    $errorMsg = "Debes leer y aceptar los Términos y Condiciones para crear tu cuenta.";
                    if ($esAjax) {
                        header('Content-Type: application/json; charset=utf-8');
                        echo json_encode(['ok' => false, 'success' => false, 'mensaje' => $errorMsg]);
                        exit();
                    }
                    $_SESSION['auth_error'] = $errorMsg;
                    $_SESSION['form_data'] = $_POST;
                    header('Location: index.php?action=home_usuario&open_register=1');
                    exit();
                }

                if (strlen($password) < 8) {
                    $errorMsg = "La contraseña debe tener al menos 8 caracteres.";
                    if ($esAjax) {
                        header('Content-Type: application/json; charset=utf-8');
                        echo json_encode(['ok' => false, 'success' => false, 'mensaje' => $errorMsg]);
                        exit();
                    }
                    $_SESSION['auth_error'] = $errorMsg;
                    $_SESSION['form_data'] = $_POST;
                    header('Location: index.php?action=home_usuario&open_register=1');
                    exit();
                }

                if (!preg_match('/[A-Z]/', $password)) {
                    $errorMsg = "La contraseña debe contener al menos 1 letra mayúscula.";
                    if ($esAjax) {
                        header('Content-Type: application/json; charset=utf-8');
                        echo json_encode(['ok' => false, 'success' => false, 'mensaje' => $errorMsg]);
                        exit();
                    }
                    $_SESSION['auth_error'] = $errorMsg;
                    $_SESSION['form_data'] = $_POST;
                    header('Location: index.php?action=home_usuario&open_register=1');
                    exit();
                }

                if (!preg_match('/[0-9]/', $password)) {
                    $errorMsg = "La contraseña debe contener al menos 1 número.";
                    if ($esAjax) {
                        header('Content-Type: application/json; charset=utf-8');
                        echo json_encode(['ok' => false, 'success' => false, 'mensaje' => $errorMsg]);
                        exit();
                    }
                    $_SESSION['auth_error'] = $errorMsg;
                    $_SESSION['form_data'] = $_POST;
                    header('Location: index.php?action=home_usuario&open_register=1');
                    exit();
                }

                if (!preg_match('/[^A-Za-z0-9]/', $password)) {
                    $errorMsg = "La contraseña debe contener al menos 1 carácter especial.";
                    if ($esAjax) {
                        header('Content-Type: application/json; charset=utf-8');
                        echo json_encode(['ok' => false, 'success' => false, 'mensaje' => $errorMsg]);
                        exit();
                    }
                    $_SESSION['auth_error'] = $errorMsg;
                    $_SESSION['form_data'] = $_POST;
                    header('Location: index.php?action=home_usuario&open_register=1');
                    exit();
                }

                if($password !== $confirm_password){
                    $errorMsg = "Las contraseñas no coinciden.";
                    if ($esAjax) {
                        header('Content-Type: application/json; charset=utf-8');
                        echo json_encode(['ok' => false, 'success' => false, 'mensaje' => $errorMsg]);
                        exit();
                    }
                    $_SESSION['auth_error'] = $errorMsg;
                    $_SESSION['form_data'] = $_POST;
                    header('Location: index.php?action=home_usuario&open_register=1');
                    exit();
                }

                $existente = $this->model->getUserByEmail($correo);
                if($existente){
                    $errorMsg = "El correo ya está registrado.";
                    if ($esAjax) {
                        header('Content-Type: application/json; charset=utf-8');
                        echo json_encode(['ok' => false, 'success' => false, 'mensaje' => $errorMsg]);
                        exit();
                    }
                    $_SESSION['auth_error'] = $errorMsg;
                    $_SESSION['form_data'] = $_POST;
                    header('Location: index.php?action=home_usuario&open_register=1');
                    exit();
                }

                $esPrimerUsuario = ($this->model->contarUsuarios() === 0);
                $this->model->registrarUsuario($nombre, $correo, $password);

                // Auto-login tras el registro exitoso
                $nuevoUsuario = $this->model->getUserByEmail($correo);
                if ($nuevoUsuario) {
                    $_SESSION['usuario_id'] = $nuevoUsuario['id_usuario'];
                    $_SESSION['usuario_nombre'] = $nuevoUsuario['nombre'];
                    $_SESSION['usuario_correo'] = $nuevoUsuario['correo'];
                    $_SESSION['usuario_rol'] = $nuevoUsuario['rol'];

                    // Transferir carrito de invitado a usuario logueado
                    if (isset($_SESSION['carritos']['invitado']) && !empty($_SESSION['carritos']['invitado'])) {
                        if (!isset($_SESSION['carritos'][$nuevoUsuario['id_usuario']])) {
                            $_SESSION['carritos'][$nuevoUsuario['id_usuario']] = [];
                        }
                        foreach ($_SESSION['carritos']['invitado'] as $idProd => $item) {
                            if (isset($_SESSION['carritos'][$nuevoUsuario['id_usuario']][$idProd])) {
                                $_SESSION['carritos'][$nuevoUsuario['id_usuario']][$idProd]['cantidad'] += $item['cantidad'];
                            } else {
                                $_SESSION['carritos'][$nuevoUsuario['id_usuario']][$idProd] = $item;
                            }
                        }
                        unset($_SESSION['carritos']['invitado']);
                    }

                    session_regenerate_id(true);
                }

                $redirect = 'index.php?action=home_usuario';

                if ($esAjax) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode([
                        'ok' => true,
                        'success' => true,
                        'redirect' => $redirect,
                        'rol' => $nuevoUsuario['rol'] ?? 'cliente',
                        'mensaje' => $esPrimerUsuario ? '¡Cuenta de Administrador Principal creada!' : '¡Cuenta creada con éxito!'
                    ]);
                    exit();
                }

                header('Location: ' . $redirect);
                exit();
            }else{
                $errorMsg = "Por favor completa todos los campos requeridos.";
                if ($esAjax) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['ok' => false, 'success' => false, 'mensaje' => $errorMsg]);
                    exit();
                }
                $_SESSION['auth_error'] = $errorMsg;
                $_SESSION['form_data'] = $_POST;
                header('Location: index.php?action=home_usuario&open_register=1');
                exit();
            }
        }else{
            header('Location: index.php?action=home_usuario&open_register=1');
            exit();
        }
    }

    public function loginGoogle(): void {
        header('Content-Type: application/json; charset=utf-8');

        $credencial = trim($_POST['credential'] ?? '');
        if ($credencial === '') {
            echo json_encode(['ok' => false, 'mensaje' => 'No se recibió la credencial de Google.']);
            exit();
        }

        // Validación con Google Token Info API
        $ch = curl_init('https://oauth2.googleapis.com/tokeninfo?id_token=' . rawurlencode($credencial));
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true
        ]);
        $respuesta = curl_exec($ch);
        $codigo = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $datosGoogle = json_decode((string)$respuesta, true);
        if ($codigo !== 200 || !is_array($datosGoogle) || ($datosGoogle['email_verified'] ?? '') !== 'true') {
            echo json_encode(['ok' => false, 'mensaje' => 'La cuenta de Google no pudo ser verificada.']);
            exit();
        }

        $email = $datosGoogle['email'];
        $googleId = $datosGoogle['sub'];
        $nombre = $datosGoogle['given_name'] ?? $datosGoogle['name'] ?? 'Usuario';
        $apellido = $datosGoogle['family_name'] ?? '';
        $avatar = $datosGoogle['picture'] ?? null;

        $usuario = $this->model->buscarPorGoogleId($googleId);
        if (!$usuario) {
            $usuario = $this->model->getUserByEmail($email);
        }

        if (!$usuario) {
            $usuario = $this->model->crearUsuarioGoogle($email, $googleId, $nombre, $apellido, $avatar);
        }

        if (isset($usuario['activo']) && (int)$usuario['activo'] === 0) {
            echo json_encode(['ok' => false, 'mensaje' => 'Esta cuenta se encuentra desactivada.']);
            exit();
        }

        $_SESSION['usuario_id']     = $usuario['id_usuario'];
        $_SESSION['usuario_nombre'] = $usuario['nombre'];
        $_SESSION['usuario_correo'] = $usuario['correo'];
        $_SESSION['usuario_rol']    = $usuario['rol'];

        // Transferir carrito de invitado a usuario logueado
        if ($usuario['rol'] === 'cliente' && isset($_SESSION['carritos']['invitado']) && !empty($_SESSION['carritos']['invitado'])) {
            if (!isset($_SESSION['carritos'][$usuario['id_usuario']])) {
                $_SESSION['carritos'][$usuario['id_usuario']] = [];
            }
            foreach ($_SESSION['carritos']['invitado'] as $idProd => $item) {
                if (isset($_SESSION['carritos'][$usuario['id_usuario']][$idProd])) {
                    $_SESSION['carritos'][$usuario['id_usuario']][$idProd]['cantidad'] += $item['cantidad'];
                } else {
                    $_SESSION['carritos'][$usuario['id_usuario']][$idProd] = $item;
                }
            }
            unset($_SESSION['carritos']['invitado']);
        }

        session_regenerate_id(true);

        echo json_encode([
            'ok' => true,
            'mensaje' => '¡Sesión iniciada con Google!',
            'rol' => $usuario['rol'],
            'redirect' => 'index.php?action=home_usuario'
        ]);
        exit();
    }

    public function recuperarPassword(): void{
        if($_SERVER['REQUEST_METHOD'] == 'POST'){
            $correo = filter_input(INPUT_POST, 'correo', FILTER_SANITIZE_EMAIL);

            if($correo){
                $usuario = $this->model->getUserByEmail($correo);

                if($usuario){
                    // Generar PIN seguro de 6 dígitos numéricos
                    $pin = str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

                    $_SESSION['correo_recuperacion'] = $correo;
                    $_SESSION['recuperacion_pin'] = $pin;
                    $_SESSION['pin_expira'] = time() + (15 * 60); // Válido por 15 minutos
                    $_SESSION['pin_intentos'] = 0;
                    $_SESSION['pin_dev_helper'] = $pin;
                    unset($_SESSION['pin_verificado']);

                    // Enviar correo electrónico
                    $asunto = "=?UTF-8?B?" . base64_encode("Código de recuperación - Tienda La Caserita") . "?=";
                    $mensaje = "Hola,\n\n"
                             . "Has solicitado restablecer tu contraseña en Tienda \"La Caserita\".\n\n"
                             . "Tu código PIN de verificación es: {$pin}\n\n"
                             . "Este código es válido durante 15 minutos.\n"
                             . "Si tú no realizaste esta solicitud, puedes ignorar este mensaje de forma segura.\n\n"
                             . "Atentamente,\n"
                             . "Equipo de Tienda La Caserita";

                    $headers = "From: Tienda La Caserita <no-reply@lacaserita.com>\r\n"
                             . "Reply-To: no-reply@lacaserita.com\r\n";
                    require_once __DIR__ . '/../Models/MailHelper.php';
                    $enviado = MailHelper::sendEmail($correo, $asunto, $mensaje, false);
                    
                    // Guardar copia local para pruebas en XAMPP
                    $backupDir = __DIR__ . '/../scratch/emails_enviados';
                    if (!is_dir($backupDir)) {
                        @mkdir($backupDir, 0777, true);
                    }
                    $backupFile = $backupDir . '/pin_' . time() . '.txt';
                    @file_put_contents($backupFile, "Para: $correo\nAsunto: $asunto\n\n$mensaje");

                    $_SESSION['auth_success'] = "Hemos enviado un código PIN de 6 dígitos a su correo electrónico.";
                    header('Location: index.php?action=verificar_pin');
                    exit();
                }else{
                    $_SESSION['auth_error'] = "El correo electrónico no se encuentra registrado en el sistema.";
                    $_SESSION['form_data'] = $_POST;
                    header('Location: index.php?action=recuperar');
                    exit();
                }
            }else{
                $_SESSION['auth_error'] = "Por favor, ingresa un correo válido.";
                $_SESSION['form_data'] = $_POST;
                header('Location: index.php?action=recuperar');
                exit();
            }
        }else{
            $error_message = $_SESSION['auth_error'] ?? null;
            $form_data = $_SESSION['form_data'] ?? [];
            unset($_SESSION['auth_error'], $_SESSION['form_data']);
            include 'Views/Login/recuperarPassword.php';
        }
    }

    public function verificarPin(): void{
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $correo = $_SESSION['correo_recuperacion'] ?? '';
        $pinGuardado = $_SESSION['recuperacion_pin'] ?? '';

        if (empty($correo) || empty($pinGuardado)) {
            $_SESSION['auth_error'] = "Por favor, ingresa tu correo para iniciar la recuperación.";
            header('Location: index.php?action=recuperar');
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $pinIngresado = trim((string)($_POST['pin'] ?? ''));

            // Verificar si expiró el tiempo (15 minutos)
            if (time() > ($_SESSION['pin_expira'] ?? 0)) {
                $_SESSION['auth_error'] = "El código PIN ha expirado. Por favor, solicita uno nuevo.";
                header('Location: index.php?action=verificar_pin');
                exit();
            }

            // Verificar intentos (máximo 5)
            $intentos = (int)($_SESSION['pin_intentos'] ?? 0) + 1;
            $_SESSION['pin_intentos'] = $intentos;

            if ($intentos > 5) {
                $_SESSION['auth_error'] = "Has superado el número de intentos permitidos. Por seguridad, solicita un nuevo código.";
                unset($_SESSION['recuperacion_pin'], $_SESSION['pin_expira'], $_SESSION['pin_dev_helper']);
                header('Location: index.php?action=recuperar');
                exit();
            }

            if ($pinIngresado === (string)$pinGuardado) {
                // PIN Correcto: Autorizar cambio de contraseña
                $_SESSION['pin_verificado'] = true;
                unset($_SESSION['recuperacion_pin'], $_SESSION['pin_dev_helper'], $_SESSION['pin_intentos']);
                header('Location: index.php?action=nueva_password');
                exit();
            } else {
                $restantes = 5 - $intentos;
                $_SESSION['auth_error'] = "El código PIN ingresado es incorrecto. Intentos restantes: {$restantes}.";
                header('Location: index.php?action=verificar_pin');
                exit();
            }
        } else {
            $error_message = $_SESSION['auth_error'] ?? null;
            $success_message = $_SESSION['auth_success'] ?? null;
            unset($_SESSION['auth_error'], $_SESSION['auth_success']);
            include 'Views/Login/verificarPin.php';
        }
    }

    public function reenviarPin(): void{
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $correo = $_SESSION['correo_recuperacion'] ?? '';
        if (empty($correo)) {
            header('Location: index.php?action=recuperar');
            exit();
        }

        // Generar nuevo PIN
        $pin = str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        $_SESSION['recuperacion_pin'] = $pin;
        $_SESSION['pin_expira'] = time() + (15 * 60);
        $_SESSION['pin_intentos'] = 0;
        $_SESSION['pin_dev_helper'] = $pin;

        $asunto = "=?UTF-8?B?" . base64_encode("Nuevo Código de verificación - Tienda La Caserita") . "?=";
        $mensaje = "Hola,\n\n"
                 . "Has solicitado un nuevo código PIN para restablecer tu contraseña.\n\n"
                 . "Tu nuevo código es: {$pin}\n\n"
                 . "Válido durante 15 minutos.\n\n"
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

        $_SESSION['auth_success'] = "¡Se ha enviado un nuevo código PIN a tu correo!";
        header('Location: index.php?action=verificar_pin');
        exit();
    }

    public function nuevaPassword(): void{
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // SEGURIDAD: Solo se permite si el PIN fue previamente verificado
        $correo = $_SESSION['correo_recuperacion'] ?? '';
        $pinVerificado = $_SESSION['pin_verificado'] ?? false;

        if (empty($correo) || !$pinVerificado) {
            $_SESSION['auth_error'] = "Debes validar tu código PIN antes de restablecer tu contraseña.";
            header('Location: index.php?action=recuperar');
            exit();
        }

        if($_SERVER['REQUEST_METHOD'] == 'POST'){
            $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_SPECIAL_CHARS);
            $confirm_password = filter_input(INPUT_POST, 'confirm_password', FILTER_SANITIZE_SPECIAL_CHARS);

            if($password && $confirm_password){
                if($password !== $confirm_password){
                    $_SESSION['auth_error'] = "Las contraseñas no coinciden.";
                    $_SESSION['form_data'] = $_POST;
                    header('Location: index.php?action=nueva_password');
                    exit();
                }else{
                    $this->model->actualizarPassword($correo, $password);
                    unset(
                        $_SESSION['correo_recuperacion'],
                        $_SESSION['recuperacion_pin'],
                        $_SESSION['pin_expira'],
                        $_SESSION['pin_intentos'],
                        $_SESSION['pin_verificado'],
                        $_SESSION['pin_dev_helper']
                    );
                    $_SESSION['success_message'] = "¡Tu contraseña ha sido actualizada con éxito! Ya puedes iniciar sesión.";
                    header('Location: index.php?action=login');
                    exit();
                }
            }else{
                $_SESSION['auth_error'] = "Por favor, completa todos los campos.";
                $_SESSION['form_data'] = $_POST;
                header('Location: index.php?action=nueva_password');
                exit();
            }
        }else{
            $error_message = $_SESSION['auth_error'] ?? null;
            $form_data = $_SESSION['form_data'] ?? [];
            unset($_SESSION['auth_error'], $_SESSION['form_data']);
            include 'Views/Login/nuevaPassword.php';
        }
    }

    public function homeUsuario(): void{
        if(session_status() == PHP_SESSION_NONE){ session_start(); }

        $categoryModel = new CategoryModel();
        $categorias_bd = $categoryModel->getCategoria();

        $momentModel = new MomentConModel();
        $moments_bd = $momentModel->getMomento();

        $grupModel = new GrupMomentModel();
        $grupMomento_bd = $grupModel->getGrupo();

        $productModel = new ProductoModel();
        $product_bd = $productModel->getProductofull();

        include 'Views/Home/home_usuario.php';
    }

    public function homeAdmin(): void{
        // El administrador tiene la misma experiencia de tienda/inicio que el cliente, con acceso a su panel desde el botón de usuario
        $this->homeUsuario();
    }

    public function logout(): void{
        if(session_status() == PHP_SESSION_NONE){ session_start(); }

        $carritos = $_SESSION['carritos'] ?? [];

        $_SESSION = [];

        $_SESSION['carritos'] = $carritos;

        header('Location: index.php?action=home_usuario');
        exit();
    }
}
?>
