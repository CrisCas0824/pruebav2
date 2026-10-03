<?php
require_once 'Core/ajaxHelper.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$nombre = htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Administrador');
$flash_success = $_SESSION['flash_success'] ?? null;
$flash_error = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

$envios = $envios ?? [];
$repartidores = $repartidores ?? [];
$filtroActual = $_GET['estado'] ?? 'todos';

// Cálculo de métricas
$totalEnvios = count($envios);
$deliveryCount = 0;
$sinRepartidorCount = 0;
$recojoCount = 0;
$pendientesCount = 0;
$enPrepCount = 0;
$enRutaCount = 0;
$entregadosCount = 0;
$totalPorCobrar = 0.00;

foreach ($envios as $env) {
    $est = $env['estado_delivery'] ?? 'Pendiente';
    $isRec = ($env['tipo_entrega_norm'] ?? '') === 'recojo';
    
    if ($isRec) {
        $recojoCount++;
    } else {
        $deliveryCount++;
        $sinRep = (empty($env['id_repartidor']) || $env['id_repartidor'] == 0 || empty($env['repartidor_nombre']));
        if ($sinRep && $est !== 'Entregado' && $est !== 'Fallido' && $est !== 'Cancelado') {
            $sinRepartidorCount++;
        }
    }

    if ($est === 'Pendiente') {
        $pendientesCount++;
    } elseif ($est === 'En Preparacion' || $est === 'En Preparación') {
        $enPrepCount++;
    } elseif ($est === 'En Ruta' || $est === 'Asignado') {
        $enRutaCount++;
    } elseif ($est === 'Entregado') {
        $entregadosCount++;
    }

    if (empty($env['cobrado_por_repartidor']) && $est !== 'Fallido' && $est !== 'Cancelado') {
        $totalPorCobrar += (float)($env['pedido_total'] ?? 0);
    }
}
?>
<?php if (!AjaxHelper::isAjax()): ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logística & Despacho Delivery — ERP La Caserita</title>
    <link rel="icon" type="image/png" href="Images/logo.png">
    <link rel="stylesheet" href="Styles/global.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="Styles/styles-home.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="Styles/styles-users-admin.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="Styles/admin-spa.css?v=<?= time(); ?>">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="Scripts/admin-spa.js?v=<?= time(); ?>" defer></script>
<link rel="stylesheet" href="Styles/Vistas/Admin/delivery_admin.css?v=<?= filemtime(__DIR__ . '/../../Styles/Vistas/Admin/delivery_admin.css'); ?>">
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
                        <span class="brand-title">Logística & Despacho Delivery</span>
                    </div>
                </div>
                <div class="admin-topbar-actions">
                    <?php include __DIR__ . '/partials/admin_topbar_actions.php'; ?>
                </div>
            </header>

            <div id="admin-container">
<?php endif; ?>

<!-- NOTIFICACIÓN TOAST SUPERIOR (IMAGEN 4) -->
<div id="deliveryTopToast" class="top-toast <?= ($flash_success || $flash_error) ? 'show' : ''; ?>">
    <div class="top-toast-icon" style="background: <?= $flash_error ? '#dc2626' : '#15803d'; ?>;">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
            <?= $flash_error ? '<line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line>' : '<polyline points="20 6 9 17 4 12"></polyline>'; ?>
        </svg>
    </div>
    <div class="top-toast-body">
        <strong><?= $flash_error ? 'Error' : 'Éxito' ?></strong>
        <span><?= htmlspecialchars($flash_error ?: ($flash_success ?: 'Operación completada con éxito.')); ?></span>
    </div>
    <button type="button" class="top-toast-close" id="deliveryToastClose" aria-label="Cerrar">&times;</button>
    <div class="top-toast-progress" style="background: <?= $flash_error ? '#dc2626' : '#15803d'; ?>;"></div>
</div>

<!-- Modal Confirmación Reutilizable (Imagen 3 / Ventas) -->
<div class="deliv-modal-overlay" id="delivConfirmModal" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.60); z-index: 999999; backdrop-filter: blur(3px); align-items: center; justify-content: center; padding: 16px;">
    <div style="background: #ffffff; border-radius: 18px; width: min(440px, 94vw); padding: 28px 24px; text-align: center; box-shadow: 0 24px 60px rgba(0,0,0,0.25); margin: auto;">
        <div id="delivConfirmIconBox" style="width: 56px; height: 56px; border-radius: 50%; background: #dcfce7; color: #15803d; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 16px;">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
        </div>
        <h3 id="delivConfirmModalTitulo" style="margin: 0 0 8px; font-size: 1.2rem; font-weight: 800; color: #0f172a;">¿Confirmar acción?</h3>
        <p id="delivConfirmModalMsg" style="margin: 0 0 22px; font-size: 0.90rem; color: #64748b; line-height: 1.5;">
            Mensaje de confirmación.
        </p>
        <div style="display: flex; gap: 12px; justify-content: center;">
            <button type="button" onclick="window.cerrarDelivConfirmacion(true)" style="padding: 10px 20px; border-radius: 8px; border: 1px solid #cbd5e1; background: #ffffff; color: #475569; font-weight: 700; font-size: 0.88rem; cursor: pointer;">
                Cancelar
            </button>
            <button type="button" id="btnDelivConfirmarProceder" style="padding: 10px 22px; border-radius: 8px; border: none; background: #15803d; color: #ffffff; font-weight: 700; font-size: 0.88rem; cursor: pointer; box-shadow: 0 2px 8px rgba(21,128,61,0.25);">
                Sí, Confirmar
            </button>
        </div>
    </div>
</div>

