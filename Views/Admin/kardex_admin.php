<?php
require_once 'Core/ajaxHelper.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$nombre = htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Administrador');
$flash_success = $_SESSION['flash_success'] ?? null;
$flash_error = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);
$idProdFiltro = isset($_GET['id_producto']) ? (int)$_GET['id_producto'] : 0;
?>
<?php if (!AjaxHelper::isAjax()): ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kardex Valorizado & Movimientos - ERP La Caserita</title>
    <link rel="icon" type="image/png" href="Images/logo.png">
    <link rel="stylesheet" href="Styles/global.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="Styles/styles-home.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="Styles/styles-users-admin.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="Styles/admin-spa.css?v=<?= time(); ?>">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="Scripts/admin-spa.js?v=<?= time(); ?>" defer></script>
<link rel="stylesheet" href="Styles/Vistas/Admin/kardex_admin.css?v=<?= filemtime(__DIR__ . '/../../Styles/Vistas/Admin/kardex_admin.css'); ?>">
</head>
<body>
    <div class="admin-layout">
        <?php include __DIR__ . '/partials/admin_sidebar.php'; ?>

        <div class="admin-main">
            <header class="admin-topbar">
                <div class="admin-topbar-left">
                    <button type="button" class="admin-hamburger-btn hide-on-desktop" id="openAdminSidebar" aria-label="Abrir Menú">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
                    </button>
                    <div class="admin-topbar-brand">
                        <span class="brand-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>
                        </span>
                        <span class="brand-title">Kardex Valorizado & Trazabilidad de Stock</span>
                    </div>
                </div>
                <div class="admin-topbar-actions">
                    <?php include __DIR__ . '/partials/admin_topbar_actions.php'; ?>
                </div>
            </header>

            <div id="admin-container">
<?php endif; ?>



