<?php

date_default_timezone_set('America/Lima');
error_reporting(E_ALL);
ini_set('display_errors', 0); 
ini_set('log_errors', 1); 


require_once 'Core/SessionManager.php';
session_set_save_handler(new DatabaseSessionHandler(), true);


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}


require_once 'Core/AuthMiddleware.php';


require_once 'Controllers/controllersUser.php';
require_once 'Controllers/controllersCategory.php';
require_once 'Controllers/controllersGrupMoment.php';
require_once 'Controllers/controllersProduct.php';
require_once 'Controllers/controllersInventory.php';
require_once 'Controllers/controllersCarrito.php';
require_once 'Controllers/controllersCuenta.php';
require_once 'Controllers/controllersDireccion.php';
require_once 'Controllers/controllersPedido.php';
require_once 'Controllers/controllersUserAdmin.php';
require_once 'Controllers/controllersCheckout.php';
require_once 'Controllers/controlador_punto_venta.php';
require_once 'Controllers/controllersAdminERP.php';
require_once 'Controllers/controllersRepartidor.php';

$controller = new UserController();
$catController = new ControllerCategory();
$grupController = new ControllerGrupMoment();
$producto = new controllerProducto();
$inventario = new ControllerInventario();
$controllerCarrito = new ControllerCarrito();
$userAdminController = new ControllerUserAdmin();
$cuentaController = new ControllerCuenta();
$direccionController = new ControllerDireccion();
$pedidoController = new ControllerPedido();
$checkoutController = new ControllerCheckout();
$posController = new ControladorPuntoVenta();
$adminERP = new ControllerAdminERP();
$repartidorController = new ControllerRepartidor();


$action = isset($_GET['action']) ? trim($_GET['action']) : 'home_usuario';

$rutasPublicas = [
    'home_usuario',
    'detalle_producto',
    'ver_categoria',
    'agregar_carrito',
    'listar_carrito',
    'actualizar_carrito',
    'eliminar_carrito',
    'vaciar_carrito',
    'login',
    'login_google',
    'registro',
    'recuperar',
    'verificar_pin',
    'reenviar_pin',
    'nueva_password',
    'pos_escaner_movil',
    'pos_escanear_movil',
    'pos_abrir_lector_celular',
    'pos_validar_emparejamiento',
    'pos_estado_sesion',
    'pos_escaneos_pendientes',
    'logout',
];


$rutasAdmin = [
    'home_admin',
    'usuarios_admin',
    'mi_perfil_admin',
    'actualizar_perfil_admin',
    'cambiar_password_admin',
    'verificar_password_actual_admin',
    'crear_administrador',
    'crear_cajero',
    'crear_repartidor',
    'actualizar_usuario',
    'cambiar_estado_usuario',
    'eliminar_usuario',
    'crear_categoria',
    'eliminar_categoria',
    'actualizar_categoria',
    'toggle_categoria',
    'agregar_grupo',
    'eliminar_grupo',
    'actualizar_grupo',
    'toggle_grupo',
    'crear_producto',
    'eliminar_producto',
    'actualizar_producto',
    'toggle_producto',
    'toggle_status',
    'categorias_admin',
    'productos_admin',
    'inventario',
    'reponer_stock',
    'ventas_admin',
    'detalle_pedido_admin',
    'actualizar_estado_pedido',
    // ERP Modules
    'dashboard_ganancias',
    'kardex_admin',
    'ajustar_kardex',
    'compras_admin',
    'proveedores_admin',
    'crear_proveedor',
    'guardar_proveedor',
    'actualizar_proveedor',
    'toggle_proveedor',
    'registrar_compra_proveedor',
    'guardar_compra',
    'caja_admin',
    'apertura_caja',
    'cierre_caja',
    'movimiento_caja',
    'delivery_admin',
    'asignar_repartidor',
    'actualizar_estado_delivery',
    'verificacion_pagos',
    'aprobar_verificacion_pago',
    'rechazar_verificacion_pago',
    'guardar_carrusel',
    'carrusel_admin',
];


