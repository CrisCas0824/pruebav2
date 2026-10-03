<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


if (empty($pedido) || !is_array($pedido)) {
    header('Location: index.php?action=home_usuario');
    exit();
}

$detalle = isset($detalle) && is_array($detalle) ? $detalle : [];

$escapar = static function ($valor): string {
    return htmlspecialchars((string)($valor ?? ''), ENT_QUOTES, 'UTF-8');
};

$formatearMoneda = static function ($valor): string {
    return 'S/ ' . number_format((float)($valor ?? 0), 2, '.', ',');
};

$fechaFmt = !empty($pedido['fecha']) ? date('d/m/Y H:i', strtotime($pedido['fecha'])) : date('d/m/Y H:i');
$tipoEntrega = !empty($pedido['tipo_entrega']) ? ucfirst((string)$pedido['tipo_entrega']) : 'Delivery';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedido Confirmado — Tienda La Caserita</title>
    <link rel="icon" type="image/png" href="Images/logo.png">
    <link rel="stylesheet" href="Styles/global.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="Styles/styles-pedido_confirmado.css?v=<?= time(); ?>">
</head>
<body>
    <main class="container">
        <div style="text-align: center; margin-bottom: 24px;">
            <a href="index.php?action=home_usuario" class="brand" style="display:inline-flex; align-items:center; gap:8px; text-decoration:none; color: #1e293b;">
                <img src="Images/logo.png" alt="La Caserita" style="width:32px; height:32px; object-fit:contain; border-radius:6px;">
                <span class="brand-title" style="font-size: 1.25rem; font-weight: 800; letter-spacing: -0.3px;">La Caserita</span>
            </a>
        </div>
        <div class="success-icon-container">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                <polyline points="22 4 12 14.01 9 11.01"></polyline>
            </svg>
        </div>

        <h2>¡Pedido Confirmado!</h2>
        <p class="subtitle">Muchas gracias por tu compra. Tu pedido ha sido registrado con éxito.</p>

        <section class="info-grid">
            <div class="info-section">
                <h3>Información de tu pedido</h3>
                <div class="info-card">
                    <div class="info-item">
                        <span class="info-label">Nº Pedido:</span>
                        <span class="info-value">#<?= str_pad((int)$pedido['id_pedido'], 5, '0', STR_PAD_LEFT); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Comprobante:</span>
                        <span class="info-value"><?= $escapar($pedido['numero_comprobante'] ?? 'Generando...'); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Fecha:</span>
                        <span class="info-value"><?= $escapar($fechaFmt); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Método de pago:</span>
                        <span class="info-value"><?= $escapar($pedido['metodo_pago'] ?? 'No especificado'); ?></span>
                    </div>
                </div>
            </div>

            <div class="info-section">
                <h3>Detalles de la entrega</h3>
                <div class="info-card">
                    <div class="info-item">
                        <span class="info-label">Tipo de Entrega:</span>
                        <span class="info-value"><?= $escapar($tipoEntrega); ?></span>
                    </div>
                    <?php if ($pedido['tipo_entrega'] === 'delivery'): ?>
                        <div class="info-item">
                            <span class="info-label">Dirección:</span>
                            <span class="info-value">
                                <?= $escapar(implode(', ', array_filter([
                                    $pedido['calle'] ?? '',
                                    $pedido['distrito'] ?? '',
                                    $pedido['provincia'] ?? '',
                                    $pedido['departamento'] ?? ''
                                ]))); ?>
                            </span>
                        </div>
                        <?php if (!empty($pedido['referencia'])): ?>
                            <div class="info-item">
                                <span class="info-label">Referencia:</span>
                                <span class="info-value"><?= $escapar($pedido['referencia']); ?></span>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="info-item">
                            <span class="info-label">Lugar de recojo:</span>
                            <span class="info-value">Av. Grau #156, Chiclayo - Perú</span>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($pedido['observacion'])): ?>
                        <div class="info-item">
                            <span class="info-label">Observaciones:</span>
                            <span class="info-value">"<?= $escapar($pedido['observacion']); ?>"</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <section class="items-section">
            <h3>Productos solicitados</h3>
            <div class="items-list">
                <?php foreach ($detalle as $item): ?>
                    <div class="item-row">
                        <img class="item-image" src="<?= $escapar($item['imagen'] ?? 'Imagenes/producto-placeholder.png'); ?>" alt="<?= $escapar($item['titulo'] ?? 'Producto'); ?>" onerror="this.src='Imagenes/producto-placeholder.png'">
                        <div class="item-details">
                            <div class="item-title"><?= $escapar($item['titulo'] ?? $item['titulo_producto'] ?? 'Producto'); ?></div>
                            <div class="item-meta">
                                <?= $escapar($item['cantidad']); ?> <?= $escapar(strtolower($item['unidad_medida'] === 'KG' ? 'kg' : 'und.')); ?> x <?= $formatearMoneda($item['precio_unitario']); ?>
                            </div>
                        </div>
                        <div class="item-price-block">
                            <span class="item-subtotal"><?= $formatearMoneda($item['subtotal']); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="summary-block">
                <div class="summary-row">
                    <span class="info-label">Subtotal:</span>
                    <span class="info-value"><?= $formatearMoneda($pedido['subtotal']); ?></span>
                </div>
                <div class="summary-row">
                    <span class="info-label">Costo de envío:</span>
                    <span class="info-value"><?= $formatearMoneda($pedido['costo_envio']); ?></span>
                </div>
                <div class="summary-row total">
                    <span>Total:</span>
                    <span><?= $formatearMoneda($pedido['total']); ?></span>
                </div>
            </div>
        </section>

        <div class="actions-group">
            <a href="index.php?action=home_usuario" class="btn btn-primary">Volver a comprar</a>
            <a href="index.php?action=mi_cuenta" class="btn btn-secondary">Mis pedidos</a>
        </div>
    </main>
</body>
</html>
