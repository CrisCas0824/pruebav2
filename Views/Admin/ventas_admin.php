<?php
    require_once 'Core/ajaxHelper.php';
    if (session_status() === PHP_SESSION_NONE) { session_start(); }
    if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin') {
        if (AjaxHelper::isAjax()) { AjaxHelper::jsonError('No autorizado.', 403); }
        header('Location: index.php?action=login'); exit();
    }

    $ventas  = isset($ventas) && is_array($ventas) ? $ventas : [];
    $nombre  = htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Admin');

    function normalizarEstadoCod($e) {
        $clean = strtolower(trim(str_replace(['_', '-'], ' ', (string)$e)));
        if (strpos($clean, 'pend') !== false) return 'pendiente';
        if (strpos($clean, 'prep') !== false) return 'en_preparacion';
        if (strpos($clean, 'camin') !== false) return 'en_camino';
        if (strpos($clean, 'recoj') !== false || strpos($clean, 'listo') !== false) return 'listo_recojo';
        if (strpos($clean, 'entreg') !== false) return 'entregado';
        if (strpos($clean, 'cancel') !== false) return 'cancelado';
        return 'pendiente';
    }

    function normalizarEntregaCod($ent) {
        $clean = strtolower(trim((string)$ent));
        if ($clean === 'delivery') return 'delivery';
        return 'recojo';
    }

    function estadoInfoAdmin($e) {
        $cod = normalizarEstadoCod($e);
        $map = [
            'pendiente'      => ['label' => 'Pendiente',         'bg' => '#fef3c7', 'color' => '#92400e', 'dot' => '#f59e0b'],
            'en_preparacion' => ['label' => 'En Preparación',    'bg' => '#eff6ff', 'color' => '#1d4ed8', 'dot' => '#3b82f6'],
            'en_camino'      => ['label' => 'En Camino',         'bg' => '#faf5ff', 'color' => '#7e22ce', 'dot' => '#a855f7'],
            'listo_recojo'   => ['label' => 'Listo para Recojo', 'bg' => '#ecfeff', 'color' => '#0e7490', 'dot' => '#06b6d4'],
            'entregado'      => ['label' => 'Entregado',         'bg' => '#dcfce7', 'color' => '#15803d', 'dot' => '#16a34a'],
            'cancelado'      => ['label' => 'Cancelado',         'bg' => '#fee2e2', 'color' => '#b91c1c', 'dot' => '#ef4444'],
        ];
        return $map[$cod] ?? $map['pendiente'];
    }

    function fechaAdminFmt($f) {
        if (empty($f)) return '—';
        $ts = strtotime($f);
        return $ts ? date('d/m/Y H:i', $ts) : htmlspecialchars($f, ENT_QUOTES, 'UTF-8');
    }

    $totalVentas    = count($ventas);
    $totalIngresos  = 0;
    $countPend      = 0;
    $countPrep      = 0;
    $countCamino    = 0;
    $countListoRec  = 0;
    $countEntreg    = 0;
    $countCancel    = 0;

    foreach ($ventas as $v) {
        $st = normalizarEstadoCod($v['estado'] ?? '');
        if ($st !== 'cancelado') {
            $totalIngresos += (float)($v['total'] ?? 0);
        }
        if ($st === 'pendiente') $countPend++;
        elseif ($st === 'en_preparacion') $countPrep++;
        elseif ($st === 'en_camino') $countCamino++;
        elseif ($st === 'listo_recojo') $countListoRec++;
        elseif ($st === 'entregado') $countEntreg++;
        elseif ($st === 'cancelado') $countCancel++;
    }

    $isAjax = AjaxHelper::isAjax();
?>
<?php if (!$isAjax): ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ventas / Pedidos — Admin La Caserita</title>
    <link rel="icon" type="image/png" href="Images/logo.png">
    <link rel="stylesheet" href="Styles/global.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="Styles/styles-home.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="Styles/styles-users-admin.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="Styles/admin-spa.css?v=<?= time(); ?>">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script src="Scripts/admin-spa.js?v=<?= time(); ?>" defer></script>
<link rel="stylesheet" href="Styles/Vistas/Admin/ventas_admin.css?v=<?= filemtime(__DIR__ . '/../../Styles/Vistas/Admin/ventas_admin.css'); ?>">
</head>
<body>
    
    <div class="admin-layout">
        <?php include __DIR__ . '/partials/admin_sidebar.php'; ?>
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
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
                        </span>
                        <span class="brand-title">Ventas & Pedidos</span>
                    </div>
                </div>
                <div class="admin-topbar-actions">
                    <?php include __DIR__ . '/partials/admin_topbar_actions.php'; ?>
                </div>
            </header>
        <div id="admin-container">
