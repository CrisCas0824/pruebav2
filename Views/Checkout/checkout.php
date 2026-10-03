<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario_id'])) {
    header('Location: index.php?action=login');
    exit();
}

$direcciones         = isset($direcciones) && is_array($direcciones) ? $direcciones : [];
$direccionSeleccionada = isset($direccionSeleccionada) && is_array($direccionSeleccionada) ? $direccionSeleccionada : null;
$metodosPago         = isset($metodosPago) && is_array($metodosPago) ? $metodosPago : [];
$distritos           = isset($distritos) && is_array($distritos) ? $distritos : [];
$productosCheckout   = isset($productosCheckout) && is_array($productosCheckout) ? $productosCheckout : [];
$productosNoDisponibles = isset($productosNoDisponibles) && is_array($productosNoDisponibles) ? $productosNoDisponibles : [];
$carritoValido       = isset($carritoValido) ? (bool)$carritoValido : false;
$cantidadTotal       = isset($cantidadTotal) ? (float)$cantidadTotal : 0;
$subtotal            = isset($subtotal) ? (float)$subtotal : 0;
$costoEnvio          = isset($costoEnvio) ? (float)$costoEnvio : 5;
$total               = isset($total) ? (float)$total : $subtotal + $costoEnvio;
$tipoEntrega         = $tipoEntrega ?? 'delivery';
$csrf_token          = $csrf_token ?? ($_SESSION['csrf_token'] ?? '');
$nombreUsuario       = trim($nombreUsuario ?? ($_SESSION['usuario_nombre'] ?? 'Usuario'));

$escapar = static function ($valor): string {
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
};
$formatearMoneda = static function ($valor): string {
    return 'S/ ' . number_format((float)$valor, 2, '.', '');
};
$obtenerUnidad = static function ($unidad): string {
    return strtoupper((string)$unidad) === 'KG' ? 'kg' : 'und.';
};
$direccionTexto = static function (?array $d) use ($escapar): string {
    if (!$d) return '';
    $partes = [];
    if (!empty($d['calle']))    $partes[] = $d['calle'];
    if (!empty($d['numero']))   $partes[] = '#' . $d['numero'];
    if (!empty($d['lote_manzana'])) $partes[] = 'Lote ' . $d['lote_manzana'];
    if (!empty($d['distrito'])) $partes[] = $d['distrito'];
    return implode(', ', $partes);
};

$idMetodoInicial = !empty($metodosPago) ? (int)$metodosPago[0]['id_metodo'] : 0;
$tieneDireccion  = !empty($direccionSeleccionada);
$puedeContinuar  = !empty($productosCheckout) && $carritoValido && !empty($metodosPago) && $tieneDireccion;

// Build distritos JS array for JS cost update
$distritosJson = json_encode(array_map(function($d) {
    return ['id' => (int)$d['id_distrito'], 'nombre' => $d['nombre'], 'costo' => (float)$d['costo_delivery']];
}, $distritos));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout &mdash; La Caserita</title>
    <meta name="description" content="Completa tu pedido. Selecciona modalidad de entrega, m&eacute;todo de pago y confirma tu orden.">
    <link rel="icon" type="image/png" href="Images/logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="Styles/global.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="Styles/styles-checkout.css?v=<?= time(); ?>">
    <script src="Scripts/normalizador-modales.js?v=<?= filemtime(__DIR__ . '/../../Scripts/normalizador-modales.js'); ?>" defer></script>
</head>
<body>

<!-- ===== HEADER ===== -->
<header class="co-header">
    <div class="co-header-inner">
        <div class="co-header-left">
            <a href="index.php?action=home_usuario" class="co-brand" title="La Caserita">
                <div class="co-brand-logo">
                    <img src="Images/logo_backup.png" alt="La Caserita" onerror="this.onerror=null;this.src='Images/logo.png';">
                </div>
                <span class="co-brand-name">La Caserita</span>
            </a>
        </div>
        <div class="co-header-center">
            <span class="co-header-title">Checkout</span>
        </div>
        <div class="co-header-right"></div>
    </div>
</header>

<div style="width: min(1400px, calc(100% - 40px)); margin: 20px auto 0; padding: 0;">
    <a href="index.php?action=home_usuario" class="link-volver">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
        Volver a la tienda
    </a>
</div>

<!-- ===== BARRA DE PROGRESO ===== -->
<nav class="co-progress" aria-label="Pasos del checkout">
    <div class="co-progress-inner">
        <div class="co-step active" id="navStep1">
            <div class="co-step-bubble">1</div>
            <div class="co-step-label"><strong>Entrega</strong><small>Modalidad</small></div>
        </div>
        <div class="co-step-connector" id="connector1"></div>
        <div class="co-step" id="navStep2">
            <div class="co-step-bubble">2</div>
            <div class="co-step-label"><strong>Pago</strong><small>M&eacute;todo</small></div>
        </div>
        <div class="co-step-connector" id="connector2"></div>
        <div class="co-step" id="navStep3">
            <div class="co-step-bubble">3</div>
            <div class="co-step-label"><strong>Confirmar</strong><small>Resumen</small></div>
        </div>
    </div>
</nav>

