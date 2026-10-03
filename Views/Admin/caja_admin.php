<?php
require_once 'Core/ajaxHelper.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin') {
    if (AjaxHelper::isAjax()) { AjaxHelper::jsonError('No autorizado.', 403); }
    header('Location: index.php?action=login'); exit();
}

$nombre = htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Administrador');
$flash_success = $_SESSION['flash_success'] ?? null;
$flash_error = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

$estaAbierta = !empty($sesionActiva);
$isAjax = AjaxHelper::isAjax();
?>
<?php if (!$isAjax): ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Caja Chica & Arqueo — ERP La Caserita</title>
    <link rel="icon" type="image/png" href="Images/logo.png">
    <link rel="stylesheet" href="Styles/global.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="Styles/styles-home.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="Styles/styles-users-admin.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="Styles/admin-spa.css?v=<?= time(); ?>">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="Scripts/admin-spa.js?v=<?= time(); ?>" defer></script>
<link rel="stylesheet" href="Styles/Vistas/Admin/caja_admin.css?v=<?= filemtime(__DIR__ . '/../../Styles/Vistas/Admin/caja_admin.css'); ?>">
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
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"></rect><line x1="12" y1="8" x2="12" y2="16"></line><line x1="8" y1="12" x2="16" y2="12"></line></svg>
                        </span>
                        <span class="brand-title">Control de Caja Chica & Arqueo</span>
                    </div>
                </div>
                <div class="admin-topbar-actions">
                    <?php include __DIR__ . '/partials/admin_topbar_actions.php'; ?>
                </div>
            </header>

        <div id="admin-container">