$rutasCajero = [
    'panel_cajero',
    'productos_cajero',
    'arqueo_caja',
    'cajero_arqueo',
    'abrir_caja_cajero',
    'movimiento_caja_cajero',
    'cerrar_caja_cajero',
    'pos',
    'historial_pos',
    'cajero_pos',
    'pos_generar_qr',
    'pos_validar_emparejamiento',
    'pos_estado_sesion',
    'pos_escanear_producto',
    'pos_actualizar_cantidad',
    'pos_procesar_venta',
    'pos_buscar_productos',
    'pos_consultar_dni',
    'pos_escaneos_pendientes',
    'pos_escaner_movil',
    'pos_escanear_movil',
    'pos_abrir_lector_celular',
];

$rutasRepartidor = [
    'panel_repartidor',
    'actualizar_entrega_repartidor',
    'tomar_entrega_repartidor',
];


$rutasAutenticado = [
    'home_usuario',
    'agregar_carrito',
    'listar_carrito',
    'actualizar_carrito',
    'eliminar_carrito',
    'mi_cuenta',
    'actualizar_perfil',
    'enviar_pin_seguridad',
    'cambiar_password',
    'verificar_password_actual',
    'crear_direccion',
    'actualizar_direccion',
    'direccion_principal',
    'eliminar_direccion',
    'listar_pedidos_usuario',
    'detalle_pedido_usuario',
    'crear_pedido',
    'pedido_confirmado',
    'checkout',
    'logout',
];




if (in_array($_SESSION['usuario_rol'] ?? '', ['admin', 'cajero', 'repartidor'], true)
    && in_array($action, [
        'agregar_carrito', 'actualizar_carrito', 'eliminar_carrito', 'vaciar_carrito',
        'crear_direccion', 'actualizar_direccion', 'direccion_principal', 'eliminar_direccion',
        'listar_pedidos_usuario', 'detalle_pedido_usuario', 'checkout', 'crear_pedido', 'pedido_confirmado'
    ], true)) {
    if (AjaxHelper::isAjax()) {
        AjaxHelper::jsonError('Las cuentas de personal no pueden realizar compras.', 403);
    }
    header('Location: index.php?action=home_usuario');
    exit();
}

if (in_array($action, $rutasPublicas, true)) {
    
} elseif (in_array($action, $rutasAdmin, true)) {
    AuthMiddleware::verificarAcceso(['admin']);
} elseif (in_array($action, $rutasCajero, true)) {
    AuthMiddleware::verificarAcceso(['cajero', 'admin']);
} elseif (in_array($action, $rutasRepartidor, true)) {
    AuthMiddleware::verificarAcceso(['repartidor']);
} elseif (in_array($action, $rutasAutenticado, true)) {
    AuthMiddleware::verificarAcceso(['cliente', 'admin', 'cajero', 'repartidor']);
} else {
    // Si la acción solicitada no existe, redirigir siempre al Home público
    header('Location: index.php?action=home_usuario');
    exit();
}