<?php endif; ?>



    <main class="main-content container-admin" style="padding-top: 15px; padding-bottom: 50px;">
        
        <div class="admin-header" style="margin-bottom: 20px;">
            <div class="admin-header-titles">
                <h1 style="font-size: 1.6rem; font-weight: 800; color: #0f172a; margin: 0;">Ventas</h1>
                <p style="margin: 4px 0 0; color: #64748b; font-size: 0.90rem;">Gestiona los pedidos realizados por los clientes.</p>
            </div>
        </div>

        <!-- Tarjetas pequeñas sin icono (interactivas) -->
        <div class="stats-grid-square" id="vaStatsGrid">
            <div class="stat-card-square active-card" data-filtro-estado="" onclick="window.vaFiltrarPorTarjeta(this, '')" title="Ver todos los pedidos">
                <span class="stat-title">Total Pedidos</span>
                <span class="stat-val"><?= $totalVentas; ?></span>
            </div>
            <div class="stat-card-square" data-filtro-estado="pendiente" onclick="window.vaFiltrarPorTarjeta(this, 'pendiente')" title="Filtrar pedidos pendientes">
                <span class="stat-title">Pendientes</span>
                <span class="stat-val"><?= $countPend; ?></span>
            </div>
            <div class="stat-card-square" data-filtro-estado="en_preparacion" onclick="window.vaFiltrarPorTarjeta(this, 'en_preparacion')" title="Filtrar pedidos en preparación">
                <span class="stat-title">En Preparación</span>
                <span class="stat-val"><?= $countPrep; ?></span>
            </div>
            <div class="stat-card-square" data-filtro-estado="en_camino" onclick="window.vaFiltrarPorTarjeta(this, 'en_camino')" title="Filtrar pedidos en camino">
                <span class="stat-title">En Camino</span>
                <span class="stat-val"><?= $countCamino; ?></span>
            </div>
            <div class="stat-card-square" data-filtro-estado="listo_recojo" onclick="window.vaFiltrarPorTarjeta(this, 'listo_recojo')" title="Filtrar pedidos listos para recojo">
                <span class="stat-title">Listo Recojo</span>
                <span class="stat-val"><?= $countListoRec; ?></span>
            </div>
            <div class="stat-card-square" data-filtro-estado="entregado" onclick="window.vaFiltrarPorTarjeta(this, 'entregado')" title="Filtrar pedidos entregados">
                <span class="stat-title">Entregados</span>
                <span class="stat-val"><?= $countEntreg; ?></span>
            </div>
            <div class="stat-card-square" data-filtro-estado="cancelado" onclick="window.vaFiltrarPorTarjeta(this, 'cancelado')" title="Filtrar pedidos cancelados">
                <span class="stat-title">Cancelados</span>
                <span class="stat-val"><?= $countCancel; ?></span>
            </div>
            <div class="stat-card-square" data-filtro-estado="" onclick="window.vaFiltrarPorTarjeta(this, '')" title="Ingresos de pedidos activos">
                <span class="stat-title">Ingresos Totales</span>
                <span class="stat-val" style="font-size: 1.05rem;">S/ <?= number_format($totalIngresos, 2); ?></span>
            </div>
        </div>

        <!-- Panel con diseño idéntico a Imagen 2 -->
        <section class="va-table-panel">
            <div class="va-panel-header">
                <h2>Lista de Pedidos</h2>
                <div class="va-filters-wrap">
                    <div class="va-search-box">
                        <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                        <input type="text" id="vaSearch" placeholder="Buscar por N° pedido o cliente...">
                    </div>
                    <select id="vaFiltroEstado" class="va-filter-select" aria-label="Filtrar por estado">
                        <option value="">Todos los estados</option>
                        <option value="pendiente">Pendiente</option>
                        <option value="en_preparacion">En Preparación</option>
                        <option value="en_camino">En Camino</option>
                        <option value="listo_recojo">Listo para Recojo</option>
                        <option value="entregado">Entregado</option>
                        <option value="cancelado">Cancelado</option>
                    </select>
                    <select id="vaFiltroEntrega" class="va-filter-select" aria-label="Filtrar por tipo de entrega">
                        <option value="">Todos los envíos</option>
                        <option value="delivery">Delivery</option>
                        <option value="recojo">Recojo en tienda</option>
                    </select>
                    <input type="date" id="vaFiltroFecha" class="va-filter-date" title="Filtrar por fecha">
                </div>
            </div>

            <div style="overflow-x: auto;">
                <?php if (empty($ventas)): ?>
                    <div style="text-align: center; padding: 50px 20px; color: #94a3b8;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 10px; opacity: 0.5;"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
                        <p style="margin: 0; font-size: 0.95rem;">No hay pedidos registrados aún.</p>
                    </div>
                <?php else: ?>
                <table class="va-table-custom" id="vaTabla">
                    <thead>
                        <tr>
                            <th>N° Pedido</th>
                            <th>Cliente</th>
                            <th>Correo</th>
                            <th>T.Entrega</th>
                            <th>Pago</th>
                            <th>Total</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th style="text-align: right;">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ventas as $v): ?>
                            <?php
                                $idPed         = (int)$v['id_pedido'];
                                $padId         = str_pad($idPed, 5, '0', STR_PAD_LEFT);
                                $nombreCliente = trim(($v['usuario_nombre'] ?? '') . ' ' . ($v['usuario_apellido'] ?? '')) ?: 'Sin nombre';
                                $correoCliente = trim($v['usuario_correo'] ?? '');
                                $fechaRaw      = !empty($v['fecha']) ? date('Y-m-d', strtotime($v['fecha'])) : '';
                                $estadoCod     = normalizarEstadoCod($v['estado'] ?? '');
                                $entregaCod    = normalizarEntregaCod($v['tipo_entrega'] ?? '');
                                $stInfo        = estadoInfoAdmin($v['estado'] ?? '');
                                $busqTexto     = strtolower("#{$padId} {$idPed} " . ($v['codigo_pedido'] ?? '') . " {$nombreCliente} {$correoCliente} {$entregaCod} " . ($v['metodo_pago'] ?? ''));
                            ?>
                            <tr class="va-row"
                                data-id="<?= $idPed; ?>"
                                data-estado="<?= $estadoCod; ?>"
                                data-entrega="<?= $entregaCod; ?>"
                                data-fecha="<?= $fechaRaw; ?>"
                                data-search="<?= htmlspecialchars($busqTexto, ENT_QUOTES, 'UTF-8'); ?>">
                                <!-- Columna N° Pedido: SOLO el número de pedido -->
                                <td style="font-weight: 800; font-size: 0.80rem; white-space: nowrap;">
                                    #<?= $padId; ?>
                                </td>
                                <td style="font-weight: 700; color: #334155; font-size: 0.82rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?= htmlspecialchars($nombreCliente, ENT_QUOTES, 'UTF-8'); ?>">
                                    <?= htmlspecialchars($nombreCliente, ENT_QUOTES, 'UTF-8'); ?>
                                </td>
                                <td style="color: #64748b; font-size: 0.82rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?= htmlspecialchars($correoCliente, ENT_QUOTES, 'UTF-8'); ?>">
                                    <?= !empty($correoCliente) ? htmlspecialchars($correoCliente, ENT_QUOTES, 'UTF-8') : '—'; ?>
                                </td>
                                <td style="text-align: center;">
                                    <?php if ($entregaCod === 'delivery'): ?>
                                        <span style="padding: 2px 7px; border-radius: 12px; font-size: 0.75rem; font-weight: 700; display: inline-flex; align-items: center; gap: 3px; white-space: nowrap; color: #1e293b;">
                                            Delivery
                                        </span>
                                    <?php else: ?>
                                        <span style="padding: 2px 7px; border-radius: 12px; font-size: 0.75rem; font-weight: 700; display: inline-flex; align-items: center; gap: 3px; white-space: nowrap; color: #1e293b;">
                                            Recojo
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: center; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    <?php
                                        $metodoOriginal = $v['metodo_pago'] ?? '—';
                                        $metodoCorto = $metodoOriginal;
                                        if (stripos($metodoCorto, 'efectivo') !== false) {
                                            $metodoCorto = 'Efectivo';
                                        } elseif (stripos($metodoCorto, 'yape') !== false) {
                                            $metodoCorto = 'Yape';
                                        } elseif (stripos($metodoCorto, 'plin') !== false) {
                                            $metodoCorto = 'Plin';
                                        } elseif (stripos($metodoCorto, 'tarjeta') !== false) {
                                            $metodoCorto = 'Tarjeta';
                                        } elseif (stripos($metodoCorto, 'transferencia') !== false) {
                                            $metodoCorto = 'Transferencia';
                                        }
                                    ?>
                                    <span style="color: #475569; font-size: 0.80rem; font-weight: 700; white-space: nowrap;" title="<?= htmlspecialchars($metodoOriginal, ENT_QUOTES, 'UTF-8'); ?>">
                                        <?= htmlspecialchars($metodoCorto, ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                </td>
                                <td style="font-weight: 800; font-size: 0.85rem; white-space: nowrap; text-align: right;">
                                    S/ <?= number_format((float)($v['total'] ?? 0), 2); ?>
                                </td>
                                <td style="color: #64748b; font-size: 0.78rem; white-space: nowrap; text-align: center;">
                                    <?= fechaAdminFmt($v['fecha']); ?>
                                </td>
                                <td style="text-align: center;">
                                    <span class="va-badge-pill" style="display: inline-flex; align-items: center; gap: 4px; padding: 2px 7px; border-radius: 20px; font-size: 0.74rem; font-weight: 700; white-space: nowrap;">
                                        <span style="width: 6px; height: 6px; border-radius: 50%; background: <?= $stInfo['dot']; ?>; flex-shrink: 0;"></span>
                                        <span><?= $stInfo['label']; ?></span>
                                    </span>
                                </td>
                                <td style="text-align: right; white-space: nowrap;">
                                    <div style="display: inline-flex; align-items: center; gap: 4px; justify-content: flex-end;">
                                        <button type="button" 
                                            class="cat-action-btn cat-btn-edit va-action-btn va-abrir-modal" 
                                            data-id="<?= $idPed; ?>" 
                                            title="Previsualizar venta y pedido" 
                                            aria-label="Previsualizar venta">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                <circle cx="12" cy="12" r="3"></circle>
                                            </svg>
                                        </button>
                                        <button type="button" 
                                            class="va-action-btn va-btn-pdf" 
                                            onclick="window.descargarPedidoPDF(<?= $idPed; ?>)" 
                                            title="Imprimir / Descargar comprobante en PDF" 
                                            aria-label="Imprimir o Descargar PDF">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="6 9 6 2 18 2 18 9"></polyline>
                                                <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                                                <rect x="6" y="14" width="12" height="8"></rect>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>

            <!-- Paginación estilo Imagen 3 -->
            <div style="display: flex; align-items: center; justify-content: center; padding: 14px 20px; border-top: 1px solid #e2e8f0; background: #fff;" id="vaPaginationControls">
                <div style="display: flex; align-items: center; gap: 6px;" id="vaPaginationList"></div>
            </div>
        </section>
    </main>

    <!-- Modal Detalle del Pedido (Imagen 3) -->
    <div class="va-modal-overlay" id="vaModalOverlay">
        <div class="va-modal-card">
            <div class="va-modal-head">
                <h3 id="vaModalTitulo">Pedido #00000</h3>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <!-- Botón X funcional para cerrar (sin botón volver) -->
                    <button type="button" class="va-modal-close-icon" id="vaBtnCerrarModal" aria-label="Cerrar modal" title="Cerrar">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    </button>
                </div>
            </div>
            <div class="va-modal-body-content" id="vaModalBody">
                <p style="text-align: center; color: #94a3b8; padding: 40px 0;">Cargando detalle...</p>
            </div>
            <div class="va-modal-foot" id="vaModalFooter"></div>
        </div>
    </div>

    <!-- Modal Confirmación para Guardar Cambio -->
    <div class="va-modal-overlay" id="vaConfirmModalOverlay" style="z-index: 99999; backdrop-filter: blur(3px);">
        <div style="background: #ffffff; border-radius: 18px; width: min(440px, 94vw); padding: 28px 24px; text-align: center; box-shadow: 0 24px 60px rgba(0,0,0,0.25);">
            <div style="width: 56px; height: 56px; border-radius: 50%; background: #dcfce7; color: #15803d; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 16px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
            </div>
            <h3 style="margin: 0 0 8px; font-size: 1.2rem; font-weight: 800; color: #0f172a;">¿Confirmar cambio de estado?</h3>
            <p id="vaConfirmModalMsg" style="margin: 0 0 22px; font-size: 0.90rem; color: #64748b; line-height: 1.5;">
                El pedido pasará al nuevo estado seleccionado.
            </p>
            <div style="display: flex; gap: 12px; justify-content: center;">
                <button type="button" id="vaBtnConfirmarCancelar" style="padding: 10px 20px; border-radius: 8px; border: 1px solid #cbd5e1; background: #ffffff; color: #475569; font-weight: 700; font-size: 0.88rem; cursor: pointer;">
                    Cancelar
                </button>
                <button type="button" id="vaBtnConfirmarProceder" style="padding: 10px 22px; border-radius: 8px; border: none; background: #15803d; color: #ffffff; font-weight: 700; font-size: 0.88rem; cursor: pointer; box-shadow: 0 2px 8px rgba(21,128,61,0.25);">
                    Sí, Guardar Cambio
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Ticket Comprobante de Venta (Imprimir / PDF) -->
    <div id="modalTicketVentaAdmin" class="va-modal-overlay" style="z-index: 99999;">
        <div class="va-modal-card" style="width: min(440px, 95vw); max-height: 94vh;">
            <div class="va-modal-head ticket-no-print" style="padding: 14px 18px;">
                <h3 style="font-size: 1.05rem; display: flex; align-items: center; gap: 8px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#15803d" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
                    Comprobante de Venta
                </h3>
                <button type="button" class="va-modal-close-icon" id="btnCerrarTicketAdmin" aria-label="Cerrar modal" title="Cerrar">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
            </div>

            <div class="va-modal-body-content" style="padding: 12px; background: #f8fafc; overflow-y: auto;">
                <div id="ticketTermicoImprimibleAdmin" style="background: #ffffff; padding: 18px 16px; font-family: 'Courier New', Courier, monospace; font-size: 11.5px; color: #000; line-height: 1.35; border: 1px solid #e2e8f0; border-radius: 8px; width: 100%; max-width: 330px; margin: 0 auto; box-shadow: 0 2px 10px rgba(0,0,0,0.06); box-sizing: border-box;">
                    <div style="text-align: center; margin-bottom: 8px;">
                        <strong style="font-size: 15px; display: block; font-weight: 900; letter-spacing: 0.5px;">TIENDA "LA CASERITA"</strong>
                        <div style="font-size: 11px; font-weight: 700; margin-top: 2px;">R.U.C. 20512345678</div>
                        <div style="font-size: 10px; color: #333; margin-top: 1px;">Av. Las Flores 123 - San Juan, Lima</div>
                        <div style="font-size: 10px; color: #333;">Teléfono: (01) 456-7890</div>
                        <div style="margin: 6px 0; border-bottom: 1px dashed #000;"></div>
                        <div style="font-weight: 800; font-size: 12.5px;" id="lblAdminTicketTipoDoc">BOLETA DE VENTA ELECTRÓNICA</div>
                        <div style="font-weight: 900; font-size: 13.5px; margin-top: 1px;" id="lblAdminTicketCodigo">B001-000000</div>
                        <div style="font-size: 10px; color: #555;" id="lblAdminTicketNumInt">PED-00000</div>
                    </div>

                    <div style="margin-bottom: 8px; font-size: 10.5px; line-height: 1.4;">
                        <div><strong>Fecha:</strong> <span id="lblAdminTicketFecha">--</span></div>
                        <div><strong>Cliente:</strong> <span id="lblAdminTicketCliente">CLIENTE GENERAL</span></div>
                        <div id="rowAdminTicketCorreo" style="display: none; word-break: break-all;"><strong>Correo:</strong> <span id="lblAdminTicketCorreo">--</span></div>
                        <div><strong>Entrega:</strong> <span id="lblAdminTicketEntrega">Recojo en tienda</span></div>
                        <div id="rowAdminTicketDireccion" style="display: none; word-break: break-word;"><strong>Dirección:</strong> <span id="lblAdminTicketDireccion">--</span></div>
                        <div><strong>Forma Pago:</strong> <span id="lblAdminTicketMetodo">EFECTIVO</span></div>
                        <div><strong>Estado:</strong> <span id="lblAdminTicketEstado">PENDIENTE</span></div>
                    </div>

                    <div style="border-bottom: 1px dashed #000; margin: 6px 0;"></div>

                    <table style="width: 100%; border-collapse: collapse; font-size: 10.5px;">
                        <thead>
                            <tr style="border-bottom: 1px solid #000; text-align: left;">
                                <th style="padding-bottom: 3px; width: 34px;">CANT</th>
                                <th style="padding-bottom: 3px;">DESCRIPCIÓN</th>
                                <th style="padding-bottom: 3px; text-align: right; width: 50px;">P.U</th>
                                <th style="padding-bottom: 3px; text-align: right; width: 55px;">TOTAL</th>
                            </tr>
                        </thead>
                        <tbody id="ticketAdminTbodyItems"></tbody>
                    </table>

                    <div style="border-bottom: 1px dashed #000; margin: 8px 0;"></div>

                    <div style="font-size: 11px; text-align: right; line-height: 1.45;">
                        <div>SUBTOTAL: <strong id="lblAdminTicketSubtotal">S/ 0.00</strong></div>
                        <div>I.G.V. (18%): <strong id="lblAdminTicketIgv">S/ 0.00</strong></div>
                        <div style="font-size: 13.5px; margin-top: 4px; font-weight: 900; border-top: 1px dashed #000; padding-top: 3px;">TOTAL A PAGAR: <span id="lblAdminTicketTotal">S/ 0.00</span></div>
                    </div>

                    <div id="rowAdminTicketEfectivo" style="display: none; font-size: 10.5px; margin-top: 6px; border-top: 1px dashed #000; padding-top: 4px; text-align: right;">
                        <div>RECIBIDO: <span id="lblAdminTicketRecibido">S/ 0.00</span></div>
                        <div>VUELTO: <span id="lblAdminTicketVuelto">S/ 0.00</span></div>
                    </div>

                    <div id="rowAdminTicketObs" style="display: none; font-size: 10px; margin-top: 6px; border-top: 1px dashed #000; padding-top: 4px; color: #475569;">
                        <strong>Observación:</strong> <span id="lblAdminTicketObs">--</span>
                    </div>

                    <div style="border-bottom: 1px dashed #000; margin: 10px 0;"></div>

                    <div style="text-align: center; font-size: 10px; line-height: 1.35;">
                        <p style="margin: 2px 0; font-weight: 700;">¡GRACIAS POR SU PREFERENCIA!</p>
                        <p style="margin: 2px 0; font-size: 9px; color: #444;">Representación impresa de la Boleta de Venta Electrónica.</p>
                        <p style="margin: 2px 0; font-size: 8.5px; color: #666;">Conserve este comprobante para cualquier reclamo.</p>
                        <div style="margin-top: 7px; letter-spacing: 2px; font-size: 16px; font-family: monospace;">||| | ||||| || |||| |||||</div>
                        <div style="font-size: 8.5px; color: #777; margin-top: 2px;">Aut. SUNAT - La Caserita</div>
                    </div>
                </div>
            </div>

            <div class="ticket-no-print" style="padding: 12px 18px; border-top: 1px solid #f1f5f9; background: #fafbfc; display: flex; flex-direction: column; gap: 8px;">
                <div style="display: flex; gap: 10px;">
                    <button type="button" id="btnAdminTicketImprimir" style="flex: 1; padding: 11px; background: #15803d; color: #fff; border: none; border-radius: 8px; font-weight: 800; cursor: pointer; font-size: 0.90rem; display: flex; align-items: center; justify-content: center; gap: 8px; box-shadow: 0 2px 6px rgba(21,128,61,0.25);">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                        IMPRIMIR
                    </button>
                    <button type="button" id="btnAdminTicketDescargarPdf" style="flex: 1; padding: 11px; background: #0284c7; color: #fff; border: none; border-radius: 8px; font-weight: 800; cursor: pointer; font-size: 0.90rem; display: flex; align-items: center; justify-content: center; gap: 8px; box-shadow: 0 2px 6px rgba(2,132,199,0.25);">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
                        DESCARGAR PDF
                    </button>
                </div>
                <button type="button" id="btnAdminTicketCerrarPie" style="padding: 8px; background: #f1f5f9; color: #475569; border: none; border-radius: 8px; font-weight: 700; cursor: pointer; font-size: 0.85rem;">
                    Cerrar
                </button>
            </div>
        </div>
    </div>

    <!-- Toast Notification Superior (Imagen 4) -->
    <div class="top-notification-toast" id="topNotificationToast">
        <div class="toast-card-top">
            <div class="toast-icon-circle" id="toastIconCircle">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
            </div>
            <div class="toast-content-top">
                <strong class="toast-title-top" id="toastTitleTop">Éxito</strong>
                <p class="toast-msg-top" id="toastMsgTop">Estado del producto actualizado.</p>
            </div>
            <button type="button" class="toast-close-top" id="toastCloseBtn" aria-label="Cerrar notificación">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
            <div class="toast-progress-bar" id="toastProgressBar"></div>
        </div>
    </div>

<script src="Scripts/Admin/ventas_admin.js?v=<?= time(); ?>"></script>

<?php if (!$isAjax): ?>
        </div>
    </div>
</div>
</body>
</html>
<?php endif; ?>
