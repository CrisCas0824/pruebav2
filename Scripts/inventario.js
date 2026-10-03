/**
 * inventario.js — Control de Inventario & Reposición de Stock (Pure Vanilla JS)
 */

// Abrir modal genérico
window.abrirModal = function(id) {
    const modal = document.getElementById(id);
    if (modal) {
        if (!modal.__modalOriginalParent) {
            modal.__modalOriginalParent = modal.parentElement;
        }
        document.body.appendChild(modal);
        modal.classList.add('active');
        modal.style.position = 'fixed';
        modal.style.inset = '0';
        modal.style.zIndex = '12000';
        modal.style.display = 'flex';
        modal.style.visibility = 'visible';
        modal.style.opacity = '1';
        document.body.style.overflow = 'hidden';
    }
};

// Cerrar modal genérico
window.cerrarModal = function(id) {
    const modal = document.getElementById(id);
    if (modal) {
        modal.classList.remove('active');
        modal.style.display = 'none';
        const originalParent = modal.__modalOriginalParent;
        if (originalParent && originalParent.isConnected && modal.parentElement === document.body) {
            originalParent.appendChild(modal);
        }
    }
    if (!document.querySelector('.modal-overlay.active')) {
        document.body.style.overflow = '';
    }
};

// Abrir modal de reposición para un producto específico
window.abrirModalReposicion = function(idProducto, nombreProducto, unidad, sku = '', codigoBarras = '') {
    const selectGroup = document.getElementById('grupoSelectProducto');
    if (selectGroup) selectGroup.style.display = 'none';

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

    abrirModal('modalReposicion');
};

// Abrir modal de reposición manual (desde el botón superior "Reponer Stock")
window.abrirModalManualReposicion = function() {
    const selectGroup = document.getElementById('grupoSelectProducto');
    if (selectGroup) selectGroup.style.display = 'block';

    const select = document.getElementById('selectProductoReposicion');
    if (select && select.options.length > 0) {
        const opt = select.options[select.selectedIndex >= 0 ? select.selectedIndex : 0];
        const id = opt.value;
        const nom = opt.getAttribute('data-nombre') || opt.textContent;
        const uni = opt.getAttribute('data-unidad') || 'und.';

        const idInput = document.getElementById('repoIdProducto');
        const nomText = document.getElementById('repoNombreProducto');
        const uniText = document.getElementById('repoUnidadTexto');

        if (idInput) idInput.value = id;
        if (nomText) nomText.textContent = nom;
        if (uniText) uniText.textContent = uni;
    }

    abrirModal('modalReposicion');
};

// Cambio de producto en select manual de reposición
document.addEventListener('change', function(e) {
    if (e.target && e.target.id === 'selectProductoReposicion') {
        const select = e.target;
        const opt = select.options[select.selectedIndex];
        if (opt) {
            const id = opt.value;
            const nom = opt.getAttribute('data-nombre') || opt.textContent;
            const uni = opt.getAttribute('data-unidad') || 'und.';

            const idInput = document.getElementById('repoIdProducto');
            const nomText = document.getElementById('repoNombreProducto');
            const uniText = document.getElementById('repoUnidadTexto');

            if (idInput) idInput.value = id;
            if (nomText) nomText.textContent = nom;
            if (uniText) uniText.textContent = uni;
        }
    }
});