switch($action) {
    
    case 'pos':
    case 'cajero_pos':
        $posController->pos();
        break;
    case 'panel_cajero':
        $posController->panelCaja();
        break;
    case 'productos_cajero':
        $posController->productosCatalogo();
        break;
    case 'arqueo_caja':
    case 'cajero_arqueo':
        $posController->arqueoCaja();
        break;
    case 'abrir_caja_cajero':
        $posController->abrirCajaCajero();
        break;
    case 'movimiento_caja_cajero':
        $posController->registrarMovimientoCajaCajero();
        break;
    case 'cerrar_caja_cajero':
        $posController->cerrarCajaCajero();
        break;
    case 'historial_pos':
        $posController->historial();
        break;
    case 'panel_repartidor':
        $repartidorController->panel();
        break;
    case 'actualizar_entrega_repartidor':
        $repartidorController->actualizarEntrega();
        break;
    case 'tomar_entrega_repartidor':
        $repartidorController->tomarEntrega();
        break;
    case 'pos_generar_qr':
        $posController->apiGenerarQR();
        break;
    case 'pos_validar_emparejamiento':
        $posController->apiValidarEmparejamiento();
        break;
    case 'pos_estado_sesion':
        $posController->apiEstadoSesion();
        break;
    case 'pos_escanear_producto':
        $posController->apiEscanearProducto();
        break;
    case 'pos_actualizar_cantidad':
        $posController->apiActualizarCantidad();
        break;
    case 'pos_procesar_venta':
        $posController->apiProcesarVenta();
        break;
    case 'pos_buscar_productos':
        $posController->apiBuscarProductos();
        break;
    case 'pos_consultar_dni':
        $posController->apiConsultarDni();
        break;
    case 'pos_escaner_movil':
        $posController->vistaEscanerMovil();
        break;
    case 'pos_escanear_movil':
        $posController->apiEscanearMovil();
        break;
    case 'pos_escaneos_pendientes':
        $posController->apiObtenerEscaneosPendientes();
        break;
    case 'pos_abrir_lector_celular':
        $posController->apiAbrirLectorEnCelular();
        break;

    
    case 'login':
        $controller->login();
        break;
    case 'login_google':
        $controller->loginGoogle();
        break;
    case 'registro':
        $controller->registro();
        break;
    case 'recuperar':
        $controller->recuperarPassword();
        break;
    case 'verificar_pin':
        $controller->verificarPin();
        break;
    case 'reenviar_pin':
        $controller->reenviarPin();
        break;
    case 'nueva_password':
        $controller->nuevaPassword();
        break;
    case 'home_usuario':
        $controller->homeUsuario();
        break;
    case 'home_admin':
        $controller->homeAdmin();
        break;
    case 'ver_categoria':
        $catController->verCategoria();
        break;
    case 'logout':
        $controller->logout();
        break;

    
    case 'usuarios_admin':
        $userAdminController->listarUsuarios();
        break;
    case 'mi_perfil_admin':
        $userAdminController->miPerfilAdmin();
        break;
    case 'actualizar_perfil_admin':
        $userAdminController->actualizarPerfilAdmin();
        break;
    case 'cambiar_password_admin':
        $userAdminController->cambiarPasswordAdmin();
        break;
    case 'verificar_password_actual_admin':
        $userAdminController->verificarPasswordActualAdmin();
        break;
    case 'crear_administrador':
        $userAdminController->crearAdministrador();
        break;
    case 'crear_cajero':
        $userAdminController->crearCajero();
        break;
    case 'crear_repartidor':
        $userAdminController->crearRepartidor();
        break;
    case 'actualizar_usuario':
        $userAdminController->actualizarUsuario();
        break;
    case 'cambiar_estado_usuario':
        $userAdminController->cambiarEstadoUsuario();
        break;
    case 'eliminar_usuario':
        $userAdminController->eliminarUsuario();
        break;

    
    case 'categorias_admin':
        $catController->vistaAdminCategorias();
        break;
    case 'crear_categoria':
        $catController->createCategoria();
        break;
    case 'eliminar_categoria':
        $catController->eliminarCategoria();
        break;
    case 'actualizar_categoria':
        $catController->actualizarCategoria();
        break;
    case 'toggle_categoria':
        $catController->toggleStatus();
        break;

    
    case 'agregar_grupo':
        $grupController->crearGrupo();
        break;
    case 'eliminar_grupo':
        $grupController->eliminarGrupo();
        break;
    case 'actualizar_grupo':
        $grupController->actualizarGrupo();
        break;
    case 'toggle_grupo':
        $grupController->toggleStatus();
        break;

    
    case 'crear_producto':
        $producto->crearProducto();
        break;
    case 'detalle_producto':
        $producto->detalleProducto();
        break;
    case 'eliminar_producto':
        $producto->eliminarProducto();
        break;
    case 'actualizar_producto':
        $producto->actualizarProducto();
        break;
    case 'toggle_status':
    case 'toggle_producto':
        $producto->toggleStatus();
        break;

    
    case 'productos_admin':
    case 'inventario':
        $inventario->inventario();
        break;
    case 'reponen_stock':
    case 'reponer_stock':
        $inventario->reponerStock();
        break;

    
    case 'agregar_carrito':
        $controllerCarrito->agregar();
        break;
    case 'listar_carrito':
        $controllerCarrito->listar();
        break;
    case 'actualizar_carrito':
        $controllerCarrito->actualizarCantidad();
        break;
    case 'eliminar_carrito':
        $controllerCarrito->eliminar();
        break;
    case 'vaciar_carrito':
        $controllerCarrito->vaciar();
        break;

    
    case 'mi_cuenta':
        $cuentaController->mostrarCuenta();
        break;
    case 'actualizar_perfil':
        $cuentaController->actualizarPerfil();
        break;
    case 'enviar_pin_seguridad':
        $cuentaController->enviarPinSeguridad();
        break;
    case 'cambiar_password':
        $cuentaController->cambiarPassword();
        break;
    case 'verificar_password_actual':
        $cuentaController->verificarPasswordActual();
        break;

    
    case 'crear_direccion':
        $direccionController->crearDireccion();
        break;
    case 'actualizar_direccion':
        $direccionController->actualizarDireccion();
        break;
    case 'direccion_principal':
        $direccionController->marcarPrincipal();
        break;
    case 'eliminar_direccion':
        $direccionController->desactivarDireccion();
        break;

    
    case 'listar_pedidos_usuario':
        $pedidoController->listarPedidosUsuario();
        break;
    case 'detalle_pedido_usuario':
        $pedidoController->detallePedidoUsuario();
        break;
    case 'crear_pedido':
        $pedidoController->crearPedido();
        break;
    case 'pedido_confirmado':
        $pedidoController->pedidoConfirmado();
        break;

    
    case 'checkout':
        $checkoutController->mostrarCheckout();
        break;

    
    case 'ventas_admin':
        $pedidoController->listarVentasAdmin();
        break;
    case 'detalle_pedido_admin':
        $pedidoController->detallePedidoAdmin();
        break;
    case 'actualizar_estado_pedido':
        $pedidoController->actualizarEstadoPedido();
        break;

    // ERP Modules
    case 'dashboard_ganancias':
        $adminERP->dashboardGanancias();
        break;
    case 'kardex_admin':
        $adminERP->kardexAdmin();
        break;
    case 'ajustar_kardex':
        $adminERP->ajustarKardex();
        break;
    case 'compras_admin':
        $adminERP->comprasAdmin();
        break;
    case 'proveedores_admin':
        $adminERP->proveedoresAdmin();
        break;
    case 'crear_proveedor':
    case 'guardar_proveedor':
        $adminERP->crearProveedor();
        break;
    case 'actualizar_proveedor':
        $adminERP->actualizarProveedor();
        break;
    case 'toggle_proveedor':
        $adminERP->toggleProveedor();
        break;
    case 'registrar_compra_proveedor':
    case 'guardar_compra':
        $adminERP->registrarCompraProveedor();
        break;
    case 'caja_admin':
        $adminERP->cajaAdmin();
        break;
    case 'apertura_caja':
        $adminERP->aperturaCaja();
        break;
    case 'cierre_caja':
        $adminERP->cierreCaja();
        break;
    case 'movimiento_caja':
        $adminERP->movimientoCaja();
        break;
    case 'delivery_admin':
        $adminERP->deliveryAdmin();
        break;
    case 'asignar_repartidor':
        $adminERP->asignarRepartidor();
        break;
    case 'actualizar_estado_delivery':
        $adminERP->actualizarEstadoDelivery();
        break;
    case 'verificacion_pagos':
        $adminERP->verificacionPagos();
        break;
    case 'aprobar_verificacion_pago':
        $adminERP->aprobarVerificacionPago();
        break;
    case 'rechazar_verificacion_pago':
        $adminERP->rechazarVerificacionPago();
        break;
    case 'carrusel_admin':
        $adminERP->carruselAdmin();
        break;
    case 'guardar_carrusel':
        $adminERP->guardarCarrusel();
        break;

    default:
        $controller->login();
        break;
}
?>
