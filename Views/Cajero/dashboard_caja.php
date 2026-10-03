<?php
$ventasEfectivo = (float)($resumen['ventas_efectivo'] ?? 0);
$ventasDigital = (float)(($resumen['ventas_yape'] ?? 0) + ($resumen['ventas_plin'] ?? 0));
$ventasTarjeta = (float)($resumen['ventas_tarjeta'] ?? 0);
$totalMetodos = $totalVentas > 0 ? $totalVentas : 1;
$porcEfectivo = round(($ventasEfectivo / $totalMetodos) * 100);
$porcDigital = round(($ventasDigital / $totalMetodos) * 100);
$porcTarjeta = round(($ventasTarjeta / $totalMetodos) * 100);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard de caja - La Caserita</title>
    <link rel="stylesheet" href="Styles/global.css">
    <link rel="stylesheet" href="Styles/staff-panel.css?v=<?= time() ?>">
    <link rel="stylesheet" href="Styles/Vistas/Cajero/dashboard_caja.css?v=<?= time() ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="staff-app cashier-app">
    <?php $paginaPanelStaff = 'caja'; include __DIR__ . '/../Staff/partials/staff_sidebar.php'; ?>
    <div class="staff-main">
        <header class="staff-topbar">
            <div class="staff-topbar-left">
                <button type="button" class="staff-menu-toggle hide-on-desktop" id="staffMenuToggle" aria-label="Abrir menú">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
                </button>
                <span class="staff-topbar-title">Panel de caja</span>
            </div>
            <div class="staff-topbar-actions">
                <a href="index.php?action=home_usuario" class="topbar-store-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                    Volver a Tienda
                </a>
            </div>
        </header>

        <main class="staff-content">
            <?php if ($flashSuccess): ?><div class="staff-flash success"><?= htmlspecialchars($flashSuccess) ?></div><?php endif; ?>
            <?php if ($flashError): ?><div class="staff-flash error"><?= htmlspecialchars($flashError) ?></div><?php endif; ?>

            <!-- Cabecera del Dashboard -->
            <section class="cashier-dashboard-heading">
                <div>
                    <span class="staff-section-kicker">PANEL GENERAL</span>
                    <h1>Dashboard de caja</h1>
                    <p><?= htmlspecialchars($nombre) ?> · Cajero</p>
                </div>
                <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
                    <?php if ($sesionActiva): ?>
                        <a class="cashier-open-pos" href="index.php?action=pos"><i class="fa-solid fa-cash-register"></i> Abrir Caja POS (Vender)</a>
                        <a class="cashier-open-pos secondary-btn" href="index.php?action=arqueo_caja"><i class="fa-solid fa-scale-balanced"></i> Arqueo de caja</a>
                    <?php else: ?>
                        <span class="cashier-pos-blocked">
                            <i class="fa-solid fa-lock"></i> Caja POS Bloqueada (Apertura requerida)
                        </span>
                        <a class="cashier-open-pos" href="index.php?action=arqueo_caja"><i class="fa-solid fa-unlock-keyhole"></i> Aperturar Turno</a>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Banner Estado de Caja -->
            <?php if ($sesionActiva): ?>
                <section class="cashier-session-banner">
                    <div>
                        <span class="cashier-live"><i></i> CAJA ABIERTA</span>
                        <h2><?= htmlspecialchars($sesionActiva['caja_id']) ?></h2>
                        <p>Turno iniciado <?= date('d/m/Y H:i', strtotime($sesionActiva['fecha_apertura'])) ?> · Fondo inicial S/ <?= number_format((float)$sesionActiva['monto_apertura'], 2) ?></p>
                    </div>
                    <div class="cashier-expected">
                        <span>EFECTIVO ESPERADO EN GAVETA</span>
                        <strong>S/ <?= number_format((float)($resumen['efectivo_esperado'] ?? 0), 2) ?></strong>
                    </div>
                </section>
            <?php else: ?>
                <section class="cashier-closed-banner">
                    <div class="closed-banner-icon"><i class="fa-solid fa-store-slash"></i></div>
                    <div>
                        <span class="closed-tag">TURNO CERRADO</span>
                        <h3>No hay ningún turno de caja abierto</h3>
                        <p>Para poder procesar ventas en Caja POS y recibir pagos, primero debes registrar el fondo inicial en el módulo de Arqueo.</p>
                    </div>
                    <a href="index.php?action=arqueo_caja" class="btn-goto-arqueo"><i class="fa-solid fa-unlock"></i> Ir a Aperturar Caja</a>
                </section>
            <?php endif; ?>

            <!-- 4 Tarjetas KPI Ejecutivas -->
            <section class="cashier-kpis-grid">
                <article class="kpi-card">
                    <span class="kpi-title">TOTAL VENTAS TURNO</span>
                    <strong class="kpi-val" style="color:#15803d;">S/ <?= number_format($totalVentas, 2) ?></strong>
                    <span class="kpi-foot"><?= $totalTickets ?> comprobantes emitidos</span>
                </article>
                <article class="kpi-card">
                    <span class="kpi-title">TICKETS EMITIDOS</span>
                    <strong class="kpi-val"><?= $totalTickets ?></strong>
                    <span class="kpi-foot">Transacciones procesadas</span>
                </article>
                <article class="kpi-card">
                    <span class="kpi-title">TICKET PROMEDIO</span>
                    <strong class="kpi-val" style="color:#0284c7;">S/ <?= number_format($ticketPromedio, 2) ?></strong>
                    <span class="kpi-foot">Promedio por cliente</span>
                </article>
                <article class="kpi-card">
                    <span class="kpi-title">EFECTIVO EN GAVETA</span>
                    <strong class="kpi-val" style="color:#b45309;">S/ <?= number_format((float)($resumen['efectivo_esperado'] ?? 0), 2) ?></strong>
                    <span class="kpi-foot">Fondo inicial + ventas efectivo</span>
                </article>
            </section>

            <!-- Sección de Desglose de Pagos y Accesos -->
            <div class="cashier-dashboard-row">
                <!-- Desglose por Método de Pago -->
                <section class="dashboard-panel">
                    <div class="panel-header">
                        <h2><i class="fa-solid fa-chart-pie"></i> Ventas por Método de Pago</h2>
                        <span>Turno actual</span>
                    </div>
                    <div class="payments-breakdown">
                        <div class="payment-method-item">
                            <div class="pm-info">
                                <span class="pm-name"><i class="fa-solid fa-money-bill-wave" style="color:#15803d;"></i> Efectivo</span>
                                <strong class="pm-amount">S/ <?= number_format($ventasEfectivo, 2) ?> (<?= $porcEfectivo ?>%)</strong>
                            </div>
                            <div class="pm-bar-bg"><div class="pm-bar" style="width:<?= $porcEfectivo ?>%; background:#15803d;"></div></div>
                        </div>

                        <div class="payment-method-item">
                            <div class="pm-info">
                                <span class="pm-name"><i class="fa-solid fa-qrcode" style="color:#0284c7;"></i> Yape / Plin / Billeteras</span>
                                <strong class="pm-amount">S/ <?= number_format($ventasDigital, 2) ?> (<?= $porcDigital ?>%)</strong>
                            </div>
                            <div class="pm-bar-bg"><div class="pm-bar" style="width:<?= $porcDigital ?>%; background:#0284c7;"></div></div>
                        </div>

                        <div class="payment-method-item">
                            <div class="pm-info">
                                <span class="pm-name"><i class="fa-solid fa-credit-card" style="color:#8b5cf6;"></i> Tarjeta (Débito/Crédito)</span>
                                <strong class="pm-amount">S/ <?= number_format($ventasTarjeta, 2) ?> (<?= $porcTarjeta ?>%)</strong>
                            </div>
                            <div class="pm-bar-bg"><div class="pm-bar" style="width:<?= $porcTarjeta ?>%; background:#8b5cf6;"></div></div>
                        </div>

                        <div class="movement-quick-summary">
                            <div class="mqs-item">
                                <span>Ingresos Manuales:</span>
                                <strong>S/ <?= number_format((float)($resumen['ingresos_extra'] ?? 0), 2) ?></strong>
                            </div>
                            <div class="mqs-item">
                                <span>Gastos / Egresos:</span>
                                <strong style="color:#dc2626;">S/ <?= number_format((float)($resumen['egresos'] ?? 0), 2) ?></strong>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Accesos Rápidos del Cajero -->
                <section class="dashboard-panel">
                    <div class="panel-header">
                        <h2><i class="fa-solid fa-bolt"></i> Accesos Directos</h2>
                        <span>Operaciones</span>
                    </div>
                    <div class="quick-links-grid">
                        <a href="index.php?action=pos" class="quick-link-box">
                            <div class="qlb-icon" style="background:#dcfce7; color:#15803d;"><i class="fa-solid fa-cash-register"></i></div>
                            <div>
                                <strong>Caja POS (Terminal de Venta)</strong>
                                <p>Escanear productos, cobrar y emitir tickets</p>
                            </div>
                        </a>
                        <a href="index.php?action=arqueo_caja" class="quick-link-box">
                            <div class="qlb-icon" style="background:#fef3c7; color:#b45309;"><i class="fa-solid fa-scale-balanced"></i></div>
                            <div>
                                <strong>Arqueo y Control de Efectivo</strong>
                                <p>Apertura, cuadre de caja, egresos y cierre</p>
                            </div>
                        </a>
                        <a href="index.php?action=historial_pos" class="quick-link-box">
                            <div class="qlb-icon" style="background:#f1f5f9; color:#475569;"><i class="fa-solid fa-clock-rotate-left"></i></div>
                            <div>
                                <strong>Historial de Ventas POS</strong>
                                <p>Consultar tickets y reimprimir comprobantes</p>
                            </div>
                        </a>
                    </div>
                </section>
            </div>

            <!-- Tabla de Últimas Ventas del Turno -->
            <section class="cashier-movements-panel">
                <div class="cashier-section-title">
                    <h2><i class="fa-solid fa-receipt" style="color:#15803d; margin-right:8px;"></i> Últimas Ventas del Turno</h2>
                    <a href="index.php?action=historial_pos" style="color:#15803d; text-decoration:none; font-size:0.85rem; font-weight:700;">Ver historial completo &rarr;</a>
                </div>
                <div class="cashier-table-scroll">
                    <table>
                        <thead>
                            <tr>
                                <th>TICKET</th>
                                <th>FECHA Y HORA</th>
                                <th>CLIENTE</th>
                                <th>MÉTODO</th>
                                <th class="cashier-align-right">TOTAL</th>
                                <th style="text-align:center; width:100px;">ACCIÓN</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$historialVentas): ?>
                                <tr>
                                    <td colspan="6" class="cashier-no-data">Aún no se han emitido tickets en este turno.</td>
                                </tr>
                            <?php else: foreach ($historialVentas as $idx => $v): ?>
                                <tr>
                                    <td style="font-weight:700; color:#0f172a;"><?= htmlspecialchars($v['codigo_pedido'] ?: ('POS-' . $v['id_pedido'])) ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($v['fecha'])) ?></td>
                                    <td><?= htmlspecialchars($v['cliente_nombre'] ?: ($v['cliente_dni'] ?: 'Público General')) ?></td>
                                    <td>
                                        <span class="badge-metodo"><?= htmlspecialchars($v['metodo_pago'] ?: 'Efectivo') ?></span>
                                    </td>
                                    <td class="cashier-align-right" style="font-weight:800; color:#15803d;">
                                        S/ <?= number_format((float)$v['total'], 2) ?>
                                    </td>
                                    <td>
                                        <div style="display:flex; gap:6px; align-items:center; justify-content:center;">
                                            <button type="button" 
                                                    class="btn-action-history btn-preview" 
                                                    onclick="previsualizarTicket(<?= $idx ?>)" 
                                                    title="Previsualizar comprobante">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                    <circle cx="12" cy="12" r="3"></circle>
                                                </svg>
                                            </button>
                                            <button type="button" 
                                                    class="btn-action-history btn-print" 
                                                    onclick="imprimirTicketDirecto(<?= $idx ?>)" 
                                                    title="Imprimir ticket térmico">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <polyline points="6 9 6 2 18 2 18 9"></polyline>
                                                    <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                                                    <rect x="6" y="14" width="12" height="8"></rect>
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>

    <!-- MODAL PREVISUALIZACIÓN DE TICKET TÉRMICO -->
    <div id="modalTicketPreview" class="ticket-modal-overlay" style="display:none;" onclick="cerrarTicketSiFondo(event)">
        <div class="ticket-modal-content">
            <div class="ticket-modal-header ticket-no-print">
                <h3 style="margin:0; font-size:1.02rem; color:#0f172a;"><i class="fa-solid fa-receipt" style="color:#15803d; margin-right:6px;"></i> Comprobante de Venta</h3>
                <button type="button" onclick="cerrarTicketPreview()" class="ticket-modal-close" aria-label="Cerrar modal">&times;</button>
            </div>

            <div class="ticket-modal-body">
                <div id="ticketTermicoImprimible" class="ticket-thermal-box">
                    <div class="ticket-center">
                        <strong class="ticket-brand">TIENDA "LA CASERITA"</strong>
                        <div class="ticket-meta">R.U.C.: 20512345678</div>
                        <div class="ticket-meta">Av. Las Flores 123 - Chiclayo, Lambayeque</div>
                        <div class="ticket-meta">Teléfono: (074) 456-7890</div>
                        <div class="ticket-dashed"></div>
                        <div class="ticket-doc-type">BOLETA DE VENTA ELECTRÓNICA</div>
                        <div class="ticket-code" id="lblDocCodigo">POS-00000000-00</div>
                    </div>

                    <div class="ticket-data-grid">
                        <div><strong>Fecha:</strong> <span id="lblDocFecha">--</span></div>
                        <div><strong>Cajero:</strong> <span id="lblDocCajero">--</span></div>
                        <div><strong>Cliente:</strong> <span id="lblDocCliente">PÚBLICO GENERAL</span></div>
                        <div><strong>DNI / Doc:</strong> <span id="lblDocDni">--</span></div>
                    </div>

                    <div class="ticket-dashed"></div>

                    <table class="ticket-items-table">
                        <thead>
                            <tr>
                                <th style="width:14%;">CANT</th>
                                <th style="text-align:left;">DESCRIPCIÓN</th>
                                <th style="text-align:right; width:22%;">P.U</th>
                                <th style="text-align:right; width:24%;">TOTAL</th>
                            </tr>
                        </thead>
                        <tbody id="lblDocItems"></tbody>
                    </table>

                    <div class="ticket-dashed"></div>

                    <div class="ticket-totals">
                        <div class="tt-row"><span>OP. GRAVADA:</span><strong id="lblDocSubtotal">S/ 0.00</strong></div>
                        <div class="tt-row"><span>I.G.V. (18%):</span><strong id="lblDocIgv">S/ 0.00</strong></div>
                        <div class="tt-row tt-total"><span>TOTAL A PAGAR:</span><strong id="lblDocTotal">S/ 0.00</strong></div>
                    </div>

                    <div class="ticket-dashed"></div>

                    <div class="ticket-payment-info">
                        <div><strong>FORMA DE PAGO:</strong> <span id="lblDocMetodo">EFECTIVO</span></div>
                        <div id="rowDocEfectivo" style="display:none; margin-top:3px;">
                            <div><span>MONTO RECIBIDO:</span> <strong id="lblDocRecibido">S/ 0.00</strong></div>
                            <div><span>VUELTO:</span> <strong id="lblDocVuelto">S/ 0.00</strong></div>
                        </div>
                    </div>

                    <div class="ticket-dashed"></div>

                    <div class="ticket-center ticket-footer">
                        <p>¡GRACIAS POR SU PREFERENCIA!</p>
                        <p>Conserve este ticket para cualquier cambio o reclamo.</p>
                        <div class="ticket-barcode">||| | ||||| || |||| |||||</div>
                    </div>
                </div>
            </div>

            <div class="ticket-modal-actions ticket-no-print">
                <button type="button" class="btn-ticket-print" onclick="ejecutarImpresion()">
                    <i class="fa-solid fa-print"></i> IMPRIMIR TICKET
                </button>
                <button type="button" class="btn-ticket-close" onclick="cerrarTicketPreview()">
                    Cerrar
                </button>
            </div>
        </div>
    </div>

    <script>
        const ventasData = <?= json_encode($historialVentas ?? []) ?>;

        function cargarDatosTicket(idx) {
            const v = ventasData[idx];
            if (!v) return;

            const codigo = v.codigo_pedido || ('POS-' + v.id_pedido);
            const total = parseFloat(v.total || 0);
            const subtotal = total / 1.18;
            const igv = total - subtotal;
            const fecha = v.fecha ? new Date(v.fecha.replace(/-/g, '/')).toLocaleString('es-PE', { dateStyle: 'short', timeStyle: 'short' }) : '--';
            const cajero = (v.cajero_nombre ? (v.cajero_nombre + ' ' + (v.cajero_apellido || '')) : '<?= htmlspecialchars($nombre) ?>').trim();
            const cliente = (v.cliente_nombre || 'PÚBLICO GENERAL').toUpperCase();
            const dni = v.cliente_dni || '--';
            const metodo = (v.metodo_pago || 'EFECTIVO').toUpperCase().replace('_', ' ');

            document.getElementById('lblDocCodigo').textContent = codigo;
            document.getElementById('lblDocFecha').textContent = fecha;
            document.getElementById('lblDocCajero').textContent = cajero;
            document.getElementById('lblDocCliente').textContent = cliente;
            document.getElementById('lblDocDni').textContent = dni;
            document.getElementById('lblDocMetodo').textContent = metodo;

            document.getElementById('lblDocSubtotal').textContent = 'S/ ' + subtotal.toFixed(2);
            document.getElementById('lblDocIgv').textContent = 'S/ ' + igv.toFixed(2);
            document.getElementById('lblDocTotal').textContent = 'S/ ' + total.toFixed(2);

            const rowEfectivo = document.getElementById('rowDocEfectivo');
            const recibido = parseFloat(v.monto_recibido || 0);
            const vuelto = parseFloat(v.vuelto || 0);
            if (metodo.includes('EFECTIVO') && recibido > 0) {
                document.getElementById('lblDocRecibido').textContent = 'S/ ' + recibido.toFixed(2);
                document.getElementById('lblDocVuelto').textContent = 'S/ ' + vuelto.toFixed(2);
                rowEfectivo.style.display = 'block';
            } else {
                rowEfectivo.style.display = 'none';
            }

            const tbody = document.getElementById('lblDocItems');
            tbody.innerHTML = '';
            if (v.items && v.items.length > 0) {
                v.items.forEach(it => {
                    const cant = it.cantidad;
                    const nom = it.producto_nombre || 'Producto';
                    const pu = parseFloat(it.precio_unitario || 0).toFixed(2);
                    const sub = parseFloat(it.subtotal || (cant * pu)).toFixed(2);
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td style="text-align:center;">${cant}</td>
                        <td style="text-align:left;">${nom}</td>
                        <td style="text-align:right;">S/ ${pu}</td>
                        <td style="text-align:right; font-weight:bold;">S/ ${sub}</td>
                    `;
                    tbody.appendChild(tr);
                });
            } else {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td style="text-align:center;">1</td>
                    <td style="text-align:left;">Venta Mostrador POS</td>
                    <td style="text-align:right;">S/ ${total.toFixed(2)}</td>
                    <td style="text-align:right; font-weight:bold;">S/ ${total.toFixed(2)}</td>
                `;
                tbody.appendChild(tr);
            }
        }

        function previsualizarTicket(idx) {
            cargarDatosTicket(idx);
            document.getElementById('modalTicketPreview').style.display = 'flex';
        }

        function imprimirTicketDirecto(idx) {
            cargarDatosTicket(idx);
            ejecutarImpresion();
        }

        function ejecutarImpresion() {
            const ticketElement = document.getElementById('ticketTermicoImprimible');
            if (!ticketElement) {
                window.print();
                return;
            }

            let iframe = document.getElementById('dashboardThermalPrintFrame');
            if (!iframe) {
                iframe = document.createElement('iframe');
                iframe.id = 'dashboardThermalPrintFrame';
                iframe.style.position = 'fixed';
                iframe.style.right = '0';
                iframe.style.bottom = '0';
                iframe.style.width = '0';
                iframe.style.height = '0';
                iframe.style.border = '0';
                iframe.style.visibility = 'hidden';
                document.body.appendChild(iframe);
            }

            const doc = iframe.contentWindow.document;
            doc.open();
            doc.write(`<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Imprimir Comprobante</title>
    <link rel="stylesheet" href="Styles/Vistas/Cajero/dashboard_caja.css?v=<?= time(); ?>">
</head>
<body>
    <div class="ticket-thermal-box">
        ${ticketElement.innerHTML}
    </div>
</body>
</html>`);
            doc.close();

            setTimeout(() => {
                try {
                    iframe.contentWindow.focus();
                    iframe.contentWindow.print();
                } catch (e) {
                    console.error('Error al imprimir ticket:', e);
                    window.print();
                }
            }, 200);
        }

        function cerrarTicketPreview() {
            document.getElementById('modalTicketPreview').style.display = 'none';
        }

        function cerrarTicketSiFondo(e) {
            if (e.target.id === 'modalTicketPreview') {
                cerrarTicketPreview();
            }
        }
    </script>

    
</body>
</html>
