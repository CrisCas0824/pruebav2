<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$estaAutenticado = isset($_SESSION['usuario_id']);
$rol = $_SESSION['usuario_rol'] ?? '';

// Home unificado para clientes y administradores por igual

// Si es cajero y entra al home, puede ir también a su POS
$nombre = $estaAutenticado ? htmlspecialchars($_SESSION['usuario_nombre']) : 'Invitado';

if (!isset($categorias_bd)) {
    $categorias_bd = [];
}

if (!isset($moments_bd)) {
    $moments_bd = [];
}

// Mapa de productos en carrito para estado inicial UI (Agregado / Selector)
$cartUserIdHome = $_SESSION['usuario_id'] ?? 'invitado';
$carritoSesionHome = $_SESSION['carritos'][$cartUserIdHome] ?? [];
$carritoCantidadesHome = [];
foreach ($carritoSesionHome as $cItem) {
    $carritoCantidadesHome[(int)$cItem['id_producto']] = (int)$cItem['cantidad'];
}

if (!isset($grupMomento_bd)) {
    $grupMomento_bd = [];
}

if (!isset($product_bd)) {
    $product_bd = [];
}

$pedidosUsuario = [];
if (isset($_SESSION['usuario_id'])) {
    try {
        require_once 'Models/pedidoModel.php';
        $pModel = new PedidoModel();
        $pedidosUsuario = $pModel->getPedidosByUsuario((int)$_SESSION['usuario_id']);
    } catch (Exception $e) {
        $pedidosUsuario = [];
    }
}
$cantPedidosActivos = 0;
if (!empty($pedidosUsuario)) {
    foreach ($pedidosUsuario as $p) {
        $e = strtolower($p['estado'] ?? '');
        if ($e !== 'entregado' && $e !== 'cancelado') {
            $cantPedidosActivos++;
        }
    }
}

$carruselConfigFile = __DIR__ . '/../../Config/carrusel.json';
$slidesCarrusel = file_exists($carruselConfigFile) ? json_decode(file_get_contents($carruselConfigFile), true) : null;
if (!$slidesCarrusel || !is_array($slidesCarrusel)) {
    $slidesCarrusel = [
        ["imagen" => "Images/123.webp", "link" => "#sugerenciasGrid", "enlace" => "#sugerenciasGrid", "boton" => "Ver más"],
        ["imagen" => "Images/1234.webp", "link" => "#sugerenciasGrid", "enlace" => "#sugerenciasGrid", "boton" => "Ver más"],
        ["imagen" => "Images/12345.webp", "link" => "#sugerenciasGrid", "enlace" => "#sugerenciasGrid", "boton" => "Ver más"],
        ["imagen" => "Images/123456.webp", "link" => "#sugerenciasGrid", "enlace" => "#sugerenciasGrid", "boton" => "Ver más"]
    ];
}

if (!function_exists('formatearUnidadMedidaImg4')) {
    function formatearUnidadMedidaImg4($p) {
        $t = strtolower($p['titulo'] ?? '');
        if (!empty($p['unidad_medida']) && strtoupper($p['unidad_medida']) === 'KG') return '1 kg';
        if (strpos($t, '5kg') !== false) return 'Saco 5 kg';
        if (strpos($t, '3kg') !== false) return 'Bolsa 3 kg';
        if (strpos($t, '2.6kg') !== false) return 'Bolsa 2.6 kg';
        if (strpos($t, '2.2kg') !== false) return 'Entero 2.2 kg';
        if (strpos($t, '1kg') !== false) return '1 kg';
        if (strpos($t, '1.5l') !== false) return 'Botella 1.5 L';
        if (strpos($t, '2.5l') !== false) return 'Botella 2.5 L';
        if (strpos($t, '1l') !== false || strpos($t, 'botella') !== false) return 'Botella 1 L';
        if (strpos($t, 'pack 6') !== false) return 'Pack 6 und';
        if (strpos($t, 'pack 8') !== false) return 'Pack 8 und';
        if (strpos($t, '44 und') !== false) return 'Pack 44 und';
        if (strpos($t, '10 und') !== false) return 'Bolsa 10 und';
        if (strpos($t, '30 und') !== false || strpos($t, 'huevo') !== false) return 'Jaba 30 und';
        if (strpos($t, '650g') !== false) return 'Bolsa 650g';
        if (strpos($t, '500g') !== false) return 'Paquete 500g';
        if (strpos($t, '400g') !== false) return 'Lata 400g';
        if (strpos($t, '375ml') !== false) return 'Frasco 375ml';
        if (strpos($t, '160g') !== false) return 'Bolsa 160g';
        return !empty($p['unidad_medida']) && $p['unidad_medida'] !== 'UND' ? $p['unidad_medida'] : '1 und';
    }
}

if (!function_exists('formatearSkuImg4')) {
    function formatearSkuImg4($p) {
        if (!empty($p['codigo'])) return $p['codigo'];
        return '00' . (1928 + (int)($p['id_producto'] ?? 1) - 1);
    }
}

