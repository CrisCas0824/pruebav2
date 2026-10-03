document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('busqueda');
    const searchClear = document.getElementById('searchClear');
    const searchDropdown = document.getElementById('searchDropdown');
    const searchSuggestionsList = document.getElementById('searchSuggestionsList');
    const searchQuickTerm = document.getElementById('searchQuickTerm');
    const searchQuickProducts = document.getElementById('searchQuickProducts');
    const searchViewAllBtn = document.getElementById('searchViewAllBtn');
    
    const searchResultsSection = document.getElementById('searchResultsSection');
    const mainContent = document.getElementById('mainContent');
    const searchResultsTerm = document.getElementById('searchResultsTerm');
    const searchResultsCount = document.getElementById('searchResultsCount');
    const searchResultsGrid = document.getElementById('searchResultsGrid');

    let currentTerm = '';

    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            const term = e.target.value.trim().toLowerCase();
            currentTerm = term;

            if (term.length > 0) {
                if (searchClear) searchClear.style.display = 'block';
                if (searchDropdown) {
                    searchDropdown.classList.add('active');
                    searchDropdown.style.display = 'block';
                }
                realizarBusqueda(term);
            } else {
                if (searchClear) searchClear.style.display = 'none';
                if (searchDropdown) {
                    searchDropdown.classList.remove('active');
                    searchDropdown.style.display = 'none';
                }
            }
        });

        if (searchClear) {
            searchClear.addEventListener('click', () => {
                searchInput.value = '';
                searchInput.focus();
                searchClear.style.display = 'none';
                if (searchDropdown) {
                    searchDropdown.classList.remove('active');
                    searchDropdown.style.display = 'none';
                }
            });
        }

        searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                mostrarResultadosCompletos(currentTerm);
            }
        });
    }

    
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.search-container')) {
            if (searchDropdown) {
                searchDropdown.classList.remove('active');
                searchDropdown.style.display = 'none';
            }
        }
    });

    if (searchViewAllBtn) {
        searchViewAllBtn.addEventListener('click', () => {
            mostrarResultadosCompletos(currentTerm);
        });
    }

    function realizarBusqueda(term) {
        if (!window.productosBD) return;

        const resultados = window.productosBD.filter(p => p.titulo.toLowerCase().includes(term));
        const suggestions = new Set();
        if (resultados.length > 0) {
            resultados.forEach(p => {
                const words = p.titulo.toLowerCase().split(/\s+/);
                words.forEach(w => {
                    if (w.includes(term)) {
                        suggestions.add(w);
                    }
                });
            });
        }

        if (searchSuggestionsList) {
            searchSuggestionsList.innerHTML = '';
            Array.from(suggestions).slice(0, 8).forEach(sug => {
                const li = document.createElement('li');
                li.innerHTML = sug.replace(new RegExp(term, 'gi'), match => `<strong>${match}</strong>`);
                li.addEventListener('click', () => {
                    if (searchInput) {
                        searchInput.value = sug;
                        currentTerm = sug;
                        realizarBusqueda(sug);
                        searchInput.focus();
                    }
                });
                searchSuggestionsList.appendChild(li);
            });

            if (suggestions.size === 0) {
                searchSuggestionsList.innerHTML = '<li>No se encontraron sugerencias</li>';
            }
        }

        if (searchQuickTerm) searchQuickTerm.textContent = term;
        if (searchQuickProducts) {
            searchQuickProducts.innerHTML = '';
            
            // Requerimiento: Solo debe mostrar el título del producto, no la tarjeta con imagen.
            resultados.slice(0, 5).forEach(p => {
                const titleItem = document.createElement('div');
                titleItem.style.padding = '10px 15px';
                titleItem.style.cursor = 'pointer';
                titleItem.style.borderBottom = '1px solid #eee';
                titleItem.style.fontSize = '14px';
                titleItem.innerHTML = p.titulo.replace(new RegExp(term, 'gi'), match => `<strong>${match}</strong>`);
                
                titleItem.addEventListener('click', () => { 
                    window.location.href = `index.php?action=detalle_producto&id=${p.id_producto}`; 
                });
                
                searchQuickProducts.appendChild(titleItem);
            });

            if (resultados.length === 0) {
                searchQuickProducts.innerHTML = '<p style="padding:15px; color:#666;">No hay productos que coincidan.</p>';
                if (searchViewAllBtn) searchViewAllBtn.style.display = 'none';
            } else if (searchViewAllBtn) {
                searchViewAllBtn.style.display = 'block';
                searchViewAllBtn.textContent = `VER LOS ${resultados.length} PRODUCTOS`;
            }
        }
    }

    window.mostrarResultadosCompletos = function(term) {
        if (!window.productosBD || !term) return;

        if (searchDropdown) {
            searchDropdown.classList.remove('active');
            searchDropdown.style.display = 'none';
        }

        const resultados = window.productosBD.filter(p => p.titulo.toLowerCase().includes(term));

        if (searchResultsTerm) searchResultsTerm.textContent = term;
        if (searchResultsCount) searchResultsCount.textContent = resultados.length;
        
        if (mainContent) mainContent.style.display = 'none';
        if (searchResultsSection) searchResultsSection.style.display = 'block';
        
        if (searchResultsGrid) {
            searchResultsGrid.innerHTML = '';

            if (resultados.length === 0) {
                searchResultsGrid.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 50px;"><h3>No se encontraron productos</h3><p>Intenta con otros términos de búsqueda.</p></div>';
                const footer = document.getElementById('searchResultsFooter');
                if (footer) footer.style.display = 'none';
                return;
            }

            resultados.sort((a, b) => parseInt(b.es_sugerencia) - parseInt(a.es_sugerencia));

            resultados.forEach(producto => {
                const card = document.createElement('div');
                const isSugerencia = parseInt(producto.es_sugerencia) === 1;
                
                card.className = isSugerencia ? 'producto-card sugerencia-card' : 'producto-card';
                
                const precioFormatted = parseFloat(producto.precio).toFixed(2);
                const tieneDesc = producto.precio_anterior && parseFloat(producto.precio_anterior) > parseFloat(producto.precio);
                const pctDesc = tieneDesc ? Math.round(((parseFloat(producto.precio_anterior) - parseFloat(producto.precio)) / parseFloat(producto.precio_anterior)) * 100) : 0;
                const badgeHtml = (tieneDesc && pctDesc > 0) ? `<span class="badge-descuento-porcentual">-${pctDesc}%</span>` : '';
                const oldPriceHtml = tieneDesc ? `<span class="product-card-old-price">S/ ${parseFloat(producto.precio_anterior).toFixed(2)}</span>` : '';

                const sku = (producto.sku || producto.codigo_sku || ('00' + String(producto.id_producto || 1000).padStart(4, '0'))).trim();

                const tit = (producto.titulo || '').toLowerCase();
                let unit = '1 und';
                if (producto.unidad_medida === 'KG') {
                    if (tit.includes('saco') || tit.includes('arroz')) unit = 'Saco 5 kg';
                    else unit = '1 kg';
                } else if (tit.includes('aceite') || tit.includes('botella')) {
                    unit = 'Botella 1 L';
                } else if (tit.includes('pack') || tit.includes('leche') || tit.includes('galleta')) {
                    unit = 'Pack 6 und';
                } else if (tit.includes('jaba') || tit.includes('huevo')) {
                    unit = 'Jaba 30 und';
                } else if (tit.includes('papel')) {
                    unit = 'Pack 4 und';
                }

                card.innerHTML = `
                    <a href="index.php?action=detalle_producto&id=${producto.id_producto}" class="product-card-link">
                        <div class="producto-img">
                            ${badgeHtml}
                            <img src="${producto.imagen || 'Imagenes/producto-placeholder.png'}" alt="${producto.titulo}">
                        </div>

                        <span class="product-card-sku">SKU: ${sku}</span>
                        <h3 class="producto-titulo">${producto.titulo}</h3>
                        <span class="product-card-unit">${unit}</span>
                    </a>

                    <div class="product-card-price-row">
                        <span class="product-card-price">S/ ${precioFormatted}</span>
                        ${oldPriceHtml}
                    </div>

                    <div class="product-card-actions-row">
                        <div class="product-qty-pill" onclick="event.stopPropagation();">
                            <button type="button" class="btn-qty-minus" onclick="if(typeof cambiarCardQty==='function') cambiarCardQty(this, -1)">-</button>
                            <span class="card-qty-val">1</span>
                            <button type="button" class="btn-qty-plus" onclick="if(typeof cambiarCardQty==='function') cambiarCardQty(this, 1)">+</button>
                        </div>
                        <button type="button" class="btn-add-cart-pill btn-agregar-carrito" 
                                data-id-producto="${producto.id_producto}"
                                onclick="event.stopPropagation(); if(typeof agregarConQty==='function') agregarConQty(this, ${producto.id_producto});">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="9" cy="21" r="1"></circle>
                                <circle cx="20" cy="21" r="1"></circle>
                                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                            </svg>
                            <span>Agregar</span>
                        </button>
                    </div>
                `;
                searchResultsGrid.appendChild(card);
            });
        }

        const footer = document.getElementById('searchResultsFooter');
        if (footer) {
            footer.style.display = resultados.length > 20 ? 'flex' : 'none';
            if (resultados.length > 20) {
                const showingText = document.getElementById('searchShowingText');
                const progressFill = document.getElementById('searchProgressFill');
                if (showingText) showingText.textContent = `Mostrando ${Math.min(20, resultados.length)} de ${resultados.length}`;
                if (progressFill) progressFill.style.width = `${(Math.min(20, resultados.length) / resultados.length) * 100}%`;
            }
        }
        
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    window.cerrarBusqueda = function() {
        if (searchResultsSection) searchResultsSection.style.display = 'none';
        if (mainContent) mainContent.style.display = '';
        if (searchInput) searchInput.value = '';
        if (searchClear) searchClear.style.display = 'none';
    };

    const notifDropdown = document.getElementById('notifDropdown');
    const notifOverlay = document.getElementById('notifOverlay');
    const notifItems = document.getElementById('notifItems');
    const notifEmpty = document.getElementById('notifEmpty');
    const notifBadge = document.getElementById('notifBadge');

    window.toggleNotificaciones = function(e) {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }
        const dropdown = document.getElementById('notifDropdown');
        const overlay = document.getElementById('notifOverlay');
        if (!dropdown) return;

        const isActive = dropdown.classList.contains('active');
        if (isActive) {
            dropdown.classList.remove('active');
            if (overlay) overlay.classList.remove('active');
        } else {
            const cartSidebar = document.getElementById('cartSidebar');
            if (cartSidebar && cartSidebar.classList.contains('active')) {
                cartSidebar.classList.remove('active');
                const cartOverlay = document.getElementById('cartOverlay');
                if(cartOverlay) cartOverlay.classList.remove('active');
                document.body.classList.remove('cart-open');
            }
            
            dropdown.classList.add('active');
            if (overlay) overlay.classList.add('active');
            cargarNotificaciones();
        }
    };

    window.cerrarNotificaciones = function(e) {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }
        const dropdown = document.getElementById('notifDropdown');
        const overlay = document.getElementById('notifOverlay');
        if (dropdown) dropdown.classList.remove('active');
        if (overlay) overlay.classList.remove('active');
    };

    function cargarNotificaciones() {
        if (!notifItems) return;
        notifItems.innerHTML = '<div style="padding:40px 20px;text-align:center;color:#64748b;"><div style="font-size:1.8rem;margin-bottom:8px;">⏳</div>Cargando notificaciones...</div>';
        if (notifEmpty) notifEmpty.style.display = 'none';

        fetch('index.php?action=listar_pedidos_usuario', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => {
            if (!res.ok) throw new Error('No autenticado o error del servidor');
            const ct = res.headers.get('content-type') || '';
            if (!ct.includes('application/json')) throw new Error('Respuesta no es JSON');
            return res.json();
        })
        .then(response => {
            if (response.success && response.data && response.data.pedidos && response.data.pedidos.length > 0) {
                renderNotificaciones(response.data.pedidos);
            } else {
                renderNotificacionesDemo();
            }
        })
        .catch(() => {
            renderNotificacionesDemo();
        });
    }

    function renderNotificacionesDemo() {
        if (!notifItems) return;
        notifItems.innerHTML = `
            <div style="padding: 16px 20px; border-bottom: 1px solid #f1f5f9; display: flex; gap: 14px; align-items: flex-start;">
                <span style="font-size: 1.4rem; background: #e0f2fe; padding: 10px; border-radius: 14px; color: #0284c7; flex-shrink:0;">🛒</span>
                <div>
                    <strong style="font-size: 0.95rem; color: #0f172a; display: block;">¡Tu pedido ha sido recibido!</strong>
                    <span style="font-size: 0.83rem; color: #64748b; display: block; margin: 3px 0 4px;">Estamos procesando tu orden correctamente.</span>
                    <small style="font-size: 0.74rem; color: #94a3b8;">Reciente</small>
                </div>
            </div>
            <div style="padding: 16px 20px; border-bottom: 1px solid #f1f5f9; display: flex; gap: 14px; align-items: flex-start;">
                <span style="font-size: 1.4rem; background: #fef3c7; padding: 10px; border-radius: 14px; color: #d97706; flex-shrink:0;">🎉</span>
                <div>
                    <strong style="font-size: 0.95rem; color: #0f172a; display: block;">Nuevas ofertas disponibles</strong>
                    <span style="font-size: 0.83rem; color: #64748b; display: block; margin: 3px 0 4px;">Descuentos especiales en Frutas y Verduras hoy.</span>
                    <small style="font-size: 0.74rem; color: #94a3b8;">Hoy</small>
                </div>
            </div>
        `;
        if (notifBadge) {
            notifBadge.textContent = '2';
            notifBadge.style.display = 'flex';
        }
    }

    function renderNotificaciones(pedidos) {
        if (!notifItems) return;
        notifItems.innerHTML = '';
        
        if (pedidos.length === 0) {
            renderNotificacionesDemo();
            return;
        }
        
        let activeCount = 0;

        pedidos.forEach(pedido => {
            const estado = pedido.estado ? pedido.estado.toLowerCase() : '';
            if (estado !== 'entregado' && estado !== 'cancelado') {
                activeCount++;
            }

            let statusClass = 'status-pendiente';
            if (estado === 'aceptado') statusClass = 'status-aceptado';
            else if (estado === 'en camino') statusClass = 'status-en_camino';
            else if (estado === 'entregado') statusClass = 'status-entregado';
            else if (estado === 'cancelado') statusClass = 'status-cancelado';

            const fechaFormat = new Date(pedido.fecha).toLocaleString('es-PE', {
                year: 'numeric', month: 'short', day: 'numeric',
                hour: '2-digit', minute: '2-digit'
            });

            const div = document.createElement('div');
            div.style.cssText = 'padding: 16px 20px; border-bottom: 1px solid #f1f5f9; display: flex; gap: 14px; align-items: flex-start;';
            
            const iconSvg = `<div style="width: 42px; height: 42px; background: #e0f2fe; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #0284c7; flex-shrink: 0; font-size: 1.3rem;">
                🛍️
            </div>`;

            div.innerHTML = `
                ${iconSvg}
                <div style="flex: 1;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <strong style="font-size: 0.95rem; color: #0f172a;">Pedido #${pedido.id_pedido}</strong>
                        <small style="font-size: 0.75rem; color: #94a3b8;">${fechaFormat}</small>
                    </div>
                    <p style="margin: 4px 0; color: #15803D; font-size: 0.85rem; font-weight: 600;">Total: S/ ${parseFloat(pedido.total).toFixed(2)} - ${pedido.tipo_entrega}</p>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 6px;">
                        <span class="notif-status ${statusClass}" style="padding: 3px 8px; border-radius: 10px; font-size: 0.72rem; font-weight: 800;">${pedido.estado}</span>
                        <a href="index.php?action=detalle_pedido_usuario&id=${pedido.id_pedido}" style="font-size: 0.8rem; color: #10b981; font-weight: bold; text-decoration: none;">Ver detalle →</a>
                    </div>
                </div>
            `;
            notifItems.appendChild(div);
        });

        if (notifBadge) {
            notifBadge.textContent = activeCount > 0 ? activeCount : '0';
            notifBadge.style.display = activeCount > 0 ? 'flex' : 'none';
        }
    }

    fetch('index.php?action=listar_pedidos_usuario', {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(res => {
        if (!res.ok) throw new Error('No autenticado');
        const ct = res.headers.get('content-type') || '';
        if (!ct.includes('application/json')) throw new Error('Respuesta no es JSON');
        return res.json();
    })
    .then(response => {
        if (response.success && response.data && response.data.pedidos) {
            const pedidos = response.data.pedidos;
            let activeCount = 0;
            pedidos.forEach(p => {
                const est = p.estado ? p.estado.toLowerCase() : '';
                if (est !== 'entregado' && est !== 'cancelado') activeCount++;
            });
            if (notifBadge && activeCount > 0) {
                notifBadge.textContent = activeCount;
                notifBadge.style.display = 'flex';
            }
        }
    }).catch(() => { /* Sin sesión activa, no mostrar badge */ });
});

