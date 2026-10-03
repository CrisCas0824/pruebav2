<?php
require_once 'Core/ajaxHelper.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$nombre = htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Administrador');
$flash_success = $_SESSION['flash_success'] ?? null;
$flash_error = $_SESSION['flash_error'] ?? null;
if (isset($_SESSION)) {
    unset($_SESSION['flash_success'], $_SESSION['flash_error']);
}

if (!isset($slidesCarrusel) || empty($slidesCarrusel) || !is_array($slidesCarrusel)) {
    $carruselConfigFile = __DIR__ . '/../../Config/carrusel.json';
    $slidesCarrusel = file_exists($carruselConfigFile) ? json_decode(file_get_contents($carruselConfigFile), true) : [];
    if (empty($slidesCarrusel) || !is_array($slidesCarrusel)) {
        $slidesCarrusel = [
            [
                "tag" => "OFERTA DE LA SEMANA",
                "titulo" => "Abarrotes y Productos Frescos al Mejor Precio",
                "descripcion" => "Aprovecha descuentos de hasta 25% en tu primera compra en línea.",
                "boton" => "Ver Ofertas",
                "link" => "#catalogo",
                "imagen" => "Images/abarrotes-banner.png"
            ]
        ];
    }
}

// Cargar categorías y macrocategorías si no vienen del controlador
if (!isset($macrocategorias) || !isset($gruposCategorias)) {
    require_once __DIR__ . '/../../Models/momentConModel.php';
    require_once __DIR__ . '/../../Models/grupoMomentModel.php';
    require_once __DIR__ . '/../../Models/categoryModel.php';
    $momentModel = new MomentConModel();
    $grupoModel  = new GrupMomentModel();
    $catModel    = new CategoryModel();
    $macrocategorias = $momentModel->getMomento();
    $gruposCategorias = $grupoModel->getGrupo();
    $subcategorias    = $catModel->getCategoria();
}
$macrocategorias = $macrocategorias ?? [];
$gruposCategorias = $gruposCategorias ?? [];
$subcategorias = $subcategorias ?? [];

// Construcción del diccionario estructurado de destinos para el buscador
$destinosList = [
    [
        'id' => 'sec_cat',
        'categoria' => 'Secciones Generales',
        'nombre' => 'Catálogo Completo',
        'subtitulo' => 'Muestra todos los productos disponibles',
        'url' => '#catalogo'
    ],
    [
        'id' => 'sec_ofe',
        'categoria' => 'Secciones Generales',
        'nombre' => 'Ofertas Especiales',
        'subtitulo' => 'Productos con descuento activo',
        'url' => 'index.php?action=home_usuario&ofertas=1'
    ],
    [
        'id' => 'sec_sug',
        'categoria' => 'Secciones Generales',
        'nombre' => 'Productos Sugeridos',
        'subtitulo' => 'Destacados de la semana en portada',
        'url' => '#sugerenciasGrid'
    ]
];

foreach ($macrocategorias as $mc) {
    $destinosList[] = [
        'id' => 'mc_' . $mc['id_momento'],
        'categoria' => 'Macrocategorías (Nivel 1)',
        'nombre' => $mc['nombre'],
        'subtitulo' => 'Macrocategoría principal de catálogo',
        'url' => 'index.php?action=ver_categoria&id_momento=' . $mc['id_momento']
    ];
}

foreach ($gruposCategorias as $gc) {
    $destinosList[] = [
        'id' => 'gc_' . $gc['id_grupo'],
        'categoria' => 'Categorías (Nivel 2)',
        'nombre' => $gc['nombre'],
        'subtitulo' => 'Perteneciente a ' . ($gc['momento_nombre'] ?? 'N1'),
        'url' => 'index.php?action=ver_categoria&id_grupo=' . $gc['id_grupo']
    ];
}

