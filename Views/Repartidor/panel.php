<?php
$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

$enviosAsignados = array_values(array_filter($envios, function ($envio) {
    $est = strtolower(trim($envio['estado_delivery'] ?? ''));
    return !in_array($est, ['entregado', 'fallido', 'cancelado'], true);
}));

$historialEnvios = array_values(array_filter($envios, function ($envio) {
    $est = strtolower(trim($envio['estado_delivery'] ?? ''));
    return in_array($est, ['entregado', 'fallido', 'cancelado'], true);
}));

$entregasCompletadas = count(array_filter($historialEnvios, function ($envio) {
    $est = strtolower(trim($envio['estado_delivery'] ?? ''));
    return $est === 'entregado';
}));

$efectivoPorCobrar = 0;
foreach ($enviosAsignados as $env) {
    $mp = mb_strtolower(trim($env['metodo_pago'] ?? ''));
    if (strpos($mp, 'efectivo') !== false || strpos($mp, 'contraentrega') !== false || strpos($mp, 'contra entrega') !== false) {
        $efectivoPorCobrar += (float)($env['pedido_total'] ?? 0);
    }
}

$activeTab = $_GET['tab'] ?? 'dashboard';
if (!in_array($activeTab, ['dashboard', 'assigned', 'available', 'history'], true)) {
    $activeTab = 'dashboard';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reparto - La Caserita</title>
    <link rel="stylesheet" href="Styles/global.css">
    <link rel="stylesheet" href="Styles/staff-panel.css?v=<?= time() ?>">
    <link rel="stylesheet" href="Styles/Vistas/Repartidor/panel.css?v=<?= time() ?>">
    <script src="Scripts/Vistas/Repartidor/filtros_historial.js?v=<?= time() ?>" defer></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="staff-app repartidor-panel">
    <?php 
    $paginaPanelStaff = 'entregas'; 
    $cantAsignados = count($enviosAsignados);
    $cantDisponibles = count($enviosDisponibles);
    $cantHistorial = count($historialEnvios);
    include __DIR__ . '/../Staff/partials/staff_sidebar.php'; 
    ?>
    <div class="staff-main">
        <header class="staff-topbar">
            <div class="staff-topbar-left">
                <button type="button" class="staff-menu-toggle hide-on-desktop" id="staffMenuToggle" aria-label="Abrir menú">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
                </button>
                <span class="staff-topbar-title">Panel de reparto</span>
            </div>
            <div class="staff-topbar-actions">
                <a href="index.php?action=home_usuario" class="topbar-store-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                    Volver a Tienda
                </a>
            </div>
        </header>

        <main class="staff-content">
            <?php if ($flashSuccess): ?><div class="delivery-flash success"><?= htmlspecialchars($flashSuccess) ?></div><?php endif; ?>
            <?php if ($flashError): ?><div class="delivery-flash error"><?= htmlspecialchars($flashError) ?></div><?php endif; ?>

            <?php if ($activeTab === 'dashboard'): ?>
                <!-- ================= DASHBOARD DEL REPARTIDOR ================= -->
                <section class="delivery-heading">
                    <div>
                        <span class="delivery-eyebrow">PANEL DE CONTROL</span>
                        <h1>Dashboard de reparto</h1>
                        <p><?= htmlspecialchars($nombre) ?> · Repartidor</p>
                    </div>
                </section>

                <section class="repartidor-metrics-grid">
                    <article class="metric-card">
                        <span class="metric-title">ENTREGAS ACTIVAS</span>
                        <strong class="metric-value"><?= count($enviosAsignados) ?></strong>
                        <span class="metric-sub">En camino o pendientes</span>
                    </article>
                    <article class="metric-card">
                        <span class="metric-title">DISPONIBLES EN TIENDA</span>
                        <strong class="metric-value" style="color:#0284c7;"><?= count($enviosDisponibles) ?></strong>
                        <span class="metric-sub">Listos para ser tomados</span>
                    </article>
                    <article class="metric-card">
                        <span class="metric-title">ENTREGAS COMPLETADAS</span>
                        <strong class="metric-value" style="color:#15803d;"><?= $entregasCompletadas ?></strong>
                        <span class="metric-sub">Historial de éxito</span>
                    </article>
                    <article class="metric-card">
                        <span class="metric-title">EFECTIVO POR RENDIR</span>
                        <strong class="metric-value" style="color:#b45309;">S/ <?= number_format($efectivoPorCobrar, 2) ?></strong>
                        <span class="metric-sub">Cobros en efectivo / contraentrega</span>
                    </article>
                </section>

                <section class="delivery-quick-actions">
                    <a href="index.php?action=panel_repartidor&tab=assigned" class="quick-action-btn">
                        <div class="quick-action-icon"><i class="fa-solid fa-truck"></i></div>
                        <div>
                            <strong>Ver Mis Pedidos Asignados</strong>
                            <p><?= count($enviosAsignados) ?> pedidos en tu lista de entrega</p>
                        </div>
                    </a>
                    <a href="index.php?action=panel_repartidor&tab=available" class="quick-action-btn">
                        <div class="quick-action-icon" style="background:#e0f2fe; color:#0284c7;"><i class="fa-solid fa-hand-holding-hand"></i></div>
                        <div>
                            <strong>Tomar Pedidos Disponibles</strong>
                            <p><?= count($enviosDisponibles) ?> pedidos listos en almacén/tienda</p>
                        </div>
                    </a>
                    <a href="index.php?action=panel_repartidor&tab=history" class="quick-action-btn">
                        <div class="quick-action-icon" style="background:#f1f5f9; color:#475569;"><i class="fa-solid fa-clock-rotate-left"></i></div>
                        <div>
                            <strong>Historial de Entregas</strong>
                            <p><?= count($historialEnvios) ?> entregas registradas en total</p>
                        </div>
                    </a>
                </section>

                <?php if ($enviosAsignados): ?>
                    <section class="delivery-section-block">
                        <div class="delivery-section-header">
                            <h2>Próximas entregas a realizar</h2>
                            <a href="index.php?action=panel_repartidor&tab=assigned">Ver todas (<?= count($enviosAsignados) ?>) &rarr;</a>
                        </div>
                        <div class="delivery-list">
                            <?php foreach (array_slice($enviosAsignados, 0, 2) as $envio): 
                                $estado = $envio['estado_delivery'] ?: 'Pendiente';
                                $metodoPago = mb_strtolower(trim($envio['metodo_pago'] ?? ''));
                                $cobroEfectivo = strpos($metodoPago, 'efectivo') !== false || strpos($metodoPago, 'contraentrega') !== false || strpos($metodoPago, 'contra entrega') !== false;
                            ?>
                                <article class="delivery-item">
                                    <header class="delivery-item-header">
                                        <div>
                                            <span class="delivery-order-code"><?= htmlspecialchars($envio['codigo_pedido'] ?: ('PED-' . $envio['id_pedido'])) ?></span>
                                            <span class="delivery-status route"><?= htmlspecialchars($estado) ?></span>
                                        </div>
                                        <strong>S/ <?= number_format((float)$envio['pedido_total'], 2) ?></strong>
                                    </header>
                                    <?php if ($cobroEfectivo): ?>
                                        <div class="delivery-cash-due">
                                            <i class="fa-solid fa-money-bill-wave"></i>
                                            <span>EFECTIVO POR COBRAR</span>
                                            <strong>S/ <?= number_format((float)$envio['pedido_total'], 2) ?></strong>
                                        </div>
                                    <?php endif; ?>
                                    <div class="delivery-details">
                                        <div><span>CLIENTE</span><strong><?= htmlspecialchars($envio['nombre_destinatario'] ?: 'Cliente') ?></strong></div>
                                        <div><span>TELÉFONO</span><a href="tel:<?= htmlspecialchars(preg_replace('/[^0-9+]/', '', $envio['celular_contacto'] ?? '')) ?>"><?= htmlspecialchars($envio['celular_contacto'] ?: 'Sin teléfono') ?></a></div>
                                        <div class="delivery-address">
                                            <span>DIRECCIÓN</span>
                                            <strong><?= htmlspecialchars($envio['direccion_destino'] ?: 'Sin dirección') ?></strong>
                                            <?php if (!empty($envio['referencia'])): ?><small><?= htmlspecialchars($envio['referencia']) ?></small><?php endif; ?>
                                        </div>
                                    </div>
                                    <form class="delivery-update-form" method="post" action="index.php?action=actualizar_entrega_repartidor">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                        <input type="hidden" name="id_envio" value="<?= (int)$envio['id_envio'] ?>">
                                        <input id="delivery-notes-<?= (int)$envio['id_envio'] ?>" type="text" name="notas" maxlength="240" placeholder="Referencia o incidencia">
                                        <div class="delivery-actions">
                                            <?php if ($estado !== 'En Ruta'): ?><button class="delivery-action secondary" name="nuevo_estado" value="En Ruta" type="submit"><i class="fa-solid fa-truck-fast"></i> Iniciar ruta</button><?php endif; ?>
                                            <button class="delivery-action primary" name="nuevo_estado" value="Entregado" type="submit"><i class="fa-solid fa-circle-check"></i> Marcar entregado</button>
                                            <button class="delivery-action failed-action" name="nuevo_estado" value="Fallido" type="submit"><i class="fa-solid fa-triangle-exclamation"></i> No entregado</button>
                                        </div>
                                    </form>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>

            <?php elseif ($activeTab === 'assigned'): ?>
                <!-- ================= PEDIDOS ASIGNADOS ================= -->
                <section class="delivery-heading">
                    <div>
                        <span class="delivery-eyebrow">OPERACIONES</span>
                        <h1>Pedidos Asignados</h1>
                        <p><?= htmlspecialchars($nombre) ?> · En curso</p>
                    </div>
                    <div class="delivery-count"><i class="fa-solid fa-box"></i><strong><?= count($enviosAsignados) ?></strong><span>asignadas</span></div>
                </section>

                <?php if (!$enviosAsignados): ?>
                    <section class="delivery-empty">
                        <i class="fa-solid fa-route"></i>
                        <h2>No tienes entregas asignadas actualmente</h2>
                        <p>Revisa los pedidos disponibles para tomar nuevos despachos.</p>
                        <a href="index.php?action=panel_repartidor&tab=available" class="delivery-action primary" style="display:inline-flex; margin-top:14px; text-decoration:none;"><i class="fa-solid fa-hand"></i> Tomar pedidos disponibles</a>
                    </section>
                <?php else: ?>
                    <section class="delivery-list" aria-label="Entregas asignadas">
                        <?php foreach ($enviosAsignados as $envio):
                            $estado = $envio['estado_delivery'] ?: 'Pendiente';
                            $metodoPago = mb_strtolower(trim($envio['metodo_pago'] ?? ''));
                            $cobroEfectivo = strpos($metodoPago, 'efectivo') !== false || strpos($metodoPago, 'contraentrega') !== false || strpos($metodoPago, 'contra entrega') !== false;
                        ?>
                            <article class="delivery-item">
                                <header class="delivery-item-header">
                                    <div><span class="delivery-order-code"><?= htmlspecialchars($envio['codigo_pedido'] ?: ('PED-' . $envio['id_pedido'])) ?></span><span class="delivery-status route"><?= htmlspecialchars($estado) ?></span></div>
                                    <strong>S/ <?= number_format((float)$envio['pedido_total'], 2) ?></strong>
                                </header>
                                <?php if ($cobroEfectivo): ?><div class="delivery-cash-due"><i class="fa-solid fa-money-bill-wave"></i><span>EFECTIVO POR COBRAR</span><strong>S/ <?= number_format((float)$envio['pedido_total'], 2) ?></strong></div><?php endif; ?>
                                <div class="delivery-details">
                                    <div><span>CLIENTE</span><strong><?= htmlspecialchars($envio['nombre_destinatario'] ?: 'Cliente') ?></strong></div>
                                    <div><span>TELÉFONO</span><a href="tel:<?= htmlspecialchars(preg_replace('/[^0-9+]/', '', $envio['celular_contacto'] ?? '')) ?>"><?= htmlspecialchars($envio['celular_contacto'] ?: 'Sin teléfono') ?></a></div>
                                    <div class="delivery-address"><span>DIRECCIÓN</span><strong><?= htmlspecialchars($envio['direccion_destino'] ?: 'Sin dirección registrada') ?></strong><?php if (!empty($envio['referencia'])): ?><small><?= htmlspecialchars($envio['referencia']) ?></small><?php endif; ?></div>
                                </div>
                                <form class="delivery-update-form" method="post" action="index.php?action=actualizar_entrega_repartidor">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                    <input type="hidden" name="id_envio" value="<?= (int)$envio['id_envio'] ?>">
                                    <label for="delivery-notes-<?= (int)$envio['id_envio'] ?>">Nota de entrega</label>
                                    <input id="delivery-notes-<?= (int)$envio['id_envio'] ?>" type="text" name="notas" maxlength="240" placeholder="Referencia o incidencia">
                                    <div class="delivery-actions">
                                        <?php if ($estado !== 'En Ruta'): ?><button class="delivery-action secondary" name="nuevo_estado" value="En Ruta" type="submit"><i class="fa-solid fa-truck-fast"></i> Iniciar ruta</button><?php endif; ?>
                                        <button class="delivery-action primary" name="nuevo_estado" value="Entregado" type="submit"><i class="fa-solid fa-circle-check"></i> Marcar entregado</button>
                                        <button class="delivery-action failed-action" name="nuevo_estado" value="Fallido" type="submit"><i class="fa-solid fa-triangle-exclamation"></i> No entregado</button>
                                    </div>
                                </form>
                            </article>
                        <?php endforeach; ?>
                    </section>
                <?php endif; ?>

            <?php elseif ($activeTab === 'available'): ?>
                <!-- ================= TOMAR PEDIDOS DISPONIBLES ================= -->
                <section class="delivery-heading">
                    <div>
                        <span class="delivery-eyebrow">OPERACIONES</span>
                        <h1>Pedidos Disponibles para Tomar</h1>
                        <p><?= htmlspecialchars($nombre) ?> · Listos en tienda</p>
                    </div>
                    <div class="delivery-count"><i class="fa-solid fa-inbox"></i><strong><?= count($enviosDisponibles) ?></strong><span>disponibles</span></div>
                </section>

                <?php if (!$enviosDisponibles): ?>
                    <section class="delivery-empty"><i class="fa-solid fa-inbox"></i><h2>No hay pedidos pendientes en tienda para tomar en este momento.</h2></section>
                <?php else: ?>
                    <section class="delivery-list" aria-label="Pedidos disponibles">
                        <?php foreach ($enviosDisponibles as $envio):
                            $metodoDisponible = mb_strtolower(trim($envio['metodo_pago'] ?? ''));
                            $esEfectivoDisponible = strpos($metodoDisponible, 'efectivo') !== false || strpos($metodoDisponible, 'contraentrega') !== false || strpos($metodoDisponible, 'contra entrega') !== false;
                        ?>
                            <article class="delivery-item">
                                <header class="delivery-item-header"><div><span class="delivery-order-code"><?= htmlspecialchars($envio['codigo_pedido'] ?: ('PED-' . $envio['id_pedido'])) ?></span><span class="delivery-status route">Listo en tienda</span></div><strong>S/ <?= number_format((float)$envio['pedido_total'], 2) ?></strong></header>
                                <?php if ($esEfectivoDisponible): ?><div class="delivery-cash-due"><i class="fa-solid fa-money-bill-wave"></i><span>EFECTIVO POR COBRAR</span><strong>S/ <?= number_format((float)$envio['pedido_total'], 2) ?></strong></div><?php endif; ?>
                                <div class="delivery-details"><div><span>CLIENTE</span><strong><?= htmlspecialchars($envio['nombre_destinatario'] ?: 'Cliente') ?></strong></div><div><span>HORARIO</span><strong><?= htmlspecialchars($envio['horario_entrega'] ?: 'Lo antes posible') ?></strong></div><div class="delivery-address"><span>DIRECCIÓN</span><strong><?= htmlspecialchars($envio['direccion_destino'] ?: 'Sin dirección registrada') ?></strong><?php if (!empty($envio['referencia'])): ?><small><?= htmlspecialchars($envio['referencia']) ?></small><?php endif; ?></div></div>
                                <form method="post" action="index.php?action=tomar_entrega_repartidor"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>"><input type="hidden" name="id_envio" value="<?= (int)$envio['id_envio'] ?>"><button class="delivery-action primary" type="submit"><i class="fa-solid fa-hand"></i> Tomar este pedido</button></form>
                            </article>
                        <?php endforeach; ?>
                    </section>
                <?php endif; ?>

            <?php elseif ($activeTab === 'history'): ?>
                <!-- ================= HISTORIAL DE ENTREGAS ================= -->
                <section class="delivery-heading">
                    <div>
                        <span class="delivery-eyebrow">HISTORIAL</span>
                        <h1>Historial de Entregas</h1>
                        <p><?= htmlspecialchars($nombre) ?> · Registros concluidos</p>
                    </div>
                    <div class="delivery-count"><i class="fa-solid fa-clock-rotate-left"></i><strong><?= count($historialEnvios) ?></strong><span>totales</span></div>
                </section>

                <?php if (!$historialEnvios): ?>
                    <section class="delivery-empty"><i class="fa-solid fa-clock-rotate-left"></i><h2>Sin entregas en el historial.</h2><p>Cuando finalices o concluyas un despacho aparecerá aquí registrado.</p></section>
                <?php else: ?>
                    <div class="delivery-history-filters" role="search" aria-label="Filtrar historial de entregas">
                        <label class="delivery-history-search">
                            <span class="visually-hidden">Buscar en el historial</span>
                            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                            <input type="search" id="deliveryHistorySearch" placeholder="Buscar pedido, cliente o dirección">
                        </label>
                        <label>
                            <span class="visually-hidden">Filtrar por resultado</span>
                            <select id="deliveryHistoryStatus">
                                <option value="">Todos los resultados</option>
                                <option value="entregado">Entregados</option>
                                <option value="incidencia">Con incidencia</option>
                            </select>
                        </label>
                        <label>
                            <span class="visually-hidden">Desde fecha</span>
                            <input type="date" id="deliveryHistoryFrom" aria-label="Desde fecha">
                        </label>
                        <label>
                            <span class="visually-hidden">Hasta fecha</span>
                            <input type="date" id="deliveryHistoryTo" aria-label="Hasta fecha">
                        </label>
                        <button type="button" id="deliveryHistoryClear" class="delivery-history-clear">Limpiar</button>
                        <span id="deliveryHistoryCount" class="delivery-history-filter-count" aria-live="polite"></span>
                    </div>
                    <section class="delivery-list" id="deliveryHistoryList" aria-label="Historial de entregas">
                        <?php foreach ($historialEnvios as $envio): 
                            $estado = $envio['estado_delivery'] ?: ($envio['pedido_estado'] ?? 'Entregado');
                            $esExito = strtolower($estado) === 'entregado';
                            $textoBusqueda = strtolower(trim(($envio['codigo_pedido'] ?? '') . ' ' . ($envio['nombre_destinatario'] ?? '') . ' ' . ($envio['celular_contacto'] ?? '') . ' ' . ($envio['direccion_destino'] ?? '') . ' ' . ($envio['referencia'] ?? '') . ' ' . ($envio['notas_entrega'] ?? '') . ' ' . $estado));
                            $fechaFiltro = !empty($envio['pedido_fecha']) ? date('Y-m-d', strtotime($envio['pedido_fecha'])) : '';
                        ?>
                            <article class="delivery-item history-item" data-search="<?= htmlspecialchars($textoBusqueda, ENT_QUOTES, 'UTF-8') ?>" data-status="<?= $esExito ? 'entregado' : 'incidencia' ?>" data-date="<?= htmlspecialchars($fechaFiltro, ENT_QUOTES, 'UTF-8') ?>">
                                <header class="delivery-item-header">
                                    <div>
                                        <span class="delivery-order-code"><?= htmlspecialchars($envio['codigo_pedido'] ?: ('PED-' . $envio['id_pedido'])) ?></span>
                                        <span class="delivery-status <?= $esExito ? 'done' : 'failed' ?>"><?= htmlspecialchars($estado) ?></span>
                                    </div>
                                    <strong>S/ <?= number_format((float)$envio['pedido_total'], 2) ?></strong>
                                </header>
                                <div class="delivery-details">
                                    <div><span>CLIENTE</span><strong><?= htmlspecialchars($envio['nombre_destinatario'] ?: 'Cliente') ?></strong></div>
                                    <div><span>TELÉFONO</span><a href="tel:<?= htmlspecialchars(preg_replace('/[^0-9+]/', '', $envio['celular_contacto'] ?? '')) ?>"><?= htmlspecialchars($envio['celular_contacto'] ?: 'Sin teléfono') ?></a></div>
                                    <div class="delivery-address"><span>DIRECCIÓN</span><strong><?= htmlspecialchars($envio['direccion_destino'] ?: 'Sin dirección registrada') ?></strong><?php if (!empty($envio['referencia'])): ?><small><?= htmlspecialchars($envio['referencia']) ?></small><?php endif; ?></div>
                                    <?php if (!empty($envio['pedido_fecha'])): ?>
                                        <div><span>FECHA REGISTRO</span><strong style="font-size:0.82rem; color:#64748b;"><?= date('d/m/Y H:i', strtotime($envio['pedido_fecha'])) ?></strong></div>
                                    <?php endif; ?>
                                    <div><span>PAGO</span><strong><?= htmlspecialchars($envio['metodo_pago'] ?: 'No especificado') ?></strong></div>
                                    <?php if (!empty($envio['notas_entrega'])): ?>
                                        <div style="grid-column:1/-1; background:#f8fafc; padding:8px 12px; border-radius:6px; border-left:3px solid <?= $esExito ? '#16a34a' : '#dc2626' ?>;">
                                            <span style="font-size:0.68rem; font-weight:800; color:#64748b;">NOTAS / INCIDENCIAS</span>
                                            <p style="margin:2px 0 0; font-size:0.82rem; color:#334155; white-space:pre-line;"><?= htmlspecialchars(trim($envio['notas_entrega'])) ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </section>
                    <p id="deliveryHistoryNoResults" class="delivery-history-no-results" hidden>No hay entregas que coincidan con estos filtros.</p>
                <?php endif; ?>
            <?php endif; ?>
        </main>
    </div>

    <script src="Scripts/app.js"></script>
</body>
</html>
