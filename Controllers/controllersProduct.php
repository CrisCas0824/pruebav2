<?php
include_once 'Models/productModel.php';
require_once 'Core/ajaxHelper.php';

class controllerProducto{
    private $model;

    public function __construct(){
        $this->model = new ProductoModel();
    }

    public function listarProductos(){
        return $this->model->getProduct();
    }

     public function crearProducto() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?action=home_admin');
            exit();
        }

        if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin') {
            if (AjaxHelper::isAjax()) AjaxHelper::jsonError("Acceso denegado.", 403);
            header('Location: index.php?action=login');
            exit();
        }

        $titulo = trim(filter_input(INPUT_POST, 'titulo', FILTER_SANITIZE_SPECIAL_CHARS));
        $descripcion = trim(filter_input(INPUT_POST, 'descripcion', FILTER_SANITIZE_SPECIAL_CHARS));
        $precio = filter_input(INPUT_POST, 'precio', FILTER_VALIDATE_FLOAT);
        $stock = filter_input(INPUT_POST, 'stock', FILTER_VALIDATE_FLOAT);
        $unidad_medida = $_POST['unidad_medida'] ?? 'UND';
        $stock_minimo = filter_input(INPUT_POST, 'stock_minimo', FILTER_VALIDATE_FLOAT);
        $ubicacion = trim(filter_input(INPUT_POST, 'ubicacion', FILTER_SANITIZE_SPECIAL_CHARS));

        if (!in_array($unidad_medida, ['UND', 'KG'])) {
            $unidad_medida = 'UND';
        }
        $es_sugerencia = isset($_POST['es_sugerencia']) ? 1 : 0;

        $id_categoria = $es_sugerencia ? null : filter_input(INPUT_POST, 'id_categoria', FILTER_VALIDATE_INT);
        $id_grupo = $es_sugerencia ? null : filter_input(INPUT_POST, 'id_grupo', FILTER_VALIDATE_INT);

        $redirect = trim($_POST['redirect_to'] ?? ($_GET['redirect_to'] ?? 'inventario'));
        $codigo_barras = trim($_POST['codigo_barras'] ?? '');
        if ($codigo_barras !== '' && $this->model->existeCodigoBarras($codigo_barras)) {
            $mensaje = 'Ya existe un producto con ese código de barras. Cada producto debe tener uno distinto.';
            if (AjaxHelper::isAjax()) AjaxHelper::jsonError($mensaje, 409);
            $_SESSION['flash_error'] = $mensaje;
            header("Location: index.php?action=$redirect");
            exit();
        }

        // Procesar hasta 4 imágenes
        $imagen_1 = $this->processImageUpload('imagen', 'imagen_url', null);
        if (!$imagen_1) $imagen_1 = $this->processImageUpload('imagen_1', 'imagen_url_1', null);
        $imagen_2 = $this->processImageUpload('imagen_2', 'imagen_url_2', null);
        $imagen_3 = $this->processImageUpload('imagen_3', 'imagen_url_3', null);
        $imagen_4 = $this->processImageUpload('imagen_4', 'imagen_url_4', null);

        if (!$titulo || $precio === false || $stock === false) {
            if (AjaxHelper::isAjax()) AjaxHelper::jsonError('Completa correctamente nombre, precio y stock.', 422);
            $_SESSION['flash_error'] = 'Completa correctamente nombre, precio y stock.';
            header("Location: index.php?action=$redirect");
            exit();
        }

        if (!$es_sugerencia && !$id_categoria) {
            if (AjaxHelper::isAjax()) AjaxHelper::jsonError('Para producto normal debes seleccionar categoría.', 422);
            $_SESSION['flash_error'] = 'Para producto normal debes seleccionar categoría.';
            header("Location: index.php?action=$redirect");
            exit();
        }

        if ($stock_minimo === false || $stock_minimo < 0) {
            $stock_minimo = 5;
        }

        $codigo = trim(filter_input(INPUT_POST, 'codigo', FILTER_SANITIZE_SPECIAL_CHARS));
        $codigo_barras = $codigo_barras !== '' ? $codigo_barras : null;

        $en_oferta = isset($_POST['en_oferta']) && $_POST['en_oferta'] == '1';
        $precio_anterior = null;

        if ($en_oferta) {
            $precio_anterior = filter_input(INPUT_POST, 'precio_anterior', FILTER_VALIDATE_FLOAT);
            if ($precio_anterior === false || $precio_anterior <= 0) {
                $precio_anterior = null;
            }
        }

        $ok = $this->model->createProduct($id_categoria, $id_grupo, $titulo, $descripcion, $precio, $unidad_medida, $stock, $imagen_1, $es_sugerencia, $stock_minimo, $ubicacion, $codigo, $precio_anterior, $imagen_2, $imagen_3, $imagen_4, $codigo_barras);

        if ($ok) {
            if (AjaxHelper::isAjax()) AjaxHelper::jsonResponse(true, 'Producto registrado correctamente.');
            $_SESSION['flash_success'] = 'Producto registrado correctamente.';
        } else {
            if (AjaxHelper::isAjax()) AjaxHelper::jsonError('Error al registrar producto.', 500);
            $_SESSION['flash_error'] = 'Error al registrar producto.';
        }

        header("Location: index.php?action=$redirect");
        exit();
    }

    public function eliminarProducto() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        $redirect = trim($_POST['redirect_to'] ?? ($_GET['redirect_to'] ?? 'inventario'));

        if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin') {
            if (AjaxHelper::isAjax()) AjaxHelper::jsonError("Acceso denegado.", 403);
            header('Location: index.php?action=login');
            exit();
        }

        $id_producto = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

        if ($id_producto) {
            $ventasCount = $this->model->getVentasCount($id_producto);
            if ($ventasCount > 0) {
                $msg = "No se puede eliminar este producto porque ya registra $ventasCount venta(s) en la tienda. Puedes cambiar su estado a Inactivo.";
                if (AjaxHelper::isAjax()) AjaxHelper::jsonError($msg, 422);
                $_SESSION['flash_error'] = $msg;
                header("Location: index.php?action=$redirect");
                exit();
            }

            $resultado = $this->model->deleteProduct($id_producto);
            if ($resultado['ok']) {
                if (AjaxHelper::isAjax()) AjaxHelper::jsonResponse(true, 'Producto eliminado correctamente.');
                $_SESSION['flash_success'] = 'Producto eliminado correctamente.';
            } else {
                if (AjaxHelper::isAjax()) AjaxHelper::jsonError($resultado['mensaje'], 422);
                $_SESSION['flash_error'] = $resultado['mensaje'];
            }
        } else {
            if (AjaxHelper::isAjax()) AjaxHelper::jsonError('ID de producto inválido.', 422);
            $_SESSION['flash_error'] = 'ID de producto inválido.';
        }

        header("Location: index.php?action=$redirect");
        exit();
    }

    public function toggleStatus() {
        if (session_status() == PHP_SESSION_NONE) session_start();
        $redirect = trim($_POST['redirect_to'] ?? ($_GET['redirect_to'] ?? 'inventario'));

        if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin') {
            if (AjaxHelper::isAjax()) AjaxHelper::jsonError("Acceso denegado.", 403);
            header('Location: index.php?action=login');
            exit();
        }

        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $estado = isset($_GET['estado']) ? (int)$_GET['estado'] : (isset($_POST['estado']) ? (int)$_POST['estado'] : null);
        if ($id) {
            $actualizado = $this->model->toggleStatus($id, $estado);
            if (!$actualizado) {
                if (AjaxHelper::isAjax()) AjaxHelper::jsonError('No se pudo actualizar el estado del producto.', 500);
                $_SESSION['flash_error'] = 'No se pudo actualizar el estado del producto.';
                header("Location: index.php?action=$redirect");
                exit();
            }
            if (AjaxHelper::isAjax()) AjaxHelper::jsonResponse(true, "Estado del producto actualizado.");
            $_SESSION['flash_success'] = "Estado del producto actualizado.";
        } else {
            if (AjaxHelper::isAjax()) AjaxHelper::jsonError("ID de producto inválido.", 422);
            $_SESSION['flash_error'] = "ID de producto inválido.";
        }

        header("Location: index.php?action=$redirect");
        exit();
    }

    public function actualizarProducto() {
        if(session_status() == PHP_SESSION_NONE){ session_start(); }

        $id_producto = filter_input(INPUT_POST, 'id_producto', FILTER_VALIDATE_INT);
        $titulo = trim($_POST['titulo'] ?? ($_POST['nombre'] ?? ''));
        $descripcion = trim($_POST['descripcion'] ?? '');
        $precio = filter_input(INPUT_POST, 'precio', FILTER_VALIDATE_FLOAT);
        $unidad_medida = $_POST['unidad_medida'] ?? 'UND';
        $stock = filter_input(INPUT_POST, 'stock', FILTER_VALIDATE_FLOAT);
        $stock_minimo = filter_input(INPUT_POST, 'stock_minimo', FILTER_VALIDATE_FLOAT);
        $ubicacion = trim($_POST['ubicacion'] ?? '');
        $es_sugerencia = isset($_POST['es_sugerencia']) ? 1 : 0;
        $redirect = trim($_POST['redirect_to'] ?? 'home_admin');
        if ($redirect !== 'inventario' && $redirect !== 'home_admin') {
            $redirect = 'home_admin';
        }

        $id_categoria = $es_sugerencia ? null : filter_input(INPUT_POST, 'id_categoria', FILTER_VALIDATE_INT);
        $id_grupo = $es_sugerencia ? null : filter_input(INPUT_POST, 'id_grupo', FILTER_VALIDATE_INT);

        $existing = $this->model->getProductoById($id_producto);
        if (!$id_categoria && $existing) {
            $id_categoria = $existing['id_categoria'] ?? null;
            $id_grupo = $id_grupo ?: ($existing['id_grupo'] ?? null);
        }

        $codigo_barras = trim($_POST['codigo_barras'] ?? '');
        if ($codigo_barras !== '' && $this->model->existeCodigoBarras($codigo_barras, $id_producto)) {
            $mensaje = 'Ese código de barras ya pertenece a otro producto.';
            if (AjaxHelper::isAjax()) AjaxHelper::jsonError($mensaje, 409);
            $_SESSION['flash_error'] = $mensaje;
            header("Location: index.php?action=$redirect");
            exit();
        }

        $imagen_actual_1 = $_POST['imagen_actual'] ?? ($existing['imagen'] ?? '');
        $imagen_actual_2 = $_POST['imagen_actual_2'] ?? ($existing['imagen_2'] ?? '');
        $imagen_actual_3 = $_POST['imagen_actual_3'] ?? ($existing['imagen_3'] ?? '');
        $imagen_actual_4 = $_POST['imagen_actual_4'] ?? ($existing['imagen_4'] ?? '');

        $imagen_1 = $this->processImageUpload('imagen', 'imagen_url', $imagen_actual_1);
        if ($imagen_1 === $imagen_actual_1 && isset($_FILES['imagen_1'])) {
            $imagen_1 = $this->processImageUpload('imagen_1', 'imagen_url_1', $imagen_actual_1);
        }
        $imagen_2 = $this->processImageUpload('imagen_2', 'imagen_url_2', $imagen_actual_2);
        $imagen_3 = $this->processImageUpload('imagen_3', 'imagen_url_3', $imagen_actual_3);
        $imagen_4 = $this->processImageUpload('imagen_4', 'imagen_url_4', $imagen_actual_4);

        if(!$id_producto || $titulo === '' || $precio === false || $stock === false){
            if (AjaxHelper::isAjax()) AjaxHelper::jsonError('Datos inválidos para actualizar producto.', 422);
            $_SESSION['flash_error'] = 'Datos inválidos para actualizar producto.';
            header("Location: index.php?action=$redirect");
            exit();
        }

        if(!$es_sugerencia && !$id_categoria){
            if (AjaxHelper::isAjax()) AjaxHelper::jsonError('Selecciona una categoría para el producto.', 422);
            $_SESSION['flash_error'] = 'Selecciona una categoría para el producto.';
            header("Location: index.php?action=$redirect");
            exit();
        }

        $codigo = trim(filter_input(INPUT_POST, 'codigo', FILTER_SANITIZE_SPECIAL_CHARS));
        $codigo_barras = $codigo_barras !== '' ? $codigo_barras : null;

        $en_oferta = isset($_POST['en_oferta']) && $_POST['en_oferta'] == '1';
        $precio_anterior = null;

        if ($en_oferta) {
            $precio_anterior = filter_input(INPUT_POST, 'precio_anterior', FILTER_VALIDATE_FLOAT);
            if ($precio_anterior === false || $precio_anterior <= 0) {
                $precio_anterior = null;
            }
        }

        $ok = $this->model->updateProducto(
            $id_producto,
            $id_categoria,
            $id_grupo,
            $titulo,
            $descripcion,
            $precio,
            $unidad_medida,
            $stock,
            $imagen_1,
            $es_sugerencia,
            $stock_minimo,
            $ubicacion,
            $codigo,
            $precio_anterior,
            $imagen_2,
            $imagen_3,
            $imagen_4,
            $codigo_barras
        );

        if ($ok) {
            if (AjaxHelper::isAjax()) AjaxHelper::jsonResponse(true, 'Producto actualizado correctamente.');
            $_SESSION['flash_success'] = 'Producto actualizado correctamente.';
        } else {
            if (AjaxHelper::isAjax()) AjaxHelper::jsonError('Error al actualizar producto.', 500);
            $_SESSION['flash_error'] = 'Error al actualizar producto.';
        }

        header("Location: index.php?action=$redirect");
        exit();
    }

    private function processImageUpload($fileKey, $urlKey, $currentValue = null) {
        if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] === UPLOAD_ERR_OK) {
            $fileTempPath = $_FILES[$fileKey]['tmp_name'];
            $fileName = $_FILES[$fileKey]['name'];
            $fileSize = $_FILES[$fileKey]['size'];
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowedExt = ['jpg', 'jpeg', 'png', 'webp'];

            if (in_array($fileExt, $allowedExt) && $fileSize <= 3145728) {
                $nuevoNombre = 'prod_' . time() . '_' . rand(100, 999) . '_' . $fileKey . '.' . $fileExt;
                $uploadDir = './uploads/productos/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                $destPath = $uploadDir . $nuevoNombre;
                if (move_uploaded_file($fileTempPath, $destPath)) {
                    return 'uploads/productos/' . $nuevoNombre;
                }
            }
        }
        $url = trim(filter_input(INPUT_POST, $urlKey, FILTER_SANITIZE_URL));
        if (!empty($url)) {
            return $url;
        }
        return $currentValue;
    }

    public function detalleProducto() {
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!$id) {
            header('Location: index.php?action=home_usuario');
            exit();
        }

        $producto = $this->model->getProductoById($id);
        if (!$producto || empty($producto['activo'])) {
            header('Location: index.php?action=home_usuario');
            exit();
        }

        // Obtener productos relacionados
        $relacionados = [];
        if (!empty($producto['id_categoria'])) {
            $relacionados = $this->model->getProductosRelacionados($id, $producto['id_categoria']);
        }
        if (empty($relacionados)) {
            $todos = $this->model->getProduct();
            $relacionados = array_slice(array_filter($todos, function($p) use ($id) {
                return (int)$p['id_producto'] !== (int)$id;
            }), 0, 4);
        }

        // Todos los productos para buscador y scripts
        $product_bd = $this->model->getProduct();

        // Pedidos activos del usuario para notificaciones
        $pedidosPendientes = [];
        if (isset($_SESSION['usuario_id'])) {
            require_once 'Models/pedidoModel.php';
            $pedidoModel = new PedidoModel();
            $todosPedidos = $pedidoModel->getPedidosByUsuario($_SESSION['usuario_id']);
            if(!empty($todosPedidos)){
                $pedidosPendientes = array_filter($todosPedidos, function($p) {
                    return in_array(strtolower($p['estado']), ['pendiente', 'en_proceso', 'en_camino', 'preparacion']);
                });
            }
        }

        require_once 'Views/Home/detalle_producto.php';
    }
}
?>