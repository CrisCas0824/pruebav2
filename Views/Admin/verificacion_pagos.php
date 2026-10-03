<?php
require_once 'Core/ajaxHelper.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$nombre = htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Administrador');
$flash_success = $_SESSION['flash_success'] ?? null;
$flash_error = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

$verificaciones = $verificaciones ?? [];
$filtro = $_GET['estado'] ?? 'todos';

// Cálculo de estadísticas iniciales
$totalAuditados = count($verificaciones);
$pendientesCount = 0;
$aprobadosCount = 0;
$rechazadosCount = 0;
$montoAprobado = 0.00;

foreach ($verificaciones as $v) {
    $est = $v['estado_verificacion'] ?? 'Pendiente';
    if ($est === 'Pendiente') {
        $pendientesCount++;
    } elseif ($est === 'Aprobado') {
        $aprobadosCount++;
        $montoAprobado += (float)($v['monto_reportado'] ?? 0);
    } elseif ($est === 'Rechazado') {
        $rechazadosCount++;
    }
}
?>
<?php if (!AjaxHelper::isAjax()): ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auditoría de Pagos Yape & Plin — ERP La Caserita</title>
    <link rel="icon" type="image/png" href="Images/logo.png">
    <link rel="stylesheet" href="Styles/global.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="Styles/styles-home.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="Styles/styles-users-admin.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="Styles/admin-spa.css?v=<?= time(); ?>">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="Scripts/admin-spa.js?v=<?= time(); ?>" defer></script>
<link rel="stylesheet" href="Styles/Vistas/Admin/verificacion_pagos.css?v=<?= filemtime(__DIR__ . '/../../Styles/Vistas/Admin/verificacion_pagos.css'); ?>">
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
                        <span class="brand-title">Auditoría de Pagos Digitales</span>
                    </div>
                </div>
                <div class="admin-topbar-actions">
                    <?php include __DIR__ . '/partials/admin_topbar_actions.php'; ?>
                </div>
            </header>

            <div id="admin-container">
<?php endif; ?>

<!-- NOTIFICACIÓN TOAST SUPERIOR (IMAGEN 4) -->
<div id="verifTopToast" class="top-toast <?= ($flash_success || $flash_error) ? 'show' : ''; ?>">
    <div class="top-toast-icon" id="verifToastIcon" style="background: <?= $flash_error ? '#dc2626' : '#15803d'; ?>;">
        <svg id="verifToastSvg" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
            <?= $flash_error ? '<line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line>' : '<polyline points="20 6 9 17 4 12"></polyline>'; ?>
        </svg>
    </div>
    <div class="top-toast-body">
        <strong id="verifToastTitle"><?= $flash_error ? 'Error' : 'Éxito' ?></strong>
        <span id="verifToastMsg"><?= htmlspecialchars($flash_error ?: ($flash_success ?: 'Operación completada con éxito.')); ?></span>
    </div>
    <button type="button" class="top-toast-close" id="verifToastClose" aria-label="Cerrar">&times;</button>
    <div class="top-toast-progress" id="verifToastProgress" style="background: <?= $flash_error ? '#dc2626' : '#15803d'; ?>;"></div>
</div>

<!-- Modal Confirmación Reutilizable (Imagen 3 / Ventas) -->
<div class="verif-modal-overlay" id="verifConfirmModal" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.60); z-index: 999999; backdrop-filter: blur(3px); align-items: center; justify-content: center; padding: 16px;">
    <div style="background: #ffffff; border-radius: 18px; width: min(440px, 94vw); padding: 28px 24px; text-align: center; box-shadow: 0 24px 60px rgba(0,0,0,0.25); margin: auto;">
        <div id="verifConfirmIconBox" style="width: 56px; height: 56px; border-radius: 50%; background: #dcfce7; color: #15803d; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 16px;">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
        </div>
        <h3 id="verifConfirmModalTitulo" style="margin: 0 0 8px; font-size: 1.2rem; font-weight: 800; color: #0f172a;">¿Confirmar acción?</h3>
        <p id="verifConfirmModalMsg" style="margin: 0 0 22px; font-size: 0.90rem; color: #64748b; line-height: 1.5;">
            Mensaje de confirmación.
        </p>
        <div style="display: flex; gap: 12px; justify-content: center;">
            <button type="button" onclick="window.cerrarVerifConfirmacion()" style="padding: 10px 20px; border-radius: 8px; border: 1px solid #cbd5e1; background: #ffffff; color: #475569; font-weight: 700; font-size: 0.88rem; cursor: pointer;">
                Cancelar
            </button>
            <button type="button" id="btnVerifConfirmarProceder" style="padding: 10px 22px; border-radius: 8px; border: none; background: #15803d; color: #ffffff; font-weight: 700; font-size: 0.88rem; cursor: pointer; box-shadow: 0 2px 8px rgba(21,128,61,0.25);">
                Sí, Confirmar
            </button>
        </div>
    </div>