<main class="main-content container-admin" style="padding-top: 25px; padding-bottom: 50px;">
    

    <!-- HEADER DE SECCIÓN -->
    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px; margin-bottom: 22px;">
        <div>
            <h1 style="font-size: 1.55rem; font-weight: 800; color: #0f172a; margin: 0 0 6px 0; letter-spacing: -0.02em;">Logística y Despacho</h1>
            <p style="color: #64748b; font-size: 0.88rem; margin: 0;">
                Supervisa los pedidos con despacho a domicilio, asigna motorizados y actualiza el estado de las entregas en tiempo real.
            </p>
        </div>
    </div>

    <!-- TARJETAS CUADRADAS COMPACTAS (IMAGEN 2) -->
    <div class="deliv-stats-grid">
        <div class="deliv-stat-card <?= $filtroActual === 'todos' ? 'active-card' : ''; ?>" id="cardDelivTodos" onclick="window.filtrarEstadoDeliv('todos')">
            <span class="deliv-stat-title">TOTAL PEDIDOS</span>
            <span class="deliv-stat-number" id="lblTotalEnvios"><?= $totalEnvios; ?></span>
        </div>
        <div class="deliv-stat-card <?= $filtroActual === 'delivery' ? 'active-card' : ''; ?>" id="cardDelivTipoDelivery" onclick="window.filtrarEstadoDeliv('delivery')">
            <span class="deliv-stat-title">DELIVERY</span>
            <span class="deliv-stat-number" id="lblDeliveryCount" style="color: #2563eb;"><?= $deliveryCount; ?></span>
        </div>
        <div class="deliv-stat-card <?= $filtroActual === 'sin_repartidor' ? 'active-card' : ''; ?>" id="cardDelivSinRepartidor" onclick="window.filtrarEstadoDeliv('sin_repartidor')">
            <span class="deliv-stat-title">SIN REPARTIDOR</span>
            <span class="deliv-stat-number" id="lblSinRepartidorCount" style="color: #dc2626;"><?= $sinRepartidorCount; ?></span>
        </div>
        <div class="deliv-stat-card <?= $filtroActual === 'Pendiente' ? 'active-card' : ''; ?>" id="cardDelivPend" onclick="window.filtrarEstadoDeliv('Pendiente')">
            <span class="deliv-stat-title">PENDIENTES</span>
            <span class="deliv-stat-number" id="lblPendientesCount" style="color: #d97706;"><?= $pendientesCount; ?></span>
        </div>
        <div class="deliv-stat-card <?= ($filtroActual === 'En Preparacion' || $filtroActual === 'En Preparación') ? 'active-card' : ''; ?>" id="cardDelivPrep" onclick="window.filtrarEstadoDeliv('En Preparacion')">
            <span class="deliv-stat-title">EN PREPARACIÓN</span>
            <span class="deliv-stat-number" id="lblEnPrepCount" style="color: #6366f1;"><?= $enPrepCount; ?></span>
        </div>
        <div class="deliv-stat-card <?= ($filtroActual === 'Asignado' || $filtroActual === 'En Ruta') ? 'active-card' : ''; ?>" id="cardDelivRuta" onclick="window.filtrarEstadoDeliv('En Ruta')">
            <span class="deliv-stat-title">EN RUTA / ASIGNADOS</span>
            <span class="deliv-stat-number" id="lblEnRutaCount" style="color: #1d4ed8;"><?= $enRutaCount; ?></span>
        </div>
        <div class="deliv-stat-card <?= $filtroActual === 'recojo' ? 'active-card' : ''; ?>" id="cardDelivRecojo" onclick="window.filtrarEstadoDeliv('recojo')">
            <span class="deliv-stat-title">RECOJO EN TIENDA</span>
            <span class="deliv-stat-number" id="lblRecojoCount" style="color: #0891b2;"><?= $recojoCount; ?></span>
        </div>
        <div class="deliv-stat-card <?= $filtroActual === 'Entregado' ? 'active-card' : ''; ?>" id="cardDelivEntr" onclick="window.filtrarEstadoDeliv('Entregado')">
            <span class="deliv-stat-title">ENTREGADOS</span>
            <span class="deliv-stat-number" id="lblEntregadosCount" style="color: #15803d;"><?= $entregadosCount; ?></span>
        </div>
        <div class="deliv-stat-card <?= $filtroActual === 'por_cobrar' ? 'active-card' : ''; ?>" id="cardDelivCobrar" onclick="window.filtrarEstadoDeliv('por_cobrar')">
            <span class="deliv-stat-title">TOTAL POR COBRAR</span>
            <span class="deliv-stat-number" id="lblTotalPorCobrar" style="font-size: 1.15rem; color: #0f172a;">S/ <?= number_format($totalPorCobrar, 2); ?></span>
        </div>
    </div>

    <!-- PANEL PRINCIPAL CON TABLA -->
    <div class="deliv-card-panel">
        <div class="deliv-card-header">
            <div class="deliv-card-title">Despachos y Entregas (Delivery & Recojo en Tienda)</div>
            <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                <select id="filtroTipoSelect" class="deliv-search-input" style="min-width: 170px; padding: 9px 12px; cursor: pointer;" onchange="window.filtrarDeliveryEnVivo()">
                    <option value="todos">Todos los Tipos</option>
                    <option value="delivery">Solo Delivery</option>
                    <option value="recojo">Solo Recojo en Tienda</option>
                </select>
                <input type="text" id="busquedaDelivInput" class="deliv-search-input" placeholder="Buscar por pedido, cliente o dirección..." oninput="window.filtrarDeliveryEnVivo()">
            </div>
        </div>

        <div style="overflow-x: auto;">
            <table class="deliv-table">
                <thead>
                    <tr>
                        <th>PEDIDO</th>
                        <th>DESTINATARIO / CELULAR</th>
                        <th>DIRECCIÓN DE ENTREGA</th>
                        <th style="text-align: right;">TOTAL A COBRAR</th>
                        <th>REPARTIDOR ASIGNADO</th>
                        <th style="text-align: center;">ESTADO DESPACHO</th>
                        <th style="text-align: right;">ACCIONES</th>
                    </tr>
                </thead>
                <tbody id="tbodyDeliv">
                    <!-- Filas renderizadas por JS con paginación de 5 en 5 -->
                </tbody>
            </table>
        </div>

        <!-- Paginación estilo Imagen 3 (< 1 2 3 ... >) -->
        <div class="deliv-pagination-bar" id="paginacionDelivBar"></div>
    </div>
</main>

