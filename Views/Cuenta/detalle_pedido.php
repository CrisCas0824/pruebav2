<?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['usuario_id'])) {
        header('Location: index.php?action=login');
        exit();
    }

    $pedido  = isset($pedido)  && is_array($pedido)  ? $pedido  : [];
    $detalle = isset($detalle) && is_array($detalle) ? $detalle : [];

    function escaparD($valor) {
        return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
    }

    function textoEstadoD($estado) {
        $textos = [
            'pendiente'  => 'Pendiente',
            'confirmado' => 'Confirmado',
            'en_camino'  => 'En camino',
            'entregado'  => 'Entregado',
            'cancelado'  => 'Cancelado',
        ];
        return $textos[$estado] ?? ucfirst(str_replace('_', ' ', $estado));
    }

    function claseEstadoD($estado) {
        $clases = [
            'pendiente'  => 'pedido-estado-pendiente',
            'confirmado' => 'pedido-estado-confirmado',
            'en_camino'  => 'pedido-estado-camino',
            'entregado'  => 'pedido-estado-entregado',
            'cancelado'  => 'pedido-estado-cancelado',
        ];
        return $clases[$estado] ?? 'pedido-estado-pendiente';
    }

    function fechaDetalleD($fecha) {
        if (empty($fecha)) return 'Sin fecha';
        $ts = strtotime($fecha);
        return $ts ? date('d/m/Y H:i', $ts) : escaparD($fecha);
    }

    $idPedido     = (int)($pedido['id_pedido'] ?? 0);
    $estado       = $pedido['estado'] ?? 'pendiente';
    $tipoEntrega  = $pedido['tipo_entrega'] ?? 'delivery';
    $metodoPago   = $pedido['metodo_pago'] ?? 'No especificado';
    $total        = (float)($pedido['total'] ?? 0);
    $costoEnvio   = (float)($pedido['costo_envio'] ?? 0);
    $subtotal     = (float)($pedido['subtotal'] ?? ($total - $costoEnvio));
    $codigoPedido = $pedido['codigo_pedido'] ?? '';
    $horarioEntrega = $pedido['horario_entrega'] ?? '';
    $fechaProgramada = $pedido['fecha_programada'] ?? '';
    $observacion  = $pedido['observacion'] ?? '';
    $fecha        = $pedido['fecha'] ?? '';

    
    $depto    = $pedido['departamento'] ?? '';
    $prov     = $pedido['provincia'] ?? '';
    $dist     = $pedido['distrito'] ?? '';
    $calle    = $pedido['calle'] ?? '';
    $refencia = $pedido['referencia'] ?? '';
    $tieneDir = $depto || $prov || $dist || $calle;
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedido #<?= str_pad($idPedido, 5, '0', STR_PAD_LEFT); ?> — La Caserita</title>
    <link rel="icon" type="image/png" href="Images/logo.png">
    <link rel="stylesheet" href="Styles/global.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="Styles/styles-cuenta.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="Styles/Vistas/Cuenta/detalle_pedido.css?v=<?= time(); ?>">
</head>