// ==========================================
// CONTROL GLOBAL DE MENÚ HAMBURGUESA Y CARRITO (MÓVIL Y DESKTOP)
// ==========================================
document.addEventListener('click', (e) => {
    // 1. Abrir Menú Hamburguesa Móvil
    const btnOpenSidebar = e.target.closest('#openSidebar, .btn-menu-mobile');
    if (btnOpenSidebar) {
        e.preventDefault();
        const sidebar = document.getElementById('mobileSidebar');
        const overlay = document.getElementById('sidebarOverlay');
        if (sidebar) sidebar.classList.add('active', 'open');
        if (overlay) overlay.classList.add('active', 'open');
        document.body.style.overflow = 'hidden';
        return;
    }

    // 2. Cerrar Menú Hamburguesa Móvil
    const btnCloseSidebar = e.target.closest('#closeSidebar, .sidebar-close, #sidebarOverlay');
    if (btnCloseSidebar) {
        e.preventDefault();
        const sidebar = document.getElementById('mobileSidebar');
        const overlay = document.getElementById('sidebarOverlay');
        if (sidebar) sidebar.classList.remove('active', 'open');
        if (overlay) overlay.classList.remove('active', 'open');
        document.body.style.overflow = '';
        return;
    }

    // 3. Abrir Carrito de Compras (Delegado para Desktop y Móvil, Autenticado o Invitado)
    const btnCart = e.target.closest('#btnAbrirCarrito, #btnAbrirCarritoMobile, .btn-cart');
    if (btnCart) {
        e.preventDefault();
        if (typeof window.abrirCarrito === 'function') {
            window.abrirCarrito();
        } else {
            const cartSidebar = document.getElementById('cartSidebar');
            const cartOverlay = document.getElementById('cartOverlay');
            if (cartSidebar) cartSidebar.classList.add('active');
            if (cartOverlay) cartOverlay.classList.add('active');
            document.body.classList.add('cart-open');
        }
        return;
    }
});

window.cerrarSidebar = function() {
    const sidebar = document.getElementById('mobileSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    if (sidebar) sidebar.classList.remove('active', 'open');
    if (overlay) overlay.classList.remove('active', 'open');
    document.body.style.overflow = '';
};

window.toggleSidebarAccordion = function(btn) {
    const parent = btn.closest('.sidebar-acc-item, .sidebar-subacc-item');
    if (!parent) return;
    parent.classList.toggle('open');
};

