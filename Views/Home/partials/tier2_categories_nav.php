<?php
/**
 * Renderizado de la Barra de Navegación (Tier 2 Desktop)
 * Exactamente 7 pestañas principales (Inicio, 5 categorías maestras, Ofertas)
 * Con submenús de 2do orden y flyouts de 3er orden, sin botón de WhatsApp.
 */
require_once __DIR__ . '/navigation_data.php';

$esAdmin = (isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'admin');
$rutaInicio = $esAdmin ? 'index.php?action=home_admin' : 'index.php?action=home_usuario';

$actionActual = $_GET['action'] ?? 'home_usuario';
$idCatActual = isset($_GET['id']) ? (int)$_GET['id'] : null;
$idsActual = $_GET['ids'] ?? '';
$nombreCatActual = mb_strtolower(trim($_GET['nombre'] ?? ''));
$subActual = mb_strtolower(trim($_GET['sub'] ?? ''));
$esOfertasActual = isset($_GET['ofertas']) && $_GET['ofertas'] == '1';

$actionReal = $actionActual; // guardamos el action real para esInicioActivo
$idMomentoActual = isset($_GET['id_momento']) ? (int)$_GET['id_momento'] : null;
$idGrupoActual   = isset($_GET['id_grupo']) ? (int)$_GET['id_grupo'] : null;

$momentoNombreActual   = '';
$grupoNombreActual     = '';
$categoriaNombreActual = '';

