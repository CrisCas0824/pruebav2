(function() {
const itemsPerPageCat = 5;

let pagesStateCat = {
    tablaMom: 1,
    tablaGrupos: 1,
    tablaCategorias: 1
};

let filterStateCat = {
    tablaGrupos: 'all', // 'all', 'activas', 'inactivas'
    tablaCategorias: 'all' // 'all', 'activas', 'inactivas'
};

function paginateTableCat(tableId, listContainerId, pageKey, filterKey) {
    const table = document.getElementById(tableId);
    if (!table) return;
    const tbody = table.querySelector('tbody');
    if (!tbody) return;

    const allRows = Array.from(tbody.querySelectorAll('tr'));
    const searchInputId = tableId === 'tablaMom' ? 'filtroMom' : (tableId === 'tablaGrupos' ? 'filtroGrupos' : 'filtroCategorias');
    const query = (document.getElementById(searchInputId)?.value || '').toLowerCase().trim();
    const activeFilter = filterKey ? filterStateCat[filterKey] : 'all';

    let filtered = allRows.filter(row => {
        if (query && !row.textContent.toLowerCase().includes(query)) {
            return false;
        }
        if (activeFilter === 'activas') {
            return row.dataset.activo === '1';
        } else if (activeFilter === 'inactivas') {
            return row.dataset.activo === '0';
        }
        return true;
    });

    const totalItems = filtered.length;
    const totalPages = Math.ceil(totalItems / itemsPerPageCat) || 1;

    if (pagesStateCat[pageKey] > totalPages) pagesStateCat[pageKey] = totalPages;
    if (pagesStateCat[pageKey] < 1) pagesStateCat[pageKey] = 1;

    const curPage = pagesStateCat[pageKey];
    const start = (curPage - 1) * itemsPerPageCat;
    const end = start + itemsPerPageCat;

    allRows.forEach(r => r.style.display = 'none');
    filtered.slice(start, end).forEach(r => r.style.display = '');

    const container = document.getElementById(listContainerId);
    if (!container) return;
    container.innerHTML = '';

    // Flecha Anterior <
    const prevBtn = document.createElement('button');
    prevBtn.type = 'button';
    prevBtn.className = 'pagination-page-button';
    prevBtn.innerHTML = '&lsaquo;';
    prevBtn.setAttribute('aria-label', 'Pagina anterior');
    if (curPage === 1) {
        prevBtn.disabled = true;
    } else {
        prevBtn.onclick = function() {
            pagesStateCat[pageKey]--;
            paginateTableCat(tableId, listContainerId, pageKey, filterKey);
            table.scrollIntoView({ behavior: 'smooth', block: 'start' });
        };
    }
    container.appendChild(prevBtn);

    // Botones numéricos
    for (let p = 1; p <= totalPages; p++) {
        if (totalPages > 7 && p > 2 && p < totalPages - 1 && Math.abs(p - curPage) > 1) {
            if (p === 3 || p === totalPages - 2) {
                const dots = document.createElement('span');
                dots.textContent = '...';
                dots.style.cssText = 'color:#94a3b8; padding:0 4px; font-weight:700; font-size:0.9rem;';
                container.appendChild(dots);
            }
            continue;
        }

        const btn = document.createElement('button');
        btn.type = 'button';
        btn.textContent = p;
        btn.className = 'pagination-page-button';
        btn.setAttribute('aria-label', 'Pagina ' + p);

        if (p === curPage) {
            btn.classList.add('is-current');
            btn.setAttribute('aria-current', 'page');
        } else {
            btn.onclick = function() {
                pagesStateCat[pageKey] = p;
                paginateTableCat(tableId, listContainerId, pageKey, filterKey);
                table.scrollIntoView({ behavior: 'smooth', block: 'start' });
            };
        }
        container.appendChild(btn);
    }

    // Flecha Siguiente >
    const nextBtn = document.createElement('button');
    nextBtn.type = 'button';
    nextBtn.className = 'pagination-page-button';
    nextBtn.innerHTML = '&rsaquo;';
    nextBtn.setAttribute('aria-label', 'Pagina siguiente');
    if (curPage >= totalPages) {
        nextBtn.disabled = true;
    } else {
        nextBtn.onclick = function() {
            pagesStateCat[pageKey]++;
            paginateTableCat(tableId, listContainerId, pageKey, filterKey);
            table.scrollIntoView({ behavior: 'smooth', block: 'start' });
        };
    }
    container.appendChild(nextBtn);
}

window.confirmarCambioEstadoCategoria = function(tipo, id, estado, nombre) {
    const activar = Number(estado) === 1;
    const esGrupo = tipo === 'grupo';
    const entidad = esGrupo ? 'categoria' : 'subcategoria';
    const accion = activar ? 'Activar' : 'Inactivar';
    const modal = document.getElementById('modalConfirmarEstadoCategoria');
    const icono = document.getElementById('iconoEstadoCategoria');
    const titulo = document.getElementById('tituloEstadoCategoria');
    const texto = document.getElementById('textoEstadoCategoria');
    const boton = document.getElementById('btnConfirmarEstadoCategoria');
    if (!modal || !icono || !titulo || !texto || !boton) return;

    titulo.textContent = accion + ' ' + entidad;
    texto.textContent = '¿Confirmas ' + accion.toLowerCase() + ' "' + nombre + '"?';
    icono.style.background = activar ? '#dcfce7' : '#fee2e2';
    icono.style.color = activar ? '#15803d' : '#dc2626';
    icono.innerHTML = activar
        ? '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="m8 12 2.5 2.5L16 9"></path></svg>'
        : '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line></svg>';
    boton.textContent = accion;
    boton.style.background = activar ? '#15803d' : '#dc2626';
    const action = esGrupo ? 'toggle_grupo' : 'toggle_categoria';
    boton.href = 'index.php?action=' + action + '&id=' + encodeURIComponent(id) + '&estado=' + (activar ? 1 : 0) + '&redirect_to=categorias_admin';
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
};

window.switchTab = function(tab, subFilter) {
    document.querySelectorAll('.stat-card-square').forEach(function(card){ card.classList.remove('active-tab'); });
    document.getElementById('tabViewCategorias').style.display = 'none';
    document.getElementById('tabViewSubcategorias').style.display = 'none';
    document.getElementById('tabViewMacrocategorias').style.display = 'none';

    var titleEl = document.getElementById('mainTitleAdmin');

    if (tab === 'categorias') {
        if (subFilter === 'activas') {
            document.getElementById('tabCardCatActivas')?.classList.add('active-tab');
            filterStateCat.tablaGrupos = 'activas';
        } else if (subFilter === 'inactivas') {
            document.getElementById('tabCardCatInactivas')?.classList.add('active-tab');
            filterStateCat.tablaGrupos = 'inactivas';
        } else {
            document.getElementById('tabCardCategorias')?.classList.add('active-tab');
            filterStateCat.tablaGrupos = 'all';
        }
        document.getElementById('tabViewCategorias').style.display = 'block';
        if(titleEl) titleEl.textContent = 'Gestion de Categorias';
        pagesStateCat.tablaGrupos = 1;
        paginateTableCat('tablaGrupos', 'paginationListGrupos', 'tablaGrupos', 'tablaGrupos');
    } else if (tab === 'subcategorias') {
        if (subFilter === 'activas') {
            document.getElementById('tabCardSubActivas')?.classList.add('active-tab');
            filterStateCat.tablaCategorias = 'activas';
        } else if (subFilter === 'inactivas') {
            document.getElementById('tabCardSubInactivas')?.classList.add('active-tab');
            filterStateCat.tablaCategorias = 'inactivas';
        } else {
            document.getElementById('tabCardSubcategorias')?.classList.add('active-tab');
            filterStateCat.tablaCategorias = 'all';
        }
        document.getElementById('tabViewSubcategorias').style.display = 'block';
        if(titleEl) titleEl.textContent = 'Gestion de Subcategorias';
        pagesStateCat.tablaCategorias = 1;
        paginateTableCat('tablaCategorias', 'paginationListCat', 'tablaCategorias', 'tablaCategorias');
    } else if (tab === 'macrocategorias') {
        document.getElementById('tabCardMacrocategorias')?.classList.add('active-tab');
        document.getElementById('tabViewMacrocategorias').style.display = 'block';
        if(titleEl) titleEl.textContent = 'Gestion de Macrocategorias';
        pagesStateCat.tablaMom = 1;
        paginateTableCat('tablaMom', 'paginationListMom', 'tablaMom', null);
    }
};

window.filtrarTabla = function(tableId, inputId, colIdx) {
    if (tableId === 'tablaMom') {
        pagesStateCat.tablaMom = 1;
        paginateTableCat('tablaMom', 'paginationListMom', 'tablaMom', null);
    } else if (tableId === 'tablaGrupos') {
        pagesStateCat.tablaGrupos = 1;
        paginateTableCat('tablaGrupos', 'paginationListGrupos', 'tablaGrupos', 'tablaGrupos');
    } else if (tableId === 'tablaCategorias') {
        pagesStateCat.tablaCategorias = 1;
        paginateTableCat('tablaCategorias', 'paginationListCat', 'tablaCategorias', 'tablaCategorias');
    }
};

window.previewGrupoFileImage = function(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) { document.getElementById('grpImgPreview').src = e.target.result; };
        reader.readAsDataURL(input.files[0]);
        document.getElementById('grpFileName').textContent = input.files[0].name;
    }
};

