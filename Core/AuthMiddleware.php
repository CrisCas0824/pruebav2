<?php
class AuthMiddleware {
    public static function verificarAcceso(array $rolesPermitidos = []) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['usuario_rol'])) {
            if (self::esAjax()) {
                http_response_code(401);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'status'  => 'error',
                    'message' => 'Sesión expirada o no autenticado. Inicia sesión.'
                ]);
                exit();
            }

            $targetAction = $_GET['action'] ?? '';
            if (in_array('admin', $rolesPermitidos, true) && !in_array('cliente', $rolesPermitidos, true)) {
                header('Location: index.php?action=login');
            } else {
                header('Location: index.php?action=home_usuario&open_auth=1');
            }
            exit();
        }

        if (!empty($rolesPermitidos) && !in_array($_SESSION['usuario_rol'], $rolesPermitidos, true)) {
            if (self::esAjax()) {
                http_response_code(403);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'status'  => 'error',
                    'message' => 'Acceso denegado. No tienes permisos para esta acción.'
                ]);
                exit();
            }

            $rolActual = $_SESSION['usuario_rol'];
            if ($rolActual === 'admin') {
                header('Location: index.php?action=home_admin');
            } elseif ($rolActual === 'cajero') {
                header('Location: index.php?action=pos');
            } else {
                header('Location: index.php?action=home_usuario');
            }
            exit();
        }
    }

    public static function requiereSesion() {
        self::verificarAcceso([]);
    }

    private static function esAjax(): bool {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) 
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            return true;
        }
        if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
            return true;
        }
        return false;
    }
}
?>
