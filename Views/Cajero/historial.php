<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de ventas - La Caserita</title>
    <link rel="stylesheet" href="Styles/global.css">
    <link rel="stylesheet" href="Styles/staff-panel.css?v=<?= time() ?>">
    <link rel="stylesheet" href="Styles/Vistas/Cajero/historial.css?v=<?= time() ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="staff-app cashier-history-page">
    <?php $paginaPanelStaff = 'historial'; include __DIR__ . '/../Staff/partials/staff_sidebar.php'; ?>
    <div class="staff-main">
        <header class="staff-topbar">
            <div class="staff-topbar-left">
                <button type="button" class="staff-menu-toggle hide-on-desktop" id="staffMenuToggle" aria-label="Abrir menú">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
                </button>
                <span class="staff-topbar-title">Historial de ventas</span>
            </div>
            <div class="staff-topbar-actions">
                <a href="index.php?action=home_usuario" class="topbar-store-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                    Volver a Tienda
                </a>
            </div>
        </header>

        <main class="staff-content">
            <div class="cashier-history">
                <header class="cashier-history-heading">
                    <div>
                        <span class="staff-section-kicker">CAJA POS</span>
                        <h1>Historial de ventas</h1>
                        <p><?= htmlspecialchars($nombre) ?> · Comprobantes emitidos en terminal POS</p>
                    </div>
                    <div style="display:flex; gap:10px; align-items:center;">
                        <a href="index.php?action=pos" class="cashier-return-pos"><i class="fa-solid fa-cash-register"></i> Volver a caja</a>
                    </div>
                </header>

                <!-- Tarjetas de Resumen Rápido -->
                <section class="history-metrics-grid">
                    <article class="h-metric-card">
                        <span class="h-metric-label">TOTAL FILTRADO</span>
                        <strong class="h-metric-val" id="metricTotalFiltrado" style="color:#15803d;">S/ 0.00</strong>
                        <span class="h-metric-foot" id="metricCantidadTickets">0 comprobantes</span>
                    </article>
                    <article class="h-metric-card">
                        <span class="h-metric-label">TOTAL EN EFECTIVO</span>
                        <strong class="h-metric-val" id="metricTotalEfectivo" style="color:#0f172a;">S/ 0.00</strong>
                        <span class="h-metric-foot">Cobrado en gaveta</span>
                    </article>
                    <article class="h-metric-card">
                        <span class="h-metric-label">DIGITAL Y TARJETAS</span>
                        <strong class="h-metric-val" id="metricTotalDigital" style="color:#0284c7;">S/ 0.00</strong>
                        <span class="h-metric-foot">Yape, Plin y tarjetas</span>
                    </article>
                </section>

                <!-- Barra de Filtros -->
                <section class="history-filters-bar">
                    <div class="search-input-wrap">
                        <i class="fa-solid fa-magnifying-glass search-icon"></i>
                        <input type="text" id="filtroTexto" placeholder="Buscar por código, cliente o DNI..." oninput="filtrarVentas()">
                    </div>

                    <div class="filter-group">
                        <label for="filtroMetodo" class="filter-label">Método:</label>
                        <select id="filtroMetodo" onchange="filtrarVentas()">
                            <option value="">Todos los métodos</option>
                            <option value="efectivo">Efectivo</option>
                            <option value="yape">Yape</option>
                            <option value="plin">Plin</option>
                            <option value="tarjeta">Tarjeta</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="filtroRangoFecha" class="filter-label">Fecha:</label>
                        <select id="filtroRangoFecha" onchange="cambiarRangoFecha()">
                            <option value="todos">Todo el historial</option>
                            <option value="hoy">Hoy</option>
                            <option value="7dias">Últimos 7 días</option>
                            <option value="custom">Personalizado...</option>
                        </select>
                    </div>

                    <div class="filter-group custom-date-wrap" id="customDateWrap" style="display:none;">
                        <input type="date" id="filtroFecha" onchange="filtrarVentas()">
                    </div>

                    <button type="button" class="btn-reset-filters" onclick="resetFiltros()" title="Restablecer filtros">
                        <i class="fa-solid fa-rotate-left"></i> Limpiar
                    </button>
                </section>

                <!-- Tabla de Historial -->
                <section class="cashier-history-table-wrap" aria-label="Ventas de este cajero">
                    <table class="cashier-history-table" id="tablaHistorial">
                        <thead>
                            <tr>
                                <th>FECHA Y HORA</th>
                                <th>COMPROBANTE</th>
                                <th>CLIENTE / DNI</th>
                                <th>FORMA DE PAGO</th>
                                <th class="amount">TOTAL</th>
                                <th style="text-align:center; width:110px;">ACCIÓN</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyHistorial">
                            <?php if (!$ventas): ?>
                                <tr id="trSinDatos">
                                    <td class="cashier-history-empty" colspan="6">
                                        <i class="fa-solid fa-receipt" style="font-size:2rem; color:#cbd5e1; margin-bottom:8px; display:block;"></i>
                                        Todavía no tienes ventas registradas.
                                    </td>
                                </tr>
                            <?php else: foreach ($ventas as $index => $venta): 
                                $metodoRaw = strtolower(trim($venta['metodo_pago'] ?: 'efectivo'));
                                $totalNum = (float)$venta['total'];
                                $fechaStr = date('Y-m-d', strtotime($venta['fecha']));
                                $horaStr = date('d/m/Y H:i', strtotime($venta['fecha']));
                                $codigo = $venta['codigo_pedido'] ?: ('POS-' . $venta['id_pedido']);
                                $clienteNom = $venta['cliente_nombre'] ?: 'Público General';
                                $clienteDni = $venta['cliente_dni'] ?: '';
                            ?>
                                <tr class="fila-venta" 
                                    data-index="<?= $index ?>"
                                    data-codigo="<?= htmlspecialchars(strtolower($codigo)) ?>"
                                    data-cliente="<?= htmlspecialchars(strtolower($clienteNom)) ?>"
                                    data-dni="<?= htmlspecialchars($clienteDni) ?>"
                                    data-metodo="<?= htmlspecialchars($metodoRaw) ?>"
                                    data-fecha="<?= $fechaStr ?>"
                                    data-total="<?= $totalNum ?>">
                                    <td><?= htmlspecialchars($horaStr) ?></td>
                                    <td><strong style="color:#0f172a;"><?= htmlspecialchars($codigo) ?></strong></td>
                                    <td>
                                        <strong style="color:#1e293b;"><?= htmlspecialchars($clienteNom) ?></strong>
                                        <?php if ($clienteDni): ?>
                                            <small><?= htmlspecialchars($clienteDni) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge-metodo metodo-<?= htmlspecialchars($metodoRaw) ?>">
                                            <?= htmlspecialchars(strtoupper(str_replace('_', ' ', $venta['metodo_pago'] ?: 'efectivo'))) ?>
                                        </span>
                                    </td>
                                    <td class="amount">
                                        <strong>S/ <?= number_format($totalNum, 2) ?></strong>
                                    </td>
                                    <td>
                                        <div class="action-buttons-wrap">
                                            <!-- Botón Azul: Previsualizar (Ojo) -->
                                            <button type="button" 
                                                    class="btn-action-history btn-preview" 
                                                    onclick="previsualizarTicket(<?= $index ?>)" 
                                                    title="Previsualizar comprobante"
                                                    aria-label="Previsualizar comprobante">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                    <circle cx="12" cy="12" r="3"></circle>
                                                </svg>
                                            </button>
                                            <!-- Botón Verde: Imprimir (Impresora) -->
                                            <button type="button" 
                                                    class="btn-action-history btn-print" 
                                                    onclick="imprimirTicketDirecto(<?= $index ?>)" 
                                                    title="Imprimir ticket térmico"
                                                    aria-label="Imprimir ticket térmico">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <polyline points="6 9 6 2 18 2 18 9"></polyline>
                                                    <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                                                    <rect x="6" y="14" width="12" height="8"></rect>
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                            <tr id="trSinResultados" style="display:none;">
                                <td colspan="6" class="cashier-history-empty">
                                    <i class="fa-solid fa-filter" style="font-size:1.8rem; color:#cbd5e1; margin-bottom:6px; display:block;"></i>
                                    No se encontraron ventas que coincidan con los filtros aplicados.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </section>
            </div>
        </main>
    </div>

    <!-- MODAL PREVISUALIZACIÓN DE TICKET TÉRMICO -->
    <div id="modalTicketPreview" class="ticket-modal-overlay" style="display:none;" onclick="cerrarTicketSiFondo(event)">
        <div class="ticket-modal-content">
            <div class="ticket-modal-header ticket-no-print">
                <h3><i class="fa-solid fa-receipt" style="color:#15803d; margin-right:6px;"></i> Comprobante de Venta</h3>
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
                        <div class="ticket-doc-type" id="lblDocType">BOLETA DE VENTA ELECTRÓNICA</div>
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
                        <tbody id="lblDocItems">
                            <!-- Items dinámicos -->
                        </tbody>
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

    <!-- DATOS DE VENTAS INCRUSTADOS -->
    <script>
        const ventasData = <?= json_encode($ventas ?? []) ?>;
        const hoyStr = '<?= date("Y-m-d") ?>';
        const hace7DiasStr = '<?= date("Y-m-d", strtotime("-7 days")) ?>';

        function cambiarRangoFecha() {
            const val = document.getElementById('filtroRangoFecha').value;
            const customWrap = document.getElementById('customDateWrap');
            if (val === 'custom') {
                customWrap.style.display = 'block';
            } else {
                customWrap.style.display = 'none';
                document.getElementById('filtroFecha').value = '';
            }
            filtrarVentas();
        }

        function filtrarVentas() {
            const texto = (document.getElementById('filtroTexto').value || '').trim().toLowerCase();
            const metodo = (document.getElementById('filtroMetodo').value || '').trim().toLowerCase();
            const rangoFecha = document.getElementById('filtroRangoFecha').value;
            const fechaCustom = document.getElementById('filtroFecha').value;

            const filas = document.querySelectorAll('#tbodyHistorial .fila-venta');
            let visibles = 0;
            let totalFiltrado = 0;
            let totalEfectivo = 0;
            let totalDigital = 0;

            filas.forEach(fila => {
                const cod = fila.getAttribute('data-codigo') || '';
                const cli = fila.getAttribute('data-cliente') || '';
                const dni = fila.getAttribute('data-dni') || '';
                const met = fila.getAttribute('data-metodo') || '';
                const fec = fila.getAttribute('data-fecha') || '';
                const tot = parseFloat(fila.getAttribute('data-total') || '0');

                // Filtro texto
                const coincideTexto = !texto || cod.includes(texto) || cli.includes(texto) || dni.includes(texto);

                // Filtro metodo
                let coincideMetodo = true;
                if (metodo) {
                    coincideMetodo = met.includes(metodo);
                }

                // Filtro fecha
                let coincideFecha = true;
                if (rangoFecha === 'hoy') {
                    coincideFecha = (fec === hoyStr);
                } else if (rangoFecha === '7dias') {
                    coincideFecha = (fec >= hace7DiasStr && fec <= hoyStr);
                } else if (rangoFecha === 'custom' && fechaCustom) {
                    coincideFecha = (fec === fechaCustom);
                }

                if (coincideTexto && coincideMetodo && coincideFecha) {
                    fila.style.display = '';
                    visibles++;
                    totalFiltrado += tot;
                    if (met.includes('efectivo')) {
                        totalEfectivo += tot;
                    } else {
                        totalDigital += tot;
                    }
                } else {
                    fila.style.display = 'none';
                }
            });

            // Actualizar métricas
            document.getElementById('metricTotalFiltrado').textContent = 'S/ ' + totalFiltrado.toFixed(2);
            document.getElementById('metricCantidadTickets').textContent = visibles + (visibles === 1 ? ' comprobante' : ' comprobantes');
            document.getElementById('metricTotalEfectivo').textContent = 'S/ ' + totalEfectivo.toFixed(2);
            document.getElementById('metricTotalDigital').textContent = 'S/ ' + totalDigital.toFixed(2);

            // Mensaje sin resultados
            const trSinRes = document.getElementById('trSinResultados');
            if (trSinRes) {
                trSinRes.style.display = (visibles === 0 && filas.length > 0) ? '' : 'none';
            }
        }

        function resetFiltros() {
            document.getElementById('filtroTexto').value = '';
            document.getElementById('filtroMetodo').value = '';
            document.getElementById('filtroRangoFecha').value = 'todos';
            document.getElementById('customDateWrap').style.display = 'none';
            document.getElementById('filtroFecha').value = '';
            filtrarVentas();
        }

        // Cargar ticket en modal
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

            // Items
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

            let iframe = document.getElementById('historialThermalPrintFrame');
            if (!iframe) {
                iframe = document.createElement('iframe');
                iframe.id = 'historialThermalPrintFrame';
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
    <link rel="stylesheet" href="Styles/Vistas/Cajero/historial.css?v=<?= time(); ?>">
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

        // Ejecutar primer filtrado al inicio para calcular tarjetas
        document.addEventListener('DOMContentLoaded', filtrarVentas);
    </script>

    
</body>
</html>
