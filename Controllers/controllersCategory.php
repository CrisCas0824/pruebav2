<?php
include_once 'Models/categoryModel.php';
include_once 'Models/momentConModel.php';
include_once 'Models/grupoMomentModel.php';
require_once 'Core/ajaxHelper.php';

class ControllerCategory {
    private $model;

    public function __construct() {
        $this->model = new CategoryModel();
    }

    public function listaCategorias() {
        return $this->model->getCategoria();
    }

    public function vistaAdminCategorias() {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin') {
            header('Location: index.php?action=login');
            exit();
        }

        $categorias_bd = $this->model->getCategoria();

        $momentModel = new MomentConModel();
        $moments_bd = $momentModel->getMomento();

        $grupModel = new GrupMomentModel();
        $grupMomento_bd = $grupModel->getGrupo();

        $flash_success = $_SESSION['flash_success'] ?? null;
        $flash_error   = $_SESSION['flash_error']   ?? null;
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);

        include 'Views/Admin/categorias_admin.php';
    }

    public function verCategoria() {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }

        $id_categoria = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_GET['id_categoria']) ? (int)$_GET['id_categoria'] : null);
        $id_momento = isset($_GET['id_momento']) ? (int)$_GET['id_momento'] : (isset($_GET['momento']) ? (int)$_GET['momento'] : null);
        $nombre = isset($_GET['nombre']) ? trim($_GET['nombre']) : null;
        $ids = isset($_GET['ids']) ? trim($_GET['ids']) : null;
        $sub = isset($_GET['sub']) ? trim($_GET['sub']) : null;
        $id_grupo = isset($_GET['grupo']) ? (int)$_GET['grupo'] : (isset($_GET['id_grupo']) ? (int)$_GET['id_grupo'] : null);
        $esOfertas = isset($_GET['ofertas']) && $_GET['ofertas'] == '1';

        // Modelos necesarios
        require_once 'Models/productModel.php';
        require_once 'Models/momentConModel.php';
        require_once 'Models/grupoMomentModel.php';
        require_once 'Models/pedidoModel.php';

        $prodModel = new ProductoModel();
        $momentModel = new MomentConModel();
        $grupModel = new GrupMomentModel();

        $categorias_bd = $this->model->getCategoria();
        $moments_bd = $momentModel->getMomento();
        $grupMomento_bd = $grupModel->getGrupo();

        // Obtener todos los productos para filtrar y para alimentar el modal de detalles
        $todosProductos = $prodModel->getProductofull();
        if (empty($todosProductos)) {
            $todosProductos = $prodModel->getProduct();
        }

        $categoriaActual = null;
        $tituloCategoria = 'Categoría';
        $descripcionCategoria = '';
        $iconoCategoria = '📦';
        $productosFiltrados = [];

        if ($esOfertas || ($nombre && strtolower($nombre) === 'ofertas')) {
            $tituloCategoria = 'Ofertas';
            $descripcionCategoria = '';
            $productosFiltrados = array_filter($todosProductos, function($p) {
                return (!empty($p['precio_anterior']) && (float)$p['precio_anterior'] > (float)$p['precio']) || !empty($p['es_sugerencia']);
            });
        } elseif ($id_momento) {
            $momentoActual = $momentModel->getMomentoById($id_momento);
            if ($momentoActual) {
                $tituloCategoria = $nombre ?: $momentoActual['nombre'];
            } elseif ($nombre) {
                $tituloCategoria = $nombre;
            }
            $productosFiltrados = array_filter($todosProductos, function($p) use ($id_momento) {
                return (int)($p['id_momento'] ?? 0) === $id_momento;
            });
        } elseif ($id_categoria) {
            $categoriaActual = $this->model->getCategoryById($id_categoria);
            if ($categoriaActual) {
                $tituloCategoria = $nombre ?: $categoriaActual['nombre'];
                $descripcionCategoria = $categoriaActual['descripcion'] ?? '';
            } elseif ($nombre) {
                $tituloCategoria = $nombre;
            }
            $productosFiltrados = array_filter($todosProductos, function($p) use ($id_categoria, $sub) {
                $match = (int)($p['id_categoria'] ?? 0) === $id_categoria;
                if ($match && $sub) {
                    return (stripos($p['titulo'] ?? '', $sub) !== false || stripos($p['descripcion'] ?? '', $sub) !== false);
                }
                return $match;
            });
            if ($sub && empty($productosFiltrados)) {
                $productosFiltrados = array_filter($todosProductos, function($p) use ($id_categoria) {
                    return (int)($p['id_categoria'] ?? 0) === $id_categoria;
                });
            }
        } elseif ($ids) {
            $idList = array_map('intval', explode(',', $ids));
            $tituloCategoria = $nombre ?: 'Categoría';
            $productosFiltrados = array_filter($todosProductos, function($p) use ($idList, $sub) {
                $catId = (int)($p['id_categoria'] ?? 0);
                $grpId = (int)($p['id_grupo'] ?? 0);
                $momId = (int)($p['id_momento'] ?? 0);
                $match = in_array($catId, $idList, true) || in_array($grpId, $idList, true) || in_array($momId, $idList, true);
                if ($match && $sub) {
                    return (stripos($p['titulo'] ?? '', $sub) !== false || stripos($p['descripcion'] ?? '', $sub) !== false);
                }
                return $match;
            });
            if (empty($productosFiltrados) && $nombre) {
                $term = mb_strtolower(trim($nombre));
                $productosFiltrados = array_filter($todosProductos, function($p) use ($term) {
                    $momNom = mb_strtolower(trim($p['momento_nombre'] ?? ''));
                    $grpNom = mb_strtolower(trim($p['grupo_nombre'] ?? ''));
                    $catNom = mb_strtolower(trim($p['categoria_nombre'] ?? ''));
                    $tit = mb_strtolower(trim($p['titulo'] ?? ''));
                    return (strpos($momNom, $term) !== false || strpos($grpNom, $term) !== false || strpos($catNom, $term) !== false || strpos($tit, $term) !== false);
                });
            }
        } elseif ($id_grupo) {
            $grupoActual = $grupModel->getGrupoById($id_grupo);
            if ($grupoActual) {
                $tituloCategoria = $grupoActual['nombre'];
                $descripcionCategoria = $grupoActual['descripcion'] ?? '';
            }
            $productosFiltrados = array_filter($todosProductos, function($p) use ($id_grupo) {
                return (int)($p['id_grupo'] ?? 0) === $id_grupo;
            });
        } elseif ($nombre) {
            $tituloCategoria = $nombre;
            $term = mb_strtolower(trim($nombre));
            $productosFiltrados = array_filter($todosProductos, function($p) use ($term, $sub) {
                $momNom = mb_strtolower(trim($p['momento_nombre'] ?? ''));
                $grpNom = mb_strtolower(trim($p['grupo_nombre'] ?? ''));
                $catNom = mb_strtolower(trim($p['categoria_nombre'] ?? ''));
                $tit = mb_strtolower(trim($p['titulo'] ?? ''));

                $match = (
                    strpos($momNom, $term) !== false || 
                    strpos($grpNom, $term) !== false || 
                    strpos($catNom, $term) !== false || 
                    strpos($tit, $term) !== false
                );
                if ($match && $sub) {
                    return (stripos($p['titulo'] ?? '', $sub) !== false || stripos($p['categoria_nombre'] ?? '', $sub) !== false);
                }
                return $match;
            });
            if (empty($productosFiltrados)) {
                $words = explode(' ', $term);
                $firstWord = reset($words);
                if (mb_strlen($firstWord) >= 3) {
                    $productosFiltrados = array_filter($todosProductos, function($p) use ($firstWord) {
                        $momNom = mb_strtolower(trim($p['momento_nombre'] ?? ''));
                        $grpNom = mb_strtolower(trim($p['grupo_nombre'] ?? ''));
                        $catNom = mb_strtolower(trim($p['categoria_nombre'] ?? ''));
                        $tit = mb_strtolower(trim($p['titulo'] ?? ''));
                        return (strpos($momNom, $firstWord) !== false || strpos($grpNom, $firstWord) !== false || strpos($catNom, $firstWord) !== false || strpos($tit, $firstWord) !== false);
                    });
                }
            }
        } else {
            $tituloCategoria = 'Catálogo de Productos';
            $productosFiltrados = $todosProductos;
        }

        // Reindexar
        $product_bd = array_values($productosFiltrados);

        // Datos del usuario logueado
        $estaAutenticado = isset($_SESSION['usuario_id']);
        $rolUsuario = $_SESSION['usuario_rol'] ?? 'cliente';
        $esAdmin = ($rolUsuario === 'admin');
        $nombre = $_SESSION['usuario_nombre'] ?? 'Invitado';

        $cantPedidosActivos = 0;
        $pedidosUsuario = [];
        if ($estaAutenticado) {
            $pedidoModel = new PedidoModel();
            $pedidosUsuario = $pedidoModel->getPedidosByUsuario($_SESSION['usuario_id']);
            if (!empty($pedidosUsuario)) {
                foreach ($pedidosUsuario as $ped) {
                    $est = strtolower($ped['estado'] ?? '');
                    if (in_array($est, ['pendiente', 'aceptado', 'confirmado', 'en camino', 'despachado'])) {
                        $cantPedidosActivos++;
                    }
                }
            }
        }

        // Configuración de banners de carrusel (si la vista los referencia)
        $carruselConfigFile = __DIR__ . '/../Config/carrusel.json';
        $slidesCarrusel = file_exists($carruselConfigFile) ? json_decode(file_get_contents($carruselConfigFile), true) : [];

        include 'Views/Home/categoria_productos.php';
    }

    public function createCategoria() {
        $redirectTo = $_POST['redirect_to'] ?? ($_GET['redirect_to'] ?? 'categorias_admin');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: index.php?action=$redirectTo");
            exit();
        }

        if (session_status() == PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin') {
            header('Location: index.php?action=login');
            exit();
        }

        $nombre = trim(filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_SPECIAL_CHARS));
        $descripcion = trim(filter_input(INPUT_POST, 'descripcion', FILTER_SANITIZE_SPECIAL_CHARS));
        $id_momento = filter_input(INPUT_POST, 'id_momento', FILTER_VALIDATE_INT) ?: null;
        $id_grupo   = filter_input(INPUT_POST, 'id_grupo', FILTER_VALIDATE_INT) ?: null;
        $imagen = null;

        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $fileTempPath = $_FILES['imagen']['tmp_name'];
            $fileName     = $_FILES['imagen']['name'];
            $fileSize     = $_FILES['imagen']['size'];
            $fileExt      = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowedExt   = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            if (in_array($fileExt, $allowedExt) && $fileSize <= 2097152) {
                $nuevoNombre = md5(time() . $fileName) . '.' . $fileExt;
                $uploadDir   = './uploads/categorias/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $destPath = $uploadDir . $nuevoNombre;
                if (move_uploaded_file($fileTempPath, $destPath)) {
                    $imagen = 'uploads/categorias/' . $nuevoNombre;
                }
            }
        }

        if ($imagen === null) {
            $imagenUrl = trim(filter_input(INPUT_POST, 'imagen_url', FILTER_SANITIZE_URL));
            if ($imagenUrl !== '') $imagen = $imagenUrl;
        }

        if ($nombre !== '') {
            $result = $this->model->createCategory($nombre, $descripcion, $imagen, $id_momento, $id_grupo);
            if ($result) {
                if (AjaxHelper::isAjax()) AjaxHelper::jsonResponse(true, "Categoría \"$nombre\" registrada correctamente.");
                $_SESSION['flash_success'] = "Categoría \"$nombre\" registrada correctamente.";
            } else {
                if (AjaxHelper::isAjax()) AjaxHelper::jsonError("Error al registrar la categoría.", 500);
                $_SESSION['flash_error'] = "Error al registrar la categoría. Inténtalo de nuevo.";
            }
        } else {
            if (AjaxHelper::isAjax()) AjaxHelper::jsonError("El nombre de la categoría es obligatorio.", 422);
            $_SESSION['flash_error'] = "El nombre de la categoría es obligatorio.";
        }

        header("Location: index.php?action=$redirectTo");
        exit();
    }

    public function eliminarCategoria(){
        if (session_status() == PHP_SESSION_NONE) session_start();
        $redirectTo = $_POST['redirect_to'] ?? ($_GET['redirect_to'] ?? 'categorias_admin');

        if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin') {
            if (AjaxHelper::isAjax()) AjaxHelper::jsonError("Acceso denegado.", 403);
            header('Location: index.php?action=login');
            exit();
        }

        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if ($id) {
            $this->model->deleteCategory($id);
            if (AjaxHelper::isAjax()) AjaxHelper::jsonResponse(true, "Subcategoría inactivada.");
            $_SESSION['flash_success'] = "Subcategoría inactivada.";
        } else {
            if (AjaxHelper::isAjax()) AjaxHelper::jsonError("ID de subcategoría inválido.", 422);
            $_SESSION['flash_error'] = "ID de subcategoría inválido.";
        }

        header("Location: index.php?action=$redirectTo");
        exit();
    }

    public function toggleStatus(){
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
                if (AjaxHelper::isAjax()) AjaxHelper::jsonResponse(true, "Estado de la subcategoría actualizado.");
                $_SESSION['flash_success'] = "Estado de la subcategoría actualizado.";
            } else {
                if (AjaxHelper::isAjax()) AjaxHelper::jsonError("No se pudo actualizar el estado de la subcategoría.", 500);
                $_SESSION['flash_error'] = "No se pudo actualizar el estado de la subcategoría.";
            }
        } else {
            if (AjaxHelper::isAjax()) AjaxHelper::jsonError("ID inválido.", 422);
            $_SESSION['flash_error'] = "ID inválido.";
        }

        header("Location: index.php?action=$redirectTo&aviso=$avisoAccion");
        exit();
    }

    public function actualizarCategoria() {
        $redirectTo = $_POST['redirect_to'] ?? ($_GET['redirect_to'] ?? 'categorias_admin');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: index.php?action=$redirectTo");
            exit();
        }

        if (session_status() == PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin') {
            if (AjaxHelper::isAjax()) AjaxHelper::jsonError("Acceso denegado.", 403);
            header('Location: index.php?action=login');
            exit();
        }

        $id = filter_input(INPUT_POST, 'id_categoria', FILTER_VALIDATE_INT);
        $nombre = trim(filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_SPECIAL_CHARS));
        $descripcion = trim(filter_input(INPUT_POST, 'descripcion', FILTER_SANITIZE_SPECIAL_CHARS));
        $id_momento = filter_input(INPUT_POST, 'id_momento', FILTER_VALIDATE_INT) ?: null;
        $id_grupo   = filter_input(INPUT_POST, 'id_grupo', FILTER_VALIDATE_INT) ?: null;
        $imagen = null;

        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $fileTempPath = $_FILES['imagen']['tmp_name'];
            $fileName     = $_FILES['imagen']['name'];
            $fileSize     = $_FILES['imagen']['size'];
            $fileExt      = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowedExt   = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            if (in_array($fileExt, $allowedExt) && $fileSize <= 2097152) {
                $nuevoNombre = md5(time() . $fileName) . '.' . $fileExt;
                $uploadDir   = './uploads/categorias/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $destPath = $uploadDir . $nuevoNombre;
                if (move_uploaded_file($fileTempPath, $destPath)) {
                    $imagen = 'uploads/categorias/' . $nuevoNombre;
                }
            }
        }

        if ($imagen === null) {
            $imagenUrl = trim(filter_input(INPUT_POST, 'imagen_url', FILTER_SANITIZE_URL));
            if ($imagenUrl !== '') $imagen = $imagenUrl;
        }

        if ($id && $nombre !== '') {
            $result = $this->model->updateCategory($id, $nombre, $descripcion, $imagen, $id_momento, $id_grupo);
            if ($result) {
                if (AjaxHelper::isAjax()) AjaxHelper::jsonResponse(true, "Categoría \"$nombre\" actualizada correctamente.");
                $_SESSION['flash_success'] = "Categoría \"$nombre\" actualizada correctamente.";
            } else {
                if (AjaxHelper::isAjax()) AjaxHelper::jsonError("Error al actualizar la categoría.", 500);
                $_SESSION['flash_error'] = "Error al actualizar la categoría.";
            }
        } else {
            if (AjaxHelper::isAjax()) AjaxHelper::jsonError("Datos inválidos para actualizar la categoría.", 422);
            $_SESSION['flash_error'] = "Datos inválidos para actualizar la categoría.";
        }

        header("Location: index.php?action=$redirectTo");
        exit();
    }
}
?>