// Ver detalles del producto en modal Ficha
window.verDetallesProducto = function(item) {
    if (typeof item === 'string') {
        try { item = JSON.parse(item); } catch (e) { return; }
    }
    if (!item) return;

    const stockActual = parseFloat(item.stock_actual !== undefined ? item.stock_actual : (item.stock || 0));
    const stockMinimo = parseFloat(item.stock_minimo || 5);
    const unidad = item.unidad_medida === 'KG' ? 'kg' : 'und.';
    const isBajo = stockActual <= stockMinimo;
    const estadoText = isBajo ? 'Stock Bajo' : 'Disponible';
    const estadoClass = isBajo ? 'status-tag-error' : 'status-tag-success';
    const ubicacion = item.ubicacion || 'Almacén Principal';
    const imgSrc = item.imagen || 'Images/icono.png';
    const tituloEsc = (item.titulo || '').replace(/"/g, '&quot;');

    let html = `
        <div class="user-detail-card-header">
            <img src="${imgSrc}" onerror="this.onerror=null;this.src='Images/icono.png';" alt="" class="user-detail-avatar" style="object-fit: cover;">
            <div class="user-detail-info">
                <h4>${item.titulo}</h4>
                <span class="user-detail-id">Precio: S/ ${parseFloat(item.precio || 0).toFixed(2)} / ${unidad}</span>
            </div>
        </div>
        
        <div class="user-detail-badges">
            <span class="status-tag ${estadoClass}">${estadoText}</span>
            <span class="status-tag ${isBajo ? 'status-tag-error' : 'status-tag-user'}">📍 ${ubicacion}</span>
        </div>

        <div class="user-detail-group">
            <div class="user-detail-row">
                <span class="detail-label">📦 Stock Actual:</span>
                <strong class="detail-value" style="color: ${isBajo ? '#dc2626' : '#15803d'}; font-size: 1.05rem;">${stockActual.toFixed(2)} ${unidad}</strong>
            </div>
            <div class="user-detail-row">
                <span class="detail-label">⚠️ Stock Mínimo:</span>
                <span class="detail-value">${stockMinimo.toFixed(2)} ${unidad}</span>
            </div>
            <div class="user-detail-row">
                <span class="detail-label">📍 Ubicación:</span>
                <span class="detail-value">${ubicacion}</span>
            </div>
            <div class="user-detail-row">
                <span class="detail-label">🗓️ Última Act.:</span>
                <span class="detail-value">${item.ultima_act || '-'}</span>
            </div>
        </div>

        <div class="user-detail-actions">
            <button type="button" class="btn btn-primary btn-full" onclick='cerrarModal("modalDetallesProducto"); abrirModalReposicion(${item.id_producto}, "${tituloEsc}", "${unidad}");'>
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                Reponer Stock
            </button>
        </div>
    `;

    const bodyEl = document.getElementById('detallesProductoBody');
    if (bodyEl) bodyEl.innerHTML = html;
    abrirModal('modalDetallesProducto');
};

// Abrir modal Editar Producto
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
    setVal('editProdPrecio', parseFloat(item.precio || 0).toFixed(2));
    setVal('editProdUnidad', item.unidad_medida || 'UND');
    setVal('editProdStock', parseFloat(item.stock_actual !== undefined ? item.stock_actual : (item.stock || 0)));
    setVal('editProdStockMinimo', parseFloat(item.stock_minimo || 5));
    setVal('editProdUbicacion', item.ubicacion || '');
    setVal('editProdDescripcion', item.descripcion || '');
    setVal('editProdCategoria', item.id_categoria || '');
    setVal('editProdImagenActual', item.imagen || '');
    setVal('editProdImagenUrl', item.imagen || '');

    const preview = document.getElementById('editProdPreviewImg');
    if (preview) {
        preview.src = item.imagen || 'Images/icono.png';
        preview.onerror = function() {
            this.onerror = null;
            this.src = 'Images/icono.png';
        };
    }

    const fileNameEl = document.getElementById('editProdFileName');
    if (fileNameEl) {
        fileNameEl.textContent = item.imagen ? 'Imagen actual en uso' : 'Ningún archivo nuevo';
        fileNameEl.style.color = '#64748b';
        fileNameEl.style.fontWeight = 'normal';
    }

    abrirModal('modalEditarProducto');
};

// Previsualización de imagen al subir archivo local
window.previewImgFile = function(input) {
    const fileNameEl = document.getElementById('editProdFileName');
    if (input.files && input.files[0]) {
        const file = input.files[0];
        if (fileNameEl) {
            fileNameEl.textContent = file.name;
            fileNameEl.style.color = '#15803D';
            fileNameEl.style.fontWeight = '600';
        }
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('editProdPreviewImg');
            if (preview) preview.src = e.target.result;
        };
        reader.readAsDataURL(file);
    } else {
        if (fileNameEl) {
            fileNameEl.textContent = 'Ningún archivo nuevo';
            fileNameEl.style.color = '#64748b';
            fileNameEl.style.fontWeight = 'normal';
        }
    }
};

// Búsqueda en tiempo real de productos en el inventario
function filtrarInventario() {
    const searchInput = document.getElementById('searchInventario');
    if (!searchInput) return;

    const q = searchInput.value.toLowerCase().trim();
    const rows = document.querySelectorAll('#inventarioTable tbody tr');
    let visibleCount = 0;

    rows.forEach(row => {
        if (row.id === 'noResultsRow') return;
        // Si es la fila original de "No hay productos registrados", no filtrar
        if (row.children.length === 1 && !row.id) return;

        const text = row.textContent.toLowerCase();
        if (q === '' || text.includes(q)) {
            row.style.display = '';
            row.classList.remove('search-hidden');
            visibleCount++;
        } else {
            row.style.display = 'none';
            row.classList.add('search-hidden');
        }
    });

    // Mensaje dinámico cuando ningún producto coincide con la búsqueda
    const tbody = document.querySelector('#inventarioTable tbody');
    let noResults = document.getElementById('noResultsRow');

    if (visibleCount === 0 && q !== '') {
        if (!noResults && tbody) {
            noResults = document.createElement('tr');
            noResults.id = 'noResultsRow';
            noResults.innerHTML = `<td colspan="7" style="text-align: center; padding: 36px 20px; color: #64748b; font-size: 0.95rem;">
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: block; margin: 0 auto 10px auto; opacity: 0.5;"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                No se encontraron productos coincidentes con "<strong>${searchInput.value.replace(/</g, '&lt;')}</strong>"
            </td>`;
            tbody.appendChild(noResults);
        } else if (noResults) {
            noResults.style.display = '';
            noResults.querySelector('strong').textContent = searchInput.value;
        }
    } else if (noResults) {
        noResults.style.display = 'none';
    }
}

