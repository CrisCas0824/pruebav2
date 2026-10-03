<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$rolUsuario = $_SESSION['usuario_rol'] ?? 'cliente';
$esAdmin = ($rolUsuario === 'admin');
$rutaInicio = $esAdmin ? 'index.php?action=home_admin' : 'index.php?action=home_usuario';
$estaAutenticado = isset($_SESSION['usuario_id']);

// Mapa de productos en carrito para estado inicial UI
$cartUserIdCat = $_SESSION['usuario_id'] ?? 'invitado';
$carritoSesionCat = $_SESSION['carritos'][$cartUserIdCat] ?? [];
$carritoCantidadesCat = [];
foreach ($carritoSesionCat as $cItem) {
    $carritoCantidadesCat[(int)$cItem['id_producto']] = (int)$cItem['cantidad'];
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
        $id = $p['id_producto'] ?? 0;
        return str_pad($id, 6, '0', STR_PAD_LEFT);
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($tituloCategoria); ?> - Tienda La Caserita</title>
    <link rel="stylesheet" href="Styles/global.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="Styles/styles-home.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="Styles/styles-carrito.css?v=<?= time(); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="icon" type="image/png" href="Images/logo.png">
</head>
<body class="<?= in_array($_SESSION['usuario_rol'] ?? '', ['admin', 'cajero', 'repartidor'], true) ? 'role-staff' : ''; ?>">
    <?php include __DIR__ . '/partials/mobile_sidebar.php'; ?>

    <!-- Header Principal Canónico (Tier 1 + Tier 2) -->
    <?php include __DIR__ . '/partials/header_principal.php'; ?>

    <main class="categoria-vista-main">
        <?php
        require_once __DIR__ . '/partials/navigation_data.php';

        if (!function_exists('obtenerTagsProductoCat')) {
            function obtenerTagsProductoCat($producto) {
                $tags = [];
                $tituloLower = mb_strtolower($producto['titulo'] ?? '');
                $grupoLower = mb_strtolower($producto['grupo_nombre'] ?? '');
                $tieneOferta = !empty($producto['precio_anterior']) && (float)$producto['precio_anterior'] > (float)$producto['precio'];

                if (!empty($producto['es_sugerencia'])) $tags[] = 'Populares';
                if ($tieneOferta) $tags[] = 'Ofertas';
                if (!empty($producto['grupo_nombre'])) $tags[] = trim($producto['grupo_nombre']);

                $keywordMap = [
                    'Leches' => ['leche'],
                    'Yogures' => ['yogur', 'yogurt'],
                    'Mantequillas' => ['mantequilla', 'margarina'],
                    'Quesos' => ['queso'],
                    'Jamones y Embutidos' => ['jamon', 'jamón', 'embutido', 'salchicha', 'tocino', 'jamonada'],
                    'Huevos' => ['huevo'],
                    'Panes y Tostadas' => ['pan', 'tostad', 'bimbo', 'panecillo'],
                    'Frutas' => ['fruta', 'tomate', 'manzana', 'platano', 'plátano', 'naranja', 'mandarina', 'uva', 'fresa', 'papaya', 'piña'],
                    'Verduras' => ['verdura', 'cebolla', 'zanahoria', 'limon', 'limón', 'palta', 'lechuga', 'apio', 'zapallo'],
                    'Pollo' => ['pollo', 'gallina'],
                    'Res' => ['res', 'carne'],
                    'Cerdo' => ['cerdo', 'chancho'],
                    'Pescados' => ['pescado', 'bonito', 'jurel', 'trucha', 'merluza'],
                    'Mariscos' => ['marisco', 'langostino', 'concha', 'calamar'],
                    'Arroz' => ['arroz'],
                    'Menestras y Legumbres' => ['menestra', 'lenteja', 'frijol', 'frejol', 'garbanzo', 'pallar'],
                    'Aceites' => ['aceite', 'oliva'],
                    'Salsas y Especias' => ['salsa', 'sillon', 'sillao', 'vinagre', 'especia', 'tuco', 'mayonesa', 'ketchup', 'mostaza'],
                    'Fideos' => ['fideo', 'tallarin', 'tallarín', 'spaghetti', 'pasta'],
                    'Conservas de Pescado' => ['conserva', 'atun', 'atún', 'caballa', 'sardina'],
                    'Gaseosas' => ['gaseosa', 'coca', 'inca', 'pepsi', 'sprite', 'fanta', 'krug'],
                    'Aguas' => ['agua', 'san luis', 'san mateo', 'cielo'],
                    'Frugos y Jugos' => ['frugos', 'jugo', 'pulp', 'citrus', 'nectar', 'néctar'],
                    'Energizantes' => ['energiz', 'red bull', 'monster', 'volt', 'sporade'],
                    'Papas y Piques' => ['papa', 'lay', 'dorito', 'cuate', 'chizito', 'snack', 'piqueo'],
                    'Galletas' => ['galleta', 'oreo', 'casino', 'soda', 'morochas', 'charada', 'ritmo'],
                    'Chocolates' => ['chocolat', 'sublime', 'triangulo', 'triángulo', 'canchita', 'doña pepa'],
                    'Detergentes' => ['detergente', 'ariel', 'ace', 'opal', 'marsella', 'bolivar'],
                    'Lavavajillas' => ['lavavajilla', 'ayudin', 'ayudín', 'sapolio'],
                    'Desinfectantes' => ['desinfectante', 'poett', 'clorox', 'lejia', 'lejía', 'pinesol'],
                    'Jabones' => ['jabon', 'jabón', 'camay', 'rexona', 'dove', 'moncler', 'lux'],
                    'Shampoo y Acondicionador' => ['shampoo', 'champú', 'h&s', 'head', 'pantene', 'sedal', 'clear', 'savital'],
                    'Higiene Oral' => ['colgate', 'oral', 'dental', 'crema dental', 'cepillo', 'kolynos']
                ];

                foreach ($keywordMap as $tag => $keywords) {
                    foreach ($keywords as $kw) {
                        if (strpos($tituloLower, $kw) !== false || strpos($grupoLower, $kw) !== false) {
                            $tags[] = $tag;
                            break;
                        }
                    }
                }

                return implode('|', array_unique($tags));
            }
        }

        // Determinar subcategorías para el sidebar según la categoría actual
        $subcatsFiltroSidebar = [];
        $catNormalizada = mb_strtolower(trim($tituloCategoria ?? ''));

        foreach ($menuMultinivel as $grpNav) {
            $tituloGrp = mb_strtolower(trim($grpNav['titulo']));
            if ($tituloGrp === $catNormalizada || strpos($catNormalizada, $tituloGrp) !== false || strpos($tituloGrp, $catNormalizada) !== false) {
                foreach ($grpNav['subcategorias'] as $scNav) {
                    if (!empty($scNav['subitems'])) {
                        foreach ($scNav['subitems'] as $sLeaf) {
                            $subcatsFiltroSidebar[] = $sLeaf['nombre'];
                        }
                    } else {
                        $subcatsFiltroSidebar[] = $scNav['nombre'];
                    }
                }
                break;
            }
        }

        if (!in_array('Populares', $subcatsFiltroSidebar)) $subcatsFiltroSidebar[] = 'Populares';
        if (!in_array('Ofertas', $subcatsFiltroSidebar)) $subcatsFiltroSidebar[] = 'Ofertas';

        if (!empty($product_bd)) {
            foreach ($product_bd as $pDb) {
                $gNom = trim($pDb['grupo_nombre'] ?? '');
                if ($gNom !== '' && $gNom !== 'Otros' && !in_array($gNom, $subcatsFiltroSidebar)) {
                    $subcatsFiltroSidebar[] = $gNom;
                }
            }
        }

        // Calcular cantidades por filtro
        $subcatCounts = [];
        if (!empty($product_bd)) {
            foreach ($subcatsFiltroSidebar as $scName) {
                $count = 0;
                foreach ($product_bd as $pItem) {
                    $pTags = explode('|', obtenerTagsProductoCat($pItem));
                    if (in_array($scName, $pTags, true)) {
                        $count++;
                    }
                }
                $subcatCounts[$scName] = $count;
            }
        }

        if (!function_exists('renderProductCardCat')) {
            function renderProductCardCat($producto, $carritoCantidadesCat) {
                $tieneDesc = !empty($producto['precio_anterior']) && (float)$producto['precio_anterior'] > (float)$producto['precio'];
                $pctDesc = $tieneDesc ? round((((float)$producto['precio_anterior'] - (float)$producto['precio']) / (float)$producto['precio_anterior']) * 100) : 0;
                $idProd = (int) $producto['id_producto'];
                $enCart = isset($carritoCantidadesCat[$idProd]) && $carritoCantidadesCat[$idProd] > 0;
                $cantCart = $enCart ? $carritoCantidadesCat[$idProd] : 1;
                $precioFormatted = number_format((float) $producto['precio'], 2);
                $precioAntFormatted = number_format((float) $producto['precio_anterior'], 2);
                $tagsAttr = htmlspecialchars(obtenerTagsProductoCat($producto));
                
                ob_start();
                ?>
                <div class="producto-card <?= $enCart ? 'is-in-cart' : ''; ?>" 
                     data-titulo="<?= htmlspecialchars(mb_strtolower($producto['titulo'])); ?>" 
                     data-precio="<?= (float)$producto['precio']; ?>" 
                     data-descuento="<?= $pctDesc; ?>" 
                     data-sugerencia="<?= !empty($producto['es_sugerencia']) ? '1' : '0'; ?>"
                     data-grupo="<?= htmlspecialchars(trim($producto['grupo_nombre'] ?? 'Otros')); ?>"
                     data-tags="<?= $tagsAttr; ?>">
                    <a href="index.php?action=detalle_producto&id=<?= $idProd; ?>" class="product-card-link">
                        <div class="producto-img">
                            <?php if ($tieneDesc && $pctDesc > 0): ?>
                                <span class="badge-descuento-porcentual">-<?= $pctDesc; ?>%</span>
                            <?php endif; ?>
                            <img src="<?= htmlspecialchars($producto['imagen']); ?>" alt="<?= htmlspecialchars($producto['titulo']); ?>" loading="lazy" onerror="this.onerror=null;this.src='Imagenes/producto-placeholder.png';">
                        </div>
                        <span class="product-card-sku">SKU: <?= htmlspecialchars(formatearSkuImg4($producto)); ?></span>
                        <h3 class="producto-titulo" style="font-size:14px; margin: 8px 0;"><?= htmlspecialchars($producto['titulo']); ?></h3>
                        <span class="product-card-unit"><?= htmlspecialchars(formatearUnidadMedidaImg4($producto)); ?></span>
                    </a>
                    <div class="product-card-price-row">
                        <span class="product-card-price">S/ <?= $precioFormatted; ?></span>
                        <?php if ($tieneDesc): ?>
                            <span class="product-card-old-price">S/ <?= $precioAntFormatted; ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="product-card-actions-row <?= $enCart ? 'is-in-cart' : ''; ?>">
                        <div class="product-qty-pill" style="display: <?= $enCart ? 'flex' : 'none'; ?>;" onclick="event.stopPropagation();">
                            <button type="button" class="btn-qty-minus" onclick="cambiarCardQty(this, -1)">-</button>
                            <span class="card-qty-val"><?= $cantCart; ?></span>
                            <button type="button" class="btn-qty-plus" onclick="cambiarCardQty(this, 1)">+</button>
                        </div>
                        <button type="button" class="btn-add-cart-pill btn-agregar-carrito <?= $enCart ? 'is-added' : ''; ?>" data-id-producto="<?= $idProd; ?>" onclick="agregarConQty(this, <?= $idProd; ?>);">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle>
                                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                            </svg>
                            <span><?= $enCart ? 'Agregado' : 'Agregar'; ?></span>
                        </button>
                    </div>
                </div>
                <?php
                return ob_get_clean();
            }
        }
        ?>

        <div style="max-width:1400px; margin:0 auto; padding:0 20px;">
            <!-- 1. Breadcrumbs Dinámicos Multinivel: Inicio / Macrocategoría / Categoría / Subcategoría -->
            <?php 
                $primerP     = !empty($product_bd) ? reset($product_bd) : null;
                $macroCatNav = trim($primerP['momento_nombre'] ?? '');
                $grupoCatNav = trim($primerP['grupo_nombre'] ?? '');
                $subCatNav   = trim($primerP['categoria_nombre'] ?? '');

                $macroIdNav  = (int)($primerP['id_momento'] ?? 0);
                $grupoIdNav  = (int)($primerP['id_grupo'] ?? 0);
                $subIdNav    = (int)($primerP['id_categoria'] ?? 0);

                // Construimos la lista de breadcrumbs respetando la jerarquía estricta
                $breadcrumbs = [];
                $breadcrumbs[] = [
                    'label' => 'Inicio',
                    'url'   => $rutaInicio,
                    'is_current' => false
                ];

                $isOfertas = (strtolower($tituloCategoria) === 'ofertas');

                if ($isOfertas) {
                    $breadcrumbs[] = [
                        'label' => 'Ofertas',
                        'url'   => null,
                        'is_current' => true
                    ];
                } else {
                    $esMacro = (!empty($macroCatNav) && strtolower($tituloCategoria) === strtolower($macroCatNav));
                    $esGrupo = (!empty($grupoCatNav) && strtolower($tituloCategoria) === strtolower($grupoCatNav));

                    if ($esMacro) {
                        // Nivel 2: Macrocategoría
                        $breadcrumbs[] = [
                            'label' => $tituloCategoria,
                            'url'   => null,
                            'is_current' => true
                        ];
                    } elseif ($esGrupo) {
                        // Nivel 3: Categoría / Grupo
                        if (!empty($macroCatNav)) {
                            $breadcrumbs[] = [
                                'label' => $macroCatNav,
                                'url'   => 'index.php?action=ver_categoria&id_momento='.$macroIdNav.'&nombre='.urlencode($macroCatNav),
                                'is_current' => false
                            ];
                        }
                        $breadcrumbs[] = [
                            'label' => $tituloCategoria,
                            'url'   => null,
                            'is_current' => true
                        ];
                    } else {
                        // Nivel 4: Subcategoría
                        if (!empty($macroCatNav)) {
                            $breadcrumbs[] = [
                                'label' => $macroCatNav,
                                'url'   => 'index.php?action=ver_categoria&id_momento='.$macroIdNav.'&nombre='.urlencode($macroCatNav),
                                'is_current' => false
                            ];
                        }
                        if (!empty($grupoCatNav) && strtolower($grupoCatNav) !== strtolower($macroCatNav)) {
                            $breadcrumbs[] = [
                                'label' => $grupoCatNav,
                                'url'   => 'index.php?action=ver_categoria&id_grupo='.$grupoIdNav.'&nombre='.urlencode($grupoCatNav),
                                'is_current' => false
                            ];
                        }
                        $breadcrumbs[] = [
                            'label' => $tituloCategoria,
                            'url'   => null,
                            'is_current' => true
                        ];
                    }
                }
            ?>
            <!-- Breadcrumb Limpio Sin Tarjeta ni Botón Volver (Estilo Imagen 2) -->
            <nav class="categoria-breadcrumb-uncontained" style="display:flex; align-items:center; gap:8px; margin: 0 0 16px 0; padding: 4px 0; font-size:0.9rem; color:#64748b; font-weight:500; flex-wrap:wrap;">
                <?php foreach ($breadcrumbs as $idx => $bc): ?>
                    <?php if ($idx > 0): ?>
                        <span class="breadcrumb-sep" style="color:#cbd5e1; font-weight:400;">/</span>
                    <?php endif; ?>

                    <?php if ($bc['is_current']): ?>
                        <strong class="breadcrumb-current" style="color:#0f172a; font-weight:700;"><?= htmlspecialchars($bc['label']); ?></strong>
                    <?php else: ?>
                        <a href="<?= htmlspecialchars($bc['url']); ?>" style="color:#64748b; text-decoration:none;" onmouseover="this.style.color='#15803d'" onmouseout="this.style.color='#64748b'"><?= htmlspecialchars($bc['label']); ?></a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </nav>

            <!-- 2. Layout Principal de 2 Columnas (Filtros a la izquierda, Productos a la derecha AL MISMO NIVEL EXACTO) -->
            <div class="categoria-layout" style="display:flex; gap:20px; align-items: flex-start; margin-top: 0;">
                <!-- SIDEBAR FILTROS FIJO A LA IZQUIERDA -->
                <aside class="categoria-filtros-sidebar" style="width: 250px; flex-shrink:0; position: sticky; top: calc(var(--main-header-height, 114px) + 12px); height: max-content; background: #fff; padding: 20px; border-radius: 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.02); border: 1px solid #f0f0f0; z-index: 90; margin-top: 0; align-self: flex-start;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 22px;">
                        <h3 style="font-size: 18px; font-weight: 800; color: #1e293b; margin: 0; line-height: 1;">Filtros</h3>
                        <button type="button" class="btn-limpiar-desktop" id="btnLimpiarDesktop" style="background:none; border:none; color:#15803d; font-size:12px; font-weight:700; cursor:pointer; padding:0; text-decoration:none;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">
                            Limpiar
                        </button>
                    </div>
                    
                    <!-- 1. Subcategorías -->
                    <div class="filtro-seccion" style="margin-bottom: 25px;">
                        <h4 style="font-size: 14px; font-weight: 700; color: #334155; margin-bottom: 14px;">Categorías</h4>
                        <ul style="list-style: none; padding: 0; margin: 0; display:flex; flex-direction: column; gap: 10px;">
                            <?php 
                            if (!empty($subcatsFiltroSidebar)) {
                                foreach ($subcatsFiltroSidebar as $scName) {
                                    $cant = $subcatCounts[$scName] ?? 0;
                                    echo "<li style='display:flex; justify-content:space-between; align-items:center; font-size:13px; color:#475569;'>
                                            <label style='display:flex; align-items:center; gap:9px; cursor:pointer;'>
                                                <input type='checkbox' class='filtro-subcat-chk' value='".htmlspecialchars($scName)."' style='accent-color:#16a34a; width:16px; height:16px; cursor:pointer;'>
                                                <span style='font-weight:500;'>".htmlspecialchars($scName)."</span>
                                            </label>
                                            <span style='background:#dcfce7; color:#16a34a; font-weight:700; padding:2px 8px; border-radius:20px; font-size:11px;'>{$cant}</span>
                                          </li>";
                                }
                            } else {
                                echo "<p style='font-size:12px; color:#94a3b8;'>No hay categorías.</p>";
                            }
                            ?>
                        </ul>
                    </div>
                    
                    <!-- 2. Rango de Precios -->
                    <div class="filtro-seccion" style="border-top: 1px solid #f1f5f9; padding-top: 22px;">
                        <h4 style="font-size: 14px; font-weight: 700; color: #334155; margin-bottom: 12px;">Rango de Precios</h4>
                        <div style="font-size:13px; color:#64748b; font-weight:600; margin-bottom:10px;" id="precioRangoTopTxt">S/ 0.00 - S/ 100.00+</div>
                        <input type="range" min="0" max="100" value="100" class="filtro-rango-slider" id="precioMaximoSlider" style="width:100%; accent-color:#16a34a; cursor:pointer;">
                        <div style="display:flex; justify-content:space-between; font-size:11px; color:#94a3b8; margin-top:6px; font-weight:500;">
                            <span>Min</span>
                            <span id="precioMaxValueTxt">S/ 100.00</span>
                        </div>
                    </div>
                </aside>

                <!-- TARJETA UNIFICADA DE PRODUCTOS Y ORDENAMIENTO (MISMO NIVEL DE FILTROS) -->
                <div class="catalog-products-wrapper" style="flex:1; min-width:0; background: #fff; padding: 20px; border-radius: 16px; border: 1px solid #f0f0f0; box-shadow: 0 4px 15px rgba(0,0,0,0.02);">
                    <!-- Barra de Ordenamiento Interna -->
                    <div class="catalog-top-bar" style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 14px; margin-bottom: 18px; border-bottom: 1px solid #f1f5f9; background: transparent; box-shadow: none; border: none; border-radius: 0;">
                        <!-- Botón abrir filtros en Móvil -->
                        <button type="button" class="btn-open-mobile-filtros" id="btnOpenMobileFiltros" title="Filtrar productos">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="4" y1="21" x2="4" y2="14"></line>
                                <line x1="4" y1="10" x2="4" y2="3"></line>
                                <line x1="12" y1="21" x2="12" y2="12"></line>
                                <line x1="12" y1="8" x2="12" y2="3"></line>
                                <line x1="20" y1="21" x2="20" y2="16"></line>
                                <line x1="20" y1="12" x2="20" y2="3"></line>
                                <line x1="1" y1="14" x2="7" y2="14"></line>
                                <line x1="9" y1="8" x2="15" y2="8"></line>
                                <line x1="17" y1="16" x2="23" y2="16"></line>
                            </svg>
                            <span class="mobile-filter-badge" id="mobileFilterBadge" style="display:none;">0</span>
                        </button>

                        <!-- Control Ordenar Por Estilizado (Desktop) -->
                        <div class="sort-control-group desktop-only-sort" style="display: flex; align-items: center; gap: 12px;">
                            <label for="selectOrdenCatalogo" style="font-size: 13px; font-weight: 600; color: #64748b;">Ordenar por:</label>
                            <div class="select-custom-wrapper">
                                <select id="selectOrdenCatalogo" class="select-orden-custom">
                                    <option value="mas_vendidos">Más vendidos / Recomendados</option>
                                    <option value="menor_precio">Menor precio</option>
                                    <option value="mayor_descuento">Mayor descuento</option>
                                </select>
                            </div>
                        </div>

                        <div class="catalog-pagination-info" style="display: flex; align-items: center; gap: 12px; font-size: 13px; color: #64748b; font-weight: 600;">
                            <span id="categoriaProductCount"><?= count($product_bd); ?> productos</span>
                        </div>
                    </div>

                    <!-- Grid de Productos (4 Tarjetas por fila) -->
                    <?php if (!empty($product_bd)): ?>
                        <div class="productos-catalogo-grid" id="productosCatalogoGrid">
                            <?php foreach ($product_bd as $p) { echo renderProductCardCat($p, $carritoCantidadesCat); } ?>
                        </div>
                    <?php else: ?>
                        <div class="categoria-empty-box">
                            <div class="categoria-empty-icon">🛒</div>
                            <h3>No se encontraron productos</h3>
                            <p>Actualmente no hay productos disponibles en la sección <strong><?= htmlspecialchars($tituloCategoria); ?></strong>.</p>
                            <a href="<?= htmlspecialchars($rutaInicio); ?>" class="btn-volver-catalogo" style="margin-top: 14px;">
                                ← Explorar todo el catálogo
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- DRAWER / MODAL DE FILTROS EN MÓVIL (IMAGEN 3) -->
    <div class="mobile-filtros-overlay" id="mobileFiltrosOverlay">
        <div class="mobile-filtros-drawer">
            <div class="mobile-filtros-header">
                <h3>Filtrar por:</h3>
                <button type="button" class="btn-close-mobile-filtros" id="btnCloseMobileFiltros" aria-label="Cerrar">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>

            <div class="mobile-filtros-body">
                <!-- Ordenar por (En Mobile está en la parte superior - IMAGEN 3) -->
                <div class="mobile-sort-row">
                    <label for="selectOrdenCatalogoMobile" style="font-size: 13px; font-weight: 600; color: #64748b;">Ordenar por:</label>
                    <div class="select-custom-wrapper" style="flex:1;">
                        <select id="selectOrdenCatalogoMobile" class="select-orden-custom" style="width:100%;">
                            <option value="mas_vendidos">Más vendidos / Recomendados</option>
                            <option value="menor_precio">Menor precio</option>
                            <option value="mayor_descuento">Mayor descuento</option>
                        </select>
                    </div>
                </div>

                <div class="mobile-filtros-section-title">Filtros</div>

                <!-- Categorías -->
                <div class="filtro-seccion" style="margin-bottom:25px;">
                    <h4 style="font-size:14px; font-weight:700; color:#334155; margin-bottom:12px;">Categorías</h4>
                    <ul style="list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:10px;">
                        <?php 
                        if (!empty($subcatsFiltroSidebar)) {
                            foreach ($subcatsFiltroSidebar as $scName) {
                                $cant = $subcatCounts[$scName] ?? 0;
                                echo "<li style='display:flex; justify-content:space-between; align-items:center; font-size:13px; color:#475569;'>
                                        <label style='display:flex; align-items:center; gap:8px; cursor:pointer;'>
                                            <input type='checkbox' class='filtro-subcat-chk mobile-subcat-chk' value='".htmlspecialchars($scName)."' style='accent-color:#16a34a; width:16px; height:16px;'>
                                            <span>".htmlspecialchars($scName)."</span>
                                        </label>
                                        <span style='background:#dcfce7; color:#16a34a; font-weight:700; padding:2px 8px; border-radius:20px; font-size:11px;'>{$cant}</span>
                                      </li>";
                            }
                        }
                        ?>
                    </ul>
                </div>

                <!-- Rango de Precios -->
                <div class="filtro-seccion" style="border-top:1px solid #f1f5f9; padding-top:20px;">
                    <h4 style="font-size:14px; font-weight:700; color:#334155; margin-bottom:12px;">Rango de Precios</h4>
                    <div style="font-size:13px; color:#64748b; font-weight:600; margin-bottom:10px;" id="precioRangoTopTxtMobile">S/ 0.00 - S/ 100.00+</div>
                    <input type="range" min="0" max="100" value="100" class="filtro-rango-slider" id="precioMaximoSliderMobile" style="width:100%; accent-color:#16a34a; cursor:pointer;">
                    <div style="display:flex; justify-content:space-between; font-size:11px; color:#94a3b8; margin-top:6px; font-weight:500;">
                        <span>Min</span>
                        <span id="precioMaxValueTxtMobile">S/ 100.00</span>
                    </div>
                </div>
            </div>

            <div class="mobile-filtros-footer">
                <button type="button" class="btn-limpiar-filtro" id="btnLimpiarFiltrosMobile">Limpiar Filtro</button>
                <button type="button" class="btn-aplicar-filtro" id="btnAplicarFiltrosMobile">Aplicar</button>
            </div>
        </div>
    </div>

    <!-- Modal Detalle Producto -->
    <div class="modal-overlay product-detail-overlay" id="modalDetalleProducto">
        <div class="product-detail-modal">
            <button class="product-detail-close" onclick="cerrarModal('modalDetalleProducto')">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
            <div class="product-detail-body">
                <div class="product-detail-main">
                    <div class="product-detail-image">
                        <img id="detailProductImage" src="Imagenes/producto-placeholder.png" alt="Producto">
                    </div>
                    <div class="product-detail-info">
                        <h1 id="detailProductTitle" class="product-detail-title">Nombre del Producto</h1>
                        <div class="product-detail-pricing">
                            <span id="detailProductPrice" class="product-detail-price">S/ 0.00</span>
                            <span id="detailProductOldPrice" class="product-detail-old-price"></span>
                            <span id="detailProductDiscount" class="product-detail-discount"></span>
                        </div>
                        <p id="detailProductUnit" class="product-detail-unit"></p>
                        <div class="product-detail-stock-option">
                            <label>Si mi producto está agotado</label>
                            <select id="detailStockOption">
                                <option value="no_contactar">Si no contesto, elijan por mí</option>
                                <option value="contactar">Contactarme antes de reemplazar</option>
                                <option value="no_reemplazar">No reemplazar</option>
                            </select>
                        </div>
                        <div class="product-detail-description">
                            <h3>Información del producto</h3>
                            <p id="detailProductDescription">Descripción del producto...</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="product-detail-footer">
                <div class="detail-quantity-control">
                    <button class="qty-btn" onclick="cambiarCantidadDetalle(-1)">−</button>
                    <span id="detailQuantity">1</span>
                    <button class="qty-btn" onclick="cambiarCantidadDetalle(1)">+</button>
                </div>
                <button class="detail-add-btn" onclick="agregarDesdeDetalle()">
                    <span>Agregar</span>
                    <span id="detailSubtotal" class="detail-subtotal">Subtotal: S/ 0.00</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Carrito Sidebar -->
    <?php include 'Views/Carrito/partials/carrito_sidebar.php'; ?>

    <!-- FOOTER OSCURO CANÓNICO (IMAGEN 2) -->
    <?php include __DIR__ . '/partials/footer.php'; ?>

    <script>window.productosBD = <?= json_encode($todosProductos ?? []); ?>; const productosBD = window.productosBD;</script>
    <script src="Scripts/carrito.js?v=<?= time(); ?>"></script>
    <script src="Scripts/Vistas/Home/categoria_productos.js?v=<?= time(); ?>"></script>
</body>
</html>
