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
$stats        = $stats ?? ($estadisticas ?? [
    'total_proveedores' => count($proveedores),
    'activos'           => 0,
    'inactivos'         => 0,
    'total_compras'     => count($historial),
    'monto_comprado'    => 0.00
]);
?>
<?php if (!AjaxHelper::isAjax()): ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Proveedores & Compras - ERP La Caserita</title>
    <link rel="icon" type="image/png" href="Images/logo.png">
    <link rel="stylesheet" href="Styles/global.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="Styles/styles-home.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="Styles/styles-users-admin.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="Styles/admin-spa.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="Scripts/admin-spa.js?v=<?= time(); ?>" defer></script>
    <!-- Lector remoto desde el celular vinculado -->
    <script src="Scripts/scanner-camara.js"></script>
<link rel="stylesheet" href="Styles/Vistas/Admin/proveedores_admin.css?v=<?= filemtime(__DIR__ . '/../../Styles/Vistas/Admin/proveedores_admin.css'); ?>">
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
                        <span class="brand-title">Gestión de Proveedores & Compras</span>
                    </div>
                </div>
                <div class="admin-topbar-actions">
                    <?php include __DIR__ . '/partials/admin_topbar_actions.php'; ?>
                </div>
            </header>

            <div id="admin-container">
<?php endif; ?>



<main class="proveedores-view-wrapper">
    <!-- Header Principal -->
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px; margin-bottom:22px;">
        <div>
            <h1 style="font-size:1.55rem; font-weight:800; color:#0f172a; margin:0 0 4px 0; display:flex; align-items:center; gap:10px;">
                <i class="fa-solid fa-handshake" style="color:#15803D;"></i>
                Directorio de Proveedores
            </h1>
            <p style="color:#64748b; font-size:0.90rem; margin:0;">Catálogo maestro de empresas proveedoras, datos fiscales (RUC), contactos y acuerdos comerciales.</p>
        </div>
        <div style="display:flex; gap:10px; align-items:center;" class="prov-header-actions">
            <button type="button" onclick="abrirModalProveedor()" class="btn btn-primary" style="background:#15803D; color:#fff; border:none; padding:10px 18px; border-radius:10px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:8px; box-shadow:0 4px 12px rgba(21,128,61,0.22); transition:all 0.2s ease;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                <span>Nuevo Proveedor</span>
            </button>
            <a href="index.php?action=compras_admin" class="btn btn-secondary" style="background:#0284c7; color:#fff; text-decoration:none; padding:10px 18px; border-radius:10px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:8px; box-shadow:0 4px 12px rgba(2,132,199,0.22); transition:all 0.2s ease;">
                <svg width="18" height="18" viewBox="0 0 640 512" fill="currentColor"><path d="M256 48c0-26.5 21.5-48 48-48L592 0c26.5 0 48 21.5 48 48l0 416c0 26.5-21.5 48-48 48l-210.7 0c1.8-5 2.7-10.4 2.7-16l0-242.7c18.6-6.6 32-24.4 32-45.3l0-32c0-26.5-21.5-48-48-48l-112 0 0-80zM571.3 347.3c6.2-6.2 6.2-16.4 0-22.6l-64-64c-6.2-6.2-16.4-6.2-22.6 0l-64 64c-6.2 6.2-6.2 16.4 0 22.6s16.4 6.2 22.6 0L480 310.6 480 432c0 8.8 7.2 16 16 16s16-7.2 16-16l0-121.4 36.7 36.7c6.2 6.2 16.4 6.2 22.6 0zM0 176c0-8.8 7.2-16 16-16l352 0c8.8 0 16 7.2 16 16l0 32c0 8.8-7.2 16-16 16L16 224c-8.8 0-16-7.2-16-16l0-32zm352 80l0 224c0 17.7-14.3 32-32 32L64 512c-17.7 0-32-14.3-32-32l0-224 320 0zM144 320c-8.8 0-16 7.2-16 16s7.2 16 16 16l96 0c8.8 0 16-7.2 16-16s-7.2-16-16-16l-96 0z"/></svg>
                <span>Ir a Compras & Reposición</span>
            </a>
        </div>
    </div>

    <?php if ($flash_success || $flash_error): 
        $esError = !empty($flash_error);
        $msgFlash = $flash_error ?: $flash_success;
    ?>
        <script>
            (function() {
                const tipo = <?= json_encode($esError ? 'error' : 'success'); ?>;
                const mensaje = <?= json_encode($msgFlash, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
                function lanzar() {
                    if (typeof window.mostrarAvisoToast === 'function') {
                        window.mostrarAvisoToast(tipo, tipo === 'error' ? 'Error' : 'Éxito', mensaje);
                    } else if (typeof SPA !== 'undefined' && typeof SPA.toast === 'function') {
                        SPA.toast(tipo, tipo === 'error' ? 'Error' : 'Éxito', mensaje, 4000);
                    }
                }
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', lanzar, { once: true });
                } else {
                    lanzar();
                }
            })();
        </script>
    <?php endif; ?>

    <!-- TARJETAS CUADRADAS COMPACTAS -->
    <div class="prov-stats-grid-square">
        <div class="prov-stat-card-square active-stat" id="cardTotal" onclick="filtrarPorEstado('todos')">
            <span class="prov-stat-title">TOTAL PROVEEDORES</span>
            <span class="prov-stat-number"><?= (int)($stats['total_proveedores'] ?? count($proveedores)) ?></span>
        </div>
        <div class="prov-stat-card-square" id="cardActivos" onclick="filtrarPorEstado('activos')">
            <span class="prov-stat-title">PROVEEDORES ACTIVOS</span>
            <span class="prov-stat-number"><?= (int)($stats['activos'] ?? 0) ?></span>
        </div>
        <div class="prov-stat-card-square" id="cardInactivos" onclick="filtrarPorEstado('inactivos')">
            <span class="prov-stat-title">PROVEEDORES INACTIVOS</span>
            <span class="prov-stat-number"><?= (int)($stats['inactivos'] ?? 0) ?></span>
        </div>
        <div class="prov-stat-card-square" onclick="window.location.href='index.php?action=compras_admin'">
            <span class="prov-stat-title">HISTORIAL DE COMPRAS</span>
            <span class="prov-stat-number"><?= (int)($stats['total_compras'] ?? count($historial)) ?></span>
        </div>
        <div class="prov-stat-card-square" onclick="window.location.href='index.php?action=compras_admin'">
            <span class="prov-stat-title">MONTO TOTAL COMPRADO</span>
            <span class="prov-stat-number" style="font-size: 1.15rem; color:#15803d;">S/ <?= number_format((float)($stats['monto_comprado'] ?? 0), 2) ?></span>
        </div>
    </div>

    <!-- SECCIÓN DIRECTORIO PROVEEDORES -->
    <div id="seccionProveedores" class="prov-main-card">
        <div class="prov-card-header">
            <div class="prov-card-title">Listado de Proveedores Registrados</div>
            <div class="prov-controls">
                <div class="prov-search-wrapper">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="text" id="busquedaProveedores" class="prov-search-input" placeholder="Buscar por RUC, Empresa, Contacto..." oninput="filtrarProveedoresEnVivo()">
                </div>
                <select id="filtroEstadoSelect" class="prov-filter-select" onchange="filtrarPorEstado(this.value)">
                    <option value="todos">Todos los Estados</option>
                    <option value="activos">Solo Activos</option>
                    <option value="inactivos">Solo Inactivos</option>
                </select>
            </div>
        </div>

        <div class="prov-table-container">
            <table class="prov-table" id="tablaProveedores">
                <thead>
                    <tr>
                        <th style="width: 120px; white-space: nowrap;">RUC / DOC</th>
                        <th style="min-width: 180px;">EMPRESA / RAZÓN SOCIAL</th>
                        <th style="min-width: 130px;">CONTACTO</th>
                        <th style="width: 110px; white-space: nowrap;">TELÉFONO</th>
                        <th style="min-width: 140px;">CORREO</th>
                        <th style="width: 90px; white-space: nowrap;">ESTADO</th>
                        <th style="width: 70px; text-align: center; white-space: nowrap;">COMPRAS</th>
                        <th style="width: 90px; text-align: right; white-space: nowrap;">MONTO</th>
                        <th style="width: 130px; text-align: right; white-space: nowrap;">ACCIONES</th>
                    </tr>
                </thead>
                <tbody id="tbodyProveedores">
                    <!-- JS renders 5 rows per page -->
                </tbody>
            </table>
        </div>

        <!-- Barra Paginación Centrada estilo Inventario / Kardex -->
        <div class="prov-pagination-bar" id="paginacionProveedoresBar">
            <div class="prov-pag-nav" id="paginacionProveedores"></div>
        </div>
    </div>

    <!-- SECCIÓN HISTORIAL DE COMPRAS -->
    <div id="seccionHistorial" class="prov-main-card" style="display: none;">
        <div class="prov-card-header">
            <div class="prov-card-title">Historial Detallado de Compras Realizadas</div>
            <div class="prov-controls">
                <div class="prov-search-wrapper">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="text" id="busquedaHistorial" class="prov-search-input" placeholder="Buscar por comprobante, proveedor..." oninput="filtrarHistorialEnVivo()">
                </div>
            </div>
        </div>

        <div class="prov-table-container">
            <table class="prov-table" id="tablaHistorial">
                <thead>
                    <tr>
                        <th>FECHA / HORA</th>
                        <th>COMPROBANTE</th>
                        <th>PROVEEDOR</th>
                        <th>TOTAL COMPRA</th>
                        <th>ESTADO</th>
                        <th>REGISTRADO POR</th>
                    </tr>
                </thead>
                <tbody id="tbodyHistorial">
                    <!-- JS renders 5 rows per page -->
                </tbody>
            </table>
        </div>

        <!-- Barra Paginación Centrada estilo Inventario / Kardex -->
        <div class="prov-pagination-bar" id="paginacionHistorialBar">
            <div class="prov-pag-nav" id="paginacionHistorial"></div>
        </div>
    </div>