</div>

<main class="main-content container-admin" style="padding-top: 25px; padding-bottom: 50px;">
    

    <!-- HEADER DE SECCIÓN (CON MÁS ESPACIO) -->
    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px; margin-bottom: 22px;">
        <div>
            <h1 style="font-size: 1.55rem; font-weight: 800; color: #0f172a; margin: 0 0 6px 0; letter-spacing: -0.02em;">Auditoría de Pagos Yape & Plin</h1>
            <p style="color: #64748b; font-size: 0.88rem; margin: 0;">
                Valida comprobantes emitidos por billeteras digitales, aprueba la liberación automática del pedido o recházalo con motivo documentado.
            </p>
        </div>
    </div>

    <!-- TARJETAS CUADRADAS COMPACTAS (IMAGEN 2) -->
    <div class="verif-stats-grid">
        <div class="verif-stat-card <?= $filtro === 'todos' ? 'active-card' : ''; ?>" id="cardVerifTodos" onclick="window.filtrarEstadoVerif('todos')">
            <span class="verif-stat-title">TOTAL AUDITADOS</span>
            <span class="verif-stat-number" id="lblTotalAuditados"><?= $totalAuditados; ?></span>
        </div>
        <div class="verif-stat-card <?= $filtro === 'Pendiente' ? 'active-card' : ''; ?>" id="cardVerifPend" onclick="window.filtrarEstadoVerif('Pendiente')">
            <span class="verif-stat-title">PENDIENTES</span>
            <span class="verif-stat-number" id="lblPendientesCount" style="color: #d97706;"><?= $pendientesCount; ?></span>
        </div>
        <div class="verif-stat-card <?= $filtro === 'Aprobado' ? 'active-card' : ''; ?>" id="cardVerifAprob" onclick="window.filtrarEstadoVerif('Aprobado')">
            <span class="verif-stat-title">APROBADOS</span>
            <span class="verif-stat-number" id="lblAprobadosCount" style="color: #15803d;"><?= $aprobadosCount; ?></span>
        </div>
        <div class="verif-stat-card <?= $filtro === 'Rechazado' ? 'active-card' : ''; ?>" id="cardVerifRech" onclick="window.filtrarEstadoVerif('Rechazado')">
            <span class="verif-stat-title">RECHAZADOS</span>
            <span class="verif-stat-number" id="lblRechazadosCount" style="color: #dc2626;"><?= $rechazadosCount; ?></span>
        </div>
        <div class="verif-stat-card" onclick="window.filtrarEstadoVerif('Aprobado')">
            <span class="verif-stat-title">MONTO APROBADO</span>
            <span class="verif-stat-number" id="lblMontoAprobado" style="font-size: 1.15rem; color: #15803d;">S/ <?= number_format($montoAprobado, 2); ?></span>
        </div>
    </div>

    <!-- PANEL PRINCIPAL CON TABLA -->
    <div class="verif-card-panel">
        <div class="verif-card-header">
            <div class="verif-card-title">Bandeja de Vouchers & Comprobantes</div>
            <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                <input type="text" id="busquedaVerifInput" class="verif-search-input" placeholder="Buscar por código, cliente o pedido..." oninput="window.filtrarVerificacionesEnVivo()">
            </div>
        </div>

        <div style="overflow-x: auto;">
            <table class="verif-table">
                <thead>
                    <tr>
                        <th>FECHA / HORA</th>
                        <th>PEDIDO</th>
                        <th>CLIENTE</th>
                        <th>MÉTODO</th>
                        <th>CÓD. OPERACIÓN</th>
                        <th>TELÉFONO EMISOR</th>
                        <th style="text-align: right;">MONTO</th>
                        <th style="text-align: center;">VOUCHER</th>
                        <th style="text-align: center;">ESTADO</th>
                        <th style="text-align: right;">ACCIONES</th>
                    </tr>
                </thead>
                <tbody id="tbodyVerif">
                    <!-- Filas renderizadas por JS con paginación de 5 en 5 -->
                </tbody>
            </table>
        </div>

        <!-- Paginación estilo Imagen 3 (< 1 2 3 ... >) -->
        <div class="verif-pagination-bar" id="paginacionVerifBar"></div>
    </div>
