<?php
require_once 'Core/ajaxHelper.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$nombre = htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Administrador');
$flash_success = $_SESSION['flash_success'] ?? null;
$flash_error = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

if (!isset($metricas) || !is_array($metricas)) {
    $metricas = [
        'total_ventas' => 0.0,
        'total_pedidos' => 0,
        'ventas_pos' => 0.0,
        'ventas_web' => 0.0,
        'ventas_hoy' => 0.0,
        'cogs' => 0.0,
        'total_egresos' => 0.0,
        'ganancia_neta' => 0.0,
        'margen_neto' => 0.0,
        'ventas_cajeros' => [],
        'metodos_pago' => [],
        'top_productos' => []
    ];
}
?>
<?php if (!AjaxHelper::isAjax()): ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Financiero & Ganancias — ERP La Caserita</title>
    <link rel="icon" type="image/png" href="Images/logo.png">
    <link rel="stylesheet" href="Styles/global.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="Styles/styles-home.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="Styles/styles-users-admin.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="Styles/admin-spa.css?v=<?= time(); ?>">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="Scripts/admin-spa.js?v=<?= time(); ?>" defer></script>
<link rel="stylesheet" href="Styles/Vistas/Admin/dashboard_ganancias.css?v=<?= filemtime(__DIR__ . '/../../Styles/Vistas/Admin/dashboard_ganancias.css'); ?>">
</head>
<body>
    <div class="admin-layout">
        <?php include __DIR__ . '/partials/admin_sidebar.php'; ?>

        <div class="admin-main">
            <!-- Topbar -->
            <header class="admin-topbar">
                <div class="admin-topbar-left">
                    <button type="button" class="admin-hamburger-btn hide-on-desktop" id="openAdminSidebar" aria-label="Abrir Menú">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
                    </button>
                    <div class="admin-topbar-brand">
                        <span class="brand-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
                        </span>
                        <span class="brand-title">Dashboard Financiero</span>
                    </div>
                </div>
                <div class="admin-topbar-actions">
                    <?php include __DIR__ . '/partials/admin_topbar_actions.php'; ?>
                </div>
            </header>

            <div id="admin-container">
<?php endif; ?>