foreach ($subcategorias as $sc) {
    $destinosList[] = [
        'id' => 'sc_' . $sc['id_categoria'],
        'categoria' => 'Subcategorías (Nivel 3)',
        'nombre' => $sc['nombre'],
        'subtitulo' => 'Grupo ' . ($sc['grupo_nombre'] ?? ''),
        'url' => 'index.php?action=ver_categoria&id=' . $sc['id_categoria']
    ];
}

// Función auxiliar para obtener el nombre legible según la URL guardada
function resolverNombreDestino($url, $destinosList) {
    if (empty($url)) return 'Catálogo Completo';
    foreach ($destinosList as $d) {
        if ($d['url'] === $url) {
            return $d['icono'] . ' ' . $d['nombre'];
        }
    }
    if (strpos($url, 'id_momento') !== false) return 'Macrocategoría seleccionada';
    if (strpos($url, 'id_grupo') !== false) return 'Categoría seleccionada';
    if (strpos($url, 'ofertas') !== false) return 'Ofertas Especiales';
    return $url;
}
?>
<?php if (!AjaxHelper::isAjax()): ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Banners & Carrusel — ERP La Caserita</title>
    <link rel="icon" type="image/png" href="Images/logo.png">
    <link rel="stylesheet" href="Styles/global.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="Styles/styles-home.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="Styles/styles-users-admin.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="Styles/admin-spa.css?v=<?= time(); ?>">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="Scripts/admin-spa.js?v=<?= time(); ?>" defer></script>
<link rel="stylesheet" href="Styles/Vistas/Admin/carrusel_admin.css?v=<?= filemtime(__DIR__ . '/../../Styles/Vistas/Admin/carrusel_admin.css'); ?>">
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
                        <span class="brand-title">Gestión de Banners & Carrusel</span>
                    </div>
                </div>
                <div class="admin-topbar-actions">
                    <?php include __DIR__ . '/partials/admin_topbar_actions.php'; ?>
                </div>
            </header>

            <div id="admin-container">
<?php endif; ?>

<!-- NOTIFICACIÓN TOAST SUPERIOR (IMAGEN 4) -->
<div id="carruselTopToast" class="top-toast <?= ($flash_success || $flash_error) ? 'show' : ''; ?>">
    <div class="top-toast-icon" style="background: <?= $flash_error ? '#dc2626' : '#15803d'; ?>;">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
            <?= $flash_error ? '<line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line>' : '<polyline points="20 6 9 17 4 12"></polyline>'; ?>
        </svg>
    </div>
    <div class="top-toast-body">
        <strong><?= $flash_error ? 'Atención' : 'Éxito' ?></strong>
        <span><?= htmlspecialchars($flash_error ?: ($flash_success ?: 'Cambios guardados correctamente.')); ?></span>
    </div>
    <button type="button" class="top-toast-close" id="carruselToastClose" aria-label="Cerrar">&times;</button>
    <div class="top-toast-progress" style="background: <?= $flash_error ? '#dc2626' : '#15803d'; ?>;"></div>
</div>

<!-- Modal Confirmación Reutilizable (Imagen 3 / Ventas) -->
<div class="carrusel-modal-overlay" id="carruselConfirmModal" style="display: none; z-index: 999999; backdrop-filter: blur(3px);">
    <div style="background: #ffffff; border-radius: 18px; width: min(440px, 94vw); padding: 28px 24px; text-align: center; box-shadow: 0 24px 60px rgba(0,0,0,0.25); margin: auto;">
        <div id="confirmIconBox" style="width: 56px; height: 56px; border-radius: 50%; background: #dcfce7; color: #15803d; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 16px;">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
        </div>
        <h3 id="confirmModalTitulo" style="margin: 0 0 8px; font-size: 1.2rem; font-weight: 800; color: #0f172a;">¿Confirmar acción?</h3>
        <p id="confirmModalMsg" style="margin: 0 0 22px; font-size: 0.90rem; color: #64748b; line-height: 1.5;">
            Los cambios se guardarán y reflejarán en la portada de la tienda.
        </p>
        <div style="display: flex; gap: 12px; justify-content: center;">
            <button type="button" onclick="window.cerrarConfirmacionModal()" style="padding: 10px 20px; border-radius: 8px; border: 1px solid #cbd5e1; background: #ffffff; color: #475569; font-weight: 700; font-size: 0.88rem; cursor: pointer;">
                Cancelar
            </button>
            <button type="button" id="btnConfirmarProceder" style="padding: 10px 22px; border-radius: 8px; border: none; background: #15803d; color: #ffffff; font-weight: 700; font-size: 0.88rem; cursor: pointer; box-shadow: 0 2px 8px rgba(21,128,61,0.25);">
                Sí, Confirmar
            </button>
        </div>
    </div>
