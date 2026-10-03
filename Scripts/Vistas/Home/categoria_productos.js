$(document).ready(function () {
            $(document).on('click', '#openSidebar', function () {
                $('#mobileSidebar, #sidebarOverlay').addClass('active open');
                $('body').css('overflow', 'hidden');
            });
            $(document).on('click', '#closeSidebar, #sidebarOverlay', function () {
                cerrarSidebar();
            });

            window.cerrarSidebar = function() {
                $('#mobileSidebar, #sidebarOverlay').removeClass('active open');
                $('body').css('overflow', '');
            };

            window.toggleSidebarAccordion = function(btn) {
                const parent = btn.closest('.sidebar-acc-item, .sidebar-subacc-item');
                if (!parent) return;
                parent.classList.toggle('open');
            };
        });

        function cerrarModal(id) {
            const modal = document.getElementById(id);
            if (modal) modal.classList.remove('active');
        }

        document.querySelectorAll('.modal-overlay').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    cerrarModal(this.id);
                }
            });
        });

        let currentDetailProduct = null;
        let detailQty = 1;

        function abrirDetalleProducto(idProducto) {
            window.location.href = 'index.php?action=detalle_producto&id=' + idProducto;
        }

        function cambiarCantidadDetalle(delta) {
            detailQty = Math.max(1, detailQty + delta);
            document.getElementById('detailQuantity').textContent = detailQty;
            if (currentDetailProduct) {
                const precio = parseFloat(currentDetailProduct.precio) || 0;
                document.getElementById('detailSubtotal').textContent = 'Subtotal: S/ ' + (precio * detailQty).toFixed(2);
            }
        }

        function agregarDesdeDetalle() {
            if (!currentDetailProduct) return;
            const id = currentDetailProduct.id_producto;
            if (typeof window.agregarProducto === 'function') {
                window.agregarProducto(id, null, detailQty);
            } else if (typeof agregarAlCarrito === 'function') {
                agregarAlCarrito(id, detailQty);
            }
            cerrarModal('modalDetalleProducto');
        }

        function cambiarCardQty(btn, delta) {
            if (window.cambiarCardQty && window.cambiarCardQty !== cambiarCardQty) {
                return window.cambiarCardQty(btn, delta);
            }
            const card = btn.closest('.producto-card, .categoria-item-card, .mini-product-card, .also-like-card-item');
            const row = btn.closest('.product-qty-pill') || (card ? card.querySelector('.product-qty-pill') : null);
            if (!row) return;
            const valSpan = row.querySelector('.card-qty-val');
            if (!valSpan) return;
            let val = parseInt(valSpan.textContent, 10) || 1;
            val = Math.max(1, val + delta);
            valSpan.textContent = val;
        }

        function agregarConQty(btn, idProducto) {
            const card = btn.closest('.producto-card, .categoria-item-card');
            let qty = 1;
            if (card) {
                delete card.dataset.userEditing;
                const valSpan = card.querySelector('.card-qty-val');
                if (valSpan) {
                    qty = parseInt(valSpan.textContent, 10) || 1;
                }
            }
            if (typeof window.procesarAccionBotonCarrito === 'function') {
                window.procesarAccionBotonCarrito(idProducto, btn, qty);
            } else if (typeof window.agregarProducto === 'function') {
                window.agregarProducto(idProducto, btn, qty);
            } else if (typeof agregarAlCarrito === 'function') {
                agregarAlCarrito(idProducto, qty, btn);
            }
        }

        $(document).ready(function() {
            function aplicarFiltrosSidebar() {
                const seleccionados = Array.from(document.querySelectorAll('.filtro-subcat-chk:checked')).map(cb => cb.value);
                const precioMax = parseFloat($('#precioMaximoSlider').val()) || 100;
                let orden = $('#selectOrdenCatalogo').val() || $('#selectOrdenCatalogoMobile').val() || 'mas_vendidos';

                const grid = document.getElementById('productosCatalogoGrid');
                if (!grid) return;

                const cards = Array.from(grid.querySelectorAll('.producto-card'));
                let visibles = 0;

                cards.forEach(card => {
                    const precio = parseFloat(card.dataset.precio || 0);
                    const tags = (card.dataset.tags || '').split('|');

                    let show = true;
                    if (precio > precioMax) show = false;

                    if (show && seleccionados.length > 0) {
                        const matchAny = seleccionados.some(sel => tags.includes(sel));
                        if (!matchAny) show = false;
                    }

                    card.style.display = show ? '' : 'none';
                    if (show) visibles++;
                });

                const countEl = document.getElementById('categoriaProductCount');
                if (countEl) {
                    countEl.textContent = visibles + (visibles === 1 ? ' producto' : ' productos');
                }

                // Insignia verde en botón móvil si hay filtros activos
                const totalFiltrosActivos = seleccionados.length;
                if (totalFiltrosActivos > 0) {
                    $('#mobileFilterBadge').text(totalFiltrosActivos).show();
                } else {
                    $('#mobileFilterBadge').hide();
                }

                cards.sort((a, b) => {
                    if (orden === 'menor_precio') {
                        return parseFloat(a.dataset.precio || 0) - parseFloat(b.dataset.precio || 0);
                    } else if (orden === 'mayor_descuento') {
                        return parseFloat(b.dataset.descuento || 0) - parseFloat(a.dataset.descuento || 0);
                    } else if (orden === 'mas_vendidos') {
                        return parseInt(b.dataset.sugerencia || 0) - parseInt(a.dataset.sugerencia || 0);
                    }
                    return 0;
                });

                cards.forEach(card => grid.appendChild(card));
            }

            // Sincronización Desktop <-> Mobile para Checkboxes
            $(document).on('change', '.filtro-subcat-chk', function() {
                const val = $(this).val();
                const isChecked = $(this).is(':checked');
                $('.filtro-subcat-chk[value="' + val + '"]').prop('checked', isChecked);
                aplicarFiltrosSidebar();
            });

            // Sincronización de Selector Ordenar Por
            $(document).on('change', '#selectOrdenCatalogo, #selectOrdenCatalogoMobile', function() {
                const val = $(this).val();
                $('#selectOrdenCatalogo, #selectOrdenCatalogoMobile').val(val);
                aplicarFiltrosSidebar();
            });

            // Sincronización del Slider de Precios
            $('#precioMaximoSlider, #precioMaximoSliderMobile').on('input change', function() {
                const val = parseFloat(this.value);
                $('#precioMaximoSlider, #precioMaximoSliderMobile').val(val);
                const txtUpper = 'S/ 0.00 - S/ ' + val.toFixed(2) + (val >= 100 ? '+' : '');
                $('#precioRangoTopTxt, #precioRangoTopTxtMobile').text(txtUpper);
                $('#precioMaxValueTxt, #precioMaxValueTxtMobile').text('S/ ' + val.toFixed(2));
                aplicarFiltrosSidebar();
            });

            // Abrir Drawer de Filtros Móvil (IMAGEN 3)
            $('#btnOpenMobileFiltros').on('click', function() {
                $('#mobileFiltrosOverlay').addClass('active');
                $('body').css('overflow', 'hidden');
            });

            // Cerrar Drawer
            $('#btnCloseMobileFiltros, #btnAplicarFiltrosMobile').on('click', function() {
                $('#mobileFiltrosOverlay').removeClass('active');
                $('body').css('overflow', '');
            });

            $('#mobileFiltrosOverlay').on('click', function(e) {
                if (e.target === this) {
                    $('#mobileFiltrosOverlay').removeClass('active');
                    $('body').css('overflow', '');
                }
            });

            // Botón Limpiar Filtros (Desktop + Mobile)
            $('#btnLimpiarDesktop, #btnLimpiarFiltrosMobile').on('click', function() {
                $('.filtro-subcat-chk').prop('checked', false);
                $('#precioMaximoSlider, #precioMaximoSliderMobile').val(100);
                $('#precioRangoTopTxt, #precioRangoTopTxtMobile').text('S/ 0.00 - S/ 100.00+');
                $('#precioMaxValueTxt, #precioMaxValueTxtMobile').text('S/ 100.00');
                $('#selectOrdenCatalogo, #selectOrdenCatalogoMobile').val('mas_vendidos');
                aplicarFiltrosSidebar();
            });

            const initVal = parseFloat($('#precioMaximoSlider').val() || 100);
            $('#precioRangoTopTxt, #precioRangoTopTxtMobile').text('S/ 0.00 - S/ ' + initVal.toFixed(2) + (initVal >= 100 ? '+' : ''));
            $('#precioMaxValueTxt, #precioMaxValueTxtMobile').text('S/ ' + initVal.toFixed(2));
        });