</main>

<!-- MODAL: NUEVO / EDITAR PROVEEDOR -->
<div class="prov-modal-overlay" id="modalProveedor">
    <div class="prov-modal-card">
        <div class="prov-modal-header">
            <h2 id="modalProvTitle">Nuevo Proveedor</h2>
            <button type="button" class="prov-modal-close" onclick="cerrarModal('modalProveedor')">&times;</button>
        </div>
        <form action="index.php?action=guardar_proveedor" method="POST" id="formProveedor">
            <input type="hidden" name="id_proveedor" id="prov_id" value="">
            <div class="prov-modal-body">
                <div class="prov-form-grid">
                    <div>
                        <label class="prov-label required">RUC / Documento</label>
                        <input type="text" name="ruc" id="prov_ruc" class="prov-input" placeholder="Ej: 20601234567" required maxlength="20">
                    </div>
                    <div>
                        <label class="prov-label required">Razón Social / Empresa</label>
                        <input type="text" name="razon_social" id="prov_razon" class="prov-input" placeholder="Ej: Distribuidora Alimentos S.A.C." required>
                    </div>
                    <div>
                        <label class="prov-label">Persona de Contacto</label>
                        <input type="text" name="contacto_nombre" id="prov_contacto" class="prov-input" placeholder="Ej: Lic. Carlos Pérez">
                    </div>
                    <div>
                        <label class="prov-label">Teléfono / Celular</label>
                        <input type="text" name="telefono" id="prov_telefono" class="prov-input" placeholder="Ej: 987654321">
                    </div>
                    <div class="prov-col-full">
                        <label class="prov-label">Correo Electrónico</label>
                        <input type="email" name="correo" id="prov_correo" class="prov-input" placeholder="contacto@empresa.com">
                    </div>
                    <div class="prov-col-full">
                        <label class="prov-label">Dirección Fiscal</label>
                        <input type="text" name="direccion" id="prov_direccion" class="prov-input" placeholder="Av. Los Laureles 450, Lima">
                    </div>
                </div>
            </div>
            <div class="prov-modal-footer">
                <button type="button" class="btn" style="background:#f1f5f9; color:#475569; padding:9px 18px; border-radius:8px; border:none; cursor:pointer;" onclick="cerrarModal('modalProveedor')">Cancelar</button>
                <button type="submit" class="btn btn-primary" id="btnGuardarProv" style="background:#15803D; color:#fff; padding:9px 20px; border-radius:8px; border:none; font-weight:700; cursor:pointer;">Guardar Proveedor</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: REGISTRAR COMPRA / REPOSICIÓN (Con scroll fluido y actualización de precio de venta) -->