</div>

<!-- Modal Edición de Banner Individual con Vista Previa -->
<div class="carrusel-modal-overlay" id="modalEditarBanner" style="display: none; z-index: 99999; backdrop-filter: blur(3px);">
    <div style="background: #ffffff; border-radius: 18px; width: min(620px, 95vw); max-height: 92vh; overflow-y: auto; box-shadow: 0 24px 60px rgba(0,0,0,0.22); margin: auto; display: flex; flex-direction: column;">
        <div style="padding: 18px 24px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between;">
            <h3 id="editModalTitulo" style="margin: 0; font-size: 1.18rem; font-weight: 800; color: #0f172a;">Editar Banner #1</h3>
            <button type="button" class="btn-close-universal" onclick="window.cerrarModalEditarBanner()" style="background: none; border: none; font-size: 1.3rem; color: #94a3b8; cursor: pointer; line-height: 1;" aria-label="Cerrar modal"><span class="modal-close-glyph" aria-hidden="true">&times;</span></button>
        </div>
        
        <div style="padding: 22px 24px; display: flex; flex-direction: column; gap: 18px;">
            <!-- Opción de Vista Previa en Vivo -->
            <div>
                <label style="display: block; font-size: 0.74rem; font-weight: 800; color: #475569; text-transform: uppercase; margin-bottom: 6px; letter-spacing: 0.04em;">Vista Previa en Tienda</label>
                <div style="position: relative; width: 100%; height: 165px; border-radius: 12px; overflow: hidden; background: #0f172a; border: 1.5px solid #cbd5e1; box-shadow: inset 0 2px 6px rgba(0,0,0,0.15);">
                    <img id="modalPreviewImg" src="Images/123.webp" alt="Vista Previa" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.onerror=null;this.src='Images/123.webp';">
                    <span style="position: absolute; bottom: 12px; left: 16px; background: #15803d; color: #ffffff; font-size: 0.78rem; font-weight: 800; padding: 5px 14px; border-radius: 9999px; box-shadow: 0 4px 10px rgba(0,0,0,0.35); border: 1px solid rgba(255,255,255,0.4);">
                        Ver más ›
                    </span>
                </div>
            </div>

            <!-- Subir / Cambiar Imagen -->
            <div>
                <label style="display: block; font-size: 0.74rem; font-weight: 800; color: #475569; text-transform: uppercase; margin-bottom: 6px; letter-spacing: 0.04em;">Imagen del Banner</label>
                <div style="display: flex; align-items: center; gap: 12px; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 10px; padding: 10px 14px;">
                    <img id="modalMiniThumb" src="Images/123.webp" alt="Miniatura" style="width: 50px; height: 50px; border-radius: 8px; object-fit: cover; background: #ffffff; border: 1px solid #e2e8f0; padding: 2px;">
                    <div style="display: flex; flex-direction: column; gap: 4px; flex-grow: 1;">
                        <label for="modalFileInput" style="background: #ffffff; border: 1px solid #cbd5e1; padding: 6px 12px; border-radius: 6px; font-size: 0.78rem; font-weight: 700; color: #15803d; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; width: fit-content;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="2" x2="12" y2="15"></line></svg>
                            <span>Subir Archivo de Imagen</span>
                        </label>
                        <input type="file" id="modalFileInput" accept="image/*" style="display: none;" onchange="window.modalCargarArchivo(this)">
                        <span id="modalFileStatus" style="font-size: 0.74rem; color: #64748b;">Selecciona una imagen desde tu equipo</span>
                    </div>
                </div>
            </div>

            <!-- Destino con Barra de Búsqueda -->
            <div>
                <label style="display: block; font-size: 0.74rem; font-weight: 800; color: #475569; text-transform: uppercase; margin-bottom: 6px; letter-spacing: 0.04em;">Destino al hacer clic en "Ver más"</label>
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; background: #f8fafc; border: 1.5px solid #cbd5e1; border-radius: 8px; margin-bottom: 8px;">
                    <div id="modalDestBadge" style="font-size: 0.88rem; font-weight: 700; color: #0f172a; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                        🛒 Catálogo Completo
                    </div>
                </div>

                <!-- Buscador en tiempo real de categorías y macrocategorías -->
                <div style="border: 1.5px solid #cbd5e1; border-radius: 10px; overflow: hidden; background: #ffffff;">
                    <div style="padding: 8px 12px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; gap: 8px;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2.2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        <input type="text" id="modalDestSearch" placeholder="Buscar categoría o macrocategoría..." style="width: 100%; border: none; background: transparent; font-size: 0.84rem; outline: none;" oninput="window.modalFiltrarDestinos(this.value)">
                    </div>
                    <div id="modalDestResults" style="max-height: 160px; overflow-y: auto; padding: 4px 0;">
                        <!-- Renderizado por JS -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer del Modal: Guardar, Cancelar o Eliminar -->
        <div style="padding: 16px 24px; border-top: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; gap: 10px; background: #f8fafc; border-radius: 0 0 18px 18px;">
            <button type="button" onclick="window.modalPedirEliminar()" style="padding: 9px 16px; border-radius: 8px; border: 1px solid #fecaca; background: #fee2e2; color: #dc2626; font-weight: 700; font-size: 0.85rem; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                <span>Eliminar</span>
            </button>
            <div style="display: flex; gap: 10px;">
                <button type="button" onclick="window.modalPedirGuardar()" style="padding: 9px 22px; border-radius: 8px; border: none; background: #15803d; color: #ffffff; font-weight: 700; font-size: 0.85rem; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 2px 8px rgba(21,128,61,0.25);">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                    <span>Guardar</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- CONTENEDOR PRINCIPAL CON ESPACIADO IDÉNTICO A IMAGEN 2 (.container-admin) -->
