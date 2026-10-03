<?php
require_once 'Core/ajaxHelper.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$nombre = htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Administrador');
$flash_success = $_SESSION['flash_success'] ?? null;
$flash_error   = $_SESSION['flash_error']   ?? null;
$avisoAccion = $_GET['aviso'] ?? '';
if (isset($_SESSION)) { unset($_SESSION['flash_success'], $_SESSION['flash_error']); }
if (!isset($categorias_bd)) {
    require_once 'Models/categoryModel.php';
    $catModel = new CategoryModel();
    $categorias_bd = $catModel->getCategoria();
}
if (!isset($moments_bd)) {
    require_once 'Models/momentConModel.php';
    $momModel = new MomentConModel();
    $moments_bd = $momModel->getMomento();
}
if (!isset($grupMomento_bd)) {
    require_once 'Models/grupoMomentModel.php';
    $grupModel = new GrupMomentModel();
    $grupMomento_bd = $grupModel->getGrupo();
}

$countMoments = count($moments_bd);
$countGrupos = count($grupMomento_bd);
$countSubcats = count($categorias_bd);

$countGruposActivos = 0;
$countGruposInactivos = 0;
if (!empty($grupMomento_bd)) {
    foreach ($grupMomento_bd as $g) {
        $act = !isset($g['activo']) || $g['activo'] == 1;
        if ($act) $countGruposActivos++;
        else $countGruposInactivos++;
    }
}

$countSubcatsActivas = 0;
$countSubcatsInactivas = 0;
if (!empty($categorias_bd)) {
    foreach ($categorias_bd as $c) {
        $act = !isset($c['activo']) || $c['activo'] == 1;
        if ($act) $countSubcatsActivas++;
        else $countSubcatsInactivas++;
    }
}
?>
<?php if (!AjaxHelper::isAjax()): ?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Gestion de Categorias - ERP La Caserita</title>
<link rel="icon" type="image/png" href="Images/icono.png">
<link rel="stylesheet" href="Styles/global.css?v=<?= time(); ?>">
<link rel="stylesheet" href="Styles/styles-home.css?v=<?= time(); ?>">
<link rel="stylesheet" href="Styles/styles-users-admin.css?v=<?= time(); ?>">
<link rel="stylesheet" href="Styles/admin-spa.css?v=<?= time(); ?>">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="Scripts/admin-spa.js?v=<?= time(); ?>" defer></script>
<link rel="stylesheet" href="Styles/Vistas/Admin/categorias_admin.css?v=<?= filemtime(__DIR__ . '/../../Styles/Vistas/Admin/categorias_admin.css'); ?>">
</head>
<body>
<div class="admin-layout">
<?php include __DIR__ . '/partials/admin_sidebar.php'; ?>
<div class="admin-main">
<header class="admin-topbar">
<div class="admin-topbar-left">
<button type="button" class="admin-hamburger-btn hide-on-desktop" id="openAdminSidebar" aria-label="Abrir Menu">
<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
</button>
<div class="admin-topbar-brand">
<span class="brand-icon"><svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg></span>
<span class="brand-title">Gestion de Categorias y Subcategorias</span>
</div>
</div>
<div class="admin-topbar-actions"><?php include __DIR__ . '/partials/admin_topbar_actions.php'; ?></div>
</header>
<div id="admin-container">
<?php endif; ?>
<div class="categorias-admin-wrapper" style="padding:26px 24px;min-height:calc(100vh - 75px);">


<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px;margin-bottom:24px;">
<div>
<h1 id="mainTitleAdmin" style="font-size:1.65rem;font-weight:800;color:#0f172a;margin:0 0 6px 0;">Gestion de Categorias</h1>
</div>
<div style="display:flex;gap:10px;flex-wrap:wrap;">
<button type="button" id="btnNuevoGrupo" onclick="window.abrirModalGrupo()" style="background:#15803d;color:#fff;border:none;font-weight:700;padding:10px 18px;border-radius:10px;cursor:pointer;display:inline-flex;align-items:center;gap:8px;box-shadow:0 4px 12px rgba(21,128,61,.25);transition:all .2s ease;">
<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
<span>Nueva Categoria</span>
</button>
<button type="button" id="btnNuevaCategoria" onclick="window.abrirModalCategoria()" style="background:#0284c7;color:#fff;border:none;font-weight:700;padding:10px 18px;border-radius:10px;cursor:pointer;display:inline-flex;align-items:center;gap:8px;box-shadow:0 4px 12px rgba(2,132,199,.25);transition:all .2s ease;">
<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
<span>Nueva Subcategoria</span>
</button>
</div>
</div>

