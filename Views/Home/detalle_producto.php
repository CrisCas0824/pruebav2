<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$usuario = $_SESSION['usuario_nombre'] ?? null;
$esAdmin = (isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'admin');
$rutaInicio = $esAdmin ? 'index.php?action=home_admin' : 'index.php?action=home_usuario';
$marca = $producto['marca'] ?? '';

// Cálculos de descuento
$tieneDesc = !empty($producto['precio_anterior']) && (float)$producto['precio_anterior'] > (float)$producto['precio'];
$pctDesc = $tieneDesc ? round((((float)$producto['precio_anterior'] - (float)$producto['precio']) / (float)$producto['precio_anterior']) * 100) : 0;
$ahorro = $tieneDesc ? number_format((float)$producto['precio_anterior'] - (float)$producto['precio'], 2) : '0.00';

$skuFormateado = !empty($producto['codigo_sku']) ? $producto['codigo_sku'] : '00' . str_pad((int)$producto['id_producto'], 4, '0', STR_PAD_LEFT);
$unidad = !empty($producto['unidad_medida']) ? $producto['unidad_medida'] : 'UND';

// Mapeo de productos en carrito para estado inicial UI
$cartUserIdDetalle = $_SESSION['usuario_id'] ?? 'invitado';
$carritoSesionDetalle = $_SESSION['carritos'][$cartUserIdDetalle] ?? [];
$carritoCantidadesDetalle = [];
foreach ($carritoSesionDetalle as $cItem) {
    $carritoCantidadesDetalle[(int)$cItem['id_producto']] = (int)$cItem['cantidad'];
}

// Estado inicial del botón principal
$idProdActual = (int)$producto['id_producto'];
$enCarritoDetalle = isset($carritoCantidadesDetalle[$idProdActual]) && $carritoCantidadesDetalle[$idProdActual] > 0;
$cantInicialDetalle = $enCarritoDetalle ? $carritoCantidadesDetalle[$idProdActual] : 1;

function formatearSkuDetalle($p) {
    if (!empty($p['codigo_sku'])) return $p['codigo_sku'];
    return '00' . str_pad((int)($p['id_producto'] ?? 1000), 4, '0', STR_PAD_LEFT);
}

