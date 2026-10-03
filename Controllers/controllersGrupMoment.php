<?php
include_once 'Models/grupoMomentModel.php';
require_once 'Core/ajaxHelper.php';

class ControllerGrupMoment{
    private $model;

    public function __construct() {
        $this->model = new GrupMomentModel();
    }

    public function listarGrupos(){
        return $this->model->getGrupo();
    }

    public function crearGrupo(){
        $redirectTo = $_POST['redirect_to'] ?? ($_GET['redirect_to'] ?? 'categorias_admin');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: index.php?action=$redirectTo");
            exit();
        }

        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin') {
            if (AjaxHelper::isAjax()) AjaxHelper::jsonError("Acceso denegado.", 403);
            header('Location: index.php?action=login');
            exit();
        }

        $id_momento = filter_input(INPUT_POST, 'id_momento', FILTER_VALIDATE_INT);
        $nombre = trim(filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_SPECIAL_CHARS));
        $descripcion = trim(filter_input(INPUT_POST, 'descripcion', FILTER_SANITIZE_SPECIAL_CHARS));
        $color = '#15803d'; // Campo de color retirado según solicitud
        $categorias = $_POST['categorias'] ?? [];
        if (!is_array($categorias)) {
            $categorias = [];
        }
        $categorias = array_filter(array_map('intval', $categorias));
        $imagen = null;

        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $fileTempPath = $_FILES['imagen']['tmp_name'];
            $fileName = $_FILES['imagen']['name'];
            $fileSize = $_FILES['imagen']['size'];
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            $allowedExt = ['jpg', 'jpeg', 'png', 'webp'];

            if (in_array($fileExt, $allowedExt) && $fileSize <= 2097152) {
                $nuevoNombre = md5(time() . $fileName) . '.' . $fileExt;
                $uploadDir = './uploads/grupos/';

                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $destPath = $uploadDir . $nuevoNombre;

                if (move_uploaded_file($fileTempPath, $destPath)) {
                    $imagen = 'uploads/grupos/' . $nuevoNombre;
                }
            }
        }

        if ($imagen === null) {
            $imagenUrl = trim(filter_input(INPUT_POST, 'imagen_url', FILTER_SANITIZE_URL));
            if ($imagenUrl !== '') {
                $imagen = $imagenUrl;
            }
        }

        if ($id_momento && $nombre !== '') {
            $ok = $this->model->createGrupMoment($id_momento, $nombre, $descripcion, $imagen, $color, $categorias);

            if ($ok) {
                if (AjaxHelper::isAjax()) AjaxHelper::jsonResponse(true, "Agrupación \"$nombre\" registrada correctamente.");
                $_SESSION['flash_success'] = "Agrupación \"$nombre\" registrada correctamente.";
            } else {
                if (AjaxHelper::isAjax()) AjaxHelper::jsonError("Error al registrar la agrupación.", 500);
                $_SESSION['flash_error'] = "Error al registrar la agrupación.";
            }
        } else {
            if (AjaxHelper::isAjax()) AjaxHelper::jsonError("Selecciona una categoría padre y escribe el nombre de la agrupación.", 422);
            $_SESSION['flash_error'] = "Selecciona una categoría padre y escribe el nombre de la agrupación.";
        }

        header("Location: index.php?action=$redirectTo");
        exit();
    }

    public function actualizarGrupo() {
        $redirectTo = $_POST['redirect_to'] ?? ($_GET['redirect_to'] ?? 'categorias_admin');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: index.php?action=$redirectTo");
            exit();
        }

        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin') {
            if (AjaxHelper::isAjax()) AjaxHelper::jsonError("Acceso denegado.", 403);
            header('Location: index.php?action=login');
            exit();
        }

        $id = filter_input(INPUT_POST, 'id_grupo', FILTER_VALIDATE_INT);
        $id_momento = filter_input(INPUT_POST, 'id_momento', FILTER_VALIDATE_INT);
        $nombre = trim(filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_SPECIAL_CHARS));
        $descripcion = trim(filter_input(INPUT_POST, 'descripcion', FILTER_SANITIZE_SPECIAL_CHARS));
        $color = '#15803d'; // Campo de color retirado según solicitud
        $categorias = $_POST['categorias'] ?? [];
        if (!is_array($categorias)) {
            $categorias = [];
        }
        $categorias = array_filter(array_map('intval', $categorias));
        $imagen = null;

        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $fileTempPath = $_FILES['imagen']['tmp_name'];
            $fileName = $_FILES['imagen']['name'];
            $fileSize = $_FILES['imagen']['size'];
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            $allowedExt = ['jpg', 'jpeg', 'png', 'webp'];

            if (in_array($fileExt, $allowedExt) && $fileSize <= 2097152) {
                $nuevoNombre = md5(time() . $fileName) . '.' . $fileExt;
                $uploadDir = './uploads/grupos/';

                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $destPath = $uploadDir . $nuevoNombre;

                if (move_uploaded_file($fileTempPath, $destPath)) {
                    $imagen = 'uploads/grupos/' . $nuevoNombre;
                }
            }
        }

        if ($imagen === null) {
            $imagenUrl = trim(filter_input(INPUT_POST, 'imagen_url', FILTER_SANITIZE_URL));
            if ($imagenUrl !== '') {
                $imagen = $imagenUrl;
            }
        }

        if ($id && $id_momento && $nombre !== '') {
            $ok = $this->model->updateGrupMoment($id, $id_momento, $nombre, $descripcion, $imagen, $color, $categorias);

            if ($ok) {
                if (AjaxHelper::isAjax()) AjaxHelper::jsonResponse(true, "Agrupación \"$nombre\" actualizada correctamente.");
                $_SESSION['flash_success'] = "Agrupación \"$nombre\" actualizada correctamente.";
            } else {
                if (AjaxHelper::isAjax()) AjaxHelper::jsonError("Error al actualizar la agrupación.", 500);
                $_SESSION['flash_error'] = "Error al actualizar la agrupación.";
            }
        } else {
            if (AjaxHelper::isAjax()) AjaxHelper::jsonError("Datos inválidos para actualizar la agrupación.", 422);
            $_SESSION['flash_error'] = "Datos inválidos para actualizar la agrupación.";
        }

        header("Location: index.php?action=$redirectTo");
        exit();
    }

    public function eliminarGrupo() {
        if (session_status() == PHP_SESSION_NONE) session_start();
        $redirectTo = $_POST['redirect_to'] ?? ($_GET['redirect_to'] ?? 'categorias_admin');

        if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin') {
            if (AjaxHelper::isAjax()) AjaxHelper::jsonError("Acceso denegado.", 403);
            header('Location: index.php?action=login');
            exit();
        }

        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if ($id) {
            $this->model->deleteGrupMoment($id);
            if (AjaxHelper::isAjax()) AjaxHelper::jsonResponse(true, "Categoría inactivada.");
            $_SESSION['flash_success'] = "Categoría inactivada.";
        } else {
            if (AjaxHelper::isAjax()) AjaxHelper::jsonError("ID de categoría inválido.", 422);
            $_SESSION['flash_error'] = "ID de categoría inválido.";
        }

        header("Location: index.php?action=$redirectTo");
        exit();
    }

    public function toggleStatus() {
        if (session_status() == PHP_SESSION_NONE) session_start();
        $redirectTo = $_POST['redirect_to'] ?? ($_GET['redirect_to'] ?? 'categorias_admin');

        if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin') {
            if (AjaxHelper::isAjax()) AjaxHelper::jsonError("Acceso denegado.", 403);
            header('Location: index.php?action=login');
            exit();
        }

        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $estado = isset($_GET['estado']) ? (int)$_GET['estado'] : (isset($_POST['estado']) ? (int)$_POST['estado'] : null);
        $avisoAccion = 'estado_error';
        if ($id) {
            $actualizado = $this->model->toggleStatus($id, $estado);
            if ($actualizado) {
            $avisoAccion = 'estado_actualizado';
                if (AjaxHelper::isAjax()) AjaxHelper::jsonResponse(true, "Estado de la categoría actualizado.");
                $_SESSION['flash_success'] = "Estado de la categoría actualizado.";
            } else {
                if (AjaxHelper::isAjax()) AjaxHelper::jsonError("No se pudo actualizar el estado de la categoría.", 500);
                $_SESSION['flash_error'] = "No se pudo actualizar el estado de la categoría.";
            }
        } else {
            if (AjaxHelper::isAjax()) AjaxHelper::jsonError("ID de categoría inválido.", 422);
            $_SESSION['flash_error'] = "ID de categoría inválido.";
        }

        header("Location: index.php?action=$redirectTo&aviso=$avisoAccion");
        exit();
    }
}
?>