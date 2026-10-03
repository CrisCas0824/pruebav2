window.previewImgModal = function(input, imgId, labelId) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.getElementById(imgId);
                    if (img) {
                        img.src = e.target.result;
                        img.style.display = 'block';
                    }
                    const lbl = document.getElementById(labelId);
                    if (lbl) {
                        lbl.textContent = input.files[0].name;
                        lbl.style.color = '#15803d';
                    }
                };
                reader.readAsDataURL(input.files[0]);
            }
        };

        window.calcDescuentoNuevo = function() {
            const chk = document.getElementById('nuevoProdEnOferta');
            const isOffer = chk ? chk.checked : false;
            const baseEl = document.getElementById('nuevoProdPrecioAnterior');
            const ofertaEl = document.getElementById('nuevoProdPrecioInput');
            const badge = document.getElementById('badgePctNuevo');
            
            const base = parseFloat(baseEl ? baseEl.value : 0) || 0;
            const oferta = parseFloat(ofertaEl ? ofertaEl.value : 0) || 0;

            if (isOffer && base > 0 && oferta > 0 && base > oferta) {
                const pct = Math.round(((base - oferta) / base) * 100);
                if (badge) {
                    badge.style.display = 'inline-block';
                    badge.innerHTML = `🔥 ¡<strong>${pct}%</strong> DE DESCUENTO!`;
                }
            } else if (badge) {
                badge.style.display = 'none';
            }
        };

        window.calcDescuentoEdit = function() {
            const chk = document.getElementById('editProdEnOferta');
            const isOffer = chk ? chk.checked : false;
            const baseEl = document.getElementById('editProdPrecioAnterior');
            const ofertaEl = document.getElementById('editProdPrecio');
            const badge = document.getElementById('badgePctEdit');
            
            const base = parseFloat(baseEl ? baseEl.value : 0) || 0;
            const oferta = parseFloat(ofertaEl ? ofertaEl.value : 0) || 0;

            if (isOffer && base > 0 && oferta > 0 && base > oferta) {
                const pct = Math.round(((base - oferta) / base) * 100);
                if (badge) {
                    badge.style.display = 'inline-block';
                    badge.innerHTML = `🔥 ¡<strong>${pct}%</strong> DE DESCUENTO!`;
                }
            } else if (badge) {
                badge.style.display = 'none';
            }
        };

        window.toggleOfertaCamposNuevo = function(checked) {
            const wrap = document.getElementById('wrapPrecioInicialNuevo');
            const lbl = document.getElementById('lblPrecioNuevo');
            if (wrap) wrap.style.display = checked ? 'block' : 'none';
            if (lbl) lbl.textContent = checked ? 'PRECIO OFERTA ESPECIAL (S/) *' : 'PRECIO VENTA (S/) *';
            calcDescuentoNuevo();
        };

        window.toggleOfertaCamposEdit = function(checked) {
            const wrap = document.getElementById('wrapPrecioInicialEdit');
            const lbl = document.getElementById('lblPrecioEdit');
            if (wrap) wrap.style.display = checked ? 'block' : 'none';
            if (lbl) lbl.textContent = checked ? 'PRECIO OFERTA ESPECIAL (S/) *' : 'PRECIO VENTA (S/) *';
            calcDescuentoEdit();
        };

        // Cascadas Selector 3 Niveles en Modal Nuevo (Habilitación progresiva N1 -> N2 -> N3)
        window.onNuevoMomentoChange = function(momentoId) {
            const selG = document.getElementById('nuevoGrupoSelect');
            const selC = document.getElementById('nuevoCategoriaSelect');
            if (!selG || !selC) return;
            selG.value = '';
            selC.value = '';

            if (!momentoId) {
                selG.disabled = true;
                selC.disabled = true;
                selG.options[0].textContent = '-- Seleccione N1 primero --';
                selC.options[0].textContent = '-- Seleccione N2 primero --';
                return;
            }

            selG.disabled = false;
            selG.options[0].textContent = '-- Seleccionar Categoría (N2) --';
            selC.disabled = true;
            selC.options[0].textContent = '-- Seleccione N2 primero --';

            Array.from(selG.options).forEach(opt => {
                if (opt.value === '') {
                    opt.style.display = '';
                } else if (opt.dataset.momento == momentoId) {
                    opt.style.display = '';
                } else {
                    opt.style.display = 'none';
                }
            });
        };

        window.onNuevoGrupoChange = function(grupoId) {
            const selC = document.getElementById('nuevoCategoriaSelect');
            if (!selC) return;
            selC.value = '';

            if (!grupoId) {
                selC.disabled = true;
                selC.options[0].textContent = '-- Seleccione N2 primero --';
                return;
            }

            selC.disabled = false;
            selC.options[0].textContent = '-- Seleccionar Subcategoría (N3) --';

            Array.from(selC.options).forEach(opt => {
                if (opt.value === '') {
                    opt.style.display = '';
                } else if (opt.dataset.grupo == grupoId) {
                    opt.style.display = '';
                } else {
                    opt.style.display = 'none';
                }
            });
        };

        // Cascadas Selector 3 Niveles en Modal Editar (Habilitación progresiva N1 -> N2 -> N3)
        window.onEditMomentoChange = function(momentoId) {
            const selG = document.getElementById('editProdGrupo');
            const selC = document.getElementById('editProdCategoria');
            if (!selG || !selC) return;

            if (!momentoId) {
                selG.disabled = true;
                selC.disabled = true;
                selG.options[0].textContent = '-- Seleccione N1 primero --';
                selC.options[0].textContent = '-- Seleccione N2 primero --';
                return;
            }

            selG.disabled = false;
            selG.options[0].textContent = '-- Seleccionar Categoría (N2) --';

            Array.from(selG.options).forEach(opt => {
                if (opt.value === '') {
                    opt.style.display = '';
                } else if (opt.dataset.momento == momentoId) {
                    opt.style.display = '';
                } else {
                    opt.style.display = 'none';
                }
            });
        };

        window.onEditGrupoChange = function(grupoId) {
            const selC = document.getElementById('editProdCategoria');
            if (!selC) return;

            if (!grupoId) {
                selC.disabled = true;
                selC.options[0].textContent = '-- Seleccione N2 primero --';
                return;
            }

            selC.disabled = false;
            selC.options[0].textContent = '-- Seleccionar Subcategoría (N3) --';

            Array.from(selC.options).forEach(opt => {
                if (opt.value === '') {
                    opt.style.display = '';
                } else if (opt.dataset.grupo == grupoId) {
                    opt.style.display = '';
                } else {
                    opt.style.display = 'none';
                }
            });
        };

        window.abrirModalCrearProducto = function() {
            window.abrirModal('modalNuevoProducto');
        };

        window.confirmarEliminarProducto = function(id, nombre, ventasCount) {
            const btnLink = document.getElementById('btnEliminarProdLink');
            const txt = document.getElementById('textoEliminarProd');
            if (ventasCount && parseInt(ventasCount) > 0) {
                txt.textContent = 'No se puede eliminar el producto "' + nombre + '" porque registra ' + ventasCount + ' venta(s) en la tienda. Puedes cambiar su estado a Inactivo para ocultarlo.';
                if (btnLink) btnLink.style.display = 'none';
            } else {
                txt.textContent = '¿Estás seguro de que deseas eliminar el producto "' + nombre + '"? Esta acción no se puede deshacer.';
                if (btnLink) {
                    btnLink.style.display = 'inline-flex';
                    btnLink.href = 'index.php?action=eliminar_producto&id=' + id + '&redirect_to=inventario';
                }
            }

            window.abrirModal('modalConfirmarEliminarProd');
        };

        window.confirmarCambioEstadoProducto = function(id, estado, nombre) {
            const activar = Number(estado) === 1;
            const icono = document.getElementById('iconoConfirmarEstadoProd');
            const titulo = document.getElementById('tituloConfirmarEstadoProd');
            const texto = document.getElementById('textoConfirmarEstadoProd');
            const boton = document.getElementById('btnConfirmarEstadoProd');
            if (!icono || !titulo || !texto || !boton) return;

            const accion = activar ? 'Activar' : 'Inactivar';
            titulo.textContent = accion + ' producto';
            texto.textContent = '¿Confirmas ' + accion.toLowerCase() + ' "' + nombre + '"?';
            icono.style.background = activar ? '#dcfce7' : '#fee2e2';
            icono.style.color = activar ? '#15803d' : '#dc2626';
            icono.innerHTML = activar
                ? '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="m8 12 2.5 2.5L16 9"></path></svg>'
                : '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line></svg>';
            boton.textContent = accion;
            boton.style.background = activar ? '#15803d' : '#dc2626';
            boton.href = 'index.php?action=toggle_status&id=' + encodeURIComponent(id) + '&estado=' + (activar ? 1 : 0) + '&redirect_to=inventario';
            window.abrirModal('modalConfirmarEstadoProd');
        };

        window.abrirModal = function(id) {
            const m = document.getElementById(id);
            if (m) {
                if (!m.__modalOriginalParent) {
                    m.__modalOriginalParent = m.parentElement;
                }
                document.body.appendChild(m);
                m.classList.add('active');
                m.style.position = 'fixed';
                m.style.inset = '0';
                m.style.zIndex = '12000';
                m.style.display = 'flex';
                m.style.visibility = 'visible';
                m.style.opacity = '1';
                document.body.style.overflow = 'hidden';
            }
        };

        window.cerrarModal = function(id) {
            const m = document.getElementById(id);
            if (m) {
                m.classList.remove('active');
                m.style.display = 'none';
                const originalParent = m.__modalOriginalParent;
                if (originalParent && originalParent.isConnected && m.parentElement === document.body) {
                    originalParent.appendChild(m);
                }
            }
            if (!document.querySelector('.modal-overlay.active')) {
                document.body.style.overflow = '';
            }
        };

        window.abrirModalReposicion = function(idProducto, nombreProducto, unidad, sku = '', codigoBarras = '') {
            const idInput = document.getElementById('repoIdProducto');
            const nomText = document.getElementById('repoNombreProducto');
            const uniText = document.getElementById('repoUnidadTexto');
            const codigoText = document.getElementById('repoCodigoProducto');
            const codigoSearch = document.getElementById('repoCodigoBusqueda');

            if (idInput) idInput.value = idProducto;
            if (nomText) nomText.textContent = nombreProducto;
            if (uniText) uniText.textContent = unidad;
            if (codigoText) codigoText.textContent = [sku ? `SKU: ${sku}` : '', codigoBarras ? `Cód: ${codigoBarras}` : ''].filter(Boolean).join(' | ') || 'Sin códigos registrados';
            if (codigoSearch) codigoSearch.value = codigoBarras || sku || '';

            window.abrirModal('modalReposicion');
        };

        window.abrirModalEditarProducto = function(item) {
            if (typeof item === 'string') {
                try { item = JSON.parse(item); } catch (e) { return; }
            }
            if (!item) return;

            const setVal = (id, val) => {
                const el = document.getElementById(id);
                if (el) el.value = (val !== null && val !== undefined) ? val : '';
            };

            setVal('editProdId', item.id_producto);
            setVal('editProdTitulo', item.titulo);
            setVal('editProdCodigo', item.codigo || ('PROD-' + String(item.id_producto).padStart(4, '0')));
            setVal('editProdCodigoBarras', item.codigo_barras || '');
            setVal('editProdUnidad', item.unidad_medida || 'UND');
            setVal('editProdStock', parseFloat(item.stock_actual !== undefined ? item.stock_actual : (item.stock || 0)));
            setVal('editProdStockMinimo', parseFloat(item.stock_minimo || 5));
            setVal('editProdUbicacion', item.ubicacion || '');
            setVal('editProdDescripcion', item.descripcion || '');
            setVal('editProdImagenActual', item.imagen || '');
            setVal('editProdImagenUrl', item.imagen || '');

            setVal('editProdImagenActual2', item.imagen_2 || '');
            setVal('editProdImagenUrl2', item.imagen_2 || '');
            setVal('editProdImagenActual3', item.imagen_3 || '');
            setVal('editProdImagenUrl3', item.imagen_3 || '');
            setVal('editProdImagenActual4', item.imagen_4 || '');
            setVal('editProdImagenUrl4', item.imagen_4 || '');

            const chkSug = document.getElementById('editProdEsSugerencia');
            if (chkSug) chkSug.checked = (parseInt(item.es_sugerencia) === 1);

            const tieneOferta = item.precio_anterior && parseFloat(item.precio_anterior) > parseFloat(item.precio);
            const chkOferta = document.getElementById('editProdEnOferta');
            if (chkOferta) {
                chkOferta.checked = tieneOferta;
                toggleOfertaCamposEdit(tieneOferta);
            }
            if (tieneOferta) {
                setVal('editProdPrecioAnterior', parseFloat(item.precio_anterior).toFixed(2));
                setVal('editProdPrecio', parseFloat(item.precio).toFixed(2));
            } else {
                setVal('editProdPrecioAnterior', '');
                setVal('editProdPrecio', parseFloat(item.precio || 0).toFixed(2));
            }
            calcDescuentoEdit();

            const mId = item.id_momento || '';
            const gId = item.id_grupo || '';
            const cId = item.id_categoria || '';

            setVal('editProdMomento', mId);
            onEditMomentoChange(mId);
            setVal('editProdGrupo', gId);
            onEditGrupoChange(gId);
            setVal('editProdCategoria', cId);

            const preview = document.getElementById('editProdPreviewImg');
            if (preview) {
                preview.src = item.imagen || 'Images/logo.png';
                preview.style.display = 'block';
            }

            ['2', '3', '4'].forEach(num => {
                const p = document.getElementById('prevEdit' + num);
                const val = item['imagen_' + num];
                if (p) {
                    if (val) {
                        p.src = val;
                        p.style.display = 'block';
                    } else {
                        p.style.display = 'none';
                    }
                }
            });

            window.abrirModal('modalEditarProducto');
        };

        window.activeStatFilter = 'all';
        window.filterByStatCard = function(type, element) {
            window.activeStatFilter = type;
            window.activeCodeFilter = 'all';
            document.querySelectorAll('.stats-grid-square .stat-card-square').forEach(c => c.classList.remove('active-card'));
            document.querySelectorAll('.inventory-code-metric').forEach(c => { c.classList.remove('active'); c.setAttribute('aria-pressed', 'false'); });
            if (element) {
                element.classList.add('active-card');
            }
            if (typeof window.triggerTableUpdate === 'function') {
                window.triggerTableUpdate();
            }
        };

        // Paginación de cinco productos e interacción completa
        function initializeInventoryTable() {
            const table = document.getElementById('inventarioTable');
            if (!table) return;
            
            const tbody = table.querySelector('tbody');
            if (!tbody) return;

            const allRows = Array.from(tbody.querySelectorAll('tr[data-id]'));
            const searchInput = document.getElementById('searchInventario');
            const selectOrden = document.getElementById('selectOrdenInventario');
            
            const itemsPerPage = 5;
            let currentPage = 1;

            function goToPage(page) {
                currentPage = page;
                filterAndPaginate();
                const tableEl = document.getElementById('inventarioTable');
                if (tableEl) {
                    tableEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }

            window.triggerTableUpdate = function() {
                currentPage = 1;
                filterAndPaginate();
            };

            function filterAndPaginate() {
                const query = (searchInput ? searchInput.value : '').toLowerCase().trim();
                
                // 1. Filtrar filas
                let filteredRows = allRows.filter(row => {
                    if (query && !row.textContent.toLowerCase().includes(query)) {
                        return false;
                    }
                    if (window.activeCodeFilter === 'sku' && row.dataset.hasSku !== '1') return false;
                    if (window.activeCodeFilter === 'barcode' && row.dataset.hasBarcode !== '1') return false;

                    const stock = parseFloat(row.dataset.stock || '0');
                    const stockmin = parseFloat(row.dataset.stockmin || '0');
                    const vendidos = parseInt(row.dataset.vendidos || '0', 10);
                    const isActivo = row.dataset.activo === '1';

                    if (window.activeStatFilter === 'activos') {
                        return isActivo;
                    } else if (window.activeStatFilter === 'inactivos') {
                        return !isActivo;
                    } else if (window.activeStatFilter === 'disponible') {
                        return stock > stockmin;
                    } else if (window.activeStatFilter === 'stock_bajo' || window.activeStatFilter === 'reposiciones') {
                        return stock <= stockmin;
                    } else if (window.activeStatFilter === 'mas_vendido') {
                        return vendidos > 0;
                    } else if (window.activeStatFilter === 'menos_vendido') {
                        return vendidos === 0;
                    }

                    return true;
                });

                // 2. Ordenar filas
                const ordenVal = selectOrden ? selectOrden.value : 'recomendados';
                if (ordenVal === 'nombre_asc') {
                    filteredRows.sort((a, b) => a.textContent.localeCompare(b.textContent));
                } else if (ordenVal === 'stock_asc') {
                    filteredRows.sort((a, b) => parseFloat(a.dataset.stock) - parseFloat(b.dataset.stock));
                } else if (ordenVal === 'stock_desc') {
                    filteredRows.sort((a, b) => parseFloat(b.dataset.stock) - parseFloat(a.dataset.stock));
                } else if (ordenVal === 'mas_vendidos') {
                    filteredRows.sort((a, b) => parseInt(b.dataset.vendidos) - parseInt(a.dataset.vendidos));
                }

                const totalItems = filteredRows.length;
                const totalPages = Math.ceil(totalItems / itemsPerPage) || 1;

                if (currentPage > totalPages) currentPage = totalPages;
                if (currentPage < 1) currentPage = 1;

                const startIdx = (currentPage - 1) * itemsPerPage;
                const endIdx = startIdx + itemsPerPage;

                allRows.forEach(row => row.style.display = 'none');
                filteredRows.slice(startIdx, endIdx).forEach(row => row.style.display = '');

                // 3. Render numérico estilo Imagen 2: < 1 2 3 ... 200 >
                const pagesContainer = document.getElementById('paginationPagesList');
                if (pagesContainer) {
                    pagesContainer.innerHTML = '';

                    // Flecha Anterior <
                    const prevBtn = document.createElement('button');
                    prevBtn.type = 'button';
                    prevBtn.className = 'pagination-page-button';
                    prevBtn.innerHTML = '&lsaquo;';
                    prevBtn.setAttribute('aria-label', 'Pagina anterior');
                    if (currentPage === 1) {
                        prevBtn.disabled = true;
                    } else {
                        prevBtn.onclick = function() { goToPage(currentPage - 1); };
                    }
                    pagesContainer.appendChild(prevBtn);

                    // Botones de página
                    for (let p = 1; p <= totalPages; p++) {
                        if (totalPages > 7 && p > 2 && p < totalPages - 1 && Math.abs(p - currentPage) > 1) {
                            if (p === 3 || p === totalPages - 2) {
                                const dots = document.createElement('span');
                                dots.textContent = '...';
                                dots.style.cssText = 'color:#94a3b8; padding:0 4px; font-weight:700; font-size:0.9rem;';
                                pagesContainer.appendChild(dots);
                            }
                            continue;
                        }

                        const pageBtn = document.createElement('button');
                        pageBtn.type = 'button';
                        pageBtn.textContent = p;
                        pageBtn.className = 'pagination-page-button';
                        pageBtn.setAttribute('aria-label', 'Pagina ' + p);
                        
                        if (p === currentPage) {
                            pageBtn.classList.add('is-current');
                            pageBtn.setAttribute('aria-current', 'page');
                        } else {
                            pageBtn.onclick = (function(page) {
                                return function() { goToPage(page); };
                            })(p);
                        }
                        pagesContainer.appendChild(pageBtn);
                    }

                    // Flecha Siguiente >
                    const nextBtn = document.createElement('button');
                    nextBtn.type = 'button';
                    nextBtn.className = 'pagination-page-button';
                    nextBtn.innerHTML = '&rsaquo;';
                    nextBtn.setAttribute('aria-label', 'Pagina siguiente');
                    if (currentPage >= totalPages) {
                        nextBtn.disabled = true;
                    } else {
                        nextBtn.onclick = function() { goToPage(currentPage + 1); };
                    }
                    pagesContainer.appendChild(nextBtn);
                }
            }

            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    currentPage = 1;
                    filterAndPaginate();
                });
            }

            filterAndPaginate();
        }
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initializeInventoryTable, { once: true });
        } else {
            initializeInventoryTable();
        }
