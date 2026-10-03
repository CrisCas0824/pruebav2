<?php
/**
 * Menú Lateral Deslizable para Móviles (Hamburguesa)
 * Exactamente 7 opciones principales con acordeón multinivel,
 * chevrons amplios, listado de 3er orden neutral y resaltado activo.
 */
require_once __DIR__ . '/navigation_data.php';

$nombreUsuarioSidebar = $nombre ?? ($_SESSION['usuario_nombre'] ?? 'Invitado');
$estaAutenticadoSidebar = isset($_SESSION['usuario_id']);
$rolSidebar = $_SESSION['usuario_rol'] ?? '';
$esAdminSidebar = $rolSidebar === 'admin';
$esPersonalSidebar = in_array($rolSidebar, ['admin', 'cajero', 'repartidor'], true);
$rutaPerfilSidebar = $esAdminSidebar ? 'index.php?action=mi_perfil_admin' : (($rolSidebar === 'cajero') ? 'index.php?action=panel_cajero' : (($rolSidebar === 'repartidor') ? 'index.php?action=panel_repartidor' : 'index.php?action=mi_cuenta'));
$rutaInicioSidebar = 'index.php?action=home_usuario';

$actionActualSidebar = $_GET['action'] ?? 'home_usuario';
$idCatActualSidebar = isset($_GET['id']) ? (int)$_GET['id'] : null;
$idGrupoActualSidebar = isset($_GET['id_grupo']) ? (int)$_GET['id_grupo'] : null;
$idMomentoActualSidebar = isset($_GET['id_momento']) ? (int)$_GET['id_momento'] : null;
$nombreCatActualSidebar = mb_strtolower(trim($_GET['nombre'] ?? ''));
$subActualSidebar = mb_strtolower(trim($_GET['sub'] ?? ''));
$esOfertasActualSidebar = isset($_GET['ofertas']) && $_GET['ofertas'] == '1';

if ($actionActualSidebar === 'detalle_producto' && isset($producto)) {
    $actionActualSidebar = 'ver_categoria';
    $idCatActualSidebar = (int)($producto['id_categoria'] ?? 0);
    $idGrupoActualSidebar = (int)($producto['id_grupo'] ?? 0);
    $idMomentoActualSidebar = (int)($producto['id_momento'] ?? 0);
}

$activeMomentoIdSidebar = null;
if (!$esOfertasActualSidebar && ($actionActualSidebar === 'ver_categoria' || $actionActualSidebar === 'detalle_producto')) {
    if (!empty($idMomentoActualSidebar)) {
        $activeMomentoIdSidebar = $idMomentoActualSidebar;
    } elseif (isset($producto) && !empty($producto['id_momento'])) {
        $activeMomentoIdSidebar = (int)$producto['id_momento'];
    } elseif (!empty($product_bd)) {
        $primerP = reset($product_bd);
        $activeMomentoIdSidebar = (int)($primerP['id_momento'] ?? 0);
    }
}

$paramsNavSidebar = [
    'action'            => $actionActualSidebar,
    'id'                => $idCatActualSidebar,
    'id_grupo'          => $idGrupoActualSidebar,
    'id_momento'        => $idMomentoActualSidebar,
    'active_momento_id' => $activeMomentoIdSidebar,
    'es_ofertas'        => $esOfertasActualSidebar,
    'nombre'            => $nombreCatActualSidebar,
    'sub'               => $subActualSidebar
];

