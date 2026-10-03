<?php
/**
 * Header Principal Verde (Tier 1 + Tier 2) - La Caserita
 * Canónico y unificado para todas las vistas (Home, Categorías, Detalle de Producto, etc.)
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$estaAutenticado = isset($_SESSION['usuario_id']);
$rol = $_SESSION['usuario_rol'] ?? '';
$esAdmin = ($rol === 'admin');
$esPersonal = in_array($rol, ['admin', 'cajero', 'repartidor'], true);
$rutaInicio = 'index.php?action=home_usuario';
$rutaPerfil = $esAdmin ? 'index.php?action=dashboard_ganancias' : (($rol === 'cajero') ? 'index.php?action=panel_cajero' : (($rol === 'repartidor') ? 'index.php?action=panel_repartidor' : 'index.php?action=mi_cuenta'));
$textoPerfil = $esAdmin ? 'Panel ERP' : (($rol === 'cajero') ? 'Panel de caja' : (($rol === 'repartidor') ? 'Mis entregas' : 'Ir a Mi Cuenta'));
$nombre = $estaAutenticado ? htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario') : 'Invitado';

if (!isset($cantPedidosActivos)) {
    $cantPedidosActivos = 0;
    if ($estaAutenticado && !empty($_SESSION['usuario_id'])) {
        try {
            require_once __DIR__ . '/../../../Models/pedidoModel.php';
            $pModel = new PedidoModel();
            $pedidosAct = $pModel->obtenerPedidosActivosPorUsuario($_SESSION['usuario_id']);
            $cantPedidosActivos = count($pedidosAct);
        } catch (\Throwable $th) {
            $cantPedidosActivos = 0;
        }
    }
}

// Total de productos en carrito para insignia roja
$cartUserIdHeader = $_SESSION['usuario_id'] ?? 'invitado';
$carritoSesionHeader = $_SESSION['carritos'][$cartUserIdHeader] ?? [];
$cantTotalCarritoHeader = 0;
foreach ($carritoSesionHeader as $itemCart) {
    $cantTotalCarritoHeader += (int)($itemCart['cantidad'] ?? 0);
}

if (!isset($productosParaBuscadorGlobal)) {
    try {
        require_once __DIR__ . '/../../../Models/productModel.php';
        $__pModelGlobal = new ProductModel();
        $productosParaBuscadorGlobal = $__pModelGlobal->getTodosProductos(true);
    } catch (\Throwable $th) {
        $productosParaBuscadorGlobal = [];
    }
}
?>
    <script>
        if (!window.productosBD) {
            window.productosBD = <?= json_encode($productosParaBuscadorGlobal ?? []); ?>;
        }
    </script>
    <script src="Scripts/app.js?v=<?= time(); ?>"></script>

    <header class="main-header" id="mainHeader">
        <!-- Tier 1: Logo Caserita + Buscador + Cuenta + Carrito -->
        <div class="header-tier1">
            <button class="btn-menu-mobile" id="openSidebar" type="button" aria-label="Abrir menú" style="margin-right: -4px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="3" y1="12" x2="21" y2="12"></line>
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <line x1="3" y1="18" x2="21" y2="18"></line>
                </svg>
            </button>

            <a href="<?= htmlspecialchars($rutaInicio); ?>" class="header-brand-wrap" onclick="if(typeof cerrarBusqueda==='function') cerrarBusqueda(); return true;">
                <div class="header-brand-icon">
                    <img src="Images/logo_backup.png" alt="La Caserita" onerror="this.onerror=null;this.src='Images/logo.png';">
                </div>
                <div class="header-brand-text">
                    <span class="header-brand-name">La Caserita</span>
                </div>
            </a>

            <div class="header-search-wrap">
                <span class="header-search-icon-inside">
                    <svg xmlns="http://www.w3.org/2000/svg" width="19" height="19" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </span>
                <input type="text" name="busqueda" id="busqueda" placeholder="Busca arroz, leche, bebidas, frutas y más..."
                    class="header-search-input-pill" autocomplete="off">
                <button type="button" class="search-clear" id="searchClear" style="display: none;">×</button>

                <div class="search-dropdown" id="searchDropdown">
                    <div class="search-dropdown-content">
                        <div class="search-suggestions">
                            <h4>Sugerencias</h4>
                            <ul id="searchSuggestionsList"></ul>
                        </div>
                        <div class="search-quick-results">
                            <div class="search-quick-header">
                                <span id="searchQuickLabel">Resultado para</span>
                                <strong id="searchQuickTerm"></strong>
                            </div>
                            <div class="search-quick-products" id="searchQuickProducts"></div>
                            <button type="button" class="search-view-all-btn" id="searchViewAllBtn">VER TODOS LOS PRODUCTOS</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="header-controls-group header-actions">
                <!-- Botón Usuario (Píldora) -->
                <?php if ($estaAutenticado): ?>
                    <a href="<?= htmlspecialchars($rutaPerfil) ?>" class="btn-user-pill-img1 btn-user" title="<?= htmlspecialchars($textoPerfil) ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        <span class="btn-user-label"><?= $esPersonal ? htmlspecialchars($textoPerfil) . ' (' . $nombre . ')' : $nombre; ?></span>
                    </a>
                <?php else: ?>
                    <button type="button" class="btn-user-pill-img1 btn-user" onclick="if(typeof abrirModalAuth==='function') abrirModalAuth('login'); else window.location.href='index.php?action=login';" title="Entrar">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        <span class="btn-user-label">Entrar</span>
                    </button>
                <?php endif; ?>

                <!-- Notificaciones (Cuando está logueado) -->
                <?php if ($estaAutenticado && !$esPersonal): ?>
                    <button class="btn-icon" id="btnNotificaciones" type="button" onclick="if(typeof toggleNotificaciones==='function') toggleNotificaciones(event);" title="Notificaciones" style="width:40px; height:40px; border-radius:50%; border:1px solid #e2e8f0; background:#fff; display:flex; align-items:center; justify-content:center; cursor:pointer;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                            stroke="#334155" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                            <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                        </svg>
                        <span class="badge notif-badge" id="notifBadge" style="display: <?= $cantPedidosActivos > 0 ? 'flex' : 'none'; ?>;"><?= $cantPedidosActivos; ?></span>
                    </button>
                <?php endif; ?>

                <!-- Botón Carrito Circular / Píldora con insignia roja -->
                <?php if (!$esPersonal): ?><button type="button" class="btn-cart-circle-img1 btn-cart" id="btnAbrirCarrito" title="Ver Carrito de Compras">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="9" cy="21" r="1"></circle>
                        <circle cx="20" cy="21" r="1"></circle>
                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                    </svg>
                    <span class="btn-cart-label">Carrito</span>
                    <span class="cart-count-badge-img1" id="cartCountHeader"><?= $cantTotalCarritoHeader; ?></span>
                </button><?php endif; ?>
            </div>
        </div>

        <!-- Tier 2: Barra Verde Automatizada de Categorías -->
        <?php include __DIR__ . '/tier2_categories_nav.php'; ?>
    </header>

    <!-- Modal de Autenticación Global (Login / Registro) -->
    <?php include_once __DIR__ . '/../../Login/auth_modal.php'; ?>

