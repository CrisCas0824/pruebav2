<?php
require_once 'Core/ajaxHelper.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$nombre = htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Administrador');
$flash_success = $_SESSION['flash_success'] ?? ($_SESSION['success'] ?? ($_GET['success'] ?? null));
$flash_error   = $_SESSION['flash_error']   ?? ($_SESSION['error']   ?? ($_GET['error']   ?? null));
unset($_SESSION['flash_success'], $_SESSION['flash_error'], $_SESSION['success'], $_SESSION['error']);

$proveedores  = $proveedores ?? [];
$productos    = $productos ?? [];
$historial    = $historial ?? ($reposiciones ?? []);
$categorias   = $categorias ?? [];
$cajaAbierta  = $cajaAbierta ?? (!empty($sesionCajaActiva));
$saldoCaja    = $saldoCaja ?? 0.0;

// Calcular estadísticas de compras
$totalCompras = count($historial);
$montoTotalCompras = 0.0;
$totalUnidadesIngresadas = 0;
$proveedoresDistintos = [];

foreach ($historial as $h) {
    $montoTotalCompras += ((float)($h['costo_unitario'] ?? 0) * (int)($h['cantidad'] ?? 0));
    $totalUnidadesIngresadas += (int)($h['cantidad'] ?? 0);
    if (!empty($h['id_proveedor'])) {
        $proveedoresDistintos[$h['id_proveedor']] = true;
    }
}
$cantProveedoresConCompras = count($proveedoresDistintos);
?>
<?php if (!AjaxHelper::isAjax()): ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compras & Reposición - ERP La Caserita</title>
    <link rel="icon" type="image/png" href="Images/logo.png">
    <link rel="stylesheet" href="Styles/global.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="Styles/styles-home.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="Styles/styles-users-admin.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="Styles/admin-spa.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="Scripts/admin-spa.js?v=<?= time(); ?>" defer></script>
    <script src="Scripts/scanner-camara.js"></script>
<link rel="stylesheet" href="Styles/Vistas/Admin/compras_admin.css?v=<?= filemtime(__DIR__ . '/../../Styles/Vistas/Admin/compras_admin.css'); ?>">
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
                        <span class="brand-title">Compras & Reposición de Stock</span>
                    </div>
                </div>
                <div class="admin-topbar-actions">
                    <?php include __DIR__ . '/partials/admin_topbar_actions.php'; ?>
                </div>
            </header>

            <div id="admin-container">
<?php endif; ?>