// Escuchadores globales registrados solo una vez para evitar envíos múltiples en navegación SPA
if (!window.__inventarioListenersInitialized) {
    window.__inventarioListenersInitialized = true;

    // Escuchador de búsqueda en tiempo real (input, keyup, search, change)
    document.addEventListener('input', function(e) {
        if (e.target && e.target.id === 'searchInventario') {
            filtrarInventario();
        }
    });
    document.addEventListener('keyup', function(e) {
        if (e.target && e.target.id === 'searchInventario') {
            filtrarInventario();
        }
    });

    // Cerrar modales al hacer clic fuera del contenido
    document.addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('modal-overlay')) {
            cerrarModal(e.target.id);
        }
    });

    // Envío AJAX del formulario de reposición
    document.addEventListener('submit', function(e) {
        if (e.target && e.target.id === 'formReposicion') {
            e.preventDefault();
            const form = e.target;
            const url = form.getAttribute('action') || 'index.php?action=reponer_stock';
            const formData = new FormData(form);
            const btnSave = form.querySelector('.btn-save');
            const btnText = btnSave ? btnSave.textContent : '';

            if (btnSave) {
                btnSave.disabled = true;
                btnSave.textContent = 'Guardando...';
            }

            fetch(url, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(response => {
                if (response.success) {
                    cerrarModal('modalReposicion');
                    if (typeof SPA !== 'undefined' && typeof SPA.toast === 'function') {
                        SPA.toast('success', 'Éxito', response.message);
                        SPA.loadModule('index.php?action=inventario', false);
                    } else {
                        window.location.reload();
                    }
                } else {
                    const msg = response.message || 'Ocurrió un error al registrar la reposición.';
                    if (typeof SPA !== 'undefined' && typeof SPA.toast === 'function') {
                        SPA.toast('error', 'Error', msg);
                    } else {
                        alert(msg);
                    }
                }
            })
            .catch(err => {
                console.error('Error al guardar reposición:', err);
                form.submit();
            })
            .finally(() => {
                if (btnSave) {
                    btnSave.disabled = false;
                    btnSave.textContent = btnText;
                }
            });
        }

        // Envío AJAX del formulario de editar producto
        if (e.target && e.target.id === 'formEditarProductoInventario') {
            e.preventDefault();
            const form = e.target;
            const url = form.getAttribute('action') || 'index.php?action=actualizar_producto';
            const formData = new FormData(form);
            const btnSubmit = form.querySelector('button[type="submit"]');
            const btnText = btnSubmit ? btnSubmit.textContent : '';

            if (btnSubmit) {
                btnSubmit.disabled = true;
                btnSubmit.textContent = 'Guardando...';
            }

            fetch(url, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(response => {
                if (response.success) {
                    cerrarModal('modalEditarProducto');
                    if (typeof SPA !== 'undefined' && typeof SPA.toast === 'function') {
                        SPA.toast('success', 'Éxito', response.message || 'Producto actualizado con éxito.');
                        SPA.loadModule('index.php?action=inventario', false);
                    } else {
                        window.location.reload();
                    }
                } else {
                    const msg = response.message || 'Error al actualizar producto.';
                    if (typeof SPA !== 'undefined' && typeof SPA.toast === 'function') {
                        SPA.toast('error', 'Error', msg);
                    } else {
                        alert(msg);
                    }
                }
            })
            .catch(err => {
                console.error('Error al actualizar producto:', err);
                form.submit();
            })
            .finally(() => {
                if (btnSubmit) {
                    btnSubmit.disabled = false;
                    btnSubmit.textContent = btnText;
                }
            });
        }
    });
}

window.activeCodeFilter = window.activeCodeFilter || 'all';

window.filterByCodeMetric = function(type, element) {
    window.activeCodeFilter = window.activeCodeFilter === type ? 'all' : type;
    window.activeStatFilter = 'all';
    document.querySelectorAll('.inventory-code-metric').forEach(card => {
        const active = card === element && window.activeCodeFilter === type;
        card.classList.toggle('active', active);
        card.setAttribute('aria-pressed', active ? 'true' : 'false');
    });
    document.querySelectorAll('.stats-grid-square .stat-card-square').forEach(card => card.classList.remove('active-card'));
    if (window.activeCodeFilter === 'all') document.querySelector('.stats-grid-square .stat-card-square')?.classList.add('active-card');
    if (typeof window.triggerTableUpdate === 'function') window.triggerTableUpdate();
};

window.buscarProductoParaReposicion = function(codigo) {
    const normalized = String(codigo || '').trim().toLowerCase();
    if (!normalized) return;
    const rows = Array.from(document.querySelectorAll('#inventarioTable tbody tr[data-id]'));
    const row = rows.find(item => [item.dataset.sku, item.dataset.barcode].some(value => (value || '').trim().toLowerCase() === normalized));
    if (!row) {
        const message = `No se encontró el producto con el código "${codigo}".`;
        if (typeof SPA !== 'undefined' && typeof SPA.toast === 'function') SPA.toast('error', 'Producto no encontrado', message, 1800);
        else alert(message);
        return;
    }
    window.abrirModalReposicion(row.dataset.id, row.dataset.name, row.dataset.unit, row.dataset.sku, row.dataset.barcode);
};