<div class="prov-modal-overlay" id="modalCompra">
    <div class="prov-modal-card" style="max-width: 720px;">
        <div class="prov-modal-header">
            <h2>Registrar Compra / Reposición</h2>
            <button type="button" class="prov-modal-close" onclick="cerrarModal('modalCompra')">&times;</button>
        </div>
        <form action="index.php?action=guardar_compra" method="POST" id="formCompra">
            <div class="prov-modal-body">
                <div>
                    <label class="prov-label">Tipo de Ingreso</label>
                    <div class="type-toggle-bar">
                        <button type="button" class="type-toggle-btn active" id="btnTipoExistente" onclick="cambiarTipoIngreso('existente')">
                            Producto del Catálogo (Reposición)
                        </button>
                        <button type="button" class="type-toggle-btn" id="btnTipoNuevo" onclick="cambiarTipoIngreso('nuevo')">
                            Nuevo Producto (Crear & Comprar)
                        </button>
                    </div>
                    <input type="hidden" name="tipo_ingreso" id="tipoIngresoInput" value="existente">
                </div>

                <div class="prov-form-grid">
                    <div class="prov-col-full">
                        <label class="prov-label required">Proveedor</label>
                        <select name="id_proveedor" id="compra_proveedor" class="prov-select" required>
                            <option value="">-- Seleccionar Proveedor --</option>
                            <?php foreach ($proveedores as $prov): ?>
                                <option value="<?= htmlspecialchars($prov['id_proveedor']) ?>">
                                    <?= htmlspecialchars($prov['razon_social']) ?> (RUC: <?= htmlspecialchars($prov['ruc']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Buscador con SKU visible en vivo en todas las búsquedas -->
                    <div class="prov-col-full" id="boxProductoExistente">
                        <label class="prov-label required">Producto a Reponer (Buscar por Nombre, SKU o Código de Barras)</label>
                        <div class="prod-search-wrapper" style="position: relative; width: 100%;">
                            <input type="text" id="buscadorProductoCompra" class="prov-input" style="padding-right: 42px;" placeholder="Escribe el nombre, SKU o escanea el código..." autocomplete="off" oninput="buscarProductoEnModal(this.value)" onfocus="buscarProductoEnModal(this.value)">
                            <button type="button" onclick="abrirCamScannerCompraProveedor()" class="btn-inline-input-scanner" title="Usar celular vinculado para escanear" aria-label="Escanear con celular vinculado">
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
                            <div class="prod-dropdown" id="dropdownProductosCompra"></div>
                        </div>
                        <div id="productoSeleccionadoInfo" style="display: none; margin-top: 8px; padding: 8px 12px; background: #f0fdf4; border: 1.5px solid #bbf7d0; border-radius: 8px; font-size: 0.85rem; color: #166534;">
                            <strong>Seleccionado:</strong> <span id="lblProductoSeleccionado"></span>
                        </div>
                    </div>

                    <!-- Campos Nuevo Producto -->
                    <div class="prov-col-full" id="boxProductoNuevo" style="display: none;">
                        <div style="background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 10px; padding: 14px;">
                            <div style="font-size: 0.84rem; font-weight: 700; color: #15803d; margin-bottom: 10px;">Datos del Nuevo Producto:</div>
                            <div class="prov-form-grid">
                                <div>
                                    <label class="prov-label required">Nombre del Producto</label>
                                    <input type="text" name="nuevo_nombre" id="nuevo_nombre" class="prov-input" placeholder="Ej: Arroz Extra 5kg">
                                </div>
                                <div>
                                    <label class="prov-label">SKU / Código</label>
                                    <input type="text" name="nuevo_sku" id="nuevo_sku" class="prov-input" placeholder="Auto-generado si vacío">
                                </div>
                                <div>
                                    <label class="prov-label">Código de Barras (EAN/UPC)</label>
                                    <div style="position: relative; width: 100%;">
                                        <input type="text" name="nuevo_codigo_barras" id="nuevo_codigo_barras" class="prov-input" style="padding-right: 42px;" placeholder="Ej: 7751234567890">
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
                                    <label class="prov-label required">Categoría</label>
                                    <select name="nuevo_categoria" id="nuevo_categoria" class="prov-select">
                                        <option value="">-- Seleccionar Categoría --</option>
                                        <?php foreach ($categorias as $cat): ?>
                                            <option value="<?= htmlspecialchars($cat['id_categoria']) ?>"><?= htmlspecialchars($cat['nombre']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="prov-label required">Precio Venta (S/)</label>
                                    <input type="number" step="0.01" name="nuevo_precio_venta" id="nuevo_precio_venta" class="prov-input" placeholder="0.00">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="prov-label">Tipo de Comprobante</label>
                        <select name="tipo_comprobante" class="prov-select">
                            <option value="Factura">Factura</option>
                            <option value="Boleta">Boleta de Venta</option>
                            <option value="Guia Remision">Guía de Remisión</option>
                            <option value="Nota de Entrada">Nota de Entrada</option>
                        </select>
                    </div>
                    <div>
                        <label class="prov-label required">N° Comprobante / Guía</label>
                        <input type="text" name="numero_comprobante" class="prov-input" placeholder="Ej: F001-000492" required>
                    </div>

                    <div>
                        <label class="prov-label required">Cantidad a Ingresar</label>
                        <input type="number" step="1" min="1" name="cantidad" id="compra_cantidad" class="prov-input" placeholder="0" required oninput="calcularTotalCompra()">
                    </div>
                    <div>
                        <label class="prov-label required">Costo Unitario Compra (S/)</label>
                        <input type="number" step="0.01" min="0" name="precio_unitario" id="compra_costo" class="prov-input" placeholder="0.00" required oninput="calcularTotalCompra()">
                    </div>

                    <!-- PRECIO DE VENTA: Toma el actual y permite editarlo -->
                    <div class="prov-col-full">
                        <div style="background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 10px; padding: 12px 14px;">
                            <label class="prov-label" style="color: #0f172a;">Precio de Venta al Público (S/)</label>
                            <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
                                <input type="number" step="0.01" min="0" name="precio_venta_sugerido" id="compra_precio_venta" class="prov-input" style="max-width: 220px;" placeholder="0.00">
                                <span style="font-size: 0.82rem; color: #64748b;">
                                    Precio actual registrado: <strong>S/ <span id="lblPrecioVentaActual" style="color: #15803d;">0.00</span></strong>. (Si lo modificas, se actualizará como nuevo precio de venta del producto).
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="prov-col-full">
                        <div style="display:flex; justify-content:space-between; align-items:center; background:#f1f5f9; padding:12px 16px; border-radius:10px;">
                            <span style="font-weight:700; color:#475569; font-size:0.90rem;">TOTAL DE LA COMPRA:</span>
                            <span style="font-size:1.35rem; font-weight:800; color:#0f172a;" id="lblTotalCompra">S/ 0.00</span>
                        </div>
                    </div>

                    <!-- Deducción Dinámica de Caja Chica -->
                    <div class="prov-col-full">
                        <label class="prov-label">Método de Pago & Caja</label>
                        <?php if ($cajaAbierta && $saldoCaja > 0): ?>
                            <div class="caja-box has-balance">
                                <div>
                                    <div style="font-weight:700; color:#15803d; font-size:0.88rem;">Caja Chica Abierta con Fondos</div>
                                    <div style="font-size:0.78rem; color:#64748b;">Puedes descontar automáticamente de los ingresos en caja.</div>
                                </div>
                                <div style="text-align:right;">
                                    <span style="font-weight:800; color:#15803d;">Saldo: S/ <?= number_format($saldoCaja, 2) ?></span>
                                    <label style="display:flex; align-items:center; gap:6px; font-weight:700; font-size:0.85rem; color:#0f172a; margin-top:4px; cursor:pointer;">
                                        <input type="checkbox" name="descontar_caja" value="1" checked> Descontar de Caja
                                    </label>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="caja-box">
                                <div>
                                    <div style="font-weight:700; color:#64748b; font-size:0.88rem;">Sin saldo en Caja Chica</div>
                                    <div style="font-size:0.78rem; color:#94a3b8;">La compra se registrará como Pago Directo sin afectar saldo de caja.</div>
                                </div>
                                <div>
                                    <span style="font-weight:700; color:#475569; background:#e2e8f0; padding:4px 10px; border-radius:6px; font-size:0.82rem;">Pagar</span>
                                    <input type="hidden" name="descontar_caja" value="0">
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="prov-col-full">
                        <label class="prov-label">Observaciones</label>
                        <input type="text" name="observaciones" class="prov-input" placeholder="Ej: Reposición quincenal de inventario">
                    </div>
                </div>
            </div>
            <div class="prov-modal-footer">
                <button type="button" class="btn" style="background:#f1f5f9; color:#475569; padding:9px 18px; border-radius:8px; border:none; cursor:pointer;" onclick="cerrarModal('modalCompra')">Cancelar</button>
                <button type="submit" class="btn btn-secondary" style="background:#2563EB; color:#fff; padding:9px 20px; border-radius:8px; border:none; font-weight:700; cursor:pointer;">Procesar & Registrar Entrada</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: CONFIRMACIÓN DE ESTADO (Idéntico a Imagen 2) -->
<div class="prov-modal-overlay" id="modalConfirmacion" onclick="if(event.target===this)cerrarModal('modalConfirmacion')">
    <div class="prov-modal-card" style="max-width: 420px; text-align: center; padding: 26px; border-radius: 20px;">
        <div id="confIconoWrapper" style="width: 56px; height: 56px; margin: 0 auto 16px; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: #fee2e2;">
            <svg id="confIcono" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/>
            </svg>
        </div>
        <h3 id="confTitulo" style="margin: 0 0 8px; font-size: 1.25rem; font-weight: 800; color: #0f172a;">Inactivar proveedor</h3>
        <p id="confMensaje" style="margin: 0 0 24px; color: #64748b; font-size: 0.92rem;">¿Confirmas inactivar este proveedor?</p>
        <div style="display: flex; justify-content: center; gap: 12px;">
            <button type="button" onclick="cerrarModal('modalConfirmacion')" style="padding: 10px 18px; border: 1px solid #cbd5e1; border-radius: 8px; background: #f8fafc; color: #475569; font-weight: 700; cursor: pointer;">Cancelar</button>
            <button type="button" id="confBtnProceder" onclick="ejecutarCambioEstadoProveedor()" style="padding: 10px 22px; border-radius: 8px; color: #ffffff; background: #dc2626; font-weight: 700; border: none; cursor: pointer; display: inline-flex; align-items: center; justify-content: center;">Inactivar</button>
        </div>
    </div>
</div>

<script>
(function() {
    const listaProveedores = <?= json_encode($proveedores) ?>;
    const listaProductos   = <?= json_encode($productos) ?>;
    const listaHistorial   = <?= json_encode($historial) ?>;

    const ITEMS_PER_PAGE = 5;
    let paginaActualProv = 1;
    let paginaActualHist = 1;
    let filtroEstadoProv = 'todos';
    let busquedaProvTexto = '';
    let busquedaHistTexto = '';

    function init() {
        renderizarProveedores();
        renderizarHistorial();

        document.addEventListener('click', (e) => {
            if (!e.target.closest('.prod-search-wrapper')) {
                const drop = document.getElementById('dropdownProductosCompra');
                if (drop) drop.classList.remove('show');
            }
        });
    }

    window.cambiarTab = function(tab) {
        const tabProv = document.getElementById('seccionProveedores');
        const tabHist = document.getElementById('seccionHistorial');
        const btnProv = document.getElementById('tabBtnProveedores');
        const btnHist = document.getElementById('tabBtnHistorial');

        if (tab === 'proveedores') {
            tabProv.style.display = 'block';
            tabHist.style.display = 'none';
            btnProv.classList.add('active');
            btnHist.classList.remove('active');
        } else {
            tabProv.style.display = 'none';
            tabHist.style.display = 'block';
            btnProv.classList.remove('active');
            btnHist.classList.add('active');
        }
    };

    window.filtrarPorEstado = function(estado) {
        window.cambiarTab('proveedores');
        filtroEstadoProv = estado;
        paginaActualProv = 1;

        document.querySelectorAll('.prov-stat-card-square').forEach(c => c.classList.remove('active-stat'));
        if (estado === 'todos') document.getElementById('cardTotal')?.classList.add('active-stat');
        if (estado === 'activos') document.getElementById('cardActivos')?.classList.add('active-stat');
        if (estado === 'inactivos') document.getElementById('cardInactivos')?.classList.add('active-stat');

        const select = document.getElementById('filtroEstadoSelect');
        if (select) select.value = estado;

        renderizarProveedores();
    };

    window.filtrarProveedoresEnVivo = function() {
        busquedaProvTexto = (document.getElementById('busquedaProveedores')?.value || '').toLowerCase().trim();
        paginaActualProv = 1;
        renderizarProveedores();
    };

    function renderizarProveedores() {
        const tbody = document.getElementById('tbodyProveedores');
        const paginacion = document.getElementById('paginacionProveedores');
        if (!tbody) return;

        const filtrados = listaProveedores.filter(p => {
            const esActivo = Number(p.activo) === 1 || (p.estado || '').toLowerCase() === 'activo';
            if (filtroEstadoProv === 'activos' && !esActivo) return false;
            if (filtroEstadoProv === 'inactivos' && esActivo) return false;

            if (busquedaProvTexto !== '') {
                const ruc = (p.ruc || '').toLowerCase();
                const razon = (p.razon_social || '').toLowerCase();
                const contacto = (p.contacto_nombre || '').toLowerCase();
                const correo = (p.correo || '').toLowerCase();
                const tel = (p.telefono || '').toLowerCase();
                if (!ruc.includes(busquedaProvTexto) && !razon.includes(busquedaProvTexto) && !contacto.includes(busquedaProvTexto) && !correo.includes(busquedaProvTexto) && !tel.includes(busquedaProvTexto)) {
                    return false;
                }
            }
            return true;
        });

        const total = filtrados.length;
        const totalPaginas = Math.ceil(total / ITEMS_PER_PAGE) || 1;
        if (paginaActualProv > totalPaginas) paginaActualProv = totalPaginas;

        const inicio = (paginaActualProv - 1) * ITEMS_PER_PAGE;
        const fin = Math.min(inicio + ITEMS_PER_PAGE, total);
        const itemsPagina = filtrados.slice(inicio, fin);

        if (itemsPagina.length === 0) {
            tbody.innerHTML = '<tr><td colspan="9" style="text-align:center; padding:36px; color:#94a3b8;">No se encontraron proveedores coincidentes.</td></tr>';
        } else {
            tbody.innerHTML = itemsPagina.map(p => {
                const esActivo = Number(p.activo) === 1 || (p.estado || '').toLowerCase() === 'activo';
                const comprasCount = parseInt(p.total_compras || 0);
                const montoComp = parseFloat(p.monto_total || 0).toFixed(2);

                return `
                    <tr>
                        <td style="white-space: nowrap;"><span class="badge-ruc">${p.ruc || 'S/N'}</span></td>
                        <td>
                            <div style="font-weight:700; color:#0f172a; line-height: 1.25;">${p.razon_social || ''}</div>
                            <div style="font-size:0.75rem; color:#64748b;">${p.direccion || 'Sin dirección'}</div>
                        </td>
                        <td><div style="font-weight:600; color:#0f172a; font-size:0.85rem;">${p.contacto_nombre || 'No asignado'}</div></td>
                        <td style="white-space: nowrap;"><span style="font-weight:600; color:#334155; font-size:0.85rem;">${p.telefono || 'Sin teléfono'}</span></td>
                        <td><span style="color:#64748b; font-size:0.82rem; word-break: break-all;">${p.correo || 'Sin correo'}</span></td>
                        <td style="white-space: nowrap;">
                            <span class="badge-status ${esActivo ? 'activo' : 'inactivo'}">
                                <span class="badge-status-dot"></span>
                                ${esActivo ? 'Activo' : 'Inactivo'}
                            </span>
                        </td>
                        <td style="text-align: center; white-space: nowrap;"><span style="font-weight:700; color:#0f172a;">${comprasCount}</span></td>
                        <td style="text-align: right; white-space: nowrap;"><span style="font-weight:800; color:#0f172a;">S/ ${montoComp}</span></td>
                        <td style="text-align: right; white-space: nowrap;">
                            <div style="display:inline-flex; gap:6px; justify-content: flex-end; align-items: center;">
                                <a href="index.php?action=compras_admin" class="btn-action-icon primary" style="background:#0284c7; width:36px; height:36px; min-width:36px; min-height:36px; padding:0; margin:0; box-sizing:border-box; border-radius:10px; display:inline-flex; align-items:center; justify-content:center; text-decoration:none;" title="Ir a Compras y Reposiciones">
                                    <svg width="17" height="17" viewBox="0 0 640 512" fill="#ffffff"><path d="M256 48c0-26.5 21.5-48 48-48L592 0c26.5 0 48 21.5 48 48l0 416c0 26.5-21.5 48-48 48l-210.7 0c1.8-5 2.7-10.4 2.7-16l0-242.7c18.6-6.6 32-24.4 32-45.3l0-32c0-26.5-21.5-48-48-48l-112 0 0-80zM571.3 347.3c6.2-6.2 6.2-16.4 0-22.6l-64-64c-6.2-6.2-16.4-6.2-22.6 0l-64 64c-6.2 6.2-6.2 16.4 0 22.6s16.4 6.2 22.6 0L480 310.6 480 432c0 8.8 7.2 16 16 16s16-7.2 16-16l0-121.4 36.7 36.7c6.2 6.2 16.4 6.2 22.6 0zM0 176c0-8.8 7.2-16 16-16l352 0c8.8 0 16 7.2 16 16l0 32c0 8.8-7.2 16-16 16L16 224c-8.8 0-16-7.2-16-16l0-32zm352 80l0 224c0 17.7-14.3 32-32 32L64 512c-17.7 0-32-14.3-32-32l0-224 320 0zM144 320c-8.8 0-16 7.2-16 16s7.2 16 16 16l96 0c8.8 0 16-7.2 16-16s-7.2-16-16-16l-96 0z"/></svg>
                                </a>
                                <button type="button" class="btn-action-icon" style="background:#15803d; width:36px; height:36px; min-width:36px; min-height:36px; padding:0; margin:0; box-sizing:border-box; border-radius:10px; display:inline-flex; align-items:center; justify-content:center;" title="Editar" onclick='abrirModalEditarProveedor(${JSON.stringify(p)})'>
                                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                                </button>
                                <button type="button" class="btn-action-icon ${esActivo ? 'danger' : 'success'}" style="width:36px; height:36px; min-width:36px; min-height:36px; padding:0; margin:0; box-sizing:border-box; border-radius:10px; display:inline-flex; align-items:center; justify-content:center;" title="${esActivo ? 'Inactivar' : 'Activar'}" onclick="confirmarCambioEstado(${p.id_proveedor}, '${esActivo ? 'inactivar' : 'activar'}', '${p.razon_social}')">
                                    ${esActivo ? 
                                        '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>' :
                                        '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="16 9 10 15 8 13"/></svg>'
                                    }
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        renderPaginadorCentrado(paginacion, totalPaginas, paginaActualProv, (p) => {
            paginaActualProv = p;
            renderizarProveedores();
            document.getElementById('seccionProveedores')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    }

    window.filtrarHistorialEnVivo = function() {
        busquedaHistTexto = (document.getElementById('busquedaHistorial')?.value || '').toLowerCase().trim();
        paginaActualHist = 1;
        renderizarHistorial();
    };

    function renderizarHistorial() {
        const tbody = document.getElementById('tbodyHistorial');
        const paginacion = document.getElementById('paginacionHistorial');
        if (!tbody) return;

        const filtrados = listaHistorial.filter(h => {
            if (busquedaHistTexto !== '') {
                const nro = (h.numero_comprobante || '').toLowerCase();
                const tipo = (h.tipo_comprobante || '').toLowerCase();
                const prov = (h.proveedor_nombre || '').toLowerCase();
                const usu = (h.usuario_nombre || '').toLowerCase();
                if (!nro.includes(busquedaHistTexto) && !tipo.includes(busquedaHistTexto) && !prov.includes(busquedaHistTexto) && !usu.includes(busquedaHistTexto)) {
                    return false;
                }
            }
            return true;
        });

        const total = filtrados.length;
        const totalPaginas = Math.ceil(total / ITEMS_PER_PAGE) || 1;
        if (paginaActualHist > totalPaginas) paginaActualHist = totalPaginas;

        const inicio = (paginaActualHist - 1) * ITEMS_PER_PAGE;
        const fin = Math.min(inicio + ITEMS_PER_PAGE, total);
        const itemsPagina = filtrados.slice(inicio, fin);

        if (itemsPagina.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:36px; color:#94a3b8;">No hay compras registradas en este período.</td></tr>';
        } else {
            tbody.innerHTML = itemsPagina.map(h => {
                const fecha = h.fecha_compra || 'Reciente';
                const totalC = parseFloat(h.total || 0).toFixed(2);
                return `
                    <tr>
                        <td><div style="font-weight:700; color:#0f172a;">${fecha}</div></td>
                        <td><span style="font-weight:700; color:#2563eb; font-family:monospace;">${h.tipo_comprobante || 'Compra'} - ${h.numero_comprobante || 'S/N'}</span></td>
                        <td><div style="font-weight:700; color:#0f172a;">${h.proveedor_nombre || 'Proveedor Desconocido'}</div></td>
                        <td><span style="font-size:0.95rem; font-weight:800; color:#15803d;">S/ ${totalC}</span></td>
                        <td><span class="badge-status activo"><span class="badge-status-dot"></span>${h.estado || 'Procesada'}</span></td>
                        <td><span style="color:#64748b; font-size:0.82rem;">${h.usuario_nombre || 'Administrador'}</span></td>
                    </tr>
                `;
            }).join('');
        }

        renderPaginadorCentrado(paginacion, totalPaginas, paginaActualHist, (p) => {
            paginaActualHist = p;
            renderizarHistorial();
            document.getElementById('seccionHistorial')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    }

    // GENERADOR DE PAGINACIÓN CENTRADA EXACTAMENTE IGUAL A INVENTARIO / KARDEX
    function renderPaginadorCentrado(container, totalPaginas, actual, onPageClick) {
        if (!container) return;
        container.innerHTML = '';
        if (totalPaginas <= 1) return;

        // Flecha <
        const prevBtn = document.createElement('button');
        prevBtn.type = 'button';
        prevBtn.className = 'prov-pag-btn';
        prevBtn.innerHTML = '&lsaquo;';
        prevBtn.disabled = (actual === 1);
        prevBtn.onclick = () => onPageClick(actual - 1);
        container.appendChild(prevBtn);

        for (let p = 1; p <= totalPaginas; p++) {
            if (totalPaginas > 7 && p > 2 && p < totalPaginas - 1 && Math.abs(p - actual) > 1) {
                if (p === 3 || p === totalPaginas - 2) {
                    const dots = document.createElement('span');
                    dots.textContent = '...';
                    dots.style.cssText = 'color:#94a3b8; padding:0 4px; font-weight:700; font-size:0.9rem;';
                    container.appendChild(dots);
                }
                continue;
            }

            const pageBtn = document.createElement('button');
            pageBtn.type = 'button';
            pageBtn.className = 'prov-pag-btn' + (p === actual ? ' active' : '');
            pageBtn.textContent = p;
            if (p !== actual) {
                pageBtn.onclick = () => onPageClick(p);
            }
            container.appendChild(pageBtn);
        }

        // Flecha >
        const nextBtn = document.createElement('button');
        nextBtn.type = 'button';
        nextBtn.className = 'prov-pag-btn';
        nextBtn.innerHTML = '&rsaquo;';
        nextBtn.disabled = (actual >= totalPaginas);
        nextBtn.onclick = () => onPageClick(actual + 1);
        container.appendChild(nextBtn);
    }

    // MODAL CONTROL
    window.abrirModal = function(id) {
        const m = document.getElementById(id);
        if (m) {
            m.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
    };

    window.cerrarModal = function(id) {
        const m = document.getElementById(id);
        if (m) {
            m.style.display = 'none';
            document.body.style.overflow = '';
        }
    };

    window.abrirModalProveedor = function() {
        document.getElementById('modalProvTitle').textContent = 'Nuevo Proveedor';
        document.getElementById('formProveedor').reset();
        document.getElementById('prov_id').value = '';
        document.getElementById('btnGuardarProv').textContent = 'Guardar Proveedor';
        window.abrirModal('modalProveedor');
    };

    window.abrirModalEditarProveedor = function(p) {
        document.getElementById('modalProvTitle').textContent = 'Editar Proveedor';
        document.getElementById('prov_id').value = p.id_proveedor || '';
        document.getElementById('prov_ruc').value = p.ruc || '';
        document.getElementById('prov_razon').value = p.razon_social || '';
        document.getElementById('prov_contacto').value = p.contacto_nombre || '';
        document.getElementById('prov_telefono').value = p.telefono || '';
        document.getElementById('prov_correo').value = p.correo || '';
        document.getElementById('prov_direccion').value = p.direccion || '';
        document.getElementById('btnGuardarProv').textContent = 'Actualizar Proveedor';
        window.abrirModal('modalProveedor');
    };

    window.abrirModalCompra = function() {
        document.getElementById('formCompra').reset();
        document.getElementById('compra_id_producto').value = '';
        document.getElementById('productoSeleccionadoInfo').style.display = 'none';
        document.getElementById('lblTotalCompra').textContent = 'S/ 0.00';
        document.getElementById('lblPrecioVentaActual').textContent = '0.00';
        document.getElementById('compra_precio_venta').value = '';
        window.cambiarTipoIngreso('existente');
        window.abrirModal('modalCompra');
    };

    window.abrirModalCompraConProveedor = function(idProveedor) {
        window.abrirModalCompra();
        const sel = document.getElementById('compra_proveedor');
        if (sel && idProveedor) {
            sel.value = String(idProveedor);
        }
    };

    // Búsqueda en vivo de productos con SKU
    window.buscarProductoEnModal = function(query) {
        const drop = document.getElementById('dropdownProductosCompra');
        const q = (query || '').toLowerCase().trim();

        const filtrados = listaProductos.filter(p => {
            const nom = (p.nombre || p.titulo || '').toLowerCase();
            const sku = (p.codigo || p.sku || p.codigo_barras || ('PROD-' + String(p.id_producto).padStart(4, '0'))).toLowerCase();
            return nom.includes(q) || sku.includes(q);
        });

        if (filtrados.length === 0) {
            drop.innerHTML = '<div style="padding:10px; font-size:0.84rem; color:#94a3b8; text-align:center;">No hay productos con ese nombre o SKU</div>';
        } else {
            drop.innerHTML = filtrados.slice(0, 15).map(p => {
                const skuFormatted = p.codigo || p.sku || ('PROD-' + String(p.id_producto).padStart(4, '0'));
                const nom = p.nombre || p.titulo || '';
                return `
                    <div class="prod-dropdown-item" onclick='seleccionarProductoCompra(${JSON.stringify(p)})'>
                        <div>
                            <div style="font-weight:700; color:#0f172a; font-size:0.88rem;">${nom}</div>
                            <div class="prod-badge-sku">SKU: ${skuFormatted}</div>
                        </div>
                        <div style="font-size:0.78rem; font-weight:700; color:#64748b; background:#f1f5f9; padding:2px 8px; border-radius:6px;">
                            Stock: ${p.stock || p.stock_actual || 0} unds
                        </div>
                    </div>
                `;
            }).join('');
        }
        drop.classList.add('show');
    };

    window.seleccionarProductoCompra = function(prod) {
        document.getElementById('compra_id_producto').value = prod.id_producto;
        const skuFormatted = prod.codigo || prod.sku || ('PROD-' + String(prod.id_producto).padStart(4, '0'));
        const nom = prod.nombre || prod.titulo || '';
        document.getElementById('buscadorProductoCompra').value = `${nom} (${skuFormatted}${prod.codigo_barras ? ' | Cód: ' + prod.codigo_barras : ''})`;

        document.getElementById('lblProductoSeleccionado').textContent = `${nom} | SKU: ${skuFormatted} | Código: ${prod.codigo_barras || 'S/C'} | Stock: ${prod.stock || prod.stock_actual || 0} unds`;
        document.getElementById('productoSeleccionadoInfo').style.display = 'block';

        // Prellenar costo unitario si existe
        if (prod.precio_costo && parseFloat(prod.precio_costo) > 0) {
            document.getElementById('compra_costo').value = parseFloat(prod.precio_costo).toFixed(2);
        } else if (prod.costo_unitario && parseFloat(prod.costo_unitario) > 0) {
            document.getElementById('compra_costo').value = parseFloat(prod.costo_unitario).toFixed(2);
        }

        // Prellenar precio de venta actual y habilitar edición
        const precioVenta = parseFloat(prod.precio || 0);
        document.getElementById('lblPrecioVentaActual').textContent = precioVenta.toFixed(2);
        document.getElementById('compra_precio_venta').value = precioVenta > 0 ? precioVenta.toFixed(2) : '';

        document.getElementById('dropdownProductosCompra').classList.remove('show');
        window.calcularTotalCompra();
    };

    window.abrirCamScannerCompraProveedor = function() {
        const doOpen = function() {
            if (typeof UniversalCameraScanner !== 'undefined' && typeof UniversalCameraScanner.open === 'function') {
                UniversalCameraScanner.open({
                    title: 'Registrar Compra — Escanear Código de Producto',
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
                            setTimeout(() => {
                                const deseaCrear = confirm(`No se encontró ningún producto con el código de barras "${codigo}".\n\n¿Deseas registrar un nuevo producto con este código?`);
                                if (deseaCrear) {
                                    cambiarTipoIngreso('nuevo');
                                    const inputCB = document.getElementById('nuevo_codigo_barras');
                                    const inputSKU = document.getElementById('nuevo_sku');
                                    if (inputCB) inputCB.value = codigo;
                                    if (inputSKU && !inputSKU.value) inputSKU.value = codigo;
                                    document.getElementById('nuevo_nombre')?.focus();
                                }
                            }, 350);
                        }
                    }
                });
            }
        };

        if (window.ensureCameraScanner) {
            window.ensureCameraScanner(doOpen);
        } else {
            doOpen();
        }
    };

    window.cambiarTipoIngreso = function(tipo) {
        const btnEx = document.getElementById('btnTipoExistente');
        const btnNu = document.getElementById('btnTipoNuevo');
        const boxEx = document.getElementById('boxProductoExistente');
        const boxNu = document.getElementById('boxProductoNuevo');
        const inputT = document.getElementById('tipoIngresoInput');

        if (tipo === 'existente') {
            btnEx?.classList.add('active');
            btnNu?.classList.remove('active');
            if (boxEx) boxEx.style.display = 'block';
            if (boxNu) boxNu.style.display = 'none';
            if (inputT) inputT.value = 'existente';
            document.getElementById('nuevo_nombre').required = false;
            document.getElementById('nuevo_categoria').required = false;
        } else {
            btnNu?.classList.add('active');
            btnEx?.classList.remove('active');
            if (boxEx) boxEx.style.display = 'none';
            if (boxNu) boxNu.style.display = 'block';
            if (inputT) inputT.value = 'nuevo';
            document.getElementById('nuevo_nombre').required = true;
            document.getElementById('nuevo_categoria').required = true;
        }
    };

    window.calcularTotalCompra = function() {
        const cant = parseFloat(document.getElementById('compra_cantidad')?.value || 0);
        const costo = parseFloat(document.getElementById('compra_costo')?.value || 0);
        const total = cant * costo;
        const lbl = document.getElementById('lblTotalCompra');
        if (lbl) lbl.textContent = 'S/ ' + total.toFixed(2);
    };

    let proveedorEstadoPendiente = null;

    window.mostrarAvisoToast = function(tipo, titulo, mensaje) {
        if (typeof SPA !== 'undefined' && typeof SPA.toast === 'function') {
            if (typeof SPA.createToastContainer === 'function') SPA.createToastContainer();
            SPA.toast(tipo, titulo, mensaje, 4000);
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
        toast.style.cssText = 'position:fixed;right:20px;top:20px;z-index:99999;display:flex;align-items:center;gap:12px;width:min(420px,calc(100vw - 32px));padding:14px 18px;background:#fff;border-radius:14px;box-shadow:0 12px 32px rgba(15,23,42,.2);border:1px solid ' + (tipo === 'error' ? '#fecaca' : '#bbf7d0') + ';color:#0f172a;';
        const icon = document.createElement('span');
        icon.className = 'toast-icon';
        icon.innerHTML = tipo === 'error'
            ? '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>'
            : '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#15803d" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><path d="m9 11 3 3L22 4"></path></svg>';
        const content = document.createElement('div');
        content.style.cssText = 'flex:1;';
        const t = document.createElement('div');
        t.style.cssText = 'font-weight:700;font-size:0.95rem;color:#0f172a;margin-bottom:2px;';
        t.textContent = titulo;
        const desc = document.createElement('div');
        desc.style.cssText = 'font-size:0.86rem;color:#475569;';
        desc.textContent = mensaje;
        content.append(t, desc);
        const close = document.createElement('button');
        close.type = 'button';
        close.style.cssText = 'background:none;border:none;cursor:pointer;color:#94a3b8;padding:4px;display:flex;';
        close.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>';
        toast.append(icon, content, close);
        container.appendChild(toast);
        const removeFn = function() {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(-10px)';
            toast.style.transition = 'all 0.3s ease';
            setTimeout(function() { toast.remove(); }, 300);
        };
        close.addEventListener('click', removeFn);
        setTimeout(removeFn, 4500);
    };

    window.confirmarCambioEstado = function(id, accion, razon) {
        const esInactivar = accion === 'inactivar';
        const nuevoEstado = esInactivar ? 0 : 1;
        proveedorEstadoPendiente = { id: id, estado: nuevoEstado, nuevo_estado: esInactivar ? 'Inactivo' : 'Activo', razon: razon };

        const iconWrapper = document.getElementById('confIconoWrapper');
        const icon = document.getElementById('confIcono');
        const btn = document.getElementById('confBtnProceder');
        const titulo = document.getElementById('confTitulo');
        const mensaje = document.getElementById('confMensaje');

        if (esInactivar) {
            if (iconWrapper) iconWrapper.style.background = '#fee2e2';
            if (icon) {
                icon.setAttribute('stroke', '#dc2626');
                icon.innerHTML = '<circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/>';
            }
            if (titulo) titulo.textContent = 'Inactivar proveedor';
            if (mensaje) mensaje.textContent = `¿Confirmas inactivar "${razon}"?`;
            if (btn) {
                btn.textContent = 'Inactivar';
                btn.style.background = '#dc2626';
            }
        } else {
            if (iconWrapper) iconWrapper.style.background = '#dcfce7';
            if (icon) {
                icon.setAttribute('stroke', '#15803d');
                icon.innerHTML = '<circle cx="12" cy="12" r="10"/><polyline points="16 9 10 15 8 13"/>';
            }
            if (titulo) titulo.textContent = 'Activar proveedor';
            if (mensaje) mensaje.textContent = `¿Confirmas activar "${razon}"?`;
            if (btn) {
                btn.textContent = 'Activar';
                btn.style.background = '#15803d';
            }
        }
        window.abrirModal('modalConfirmacion');
    };

    window.ejecutarCambioEstadoProveedor = function() {
        if (!proveedorEstadoPendiente) return;
        const { id, estado, nuevo_estado, razon } = proveedorEstadoPendiente;
        const btn = document.getElementById('confBtnProceder');
        if (btn) { btn.disabled = true; btn.textContent = 'Procesando...'; }

        fetch(`index.php?action=toggle_proveedor&id=${id}&estado=${estado}&nuevo_estado=${nuevo_estado}`, {
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
            window.cerrarModal('modalConfirmacion');
            if (data.success) {
                const prov = listaProveedores.find(item => Number(item.id_proveedor) === Number(id));
                if (prov) {
                    prov.activo = estado;
                    prov.estado = (estado === 1 ? 'Activo' : 'Inactivo');
                }
                renderizarProveedores();
                window.mostrarAvisoToast('success', 'Éxito', data.message || `Proveedor "${razon}" actualizado.`);
            } else {
                window.mostrarAvisoToast('error', 'Error', data.message || 'No se pudo actualizar el estado.');
            }
        })
        .catch(() => {
            window.location.href = `index.php?action=toggle_proveedor&id=${id}&estado=${estado}&nuevo_estado=${nuevo_estado}`;
        })
        .finally(() => {
            if (btn) { btn.disabled = false; btn.textContent = estado === 0 ? 'Inactivar' : 'Activar'; }
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>
<!-- Librerías de escáner cámara y celular siempre incluidas en el contenedor SPA -->
<script src="Scripts/scanner-camara.js"></script>

<?php if (!AjaxHelper::isAjax()): ?>
            </div> 
        </div> 
    </div> 
</body>
</html>
<?php endif; ?>
