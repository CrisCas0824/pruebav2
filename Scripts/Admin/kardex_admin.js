(function() {
    const ITEMS_PER_PAGE = 5;
    let currentPage = 1;
    let allRows = [];
    let filteredRows = [];

    let currentTipoFilter = 'ALL';
    let currentSearchText = '';
    let currentFechaDesde = '';
    let currentFechaHasta = '';
    let currentOrden = 'reciente';

    function initKardex() {
        const tbody = document.getElementById('kardexTbody');
        if (!tbody) return;

        allRows = Array.from(tbody.querySelectorAll('.kardex-row'));
        
        // Calcular conteo de estadísticas rápidas
        let countEntradas = 0;
        let countSalidas = 0;
        allRows.forEach(row => {
            const tipo = row.getAttribute('data-tipo');
            if (tipo === 'ENTRADA') countEntradas++;
            else if (tipo === 'SALIDA') countSalidas++;
        });
        const statEnt = document.getElementById('statTotalEntradas');
        const statSal = document.getElementById('statTotalSalidas');
        if (statEnt) statEnt.textContent = countEntradas;
        if (statSal) statSal.textContent = countSalidas;

        // Búsqueda en vivo instantánea
        const searchInput = document.getElementById('kardexLiveSearch');
        const clearBtn = document.getElementById('kardexClearSearch');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                currentSearchText = this.value.trim().toLowerCase();
                if (clearBtn) clearBtn.style.display = currentSearchText ? 'block' : 'none';
                currentPage = 1;
                aplicarFiltrosYRender();
            });
        }
        if (clearBtn) {
            clearBtn.addEventListener('click', function() {
                if (searchInput) {
                    searchInput.value = '';
                    currentSearchText = '';
                    clearBtn.style.display = 'none';
                    currentPage = 1;
                    aplicarFiltrosYRender();
                    searchInput.focus();
                }
            });
        }

        // Filtro por Fechas
        const fechaDesdeInput = document.getElementById('kardexFechaDesde');
        const fechaHastaInput = document.getElementById('kardexFechaHasta');
        const btnLimpiarFechas = document.getElementById('btnLimpiarFechas');

        if (fechaDesdeInput) {
            currentFechaDesde = fechaDesdeInput.value;
            fechaDesdeInput.addEventListener('change', function() {
                currentFechaDesde = this.value;
                currentPage = 1;
                aplicarFiltrosYRender();
            });
        }
        if (fechaHastaInput) {
            currentFechaHasta = fechaHastaInput.value;
            fechaHastaInput.addEventListener('change', function() {
                currentFechaHasta = this.value;
                currentPage = 1;
                aplicarFiltrosYRender();
            });
        }
        if (btnLimpiarFechas) {
            btnLimpiarFechas.addEventListener('click', function() {
                if (fechaDesdeInput) fechaDesdeInput.value = '';
                if (fechaHastaInput) fechaHastaInput.value = '';
                currentFechaDesde = '';
                currentFechaHasta = '';
                currentPage = 1;
                aplicarFiltrosYRender();
            });
        }

        // Ordenamiento & Filtro por Tipo integrado (sin iconos SVG)
        const ordenSelect = document.getElementById('kardexOrdenSelect');
        if (ordenSelect) {
            ordenSelect.addEventListener('change', function() {
                const val = this.value;
                if (val.startsWith('tipo_')) {
                    currentTipoFilter = val.replace('tipo_', '');
                } else {
                    currentOrden = val;
                }
                currentPage = 1;
                aplicarFiltrosYRender();
            });
        }

        // Búsqueda en tiempo real dentro del modal de productos (Sin select estático)
        configurarBuscadorModalProductos();

        // Render inicial
        aplicarFiltrosYRender();
    }

    function aplicarFiltrosYRender() {
        filteredRows = allRows.filter(row => {
            // Filtro por Tipo
            if (currentTipoFilter !== 'ALL') {
                const rowTipo = row.getAttribute('data-tipo');
                if (rowTipo !== currentTipoFilter) return false;
            }

            // Filtro por Texto de Búsqueda
            if (currentSearchText) {
                const prod = row.getAttribute('data-producto') || '';
                const mot = row.getAttribute('data-motivo') || '';
                const resp = row.getAttribute('data-responsable') || '';
                const tipo = (row.getAttribute('data-tipo') || '').toLowerCase();
                const textContent = (prod + ' ' + mot + ' ' + resp + ' ' + tipo).toLowerCase();
                if (!textContent.includes(currentSearchText)) return false;
            }

            // Filtro por Fecha Desde / Hasta
            const rowFecha = row.getAttribute('data-fecha') || '';
            if (currentFechaDesde && rowFecha < currentFechaDesde) return false;
            if (currentFechaHasta && rowFecha > currentFechaHasta) return false;

            return true;
        });

        // Aplicar Ordenamiento
        filteredRows.sort((a, b) => {
            const tsA = parseInt(a.getAttribute('data-timestamp') || 0);
            const tsB = parseInt(b.getAttribute('data-timestamp') || 0);
            const cantA = parseInt(a.getAttribute('data-cantidad') || 0);
            const cantB = parseInt(b.getAttribute('data-cantidad') || 0);

            if (currentOrden === 'reciente') return tsB - tsA;
            if (currentOrden === 'antiguo') return tsA - tsB;
            if (currentOrden === 'cant_desc') return cantB - cantA;
            if (currentOrden === 'cant_asc') return cantA - cantB;
            return 0;
        });

        renderTablaYPaginacion();
    }

    function renderTablaYPaginacion() {
        const tbody = document.getElementById('kardexTbody');
        if (!tbody) return;

        // Ocultar todas las filas primero
        allRows.forEach(row => {
            row.style.display = 'none';
        });

        // Remover fila de estado vacío existente si la hay
        const existingEmpty = document.getElementById('kardexEmptyMsgRow');
        if (existingEmpty) existingEmpty.remove();

        const totalItems = filteredRows.length;
        const totalPages = Math.max(1, Math.ceil(totalItems / ITEMS_PER_PAGE));

        if (currentPage > totalPages) currentPage = totalPages;
        if (currentPage < 1) currentPage = 1;

        if (totalItems === 0) {
            const emptyRow = document.createElement('tr');
            emptyRow.id = 'kardexEmptyMsgRow';
            emptyRow.innerHTML = `
                <td colspan="10" style="text-align:center; color:#94a3b8; padding:45px 20px; font-weight:700;">
                    <div style="font-size:2rem; margin-bottom:8px;">🔍</div>
                    <div style="font-size:1.05rem; color:#475569;">No se encontraron movimientos coincidentes</div>
                    <div style="font-size:0.84rem; color:#94a3b8; margin-top:4px;">Prueba ajustando los términos de búsqueda o los filtros de fecha y tipo.</div>
                </td>
            `;
            tbody.appendChild(emptyRow);
        } else {
            const startIndex = (currentPage - 1) * ITEMS_PER_PAGE;
            const endIndex = Math.min(startIndex + ITEMS_PER_PAGE, totalItems);

            for (let i = startIndex; i < endIndex; i++) {
                const row = filteredRows[i];
                row.style.display = '';
                tbody.appendChild(row);
            }
        }

        // Actualizar contador del encabezado
        const contador = document.getElementById('kardexTotalContador');
        if (contador) {
            contador.textContent = `Mostrando ${totalItems} movimiento${totalItems === 1 ? '' : 's'}`;
        }

        // Actualizar texto informativo de paginación
        const pagInfo = document.getElementById('kardexPagInfo');
        if (pagInfo) {
            if (totalItems === 0) {
                pagInfo.textContent = '0 movimientos';
            } else {
                const start = (currentPage - 1) * ITEMS_PER_PAGE + 1;
                const end = Math.min(currentPage * ITEMS_PER_PAGE, totalItems);
                pagInfo.textContent = `Mostrando ${start} - ${end} de ${totalItems} movimientos`;
            }
        }

        // Construir botones de paginación
        construirPaginador(totalPages);
    }

    function construirPaginador(totalPages) {
        const pagNav = document.getElementById('kardexPagNav');
        if (!pagNav) return;
        pagNav.innerHTML = '';

        if (totalPages <= 1) {
            pagNav.style.display = 'none';
            return;
        }
        pagNav.style.display = 'flex';

        // Flecha Anterior < (estilo inventario)
        const btnPrev = document.createElement('button');
        btnPrev.type = 'button';
        btnPrev.className = 'kardex-pag-btn';
        btnPrev.innerHTML = '&lsaquo;';
        btnPrev.setAttribute('aria-label', 'Página anterior');
        if (currentPage === 1) {
            btnPrev.disabled = true;
        } else {
            btnPrev.onclick = () => cambiarPagina(currentPage - 1);
        }
        pagNav.appendChild(btnPrev);

        // Botones de página numerados
        for (let p = 1; p <= totalPages; p++) {
            if (totalPages > 7 && p > 2 && p < totalPages - 1 && Math.abs(p - currentPage) > 1) {
                if (p === 3 || p === totalPages - 2) {
                    const dots = document.createElement('span');
                    dots.textContent = '...';
                    dots.style.cssText = 'color:#94a3b8; padding:0 4px; font-weight:700; font-size:0.9rem;';
                    pagNav.appendChild(dots);
                }
                continue;
            }

            const pageBtn = document.createElement('button');
            pageBtn.type = 'button';
            pageBtn.textContent = p;
            pageBtn.className = 'kardex-pag-btn' + (p === currentPage ? ' active' : '');
            pageBtn.setAttribute('aria-label', 'Página ' + p);
            if (p === currentPage) {
                pageBtn.setAttribute('aria-current', 'page');
            } else {
                pageBtn.onclick = () => cambiarPagina(p);
            }
            pagNav.appendChild(pageBtn);
        }

        // Flecha Siguiente > (estilo inventario)
        const btnNext = document.createElement('button');
        btnNext.type = 'button';
        btnNext.className = 'kardex-pag-btn';
        btnNext.innerHTML = '&rsaquo;';
        btnNext.setAttribute('aria-label', 'Página siguiente');
        if (currentPage >= totalPages) {
            btnNext.disabled = true;
        } else {
            btnNext.onclick = () => cambiarPagina(currentPage + 1);
        }
        pagNav.appendChild(btnNext);
    }

    function cambiarPagina(newPage) {
        currentPage = newPage;
        renderTablaYPaginacion();

        const tableCard = document.getElementById('kardexTableCard');
        if (tableCard) {
            tableCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    // Filtro por tipo desde tarjetas superiores y sincronización con el selector Ordenar
    window.filtrarPorTipoKardex = function(tipo) {
        currentTipoFilter = tipo;
        const ordenSelect = document.getElementById('kardexOrdenSelect');
        if (ordenSelect) {
            if (tipo === 'ALL') ordenSelect.value = 'tipo_ALL';
            else if (tipo === 'ENTRADA') ordenSelect.value = 'tipo_ENTRADA';
            else if (tipo === 'SALIDA') ordenSelect.value = 'tipo_SALIDA';
            else if (tipo === 'AJUSTE') ordenSelect.value = 'tipo_AJUSTE';
        }
        currentPage = 1;
        aplicarFiltrosYRender();
    };

    // Modal de Ajuste Manual
    window.abrirModalAjuste = function() { 
        const m = document.getElementById('modalAjuste');
        if (m) {
            m.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            window.actualizarPreviewModalAjuste();
        }
    };

    window.cerrarModalAjuste = function() { 
        const m = document.getElementById('modalAjuste');
        if (m) {
            m.style.display = 'none';
            document.body.style.overflow = '';
        }
    };

    window.seleccionarTipoOperacion = function(tipo) {
        const hiddenInput = document.getElementById('modalTipoOperacion');
        if (hiddenInput) hiddenInput.value = tipo;

        const optEntrada = document.getElementById('optTipoEntrada');
        const optSalida = document.getElementById('optTipoSalida');
        const optAjuste = document.getElementById('optTipoAjuste');

        if (optEntrada) optEntrada.className = 'kardex-type-option' + (tipo === 'entrada' ? ' selected-entrada' : '');
        if (optSalida) optSalida.className = 'kardex-type-option' + (tipo === 'salida' ? ' selected-salida' : '');
        if (optAjuste) optAjuste.className = 'kardex-type-option' + (tipo === 'ajuste' ? ' selected-ajuste' : '');

        const contCant = document.getElementById('contenedorCantidadOperacion');
        const contNuevo = document.getElementById('contenedorNuevoStockOperacion');
        const lblCant = document.getElementById('lblCantidadOperacion');
        const inputCant = document.getElementById('inputCantidadModal');
        const inputNuevo = document.getElementById('inputNuevoStockModal');
        const btnSubmit = document.getElementById('btnGuardarAjusteModal');

        if (tipo === 'entrada') {
            if (contCant) contCant.style.display = 'block';
            if (contNuevo) contNuevo.style.display = 'none';
            if (lblCant) lblCant.textContent = 'Cantidad a Ingresar (+):';
            if (inputCant) { inputCant.required = true; inputCant.focus(); }
            if (inputNuevo) inputNuevo.required = false;
            if (btnSubmit) {
                btnSubmit.textContent = 'Guardar Ingreso (+)';
                btnSubmit.style.background = '#15803d';
            }
        } else if (tipo === 'salida') {
            if (contCant) contCant.style.display = 'block';
            if (contNuevo) contNuevo.style.display = 'none';
            if (lblCant) lblCant.textContent = 'Cantidad a Retirar / Salida (-):';
            if (inputCant) { inputCant.required = true; inputCant.focus(); }
            if (inputNuevo) inputNuevo.required = false;
            if (btnSubmit) {
                btnSubmit.textContent = 'Guardar Salida (-)';
                btnSubmit.style.background = '#dc2626';
            }
        } else {
            if (contCant) contCant.style.display = 'none';
            if (contNuevo) contNuevo.style.display = 'block';
            if (inputCant) inputCant.required = false;
            if (inputNuevo) { inputNuevo.required = true; inputNuevo.focus(); }
            if (btnSubmit) {
                btnSubmit.textContent = 'Guardar Ajuste Físico';
                btnSubmit.style.background = '#0284c7';
            }
        }

        window.actualizarPreviewModalAjuste();
    };

    // Selección de Producto en Tiempo Real en Modal
    window.seleccionarProductoModal = function(id, nombre, stock, sku) {
        const hiddenId = document.getElementById('modalIdProducto');
        const hiddenStock = document.getElementById('modalStockActualProd');
        const searchInput = document.getElementById('buscarProductoAjuste');
        const dropdown = document.getElementById('listaResultadosProductos');
        const selectedBadge = document.getElementById('selectedProdCard');
        const badgeName = document.getElementById('selectedProdNameText');
        const badgeStock = document.getElementById('selectedProdStockText');
        const clearBtn = document.getElementById('btnClearSelectedProd');

        if (hiddenId) hiddenId.value = id;
        if (hiddenStock) hiddenStock.value = stock;
        if (searchInput) {
            searchInput.value = nombre;
            searchInput.style.borderColor = '#15803d';
        }
        if (dropdown) dropdown.style.display = 'none';
        if (clearBtn) clearBtn.style.display = 'block';

        if (selectedBadge && badgeName && badgeStock) {
            badgeName.textContent = nombre + (sku && sku !== '-' ? ' (SKU: ' + sku + ')' : '');
            badgeStock.textContent = 'Stock actual: ' + stock + ' unds';
            selectedBadge.style.display = 'flex';
        }

        window.actualizarPreviewModalAjuste();
    };

    function configurarBuscadorModalProductos() {
        const input = document.getElementById('buscarProductoAjuste');
        const dropdown = document.getElementById('listaResultadosProductos');
        const clearBtn = document.getElementById('btnClearSelectedProd');
        const hiddenId = document.getElementById('modalIdProducto');
        const hiddenStock = document.getElementById('modalStockActualProd');
        const selectedBadge = document.getElementById('selectedProdCard');

        if (!input || !dropdown) return;

        const items = Array.from(dropdown.querySelectorAll('.kardex-prod-result-item'));

        input.addEventListener('focus', function() {
            dropdown.style.display = 'block';
            filtrarItems(this.value.trim().toLowerCase());
        });

        input.addEventListener('input', function() {
            dropdown.style.display = 'block';
            const val = this.value.trim().toLowerCase();
            filtrarItems(val);

            // Si se borra el texto, resetear selección
            if (!val) {
                if (hiddenId) hiddenId.value = '';
                if (hiddenStock) hiddenStock.value = '0';
                if (selectedBadge) selectedBadge.style.display = 'none';
                if (clearBtn) clearBtn.style.display = 'none';
                input.style.borderColor = '#cbd5e1';
                window.actualizarPreviewModalAjuste();
            }
        });

        function filtrarItems(text) {
            let visibleCount = 0;
            items.forEach(item => {
                const nombre = (item.getAttribute('data-nombre') || '').toLowerCase();
                const sku = (item.getAttribute('data-sku') || '').toLowerCase();
                const match = !text || nombre.includes(text) || sku.includes(text);
                item.style.display = match ? 'flex' : 'none';
                if (match) visibleCount++;
            });
        }

        if (clearBtn) {
            clearBtn.addEventListener('click', function() {
                input.value = '';
                if (hiddenId) hiddenId.value = '';
                if (hiddenStock) hiddenStock.value = '0';
                if (selectedBadge) selectedBadge.style.display = 'none';
                clearBtn.style.display = 'none';
                input.style.borderColor = '#cbd5e1';
                dropdown.style.display = 'block';
                filtrarItems('');
                input.focus();
                window.actualizarPreviewModalAjuste();
            });
        }

        // Cerrar dropdown si se hace click fuera
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.kardex-prod-search-wrapper')) {
                dropdown.style.display = 'none';
            }
        });
    }

    window.actualizarPreviewModalAjuste = function() {
        const hiddenId = document.getElementById('modalIdProducto')?.value;
        const stockActual = parseInt(document.getElementById('modalStockActualProd')?.value || 0);
        const tipo = document.getElementById('modalTipoOperacion')?.value || 'entrada';
        const inputCant = document.getElementById('inputCantidadModal');
        const inputNuevo = document.getElementById('inputNuevoStockModal');

        const spanActual = document.getElementById('previewStockActual');
        const spanResultante = document.getElementById('previewStockResultante');
        if (!spanActual || !spanResultante) return;

        if (!hiddenId) {
            spanActual.textContent = '-';
            spanResultante.textContent = '-';
            return;
        }

        spanActual.textContent = stockActual + ' unds';

        let stockResultante = stockActual;
        if (tipo === 'entrada') {
            const cant = parseInt(inputCant?.value || 0);
            stockResultante = stockActual + (cant > 0 ? cant : 0);
            spanResultante.style.color = '#15803d';
        } else if (tipo === 'salida') {
            const cant = parseInt(inputCant?.value || 0);
            stockResultante = Math.max(0, stockActual - (cant > 0 ? cant : 0));
            spanResultante.style.color = (stockActual - cant < 0) ? '#dc2626' : '#b91c1c';
        } else {
            const nuevo = parseInt(inputNuevo?.value || 0);
            stockResultante = isNaN(nuevo) ? stockActual : nuevo;
            spanResultante.style.color = '#0284c7';
        }

        spanResultante.textContent = stockResultante + ' unds';
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initKardex);
    } else {
        initKardex();
    }
})();
