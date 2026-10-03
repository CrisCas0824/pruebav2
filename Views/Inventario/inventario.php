<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($nombre)) {
    $nombre = htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Administrador');
}

if (!isset($inventario_bd)) {
    $inventario_bd = [];
}

if (!isset($historial_reposicion)) {
    $historial_reposicion = [];
}
$flash_success = $flash_success ?? null;
$flash_error = $flash_error ?? null;

require_once 'Core/ajaxHelper.php';
?>
<?php if (!AjaxHelper::isAjax()): ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventario — La Caserita</title>
    <link rel="stylesheet" href="Styles/global.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="Styles/styles-home.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="Styles/styles-users-admin.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="Styles/admin-spa.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="Scripts/admin-spa.js?v=<?php echo time(); ?>" defer></script>
    <!-- Lector remoto desde el celular vinculado -->
    <script src="Scripts/scanner-camara.js"></script>
    <link rel="icon" type="image/png" href="Images/logo.png">
</head>
<body>
    <div class="admin-layout">
        <?php include __DIR__ . '/../Admin/partials/admin_sidebar.php'; ?>

        <div class="admin-main">
            <header class="admin-topbar">
                <div class="admin-topbar-left">
                    <button type="button" class="admin-hamburger-btn hide-on-desktop" id="openAdminSidebar" aria-label="Abrir Menú">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="3" y1="6" x2="21" y2="6"></line>
                            <line x1="3" y1="12" x2="21" y2="12"></line>
                            <line x1="3" y1="18" x2="21" y2="18"></line>
                        </svg>
                    </button>
                    <div class="admin-topbar-brand">
                        <span class="brand-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
                        </span>
                        <span class="brand-title">Control de Inventario & Stock</span>
                    </div>
                </div>
                <div class="admin-topbar-actions">
                    <?php include __DIR__ . '/../Admin/partials/admin_topbar_actions.php'; ?>
                </div>
            </header>

            <div id="admin-container">
<?php endif; ?>

