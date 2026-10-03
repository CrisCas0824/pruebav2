<?php
require_once __DIR__ . '/../Core/AuthMiddleware.php';
require_once __DIR__ . '/../Core/ajaxHelper.php';
require_once __DIR__ . '/../Models/reportesModel.php';
require_once __DIR__ . '/../Models/kardexModel.php';
require_once __DIR__ . '/../Models/proveedorModel.php';
require_once __DIR__ . '/../Models/cajaModel.php';
require_once __DIR__ . '/../Models/deliveryModel.php';
require_once __DIR__ . '/../Models/verificacionPagoModel.php';
require_once __DIR__ . '/../Models/productModel.php';
require_once __DIR__ . '/../Models/categoryModel.php';

class ControllerAdminERP {
    private $reportesModel;
    private $kardexModel;
    private $proveedorModel;
    private $cajaModel;
    private $deliveryModel;
    private $verificacionModel;
    private $productoModel;
    private $categoriaModel;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->reportesModel = new ReportesModel();
        $this->kardexModel = new KardexModel();
        $this->proveedorModel = new ProveedorModel();
        $this->cajaModel = new CajaModel();
        $this->deliveryModel = new DeliveryModel();
        $this->verificacionModel = new VerificacionPagoModel();
        $this->productoModel = new ProductoModel();
        $this->categoriaModel = new CategoryModel();
    }

    private function verificarAdmin() {
        AuthMiddleware::verificarAcceso(['admin']);
    }

    // =========================================================================
    // 1. DASHBOARD DE GANANCIAS & MÉTRICAS FINANCIERAS
    // =========================================================================
    public function dashboardGanancias() {
        $this->verificarAdmin();
        $nombre = htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Administrador');
        $metricas = $this->reportesModel->obtenerMetricasFinancieras();
        $valorizacion = $this->kardexModel->obtenerValorizacionInventario();

        include __DIR__ . '/../Views/Admin/dashboard_ganancias.php';
    }

    // =========================================================================
    // 2. KARDEX VALORIZADO
    // =========================================================================
    public function kardexAdmin() {
        $this->verificarAdmin();
        $nombre = htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Administrador');
        $idProdFiltro = isset($_GET['id_producto']) ? (int)$_GET['id_producto'] : null;
        $fechaDesde = trim($_GET['fecha_desde'] ?? '');
        $fechaHasta = trim($_GET['fecha_hasta'] ?? '');
        $orden = ($_GET['orden'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';
        $porPagina = 1000;
        $totalMovimientos = $this->kardexModel->contarMovimientos($idProdFiltro, $fechaDesde, $fechaHasta);
        $totalPaginas = max(1, (int)ceil($totalMovimientos / 5));
        $pagina = 1;
        $movimientos = $this->kardexModel->listarMovimientos(
            $idProdFiltro,
            $porPagina,
            0,
            $fechaDesde,
            $fechaHasta,
            $orden
        );
        $productos = $this->productoModel->obtenerTodos();
        $valorizacion = $this->kardexModel->obtenerValorizacionInventario();

        include __DIR__ . '/../Views/Admin/kardex_admin.php';
    }

    public function ajustarKardex() {
        $this->verificarAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?action=kardex_admin');
            exit();
        }

        $idProducto = (int)($_POST['id_producto'] ?? 0);
        $tipoOperacion = trim($_POST['tipo_operacion'] ?? 'ajuste');
        $cantidad = (int)($_POST['cantidad'] ?? 0);
        $nuevoStock = isset($_POST['nuevo_stock']) ? (int)$_POST['nuevo_stock'] : 0;
        $motivo = trim($_POST['motivo'] ?? 'Ajuste manual de inventario');
        $idUsuario = (int)$_SESSION['usuario_id'];

        $res = $this->kardexModel->ajustarStockFlexible($idProducto, $tipoOperacion, $cantidad, $nuevoStock, $motivo, $idUsuario);
        if ($res['ok']) {
            $_SESSION['flash_success'] = $res['mensaje'];
        } else {
            $_SESSION['flash_error'] = $res['mensaje'];
        }
        header('Location: index.php?action=kardex_admin');
        exit();
    }

    // =========================================================================
    // 3. COMPRAS & REPOSICIONES (Módulo Operativo)
    // =========================================================================
    public function comprasAdmin() {
        $this->verificarAdmin();
        $nombre = htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Administrador');
        $proveedores = $this->proveedorModel->listarProveedores(true);
        $reposiciones = $this->proveedorModel->listarReposiciones(500);
        $historial = $reposiciones;
        $productos = $this->productoModel->obtenerTodos();
        $categorias = $this->categoriaModel->getCategoria();
        $sesionCajaActiva = $this->cajaModel->obtenerSesionActiva();
        $estadisticas = $this->proveedorModel->obtenerEstadisticasProveedores();
        $saldoCaja = 0.00;
        if ($sesionCajaActiva) {
            $saldoCaja = (float)$sesionCajaActiva['monto_apertura'] + (float)$sesionCajaActiva['total_venta'] + (float)$sesionCajaActiva['total_ingresos'] - (float)$sesionCajaActiva['total_egresos'];
        }

        include __DIR__ . '/../Views/Admin/compras_admin.php';
    }

    // =========================================================================
    // 3.1 PROVEEDORES (Directorio & Catálogo Maestro)
    // =========================================================================
    public function proveedoresAdmin() {
        $this->verificarAdmin();
        $nombre = htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Administrador');
        $proveedores = $this->proveedorModel->listarProveedores(false);
        $reposiciones = $this->proveedorModel->listarReposiciones(500);
        $productos = $this->productoModel->obtenerTodos();
        $categorias = $this->categoriaModel->getCategoria();
        $sesionCajaActiva = $this->cajaModel->obtenerSesionActiva();
        $estadisticas = $this->proveedorModel->obtenerEstadisticasProveedores();
        $saldoCaja = 0.00;
        if ($sesionCajaActiva) {
            $saldoCaja = (float)$sesionCajaActiva['monto_apertura'] + (float)$sesionCajaActiva['total_venta'] + (float)$sesionCajaActiva['total_ingresos'] - (float)$sesionCajaActiva['total_egresos'];
        }

        include __DIR__ . '/../Views/Admin/proveedores_admin.php';
    }

    public function crearProveedor() {
        $this->verificarAdmin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $ruc = trim($_POST['ruc'] ?? '');
            $razon = trim($_POST['razon_social'] ?? '');
            $contacto = trim($_POST['contacto_nombre'] ?? '');
            $telefono = trim($_POST['telefono'] ?? '');
            $correo = trim($_POST['correo'] ?? '');
            $direccion = trim($_POST['direccion'] ?? '');

            if (empty($razon)) {
                $_SESSION['flash_error'] = 'La Razón Social es obligatoria';
            } else {
                $this->proveedorModel->crearProveedor($ruc, $razon, $contacto, $telefono, $correo, $direccion);
                $_SESSION['flash_success'] = 'Proveedor registrado con éxito';
            }
        }
        header('Location: index.php?action=proveedores_admin');
        exit();
    }

    public function actualizarProveedor() {
        $this->verificarAdmin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int)($_POST['id_proveedor'] ?? 0);
            $ruc = trim($_POST['ruc'] ?? '');
            $razon = trim($_POST['razon_social'] ?? '');
            $contacto = trim($_POST['contacto_nombre'] ?? '');
            $telefono = trim($_POST['telefono'] ?? '');
            $correo = trim($_POST['correo'] ?? '');
            $direccion = trim($_POST['direccion'] ?? '');

            if ($id > 0 && !empty($razon)) {
                $this->proveedorModel->actualizarProveedor($id, $ruc, $razon, $contacto, $telefono, $correo, $direccion);
                $_SESSION['flash_success'] = 'Proveedor actualizado con éxito';
            } else {
                $_SESSION['flash_error'] = 'Datos inválidos para actualizar proveedor';
            }
        }
        header('Location: index.php?action=proveedores_admin');
        exit();
    }

    public function toggleProveedor() {
        $this->verificarAdmin();
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $estado = isset($_GET['estado']) ? (int)$_GET['estado'] : (isset($_POST['estado']) ? (int)$_POST['estado'] : null);
        if ($estado === null && isset($_GET['nuevo_estado'])) {
            $estado = strtolower($_GET['nuevo_estado']) === 'activo' ? 1 : 0;
        } elseif ($estado === null && isset($_POST['nuevo_estado'])) {
            $estado = strtolower($_POST['nuevo_estado']) === 'activo' ? 1 : 0;
        }

        if ($id) {
            $prov = $this->proveedorModel->obtenerProveedor($id);
            if ($prov) {
                $nuevoEstado = $estado !== null ? $estado : ($prov['activo'] ? 0 : 1);
                $this->proveedorModel->cambiarEstado($id, $nuevoEstado);
                $accionTexto = $nuevoEstado ? 'activado' : 'inactivado';
                $msg = 'Proveedor "' . htmlspecialchars($prov['razon_social']) . '" ' . $accionTexto . ' correctamente.';
                $_SESSION['flash_success'] = $msg;
                if (AjaxHelper::isAjax()) {
                    AjaxHelper::jsonResponse(true, $msg, ['nuevo_estado' => $nuevoEstado]);
                }
            } else {
                $_SESSION['flash_error'] = 'Proveedor no encontrado';
                if (AjaxHelper::isAjax()) AjaxHelper::jsonError('Proveedor no encontrado', 404);
            }
        } else {
            $_SESSION['flash_error'] = 'ID de proveedor inválido';
            if (AjaxHelper::isAjax()) AjaxHelper::jsonError('ID de proveedor inválido', 422);
        }
        header('Location: index.php?action=proveedores_admin');
        exit();
    }

    public function registrarCompraProveedor() {
        $this->verificarAdmin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $tipoIngreso = $_POST['tipo_ingreso'] ?? 'existente';
            $esNuevoProd = ($tipoIngreso === 'nuevo') || (isset($_POST['es_nuevo_producto']) && $_POST['es_nuevo_producto'] == '1');
            $idProducto = (int)($_POST['id_producto'] ?? 0);
            $idProveedor = !empty($_POST['id_proveedor']) ? (int)$_POST['id_proveedor'] : null;
            $idUsuario = (int)($_SESSION['usuario_id'] ?? 1);
            $cantidad = (int)($_POST['cantidad'] ?? 0);
            $costoUnit = (float)($_POST['costo_unitario'] ?? ($_POST['precio_unitario'] ?? 0));
            $precioSugerido = !empty($_POST['precio_venta_sugerido']) ? (float)$_POST['precio_venta_sugerido'] : (!empty($_POST['precio_venta']) ? (float)$_POST['precio_venta'] : null);
            $comprobante = trim($_POST['nro_comprobante'] ?? ($_POST['numero_comprobante'] ?? ''));
            $lote = trim($_POST['lote'] ?? '');
            $vencimiento = !empty($_POST['fecha_vencimiento']) ? trim($_POST['fecha_vencimiento']) : null;
            $observacion = trim($_POST['observacion'] ?? ($_POST['observaciones'] ?? ''));
            $descontarDeCaja = isset($_POST['descontar_caja']) && $_POST['descontar_caja'] == '1';

            $datosNuevoProd = null;
            if ($esNuevoProd) {
                if (empty($precioSugerido) && !empty($_POST['nuevo_precio_venta'])) {
                    $precioSugerido = (float)$_POST['nuevo_precio_venta'];
                }
                $datosNuevoProd = [
                    'nombre' => trim($_POST['nombre_nuevo_producto'] ?? ($_POST['nuevo_nombre'] ?? '')),
                    'sku' => trim($_POST['sku_nuevo_producto'] ?? ($_POST['nuevo_sku'] ?? '')),
                    'codigo_barras' => trim($_POST['nuevo_codigo_barras'] ?? ($_POST['codigo_barras'] ?? '')),
                    'id_categoria' => (int)($_POST['id_categoria_nuevo'] ?? ($_POST['nuevo_categoria'] ?? 1))
                ];
            }

            $sesionActiva = $this->cajaModel->obtenerSesionActiva();
            $idSesion = $sesionActiva ? (int)$sesionActiva['id_sesion'] : null;

            $res = $this->proveedorModel->registrarCompraReposicion(
                $idProducto,
                $idProveedor,
                $idUsuario,
                $cantidad,
                $costoUnit,
                $precioSugerido,
                $comprobante,
                $lote,
                $vencimiento,
                $observacion,
                $descontarDeCaja,
                $idSesion,
                $datosNuevoProd
            );

            if ($res['ok']) {
                $_SESSION['flash_success'] = $res['mensaje'];
            } else {
                $_SESSION['flash_error'] = $res['mensaje'];
            }
        }
        $dest = !empty($_POST['redirect_to']) ? $_POST['redirect_to'] : 'compras_admin';
        header('Location: index.php?action=' . $dest);
        exit();
    }

    // =========================================================================
    // 4. CONTROL DE CAJA CHICA & ARQUEO
    // =========================================================================
    public function cajaAdmin() {
        $this->verificarAdmin();
        $nombre = htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Administrador');
        $idUsuario = (int)$_SESSION['usuario_id'];
        
        $sesionActiva = $this->cajaModel->obtenerSesionActiva();
        $resumen = null;
        $movimientos = [];

        if ($sesionActiva) {
            $resumen = $this->cajaModel->calcularResumenCaja($sesionActiva['id_sesion']);
            $movimientos = $this->cajaModel->listarMovimientos($sesionActiva['id_sesion']);
        }

        $historialSesiones = $this->cajaModel->listarHistorialSesiones(50);

        include __DIR__ . '/../Views/Admin/caja_admin.php';
    }

    public function aperturaCaja() {
        $this->verificarAdmin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $montoApertura = (float)($_POST['monto_apertura'] ?? 0);
            $idUsuario = (int)$_SESSION['usuario_id'];
            $cajaId = trim($_POST['caja_id'] ?? 'CAJA-01');

            $res = $this->cajaModel->abrirCaja($idUsuario, $montoApertura, $cajaId);
            if ($res['ok']) {
                $_SESSION['flash_success'] = $res['mensaje'];
            } else {
                $_SESSION['flash_error'] = $res['mensaje'];
            }
        }
        header('Location: index.php?action=caja_admin');
        exit();
    }

    public function movimientoCaja() {
        $this->verificarAdmin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $idSesion = (int)($_POST['id_sesion'] ?? 0);
            $tipo = trim($_POST['tipo'] ?? 'EGRESO');
            $categoria = trim($_POST['categoria'] ?? 'otro');
            $monto = (float)($_POST['monto'] ?? 0);
            $motivo = trim($_POST['motivo'] ?? '');
            $comprobante = trim($_POST['comprobante'] ?? '');
            $idUsuario = (int)$_SESSION['usuario_id'];

            if ($idSesion <= 0 || $monto <= 0 || empty($motivo)) {
                $_SESSION['flash_error'] = 'Por favor complete todos los datos del movimiento';
            } else {
                $res = $this->cajaModel->registrarMovimiento($idSesion, $idUsuario, $tipo, $categoria, $monto, $motivo, $comprobante);
                if ($res['ok']) {
                    $_SESSION['flash_success'] = $res['mensaje'];
                } else {
                    $_SESSION['flash_error'] = $res['mensaje'];
                }
            }
        }
        header('Location: index.php?action=caja_admin');
        exit();
    }

    public function cierreCaja() {
        $this->verificarAdmin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $idSesion = (int)($_POST['id_sesion'] ?? 0);
            $montoReal = (float)($_POST['monto_cierre_real'] ?? 0);

            if ($idSesion <= 0) {
                $_SESSION['flash_error'] = 'Sesión no especificada';
            } else {
                $res = $this->cajaModel->cerrarCaja($idSesion, $montoReal);
                if ($res['ok']) {
                    $_SESSION['flash_success'] = $res['mensaje'] . '. Esperado: S/ ' . number_format($res['esperado'], 2) . ' | Contado: S/ ' . number_format($res['real'], 2) . ' | Diferencia: S/ ' . number_format($res['diferencia'], 2);
                } else {
                    $_SESSION['flash_error'] = $res['mensaje'];
                }
            }
        }
        header('Location: index.php?action=caja_admin');
        exit();
    }

    // =========================================================================
    // 5. DELIVERY & DESPACHO
    // =========================================================================
    public function deliveryAdmin() {
        $this->verificarAdmin();
        $nombre = htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Administrador');
        $filtroEstado = $_GET['estado'] ?? null;
        $envios = $this->deliveryModel->listarEnvios($filtroEstado);
        $repartidores = $this->deliveryModel->listarRepartidores();

        include __DIR__ . '/../Views/Admin/delivery_admin.php';
    }

    public function asignarRepartidor() {
        $this->verificarAdmin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $idEnvio = (int)($_POST['id_envio'] ?? 0);
            $idRepartidor = (int)($_POST['id_repartidor'] ?? 0);
            $esAjax = AjaxHelper::isAjax() || !empty($_POST['is_ajax']) || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

            if ($idEnvio > 0 && $idRepartidor > 0) {
                $this->deliveryModel->asignarRepartidor($idEnvio, $idRepartidor);
                if ($esAjax) {
                    AjaxHelper::jsonResponse(true, 'Repartidor asignado con éxito');
                }
                $_SESSION['flash_success'] = 'Repartidor asignado con éxito';
            } else {
                if ($esAjax) {
                    AjaxHelper::jsonError('Seleccione un repartidor válido', 422);
                }
                $_SESSION['flash_error'] = 'Seleccione un repartidor válido';
            }
        }
        header('Location: index.php?action=delivery_admin');
        exit();
    }

    public function actualizarEstadoDelivery() {
        $this->verificarAdmin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $idEnvio = (int)($_POST['id_envio'] ?? 0);
            $idPedido = (int)($_POST['id_pedido'] ?? 0);
            $nuevoEstado = trim($_POST['nuevo_estado'] ?? '');
            $cobrado = isset($_POST['cobrado']) ? (int)$_POST['cobrado'] : null;
            $liquidado = isset($_POST['liquidado']) ? (int)$_POST['liquidado'] : null;
            $notas = trim($_POST['notas'] ?? '');
            $esAjax = AjaxHelper::isAjax() || !empty($_POST['is_ajax']) || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

            if (($idEnvio > 0 || $idPedido > 0) && !empty($nuevoEstado)) {
                $this->deliveryModel->actualizarEstadoDelivery($idEnvio, $nuevoEstado, $cobrado, $liquidado, $notas, $idPedido);
                if ($esAjax) {
                    AjaxHelper::jsonResponse(true, 'Estado actualizado a: ' . $nuevoEstado);
                }
                $_SESSION['flash_success'] = 'Estado actualizado a: ' . $nuevoEstado;
            } else {
                if ($esAjax) {
                    AjaxHelper::jsonError('Datos insuficientes para actualizar el pedido/envío', 422);
                }
                $_SESSION['flash_error'] = 'Datos insuficientes para actualizar el pedido/envío';
            }
        }
        header('Location: index.php?action=delivery_admin');
        exit();
    }

    // =========================================================================
    // 6. VERIFICACIÓN DE PAGOS YAPE / PLIN
    // =========================================================================
    public function verificacionPagos() {
        $this->verificarAdmin();
        $nombre = htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Administrador');
        $filtro = $_GET['estado'] ?? null;
        $verificaciones = $this->verificacionModel->listarVerificaciones($filtro);

        include __DIR__ . '/../Views/Admin/verificacion_pagos.php';
    }

    public function aprobarVerificacionPago() {
        $this->verificarAdmin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $idVerif = (int)($_POST['id_verificacion'] ?? 0);
            $idUser = (int)$_SESSION['usuario_id'];

            if ($idVerif > 0) {
                $res = $this->verificacionModel->aprobarPago($idVerif, $idUser);
                if ($res['ok']) {
                    if (AjaxHelper::isAjax()) AjaxHelper::jsonResponse(true, $res['mensaje']);
                    $_SESSION['flash_success'] = $res['mensaje'];
                } else {
                    if (AjaxHelper::isAjax()) AjaxHelper::jsonError($res['mensaje'], 422);
                    $_SESSION['flash_error'] = $res['mensaje'];
                }
            }
        }
        header('Location: index.php?action=verificacion_pagos');
        exit();
    }

    public function rechazarVerificacionPago() {
        $this->verificarAdmin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $idVerif = (int)($_POST['id_verificacion'] ?? 0);
            $motivo = trim($_POST['motivo_rechazo'] ?? 'Voucher ilegible o no concuerda');
            $idUser = (int)$_SESSION['usuario_id'];

            if ($idVerif > 0) {
                $res = $this->verificacionModel->rechazarPago($idVerif, $idUser, $motivo);
                if ($res['ok']) {
                    if (AjaxHelper::isAjax()) AjaxHelper::jsonResponse(true, $res['mensaje']);
                    $_SESSION['flash_success'] = $res['mensaje'];
                } else {
                    if (AjaxHelper::isAjax()) AjaxHelper::jsonError($res['mensaje'], 422);
                    $_SESSION['flash_error'] = $res['mensaje'];
                }
            }
        }
        header('Location: index.php?action=verificacion_pagos');
        exit();
    }

    // =========================================================================
    // 7. EDICIÓN DE CARRUSEL & BANNERS
    // =========================================================================
    public function carruselAdmin() {
        $this->verificarAdmin();
        $nombre = htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Administrador');
        $carruselConfigFile = __DIR__ . '/../Config/carrusel.json';
        $slidesCarrusel = file_exists($carruselConfigFile) ? json_decode(file_get_contents($carruselConfigFile), true) : [];
        if (empty($slidesCarrusel) || !is_array($slidesCarrusel)) {
            $slidesCarrusel = [
                [
                    "tag" => "OFERTA DE LA SEMANA",
                    "titulo" => "Abarrotes y Productos Frescos al Mejor Precio",
                    "descripcion" => "Aprovecha descuentos de hasta 25% en tu primera compra en línea.",
                    "boton" => "Ver Ofertas",
                    "link" => "#catalogo",
                    "bg" => "#15803D",
                    "imagen" => "Images/abarrotes-banner.png"
                ],
                [
                    "tag" => "LÁCTEOS Y BEBIDAS",
                    "titulo" => "Calidad y Frescura Directo a tu Hogar",
                    "descripcion" => "Leche gloria, jugos naturales y bebidas con entrega rápida garantizada.",
                    "boton" => "Comprar Ahora",
                    "link" => "#catalogo",
                    "bg" => "#1e3a8a",
                    "imagen" => "uploads/productos/leche_gloria.png"
                ],
                [
                    "tag" => "DELIVERY GRATIS",
                    "titulo" => "Envíos Inmediatos en Todo el Distrito",
                    "descripcion" => "Haz tu pedido hoy y recíbelo en la puerta de tu casa en menos de 45 minutos.",
                    "boton" => "Hacer Pedido",
                    "link" => "#catalogo",
                    "bg" => "#065f46",
                    "imagen" => "Images/delivery-banner.png"
                ],
                [
                    "tag" => "PAGO SEGURO",
                    "titulo" => "Paga Fácil con Yape, Plin o Tarjeta",
                    "descripcion" => "Validación automática de tus pagos al instante sin esperas.",
                    "boton" => "Explorar Tienda",
                    "link" => "#catalogo",
                    "bg" => "#4c1d95",
                    "imagen" => "Images/yape-banner.png"
                ]
            ];
        }
        $flash_success = $_SESSION['flash_success'] ?? null;
        $flash_error = $_SESSION['flash_error'] ?? null;
        require_once 'Models/momentConModel.php';
        require_once 'Models/grupoMomentModel.php';
        require_once 'Models/categoryModel.php';

        $momentModel = new MomentConModel();
        $grupoModel  = new GrupMomentModel();
        $catModel    = new CategoryModel();

        $macrocategorias = $momentModel->getMomento();
        $gruposCategorias = $grupoModel->getGrupo();
        $subcategorias    = $catModel->getCategoria();

        include 'Views/Admin/carrusel_admin.php';
    }

    public function guardarCarrusel() {
        $this->verificarAdmin();

        $isFormSubmit = isset($_POST['guardar_form_carrusel']);
        $file = __DIR__ . '/../Config/carrusel.json';
        $currentSlides = file_exists($file) ? json_decode(file_get_contents($file), true) : [];

        if ($isFormSubmit) {
            $slides = [];
            $uploadsDir = 'uploads/banners/';
            if (!is_dir($uploadsDir)) {
                mkdir($uploadsDir, 0777, true);
            }

            $indices = $_POST['slide_indices'] ?? [];
            if (empty($indices) || !is_array($indices)) {
                $indices = range(0, count($currentSlides) > 0 ? count($currentSlides) - 1 : 3);
            }

            foreach ($indices as $i) {
                $link = trim($_POST["link_$i"] ?? ($_POST["enlace_$i"] ?? '#sugerenciasGrid'));
                if (empty($link)) $link = '#sugerenciasGrid';
                $imagen = trim($_POST["imagen_url_$i"] ?? ($currentSlides[$i]['imagen'] ?? ''));

                if (isset($_FILES["imagen_file_$i"]) && $_FILES["imagen_file_$i"]['error'] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($_FILES["imagen_file_$i"]['name'], PATHINFO_EXTENSION));
                    $destName = 'banner_' . ($i + 1) . '_' . time() . '_' . rand(100, 999) . '.' . $ext;
                    $destPath = $uploadsDir . $destName;
                    if (move_uploaded_file($_FILES["imagen_file_$i"]['tmp_name'], $destPath)) {
                        $imagen = $destPath;
                    }
                }

                if ($imagen !== '') {
                    $slides[] = [
                        'imagen' => $imagen,
                        'link' => $link,
                        'enlace' => $link,
                        'boton' => 'Ver más',
                        'tag' => '',
                        'titulo' => '',
                        'descripcion' => '',
                        'bg' => 'transparent'
                    ];
                }
            }

            if (!is_dir(dirname($file))) {
                mkdir(dirname($file), 0777, true);
            }
            file_put_contents($file, json_encode($slides, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $_SESSION['flash_success'] = '¡Banners del carrusel actualizados exitosamente!';
            header('Location: index.php?action=carrusel_admin');
            exit();
        }

        // AJAX / JSON fallback
        header('Content-Type: application/json');
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        if (!$data && isset($_POST['slides'])) {
            $data = json_decode($_POST['slides'], true);
        }

        if (is_array($data) && count($data) > 0) {
            $slides = [];
            foreach ($data as $s) {
                $img = trim($s['imagen'] ?? '');
                $lnk = trim($s['link'] ?? ($s['enlace'] ?? '#sugerenciasGrid'));
                if (empty($lnk)) $lnk = '#sugerenciasGrid';
                if ($img !== '') {
                    $slides[] = [
                        'imagen' => $img,
                        'link' => $lnk,
                        'enlace' => $lnk,
                        'boton' => 'Ver más',
                        'tag' => '',
                        'titulo' => '',
                        'descripcion' => '',
                        'bg' => 'transparent'
                    ];
                }
            }

            if (!is_dir(dirname($file))) {
                mkdir(dirname($file), 0777, true);
            }
            file_put_contents($file, json_encode($slides, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            echo json_encode(['ok' => true, 'mensaje' => 'Carrusel actualizado con éxito']);
        } else {
            echo json_encode(['ok' => false, 'mensaje' => 'Datos inválidos para actualizar el carrusel']);
        }
        exit();
    }
}

