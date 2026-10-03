<?php
class AjaxHelper {
    public static function isAjax() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }

    public static function jsonResponse($success, $message, $data = null, $errors = [], $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $success,
            'message' => $message,
            'data'    => $data,
            'errors'  => $errors
        ]);
        exit;
    }

    public static function jsonError($message, $statusCode = 400, $errors = []) {
        self::jsonResponse(false, $message, null, $errors, $statusCode);
    }
}
?>