<?php endif; ?>



    <main class="main-content container-admin" style="padding-top: 15px; padding-bottom: 50px;">
        
        <!-- Header con Título y Acciones Funcionales -->
        <div class="admin-header" style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px;">
            <div class="admin-header-titles">
                <h1 style="font-size: 1.6rem; font-weight: 800; color: #0f172a; margin: 0;">Arqueo de Caja Chica</h1>
                <p style="margin: 4px 0 0; color: #64748b; font-size: 0.90rem;">Gestiona la apertura, movimientos diarios y cuadre de caja de los cajeros.</p>
            </div>
            <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                <?php if ($estaAbierta): ?>
                    <button type="button" id="btnHeaderRegistrarMov" class="btn-caja-header btn-caja-outline" title="Registrar ingreso o gasto extra">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        Movimiento (Ingreso / Gasto)
                    </button>
                    <button type="button" id="btnHeaderCerrarCaja" class="btn-caja-header btn-caja-red" title="Efectuar arqueo y cierre">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                        Cerrar Caja & Arqueo
                    </button>
                <?php else: ?>
                    <button type="button" id="btnHeaderAperturarCaja" class="btn-caja-header btn-caja-green" title="Aperturar nuevo turno">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 9.9-1"></path></svg>
                        Aperturar Turno de Caja
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Banner de Estado de Caja -->
        <?php if ($estaAbierta): ?>
            <div class="caja-status-banner-modern is-open">
                <div>
                    <span style="padding: 4px 12px; border-radius: 20px; font-size: 0.78rem; font-weight: 800; display: inline-flex; align-items: center; gap: 6px;">
                        <span style="width: 6px; height: 6px; border-radius: 50%; background: #16a34a;"></span>
                        Caja Abierta
                    </span>
                    <h2 style="margin: 8px 0 4px; font-size: 1.35rem; font-weight: 800; color: #0f172a;">
                        <?= htmlspecialchars($sesionActiva['caja_id']); ?> — Cajero: <?= htmlspecialchars($sesionActiva['cajero_nombre'] . ' ' . $sesionActiva['cajero_apellido']); ?>
                    </h2>
                    <p style="margin: 0; font-size: 0.88rem; color: #64748b;">
                        Aperturada el <?= date('d/m/Y h:i A', strtotime($sesionActiva['fecha_apertura'])); ?> con fondo inicial de <strong>S/ <?= number_format($sesionActiva['monto_apertura'], 2); ?></strong>
                    </p>
                </div>
                <div style="text-align: right;">
                    <span style="font-size: 0.74rem; font-weight: 800; color: #64748b; letter-spacing: 0.05em; text-transform: uppercase;">Efectivo Físico Esperado en Gaveta</span>
                    <div style="font-size: 2rem; font-weight: 900; color: #15803d; line-height: 1; margin-top: 4px;">
                        S/ <?= number_format($resumen['efectivo_esperado'] ?? 0, 2); ?>
                    </div>
                </div>
            </div>

            <!-- Métricas / KPIs en Tarjetas Pequeñas sin Icono (Estilo Imagen 2) -->
            <div class="stats-grid-square">
                <div class="stat-card-square">
                    <span class="stat-title">Monto Apertura</span>
                    <span class="stat-val">S/ <?= number_format($resumen['apertura'] ?? 0, 2); ?></span>
                </div>
                <div class="stat-card-square">
                    <span class="stat-title">Ventas Efectivo</span>
                    <span class="stat-val" style="color:#15803d;">+ S/ <?= number_format($resumen['ventas_efectivo'] ?? 0, 2); ?></span>
                </div>
                <div class="stat-card-square">
                    <span class="stat-title">Ingresos Extras</span>
                    <span class="stat-val" style="color:#0284c7;">+ S/ <?= number_format($resumen['ingresos_extra'] ?? 0, 2); ?></span>
                </div>
                <div class="stat-card-square">
                    <span class="stat-title">Egresos / Gastos</span>
                    <span class="stat-val" style="color:#dc2626;">- S/ <?= number_format($resumen['egresos'] ?? 0, 2); ?></span>
                </div>
                <div class="stat-card-square">
                    <span class="stat-title">Ventas Yape / Plin</span>
                    <span class="stat-val" style="color:#7c3aed; font-size:1.1rem;">S/ <?= number_format(($resumen['ventas_yape'] ?? 0) + ($resumen['ventas_plin'] ?? 0), 2); ?></span>
                </div>
                <div class="stat-card-square">
                    <span class="stat-title">Efectivo Esperado</span>
                    <span class="stat-val" style="color:#15803d; font-size:1.1rem;">S/ <?= number_format($resumen['efectivo_esperado'] ?? 0, 2); ?></span>
                </div>
            </div>

            <!-- Panel 1: Movimientos de Caja Chica (Esta Sesión) -->
            <div class="caja-panel-card">
                <div class="caja-panel-head">
                    <h3>Movimientos de Caja Chica (Esta Sesión)</h3>
                    <button type="button" id="btnTableRegistrarMov" class="btn-caja-header btn-caja-green" title="Registrar nuevo gasto o ingreso">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>Registrar Gasto / Ingreso
                    </button>
                </div>
                <div style="overflow-x: auto;">
                    <table class="caja-table-custom" id="tablaMovimientosCaja">
                        <thead>
                            <tr>
                                <th>Hora</th>
                                <th>Tipo</th>
                                <th>Categoría</th>
                                <th>Motivo</th>
                                <th>Comprobante</th>
                                <th style="text-align: right;">Monto</th>
                                <th>Registrado por</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($movimientos)): ?>
                                <?php foreach ($movimientos as $mv): 
                                    $esIngreso = ($mv['tipo'] === 'INGRESO');
                                ?>
                                    <tr class="row-movimiento-caja">
                                        <td style="color: #64748b; font-size: 0.85rem; font-weight: 600; white-space: nowrap;">
                                            <?= date('h:i A', strtotime($mv['fecha'])); ?>
                                        </td>
                                        <td>
                                            <span style="color: <?= $esIngreso ? '#15803d' : '#b91c1c'; ?>; padding: 3px 10px; border-radius: 20px; font-size: 0.78rem; font-weight: 700; display: inline-flex; align-items: center; gap: 4px;">
                                                <?= htmlspecialchars($mv['tipo']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span style="text-transform: capitalize; color: #475569; font-weight: 600; font-size: 0.85rem;">
                                                <?= htmlspecialchars($mv['categoria']); ?>
                                            </span>
                                        </td>
                                        <td style="color: #0f172a; font-weight: 600;">
                                            <?= htmlspecialchars($mv['motivo']); ?>
                                        </td>
                                        <td>
                                            <span style="background: #f1f5f9; color: #475569; padding: 3px 8px; border-radius: 6px; font-size: 0.78rem; font-family: monospace;">
                                                <?= htmlspecialchars($mv['comprobante_ref'] ?: '—'); ?>
                                            </span>
                                        </td>
                                        <td style="text-align: right; font-weight: 800; color: <?= $esIngreso ? '#15803d' : '#dc2626'; ?>; white-space: nowrap;">
                                            <?= $esIngreso ? '+' : '-'; ?> S/ <?= number_format($mv['monto'], 2); ?>
                                        </td>
                                        <td style="color: #64748b; font-size: 0.85rem; white-space: nowrap;">
                                            <?= htmlspecialchars($mv['usuario_nombre']); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; color: #94a3b8; padding: 36px 20px;">
                                        No hay movimientos de caja chica en este turno.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <!-- Paginación de Movimientos (Imagen 3) -->
                <div style="display: flex; align-items: center; justify-content: center; padding: 14px 20px; border-top: 1px solid #e2e8f0; background: #fff;" id="paginacionMovimientosControls">
                    <div style="display: flex; align-items: center; gap: 6px;" id="paginacionMovimientosList"></div>
                </div>
            </div>

        <?php else: ?>
            <!-- Banner Caja Cerrada -->
            <div class="caja-status-banner-modern is-closed">
                <div>
                    <span style="padding: 4px 12px; border-radius: 20px; font-size: 0.78rem; font-weight: 800; display: inline-flex; align-items: center; gap: 6px;">
                        <span style="width: 6px; height: 6px; border-radius: 50%; background: #ef4444;"></span>
                        Caja Cerrada
                    </span>
                    <h2 style="margin: 8px 0 4px; font-size: 1.35rem; font-weight: 800;">
                        No hay turno de caja abierto en este momento
                    </h2>
                    <p style="margin: 0; font-size: 0.88rem; color: #64748b;">
                        Para empezar a registrar ventas en POS y gastos menores, apertura el turno con el fondo inicial.
                    </p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Panel 2: Historial de Sesiones & Arqueos Anteriores (Estilo Imagen 2 y 3) -->
        <div class="caja-panel-card">
            <div class="caja-panel-head">
                <h3>Historial de Sesiones y Arqueos Anteriores</h3>
                <div style="font-size: 0.85rem; color: #64748b; font-weight: 600;">
                    Total sesiones registradas: <strong><?= count($historialSesiones); ?></strong>
                </div>
            </div>
            <div style="overflow-x: auto;">
                <table class="caja-table-custom" id="tablaHistorialCaja">
                    <thead>
                        <tr>
                            <th>Caja ID</th>
                            <th>Cajero</th>
                            <th>Apertura</th>
                            <th>Cierre</th>
                            <th style="text-align: right;">Fondo Apertura</th>
                            <th style="text-align: right;">Ventas Efectivo</th>
                            <th style="text-align: right;">Esperado</th>
                            <th style="text-align: right;">Real Contado</th>
                            <th style="text-align: center;">Diferencia</th>
                            <th style="text-align: center;">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($historialSesiones)): ?>
                            <?php foreach ($historialSesiones as $hs): 
                                $dif = (float)($hs['diferencia'] ?? 0);
                            ?>
                                <tr class="row-historial-caja">
                                    <td style="font-weight: 800; font-size: 0.78rem; color: #0f172a; white-space: nowrap;">
                                        <?= htmlspecialchars($hs['caja_id']); ?>
                                    </td>
                                    <td style="font-weight: 700; font-size: 0.78rem; color: #1e293b;">
                                        <?= htmlspecialchars($hs['cajero_nombre'] . ' ' . $hs['cajero_apellido']); ?>
                                    </td>
                                    <td style="white-space: nowrap;">
                                        <div style="font-weight: 700; font-size: 0.76rem; color: #1e293b; line-height: 1.2;"><?= date('d/m/y', strtotime($hs['fecha_apertura'])); ?></div>
                                        <div style="font-size: 0.70rem; color: #64748b; line-height: 1.2;"><?= date('H:i', strtotime($hs['fecha_apertura'])); ?></div>
                                    </td>
                                    <td style="white-space: nowrap;">
                                        <?php if ($hs['fecha_cierre']): ?>
                                            <div style="font-weight: 700; font-size: 0.76rem; color: #1e293b; line-height: 1.2;"><?= date('d/m/y', strtotime($hs['fecha_cierre'])); ?></div>
                                            <div style="font-size: 0.70rem; color: #64748b; line-height: 1.2;"><?= date('H:i', strtotime($hs['fecha_cierre'])); ?></div>
                                        <?php else: ?>
                                            <span style="color: #94a3b8; font-size: 0.75rem;">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: right; font-weight: 600; font-size: 0.78rem; white-space: nowrap;">
                                        S/ <?= number_format($hs['monto_apertura'], 2); ?>
                                    </td>
                                    <td style="text-align: right; font-weight: 700; color: #15803d; font-size: 0.78rem; white-space: nowrap;">
                                        +S/ <?= number_format($hs['total_ventas_efectivo'], 2); ?>
                                    </td>
                                    <td style="text-align: right; font-weight: 700; font-size: 0.78rem; white-space: nowrap;">
                                        S/ <?= number_format($hs['monto_cierre_esperado'] ?? 0, 2); ?>
                                    </td>
                                    <td style="text-align: right; font-weight: 800; font-size: 0.78rem; white-space: nowrap; color: #0f172a;">
                                        S/ <?= number_format($hs['monto_cierre_real'] ?? 0, 2); ?>
                                    </td>
                                    <td style="text-align: center; white-space: nowrap;">
                                        <?php if ($hs['estado'] === 'cerrada'): ?>
                                            <?php if ($dif == 0): ?>
                                                <span style="color: #15803d; padding: 2px 7px; border-radius: 12px; font-size: 0.72rem; font-weight: 700;">
                                                    Exacto (S/ 0.00)
                                                </span>
                                            <?php elseif ($dif > 0): ?>
                                                <span style="color: #0369a1; padding: 2px 7px; border-radius: 12px; font-size: 0.72rem; font-weight: 700;">
                                                    +S/ <?= number_format($dif, 2); ?>
                                                </span>
                                            <?php else: ?>
                                                <span style="color: #dc2626; padding: 2px 7px; border-radius: 12px; font-size: 0.72rem; font-weight: 700;">
                                                    -S/ <?= number_format(abs($dif), 2); ?>
                                                </span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span style="color: #94a3b8; font-size: 0.75rem;">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: center; white-space: nowrap;">
                                        <span style="color: <?= $hs['estado'] === 'abierta' ? '#15803d' : '#475569'; ?>; padding: 2px 8px; border-radius: 12px; font-size: 0.72rem; font-weight: 800; display: inline-flex; align-items: center; gap: 4px;">
                                            <?= strtoupper($hs['estado']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" style="text-align: center; color: #94a3b8; padding: 36px 20px;">
                                    No hay historial de sesiones todavía.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Paginación de Historial (Imagen 3) -->
            <div style="display: flex; align-items: center; justify-content: center; padding: 14px 20px; border-top: 1px solid #e2e8f0; background: #fff;" id="paginacionHistorialControls">
                <div style="display: flex; align-items: center; gap: 6px;" id="paginacionHistorialList"></div>
            </div>
        </div>

    </main>

    <!-- Modal 1: Apertura de Caja -->
    <div id="modalApertura" class="modal-overlay-caja">
        <div class="modal-card-caja">
            <button type="button" class="modal-close-caja" onclick="window.cerrarModalApertura()" aria-label="Cerrar modal">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
            <h3 style="margin: 0 0 16px; font-size: 1.25rem; font-weight: 800; color: #0f172a;">Aperturar Turno de Caja</h3>
            <form action="index.php?action=apertura_caja" method="POST">
                <div style="margin-bottom: 14px;">
                    <label style="display: block; font-size: 0.85rem; font-weight: 700; color: #334155; margin-bottom: 6px;">Identificador de Caja</label>
                    <input type="text" name="caja_id" value="CAJA-01" required style="width: 100%; padding: 10px 12px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 0.95rem; box-sizing: border-box;">
                </div>
                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 0.85rem; font-weight: 700; color: #334155; margin-bottom: 6px;">Fondo Inicial en Efectivo (S/)</label>
                    <input type="number" step="0.50" name="monto_apertura" min="0" required placeholder="Ej: 50.00" style="width: 100%; padding: 10px 12px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 1.1rem; font-weight: 700; box-sizing: border-box;">
                    <small style="color: #64748b; font-size: 0.80rem; margin-top: 4px; display: block;">Monto físico dejado en gaveta para dar cambio / vueltos.</small>
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" onclick="window.cerrarModalApertura()" style="padding: 10px 18px; background: #f1f5f9; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; color: #475569;">Cancelar</button>
                    <button type="submit" class="btn-caja-header btn-caja-green">Abrir Turno</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal 2: Registrar Movimiento de Caja Chica -->
    <?php if ($estaAbierta): ?>
        <div id="modalMovimiento" class="modal-overlay-caja">
            <div class="modal-card-caja" style="max-width: 540px;">
                <button type="button" class="modal-close-caja" onclick="window.cerrarModalMovimiento()" aria-label="Cerrar modal">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
                <h3 style="margin: 0 0 16px; font-size: 1.25rem; font-weight: 800; color: #0f172a;">Registrar Movimiento de Caja Chica</h3>
                <form action="index.php?action=movimiento_caja" method="POST">
                    <input type="hidden" name="id_sesion" value="<?= $sesionActiva['id_sesion']; ?>">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                        <div>
                            <label style="display: block; font-size: 0.85rem; font-weight: 700; color: #334155; margin-bottom: 6px;">Tipo de Movimiento</label>
                            <select name="tipo" required style="width: 100%; padding: 10px 12px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 0.90rem; background:#fff;">
                                <option value="EGRESO">EGRESO (Salida de dinero)</option>
                                <option value="INGRESO">INGRESO (Fondo extra)</option>
                            </select>
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.85rem; font-weight: 700; color: #334155; margin-bottom: 6px;">Categoría</label>
                            <select name="categoria" required style="width: 100%; padding: 10px 12px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 0.90rem; background:#fff;">
                                <option value="insumos">Insumos (Bolsas, rollos térmica)</option>
                                <option value="servicios">Servicios (Luz, agua, internet)</option>
                                <option value="proveedor">Pago a Proveedor</option>
                                <option value="adelanto">Adelanto de Personal</option>
                                <option value="fondo_extra">Inyección de Efectivo</option>
                                <option value="otro">Otro Gasto</option>
                            </select>
                        </div>
                    </div>
                    <div style="margin-bottom: 14px;">
                        <label style="display: block; font-size: 0.85rem; font-weight: 700; color: #334155; margin-bottom: 6px;">Monto (S/)</label>
                        <input type="number" step="0.10" name="monto" min="0.50" required placeholder="0.00" style="width: 100%; padding: 10px 12px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 1.1rem; font-weight: 700; box-sizing: border-box;">
                    </div>
                    <div style="margin-bottom: 14px;">
                        <label style="display: block; font-size: 0.85rem; font-weight: 700; color: #334155; margin-bottom: 6px;">Motivo / Descripción</label>
                        <input type="text" name="motivo" required placeholder="Ej: Compra de 5 rollos térmicos para ticketera" style="width: 100%; padding: 10px 12px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 0.92rem; box-sizing: border-box;">
                    </div>
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; font-size: 0.85rem; font-weight: 700; color: #334155; margin-bottom: 6px;">Nro. Comprobante / Recibo (Opcional)</label>
                        <input type="text" name="comprobante" placeholder="Ej: B001-1234 o Recibo N° 55" style="width: 100%; padding: 10px 12px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 0.92rem; box-sizing: border-box;">
                    </div>
                    <div style="display: flex; justify-content: flex-end; gap: 10px;">
                        <button type="button" onclick="window.cerrarModalMovimiento()" style="padding: 10px 18px; background: #f1f5f9; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; color: #475569;">Cancelar</button>
                        <button type="submit" class="btn-caja-header btn-caja-green">Guardar Movimiento</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal 3: Cierre & Arqueo de Caja -->
        <div id="modalCierre" class="modal-overlay-caja">
            <div class="modal-card-caja">
                <button type="button" class="modal-close-caja" onclick="window.cerrarModalCierre()" aria-label="Cerrar modal">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
                <h3 style="margin: 0 0 16px; font-size: 1.25rem; font-weight: 800; color: #dc2626;">Arqueo y Cierre de Caja</h3>
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px 16px; margin-bottom: 16px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                        <span style="color: #64748b; font-size: 0.88rem; font-weight: 600;">Monto Esperado en Sistema:</span>
                        <strong style="color: #15803d; font-size: 1.15rem; font-weight: 800;">S/ <?= number_format($resumen['efectivo_esperado'] ?? 0, 2); ?></strong>
                    </div>
                    <small style="font-size: 0.76rem; color: #94a3b8; display: block;">
                        (Apertura + Ventas Efectivo + Ingresos Extra - Gastos)
                    </small>
                </div>
                <form action="index.php?action=cierre_caja" method="POST">
                    <input type="hidden" name="id_sesion" value="<?= $sesionActiva['id_sesion']; ?>">
                    <div style="margin-bottom: 14px;">
                        <label style="display: block; font-size: 0.85rem; font-weight: 700; color: #334155; margin-bottom: 6px;">Efectivo Físico Contado en Gaveta (S/)</label>
                        <input type="number" step="0.10" id="inputRealContado" name="monto_cierre_real" required placeholder="0.00" oninput="window.calcularDiferenciaArqueo(<?= (float)($resumen['efectivo_esperado'] ?? 0); ?>)" style="width: 100%; padding: 10px 12px; border: 2px solid #cbd5e1; border-radius: 8px; font-size: 1.2rem; font-weight: 800; box-sizing: border-box;">
                    </div>
                    <div id="boxDiferenciaArqueo" style="display: none; padding: 12px; border-radius: 8px; margin-bottom: 18px; font-weight: 700; font-size: 0.92rem; text-align: center;">
                    </div>
                    <div style="display: flex; justify-content: flex-end; gap: 10px;">
                        <button type="button" onclick="window.cerrarModalCierre()" style="padding: 10px 18px; background: #f1f5f9; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; color: #475569;">Cancelar</button>
                        <button type="submit" class="btn-caja-header btn-caja-red">Confirmar Cierre de Turno</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- Notificación Toast Superior (Imagen 4) -->
    <div class="top-notification-toast" id="topNotificationToastCaja">
        <div class="toast-card-top">
            <div class="toast-icon-circle" id="toastIconCircleCaja">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
            </div>
            <div class="toast-content-top">
                <strong class="toast-title-top" id="toastTitleTopCaja">Éxito</strong>
                <p class="toast-msg-top" id="toastMsgTopCaja">Acción realizada correctamente.</p>
            </div>
            <button type="button" class="toast-close-top" id="toastCloseBtnCaja" aria-label="Cerrar notificación">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
            <div class="toast-progress-bar" id="toastProgressBarCaja"></div>
        </div>
    </div>

<script>
(function() {
    'use strict';

    // Funciones globales para control de modales
    window.abrirModalApertura = function() {
        var m = document.getElementById('modalApertura');
        if (m) m.style.display = 'flex';
    };
    window.cerrarModalApertura = function() {
        var m = document.getElementById('modalApertura');
        if (m) m.style.display = 'none';
    };
    window.abrirModalMovimiento = function() {
        var m = document.getElementById('modalMovimiento');
        if (m) m.style.display = 'flex';
    };
    window.cerrarModalMovimiento = function() {
        var m = document.getElementById('modalMovimiento');
        if (m) m.style.display = 'none';
    };
    window.abrirModalCierre = function() {
        var m = document.getElementById('modalCierre');
        if (m) m.style.display = 'flex';
    };
    window.cerrarModalCierre = function() {
        var m = document.getElementById('modalCierre');
        if (m) m.style.display = 'none';
    };

    // Vincular clics de botones explícitamente
    var btnHeadMov = document.getElementById('btnHeaderRegistrarMov');
    if (btnHeadMov) btnHeadMov.addEventListener('click', window.abrirModalMovimiento);

    var btnTblMov = document.getElementById('btnTableRegistrarMov');
    if (btnTblMov) btnTblMov.addEventListener('click', window.abrirModalMovimiento);

    var btnHeadCierre = document.getElementById('btnHeaderCerrarCaja');
    if (btnHeadCierre) btnHeadCierre.addEventListener('click', window.abrirModalCierre);

    var btnHeadAp = document.getElementById('btnHeaderAperturarCaja');
    if (btnHeadAp) btnHeadAp.addEventListener('click', window.abrirModalApertura);

    var btnBannerAp = document.getElementById('btnBannerAperturarCaja');
    if (btnBannerAp) btnBannerAp.addEventListener('click', window.abrirModalApertura);

    // Cerrar al hacer clic fuera
    ['modalApertura', 'modalMovimiento', 'modalCierre'].forEach(function(id) {
        var modal = document.getElementById(id);
        if (modal) {
            modal.addEventListener('click', function(e) {
                if (e.target === modal) modal.style.display = 'none';
            });
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            ['modalApertura', 'modalMovimiento', 'modalCierre'].forEach(function(id) {
                var modal = document.getElementById(id);
                if (modal) modal.style.display = 'none';
            });
        }
    });

    // Cálculo de diferencia de arqueo en vivo
    window.calcularDiferenciaArqueo = function(esperado) {
        var input = document.getElementById('inputRealContado');
        var box = document.getElementById('boxDiferenciaArqueo');
        if (!input || !box) return;

        var contado = parseFloat(input.value || 0);
        var dif = contado - esperado;
        box.style.display = 'block';

        if (Math.abs(dif) < 0.01) {
            box.style.background = '#dcfce7';
            box.style.color = '#15803d';
            box.style.border = '1px solid #bbf7d0';
            box.textContent = '✓ Cuadre Perfecto: Sin diferencia (S/ 0.00)';
        } else if (dif > 0) {
            box.style.background = '#e0f2fe';
            box.style.color = '#0369a1';
            box.style.border = '1px solid #bae6fd';
            box.textContent = 'ℹ️ Sobrante en caja: + S/ ' + dif.toFixed(2);
        } else {
            box.style.background = '#fee2e2';
            box.style.color = '#dc2626';
            box.style.border = '1px solid #fecaca';
            box.textContent = '⚠️ Faltante en caja: - S/ ' + Math.abs(dif).toFixed(2);
        }
    };

    // Notificación Toast Superior (Imagen 4)
    var toastTop   = document.getElementById('topNotificationToastCaja');
    var toastTitle = document.getElementById('toastTitleTopCaja');
    var toastMsg   = document.getElementById('toastMsgTopCaja');
    var toastIcon  = document.getElementById('toastIconCircleCaja');
    var toastProg  = document.getElementById('toastProgressBarCaja');
    var toastClose = document.getElementById('toastCloseBtnCaja');
    var toastTimer = null;

    window.mostrarTopToastCaja = function(titulo, msg, esExito) {
        if (!toastTop) return;
        clearTimeout(toastTimer);

        toastTitle.textContent = titulo || (esExito ? 'Éxito' : 'Atención');
        toastMsg.textContent   = msg || '';

        if (esExito) {
            toastIcon.className = 'toast-icon-circle';
            toastIcon.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>';
            toastProg.className = 'toast-progress-bar';
        } else {
            toastIcon.className = 'toast-icon-circle error';
            toastIcon.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>';
            toastProg.className = 'toast-progress-bar error';
        }

        toastTop.classList.add('show');
        toastTimer = setTimeout(function() {
            toastTop.classList.remove('show');
        }, 3600);
    };

    if (toastClose) {
        toastClose.addEventListener('click', function() {
            if (toastTop) toastTop.classList.remove('show');
        });
    }

    // Disparar flash messages del servidor si existen
    <?php if ($flash_success): ?>
        window.mostrarTopToastCaja('Éxito', <?= json_encode($flash_success); ?>, true);
    <?php endif; ?>
    <?php if ($flash_error): ?>
        window.mostrarTopToastCaja('Atención', <?= json_encode($flash_error); ?>, false);
    <?php endif; ?>

    // Paginación Reutilizable estilo Imagen 3
    function crearPaginacion(rowsSelector, listContainerId, controlsContainerId, pageSize) {
        var rows = Array.from(document.querySelectorAll(rowsSelector));
        var listContainer = document.getElementById(listContainerId);
        var controlsContainer = document.getElementById(controlsContainerId);
        if (!listContainer || !controlsContainer) return;

        var currentPage = 1;
        var total = rows.length;
        var totalPages = Math.ceil(total / pageSize) || 1;

        function render() {
            if (total === 0) {
                controlsContainer.style.display = 'none';
                return;
            }
            controlsContainer.style.display = 'flex';
            listContainer.innerHTML = '';

            var start = (currentPage - 1) * pageSize;
            var end = start + pageSize;

            rows.forEach(function(row, idx) {
                row.style.display = (idx >= start && idx < end) ? '' : 'none';
            });

            // Botón <
            var prevBtn = document.createElement('button');
            prevBtn.type = 'button';
            prevBtn.className = 'pagination-page-button';
            prevBtn.innerHTML = '‹';
            prevBtn.disabled = (currentPage === 1);
            prevBtn.onclick = function() {
                if (currentPage > 1) { currentPage--; render(); }
            };
            listContainer.appendChild(prevBtn);

            // Páginas numeradas
            for (var p = 1; p <= totalPages; p++) {
                (function(pageNum) {
                    var btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'pagination-page-button' + (pageNum === currentPage ? ' is-current' : '');
                    btn.textContent = pageNum;
                    btn.onclick = function() {
                        currentPage = pageNum;
                        render();
                    };
                    listContainer.appendChild(btn);
                })(p);
            }

            // Botón >
            var nextBtn = document.createElement('button');
            nextBtn.type = 'button';
            nextBtn.className = 'pagination-page-button';
            nextBtn.innerHTML = '›';
            nextBtn.disabled = (currentPage === totalPages);
            nextBtn.onclick = function() {
                if (currentPage < totalPages) { currentPage++; render(); }
            };
            listContainer.appendChild(nextBtn);
        }

        render();
    }

    // Inicializar paginación de Historial y Movimientos
    crearPaginacion('.row-historial-caja', 'paginacionHistorialList', 'paginacionHistorialControls', 5);
    crearPaginacion('.row-movimiento-caja', 'paginacionMovimientosList', 'paginacionMovimientosControls', 5);

})();
</script>

<?php if (!$isAjax): ?>
        </div>
    </div>
</div>
</body>
</html>
<?php endif; ?>