window.previewCatFileImage = function(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) { document.getElementById('catImgPreview').src = e.target.result; };
        reader.readAsDataURL(input.files[0]);
        document.getElementById('catFileName').textContent = input.files[0].name;
    }
};

window.filtrarSubcats = function() {
    var midVal = document.getElementById('catMomentoSelect').value;
    var gSel = document.getElementById('catGrupoSelect');
    if (!gSel) return;
    if (!midVal) {
        gSel.disabled = true;
        gSel.style.background = '#f1f5f9';
        gSel.style.cursor = 'not-allowed';
        gSel.style.color = '#94a3b8';
        gSel.value = '';
        Array.from(gSel.options).forEach(function(opt, i) { if (i > 0) opt.style.display = 'none'; });
        return;
    }
    gSel.disabled = false;
    gSel.style.background = '#fff';
    gSel.style.cursor = 'pointer';
    gSel.style.color = '#0f172a';
    Array.from(gSel.options).forEach(function(opt, i) {
        if (i === 0) { opt.style.display = ''; return; }
        opt.style.display = (opt.getAttribute('data-momento') === midVal) ? '' : 'none';
    });
    gSel.value = '';
};

window.abrirModalGrupo = function() {
    document.getElementById('tituloModalGrupo').textContent = 'Nueva Categoria';
    document.getElementById('formGrupoAdmin').action = 'index.php?action=agregar_grupo';
    document.getElementById('grpId').value = '';
    document.getElementById('grpNombre').value = '';
    document.getElementById('grpMomentoSelect').value = '';
    document.getElementById('grpDescripcion').value = '';
    document.getElementById('grpImagenUrl').value = '';
    document.getElementById('grpFileInput').value = '';
    document.getElementById('grpFileName').textContent = 'Seleccionar archivo PC...';
    document.getElementById('grpImgPreview').src = 'Images/icono.png';
    document.getElementById('btnGuardarGrp').textContent = 'Guardar Categoria';
    document.getElementById('modalGrupoAdmin').style.display = 'flex';
};