<body>
<div class="detalle-page">

    
    <header class="cuenta-header">
        <div class="cuenta-header-container">
            <div class="cuenta-header-left">
                <a href="index.php?action=home_usuario" class="cuenta-brand" title="Volver a la tienda">
                    <div class="cuenta-brand-icon">
                        <img src="Images/logo_backup.png" alt="La Caserita" onerror="this.onerror=null;this.src='Images/logo.png';">
                    </div>
                    <span class="cuenta-brand-name">La Caserita</span>
                </a>
            </div>
            
            <div class="cuenta-header-center">
                <h1 class="cuenta-header-title" style="color:#fff;">Detalle de Pedido</h1>
            </div>

            <div class="cuenta-header-right"></div>
        </div>
    </header>

    <div style="max-width: 680px; margin: 20px auto 0; padding: 0 16px;">
        <a href="index.php?action=mi_cuenta" class="link-volver">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
            Volver a Mi Cuenta
        </a>
    </div>

    <div class="detalle-main">

        
        <div class="detalle-card" style="margin-top: 0;">
            <div class="detalle-status-banner">
                <div class="detalle-status-info">
                    <h2>Estado del pedido</h2>
                    <p>Sigue el avance de tu compra en tiempo real.</p>
                </div>
                <span class="<?= claseEstadoD($estado); ?>">
                    <?= escaparD(textoEstadoD($estado)); ?>
                </span>
            </div>

            <div class="detalle-card-body">
                <div class="detalle-summary-grid">
                    <div class="detalle-summary-item">
                        <small>Número de pedido</small>
                        <strong>#<?= str_pad($idPedido, 5, '0', STR_PAD_LEFT); ?></strong>
                    </div>
                    <?php if (!empty($codigoPedido)): ?>
                    <div class="detalle-summary-item">
                        <small>Código</small>
                        <strong><?= escaparD($codigoPedido); ?></strong>
                    </div>
                    <?php endif; ?>
                    <div class="detalle-summary-item">
                        <small>Tipo de entrega</small>
                        <strong><?= $tipoEntrega === 'delivery' ? '🛵 Delivery' : '🏪 Recojo en tienda'; ?></strong>
                    </div>
                    <div class="detalle-summary-item">
                        <small>Método de pago</small>
                        <strong><?= escaparD($metodoPago); ?></strong>
                    </div>
                    <div class="detalle-summary-item">
                        <small>Fecha del pedido</small>
                        <strong><?= escaparD(fechaDetalleD($fecha)); ?></strong>
                    </div>
                    <?php if (!empty($horarioEntrega) || !empty($fechaProgramada)): ?>
                    <div class="detalle-summary-item">
                        <small>Entrega programada</small>
                        <strong><?= escaparD(trim(($horarioEntrega ? $horarioEntrega . ' · ' : '') . ($fechaProgramada ? fechaDetalleD($fechaProgramada) : ''))); ?></strong>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($observacion)): ?>
                    <div class="detalle-summary-item" style="grid-column: 1 / -1;">
                        <small>Observaciones</small>
                        <strong><?= escaparD($observacion); ?></strong>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        
        <?php if ($tipoEntrega === 'delivery' && $tieneDir): ?>
        <div class="detalle-card">
            <div class="detalle-card-header">
                <div class="card-icon">📍</div>
                <h3>Dirección de entrega</h3>
            </div>
            <div class="detalle-card-body">
                <p style="margin:0; color:#334155; font-size:0.95rem;">
                    <?php
                        $partes = array_filter([$calle, $dist, $prov, $depto]);
                        echo escaparD(implode(', ', $partes));
                    ?>
                </p>
                <?php if (!empty($refencia)): ?>
                <p style="margin:8px 0 0; color:#64748b; font-size:0.85rem;">
                    Referencia: <?= escaparD($refencia); ?>
                </p>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        
        <div class="detalle-card">
            <div class="detalle-card-header">
                <div class="card-icon">🛍️</div>
                <h3>Productos del pedido (<?= count($detalle); ?> ítem<?= count($detalle) !== 1 ? 's' : ''; ?>)</h3>
            </div>
            <div class="detalle-card-body">
                <?php if (empty($detalle)): ?>
                    <p style="text-align:center; color:#94a3b8; padding:20px 0;">
                        No se encontraron productos en este pedido.
                    </p>
                <?php else: ?>
                <div class="detalle-product-list">
                    <?php foreach ($detalle as $item): ?>
                        <?php
                            $img         = !empty($item['imagen']) ? htmlspecialchars($item['imagen']) : 'Imagenes/sin-imagen.png';
                            $isUrl       = (strpos($img, 'http://') === 0) || (strpos($img, 'https://') === 0);
                            $imgSrc      = $isUrl ? $img : $img;
                            $qty         = (int)($item['cantidad'] ?? 1);
                            $precioUnit  = (float)($item['precio_unitario'] ?? 0);
                            $subtotalItem= (float)($item['subtotal'] ?? ($qty * $precioUnit));
                            $unidad      = $item['unidad_medida'] ?? 'unid.';
                        ?>
                        <div class="detalle-product-item">
                            <img
                                src="<?= $imgSrc; ?>"
                                alt="<?= escaparD($item['titulo'] ?? ''); ?>"
                                class="detalle-product-img"
                                onerror="this.src='Imagenes/sin-imagen.png'">

                            <div class="detalle-product-info">
                                <h4><?= escaparD($item['titulo'] ?? 'Producto'); ?></h4>
                                <p><?= $qty; ?> <?= escaparD($unidad); ?> × S/ <?= number_format($precioUnit, 2); ?></p>
                            </div>

                            <div class="detalle-product-subtotal">
                                <strong>S/ <?= number_format($subtotalItem, 2); ?></strong>
                                <span>subtotal</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                
                <div class="detalle-totals">
                    <div class="detalle-total-row">
                        <span>Subtotal de productos</span>
                        <span>S/ <?= number_format($subtotal, 2); ?></span>
                    </div>
                    <?php if ($tipoEntrega === 'delivery'): ?>
                    <div class="detalle-total-row">
                        <span>Costo de envío</span>
                        <span><?= $costoEnvio > 0 ? 'S/ ' . number_format($costoEnvio, 2) : 'Gratis'; ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="detalle-total-row total-final">
                        <span>Total pagado</span>
                        <span>S/ <?= number_format($total, 2); ?></span>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        
        <div style="display:flex; gap:12px; justify-content:flex-end; flex-wrap:wrap; margin-top:8px;">
            <a href="index.php?action=mi_cuenta" style="
                padding: 12px 24px;
                border-radius: 10px;
                background: var(--lc-primary, #15803D);
                color: #ffffff;
                font-weight: 700;
                font-size: 0.9rem;
                text-decoration: none;
                transition: all 0.2s;
            ">
                Ver Mis Pedidos →
            </a>
        </div>

    </div>
</div>
</body>
</html>