<main class="main-content container-admin" style="padding-top: 25px; padding-bottom: 50px;">
    

    <!-- HEADER DE SECCIÓN (ESPACIO ARRIBA Y A LOS COSTADOS IDÉNTICO A IMAGEN 2) -->
    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px; margin-bottom: 22px;">
        <div>
            <h1 style="font-size: 1.6rem; font-weight: 800; color: #0f172a; margin: 0 0 4px 0; letter-spacing: -0.02em;">Banners Principales del Carrusel</h1>
            <p style="color: #64748b; font-size: 0.90rem; margin: 0;">
                Configura los banners interactivos de la tienda. Puedes seleccionar directamente la macrocategoría o categoría sin buscar enlaces manuales.
            </p>
        </div>
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <button type="button" onclick="window.agregarNuevoBanner()" style="background: #15803d; border: none; padding: 10px 18px; border-radius: 10px; font-weight: 700; font-size: 0.88rem; color: #ffffff; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 4px 12px rgba(21,128,61,0.25);">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                <span>Agregar Banner</span>
            </button>
            <a href="index.php" target="_blank" style="background: #ffffff; border: 1px solid #cbd5e1; padding: 10px 16px; border-radius: 10px; font-weight: 700; font-size: 0.88rem; color: #334155; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                <span>Ver en Tienda</span>
            </a>
        </div>
    </div>

    <!-- TARJETAS CUADRADAS COMPACTAS (IMAGEN 2) -->
    <div class="stats-grid-square">
        <div class="stat-card-square active-card">
            <span class="stat-title">TOTAL BANNERS</span>
            <span class="stat-val" id="lblTotalBanners"><?= count($slidesCarrusel); ?></span>
        </div>
        <div class="stat-card-square">
            <span class="stat-title">MACROCATEGORÍAS DISPONIBLES</span>
            <span class="stat-val"><?= count($macrocategorias); ?></span>
        </div>
        <div class="stat-card-square">
            <span class="stat-title">CATEGORÍAS DISPONIBLES</span>
            <span class="stat-val"><?= count($gruposCategorias); ?></span>
        </div>
        <div class="stat-card-square">
            <span class="stat-title">ESTADO EN TIENDA</span>
            <span class="stat-val" style="font-size: 1.05rem; color: #15803d;">Activo</span>
        </div>
    </div>

    <!-- FORMULARIO OCULTO PARA PERSISTENCIA DE BANNERS -->
    <form id="formPrincipalCarrusel" action="index.php?action=guardar_carrusel" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="guardar_form_carrusel" value="1">
        <div id="hiddenInputsContainer"></div>

        <!-- GRID DE 3 TARJETAS POR FILA -->
        <div class="carrusel-grid-3" id="carruselCardsGrid">
            <!-- Renderizado por JS desde el arreglo de banners -->
        </div>
    </form>