<link rel="stylesheet" href="Styles/Vistas/Inventario/inventario.css?v=<?= time(); ?>">
<link rel="stylesheet" href="Styles/Vistas/Inventario/inventario_panel.css?v=<?= time(); ?>">

    <main class="main-content inventario-main container-admin" style="padding-top: 15px; padding-bottom: 50px;">
        <?php
            $countActivos = 0;
            $countInactivos = 0;
            $countDisponibles = 0;
            $countCriticos = 0;
            $countMasVendidos = 0;
            $countMenosVendidos = 0;
                $countConSku = 0;
                $countConCodigoBarras = 0;
            if(!empty($inventario_bd)){
                foreach($inventario_bd as $item){
                    if (!empty(trim((string)($item['codigo'] ?? '')))) $countConSku++;
                    if (!empty(trim((string)($item['codigo_barras'] ?? '')))) $countConCodigoBarras++;
                    $isAct = (int)($item['activo'] ?? 1) === 1;
                    if ($isAct) {
                        $countActivos++;
                    } else {
                        $countInactivos++;
                    }

                    if((float)$item['stock_actual'] <= (float)$item['stock_minimo']){
                        $countCriticos++;
                    }else{
                        $countDisponibles++;
                    }
                    if ((int)($item['ventas_count'] ?? 0) > 0) {
                        $countMasVendidos++;
                    } else {
                        $countMenosVendidos++;
                    }
                }
            }
        ?>

        <div class="admin-header">
            <div class="admin-header-titles">
                <h1>Gestión de Inventario</h1>
                <p>Controla el stock actual, niveles mínimos, ubicación y reposición de productos.</p>
            </div>
            <div class="admin-header-actions" style="display: flex; gap: 10px; flex-wrap: wrap;">
                <button type="button" class="btn btn-primary" onclick="abrirModalCrearProducto()" style="background: #15803d; color: #ffffff; border: none; font-weight: 700; padding: 10px 20px; border-radius: 8px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 4px 12px rgba(21,128,61,0.25);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                    <span>Nuevo Producto</span>
                </button>
            </div>
        </div>

        <?php if ($flash_success || $flash_error): ?>
            <script>
                (function() {
                    const mensaje = <?= json_encode($flash_error ?: $flash_success, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
                    const tipo = <?= json_encode($flash_error ? 'error' : 'success'); ?>;
                    function mostrarNotificacionInventario() {
                        if (typeof SPA !== 'undefined' && typeof SPA.toast === 'function') {
                            if (typeof SPA.createToastContainer === 'function') SPA.createToastContainer();
                            SPA.toast(tipo, tipo === 'error' ? 'Error' : 'Éxito', mensaje, 4000);
                            return;
                        }

                        let container = document.getElementById('toast-container');
                        if (!container) {
                            container = document.createElement('div');
                            container.id = 'toast-container';
                            document.body.appendChild(container);
                        }
                        const toast = document.createElement('div');
                        toast.className = 'custom-toast toast-' + tipo + ' toast-show';
                        toast.setAttribute('role', tipo === 'error' ? 'alert' : 'status');
                        toast.style.cssText = 'position:fixed;right:20px;bottom:20px;z-index:12001;display:flex;align-items:center;gap:12px;width:min(420px,calc(100vw - 32px));padding:14px 18px;background:#fff;border-radius:14px;box-shadow:0 12px 32px rgba(15,23,42,.2);border:1px solid ' + (tipo === 'error' ? '#fecaca' : '#bbf7d0') + ';color:#0f172a;';
                        const icon = document.createElement('span');
                        icon.className = 'toast-icon';
                        icon.innerHTML = tipo === 'error'
                            ? '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="m15 9-6 6m0-6 6 6"></path></svg>'
                            : '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#15803d" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><path d="m9 11 3 3L22 4"></path></svg>';
                        const text = document.createElement('span');
                        text.textContent = mensaje;
                        const close = document.createElement('button');
                        close.type = 'button';
                        close.className = 'toast-close';
                        close.setAttribute('aria-label', 'Cerrar notificación');
                        close.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m18 6-12 12M6 6l12 12"></path></svg>';
                        const progress = document.createElement('div');
                        progress.className = 'toast-progress';
                        progress.style.animationDuration = '4000ms';
                        toast.append(icon, text, close, progress);
                        container.appendChild(toast);
                        const cerrarToast = function() {
                            toast.classList.remove('toast-show');
                            toast.classList.add('toast-hide');
                            setTimeout(function() { toast.remove(); }, 300);
                        };
                        close.addEventListener('click', cerrarToast);
                        setTimeout(cerrarToast, 4000);
                    }
                    if (document.readyState === 'loading') {
                        document.addEventListener('DOMContentLoaded', mostrarNotificacionInventario, { once: true });
                    } else {
                        mostrarNotificacionInventario();
                    }
                })();
            </script>
        <?php endif; ?>

        <!-- Tarjetas Cuadradas Pequeñas de Métricas -->
        <div class="stats-grid-square">
            <div class="stat-card-square active-card" onclick="filterByStatCard('all', this)">
                <span class="stat-title">TOTAL</span>
                <span class="stat-val"><?php echo count($inventario_bd); ?></span>
            </div>
            <div class="stat-card-square" onclick="filterByStatCard('activos', this)">
                <span class="stat-title">ACTIVOS</span>
                <span class="stat-val"><?php echo $countActivos; ?></span>
            </div>
            <div class="stat-card-square" onclick="filterByStatCard('inactivos', this)">
                <span class="stat-title">INACTIVOS</span>
                <span class="stat-val"><?php echo $countInactivos; ?></span>
            </div>
            <div class="stat-card-square" onclick="filterByStatCard('disponible', this)">
                <span class="stat-title">DISPONIBLE</span>
                <span class="stat-val"><?php echo $countDisponibles; ?></span>
            </div>
            <div class="stat-card-square" onclick="filterByStatCard('stock_bajo', this)">
                <span class="stat-title">STOCK BAJO</span>
                <span class="stat-val"><?php echo $countCriticos; ?></span>
            </div>
            <div class="stat-card-square" onclick="filterByStatCard('reposiciones', this)">
                <span class="stat-title">REPOSICIÓN</span>
                <span class="stat-val"><?php echo count($historial_reposicion); ?></span>
            </div>
            <div class="stat-card-square" onclick="filterByStatCard('mas_vendido', this)">
                <span class="stat-title">MÁS VENDIDO</span>
                <span class="stat-val"><?php echo $countMasVendidos; ?></span>
            </div>
            <div class="stat-card-square" onclick="filterByStatCard('menos_vendido', this)">
                <span class="stat-title">MENOS VENDIDO</span>
                <span class="stat-val"><?php echo $countMenosVendidos; ?></span>
            </div>
        </div>
        <div class="inventory-code-metrics" aria-label="Cobertura de códigos de productos">
            <button type="button" class="inventory-code-metric" aria-pressed="false" onclick="filterByCodeMetric('sku', this)">
                <span>PRODUCTOS CON SKU</span><strong><?= $countConSku ?></strong>
            </button>
            <button type="button" class="inventory-code-metric" aria-pressed="false" onclick="filterByCodeMetric('barcode', this)">
                <span>PRODUCTOS CON CÓDIGO DE BARRAS</span><strong><?= $countConCodigoBarras ?></strong>
            </button>
        </div>

        <!-- Tabla y Buscador -->
        <section class="panel" style="margin-bottom: 28px; background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 20px rgba(0,0,0,0.04); overflow: hidden;">
            <div class="panel-header" style="display: flex; align-items: center; justify-content: space-between; padding: 18px 24px; border-bottom: 1px solid #e2e8f0; background: #ffffff; flex-wrap: wrap; gap: 14px;">
                <h2 style="margin: 0; font-size: 1.2rem; font-weight: 800; color: #0f172a;">Productos en Inventario</h2>
                
                <div style="display: flex; align-items: center; gap: 16px; flex-wrap: wrap; flex: 1; justify-content: flex-end;">
                    <!-- Barra de búsqueda estética con botón de cámara integrado en el input (Imagen 4) -->
                    <div style="position: relative; flex: 1; max-width: 400px; min-width: 220px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); pointer-events: none;"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                        <input type="text" id="searchInventario" class="search-input" placeholder="Buscar producto, SKU o código..." style="width: 100%; padding: 10px 42px 10px 40px; border: 1.5px solid #cbd5e1; border-radius: 24px; font-size: 0.88rem; outline: none; background: #f8fafc; box-sizing: border-box;">
                        <button type="button" onclick="UniversalCameraScanner.open({ targetInputId: 'searchInventario', title: 'Buscar Producto por Código de Barras' })" class="btn-inline-input-scanner" style="right: 12px;" title="Usar celular vinculado para escanear" aria-label="Escanear con celular vinculado">
                            <svg xmlns="http://www.w3.org/2000/svg" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 7V5a2 2 0 0 1 2-2h2"></path>
                                <path d="M17 3h2a2 2 0 0 1 2 2v2"></path>
                                <path d="M21 17v2a2 2 0 0 1-2 2h-2"></path>
                                <path d="M7 21H5a2 2 0 0 1-2-2v-2"></path>
                                <line x1="7" y1="8" x2="7" y2="16"></line>
                                <line x1="12" y1="8" x2="12" y2="16"></line>
                                <line x1="17" y1="8" x2="17" y2="16"></line>
                            </svg>
                        </button>
                    </div>

                    <!-- Selector Ordenar por (ARRIBA) -->
                    <div class="inventario-sort-control">
                        <span>Ordenar por</span>
                        <select id="selectOrdenInventario" aria-label="Ordenar productos" onchange="triggerTableUpdate()">
                            <option value="recomendados">Recomendados</option>
                            <option value="nombre_asc">Nombre (A - Z)</option>
                            <option value="stock_asc">Stock (Menor a Mayor)</option>
                            <option value="stock_desc">Stock (Mayor a Menor)</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table inventario-table" id="inventarioTable" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; text-align: left;">
                            <th style="padding: 14px 20px; font-size: 0.78rem; font-weight: 800; color: #64748b; text-transform: uppercase;">PRODUCTO</th>
                            <th style="padding: 14px 16px; font-size: 0.78rem; font-weight: 800; color: #64748b; text-transform: uppercase;">PRECIO COMPRA</th>
                            <th style="padding: 14px 16px; font-size: 0.78rem; font-weight: 800; color: #64748b; text-transform: uppercase;">PRECIO VENTA</th>
                            <th style="padding: 14px 16px; font-size: 0.78rem; font-weight: 800; color: #64748b; text-transform: uppercase;">STOCK ACTUAL</th>
                            <th style="padding: 14px 16px; font-size: 0.78rem; font-weight: 800; color: #64748b; text-transform: uppercase;">STOCK MÍNIMO</th>
                            <th style="padding: 14px 16px; font-size: 0.78rem; font-weight: 800; color: #64748b; text-transform: uppercase;">UBICACIÓN</th>
                            <th style="padding: 14px 16px; font-size: 0.78rem; font-weight: 800; color: #64748b; text-transform: uppercase;">ESTADO</th>
                            <th style="padding: 14px 16px; font-size: 0.78rem; font-weight: 800; color: #64748b; text-transform: uppercase;">ÚLTIMA ACT.</th>
                            <th class="text-right actions-col" style="padding: 14px 20px; font-size: 0.78rem; font-weight: 800; color: #64748b; text-transform: uppercase; text-align: right;">ACCIÓN</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($inventario_bd)): ?>
                            <?php foreach($inventario_bd as $item): ?>
                                <?php
                                    $stockActual = (float)$item['stock_actual'];
                                    $stockMinimo = (float)$item['stock_minimo'];
                                    $unidad = $item['unidad_medida'] === 'KG' ? 'kg' : 'und.';

                                    if($stockActual <= $stockMinimo){
                                        $estadoClase = 'status-tag-error';
                                        $estadoTexto = 'Stock Bajo';
                                    }else{
                                        $estadoClase = 'status-tag-success';
                                        $estadoTexto = 'Disponible';
                                    }
                                    $skuProd = !empty($item['codigo']) ? $item['codigo'] : ('PROD-' . str_pad($item['id_producto'], 4, '0', STR_PAD_LEFT));
                                    $ventasCount = (int)($item['ventas_registradas'] ?? 0);
                                    $puedeEliminar = $ventasCount === 0
                                        && (int)($item['movimientos_count'] ?? 0) === 0
                                        && (int)($item['reposiciones_count'] ?? 0) === 0;
                                ?>
                                <?php $isActivoProd = (int)($item['activo'] ?? 1) === 1; ?>
                                <tr data-id="<?= (int)$item['id_producto']; ?>" data-name="<?= htmlspecialchars($item['titulo'], ENT_QUOTES); ?>" data-unit="<?= htmlspecialchars($unidad, ENT_QUOTES); ?>" data-sku="<?= htmlspecialchars((string)($item['codigo'] ?? ''), ENT_QUOTES); ?>" data-barcode="<?= htmlspecialchars((string)($item['codigo_barras'] ?? ''), ENT_QUOTES); ?>" data-has-sku="<?= !empty(trim((string)($item['codigo'] ?? ''))) ? 1 : 0; ?>" data-has-barcode="<?= !empty(trim((string)($item['codigo_barras'] ?? ''))) ? 1 : 0; ?>" data-stock="<?= $stockActual; ?>" data-stockmin="<?= $stockMinimo; ?>" data-vendidos="<?= (int)($item['ventas_count'] ?? 0); ?>" data-activo="<?= $isActivoProd ? 1 : 0; ?>" style="border-bottom: 1px solid #f1f5f9;">
                                    <!-- Columna Producto: SOLO nombre y SKU -->
                                    <td style="padding: 14px 20px;">
                                        <div class="inv-producto" style="display: flex; align-items: center; gap: 12px;">
                                            <img src="<?php echo htmlspecialchars($item['imagen']); ?>" alt="<?php echo htmlspecialchars($item['titulo']); ?>" class="inv-thumb" onerror="this.onerror=null;this.src='Images/logo.png';" style="width: 44px; height: 44px; border-radius: 8px; object-fit: cover; border: 1px solid #e2e8f0; flex-shrink: 0;">
                                            <div class="inv-details" style="display: flex; flex-direction: column; gap: 3px;">
                                                <strong style="font-size: 0.92rem; color: #0f172a; font-weight: 700; line-height: 1.25;"><?php echo htmlspecialchars($item['titulo']); ?></strong>
                                                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                                    <small style="color: #64748b; font-size: 0.78rem; font-weight: 600;">SKU: <?php echo htmlspecialchars($skuProd); ?></small>
                                                    <?php if (!empty($item['codigo_barras'])): ?>
                                                        <small style="color: #64748b; font-size: 0.75rem; font-weight: 700; padding: 1px 6px; border-radius: 4px;">Cód: <?= htmlspecialchars($item['codigo_barras']); ?></small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="padding: 14px 16px; color: #475569; font-weight: 650; white-space: nowrap;">
                                        S/ <?= number_format((float)($item['precio_costo'] ?? 0), 2); ?>
                                    </td>
                                    <td style="padding: 14px 16px; color: #14532d; font-weight: 800; white-space: nowrap;">
                                        S/ <?= number_format((float)($item['precio'] ?? 0), 2); ?>
                                    </td>
                                    <td style="padding: 14px 16px;">
                                        <span class="inv-stock-pill" style="color: <?= $stockActual <= $stockMinimo ? '#dc2626' : '#0f172a'; ?>;">
                                            <?php echo number_format($stockActual, 2); ?> <?php echo $unidad; ?>
                                        </span>
                                    </td>
                                    <td style="padding: 14px 16px; color: #475569; font-weight: 600;">
                                        <?php echo number_format($stockMinimo, 2); ?> <?php echo $unidad; ?>
                                    </td>
                                    <td style="padding: 14px 16px;">
                                        <span class="ubicacion-tag">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                                            <span><?php echo htmlspecialchars($item['ubicacion']); ?></span>
                                        </span>
                                    </td>
                                    <td style="padding: 14px 16px;">
                                        <span class="badge-deliv <?= $isActivoProd ? 'disponible' : 'inactivo'; ?>">
                                            <?= $isActivoProd ? 'Disponible' : 'Inactivo'; ?>
                                        </span>
                                    </td>
                                    <td style="padding: 14px 16px; color: #64748b; font-size: 0.82rem;">
                                        <?php echo date('d/m/Y H:i', strtotime($item['ultima_act'])); ?>
                                    </td>
                                    <td class="actions-col" style="padding: 14px 20px; text-align: right; white-space: nowrap; vertical-align: middle;">
                                        <div class="inventory-actions">
                                        <button type="button" class="inventory-action-btn btn-editar-prod" aria-label="Editar producto" title="Editar producto" onclick='abrirModalEditarProducto(<?php echo json_encode($item, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>)'>
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                                        </button>
                                                <button type="button" class="inventory-action-btn btn-reponer" aria-label="Reponer stock" title="Reponer stock" onclick="abrirModalReposicion(
                                                '<?php echo (int)$item['id_producto']; ?>',
                                                '<?php echo htmlspecialchars($item['titulo'], ENT_QUOTES); ?>',
                                                '<?php echo $unidad; ?>',
                                                '<?php echo htmlspecialchars($item['codigo'] ?? '', ENT_QUOTES); ?>',
                                                '<?php echo htmlspecialchars($item['codigo_barras'] ?? '', ENT_QUOTES); ?>'
                                            )">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 8v8"></path><path d="m8 12 4 4 4-4"></path><path d="M20 16.5A9 9 0 1 1 19.2 7"></path></svg>
                                        </button>
                                        <?php if ($puedeEliminar): ?>
                                        <button type="button" class="inventory-action-btn btn-eliminar-prod" aria-label="Eliminar producto" title="Eliminar producto" onclick="confirmarEliminarProducto('<?php echo (int)$item['id_producto']; ?>', '<?php echo htmlspecialchars($item['titulo'], ENT_QUOTES); ?>', 0)">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"></path><path d="M8 6V4h8v2"></path><path d="m19 6-1 14H6L5 6"></path><path d="M10 11v5"></path><path d="M14 11v5"></path></svg>
                                        </button>
                                        <?php else: ?>
                                        <button type="button" class="inventory-action-btn <?= $isActivoProd ? 'btn-inactivar-prod' : 'btn-activar-prod'; ?>" aria-label="<?= $isActivoProd ? 'Inactivar' : 'Activar'; ?> producto" title="<?= $isActivoProd ? 'Inactivar' : 'Activar'; ?> producto" onclick='window.confirmarCambioEstadoProducto(<?= (int)$item['id_producto']; ?>, <?= $isActivoProd ? 0 : 1; ?>, <?= json_encode($item['titulo'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>)'>
                                            <?php if ($isActivoProd): ?>
                                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line></svg>
                                            <?php else: ?>
                                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><path d="m8 12 2.5 2.5L16 9"></path></svg>
                                            <?php endif; ?>
                                        </button>
                                        <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center" style="padding: 40px; color: #64748b; text-align: center;">
                                    No hay productos registrados en el inventario.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Controles de Paginación (Ubicados ABAJO de la tabla) -->
            <div class="pagination-container" id="paginationControls" style="display: flex; align-items: center; justify-content: center; padding: 16px 24px; border-top: 1px solid #e2e8f0; background: #ffffff; gap: 14px;">
                <div style="display: flex; align-items: center; gap: 6px;" id="paginationPagesList">
                    <!-- JS renderiza: < 1 2 3 ... > -->
                </div>
            </div>
        </section>
    </main>

    <!-- Modal Reposición -->
    <div class="modal-overlay" id="modalReposicion">
        <div class="modal-content" style="max-width: 480px;">
            <div class="modal-header">
                <h3 style="margin: 0; font-size: 1.15rem; font-weight: 800;">Reponer Stock</h3>
                <button type="button" class="modal-close" onclick="cerrarModal('modalReposicion')" aria-label="Cerrar">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
            </div>
            <div class="modal-body" style="padding: 20px;">
                <form method="POST" action="index.php?action=reponer_stock" id="formReposicion">
                    <input type="hidden" name="id_producto" id="repoIdProducto">

                    <div class="grupo" style="margin-bottom: 14px;">
                        <label class="label" for="repoCodigoBusqueda">Buscar producto por SKU o código de barras</label>
                        <div style="position: relative;">
                            <input type="text" id="repoCodigoBusqueda" class="input-modern" autocomplete="off" placeholder="Escribe o escanea el código" onchange="buscarProductoParaReposicion(this.value)" onkeydown="if(event.key==='Enter'){event.preventDefault();buscarProductoParaReposicion(this.value);}" style="width:100%;padding:10px 44px 10px 12px;box-sizing:border-box;">
                            <button type="button" class="btn-inline-input-scanner" aria-label="Escanear producto para reposición" title="Escanear con el celular" onclick="UniversalCameraScanner.open({ targetInputId: 'repoCodigoBusqueda', title: 'Buscar producto para reposición', onScan: function(codigo){ buscarProductoParaReposicion(codigo); } })">
                                <svg xmlns="http://www.w3.org/2000/svg" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7V5a2 2 0 0 1 2-2h2M17 3h2a2 2 0 0 1 2 2v2M21 17v2a2 2 0 0 1-2 2h-2M7 21H5a2 2 0 0 1-2-2v-2M7 8v8M12 8v8M17 8v8" /></svg>
                            </button>
                        </div>
                    </div>
                    
                    <div class="user-detail-group" style="margin-bottom: 16px;">
                        <div class="user-detail-row">
                            <span class="detail-label">Producto:</span>
                            <strong class="detail-value" id="repoNombreProducto" style="color: #15803D; font-size: 1rem;">-</strong>
                        </div>
                        <div class="user-detail-row">
                            <span class="detail-label">Unidad de medida:</span>
                            <span class="detail-value" id="repoUnidadTexto">-</span>
                        </div>
                        <div class="user-detail-row">
                            <span class="detail-label">SKU / Código de barras:</span>
                            <span class="detail-value" id="repoCodigoProducto">-</span>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="grupo">
                            <label class="label">Cantidad a ingresar *</label>
                            <input type="number" step="0.01" name="cantidad" class="input-modern" required placeholder="Ej. 10">
                        </div>
                        <div class="grupo">
                            <label class="label">Costo unitario (S/)</label>
                            <input type="number" step="0.01" name="costo_unitario" class="input-modern" placeholder="Ej. 3.50">
                        </div>
                    </div>

                    <div class="grupo" style="margin-top: 14px;">
                        <label class="label">Proveedor</label>
                        <input type="text" name="proveedor" class="input-modern" placeholder="Ej. Distribuidora La Norteña">
                    </div>

                    <div class="grupo" style="margin-top: 14px;">
                        <label class="label">Observación / Nota</label>
                        <textarea name="observacion" rows="3" class="input-modern" style="resize: vertical;" placeholder="Detalle opcional..."></textarea>
                    </div>

                    <div class="modal-footer" style="margin-top: 20px; display: flex; justify-content: flex-end; gap: 10px;">
                        <button type="button" class="btn btn-secondary" onclick="cerrarModal('modalReposicion')">Cancelar</button>
                        <button type="submit" class="btn btn-primary btn-save" style="background: #15803d;">Guardar Reposición</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL NUEVO PRODUCTO DESDE INVENTARIO (IMAGEN 3) -->
    <div class="modal-overlay" id="modalNuevoProducto">
        <div class="modal-content" style="max-width: 560px; border-radius: 16px;">
            <div class="modal-header" style="padding: 18px 24px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0; font-size: 1.25rem; font-weight: 800; color: #0f172a;">Registrar Nuevo Producto</h3>
                <button type="button" class="modal-close" onclick="cerrarModal('modalNuevoProducto')" aria-label="Cerrar" style="border: none; background: #f1f5f9; width: 34px; height: 34px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; color: #64748b;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
            </div>
            <div class="modal-body" style="padding: 24px; max-height: 80vh; overflow-y: auto;">
                <form method="POST" action="index.php?action=crear_producto" enctype="multipart/form-data" id="formNuevoProductoInventario">
                    <input type="hidden" name="redirect_to" value="inventario">

                    <!-- Nombre / Título -->
                    <div class="grupo" style="margin-bottom: 16px;">
                        <label class="label" style="display: block; font-weight: 700; font-size: 0.78rem; color: #334155; text-transform: uppercase; margin-bottom: 6px;">NOMBRE / TÍTULO DEL PRODUCTO *</label>
                        <input type="text" name="titulo" class="input-modern" required placeholder="Ej. Arroz Costeño 5kg" style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.95rem; box-sizing: border-box;">
                    </div>

                    <!-- Código SKU y Código de Barras -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 16px;">
                        <div class="grupo">
                            <label class="label" style="display: block; font-weight: 700; font-size: 0.78rem; color: #334155; text-transform: uppercase; margin-bottom: 6px;">CÓDIGO SKU / REFERENCIA</label>
                            <input type="text" name="codigo" class="input-modern" placeholder="Ej. SKU-10023" style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.92rem; box-sizing: border-box;">
                        </div>
                        <div class="grupo">
                            <label class="label" style="display: block; font-weight: 700; font-size: 0.78rem; color: #334155; text-transform: uppercase; margin-bottom: 6px;">CÓDIGO DE BARRAS (EAN/UPC)</label>
                            <div style="position: relative; width: 100%;">
                                <input type="text" name="codigo_barras" id="nuevoProdCodigoBarras" class="input-modern" placeholder="Ej. 7751234567890" style="width: 100%; padding: 10px 42px 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.92rem; box-sizing: border-box;">
                                <button type="button" onclick="UniversalCameraScanner.open({ targetInputId: 'nuevoProdCodigoBarras', title: 'Escanear Código de Barras (Nuevo Producto)' })" class="btn-inline-input-scanner" title="Usar celular vinculado para escanear" aria-label="Escanear con celular vinculado">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M3 7V5a2 2 0 0 1 2-2h2"></path>
                                        <path d="M17 3h2a2 2 0 0 1 2 2v2"></path>
                                        <path d="M21 17v2a2 2 0 0 1-2 2h-2"></path>
                                        <path d="M7 21H5a2 2 0 0 1-2-2v-2"></path>
                                        <line x1="7" y1="8" x2="7" y2="16"></line>
                                        <line x1="12" y1="8" x2="12" y2="16"></line>
                                        <line x1="17" y1="8" x2="17" y2="16"></line>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Fila Oferta y Precios -->
                    <div style="margin-bottom: 16px; padding: 14px; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 12px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; flex-wrap: wrap; gap: 8px;">
                            <label style="display: inline-flex; align-items: center; gap: 8px; font-size: 0.88rem; font-weight: 700; color: #15803d; cursor: pointer;">
                                <input type="checkbox" name="en_oferta" id="nuevoProdEnOferta" value="1" onchange="toggleOfertaCamposNuevo(this.checked)" style="width: 18px; height: 18px; accent-color: #15803d;">
                                <span>🔥 ¿Este producto entra en Oferta Especial / Descuento?</span>
                            </label>
                            <span id="badgePctNuevo" style="display: none; padding: 4px 10px; border-radius: 20px; background: #dcfce7; color: #15803d; font-weight: 800; font-size: 0.82rem; border: 1px solid #bbf7d0;"></span>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                            <div class="grupo" id="wrapPrecioInicialNuevo" style="display: none;">
                                <label class="label" style="display: block; font-weight: 700; font-size: 0.78rem; color: #64748b; text-transform: uppercase; margin-bottom: 6px;">PRECIO INICIAL / REAL (S/) *</label>
                                <input type="number" step="0.01" name="precio_anterior" id="nuevoProdPrecioAnterior" class="input-modern" placeholder="Ej. 20.00" oninput="calcDescuentoNuevo()" style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.95rem; box-sizing: border-box;">
                            </div>
                            <div class="grupo">
                                <label class="label" id="lblPrecioNuevo" style="display: block; font-weight: 700; font-size: 0.78rem; color: #334155; text-transform: uppercase; margin-bottom: 6px;">PRECIO VENTA (S/) *</label>
                                <input type="number" step="0.01" name="precio" id="nuevoProdPrecioInput" class="input-modern" required placeholder="Ej. 15.00" oninput="calcDescuentoNuevo()" style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.95rem; box-sizing: border-box;">
                            </div>
                        </div>
                    </div>

                    <!-- Fila Unidad y Stock -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 16px;">
                        <div class="grupo">
                            <label class="label" style="display: block; font-weight: 700; font-size: 0.78rem; color: #334155; text-transform: uppercase; margin-bottom: 6px;">UNIDAD DE MEDIDA *</label>
                            <select name="unidad_medida" class="input-modern" style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.95rem; box-sizing: border-box; background: #fff;">
                                <option value="UND">Unidad (UND)</option>
                                <option value="KG">Kilogramo (KG)</option>
                            </select>
                        </div>
                        <div class="grupo">
                            <label class="label" style="display: block; font-weight: 700; font-size: 0.78rem; color: #334155; text-transform: uppercase; margin-bottom: 6px;">STOCK INICIAL *</label>
                            <input type="number" step="0.01" name="stock" class="input-modern" required placeholder="Ej. 50" style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.95rem; box-sizing: border-box;">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 16px;">
                        <div class="grupo">
                            <label class="label" style="display: block; font-weight: 700; font-size: 0.78rem; color: #334155; text-transform: uppercase; margin-bottom: 6px;">STOCK MÍNIMO ALERTA</label>
                            <input type="number" step="0.01" name="stock_minimo" value="5" class="input-modern" style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.95rem; box-sizing: border-box;">
                        </div>
                        <div class="grupo">
                            <label class="label" style="display: block; font-weight: 700; font-size: 0.78rem; color: #334155; text-transform: uppercase; margin-bottom: 6px;">UBICACIÓN EN ALMACÉN</label>
                            <input type="text" name="ubicacion" class="input-modern" placeholder="Ej. Pasillo 2 - Estante A" style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.95rem; box-sizing: border-box;">
                        </div>
                    </div>

                    <!-- 3 SELECTORES CASCADA: Macrocategoría -> Categoría -> Subcategoría (IMAGEN 2) -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; margin-bottom: 16px;">
                        <div class="grupo">
                            <label class="label" style="display: block; font-weight: 700; font-size: 0.72rem; color: #334155; text-transform: uppercase; margin-bottom: 6px;">MACROCATEGORÍA (N1)</label>
                            <select id="nuevoMomentoSelect" class="input-modern" onchange="onNuevoMomentoChange(this.value)" style="width: 100%; padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.85rem; background: #fff;">
                                <option value="">-- Seleccionar N1 --</option>
                                <?php if (!empty($moments)): ?>
                                    <?php foreach ($moments as $m): ?>
                                        <option value="<?= $m['id_momento']; ?>"><?= htmlspecialchars($m['nombre']); ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="grupo">
                            <label class="label" style="display: block; font-weight: 700; font-size: 0.72rem; color: #334155; text-transform: uppercase; margin-bottom: 6px;">CATEGORÍA (N2)</label>
                            <select id="nuevoGrupoSelect" name="id_grupo" class="input-modern" disabled onchange="onNuevoGrupoChange(this.value)" style="width: 100%; padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.85rem; background: #fff;">
                                <option value="">-- Seleccione N1 primero --</option>
                                <?php if (!empty($grupos)): ?>
                                    <?php foreach ($grupos as $g): ?>
                                        <option value="<?= $g['id_grupo']; ?>" data-momento="<?= $g['id_momento']; ?>"><?= htmlspecialchars($g['nombre']); ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="grupo">
                            <label class="label" style="display: block; font-weight: 700; font-size: 0.72rem; color: #334155; text-transform: uppercase; margin-bottom: 6px;">SUBCATEGORÍA (N3) *</label>
                            <select name="id_categoria" id="nuevoCategoriaSelect" required disabled class="input-modern" style="width: 100%; padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.85rem; background: #fff;">
                                <option value="">-- Seleccione N2 primero --</option>
                                <?php if (!empty($categorias)): ?>
                                    <?php foreach ($categorias as $cat): ?>
                                        <option value="<?= $cat['id_categoria']; ?>" data-grupo="<?= $cat['id_grupo']; ?>" data-momento="<?= $cat['id_momento']; ?>"><?= htmlspecialchars($cat['nombre']); ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Hasta 4 Imágenes por producto -->
                    <div class="grupo" style="margin-bottom: 16px;">
                        <label class="label" style="display: block; font-weight: 700; font-size: 0.78rem; color: #334155; text-transform: uppercase; margin-bottom: 8px;">IMÁGENES DEL PRODUCTO (HASTA 4 IMÁGENES)</label>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <div style="border: 1.5px dashed #cbd5e1; border-radius: 10px; padding: 10px; background: #f8fafc; text-align: center;">
                                <span style="display:block; font-size: 0.72rem; font-weight: 800; color: #15803d; text-transform: uppercase; margin-bottom: 4px;">1. Imagen Principal *</span>
                                <label for="nuevoProdFile1" style="display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; background: #15803d; color: #fff; font-size: 0.75rem; font-weight: 700; border-radius: 6px; cursor: pointer;">
                                    <span>Subir archivo</span>
                                    <input type="file" id="nuevoProdFile1" name="imagen" accept="image/*" style="display:none;" onchange="previewImgModal(this, 'prevNuevo1', 'lblNuevo1')">
                                </label>
                                <span id="lblNuevo1" style="display:block; font-size: 0.72rem; color: #64748b; margin-top: 4px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">Principal</span>
                                <img id="prevNuevo1" src="" style="display:none; max-height: 48px; border-radius: 6px; margin: 4px auto 0 auto; border: 1px solid #cbd5e1;">
                                <input type="text" name="imagen_url" placeholder="o URL directo..." style="width:100%; margin-top: 6px; padding: 4px 8px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.78rem; box-sizing: border-box;" oninput="document.getElementById('prevNuevo1').src = this.value; document.getElementById('prevNuevo1').style.display = 'block';">
                            </div>

                            <div style="border: 1.5px dashed #cbd5e1; border-radius: 10px; padding: 10px; background: #f8fafc; text-align: center;">
                                <span style="display:block; font-size: 0.72rem; font-weight: 800; color: #475569; text-transform: uppercase; margin-bottom: 4px;">2. Imagen 2 (Galería)</span>
                                <label for="nuevoProdFile2" style="display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; background: #475569; color: #fff; font-size: 0.75rem; font-weight: 700; border-radius: 6px; cursor: pointer;">
                                    <span>Subir archivo</span>
                                    <input type="file" id="nuevoProdFile2" name="imagen_2" accept="image/*" style="display:none;" onchange="previewImgModal(this, 'prevNuevo2', 'lblNuevo2')">
                                </label>
                                <span id="lblNuevo2" style="display:block; font-size: 0.72rem; color: #64748b; margin-top: 4px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">Galería 2</span>
                                <img id="prevNuevo2" src="" style="display:none; max-height: 48px; border-radius: 6px; margin: 4px auto 0 auto; border: 1px solid #cbd5e1;">
                                <input type="text" name="imagen_url_2" placeholder="o URL directo..." style="width:100%; margin-top: 6px; padding: 4px 8px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.78rem; box-sizing: border-box;" oninput="document.getElementById('prevNuevo2').src = this.value; document.getElementById('prevNuevo2').style.display = 'block';">
                            </div>

                            <div style="border: 1.5px dashed #cbd5e1; border-radius: 10px; padding: 10px; background: #f8fafc; text-align: center;">
                                <span style="display:block; font-size: 0.72rem; font-weight: 800; color: #475569; text-transform: uppercase; margin-bottom: 4px;">3. Imagen 3 (Galería)</span>
                                <label for="nuevoProdFile3" style="display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; background: #475569; color: #fff; font-size: 0.75rem; font-weight: 700; border-radius: 6px; cursor: pointer;">
                                    <span>Subir archivo</span>
                                    <input type="file" id="nuevoProdFile3" name="imagen_3" accept="image/*" style="display:none;" onchange="previewImgModal(this, 'prevNuevo3', 'lblNuevo3')">
                                </label>
                                <span id="lblNuevo3" style="display:block; font-size: 0.72rem; color: #64748b; margin-top: 4px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">Galería 3</span>
                                <img id="prevNuevo3" src="" style="display:none; max-height: 48px; border-radius: 6px; margin: 4px auto 0 auto; border: 1px solid #cbd5e1;">
                                <input type="text" name="imagen_url_3" placeholder="o URL directo..." style="width:100%; margin-top: 6px; padding: 4px 8px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.78rem; box-sizing: border-box;" oninput="document.getElementById('prevNuevo3').src = this.value; document.getElementById('prevNuevo3').style.display = 'block';">
                            </div>

                            <div style="border: 1.5px dashed #cbd5e1; border-radius: 10px; padding: 10px; background: #f8fafc; text-align: center;">
                                <span style="display:block; font-size: 0.72rem; font-weight: 800; color: #475569; text-transform: uppercase; margin-bottom: 4px;">4. Imagen 4 (Galería)</span>
                                <label for="nuevoProdFile4" style="display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; background: #475569; color: #fff; font-size: 0.75rem; font-weight: 700; border-radius: 6px; cursor: pointer;">
                                    <span>Subir archivo</span>
                                    <input type="file" id="nuevoProdFile4" name="imagen_4" accept="image/*" style="display:none;" onchange="previewImgModal(this, 'prevNuevo4', 'lblNuevo4')">
                                </label>
                                <span id="lblNuevo4" style="display:block; font-size: 0.72rem; color: #64748b; margin-top: 4px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">Galería 4</span>
                                <img id="prevNuevo4" src="" style="display:none; max-height: 48px; border-radius: 6px; margin: 4px auto 0 auto; border: 1px solid #cbd5e1;">
                                <input type="text" name="imagen_url_4" placeholder="o URL directo..." style="width:100%; margin-top: 6px; padding: 4px 8px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.78rem; box-sizing: border-box;" oninput="document.getElementById('prevNuevo4').src = this.value; document.getElementById('prevNuevo4').style.display = 'block';">
                            </div>
                        </div>
                    </div>

                    <!-- Descripción -->
                    <div class="grupo" style="margin-bottom: 20px;">
                        <label class="label" style="display: block; font-weight: 700; font-size: 0.78rem; color: #334155; text-transform: uppercase; margin-bottom: 6px;">DESCRIPCIÓN DEL PRODUCTO</label>
                        <textarea name="descripcion" rows="2" class="input-modern" placeholder="Detalles, especificaciones o recomendaciones..." style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.88rem; box-sizing: border-box; resize: vertical;"></textarea>
                    </div>

                    <!-- Sugerencia -->
                    <div style="margin-bottom: 20px;">
                        <label style="display: inline-flex; align-items: center; gap: 8px; font-size: 0.88rem; font-weight: 600; color: #334155; cursor: pointer;">
                            <input type="checkbox" name="es_sugerencia" value="1" style="accent-color: #15803d; width: 16px; height: 16px;">
                            <span>Destacar como producto sugerido / oferta especial</span>
                        </label>
                    </div>

                    <div class="modal-footer" style="display: flex; justify-content: flex-end; gap: 10px;">
                        <button type="button" class="btn btn-secondary" onclick="cerrarModal('modalNuevoProducto')">Cancelar</button>
                        <button type="submit" class="btn btn-primary" style="background: #15803d; border-color: #15803d; font-weight: 700;">Guardar Producto</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Editar Producto -->
    <div class="modal-overlay" id="modalEditarProducto">
        <div class="modal-content" style="max-width: 560px; border-radius: 16px;">
            <div class="modal-header" style="padding: 18px 24px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0; font-size: 1.25rem; font-weight: 800; color: #0f172a;">Editar Información del Producto</h3>
                <button type="button" class="modal-close" onclick="cerrarModal('modalEditarProducto')" aria-label="Cerrar" style="border: none; background: #f1f5f9; width: 34px; height: 34px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; color: #64748b;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
            </div>
            <div class="modal-body" style="padding: 24px; max-height: 80vh; overflow-y: auto;">
                <form method="POST" action="index.php?action=actualizar_producto" enctype="multipart/form-data" id="formEditarProductoInventario">
                    <input type="hidden" name="id_producto" id="editProdId">
                    <input type="hidden" name="redirect_to" value="inventario">
                    <input type="hidden" name="imagen_actual" id="editProdImagenActual">
                    <input type="hidden" name="imagen_actual_2" id="editProdImagenActual2">
                    <input type="hidden" name="imagen_actual_3" id="editProdImagenActual3">
                    <input type="hidden" name="imagen_actual_4" id="editProdImagenActual4">

                    <!-- Nombre / Título -->
                    <div class="grupo" style="margin-bottom: 16px;">
                        <label class="label" style="display: block; font-weight: 700; font-size: 0.78rem; color: #334155; text-transform: uppercase; margin-bottom: 6px;">Nombre / Título del Producto *</label>
                        <input type="text" name="titulo" id="editProdTitulo" class="input-modern" required placeholder="Ej. Aceite Primor 900ml" style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.95rem; box-sizing: border-box;">
                    </div>

                    <!-- Código SKU y Código de Barras -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 16px;">
                        <div class="grupo">
                            <label class="label" style="display: block; font-weight: 700; font-size: 0.78rem; color: #334155; text-transform: uppercase; margin-bottom: 6px;">Código SKU / Referencia</label>
                            <input type="text" name="codigo" id="editProdCodigo" class="input-modern" placeholder="Ej. SKU-10023" style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.92rem; box-sizing: border-box;">
                        </div>
                        <div class="grupo">
                            <label class="label" style="display: block; font-weight: 700; font-size: 0.78rem; color: #334155; text-transform: uppercase; margin-bottom: 6px;">Código de Barras (EAN/UPC)</label>
                            <div style="position: relative; width: 100%;">
                                <input type="text" name="codigo_barras" id="editProdCodigoBarras" class="input-modern" placeholder="Ej. 7751234567890" style="width: 100%; padding: 10px 42px 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.92rem; box-sizing: border-box;">
                                <button type="button" onclick="UniversalCameraScanner.open({ targetInputId: 'editProdCodigoBarras', title: 'Escanear Código de Barras (Editar Producto)' })" class="btn-inline-input-scanner" title="Usar celular vinculado para escanear" aria-label="Escanear con celular vinculado">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M3 7V5a2 2 0 0 1 2-2h2"></path>
                                        <path d="M17 3h2a2 2 0 0 1 2 2v2"></path>
                                        <path d="M21 17v2a2 2 0 0 1-2 2h-2"></path>
                                        <path d="M7 21H5a2 2 0 0 1-2-2v-2"></path>
                                        <line x1="7" y1="8" x2="7" y2="16"></line>
                                        <line x1="12" y1="8" x2="12" y2="16"></line>
                                        <line x1="17" y1="8" x2="17" y2="16"></line>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Fila Oferta y Precios Edit -->
                    <div style="margin-bottom: 16px; padding: 14px; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 12px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; flex-wrap: wrap; gap: 8px;">
                            <label style="display: inline-flex; align-items: center; gap: 8px; font-size: 0.88rem; font-weight: 700; color: #15803d; cursor: pointer;">
                                <input type="checkbox" name="en_oferta" id="editProdEnOferta" value="1" onchange="toggleOfertaCamposEdit(this.checked)" style="width: 18px; height: 18px; accent-color: #15803d;">
                                <span>🔥 ¿Este producto entra en Oferta Especial / Descuento?</span>
                            </label>
                            <span id="badgePctEdit" style="display: none; padding: 4px 10px; border-radius: 20px; background: #dcfce7; color: #15803d; font-weight: 800; font-size: 0.82rem; border: 1px solid #bbf7d0;"></span>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                            <div class="grupo" id="wrapPrecioInicialEdit" style="display: none;">
                                <label class="label" style="display: block; font-weight: 700; font-size: 0.78rem; color: #64748b; text-transform: uppercase; margin-bottom: 6px;">PRECIO INICIAL / REAL (S/)</label>
                                <input type="number" step="0.01" name="precio_anterior" id="editProdPrecioAnterior" class="input-modern" placeholder="Ej. 18.50" oninput="calcDescuentoEdit()" style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.95rem; box-sizing: border-box;">
                            </div>
                            <div class="grupo">
                                <label class="label" id="lblPrecioEdit" style="display: block; font-weight: 700; font-size: 0.78rem; color: #334155; text-transform: uppercase; margin-bottom: 6px;">PRECIO VENTA (S/) *</label>
                                <input type="number" step="0.01" name="precio" id="editProdPrecio" class="input-modern" required placeholder="Ej. 9.50" oninput="calcDescuentoEdit()" style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.95rem; box-sizing: border-box;">
                            </div>
                        </div>
                    </div>

                    <!-- Fila Unidad y Stock -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 16px;">
                        <div class="grupo">
                            <label class="label" style="display: block; font-weight: 700; font-size: 0.78rem; color: #334155; text-transform: uppercase; margin-bottom: 6px;">Unidad de Medida *</label>
                            <select name="unidad_medida" id="editProdUnidad" class="input-modern" style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.95rem; box-sizing: border-box; background: #fff;">
                                <option value="UND">Unidad (UND)</option>
                                <option value="KG">Kilogramo (KG)</option>
                            </select>
                        </div>
                        <div class="grupo">
                            <label class="label" style="display: block; font-weight: 700; font-size: 0.78rem; color: #334155; text-transform: uppercase; margin-bottom: 6px;">Stock Actual *</label>
                            <input type="number" step="0.01" name="stock" id="editProdStock" class="input-modern" required placeholder="Ej. 25" style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.95rem; box-sizing: border-box;">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 16px;">
                        <div class="grupo">
                            <label class="label" style="display: block; font-weight: 700; font-size: 0.78rem; color: #334155; text-transform: uppercase; margin-bottom: 6px;">Stock Mínimo de Alerta</label>
                            <input type="number" step="0.01" name="stock_minimo" id="editProdStockMinimo" class="input-modern" placeholder="Ej. 10" style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.95rem; box-sizing: border-box;">
                        </div>
                        <div class="grupo">
                            <label class="label" style="display: block; font-weight: 700; font-size: 0.78rem; color: #334155; text-transform: uppercase; margin-bottom: 6px;">Ubicación Física en Tienda</label>
                            <input type="text" name="ubicacion" id="editProdUbicacion" class="input-modern" placeholder="Ej. Pasillo 1 - Estante B" style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.95rem; box-sizing: border-box;">
                        </div>
                    </div>

                    <!-- 3 SELECTORES CASCADA EDIT: Macrocategoría -> Categoría -> Subcategoría -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; margin-bottom: 16px;">
                        <div class="grupo">
                            <label class="label" style="display: block; font-weight: 700; font-size: 0.72rem; color: #334155; text-transform: uppercase; margin-bottom: 6px;">MACROCATEGORÍA (N1)</label>
                            <select id="editProdMomento" class="input-modern" onchange="onEditMomentoChange(this.value)" style="width: 100%; padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.85rem; background: #fff;">
                                <option value="">-- Seleccionar N1 --</option>
                                <?php if (!empty($moments)): ?>
                                    <?php foreach ($moments as $m): ?>
                                        <option value="<?= $m['id_momento']; ?>"><?= htmlspecialchars($m['nombre']); ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="grupo">
                            <label class="label" style="display: block; font-weight: 700; font-size: 0.72rem; color: #334155; text-transform: uppercase; margin-bottom: 6px;">CATEGORÍA (N2)</label>
                            <select id="editProdGrupo" name="id_grupo" class="input-modern" disabled onchange="onEditGrupoChange(this.value)" style="width: 100%; padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.85rem; background: #fff;">
                                <option value="">-- Seleccione N1 primero --</option>
                                <?php if (!empty($grupos)): ?>
                                    <?php foreach ($grupos as $g): ?>
                                        <option value="<?= $g['id_grupo']; ?>" data-momento="<?= $g['id_momento']; ?>"><?= htmlspecialchars($g['nombre']); ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="grupo">
                            <label class="label" style="display: block; font-weight: 700; font-size: 0.72rem; color: #334155; text-transform: uppercase; margin-bottom: 6px;">SUBCATEGORÍA (N3) *</label>
                            <select name="id_categoria" id="editProdCategoria" disabled class="input-modern" style="width: 100%; padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.85rem; background: #fff;">
                                <option value="">-- Seleccione N2 primero --</option>
                                <?php if (!empty($categorias)): ?>
                                    <?php foreach ($categorias as $cat): ?>
                                        <option value="<?= $cat['id_categoria']; ?>" data-grupo="<?= $cat['id_grupo']; ?>" data-momento="<?= $cat['id_momento']; ?>"><?= htmlspecialchars($cat['nombre']); ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Hasta 4 Imágenes Edit -->
                    <div class="grupo" style="margin-bottom: 16px;">
                        <label class="label" style="display: block; font-weight: 700; font-size: 0.78rem; color: #334155; text-transform: uppercase; margin-bottom: 8px;">IMÁGENES DEL PRODUCTO (HASTA 4 IMÁGENES)</label>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <div style="border: 1.5px dashed #cbd5e1; border-radius: 10px; padding: 10px; background: #f8fafc; text-align: center;">
                                <span style="display:block; font-size: 0.72rem; font-weight: 800; color: #15803d; text-transform: uppercase; margin-bottom: 4px;">1. Imagen Principal</span>
                                <label for="editProdFileInput" style="display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; background: #15803d; color: #fff; font-size: 0.75rem; font-weight: 700; border-radius: 6px; cursor: pointer;">
                                    <span>Cambiar Imagen</span>
                                    <input type="file" id="editProdFileInput" name="imagen" accept="image/*" style="display: none;" onchange="previewImgModal(this, 'editProdPreviewImg', 'editProdFileName')">
                                </label>
                                <span id="editProdFileName" style="display:block; font-size: 0.72rem; color: #64748b; margin-top: 4px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">Principal</span>
                                <img src="" id="editProdPreviewImg" alt="Preview" style="max-height: 48px; border-radius: 6px; margin: 4px auto 0 auto; border: 1px solid #cbd5e1;" onerror="this.onerror=null;this.src='Images/logo.png';">
                                <input type="text" name="imagen_url" id="editProdImagenUrl" placeholder="o URL directo..." style="width: 100%; margin-top: 6px; padding: 4px 8px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.78rem; box-sizing: border-box;" oninput="document.getElementById('editProdPreviewImg').src = this.value;">
                            </div>

                            <div style="border: 1.5px dashed #cbd5e1; border-radius: 10px; padding: 10px; background: #f8fafc; text-align: center;">
                                <span style="display:block; font-size: 0.72rem; font-weight: 800; color: #475569; text-transform: uppercase; margin-bottom: 4px;">2. Imagen 2 (Galería)</span>
                                <label for="editFile2" style="display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; background: #475569; color: #fff; font-size: 0.75rem; font-weight: 700; border-radius: 6px; cursor: pointer;">
                                    <span>Cambiar Imagen</span>
                                    <input type="file" id="editFile2" name="imagen_2" accept="image/*" style="display: none;" onchange="previewImgModal(this, 'prevEdit2', 'lblEdit2')">
                                </label>
                                <span id="lblEdit2" style="display:block; font-size: 0.72rem; color: #64748b; margin-top: 4px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">Galería 2</span>
                                <img src="" id="prevEdit2" style="max-height: 48px; border-radius: 6px; margin: 4px auto 0 auto; border: 1px solid #cbd5e1; display:none;">
                                <input type="text" name="imagen_url_2" id="editProdImagenUrl2" placeholder="o URL directo..." style="width: 100%; margin-top: 6px; padding: 4px 8px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.78rem; box-sizing: border-box;" oninput="document.getElementById('prevEdit2').src = this.value; document.getElementById('prevEdit2').style.display = 'block';">
                            </div>

                            <div style="border: 1.5px dashed #cbd5e1; border-radius: 10px; padding: 10px; background: #f8fafc; text-align: center;">
                                <span style="display:block; font-size: 0.72rem; font-weight: 800; color: #475569; text-transform: uppercase; margin-bottom: 4px;">3. Imagen 3 (Galería)</span>
                                <label for="editFile3" style="display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; background: #475569; color: #fff; font-size: 0.75rem; font-weight: 700; border-radius: 6px; cursor: pointer;">
                                    <span>Cambiar Imagen</span>
                                    <input type="file" id="editFile3" name="imagen_3" accept="image/*" style="display: none;" onchange="previewImgModal(this, 'prevEdit3', 'lblEdit3')">
                                </label>
                                <span id="lblEdit3" style="display:block; font-size: 0.72rem; color: #64748b; margin-top: 4px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">Galería 3</span>
                                <img src="" id="prevEdit3" style="max-height: 48px; border-radius: 6px; margin: 4px auto 0 auto; border: 1px solid #cbd5e1; display:none;">
                                <input type="text" name="imagen_url_3" id="editProdImagenUrl3" placeholder="o URL directo..." style="width: 100%; margin-top: 6px; padding: 4px 8px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.78rem; box-sizing: border-box;" oninput="document.getElementById('prevEdit3').src = this.value; document.getElementById('prevEdit3').style.display = 'block';">
                            </div>

                            <div style="border: 1.5px dashed #cbd5e1; border-radius: 10px; padding: 10px; background: #f8fafc; text-align: center;">
                                <span style="display:block; font-size: 0.72rem; font-weight: 800; color: #475569; text-transform: uppercase; margin-bottom: 4px;">4. Imagen 4 (Galería)</span>
                                <label for="editFile4" style="display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; background: #475569; color: #fff; font-size: 0.75rem; font-weight: 700; border-radius: 6px; cursor: pointer;">
                                    <span>Cambiar Imagen</span>
                                    <input type="file" id="editFile4" name="imagen_4" accept="image/*" style="display: none;" onchange="previewImgModal(this, 'prevEdit4', 'lblEdit4')">
                                </label>
                                <span id="lblEdit4" style="display:block; font-size: 0.72rem; color: #64748b; margin-top: 4px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">Galería 4</span>
                                <img src="" id="prevEdit4" style="max-height: 48px; border-radius: 6px; margin: 4px auto 0 auto; border: 1px solid #cbd5e1; display:none;">
                                <input type="text" name="imagen_url_4" id="editProdImagenUrl4" placeholder="o URL directo..." style="width: 100%; margin-top: 6px; padding: 4px 8px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.78rem; box-sizing: border-box;" oninput="document.getElementById('prevEdit4').src = this.value; document.getElementById('prevEdit4').style.display = 'block';">
                            </div>
                        </div>
                    </div>

                    <!-- Descripción -->
                    <div class="grupo" style="margin-bottom: 20px;">
                        <label class="label" style="display: block; font-weight: 700; font-size: 0.78rem; color: #334155; text-transform: uppercase; margin-bottom: 6px;">Descripción del Producto</label>
                        <textarea name="descripcion" id="editProdDescripcion" rows="3" class="input-modern" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.88rem; box-sizing: border-box; resize: vertical;" placeholder="Descripción o detalles del producto..."></textarea>
                    </div>

                    <!-- Sugerencia Edit -->
                    <div style="margin-bottom: 20px;">
                        <label style="display: inline-flex; align-items: center; gap: 8px; font-size: 0.88rem; font-weight: 600; color: #334155; cursor: pointer;">
                            <input type="checkbox" name="es_sugerencia" id="editProdEsSugerencia" value="1" style="accent-color: #15803d; width: 16px; height: 16px;">
                            <span>Destacar como producto sugerido / oferta especial</span>
                        </label>
                    </div>

                    <div class="modal-footer" style="display: flex; justify-content: flex-end; gap: 10px;">
                        <button type="button" class="btn btn-secondary" onclick="cerrarModal('modalEditarProducto')">Cancelar</button>
                        <button type="submit" class="btn btn-primary" style="background: #15803d; font-weight: 700;">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Confirmar Eliminar -->
    <div class="modal-overlay" id="modalConfirmarEliminarProd" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); z-index: 12000; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div style="background: #ffffff; width: 100%; max-width: 400px; border-radius: 16px; padding: 26px; text-align: center; box-shadow: 0 20px 40px rgba(0,0,0,0.2); margin: 16px;">
            <div style="width: 56px; height: 56px; border-radius: 50%; background: #fee2e2; color: #dc2626; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px auto;">
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
            </div>
            <h3 style="margin: 0 0 8px 0; color: #0f172a; font-size: 1.2rem; font-weight: 800;">¿Eliminar producto?</h3>
            <p id="textoEliminarProd" style="color: #64748b; font-size: 0.92rem; margin: 0 0 22px 0;">Esta acción no se puede deshacer.</p>
            <div style="display: flex; gap: 10px; justify-content: center;">
                <button type="button" onclick="cerrarModal('modalConfirmarEliminarProd')" style="padding: 10px 20px; border-radius: 8px; border: 1px solid #cbd5e1; background: #f8fafc; font-weight: 700; cursor: pointer; color: #475569;">Cancelar</button>
                <a id="btnEliminarProdLink" href="#" style="padding: 10px 22px; border-radius: 8px; background: #dc2626; color: #ffffff; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center;">Sí, Eliminar</a>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="modalConfirmarEstadoProd" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); z-index: 12000; align-items: center; justify-content: center; backdrop-filter: blur(4px);" onclick="if (event.target === this) cerrarModal('modalConfirmarEstadoProd')">
        <div style="background:#fff;width:100%;max-width:400px;border-radius:16px;padding:26px;text-align:center;box-shadow:0 20px 40px rgba(0,0,0,.2);margin:16px;">
            <div id="iconoConfirmarEstadoProd" style="width:56px;height:56px;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;"></div>
            <h3 id="tituloConfirmarEstadoProd" style="margin:0 0 8px;color:#0f172a;font-size:1.2rem;font-weight:800;">Confirmar cambio de estado</h3>
            <p id="textoConfirmarEstadoProd" style="color:#64748b;font-size:.92rem;margin:0 0 22px;"></p>
            <div style="display:flex;gap:10px;justify-content:center;">
                <button type="button" onclick="cerrarModal('modalConfirmarEstadoProd')" style="padding:10px 20px;border-radius:8px;border:1px solid #cbd5e1;background:#f8fafc;font-weight:700;cursor:pointer;color:#475569;">Cancelar</button>
                <a id="btnConfirmarEstadoProd" href="#" style="padding:10px 22px;border-radius:8px;color:#fff;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;">Confirmar</a>
            </div>
        </div>
    </div>

    <script src="Scripts/Vistas/Inventario/inventario.js?v=<?= time(); ?>"></script>
    <script src="Scripts/inventario.js?v=<?php echo time(); ?>"></script>
    <!-- Librerías de escáner cámara y celular siempre incluidas en el contenedor SPA -->
    <script src="Scripts/scanner-camara.js"></script>

<?php if (!AjaxHelper::isAjax()): ?>
            </div> 
        </div> 
    </div> 
</body>
</html>
<?php endif; ?>
