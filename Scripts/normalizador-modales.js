(function () {
    'use strict';

    if (window.normalizadorModalesActivo) return;
    window.normalizadorModalesActivo = true;

    const selectoresCierre = [
        '.btn-close-universal', '[class*="modal" i][class*="close" i]:not(.modal-close-glyph)',
        '.sidebar-close', '.sidebar-close-btn', '.staff-sidebar-close', '.btn-close-cart',
        '.product-detail-close', '.btn-close-mobile-filtros',
        '[aria-label*="cerrar" i]', '[title*="cerrar" i]'
    ].join(',');
    const selectorDialogo = '[class*="modal" i], [role="dialog"], [aria-modal="true"]';

    function buscarElementos(raiz, selector) {
        const encontrados = Array.from(raiz.querySelectorAll(selector));
        if (raiz.matches && raiz.matches(selector)) encontrados.unshift(raiz);
        return encontrados;
    }

    function normalizarCierre(boton) {
        if (boton.closest('[class*="toast" i]')) return;
        boton.classList.add('btn-close-universal');
        const simbolo = (boton.textContent || '').trim();
        if (!/^(×|✕|✖|x)$/i.test(simbolo) || boton.children.length > 0) return;
        const icono = document.createElement('span');
        icono.className = 'modal-close-glyph';
        icono.setAttribute('aria-hidden', 'true');
        icono.textContent = simbolo;
        boton.textContent = '';
        boton.appendChild(icono);
    }

    function normalizar(raiz) {
        buscarElementos(raiz, selectoresCierre).forEach(normalizarCierre);
        buscarElementos(raiz, 'button').forEach(function (boton) {
            if ((boton.textContent || '').trim().toLocaleLowerCase() !== 'cancelar') return;
            let dialogo = boton.parentElement;
            while (dialogo && dialogo !== document.body) {
                if (dialogo.matches(selectorDialogo) && dialogo.querySelector(selectoresCierre)) {
                    boton.remove();
                    return;
                }
                dialogo = dialogo.parentElement;
            }
        });
    }

    function iniciar() {
        normalizar(document);
        const observador = new MutationObserver(function (mutaciones) {
            mutaciones.forEach(function (mutacion) {
                mutacion.addedNodes.forEach(function (nodo) {
                    if (nodo.nodeType === Node.ELEMENT_NODE) normalizar(nodo);
                });
            });
        });
        observador.observe(document.body, { childList: true, subtree: true });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', iniciar, { once: true });
    } else {
        iniciar();
    }
})();
