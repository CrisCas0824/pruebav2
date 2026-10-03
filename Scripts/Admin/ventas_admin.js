(function () {
    'use strict';

    var searchInput    = document.getElementById('vaSearch');
    var filtEstado     = document.getElementById('vaFiltroEstado');
    var filtEntrega    = document.getElementById('vaFiltroEntrega');
    var filtFecha      = document.getElementById('vaFiltroFecha');
    var statCards      = document.querySelectorAll('.stat-card-square[data-filtro-estado]');
    var rows           = Array.from(document.querySelectorAll('#vaTabla tbody tr.va-row'));
    var paginationList = document.getElementById('vaPaginationList');

    var currentPage = 1;
    var pageSize = 5; // Paginación de 5 pedidos por página para mostrar controles idénticos a Imagen 3
    var currentFilteredRows = rows.slice();

    // Normalizador de estados en JS
    function normEstado(e) {
        if (!e) return 'pendiente';
        var s = String(e).toLowerCase().replace(/[_-]/g, ' ').trim();
        if (s.indexOf('pend') !== -1) return 'pendiente';
        if (s.indexOf('prep') !== -1) return 'en_preparacion';
        if (s.indexOf('camin') !== -1) return 'en_camino';
        if (s.indexOf('recoj') !== -1 || s.indexOf('listo') !== -1) return 'listo_recojo';
        if (s.indexOf('entreg') !== -1) return 'entregado';
        if (s.indexOf('cancel') !== -1) return 'cancelado';
        return 'pendiente';
    }

    // Configuración visual por estado
    var estadoMap = {
        'pendiente':      { label: 'Pendiente',         bg: '#fef3c7', color: '#92400e', dot: '#f59e0b' },
        'en_preparacion': { label: 'En Preparación',    bg: '#eff6ff', color: '#1d4ed8', dot: '#3b82f6' },
        'en_camino':      { label: 'En Camino',         bg: '#faf5ff', color: '#7e22ce', dot: '#a855f7' },
        'listo_recojo':   { label: 'Listo para Recojo', bg: '#ecfeff', color: '#0e7490', dot: '#06b6d4' },
        'entregado':      { label: 'Entregado',         bg: '#dcfce7', color: '#15803d', dot: '#16a34a' },
        'cancelado':      { label: 'Cancelado',         bg: '#fee2e2', color: '#b91c1c', dot: '#ef4444' }
    };

    // Filtrar tabla
    function aplicarFiltros(resetPage) {
        if (resetPage !== false) currentPage = 1;

        var q = (searchInput ? searchInput.value : '').toLowerCase().trim();
        var est = (filtEstado ? filtEstado.value : '').toLowerCase();
        var ent = (filtEntrega ? filtEntrega.value : '').toLowerCase();
        var fec = filtFecha ? filtFecha.value : '';

        currentFilteredRows = rows.filter(function(row) {
            var rowSearch  = (row.getAttribute('data-search') || '').toLowerCase();
            var rowEstado  = normEstado(row.getAttribute('data-estado'));
            var rowEntrega = (row.getAttribute('data-entrega') || '').toLowerCase();
            var rowFecha   = row.getAttribute('data-fecha') || '';

            var matchQ = !q || rowSearch.indexOf(q) !== -1;
            var matchEstado = !est || rowEstado === est;
            var matchEntrega = !ent || rowEntrega === ent;
            var matchFecha = !fec || rowFecha === fec;

            return matchQ && matchEstado && matchEntrega && matchFecha;
        });

        renderPaginacion();
    }

    function renderPaginacion() {
        var total = currentFilteredRows.length;
        var totalPages = Math.ceil(total / pageSize) || 1;
        if (currentPage > totalPages) currentPage = totalPages;
        if (currentPage < 1) currentPage = 1;

        var start = (currentPage - 1) * pageSize;
        var end = start + pageSize;

        rows.forEach(function(row) {
            row.style.display = 'none';
        });

        currentFilteredRows.forEach(function(row, idx) {
            if (idx >= start && idx < end) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });

        // Controles de paginación (Imagen 3)
        if (!paginationList) return;
        paginationList.innerHTML = '';

        if (total === 0) {
            document.getElementById('vaPaginationControls').style.display = 'none';
            return;
        }
        document.getElementById('vaPaginationControls').style.display = 'flex';

        // Botón Anterior <
        var prevBtn = document.createElement('button');
        prevBtn.type = 'button';
        prevBtn.className = 'pagination-page-button';
        prevBtn.innerHTML = '‹';
        prevBtn.disabled = currentPage === 1;
        prevBtn.onclick = function() {
            if (currentPage > 1) { currentPage--; renderPaginacion(); }
        };
        paginationList.appendChild(prevBtn);

        // Páginas numeradas
        for (var p = 1; p <= totalPages; p++) {
            (function(pageNum) {
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'pagination-page-button' + (pageNum === currentPage ? ' is-current' : '');
                btn.textContent = pageNum;
                btn.onclick = function() {
                    currentPage = pageNum;
                    renderPaginacion();
                };
                paginationList.appendChild(btn);
            })(p);
        }

        // Botón Siguiente >
        var nextBtn = document.createElement('button');
        nextBtn.type = 'button';
        nextBtn.className = 'pagination-page-button';
        nextBtn.innerHTML = '›';
        nextBtn.disabled = currentPage === totalPages;
        nextBtn.onclick = function() {
            if (currentPage < totalPages) { currentPage++; renderPaginacion(); }
        };
        paginationList.appendChild(nextBtn);
    }

    // Selección interactiva de tarjetas
    window.vaFiltrarPorTarjeta = function(cardEl, estadoFiltro) {
        statCards.forEach(function(c) { c.classList.remove('active-card'); });
        if (cardEl) cardEl.classList.add('active-card');

        if (filtEstado) {
            filtEstado.value = estadoFiltro || '';
        }
        aplicarFiltros(true);
    };

    // Sincronizar select con tarjetas activas
    if (filtEstado) {
        filtEstado.addEventListener('change', function() {
            var val = this.value;
            statCards.forEach(function(c) {
                if (c.getAttribute('data-filtro-estado') === val) {
                    c.classList.add('active-card');
                } else {
                    c.classList.remove('active-card');
                }
            });
            aplicarFiltros(true);
        });
    }

    if (searchInput) {
        ['input', 'keyup', 'search', 'change'].forEach(function(evt) {
            searchInput.addEventListener(evt, function() { aplicarFiltros(true); });
        });
    }
    if (filtEntrega) filtEntrega.addEventListener('change', function() { aplicarFiltros(true); });
    if (filtFecha) filtFecha.addEventListener('change', function() { aplicarFiltros(true); });

    // Modales y Detalles
    var modalOverlay = document.getElementById('vaModalOverlay');
    var modalTitulo  = document.getElementById('vaModalTitulo');
    var modalBody    = document.getElementById('vaModalBody');
    var modalFooter  = document.getElementById('vaModalFooter');
    var btnCerrar    = document.getElementById('vaBtnCerrarModal');

    var confirmOverlay   = document.getElementById('vaConfirmModalOverlay');
    var confirmMsg       = document.getElementById('vaConfirmModalMsg');
    var btnConfirmCancel = document.getElementById('vaBtnConfirmarCancelar');
    var btnConfirmProced = document.getElementById('vaBtnConfirmarProceder');

    // Notificación Toast Superior (Imagen 4)
    var topToast     = document.getElementById('topNotificationToast');
    var toastTitle   = document.getElementById('toastTitleTop');
    var toastMsg     = document.getElementById('toastMsgTop');
    var toastIcon    = document.getElementById('toastIconCircle');
    var toastProg    = document.getElementById('toastProgressBar');
    var toastClose   = document.getElementById('toastCloseBtn');
    var toastTimeout = null;

    window.mostrarTopToast = function(titulo, msg, esExito) {
        if (!topToast) return;
        clearTimeout(toastTimeout);

        toastTitle.textContent = titulo || (esExito ? 'Éxito' : 'Atención');
        toastMsg.textContent   = msg || '';

        if (esExito) {
            toastIcon.className = 'toast-icon-circle';
            toastIcon.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>';
            toastProg.className = 'toast-progress-bar';
        } else {
            toastIcon.className = 'toast-icon-circle error';
            toastIcon.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>';
            toastProg.className = 'toast-progress-bar error';
        }

        topToast.classList.add('show');
        toastTimeout = setTimeout(function() {
            topToast.classList.remove('show');
        }, 3600);
    };

    if (toastClose) {
        toastClose.addEventListener('click', function() {
            if (topToast) topToast.classList.remove('show');
        });
    }

    var currentPedidoModalId = null;
    var currentPedidoEntrega = 'delivery';
    var currentPedidoEstado  = 'pendiente';
    var currentPedidoData    = null;

    function escH(str) {
        var d = document.createElement('div');
        d.textContent = String(str || '');
        return d.innerHTML;
    }

    function fechaFmt(f) {
        if (!f) return '—';
        var d = new Date(f);
        if (isNaN(d)) return f;
        return d.toLocaleDateString('es-PE', { day:'2-digit', month:'2-digit', year:'numeric', hour:'2-digit', minute:'2-digit' });
    }

    function infoCard(label, val) {
        return '<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:11px 13px;">'
             + '<small style="display:block;color:#94a3b8;font-size:.70rem;font-weight:800;text-transform:uppercase;letter-spacing:.05em;margin-bottom:3px;">' + label + '</small>'
             + '<strong style="font-size:.88rem;color:#0f172a;word-break:break-word;">' + val + '</strong></div>';
    }

    function abrirDetallePedido(idPedido) {
        currentPedidoModalId = idPedido;
        window.currentPedidoModalId = idPedido;
        modalTitulo.textContent = 'Pedido #' + String(idPedido).padStart(5, '0');
        modalBody.innerHTML = '<p style="text-align:center;color:#94a3b8;padding:40px 0;">Cargando detalle...</p>';
        modalFooter.innerHTML = '';
        modalOverlay.classList.add('active');

        fetch('index.php?action=detalle_pedido_admin&id=' + idPedido, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (!res || !res.success || !res.data) {
                modalBody.innerHTML = '<p style="color:#dc2626;text-align:center;padding:30px;">Error al cargar el detalle del pedido.</p>';
                return;
            }

            currentPedidoData = res.data;
            var p = res.data.pedido;
            var d = res.data.detalle || [];
            var nombreCliente = ((p.usuario_nombre || '') + ' ' + (p.usuario_apellido || '')).trim() || (p.cliente_nombre || 'Sin nombre');
            var isDelivery = (p.tipo_entrega === 'delivery');
            currentPedidoEntrega = isDelivery ? 'delivery' : 'recojo';
            currentPedidoEstado  = normEstado(p.estado);

            var estCod = currentPedidoEstado;
            var estData = estadoMap[estCod] || { label: p.estado || 'Pendiente', bg: '#f1f5f9', color: '#475569', dot: '#94a3b8' };
            var badgeHtml = '<span class="va-badge-pill" style="display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:20px;font-size:0.78rem;font-weight:700;"><span style="width:6px;height:6px;border-radius:50%;background:' + estData.dot + ';"></span><span>' + estData.label + '</span></span>';

            var info = '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:10px;margin-bottom:16px;">';
            info += infoCard('Cliente', escH(nombreCliente));
            info += infoCard('Correo', escH(p.usuario_correo || '—'));
            info += infoCard('Entrega', isDelivery ? 'Delivery' : 'Recojo en tienda');
            info += infoCard('Pago', escH(p.metodo_pago || '—'));
            info += infoCard('Fecha', fechaFmt(p.fecha));
            info += infoCard('Estado', badgeHtml);
            info += infoCard('Total', '<span style="color:#15803d;font-weight:800;">S/ ' + parseFloat(p.total || 0).toFixed(2) + '</span>');

            var montoRec = parseFloat(p.monto_recibido || 0);
            var vueltoPed = parseFloat(p.vuelto || 0);
            if (montoRec > 0) {
                info += infoCard('Paga Con', '<span style="color:#2563eb;font-weight:800;">S/ ' + montoRec.toFixed(2) + '</span>');
                info += infoCard('Vuelto', '<span style="color:#ea580c;font-weight:800;">S/ ' + vueltoPed.toFixed(2) + '</span>');
            }
            info += '</div>';

            if (p.direccion_envio) {
                info += '<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:10px 14px;margin-bottom:16px;">'
                      + '<small style="display:block;color:#94a3b8;font-size:.70rem;font-weight:800;text-transform:uppercase;margin-bottom:2px;">Dirección de Entrega</small>'
                      + '<span style="font-size:.85rem;color:#334155;">' + escH(p.direccion_envio) + '</span></div>';
            }

            if (p.observacion) {
                info += '<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:10px 14px;margin-bottom:16px;">'
                      + '<small style="display:block;color:#94a3b8;font-size:.70rem;font-weight:800;text-transform:uppercase;margin-bottom:2px;">Observación</small>'
                      + '<span style="font-size:.85rem;color:#334155;">' + escH(p.observacion) + '</span></div>';
            }

            // Productos
            var prods = '<h4 style="margin:0 0 10px;font-size:.85rem;color:#475569;text-transform:uppercase;letter-spacing:.05em;font-weight:800;">Productos (' + d.length + ')</h4><div style="background:#fff;border:1px solid #f1f5f9;border-radius:10px;padding:0 12px;">';
            if (d.length === 0) {
                prods += '<p style="color:#94a3b8;text-align:center;padding:20px;">Sin productos registrados.</p>';
            } else {
                d.forEach(function(item) {
                    var imgSrc = item.imagen ? escH(item.imagen) : 'Images/icono.png';
                    prods += '<div class="va-product-row">'
                        + '<img src="' + imgSrc + '" alt="' + escH(item.titulo) + '" onerror="this.src=\'Images/icono.png\'">'
                        + '<div><strong style="color:#0f172a;font-size:.88rem;">' + escH(item.titulo) + '</strong>'
                        + '<p style="margin:2px 0 0;font-size:.78rem;color:#64748b;">' + item.cantidad + ' ' + escH(item.unidad_medida || 'und.') + ' × S/ ' + parseFloat(item.precio_unitario).toFixed(2) + '</p></div>'
                        + '<div style="text-align:right;font-weight:800;color:#15803d;font-size:.90rem;">S/ ' + parseFloat(item.subtotal).toFixed(2) + '</div>'
                        + '</div>';
                });
            }
            prods += '</div>';

            modalBody.innerHTML = info + prods;

            // Footer: Solo Visualizar, Imprimir y Descargar PDF (Cambio de estados en Logística y Despacho)
            var footerHtml = '<div style="display:flex;align-items:center;gap:6px;font-size:0.80rem;color:#64748b;">'
                + '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>'
                + '<span>El cambio de estados se realiza en <strong>Logística & Despacho</strong></span>'
                + '</div>';

            

            modalFooter.innerHTML = footerHtml;

            var btnPdfModal = document.getElementById('vaBtnDescargarPdfModal');
            if (btnPdfModal) {
                btnPdfModal.addEventListener('click', function() {
                    window.descargarPedidoPDF(idPedido);
                });
            }

            var btnImpModal = document.getElementById('vaBtnImprimirModal');
            if (btnImpModal) {
                btnImpModal.addEventListener('click', function() {
                    if (modalTicketAdmin) {
                        modalTicketAdmin.classList.add('active');
                        popularDatosTicketAdmin(res.data);
                        setTimeout(function() {
                            window.print();
                        }, 200);
                    }
                });
            }

            var btnCerrarPie = document.getElementById('vaBtnCerrarModalPie');
            if (btnCerrarPie) {
                btnCerrarPie.addEventListener('click', function() {
                    modalOverlay.classList.remove('active');
                });
            }
        })
        .catch(function() {
            modalBody.innerHTML = '<p style="color:#dc2626;text-align:center;padding:30px;">Error de conexión.</p>';
        });
    }

    var estadoAGuardar = '';
    var pedidoAGuardarId = null;

    function abrirModalConfirmacion(idPedido, nuevoEstado) {
        pedidoAGuardarId = idPedido;
        estadoAGuardar = nuevoEstado;
        confirmMsg.innerHTML = 'El <strong>Pedido #' + String(idPedido).padStart(5, '0') + '</strong> cambiará al estado: <br><strong style="color:#15803d;font-size:1.05rem;">' + escH(nuevoEstado) + '</strong>';
        confirmOverlay.style.display = 'flex';
        confirmOverlay.classList.add('active');
    }

    function cerrarModalConfirmacion() {
        confirmOverlay.style.display = 'none';
        confirmOverlay.classList.remove('active');
    }

    function ejecutarGuardadoEstado() {
        if (!pedidoAGuardarId || !estadoAGuardar) return;
        var id = pedidoAGuardarId;
        var est = estadoAGuardar;

        var body = new FormData();
        body.append('id_pedido', id);
        body.append('estado', est);

        fetch('index.php?action=actualizar_estado_pedido', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: body
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            cerrarModalConfirmacion();
            if (res && res.success) {
                var estCod = normEstado(est);
                var estData = estadoMap[estCod] || { label: est, bg: '#f1f5f9', color: '#475569', dot: '#94a3b8' };
                mostrarTopToast('Éxito', 'Estado del Pedido #' + String(id).padStart(5, '0') + ' actualizado a: ' + estData.label, true);

                // Actualizar fila en la tabla
                rows.forEach(function(row) {
                    if (parseInt(row.getAttribute('data-id')) === parseInt(id)) {
                        row.setAttribute('data-estado', estCod);
                        var badge = row.querySelector('.va-badge-pill');
                        if (badge) {
                            //badge.style.background = estData.bg;
                            //badge.style.color = estData.color;
                            badge.innerHTML = '<span style="width:6px;height:6px;border-radius:50%;background:' + estData.dot + ';flex-shrink:0;"></span><span>' + estData.label + '</span>';
                        }
                    }
                });

                // Actualizar conteos en stat-cards
                actualizarConteoCards();

                // Re-filtrar con la página actual
                aplicarFiltros(false);

                // Cerrar modal de detalle
                modalOverlay.classList.remove('active');
            } else {
                mostrarTopToast('Error', ((res && res.message) || 'No se pudo actualizar el estado.'), false);
            }
        })
        .catch(function() {
            cerrarModalConfirmacion();
            mostrarTopToast('Error', 'Error de conexión al actualizar.', false);
        });
    }

    function actualizarConteoCards() {
        var counts = {
            '': rows.length,
            'pendiente': 0,
            'en_preparacion': 0,
            'en_camino': 0,
            'listo_recojo': 0,
            'entregado': 0,
            'cancelado': 0
        };

        rows.forEach(function(r) {
            var st = normEstado(r.getAttribute('data-estado'));
            if (counts[st] !== undefined) counts[st]++;
        });

        statCards.forEach(function(c) {
            var f = c.getAttribute('data-filtro-estado');
            if (counts[f] !== undefined) {
                var valEl = c.querySelector('.stat-val');
                if (valEl) valEl.textContent = counts[f];
            }
        });
    }

    // Eventos de botones
    if (btnConfirmCancel) btnConfirmCancel.addEventListener('click', cerrarModalConfirmacion);
    if (btnConfirmProced) btnConfirmProced.addEventListener('click', ejecutarGuardadoEstado);

    // Botón Cerrar (X) del modal de detalle
    if (btnCerrar) {
        btnCerrar.addEventListener('click', function() {
            modalOverlay.classList.remove('active');
        });
    }

    // Cierre al dar clic fuera o escape
    if (modalOverlay) {
        modalOverlay.addEventListener('click', function(e) {
            if (e.target === modalOverlay) modalOverlay.classList.remove('active');
        });
    }
    if (confirmOverlay) {
        confirmOverlay.addEventListener('click', function(e) {
            if (e.target === confirmOverlay) cerrarModalConfirmacion();
        });
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            if (confirmOverlay && confirmOverlay.classList.contains('active')) {
                cerrarModalConfirmacion();
            } else if (modalTicketAdmin && modalTicketAdmin.classList.contains('active')) {
                cerrarModalTicketAdmin();
            } else if (modalOverlay) {
                modalOverlay.classList.remove('active');
            }
        }
    });

    // Abrir modal desde botones de acción
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.va-abrir-modal');
        if (btn) {
            var id = parseInt(btn.getAttribute('data-id'), 10);
            if (id) abrirDetallePedido(id);
        }
    });

    // Modal Comprobante Ticket Admin (Imprimir y PDF)
    var modalTicketAdmin        = document.getElementById('modalTicketVentaAdmin');
    var btnCerrarTicketAdmin    = document.getElementById('btnCerrarTicketAdmin');
    var btnAdminTicketCerrarPie = document.getElementById('btnAdminTicketCerrarPie');
    var btnAdminTicketImprimir  = document.getElementById('btnAdminTicketImprimir');
    var btnAdminTicketDescargar = document.getElementById('btnAdminTicketDescargarPdf');
    var ultimoTicketAdminData   = null;

    function cerrarModalTicketAdmin() {
        if (modalTicketAdmin) {
            modalTicketAdmin.classList.remove('active');
        }
    }

    if (btnCerrarTicketAdmin) btnCerrarTicketAdmin.addEventListener('click', cerrarModalTicketAdmin);
    if (btnAdminTicketCerrarPie) btnAdminTicketCerrarPie.addEventListener('click', cerrarModalTicketAdmin);
    if (btnAdminTicketImprimir) {
        btnAdminTicketImprimir.addEventListener('click', function() {
            window.print();
        });
    }
    if (btnAdminTicketDescargar) {
        btnAdminTicketDescargar.addEventListener('click', function() {
            ejecutarDescargaPdfTicket();
        });
    }
    if (modalTicketAdmin) {
        modalTicketAdmin.addEventListener('click', function(e) {
            if (e.target === modalTicketAdmin) cerrarModalTicketAdmin();
        });
    }

    // Botón descargar PDF desde el header del modal de detalle
    var btnDescargarPdfHead = document.getElementById('vaBtnDescargarPdfHead');
    if (btnDescargarPdfHead) {
        btnDescargarPdfHead.addEventListener('click', function() {
            var id = currentPedidoModalId || window.currentPedidoModalId;
            if (id) window.descargarPedidoPDF(id);
        });
    }

    // Carga dinámica de html2pdf en caso de navegación SPA
    window.asegurarHtml2Pdf = function(callback) {
        if (typeof window.html2pdf !== 'undefined') {
            callback();
            return;
        }
        var exist = document.querySelector('script[src*="html2pdf"]');
        if (exist) {
            exist.addEventListener('load', function() { callback(); });
            return;
        }
        var s = document.createElement('script');
        s.src = 'https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js';
        s.onload = function() { callback(); };
        s.onerror = function() {
            if (window.mostrarTopToast) {
                window.mostrarTopToast('Error', 'No se pudo cargar la librería para exportar PDF.', false);
            } else {
                alert('No se pudo cargar la librería para exportar PDF.');
            }
        };
        document.head.appendChild(s);
    };

    function popularDatosTicketAdmin(data) {
        ultimoTicketAdminData = data;
        var p = data.pedido;
        var prods = data.detalle || [];

        var numPadded = String(p.id_pedido || 0).padStart(5, '0');
        var codigoDoc = p.codigo_pedido ? escH(p.codigo_pedido) : ('B001-' + String(p.id_pedido).padStart(6, '0'));
        var clienteNombre = ((p.usuario_nombre || '') + ' ' + (p.usuario_apellido || '')).trim() || (p.cliente_nombre || 'Cliente General');
        var isDelivery = (p.tipo_entrega === 'delivery');
        var total = parseFloat(p.total || 0);
        var subtotal = parseFloat((total / 1.18).toFixed(2));
        var igv = parseFloat((total - subtotal).toFixed(2));
        var montoRec = parseFloat(p.monto_recibido || 0);
        var vueltoPed = parseFloat(p.vuelto || 0);

        document.getElementById('lblAdminTicketCodigo').textContent = codigoDoc;
        document.getElementById('lblAdminTicketNumInt').textContent = 'N° Pedido: #' + numPadded;
        document.getElementById('lblAdminTicketFecha').textContent = fechaFmt(p.fecha);
        document.getElementById('lblAdminTicketCliente').textContent = clienteNombre.toUpperCase();
        
        var rowCorreo = document.getElementById('rowAdminTicketCorreo');
        if (p.usuario_correo) {
            rowCorreo.style.display = 'block';
            document.getElementById('lblAdminTicketCorreo').textContent = p.usuario_correo;
        } else {
            rowCorreo.style.display = 'none';
        }

        document.getElementById('lblAdminTicketEntrega').textContent = isDelivery ? 'DELIVERY A DOMICILIO' : 'RECOJO EN TIENDA';

        var rowDirec = document.getElementById('rowAdminTicketDireccion');
        if (isDelivery && p.direccion_envio) {
            rowDirec.style.display = 'block';
            document.getElementById('lblAdminTicketDireccion').textContent = p.direccion_envio;
        } else {
            rowDirec.style.display = 'none';
        }

        document.getElementById('lblAdminTicketMetodo').textContent = (p.metodo_pago || 'EFECTIVO').toUpperCase();
        document.getElementById('lblAdminTicketEstado').textContent = (p.estado || 'PENDIENTE').toUpperCase();

        var tbody = document.getElementById('ticketAdminTbodyItems');
        var rowsHtml = '';
        if (prods.length === 0) {
            rowsHtml = '<tr><td colspan="4" style="text-align:center; padding:8px 0; color:#64748b;">(Sin productos registrados)</td></tr>';
        } else {
            prods.forEach(function(item) {
                var cant = item.cantidad || 1;
                var pu = parseFloat(item.precio_unitario || 0).toFixed(2);
                var st = parseFloat(item.subtotal || 0).toFixed(2);
                rowsHtml += '<tr>'
                    + '<td style="padding:3px 0; vertical-align:top;">' + cant + '</td>'
                    + '<td style="padding:3px 4px; vertical-align:top;">' + escH(item.titulo || 'Producto') + '</td>'
                    + '<td style="padding:3px 0; vertical-align:top; text-align:right;">' + pu + '</td>'
                    + '<td style="padding:3px 0; vertical-align:top; text-align:right; font-weight:bold;">' + st + '</td>'
                    + '</tr>';
            });
        }
        tbody.innerHTML = rowsHtml;

        document.getElementById('lblAdminTicketSubtotal').textContent = 'S/ ' + subtotal.toFixed(2);
        document.getElementById('lblAdminTicketIgv').textContent = 'S/ ' + igv.toFixed(2);
        document.getElementById('lblAdminTicketTotal').textContent = 'S/ ' + total.toFixed(2);

        var rowEfectivo = document.getElementById('rowAdminTicketEfectivo');
        if (montoRec > 0) {
            rowEfectivo.style.display = 'block';
            document.getElementById('lblAdminTicketRecibido').textContent = 'S/ ' + montoRec.toFixed(2);
            document.getElementById('lblAdminTicketVuelto').textContent = 'S/ ' + vueltoPed.toFixed(2);
        } else {
            rowEfectivo.style.display = 'none';
        }

        var rowObs = document.getElementById('rowAdminTicketObs');
        if (p.observacion) {
            rowObs.style.display = 'block';
            document.getElementById('lblAdminTicketObs').textContent = p.observacion;
        } else {
            rowObs.style.display = 'none';
        }
    }

    function ejecutarDescargaPdfTicket() {
        var ticketElement = document.getElementById('ticketTermicoImprimibleAdmin');
        if (!ticketElement) return;

        var numPadded = ultimoTicketAdminData ? String(ultimoTicketAdminData.pedido.id_pedido).padStart(5, '0') : '00000';
        var filename = 'Comprobante-Pedido-' + numPadded + '.pdf';

        if (window.mostrarTopToast) {
            window.mostrarTopToast('Generando PDF', 'Descargando comprobante...', true);
        }

        window.asegurarHtml2Pdf(function() {
            var rect = ticketElement.getBoundingClientRect();
            var altoMm = Math.ceil((rect.height * 25.4) / 96) + 12;
            if (altoMm < 140) altoMm = 140;

            var opt = {
                margin:       [4, 4, 4, 4],
                filename:     filename,
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  {
                    scale: 2,
                    useCORS: true,
                    logging: false,
                    scrollX: 0,
                    scrollY: 0
                },
                jsPDF:        { unit: 'mm', format: [80, altoMm], orientation: 'portrait' }
            };

            html2pdf().set(opt).from(ticketElement).save().then(function() {
                if (window.mostrarTopToast) {
                    window.mostrarTopToast('Descargado', 'Comprobante #' + numPadded + ' descargado con éxito.', true);
                }
            }).catch(function(err) {
                console.error(err);
                if (window.mostrarTopToast) {
                    window.mostrarTopToast('Error', 'No se pudo generar el archivo PDF.', false);
                }
            });
        });
    }

    window.descargarPedidoPDF = function(idPedido) {
        if (!idPedido) {
            idPedido = currentPedidoModalId || window.currentPedidoModalId;
        }
        if (!idPedido) return;

        // Abrir modal del ticket para que esté 100% visible en el viewport
        if (modalTicketAdmin) {
            modalTicketAdmin.classList.add('active');
        }

        if (currentPedidoData && currentPedidoData.pedido && parseInt(currentPedidoData.pedido.id_pedido, 10) === parseInt(idPedido, 10)) {
            popularDatosTicketAdmin(currentPedidoData);
            setTimeout(function() {
                ejecutarDescargaPdfTicket();
            }, 300);
            return;
        }

        fetch('index.php?action=detalle_pedido_admin&id=' + idPedido, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (!res || !res.success || !res.data) {
                if (window.mostrarTopToast) {
                    window.mostrarTopToast('Error', 'No se pudo cargar el detalle para el comprobante.', false);
                }
                return;
            }
            popularDatosTicketAdmin(res.data);
            setTimeout(function() {
                ejecutarDescargaPdfTicket();
            }, 300);
        })
        .catch(function() {
            if (window.mostrarTopToast) {
                window.mostrarTopToast('Error', 'Error de conexión al cargar comprobante.', false);
            }
        });
    };

    // Inicializar paginación
    aplicarFiltros(true);
})();