function formatearUnidadMedidaDetalle($p) {
    $tit = mb_strtolower($p['titulo'] ?? '');
    if (($p['unidad_medida'] ?? '') === 'KG') {
        if (str_contains($tit, 'saco') || str_contains($tit, 'arroz')) return 'Saco 5 kg';
        return '1 kg';
    }
    if (str_contains($tit, 'aceite') || str_contains($tit, 'botella')) return 'Botella 1 L';
    if (str_contains($tit, 'pack') || str_contains($tit, 'leche') || str_contains($tit, 'galleta')) return 'Pack 6 und';
    if (str_contains($tit, 'jaba') || str_contains($tit, 'huevo')) return 'Jaba 30 und';
    if (str_contains($tit, 'papel')) return 'Pack 4 und';
    return '1 und';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($producto['titulo']); ?> - La Caserita</title>
    <meta name="description" content="<?= htmlspecialchars(mb_substr($producto['descripcion'] ?? $producto['titulo'], 0, 150)); ?>">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="Styles/global.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="Styles/styles-home.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="Styles/styles-carrito.css?v=<?= time(); ?>">
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="Styles/Vistas/Home/detalle_producto.css?v=<?= time(); ?>">
    <link rel="icon" type="image/png" href="Images/logo.png">
</head>
<body class="<?= in_array($_SESSION['usuario_rol'] ?? '', ['admin', 'cajero', 'repartidor'], true) ? 'role-staff' : ''; ?>">
    <?php include __DIR__ . '/partials/mobile_sidebar.php'; ?>

    <!-- ENCABEZADO PRINCIPAL CANÓNICO (TIER 1 + TIER 2) -->
    <?php include __DIR__ . '/partials/header_principal.php'; ?>

    <!-- CUERPO PRINCIPAL DEL PRODUCTO -->
    <main class="detalle-page-wrapper" style="max-width: 1400px; margin: 12px auto 60px auto; padding: 0 20px;">
        <!-- Breadcrumbs completos con nivel de Jerarquía: Inicio / Macrocategoría / Categoría / Subcategoría / Producto -->
        <?php 
            $macroNom = trim($producto['momento_nombre'] ?? '');
            $macroId  = (int)($producto['id_momento'] ?? 0);

            $grupoNom = trim($producto['grupo_nombre'] ?? '');
            $grupoId  = (int)($producto['id_grupo'] ?? 0);

            $subNom   = trim(!empty($producto['nombre_categoria']) ? $producto['nombre_categoria'] : ($producto['categoria_nombre'] ?? ''));
            $subId    = (int)($producto['id_categoria'] ?? 0);

            $prodTit  = trim($producto['titulo'] ?? '');
        ?>
        <nav class="detalle-page-breadcrumb" style="display:flex; align-items:center; gap:8px; margin-bottom:20px; font-size:0.9rem; color:#64748b; font-weight:500; flex-wrap:wrap;">
            <a href="<?= htmlspecialchars($rutaInicio); ?>" style="color:#64748b; text-decoration:none;" onmouseover="this.style.color='#15803d'" onmouseout="this.style.color='#64748b'">Inicio</a>
            
            <?php if ($macroNom !== ''): ?>
                <span class="sep" style="color:#cbd5e1;">/</span>
                <a href="index.php?action=ver_categoria&id_momento=<?= $macroId; ?>&nombre=<?= urlencode($macroNom); ?>" style="color:#64748b; text-decoration:none;" onmouseover="this.style.color='#15803d'" onmouseout="this.style.color='#64748b'"><?= htmlspecialchars($macroNom); ?></a>
            <?php endif; ?>

            <?php if ($grupoNom !== '' && $grupoNom !== $macroNom): ?>
                <span class="sep" style="color:#cbd5e1;">/</span>
                <a href="index.php?action=ver_categoria&id_grupo=<?= $grupoId; ?>&nombre=<?= urlencode($grupoNom); ?>" style="color:#64748b; text-decoration:none;" onmouseover="this.style.color='#15803d'" onmouseout="this.style.color='#64748b'"><?= htmlspecialchars($grupoNom); ?></a>
            <?php endif; ?>

            <?php if ($subNom !== '' && $subNom !== $grupoNom && $subNom !== $macroNom): ?>
                <span class="sep" style="color:#cbd5e1;">/</span>
                <a href="index.php?action=ver_categoria&id=<?= $subId; ?>&nombre=<?= urlencode($subNom); ?>" style="color:#64748b; text-decoration:none;" onmouseover="this.style.color='#15803d'" onmouseout="this.style.color='#64748b'"><?= htmlspecialchars($subNom); ?></a>
            <?php endif; ?>

            <span class="sep" style="color:#cbd5e1;">/</span>
            <strong style="color:#0f172a; font-weight:700;"><?= htmlspecialchars($prodTit); ?></strong>
        </nav>

        <!-- Ficha Principal del Producto -->
        <div class="detalle-page-card">
            <div class="detalle-grid-main">
                <!-- Columna Galería -->
                <div class="detalle-gallery-col">
                    <div class="detalle-thumbs-strip">
                        <button type="button" class="detalle-thumb-btn active" onclick="cambiarFotoDetallePage(this)">
                            <img src="<?= htmlspecialchars($producto['imagen']); ?>" alt="Foto 1">
                        </button>
                        <button type="button" class="detalle-thumb-btn" onclick="cambiarFotoDetallePage(this)">
                            <img src="<?= htmlspecialchars($producto['imagen']); ?>" alt="Foto 2">
                        </button>
                    </div>
                    <div class="detalle-main-box">
                        <?php if ($tieneDesc && $pctDesc > 0): ?>
                            <span class="detalle-badge-desc">-<?= $pctDesc; ?>% OFERTA</span>
                        <?php endif; ?>
                        <img id="mainDetailImg" src="<?= htmlspecialchars($producto['imagen']); ?>" alt="<?= htmlspecialchars($producto['titulo']); ?>">
                    </div>
                </div>

                <!-- Columna Información -->
                <div class="detalle-info-col">
                    <div class="detalle-tags-row">
                        <?php if ($tieneDesc && $pctDesc > 0): ?>
                            <span class="pill-tag-oferta">OFERTA ESPECIAL</span>
                        <?php endif; ?>
                        <span class="pill-tag-stock">● Stock Disponible</span>
                    </div>

                    <h1 class="detalle-prod-title"><?= htmlspecialchars($producto['titulo']); ?></h1>
                    
                    <div class="detalle-prod-meta">
                        SKU: <strong><?= htmlspecialchars($skuFormateado); ?></strong> • Unidad: <strong><?= htmlspecialchars($unidad); ?></strong> • Marca: <strong><?= htmlspecialchars($marca); ?></strong>
                    </div>

                    <div>
                        <span class="detalle-section-heading">Descripción del producto</span>
                        <p class="detalle-desc-text">
                            <?= nl2br(htmlspecialchars($producto['descripcion'] ?: 'Producto fresco y seleccionado de máxima calidad para tu despensa y bienestar familiar. Garantía de frescura y atención de La Caserita.')); ?>
                        </p>
                    </div>

                    <!-- Fila de Precios -->
                    <div class="detalle-pricing-container">
                        <span class="detalle-current-price">S/ <?= number_format((float)$producto['precio'], 2); ?></span>
                        <?php if ($tieneDesc): ?>
                            <span class="detalle-old-price">S/ <?= number_format((float)$producto['precio_anterior'], 2); ?></span>
                            <span class="detalle-savings-badge">Ahorras S/ <?= $ahorro; ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Fila de Compra / Agregar al Carrito -->
                    <div class="detalle-buy-row" id="detalleBuyRow">
                        <!-- Selector qty: oculto por default, visible si ya está en carrito -->
                        <div class="detalle-page-qty-pill" id="detallePill" style="display: <?= $enCarritoDetalle ? 'flex' : 'none'; ?>;">
                            <button type="button" class="detalle-page-qty-btn" id="detalleQtyMinus">−</button>
                            <span class="detalle-page-qty-val" id="pageQtyVal"><?= $cantInicialDetalle; ?></span>
                            <button type="button" class="detalle-page-qty-btn" id="detalleQtyPlus">+</button>
                        </div>

                        <button type="button"
                            class="detalle-page-btn-add btn-agregar-carrito <?= $enCarritoDetalle ? 'is-added' : ''; ?>"
                            id="detalleBtnAdd"
                            data-id-producto="<?= $idProdActual; ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="9" cy="21" r="1"></circle>
                                <circle cx="20" cy="21" r="1"></circle>
                                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                            </svg>
                            <span><?= $enCarritoDetalle ? 'Agregado' : 'Agregar al carrito'; ?></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- PRODUCTOS RELACIONADOS ("También te puede gustar") -->
        <?php if (!empty($relacionados)): ?>
            <section class="detalle-relacionados-wrap">
                <div class="detalle-relacionados-head">
                    <h3>También te puede gustar</h3>
                    <p>Completa tu despensa con los favoritos del barrio</p>
                </div>
                <div class="detalle-relacionados-grid">
                    <?php foreach ($relacionados as $rel): 
                        $relDesc = !empty($rel['precio_anterior']) && (float)$rel['precio_anterior'] > (float)$rel['precio'];
                        $relPct = $relDesc ? round((((float)$rel['precio_anterior'] - (float)$rel['precio']) / (float)$rel['precio_anterior']) * 100) : 0;
                        $idRel = (int)$rel['id_producto'];
                        $enCartRel = isset($carritoCantidadesDetalle[$idRel]) && $carritoCantidadesDetalle[$idRel] > 0;
                        $cantCartRel = $enCartRel ? $carritoCantidadesDetalle[$idRel] : 1;
                    ?>
                        <div class="producto-card <?= $enCartRel ? 'is-in-cart' : ''; ?>">
                            <a href="index.php?action=detalle_producto&id=<?= $idRel; ?>" class="product-card-link">
                                <div class="producto-img">
                                    <?php if ($relDesc && $relPct > 0): ?>
                                        <span class="badge-descuento-porcentual">-<?= $relPct; ?>%</span>
                                    <?php endif; ?>
                                    <img src="<?= htmlspecialchars($rel['imagen']); ?>" alt="<?= htmlspecialchars($rel['titulo']); ?>" loading="lazy" onerror="this.src='Images/logo.png'">
                                </div>
                                <span class="product-card-sku">SKU: <?= htmlspecialchars(formatearSkuDetalle($rel)); ?></span>
                                <h4 class="producto-titulo"><?= htmlspecialchars($rel['titulo']); ?></h4>
                            </a>
                            <span class="product-card-unit"><?= htmlspecialchars(formatearUnidadMedidaDetalle($rel)); ?></span>

                            <div class="product-card-price-row">
                                <span class="product-card-price">S/ <?= number_format((float)$rel['precio'], 2); ?></span>
                                <?php if ($relDesc): ?>
                                    <span class="product-card-old-price">S/ <?= number_format((float)$rel['precio_anterior'], 2); ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="product-card-actions-row <?= $enCartRel ? 'is-in-cart' : ''; ?>">
                                <div class="product-qty-pill" style="display: <?= $enCartRel ? 'flex' : 'none'; ?>;" onclick="event.stopPropagation();">
                                    <button type="button" class="btn-qty-minus" onclick="cambiarCardQty(this, -1)">-</button>
                                    <span class="card-qty-val"><?= $cantCartRel; ?></span>
                                    <button type="button" class="btn-qty-plus" onclick="cambiarCardQty(this, 1)">+</button>
                                </div>
                                <button type="button" class="btn-add-cart-pill btn-agregar-carrito <?= $enCartRel ? 'is-added' : ''; ?>"
                                    data-id-producto="<?= $idRel; ?>"
                                    onclick="agregarConQty(this, <?= $idRel; ?>);">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="9" cy="21" r="1"></circle>
                                        <circle cx="20" cy="21" r="1"></circle>
                                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                                    </svg>
                                    <span><?= $enCartRel ? 'Agregado' : 'Agregar'; ?></span>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    </main>

    <!-- FOOTER OSCURO CANÓNICO (IMAGEN 2) -->
    <?php include __DIR__ . '/partials/footer.php'; ?>

    <!-- Modales y Sidebars -->
    <?php include 'Views/Carrito/partials/carrito_sidebar.php'; ?>
    <?php include 'Views/Login/auth_modal.php'; ?>

    <script>
        window.productosBD = <?= json_encode($product_bd ?? []); ?>;
        const productosBD = window.productosBD;
    </script>
    <script src="Scripts/carrito.js?v=<?= time(); ?>"></script>
    <script>
        // =====================================================================
        // UI DINÁMICA DEL BOTÓN EN LA PÁGINA DE DETALLE DE PRODUCTO
        // =====================================================================
        (function() {
            const idProducto = <?= $idProdActual; ?>;
            const pill  = document.getElementById('detallePill');
            const btn   = document.getElementById('detalleBtnAdd');
            const qtyEl = document.getElementById('pageQtyVal');

            // Galería
            function cambiarFotoDetallePage(thumbBtn) {
                document.querySelectorAll('.detalle-thumb-btn').forEach(b => b.classList.remove('active'));
                thumbBtn.classList.add('active');
                const img  = thumbBtn.querySelector('img');
                const main = document.getElementById('mainDetailImg');
                if (img && main) main.src = img.src;
            }
            window.cambiarFotoDetallePage = cambiarFotoDetallePage;

            // Helpers de estado UI
            function setEstadoAgregado(cant) {
                if (pill) pill.style.display = 'flex';
                if (qtyEl) qtyEl.textContent = cant;
                if (btn) {
                    btn.classList.add('is-added');
                    const sp = btn.querySelector('span');
                    if (sp) sp.textContent = 'Agregado';
                }
            }

            function setEstadoAgregar() {
                if (pill) pill.style.display = 'none';
                if (qtyEl) qtyEl.textContent = 1;
                if (btn) {
                    btn.classList.remove('is-added');
                    const sp = btn.querySelector('span');
                    if (sp) sp.textContent = 'Agregar al carrito';
                }
            }

            // Botón principal: Agregar / (ya en carrito = no hacer nada, los -/+ manejan)
            if (btn) {
                btn.addEventListener('click', async function() {
                    if (btn.classList.contains('is-added')) return; // ya está en el carrito
                    if (btn.disabled) return;

                    const cantActual = parseInt(qtyEl ? qtyEl.textContent : '1', 10) || 1;
                    btn.disabled = true;
                    const sp = btn.querySelector('span');
                    if (sp) sp.textContent = 'Agregando...';

                    try {
                        if (typeof window.agregarProducto === 'function') {
                            await window.agregarProducto(idProducto, btn, cantActual);
                            setEstadoAgregado(cantActual);
                        }
                    } catch(e) {
                        setEstadoAgregar();
                    } finally {
                        btn.disabled = false;
                    }
                });
            }

            // Botones +/- del selector en detalle
            const btnMinus = document.getElementById('detalleQtyMinus');
            const btnPlus  = document.getElementById('detalleQtyPlus');

            let debounceDetalle = null;

            async function syncDetalleQty(newQty) {
                if (debounceDetalle) clearTimeout(debounceDetalle);
                debounceDetalle = setTimeout(async function() {
                    try {
                        if (newQty <= 0) {
                            if (typeof window.eliminarProducto === 'function') {
                                await window.eliminarProducto(idProducto);
                            } else {
                                // fallback manual
                                const fd = new FormData();
                                fd.append('id_producto', idProducto);
                                await fetch('index.php?action=eliminar_carrito', {
                                    method: 'POST',
                                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                                    body: fd
                                });
                            }
                        } else {
                            if (typeof window.actualizarCantidad === 'function') {
                                await window.actualizarCantidad(idProducto, newQty, null, '', true);
                            }
                        }
                    } catch(e) {
                        console.warn('Error sincronizando carrito desde detalle:', e);
                    }
                }, 300);
            }

            if (btnMinus) {
                btnMinus.addEventListener('click', async function() {
                    let cant = parseInt(qtyEl ? qtyEl.textContent : '1', 10) || 1;
                    cant--;
                    if (cant <= 0) {
                        setEstadoAgregar();
                        // Actualizar mapa global
                        if (window.cartItemsMap) delete window.cartItemsMap[idProducto];
                        const total = typeof obtenerContadorHeader === 'function' ? obtenerContadorHeader() : 0;
                        if (typeof window.actualizarBurbujaHeader === 'function') window.actualizarBurbujaHeader(Math.max(0, total - 1));
                        await syncDetalleQty(0);
                    } else {
                        if (qtyEl) qtyEl.textContent = cant;
                        if (window.cartItemsMap) window.cartItemsMap[idProducto] = cant;
                        const total = typeof obtenerContadorHeader === 'function' ? obtenerContadorHeader() : 0;
                        if (typeof window.actualizarBurbujaHeader === 'function') window.actualizarBurbujaHeader(Math.max(0, total - 1));
                        await syncDetalleQty(cant);
                    }
                });
            }

            if (btnPlus) {
                btnPlus.addEventListener('click', async function() {
                    let cant = parseInt(qtyEl ? qtyEl.textContent : '1', 10) || 1;
                    cant++;
                    if (qtyEl) qtyEl.textContent = cant;
                    if (window.cartItemsMap) window.cartItemsMap[idProducto] = cant;
                    if (typeof window.actualizarBurbujaHeader === 'function') {
                        const total = parseInt((document.getElementById('cartCountHeader') || {}).textContent || '0', 10);
                        window.actualizarBurbujaHeader(total + 1);
                    }
                    setEstadoAgregado(cant);
                    await syncDetalleQty(cant);
                });
            }

            // Sincronizar estado con el carrito cargado (por si hay datos en sesión)
            document.addEventListener('carritoSincronizado', function(e) {
                const items = e.detail && e.detail.items ? e.detail.items : [];
                const item = items.find(i => Number(i.id_producto) === idProducto);
                if (item && Number(item.cantidad) > 0) {
                    setEstadoAgregado(Number(item.cantidad));
                } else {
                    setEstadoAgregar();
                }
            });
        })();

        // Sidebar móvil jQuery
        $(document).on('click', '#openSidebar', function () {
            $('#mobileSidebar, #sidebarOverlay').addClass('active open');
        });
        $(document).on('click', '#closeSidebar, #sidebarOverlay', function () {
            $('#mobileSidebar, #sidebarOverlay').removeClass('active open');
        });
    </script>
</body>
</html>