// Agrupar productos activos por categoría para las secciones del Home
$productosPorCategoria = [];
foreach ($product_bd as $prod) {
    $idCat = (int)($prod['id_categoria'] ?? 0);
    if ($idCat > 0) {
        $productosPorCategoria[$idCat][] = $prod;
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tienda La Caserita</title>
    <link rel="icon" type="image/png" href="Images/logo.png">
    <link rel="shortcut icon" href="Images/logo.png">
    <link rel="apple-touch-icon" href="Images/logo.png">
    <link rel="stylesheet" href="Styles/global.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="Styles/styles-home.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="Styles/styles-carrito.css?v=<?php echo time(); ?>">
    <script>
        window.usuarioAutenticado = <?= $estaAutenticado ? 'true' : 'false'; ?>;
    </script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <script src="Scripts/normalizador-modales.js?v=<?= filemtime(__DIR__ . '/../../Scripts/normalizador-modales.js'); ?>" defer></script>
</head>

<body class="<?= in_array($_SESSION['usuario_rol'] ?? '', ['admin', 'cajero', 'repartidor'], true) ? 'role-staff' : ''; ?>">
    <?php include __DIR__ . '/partials/mobile_sidebar.php'; ?>

    <!-- Encabezado Principal Completo (Tier 1 + Tier 2) -->
    <?php include __DIR__ . '/partials/header_principal.php'; ?>

    
    <div class="notif-overlay" id="notifOverlay" onclick="cerrarNotificaciones(event)"></div>
    <aside class="notif-sidebar" id="notifDropdown">
        <div class="notif-header" style="color: #0f172a; padding: 18px 20px; display: flex; align-items: center; justify-content: space-between;">
            <div>
                <h2 style="display:flex; align-items:center; gap:8px; margin: 0; font-size: 1.25rem;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                    Mis Pedidos
                </h2>
                <p class="notif-header-subtitle" style="margin: 4px 0 0 0; font-size: 0.85rem; opacity: 0.9;">Estado de tus compras en tiempo real</p>
            </div>
            <button type="button" class="btn-close-universal" id="closeNotifDropdown" onclick="cerrarNotificaciones(event)" aria-label="Cerrar notificaciones" style="position: static; margin-left: auto;">
                &times;
            </button>
        </div>

        <div class="notif-content" id="notifItems">
            <?php if (!empty($pedidosUsuario)): ?>
                <?php foreach (array_slice($pedidosUsuario, 0, 10) as $ped):
                    $est = strtolower($ped['estado'] ?? 'pendiente');
                    $badgeStyle = 'background:#fef3c7;color:#92400e;';
                    $badgeText = ucfirst($est);
                    if ($est === 'confirmado' || $est === 'aceptado') {
                        $badgeStyle = 'background:#d1fae5;color:#065f46;';
                    } elseif ($est === 'en camino' || $est === 'despachado') {
                        $badgeStyle = 'background:#e0f2fe;color:#0369a1;';
                    } elseif ($est === 'entregado') {
                        $badgeStyle = 'background:#dcfce7;color:#15803d;';
                    } elseif ($est === 'cancelado') {
                        $badgeStyle = 'background:#fee2e2;color:#b91c1c;';
                    }
                    $fechaFormat = !empty($ped['fecha']) ? date('d M Y, h:i A', strtotime($ped['fecha'])) : 'Reciente';
                ?>
                <div class="stock-dropdown-item" style="cursor:pointer; padding: 16px 20px; border-bottom: 1px solid #f1f5f9; display: flex; gap: 14px; align-items: flex-start;" onclick="window.location.href='index.php?action=detalle_pedido_usuario&id=<?= (int)$ped['id_pedido']; ?>'">
                    <div style="width:42px;height:42px;background:#e0f2fe;border-radius:12px;display:flex;align-items:center;justify-content:center;color:#0284c7;flex-shrink:0;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
                    </div>
                    <div style="flex:1;">
                        <div style="display:flex;justify-content:space-between;align-items:center;">
                            <strong style="font-size:0.95rem;color:#0f172a;">Pedido #<?= str_pad((int)$ped['id_pedido'], 5, '0', STR_PAD_LEFT); ?></strong>
                            <small style="font-size:0.74rem;color:#94a3b8;"><?= $fechaFormat; ?></small>
                        </div>
                        <p style="margin:4px 0;color:#15803D;font-size:0.85rem;font-weight:600;">Total: S/ <?= number_format((float)($ped['total'] ?? 0), 2); ?> - <?= htmlspecialchars($ped['tipo_entrega'] ?? 'Delivery'); ?></p>
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:6px;">
                            <span style="<?= $badgeStyle; ?>padding:3px 8px;border-radius:10px;font-size:0.72rem;font-weight:800;"><?= $badgeText; ?></span>
                            <span style="font-size:0.8rem;color:#10b981;font-weight:bold;">Ver detalle →</span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="padding: 50px 20px; text-align: center; color: #64748b;">
                    <div style="margin-bottom: 10px; display: inline-flex; align-items: center; justify-content: center; width: 64px; height: 64px; border-radius: 50%; background: #f1f5f9; color: #94a3b8;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
                    </div>
                    <strong style="font-size: 1rem; color: #0f172a; display: block;">Aún no has realizado pedidos</strong>
                    <p style="font-size: 0.85rem; margin-top: 4px;">Agrega productos al carrito para realizar tu primera compra.</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="notif-footer">
            <a href="index.php?action=mi_cuenta" class="btn-notif-action">
                Ver todos mis pedidos →
            </a>
        </div>
    </aside>

    <main>
        <section class="search-results-section" id="searchResultsSection" style="display: none;">
            <div class="search-results-header">
                <div class="search-results-breadcrumb">
                    <a href="#" onclick="cerrarBusqueda()">Inicio</a>
                    <span>›</span>
                    <span id="searchResultsTerm">búsqueda</span>
                </div>
                <div class="search-results-title">
                    <h2>Resultado de búsqueda: <span id="searchResultsCount">0</span> PRODUCTOS</h2>
                </div>
            </div>
            <div class="search-results-grid" id="searchResultsGrid"></div>
            <div class="search-results-footer" id="searchResultsFooter" style="display: none;">
                <div class="search-results-progress">
                    <div class="progress-bar">
                        <div class="progress-fill" id="searchProgressFill"></div>
                    </div>
                    <span id="searchShowingText">Mostrando 60 de 100</span>
                </div>
                <button class="search-show-more-btn" id="searchShowMoreBtn">MOSTRAR MÁS</button>
            </div>
        </section>

        <div id="mainContent">
            <!-- Carrusel Hero (Full-width, flechas siempre visibles y paginación con puntos activos) -->
            <section class="hero-section" id="heroSlider">
                <div class="hero-slides">
                    <?php foreach ($slidesCarrusel as $idx => $s): 
                        $imgSlide = !empty($s['imagen']) ? $s['imagen'] : 'Images/123.webp';
                        $enlace = !empty($s['link']) ? $s['link'] : (!empty($s['enlace']) ? $s['enlace'] : '#sugerenciasGrid');
                    ?>
                        <div class="hero-slide <?= $idx === 0 ? 'active' : ''; ?>" data-slide-index="<?= $idx; ?>">
                            <a href="<?= htmlspecialchars($enlace); ?>" class="hero-slide-link" title="Ver más">
                                <img src="<?= htmlspecialchars($imgSlide); ?>" alt="Banner <?= $idx + 1; ?>" class="hero-slide-full-img">
                                <span class="hero-ver-mas-btn">
                                    Ver más
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="9 18 15 12 9 6"></polyline>
                                    </svg>
                                </span>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Puntos de navegación con punto activo alargado -->
                <div class="hero-dots">
                    <?php foreach ($slidesCarrusel as $idx => $s): ?>
                        <button type="button" class="hero-dot <?= $idx === 0 ? 'active' : ''; ?>" data-slide="<?= $idx; ?>" aria-label="Slide <?= $idx + 1; ?>"></button>
                    <?php endforeach; ?>
                </div>

                <!-- Flechas en círculos perfectos -->
                <button type="button" class="hero-arrow hero-prev" aria-label="atrás">❮</button>
                <button type="button" class="hero-arrow hero-next" aria-label="adelante">❯</button>
            </section>

            <section class="sugerencias-section" id="seccionSugerencias">
                <div class="categoria-bloque-popular">
                    <div class="categoria-bloque-header">
                        <div class="categoria-bloque-title-wrap">
                            <span class="categoria-bloque-tag">DESTACADO</span>
                            <h3 class="categoria-bloque-title">Ofertas del Día</h3>
                            <p class="categoria-bloque-subtitle">Ofertas y productos recomendados especialmente para ti hoy</p>
                        </div>
                        <a href="index.php?action=ver_categoria&ofertas=1&nombre=<?= urlencode('Ofertas'); ?>" class="btn-ver-todos-categoria" title="Ver todas las sugerencias">
                            Ver todos
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="9 18 15 12 9 6"></polyline>
                            </svg>
                        </a>
                    </div>
                    <div class="populares-carousel-container">
                        <button type="button" class="nav-arrow nav-arrow-populares" onclick="moverCarrusel('sugerenciasGrid', -1)" aria-label="Anterior">&lt;</button>
                        <div class="populares-productos-row" id="sugerenciasGrid">
                            <?php foreach ($product_bd as $producto): ?>
                                <?php if ((int) $producto['es_sugerencia'] === 1): ?>
                                    <?php 
                                        $tieneDesc = !empty($producto['precio_anterior']) && (float)$producto['precio_anterior'] > (float)$producto['precio'];
                                        $pctDesc = $tieneDesc ? round((((float)$producto['precio_anterior'] - (float)$producto['precio']) / (float)$producto['precio_anterior']) * 100) : 0;
                                    ?>
                                    <?php 
                                        $idProd = (int) $producto['id_producto'];
                                        $enCart = isset($carritoCantidadesHome[$idProd]) && $carritoCantidadesHome[$idProd] > 0;
                                        $cantCart = $enCart ? $carritoCantidadesHome[$idProd] : 1;
                                    ?>
                                    <div class="producto-card <?= $enCart ? 'is-in-cart' : ''; ?>">
                                        <a href="index.php?action=detalle_producto&id=<?= $idProd; ?>" class="product-card-link">
                                            <div class="producto-img">
                                                <?php if ($tieneDesc && $pctDesc > 0): ?>
                                                    <span class="badge-descuento-porcentual">-<?= $pctDesc; ?>%</span>
                                                <?php endif; ?>
                                                <img src="<?= htmlspecialchars($producto['imagen']); ?>" alt="<?= htmlspecialchars($producto['titulo']); ?>" loading="lazy" onerror="this.src='Images/logo.png'">
                                            </div>

                                            <span class="product-card-sku">SKU: <?= htmlspecialchars(formatearSkuImg4($producto)); ?></span>
                                            <h4 class="producto-titulo"><?= htmlspecialchars($producto['titulo']); ?></h4>
                                        </a>
                                        <span class="product-card-unit"><?= htmlspecialchars(formatearUnidadMedidaImg4($producto)); ?></span>

                                        <div class="product-card-price-row">
                                            <span class="product-card-price">S/ <?= number_format((float) $producto['precio'], 2); ?></span>
                                            <?php if ($tieneDesc): ?>
                                                <span class="product-card-old-price">S/ <?= number_format((float) $producto['precio_anterior'], 2); ?></span>
                                            <?php endif; ?>
                                        </div>

                                        <div class="product-card-actions-row <?= $enCart ? 'is-in-cart' : ''; ?>">
                                            <div class="product-qty-pill" style="display: <?= $enCart ? 'flex' : 'none'; ?>;" onclick="event.stopPropagation();">
                                                <button type="button" class="btn-qty-minus" onclick="cambiarCardQty(this, -1)">-</button>
                                                <span class="card-qty-val"><?= $cantCart; ?></span>
                                                <button type="button" class="btn-qty-plus" onclick="cambiarCardQty(this, 1)">+</button>
                                            </div>
                                            <button type="button" class="btn-add-cart-pill btn-agregar-carrito <?= $enCart ? 'is-added' : ''; ?>"
                                                data-id-producto="<?= $idProd; ?>"
                                                onclick="agregarConQty(this, <?= $idProd; ?>);">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                    <circle cx="9" cy="21" r="1"></circle>
                                                    <circle cx="20" cy="21" r="1"></circle>
                                                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                                                </svg>
                                                <span><?= $enCart ? 'Agregado' : 'Agregar'; ?></span>
                                            </button>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="nav-arrow nav-arrow-populares" onclick="moverCarrusel('sugerenciasGrid', 1)" aria-label="Siguiente">&gt;</button>
                    </div>
                </div>
            </section>

            <!-- SECCIÓN 3: PRODUCTOS POPULARES POR CATEGORÍA MAESTRA -->
            <?php
            $bloquesPopulares = [
                [
                    'momento_id' => 1,
                    'titulo' => 'Populares en Frescos y Proteínas',
                    'url' => 'index.php?action=ver_categoria&id_momento=1&nombre=' . urlencode('Frescos y Proteínas'),
                    'grid_id' => 'gridPopularesFrescos'
                ],
                [
                    'momento_id' => 2,
                    'titulo' => 'Populares en Lácteos y Huevos',
                    'url' => 'index.php?action=ver_categoria&id_momento=2&nombre=' . urlencode('Lácteos y Huevos'),
                    'grid_id' => 'gridPopularesLacteos'
                ],
                [
                    'momento_id' => 3,
                    'titulo' => 'Populares en Abarrotes y Despensa',
                    'url' => 'index.php?action=ver_categoria&id_momento=3&nombre=' . urlencode('Abarrotes y Despensa'),
                    'grid_id' => 'gridPopularesAbarrotes'
                ],
                [
                    'momento_id' => 4,
                    'titulo' => 'Populares en Bebidas y Snacks',
                    'url' => 'index.php?action=ver_categoria&id_momento=4&nombre=' . urlencode('Bebidas y Snacks'),
                    'grid_id' => 'gridPopularesBebidas'
                ],
                [
                    'momento_id' => 5,
                    'titulo' => 'Populares en Otros',
                    'url' => 'index.php?action=ver_categoria&id_momento=5&nombre=' . urlencode('Otros'),
                    'grid_id' => 'gridPopularesOtros'
                ],
            ];
            ?>
            <section class="populares-categorias-section" id="popularesCategoriasSection">
                <div class="populares-categorias-header-main">
                    <h2>Productos Populares por Categoría</h2>
                    <p>Descubre lo más destacado y preferido por nuestras familias en cada sección</p>
                </div>

                <?php foreach ($bloquesPopulares as $bloque): 
                    $prodsBloque = array_values(array_filter($product_bd, function($p) use ($bloque) {
                        return (int)($p['id_momento'] ?? 0) === $bloque['momento_id'];
                    }));
                    if (empty($prodsBloque)) continue;
                ?>
                    <div class="categoria-bloque-popular">
                        <div class="categoria-bloque-header">
                            <div class="categoria-bloque-title-wrap">
                                <h3 class="categoria-bloque-title"><?= htmlspecialchars($bloque['titulo']); ?></h3>
                                <p class="categoria-bloque-subtitle"><?= htmlspecialchars($bloque['subtitulo'] ?? ''); ?></p>
                            </div>
                            <a href="<?= htmlspecialchars($bloque['url']); ?>" class="btn-ver-todos-categoria" title="Ver pasillo completo">
                                Ver todos (<?= count($prodsBloque); ?>)
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="9 18 15 12 9 6"></polyline>
                                </svg>
                            </a>
                        </div>
                        
                        <div class="populares-carousel-container">
                            <button type="button" class="nav-arrow nav-arrow-populares" onclick="moverCarrusel('<?= $bloque['grid_id']; ?>', -1)" aria-label="Anterior">&lt;</button>
                            <div class="populares-productos-row" id="<?= $bloque['grid_id']; ?>">
                                <?php 
                                $itemsAMostrar = array_slice($prodsBloque, 0, 10);
                                foreach ($itemsAMostrar as $producto): 
                                    $tieneDesc = !empty($producto['precio_anterior']) && (float)$producto['precio_anterior'] > (float)$producto['precio'];
                                    $pctDesc = $tieneDesc ? round((((float)$producto['precio_anterior'] - (float)$producto['precio']) / (float)$producto['precio_anterior']) * 100) : 0;
                                ?>
                                    <?php 
                                        $idProd = (int) $producto['id_producto'];
                                        $enCart = isset($carritoCantidadesHome[$idProd]) && $carritoCantidadesHome[$idProd] > 0;
                                        $cantCart = $enCart ? $carritoCantidadesHome[$idProd] : 1;
                                    ?>
                                    <div class="producto-card <?= $enCart ? 'is-in-cart' : ''; ?>">
                                        <a href="index.php?action=detalle_producto&id=<?= $idProd; ?>" class="product-card-link">
                                            <div class="producto-img">
                                                <?php if ($tieneDesc && $pctDesc > 0): ?>
                                                    <span class="badge-descuento-porcentual">-<?= $pctDesc; ?>%</span>
                                                <?php endif; ?>
                                                <img src="<?= htmlspecialchars($producto['imagen']); ?>" alt="<?= htmlspecialchars($producto['titulo']); ?>" loading="lazy" onerror="this.src='Images/logo.png'">
                                            </div>
                                            <span class="product-card-sku">SKU: <?= htmlspecialchars(formatearSkuImg4($producto)); ?></span>
                                            <h4 class="producto-titulo"><?= htmlspecialchars($producto['titulo']); ?></h4>
                                        </a>
                                        <span class="product-card-unit"><?= htmlspecialchars(formatearUnidadMedidaImg4($producto)); ?></span>
                                        <div class="product-card-price-row">
                                            <span class="product-card-price">S/ <?= number_format((float)$producto['precio'], 2); ?></span>
                                            <?php if ($tieneDesc): ?>
                                                <span class="product-card-old-price">S/ <?= number_format((float)$producto['precio_anterior'], 2); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="product-card-actions-row <?= $enCart ? 'is-in-cart' : ''; ?>">
                                            <div class="product-qty-pill" style="display: <?= $enCart ? 'flex' : 'none'; ?>;" onclick="event.stopPropagation();">
                                                <button type="button" class="btn-qty-minus" onclick="cambiarCardQty(this, -1)">-</button>
                                                <span class="card-qty-val"><?= $cantCart; ?></span>
                                                <button type="button" class="btn-qty-plus" onclick="cambiarCardQty(this, 1)">+</button>
                                            </div>
                                            <button type="button" class="btn-add-cart-pill btn-agregar-carrito <?= $enCart ? 'is-added' : ''; ?>"
                                                data-id-producto="<?= $idProd; ?>"
                                                onclick="agregarConQty(this, <?= $idProd; ?>);">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                    <circle cx="9" cy="21" r="1"></circle>
                                                    <circle cx="20" cy="21" r="1"></circle>
                                                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                                                </svg>
                                                <span><?= $enCart ? 'Agregado' : 'Agregar'; ?></span>
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" class="nav-arrow nav-arrow-populares" onclick="moverCarrusel('<?= $bloque['grid_id']; ?>', 1)" aria-label="Siguiente">&gt;</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </section>

            <!-- SECCIÓN 4: RESUMEN / CATÁLOGO DE LAS 5 FAMILIAS MAESTRAS -->
            <?php
            $familiasMaestrasCatalogo = [
                [
                    'nombre' => 'Frescos y Proteínas',
                    'descripcion' => 'Frutas, verduras, carnes y pescados',
                    'id_momento' => 1,
                    'url' => 'index.php?action=ver_categoria&id_momento=1&nombre=' . urlencode('Frescos y Proteínas')
                ],
                [
                    'nombre' => 'Lácteos y Huevos',
                    'descripcion' => 'Leches, quesos, embutidos y panadería',
                    'id_momento' => 2,
                    'url' => 'index.php?action=ver_categoria&id_momento=2&nombre=' . urlencode('Lácteos y Huevos')
                ],
                [
                    'nombre' => 'Abarrotes y Despensa',
                    'descripcion' => 'Granos, aceites, pastas y conservas',
                    'id_momento' => 3,
                    'url' => 'index.php?action=ver_categoria&id_momento=3&nombre=' . urlencode('Abarrotes y Despensa')
                ],
                [
                    'nombre' => 'Bebidas y Snacks',
                    'descripcion' => 'Gaseosas, jugos, aguas y golosinas',
                    'id_momento' => 4,
                    'url' => 'index.php?action=ver_categoria&id_momento=4&nombre=' . urlencode('Bebidas y Snacks')
                ],
                [
                    'nombre' => 'Otros',
                    'descripcion' => 'Cuidado personal, limpieza del hogar y mascotas',
                    'id_momento' => 5,
                    'url' => 'index.php?action=ver_categoria&id_momento=5&nombre=' . urlencode('Otros')
                ],
            ];
            ?>
            <?php
            $subcategoriasSmallList = [
                [
                    'id' => 1,
                    'nombre' => 'Frutas',
                    'url' => 'index.php?action=ver_categoria&id=1&nombre=' . urlencode('Frutas'),
                    'imagen' => 'https://images.unsplash.com/photo-1619566636858-adf3ef46400b?w=150&auto=format&fit=crop&q=80'
                ],
                [
                    'id' => 2,
                    'nombre' => 'Verduras',
                    'url' => 'index.php?action=ver_categoria&id=2&nombre=' . urlencode('Verduras'),
                    'imagen' => 'https://images.unsplash.com/photo-1540420773420-3366772f4999?w=150&auto=format&fit=crop&q=80'
                ],
                [
                    'id_grupo' => 2,
                    'nombre' => 'Carnes y Aves',
                    'url' => 'index.php?action=ver_categoria&id_grupo=2&nombre=' . urlencode('Carnes y Aves'),
                    'imagen' => 'https://images.unsplash.com/photo-1607623814075-e51df1bdc82f?w=150&auto=format&fit=crop&q=80'
                ],
                [
                    'id_grupo' => 3,
                    'nombre' => 'Pescados y Mariscos',
                    'url' => 'index.php?action=ver_categoria&id_grupo=3&nombre=' . urlencode('Pescados y Mariscos'),
                    'imagen' => 'https://images.unsplash.com/photo-1534422298391-e4f8c172dddb?w=150&auto=format&fit=crop&q=80'
                ],
                [
                    'id_grupo' => 4,
                    'nombre' => 'Lácteos y Leches',
                    'url' => 'index.php?action=ver_categoria&id_grupo=4&nombre=' . urlencode('Lácteos'),
                    'imagen' => 'https://images.unsplash.com/photo-1550583724-b2692b85b150?w=150&auto=format&fit=crop&q=80'
                ],
                [
                    'id_grupo' => 5,
                    'nombre' => 'Quesos y Embutidos',
                    'url' => 'index.php?action=ver_categoria&id_grupo=5&nombre=' . urlencode('Quesos y Embutidos'),
                    'imagen' => 'https://images.unsplash.com/photo-1486297678162-eb2a19b0a32d?w=150&auto=format&fit=crop&q=80'
                ],
                [
                    'id_grupo' => 6,
                    'nombre' => 'Panadería y Huevos',
                    'url' => 'index.php?action=ver_categoria&id_grupo=6&nombre=' . urlencode('Huevos y Panadería'),
                    'imagen' => 'https://images.unsplash.com/photo-1509440159596-0249088772ff?w=150&auto=format&fit=crop&q=80'
                ],
                [
                    'id_grupo' => 7,
                    'nombre' => 'Abarrotes y Granos',
                    'url' => 'index.php?action=ver_categoria&id_grupo=7&nombre=' . urlencode('Granos y Menestras'),
                    'imagen' => 'https://images.unsplash.com/photo-1586201375761-83865001e31c?w=150&auto=format&fit=crop&q=80'
                ],
                [
                    'id_grupo' => 10,
                    'nombre' => 'Bebidas y Aguas',
                    'url' => 'index.php?action=ver_categoria&id_grupo=10&nombre=' . urlencode('Bebidas'),
                    'imagen' => 'https://images.unsplash.com/photo-1622483767028-3f66f32aef97?w=150&auto=format&fit=crop&q=80'
                ],
                [
                    'id_grupo' => 11,
                    'nombre' => 'Snacks y Golosinas',
                    'url' => 'index.php?action=ver_categoria&id_grupo=11&nombre=' . urlencode('Snacks y Golosinas'),
                    'imagen' => 'https://images.unsplash.com/photo-1621996346565-e3d5d6281699?w=150&auto=format&fit=crop&q=80'
                ],
                [
                    'id_grupo' => 12,
                    'nombre' => 'Limpieza del Hogar',
                    'url' => 'index.php?action=ver_categoria&id_grupo=12&nombre=' . urlencode('Limpieza del Hogar'),
                    'imagen' => 'https://images.unsplash.com/photo-1583947215259-38e31be8751f?w=150&auto=format&fit=crop&q=80'
                ],
                [
                    'id' => 14,
                    'nombre' => 'Cuidado Personal',
                    'url' => 'index.php?action=ver_categoria&id=14&nombre=' . urlencode('Cuidado Personal'),
                    'imagen' => 'https://images.unsplash.com/photo-1556228720-195a672e8a03?w=150&auto=format&fit=crop&q=80'
                ],
                [
                    'id' => 15,
                    'nombre' => 'Mascotas',
                    'url' => 'index.php?action=ver_categoria&id=15&nombre=' . urlencode('Mascotas'),
                    'imagen' => 'https://images.unsplash.com/photo-1583511655857-d19b40a7a54e?w=150&auto=format&fit=crop&q=80'
                ],
            ];

            // Asociar imagen dinámica de categoría si existe en BD
            foreach ($subcategoriasSmallList as &$subItem) {
                if (isset($subItem['id'])) {
                    foreach ($categorias_bd as $catBd) {
                        if ((int)$catBd['id_categoria'] === $subItem['id'] && !empty($catBd['imagen'])) {
                            $subItem['imagen'] = $catBd['imagen'];
                            break;
                        }
                    }
                }
            }
            unset($subItem);
            ?>
            <section class="catalogo-todas-categorias-section" id="seccionTodasCategorias">
                <div class="catalogo-categorias-header">
                    <h2>Catálogo de Macrocategorías</h2>
                    <p>Explora nuestras 5 familias maestras de productos con entrega rápida a domicilio</p>
                </div>
                <div class="categorias-grid-todas">
                    <?php 
                    $defaultMomentoImgs = [
                        1 => 'https://images.unsplash.com/photo-1610832958506-aa56368176cf?w=500&auto=format&fit=crop&q=80',
                        2 => 'https://images.unsplash.com/photo-1528750997573-59b89d56f4f7?w=500&auto=format&fit=crop&q=80',
                        3 => 'https://images.unsplash.com/photo-1588964895597-cfccd6e2dbf9?w=500&auto=format&fit=crop&q=80',
                        4 => 'https://images.unsplash.com/photo-1527661591475-527312dd65f5?w=500&auto=format&fit=crop&q=80',
                        5 => 'https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?w=500&auto=format&fit=crop&q=80',
                    ];
                    foreach ($familiasMaestrasCatalogo as $fam): 
                        $mId = (int)($fam['id_momento'] ?? 0);
                        $imgSrcFam = $fam['imagen'] ?? ($defaultMomentoImgs[$mId] ?? 'Images/logo.png');
                        if (!empty($moments_bd)) {
                            foreach ($moments_bd as $m) {
                                if ((int)($m['id_momento'] ?? 0) === $mId && !empty($m['imagen'])) {
                                    $imgSrcFam = $m['imagen'];
                                    break;
                                }
                            }
                        }
                    ?>
                        <a href="<?= htmlspecialchars($fam['url']); ?>" class="categoria-catalogo-card" title="Ver pasillo <?= htmlspecialchars($fam['nombre']); ?>">
                            <div class="categoria-catalogo-img-wrap">
                                <img src="<?= htmlspecialchars($imgSrcFam); ?>" alt="<?= htmlspecialchars($fam['nombre']); ?>" loading="lazy" onerror="this.src='Images/logo.png'">
                            </div>
                            <div class="categoria-catalogo-info">
                                <h3 class="categoria-catalogo-name"><?= htmlspecialchars($fam['nombre']); ?></h3>
                                <p class="categoria-catalogo-desc"><?= htmlspecialchars($fam['descripcion']); ?></p>
                                <span class="categoria-catalogo-link-text">
                                    Ver pasillo
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="9 18 15 12 9 6"></polyline>
                                    </svg>
                                </span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>

                <!-- BLOQUE SECUNDARIO: CATEGORÍAS EN TARJETAS PEQUEÑAS -->
                <div class="subcategorias-catalogo-wrap">
                    <div class="subcategorias-catalogo-header">
                        <h3>Categorías por Pasillo</h3>
                        <p>Accede de forma rápida y directa a la sección específica que buscas</p>
                    </div>
                    <div class="subcategorias-grid-small">
                        <?php 
                        $defaultGrupoImgsUser = [
                            1 => 'https://images.unsplash.com/photo-1619566636858-adf3ef46400b?w=150&auto=format&fit=crop&q=80',
                            2 => 'https://images.unsplash.com/photo-1607623814075-e51df1bdc82f?w=150&auto=format&fit=crop&q=80',
                            3 => 'https://images.unsplash.com/photo-1534422298391-e4f8c172dddb?w=150&auto=format&fit=crop&q=80',
                            4 => 'https://images.unsplash.com/photo-1550583724-b2692b85b150?w=150&auto=format&fit=crop&q=80',
                            5 => 'https://images.unsplash.com/photo-1486297678162-eb2a19b0a32d?w=150&auto=format&fit=crop&q=80',
                            6 => 'https://images.unsplash.com/photo-1509440159596-0249088772ff?w=150&auto=format&fit=crop&q=80',
                            7 => 'https://images.unsplash.com/photo-1586201375761-83865001e31c?w=150&auto=format&fit=crop&q=80',
                            8 => 'https://images.unsplash.com/photo-1474979266404-7eaacbcd87c5?w=150&auto=format&fit=crop&q=80',
                            9 => 'https://images.unsplash.com/photo-1621996346565-e3d5d6281699?w=150&auto=format&fit=crop&q=80',
                            10 => 'https://images.unsplash.com/photo-1622483767028-3f66f32aef97?w=150&auto=format&fit=crop&q=80',
                            11 => 'https://images.unsplash.com/photo-1621996346565-e3d5d6281699?w=150&auto=format&fit=crop&q=80',
                            12 => 'https://images.unsplash.com/photo-1583947215259-38e31be8751f?w=150&auto=format&fit=crop&q=80',
                            13 => 'https://images.unsplash.com/photo-1556228720-195a672e8a03?w=150&auto=format&fit=crop&q=80',
                            15 => 'https://images.unsplash.com/photo-1583511655857-d19b40a7a54e?w=150&auto=format&fit=crop&q=80',
                        ];
                        if (!empty($grupMomento_bd)):
                            foreach ($grupMomento_bd as $gItem):
                                if (isset($gItem['activo']) && (int)$gItem['activo'] === 0) continue;
                                $gId = (int)$gItem['id_grupo'];
                                $gNombre = $gItem['nombre'];
                                $gImg = !empty($gItem['imagen']) ? $gItem['imagen'] : ($defaultGrupoImgsUser[$gId] ?? 'Images/logo.png');
                                $gUrl = 'index.php?action=ver_categoria&grupo=' . $gId . '&nombre=' . urlencode($gNombre);
                        ?>
                            <a href="<?= htmlspecialchars($gUrl); ?>" class="subcategoria-card-small" title="Explorar <?= htmlspecialchars($gNombre); ?>">
                                <div class="subcategoria-img-small">
                                    <img src="<?= htmlspecialchars($gImg); ?>" alt="<?= htmlspecialchars($gNombre); ?>" loading="lazy" onerror="this.src='Images/logo.png'">
                                </div>
                                <span class="subcategoria-name-small"><?= htmlspecialchars($gNombre); ?></span>
                            </a>
                        <?php 
                            endforeach;
                        endif;
                        ?>
                    </div>
                </div>
            </section>

            <!-- SECCIÓN 5: BANNER PROMOCIONAL "Descárgalo aquí" -->
            <section class="app-promo-banner-section" id="seccionAppPromo">
                <div class="app-promo-banner-card">
                    <div class="app-promo-banner-left">
                        <p class="app-promo-sub">Conoce los beneficios de nuestra</p>
                        <h3 class="app-promo-title">APP Tienda "La Caserita"</h3>
                    </div>
                    <div class="app-promo-banner-right">
                        <a href="#descargar-app" class="btn-app-descargar" onclick="alert('Próximamente disponible en Google Play Store y App Store.'); return false;">
                            Descárgalo aquí
                        </a>
                    </div>
                </div>
            </section>

            <!-- SECCIÓN 6: UBICACIÓN / ENCUÉNTRANOS -->
            <section class="ubicacion-section" id="seccionUbicacion">
                <h2>Encuéntranos:</h2>
                <div class="ubicacion-card">
                    <div class="info-lista">
                        <div class="info-item">
                            <div class="info-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                                    <polyline points="9 22 9 12 15 12 15 22" />
                                </svg>
                            </div>
                            <div class="info-text">
                                <h4>Dirección:</h4>
                                <p>Av. Grau #156, Chiclayo - Perú</p>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10" />
                                    <polyline points="12 6 12 12 16 14" />
                                </svg>
                            </div>
                            <div class="info-text">
                                <h4>Horario:</h4>
                                <p>Lun-Dom: 6:00 AM - 10:30 PM</p>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <path
                                        d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z" />
                                </svg>
                            </div>
                            <div class="info-text">
                                <h4>Teléfono:</h4>
                                <p>074 618512 / 969 329 854</p>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                                </svg>
                            </div>
                            <div class="info-text">
                                <h4>WhatsApp:</h4>
                                <p>Escríbenos al privado para conocer más.</p>
                            </div>
                        </div>
                    </div>
                    <div class="mapa-container">
                        <div class="mapa-frame">
                            <iframe
                                src="https://www.google.com/maps/embed?pb=!1m24!1m12!1m3!1d247.6077853266815!2d-79.84990417957307!3d-6.803323642909022!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!4m9!3e6!4m3!3m2!1d-6.803271!2d-79.8499216!4m3!3m2!1d-6.8032696999999995!2d-79.8499223!5e0!3m2!1ses-419!2spe!4v1763996820134!5m2!1ses-419!2spe"
                                width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy"
                                referrerpolicy="no-referrer-when-downgrade"></iframe>
                        </div>
                        <a href="https://maps.app.goo.gl/kbdAPLCaNXfiYPW48" target="_blank" class="btn-como-llegar">Como llegar</a>
                    </div>
                </div>
            </section>
        </div>
    </main>

    
    <!-- MODAL DETALLE DE PRODUCTO (IMAGEN 5) -->
    <div class="modal-overlay product-detail-overlay" id="modalDetalleProducto">
        <div class="product-detail-modal">
            <button class="product-detail-close" onclick="cerrarModal('modalDetalleProducto')" aria-label="Cerrar modal">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>

            <div class="product-detail-body" style="padding-bottom: 0;">
                <!-- Breadcrumbs (Imagen 5: Inicio / Categoría / Subcategoría) -->
                <nav class="detail-modal-breadcrumb">
                    <a href="index.php?action=home_usuario">Inicio</a>
                    <span class="sep">/</span>
                    <a href="#momentosSection" id="detailBreadcrumbCat">Abarrotes & Despensa</a>
                    <span class="sep">/</span>
                    <span id="detailBreadcrumbGroup" style="color: #1e293b; font-weight: 600;">Arroz y Legumbres</span>
                </nav>

                <!-- Grid Principal: Galería (Izquierda) + Ficha (Derecha) -->
                <div class="detail-modal-grid-img5">
                    <!-- Columna Izquierda: Miniaturas verticales + Tarjeta con Imagen Principal -->
                    <div class="detail-gallery-container">
                        <div class="detail-thumbnails-strip" id="detailThumbnailsStrip">
                            <button type="button" class="thumb-strip-btn active" onclick="cambiarFotoDetalle(this, 0)">
                                <img id="thumbImg0" src="Images/logo.png" alt="Vista 1">
                            </button>
                            <button type="button" class="thumb-strip-btn" onclick="cambiarFotoDetalle(this, 1)">
                                <img id="thumbImg1" src="Images/logo.png" alt="Vista 2">
                            </button>
                            <button type="button" class="thumb-strip-btn" onclick="cambiarFotoDetalle(this, 2)">
                                <img id="thumbImg2" src="Images/logo.png" alt="Vista 3">
                            </button>
                            <button type="button" class="thumb-strip-btn" onclick="cambiarFotoDetalle(this, 3)">
                                <img id="thumbImg3" src="Images/logo.png" alt="Vista 4">
                            </button>
                        </div>
                        <div class="detail-main-img-card">
                            <span class="detail-badge-oferta-img" id="detailBadgeOfertaImg">OFERTA</span>
                            <button type="button" class="btn-detail-wishlist-heart" id="btnWishlistHeart" onclick="toggleWishlist(this)" title="Añadir a lista de deseos">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                                </svg>
                            </button>
                            <img id="detailProductImage" src="Images/logo.png" alt="Producto Principal">
                        </div>
                    </div>

                    <!-- Columna Derecha: Badges, Título, SKU/Marca, Tabs, Precio y Botón Agregar -->
                    <div class="detail-right-info">
                        <div class="detail-tags-top-row">
                            <span class="pill-oferta-detail" id="detailPillOferta">OFERTA</span>
                            <span class="pill-stock-detail">
                                <span class="dot-stock">●</span> Stock disponible
                            </span>
                        </div>

                        <h2 id="detailProductTitle" class="detail-prod-title-img5">Arroz Extra Premium Costeño</h2>
                        <div class="detail-prod-sku-marca">
                            SKU: <span id="detailProductSku" style="font-weight: 600;">001928</span> • Marca: <span id="detailProductMarca" style="font-weight: 600;">Costeño</span>
                        </div>

                        <div class="detail-tab-section-header">
                            <span class="detail-tab-title">Descripción</span>
                        </div>

                        <strong class="detail-desc-headline-bold" id="detailDescHeadline">Granos selectos para una mesa bien peruana</strong>
                        <p class="detail-desc-body-text" id="detailProductDescription">
                            Arroz extra de grano largo, seleccionado y pulido para lograr una cocción graneada y pareja. Ideal para el menú diario, arroz con pollo y todos esos platos que reúnen a la familia.
                        </p>

                        <div class="detail-pricing-row-img5">
                            <span class="price-main-bold-green" id="detailProductPrice">S/ 18.50</span>
                            <span class="price-old-striked" id="detailProductOldPrice">S/ 22.90</span>
                            <span class="badge-ahorras-green" id="detailProductSavings">Ahorras S/ 4.40</span>
                        </div>

                        <div class="detail-actions-bar-img5">
                            <div class="detail-qty-pill">
                                <button type="button" class="detail-qty-btn-icon" onclick="cambiarCantidadDetalle(-1)">−</button>
                                <span class="detail-qty-val" id="detailQuantity">1</span>
                                <button type="button" class="detail-qty-btn-icon" onclick="cambiarCantidadDetalle(1)">+</button>
                            </div>
                            <button type="button" class="btn-add-cart-img5" onclick="agregarDesdeDetalle(event)">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="9" cy="21" r="1"></circle>
                                    <circle cx="20" cy="21" r="1"></circle>
                                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                                </svg>
                                <span>Agregar al carrito</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Sección Inferior "También te puede gustar" (Imagen 5) -->
                <div class="detail-also-like-section">
                    <div class="also-like-titles">
                        <h3>También te puede gustar</h3>
                        <p>Completa tu despensa con los favoritos del barrio</p>
                    </div>
                    <div class="also-like-grid-4" id="alsoLikeGrid4">
                        <!-- Generado dinámicamente con los 4 productos favoritos del barrio -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL DE ALERTAS -->
    <div class="modal-overlay" id="alertModal">
        <div class="modal-content" style="text-align:center;max-width:380px;">
            <div id="alertIcon" style="font-size:50px;margin-bottom:15px;">⚠️</div>
            <h3 id="alertTitle" style="margin-bottom:10px;">Mensaje</h3>
            <p id="alertMessage" style="color:#666;margin-bottom:25px;"></p>
            <button class="btn-save" onclick="cerrarModal('alertModal')">Entendido</button>
        </div>
    </div>

    <!-- FOOTER OSCURO CANÓNICO (IMAGEN 2) -->
    <?php include __DIR__ . '/partials/footer.php'; ?>

    <!-- BANNER DE GESTIÓN DE COOKIES -->
    <div class="cookie-consent-banner" id="cookieConsentBanner" style="display: none;">
        <div class="cookie-banner-text">
            <h4>
                <span>🍪</span> Gestión de Cookies y Privacidad
            </h4>
            <p>
                Utilizamos <strong>cookies técnicas esenciales</strong> para garantizar la persistencia de tu sesión, la integridad del carrito de compras y la seguridad de tus transacciones. También empleamos cookies opcionales para recordar tus preferencias. Puedes aceptar todas, rechazarlas o configurar tus opciones.
            </p>
        </div>
        <div class="cookie-banner-actions">
            <button type="button" class="btn-cookie-accept" onclick="aceptarTodasCookies()">Aceptar todas</button>
            <button type="button" class="btn-cookie-reject" onclick="rechazarCookiesNoEsenciales()">Rechazar no esenciales</button>
            <button type="button" class="btn-cookie-config" onclick="abrirModalConfigCookies()">Configurar</button>
        </div>
    </div>

    <!-- MODAL DE CONFIGURACIÓN DE COOKIES -->
    <div class="modal-overlay" id="modalConfigCookies">
        <div class="modal-content modal-cookies-dialog">
            <div class="modal-header" style="border-bottom: 1px solid #f1f5f9; padding-bottom: 12px; margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between;">
                <h3 style="margin: 0; color: #0f172a; font-size: 1.2rem; display: flex; align-items: center; gap: 8px;">
                    <span>🍪</span> Configuración de Cookies
                </h3>
                <button type="button" class="btn-close-universal" onclick="cerrarModalConfigCookies()">&times;</button>
            </div>
            <div class="modal-body" style="padding: 0;">
                <p style="font-size: 0.88rem; color: #64748b; margin-bottom: 16px;">
                    Administra tus preferencias de cookies. Las cookies técnicas esenciales son obligatorias para el correcto funcionamiento de tu cuenta y carrito.
                </p>

                <!-- Opción 1: Técnicas -->
                <div class="cookie-opt-card">
                    <div class="cookie-opt-header">
                        <strong>Cookies Técnicas (Esenciales)</strong>
                        <label class="cookie-switch">
                            <input type="checkbox" checked disabled>
                            <span class="cookie-slider"></span>
                        </label>
                    </div>
                    <p>
                        Indispensables para la persistencia de sesión segura (<code>PHPSESSID</code>), retención de productos en tu carrito de compras, token CSRF y autenticación. <em>Siempre activas.</em>
                    </p>
                </div>

                <!-- Opción 2: Preferencias -->
                <div class="cookie-opt-card">
                    <div class="cookie-opt-header">
                        <strong>Cookies de Personalización y Preferencias</strong>
                        <label class="cookie-switch">
                            <input type="checkbox" id="chkCookiePreferencias" checked>
                            <span class="cookie-slider"></span>
                        </label>
                    </div>
                    <p>
                        Permiten recordar tus filtros favoritos, última categoría vista y dirección de entrega para agilizar tus compras.
                    </p>
                </div>

                <!-- Opción 3: Analíticas -->
                <div class="cookie-opt-card">
                    <div class="cookie-opt-header">
                        <strong>Cookies de Métricas y Rendimiento</strong>
                        <label class="cookie-switch">
                            <input type="checkbox" id="chkCookieAnaliticas" checked>
                            <span class="cookie-slider"></span>
                        </label>
                    </div>
                    <p>
                        Nos ayudan a medir el rendimiento de la tienda y determinar qué productos son los más buscados por los vecinos.
                    </p>
                </div>
            </div>
            <div class="modal-footer" style="margin-top: 20px; display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" onclick="cerrarModalConfigCookies()">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="guardarPreferenciasCookies()">Guardar preferencias</button>
            </div>
        </div>
    </div>

    <?php include 'Views/Carrito/partials/carrito_sidebar.php'; ?>    <script src="Scripts/app.js"></script>
    <script>window.productosBD = <?= json_encode($product_bd); ?>;</script>
    <script src="Scripts/carrito.js?v=<?php echo time(); ?>"></script>

    <script>
        $(document).ready(function () {
            const scrollAmount = 250;

            
            $(document).on('click', '#openSidebar', function () {
                $('#mobileSidebar, #sidebarOverlay').addClass('active open');
                $('body').css('overflow', 'hidden');
            });
            $(document).on('click', '#closeSidebar, #sidebarOverlay', function () {
                cerrarSidebar();
            });

            window.cerrarSidebar = function() {
                $('#mobileSidebar, #sidebarOverlay').removeClass('active open');
                $('body').css('overflow', '');
            };

            window.toggleSidebarAccordion = function(btn) {
                const parent = btn.closest('.sidebar-acc-item, .sidebar-subacc-item');
                if (!parent) return;
                parent.classList.toggle('open');
            };

            
            $('.filtro-pill').click(function () {
                $('.filtro-pill').removeClass('active');
                $(this).addClass('active');
                const target = $(this).data('target');
                if (target) {
                    $('.tab-content').removeClass('active-content');
                    $('#content-' + target).addClass('active-content');
                }
            });

            
            $('.btn-scroll-right').click(function () {
                const $grid = $(this).siblings('.metro-grid');
                if ($grid.length) {
                    const maxScroll = $grid[0].scrollWidth - $grid[0].clientWidth;
                    $grid.animate({ scrollLeft: $grid.scrollLeft() >= maxScroll - 5 ? 0 : "+=" + scrollAmount }, 300);
                }
            });
            $('.btn-scroll-left').click(function () {
                const $grid = $(this).siblings('.metro-grid');
                if ($grid.length) {
                    $grid.animate({ scrollLeft: $grid.scrollLeft() <= 0 ? $grid[0].scrollWidth : "-=" + scrollAmount }, 300);
                }
            });

            
            const $catGrid = $('#categoriaGrid');
            $(document).on('click', '.categoria-card', function () {
                const targetScroll = $catGrid.scrollLeft() + ($(this).offset().left - $catGrid.offset().left)
                    - ($catGrid.width() / 2) + ($(this).width() / 2);
                $catGrid.animate({ scrollLeft: targetScroll }, 500);
            });
        });

        
        function abrirModal(id) { document.getElementById(id).classList.add('active'); }
        function cerrarModal(id) { document.getElementById(id).classList.remove('active'); }
        function cerrarBusqueda() {
            document.getElementById('searchResultsSection').style.display = 'none';
            document.getElementById('mainContent').style.display = '';
        }


        let heroIndex = 0;

        const heroSlides = document.querySelectorAll('.hero-slide');
        const heroDots = document.querySelectorAll('.hero-dot');
        const heroSection = document.getElementById('heroSlider');

        function mostrarHeroSlide(index) {
            if (!heroSlides.length) return;

            heroSlides.forEach(slide => slide.classList.remove('active'));
            heroDots.forEach(dot => dot.classList.remove('active'));

            heroSlides[index].classList.add('active');

            if (heroDots[index]) {
                heroDots[index].classList.add('active');
            }

            const bg = heroSlides[index].dataset.bg;
            if (bg) {
                heroSection.style.background = bg;
            }
        }

        function siguienteHero() {
            heroIndex = (heroIndex + 1) % heroSlides.length;
            mostrarHeroSlide(heroIndex);
        }

        function anteriorHero() {
            heroIndex = (heroIndex - 1 + heroSlides.length) % heroSlides.length;
            mostrarHeroSlide(heroIndex);
        }

        document.querySelector('.hero-next')?.addEventListener('click', siguienteHero);
        document.querySelector('.hero-prev')?.addEventListener('click', anteriorHero);

        heroDots.forEach((dot, index) => {
            dot.addEventListener('click', function () {
                heroIndex = index;
                mostrarHeroSlide(heroIndex);
            });
        });

        setInterval(siguienteHero, 5000);

        mostrarHeroSlide(heroIndex);

        let modalState = {
            idProducto: null,
            precio: 0,
            cantidad: 1,
            stock: 0
        };

        window.cambiarFotoDetalle = function(btn, index) {
            document.querySelectorAll('#detailThumbnailsStrip .thumb-strip-btn').forEach(b => b.classList.remove('active'));
            if (btn) btn.classList.add('active');
            const img = btn.querySelector('img');
            const mainImg = document.getElementById('detailProductImage');
            if (img && mainImg) {
                mainImg.src = img.src;
            }
        };

        window.toggleWishlist = function(btn) {
            if (!btn) return;
            btn.classList.toggle('active');
            const isActive = btn.classList.contains('active');
            if (typeof window.mostrarToast === 'function') {
                window.mostrarToast(isActive ? 'Añadido a tus favoritos ❤️' : 'Eliminado de tus favoritos', isActive ? 'success' : 'info');
            }
        };

        function actualizarSubtotalDetalle() {
            const subtotal = modalState.precio * modalState.cantidad;
            const qtyEl = document.getElementById('detailQuantity');
            if (qtyEl) qtyEl.textContent = modalState.cantidad;
        }

        window.cambiarCantidadDetalle = function (delta) {
            let nuevaCant = modalState.cantidad + delta;
            if (nuevaCant < 1) nuevaCant = 1;
            if (modalState.stock > 0 && nuevaCant > modalState.stock) {
                if (typeof window.mostrarToast === 'function') {
                    window.mostrarToast(`Límite alcanzado. Solo hay ${modalState.stock} unidades disponibles de este producto.`, 'warning');
                }
                nuevaCant = modalState.stock;
            }
            modalState.cantidad = nuevaCant;
            actualizarSubtotalDetalle();
        };

        window.agregarDesdeDetalle = function (event) {
            if (!modalState.idProducto) return;
            const boton = event ? event.currentTarget : document.querySelector('.btn-add-cart-img5');
            if (window.agregarProducto) {
                window.agregarProducto(modalState.idProducto, boton, modalState.cantidad);
            } else {
                console.error("agregarProducto no está disponible");
            }
        };

        function abrirDetalleProducto(event, idProducto) {
            let id = idProducto;
            let evt = event;
            if (typeof event === 'number' || typeof event === 'string') {
                id = event;
                evt = window.event;
            }
            if (evt && (evt.target.closest('.btn-agregar-carrito') || evt.target.closest('.product-qty-pill') || evt.target.closest('.btn-mini-round-green') || evt.target.closest('.btn-delete-item'))) return;
            if (id) {
                window.location.href = 'index.php?action=detalle_producto&id=' + id;
            }
        }

        function renderAlsoLikeGrid(currentProdId) {
            const grid = document.getElementById('alsoLikeGrid4');
            if (!grid) return;
            grid.innerHTML = '';

            const otros = (typeof productosBD !== 'undefined' ? productosBD : [])
                .filter(p => parseInt(p.id_producto) !== parseInt(currentProdId))
                .slice(0, 4);

            if (!otros.length) {
                grid.innerHTML = '<p style="color:#94a3b8; grid-column:1/-1;">No hay más sugerencias disponibles.</p>';
                return;
            }

            otros.forEach(p => {
                const precio = parseFloat(p.precio).toFixed(2);
                let unidad = '1 und';
                const tLower = p.titulo.toLowerCase();
                if (p.unidad_medida === 'KG') unidad = '1 kg';
                else if (tLower.includes('aceite')) unidad = 'Botella 1 L';
                else if (tLower.includes('leche')) unidad = 'Pack 6 und';
                else if (tLower.includes('huevo')) unidad = 'Jaba 30 und';
                else if (tLower.includes('galleta')) unidad = 'Pack 8 und';

                grid.innerHTML += `
                    <div class="also-like-card-item">
                        <a href="index.php?action=detalle_producto&id=${p.id_producto}" class="product-card-link">
                            <div class="also-like-card-thumb">
                                <img src="${p.imagen || 'Images/logo.png'}" alt="${p.titulo}">
                            </div>
                            <h4 class="also-like-card-name" title="${p.titulo}">${p.titulo}</h4>
                            <div class="also-like-card-unit">${unidad}</div>
                        </a>
                        <div class="also-like-card-footer">
                            <span class="also-like-card-price">S/ ${precio}</span>
                            <button type="button" class="btn-mini-round-green" onclick="event.stopPropagation(); agregarProducto(${p.id_producto}, this, 1);" title="Agregar al carrito">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="9" cy="21" r="1"></circle>
                                    <circle cx="20" cy="21" r="1"></circle>
                                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                `;
            });
        }

        function obtenerSkuImg4(p) {
            if (p.sku && String(p.sku).trim() !== '') return String(p.sku).trim();
            if (p.codigo_sku && String(p.codigo_sku).trim() !== '') return String(p.codigo_sku).trim();
            return '00' + String(p.id_producto || 1000).padStart(4, '0');
        }

        function obtenerUnidadImg4(p) {
            const tit = (p.titulo || '').toLowerCase();
            if (p.unidad_medida === 'KG') {
                if (tit.includes('saco') || tit.includes('arroz')) return 'Saco 5 kg';
                return '1 kg';
            }
            if (tit.includes('aceite') || tit.includes('botella')) return 'Botella 1 L';
            if (tit.includes('pack') || tit.includes('leche') || tit.includes('galleta')) return 'Pack 6 und';
            if (tit.includes('jaba') || tit.includes('huevo')) return 'Jaba 30 und';
            if (tit.includes('papel')) return 'Pack 4 und';
            return '1 und';
        }

        function cambiarCardQty(btn, delta) {
            if (window.cambiarCardQty && window.cambiarCardQty !== cambiarCardQty) {
                return window.cambiarCardQty(btn, delta);
            }
            const card = btn.closest('.producto-card, .categoria-item-card, .mini-product-card, .also-like-card-item');
            const pill = btn.closest('.product-qty-pill') || (card ? card.querySelector('.product-qty-pill') : null);
            if (!pill) return;
            const qtySpan = pill.querySelector('.card-qty-val');
            if (!qtySpan) return;
            let qty = parseInt(qtySpan.textContent, 10) || 1;
            qty += delta;
            if (qty < 1) qty = 1;
            qtySpan.textContent = qty;
        }

        function agregarConQty(btn, idProducto) {
            const card = btn.closest('.producto-card, .categoria-item-card');
            let qty = 1;
            if (card) {
                delete card.dataset.userEditing;
                const qtySpan = card.querySelector('.card-qty-val');
                if (qtySpan) {
                    qty = parseInt(qtySpan.textContent, 10) || 1;
                }
            }
            if (typeof window.procesarAccionBotonCarrito === 'function') {
                window.procesarAccionBotonCarrito(idProducto, btn, qty);
            } else if (typeof window.agregarProducto === 'function') {
                window.agregarProducto(idProducto, btn, qty);
            }
        }

        function renderCardHtmlImg4(producto, esAdmin = false) {
            const tieneDesc = producto.precio_anterior && parseFloat(producto.precio_anterior) > parseFloat(producto.precio);
            const pctDesc = tieneDesc ? Math.round(((parseFloat(producto.precio_anterior) - parseFloat(producto.precio)) / parseFloat(producto.precio_anterior)) * 100) : 0;
            const badgeHtml = (tieneDesc && pctDesc > 0) ? `<span class="badge-descuento-porcentual">-${pctDesc}%</span>` : '';
            const sku = obtenerSkuImg4(producto);
            const unit = obtenerUnidadImg4(producto);
            const precio = parseFloat(producto.precio).toFixed(2);
            const precioAntHtml = tieneDesc ? `<span class="product-card-old-price">S/ ${parseFloat(producto.precio_anterior).toFixed(2)}</span>` : '';

            const adminDeleteBtn = esAdmin ? `
                <button type="button"
                    class="btn-delete-item"
                    onclick="event.stopPropagation(); confirmarEliminar('¿Eliminar producto?', 'index.php?action=eliminar_producto&id=${producto.id_producto}')"
                    style="display:inline-flex; align-items:center; justify-content:center; position:absolute; top:8px; right:8px; background:rgba(239,68,68,0.9); border:none; border-radius:50%; width:28px; height:28px; color:#fff; cursor:pointer; z-index:5;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                </button>
            ` : '';

            const idProdNum = Number(producto.id_producto);
            const enCart = Boolean(window.cartItemsMap && window.cartItemsMap[idProdNum] !== undefined && window.cartItemsMap[idProdNum] > 0);
            const qtyCart = enCart ? window.cartItemsMap[idProdNum] : 1;

            return `
                <div class="producto-card ${enCart ? 'is-in-cart' : ''}">
                    ${adminDeleteBtn}
                    <a href="index.php?action=detalle_producto&id=${producto.id_producto}" class="product-card-link">
                        <div class="producto-img">
                            ${badgeHtml}
                            <img src="${producto.imagen || 'Imagenes/producto-placeholder.png'}" alt="${producto.titulo}">
                        </div>

                        <span class="product-card-sku">SKU: ${sku}</span>
                        <h3 class="producto-titulo">${producto.titulo}</h3>
                        <span class="product-card-unit">${unit}</span>
                    </a>

                    <div class="product-card-price-row">
                        <span class="product-card-price">S/ ${precio}</span>
                        ${precioAntHtml}
                    </div>

                    <div class="product-card-actions-row ${enCart ? 'is-in-cart' : ''}">
                        <div class="product-qty-pill" style="display: ${enCart ? 'flex' : 'none'};" onclick="event.stopPropagation();">
                            <button type="button" class="btn-qty-minus" onclick="cambiarCardQty(this, -1)">-</button>
                            <span class="card-qty-val">${qtyCart}</span>
                            <button type="button" class="btn-qty-plus" onclick="cambiarCardQty(this, 1)">+</button>
                        </div>
                        <button type="button" class="btn-add-cart-pill btn-agregar-carrito ${enCart ? 'is-added' : ''}"
                            data-id-producto="${producto.id_producto}"
                            onclick="agregarConQty(this, ${producto.id_producto});">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="9" cy="21" r="1"></circle>
                                <circle cx="20" cy="21" r="1"></circle>
                                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                            </svg>
                            <span>${enCart ? 'Agregado' : 'Agregar'}</span>
                        </button>
                    </div>
                </div>
            `;
        }

        function abrirCategoriaDesdeSidebar(idCategoria) {
            window.location.href = 'index.php?action=ver_categoria&id=' + encodeURIComponent(idCategoria);
        }

        function toggleCategoriaDetalle(card) {
            const id = card.dataset.idCategoria;
            if (id) {
                window.location.href = 'index.php?action=ver_categoria&id=' + encodeURIComponent(id);
            }
        }

        function cerrarCategoriaDetalle() {
            // Ya no es necesario pues cada categoría tiene su vista dedicada
        }

        function mostrarGrupoDetalle(idGrupo) {
            window.location.href = 'index.php?action=ver_categoria&grupo=' + encodeURIComponent(idGrupo);
        }

        function cerrarGrupoDetalle() {
            // Vista dedicada
        }

        function moverCarrusel(id, direccion) {
            const carrusel = document.getElementById(id);
            if (!carrusel) return;
            const tarjeta = carrusel.querySelector('.producto-card, .categoria-card');
            if (!tarjeta) return;
            const anchoTarjeta = tarjeta.offsetWidth + 24;
            carrusel.scrollBy({
                left: direccion * anchoTarjeta * 3,
                behavior: 'smooth'
            });
        }
        function toggleNotificaciones(e) {
            if (e) {
                e.preventDefault();
                e.stopPropagation();
            }
            const dropdown = document.getElementById('notifDropdown');
            const overlay = document.getElementById('notifOverlay');
            if (!dropdown) return;

            const isActive = dropdown.classList.contains('active');
            if (isActive) {
                dropdown.classList.remove('active');
                if (overlay) overlay.classList.remove('active');
            } else {
                dropdown.classList.add('active');
                if (overlay) overlay.classList.add('active');
            }
        }

        function cerrarNotificaciones(e) {
            if (e) {
                e.preventDefault();
                e.stopPropagation();
            }
            const dropdown = document.getElementById('notifDropdown');
            const overlay = document.getElementById('notifOverlay');
            if (dropdown) dropdown.classList.remove('active');
            if (overlay) overlay.classList.remove('active');
        }

        // ==========================================
        // MODAL DE AUTENTICACIÓN (LOGIN / REGISTRO / GOOGLE) - ESTILO AMARANTO
        // ==========================================
        function abrirModalAuth(modo = 'login') {
            const modal = document.getElementById('modalAuthCliente');
            if (!modal) return;
            modal.style.display = 'flex';
            toggleModoAuth(modo);
        }

        function cerrarModalAuth() {
            const modal = document.getElementById('modalAuthCliente');
            if (modal) modal.style.display = 'none';
        }

        function toggleModoAuth(modo) {
            const formLogin = document.getElementById('formAuthLogin');
            const formReg = document.getElementById('formAuthRegistro');
            const titulo = document.getElementById('tituloAuthModal');
            if (modo === 'registro') {
                if (formLogin) formLogin.style.display = 'none';
                if (formReg) formReg.style.display = 'block';
                if (titulo) titulo.textContent = 'Crear Cuenta';
            } else {
                if (formLogin) formLogin.style.display = 'block';
                if (formReg) formReg.style.display = 'none';
                if (titulo) titulo.textContent = 'Iniciar Sesión';
            }
        }

        // Manejador Google Sign-In Credential Response
        function handleGoogleCredentialResponse(response) {
            if (!response.credential) {
                alert('No se pudo obtener la credencial de Google.');
                return;
            }
            fetch('index.php?action=login_google', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: 'credential=' + encodeURIComponent(response.credential)
            })
            .then(res => res.json())
            .then(data => {
                if (data.ok) {
                    window.location.reload();
                } else {
                    alert(data.mensaje || 'Error al autenticar con Google');
                }
            })
            .catch(() => alert('Error de conexión con el servidor'));
        }

        // ==========================================
        // FILTRADO DESDE BARRA DE NAVEGACIÓN TIER 2 (IMAGEN 1)
        // ==========================================
        function filtrarCategoriaNavBar(keyword) {
            if (!keyword) return;
            window.location.href = 'index.php?action=ver_categoria&nombre=' + encodeURIComponent(keyword);
        }

        // ==========================================
        // MODAL DE AYUDA Y POLÍTICAS DEL FOOTER (IMAGEN 2)
        // ==========================================
        function mostrarModalAyuda(tipo) {
            let titulo = 'Información';
            let contenido = '';

            if (tipo === 'faq') {
                titulo = 'Preguntas Frecuentes';
                contenido = `
                    <div style="font-size:0.95rem; line-height:1.6; color:#334155;">
                        <h4 style="color:#15803d; margin-top:0;">¿Cómo realizo un pedido?</h4>
                        <p>Solo agrega los productos deseados al carrito haciendo clic en "Agregar al carrito" o el icono de carrito, abre tu bolsa y procede a pagar mediante Yape, Plin, tarjeta o efectivo contraentrega.</p>
                        <h4 style="color:#15803d; margin-top:16px;">¿Cuál es el tiempo de entrega?</h4>
                        <p>El delivery express del barrio llega en un promedio de 25 a 45 minutos dentro de la zona de cobertura.</p>
                        <h4 style="color:#15803d; margin-top:16px;">¿Puedo cancelar o modificar mi orden?</h4>
                        <p>Sí, comunicándote directamente a nuestro WhatsApp de atención (+51 987 654 321) antes de que el motorizado salga en camino.</p>
                    </div>`;
            } else if (tipo === 'delivery') {
                titulo = 'Zonas de Delivery y Horarios';
                contenido = `
                    <div style="font-size:0.95rem; line-height:1.6; color:#334155;">
                        <p>Atendemos con reparto a domicilio en las siguientes áreas:</p>
                        <ul style="padding-left:20px; margin-bottom:12px;">
                            <li><strong>Zona 1 (Barrio Central / Cercado):</strong> Envío S/ 3.50 (Gratis en compras mayores a S/ 50.00).</li>
                            <li><strong>Zona 2 (Urbanizaciones aledañas):</strong> Envío S/ 5.00.</li>
                        </ul>
                        <p><strong>Horario de entregas:</strong> Lunes a Domingo de 7:00 AM a 10:00 PM ininterrumpido.</p>
                    </div>`;
            } else if (tipo === 'terminos') {
                titulo = 'Términos y Condiciones';
                contenido = `
                    <div style="font-size:0.92rem; line-height:1.6; color:#334155;">
                        <p>La Caserita Minimarket se compromete a entregar productos frescos y de calidad garantizada en cada despacho.</p>
                        <p>Los precios y descuentos mostrados en la plataforma web son válidos exclusivamente para compras procesadas a través de nuestro sitio web y sistema POS oficial.</p>
                        <p>Garantía de frescura: Si algún producto perecible no cumple tus expectativas, te lo cambiamos o reembolsamos de inmediato.</p>
                    </div>`;
            }

            const modalExistente = document.getElementById('modalAyudaDinamico');
            if (modalExistente) modalExistente.remove();

            const modalHtml = `
                <div class="modal active" id="modalAyudaDinamico" style="display:flex; z-index:99999;" onclick="if(event.target===this) this.remove();">
                    <div class="modal-content" style="max-width:550px; background:#fff; border-radius:18px; padding:26px; box-shadow:0 20px 50px rgba(0,0,0,0.2);">
                        <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #e2e8f0; padding-bottom:14px; margin-bottom:18px;">
                            <h3 style="margin:0; font-size:1.25rem; font-weight:700; color:#0f172a;">${titulo}</h3>
                            <button type="button" class="btn-close-universal" onclick="document.getElementById('modalAyudaDinamico').remove()">&times;</button>
                        </div>
                        <div class="modal-body">${contenido}</div>
                        <div style="margin-top:20px; text-align:right;">
                            <button type="button" class="btn btn-primary" onclick="document.getElementById('modalAyudaDinamico').remove()" style="background:#15803d; color:#fff; border:none; padding:9px 20px; border-radius:10px; font-weight:600; cursor:pointer;">Entendido</button>
                        </div>
                    </div>
                </div>
            `;
            document.body.insertAdjacentHTML('beforeend', modalHtml);
        }

        // ==========================================
        // GESTIÓN DE COOKIES (BANNER + TÉCNICAS ESENCIALES)
        // ==========================================
        const COOKIE_STORAGE_KEY = 'lacaserita_cookie_consent_v1';

        function verificarBannerCookies() {
            // Garantizar cookies técnicas esenciales activas (Sesión y Carrito)
            setCookie('cookie_tecnicas_activas', 'true', 365);
            setCookie('lacaserita_session_integrity', '1', 365);

            const consentimiento = localStorage.getItem(COOKIE_STORAGE_KEY);
            const banner = document.getElementById('cookieConsentBanner');
            if (!consentimiento && banner) {
                banner.style.display = 'flex';
            }
        }

        function setCookie(nombre, valor, dias) {
            let expires = '';
            if (dias) {
                const date = new Date();
                date.setTime(date.getTime() + (dias * 24 * 60 * 60 * 1000));
                expires = '; expires=' + date.toUTCString();
            }
            document.cookie = nombre + '=' + encodeURIComponent(valor) + expires + '; path=/; SameSite=Lax';
        }

        function aceptarTodasCookies() {
            const config = {
                tecnicas: true,
                preferencias: true,
                analiticas: true,
                fecha: new Date().toISOString()
            };
            localStorage.setItem(COOKIE_STORAGE_KEY, JSON.stringify(config));
            setCookie('cookie_consent_preferencias', '1', 365);
            setCookie('cookie_consent_analiticas', '1', 365);
            
            const banner = document.getElementById('cookieConsentBanner');
            if (banner) banner.style.display = 'none';

            if (typeof window.mostrarToast === 'function') {
                window.mostrarToast('Preferencias de cookies guardadas exitosamente.', 'success');
            }
        }

        function rechazarCookiesNoEsenciales() {
            const config = {
                tecnicas: true,
                preferencias: false,
                analiticas: false,
                fecha: new Date().toISOString()
            };
            localStorage.setItem(COOKIE_STORAGE_KEY, JSON.stringify(config));
            setCookie('cookie_consent_preferencias', '0', 365);
            setCookie('cookie_consent_analiticas', '0', 365);

            const banner = document.getElementById('cookieConsentBanner');
            if (banner) banner.style.display = 'none';

            if (typeof window.mostrarToast === 'function') {
                window.mostrarToast('Se mantendrán activas únicamente las cookies técnicas esenciales.', 'info');
            }
        }

        function abrirModalConfigCookies() {
            const modal = document.getElementById('modalConfigCookies');
            if (!modal) return;
            
            const consentGuardado = localStorage.getItem(COOKIE_STORAGE_KEY);
            if (consentGuardado) {
                try {
                    const c = JSON.parse(consentGuardado);
                    const chkPref = document.getElementById('chkCookiePreferencias');
                    const chkAna = document.getElementById('chkCookieAnaliticas');
                    if (chkPref) chkPref.checked = !!c.preferencias;
                    if (chkAna) chkAna.checked = !!c.analiticas;
                } catch(e) {}
            }
            modal.style.display = 'flex';
        }

        function cerrarModalConfigCookies() {
            const modal = document.getElementById('modalConfigCookies');
            if (modal) modal.style.display = 'none';
        }

        function guardarPreferenciasCookies() {
            const chkPref = document.getElementById('chkCookiePreferencias');
            const chkAna = document.getElementById('chkCookieAnaliticas');

            const prefActivo = chkPref ? chkPref.checked : true;
            const anaActivo = chkAna ? chkAna.checked : false;

            const config = {
                tecnicas: true,
                preferencias: prefActivo,
                analiticas: anaActivo,
                fecha: new Date().toISOString()
            };

            localStorage.setItem(COOKIE_STORAGE_KEY, JSON.stringify(config));
            setCookie('cookie_consent_preferencias', prefActivo ? '1' : '0', 365);
            setCookie('cookie_consent_analiticas', anaActivo ? '1' : '0', 365);

            cerrarModalConfigCookies();
            const banner = document.getElementById('cookieConsentBanner');
            if (banner) banner.style.display = 'none';

            if (typeof window.mostrarToast === 'function') {
                window.mostrarToast('Tus preferencias de cookies han sido actualizadas.', 'success');
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            verificarBannerCookies();
            <?php if (isset($_GET['open_auth']) && $_GET['open_auth'] == '1' && !$estaAutenticado): ?>
            if (typeof abrirModalAuth === 'function') {
                abrirModalAuth('login');
            }
            <?php endif; ?>
        });
    </script>



    <!-- MODAL DE AUTENTICACIÓN ELEGANTE (AMARANTO STYLE REDESIGN) -->
    <?php include 'Views/Login/auth_modal.php'; ?>
</body>

</html>