window.editarGrupo = function(g) {
    document.getElementById('tituloModalGrupo').textContent = 'Editar Categoria';
    document.getElementById('formGrupoAdmin').action = 'index.php?action=actualizar_grupo';
    document.getElementById('grpId').value = g.id_grupo || '';
    document.getElementById('grpNombre').value = g.nombre || '';
    document.getElementById('grpMomentoSelect').value = g.id_momento || '';
    document.getElementById('grpDescripcion').value = g.descripcion || '';
    document.getElementById('grpImagenUrl').value = g.imagen || '';
    document.getElementById('grpFileInput').value = '';
    document.getElementById('grpFileName').textContent = 'Seleccionar archivo PC...';
    document.getElementById('grpImgPreview').src = g.imagen || 'Images/icono.png';
    document.getElementById('btnGuardarGrp').textContent = 'Actualizar Cambios';
    document.getElementById('modalGrupoAdmin').style.display = 'flex';
};

window.abrirModalCategoria = function() {
    document.getElementById('tituloModalCat').textContent = 'Nueva Subcategoria';
    document.getElementById('formCategoriaAdmin').action = 'index.php?action=crear_categoria';
    document.getElementById('catId').value = '';
    document.getElementById('catNombre').value = '';
    document.getElementById('catDescripcion').value = '';
    document.getElementById('catImagenUrl').value = '';
    document.getElementById('catFileInput').value = '';
    document.getElementById('catFileName').textContent = 'Seleccionar archivo PC...';
    document.getElementById('catImgPreview').src = 'Images/icono.png';
    document.getElementById('catMomentoSelect').value = '';
    document.getElementById('catGrupoSelect').value = '';
    window.filtrarSubcats();
    document.getElementById('btnGuardarCat').textContent = 'Guardar Subcategoria';
    document.getElementById('modalCategoriaAdmin').style.display = 'flex';
};

window.editarCategoria = function(cat) {
    document.getElementById('tituloModalCat').textContent = 'Editar Subcategoria';
    document.getElementById('formCategoriaAdmin').action = 'index.php?action=actualizar_categoria';
    document.getElementById('catId').value = cat.id_categoria || '';
    document.getElementById('catNombre').value = cat.nombre || '';
    document.getElementById('catDescripcion').value = cat.descripcion || '';
    document.getElementById('catImagenUrl').value = cat.imagen || '';
    document.getElementById('catFileInput').value = '';
    document.getElementById('catFileName').textContent = 'Seleccionar archivo PC...';
    document.getElementById('catImgPreview').src = cat.imagen || 'Images/icono.png';
    document.getElementById('catMomentoSelect').value = cat.id_momento || '';
    window.filtrarSubcats();
    document.getElementById('catGrupoSelect').value = cat.id_grupo || '';
    document.getElementById('btnGuardarCat').textContent = 'Actualizar Cambios';
    document.getElementById('modalCategoriaAdmin').style.display = 'flex';
};

window.cerrarModalId = function(id) {
    var m = document.getElementById(id);
    if (m) m.style.display = 'none';
    if (!document.querySelector('.cat-modal-overlay[style*="display: flex"]')) {
        document.body.style.overflow = '';
    }
};

function initializeCategoriesView() {
    window.switchTab('subcategorias', 'all');
}
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeCategoriesView, { once: true });
} else {
    initializeCategoriesView();
}
})();
