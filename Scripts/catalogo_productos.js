const campoBusquedaCatalogo = document.getElementById('cashierProductSearch');
const productosCatalogo = Array.from(document.querySelectorAll('[data-catalog-product]'));
const avisoCatalogoVacio = document.getElementById('catalogEmpty');
const contadorCatalogo = document.getElementById('catalogCount');

function filtrarCatalogoProductos() {
    const consulta = campoBusquedaCatalogo.value.trim().toLowerCase();
    let visibles = 0;

    productosCatalogo.forEach(function (producto) {
        const coincide = !consulta || producto.dataset.search.includes(consulta);
        producto.hidden = !coincide;
        if (coincide) visibles++;
    });

    avisoCatalogoVacio.hidden = visibles !== 0;
    contadorCatalogo.textContent = `Mostrando ${visibles} de ${productosCatalogo.length} productos`;
}

campoBusquedaCatalogo.addEventListener('input', filtrarCatalogoProductos);
filtrarCatalogoProductos();
