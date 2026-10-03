'use strict';

document.addEventListener('DOMContentLoaded', function () {
    const cartSidebar = document.getElementById('cartSidebar');
    const cartOverlay = document.getElementById('cartOverlay');

    cartSidebar?.classList.remove('active');
    cartOverlay?.classList.remove('active');
    document.body.classList.remove('cart-open');

    cartSidebar?.setAttribute('aria-hidden', 'true');

    const cartItems = document.getElementById('cartItems');
    const cartEmpty = document.getElementById('cartEmpty');

    const cartSubtotal = document.getElementById('cartSubtotal');
    const cartDelivery = document.getElementById('cartDelivery');
    const cartTotal = document.getElementById('cartTotal');
    const cartQuantitySummary = document.getElementById('cartQuantitySummary');

    const cartCountHeader = document.getElementById('cartCountHeader');
    const cartBadge = document.getElementById('cartBadge');

    const btnAbrirCarrito = document.getElementById('btnAbrirCarrito');
    const btnAbrirCarritoMobile = document.getElementById('btnAbrirCarritoMobile');
    const closeCart = document.getElementById('closeCart');
    const btnContinuarComprando = document.getElementById('btnContinuarComprando');
    const btnVerCarrito = document.getElementById('btnVerCarrito');
    const confirmOverlay = document.getElementById('cartConfirmOverlay');
    const confirmTitle = document.getElementById('cartConfirmTitle');
    const confirmMessage = document.getElementById('cartConfirmMessage');
    const confirmAccept = document.getElementById('cartConfirmAccept');
    const confirmCancel = document.getElementById('cartConfirmCancel');
    const confirmIcon = document.getElementById('cartConfirmIcon');

    function solicitarConfirmacionCarrito(titulo, mensaje, accion = 'Eliminar') {
        if (!confirmOverlay || !confirmAccept || !confirmCancel) {
            return Promise.resolve(window.confirm(mensaje));
        }

        return new Promise(resolve => {
            let resuelta = false;
            const finalizar = (confirmada) => {
                if (resuelta) return;
                resuelta = true;
                confirmOverlay.classList.remove('active');
                confirmOverlay.setAttribute('aria-hidden', 'true');
                confirmAccept.onclick = null;
                confirmCancel.onclick = null;
                confirmOverlay.onclick = null;
                document.removeEventListener('keydown', alPresionarEscape, true);
                resolve(confirmada);
            };
            const alPresionarEscape = (event) => {
                if (event.key === 'Escape' && confirmOverlay.classList.contains('active')) {
                    event.preventDefault();
                    event.stopImmediatePropagation();
                    finalizar(false);
                }
            };

            confirmTitle.textContent = titulo;
            confirmMessage.textContent = mensaje;
            confirmAccept.textContent = accion;
            confirmIcon.style.background = '#fee2e2';
            confirmIcon.style.color = '#dc2626';
            confirmOverlay.setAttribute('aria-hidden', 'false');
            confirmAccept.onclick = () => finalizar(true);
            confirmCancel.onclick = () => finalizar(false);
            confirmOverlay.onclick = event => {
                if (event.target === confirmOverlay) finalizar(false);
            };
            document.addEventListener('keydown', alPresionarEscape, true);
            confirmOverlay.classList.add('active');
            confirmCancel.focus();
        });
    }

    function abrirCarrito() {
        if (cartSidebar?.classList.contains('active')) return;
        cartSidebar?.classList.add('active');
        cartOverlay?.classList.add('active');
        cartSidebar?.setAttribute('aria-hidden', 'false');
        document.body.classList.add('cart-open');

        cargarCarrito();
    }

    function cerrarCarrito() {
        cartSidebar?.classList.remove('active');
        cartOverlay?.classList.remove('active');
        cartSidebar?.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('cart-open');
    }

    async function peticionCarrito(url, formData = null) {
        const opciones = {
            method: formData ? 'POST' : 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        };

        if (formData) {
            opciones.body = formData;
        }

        const response = await fetch(url, opciones);
        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(data.message || 'Ocurrió un error.');
        }

        return data;
    }

    async function cargarCarrito() {
        try {
            const respuesta = await peticionCarrito(
                'index.php?action=listar_carrito'
            );

            renderizarCarrito(respuesta.data);
        } catch (error) {
            mostrarMensajeCarrito(error.message, 'error');
        }
    }

    function actualizarContadorHeader(cantidadTotal) {
        const total = Math.max(0, parseInt(cantidadTotal, 10) || 0);
        const elementos = document.querySelectorAll('#cartCountHeader, #cartBadge, .cart-count-badge-img1');
        elementos.forEach(function (el) {
            el.textContent = total;
        });
    }

    function obtenerContadorHeader() {
        const el = document.getElementById('cartCountHeader') || document.getElementById('cartBadge') || document.querySelector('.cart-count-badge-img1');
        return el ? (parseInt(el.textContent, 10) || 0) : 0;
    }

    function sincronizarTarjetasConCarrito(data) {
        window.lastCartData = data;
        const items = Array.isArray(data?.items) ? data.items : [];
        const itemsMap = {};
        items.forEach(function (item) {
            itemsMap[Number(item.id_producto)] = Number(item.cantidad);
        });
        window.cartItemsMap = itemsMap;

        const cards = document.querySelectorAll('.producto-card, .categoria-item-card, .mini-product-card, .also-like-card-item');
        cards.forEach(function (card) {
            const addBtn = card.querySelector('.btn-agregar-carrito, .btn-add-cart-pill');
            if (!addBtn) return;
            const idProd = Number(addBtn.dataset.idProducto || addBtn.getAttribute('data-id-producto'));
            if (!idProd) return;

            const pill = card.querySelector('.product-qty-pill');
            const qtyValEl = card.querySelector('.card-qty-val');
            const spanText = addBtn.querySelector('span') || addBtn;

            if (itemsMap[idProd] !== undefined && itemsMap[idProd] > 0) {
                const cantidadCarrito = itemsMap[idProd];
                card.classList.add('is-in-cart');
                if (pill) {
                    pill.style.display = 'flex';
                }
                if (qtyValEl) {
                    qtyValEl.textContent = cantidadCarrito;
                }
                addBtn.classList.add('is-added');
                spanText.textContent = 'Agregado';
            } else {
                card.classList.remove('is-in-cart');
                if (pill) {
                    pill.style.display = 'none';
                }
                addBtn.classList.remove('is-added');
                spanText.textContent = 'Agregar';
                if (qtyValEl) {
                    qtyValEl.textContent = '1';
                }
            }
        });

        if (data && typeof data.cantidad_total !== 'undefined') {
            actualizarContadorHeader(data.cantidad_total);
        }

        // Notificar a otros scripts (ej. detalle_producto) que el carrito fue sincronizado
        document.dispatchEvent(new CustomEvent('carritoSincronizado', { detail: data }));
    }

    async function agregarProducto(idProducto, boton, cantidad = 1) {
        if (boton && boton.disabled) return;

        const textElement = boton ? (boton.querySelector('span') || boton) : null;
        const textoOriginal = textElement ? textElement.innerHTML : 'Agregar';

        if (boton) {
            boton.disabled = true;
            if (textElement) textElement.innerHTML = 'Agregando...';
        }

        const formData = new FormData();
        formData.append('id_producto', idProducto);
        formData.append('cantidad', cantidad.toString());

        try {
            const respuesta = await peticionCarrito(
                'index.php?action=agregar_carrito',
                formData
            );

            let nombreProducto = '';
            if (boton) {
                const card = boton.closest('.producto-card, .categoria-item-card, .mini-product-card, .also-like-card-item');
                if (card) {
                    const titEl = card.querySelector('.producto-titulo, .also-like-card-name, h3, h4');
                    if (titEl) nombreProducto = titEl.textContent.trim();
                }
            }
            if (!nombreProducto && Array.isArray(respuesta.data?.items)) {
                const item = respuesta.data.items.find(i => Number(i.id_producto) === Number(idProducto));
                if (item && item.titulo) nombreProducto = item.titulo;
            }

            // Notificación Toast de confirmación inmediata
            mostrarToastModerno({
                titulo: '¡Agregado al carrito!',
                detalle: nombreProducto ? `${nombreProducto} (${cantidad} und)` : `${cantidad} producto(s) agregado(s)`,
                tipo: 'success'
            });

            if (boton) {
                boton.disabled = false;
                boton.classList.add('is-added');
                if (textElement) textElement.innerHTML = 'Agregado';
                const card = boton.closest('.producto-card, .categoria-item-card, .mini-product-card, .also-like-card-item');
                if (card) {
                    card.classList.add('is-in-cart');
                    const pill = card.querySelector('.product-qty-pill');
                    if (pill) pill.style.display = 'flex';
                    const qtyValEl = card.querySelector('.card-qty-val');
                    if (qtyValEl) qtyValEl.textContent = cantidad;
                }
            }

            try {
                renderizarCarrito(respuesta.data);
            } catch (errRender) {
                console.warn('Detalle al renderizar sidebar carrito:', errRender);
            }

            sincronizarTarjetasConCarrito(respuesta.data);

        } catch (error) {
            mostrarToastModerno({
                titulo: 'No se pudo agregar',
                detalle: error.message,
                tipo: 'error'
            });
            if (boton) {
                boton.disabled = false;
                if (textElement) textElement.innerHTML = textoOriginal;
            }
        }
    }

    async function actualizarCantidad(idProducto, cantidad, boton = null, nombreProd = '', silencioBoton = false, confirmada = false) {
        idProducto = Number(idProducto);
        cantidad = parseInt(cantidad, 10);
        if (isNaN(cantidad) || cantidad < 0) cantidad = 0;

        if (cantidad === 0 && !confirmada) {
            const aceptada = await solicitarConfirmacionCarrito(
                '¿Eliminar producto?',
                'Se quitará "' + (nombreProd || 'este producto') + '" del carrito.',
                'Eliminar'
            );
            if (!aceptada) return null;
        }

        if (boton && boton.disabled) return;
        const textElement = boton ? (boton.querySelector('span') || boton) : null;
        const textoOriginal = textElement ? textElement.innerHTML : 'Agregado';

        if (boton && !silencioBoton) {
            boton.disabled = true;
            if (textElement) textElement.innerHTML = 'Actualizando...';
        }

        const formData = new FormData();
        formData.append('id_producto', idProducto.toString());
        formData.append('cantidad', cantidad.toString());

        try {
            const respuesta = await peticionCarrito(
                'index.php?action=actualizar_carrito',
                formData
            );

            if (!nombreProd && boton) {
                const card = boton.closest('.producto-card, .categoria-item-card, .mini-product-card, .also-like-card-item');
                if (card) {
                    const titEl = card.querySelector('.producto-titulo, .also-like-card-name, h3, h4');
                    if (titEl) nombreProd = titEl.textContent.trim();
                }
            }
            if (!nombreProd && Array.isArray(respuesta.data?.items)) {
                const item = respuesta.data.items.find(i => Number(i.id_producto) === Number(idProducto));
                if (item && item.titulo) nombreProd = item.titulo;
            }

            if (cantidad === 0) {
                mostrarToastModerno({
                    titulo: 'Producto eliminado',
                    detalle: respuesta.message || 'Se retiró el producto del carrito.',
                    tipo: 'info'
                });
            } else {
                mostrarToastModerno({
                    titulo: '¡Cantidad actualizada!',
                    detalle: nombreProd ? `${nombreProd} (${cantidad} und)` : `Cantidad actualizada a ${cantidad}`,
                    tipo: 'success'
                });
            }

            if (boton) {
                boton.disabled = false;
                boton.classList.add('is-added');
                if (textElement) textElement.innerHTML = 'Agregado';
            }

            try {
                renderizarCarrito(respuesta.data);
            } catch (errRender) {
                console.warn('Detalle al renderizar sidebar carrito:', errRender);
            }

            sincronizarTarjetasConCarrito(respuesta.data);

            return respuesta;
        } catch (error) {
            mostrarToastModerno({
                titulo: 'No se pudo actualizar',
                detalle: error.message,
                tipo: 'error'
            });
            if (boton) {
                boton.disabled = false;
                if (textElement) textElement.innerHTML = textoOriginal;
            }
            cargarCarrito();
            throw error;
        }
    }

    async function procesarAccionBotonCarrito(idProducto, boton = null, cantidad = 1) {
        idProducto = Number(idProducto);
        cantidad = parseInt(cantidad, 10) || 1;
        if (cantidad < 1) cantidad = 1;

        if (boton) {
            const card = boton.closest('.producto-card, .categoria-item-card, .mini-product-card, .also-like-card-item');
            if (card) {
                delete card.dataset.userEditing;
            }
        }

        // Detectar si el producto ya está en el carrito
        const enCarrito = (boton && boton.classList.contains('is-added')) ||
            (window.cartItemsMap && window.cartItemsMap[idProducto] !== undefined && window.cartItemsMap[idProducto] > 0);

        const cantidadEnCarrito = (window.cartItemsMap && window.cartItemsMap[idProducto] !== undefined)
            ? Number(window.cartItemsMap[idProducto])
            : 0;

        let nombreProducto = '';
        if (boton) {
            const card = boton.closest('.producto-card, .categoria-item-card, .mini-product-card, .also-like-card-item');
            if (card) {
                const titEl = card.querySelector('.producto-titulo, .also-like-card-name, h3, h4');
                if (titEl) nombreProducto = titEl.textContent.trim();
            }
        }

        // Caso 1: El producto ya está en el carrito -> El usuario usa el selector (- +) para modificar la cantidad
        if (enCarrito && cantidadEnCarrito > 0) {
            mostrarToastModerno({
                titulo: '¡Agregado al carrito!',
                detalle: nombreProducto ? `${nombreProducto} (${cantidadEnCarrito} und)` : `Ya tienes este producto en tu carrito (${cantidadEnCarrito} und)`,
                tipo: 'info'
            });
            if (boton) {
                boton.classList.add('is-added');
                const textElement = boton.querySelector('span') || boton;
                if (textElement) textElement.textContent = 'Agregado';
            }
            return;
        }

        // Caso 2: El producto NO está en el carrito todavía -> Agregar por primera vez (cantidad inicial = 1)
        await agregarProducto(idProducto, boton, cantidad);
    }

    async function eliminarProducto(idProducto, confirmada = false) {
        if (!confirmada) {
            const item = document.querySelector('.cart-item[data-id-producto="' + Number(idProducto) + '"]');
            const nombre = item ? item.querySelector('.cart-item-title')?.textContent.trim() : '';
            const aceptada = await solicitarConfirmacionCarrito(
                '¿Eliminar producto?',
                'Se quitará "' + (nombre || 'este producto') + '" del carrito.',
                'Eliminar'
            );
            if (!aceptada) return null;
        }

        const formData = new FormData();
        formData.append('id_producto', idProducto);

        try {
            const respuesta = await peticionCarrito(
                'index.php?action=eliminar_carrito',
                formData
            );

            renderizarCarrito(respuesta.data);
            sincronizarTarjetasConCarrito(respuesta.data);
            mostrarToastModerno({
                titulo: 'Producto eliminado',
                detalle: respuesta.message || 'Se retiró el producto de tu carrito.',
                tipo: 'info'
            });
            return respuesta;
        } catch (error) {
            mostrarToastModerno(error.message, 'error');
            throw error;
        }
    }
    window.eliminarProducto = eliminarProducto;

    function renderizarCarrito(data) {
        if (!cartItems || !cartEmpty) {
            console.error(
                'No se encontró #cartItems o #cartEmpty. Revisa carrito_sidebar.php'
            );
            return;
        }

        const items = Array.isArray(data?.items)
            ? data.items
            : [];

        cartItems.innerHTML = '';

        if (items.length === 0) {
            cartEmpty.hidden = false;

            if (btnVerCarrito) {
                btnVerCarrito.disabled = true;
            }
        } else {
            cartEmpty.hidden = true;

            if (btnVerCarrito) {
                btnVerCarrito.disabled = false;
            }

            items.forEach(function (item) {
                cartItems.insertAdjacentHTML(
                    'beforeend',
                    crearTarjetaCarrito(item)
                );
            });
        }

        const cantidadTotal = Number(data?.cantidad_total || 0);
        const subtotal = Number(data?.subtotal || 0);
        const entrega = Number(data?.entrega || 0);
        const total = Number(data?.total || 0);

        if (cartQuantitySummary) {
            cartQuantitySummary.textContent =
                cantidadTotal === 1
                    ? '1 producto'
                    : `${cantidadTotal} productos`;
        }

        if (cartSubtotal) {
            cartSubtotal.textContent = formatearMoneda(subtotal);
        }

        if (cartDelivery) {
            cartDelivery.textContent =
                entrega > 0
                    ? formatearMoneda(entrega)
                    : 'Por calcular';
        }

        if (cartTotal) {
            cartTotal.textContent = formatearMoneda(total);
        }

        if (cartCountHeader) {
            cartCountHeader.textContent = cantidadTotal;
        }

        if (cartBadge) {
            cartBadge.textContent = cantidadTotal;
        }

        // Mostrar/ocultar botón "Vaciar todo"
        const btnVaciar = document.getElementById('btnVaciarCarrito');
        if (btnVaciar) {
            btnVaciar.style.display = items.length > 0 ? 'flex' : 'none';
        }

        sincronizarTarjetasConCarrito(data);
    }

    function crearTarjetaCarrito(item) {
        const id = Number(item.id_producto);
        const cantidad = Number(item.cantidad);
        const precio = Number(item.precio);
        const subtotal = Number(item.subtotal);
        const stockMax = Number(item.stock || 0);

        const unidad =
            item.unidad_medida === 'KG'
                ? 'kg'
                : 'und.';

        const plusDisabled = stockMax > 0 && cantidad >= stockMax
            ? 'disabled title="Stock máximo alcanzado"'
            : '';

        return `
            <article class="cart-item" data-id-producto="${id}">
                <div class="cart-item-image">
                    <img
                        src="${escaparHtml(item.imagen || '')}"
                        alt="${escaparHtml(item.titulo || 'Producto')}"
                    >
                </div>

                <div class="cart-item-info">
                    <h3 class="cart-item-title">
                        ${escaparHtml(item.titulo || '')}
                    </h3>

                    <p class="cart-item-unit">
                        ${formatearMoneda(precio)} / ${unidad}
                    </p>

                    ${stockMax > 0 && cantidad >= stockMax
                        ? `<p style="color:#f59e0b; font-size:0.75rem; margin:2px 0;">⚠ Máximo disponible: ${stockMax}</p>`
                        : ''}

                    <div class="cart-item-bottom">
                        <div class="cart-quantity">
                            <button
                                type="button"
                                class="cart-qty-btn cart-minus"
                                data-id-producto="${id}"
                                data-cantidad="${cantidad}"
                                data-stock="${stockMax}"
                            >
                                −
                            </button>

                            <span class="cart-qty-value">
                                ${cantidad}
                            </span>

                            <button
                                type="button"
                                class="cart-qty-btn cart-plus"
                                data-id-producto="${id}"
                                  data-cantidad="${cantidad}"
                                data-stock="${stockMax}"
                                ${plusDisabled}
                            >
                                +
                            </button>
                        </div>

                        <strong class="cart-item-price">
                            ${formatearMoneda(subtotal)}
                        </strong>
                    </div>
                </div>

                <button
                    type="button"
                    class="cart-remove-btn"
                    data-id-producto="${id}"
                    title="Eliminar producto"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path>
                        <path d="M10 11v6"></path>
                        <path d="M14 11v6"></path>
                        <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"></path>
                    </svg>
                </button>
            </article>
        `;
    }

    function formatearMoneda(valor) {
        return `S/ ${Number(valor).toFixed(2)}`;
    }

    function escaparHtml(valor) {
        const div = document.createElement('div');
        div.textContent = String(valor);
        return div.innerHTML;
    }

    // =========================================================================
    // AJUSTE DINÁMICO DE HEADER FIJO (PADDING-TOP AL BODY)
    // =========================================================================
    function ajustarEspacioHeaderFijo() {
        const header = document.querySelector('header.main-header') || document.querySelector('.main-header');
        if (!header) return;

        // Medir exclusivamente las barras estructurales base cerradas (Tier 1 + Tier 2 si está visible)
        // para NUNCA sumar alturas de submenús, dropdowns, autocompletados o menús laterales
        const tier1 = header.querySelector('.header-tier1');
        const tier2 = header.querySelector('.header-tier2-green-bar');

        let alturaCalculada = 0;
        if (tier1) {
            alturaCalculada += tier1.offsetHeight;
        }
        if (tier2 && window.getComputedStyle(tier2).display !== 'none') {
            alturaCalculada += tier2.offsetHeight;
        }

        if (alturaCalculada === 0) {
            alturaCalculada = header.offsetHeight;
        }

        // Rango de seguridad (40px a 180px) para evitar cualquier "espaciazo"
        if (alturaCalculada >= 40 && alturaCalculada <= 180) {
            document.body.style.paddingTop = alturaCalculada + 'px';
            document.documentElement.style.setProperty('--main-header-height', alturaCalculada + 'px');
        }
    }
    ajustarEspacioHeaderFijo();
    window.addEventListener('resize', ajustarEspacioHeaderFijo);
    window.addEventListener('load', ajustarEspacioHeaderFijo);

    function mostrarToastModerno(opciones, tipoFallback = 'success') {
        let mensaje = '';
        let titulo = '¡Producto agregado!';
        let tipo = 'success';

        if (typeof opciones === 'string') {
            mensaje = opciones;
            tipo = tipoFallback;
            if (tipo === 'error') titulo = 'Error';
            else if (tipo === 'warning') titulo = 'Atención';
            else if (tipo === 'info') titulo = 'Información';
        } else if (opciones && typeof opciones === 'object') {
            mensaje = opciones.detalle || opciones.mensaje || '';
            titulo = opciones.titulo || titulo;
            tipo = opciones.tipo || 'success';
        }

        let container = document.getElementById('caserita-toast-stack');
        if (!container) {
            container = document.createElement('div');
            container.id = 'caserita-toast-stack';
            container.className = 'caserita-toast-stack';
            container.style.cssText = 'position:fixed; top:130px; right:24px; z-index:999999; display:flex; flex-direction:column; gap:10px; pointer-events:none; max-width:360px; width:calc(100% - 48px);';
            document.body.appendChild(container);
        }

        const toast = document.createElement('div');
        toast.className = `caserita-toast show ${tipo === 'error' ? 'caserita-toast-error' : ''}`;
        toast.style.cssText = 'background:#ffffff; border:1px solid #e2e8f0; border-radius:12px; padding:12px 16px; box-shadow:0 10px 25px -5px rgba(15,23,42,0.18), 0 8px 10px -6px rgba(15,23,42,0.08); display:flex; align-items:center; gap:12px; pointer-events:auto; opacity:1; transform:none; box-sizing:border-box; transition:opacity 0.25s ease, transform 0.25s ease;';

        const iconSvg = tipo === 'success'
            ? `<svg class="caserita-toast-icon" style="width:20px;height:20px;color:#15803d;flex-shrink:0;" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>`
            : (tipo === 'error'
                ? `<svg class="caserita-toast-icon" style="width:20px;height:20px;color:#ef4444;flex-shrink:0;" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" /></svg>`
                : `<svg class="caserita-toast-icon" style="width:20px;height:20px;color:#3b82f6;flex-shrink:0;" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" /></svg>`);

        const bgIcon = tipo === 'success' ? '#dcfce7' : (tipo === 'error' ? '#fee2e2' : '#e0e7ff');

        toast.innerHTML = `
            <div class="caserita-toast-icon-wrap" style="flex-shrink:0; width:32px; height:32px; border-radius:50%; background:${bgIcon}; display:flex; align-items:center; justify-content:center;">
                ${iconSvg}
            </div>
            <div class="caserita-toast-body" style="flex:1; min-width:0;">
                <div class="caserita-toast-title" style="font-size:0.88rem; font-weight:700; color:#0f172a; line-height:1.2;">${titulo}</div>
                ${mensaje ? `<div class="caserita-toast-desc" style="font-size:0.80rem; color:#64748b; margin-top:2px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:240px;">${mensaje}</div>` : ''}
            </div>
            <button type="button" class="caserita-toast-close" aria-label="Cerrar" style="background:none; border:none; color:#94a3b8; font-size:18px; line-height:1; cursor:pointer; padding:4px;">&times;</button>
        `;

        const closeBtn = toast.querySelector('button');
        let isDismissed = false;
        const dismiss = () => {
            if (isDismissed) return;
            isDismissed = true;
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(25px)';
            setTimeout(() => { toast.remove(); }, 250);
        };

        if (closeBtn) closeBtn.onclick = dismiss;

        container.appendChild(toast);
        setTimeout(dismiss, 3200);
    }

    window.mostrarToastModerno = mostrarToastModerno;
    window.mostrarToast = mostrarToastModerno;

    function mostrarMensajeCarrito(mensaje, tipo) {
        mostrarToastModerno(mensaje, tipo);
    }

    const debounceActualizarMap = new Map();

    async function cambiarCardQty(btn, delta) {
        if (!btn) return;
        // Evitar doble ejecución si coincide evento inline con delegación
        if (btn._isChangingQty && (Date.now() - btn._isChangingQty < 60)) {
            return;
        }
        btn._isChangingQty = Date.now();

        const card = btn.closest('.producto-card, .categoria-item-card, .mini-product-card, .also-like-card-item');
        const pill = btn.closest('.product-qty-pill') || (card ? card.querySelector('.product-qty-pill') : null);
        const addBtn = card ? card.querySelector('.btn-agregar-carrito, .btn-add-cart-pill') : null;

        let idProducto = addBtn ? (addBtn.dataset.idProducto || addBtn.getAttribute('data-id-producto')) : null;
        if (!idProducto && card) {
            idProducto = card.dataset.idProducto || card.getAttribute('data-id-producto');
        }
        idProducto = Number(idProducto);
        if (!idProducto) return;

        const qtySpan = pill ? pill.querySelector('.card-qty-val') : (card ? card.querySelector('.card-qty-val') : null);

        let cantidadActual = 1;
        if (qtySpan) {
            const val = parseInt(qtySpan.textContent, 10);
            if (!isNaN(val)) cantidadActual = val;
        } else if (window.cartItemsMap && window.cartItemsMap[idProducto] !== undefined) {
            cantidadActual = Number(window.cartItemsMap[idProducto]);
        }

        const nuevaCantidad = cantidadActual + delta;

        // Cancelar timer debounce pendiente para este producto
        if (debounceActualizarMap.has(idProducto)) {
            clearTimeout(debounceActualizarMap.get(idProducto));
            debounceActualizarMap.delete(idProducto);
        }

        if (nuevaCantidad <= 0) {
            // ============================================================
            // CASO RESTAR A CERO (0):
            // 1. Selector numérico (- +) se oculta inmediatamente
            // 2. Botón principal regresa a verde "Agregar"
            // 3. Valor se restablece a 1 en el DOM
            // 4. Contador del Header se decrementa en tiempo real
            // 5. Backend elimina el producto en segundo plano
            // ============================================================
            const aceptada = await solicitarConfirmacionCarrito(
                '¿Eliminar producto?',
                'Se quitará "' + (card?.querySelector('.producto-titulo, .also-like-card-name, h3, h4')?.textContent.trim() || 'este producto') + '" del carrito.',
                'Eliminar'
            );
            if (!aceptada) {
                if (qtySpan) qtySpan.textContent = String(cantidadActual);
                return;
            }

            if (qtySpan) qtySpan.textContent = '1';
            if (pill) pill.style.display = 'none';
            if (card) card.classList.remove('is-in-cart');
            if (addBtn) {
                addBtn.classList.remove('is-added');
                const spanText = addBtn.querySelector('span') || addBtn;
                if (spanText) spanText.textContent = 'Agregar';
            }
            if (window.cartItemsMap) {
                delete window.cartItemsMap[idProducto];
            }

            // Actualización inmediata en tiempo real del contador del Header
            const totalActual = obtenerContadorHeader();
            actualizarContadorHeader(totalActual - cantidadActual);

            // Sincronizar eliminación en el backend (segundo plano)
            try {
                await eliminarProducto(idProducto, true);
            } catch (err) {
                console.error('Error al retirar producto tras restar a 0:', err);
                cargarCarrito();
            }
        } else {
            // ============================================================
            // CASO AUTO-SUMA / RESTA (> 0):
            // 1. Número en tarjeta se actualiza en tiempo real
            // 2. Selector permanece visible (display: flex)
            // 3. Botón se mantiene en estado "Agregado"
            // 4. Contador del Header se actualiza de inmediato
            // 5. Backend sincroniza en segundo plano automáticamente
            // ============================================================
            if (qtySpan) qtySpan.textContent = nuevaCantidad;
            if (pill) pill.style.display = 'flex';
            if (card) card.classList.add('is-in-cart');
            if (addBtn) {
                addBtn.classList.add('is-added');
                const spanText = addBtn.querySelector('span') || addBtn;
                if (spanText) spanText.textContent = 'Agregado';
            }
            if (window.cartItemsMap) {
                window.cartItemsMap[idProducto] = nuevaCantidad;
            }

            // Actualización inmediata en tiempo real del contador del Header
            const totalActual = obtenerContadorHeader();
            actualizarContadorHeader(totalActual + delta);

            // Auto-update en segundo plano con debounce suave (250ms)
            const timer = setTimeout(async () => {
                debounceActualizarMap.delete(idProducto);
                try {
                    await actualizarCantidad(idProducto, nuevaCantidad, addBtn, '', true);
                } catch (err) {
                    console.error('Error al sincronizar cantidad en tiempo real:', err);
                    cargarCarrito();
                }
            }, 250);
            debounceActualizarMap.set(idProducto, timer);
        }
    }
    window.cambiarCardQty = cambiarCardQty;

    // Delegación de eventos global
    document.addEventListener('click', function (event) {
        // Selector de cantidad en tarjeta: botón restar
        const btnQtyMinus = event.target.closest('.btn-qty-minus');
        if (btnQtyMinus) {
            if (!btnQtyMinus.hasAttribute('onclick')) {
                event.preventDefault();
                event.stopPropagation();
                cambiarCardQty(btnQtyMinus, -1);
            }
            return;
        }

        // Selector de cantidad en tarjeta: botón sumar
        const btnQtyPlus = event.target.closest('.btn-qty-plus');
        if (btnQtyPlus) {
            if (!btnQtyPlus.hasAttribute('onclick')) {
                event.preventDefault();
                event.stopPropagation();
                cambiarCardQty(btnQtyPlus, 1);
            }
            return;
        }

        const botonAgregar = event.target.closest('.btn-agregar-carrito, .btn-add-cart-pill, .mini-add-btn, [data-action="agregar-carrito"]');

        if (botonAgregar) {
            if (!botonAgregar.hasAttribute('onclick')) {
                event.preventDefault();
                event.stopImmediatePropagation();

                const idProducto = botonAgregar.dataset.idProducto || botonAgregar.getAttribute('data-id-producto');
                if (idProducto) {
                    let qty = 1;
                    const card = botonAgregar.closest('.producto-card, .categoria-item-card, .mini-product-card, .also-like-card-item');
                    if (card) {
                        delete card.dataset.userEditing;
                        const qtyEl = card.querySelector('.card-qty-val');
                        if (qtyEl) {
                            const parsed = parseInt(qtyEl.textContent, 10);
                            if (!isNaN(parsed) && parsed > 0) qty = parsed;
                        }
                    }
                    procesarAccionBotonCarrito(idProducto, botonAgregar, qty);
                }
            }
            return;
        }

        const botonMas = event.target.closest('.cart-plus');

        if (botonMas) {
            if (botonMas.disabled) return;

            const idProducto = botonMas.dataset.idProducto;
            const cantidadActual = Number(botonMas.dataset.cantidad);
            const stockMax = Number(botonMas.dataset.stock || 0);

            if (stockMax > 0 && cantidadActual >= stockMax) {
                mostrarMensajeCarrito(`Stock máximo disponible: ${stockMax}`, 'error');
                return;
            }

            actualizarCantidad(
                idProducto,
                cantidadActual + 1
            );

            return;
        }

        const botonMenos = event.target.closest('.cart-minus');

        if (botonMenos) {
            const idProducto = botonMenos.dataset.idProducto;
            const cantidadActual = Number(botonMenos.dataset.cantidad);
            const nombreProd = botonMenos.closest('.cart-item')?.querySelector('.cart-item-title')?.textContent.trim() || '';

            if (cantidadActual > 0) {
                actualizarCantidad(
                    idProducto,
                    cantidadActual - 1,
                    null,
                    nombreProd
                );
            }

            return;
        }

        const botonEliminar = event.target.closest('.cart-remove-btn');

        if (botonEliminar) {
            eliminarProducto(
                botonEliminar.dataset.idProducto
            );
        }
    }, true);

    document.addEventListener('click', function (event) {
        const btnCart = event.target.closest('#btnAbrirCarrito, #btnAbrirCarritoMobile, .btn-cart');
        if (btnCart) {
            event.preventDefault();
            abrirCarrito();
        }
    });

    closeCart?.addEventListener('click', cerrarCarrito);
    cartOverlay?.addEventListener('click', cerrarCarrito);
    btnContinuarComprando?.addEventListener('click', cerrarCarrito);

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            if (confirmOverlay?.classList.contains('active')) return;
            cerrarCarrito();
        }
    });

    btnVerCarrito?.addEventListener('click', function () {
        if (btnVerCarrito.disabled) {
            return;
        }

        // Si es invitado (no logueado), abrir modal de login como en Amaranto
        if (typeof window.usuarioAutenticado !== 'undefined' && !window.usuarioAutenticado) {
            if (typeof abrirModalAuth === 'function') {
                abrirModalAuth('login');
                cerrarCarrito();
                return;
            }
        }

        window.location.href = 'index.php?action=checkout';
    });

    cargarCarrito();

    window.abrirCarrito = abrirCarrito;
    window.cerrarCarrito = cerrarCarrito;
    window.agregarProducto = agregarProducto;
    window.actualizarCantidad = actualizarCantidad;
    window.actualizarCantidadCarrito = actualizarCantidad;
    window.procesarAccionBotonCarrito = procesarAccionBotonCarrito;
    window.sincronizarTarjetasConCarrito = sincronizarTarjetasConCarrito;
    window.cambiarCardQty = cambiarCardQty;
    window.actualizarBurbujaHeader = actualizarContadorHeader;
    window.agregarConQty = function (btn, idProducto) {
        const card = btn ? btn.closest('.producto-card, .categoria-item-card, .mini-product-card, .also-like-card-item') : null;
        let qty = 1;
        if (card) {
            delete card.dataset.userEditing;
            const qtySpan = card.querySelector('.card-qty-val');
            if (qtySpan) {
                qty = parseInt(qtySpan.textContent, 10) || 1;
            }
        }
        procesarAccionBotonCarrito(idProducto, btn, qty);
    };
    window.agregarAlCarrito = function (id, qty = 1) {
        procesarAccionBotonCarrito(id, null, qty);
    };

    // =========================================================================
    // VACIAR CARRITO COMPLETO
    // =========================================================================
    async function vaciarCarritoCompleto() {
        const totalProductos = Number(window.lastCartData?.cantidad_total || 0);
        const aceptada = await solicitarConfirmacionCarrito(
            '¿Vaciar carrito?',
            'Se quitarán todos los productos (' + totalProductos + ') del carrito.',
            'Vaciar carrito'
        );
        if (!aceptada) return;

        const btnVaciar = document.getElementById('btnVaciarCarrito');
        if (btnVaciar) {
            btnVaciar.disabled = true;
            btnVaciar.textContent = 'Vaciando...';
        }

        try {
            const resp = await peticionCarrito('index.php?action=vaciar_carrito', new FormData());

            // Limpiar el mapa local del carrito
            window.cartItemsMap = {};
            window.lastCartData = null;

            // Resetear visualmente todas las tarjetas del DOM
            document.querySelectorAll('.producto-card, .categoria-item-card, .mini-product-card, .also-like-card-item').forEach(function(card) {
                card.classList.remove('is-in-cart');
                const pill = card.querySelector('.product-qty-pill');
                if (pill) pill.style.display = 'none';
                const addBtn = card.querySelector('.btn-agregar-carrito, .btn-add-cart-pill');
                if (addBtn) {
                    addBtn.classList.remove('is-added');
                    const spanText = addBtn.querySelector('span') || addBtn;
                    if (spanText) spanText.textContent = 'Agregar';
                }
                const qtyVal = card.querySelector('.card-qty-val');
                if (qtyVal) qtyVal.textContent = '1';
            });

            // Resetear botón de detalle producto si existe
            const detalleBtn = document.querySelector('.detalle-page-btn-add');
            if (detalleBtn) {
                detalleBtn.classList.remove('is-added');
                const sp = detalleBtn.querySelector('span');
                if (sp) sp.textContent = 'Agregar al carrito';
            }
            const detallePill = document.querySelector('.detalle-page-qty-pill');
            if (detallePill) detallePill.style.display = 'none';

            renderizarCarrito(resp.data);

            mostrarToastModerno({
                titulo: 'Carrito vaciado',
                detalle: 'Todos los productos fueron eliminados.',
                tipo: 'info'
            });
        } catch (err) {
            mostrarToastModerno({
                titulo: 'Error al vaciar',
                detalle: err.message || 'No se pudo vaciar el carrito.',
                tipo: 'error'
            });
        } finally {
            if (btnVaciar) {
                btnVaciar.disabled = false;
                btnVaciar.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path><path d="M10 11v6"></path><path d="M14 11v6"></path><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"></path></svg> Vaciar`;
            }
        }
    }
    window.vaciarCarritoCompleto = vaciarCarritoCompleto;
});