<!-- Modal Asignar Repartidor -->
<div id="modalAsignar" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999; justify-content: center; align-items: center; padding: 20px;">
    <div style="background: #ffffff; border-radius: 16px; width: 100%; max-width: 440px; padding: 24px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.15);">
        <h3 style="margin: 0 0 10px; font-size: 1.2rem; font-weight: 800; color: #0f172a;">Asignar Repartidor al Envío</h3>
        <p style="margin: 0 0 16px; font-size: 0.85rem; color: #64748b;">Seleccione el conductor o motorizado responsable del despacho.</p>
        <form id="formAsignarRepartidor" action="index.php?action=asignar_repartidor" method="POST" onsubmit="window.enviarAsignarAjax(event)">
            <input type="hidden" name="id_envio" id="idEnvioAsignar">
            <div style="margin-bottom: 18px;">
                <label style="display: block; font-size: 0.82rem; font-weight: 700; color: #334155; margin-bottom: 6px; text-transform: uppercase;">Repartidores Disponibles</label>
                <select name="id_repartidor" id="selectRepartidor" required style="width: 100%; padding: 10px 12px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 0.90rem; box-sizing: border-box; outline: none; background: #fff;">
                    <option value="">-- Seleccionar Repartidor --</option>
                    <?php foreach ($repartidores as $rep): ?>
                        <option value="<?= $rep['id_usuario']; ?>" data-nombre="<?= htmlspecialchars($rep['nombre']); ?>" data-apellido="<?= htmlspecialchars($rep['apellido'] ?? ''); ?>" data-telefono="<?= htmlspecialchars($rep['telefono'] ?? ''); ?>">
                            <?= htmlspecialchars($rep['nombre'] . ' ' . $rep['apellido']); ?> (<?= htmlspecialchars($rep['telefono'] ?? 'Sin tel.'); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" onclick="window.cerrarModalAsignar()" style="padding: 9px 18px; background: #f1f5f9; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; color: #475569;">Cancelar</button>
                <button type="submit" id="btnSubmitAsignar" style="padding: 9px 20px; background: #15803d; color: #ffffff; border: none; border-radius: 8px; font-weight: 800; cursor: pointer;">Guardar Asignación</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Actualizar Estado Delivery & Recojo -->
<div id="modalEstado" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999; justify-content: center; align-items: center; padding: 20px;">
    <div style="background: #ffffff; border-radius: 16px; width: 100%; max-width: 460px; padding: 24px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.15);">
        <h3 id="delivModalEstadoTitulo" style="margin: 0 0 10px; font-size: 1.2rem; font-weight: 800; color: #0f172a;">Actualizar Estado</h3>
        <p id="delivModalEstadoSubtitulo" style="margin: 0 0 16px; font-size: 0.85rem; color: #64748b;">Cambie el estado del pedido y registre cobro en caso aplique.</p>
        <form id="formActualizarEstado" action="index.php?action=actualizar_estado_delivery" method="POST" onsubmit="window.enviarEstadoAjax(event)">
            <input type="hidden" name="id_envio" id="idEnvioEstado">
            <input type="hidden" name="id_pedido" id="idPedidoEstado">
            <div style="margin-bottom: 14px;">
                <label style="display: block; font-size: 0.82rem; font-weight: 700; color: #334155; margin-bottom: 6px; text-transform: uppercase;">Nuevo Estado</label>
                <select name="nuevo_estado" id="selectNuevoEstado" required style="width: 100%; padding: 10px 12px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 0.90rem; box-sizing: border-box; outline: none; background: #fff;">
                    <option value="Pendiente">Pendiente</option>
                    <option value="En Preparacion">En Preparación</option>
                    <option value="Asignado">Asignado</option>
                    <option value="En Ruta">En Ruta</option>
                    <option value="Listo para Recojo">Listo para Recojo</option>
                    <option value="Entregado">Entregado</option>
                    <option value="Fallido">Fallido / No entregado</option>
                </select>
            </div>

            <div style="margin-bottom: 14px;" id="grpCobrado">
                <label style="display: inline-flex; align-items: center; gap: 8px; font-size: 0.88rem; font-weight: 700; color: #15803d; cursor: pointer;">
                    <input type="checkbox" name="cobrado" value="1" id="chkCobrado" style="accent-color: #15803d; width: 18px; height: 18px;">
                    <span id="lblCobradoTexto">¿Dinero cobrado / pagado?</span>
                </label>
            </div>

            <div style="margin-bottom: 18px;">
                <label style="display: block; font-size: 0.82rem; font-weight: 700; color: #334155; margin-bottom: 6px; text-transform: uppercase;">Notas o Incidencias</label>
                <textarea name="notas" id="txtNotasEstado" rows="2" placeholder="Ej: Entregado a cliente / Cobro presencial completado" style="width: 100%; padding: 10px 12px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 0.90rem; box-sizing: border-box; outline: none;"></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" onclick="window.cerrarModalEstado()" style="padding: 9px 18px; background: #f1f5f9; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; color: #475569;">Cancelar</button>
                <button type="submit" id="btnSubmitEstado" style="padding: 9px 20px; background: #15803d; color: #ffffff; border: none; border-radius: 8px; font-weight: 800; cursor: pointer;">Guardar Estado</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Previsualizar Detalle del Pedido en Delivery -->
<div id="modalDetalleDeliv" class="deliv-modal-overlay" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.60); z-index: 99999; backdrop-filter: blur(3px); align-items: center; justify-content: center; padding: 16px;">
    <div style="background: #ffffff; border-radius: 18px; width: min(650px, 95vw); max-height: 90vh; display: flex; flex-direction: column; box-shadow: 0 28px 80px rgba(15, 23, 42, 0.28); overflow: hidden; margin: auto;">
        <div style="padding: 18px 22px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between;">
            <h3 id="delivModalTitulo" style="margin: 0; font-size: 1.15rem; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#0284c7" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                <span>Detalle del Pedido</span>
            </h3>
            <button type="button" onclick="window.cerrarModalDetalleDeliv()" style="width: 32px; height: 32px; border: none; border-radius: 50%; background: #f1f5f9; color: #64748b; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; font-weight: 700;" title="Cerrar">&times;</button>
        </div>
        
        <div id="delivModalBody" style="padding: 20px 22px; overflow-y: auto; flex: 1;">
            <p style="text-align: center; color: #94a3b8; padding: 30px 0;">Cargando detalle...</p>
        </div>

        <div id="delivModalFooter" style="padding: 14px 22px; border-top: 1px solid #f1f5f9; background: #fafbfc; display: flex; align-items: center; justify-content: center; gap: 10px; flex-wrap: wrap;">
            <div id="delivModalFooterAcciones" style="display: flex; justify-content: center; gap: 8px; flex-wrap: wrap;"></div>
        </div>
    </div>
</div>