<!-- ===== MAIN ===== -->
<main class="co-page">
<form id="formCheckout" action="index.php?action=crear_pedido" method="POST" autocomplete="off" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= $escapar($csrf_token); ?>">
    <input type="hidden" name="id_direccion" id="checkoutDireccionId" value="<?= $direccionSeleccionada ? (int)$direccionSeleccionada['id_direccion'] : ''; ?>">
    <input type="hidden" name="tipo_entrega" id="checkoutTipoEntrega" value="delivery">
    <input type="hidden" name="id_metodo" id="checkoutMetodoId" value="<?= $idMetodoInicial; ?>">

    <!-- ================================
         PANEL 1: MODALIDAD DE ENTREGA
         ================================ -->
    <div class="co-step-panel active" id="panel1">
        <div class="co-panel-layout">
            <div class="co-main-col">

                <?php if (!empty($productosNoDisponibles)): ?>
                <div class="co-alert-warn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                    <div>
                        <strong>Algunos productos necesitan revisi&oacute;n</strong>
                        <ul>
                            <?php foreach ($productosNoDisponibles as $prob): ?>
                            <li><?= $escapar($prob['titulo'] ?? 'Producto'); ?>: <?= $escapar($prob['motivo'] ?? 'No disponible.'); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Modalidad de entrega -->
                <section class="co-card">
                    <div class="co-card-header">
                        <div class="co-card-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
                        </div>
                        <div>
                            <span class="co-eyebrow">Paso 1</span>
                            <h2>Modalidad de entrega</h2>
                            <p>Elige si deseas delivery a domicilio o recojo en tienda.</p>
                        </div>
                    </div>
                    <div class="co-options-grid">
                        <label class="co-option active" data-delivery-option="delivery">
                            <input type="radio" name="tipo_entrega_visual" value="delivery" checked>
                            <span class="co-radio-dot"></span>
                            <span class="co-option-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
                            </span>
                            <div class="co-option-body">
                                <strong>Delivery a domicilio</strong>
                                <small>Recibe tu pedido en la direcci&oacute;n seleccionada</small>
                                <span class="co-option-meta" id="deliveryPriceLabel">&#9201; 30&ndash;45 min &nbsp;&middot;&nbsp; + <span id="costoDeliveryDisplay"><?= $formatearMoneda($costoEnvio); ?></span></span>
                            </div>
                        </label>
                        <label class="co-option" data-delivery-option="recojo">
                            <input type="radio" name="tipo_entrega_visual" value="recojo">
                            <span class="co-radio-dot"></span>
                            <span class="co-option-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                            </span>
                            <div class="co-option-body">
                                <strong>Recojo en tienda</strong>
                                <small>Recoge tu pedido en nuestro local</small>
                                <span class="co-option-meta">&#9201; 15&ndash;20 min &nbsp;&middot;&nbsp; Gratis</span>
                            </div>
                        </label>
                    </div>
                </section>

                <!-- Horario de entrega (Debajo de Modalidad de entrega - Imagen 4 y 5) -->
                <section class="co-card" id="seccionHorario">
                    <div class="co-card-header">
                        <div class="co-card-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                        </div>
                        <div>
                            <span class="co-eyebrow">Horario</span>
                            <h2>Horario</h2>
                            <p>&iquest;Cu&aacute;ndo deseas recibir o recoger tu pedido?</p>
                        </div>
                    </div>

                    <div class="co-horario-row" id="horarioRow">
                        <div class="co-horario-col">
                            <label for="checkoutHorarioTipo" class="co-label">Horario</label>
                            <select name="horario_tipo" id="checkoutHorarioTipo" class="co-select">
                                <option value="inmediato">Lo antes posible (30-45 min)</option>
                                <option value="programado">Programar para despu&eacute;s</option>
                            </select>
                        </div>
                        <div class="co-horario-col" id="colFechaProgramada" style="display: none;">
                            <label for="checkoutFechaProgramada" class="co-label">Fecha programada</label>
                            <input type="datetime-local" name="fecha_programada" id="checkoutFechaProgramada" class="co-input">
                        </div>
                    </div>
                </section>

                <!-- Dirección de entrega (Nivel Chiclayo - Imagen 2 y 3) -->
                <section class="co-card" id="seccionDireccion">
                    <div class="co-card-header">
                        <div class="co-card-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 12-9 12S3 17 3 10a9 9 0 1 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                        </div>
                        <div style="flex:1">
                            <span class="co-eyebrow">Entrega</span>
                            <h2>Direcci&oacute;n de entrega</h2>
                            <p>Zona de reparto: distritos autorizados de Chiclayo.</p>
                        </div>
                        <?php if (!empty($direcciones)): ?>
                        <div class="co-saved-addresses-dropdown" style="display:flex; align-items:center; gap:8px;">
                            <label for="selectDireccionGuardada" class="co-small-label">Cargar guardada:</label>
                            <select id="selectDireccionGuardada" class="co-select-sm">
                                <option value="">-- Seleccionar --</option>
                                <?php foreach ($direcciones as $d): ?>
                                <option value="<?= (int)$d['id_direccion']; ?>"
                                    data-calle="<?= $escapar($d['calle'] ?? ''); ?>"
                                    data-numero="<?= $escapar($d['numero'] ?? ''); ?>"
                                    data-lote="<?= $escapar($d['lote_manzana'] ?? ''); ?>"
                                    data-distrito="<?= $escapar($d['distrito'] ?? ''); ?>"
                                    data-id-distrito="<?= (int)($d['id_distrito'] ?? 0); ?>"
                                    data-referencia="<?= $escapar($d['referencia'] ?? ''); ?>"
                                    data-costo="<?= (float)($d['costo_delivery'] ?? 5); ?>"
                                    <?= ($direccionSeleccionada && (int)$direccionSeleccionada['id_direccion'] === (int)$d['id_direccion']) ? 'selected' : ''; ?>>
                                    <?= $escapar($d['calle'] ?? ''); ?><?= !empty($d['distrito']) ? ' (' . $escapar($d['distrito']) . ')' : ''; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="co-address-form-grid">
                        <!-- Fila 1: Calle / Avenida + Número (Imagen 2) -->
                        <div class="co-field-group">
                            <label for="checkoutCalle" class="co-label">Calle / Avenida</label>
                            <input type="text" name="calle" id="checkoutCalle" class="co-input"
                                placeholder="Ej. Av. Jos&eacute; Balta"
                                value="<?= $escapar($direccionSeleccionada['calle'] ?? ''); ?>">
                        </div>
                        <div class="co-field-group">
                            <label for="checkoutNumero" class="co-label">N&uacute;mero</label>
                            <input type="text" name="numero" id="checkoutNumero" class="co-input"
                                placeholder="Ej. 456"
                                value="<?= $escapar($direccionSeleccionada['numero'] ?? ''); ?>">
                        </div>

                        <!-- Fila 2: Lote / Manzana + Distrito (Imagen 2 y 3) -->
                        <div class="co-field-group">
                            <label for="checkoutLote" class="co-label">Lote / Manzana</label>
                            <input type="text" name="lote_manzana" id="checkoutLote" class="co-input"
                                placeholder="Ej. Lote 12"
                                value="<?= $escapar($direccionSeleccionada['lote_manzana'] ?? ''); ?>">
                        </div>
                        <div class="co-field-group">
                            <label for="checkoutDistrito" class="co-label">Distrito</label>
                            <select name="distrito" id="checkoutDistrito" class="co-select">
                                <option value="">Selecciona...</option>
                                <?php 
                                $distritoActual = $direccionSeleccionada['distrito'] ?? '';
                                foreach ($distritos as $dist): 
                                    $isSelected = (strcasecmp(trim($distritoActual), trim($dist['nombre'])) === 0);
                                ?>
                                <option value="<?= $escapar($dist['nombre']); ?>"
                                    data-costo="<?= (float)$dist['costo_delivery']; ?>"
                                    data-id="<?= (int)$dist['id_distrito']; ?>"
                                    <?= $isSelected ? 'selected' : ''; ?>>
                                    <?= $escapar($dist['nombre']); ?> (S/ <?= number_format((float)$dist['costo_delivery'], 2); ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="hidden" name="id_distrito" id="checkoutIdDistrito" value="<?= (int)($direccionSeleccionada['id_distrito'] ?? 1); ?>">
                        </div>

                        <!-- Fila 3: Referencia (Imagen 2) -->
                        <div class="co-field-group co-field-full">
                            <label for="checkoutReferencia" class="co-label">Referencia</label>
                            <textarea name="referencia" id="checkoutReferencia" class="co-textarea" rows="3"
                                placeholder="Casa color amarillo, frente al parque..."><?= $escapar($direccionSeleccionada['referencia'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <div class="co-field-error-box" id="checkoutAddressRequirement" hidden>
                        Por favor completa los campos de Calle / Avenida, N&uacute;mero y Distrito.
                    </div>
                </section>

                <!-- Indicaciones -->
                <section class="co-card">
                    <div class="co-card-header">
                        <div class="co-card-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"></path></svg>
                        </div>
                        <div>
                            <span class="co-eyebrow">Opcional</span>
                            <h2>Indicaciones del pedido</h2>
                            <p>Instrucciones especiales para la entrega.</p>
                        </div>
                    </div>
                    <div class="co-textarea-wrap">
                        <textarea name="observacion" id="checkoutObservacion" maxlength="255" rows="3" placeholder="Ej. Tocar el timbre, llamar antes de llegar..."></textarea>
                        <span class="co-char-count"><span id="observacionCount">0</span>/255</span>
                    </div>
                </section>

            </div><!-- /co-main-col -->

            <!-- Sidebar paso 1 -->
            <aside class="co-sidebar">
                <div class="co-sidebar-card co-card">
                    <div class="co-summary-header">
                        <h2>Tu Pedido</h2>
                        <span class="co-count-badge"><?= (float)$cantidadTotal; ?> prod.</span>
                    </div>
                    <div class="co-products-list">
                        <?php foreach ($productosCheckout as $prod): ?>
                        <div class="co-product-row <?= empty($prod['tiene_stock']) ? 'co-stock-err' : ''; ?>">
                            <div class="co-product-img"><img src="<?= $escapar($prod['imagen'] ?? ''); ?>" alt="<?= $escapar($prod['titulo'] ?? ''); ?>"></div>
                            <div class="co-product-data">
                                <span><?= $escapar($prod['titulo'] ?? ''); ?></span>
                                <small><?= $escapar($prod['cantidad']); ?> <?= $obtenerUnidad($prod['unidad_medida'] ?? 'UND'); ?> &times; <?= $formatearMoneda($prod['precio'] ?? 0); ?></small>
                                <?php if (empty($prod['tiene_stock'])): ?><em class="co-stock-label">Sin stock</em><?php endif; ?>
                            </div>
                            <strong><?= $formatearMoneda($prod['subtotal'] ?? 0); ?></strong>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="co-totals">
                        <div class="co-total-row"><span>Subtotal</span><strong id="checkoutSubtotal"><?= $formatearMoneda($subtotal); ?></strong></div>
                        <div class="co-total-row"><span>Env&iacute;o</span><strong id="checkoutDeliveryCost"><?= $formatearMoneda($costoEnvio); ?></strong></div>
                        <div class="co-total-divider"></div>
                        <div class="co-total-row co-total-final"><span>Total</span><strong id="checkoutTotal"><?= $formatearMoneda($total); ?></strong></div>
                    </div>
                    <!-- Seguir comprando en rojo vino y DEBAJO continuar al pago -->
                    <div style="margin-top:16px; display:flex; flex-direction:column; gap:10px;">
                        <a href="index.php?action=home_usuario" class="co-keep-shopping">&#8592; Seguir comprando</a>
                        <button type="button" class="co-btn-next co-btn-block" id="btnNextStep1Sidebar">
                            Continuar al pago
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                        </button>
                    </div>
                </div>
            </aside>
        </div><!-- /co-panel-layout -->
    </div><!-- /panel1 -->

    <!-- ================================
         PANEL 2: MÉTODO DE PAGO
         ================================ -->
    <div class="co-step-panel" id="panel2">
        <div class="co-panel-layout">
            <div class="co-main-col">

                <section class="co-card">
                    <div class="co-card-header">
                        <div class="co-card-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
                        </div>
                        <div>
                            <span class="co-eyebrow">Paso 2</span>
                            <h2>M&eacute;todo de pago</h2>
                            <p>Selecciona c&oacute;mo deseas pagar tu pedido.</p>
                        </div>
                    </div>

                    <?php if (!empty($metodosPago)): ?>
                    <div class="co-options-grid co-payment-options">
                        <?php foreach ($metodosPago as $idx => $metodo): ?>
                        <?php
                        $nombreM = strtolower(trim($metodo['nombre'] ?? ''));
                        $esYapePlin = strpos($nombreM, 'yape') !== false || strpos($nombreM, 'plin') !== false;
                        $esTarjeta = strpos($nombreM, 'tarjeta') !== false;
                        
                        if ($esYapePlin) {
                            $iconoM = '<img src="public/images/yape-plin-icon.png" alt="Yape Plin" style="height:24px;">'; // Fallback text icon if img not there
                            $iconoM = '<div style="display:flex; gap:4px;"><span style="color:#6A1B9A; font-weight:bold; background:#e1bee7; padding:2px 4px; border-radius:4px; font-size:10px;">Yape</span><span style="color:#00BFA5; font-weight:bold; background:#b2dfdb; padding:2px 4px; border-radius:4px; font-size:10px;">Plin</span></div>';
                        } elseif ($esTarjeta) {
                            $iconoM = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#d32f2f" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"></rect><line x1="2" y1="10" x2="22" y2="10"></line></svg>';
                        } else {
                            $iconoM = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#388e3c" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>';
                        }
                        ?>
                        <label class="co-option <?= $idx === 0 ? 'active' : ''; ?>"
                            data-payment-option="<?= (int)$metodo['id_metodo']; ?>"
                            data-nombre-metodo="<?= htmlspecialchars(strtolower($metodo['nombre'])); ?>"
                            data-qr="<?= htmlspecialchars($metodo['codigo_qr'] ?? ''); ?>"
                            data-cuenta="<?= htmlspecialchars($metodo['numero_cuenta'] ?? '999-111-222'); ?>"
                            data-titular="<?= htmlspecialchars($metodo['titular'] ?? 'La Caserita S.A.C.'); ?>"
                            data-requiere-comprobante="<?= !empty($metodo['requiere_comprobante']) ? '1' : '0'; ?>">
                            <input type="radio" name="metodo_pago_visual" value="<?= (int)$metodo['id_metodo']; ?>" <?= $idx === 0 ? 'checked' : ''; ?>>
                            <div class="co-radio-dot"></div>
                            <span class="co-option-icon"><?= $iconoM; ?></span>
                            <div class="co-option-body">
                                <strong><?= $escapar($metodo['nombre'] ?? 'M&eacute;todo de pago'); ?></strong>
                            </div>
                        </label>
                        
                        <?php if (strpos($nombreM, 'efectivo') !== false || strpos($nombreM, 'recibir') !== false): ?>
                            <!-- Panel Efectivo -->
                            <div id="seccionEfectivo" class="co-dynamic-panel" style="display:<?= $idx === 0 ? 'block' : 'none'; ?>; margin-bottom:20px; padding:20px; background:#f8fafc; border-radius:12px; border:1px solid #e2e8f0; width:100%;">
                                <h4 style="margin:0 0 12px; color:#1e293b; font-size:1.1rem;">Pago en efectivo</h4>
                                <p style="margin:0 0 16px; font-size:0.95rem; color:#475569;">Monto a pagar: <strong id="montoEfectivoTexto" style="color:#d32f2f; font-size:1.2rem;"><?= $formatearMoneda($total); ?></strong></p>
                                <div class="co-form-group">
                                    <label style="font-weight:600; font-size:0.95rem;">¿Con cuánto vas a pagar? <span class="co-required">*</span></label>
                                    <input type="number" step="0.10" name="monto_efectivo" id="monto_efectivo" placeholder="Ej. 50.00" style="width:100%; padding:14px; border:1px solid #cbd5e1; border-radius:8px; font-size:1.1rem; margin-top:8px;">
                                    <small id="efectivoError" style="color:#d32f2f; display:none; margin-top:6px;">El monto ingresado debe ser mayor o igual al total a pagar.</small>
                                </div>
                            </div>
                        <?php elseif ($esYapePlin): ?>
                            <!-- Panel Yape / Plin -->
                            <div id="seccionYapePlin" class="co-dynamic-panel" style="display:<?= $idx === 0 ? 'block' : 'none'; ?>; margin-bottom:20px; width:100%;">
                                <div class="co-yape-plin-tabs" style="display:flex; gap:12px; margin-bottom:16px;">
                                    <button type="button" class="co-yp-tab active" data-target="yape" style="flex:1; padding:12px; border-radius:8px; border:1.5px solid #d32f2f; background:transparent; display:flex; align-items:center; justify-content:center; gap:8px; cursor:pointer; font-weight:bold; color:#111; transition:all 0.2s;">
                                        <span style="color:#6A1B9A; background:#e1bee7; padding:2px 4px; border-radius:4px; font-size:12px;">Yape</span> Yape
                                    </button>
                                    <button type="button" class="co-yp-tab" data-target="plin" style="flex:1; padding:12px; border-radius:8px; border:1.5px solid #e2e8f0; background:transparent; display:flex; align-items:center; justify-content:center; gap:8px; cursor:pointer; font-weight:bold; color:#111; transition:all 0.2s;">
                                        <span style="color:#00BFA5; background:#b2dfdb; padding:2px 4px; border-radius:4px; font-size:12px;">Plin</span> Plin
                                    </button>
                                </div>
                                <div style="background:#f8fafc; border-radius:12px; padding:20px; border:1px solid #e2e8f0;">
                                    <div style="display:flex; flex-wrap:wrap; gap:20px; margin-bottom:20px; border-bottom:1px solid #cbd5e1; padding-bottom:20px;">
                                        <div style="text-align:center; background:#fff; padding:12px; border-radius:8px; border:1px solid #e2e8f0;">
                                            <img id="qrBilletera" src="uploads/qr/qr_yape.svg" alt="QR Oficial" style="max-width:120px; display:block; margin:0 auto;">
                                            <small style="color:#64748b; margin-top:8px; display:block;">Escanea para pagar</small>
                                        </div>
                                        <div style="flex:1; display:flex; flex-direction:column; justify-content:center;">
                                            <h3 style="margin:0 0 8px; font-size:1.1rem; color:#0f172a;">La Caserita S.A.C.</h3>
                                            <p style="margin:0 0 4px; color:#475569; font-size:0.9rem;">Número: <strong id="numeroBilletera">999-111-222</strong></p>
                                            <p style="margin:0; color:#475569; font-size:0.9rem;">Monto a transferir: <strong style="color:#d32f2f;"><?= $formatearMoneda($total); ?></strong></p>
                                        </div>
                                    </div>
                                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px;">
                                        <div class="co-form-group">
                                            <label id="lblCelularYP" style="font-weight:600; font-size:0.9rem;">Tu celular emisor <span class="co-required">*</span></label>
                                            <input type="text" name="celular_yape_plin" id="celular_yape_plin" placeholder="Ej: 987 654 321" maxlength="11" style="width:100%; padding:12px; border:1px solid #cbd5e1; border-radius:8px; font-size:1rem; margin-top:6px;">
                                        </div>
                                        <div class="co-form-group">
                                            <label style="font-weight:600; font-size:0.9rem;">Código de aprobación <span class="co-required">*</span></label>
                                            <input type="text" name="codigo_yape_plin" id="codigo_yape_plin" placeholder="123 456" maxlength="8" style="width:100%; padding:12px; border:1px solid #cbd5e1; border-radius:8px; font-size:1rem; margin-top:6px;">
                                        </div>
                                    </div>
                                    <div class="co-form-group" style="margin-top:16px;">
                                        <label style="font-weight:600; font-size:0.9rem;">Sube la captura de pago <span class="co-required">*</span></label>
                                        <label for="captura_voucher" style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 20px; border: 2px dashed #cbd5e1; border-radius: 12px; background: #fff; cursor: pointer; transition: all 0.3s; color: #475569; margin-top: 6px;">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="none" stroke="#d32f2f" stroke-width="2" style="margin-bottom:8px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                                            <span id="file-upload-name" style="font-weight:600;">Haz clic para seleccionar imagen</span>
                                        </label>
                                        <input type="file" name="captura_voucher" id="captura_voucher" accept="image/*" style="display:none;" onchange="document.getElementById('file-upload-name').textContent = this.files[0] ? this.files[0].name : 'Haz clic para seleccionar imagen';">
                                    </div>
                                    <input type="hidden" name="billetera_seleccionada" id="billetera_seleccionada" value="Yape">
                                </div>
                            </div>
                        <?php elseif ($esTarjeta): ?>
                            <!-- Panel Tarjeta -->
                            <div id="seccionTarjeta" class="co-dynamic-panel" style="display:<?= $idx === 0 ? 'block' : 'none'; ?>; margin-bottom:20px; padding:20px; background:#f8fafc; border-radius:12px; border:1px solid #e2e8f0; width:100%;">
                                <!-- Tarjeta Visual 3D Flip -->
                                <div class="cc-flip-container" style="perspective:1000px; max-width:340px; margin:0 auto 24px;">
                                    <div class="cc-flipper" id="ccFlipper" style="transition:0.6s; transform-style:preserve-3d; position:relative; height:200px; width:100%;">
                                        <!-- Front -->
                                        <div class="cc-front" style="backface-visibility:hidden; position:absolute; top:0; left:0; width:100%; height:100%; background:#1e3a8a; border-radius:16px; padding:24px; color:#fff; box-shadow:0 8px 24px rgba(30,58,138,0.4); box-sizing:border-box;">
                                            <div style="display:flex; justify-content:space-between; margin-bottom:30px;">
                                                <div style="width:48px; height:32px; background:#fbbf24; border-radius:6px; opacity:0.9;"></div>
                                                <div style="font-weight:800; font-size:1.4rem; font-style:italic;">VISA</div>
                                            </div>
                                            <div id="ccNumVisual" style="font-size:1.4rem; letter-spacing:2px; margin-bottom:24px; font-family:monospace; min-height:26px;">•••• •••• •••• ••••</div>
                                            <div style="display:flex; justify-content:space-between; font-size:0.75rem; text-transform:uppercase; letter-spacing:1px; opacity:0.8; margin-bottom:4px;">
                                                <span>Titular</span>
                                                <span>Vence</span>
                                            </div>
                                            <div style="display:flex; justify-content:space-between; font-size:1.1rem; font-family:monospace;">
                                                <span id="ccNameVisual" style="min-height:20px; text-transform:uppercase;">NOMBRE APELLIDO</span>
                                                <span id="ccExpVisual" style="min-height:20px;">MM/AA</span>
                                            </div>
                                        </div>
                                        <!-- Back -->
                                        <div class="cc-back" style="backface-visibility:hidden; position:absolute; top:0; left:0; width:100%; height:100%; background:#1e3a8a; border-radius:16px; color:#fff; box-shadow:0 8px 24px rgba(30,58,138,0.4); transform:rotateY(180deg); box-sizing:border-box; padding-top:24px;">
                                            <div style="background:#111; height:40px; width:100%; margin-bottom:16px;"></div>
                                            <div style="padding:0 24px;">
                                                <div style="text-align:right; font-size:0.8rem; margin-bottom:4px; opacity:0.8;">CVV</div>
                                                <div style="background:#fff; color:#111; padding:8px 12px; border-radius:4px; text-align:right; font-family:monospace; font-size:1.1rem; min-height:18px;" id="ccCvvVisual">•••</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Formulario Tarjeta -->
                                <div class="co-form-group" style="margin-bottom:16px;">
                                    <label style="font-weight:600; font-size:0.9rem;">Número de tarjeta <span class="co-required">*</span></label>
                                    <input type="text" name="cc_numero" id="cc_numero" placeholder="1234 5678 9012 3456" maxlength="19" style="width:100%; padding:12px; border:1px solid #cbd5e1; border-radius:8px; font-size:1rem; margin-top:6px;">
                                </div>
                                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px;">
                                    <div class="co-form-group">
                                        <label style="font-weight:600; font-size:0.9rem;">Vencimiento <span class="co-required">*</span></label>
                                        <input type="text" name="cc_vencimiento" id="cc_vencimiento" placeholder="MM/AA" maxlength="5" style="width:100%; padding:12px; border:1px solid #cbd5e1; border-radius:8px; font-size:1rem; margin-top:6px;">
                                    </div>
                                    <div class="co-form-group">
                                        <label style="font-weight:600; font-size:0.9rem;">CVV <span class="co-required">*</span></label>
                                        <input type="text" name="cc_cvv" id="cc_cvv" placeholder="•••" maxlength="3" oninput="this.value = this.value.replace(/[^0-9]/g, '');" style="width:100%; padding:12px; border:1px solid #cbd5e1; border-radius:8px; font-size:1rem; margin-top:6px;">
                                    </div>
                                </div>
                                <div class="co-form-group">
                                    <label style="font-weight:600; font-size:0.9rem;">Nombre en la tarjeta <span class="co-required">*</span></label>
                                    <input type="text" name="cc_nombre" id="cc_nombre" placeholder="JUAN PÉREZ" oninput="this.value = this.value.replace(/[^a-zA-Z\s]/g, '');" style="width:100%; padding:12px; border:1px solid #cbd5e1; border-radius:8px; font-size:1rem; margin-top:6px; text-transform:uppercase;">
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="co-empty-msg">No hay m&eacute;todos de pago disponibles en este momento.</div>
                    <?php endif; ?>
                </section>

            </div><!-- /co-main-col -->

            <!-- Sidebar paso 2 -->
            <aside class="co-sidebar">
                <div class="co-sidebar-card co-card">
                    <div class="co-summary-header">
                        <h2>Tu Pedido</h2>
                        <span class="co-count-badge"><?= (float)$cantidadTotal; ?> prod.</span>
                    </div>
                    <div class="co-products-list">
                        <?php foreach ($productosCheckout as $prod): ?>
                        <div class="co-product-row <?= empty($prod['tiene_stock']) ? 'co-stock-err' : ''; ?>">
                            <div class="co-product-img"><img src="<?= $escapar($prod['imagen'] ?? ''); ?>" alt="<?= $escapar($prod['titulo'] ?? ''); ?>"></div>
                            <div class="co-product-data">
                                <span><?= $escapar($prod['titulo'] ?? ''); ?></span>
                                <small><?= $escapar($prod['cantidad']); ?> <?= $obtenerUnidad($prod['unidad_medida'] ?? 'UND'); ?> &times; <?= $formatearMoneda($prod['precio'] ?? 0); ?></small>
                                <?php if (empty($prod['tiene_stock'])): ?><em class="co-stock-label">Sin stock</em><?php endif; ?>
                            </div>
                            <strong><?= $formatearMoneda($prod['subtotal'] ?? 0); ?></strong>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="co-totals">
                        <div class="co-total-row"><span>Subtotal</span><strong><?= $formatearMoneda($subtotal); ?></strong></div>
                        <div class="co-total-row"><span>Env&iacute;o</span><strong id="checkoutDeliveryCost2"><?= $formatearMoneda($costoEnvio); ?></strong></div>
                        <div class="co-total-divider"></div>
                        <div class="co-total-row co-total-final"><span>Total</span><strong id="checkoutTotal2"><?= $formatearMoneda($total); ?></strong></div>
                    <div style="margin-top:16px; display:flex; flex-direction:column; gap:10px;">
                        <button type="button" class="co-btn-back-link" id="btnBackStep2Sidebar">&#8592; Volver</button>
                        <button type="button" class="co-btn-next co-btn-block" id="btnNextStep2Sidebar">
                            Ver confirmaci&oacute;n
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                        </button>
                    </div>
                </div>
            </aside>
        </div><!-- /co-panel-layout -->
    </div><!-- /panel2 -->

    <!-- ================================
         PANEL 3: CONFIRMACIÓN + RESUMEN
         ================================ -->
    <div class="co-step-panel" id="panel3">
        <div class="co-panel-layout">
            <div class="co-main-col">

                <section class="co-card">
                    <div class="co-card-header">
                        <div class="co-card-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        </div>
                        <div>
                            <span class="co-eyebrow">Paso 3</span>
                            <h2>Confirma tu pedido</h2>
                            <p>Revisa los detalles antes de enviar tu orden.</p>
                        </div>
                    </div>

                    <!-- Bloque Entrega -->
                    <div class="co-confirm-block">
                        <div class="co-confirm-block-header">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
                            <h3>Modalidad de entrega</h3>
                            <button type="button" class="co-edit-link" onclick="goToStep(1)">Editar</button>
                        </div>
                        <div class="co-confirm-content">
                            <p id="confirmEntregaModalidad"><strong>Delivery a domicilio</strong></p>
                            <p id="confirmEntregaDireccion" class="co-muted"></p>
                            <p id="confirmEntregaIndicaciones" class="co-muted co-italic"></p>
                        </div>
                    </div>

                    <!-- Bloque Pago -->
                    <div class="co-confirm-block">
                        <div class="co-confirm-block-header">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
                            <h3>M&eacute;todo de pago</h3>
                            <button type="button" class="co-edit-link" onclick="goToStep(2)">Editar</button>
                        </div>
                        <div class="co-confirm-content">
                            <p id="confirmMetodoPago"><strong>&mdash;</strong></p>
                        </div>
                    </div>



                    <!-- Totales -->
                    <div class="co-confirm-totals">
                        <div class="co-total-row"><span>Subtotal</span><strong><?= $formatearMoneda($subtotal); ?></strong></div>
                        <div class="co-total-row"><span>Env&iacute;o</span><strong id="checkoutDeliveryCost3"><?= $formatearMoneda($costoEnvio); ?></strong></div>
                        <div class="co-total-divider"></div>
                        <div class="co-total-row co-total-final"><span>Total a pagar</span><strong id="checkoutTotal3"><?= $formatearMoneda($total); ?></strong></div>
                    </div>
                </section>

                <div class="co-field-error-box" id="checkoutValidationMessage" <?= $puedeContinuar ? 'hidden' : ''; ?>>
                    <?php if (!$carritoValido): ?>
                        Revisa los productos que no tienen stock suficiente.
                    <?php elseif (empty($metodosPago)): ?>
                        No existen m&eacute;todos de pago disponibles.
                    <?php elseif (!$tieneDireccion): ?>
                        Selecciona o registra una direcci&oacute;n para delivery.
                    <?php endif; ?>
                </div>

            </div><!-- /co-main-col -->

            <!-- Sidebar paso 3 -->
            <aside class="co-sidebar">
                <div class="co-sidebar-card co-card">
                    <div class="co-summary-header">
                        <h2>Tu Pedido</h2>
                        <span class="co-count-badge"><?= (float)$cantidadTotal; ?> prod.</span>
                    </div>
                    <div class="co-products-list">
                        <?php foreach ($productosCheckout as $prod): ?>
                        <div class="co-product-row <?= empty($prod['tiene_stock']) ? 'co-stock-err' : ''; ?>">
                            <div class="co-product-img"><img src="<?= $escapar($prod['imagen'] ?? ''); ?>" alt="<?= $escapar($prod['titulo'] ?? ''); ?>"></div>
                            <div class="co-product-data">
                                <span><?= $escapar($prod['titulo'] ?? ''); ?></span>
                                <small><?= $escapar($prod['cantidad']); ?> <?= $obtenerUnidad($prod['unidad_medida'] ?? 'UND'); ?> &times; <?= $formatearMoneda($prod['precio'] ?? 0); ?></small>
                                <?php if (empty($prod['tiene_stock'])): ?><em class="co-stock-label">Sin stock</em><?php endif; ?>
                            </div>
                            <strong><?= $formatearMoneda($prod['subtotal'] ?? 0); ?></strong>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="co-totals">
                        <div class="co-total-row"><span>Subtotal</span><strong><?= $formatearMoneda($subtotal); ?></strong></div>
                        <div class="co-total-row"><span>Env&iacute;o</span><strong id="checkoutDeliveryCost4"><?= $formatearMoneda($costoEnvio); ?></strong></div>
                        <div class="co-total-divider"></div>
                        <div class="co-total-row co-total-final"><span>Total</span><strong id="checkoutTotal4"><?= $formatearMoneda($total); ?></strong></div>
                    </div>
                    <div style="margin-top:16px; display:flex; flex-direction:column; gap:10px;">
                        <button type="button" class="co-btn-back-link" id="btnBackStep3Sidebar">&#8592; Volver al pago</button>
                        <button type="submit" class="co-btn-submit co-btn-block" id="btnHacerPedidoSidebar" <?= $puedeContinuar ? '' : 'disabled'; ?>>
                            <span class="co-submit-default">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                Hacer pedido
                            </span>
                            <span class="co-submit-loading" hidden>Procesando...</span>
                        </button>
                    </div>
                    <p class="co-terms" style="margin-top:16px;">Al hacer el pedido confirmas que revisaste los productos, direcci&oacute;n y m&eacute;todo de pago.</p>
                </div>
            </aside>
        </div><!-- /co-panel-layout -->
    </div><!-- /panel3 -->

</form>

<div class="co-modal-overlay" id="modalConfirmarPedido" aria-hidden="true">
    <div class="co-modal" role="dialog" aria-modal="true" aria-labelledby="tituloConfirmarPedido">
        <div class="co-modal-head">
            <div>
                <span class="co-eyebrow">Revisión final</span>
                <h2 id="tituloConfirmarPedido">¿Confirmas tu pedido?</h2>
                <p>Se enviará la orden con los productos y datos que revisaste.</p>
            </div>
            <button type="button" class="co-modal-close" data-close-modal="modalConfirmarPedido" aria-label="Cerrar">&times;</button>
        </div>
        <div class="co-modal-body">
            <div class="co-confirm-totals" style="margin:0;">
                <div class="co-total-row co-total-final"><span>Total a pagar</span><strong id="checkoutModalConfirmTotal"><?= $formatearMoneda($total); ?></strong></div>
            </div>
        </div>
        <div class="co-modal-foot">
            <button type="button" class="co-btn-secondary" data-close-modal="modalConfirmarPedido">Seguir revisando</button>
            <button type="button" class="co-btn-submit" id="btnConfirmarPedidoFinal">Sí, hacer pedido</button>
        </div>
    </div>
</div>
</main>

<!-- FOOTER -->
<?php include 'Views/Home/partials/footer.php'; ?>

<!-- MODAL: LISTA DE DIRECCIONES -->
<div class="co-modal-overlay" id="modalDireccionesCheckout" aria-hidden="true">
    <div class="co-modal">
        <div class="co-modal-head">
            <div>
                <span class="co-eyebrow">Entrega</span>
                <h2>Mis direcciones</h2>
                <p>Selecciona d&oacute;nde deseas recibir el pedido.</p>
            </div>
            <button type="button" class="co-modal-close" data-close-modal="modalDireccionesCheckout">&#215;</button>
        </div>
        <div class="co-modal-body">
            <div id="checkoutAddressList" class="co-address-list">
                <?php if (!empty($direcciones)): ?>
                <?php foreach ($direcciones as $dir): ?>
                <label class="co-address-opt <?= $direccionSeleccionada && (int)$direccionSeleccionada['id_direccion'] === (int)$dir['id_direccion'] ? 'active' : ''; ?>"
                    data-id-direccion="<?= (int)$dir['id_direccion']; ?>"
                    data-calle="<?= $escapar($dir['calle'] ?? ''); ?>"
                    data-numero="<?= $escapar($dir['numero'] ?? ''); ?>"
                    data-lote="<?= $escapar($dir['lote_manzana'] ?? ''); ?>"
                    data-distrito="<?= $escapar($dir['distrito'] ?? ''); ?>"
                    data-referencia="<?= $escapar($dir['referencia'] ?? ''); ?>"
                    data-costo="<?= (float)($dir['costo_delivery'] ?? 5); ?>">
                    <input type="radio" name="direccion_checkout_visual" value="<?= (int)$dir['id_direccion']; ?>" <?= $direccionSeleccionada && (int)$direccionSeleccionada['id_direccion'] === (int)$dir['id_direccion'] ? 'checked' : ''; ?>>
                    <span class="co-radio-dot"></span>
                    <span class="co-address-opt-icon">&#127968;</span>
                    <div class="co-address-opt-info">
                        <strong><?= (int)$dir['es_principal'] === 1 ? 'Direcci&oacute;n principal' : 'Direcci&oacute;n guardada'; ?></strong>
                        <small><?= $escapar($direccionTexto($dir)); ?></small>
                        <?php if (!empty($dir['referencia'])): ?><span><?= $escapar($dir['referencia']); ?></span><?php endif; ?>
                        <?php if (!empty($dir['costo_delivery'])): ?>
                        <span class="co-badge-green" style="margin-top:4px;">Env&iacute;o: <?= $formatearMoneda($dir['costo_delivery']); ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if ((int)$dir['es_principal'] === 1): ?><span class="co-badge-green">Principal</span><?php endif; ?>
                </label>
                <?php endforeach; ?>
                <?php else: ?>
                <div class="co-empty-msg">No tienes direcciones registradas.</div>
                <?php endif; ?>
            </div>
        </div>
        <div class="co-modal-foot">
            <button type="button" class="co-btn-secondary" data-close-modal="modalDireccionesCheckout">Cancelar</button>
            <button type="button" class="co-btn-primary" id="btnAbrirNuevaDireccion">+ Nueva direcci&oacute;n</button>
        </div>
    </div>
</div>

<!-- MODAL: NUEVA DIRECCIÓN (campos Chiclayo) -->
<div class="co-modal-overlay" id="modalNuevaDireccionCheckout" aria-hidden="true">
    <div class="co-modal">
        <div class="co-modal-head">
            <div>
                <span class="co-eyebrow">Direcci&oacute;n</span>
                <h2>Registrar nueva direcci&oacute;n</h2>
                <p>Esta direcci&oacute;n tambi&eacute;n se guardar&aacute; en tu cuenta.</p>
            </div>
            <button type="button" class="co-modal-close" data-close-modal="modalNuevaDireccionCheckout">&#215;</button>
        </div>
        <form id="formNuevaDireccionCheckout" action="index.php?action=crear_direccion" method="POST" autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?= $escapar($csrf_token); ?>">
            <input type="hidden" name="departamento" value="Lambayeque">
            <input type="hidden" name="provincia" value="Chiclayo">
            <div class="co-modal-body">
                <div class="co-form-grid">
                    <!-- Calle / Avenida -->
                    <div class="co-form-group">
                        <label for="checkoutCalle">Calle / Avenida <span class="co-required">*</span></label>
                        <input type="text" name="calle" id="checkoutCalle" maxlength="150" placeholder="Ej. Av. Jos&eacute; Balta" required>
                        <small class="co-field-error" data-error-for="calle"></small>
                    </div>
                    <!-- Número -->
                    <div class="co-form-group">
                        <label for="checkoutNumero">N&uacute;mero <span class="co-required">*</span></label>
                        <input type="text" name="numero" id="checkoutNumero" maxlength="20" placeholder="Ej. 456" required>
                        <small class="co-field-error" data-error-for="numero"></small>
                    </div>
                    <!-- Lote / Manzana -->
                    <div class="co-form-group">
                        <label for="checkoutLote">Lote / Manzana <small style="color:#94a3b8;">(opcional)</small></label>
                        <input type="text" name="lote_manzana" id="checkoutLote" maxlength="30" placeholder="Ej. Lote 12">
                    </div>
                    <!-- Distrito dropdown -->
                    <div class="co-form-group">
                        <label for="checkoutDistrito">Distrito <span class="co-required">*</span></label>
                        <select name="distrito" id="checkoutDistrito" required class="co-select">
                            <option value="">Selecciona...</option>
                            <?php foreach ($distritos as $dist): ?>
                            <option value="<?= $escapar($dist['nombre']); ?>"
                                data-costo="<?= (float)$dist['costo_delivery']; ?>"
                                data-id="<?= (int)$dist['id_distrito']; ?>">
                                <?= $escapar($dist['nombre']); ?> (S/ <?= number_format((float)$dist['costo_delivery'], 2); ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="hidden" name="id_distrito" id="checkoutIdDistrito" value="">
                        <small class="co-field-error" data-error-for="distrito"></small>
                    </div>
                    <!-- Referencia -->
                    <div class="co-form-group co-form-full">
                        <label for="checkoutReferencia">Referencia <small style="color:#94a3b8;">(opcional)</small></label>
                        <textarea name="referencia" id="checkoutReferencia" rows="3" maxlength="200" placeholder="Ej. Casa color amarilla, frente al parque..."></textarea>
                    </div>
                    <label class="co-checkbox co-form-full">
                        <input type="checkbox" name="es_principal" value="1">
                        <span class="co-checkbox-box"></span>
                        <span>
                            <strong>Guardar como direcci&oacute;n principal</strong>
                            <small>Se seleccionar&aacute; autom&aacute;ticamente en futuras compras.</small>
                        </span>
                    </label>
                </div>
            </div>
            <div class="co-modal-foot">
                <button type="button" class="co-btn-secondary" data-close-modal="modalNuevaDireccionCheckout">Cancelar</button>
                <button type="submit" class="co-btn-primary" id="btnGuardarDireccionCheckout">Guardar direcci&oacute;n</button>
            </div>
        </form>
    </div>
</div>

<!-- TOAST -->
<div class="co-toast-container" id="checkoutToastContainer"></div>

<script>
var DISTRITOS_DATA = <?= $distritosJson; ?>;

function previewVoucherImage(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('voucherPreviewImg').src = e.target.result;
            document.getElementById('voucherPreviewContainer').style.display = 'block';
            document.getElementById('voucherDropContent').style.display = 'none';
        };
        reader.readAsDataURL(input.files[0]);
    }
}
function removeVoucherImage() {
    var input = document.getElementById('captura_voucher');
    if (input) input.value = '';
    document.getElementById('voucherPreviewContainer').style.display = 'none';
    document.getElementById('voucherDropContent').style.display = 'block';
}

var currentStep = 1;

function goToStep(n) {
    document.querySelectorAll('.co-step-panel').forEach(function(p) { p.classList.remove('active'); });
    document.querySelectorAll('.co-step').forEach(function(s) { s.classList.remove('active','completed'); });
    document.querySelectorAll('.co-step-connector').forEach(function(c) { c.classList.remove('active'); });

    for (var i = 1; i <= 3; i++) {
        var el = document.getElementById('navStep' + i);
        if (!el) continue;
        if (i < n) el.classList.add('completed');
        else if (i === n) el.classList.add('active');
    }
    if (n >= 2) { var c1 = document.getElementById('connector1'); if(c1) c1.classList.add('active'); }
    if (n >= 3) { var c2 = document.getElementById('connector2'); if(c2) c2.classList.add('active'); }

    var panel = document.getElementById('panel' + n);
    if (panel) panel.classList.add('active');
    currentStep = n;

    if (n === 3) populateConfirmation();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function populateConfirmation() {
    var tipoEl = document.getElementById('checkoutTipoEntrega');
    var tipo = tipoEl ? tipoEl.value : 'delivery';

    var mEl = document.getElementById('confirmEntregaModalidad');
    var dEl = document.getElementById('confirmEntregaDireccion');
    var iEl = document.getElementById('confirmEntregaIndicaciones');

    // Horario
    var hTipo = document.getElementById('checkoutHorarioTipo');
    var hFecha = document.getElementById('checkoutFechaProgramada');
    var hTexto = 'Lo antes posible (30-45 min)';
    if (hTipo && hTipo.value === 'programado') {
        if (hFecha && hFecha.value) {
            var f = new Date(hFecha.value);
            hTexto = 'Programado: ' + f.toLocaleDateString('es-PE') + ' ' + f.toLocaleTimeString('es-PE', { hour: '2-digit', minute: '2-digit' });
        } else {
            hTexto = 'Programado para despu\u00e9s';
        }
    }

    if (mEl) mEl.innerHTML = tipo === 'delivery'
        ? '<strong>&#128666; Delivery a domicilio</strong> <span style="margin-left:8px;font-size:0.8rem;background:#f1f5f9;padding:3px 8px;border-radius:6px;color:#334155;">&#9200; ' + hTexto + '</span>'
        : '<strong>&#127968; Recojo en tienda</strong> <span style="margin-left:8px;font-size:0.8rem;background:#f1f5f9;padding:3px 8px;border-radius:6px;color:#334155;">&#9200; ' + hTexto + '</span>';

    if (dEl) {
        if (tipo === 'delivery') {
            var calle = (document.getElementById('checkoutCalle') || {}).value || '';
            var num = (document.getElementById('checkoutNumero') || {}).value || '';
            var lote = (document.getElementById('checkoutLote') || {}).value || '';
            var dist = (document.getElementById('checkoutDistrito') || {}).value || '';
            var parts = [];
            if (calle) parts.push(calle);
            if (num) parts.push('#' + num);
            if (lote) parts.push('Lote ' + lote);
            if (dist) parts.push(dist + ', Chiclayo');
            dEl.textContent = parts.length ? parts.join(', ') : 'Direcci\u00f3n en Chiclayo';
        } else {
            dEl.textContent = 'Pasar\u00e1s a recoger tu pedido en nuestro local.';
        }
    }

    var obs = document.getElementById('checkoutObservacion');
    if (iEl) iEl.textContent = obs && obs.value.trim() ? '&#128172; ' + obs.value.trim() : '';

    var pagoEl = document.getElementById('confirmMetodoPago');
    var activePayment = document.querySelector('[data-payment-option].active');
    if (pagoEl && activePayment) {
        var nm = activePayment.querySelector('strong');
        pagoEl.innerHTML = '<strong>' + (nm ? nm.textContent.trim() : 'M\u00e9todo seleccionado') + '</strong>';
    }

    ['3','4'].forEach(function(s) {
        var dc = document.getElementById('checkoutDeliveryCost');
        var dcN = document.getElementById('checkoutDeliveryCost' + s);
        if (dc && dcN) dcN.textContent = dc.textContent;
        var t = document.getElementById('checkoutTotal');
        var tN = document.getElementById('checkoutTotal' + s);
        if (t && tN) tN.textContent = t.textContent;
    });
}

document.addEventListener('DOMContentLoaded', function() {
    // Distrito select — actualizar costo y recalculate
    var selectDistrito = document.getElementById('checkoutDistrito');
    var idDistritoInput = document.getElementById('checkoutIdDistrito');
    if (selectDistrito) {
        selectDistrito.addEventListener('change', function() {
            var opt = this.options[this.selectedIndex];
            if (!opt) return;
            var costo = parseFloat(opt.dataset.costo || '5') || 5;
            var idDist = opt.dataset.id || '';
            if (idDistritoInput) idDistritoInput.value = idDist;
            
            // Actualizar preview de costo en entrega
            var displayEl = document.getElementById('costoDeliveryDisplay');
            if (displayEl) displayEl.textContent = 'S/ ' + costo.toFixed(2);

            // Actualizar costo de envío y totales en checkout
            var subtotalEl = document.getElementById('checkoutSubtotal');
            var subtotalVal = parseFloat((subtotalEl ? subtotalEl.textContent : '0').replace('S/','').replace(',','').trim()) || 0;
            var tipoEntregaEl = document.getElementById('checkoutTipoEntrega');
            var esDelivery = !tipoEntregaEl || tipoEntregaEl.value === 'delivery';
            var envioVal = esDelivery ? costo : 0;
            var totalVal = subtotalVal + envioVal;

            ['', '2', '3', '4'].forEach(function(suf) {
                var dCostEl = document.getElementById('checkoutDeliveryCost' + suf);
                var totEl = document.getElementById('checkoutTotal' + suf);
                if (dCostEl) dCostEl.textContent = 'S/ ' + envioVal.toFixed(2);
                if (totEl) totEl.textContent = 'S/ ' + totalVal.toFixed(2);
            });
            var voucherMonto = document.getElementById('voucherMontoTexto');
            if (voucherMonto) voucherMonto.textContent = 'S/ ' + totalVal.toFixed(2);
        });
    }

    // Horario de entrega toggle (Imagen 4 y 5)
    var selectHorario = document.getElementById('checkoutHorarioTipo');
    var colFecha = document.getElementById('colFechaProgramada');
    var horarioRow = document.getElementById('horarioRow');
    var fechaInput = document.getElementById('checkoutFechaProgramada');
    if (selectHorario) {
        if (fechaInput) {
            var now = new Date();
            now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
            fechaInput.min = now.toISOString().slice(0, 16);
        }
        selectHorario.addEventListener('change', function() {
            if (this.value === 'programado') {
                if (colFecha) colFecha.style.display = 'flex';
                if (horarioRow) horarioRow.classList.add('is-programado');
                if (fechaInput && !fechaInput.value) {
                    var defaultDate = new Date();
                    defaultDate.setHours(defaultDate.getHours() + 1);
                    defaultDate.setMinutes(defaultDate.getMinutes() - defaultDate.getTimezoneOffset());
                    fechaInput.value = defaultDate.toISOString().slice(0, 16);
                }
            } else {
                if (colFecha) colFecha.style.display = 'none';
                if (horarioRow) horarioRow.classList.remove('is-programado');
            }
        });
    }

    // Cargar dirección guardada si el usuario selecciona una
    var selectSaved = document.getElementById('selectDireccionGuardada');
    if (selectSaved) {
        selectSaved.addEventListener('change', function() {
            var opt = this.options[this.selectedIndex];
            if (!opt || !opt.value) return;
            var calle = document.getElementById('checkoutCalle');
            var num = document.getElementById('checkoutNumero');
            var lote = document.getElementById('checkoutLote');
            var dist = document.getElementById('checkoutDistrito');
            var ref = document.getElementById('checkoutReferencia');
            var idDir = document.getElementById('checkoutDireccionId');
            var idDist = document.getElementById('checkoutIdDistrito');

            if (calle && opt.dataset.calle) calle.value = opt.dataset.calle;
            if (num) num.value = opt.dataset.numero || '';
            if (lote) lote.value = opt.dataset.lote || '';
            if (ref) ref.value = opt.dataset.referencia || '';
            if (idDir) idDir.value = opt.value;
            if (idDist && opt.dataset.idDistrito) idDist.value = opt.dataset.idDistrito;

            if (dist && opt.dataset.distrito) {
                for (var i = 0; i < dist.options.length; i++) {
                    if (dist.options[i].value.toLowerCase() === opt.dataset.distrito.toLowerCase()) {
                        dist.selectedIndex = i;
                        dist.dispatchEvent(new Event('change'));
                        break;
                    }
                }
            }
        });
    }

    // Navegación entre pasos
    function goNext1() {
        var tipo = document.getElementById('checkoutTipoEntrega');
        var addrReq = document.getElementById('checkoutAddressRequirement');
        if (tipo && tipo.value === 'delivery') {
            var calle = document.getElementById('checkoutCalle');
            var num = document.getElementById('checkoutNumero');
            var dist = document.getElementById('checkoutDistrito');

            if (!calle || !calle.value.trim() || !num || !num.value.trim() || !dist || !dist.value.trim()) {
                if (addrReq) {
                    addrReq.textContent = 'Por favor completa la direcci\u00f3n de entrega: Calle / Avenida, N\u00famero y Distrito.';
                    addrReq.removeAttribute('hidden');
                }
                if (calle && !calle.value.trim()) calle.focus();
                else if (num && !num.value.trim()) num.focus();
                else if (dist && !dist.value.trim()) dist.focus();
                return;
            }
            if (addrReq) addrReq.setAttribute('hidden', '');
        }

        // Validar horario si es programado
        var hTipo = document.getElementById('checkoutHorarioTipo');
        var hFecha = document.getElementById('checkoutFechaProgramada');
        if (hTipo && hTipo.value === 'programado') {
            if (!hFecha || !hFecha.value) {
                alert('Por favor selecciona la fecha y hora programada.');
                if (hFecha) hFecha.focus();
                return;
            }
        }

        goToStep(2);
    }

    var b1s = document.getElementById('btnNextStep1Sidebar');
    var b2bs = document.getElementById('btnBackStep2Sidebar');
    var b2ns = document.getElementById('btnNextStep2Sidebar');
    var b3bs = document.getElementById('btnBackStep3Sidebar');

    function goNext2() {
        const metodoOption = document.querySelector('[data-payment-option].active');
        const nombreMetodo = (metodoOption && metodoOption.dataset.nombreMetodo) ? metodoOption.dataset.nombreMetodo.toLowerCase() : '';
        
        // Efectivo
        if (nombreMetodo.includes('efectivo') || nombreMetodo.includes('recibir')) {
            const totalTexto = document.getElementById('checkoutTotal2').textContent.replace('S/', '').trim();
            const total = parseFloat(totalTexto) || 0;
            const montoEf = document.getElementById('monto_efectivo');
            const val = parseFloat(montoEf ? montoEf.value : 0);
            if (isNaN(val) || val < total) {
                alert('El monto ingresado es menor al total a pagar.');
                if (montoEf) montoEf.focus();
                return;
            }
        }
        
        // Yape/Plin
        if (nombreMetodo.includes('yape') || nombreMetodo.includes('plin')) {
            const celYP = document.getElementById('celular_yape_plin');
            const codYP = document.getElementById('codigo_yape_plin');
            const voucher = document.getElementById('captura_voucher');
            const celularLimpio = celYP.value.replace(/\D/g, '');
            if (!celYP || celularLimpio.length < 9 || !celularLimpio.startsWith('9')) {
                alert('Ingresa un número de celular válido (9 dígitos). Debe empezar con 9.');
                if (celYP) celYP.focus();
                return;
            }
            if (!codYP || codYP.value.replace(/\D/g, '').length < 6) {
                alert('Ingresa el código de aprobación (mín. 6 dígitos).');
                if (codYP) codYP.focus();
                return;
            }
            if (!voucher || !voucher.files || voucher.files.length === 0) {
                alert('Por favor sube la captura de pago.');
                return;
            }
        }

        // Tarjeta
        if (nombreMetodo.includes('tarjeta') || nombreMetodo.includes('débito') || nombreMetodo.includes('crédito')) {
            const ccNum = document.getElementById('cc_numero');
            const ccExp = document.getElementById('cc_vencimiento');
            const ccCvv = document.getElementById('cc_cvv');
            const ccName = document.getElementById('cc_nombre');
            
            if (!ccNum || ccNum.value.replace(/\D/g, '').length < 14) {
                alert('Ingresa un número de tarjeta válido.');
                if (ccNum) ccNum.focus();
                return;
            }
            if (!ccExp || ccExp.value.replace(/\D/g, '').length < 4) {
                alert('Ingresa una fecha de vencimiento válida (MM/AA).');
                if (ccExp) ccExp.focus();
                return;
            }
            if (!ccCvv || ccCvv.value.replace(/\D/g, '').length < 3) {
                alert('Ingresa un CVV válido (3 dígitos).');
                if (ccCvv) ccCvv.focus();
                return;
            }
            if (!ccName || ccName.value.trim().length < 3) {
                alert('Ingresa el nombre del titular de la tarjeta.');
                if (ccName) ccName.focus();
                return;
            }
        }

        goToStep(3);
    }

    if (b1s) b1s.addEventListener('click', goNext1);
    if (b2bs) b2bs.addEventListener('click', function() { goToStep(1); });
    if (b2ns) b2ns.addEventListener('click', goNext2);
    if (b3bs) b3bs.addEventListener('click', function() { goToStep(2); });
});
</script>
<script src="Scripts/checkout.js?v=<?= time(); ?>"></script>

</body>
</html>