<main class="kardex-view-wrapper">
    <!-- Header Principal -->
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px; margin-bottom:22px;">
        <div>
            <h1 style="font-size:1.55rem; font-weight:800; color:#0f172a; margin:0 0 4px 0;">Kardex Valorizado & Trazabilidad</h1>
            <p style="color:#64748b; font-size:0.90rem; margin:0;">Registro contable cronológico de compras (entradas), ventas mostrador/web (salidas) y ajustes físicos.</p>
        </div>
        <div>
            <button type="button" onclick="window.abrirModalAjuste()" class="btn btn-primary" style="background:#15803D; color:#fff; border:none; padding:10px 18px; border-radius:10px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:8px; box-shadow:0 4px 12px rgba(21,128,61,0.22); transition:all 0.2s ease;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                <span>Ajustar Stock Manual</span>
            </button>
        </div>
    </div>

    <!-- Banner de Notificación Flash -->
    <?php if ($flash_success || $flash_error): 
        $esError = !empty($flash_error);
        $msgFlash = $flash_error ?: $flash_success;
    ?>
        <div id="kardexAlertaBanner" style="display:flex; align-items:center; justify-content:space-between; padding:14px 18px; border-radius:12px; margin-bottom:20px; font-weight:700; font-size:0.94rem; background:<?= $esError ? '#fee2e2' : '#dcfce7'; ?>; color:<?= $esError ? '#dc2626' : '#15803d'; ?>; border:1.5px solid <?= $esError ? '#fecaca' : '#bbf7d0'; ?>; box-shadow:0 4px 12px rgba(0,0,0,0.04);">
            <div style="display:flex; align-items:center; gap:10px;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <?= $esError ? '<circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line>' : '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><path d="m9 11 3 3L22 4"></path>'; ?>
                </svg>
                <span><?= htmlspecialchars($msgFlash); ?></span>
            </div>
            <button type="button" onclick="document.getElementById('kardexAlertaBanner')?.remove()" style="background:none; border:none; cursor:pointer; color:inherit; font-weight:800; font-size:1.3rem; padding:0 6px; line-height:1;">&times;</button>
        </div>
    <?php endif; ?>

    <!-- 1. Tarjetas Métricas Cuadradas Compactas con Redirección -->
    <div class="kardex-stats-grid-square">
        <div class="kardex-stat-card-square" onclick="window.location.href='index.php?action=inventario'" title="Ir a Inventario de Productos">
            <span class="kardex-stat-title">STOCK FÍSICO TOTAL</span>
            <span class="kardex-stat-val"><?= number_format($valorizacion['total_unidades'] ?? 0); ?> <span style="font-size:0.75rem; color:#64748b; font-weight:600;">unds</span></span>
            <span class="kardex-stat-sub">En almacén activo</span>
        </div>

        <div class="kardex-stat-card-square" onclick="window.location.href='index.php?action=compras_admin'" title="Ir a Compras & Reposición">
            <span class="kardex-stat-title">VALORIZACIÓN COSTO</span>
            <span class="kardex-stat-val" style="color:#0284c7;">S/ <?= number_format($valorizacion['valor_costo_total'] ?? 0, 2); ?></span>
            <span class="kardex-stat-sub">Capital invertido</span>
        </div>

        <div class="kardex-stat-card-square" onclick="window.location.href='index.php?action=ventas_admin'" title="Ir a Ventas & Facturas">
            <span class="kardex-stat-title">VALORIZACIÓN COMERCIAL</span>
            <span class="kardex-stat-val" style="color:#7c3aed;">S/ <?= number_format($valorizacion['valor_venta_total'] ?? 0, 2); ?></span>
            <span class="kardex-stat-sub">Precio en venta</span>
        </div>

        <div class="kardex-stat-card-square" onclick="window.location.href='index.php?action=dashboard_ganancias'" title="Ir a Dashboard Financiero">
            <span class="kardex-stat-title">UTILIDAD PROYECTADA</span>
            <span class="kardex-stat-val" style="color:#15803d;">S/ <?= number_format($valorizacion['ganancia_potencial'] ?? 0, 2); ?></span>
            <span class="kardex-stat-sub">Margen esperado</span>
        </div>

        <div class="kardex-stat-card-square" onclick="window.location.href='index.php?action=compras_admin'" title="Ir a Compras & Reposición (Entradas)">
            <span class="kardex-stat-title">ENTRADAS / COMPRAS</span>
            <span class="kardex-stat-val" style="color:#15803d;" id="statTotalEntradas">-</span>
            <span class="kardex-stat-sub">Ingresos registrados</span>
        </div>

        <div class="kardex-stat-card-square" onclick="window.location.href='index.php?action=ventas_admin'" title="Ir a Ventas & Facturas (Salidas)">
            <span class="kardex-stat-title">SALIDAS / VENTAS</span>
            <span class="kardex-stat-val" style="color:#dc2626;" id="statTotalSalidas">-</span>
            <span class="kardex-stat-sub">Ventas y mermas</span>
        </div>
    </div>

    <!-- 2. Barra de Búsqueda Instantánea & Filtros (Organizada & Responsiva) -->
    <div class="kardex-controls-bar">
        <div class="kardex-controls-main">
            <!-- Buscador en tiempo real -->
            <div class="kardex-search-box">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input type="text" id="kardexLiveSearch" class="kardex-search-input" placeholder="Buscar por producto, SKU, motivo, responsable o tipo..." autocomplete="off">
                <button type="button" id="kardexClearSearch" class="kardex-clear-search" title="Limpiar búsqueda">&times;</button>
            </div>

            <!-- Selector de Ordenamiento y Tipo integrado junto al buscador -->
            <div class="kardex-order-group">
                <span class="kardex-control-label">Ordenar:</span>
                <select id="kardexOrdenSelect" class="kardex-select-order">
                    <option value="reciente">Más recientes primero</option>
                    <option value="antiguo">Más antiguos primero</option>
                    <option value="tipo_ALL">Todos los movimientos</option>
                    <option value="tipo_ENTRADA">Entradas (+)</option>
                    <option value="tipo_SALIDA">Salidas (-)</option>
                    <option value="tipo_AJUSTE">Ajustes</option>
                    <option value="cant_desc">Mayor cantidad (±)</option>
                    <option value="cant_asc">Menor cantidad (±)</option>
                </select>
            </div>
        </div>

        <!-- Filtro por Rango de Fechas -->
        <div class="kardex-filters-row">
            <div class="kardex-date-group">
                <span class="kardex-control-label">Desde:</span>
                <input type="date" id="kardexFechaDesde" class="kardex-date-input" value="<?= htmlspecialchars($_GET['fecha_desde'] ?? ''); ?>">
                <span class="kardex-control-label">Hasta:</span>
                <input type="date" id="kardexFechaHasta" class="kardex-date-input" value="<?= htmlspecialchars($_GET['fecha_hasta'] ?? ''); ?>">
                <button type="button" id="btnLimpiarFechas" class="kardex-btn-clear-date" title="Limpiar fechas">Limpiar</button>
            </div>
        </div>
    </div>

    <!-- 3. Tabla de Movimientos del Kardex -->
    <div class="kardex-table-card" id="kardexTableCard">
        <div class="kardex-table-header">
            <h2 class="kardex-table-title">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#15803d" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                <span>Historial de Movimientos de Entradas y Salidas</span>
            </h2>
            <div class="kardex-table-count" id="kardexTotalContador">
                Mostrando <?= count($movimientos); ?> registros
            </div>
        </div>

        <div class="kardex-table-container">
            <table class="kardex-table" id="tablaKardex">
                <thead>
                    <tr>
                        <th style="width: 120px; white-space: nowrap;">FECHA / HORA</th>
                        <th style="min-width: 160px;">PRODUCTO</th>
                        <th style="width: 85px; text-align: center; white-space: nowrap;">TIPO</th>
                        <th style="min-width: 140px; max-width: 220px;">MOTIVO / REFERENCIA</th>
                        <th style="width: 60px; text-align: center; white-space: nowrap;">CANT.</th>
                        <th style="width: 85px; text-align: right; white-space: nowrap;">COSTO UNIT.</th>
                        <th style="width: 85px; text-align: right; white-space: nowrap;">P. VENTA</th>
                        <th style="width: 75px; text-align: center; white-space: nowrap;">STOCK ANT.</th>
                        <th style="width: 75px; text-align: center; white-space: nowrap;">STOCK ACT.</th>
                        <th style="width: 100px; white-space: nowrap;">RESPONSABLE</th>
                    </tr>
                </thead>
                <tbody id="kardexTbody">
                    <?php if (!empty($movimientos)): ?>
                        <?php foreach ($movimientos as $m): 
                            $tipo = strtoupper($m['tipo_movimiento']);
                            $badgeClass = 'ajuste';
                            $signo = '';
                            if ($tipo === 'ENTRADA') {
                                $badgeClass = 'entrada';
                                $signo = '+';
                            } elseif ($tipo === 'SALIDA') {
                                $badgeClass = 'salida';
                                $signo = '-';
                            }
                            $fechaTs = strtotime($m['fecha']);
                            $fechaFormato = date('d/m/Y H:i', $fechaTs);
                            $fechaIso = date('Y-m-d', $fechaTs);
                        ?>
                            <tr class="kardex-row" 
                                data-id="<?= $m['id_kardex']; ?>"
                                data-producto="<?= htmlspecialchars(strtolower($m['producto_nombre'] . ' ' . ($m['codigo'] ?? ''))); ?>"
                                data-tipo="<?= $tipo; ?>"
                                data-motivo="<?= htmlspecialchars(strtolower($m['motivo'])); ?>"
                                data-responsable="<?= htmlspecialchars(strtolower($m['usuario_nombre'] . ' ' . ($m['usuario_apellido'] ?? ''))); ?>"
                                data-fecha="<?= $fechaIso; ?>"
                                data-timestamp="<?= $fechaTs; ?>"
                                data-cantidad="<?= (int)$m['cantidad']; ?>">
                                <td style="white-space:nowrap; color:#64748b; font-weight:600; font-size:0.82rem;">
                                    <?= $fechaFormato; ?>
                                </td>
                                <td>
                                    <strong style="color:#0f172a; font-size:0.88rem; line-height:1.25; display:block;"><?= htmlspecialchars($m['producto_nombre']); ?></strong>
                                    <div style="font-size:0.75rem; color:#64748b; font-weight:600;">SKU: <?= htmlspecialchars($m['codigo'] ?? '-'); ?></div>
                                </td>
                                <td style="text-align:center; white-space:nowrap;">
                                    <span class="kardex-badge <?= $badgeClass; ?>"><?= $tipo; ?></span>
                                </td>
                                <td style="color:#334155; font-size:0.84rem; max-width:220px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="<?= htmlspecialchars($m['motivo']); ?>">
                                    <?= htmlspecialchars($m['motivo']); ?>
                                </td>
                                <td style="text-align:center; white-space:nowrap; font-weight:800; font-size:0.92rem; color:<?= $tipo === 'ENTRADA' ? '#15803d' : ($tipo === 'SALIDA' ? '#dc2626' : '#0369a1'); ?>;">
                                    <?= $signo . $m['cantidad']; ?>
                                </td>
                                <td style="text-align:right; white-space:nowrap; color:#64748b; font-weight:600;">S/ <?= number_format($m['costo_unitario'], 2); ?></td>
                                <td style="text-align:right; white-space:nowrap; font-weight:700; color:#0f172a;">S/ <?= number_format($m['precio_unitario'], 2); ?></td>
                                <td style="text-align:center; white-space:nowrap; color:#94a3b8; font-weight:600;"><?= $m['stock_anterior']; ?></td>
                                <td style="text-align:center; white-space:nowrap; font-weight:800; color:#0f172a; font-size:0.92rem;"><?= $m['stock_actual']; ?></td>
                                <td style="font-size:0.84rem; color:#475569; font-weight:600; white-space:nowrap;">
                                    <?= htmlspecialchars($m['usuario_nombre']); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr id="emptyInitialRow">
                            <td colspan="10" style="text-align:center; color:#94a3b8; padding:38px; font-weight:600;">
                                No se registraron movimientos en el kardex para este criterio.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Barra de Paginación de 5 en 5 (Estilo Inventario: botones centrados) -->
        <div class="kardex-pagination-bar" id="kardexPaginationBar">
            <div class="kardex-pag-info" id="kardexPagInfo" style="display:none;">
                Mostrando 1 - 5 de <?= count($movimientos); ?> movimientos
            </div>
            <div class="kardex-pag-nav" id="kardexPagNav">
                <!-- Se genera dinámicamente con JavaScript: < 1 2 3 ... > -->
            </div>
        </div>
    </div>