<main class="fin-dashboard-wrapper">
    <div class="fin-header-row">
        <div class="fin-header-titles">
            <h1>Dashboard Financiero</h1>
            <p>Métricas consolidadas de ventas, costos de inventario (COGS), gastos operativos y margen real neto.</p>
        </div>
        <div>
            <span class="fin-badge-pill">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                <span>Actualizado: <?= date('d/m/Y H:i'); ?></span>
            </span>
        </div>
    </div>

    <?php if ($flash_success): ?>
        <div style="background:#dcfce7; color:#15803d; padding:14px 18px; border-radius:12px; margin-bottom:24px; font-weight:700; border:1px solid #bbf7d0;">
            ✓ <?= htmlspecialchars($flash_success); ?>
        </div>
    <?php endif; ?>

    <!-- 1. Grilla Principal de KPIs Financieros -->
    <div class="fin-kpi-grid">
        <!-- 1. Ventas Totales Brutas -->
        <div class="fin-kpi-card">
            <div class="fin-kpi-header">
                <span class="fin-kpi-title">Ventas Totales Brutas</span>
            </div>
            <div class="fin-kpi-val">S/ <?= number_format($metricas['total_ventas'], 2); ?></div>
            <div class="fin-kpi-footer">
                <span><?= $metricas['total_pedidos']; ?> pedidos concluidos</span>
                <strong>Hoy: S/ <?= number_format($metricas['ventas_hoy'], 2); ?></strong>
            </div>
        </div>

        <!-- 2. Costo Mercadería (COGS) -->
        <div class="fin-kpi-card">
            <div class="fin-kpi-header">
                <span class="fin-kpi-title">Costo Mercadería</span>
            </div>
            <div class="fin-kpi-val">S/ <?= number_format($metricas['cogs'], 2); ?></div>
            <div class="fin-kpi-footer">
                <span>Costo de adquisición mercadería vendida</span>
            </div>
        </div>

        <!-- 3. Gastos Caja Chica (Egresos) -->
        <div class="fin-kpi-card">
            <div class="fin-kpi-header">
                <span class="fin-kpi-title">Gastos Caja Chica</span>
            </div>
            <div class="fin-kpi-val">S/ <?= number_format($metricas['total_egresos'], 2); ?></div>
            <div class="fin-kpi-footer">
                <span>Insumos, servicios y egresos registrados</span>
            </div>
        </div>

        <!-- 4. Ganancia Neta Real -->
        <div class="fin-kpi-card">
            <div class="fin-kpi-header">
                <span class="fin-kpi-title">Ganancia Neta Real</span>
            </div>
            <div class="fin-kpi-val">
                S/ <?= number_format($metricas['ganancia_neta'], 2); ?>
            </div>
            <div class="fin-kpi-footer">
                <span style="font-weight:700; color:#059669;">Margen Neto: <?= $metricas['margen_neto']; ?>%</span>
            </div>
        </div>
    </div>

    <!-- 2. Grilla Secundaria: Desglose por Empleado & Métodos / Canales -->
    <div class="fin-content-grid">
        <!-- Izquierda: Ventas por Empleado -->
        <div class="fin-panel-card">
            <h3 class="fin-panel-title">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="color:#15803D;"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                <span>Ventas por Empleado / Cajero</span>
            </h3>
            <div style="overflow-x:auto;">
                <table class="fin-table">
                    <thead>
                        <tr>
                            <th>Empleado</th>
                            <th>Rol</th>
                            <th style="text-align:center;">Tickets</th>
                            <th style="text-align:right;">Total Vendido</th>
                            <th style="text-align:right;">Participación</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($metricas['ventas_cajeros'])): ?>
                            <?php foreach ($metricas['ventas_cajeros'] as $caj): 
                                $porc = $metricas['total_ventas'] > 0 ? round(($caj['total_vendido'] / $metricas['total_ventas']) * 100, 1) : 0;
                            ?>
                                <tr>
                                    <td>
                                        <div style="display:flex; align-items:center; gap:10px;">
                                            <div style="width:34px; height:34px; border-radius:50%; background:#e0f2fe; color:#0369a1; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:0.85rem;">
                                                <?= strtoupper(substr($caj['empleado_nombre'], 0, 1)); ?>
                                            </div>
                                            <strong><?= htmlspecialchars($caj['empleado_nombre']); ?></strong>
                                        </div>
                                    </td>
                                    <td>
                                        <span style="background:#f1f5f9; color:#475569; padding:3px 8px; border-radius:8px; font-size:0.75rem; font-weight:700; text-transform:uppercase;">
                                            <?= htmlspecialchars($caj['rol']); ?>
                                        </span>
                                    </td>
                                    <td style="text-align:center; font-weight:700;"><?= $caj['tickets_atendidos']; ?></td>
                                    <td style="text-align:right; font-weight:800; color:#0f172a;">S/ <?= number_format($caj['total_vendido'], 2); ?></td>
                                    <td style="text-align:right; min-width:110px;">
                                        <div style="font-weight:700; color:#15803D;"><?= $porc; ?>%</div>
                                        <div class="fin-progress-bar">
                                            <div class="fin-progress-fill" style="width:<?= $porc; ?>%; background:#15803D;"></div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" style="text-align:center; color:#94a3b8; padding:30px;">No hay ventas registradas aún</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Derecha: Métodos de Pago & Canales -->
        <div class="fin-panel-card">
            <h3 class="fin-panel-title">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="color:#15803D;"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
                <span>Ventas por Método de Pago</span>
            </h3>

            <div style="display:flex; flex-direction:column; gap:12px;">
                <?php if (!empty($metricas['metodos_pago'])): ?>
                    <?php foreach ($metricas['metodos_pago'] as $mp): 
                        $pMet = $metricas['total_ventas'] > 0 ? round(($mp['total_recaudado'] / $metricas['total_ventas']) * 100, 1) : 0;
                        $colorMet = '#15803D';
                        $bgMet = '#e6f4f6';
                        if (stripos($mp['metodo'], 'yape') !== false) { $colorMet = '#7c3aed'; $bgMet = '#f3e8ff'; }
                        elseif (stripos($mp['metodo'], 'plin') !== false) { $colorMet = '#0284c7'; $bgMet = '#e0f2fe'; }
                        elseif (stripos($mp['metodo'], 'efectivo') !== false) { $colorMet = '#16a34a'; $bgMet = '#dcfce7'; }
                        elseif (stripos($mp['metodo'], 'tarjeta') !== false) { $colorMet = '#ea580c'; $bgMet = '#ffedd5'; }
                    ?>
                        <div style="background:#ffffff; border:1px solid #e2e8f0; padding:14px; border-radius:12px; box-shadow:0 1px 3px rgba(0,0,0,0.02);">
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                                <span style="font-weight:800; font-size:0.92rem; color:<?= $colorMet; ?>; display:flex; align-items:center; gap:6px;">
                                    <span style="width:8px; height:8px; border-radius:50%; background:<?= $colorMet; ?>;"></span>
                                    <?= htmlspecialchars($mp['metodo']); ?>
                                </span>
                                <span style="font-weight:800; font-size:1.05rem; color:#0f172a;">S/ <?= number_format($mp['total_recaudado'], 2); ?></span>
                            </div>
                            <div style="display:flex; justify-content:space-between; font-size:0.8rem; color:#64748b; margin-bottom:4px;">
                                <span><?= $mp['cantidad_transacciones']; ?> operaciones</span>
                                <strong><?= $pMet; ?>%</strong>
                            </div>
                            <div class="fin-progress-bar">
                                <div class="fin-progress-fill" style="width:<?= $pMet; ?>%; background:<?= $colorMet; ?>;"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color:#94a3b8; font-size:0.9rem; padding:10px 0;">Sin transacciones registradas aún.</p>
                <?php endif; ?>
            </div>

            <!-- Canal POS vs Web -->
            <h3 class="fin-panel-title" style="margin-top:24px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="color:#15803D;"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
                <span>Canal POS vs Web E-Commerce</span>
            </h3>
            <div class="fin-channel-grid">
                <div class="fin-channel-card" style="background:#f0fdf4; border:1px solid #bbf7d0;">
                    <div style="font-size:0.75rem; font-weight:800; color:#166534; text-transform:uppercase;">POS TIENDA</div>
                    <div style="font-size:1.35rem; font-weight:800; color:#15803d;">S/ <?= number_format($metricas['ventas_pos'], 2); ?></div>
                    <small style="color:#166534; opacity:0.85;">Venta Presencial</small>
                </div>
                <div class="fin-channel-card" style="background:#eff6ff; border:1px solid #bfdbfe;">
                    <div style="font-size:0.75rem; font-weight:800; color:#1e40af; text-transform:uppercase;">WEB E-COMMERCE</div>
                    <div style="font-size:1.35rem; font-weight:800; color:#1d4ed8;">S/ <?= number_format($metricas['ventas_web'], 2); ?></div>
                    <small style="color:#1e40af; opacity:0.85;">Venta en Línea</small>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. Top Productos Más Vendidos y Rentables -->
    <div class="fin-panel-card">
        <h3 class="fin-panel-title">
            <span>Top Productos Más Vendidos y con Mayor Aporte de Ganancia</span>
        </h3>
        <div style="overflow-x:auto;">
            <table class="fin-table">
                <thead>
                    <tr>
                        <th style="width:40px; text-align:center;">#</th>
                        <th>Producto</th>
                        <th>Código / SKU</th>
                        <th style="text-align:center;">Cant. Vendida</th>
                        <th style="text-align:right;">Precio Venta</th>
                        <th style="text-align:right;">Costo Unit.</th>
                        <th style="text-align:right;">Total Ingresos</th>
                        <th style="text-align:right;">Ganancia Aportada</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($metricas['top_productos'])): ?>
                        <?php foreach ($metricas['top_productos'] as $idx => $tp): ?>
                            <tr>
                                <td style="text-align:center;">
                                    <span >
                                        <?= $idx + 1; ?>
                                    </span>
                                </td>
                                <td><strong style="color:#0f172a;"><?= htmlspecialchars($tp['nombre']); ?></strong></td>
                                <td><code style="color:#0f172a; font-weight:700;"><?= htmlspecialchars($tp['codigo'] ?? '-'); ?></code></td>
                                <td style="text-align:center; font-weight:800; color:#0f172a;"><?= $tp['total_unidades_vendidas']; ?></td>
                                <td style="text-align:right;">S/ <?= number_format($tp['precio'], 2); ?></td>
                                <td style="text-align:right; color:#94a3b8;">S/ <?= number_format($tp['precio_costo'], 2); ?></td>
                                <td style="text-align:right; font-weight:800; color:#0f172a;">S/ <?= number_format($tp['total_ingresos'], 2); ?></td>
                                <td style="text-align:right; font-weight:800; color:#15803d; font-size:0.98rem;">+ S/ <?= number_format($tp['ganancia_aportada'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="8" style="text-align:center; color:#94a3b8; padding:35px;">No hay ventas registradas aún para el cálculo de productos estrella.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php if (!AjaxHelper::isAjax()): ?>
            </div> 
        </div> 
    </div> 
</body>
</html>
<?php endif; ?>