$esInicioActivoSidebar = ($actionActualSidebar === 'home_usuario' || $actionActualSidebar === 'home_admin' || $actionActualSidebar === 'home') && empty($_GET['nombre']) && empty($_GET['id']) && empty($_GET['id_grupo']) && empty($_GET['id_momento']) && !$esOfertasActualSidebar;
?>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="mobile-sidebar" id="mobileSidebar">
    <div class="sidebar-header <?= $esPersonalSidebar ? 'sidebar-header-staff' : ''; ?>">
        <?php if ($estaAutenticadoSidebar): ?>
            <a href="<?= htmlspecialchars($rutaPerfilSidebar); ?>" class="sidebar-user-link" title="<?= $esPersonalSidebar ? 'Abrir mi panel de trabajo' : 'Ir a Mi Cuenta'; ?>" onclick="cerrarSidebar();">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
                <span><?= htmlspecialchars($esPersonalSidebar ? (($rolSidebar === 'admin') ? 'Panel ERP (Admin)' : (($rolSidebar === 'cajero') ? 'Panel de caja (Cajero)' : 'Panel de reparto (Repartidor)')) : $nombreUsuarioSidebar); ?></span>
            </a>
        <?php else: ?>
            <button type="button" class="sidebar-user-link btn-sidebar-auth" onclick="cerrarSidebar(); if(typeof abrirModalAuth === 'function') abrirModalAuth('login');">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
                <span>Iniciar Sesión / Registrarse</span>
            </button>
        <?php endif; ?>
        <button type="button" class="sidebar-close" id="closeSidebar" aria-label="Cerrar menú">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
    </div>

    <div class="sidebar-content">
        <?php if ($esPersonalSidebar): ?>
        <div class="sidebar-title sidebar-mobile-menu-title" style="padding: 16px 20px 4px; font-size: 0.72rem; font-weight: 800; color: #15803d; letter-spacing: 0.05em; text-transform: uppercase;">MENÚ PRINCIPAL</div>
        <div class="sidebar-title" style="padding: 10px 20px 6px; font-size: 0.68rem; font-weight: 700; color: #15803d; letter-spacing: 0.5px; text-transform: uppercase; border-bottom: none;">OPERACIONES</div>
        <ul class="sidebar-menu-list sidebar-staff-menu" style="padding: 0 16px; margin: 0; list-style: none;">
            <?php if ($rolSidebar === 'cajero'): ?>
                <li style="margin-bottom: 2px;">
                    <a href="index.php?action=panel_cajero" class="sidebar-leaf-link level-1 <?= $actionActualSidebar === 'panel_cajero' ? 'is-active' : ''; ?>" onclick="cerrarSidebar();">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"></rect><line x1="12" y1="8" x2="12" y2="16"></line><line x1="8" y1="12" x2="16" y2="12"></line></svg>
                        <span class="sidebar-item-label">Panel de caja</span>
                    </a>
                </li>
                <li style="margin-bottom: 2px;">
                    <a href="index.php?action=pos" class="sidebar-leaf-link level-1 <?= $actionActualSidebar === 'pos' ? 'is-active' : ''; ?>" onclick="cerrarSidebar();">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line></svg>
                        <span class="sidebar-item-label">Caja POS</span>
                    </a>
                </li>
                <li style="margin-bottom: 2px;">
                    <a href="index.php?action=historial_pos" class="sidebar-leaf-link level-1 <?= $actionActualSidebar === 'historial_pos' ? 'is-active' : ''; ?>" onclick="cerrarSidebar();">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                        <span class="sidebar-item-label">Historial de ventas</span>
                    </a>
                </li>
            <?php elseif ($rolSidebar === 'repartidor'): ?>
                <li style="margin-bottom: 2px;">
                    <a href="index.php?action=panel_repartidor" class="sidebar-leaf-link level-1 <?= $actionActualSidebar === 'panel_repartidor' ? 'is-active' : ''; ?>" onclick="cerrarSidebar();">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
                        <span class="sidebar-item-label">Mis entregas</span>
                    </a>
                </li>
            <?php else: ?>
                <li style="margin-bottom: 2px;">
                    <a href="index.php?action=dashboard_ganancias" class="sidebar-leaf-link level-1" onclick="cerrarSidebar();">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
                        <span class="sidebar-item-label">Dashboard Financiero</span>
                    </a>
                </li>
            <?php endif; ?>
        </ul>
        <?php else: ?>
        <div class="sidebar-title">MENÚ PRINCIPAL</div>
        <ul class="sidebar-menu-list">
            <!-- 1. Inicio -->
            <li class="sidebar-nav-row">
                <a href="<?= htmlspecialchars($rutaInicioSidebar); ?>" class="sidebar-leaf-link level-1 <?= $esInicioActivoSidebar ? 'is-active' : ''; ?>" onclick="cerrarSidebar();">
                    <span class="sidebar-item-label">Inicio</span>
                </a>
            </li>

            <!-- 2-6. Categorías Maestras Acordeón Multinivel -->
            <?php foreach ($menuMultinivel as $idxGrupo => $grupo): 
                $isGrupoActive = ($activeMomentoIdSidebar !== null && (int)($grupo['id_momento'] ?? 0) === $activeMomentoIdSidebar);
                $isGrupoExact = ($actionActualSidebar === 'ver_categoria' && $nombreCatActualSidebar === mb_strtolower($grupo['titulo']));
            ?>
                <li class="sidebar-acc-item <?= $isGrupoActive ? 'open' : ''; ?>" id="sidebarGroup_<?= $idxGrupo; ?>">
                    <div class="sidebar-acc-header">
                        <a href="<?= htmlspecialchars($grupo['url']); ?>" class="sidebar-acc-main-link <?= $isGrupoExact ? 'is-active' : ''; ?>" onclick="cerrarSidebar();">
                            <?= htmlspecialchars($grupo['titulo']); ?>
                        </a>
                        <button type="button" class="sidebar-acc-toggle" onclick="toggleSidebarAccordion(this);" aria-label="Desplegar <?= htmlspecialchars($grupo['titulo']); ?>">
                            <span class="sidebar-acc-chevron">▾</span>
                        </button>
                    </div>

                    <div class="sidebar-acc-body">
                        <?php foreach ($grupo['subcategorias'] as $idxSub => $subcat): 
                            $tieneSubitems = !empty($subcat['subitems']);
                            $isSubcatActive = $isGrupoActive ? evaluarEstadoNavegacion($subcat, 'subcat', $paramsNavSidebar) : false;
                            $isSubcatExact = ($actionActualSidebar === 'ver_categoria' && $nombreCatActualSidebar === mb_strtolower($subcat['nombre']) && empty($subActualSidebar));
                        ?>
                            <div class="sidebar-subacc-item <?= $tieneSubitems ? 'has-children' : ''; ?> <?= $isSubcatActive ? 'open' : ''; ?>" id="sidebarSub_<?= $idxGrupo; ?>_<?= $idxSub; ?>">
                                <div class="sidebar-subacc-header">
                                    <a href="<?= htmlspecialchars($subcat['url']); ?>" class="sidebar-subacc-link <?= $isSubcatExact ? 'is-active' : ''; ?>" onclick="cerrarSidebar();">
                                        <?= htmlspecialchars($subcat['nombre']); ?>
                                    </a>
                                    <?php if ($tieneSubitems): ?>
                                        <button type="button" class="sidebar-subacc-toggle" onclick="toggleSidebarAccordion(this);" aria-label="Desplegar <?= htmlspecialchars($subcat['nombre']); ?>">
                                            <span class="sidebar-acc-chevron">▸</span>
                                        </button>
                                    <?php endif; ?>
                                </div>

                                <?php if ($tieneSubitems): ?>
                                    <div class="sidebar-subacc-body">
                                        <?php foreach ($subcat['subitems'] as $item): 
                                            $isLeafActive = $isSubcatActive ? evaluarEstadoNavegacion($item, 'leaf', $paramsNavSidebar) : false;
                                        ?>
                                            <a href="<?= htmlspecialchars($item['url']); ?>" class="sidebar-leaf-link level-3 <?= $isLeafActive ? 'is-active' : ''; ?>" onclick="cerrarSidebar();">
                                                <?= htmlspecialchars($item['nombre']); ?>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </li>
            <?php endforeach; ?>

            <!-- 7. Ofertas (Texto directo: "Ofertas") -->
            <li class="sidebar-nav-row">
                <a href="index.php?action=ver_categoria&ofertas=1&nombre=<?= urlencode('Ofertas'); ?>" class="sidebar-leaf-link level-1 sidebar-ofertas-link <?= ($esOfertasActualSidebar && $actionActualSidebar === 'ver_categoria') ? 'is-active' : ''; ?>" onclick="cerrarSidebar();">
                    <span class="sidebar-item-label">Ofertas</span>
                </a>
            </li>
        </ul>
        <?php endif; ?>
    </div>

        <div class="sidebar-footer">
            <?php if ($estaAutenticadoSidebar): ?>
                <?php if ($esPersonalSidebar): ?>
                    <a href="index.php?action=<?= $rolSidebar === 'admin' ? 'mi_perfil_admin' : 'mi_cuenta'; ?>" class="sidebar-leaf-link level-1" onclick="cerrarSidebar();" style="display:flex; align-items:center; gap:10px; padding:8px 14px; margin-bottom:8px; border-radius:8px; color:#475569; font-weight:600; text-decoration:none;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        <span>Mi Perfil (<?= $rolSidebar === 'cajero' ? 'Cajero' : ($rolSidebar === 'repartidor' ? 'Repartidor' : 'Admin') ?>)</span>
                    </a>
                <?php endif; ?>
                <form method="POST" action="index.php?action=logout" style="margin:0;">
                    <button type="submit" class="btn-logout <?= $esPersonalSidebar ? 'staff-logout-pill' : ''; ?>" style="<?= $esPersonalSidebar ? 'background:#fee2e2; color:#dc2626; border:none; border-radius:10px; font-weight:700; padding:11px 16px; display:flex; align-items:center; justify-content:center; gap:8px; cursor:pointer; width:100%; font-size:0.85rem;' : ''; ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                            <polyline points="16 17 21 12 16 7"></polyline>
                            <line x1="21" y1="12" x2="9" y2="12"></line>
                        </svg>
                        Cerrar Sesión
                    </button>
                </form>
            <?php else: ?>
                <button type="button" class="btn-logout" onclick="cerrarSidebar(); if(typeof abrirModalAuth === 'function') abrirModalAuth('login');">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                        <polyline points="10 17 15 12 10 7"></polyline>
                        <line x1="15" y1="12" x2="3" y2="12"></line>
                    </svg>
                    Iniciar Sesión
                </button>
            <?php endif; ?>
        </div>
</div>
