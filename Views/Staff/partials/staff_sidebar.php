<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$rolPanelStaff = $_SESSION['usuario_rol'] ?? '';
$nombrePanelStaff = htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Personal');
$paginaPanelStaff = $paginaPanelStaff ?? '';
$activeTab = $_GET['tab'] ?? ($paginaPanelStaff === 'entregas' ? 'dashboard' : '');

if (!isset($cantAsignados) || !isset($cantDisponibles) || !isset($cantHistorial)) {
    $cantAsignados = 0;
    $cantDisponibles = 0;
    $cantHistorial = 0;

    if ($rolPanelStaff === 'repartidor') {
        try {
            require_once __DIR__ . '/../../../Models/deliveryModel.php';
            $dm = new DeliveryModel();
            $misEnv = $dm->listarEnvios(null, (int)($_SESSION['usuario_id'] ?? 0));
            $dispEnv = $dm->listarEnvios(null, null, true);
            $cantAsignados = count(array_filter($misEnv, function($e) { 
                $est = strtolower($e['estado_delivery'] ?? '');
                return !in_array($est, ['entregado', 'fallido', 'cancelado'], true); 
            }));
            $cantHistorial = count(array_filter($misEnv, function($e) { 
                $est = strtolower($e['estado_delivery'] ?? '');
                return in_array($est, ['entregado', 'fallido', 'cancelado'], true); 
            }));
            $cantDisponibles = count($dispEnv);
        } catch (Throwable $e) {}
    }
}

$tituloMobile = ($rolPanelStaff === 'cajero') ? 'Panel de caja (Cajero)' : 'Panel de reparto (Repartidor)';
$rolLabel = ($rolPanelStaff === 'cajero') ? 'Cajero' : 'Repartidor';
?>
<script src="Scripts/normalizador-modales.js?v=<?= time(); ?>" defer></script>
<link rel="stylesheet" href="Styles/Vistas/Staff/partials/staff_sidebar.css?v=<?= time(); ?>">

<div class="staff-sidebar-overlay" id="staffSidebarOverlay"></div>

<aside class="staff-sidebar" id="staffSidebar" aria-label="Navegación del personal">
    <!-- Header Móvil (Imagen 5) -->
    <div class="sidebar-header-mobile">
        <div class="sidebar-mobile-heading">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
            </svg>
            <span><?= $tituloMobile ?></span>
        </div>
        <button type="button" class="sidebar-close-btn" id="staffSidebarClose" aria-label="Cerrar Menú">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>
    </div>

    <!-- Brand Desktop (Imagen 2) -->
    <a href="index.php?action=home_usuario" class="sidebar-brand hide-on-mobile">
        <img src="Images/logo.png" alt="Logo" class="sidebar-brand-img">
        <span>La Caserita</span>
    </a>

    <!-- Menú de Navegación -->
    <nav class="sidebar-nav">
        <div class="sidebar-nav-title sidebar-mobile-menu-title">MENÚ PRINCIPAL</div>
        <div class="sidebar-nav-title">OPERACIONES</div>
        <ul class="sidebar-menu">
            <?php if ($rolPanelStaff === 'cajero'): ?>
                <li>
                    <a href="index.php?action=panel_cajero" class="sidebar-link <?= ($paginaPanelStaff === 'caja') ? 'active' : ''; ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="3" width="7" height="7"></rect>
                            <rect x="14" y="3" width="7" height="7"></rect>
                            <rect x="14" y="14" width="7" height="7"></rect>
                            <rect x="3" y="14" width="7" height="7"></rect>
                        </svg>
                        Dashboard de caja
                    </a>
                </li>
                <li>
                    <a href="index.php?action=pos" class="sidebar-link <?= ($paginaPanelStaff === 'pos') ? 'active' : ''; ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                            <line x1="8" y1="21" x2="16" y2="21"></line>
                            <line x1="12" y1="17" x2="12" y2="21"></line>
                        </svg>
                        Caja POS
                    </a>
                </li>
                    <li>
                      <a href="index.php?action=productos_cajero" class="sidebar-link <?= ($paginaPanelStaff === 'productos') ? 'active' : ''; ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                          <path d="M21 8a2 2 0 0 0-2-2h-3l-2-3h-4L8 6H5a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2z"></path>
                          <circle cx="12" cy="13" r="3"></circle>
                        </svg>
                        Productos
                      </a>
                    </li>
                <li>
                    <a href="index.php?action=arqueo_caja" class="sidebar-link <?= ($paginaPanelStaff === 'arqueo') ? 'active' : ''; ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M16 16l3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1z"></path>
                            <path d="M2 16l3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1z"></path>
                            <path d="M7 21h10"></path>
                            <path d="M12 3v18"></path>
                            <path d="M3 7h2c2 0 5-1 7-2 2 1 5 2 7 2h2"></path>
                        </svg>
                        Arqueo de caja
                    </a>
                </li>
                <li>
                    <a href="index.php?action=historial_pos" class="sidebar-link <?= ($paginaPanelStaff === 'historial') ? 'active' : ''; ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                        Historial de ventas
                    </a>
                </li>
            <?php elseif ($rolPanelStaff === 'repartidor'): ?>
                <li>
                    <a href="index.php?action=panel_repartidor&tab=dashboard" class="sidebar-link <?= ($paginaPanelStaff === 'entregas' && $activeTab === 'dashboard') ? 'active' : ''; ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="3" width="7" height="7"></rect>
                            <rect x="14" y="3" width="7" height="7"></rect>
                            <rect x="14" y="14" width="7" height="7"></rect>
                            <rect x="3" y="14" width="7" height="7"></rect>
                        </svg>
                        Dashboard
                    </a>
                </li>
                <li>
                    <a href="index.php?action=panel_repartidor&tab=assigned" class="sidebar-link <?= ($paginaPanelStaff === 'entregas' && $activeTab === 'assigned') ? 'active' : ''; ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="1" y="3" width="15" height="13"></rect>
                            <polygon points="16 8 20 8 23 11 23 16 16 16 8"></polygon>
                            <circle cx="5.5" cy="18.5" r="2.5"></circle>
                            <circle cx="18.5" cy="18.5" r="2.5"></circle>
                        </svg>
                        Asignados
                        <span class="sidebar-badge"><?= $cantAsignados ?></span>
                    </a>
                </li>
                <li>
                    <a href="index.php?action=panel_repartidor&tab=available" class="sidebar-link <?= ($paginaPanelStaff === 'entregas' && $activeTab === 'available') ? 'active' : ''; ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        Tomar pedidos
                        <span class="sidebar-badge"><?= $cantDisponibles ?></span>
                    </a>
                </li>
                <li>
                    <a href="index.php?action=panel_repartidor&tab=history" class="sidebar-link <?= ($paginaPanelStaff === 'entregas' && $activeTab === 'history') ? 'active' : ''; ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                        Historial
                        <span class="sidebar-badge"><?= $cantHistorial ?></span>
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>

    <!-- Footer Sidebar (Imagen 2 y 5) -->
    <div class="sidebar-footer">
        <a href="index.php?action=mi_cuenta" class="sidebar-link">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
            </svg>
            Mi Perfil (<?= $rolLabel ?>)
        </a>
        <a href="index.php?action=logout" class="sidebar-link sidebar-logout-btn">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                <polyline points="16 17 21 12 16 7"></polyline>
                <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
            Cerrar Sesión
        </a>
    </div>
</aside>

<script src="Scripts/Vistas/Staff/partials/staff_sidebar.js?v=<?= time(); ?>"></script>