<?php if ($flash_success || $flash_error || in_array($avisoAccion, ['estado_actualizado', 'estado_error'], true)): 
    $esError = ($flash_error || $avisoAccion === 'estado_error');
    $msgNotif = $flash_error ?: $flash_success ?: ($avisoAccion === 'estado_actualizado' ? 'Estado actualizado correctamente.' : 'No se pudo actualizar el estado.');
?>


<script>
(function() {
    const tipo = <?= json_encode($esError ? 'error' : 'success'); ?>;
    const mensaje = <?= json_encode($msgNotif, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    function mostrarAvisoCategorias() {
        if (typeof SPA !== 'undefined' && typeof SPA.toast === 'function') {
            if (typeof SPA.createToastContainer === 'function') SPA.createToastContainer();
            SPA.toast(tipo, tipo === 'error' ? 'Error' : 'Éxito', mensaje, 4000);
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
        toast.setAttribute('role', tipo === 'error' ? 'alert' : 'status');
        toast.style.cssText = 'position:fixed;right:20px;top:20px;z-index:99999;display:flex;align-items:center;gap:12px;width:min(420px,calc(100vw - 32px));padding:14px 18px;background:#fff;border-radius:14px;box-shadow:0 12px 32px rgba(15,23,42,.2);border:1px solid ' + (tipo === 'error' ? '#fecaca' : '#bbf7d0') + ';color:#0f172a;';
        const icon = document.createElement('span');
        icon.className = 'toast-icon';
        icon.innerHTML = tipo === 'error'
            ? '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>'
            : '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#15803d" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><path d="m9 11 3 3L22 4"></path></svg>';
        const content = document.createElement('div');
        content.style.cssText = 'flex:1;';
        const title = document.createElement('div');
        title.style.cssText = 'font-weight:700;font-size:0.95rem;color:#0f172a;margin-bottom:2px;';
        title.textContent = tipo === 'error' ? 'Error' : 'Éxito';
        const description = document.createElement('div');
        description.style.cssText = 'font-size:0.86rem;color:#475569;';
        description.textContent = mensaje;
        content.append(title, description);
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
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', mostrarAvisoCategorias, { once: true });
    } else {
        mostrarAvisoCategorias();
    }
})();
</script>
<?php endif; ?>

<!-- TARJETAS CUADRADAS DE MÉTRICAS (ORDEN SOLICITADO: SUBCATEGORÍAS, CATEGORÍAS, MACROCATEGORÍAS, CAT ACTIVAS/INACTIVAS, SUBCAT ACTIVAS/INACTIVAS) -->
<div class="stats-grid-square">
    <div class="stat-card-square active-tab" id="tabCardSubcategorias" onclick="window.switchTab('subcategorias', 'all')">
        <span class="stat-title">SUBCATEGORÍAS (N3)</span>
        <span class="stat-val"><?= $countSubcats; ?></span>
    </div>
    <div class="stat-card-square" id="tabCardCategorias" onclick="window.switchTab('categorias', 'all')">
        <span class="stat-title">CATEGORÍAS (N2)</span>
        <span class="stat-val"><?= $countGrupos; ?></span>
    </div>
    <div class="stat-card-square" id="tabCardMacrocategorias" onclick="window.switchTab('macrocategorias', 'all')">
        <span class="stat-title">MACROCATEGORÍAS (N1)</span>
        <span class="stat-val"><?= $countMoments; ?></span>
    </div>
    <div class="stat-card-square" id="tabCardCatActivas" onclick="window.switchTab('categorias', 'activas')">
        <span class="stat-title">CAT. ACTIVAS</span>
        <span class="stat-val"><?= $countGruposActivos; ?></span>
    </div>
    <div class="stat-card-square" id="tabCardCatInactivas" onclick="window.switchTab('categorias', 'inactivas')">
        <span class="stat-title">CAT. INACTIVAS</span>
        <span class="stat-val"><?= $countGruposInactivos; ?></span>
    </div>
    <div class="stat-card-square" id="tabCardSubActivas" onclick="window.switchTab('subcategorias', 'activas')">
        <span class="stat-title">SUBCAT. ACTIVAS</span>
        <span class="stat-val"><?= $countSubcatsActivas; ?></span>
    </div>
    <div class="stat-card-square" id="tabCardSubInactivas" onclick="window.switchTab('subcategorias', 'inactivas')">
        <span class="stat-title">SUBCAT. INACTIVAS</span>
        <span class="stat-val"><?= $countSubcatsInactivas; ?></span>
    </div>
</div>

<!-- TAB 1: MACROCATEGORIAS (NIVEL 1) -->
<div id="tabViewMacrocategorias" style="display:none;background:#fff;border-radius:14px;border:1px solid #e2e8f0;box-shadow:0 4px 12px rgba(0,0,0,.03);overflow:hidden;margin-bottom:30px;">
<div style="padding:18px 24px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid #f1f5f9;flex-wrap:wrap;gap:14px;">
<div><h3 style="margin:0;font-size:1.15rem;font-weight:800;color:#0f172a;">Listado de Macrocategorias (Nivel 1)</h3><p style="margin:3px 0 0;font-size:.82rem;color:#64748b;">Las familias maestras de la barra de navegacion superior.</p></div>
<input type="text" id="filtroMom" onkeyup="window.filtrarTabla('tablaMom','filtroMom',1)" placeholder="Buscar macrocategoria..." style="padding:8px 14px;border:1px solid #cbd5e1;border-radius:8px;font-size:.88rem;min-width:240px;">
</div>
<div style="overflow-x:auto;">
<table style="width:100%;border-collapse:collapse;text-align:left;" id="tablaMom">
<thead><tr style="background:#f8fafc;border-bottom:2px solid #e2e8f0;">
<th style="padding:12px 18px;font-size:.76rem;font-weight:800;color:#475569;text-transform:uppercase;">ID</th>
<th style="padding:12px 18px;font-size:.76rem;font-weight:800;color:#475569;text-transform:uppercase;">Macrocategoria (Nivel 1)</th>
<th style="padding:12px 18px;font-size:.76rem;font-weight:800;color:#475569;text-transform:uppercase;">Descripcion</th>
<th style="padding:12px 18px;font-size:.76rem;font-weight:800;color:#475569;text-transform:uppercase;">Estado</th>
</tr></thead>
<tbody>
<?php foreach ($moments_bd as $m): ?>
<tr style="border-bottom:1px solid #f1f5f9;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
<td style="padding:14px 18px;font-weight:700;color:#64748b;font-size:.85rem;">#<?= (int)$m['id_momento']; ?></td>
<td style="padding:14px 18px;"><strong style="font-size:.95rem;color:#0f172a;"><?= htmlspecialchars($m['nombre'] ?? ($m['titulo'] ?? '')); ?></strong></td>
<td style="padding:14px 18px;color:#64748b;font-size:.88rem;max-width:300px;"><?= !empty($m['descripcion']) ? htmlspecialchars($m['descripcion']) : '<span style="color:#94a3b8;font-style:italic;">Sin descripcion</span>'; ?></td>
<td style="padding:14px 18px;">
    <span class="badge-status-activo"><svg width="8" height="8" fill="currentColor" viewBox="0 0 8 8"><circle cx="4" cy="4" r="4"/></svg>Activo</span>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<div style="display:flex;align-items:center;justify-content:center;padding:14px 20px;border-top:1px solid #e2e8f0;background:#fff;" id="paginationControlsMom">
    <div style="display:flex;align-items:center;gap:6px;" id="paginationListMom"></div>
</div>
</div>

<!-- TAB 2: CATEGORIAS (NIVEL 2) -->
<div id="tabViewCategorias" style="display:none;background:#fff;border-radius:14px;border:1px solid #e2e8f0;box-shadow:0 4px 12px rgba(0,0,0,.03);overflow:hidden;margin-bottom:30px;">
<div style="padding:18px 24px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid #f1f5f9;flex-wrap:wrap;gap:14px;">
<div><h3 style="margin:0;font-size:1.15rem;font-weight:800;color:#0f172a;">Listado de Categorias (Nivel 2)</h3><p style="margin:3px 0 0;font-size:.82rem;color:#64748b;">Categorias que agrupan los productos bajo cada macrocategoria.</p></div>
<input type="text" id="filtroGrupos" onkeyup="window.filtrarTabla('tablaGrupos','filtroGrupos',2)" placeholder="Buscar categoria..." style="padding:8px 14px;border:1px solid #cbd5e1;border-radius:8px;font-size:.88rem;min-width:240px;">
</div>
<div style="overflow-x:auto;">
<table style="width:100%;border-collapse:collapse;text-align:left;" id="tablaGrupos">
<thead><tr style="background:#f8fafc;border-bottom:2px solid #e2e8f0;">
<th style="padding:12px 18px;font-size:.76rem;font-weight:800;color:#475569;text-transform:uppercase;">ID</th>
<th style="padding:12px 18px;font-size:.76rem;font-weight:800;color:#475569;text-transform:uppercase;">Imagen</th>
<th style="padding:12px 18px;font-size:.76rem;font-weight:800;color:#475569;text-transform:uppercase;">Categoria (Nivel 2)</th>
<th style="padding:12px 18px;font-size:.76rem;font-weight:800;color:#475569;text-transform:uppercase;">Macrocategoria (Nivel 1)</th>
<th style="padding:12px 18px;font-size:.76rem;font-weight:800;color:#475569;text-transform:uppercase;">Descripcion</th>
<th style="padding:12px 18px;font-size:.76rem;font-weight:800;color:#475569;text-transform:uppercase;">Estado</th>
<th style="padding:12px 18px;font-size:.76rem;font-weight:800;color:#475569;text-transform:uppercase;text-align:right;">Acciones</th>
</tr></thead>
<tbody>
<?php if (!empty($grupMomento_bd)): ?>
<?php foreach ($grupMomento_bd as $g): 
    $gImg = !empty($g['imagen']) ? $g['imagen'] : 'Images/icono.png'; 
    $esActivo = !isset($g['activo']) || $g['activo'] == 1;
?>
<tr data-activo="<?= $esActivo ? 1 : 0; ?>" style="border-bottom:1px solid #f1f5f9;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
<td style="padding:14px 18px;font-weight:700;color:#64748b;font-size:.85rem;">#<?= (int)$g['id_grupo']; ?></td>
<td style="padding:14px 18px;"><img src="<?= htmlspecialchars($gImg); ?>" alt="" class="cat-thumb-img" onerror="this.onerror=null;this.src='Images/icono.png';"></td>
<td style="padding:14px 18px;"><strong style="font-size:.95rem;color:#0f172a;"><?= htmlspecialchars($g['nombre']); ?></strong></td>
<td style="padding:14px 18px;">
    <?php if (!empty($g['momento_nombre'])): ?>
        <span class="cat-hierarchy-pill cat-macro-pill"><?= htmlspecialchars($g['momento_nombre']); ?></span>
    <?php else: ?>
        <span style="color:#94a3b8;font-size:.82rem;font-style:italic;">Sin asignar</span>
    <?php endif; ?>
</td>
<td style="padding:14px 18px;color:#64748b;font-size:.88rem;max-width:240px;"><?= !empty($g['descripcion']) ? htmlspecialchars($g['descripcion']) : '<span style="color:#94a3b8;font-style:italic;">Sin descripcion</span>'; ?></td>
<td style="padding:14px 18px;">
    <?php if ($esActivo): ?>
        <span class="badge-status-activo"><svg width="8" height="8" fill="currentColor" viewBox="0 0 8 8"><circle cx="4" cy="4" r="4"/></svg>Activo</span>
    <?php else: ?>
        <span class="badge-status-inactivo"><svg width="8" height="8" fill="currentColor" viewBox="0 0 8 8"><circle cx="4" cy="4" r="4"/></svg>Inactivo</span>
    <?php endif; ?>
</td>
<td style="padding:14px 18px;text-align:right;white-space:nowrap;">
<button type="button" class="cat-action-btn cat-btn-edit" aria-label="Editar categoria" title="Editar categoria" onclick='window.editarGrupo(<?= json_encode($g,JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP); ?>)'>
<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg></button>

<?php if ($esActivo): ?>
<button type="button" class="cat-action-btn cat-btn-inactivar" aria-label="Inactivar categoria" title="Inactivar categoria" onclick='window.confirmarCambioEstadoCategoria("grupo", <?= (int)$g['id_grupo']; ?>, 0, <?= json_encode($g['nombre'], JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP); ?>)'>
<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line></svg></button>
<?php else: ?>
<button type="button" class="cat-action-btn cat-btn-activar" aria-label="Activar categoria" title="Activar categoria" onclick='window.confirmarCambioEstadoCategoria("grupo", <?= (int)$g['id_grupo']; ?>, 1, <?= json_encode($g['nombre'], JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP); ?>)'>
<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><path d="m8 12 2.5 2.5L16 9"></path></svg></button>
<?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
<?php else: ?>
<tr><td colspan="7" style="text-align:center;padding:40px;color:#64748b;">No hay categorias registradas.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>
<div style="display:flex;align-items:center;justify-content:center;padding:14px 20px;border-top:1px solid #e2e8f0;background:#fff;" id="paginationControlsGrupos">
    <div style="display:flex;align-items:center;gap:6px;" id="paginationListGrupos"></div>
</div>
</div>

<!-- TAB 3: SUBCATEGORIAS (NIVEL 3) -->
<div id="tabViewSubcategorias" style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;box-shadow:0 4px 12px rgba(0,0,0,.03);overflow:hidden;margin-bottom:30px;">
<div style="padding:18px 24px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid #f1f5f9;flex-wrap:wrap;gap:14px;">
<div><h3 style="margin:0;font-size:1.15rem;font-weight:800;color:#0f172a;">Listado de Subcategorias (Nivel 3)</h3><p style="margin:3px 0 0;font-size:.82rem;color:#64748b;">Subcategorias de productos asociadas a una Categoria y Macrocategoria.</p></div>
<input type="text" id="filtroCategorias" onkeyup="window.filtrarTabla('tablaCategorias','filtroCategorias',2)" placeholder="Buscar subcategoria..." style="padding:8px 14px;border:1px solid #cbd5e1;border-radius:8px;font-size:.88rem;min-width:240px;">
</div>
<div style="overflow-x:auto;">
<table style="width:100%;border-collapse:collapse;text-align:left;" id="tablaCategorias">
<thead><tr style="background:#f8fafc;border-bottom:2px solid #e2e8f0;">
<th style="padding:12px 18px;font-size:.76rem;font-weight:800;color:#475569;text-transform:uppercase;">ID</th>
<th style="padding:12px 18px;font-size:.76rem;font-weight:800;color:#475569;text-transform:uppercase;">Imagen</th>
<th style="padding:12px 18px;font-size:.76rem;font-weight:800;color:#475569;text-transform:uppercase;">Subcategoria (Nivel 3)</th>
<th style="padding:12px 18px;font-size:.76rem;font-weight:800;color:#475569;text-transform:uppercase;">Macrocategoria / Categoria</th>
<th style="padding:12px 18px;font-size:.76rem;font-weight:800;color:#475569;text-transform:uppercase;">Descripcion</th>
<th style="padding:12px 18px;font-size:.76rem;font-weight:800;color:#475569;text-transform:uppercase;">Estado</th>
<th style="padding:12px 18px;font-size:.76rem;font-weight:800;color:#475569;text-transform:uppercase;text-align:right;">Acciones</th>
</tr></thead>
<tbody>
<?php if (!empty($categorias_bd)): ?>
<?php foreach ($categorias_bd as $cat): 
    $catImg = !empty($cat['imagen']) ? $cat['imagen'] : 'Images/icono.png';
    $esActivo = !isset($cat['activo']) || $cat['activo'] == 1;
?>
<tr data-activo="<?= $esActivo ? 1 : 0; ?>" style="border-bottom:1px solid #f1f5f9;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
<td style="padding:14px 18px;font-weight:700;color:#64748b;font-size:.85rem;">#<?= (int)$cat['id_categoria']; ?></td>
<td style="padding:14px 18px;"><img src="<?= htmlspecialchars($catImg); ?>" alt="" class="cat-thumb-img" onerror="this.onerror=null;this.src='Images/icono.png';"></td>
<td style="padding:14px 18px;"><strong style="font-size:.95rem;color:#0f172a;"><?= htmlspecialchars($cat['nombre']); ?></strong></td>
<td style="padding:14px 18px;">
    <div class="cat-hierarchy-list">
    <?php if (!empty($cat['momento_nombre'])): ?>
        <span class="cat-hierarchy-pill cat-macro-pill"><?= htmlspecialchars($cat['momento_nombre']); ?></span>
    <?php endif; ?>
    <?php if (!empty($cat['grupo_nombre'])): ?>
        <span class="cat-hierarchy-pill cat-group-pill"><?= htmlspecialchars($cat['grupo_nombre']); ?></span>
    <?php endif; ?>
    <?php if (empty($cat['momento_nombre']) && empty($cat['grupo_nombre'])): ?>
        <span style="color:#94a3b8;font-size:.82rem;font-style:italic;">Sin asignar</span>
    <?php endif; ?>
    </div>
</td>
<td style="padding:14px 18px;color:#64748b;font-size:.88rem;max-width:240px;"><?= !empty($cat['descripcion']) ? htmlspecialchars($cat['descripcion']) : '<span style="color:#94a3b8;font-style:italic;">Sin descripcion</span>'; ?></td>
<td style="padding:14px 18px;">
    <?php if ($esActivo): ?>
        <span class="badge-status-activo"><svg width="8" height="8" fill="currentColor" viewBox="0 0 8 8"><circle cx="4" cy="4" r="4"/></svg>Activo</span>
    <?php else: ?>
        <span class="badge-status-inactivo"><svg width="8" height="8" fill="currentColor" viewBox="0 0 8 8"><circle cx="4" cy="4" r="4"/></svg>Inactivo</span>
    <?php endif; ?>
</td>
<td style="padding:14px 18px;text-align:right;white-space:nowrap;">
<button type="button" class="cat-action-btn cat-btn-edit" aria-label="Editar subcategoria" title="Editar subcategoria" onclick='window.editarCategoria(<?= json_encode($cat,JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP); ?>)'>
<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg></button>

<?php if ($esActivo): ?>
<button type="button" class="cat-action-btn cat-btn-inactivar" aria-label="Inactivar subcategoria" title="Inactivar subcategoria" onclick='window.confirmarCambioEstadoCategoria("categoria", <?= (int)$cat['id_categoria']; ?>, 0, <?= json_encode($cat['nombre'], JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP); ?>)'>
<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line></svg></button>
<?php else: ?>
<button type="button" class="cat-action-btn cat-btn-activar" aria-label="Activar subcategoria" title="Activar subcategoria" onclick='window.confirmarCambioEstadoCategoria("categoria", <?= (int)$cat['id_categoria']; ?>, 1, <?= json_encode($cat['nombre'], JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP); ?>)'>
<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><path d="m8 12 2.5 2.5L16 9"></path></svg></button>
<?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
<?php else: ?>
<tr><td colspan="7" style="text-align:center;padding:40px;color:#64748b;">No hay subcategorias registradas.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>
<div style="display:flex;align-items:center;justify-content:center;padding:14px 20px;border-top:1px solid #e2e8f0;background:#fff;" id="paginationControlsCat">
    <div style="display:flex;align-items:center;gap:6px;" id="paginationListCat"></div>
</div>
</div>

<!-- MODAL CATEGORIA (NIVEL 2) -->
<div class="cat-modal-overlay" id="modalGrupoAdmin" onclick="if(event.target===this)window.cerrarModalId('modalGrupoAdmin')">
<div class="cat-modal-card">
<div style="padding-bottom:16px;border-bottom:1px solid #f1f5f9;display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
<h3 id="tituloModalGrupo" style="margin:0;font-size:1.25rem;font-weight:800;color:#0f172a;">Nueva Categoria</h3>
<button type="button" onclick="window.cerrarModalId('modalGrupoAdmin')" class="cat-modal-close-btn" aria-label="Cerrar modal" title="Cerrar">
<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
</button>
</div>
<form method="POST" action="index.php?action=agregar_grupo" enctype="multipart/form-data" id="formGrupoAdmin">
<input type="hidden" name="id_grupo" id="grpId">
<input type="hidden" name="redirect_to" value="categorias_admin">
<div style="margin-bottom:16px;">
<label style="display:block;font-weight:700;font-size:.82rem;color:#334155;text-transform:uppercase;margin-bottom:6px;">Nombre de la Categoria (Nivel 2) *</label>
<input type="text" name="nombre" id="grpNombre" required placeholder="Ej. Frutas, Verduras, Carnes y Aves, Mascotas..." style="width:100%;padding:10px 14px;border:1.5px solid #cbd5e1;border-radius:8px;font-size:.95rem;box-sizing:border-box;outline:none;">
</div>
<div style="margin-bottom:16px;">
<label style="display:block;font-weight:700;font-size:.82rem;color:#334155;text-transform:uppercase;margin-bottom:6px;">Macrocategoria (Nivel 1) *</label>
<select name="id_momento" id="grpMomentoSelect" required style="width:100%;padding:10px 14px;border:1.5px solid #cbd5e1;border-radius:8px;font-size:.9rem;box-sizing:border-box;background:#fff;outline:none;">
<option value="">-- Seleccionar Macrocategoria --</option>
<?php foreach ($moments_bd as $m): ?>
<option value="<?= (int)$m['id_momento']; ?>"><?= htmlspecialchars($m['nombre'] ?? ($m['titulo'] ?? 'Macrocategoria')); ?></option>
<?php endforeach; ?>
</select>
</div>
<div style="margin-bottom:16px;">
<label style="display:block;font-weight:700;font-size:.82rem;color:#334155;text-transform:uppercase;margin-bottom:6px;">Descripcion (Opcional)</label>
<textarea name="descripcion" id="grpDescripcion" rows="2" placeholder="Breve detalle..." style="width:100%;padding:10px 14px;border:1.5px solid #cbd5e1;border-radius:8px;font-size:.95rem;box-sizing:border-box;resize:vertical;outline:none;"></textarea>
</div>
<div style="margin-bottom:20px;">
<label style="display:block;font-weight:700;font-size:.82rem;color:#334155;text-transform:uppercase;margin-bottom:8px;">Imagen de Categoria (Opcional)</label>
<div style="display:flex;gap:14px;align-items:center;background:#f8fafc;padding:12px;border:1.5px solid #e2e8f0;border-radius:12px;">
    <div style="width:52px;height:52px;border-radius:10px;border:1.5px dashed #cbd5e1;background:#fff;display:flex;align-items:center;justify-content:center;overflow:hidden;flex-shrink:0;">
        <img id="grpImgPreview" src="Images/icono.png" style="width:100%;height:100%;object-fit:cover;" onerror="this.onerror=null;this.src='Images/icono.png';">
    </div>
    <div style="flex:1;">
        <label for="grpFileInput" style="display:inline-flex;align-items:center;gap:6px;padding:7px 13px;background:#fff;border:1px solid #cbd5e1;border-radius:8px;font-size:.82rem;font-weight:700;color:#334155;cursor:pointer;transition:all .2s ease;box-shadow:0 1px 3px rgba(0,0,0,.04);" onmouseover="this.style.background='#f1f5f9';this.style.borderColor='#94a3b8';" onmouseout="this.style.background='#fff';this.style.borderColor='#cbd5e1';">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
            <span id="grpFileName">Seleccionar archivo PC...</span>
        </label>
        <input type="file" name="imagen" id="grpFileInput" accept="image/*" style="display:none;" onchange="window.previewGrupoFileImage(this)">
        <div style="margin-top:8px;">
            <input type="text" name="imagen_url" id="grpImagenUrl" placeholder="O pega enlace / ruta: Images/..." style="width:100%;padding:7px 12px;border:1.5px solid #cbd5e1;border-radius:8px;font-size:.84rem;box-sizing:border-box;outline:none;background:#fff;" oninput="document.getElementById('grpImgPreview').src = this.value || 'Images/icono.png'">
        </div>
    </div>
</div>
</div>
<div style="display:flex;justify-content:flex-end;gap:10px;">
<button type="button" onclick="window.cerrarModalId('modalGrupoAdmin')" style="padding:10px 18px;border-radius:8px;border:1px solid #cbd5e1;background:#f8fafc;font-weight:700;cursor:pointer;color:#475569;">Cancelar</button>
<button type="submit" id="btnGuardarGrp" style="padding:10px 22px;border-radius:8px;border:none;background:#15803d;color:#fff;font-weight:700;cursor:pointer;">Guardar Categoria</button>
</div>
</form>
</div>
</div>

<!-- MODAL SUBCATEGORIA (NIVEL 3) -->
<div class="cat-modal-overlay" id="modalCategoriaAdmin" onclick="if(event.target===this)window.cerrarModalId('modalCategoriaAdmin')">
<div class="cat-modal-card">
<div style="padding-bottom:16px;border-bottom:1px solid #f1f5f9;display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
<h3 id="tituloModalCat" style="margin:0;font-size:1.25rem;font-weight:800;color:#0f172a;">Nueva Subcategoria</h3>
<button type="button" onclick="window.cerrarModalId('modalCategoriaAdmin')" class="cat-modal-close-btn" aria-label="Cerrar modal" title="Cerrar">
<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
<line x1="18" y1="6" x2="6" y2="18"></line>
<line x1="6" y1="6" x2="18" y2="18"></line>
</svg>
</button>
</div>
<form method="POST" action="index.php?action=crear_categoria" enctype="multipart/form-data" id="formCategoriaAdmin">
<input type="hidden" name="id_categoria" id="catId">
<input type="hidden" name="redirect_to" value="categorias_admin">
<div style="margin-bottom:16px;">
<label style="display:block;font-weight:700;font-size:.82rem;color:#334155;text-transform:uppercase;margin-bottom:6px;">Nombre de la Subcategoria *</label>
<input type="text" name="nombre" id="catNombre" required placeholder="Ej. Pollo, Res, Cerdo, Alimento para Perros..." style="width:100%;padding:10px 14px;border:1.5px solid #cbd5e1;border-radius:8px;font-size:.95rem;box-sizing:border-box;outline:none;">
</div>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px;">
<div>
<label style="display:block;font-weight:700;font-size:.82rem;color:#334155;text-transform:uppercase;margin-bottom:6px;">Macrocategoria *</label>
<select name="id_momento" id="catMomentoSelect" required onchange="window.filtrarSubcats()" style="width:100%;padding:10px 14px;border:1.5px solid #cbd5e1;border-radius:8px;font-size:.9rem;box-sizing:border-box;background:#fff;outline:none;">
<option value="">-- Seleccionar --</option>
<?php foreach ($moments_bd as $m): ?>
<option value="<?= (int)$m['id_momento']; ?>"><?= htmlspecialchars($m['nombre'] ?? ($m['titulo'] ?? 'Macrocategoria')); ?></option>
<?php endforeach; ?>
</select>
</div>
<div>
<label style="display:block;font-weight:700;font-size:.82rem;color:#334155;text-transform:uppercase;margin-bottom:6px;">Categoria</label>
<select name="id_grupo" id="catGrupoSelect" disabled style="width:100%;padding:10px 14px;border:1.5px solid #cbd5e1;border-radius:8px;font-size:.9rem;box-sizing:border-box;background:#f1f5f9;outline:none;cursor:not-allowed;color:#94a3b8;">
<option value="">-- Seleccionar --</option>
<?php foreach ($grupMomento_bd as $g): ?>
<option value="<?= (int)$g['id_grupo']; ?>" data-momento="<?= (int)$g['id_momento']; ?>"><?= htmlspecialchars($g['nombre']); ?></option>
<?php endforeach; ?>
</select>
</div>
</div>
<div style="margin-bottom:16px;">
<label style="display:block;font-weight:700;font-size:.82rem;color:#334155;text-transform:uppercase;margin-bottom:6px;">Descripcion (Opcional)</label>
<textarea name="descripcion" id="catDescripcion" rows="2" placeholder="Breve detalle..." style="width:100%;padding:10px 14px;border:1.5px solid #cbd5e1;border-radius:8px;font-size:.95rem;box-sizing:border-box;resize:vertical;outline:none;"></textarea>
</div>
<div style="margin-bottom:20px;">
<label style="display:block;font-weight:700;font-size:.82rem;color:#334155;text-transform:uppercase;margin-bottom:8px;">Imagen de Subcategoria (Opcional)</label>
<div style="display:flex;gap:14px;align-items:center;background:#f8fafc;padding:12px;border:1.5px solid #e2e8f0;border-radius:12px;">
    <div style="width:52px;height:52px;border-radius:10px;border:1.5px dashed #cbd5e1;background:#fff;display:flex;align-items:center;justify-content:center;overflow:hidden;flex-shrink:0;">
        <img id="catImgPreview" src="Images/icono.png" style="width:100%;height:100%;object-fit:cover;" onerror="this.onerror=null;this.src='Images/icono.png';">
    </div>
    <div style="flex:1;">
        <label for="catFileInput" style="display:inline-flex;align-items:center;gap:6px;padding:7px 13px;background:#fff;border:1px solid #cbd5e1;border-radius:8px;font-size:.82rem;font-weight:700;color:#334155;cursor:pointer;transition:all .2s ease;box-shadow:0 1px 3px rgba(0,0,0,.04);" onmouseover="this.style.background='#f1f5f9';this.style.borderColor='#94a3b8';" onmouseout="this.style.background='#fff';this.style.borderColor='#cbd5e1';">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
            <span id="catFileName">Seleccionar archivo PC...</span>
        </label>
        <input type="file" name="imagen" id="catFileInput" accept="image/*" style="display:none;" onchange="window.previewCatFileImage(this)">
        <div style="margin-top:8px;">
            <input type="text" name="imagen_url" id="catImagenUrl" placeholder="O pega enlace / ruta: Images/..." style="width:100%;padding:7px 12px;border:1.5px solid #cbd5e1;border-radius:8px;font-size:.84rem;box-sizing:border-box;outline:none;background:#fff;" oninput="document.getElementById('catImgPreview').src = this.value || 'Images/icono.png'">
        </div>
    </div>
</div>
</div>
<div style="display:flex;justify-content:flex-end;gap:10px;">
<button type="button" onclick="window.cerrarModalId('modalCategoriaAdmin')" style="padding:10px 18px;border-radius:8px;border:1px solid #cbd5e1;background:#f8fafc;font-weight:700;cursor:pointer;color:#475569;">Cancelar</button>
<button type="submit" id="btnGuardarCat" style="padding:10px 22px;border-radius:8px;border:none;background:#0284c7;color:#fff;font-weight:700;cursor:pointer;">Guardar Subcategoria</button>
</div>
</form>
</div>
</div>

<div class="cat-modal-overlay" id="modalConfirmarEstadoCategoria" onclick="if(event.target===this)window.cerrarModalId('modalConfirmarEstadoCategoria')">
    <div class="cat-modal-card" style="max-width:420px;text-align:center;">
        <div id="iconoEstadoCategoria" style="width:56px;height:56px;margin:0 auto 16px;border-radius:50%;display:flex;align-items:center;justify-content:center;"></div>
        <h3 id="tituloEstadoCategoria" style="margin:0 0 8px;font-size:1.2rem;font-weight:800;color:#0f172a;">Confirmar cambio</h3>
        <p id="textoEstadoCategoria" style="margin:0 0 22px;color:#64748b;font-size:.92rem;"></p>
        <div style="display:flex;justify-content:center;gap:10px;">
            <button type="button" onclick="window.cerrarModalId('modalConfirmarEstadoCategoria')" style="padding:10px 18px;border:1px solid #cbd5e1;border-radius:8px;background:#f8fafc;color:#475569;font-weight:700;cursor:pointer;">Cancelar</button>
            <a id="btnConfirmarEstadoCategoria" href="#" style="padding:10px 20px;border-radius:8px;color:#fff;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;">Confirmar</a>
        </div>
    </div>
</div>

<script src="Scripts/Admin/categorias_admin.js?v=<?= time(); ?>"></script>
<?php if (!AjaxHelper::isAjax()): ?>
            </div>
        </div>
    </div>
</body>
</html>
<?php endif; ?>