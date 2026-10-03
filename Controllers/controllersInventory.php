<?php
include_once 'Models/inventoryModel.php';
include_once 'Models/categoryModel.php';
require_once 'Core/ajaxHelper.php';

class ControllerInventario {
    private $model;
    private $categoryModel;

    public function __construct() {
        $this->model = new InventarioModel();
        $this->categoryModel = new CategoryModel();
    }

    private function validarAdmin() {
        if(!isset($_SESSION['usuario_id'])){
            if (AjaxHelper::isAjax()) AjaxHelper::jsonError("Sesión expirada.", 401);
            header('Location: index.php?action=login');
            exit();
        }

        if($_SESSION['usuario_rol'] !== 'admin'){
            if (AjaxHelper::isAjax()) AjaxHelper::jsonError("Acceso denegado.", 403);
            header('Location: index.php?action=home_usuario');
            exit();
        }
    }

    public function inventario() {
        $this->validarAdmin();

        $nombre = htmlspecialchars($_SESSION['usuario_nombre']);

        $inventario_bd = $this->model->getInventario();
        $historial_reposicion = $this->model->getHistorialReposicion();
        $categorias = $this->categoryModel->getCategoria();

        include_once 'Models/grupoMomentModel.php';
        $grupModel = new GrupMomentModel();
        $grupos = $grupModel->getGrupo();

        include_once 'Models/momentConModel.php';
        $momModel = new MomentConModel();
        $moments = $momModel->getMomento();

        $masVendidos = $this->model->getMasVendidos(5);
        $menosVendidos = $this->model->getMenosVendidos(5);

        $flash_success = $_SESSION['flash_success'] ?? null;
        $flash_error = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);

        include 'Views/Inventario/inventario.php';
    }

    public function reponerStock() {
        $this->validarAdmin();

        if($_SERVER['REQUEST_METHOD'] !== 'POST'){
            header('Location: index.php?action=inventario');
            exit();
        }

        $id_producto = filter_input(INPUT_POST, 'id_producto', FILTER_VALIDATE_INT);
        $cantidad = filter_input(INPUT_POST, 'cantidad', FILTER_VALIDATE_FLOAT);
        $costo_unitario = filter_input(INPUT_POST, 'costo_unitario', FILTER_VALIDATE_FLOAT);
        $proveedor = trim(filter_input(INPUT_POST, 'proveedor', FILTER_SANITIZE_SPECIAL_CHARS));
        $observacion = trim(filter_input(INPUT_POST, 'observacion', FILTER_SANITIZE_SPECIAL_CHARS));
        $id_usuario = (int)$_SESSION['usuario_id'];

        if(!$id_producto || $cantidad === false || $cantidad <= 0){
            if (AjaxHelper::isAjax()) AjaxHelper::jsonError('Cantidad inválida para reposición.', 422);
            $_SESSION['flash_error'] = 'Cantidad inválida para reposición.';
            header('Location: index.php?action=inventario');
            exit();
        }

        if($costo_unitario === false || $costo_unitario < 0){
            $costo_unitario = 0;
        }

        if($proveedor === ''){
            $proveedor = 'Sin proveedor';
        }

        $ok = $this->model->reponerStock(
            $id_producto,
            $id_usuario,
            $cantidad,
            $costo_unitario,
            $proveedor,
            $observacion
        );

        if ($ok) {
            if (AjaxHelper::isAjax()) AjaxHelper::jsonResponse(true, 'Stock repuesto correctamente.');
            $_SESSION['flash_success'] = 'Stock repuesto correctamente.';
        } else {
            if (AjaxHelper::isAjax()) AjaxHelper::jsonError('Error al reponer stock.', 500);
            $_SESSION['flash_error'] = 'Error al reponer stock.';
        }

        header('Location: index.php?action=inventario');
        exit();
    }
}
?>