</main>

<script>
    // DICCIONARIO DE DESTINOS
    const DESTINOS = <?= json_encode($destinosList); ?>;
    
    // LISTA DE BANNERS EN MEMORIA
    let listaBanners = <?= json_encode($slidesCarrusel); ?>;
    let indexEditandoActualmente = -1;
    let archivoPendienteSubida = null;
    let accionConfirmacionPendiente = null;

    // Toast interactivo (Imagen 4)
    window.mostrarCarruselToast = function(titulo, mensaje, esError = false) {
        const toast = document.getElementById('carruselTopToast');
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
        if (window._carruselToastTimer) clearTimeout(window._carruselToastTimer);
        window._carruselToastTimer = setTimeout(() => toast.classList.remove('show'), 3800);
    };

    (function initToast() {
        const toast = document.getElementById('carruselTopToast');
        const closeBtn = document.getElementById('carruselToastClose');
        if (closeBtn && toast) {
            closeBtn.addEventListener('click', () => toast.classList.remove('show'));
        }
        if (toast && toast.classList.contains('show')) {
            window._carruselToastTimer = setTimeout(() => toast.classList.remove('show'), 3800);
        }
    })();

    // Helper para resolver el nombre amigable de un destino
    function resolverNombreDestino(url) {
        if (!url || url === '#' || url === '#catalogo') return 'Catálogo Completo';
        const found = DESTINOS.find(d => d.url === url);
        return found ? (found.nombre) : 'Enlace Personalizado';
    }

    // RENDERIZADO DE LAS TARJETAS (3 POR FILA, IMÁGENES REDUCIDAS, SOLO BOTÓN EDITAR)
    function renderizarGridBanners() {
        const grid = document.getElementById('carruselCardsGrid');
        const lblTotal = document.getElementById('lblTotalBanners');
        if (lblTotal) lblTotal.textContent = listaBanners.length;
        if (!grid) return;

        if (listaBanners.length === 0) {
            grid.innerHTML = '<div style="grid-column: 1 / -1; padding: 40px; text-align: center; color: #94a3b8; background: #ffffff; border-radius: 14px; border: 1px solid #e2e8f0;">No hay banners configurados actualmente.</div>';
            return;
        }

        grid.innerHTML = listaBanners.map((b, i) => {
            const imgSrc = b.imagen || 'Images/123.webp';
            const linkSrc = b.link || b.enlace || '#catalogo';
            const destText = resolverNombreDestino(linkSrc);

            return `
                <div class="banner-item-card" id="bannerCard_${i}">
                    <!-- Imagen reducida al tamaño de la tarjeta -->
                    <div style="position: relative; width: 100%; height: 140px; background: #0f172a; overflow: hidden;">
                        <img src="${imgSrc}" alt="Banner #${i + 1}" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.onerror=null;this.src='Images/123.webp';">
                        <span style="position: absolute; top: 10px; left: 10px; background: rgba(15,23,42,0.75); color: #ffffff; font-size: 0.72rem; font-weight: 800; padding: 3px 8px; border-radius: 6px; backdrop-filter: blur(4px);">
                            Banner #${i + 1}
                        </span>
                    </div>

                    <!-- Información del banner -->
                    <div style="padding: 14px 16px; display: flex; flex-direction: column; gap: 12px; flex-grow: 1; justify-content: space-between;">
                        <div>
                            <div style="font-size: 0.70rem; font-weight: 800; color: #64748b; text-transform: uppercase; margin-bottom: 2px;">DESTINO SELECCIONADO</div>
                            <div style="font-size: 0.88rem; font-weight: 700; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="${destText}">
                                ${destText}
                            </div>
                        </div>

                        <!-- CADA TARJETA SOLO TIENE EL BOTÓN DE EDITAR -->
                        <button type="button" class="btn-card-edit-only" onclick="window.abrirModalEditarBanner(${i})">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                            <span>Editar</span>
                        </button>
                    </div>
                </div>
            `;
        }).join('');
    }

    // ABRIR MODAL DE EDICIÓN DE BANNER
    window.abrirModalEditarBanner = function(idx) {
        indexEditandoActualmente = idx;
        archivoPendienteSubida = null;

        const b = listaBanners[idx];
        if (!b) return;

        document.getElementById('editModalTitulo').textContent = 'Editar Banner #' + (idx + 1);
        const previewImg = document.getElementById('modalPreviewImg');
        const miniThumb = document.getElementById('modalMiniThumb');
        const fileStatus = document.getElementById('modalFileStatus');
        const destBadge = document.getElementById('modalDestBadge');
        const searchInput = document.getElementById('modalDestSearch');

        const imgSrc = b.imagen || 'Images/123.webp';
        if (previewImg) previewImg.src = imgSrc;
        if (miniThumb) miniThumb.src = imgSrc;
        if (fileStatus) {
            fileStatus.textContent = 'Imagen actual asignada';
            fileStatus.style.color = '#64748b';
        }
        if (destBadge) destBadge.textContent = resolverNombreDestino(b.link || b.enlace || '#catalogo');
        if (searchInput) searchInput.value = '';

        window.modalFiltrarDestinos('');
        document.getElementById('modalEditarBanner').style.display = 'flex';
    };

    window.cerrarModalEditarBanner = function() {
        document.getElementById('modalEditarBanner').style.display = 'none';
        indexEditandoActualmente = -1;
        archivoPendienteSubida = null;
    };

    // CARGAR ARCHIVO EN EL MODAL DE EDICIÓN
    window.modalCargarArchivo = function(input) {
        if (input.files && input.files[0]) {
            const file = input.files[0];
            archivoPendienteSubida = file;
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('modalPreviewImg').src = e.target.result;
                document.getElementById('modalMiniThumb').src = e.target.result;
                const status = document.getElementById('modalFileStatus');
                if (status) {
                    status.textContent = 'Archivo: ' + file.name;
                    status.style.color = '#15803d';
                    status.style.fontWeight = '700';
                }
            };
            reader.readAsDataURL(file);
        }
    };

    // FILTRAR DESTINOS EN TIEMPO REAL DENTRO DEL MODAL
    window.modalFiltrarDestinos = function(query) {
        const resultsBox = document.getElementById('modalDestResults');
        if (!resultsBox) return;

        const q = (query || '').toLowerCase().trim();
        const filtrados = DESTINOS.filter(d => {
            if (!q) return true;
            return d.nombre.toLowerCase().includes(q) || d.categoria.toLowerCase().includes(q) || d.subtitulo.toLowerCase().includes(q);
        });

        if (filtrados.length === 0) {
            resultsBox.innerHTML = '<div style="padding:12px; text-align:center; color:#94a3b8; font-size:0.80rem;">No se encontraron categorías.</div>';
            return;
        }

        resultsBox.innerHTML = filtrados.map(d => `
            <div class="dest-opt-item" onclick="window.modalSeleccionarDestino('${d.url}', '${d.nombre.replace(/'/g, "\\'")}')">
                <div>
                    <div style="font-size:0.84rem; font-weight:700; color:#0f172a;">${d.nombre}</div>
                    <div style="font-size:0.72rem; color:#64748b;">${d.subtitulo}</div>
                </div>
                <span style="font-size:0.68rem; font-weight:800; background:#f1f5f9; color:#475569; padding:2px 6px; border-radius:4px; white-space:nowrap;">
                    ${d.categoria}
                </span>
            </div>
        `).join('');
    };

    window.modalSeleccionarDestino = function(url, nombreVisible) {
        if (indexEditandoActualmente < 0) return;
        listaBanners[indexEditandoActualmente].link = url;
        listaBanners[indexEditandoActualmente].enlace = url;
        const badge = document.getElementById('modalDestBadge');
        if (badge) {
            badge.textContent = nombreVisible;
            badge.style.color = '#15803d';
            setTimeout(() => { badge.style.color = '#0f172a'; }, 1000);
        }
    };

    // MODAL DE CONFIRMACIÓN REUTILIZABLE
    window.abrirConfirmacionModal = function(titulo, mensaje, accionProceder, esPeligro = false) {
        const modal = document.getElementById('carruselConfirmModal');
        const titEl = document.getElementById('confirmModalTitulo');
        const msgEl = document.getElementById('confirmModalMsg');
        const iconBox = document.getElementById('confirmIconBox');
        const btnProc = document.getElementById('btnConfirmarProceder');

        if (titEl) titEl.textContent = titulo;
        if (msgEl) msgEl.textContent = mensaje;

        if (esPeligro) {
            if (iconBox) {
                iconBox.style.background = '#fee2e2';
                iconBox.style.color = '#dc2626';
                iconBox.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>';
            }
            if (btnProc) {
                btnProc.style.background = '#dc2626';
                btnProc.textContent = 'Sí, Eliminar';
            }
        } else {
            if (iconBox) {
                iconBox.style.background = '#dcfce7';
                iconBox.style.color = '#15803d';
                iconBox.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>';
            }
            if (btnProc) {
                btnProc.style.background = '#15803d';
                btnProc.textContent = 'Sí, Guardar';
            }
        }

        accionConfirmacionPendiente = accionProceder;
        if (btnProc) {
            btnProc.onclick = function() {
                window.cerrarConfirmacionModal();
                if (typeof accionConfirmacionPendiente === 'function') {
                    accionConfirmacionPendiente();
                }
            };
        }

        if (modal) modal.style.display = 'flex';
    };

    window.cerrarConfirmacionModal = function() {
        const modal = document.getElementById('carruselConfirmModal');
        if (modal) modal.style.display = 'none';
        accionConfirmacionPendiente = null;
    };

    // GUARDAR DESDE EL MODAL (PASA POR MODAL DE CONFIRMACIÓN)
    window.modalPedirGuardar = function() {
        if (indexEditandoActualmente < 0) return;

        window.abrirConfirmacionModal(
            '¿Guardar cambios del banner?',
            'Los ajustes de imagen y destino se aplicarán en el carrusel de la tienda.',
            function() {
                window.ejecutarPersistenciaBanners(indexEditandoActualmente, archivoPendienteSubida);
            },
            false
        );
    };

    // ELIMINAR DESDE EL MODAL (PASA POR MODAL DE CONFIRMACIÓN)
    window.modalPedirEliminar = function() {
        if (indexEditandoActualmente < 0) return;
        if (listaBanners.length <= 1) {
            alert('Debe haber al menos un banner configurado para la tienda.');
            return;
        }

        const idx = indexEditandoActualmente;
        window.abrirConfirmacionModal(
            '¿Eliminar este banner?',
            'El banner #' + (idx + 1) + ' será retirado definitivamente de la tienda.',
            function() {
                listaBanners.splice(idx, 1);
                window.cerrarModalEditarBanner();
                window.ejecutarPersistenciaBanners(-1, null, true);
            },
            true
        );
    };

    // PERSISTENCIA MEDIANTE FORMULARIO / AJAX
    window.ejecutarPersistenciaBanners = function(idxConArchivo, archivo = null, fueEliminado = false) {
        const formData = new FormData();
        formData.append('guardar_form_carrusel', '1');

        listaBanners.forEach((b, i) => {
            formData.append('slide_indices[]', i);
            formData.append('link_' + i, b.link || b.enlace || '#catalogo');
            formData.append('imagen_url_' + i, b.imagen || 'Images/123.webp');

            if (i === idxConArchivo && archivo) {
                formData.append('imagen_file_' + i, archivo);
            }
        });

        fetch('index.php?action=guardar_carrusel', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(res => {
            window.cerrarModalEditarBanner();
            window.mostrarCarruselToast('Éxito', fueEliminado ? 'Banner eliminado correctamente.' : 'Banner guardado con éxito.', false);
            
            // Si subió archivo local, recargar vista o actualizar URL localmente
            if (archivo) {
                setTimeout(() => location.reload(), 900);
            } else {
                renderizarGridBanners();
            }
        })
        .catch(() => {
            // Si el servidor hizo redirect en vez de json, recargar o enviar standard
            const hiddenContainer = document.getElementById('hiddenInputsContainer');
            if (hiddenContainer) {
                hiddenContainer.innerHTML = '';
                listaBanners.forEach((b, i) => {
                    hiddenContainer.innerHTML += `<input type="hidden" name="slide_indices[]" value="${i}">`;
                    hiddenContainer.innerHTML += `<input type="hidden" name="link_${i}" value="${b.link || b.enlace || '#catalogo'}">`;
                    hiddenContainer.innerHTML += `<input type="hidden" name="imagen_url_${i}" value="${b.imagen || 'Images/123.webp'}">`;
                });
                if (idxConArchivo >= 0 && archivo) {
                    const form = document.getElementById('formPrincipalCarrusel');
                    const fileInput = document.getElementById('modalFileInput');
                    if (fileInput && fileInput.files.length > 0) {
                        fileInput.name = 'imagen_file_' + idxConArchivo;
                        form.appendChild(fileInput);
                    }
                    form.submit();
                    return;
                }
            }
            renderizarGridBanners();
            window.cerrarModalEditarBanner();
            window.mostrarCarruselToast('Éxito', 'Cambios guardados con éxito.', false);
        });
    };

    // AGREGAR NUEVO BANNER
    window.agregarNuevoBanner = function() {
        const nuevo = {
            imagen: 'Images/123.webp',
            link: '#catalogo',
            enlace: '#catalogo'
        };
        listaBanners.push(nuevo);
        renderizarGridBanners();
        window.abrirModalEditarBanner(listaBanners.length - 1);
    };

    // Render inicial
    renderizarGridBanners();
</script>

<?php if (!AjaxHelper::isAjax()): ?>
            </div> 
        </div> 
    </div> 
</body>
</html>
<?php endif; ?>
