<?php
$productos = $productos ?? [];
$nombre = htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Cajero', ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Productos - La Caserita</title>
    <link rel="stylesheet" href="Styles/global.css">
    <link rel="stylesheet" href="Styles/staff-panel.css">
    <link rel="stylesheet" href="Styles/Vistas/Cajero/catalogo_productos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="Scripts/scanner-camara.js" defer></script>
</head>
<body class="staff-app cashier-app">
    <?php $paginaPanelStaff = 'productos'; include __DIR__ . '/../Staff/partials/staff_sidebar.php'; ?>
    <div class="staff-main">
        <header class="staff-topbar">
            <div class="staff-topbar-left">
                <button type="button" class="staff-menu-toggle hide-on-desktop" id="staffMenuToggle" aria-label="Abrir menú">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
                </button>
                <span class="staff-topbar-title">Productos</span>
            </div>
            <div class="staff-topbar-actions">
                <a href="index.php?action=home_usuario" class="topbar-store-btn">Volver a Tienda</a>
            </div>
        </header>
        <main class="staff-content catalog-content">
            <div class="catalog-heading">
                <div>
                    <h1>Productos</h1>
                    <p>Consulta productos, precios, existencias y códigos.</p>
                </div>
                <div class="catalog-tools">
                    <label class="catalog-search">
                        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                        <input type="search" id="cashierProductSearch" placeholder="Nombre, SKU o código de barras" autocomplete="off" aria-label="Buscar productos">
                        <button type="button" aria-label="Escanear código de barras" title="Escanear con el celular" onclick="UniversalCameraScanner.open({ targetInputId: 'cashierProductSearch', title: 'Buscar producto' })">
                            <i class="fa-solid fa-barcode" aria-hidden="true"></i>
                        </button>
                    </label>
                </div>
            </div>
            <p class="catalog-count" id="catalogCount" aria-live="polite"></p>
            <section class="catalog-grid" id="catalogGrid" aria-label="Catálogo de productos">
                <?php foreach ($productos as $producto): ?>
                    <?php
                        $productoNombre = (string)($producto['nombre'] ?: ($producto['titulo'] ?? 'Producto'));
                        $sku = (string)($producto['codigo'] ?? '');
                        $barcode = (string)($producto['codigo_barras'] ?? '');
                        $imagen = (string)($producto['imagen'] ?? 'Images/logo.png');
                        $stock = (float)($producto['stock'] ?? 0);
                    ?>
                    <article class="catalog-item" data-catalog-product data-search="<?= htmlspecialchars(strtolower($productoNombre . ' ' . $sku . ' ' . $barcode . ' ' . ($producto['marca'] ?? '')), ENT_QUOTES, 'UTF-8') ?>">
                        <img src="<?= htmlspecialchars($imagen, ENT_QUOTES, 'UTF-8') ?>" alt="" loading="lazy" onerror="this.onerror=null;this.src='Images/logo.png';">
                        <div>
                            <h2><?= htmlspecialchars($productoNombre, ENT_QUOTES, 'UTF-8') ?></h2>
                            <div class="catalog-codes">
                                <span>SKU: <?= htmlspecialchars($sku ?: 'Sin SKU', ENT_QUOTES, 'UTF-8') ?></span>
                                <span>Código: <?= htmlspecialchars($barcode ?: 'Sin código', ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                        </div>
                        <div class="catalog-item-footer">
                            <span class="catalog-price">S/ <?= number_format((float)($producto['precio'] ?? 0), 2) ?></span>
                            <span class="catalog-stock">Stock: <?= number_format($stock, 2) ?> <?= htmlspecialchars(strtolower($producto['unidad_medida'] ?? 'und.'), ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                    </article>
                <?php endforeach; ?>
                <div class="catalog-empty" id="catalogEmpty" hidden>No hay productos que coincidan con la búsqueda.</div>
            </section>
        </main>
    </div>
    <script src="Scripts/catalogo_productos.js" defer></script>
</body>
</html>