</main>

<!-- Modal Zoom Voucher Lightbox -->
<div id="modalZoom" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.75); z-index: 99999; justify-content: center; align-items: center; padding: 20px;" onclick="window.cerrarZoom()">
    <div style="max-width: 90%; max-height: 90%; position: relative;" onclick="event.stopPropagation()">
        <button type="button" onclick="window.cerrarZoom()" style="position: absolute; top: -14px; right: -14px; width: 34px; height: 34px; border-radius: 50%; background: #ffffff; color: #0f172a; border: none; font-size: 1.2rem; cursor: pointer; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(0,0,0,0.3);">&times;</button>
        <img id="imgZoomVoucher" src="" style="max-width: 100%; max-height: 85vh; border-radius: 12px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);">
    </div>
</div>

<!-- Modal Rechazo -->
<div id="modalRechazo" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999; justify-content: center; align-items: center; padding: 20px;">
    <div style="background: #ffffff; border-radius: 16px; width: 100%; max-width: 460px; padding: 24px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.15);">
        <h3 style="margin: 0 0 10px; font-size: 1.2rem; font-weight: 800; color: #dc2626;">Rechazar Comprobante de Pago</h3>
        <p style="margin: 0 0 16px; font-size: 0.85rem; color: #64748b;">Indique el motivo por el cual no coincide el comprobante. El pedido quedará cancelado en el sistema.</p>
        <form id="formRechazoModal" onsubmit="window.enviarRechazoAjax(event)">
            <input type="hidden" name="id_verificacion" id="idVerifRechazo">
            <div style="margin-bottom: 18px;">
                <label style="display: block; font-size: 0.82rem; font-weight: 700; color: #334155; margin-bottom: 6px; text-transform: uppercase;">Motivo de Rechazo</label>
                <textarea name="motivo_rechazo" id="txtMotivoRechazo" required rows="3" placeholder="Ej: No figura en los movimientos bancarios / Monto no coincide" style="width: 100%; padding: 10px 12px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 0.90rem; box-sizing: border-box; outline: none;"></textarea>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" onclick="window.cerrarModalRechazo()" style="padding: 9px 18px; background: #f1f5f9; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; color: #475569;">Cancelar</button>
                <button type="submit" style="padding: 9px 20px; background: #dc2626; color: #ffffff; border: none; border-radius: 8px; font-weight: 800; cursor: pointer;">Rechazar Pago</button>
            </div>
        </form>
    </div>
</div>