<script>
    const listaEnvios = <?= json_encode($envios); ?>;
    let filtroEstado = '<?= $filtroActual; ?>';
    let busquedaTexto = '';
    let paginaActual = 1;
    const ITEMS_PER_PAGE = 5;
    let accionDelivConfirmacion = null;
    let accionDelivCancelar = null;

    // Modal Confirmación (Imagen 3 / Ventas)
    window.abrirDelivConfirmacion = function(titulo, mensaje, accion, esPeligro = false, onCancel = null) {
        const modal = document.getElementById('delivConfirmModal');
        const titEl = document.getElementById('delivConfirmModalTitulo');
        const msgEl = document.getElementById('delivConfirmModalMsg');
        const iconBox = document.getElementById('delivConfirmIconBox');
        const btnProc = document.getElementById('btnDelivConfirmarProceder');

        if (titEl) titEl.textContent = titulo;
        if (msgEl) msgEl.textContent = mensaje;

        if (esPeligro) {
            if (iconBox) {
                iconBox.style.background = '#fee2e2';
                iconBox.style.color = '#dc2626';
                iconBox.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>';
            }
            if (btnProc) {
                btnProc.style.background = '#dc2626';
                btnProc.textContent = 'Sí, Continuar';
            }
        } else {
            if (iconBox) {
                iconBox.style.background = '#dcfce7';
                iconBox.style.color = '#15803d';
                iconBox.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>';
            }
            if (btnProc) {
                btnProc.style.background = '#15803d';
                btnProc.textContent = 'Sí, Confirmar';
            }
        }

        accionDelivConfirmacion = accion;
        accionDelivCancelar = onCancel;
        if (btnProc) {
            btnProc.onclick = function() {
                const fn = accionDelivConfirmacion;
                window.cerrarDelivConfirmacion(false);
                if (typeof fn === 'function') fn();
            };
        }
        if (modal) modal.style.display = 'flex';
    };

    window.cerrarDelivConfirmacion = function(esCancel = false) {
        const modal = document.getElementById('delivConfirmModal');
        if (modal) modal.style.display = 'none';
        if (esCancel && typeof accionDelivCancelar === 'function') {
            accionDelivCancelar();
        }
        accionDelivConfirmacion = null;
        accionDelivCancelar = null;
    };

    // Toast interactivo (Imagen 4)
    window.mostrarDelivToast = function(titulo, mensaje, esError = false) {
        const toast = document.getElementById('deliveryTopToast');
        if (!toast) return;

        const iconEl = toast.querySelector('.top-toast-icon');
        const strongEl = toast.querySelector('.top-toast-body strong');
        const spanEl = toast.querySelector('.top-toast-body span');
        const progEl = toast.querySelector('.top-toast-progress');

        const color = esError ? '#dc2626' : '#15803d';
        if (iconEl) {
            iconEl.style.background = color;
            iconEl.innerHTML = esError ? 
                '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>' :
                '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>';
        }
        if (strongEl) strongEl.textContent = titulo;
        if (spanEl) spanEl.textContent = mensaje;
        if (progEl) progEl.style.background = color;

        toast.classList.add('show');
        if (window._delivToastTimer) clearTimeout(window._delivToastTimer);
        window._delivToastTimer = setTimeout(() => toast.classList.remove('show'), 3800);
    };

    (function initToast() {
        const toast = document.getElementById('deliveryTopToast');
        const closeBtn = document.getElementById('deliveryToastClose');
        if (closeBtn && toast) {
            closeBtn.addEventListener('click', () => toast.classList.remove('show'));
        }
        if (toast && toast.classList.contains('show')) {
            window._delivToastTimer = setTimeout(() => toast.classList.remove('show'), 3800);
        }
    })();

    // Recalcular métricas de las tarjetas en vivo
    window.actualizarContadoresDelivUI = function() {
        let total = listaEnvios.length;
        let deliveryCount = 0;
        let sinRepartidor = 0;
        let recojo = 0;
        let pend = 0;
        let enPrep = 0;
        let enRuta = 0;
        let entr = 0;
        let porCobrar = 0;

        listaEnvios.forEach(e => {
            const est = e.estado_delivery || 'Pendiente';
            const isRec = (e.tipo_entrega_norm === 'recojo');
            
            if (isRec) {
                recojo++;
            } else {
                deliveryCount++;
                const sinRep = !e.id_repartidor || parseInt(e.id_repartidor) === 0 || !e.repartidor_nombre;
                if (sinRep && est !== 'Entregado' && est !== 'Fallido' && est !== 'Cancelado') {
                    sinRepartidor++;
                }
            }

            if (est === 'Pendiente') pend++;
            else if (est === 'En Preparacion' || est === 'En Preparación') enPrep++;
            else if (est === 'En Ruta' || est === 'Asignado') enRuta++;
            else if (est === 'Entregado') entr++;

            if (parseInt(e.cobrado_por_repartidor || 0) === 0 && est !== 'Fallido' && est !== 'Cancelado') {
                porCobrar += parseFloat(e.pedido_total || 0);
            }
        });

        const elTot = document.getElementById('lblTotalEnvios');
        const elDeliv = document.getElementById('lblDeliveryCount');
        const elSinRep = document.getElementById('lblSinRepartidorCount');
        const elPend = document.getElementById('lblPendientesCount');
        const elPrep = document.getElementById('lblEnPrepCount');
        const elRuta = document.getElementById('lblEnRutaCount');
        const elRec = document.getElementById('lblRecojoCount');
        const elEntr = document.getElementById('lblEntregadosCount');
        const elCobrar = document.getElementById('lblTotalPorCobrar');

        if (elTot) elTot.textContent = total;
        if (elDeliv) elDeliv.textContent = deliveryCount;
        if (elSinRep) elSinRep.textContent = sinRepartidor;
        if (elPend) elPend.textContent = pend;
        if (elPrep) elPrep.textContent = enPrep;
        if (elRuta) elRuta.textContent = enRuta;
        if (elRec) elRec.textContent = recojo;
        if (elEntr) elEntr.textContent = entr;
        if (elCobrar) elCobrar.textContent = 'S/ ' + porCobrar.toFixed(2);
    };

    window.filtrarEstadoDeliv = function(est) {
        filtroEstado = est;
        paginaActual = 1;
        document.querySelectorAll('.deliv-stat-card').forEach(c => c.classList.remove('active-card'));
        if (est === 'todos') document.getElementById('cardDelivTodos')?.classList.add('active-card');
        else if (est === 'delivery') document.getElementById('cardDelivTipoDelivery')?.classList.add('active-card');
        else if (est === 'sin_repartidor') document.getElementById('cardDelivSinRepartidor')?.classList.add('active-card');
        else if (est === 'Pendiente') document.getElementById('cardDelivPend')?.classList.add('active-card');
        else if (est === 'En Preparacion' || est === 'En Preparación') document.getElementById('cardDelivPrep')?.classList.add('active-card');
        else if (est === 'En Ruta') document.getElementById('cardDelivRuta')?.classList.add('active-card');
        else if (est === 'recojo') document.getElementById('cardDelivRecojo')?.classList.add('active-card');
        else if (est === 'Entregado') document.getElementById('cardDelivEntr')?.classList.add('active-card');
        else if (est === 'por_cobrar') document.getElementById('cardDelivCobrar')?.classList.add('active-card');

        const selTipo = document.getElementById('filtroTipoSelect');
        if (selTipo) {
            if (est === 'recojo') selTipo.value = 'recojo';
            else if (est === 'delivery' || est === 'sin_repartidor') selTipo.value = 'delivery';
            else selTipo.value = 'todos';
        }

        renderizarTabla();
    };

    window.filtrarDeliveryEnVivo = function() {
        busquedaTexto = (document.getElementById('busquedaDelivInput')?.value || '').toLowerCase().trim();
        paginaActual = 1;
        renderizarTabla();
    };

    function renderizarTabla() {
        const tbody = document.getElementById('tbodyDeliv');
        const pagContainer = document.getElementById('paginacionDelivBar');
        if (!tbody) return;

        const filtroTipo = document.getElementById('filtroTipoSelect')?.value || 'todos';

        const filtrados = listaEnvios.filter(env => {
            const est = env.estado_delivery || 'Pendiente';
            const isRec = (env.tipo_entrega_norm === 'recojo');
            const sinRep = !isRec && (!env.id_repartidor || parseInt(env.id_repartidor) === 0 || !env.repartidor_nombre);

            if (filtroTipo === 'delivery' && isRec) return false;
            if (filtroTipo === 'recojo' && !isRec) return false;

            if (filtroEstado !== 'todos') {
                if (filtroEstado === 'recojo') {
                    if (!isRec) return false;
                } else if (filtroEstado === 'delivery') {
                    if (isRec) return false;
                } else if (filtroEstado === 'sin_repartidor') {
                    if (isRec || !sinRep || est === 'Entregado' || est === 'Cancelado' || est === 'Fallido') return false;
                } else if (filtroEstado === 'En Ruta') {
                    if (est !== 'En Ruta' && est !== 'Asignado') return false;
                } else if (filtroEstado === 'En Preparacion') {
                    if (est !== 'En Preparacion' && est !== 'En Preparación') return false;
                } else if (filtroEstado === 'por_cobrar') {
                    if (parseInt(env.cobrado_por_repartidor || 0) === 1 || est === 'Fallido' || est === 'Cancelado') return false;
                } else if (est !== filtroEstado) {
                    return false;
                }
            }

            if (busquedaTexto !== '') {
                const ped = (env.codigo_pedido || '#' + env.id_pedido).toLowerCase();
                const cli = (env.nombre_destinatario || '').toLowerCase();
                const dir = (env.direccion_destino || '').toLowerCase();
                const rep = ((env.repartidor_nombre || '') + ' ' + (env.repartidor_apellido || '')).toLowerCase();
                const met = (env.metodo_pago || '').toLowerCase();
                const tipo = isRec ? 'recojo tienda' : 'delivery';
                if (!ped.includes(busquedaTexto) && !cli.includes(busquedaTexto) && !dir.includes(busquedaTexto) && !rep.includes(busquedaTexto) && !met.includes(busquedaTexto) && !tipo.includes(busquedaTexto)) {
                    return false;
                }
            }
            return true;
        });

        const total = filtrados.length;
        const totalPaginas = Math.ceil(total / ITEMS_PER_PAGE) || 1;
        if (paginaActual > totalPaginas) paginaActual = totalPaginas;

        const inicio = (paginaActual - 1) * ITEMS_PER_PAGE;
        const fin = Math.min(inicio + ITEMS_PER_PAGE, total);
        const paginaItems = filtrados.slice(inicio, fin);

        if (paginaItems.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding:36px; color:#94a3b8;">No se encontraron pedidos con el filtro aplicado.</td></tr>';
        } else {
            tbody.innerHTML = paginaItems.map(env => {
                const est = env.estado_delivery || 'Pendiente';
                const estClass = est.toLowerCase().replace(/\s+/g, '_');
                const nroPed = env.codigo_pedido || ('#' + String(env.id_pedido).padStart(5, '0'));
                const repAsignado = env.repartidor_nombre ? (env.repartidor_nombre + ' ' + (env.repartidor_apellido || '')) : null;
                const isRec = (env.tipo_entrega_norm === 'recojo');

                return `
                    <tr>
                        <td>
                            <strong style="color:#0f172a; font-weight:800;">${nroPed}</strong>
                            <div style="font-size:0.75rem; color:#64748b;">${env.metodo_pago || 'Pago en entrega'}</div>
                            
                        </td>
                        <td>
                            <div style="font-weight:700; color:#0f172a;">${env.nombre_destinatario || 'Cliente'}</div>
                            <span style="color:#475569; font-weight:600; font-size:0.82rem;">${env.celular_contacto || 'Sin cel.'}</span>
                        </td>
                        <td style="max-width:260px;">
                            ${isRec 
                                ? `<div style="font-size:0.85rem; color:#334155; font-weight:700;">Recojo en Tienda</div><small style="color:#64748b; font-size:0.75rem;">Local Principal - Mostrador</small>`
                                : `<div style="font-size:0.85rem; color:#334155; line-height:1.3;">${env.direccion_destino || '—'}</div>${env.referencia ? `<small style="color:#64748b; font-size:0.75rem;">Ref: ${env.referencia}</small>` : ''}`
                            }
                        </td>
                        <td style="text-align:right;">
                            <span style="font-weight:800; font-size:1.02rem; color:#0f172a;">S/ ${parseFloat(env.pedido_total || 0).toFixed(2)}</span>
                            ${parseInt(env.cobrado_por_repartidor) === 1 ? '<div style="font-size:0.70rem; color:#15803d; font-weight:700;">✓ Cobrado</div>' : ''}
                        </td>
                        <td>
                            ${isRec ? `
                                <span style="color:#64748b; font-size:0.80rem; font-style:italic;">No aplica (Recojo en tienda)</span>
                            ` : (repAsignado ? `
                                <div style="font-weight:700; color:#0f172a; font-size:0.85rem;">${repAsignado}</div>
                                <small style="color:#64748b; font-size:0.75rem;">${env.repartidor_telefono || ''}</small>
                            ` : `
                                <span style="color:#94a3b8; font-style:italic; font-size:0.82rem;">Sin asignar</span>
                            `)}
                        </td>
                        <td style="text-align:center;">
                            <span class="badge-deliv ${estClass}">
                                ${est}
                            </span>
                        </td>
                        <td style="text-align:right; white-space:nowrap;">
                            <div style="display:inline-flex; gap:6px; justify-content: flex-end;">
                                <button type="button" class="btn-action-icon detalle" title="Previsualizar Detalle del Pedido" onclick="window.abrirDetallePedidoDeliv(${env.id_pedido}, ${env.id_envio})">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                </button>
                                ${!isRec ? `
                                <button type="button" class="btn-action-icon repartidor" title="Asignar Repartidor" onclick="window.abrirModalAsignar(${env.id_envio})">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6"/><path d="M22 11h-6"/></svg>
                                </button>
                                ` : ''}
                                <button type="button" class="btn-action-icon estado" title="Cambiar Estado" onclick='window.abrirModalEstado(${JSON.stringify(env)})'>
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        renderPaginador(pagContainer, totalPaginas, paginaActual);
    }

    function renderPaginador(container, totalPaginas, actual) {
        if (!container) return;
        if (totalPaginas <= 1) {
            container.innerHTML = '';
            return;
        }

        let html = '';
        html += `<button type="button" class="pag-btn" ${actual <= 1 ? 'disabled' : ''} onclick="window.cambiarPagina(${actual - 1})" aria-label="Anterior">&lt;</button>`;

        let pages = [];
        if (totalPaginas <= 5) {
            for (let i = 1; i <= totalPaginas; i++) pages.push(i);
        } else {
            pages.push(1);
            if (actual > 3) pages.push('...');
            let start = Math.max(2, actual - 1);
            let end = Math.min(totalPaginas - 1, actual + 1);
            for (let i = start; i <= end; i++) {
                if (!pages.includes(i)) pages.push(i);
            }
            if (actual < totalPaginas - 2) pages.push('...');
            pages.push(totalPaginas);
        }

        pages.forEach(p => {
            if (p === '...') {
                html += `<span style="padding:0 4px; color:#94a3b8; font-weight:700;">...</span>`;
            } else {
                html += `<button type="button" class="pag-btn ${p === actual ? 'active' : ''}" onclick="window.cambiarPagina(${p})">${p}</button>`;
            }
        });

        html += `<button type="button" class="pag-btn" ${actual >= totalPaginas ? 'disabled' : ''} onclick="window.cambiarPagina(${actual + 1})" aria-label="Siguiente">&gt;</button>`;
        container.innerHTML = html;
    }

    window.cambiarPagina = function(p) {
        paginaActual = p;
        renderizarTabla();
    };

    window.cerrarModalDetalleDeliv = function() {
        const modal = document.getElementById('modalDetalleDeliv');
        if (modal) modal.style.display = 'none';
    };

    window.abrirDetallePedidoDeliv = function(idPedido, idEnvio) {
        const modal = document.getElementById('modalDetalleDeliv');
        const titEl = document.getElementById('delivModalTitulo');
        const bodyEl = document.getElementById('delivModalBody');
        const footAct = document.getElementById('delivModalFooterAcciones');
        if (!modal || !bodyEl) return;

        const env = listaEnvios.find(e => parseInt(e.id_envio) === parseInt(idEnvio)) || {};
        const nroPed = env.codigo_pedido || ('#' + String(idPedido).padStart(5, '0'));

        if (titEl) {
            titEl.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#0284c7" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg> <span>Detalle del Pedido ${nroPed}</span>`;
        }

        bodyEl.innerHTML = '<p style="text-align: center; color: #94a3b8; padding: 30px 0;">Cargando detalle del pedido...</p>';
        
        if (footAct) {
            footAct.innerHTML = `
                <button type="button" id="btnDelivModalAsignar" style="display:inline-flex; align-items:center; gap:6px; padding:8px 14px; background:#2563eb; color:#fff; border:none; border-radius:8px; font-weight:700; font-size:0.85rem; cursor:pointer;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6"/><path d="M22 11h-6"/></svg>
                    <span>Asignar Repartidor</span>
                </button>
                <button type="button" id="btnDelivModalEstado" style="display:inline-flex; align-items:center; gap:6px; padding:8px 14px; background:#15803d; color:#fff; border:none; border-radius:8px; font-weight:700; font-size:0.85rem; cursor:pointer;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                    <span>Cambiar Estado</span>
                </button>
            `;
            document.getElementById('btnDelivModalAsignar')?.addEventListener('click', function() {
                window.cerrarModalDetalleDeliv();
                window.abrirModalAsignar(idEnvio);
            });
            document.getElementById('btnDelivModalEstado')?.addEventListener('click', function() {
                window.cerrarModalDetalleDeliv();
                window.abrirModalEstado(env);
            });
        }

        modal.style.display = 'flex';

        fetch('index.php?action=detalle_pedido_admin&id=' + idPedido, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(res => {
            if (!res || !res.success || !res.data) {
                bodyEl.innerHTML = '<p style="color:#dc2626; text-align:center; padding:30px;">Error al cargar el detalle del pedido.</p>';
                return;
            }

            const p = res.data.pedido;
            const d = res.data.detalle || [];
            const cliente = ((p.usuario_nombre || '') + ' ' + (p.usuario_apellido || '')).trim() || (p.cliente_nombre || env.nombre_destinatario || 'Cliente');
            const cel = env.celular_contacto || 'Sin celular';
            const repAsignado = env.repartidor_nombre ? (env.repartidor_nombre + ' ' + (env.repartidor_apellido || '')) : 'Sin asignar';
            const est = env.estado_delivery || p.estado || 'Pendiente';
            const estClass = est.toLowerCase().replace(' ', '_');

            function esc(s) {
                const el = document.createElement('div');
                el.textContent = String(s || '');
                return el.innerHTML;
            }

            let info = '<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(140px, 1fr)); gap:10px; margin-bottom:14px;">';
            info += `<div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:10px 12px;"><small style="display:block; color:#94a3b8; font-size:.70rem; font-weight:800; text-transform:uppercase;">Destinatario</small><strong style="font-size:.88rem; color:#0f172a;">${esc(cliente)}</strong></div>`;
            info += `<div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:10px 12px;"><small style="display:block; color:#94a3b8; font-size:.70rem; font-weight:800; text-transform:uppercase;">Celular</small><strong style="font-size:.88rem; color:#15803d;">${esc(cel)}</strong></div>`;
            info += `<div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:10px 12px;"><small style="display:block; color:#94a3b8; font-size:.70rem; font-weight:800; text-transform:uppercase;">Repartidor</small><strong style="font-size:.88rem; color:#1d4ed8;">${esc(repAsignado)}</strong></div>`;
            info += `<div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:10px 12px;"><small style="display:block; color:#94a3b8; font-size:.70rem; font-weight:800; text-transform:uppercase;">Estado Envío</small><span class="badge-deliv ${estClass}" style="margin-top:2px;">${esc(est)}</span></div>`;
            info += `<div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:10px 12px;"><small style="display:block; color:#94a3b8; font-size:.70rem; font-weight:800; text-transform:uppercase;">Pago</small><strong style="font-size:.88rem; color:#0f172a;">${esc(p.metodo_pago || env.metodo_pago || '—')}</strong></div>`;
            info += `<div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:10px 12px;"><small style="display:block; color:#94a3b8; font-size:.70rem; font-weight:800; text-transform:uppercase;">Total Pedido</small><strong style="font-size:.95rem; color:#15803d;">S/ ${parseFloat(p.total || env.pedido_total || 0).toFixed(2)}</strong></div>`;
            info += '</div>';

            const dirDest = env.direccion_destino || p.direccion_envio;
            if (dirDest) {
                info += `<div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:10px 14px; margin-bottom:12px;">
                    <small style="display:block; color:#94a3b8; font-size:.70rem; font-weight:800; text-transform:uppercase; margin-bottom:2px;">Dirección de Entrega</small>
                    <span style="font-size:.88rem; color:#0f172a; font-weight:600;">${esc(dirDest)}</span>
                    ${env.referencia ? `<div style="font-size:.78rem; color:#64748b; margin-top:2px;">Ref: ${esc(env.referencia)}</div>` : ''}
                </div>`;
            }

            if (p.observacion || env.notas_entrega) {
                info += `<div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:10px 14px; margin-bottom:14px;">
                    <small style="display:block; color:#94a3b8; font-size:.70rem; font-weight:800; text-transform:uppercase; margin-bottom:2px;">Notas / Observación</small>
                    <span style="font-size:.85rem; color:#475569;">${esc(p.observacion || env.notas_entrega)}</span>
                </div>`;
            }

            let prodsHtml = `<h4 style="margin:0 0 10px; font-size:.82rem; color:#475569; text-transform:uppercase; letter-spacing:.05em; font-weight:800;">Productos a Entregar (${d.length})</h4><div style="background:#fff; border:1px solid #f1f5f9; border-radius:10px; padding:0 12px;">`;
            if (d.length === 0) {
                prodsHtml += '<p style="color:#94a3b8; text-align:center; padding:18px;">Sin productos registrados en este pedido.</p>';
            } else {
                d.forEach(item => {
                    const imgSrc = item.imagen ? esc(item.imagen) : 'Images/icono.png';
                    prodsHtml += `
                        <div class="deliv-product-row">
                            <img src="${imgSrc}" alt="${esc(item.titulo)}" onerror="this.src='Images/icono.png'">
                            <div>
                                <strong style="color:#0f172a; font-size:.88rem; display:block;">${esc(item.titulo)}</strong>
                                <span style="font-size:.78rem; color:#64748b;">${item.cantidad} ${esc(item.unidad_medida || 'und.')} × S/ ${parseFloat(item.precio_unitario).toFixed(2)}</span>
                            </div>
                            <div style="text-align:right; font-weight:800; color:#15803d; font-size:.90rem;">
                                S/ ${parseFloat(item.subtotal).toFixed(2)}
                            </div>
                        </div>
                    `;
                });
            }
            prodsHtml += '</div>';

            bodyEl.innerHTML = info + prodsHtml;
        })
        .catch(() => {
            bodyEl.innerHTML = '<p style="color:#dc2626; text-align:center; padding:30px;">Error de conexión al cargar detalle.</p>';
        });
    };

    const modalDetalleDelivEl = document.getElementById('modalDetalleDeliv');
    if (modalDetalleDelivEl) {
        modalDetalleDelivEl.addEventListener('click', function(e) {
            if (e.target === modalDetalleDelivEl) {
                window.cerrarModalDetalleDeliv();
            }
        });
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            window.cerrarModalDetalleDeliv();
        }
    });

    window.abrirModalAsignar = function(idEnvio) {
        document.getElementById('idEnvioAsignar').value = idEnvio;
        document.getElementById('modalAsignar').style.display = 'flex';
    };
    window.cerrarModalAsignar = function() {
        document.getElementById('modalAsignar').style.display = 'none';
    };

    window.abrirModalEstado = function(env) {
        const isRec = (env.tipo_entrega_norm === 'recojo');
        document.getElementById('idEnvioEstado').value = env.id_envio || 0;
        document.getElementById('idPedidoEstado').value = env.id_pedido || 0;

        const tit = document.getElementById('delivModalEstadoTitulo');
        const sub = document.getElementById('delivModalEstadoSubtitulo');
        const sel = document.getElementById('selectNuevoEstado');
        const lblCobrado = document.getElementById('lblCobradoTexto');

        if (tit) tit.textContent = isRec ? 'Actualizar Estado — Recojo en Tienda' : 'Actualizar Estado — Envío Delivery';
        if (sub) sub.textContent = isRec 
            ? 'Actualice la preparación del pedido para entrega al cliente en tienda.' 
            : 'Cambie el progreso logístico del pedido y registre cobro en caso aplique.';

        if (sel) {
            let options = '';
            if (isRec) {
                options = `
                    <option value="Pendiente">Pendiente</option>
                    <option value="En Preparacion">En Preparación</option>
                    <option value="Listo para Recojo">Listo para Recojo</option>
                    <option value="Entregado">Entregado</option>
                    <option value="Cancelado">Cancelado</option>
                `;
            } else {
                options = `
                    <option value="Pendiente">Pendiente</option>
                    <option value="En Preparacion">En Preparación</option>
                    <option value="Asignado">Asignado</option>
                    <option value="En Ruta">En Ruta</option>
                    <option value="Entregado">Entregado</option>
                    <option value="Fallido">Fallido / No entregado</option>
                `;
            }
            sel.innerHTML = options;
            sel.value = env.estado_delivery || 'Pendiente';
        }

        if (lblCobrado) {
            lblCobrado.textContent = isRec ? '¿Pago presencial completado en caja/mostrador?' : '¿Dinero cobrado por el repartidor?';
        }

        document.getElementById('chkCobrado').checked = parseInt(env.cobrado_por_repartidor) === 1;
        document.getElementById('txtNotasEstado').value = env.notas_entrega || env.observacion || '';
        document.getElementById('modalEstado').style.display = 'flex';
    };
    window.cerrarModalEstado = function() {
        document.getElementById('modalEstado').style.display = 'none';
    };

    // AJAX: ASIGNAR REPARTIDOR CON MODAL DE CONFIRMACIÓN Y NOTIFICACIÓN ARRIBA
    window.enviarAsignarAjax = function(e) {
        e.preventDefault();
        const idEnvio = document.getElementById('idEnvioAsignar')?.value;
        const sel = document.getElementById('selectRepartidor');
        const idRepartidor = sel ? sel.value : null;

        if (!idEnvio || !idRepartidor) {
            alert('Por favor selecciona un repartidor.');
            return;
        }

        const optSelected = sel.options[sel.selectedIndex];
        const repNombre = optSelected.getAttribute('data-nombre') || '';
        const repApellido = optSelected.getAttribute('data-apellido') || '';
        const repTelefono = optSelected.getAttribute('data-telefono') || '';

        // Cerrar modal de asignación para que no quede visible debajo
        window.cerrarModalAsignar();

        window.abrirDelivConfirmacion(
            '¿Asignar repartidor al envío?',
            'El repartidor ' + repNombre + ' ' + repApellido + ' será asignado al despacho de este pedido.',
            function() {
                const formData = new FormData();
                formData.append('id_envio', idEnvio);
                formData.append('id_repartidor', idRepartidor);
                formData.append('is_ajax', '1');

                fetch('index.php?action=asignar_repartidor', {
                    method: 'POST',
                    body: formData,
                    headers: { 
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(r => r.json())
                .then(res => {
                    if (res && res.success) {
                        window.mostrarDelivToast('Éxito', res.message || 'Repartidor asignado con éxito.', false);
                        const item = listaEnvios.find(x => String(x.id_envio) === String(idEnvio));
                        if (item) {
                            item.id_repartidor = idRepartidor;
                            item.repartidor_nombre = repNombre;
                            item.repartidor_apellido = repApellido;
                            item.repartidor_telefono = repTelefono;
                            if (item.estado_delivery === 'Pendiente') {
                                item.estado_delivery = 'Asignado';
                            }
                        }
                        window.actualizarContadoresDelivUI();
                        renderizarTabla();
                    } else {
                        window.mostrarDelivToast('Error', (res && res.message) || 'No se pudo asignar el repartidor.', true);
                    }
                })
                .catch(() => {
                    window.mostrarDelivToast('Error', 'Error de conexión al asignar repartidor.', true);
                });
            },
            false,
            function() {
                document.getElementById('modalAsignar').style.display = 'flex';
            }
        );
    };

    // AJAX: CAMBIAR ESTADO DELIVERY/RECOJO CON MODAL DE CONFIRMACIÓN Y NOTIFICACIÓN ARRIBA
    window.enviarEstadoAjax = function(e) {
        e.preventDefault();
        const idEnvio = document.getElementById('idEnvioEstado')?.value || 0;
        const idPedido = document.getElementById('idPedidoEstado')?.value || 0;
        const nuevoEstado = document.getElementById('selectNuevoEstado')?.value;
        const cobrado = document.getElementById('chkCobrado')?.checked ? 1 : 0;
        const notas = document.getElementById('txtNotasEstado')?.value || '';

        if ((!idEnvio && !idPedido) || !nuevoEstado) {
            return;
        }

        // Cerrar modal de estados inmediatamente
        window.cerrarModalEstado();

        window.abrirDelivConfirmacion(
            '¿Confirmar cambio de estado?',
            'El estado del pedido cambiará a: ' + nuevoEstado + (cobrado ? ' (Registrado como cobrado/pagado)' : '') + '.',
            function() {
                const formData = new FormData();
                formData.append('id_envio', idEnvio);
                formData.append('id_pedido', idPedido);
                formData.append('nuevo_estado', nuevoEstado);
                formData.append('cobrado', cobrado);
                formData.append('notas', notas);
                formData.append('is_ajax', '1');

                fetch('index.php?action=actualizar_estado_delivery', {
                    method: 'POST',
                    body: formData,
                    headers: { 
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(r => r.json())
                .then(res => {
                    if (res && res.success) {
                        window.mostrarDelivToast('Éxito', res.message || 'Estado actualizado.', false);
                        const item = listaEnvios.find(x => 
                            (idEnvio && parseInt(x.id_envio) === parseInt(idEnvio)) || 
                            (idPedido && parseInt(x.id_pedido) === parseInt(idPedido))
                        );
                        if (item) {
                            item.estado_delivery = nuevoEstado;
                            item.pedido_estado = nuevoEstado;
                            item.cobrado_por_repartidor = cobrado;
                            item.notas_entrega = notas;
                        }
                        window.actualizarContadoresDelivUI();
                        renderizarTabla();
                    } else {
                        window.mostrarDelivToast('Error', (res && res.message) || 'No se pudo actualizar el estado.', true);
                    }
                })
                .catch(() => {
                    window.mostrarDelivToast('Error', 'Error de conexión al actualizar estado.', true);
                });
            },
            false,
            function() {
                document.getElementById('modalEstado').style.display = 'flex';
            }
        );
    };

    // Render inicial
    renderizarTabla();
</script>

<?php if (!AjaxHelper::isAjax()): ?>
            </div>
        </div>
    </div>
</body>
</html>
<?php endif; ?>