if ($actionActual === 'detalle_producto' && isset($producto)) {
    $actionActual = 'ver_categoria'; // simula ver_categoria para el evaluador
    $idCatActual = (int)($producto['id_categoria'] ?? 0);
    $idGrupoActual = (int)($producto['id_grupo'] ?? 0);
    $idMomentoActual = (int)($producto['id_momento'] ?? 0);
    
    $momentoNombreActual   = $producto['momento_nombre'] ?? '';
    $grupoNombreActual     = $producto['grupo_nombre'] ?? '';
    $categoriaNombreActual = !empty($producto['nombre_categoria']) ? $producto['nombre_categoria'] : ($producto['categoria_nombre'] ?? '');

    if (empty($nombreCatActual)) {
        $nombreCatActual = mb_strtolower(trim($categoriaNombreActual ?: ($grupoNombreActual ?: $momentoNombreActual)));
    }
} elseif ($actionActual === 'ver_categoria' && !empty($product_bd)) {
    $primerP = reset($product_bd);

    if (!empty($_GET['id'])) {
        // 1. Navegación explícita a subcategoría hoja (nivel 3, ej: Verduras, Leches)
        $idCatActual = (int)$_GET['id'];
        $categoriaNombreActual = $primerP['categoria_nombre'] ?? '';
        if (empty($idGrupoActual))   $idGrupoActual   = (int)($primerP['id_grupo'] ?? 0);
        $grupoNombreActual = $primerP['grupo_nombre'] ?? '';
        if (empty($idMomentoActual)) $idMomentoActual = (int)($primerP['id_momento'] ?? 0);
        $momentoNombreActual = $primerP['momento_nombre'] ?? '';
    } elseif (!empty($_GET['id_grupo'])) {
        // 2. Navegación explícita a grupo/subcategoría intermedia (nivel 2, ej: Frutas y Verduras, Lácteos)
        // NUNCA asignar idCatActual ni categoriaNombreActual para no activar hojas
        $idCatActual = null;
        $categoriaNombreActual = '';
        $idGrupoActual = (int)$_GET['id_grupo'];
        $grupoNombreActual = $primerP['grupo_nombre'] ?? '';
        if (empty($idMomentoActual)) $idMomentoActual = (int)($primerP['id_momento'] ?? 0);
        $momentoNombreActual = $primerP['momento_nombre'] ?? '';
    } elseif (!empty($_GET['id_momento'])) {
        // 3. Navegación explícita a macrocategoría (nivel 1, ej: Frescos y Proteínas)
        $idCatActual = null;
        $categoriaNombreActual = '';
        $idGrupoActual = null;
        $grupoNombreActual = '';
        $idMomentoActual = (int)$_GET['id_momento'];
        $momentoNombreActual = $primerP['momento_nombre'] ?? '';
    } else {
        // 4. Búsqueda por nombre si no vienen IDs en GET
        $idCatActual = null;
        $categoriaNombreActual = '';
        $idGrupoActual = null;
        $grupoNombreActual = '';
        if (empty($idMomentoActual)) $idMomentoActual = (int)($primerP['id_momento'] ?? 0);
        $momentoNombreActual = $primerP['momento_nombre'] ?? '';

        if (!empty($nombreCatActual)) {
            foreach ($menuMultinivel as $grp) {
                if (mb_strtolower($grp['titulo']) === $nombreCatActual) {
                    $idMomentoActual = (int)$grp['id_momento'];
                    $momentoNombreActual = $grp['titulo'];
                    break;
                }
                if (!empty($grp['subcategorias'])) {
                    foreach ($grp['subcategorias'] as $sc) {
                        if (mb_strtolower($sc['nombre']) === $nombreCatActual) {
                            $idMomentoActual = (int)$grp['id_momento'];
                            $momentoNombreActual = $grp['titulo'];
                            $idGrupoActual = (int)$sc['id_grupo'];
                            $grupoNombreActual = $sc['nombre'];
                            break 2;
                        }
                        if (!empty($sc['subitems'])) {
                            foreach ($sc['subitems'] as $si) {
                                if (mb_strtolower($si['nombre']) === $nombreCatActual) {
                                    $idMomentoActual = (int)$grp['id_momento'];
                                    $momentoNombreActual = $grp['titulo'];
                                    $idGrupoActual = (int)$sc['id_grupo'];
                                    $grupoNombreActual = $sc['nombre'];
                                    $idCatActual = (int)$si['id'];
                                    $categoriaNombreActual = $si['nombre'];
                                    break 3;
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}

$activeMomentoId = null;

if (!$esOfertasActual && ($actionActual === 'ver_categoria' || $actionReal === 'detalle_producto')) {
    if (!empty($idMomentoActual)) {
        $activeMomentoId = (int)$idMomentoActual;
    } elseif (!empty($_GET['id_momento'])) {
        $activeMomentoId = (int)$_GET['id_momento'];
    } elseif (isset($producto) && !empty($producto['id_momento'])) {
        $activeMomentoId = (int)$producto['id_momento'];
    } elseif (!empty($product_bd)) {
        $primerP = reset($product_bd);
        $activeMomentoId = (int)($primerP['id_momento'] ?? 0);
    }
}

$paramsNav = [
    'action'            => $actionActual,
    'id'                => $idCatActual,
    'id_grupo'          => $idGrupoActual,
    'id_momento'        => $idMomentoActual,
    'active_momento_id' => $activeMomentoId,
    'es_ofertas'        => $esOfertasActual,
    'ids'               => $idsActual,
    'nombre'            => $nombreCatActual,
    'sub'               => $subActual,
    'momento_nombre'    => $momentoNombreActual,
    'grupo_nombre'      => $grupoNombreActual,
    'categoria_nombre'  => $categoriaNombreActual
];

$esInicioActivo = ($actionReal === 'home_usuario' || $actionReal === 'home_admin' || $actionReal === 'home')
    && empty($_GET['nombre']) && empty($_GET['id']) && empty($_GET['ids']) && !$esOfertasActual;
?>

<nav class="header-tier2-green-bar" id="headerTier2Bar">
    <div class="header-tier2-container">
        <ul class="tier2-nav-links" id="tier2NavLinks">
            <!-- 1. Inicio -->
            <li class="tier2-nav-item <?= $esInicioActivo ? 'active' : ''; ?>" id="navItemInicio">
                <a href="<?= htmlspecialchars($rutaInicio); ?>">Inicio</a>
            </li>

            <!-- 2-6. Categorías Maestras Anidadas con Flyouts (Exactamente una activa) -->
            <?php foreach ($menuMultinivel as $grupo): 
                $isGrupoActive = ($activeMomentoId !== null && (int)($grupo['id_momento'] ?? 0) === $activeMomentoId);
            ?>
                <li class="tier2-nav-item has-dropdown <?= $isGrupoActive ? 'active' : ''; ?>">
                    <a href="<?= htmlspecialchars($grupo['url']); ?>">
                        <?= htmlspecialchars($grupo['titulo']); ?>
                        <span class="nav-chevron">▾</span>
                    </a>
                    
                    <ul class="tier2-dropdown-menu">
                        <?php foreach ($grupo['subcategorias'] as $subcat): 
                            $tieneSubitems = !empty($subcat['subitems']);
                            $isSubcatActive = $isGrupoActive ? evaluarEstadoNavegacion($subcat, 'subcat', $paramsNav) : false;
                        ?>
                            <li class="tier2-sub-item <?= $tieneSubitems ? 'has-flyout' : ''; ?>">
                                <a href="<?= htmlspecialchars($subcat['url']); ?>" class="tier2-sub-link <?= $isSubcatActive ? 'is-active' : ''; ?>">
                                    <span class="sub-link-title"><?= htmlspecialchars($subcat['nombre']); ?></span>
                                    <?php if ($tieneSubitems): ?>
                                        <span class="flyout-arrow">▸</span>
                                    <?php endif; ?>
                                </a>
                                
                                <?php if ($tieneSubitems): ?>
                                    <ul class="tier2-flyout-menu">
                                        <?php foreach ($subcat['subitems'] as $item): 
                                            $isLeafActive = $isSubcatActive ? evaluarEstadoNavegacion($item, 'leaf', $paramsNav) : false;
                                        ?>
                                            <li>
                                                <a href="<?= htmlspecialchars($item['url']); ?>" class="<?= $isLeafActive ? 'is-active' : ''; ?>">
                                                    <?= htmlspecialchars($item['nombre']); ?>
                                                </a>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </li>
            <?php endforeach; ?>

            <!-- 7. Ofertas (Texto directo: "Ofertas", sin palabras adicionales) -->
            <li class="tier2-nav-item <?= ($esOfertasActual && $actionActual === 'ver_categoria') ? 'active' : ''; ?>">
                <a href="index.php?action=ver_categoria&ofertas=1&nombre=<?= urlencode('Ofertas'); ?>">
                    Ofertas
                </a>
            </li>
        </ul>
    </div>
</nav>