<main class="compras-view-wrapper">
    <!-- Header Principal -->
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px; margin-bottom:20px;">
        <div>
            <h1 style="font-size:1.55rem; font-weight:800; color:#0f172a; margin:0 0 4px 0; display:flex; align-items:center; gap:10px;">
                Compras y Reposición de Stock
            </h1>
            <p style="color:#64748b; font-size:0.90rem; margin:0;">Registro de abastecimiento de mercadería a proveedores con impacto automático en Kardex, inventario y costos.</p>
        </div>
        <div>
            <button type="button" onclick="abrirModalCompra()" class="btn btn-primary" style="background:#15803d; color:#fff; border:none; padding:11px 20px; border-radius:12px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:8px; box-shadow:0 4px 14px rgba(21,128,61,0.25); font-size:0.94rem; transition:all 0.2s ease;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                <span>Registrar Nueva Compra</span>
            </button>
        </div>
    </div>

    <!-- Banner Flash -->
    <?php if ($flash_success || $flash_error): 
        $esError = !empty($flash_error);
        $msgFlash = $flash_error ?: $flash_success;
    ?>
        <div id="comprasAlertaBanner" style="display:flex; align-items:center; justify-content:space-between; padding:14px 18px; border-radius:12px; margin-bottom:18px; font-weight:700; font-size:0.94rem; background:<?= $esError ? '#fee2e2' : '#dcfce7'; ?>; color:<?= $esError ? '#dc2626' : '#15803d'; ?>; border:1.5px solid <?= $esError ? '#fecaca' : '#bbf7d0'; ?>; box-shadow:0 4px 12px rgba(0,0,0,0.04);">
            <div style="display:flex; align-items:center; gap:10px;">
                <i class="fa-solid <?= $esError ? 'fa-circle-exclamation' : 'fa-circle-check'; ?>" style="font-size:1.2rem;"></i>
                <span><?= htmlspecialchars($msgFlash); ?></span>
            </div>
            <button type="button" onclick="document.getElementById('comprasAlertaBanner').remove()" style="background:none; border:none; cursor:pointer; color:inherit; font-size:1.2rem;">&times;</button>
        </div>
    <?php endif; ?>

    <!-- TARJETAS DE MÉTRICAS COMPACTAS -->
    <div class="compras-stats-grid">
        <div class="compras-stat-card" onclick="document.getElementById('tablaCompras')?.scrollIntoView({behavior: 'smooth'})" title="Ver Historial de Compras">
            <span class="compras-stat-title">TOTAL COMPRAS</span>
            <span class="compras-stat-number"><?= $totalCompras ?></span>
        </div>
        <div class="compras-stat-card featured" onclick="window.location.href='index.php?action=kardex_admin'" title="Ir a Kardex Valorizado">
            <span class="compras-stat-title">MONTO TOTAL INVERTIDO</span>
            <span class="compras-stat-number">S/ <?= number_format($montoTotalCompras, 2) ?></span>
        </div>
        <div class="compras-stat-card" onclick="window.location.href='index.php?action=kardex_admin'" title="Ir a Kardex Valorizado">
            <span class="compras-stat-title">UNIDADES INGRESADAS</span>
            <span class="compras-stat-number">+<?= number_format($totalUnidadesIngresadas) ?></span>
        </div>
        <div class="compras-stat-card" onclick="window.location.href='index.php?action=proveedores_admin'" title="Ir a Directorio de Proveedores">
            <span class="compras-stat-title">PROVEEDORES ABASTECEDORES</span>
            <span class="compras-stat-number"><?= $cantProveedoresConCompras ?></span>
        </div>
    </div>

    <!-- TABLA DE HISTORIAL DE COMPRAS -->
    <div class="compras-main-card">
        <div class="compras-card-header">
            <div class="compras-card-title">
                Historial de Compras & Reposiciones de Almacén
            </div>
            <div class="compras-controls">
                <div class="compras-search-wrapper">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="text" id="busquedaCompras" class="compras-search-input" placeholder="Buscar por producto, proveedor, comprobante..." oninput="filtrarComprasEnVivo()">
                </div>
                <select id="filtroProveedorSelect" class="compras-filter-select" onchange="filtrarComprasEnVivo()">
                    <option value="todos">Todos los Proveedores</option>
                    <?php foreach ($proveedores as $pv): ?>
                        <option value="<?= htmlspecialchars(strtolower($pv['razon_social'])) ?>"><?= htmlspecialchars($pv['razon_social']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="compras-table-container">
            <table class="compras-table" id="tablaCompras">
                <thead>
                    <tr>
                        <th>FECHA & HORA</th>
                        <th>PRODUCTO / CÓDIGO</th>
                        <th>PROVEEDOR</th>
                        <th>CANTIDAD</th>
                        <th>COSTO UNIT.</th>
                        <th>TOTAL COMPRA</th>
                        <th>P. VENTA</th>
                        <th>COMPROBANTE</th>
                        <th>RESPONSABLE</th>
                    </tr>
                </thead>
                <tbody id="tbodyCompras">
                    <?php if (empty($historial)): ?>
                        <tr>
                            <td colspan="9" style="text-align:center; padding:36px; color:#64748b;">
                                <i class="fa-solid fa-box-open" style="font-size:2.2rem; color:#cbd5e1; margin-bottom:8px; display:block;"></i>
                                No hay compras ni reposiciones registradas todavía.<br>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($historial as $item): 
                            $cant = (int)$item['cantidad'];
                            $costo = (float)$item['costo_unitario'];
                            $subtotal = round($cant * $costo, 2);
                            $provNombre = $item['proveedor_razon_social'] ?: ($item['proveedor'] ?: 'Proveedor General');
                            $userResp = trim(($item['usuario_nombre'] ?? '') . ' ' . ($item['usuario_apellido'] ?? '')) ?: 'Administrador';
                        ?>
                            <tr class="fila-compra" 
                                data-producto="<?= htmlspecialchars(strtolower($item['producto_nombre'] . ' ' . ($item['codigo'] ?? '') . ' ' . ($item['codigo_barras'] ?? ''))) ?>"
                                data-proveedor="<?= htmlspecialchars(strtolower($provNombre)) ?>"
                                data-comprobante="<?= htmlspecialchars(strtolower($item['nro_comprobante'] ?? '')) ?>">
                                <td style="white-space:nowrap; font-size:0.82rem; color:#64748b;">
                                    <i class="fa-regular fa-calendar-days" style="margin-right:4px;"></i>
                                    <?= date('d/m/Y H:i', strtotime($item['fecha'])) ?>
                                </td>
                                <td>
                                    <strong style="display:block; color:#0f172a; font-size:0.90rem;"><?= htmlspecialchars($item['producto_nombre']) ?></strong>
                                    <small style="color:#64748b;">
                                        Cód: <?= htmlspecialchars($item['codigo_barras'] ?: ($item['codigo'] ?: 'S/C')) ?>
                                    </small>
                                </td>
                                <td>
                                    <span style="font-weight:700; color:#334155;"><?= htmlspecialchars($provNombre) ?></span>
                                </td>
                                <td>
                                    <span class="badge-ingreso">
                                        <i class="fa-solid fa-arrow-up"></i> +<?= $cant ?> un.
                                    </span>
                                </td>
                                <td style="font-weight:700; color:#475569;">
                                    S/ <?= number_format($costo, 2) ?>
                                </td>
                                <td>
                                    <strong style="color:#0284c7; font-size:0.95rem;">S/ <?= number_format($subtotal, 2) ?></strong>
                                </td>
                                <td>
                                    <?php if (!empty($item['precio_venta_sugerido'])): ?>
                                        <span style="color:#15803d; font-weight:700;">S/ <?= number_format((float)$item['precio_venta_sugerido'], 2) ?></span>
                                    <?php else: ?>
                                        <span style="color:#94a3b8;">--</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($item['nro_comprobante'])): ?>
                                        <span style="background:#f1f5f9; padding:3px 8px; border-radius:6px; font-weight:700; font-size:0.78rem; color:#334155;">
                                            <i class="fa-solid fa-file-invoice" style="margin-right:3px;"></i><?= htmlspecialchars($item['nro_comprobante']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color:#cbd5e1; font-size:0.80rem;">Sin comprobante</span>
                                    <?php endif; ?>
                                </td>
                                <td style="font-size:0.82rem; color:#64748b;">
                                    <i class="fa-regular fa-user" style="margin-right:3px;"></i><?= htmlspecialchars($userResp) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="compras-pagination-bar" id="paginacionComprasBar">
            <div style="font-size:0.82rem; color:#64748b;" id="lblTotalComprasMostradas">
                Mostrando <?= count($historial) ?> compras registradas
            </div>
            <div class="compras-pag-nav" id="paginacionComprasNav"></div>
        </div>
    </div>
</main>

<!-- MODAL: REGISTRAR COMPRA / REPOSICIÓN -->
<div class="comp-modal-overlay" id="modalCompra">
    <div class="comp-modal-card">
        <div class="comp-modal-header">
            <h2><i class="fa-solid fa-boxes-stacked" style="margin-right:8px;"></i> Registrar Compra / Reposición</h2>
            <button type="button" class="comp-modal-close" onclick="cerrarModalCompra()">&times;</button>
        </div>
        <form action="index.php?action=guardar_compra" method="POST" id="formCompra">
            <input type="hidden" name="redirect_to" value="compras_admin">
            <div class="comp-modal-body">
                <div>
                    <label class="comp-label">Tipo de Ingreso de Mercadería</label>
                    <div class="type-toggle-bar">
                        <button type="button" class="type-toggle-btn active" id="btnTipoExistente" onclick="cambiarTipoIngreso('existente')">
                            <i class="fa-solid fa-box" style="margin-right:4px;"></i> Producto Existente (Reposición)
                        </button>
                        <button type="button" class="type-toggle-btn" id="btnTipoNuevo" onclick="cambiarTipoIngreso('nuevo')">
                            <i class="fa-solid fa-plus-circle" style="margin-right:4px;"></i> Nuevo Producto (Crear & Comprar)
                        </button>
                    </div>
                    <input type="hidden" name="tipo_ingreso" id="tipoIngresoInput" value="existente">
                </div>

                <div class="comp-form-grid">
                    <div class="comp-col-full">
                        <label class="comp-label required">Proveedor</label>
                        <select name="id_proveedor" id="compra_proveedor" class="comp-select" required>
                            <option value="">-- Seleccionar Proveedor --</option>
                            <?php foreach ($proveedores as $prov): ?>
                                <option value="<?= htmlspecialchars($prov['id_proveedor']) ?>">
                                    <?= htmlspecialchars($prov['razon_social']) ?> (RUC: <?= htmlspecialchars($prov['ruc']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Buscador Producto Existente -->
                    <div class="comp-col-full" id="boxProductoExistente">
                        <label class="comp-label required">Producto a Reponer (Buscar por Nombre, SKU o Código)</label>
                        <div style="position:relative; width:100%;">
                            <input type="text" id="buscadorProductoCompra" class="comp-input" style="padding-right:42px;" placeholder="Escribe el nombre, SKU o escanea el código..." autocomplete="off" oninput="buscarProductoEnModal(this.value)" onfocus="buscarProductoEnModal(this.value)">
                            <button type="button" onclick="abrirCamScannerCompra()" class="btn-inline-input-scanner" title="Escanear con celular o cámara" aria-label="Escanear con cámara">
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
                            <input type="hidden" name="id_producto" id="compra_id_producto" value="">
                            <div class="prod-search-dropdown" id="dropdownProductosCompra"></div>
                        </div>
                        <div id="productoSeleccionadoInfo" style="display:none; margin-top:8px; padding:8px 12px; background:#f0fdf4; border:1.5px solid #bbf7d0; border-radius:8px; font-size:0.85rem; color:#166534;">
                            <strong>Seleccionado:</strong> <span id="lblProductoSeleccionado"></span>
                        </div>
                    </div>

                    <!-- Campos Nuevo Producto -->
                    <div class="comp-col-full" id="boxProductoNuevo" style="display:none;">
                        <div style="background:#f8fafc; border:1.5px solid #e2e8f0; border-radius:10px; padding:12px;">
                            <div style="font-size:0.84rem; font-weight:700; color:#15803d; margin-bottom:10px;">Datos del Nuevo Producto:</div>
                            <div class="comp-form-grid">
                                <div>
                                    <label class="comp-label required">Nombre del Producto</label>
                                    <input type="text" name="nombre_nuevo_producto" id="nuevo_nombre" class="comp-input" placeholder="Ej: Aceite Vegetal 1L">
                                </div>
                                <div>
                                    <label class="comp-label">Código SKU</label>
                                    <input type="text" name="sku_nuevo_producto" id="nuevo_sku" class="comp-input" placeholder="Auto-generado si vacío">
                                </div>
                                <div>
                                    <label class="comp-label">Código de Barras</label>
                                    <div style="position:relative; width:100%;">
                                        <input type="text" name="nuevo_codigo_barras" id="nuevo_codigo_barras" class="comp-input" style="padding-right:42px;" placeholder="Ej: 7751234567890">
                                        <button type="button" onclick="UniversalCameraScanner.open({ targetInputId: 'nuevo_codigo_barras', title: 'Código de Barras (Nuevo Producto)' })" class="btn-inline-input-scanner" title="Usar celular vinculado para escanear" aria-label="Escanear con celular vinculado">
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
                                <div>
                                    <label class="comp-label required">Categoría</label>
                                    <select name="id_categoria_nuevo" id="nuevo_categoria" class="comp-select">
                                        <option value="">-- Seleccionar Categoría --</option>
                                        <?php foreach ($categorias as $cat): ?>
                                            <option value="<?= htmlspecialchars($cat['id_categoria']) ?>"><?= htmlspecialchars($cat['nombre']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Datos Económicos -->
                    <div>
                        <label class="comp-label required">Cantidad a Ingresar</label>
                        <input type="number" name="cantidad" id="compra_cantidad" class="comp-input" min="1" step="1" placeholder="Ej: 24" required oninput="calcularTotalCompra()">
                    </div>
                    <div>
                        <label class="comp-label required">Costo Unitario de Compra (S/)</label>
                        <input type="number" name="costo_unitario" id="compra_costo" class="comp-input" min="0.01" step="0.01" placeholder="Ej: 8.50" required oninput="calcularTotalCompra()">
                    </div>
                    <div>
                        <label class="comp-label">Precio de Venta al Público (S/)</label>
                        <input type="number" name="precio_venta_sugerido" id="compra_precio_venta" class="comp-input" min="0.01" step="0.01" placeholder="Precio sugerido al cliente">
                        <small style="color:#64748b; font-size:0.75rem;">Actual: S/ <span id="lblPrecioVentaActual">0.00</span></small>
                    </div>
                    <div>
                        <label class="comp-label">N° de Factura / Guía / Boleta</label>
                        <input type="text" name="nro_comprobante" id="compra_comprobante" class="comp-input" placeholder="Ej: F001-00045231">
                    </div>

                    <!-- Lote & Vencimiento -->
                    <div>
                        <label class="comp-label">N° de Lote</label>
                        <input type="text" name="lote" id="compra_lote" class="comp-input" placeholder="Ej: LOTE-2026-X">
                    </div>
                    <div>
                        <label class="comp-label">Fecha de Vencimiento</label>
                        <input type="date" name="fecha_vencimiento" id="compra_vencimiento" class="comp-input">
                    </div>

                    <!-- Observaciones -->
                    <div class="comp-col-full">
                        <label class="comp-label">Observaciones / Notas</label>
                        <textarea name="observacion" id="compra_observacion" class="comp-input" rows="2" placeholder="Detalles de entrega, estado del empaque, etc."></textarea>
                    </div>

                    <!-- Total & Caja Chica -->
                    <div class="comp-col-full">
                        <div class="caja-badge-info">
                            <div>
                                <div style="font-size:0.80rem; font-weight:700; color:#64748b;">TOTAL DE ESTA COMPRA:</div>
                                <div style="font-size:1.45rem; font-weight:800; color:#15803d;" id="lblTotalCompraCalculado">S/ 0.00</div>
                            </div>
                            <?php if ($cajaAbierta): ?>
                                <label style="display:flex; align-items:center; gap:8px; font-size:0.85rem; font-weight:700; color:#0f172a; cursor:pointer;">
                                    <input type="checkbox" name="descontar_caja" id="compra_descontar_caja" value="1" style="width:18px; height:18px; accent-color:#15803d;">
                                    <span>Pagar y descontar de Caja Chica (Saldo: S/ <?= number_format($saldoCaja, 2) ?>)</span>
                                </label>
                            <?php else: ?>
                                <small style="color:#94a3b8;"><i class="fa-solid fa-lock"></i> Caja cerrada (No afecta caja chica)</small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="comp-modal-footer">
                <button type="button" class="btn" style="background:#f1f5f9; color:#475569; padding:9px 18px; border-radius:8px; border:none; cursor:pointer; font-weight:600;" onclick="cerrarModalCompra()">Cancelar</button>
                <button type="submit" class="btn btn-primary" id="btnGuardarCompra" style="background:#15803d; color:#fff; padding:9px 20px; border-radius:8px; border:none; font-weight:700; cursor:pointer; box-shadow:0 2px 8px rgba(21,128,61,0.25);">
                    <i class="fa-solid fa-check"></i> Registrar Compra en Kardex
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    const listaProductos = <?= json_encode($productos) ?>;

    function abrirModalCompra() {
        document.getElementById('modalCompra').classList.add('active');
        document.getElementById('formCompra').reset();
        document.getElementById('lblTotalCompraCalculado').textContent = 'S/ 0.00';
        document.getElementById('productoSeleccionadoInfo').style.display = 'none';
        cambiarTipoIngreso('existente');
    }

    function cerrarModalCompra() {
        document.getElementById('modalCompra').classList.remove('active');
    }

    function cambiarTipoIngreso(tipo) {
        document.getElementById('tipoIngresoInput').value = tipo;
        const btnEx = document.getElementById('btnTipoExistente');
        const btnNv = document.getElementById('btnTipoNuevo');
        const boxEx = document.getElementById('boxProductoExistente');
        const boxNv = document.getElementById('boxProductoNuevo');

        if (tipo === 'nuevo') {
            btnNv.classList.add('active');
            btnEx.classList.remove('active');
            boxNv.style.display = 'block';
            boxEx.style.display = 'none';
            document.getElementById('nuevo_nombre').required = true;
            document.getElementById('buscadorProductoCompra').required = false;
        } else {
            btnEx.classList.add('active');
            btnNv.classList.remove('active');
            boxEx.style.display = 'block';
            boxNv.style.display = 'none';
            document.getElementById('nuevo_nombre').required = false;
            document.getElementById('buscadorProductoCompra').required = true;
        }
    }

    function buscarProductoEnModal(q) {
        const dd = document.getElementById('dropdownProductosCompra');
        const val = q.trim().toLowerCase();
        if (val.length === 0) {
            dd.classList.remove('show');
            return;
        }
        const matches = listaProductos.filter(p => {
            const nom = (p.nombre || p.titulo || '').toLowerCase();
            const sku = (p.sku || '').toLowerCase();
            const cb = (p.codigo_barras || p.codigo || '').toLowerCase();
            return nom.includes(val) || sku.includes(val) || cb.includes(val);
        }).slice(0, 10);

        if (matches.length === 0) {
            dd.innerHTML = '<div style="padding:10px; color:#64748b; font-size:0.85rem;">No se encontraron productos coincidentes</div>';
        } else {
            dd.innerHTML = matches.map(p => `
                <div class="prod-dropdown-item" onclick="seleccionarProductoCompra(${JSON.stringify(p).replace(/"/g, '&quot;')})">
                    <div>
                        <strong style="color:#0f172a; font-size:0.88rem;">${p.nombre}</strong>
                        <div style="font-size:0.75rem; color:#64748b;">
                            SKU: <strong style="color:#0284c7;">${p.sku || p.codigo || 'S/SKU'}</strong> &bull; Cód: ${p.codigo_barras || 'S/C'}
                        </div>
                    </div>
                    <div style="text-align:right;">
                        <span style="font-size:0.80rem; font-weight:700; color:#15803d;">Stock: ${p.stock || 0}</span>
                        <div style="font-size:0.75rem; color:#64748b;">P.Venta: S/ ${parseFloat(p.precio || 0).toFixed(2)}</div>
                    </div>
                </div>
            `).join('');
        }
        dd.classList.add('show');
    }

    function seleccionarProductoCompra(prod) {
        document.getElementById('compra_id_producto').value = prod.id_producto;
        const nombre = prod.nombre || prod.titulo || '';
        document.getElementById('buscadorProductoCompra').value = nombre;
        document.getElementById('dropdownProductosCompra').classList.remove('show');

        const info = document.getElementById('productoSeleccionadoInfo');
        const lbl = document.getElementById('lblProductoSeleccionado');
        lbl.textContent = `${nombre} (SKU: ${prod.sku || prod.codigo || 'S/SKU'} | Código: ${prod.codigo_barras || 'S/C'} | Stock actual: ${prod.stock || 0})`;
        info.style.display = 'block';

        const precioVenta = parseFloat(prod.precio || 0);
        document.getElementById('lblPrecioVentaActual').textContent = precioVenta.toFixed(2);
        document.getElementById('compra_precio_venta').value = precioVenta > 0 ? precioVenta.toFixed(2) : '';

        if (prod.precio_costo && parseFloat(prod.precio_costo) > 0) {
            document.getElementById('compra_costo').value = parseFloat(prod.precio_costo).toFixed(2);
        }

        calcularTotalCompra();
    }

    function calcularTotalCompra() {
        const cant = parseFloat(document.getElementById('compra_cantidad').value) || 0;
        const costo = parseFloat(document.getElementById('compra_costo').value) || 0;
        const total = (cant * costo).toFixed(2);
        document.getElementById('lblTotalCompraCalculado').textContent = 'S/ ' + total;
    }

    function abrirCamScannerCompra() {
        if (typeof UniversalCameraScanner !== 'undefined' && typeof UniversalCameraScanner.open === 'function') {
            UniversalCameraScanner.open({
                title: 'Compras — Escanear Código de Producto',
                subtitle: 'El celular vinculado lee el código y lo transmite a esta PC',
                continuous: false,
                onScan: function (codigo) {
                    const codLimpio = codigo.trim().toLowerCase();
                    const encontrado = listaProductos.find(p => {
                        const cb = (p.codigo_barras || '').toLowerCase();
                        const cod = (p.codigo || '').toLowerCase();
                        const sku = (p.sku || '').toLowerCase();
                        return cb === codLimpio || cod === codLimpio || sku === codLimpio;
                    });

                    if (encontrado) {
                        seleccionarProductoCompra(encontrado);
                    } else {
                        document.getElementById('buscadorProductoCompra').value = codigo;
                        buscarProductoEnModal(codigo);
                    }
                }
            });
        }
    }

    // Filtrado en vivo de la tabla de compras
    function filtrarComprasEnVivo() {
        const q = (document.getElementById('busquedaCompras').value || '').trim().toLowerCase();
        const provFiltro = (document.getElementById('filtroProveedorSelect').value || 'todos').toLowerCase();
        const filas = document.querySelectorAll('.fila-compra');
        let contador = 0;

        filas.forEach(f => {
            const prod = f.getAttribute('data-producto') || '';
            const prov = f.getAttribute('data-proveedor') || '';
            const comp = f.getAttribute('data-comprobante') || '';

            const coincideTexto = !q || prod.includes(q) || prov.includes(q) || comp.includes(q);
            const coincideProv = provFiltro === 'todos' || prov.includes(provFiltro);

            if (coincideTexto && coincideProv) {
                f.style.display = '';
                contador++;
            } else {
                f.style.display = 'none';
            }
        });

        const lbl = document.getElementById('lblTotalComprasMostradas');
        if (lbl) {
            lbl.textContent = `Mostrando ${contador} compras registradas`;
        }
    }

    // Paginación de compras (15 por página)
    let paginaActualCompras = 1;
    const comprasPorPagina = 15;

    function renderPaginacionCompras() {
        const filas = Array.from(document.querySelectorAll('.fila-compra')).filter(f => f.style.display !== 'none');
        const total = filas.length;
        const totalPags = Math.ceil(total / comprasPorPagina) || 1;
        const nav = document.getElementById('paginacionComprasNav');
        if (!nav) return;

        if (totalPags <= 1) {
            nav.innerHTML = '';
            filas.forEach(f => f.style.display = '');
            return;
        }

        filas.forEach((f, idx) => {
            const inicio = (paginaActualCompras - 1) * comprasPorPagina;
            const fin = inicio + comprasPorPagina;
            f.style.display = (idx >= inicio && idx < fin) ? '' : 'none';
        });

        let btns = '';
        for (let p = 1; p <= totalPags; p++) {
            btns += `<button type="button" class="compras-pag-btn ${p === paginaActualCompras ? 'active' : ''}" onclick="irPaginaCompras(${p})">${p}</button>`;
        }
        nav.innerHTML = btns;
    }

    function irPaginaCompras(p) {
        paginaActualCompras = p;
        renderPaginacionCompras();
    }

    window.addEventListener('DOMContentLoaded', () => {
        renderPaginacionCompras();
    });
</script>

<?php if (!AjaxHelper::isAjax()): ?>
            </div><!-- /#admin-container -->
        </div><!-- /.admin-main -->
    </div><!-- /.admin-layout -->
</body>
</html>
<?php endif; ?>
