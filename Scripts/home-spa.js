$(document).ready(function() {
    function refrescarDOMHibrido() {
        $.get(window.location.href, function(html) {
            const $newHtml = $('<div>').html(html);
            
            if ($('.sidebar-menu').length && $newHtml.find('.sidebar-menu').length) {
                $('.sidebar-menu').html($newHtml.find('.sidebar-menu').html());
            }
            if ($('#categoriaGrid').length && $newHtml.find('#categoriaGrid').length) {
                $('#categoriaGrid').html($newHtml.find('#categoriaGrid').html());
            }
            if ($('#momentosContent').length && $newHtml.find('#momentosContent').length) {
                $('#momentosContent').html($newHtml.find('#momentosContent').html());
            }
            if ($('#sugerenciasGrid').length && $newHtml.find('#sugerenciasGrid').length) {
                $('#sugerenciasGrid').html($newHtml.find('#sugerenciasGrid').html());
            }
            if ($('#idCategoria').length && $newHtml.find('#idCategoria').length) {
                $('#idCategoria').html($newHtml.find('#idCategoria').html());
            }
            if ($('#idMomento').length && $newHtml.find('#idMomento').length) {
                $('#idMomento').html($newHtml.find('#idMomento').html());
            }
            if ($('#idGrupo').length && $newHtml.find('#idGrupo').length) {
                $('#idGrupo').html($newHtml.find('#idGrupo').html());
            }
        }).fail(function() {
            window.location.reload();
        });
    }

    $(document).on('submit', '#formNuevaCategoria, #formNuevoGrupo, #formProducto', function(e) {
        e.preventDefault();
        const $form = $(this);
        const url = $form.attr('action');
        const formData = new FormData(this);
        
        const $btn = $form.find('button[type="submit"]');
        const btnText = $btn.text();
        $btn.prop('disabled', true).text('Guardando...');
        
        $.ajax({
            url: url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    if (typeof cerrarModal === 'function') {
                        cerrarModal('modalCategoria');
                        cerrarModal('modalSubcategoria');
                        cerrarModal('modalNuevoProducto');
                    }
                    
                    SPA.toast('success', 'Éxito', response.message);
                    setTimeout(function() {
                        window.location.reload();
                    }, 500);
                } else {
                    SPA.toast('error', 'Error', response.message || 'Ocurrió un error');
                }
            },
            error: function(jqXHR) {
                if (typeof SPA !== 'undefined' && typeof SPA.handleHttpError === 'function') {
                    SPA.handleHttpError(jqXHR);
                } else {
                    SPA.toast('error', 'Error', 'Ocurrió un error en la comunicación.');
                }
            },
            complete: function() {
                $btn.prop('disabled', false).text(btnText);
            }
        });
    });

    const btnConfirmDelete = document.getElementById('btnConfirmDelete');
    if (btnConfirmDelete) {
        btnConfirmDelete.addEventListener('click', function() {
            if (window.deleteUrl) {
                const $btn = $(this);
                const btnText = $btn.text();
                $btn.prop('disabled', true).text('Eliminando...');
                
                $.ajax({
                    url: window.deleteUrl,
                    type: 'GET',
                    success: function(response) {
                        if (response.success) {
                            if (typeof cerrarModal === 'function') {
                                cerrarModal('deleteConfirmModal');
                            }
                             SPA.toast('success', 'Éxito', response.message || 'Elemento eliminado');
                             setTimeout(function() {
                                 window.location.reload();
                             }, 500);
                        } else {
                            SPA.toast('error', 'Error', response.message || 'No se pudo eliminar');
                        }
                    },
                    error: function(jqXHR) {
                        if (typeof SPA !== 'undefined' && typeof SPA.handleHttpError === 'function') {
                            SPA.handleHttpError(jqXHR);
                        } else {
                            SPA.toast('error', 'Error', 'Ocurrió un error al intentar eliminar.');
                        }
                    },
                    complete: function() {
                        $btn.prop('disabled', false).text(btnText);
                    }
                });
            }
        });
    }
});
