<?php
include_once 'Models/userAdminModel.php';
require_once 'Core/ajaxHelper.php';

class ControllerUserAdmin {
    private $model;

    public function __construct() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $this->model = new UserAdminModel();
    }

    private function validarAdmin() {
        if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin') {
            if (AjaxHelper::isAjax()) {
                AjaxHelper::jsonError("Sesión expirada o acceso denegado.", 401);
            }
            header("Location: index.php?action=home_usuario");
            exit();
        }
    }

    private function generarCsrfToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    private function validarCsrfToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    public function listarUsuarios() {
        $this->validarAdmin();
        $usuarios = $this->model->getUsuarios();

        $current_user_id = $_SESSION['usuario_id'] ?? null;
        if ($current_user_id) {
            usort($usuarios, function($a, $b) use ($current_user_id) {
                if ($a['id_usuario'] == $current_user_id) return -1;
                if ($b['id_usuario'] == $current_user_id) return 1;
                return 0;
            });
        }
        
        $total_usuarios = count($usuarios);
        $activos = 0;
        $admin = 0;
        $desactivados = 0;
        $cajeros = 0;
        $repartidores = 0;
        $clientes = 0;
        
        foreach ($usuarios as $u) {
            if ($u['activo'] == 1) $activos++;
            if ($u['activo'] == 0) $desactivados++;
            $r = strtolower($u['rol'] ?? '');
            if ($r === 'admin') $admin++;
            elseif ($r === 'cajero') $cajeros++;
            elseif ($r === 'repartidor') $repartidores++;
            elseif ($r === 'cliente') $clientes++;
        }
        
        $csrf_token = $this->generarCsrfToken();
        include 'Views/Usuarios/usuarios_admin.php';
    }

    public function crearAdministrador() {
        $this->validarAdmin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = filter_input(INPUT_POST, 'csrf_token', FILTER_SANITIZE_STRING);
            if (!$this->validarCsrfToken($token)) {
                if (AjaxHelper::isAjax()) AjaxHelper::jsonError("Error de validación CSRF.", 419);
                $_SESSION['flash_error'] = "Error de validación de seguridad.";
                header("Location: index.php?action=usuarios_admin");
                exit();
            }

            $nombre = filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_STRING);
            $apellido = filter_input(INPUT_POST, 'apellido', FILTER_SANITIZE_STRING) ?: '';
            $correo = filter_input(INPUT_POST, 'correo', FILTER_SANITIZE_EMAIL);
            $telefono = filter_input(INPUT_POST, 'telefono', FILTER_SANITIZE_STRING) ?: '';
            $password = $_POST['password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';

            if (empty($nombre) || empty($correo) || empty($password)) {
                if (AjaxHelper::isAjax()) AjaxHelper::jsonError("Nombre, correo y contraseña son obligatorios.", 422);
                $_SESSION['flash_error'] = "Nombre, correo y contraseña son obligatorios.";
            } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                if (AjaxHelper::isAjax()) AjaxHelper::jsonError("El correo electrónico no es válido.", 422);
                $_SESSION['flash_error'] = "El correo electrónico no es válido.";
            } elseif ($password !== $confirm_password) {
                if (AjaxHelper::isAjax()) AjaxHelper::jsonError("Las contraseñas no coinciden.", 422);
                $_SESSION['flash_error'] = "Las contraseñas no coinciden.";
            } elseif ($this->model->correoExiste($correo)) {
                if (AjaxHelper::isAjax()) AjaxHelper::jsonError("El correo electrónico ya está registrado.", 422);
                $_SESSION['flash_error'] = "El correo electrónico ya está registrado.";
            } else {
                if ($this->model->createAdministrador($nombre, $apellido, $correo, $telefono, $password)) {
                    if (AjaxHelper::isAjax()) AjaxHelper::jsonResponse(true, "Administrador creado exitosamente.");
                    $_SESSION['flash_success'] = "Administrador creado exitosamente.";
                } else {
                    if (AjaxHelper::isAjax()) AjaxHelper::jsonError("Error interno al crear el administrador.", 500);
                    $_SESSION['flash_error'] = "Error al crear administrador en la base de datos.";
                }
            }
            header("Location: index.php?action=usuarios_admin");
            exit();
        }
    }

    public function crearCajero() {
        $this->validarAdmin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = filter_input(INPUT_POST, 'csrf_token', FILTER_SANITIZE_STRING);
            if (!$this->validarCsrfToken($token)) {
                if (AjaxHelper::isAjax()) AjaxHelper::jsonError("Error de validación CSRF.", 419);
                $_SESSION['flash_error'] = "Error de validación de seguridad.";
                header("Location: index.php?action=usuarios_admin");
                exit();
            }

            $nombre = filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_STRING);
            $apellido = filter_input(INPUT_POST, 'apellido', FILTER_SANITIZE_STRING) ?: '';
            $correo = filter_input(INPUT_POST, 'correo', FILTER_SANITIZE_EMAIL);
            $telefono = filter_input(INPUT_POST, 'telefono', FILTER_SANITIZE_STRING) ?: '';
            $password = $_POST['password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';

            if (empty($nombre) || empty($correo) || empty($password)) {
                if (AjaxHelper::isAjax()) AjaxHelper::jsonError("Nombre, correo y contraseña son obligatorios.", 422);
                $_SESSION['flash_error'] = "Nombre, correo y contraseña son obligatorios.";
            } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                if (AjaxHelper::isAjax()) AjaxHelper::jsonError("El correo electrónico no es válido.", 422);
                $_SESSION['flash_error'] = "El correo electrónico no es válido.";
            } elseif ($password !== $confirm_password) {
                if (AjaxHelper::isAjax()) AjaxHelper::jsonError("Las contraseñas no coinciden.", 422);
                $_SESSION['flash_error'] = "Las contraseñas no coinciden.";
            } elseif ($this->model->correoExiste($correo)) {
                if (AjaxHelper::isAjax()) AjaxHelper::jsonError("El correo electrónico ya está registrado.", 422);
                $_SESSION['flash_error'] = "El correo electrónico ya está registrado.";
            } else {
                if ($this->model->createCajero($nombre, $apellido, $correo, $telefono, $password)) {
                    if (AjaxHelper::isAjax()) AjaxHelper::jsonResponse(true, "Cajero POS creado exitosamente.");
                    $_SESSION['flash_success'] = "Cajero POS creado exitosamente.";
                } else {
                    if (AjaxHelper::isAjax()) AjaxHelper::jsonError("Error interno al crear el cajero.", 500);
                    $_SESSION['flash_error'] = "Error al crear el cajero en la base de datos.";
                }
            }
            header("Location: index.php?action=usuarios_admin");
            exit();
        }
    }

    public function crearRepartidor() {
        $this->validarAdmin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = filter_input(INPUT_POST, 'csrf_token', FILTER_SANITIZE_STRING);
            if (!$this->validarCsrfToken($token)) {
                if (AjaxHelper::isAjax()) AjaxHelper::jsonError("Error de validación CSRF.", 419);
                $_SESSION['flash_error'] = "Error de validación de seguridad.";
                header("Location: index.php?action=usuarios_admin");
                exit();
            }

            $nombre = filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_STRING);
            $apellido = filter_input(INPUT_POST, 'apellido', FILTER_SANITIZE_STRING) ?: '';
            $correo = filter_input(INPUT_POST, 'correo', FILTER_SANITIZE_EMAIL);
            $telefono = filter_input(INPUT_POST, 'telefono', FILTER_SANITIZE_STRING) ?: '';
            $password = $_POST['password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';

            if (empty($nombre) || empty($correo) || empty($password)) {
                if (AjaxHelper::isAjax()) AjaxHelper::jsonError("Nombre, correo y contraseña son obligatorios.", 422);
                $_SESSION['flash_error'] = "Nombre, correo y contraseña son obligatorios.";
            } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                if (AjaxHelper::isAjax()) AjaxHelper::jsonError("El correo electrónico no es válido.", 422);
                $_SESSION['flash_error'] = "El correo electrónico no es válido.";
            } elseif ($password !== $confirm_password) {
                if (AjaxHelper::isAjax()) AjaxHelper::jsonError("Las contraseñas no coinciden.", 422);
                $_SESSION['flash_error'] = "Las contraseñas no coinciden.";
            } elseif ($this->model->correoExiste($correo)) {
                if (AjaxHelper::isAjax()) AjaxHelper::jsonError("El correo electrónico ya está registrado.", 422);
                $_SESSION['flash_error'] = "El correo electrónico ya está registrado.";
            } else {
                if ($this->model->createRepartidor($nombre, $apellido, $correo, $telefono, $password)) {
                    if (AjaxHelper::isAjax()) AjaxHelper::jsonResponse(true, "Repartidor Delivery creado exitosamente.");
                    $_SESSION['flash_success'] = "Repartidor Delivery creado exitosamente.";
                } else {
                    if (AjaxHelper::isAjax()) AjaxHelper::jsonError("Error interno al crear el repartidor.", 500);
                    $_SESSION['flash_error'] = "Error al crear el repartidor en la base de datos.";
                }
            }
            header("Location: index.php?action=usuarios_admin");
            exit();
        }
    }

    public function actualizarUsuario() {
        $this->validarAdmin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = filter_input(INPUT_POST, 'csrf_token', FILTER_SANITIZE_STRING);
            if (!$this->validarCsrfToken($token)) {
                if (AjaxHelper::isAjax()) AjaxHelper::jsonError("Error de validación CSRF.", 419);
                $_SESSION['flash_error'] = "Error de validación de seguridad.";
                header("Location: index.php?action=usuarios_admin");
                exit();
            }

            $id_usuario = filter_input(INPUT_POST, 'id_usuario', FILTER_VALIDATE_INT);
            $nombre = filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_STRING);
            $apellido = filter_input(INPUT_POST, 'apellido', FILTER_SANITIZE_STRING) ?: '';
            $correo = filter_input(INPUT_POST, 'correo', FILTER_SANITIZE_EMAIL);
            $telefono = filter_input(INPUT_POST, 'telefono', FILTER_SANITIZE_STRING) ?: '';
            $rol = filter_input(INPUT_POST, 'rol', FILTER_SANITIZE_STRING);
            $password = !empty($_POST['password']) ? $_POST['password'] : null;
            $activo = isset($_POST['activo']) ? (int)$_POST['activo'] : null;

            if (!$id_usuario || empty($nombre) || empty($correo) || empty($rol)) {
                if (AjaxHelper::isAjax()) AjaxHelper::jsonError("Faltan datos obligatorios.", 422);
                $_SESSION['flash_error'] = "Datos incompletos.";
                header("Location: index.php?action=usuarios_admin");
                exit();
            }
            
            if ($rol !== 'cliente' && $rol !== 'admin' && $rol !== 'cajero') {
                $rol = 'cliente';
            }

            if ($this->model->correoExisteExceptoUsuario($correo, $id_usuario)) {
                if (AjaxHelper::isAjax()) AjaxHelper::jsonError("El correo ya está en uso por otra cuenta.", 422);
                $_SESSION['flash_error'] = "El correo ya está en uso por otra cuenta.";
                header("Location: index.php?action=usuarios_admin");
                exit();
            }

            $usuario_actual = $this->model->getUsuarioById($id_usuario);
            if (!$usuario_actual) {
                if (AjaxHelper::isAjax()) AjaxHelper::jsonError("El usuario no existe.", 404);
                $_SESSION['flash_error'] = "El usuario no existe.";
                header("Location: index.php?action=usuarios_admin");
                exit();
            }

            if ($_SESSION['usuario_id'] == $id_usuario) {
                if ($rol !== 'admin') {
                    if (AjaxHelper::isAjax()) AjaxHelper::jsonError("No puedes cambiar tu propio rol a usuario.", 403);
                    $_SESSION['flash_error'] = "No puedes cambiar tu propio rol a usuario.";
                    header("Location: index.php?action=usuarios_admin");
                    exit();
                }
                if ($activo === 0) {
                    if (AjaxHelper::isAjax()) AjaxHelper::jsonError("No puedes desactivar tu propia cuenta.", 403);
                    $_SESSION['flash_error'] = "No puedes desactivar tu propia cuenta.";
                    header("Location: index.php?action=usuarios_admin");
                    exit();
                }
            } else {
                if ($usuario_actual['rol'] === 'admin' && $rol === 'cliente') {
                    if ($this->model->contarAdministradoresActivos() <= 1 && $usuario_actual['activo'] == 1) {
                        if (AjaxHelper::isAjax()) AjaxHelper::jsonError("No puedes quitar el rol admin al último administrador activo.", 403);
                        $_SESSION['flash_error'] = "No puedes quitar el rol admin al último administrador activo.";
                        header("Location: index.php?action=usuarios_admin");
                        exit();
                    }
                }
            }

            if ($this->model->updateUsuario($id_usuario, $nombre, $apellido, $correo, $telefono, $rol, $password)) {
                if ($activo !== null) {
                    $this->model->cambiarEstadoUsuario($id_usuario, $activo);
                }

                if ($_SESSION['usuario_id'] == $id_usuario) {
                    $_SESSION['usuario_nombre'] = $nombre;
                }

                if (AjaxHelper::isAjax()) AjaxHelper::jsonResponse(true, "Usuario actualizado exitosamente.");
                $_SESSION['flash_success'] = "Usuario actualizado exitosamente.";
            } else {
                if (AjaxHelper::isAjax()) AjaxHelper::jsonError("Error al actualizar el usuario.", 500);
                $_SESSION['flash_error'] = "Error al actualizar el usuario.";
            }

            header("Location: index.php?action=usuarios_admin");
            exit();
        }
    }

    public function cambiarEstadoUsuario() {
        $this->validarAdmin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = filter_input(INPUT_POST, 'csrf_token', FILTER_SANITIZE_STRING);
            if (!$this->validarCsrfToken($token)) {
                if (AjaxHelper::isAjax()) AjaxHelper::jsonError("Error de validación CSRF.", 419);
                $_SESSION['flash_error'] = "Error de seguridad.";
                header("Location: index.php?action=usuarios_admin");
                exit();
            }

            $id_usuario = filter_input(INPUT_POST, 'id_usuario', FILTER_VALIDATE_INT);
            $activo = filter_input(INPUT_POST, 'activo', FILTER_VALIDATE_INT);
            
            if (!$id_usuario) {
                if (AjaxHelper::isAjax()) AjaxHelper::jsonError("ID de usuario inválido.", 422);
                $_SESSION['flash_error'] = "ID de usuario inválido.";
                header("Location: index.php?action=usuarios_admin");
                exit();
            }

            if ($_SESSION['usuario_id'] == $id_usuario && $activo == 0) {
                if (AjaxHelper::isAjax()) AjaxHelper::jsonError("No puedes desactivar tu propia cuenta.", 403);
                $_SESSION['flash_error'] = "No puedes desactivar tu propia cuenta.";
                header("Location: index.php?action=usuarios_admin");
                exit();
            }

            $usuario_actual = $this->model->getUsuarioById($id_usuario);
            if ($usuario_actual['rol'] === 'admin' && $activo == 0) {
                if ($this->model->contarAdministradoresActivos() <= 1) {
                    if (AjaxHelper::isAjax()) AjaxHelper::jsonError("No puedes desactivar al último administrador activo.", 403);
                    $_SESSION['flash_error'] = "No puedes desactivar al último administrador activo del sistema.";
                    header("Location: index.php?action=usuarios_admin");
                    exit();
                }
            }

            if ($this->model->cambiarEstadoUsuario($id_usuario, $activo)) {
                $msg = $activo ? "Usuario reactivado exitosamente." : "Usuario desactivado.";
                if (AjaxHelper::isAjax()) AjaxHelper::jsonResponse(true, $msg);
                $_SESSION['flash_success'] = $msg;
            } else {
                if (AjaxHelper::isAjax()) AjaxHelper::jsonError("Error interno al cambiar estado.", 500);
                $_SESSION['flash_error'] = "Error al cambiar el estado del usuario.";
            }

            header("Location: index.php?action=usuarios_admin");
            exit();
        }
    }

    public function eliminarUsuario() {
        $this->validarAdmin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = filter_input(INPUT_POST, 'csrf_token', FILTER_SANITIZE_STRING);
            if (!$this->validarCsrfToken($token)) {
                if (AjaxHelper::isAjax()) AjaxHelper::jsonError("Error de seguridad CSRF.", 419);
                $_SESSION['flash_error'] = "Error de validación de seguridad.";
                header("Location: index.php?action=usuarios_admin");
                exit();
            }

            $id_usuario = filter_input(INPUT_POST, 'id_usuario', FILTER_VALIDATE_INT);
            if (!$id_usuario) {
                if (AjaxHelper::isAjax()) AjaxHelper::jsonError("ID de usuario inválido.", 422);
                $_SESSION['flash_error'] = "ID de usuario inválido.";
                header("Location: index.php?action=usuarios_admin");
                exit();
            }

            if ($_SESSION['usuario_id'] == $id_usuario) {
                if (AjaxHelper::isAjax()) AjaxHelper::jsonError("No puedes eliminar tu propia cuenta.", 403);
                $_SESSION['flash_error'] = "No puedes eliminar tu propia cuenta.";
                header("Location: index.php?action=usuarios_admin");
                exit();
            }

            $usuario_actual = $this->model->getUsuarioById($id_usuario);
            if (!$usuario_actual) {
                if (AjaxHelper::isAjax()) AjaxHelper::jsonError("El usuario no existe.", 404);
                $_SESSION['flash_error'] = "El usuario no existe.";
                header("Location: index.php?action=usuarios_admin");
                exit();
            }

            if ($usuario_actual['rol'] === 'admin' && $this->model->contarAdministradoresActivos() <= 1) {
                if (AjaxHelper::isAjax()) AjaxHelper::jsonError("No puedes eliminar al único administrador activo.", 403);
                $_SESSION['flash_error'] = "No puedes eliminar al único administrador activo.";
                header("Location: index.php?action=usuarios_admin");
                exit();
            }

            $actividad = $this->model->contarActividadUsuario($id_usuario);
            if ($actividad > 0) {
                if (AjaxHelper::isAjax()) AjaxHelper::jsonError("El usuario tiene actividad registrada y no puede eliminarse; solo puede inactivarse.", 400);
                $_SESSION['flash_error'] = "El usuario tiene movimientos registrados. Solo puede ser inactivado.";
                header("Location: index.php?action=usuarios_admin");
                exit();
            }

            if ($this->model->eliminarUsuario($id_usuario)) {
                $nombreCompleto = trim($usuario_actual['nombre'] . ' ' . $usuario_actual['apellido']);
                $msg = 'Usuario "' . htmlspecialchars($nombreCompleto) . '" eliminado permanentemente.';
                if (AjaxHelper::isAjax()) AjaxHelper::jsonResponse(true, $msg);
                $_SESSION['flash_success'] = $msg;
            } else {
                if (AjaxHelper::isAjax()) AjaxHelper::jsonError("Error interno al eliminar el usuario.", 500);
                $_SESSION['flash_error'] = "No se pudo eliminar el usuario.";
            }

            header("Location: index.php?action=usuarios_admin");
            exit();
        }
    }

    public function miPerfilAdmin() {
        $this->validarAdmin();
        $idUsuario = (int)$_SESSION['usuario_id'];
        $usuario = $this->model->getUsuarioById($idUsuario);
        $csrf_token = $this->generarCsrfToken();
        
        $flash_success = $_SESSION['flash_success'] ?? null;
        $flash_error = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);

        $lockTime = $_SESSION['password_lock_until_admin'] ?? 0;
        $remainingLock = max(0, $lockTime - time());

        include 'Views/Usuarios/mi_perfil_admin.php';
    }

    public function actualizarPerfilAdmin() {
        $this->validarAdmin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = filter_input(INPUT_POST, 'csrf_token', FILTER_SANITIZE_STRING);
            if (!$this->validarCsrfToken($token)) {
                if (AjaxHelper::isAjax()) AjaxHelper::jsonError("Solicitud expirada.", 403);
                $_SESSION['flash_error'] = "La solicitud ha expirado.";
                header("Location: index.php?action=mi_perfil_admin");
                exit();
            }

            $idUsuario = (int)$_SESSION['usuario_id'];
            $nombre = trim($_POST['nombre'] ?? '');
            $apellido = trim($_POST['apellido'] ?? '');
            $correo = trim($_POST['correo'] ?? '');
            $telefono = trim($_POST['telefono'] ?? '');

            $errores = [];
            if ($nombre === '') $errores['nombre'] = 'El nombre es obligatorio.';
            if ($apellido === '') $errores['apellido'] = 'El apellido es obligatorio.';
            if ($correo === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                $errores['correo'] = 'Ingresa un correo válido.';
            } else if ($this->model->correoExisteExceptoUsuario($correo, $idUsuario)) {
                $errores['correo'] = 'Este correo ya está registrado.';
            }

            if (!empty($errores)) {
                if (AjaxHelper::isAjax()) {
                    http_response_code(422);
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode([
                        'success' => false,
                        'message' => 'Corrige los campos indicados.',
                        'errors' => $errores
                    ]);
                    exit();
                }
                $_SESSION['flash_error'] = 'Revisa los datos ingresados.';
                header("Location: index.php?action=mi_perfil_admin");
                exit();
            }

            $ok = $this->model->updateUsuario($idUsuario, $nombre, $apellido, $correo, $telefono, 'admin');
            if ($ok) {
                $_SESSION['usuario_nombre'] = trim($nombre . ' ' . $apellido);
                $_SESSION['usuario_correo'] = $correo;
                $msg = 'Tus datos fueron actualizados correctamente.';
                if (AjaxHelper::isAjax()) {
                    AjaxHelper::jsonResponse(true, $msg, [
                        'usuario' => $this->model->getUsuarioById($idUsuario)
                    ]);
                }
                $_SESSION['flash_success'] = $msg;
            } else {
                $msg = 'No se pudieron actualizar tus datos.';
                if (AjaxHelper::isAjax()) AjaxHelper::jsonError($msg, 500);
                $_SESSION['flash_error'] = $msg;
            }

            header("Location: index.php?action=mi_perfil_admin");
            exit();
        }
    }

    public function cambiarPasswordAdmin() {
        $this->validarAdmin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = filter_input(INPUT_POST, 'csrf_token', FILTER_SANITIZE_STRING);
            if (!$this->validarCsrfToken($token)) {
                if (AjaxHelper::isAjax()) AjaxHelper::jsonError("Solicitud expirada.", 403);
                $_SESSION['flash_error'] = "La solicitud ha expirado.";
                header("Location: index.php?action=mi_perfil_admin");
                exit();
            }

            $idUsuario = (int)$_SESSION['usuario_id'];
            $passwordActual = $_POST['password_actual'] ?? '';
            $passwordNueva = $_POST['password_nueva'] ?? '';
            $passwordConfirmacion = $_POST['password_confirmacion'] ?? '';

            $usuario = $this->model->getUsuarioById($idUsuario);
            
            $errores = [];
            if (!password_verify($passwordActual, $usuario['password'])) {
                $errores['password_actual'] = 'La contraseña actual es incorrecta.';
            }
            if (strlen($passwordNueva) < 8) {
                $errores['password_nueva'] = 'La contraseña debe tener al menos 8 caracteres.';
            }
            if ($passwordNueva !== $passwordConfirmacion) {
                $errores['password_confirmacion'] = 'La confirmación de la contraseña no coincide.';
            }

            if (!empty($errores)) {
                if (AjaxHelper::isAjax()) {
                    http_response_code(422);
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode([
                        'success' => false,
                        'message' => 'Corrige los campos indicados.',
                        'errors' => $errores
                    ]);
                    exit();
                }
                $_SESSION['flash_error'] = 'Revisa los datos ingresados.';
                header("Location: index.php?action=mi_perfil_admin");
                exit();
            }

            $ok = $this->model->actualizarPassword($idUsuario, $passwordNueva);
            if ($ok) {
                $msg = 'Tu contraseña fue actualizada correctamente.';
                if (AjaxHelper::isAjax()) {
                    AjaxHelper::jsonResponse(true, $msg);
                }
                $_SESSION['flash_success'] = $msg;
            } else {
                $msg = 'No se pudo actualizar tu contraseña.';
                if (AjaxHelper::isAjax()) AjaxHelper::jsonError($msg, 500);
                $_SESSION['flash_error'] = $msg;
            }

            header("Location: index.php?action=mi_perfil_admin");
            exit();
        }
    }

    public function verificarPasswordActualAdmin() {
        $this->validarAdmin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = filter_input(INPUT_POST, 'csrf_token', FILTER_SANITIZE_STRING);
            if (!$this->validarCsrfToken($token)) {
                if (AjaxHelper::isAjax()) AjaxHelper::jsonError("Solicitud expirada.", 403);
                $_SESSION['flash_error'] = "La solicitud ha expirado.";
                header("Location: index.php?action=mi_perfil_admin");
                exit();
            }

            $idUsuario = (int)$_SESSION['usuario_id'];
            $lockTime = $_SESSION['password_lock_until_admin'] ?? 0;
            if (time() < $lockTime) {
                $remaining = $lockTime - time();
                if (AjaxHelper::isAjax()) {
                    AjaxHelper::jsonError("Por el momento no puede cambiar la contraseña, bloqueado por {$remaining} segundos.", 429);
                }
                exit();
            }

            $passwordActual = $_POST['password_actual'] ?? '';
            if ($passwordActual === '') {
                if (AjaxHelper::isAjax()) AjaxHelper::jsonError('Ingresa tu contraseña actual.', 400);
                exit();
            }

            $usuario = $this->model->getUsuarioById($idUsuario);
            if (!$usuario || !password_verify($passwordActual, $usuario['password'])) {
                $attempts = ($_SESSION['password_attempts_admin'] ?? 0) + 1;
                $_SESSION['password_attempts_admin'] = $attempts;

                if ($attempts >= 6) {
                    $_SESSION['password_lock_until_admin'] = time() + 30;
                    $_SESSION['password_attempts_admin'] = 0;
                    if (AjaxHelper::isAjax()) {
                        AjaxHelper::jsonError("Por el momento no puede cambiar la contraseña, bloqueado por 30 segundos.", 429);
                    }
                    exit();
                }

                $remainingAttempts = 6 - $attempts;
                if (AjaxHelper::isAjax()) {
                    AjaxHelper::jsonError("La contraseña actual es incorrecta. Te quedan {$remainingAttempts} intentos.", 401);
                }
                exit();
            }

            $_SESSION['password_attempts_admin'] = 0;
            if (AjaxHelper::isAjax()) {
                AjaxHelper::jsonResponse(true, 'Contraseña verificada correctamente.');
            }
            exit();
        }
    }
}
?>