<script>
    let listaVerificaciones = <?= json_encode($verificaciones); ?>;
    let filtroEstado = '<?= $filtro; ?>';
    let busquedaTexto = '';
    let paginaActual = 1;
    const ITEMS_PER_PAGE = 5;
    let accionVerifConfirmacion = null;

    // Modal Confirmación (Imagen 3 / Ventas)
    window.abrirVerifConfirmacion = function(titulo, mensaje, accion, esPeligro = false) {
        const modal = document.getElementById('verifConfirmModal');
        const titEl = document.getElementById('verifConfirmModalTitulo');
        const msgEl = document.getElementById('verifConfirmModalMsg');
        const iconBox = document.getElementById('verifConfirmIconBox');
        const btnProc = document.getElementById('btnVerifConfirmarProceder');

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
                btnProc.textContent = 'Sí, Rechazar';
            }
        } else {
            if (iconBox) {
                iconBox.style.background = '#dcfce7';
                iconBox.style.color = '#15803d';
                iconBox.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>';
            }
            if (btnProc) {
                btnProc.style.background = '#15803d';
                btnProc.textContent = 'Sí, Aprobar Pago';
            }
        }

        accionVerifConfirmacion = accion;
        if (btnProc) {
            btnProc.onclick = function() {
                window.cerrarVerifConfirmacion();
                if (typeof accionVerifConfirmacion === 'function') accionVerifConfirmacion();
            };
        }
        if (modal) modal.style.display = 'flex';
    };

    window.cerrarVerifConfirmacion = function() {
        const modal = document.getElementById('verifConfirmModal');
        if (modal) modal.style.display = 'none';
        accionVerifConfirmacion = null;
    };

    // Toast function (Imagen 4)
    window.mostrarVerifToast = function(titulo, mensaje, esError = false) {
        const toast = document.getElementById('verifTopToast');
        const tTitle = document.getElementById('verifToastTitle');
        const tMsg = document.getElementById('verifToastMsg');
        const tIcon = document.getElementById('verifToastIcon');
        const tProg = document.getElementById('verifToastProgress');
        const tSvg = document.getElementById('verifToastSvg');

        if (!toast) return;

        tTitle.textContent = titulo;
        tMsg.textContent = mensaje;
        tIcon.style.background = esError ? '#dc2626' : '#15803d';
        tProg.style.background = esError ? '#dc2626' : '#15803d';
        tSvg.innerHTML = esError 
            ? '<line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line>'
            : '<polyline points="20 6 9 17 4 12"></polyline>';

        toast.classList.add('show');
        setTimeout(() => toast.classList.remove('show'), 3800);
    };

    (function initToastEvents() {
        const closeBtn = document.getElementById('verifToastClose');
        const toast = document.getElementById('verifTopToast');
        if (closeBtn && toast) {
            closeBtn.addEventListener('click', () => toast.classList.remove('show'));
        }
        if (toast && toast.classList.contains('show')) {
            setTimeout(() => toast.classList.remove('show'), 3800);
        }
    })();

    // Recalcular contadores en tarjetas superiores
    function actualizarContadoresUI() {
        let total = listaVerificaciones.length;
        let pend = 0;
        let aprob = 0;
        let rech = 0;
        let monto = 0;

        listaVerificaciones.forEach(v => {
            const e = v.estado_verificacion || 'Pendiente';
            if (e === 'Pendiente') pend++;
            else if (e === 'Aprobado') {
                aprob++;
                monto += parseFloat(v.monto_reportado || 0);
            }
            else if (e === 'Rechazado') rech++;
        });

        const elTot = document.getElementById('lblTotalAuditados');
        const elPend = document.getElementById('lblPendientesCount');
        const elAprob = document.getElementById('lblAprobadosCount');
        const elRech = document.getElementById('lblRechazadosCount');
        const elMonto = document.getElementById('lblMontoAprobado');

        if (elTot) elTot.textContent = total;
        if (elPend) elPend.textContent = pend;
        if (elAprob) elAprob.textContent = aprob;
        if (elRech) elRech.textContent = rech;
        if (elMonto) elMonto.textContent = 'S/ ' + monto.toFixed(2);
    }

    // ACCIÓN APROBAR PAGO (CON MODAL DE CONFIRMACIÓN Y NOTIFICACIÓN ARRIBA)
    window.aprobarPagoDirecto = function(idVerif, monto) {
        window.abrirVerifConfirmacion(
            '¿Aprobar comprobante de pago?',
            'Se validará el pago de S/ ' + parseFloat(monto || 0).toFixed(2) + ', se actualizará el kardex y el pedido pasará a preparación.',
            function() {
                const formData = new FormData();
                formData.append('id_verificacion', idVerif);

                fetch('index.php?action=aprobar_verificacion_pago', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(r => r.json())
                .then(res => {
                    if (res && res.success) {
                        window.mostrarVerifToast('Éxito', res.message || 'Pago aprobado correctamente.', false);
                        const item = listaVerificaciones.find(v => String(v.id_verificacion) === String(idVerif));
                        if (item) item.estado_verificacion = 'Aprobado';
                        actualizarContadoresUI();
                        renderizarTabla();
                    } else {
                        window.mostrarVerifToast('Error', (res && res.message) || 'No se pudo aprobar el comprobante.', true);
                    }
                })
                .catch(() => {
                    window.mostrarVerifToast('Error', 'Error de conexión al aprobar.', true);
                });
            },
            false
        );
    };

    // ACCIÓN RECHAZAR PAGO (CON MODAL DE CONFIRMACIÓN Y NOTIFICACIÓN ARRIBA)
    window.enviarRechazoAjax = function(e) {
        e.preventDefault();
        const idVerif = document.getElementById('idVerifRechazo')?.value;
        const motivo = document.getElementById('txtMotivoRechazo')?.value;

        if (!idVerif) return;

        window.abrirVerifConfirmacion(
            '¿Confirmar rechazo de este pago?',
            'El pedido quedará registrado como cancelado y el comprobante como rechazado.',
            function() {
                const formData = new FormData();
                formData.append('id_verificacion', idVerif);
                formData.append('motivo_rechazo', motivo);

                fetch('index.php?action=rechazar_verificacion_pago', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(r => r.json())
                .then(res => {
                    window.cerrarModalRechazo();
                    if (res && res.success) {
                        window.mostrarVerifToast('Atención', res.message || 'Pago rechazado correctamente.', false);
                        const item = listaVerificaciones.find(v => String(v.id_verificacion) === String(idVerif));
                        if (item) item.estado_verificacion = 'Rechazado';
                        actualizarContadoresUI();
                        renderizarTabla();
                    } else {
                        window.mostrarVerifToast('Error', (res && res.message) || 'No se pudo rechazar.', true);
                    }
                })
                .catch(() => {
                    window.cerrarModalRechazo();
                    window.mostrarVerifToast('Error', 'Error de conexión al rechazar.', true);
                });
            },
            true
        );
    };

    // FILTRO POR ESTADO AL CLIC EN TARJETAS (IMAGEN 2)
    window.filtrarEstadoVerif = function(est) {
        filtroEstado = est;
        paginaActual = 1;
        document.querySelectorAll('.verif-stat-card').forEach(c => c.classList.remove('active-card'));
        if (est === 'todos') document.getElementById('cardVerifTodos')?.classList.add('active-card');
        else if (est === 'Pendiente') document.getElementById('cardVerifPend')?.classList.add('active-card');
        else if (est === 'Aprobado') document.getElementById('cardVerifAprob')?.classList.add('active-card');
        else if (est === 'Rechazado') document.getElementById('cardVerifRech')?.classList.add('active-card');
        renderizarTabla();
    };

    window.filtrarVerificacionesEnVivo = function() {
        busquedaTexto = (document.getElementById('busquedaVerifInput')?.value || '').toLowerCase().trim();
        paginaActual = 1;
        renderizarTabla();
    };

    function renderizarTabla() {
        const tbody = document.getElementById('tbodyVerif');
        const pagContainer = document.getElementById('paginacionVerifBar');
        if (!tbody) return;

        const filtrados = listaVerificaciones.filter(v => {
            const est = v.estado_verificacion || 'Pendiente';
            if (filtroEstado !== 'todos' && est !== filtroEstado) return false;

            if (busquedaTexto !== '') {
                const cod = (v.codigo_operacion || '').toLowerCase();
                const cli = ((v.cliente_nombre || '') + ' ' + (v.cliente_apellido || '')).toLowerCase();
                const ped = (v.codigo_pedido || '#' + v.id_pedido).toLowerCase();
                const tel = (v.celular_remitente || '').toLowerCase();
                if (!cod.includes(busquedaTexto) && !cli.includes(busquedaTexto) && !ped.includes(busquedaTexto) && !tel.includes(busquedaTexto)) {
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
            tbody.innerHTML = '<tr><td colspan="10" style="text-align:center; padding:36px; color:#94a3b8;">No se encontraron comprobantes con el filtro aplicado.</td></tr>';
        } else {
            tbody.innerHTML = paginaItems.map(v => {
                const esPend = (v.estado_verificacion || 'Pendiente') === 'Pendiente';
                const estLower = (v.estado_verificacion || 'Pendiente').toLowerCase();
                const esYape = (v.metodo_nombre || '').toLowerCase().includes('yape');
                const colorMet = esYape ? '#7c3aed' : '#0284c7';
                const bgMet = esYape ? '#f5f3ff' : '#f0f9ff';
                const nroPed = v.codigo_pedido || ('#' + String(v.id_pedido).padStart(5, '0'));
                const fecha = v.fecha_envio ? new Date(v.fecha_envio).toLocaleString('es-PE', { day:'2-digit', month:'2-digit', year:'numeric', hour:'2-digit', minute:'2-digit' }) : '—';

                return `
                    <tr>
                        <td style="color:#64748b; font-size:0.82rem; white-space:nowrap;">${fecha}</td>
                        <td>
                            <strong style="color:#0f172a; font-weight:800;">${nroPed}</strong>
                            <div style="font-size:0.75rem; color:#64748b;">Total: S/ ${parseFloat(v.pedido_total || 0).toFixed(2)}</div>
                        </td>
                        <td>
                            <div style="font-weight:700; color:#0f172a;">${(v.cliente_nombre || '')} ${(v.cliente_apellido || '')}</div>
                            <small style="color:#64748b; font-size:0.75rem;">${v.cliente_correo || '—'}</small>
                        </td>
                        <td>
                            <span style="background:${bgMet}; color:${colorMet}; padding:3px 8px; border-radius:6px; font-weight:800; font-size:0.78rem;">
                                ${v.metodo_nombre || 'Billetera'}
                            </span>
                        </td>
                        <td>
                            <code style="font-size:0.88rem; font-weight:800; color:#0f172a; background:#f1f5f9; padding:2px 6px; border-radius:4px;">
                                ${v.codigo_operacion || 'S/N'}
                            </code>
                        </td>
                        <td>
                            <span style="font-weight:600; color:#334155; font-size:0.85rem;">${v.celular_remitente || '—'}</span>
                            ${v.titular_origen ? `<div style="font-size:0.72rem; color:#64748b;">Titular: ${v.titular_origen}</div>` : ''}
                        </td>
                        <td style="text-align:right; font-weight:800; font-size:1.02rem; color:#0f172a;">
                            S/ ${parseFloat(v.monto_reportado || 0).toFixed(2)}
                        </td>
                        <td style="text-align:center;">
                            ${v.captura_voucher ? 
                                `<img src="${v.captura_voucher}" class="voucher-thumb" alt="Voucher" onclick="window.verVoucherZoom('${v.captura_voucher}')">` : 
                                `<span style="color:#94a3b8; font-size:0.78rem;">Sin foto</span>`
                            }
                        </td>
                        <td style="text-align:center;">
                            <span class="badge-verif ${estLower}">
                                ${v.estado_verificacion || 'Pendiente'}
                            </span>
                        </td>
                        <td style="text-align:right; white-space:nowrap;">
                            ${esPend ? `
                                <div style="display:inline-flex; gap:6px; justify-content: flex-end;">
                                    <button type="button" class="btn-action-icon aprobar" title="Aprobar Pago" onclick="window.aprobarPagoDirecto(${v.id_verificacion}, ${parseFloat(v.monto_reportado || 0)})">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                    </button>
                                    <button type="button" class="btn-action-icon rechazar" title="Rechazar" onclick="window.abrirModalRechazo(${v.id_verificacion})">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                                    </button>
                                </div>
                            ` : `
                                <small style="color:#64748b; font-size:0.75rem;">Auditado por ${v.verificador_nombre || 'Sistema'}</small>
                            `}
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

        html += `<button type="button" class="pag-btn ${actual >= totalPaginas ? 'disabled' : ''} onclick="window.cambiarPagina(${actual + 1})" aria-label="Siguiente">&gt;</button>`;
        container.innerHTML = html;
    }

    window.cambiarPagina = function(p) {
        paginaActual = p;
        renderizarTabla();
    };

    window.verVoucherZoom = function(src) {
        const img = document.getElementById('imgZoomVoucher');
        const modal = document.getElementById('modalZoom');
        if (img && modal) {
            img.src = src;
            modal.style.display = 'flex';
        }
    };
    window.cerrarZoom = function() {
        const modal = document.getElementById('modalZoom');
        if (modal) modal.style.display = 'none';
    };

    window.abrirModalRechazo = function(id) {
        const inp = document.getElementById('idVerifRechazo');
        const modal = document.getElementById('modalRechazo');
        if (inp) inp.value = id;
        if (modal) modal.style.display = 'flex';
    };
    window.cerrarModalRechazo = function() {
        const modal = document.getElementById('modalRechazo');
        if (modal) modal.style.display = 'none';
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