</main>

<!-- Modal Ajuste Manual y Registro de Movimiento (Imagen 3: Búsqueda en tiempo real sin select estático) -->
<div id="modalAjuste" class="kardex-modal-overlay">
    <div class="kardex-modal-card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:18px;">
            <h3 style="margin:0; font-size:1.25rem; font-weight:800; color:#0f172a;">Movimiento & Ajuste de Stock</h3>
            <button type="button" onclick="window.cerrarModalAjuste()" style="background:none; border:none; font-size:1.5rem; color:#94a3b8; cursor:pointer; line-height:1;">&times;</button>
        </div>

        <form action="index.php?action=ajustar_kardex" method="POST" id="formAjusteKardex">
            <!-- 1. Búsqueda en Tiempo Real de Producto (Sin select estático) -->
            <div style="margin-bottom:16px;">
                <label style="display:block; font-size:0.80rem; font-weight:800; color:#334155; margin-bottom:6px; text-transform:uppercase;">1. Seleccionar Producto</label>
                
                <input type="hidden" name="id_producto" id="modalIdProducto" required>
                <input type="hidden" id="modalStockActualProd" value="0">

                <div class="kardex-prod-search-wrapper">
                    <div style="position:relative; display:flex; align-items:center;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="position:absolute; left:12px; pointer-events:none;"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                        <input id="buscarProductoAjuste" type="search" placeholder="Escribe para buscar producto..." autocomplete="off" style="width:100%; padding:10px 36px 10px 36px; border:1.5px solid #cbd5e1; border-radius:10px; font-size:0.92rem; font-weight:600; color:#0f172a; box-sizing:border-box; outline:none; transition:border-color 0.2s;">
                        <button type="button" id="btnClearSelectedProd" style="position:absolute; right:10px; background:none; border:none; color:#94a3b8; font-size:1.2rem; cursor:pointer; display:none;" title="Cambiar producto">&times;</button>
                    </div>

                    <!-- Lista desplegable dinámica de resultados -->
                    <div id="listaResultadosProductos" class="kardex-prod-results-dropdown">
                        <?php foreach ($productos as $p): ?>
                            <div class="kardex-prod-result-item" 
                                 data-id="<?= $p['id_producto']; ?>" 
                                 data-nombre="<?= htmlspecialchars($p['nombre']); ?>" 
                                 data-sku="<?= htmlspecialchars($p['codigo'] ?? '-'); ?>" 
                                 data-stock="<?= (int)$p['stock']; ?>"
                                 onclick="window.seleccionarProductoModal(<?= $p['id_producto']; ?>, '<?= htmlspecialchars(addslashes($p['nombre'])); ?>', <?= (int)$p['stock']; ?>, '<?= htmlspecialchars(addslashes($p['codigo'] ?? '-')); ?>')">
                                <div>
                                    <div class="kardex-prod-item-name"><?= htmlspecialchars($p['nombre']); ?></div>
                                    <div class="kardex-prod-item-sku">SKU: <?= htmlspecialchars($p['codigo'] ?? '-'); ?></div>
                                </div>
                                <div class="kardex-prod-item-stock">Stock: <?= $p['stock']; ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Tarjeta informativa de producto seleccionado -->
                <div id="selectedProdCard" class="kardex-selected-prod-badge">
                    <div style="display:flex; align-items:center; gap:8px;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#15803d" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><path d="m9 11 3 3L22 4"></path></svg>
                        <span id="selectedProdNameText" style="font-size:0.90rem; font-weight:800; color:#0f172a;">-</span>
                    </div>
                    <span id="selectedProdStockText" style="font-size:0.80rem; font-weight:800; color:#15803d; background:#dcfce7; padding:2px 8px; border-radius:6px;">Stock: 0</span>
                </div>
            </div>

            <!-- 2. Tipo de Movimiento con SVG (Imagen 2) -->
            <div style="margin-bottom:14px;">
                <label style="display:block; font-size:0.80rem; font-weight:800; color:#334155; margin-bottom:6px; text-transform:uppercase;">2. Tipo de Movimiento</label>
                <input type="hidden" name="tipo_operacion" id="modalTipoOperacion" value="entrada">
                <div class="kardex-type-selector">
                    <div class="kardex-type-option selected-entrada" id="optTipoEntrada" onclick="window.seleccionarTipoOperacion('entrada')">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#15803d" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9" fill="#dcfce7"></circle><path d="M12 8v8M8 12h8"></path></svg>
                        <span>Ingreso (+)</span>
                    </div>
                    <div class="kardex-type-option" id="optTipoSalida" onclick="window.seleccionarTipoOperacion('salida')">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9" fill="#fee2e2"></circle><path d="M8 12h8"></path></svg>
                        <span>Salida (-)</span>
                    </div>
                    <div class="kardex-type-option" id="optTipoAjuste" onclick="window.seleccionarTipoOperacion('ajuste')">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#0284c7" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9" fill="#e0f2fe"></circle><path d="M8 10h8M8 14h8"></path></svg>
                        <span>Ajuste Real (=)</span>
                    </div>
                </div>
            </div>

            <!-- 3. Campo de Cantidad / Stock Real Dinámico -->
            <div style="margin-bottom:14px;" id="contenedorCantidadOperacion">
                <label id="lblCantidadOperacion" style="display:block; font-size:0.80rem; font-weight:800; color:#334155; margin-bottom:6px; text-transform:uppercase;">Cantidad a Ingresar</label>
                <input type="number" name="cantidad" id="inputCantidadModal" min="1" required placeholder="Ej: 10" style="width:100%; padding:10px 12px; border:1.5px solid #cbd5e1; border-radius:8px; font-size:0.95rem; font-weight:700; box-sizing:border-box; outline:none;" oninput="window.actualizarPreviewModalAjuste()">
            </div>

            <div style="margin-bottom:14px; display:none;" id="contenedorNuevoStockOperacion">
                <label style="display:block; font-size:0.80rem; font-weight:800; color:#334155; margin-bottom:6px; text-transform:uppercase;">Nuevo Stock Real (Conteo Físico)</label>
                <input type="number" name="nuevo_stock" id="inputNuevoStockModal" min="0" placeholder="Ej: 45" style="width:100%; padding:10px 12px; border:1.5px solid #cbd5e1; border-radius:8px; font-size:0.95rem; font-weight:700; box-sizing:border-box; outline:none;" oninput="window.actualizarPreviewModalAjuste()">
            </div>

            <!-- Preview del Stock Resultante -->
            <div id="previewStockModal" style="background:#f8fafc; border:1.5px solid #e2e8f0; border-radius:10px; padding:12px 16px; margin-bottom:14px; font-size:0.88rem; color:#475569; display:flex; justify-content:space-between; align-items:center;">
                <span>Stock Actual: <strong id="previewStockActual" style="color:#0f172a;">-</strong></span>
                <span style="font-weight:800; color:#94a3b8;">→</span>
                <span>Stock Resultante: <strong id="previewStockResultante" style="color:#15803d; font-size:1.02rem;">-</strong></span>
            </div>

            <!-- 4. Motivo del Movimiento -->
            <div style="margin-bottom:20px;">
                <label style="display:block; font-size:0.80rem; font-weight:800; color:#334155; margin-bottom:6px; text-transform:uppercase;">3. Motivo o Referencia</label>
                <input type="text" name="motivo" id="inputMotivoModal" required placeholder="Ej: Compra adicional / Merma por rotura / Corrección de inventario" style="width:100%; padding:10px 12px; border:1.5px solid #cbd5e1; border-radius:8px; font-size:0.90rem; font-weight:600; box-sizing:border-box; outline:none;">
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" onclick="window.cerrarModalAjuste()" style="padding:10px 18px; background:#f1f5f9; border:1px solid #cbd5e1; border-radius:8px; font-weight:700; cursor:pointer; color:#475569;">Cancelar</button>
                <button type="submit" id="btnGuardarAjusteModal" style="padding:10px 22px; background:#15803D; color:#fff; border:none; border-radius:8px; font-weight:800; cursor:pointer; box-shadow:0 4px 12px rgba(21,128,61,0.25);">Guardar Movimiento</button>
            </div>
        </form>
    </div>
</div>

<script src="Scripts/Admin/kardex_admin.js?v=<?= time(); ?>"></script>

<?php if (!AjaxHelper::isAjax()): ?>
            </div> 
        </div> 
    </div> 
</body>
</html>
<?php endif; ?>
