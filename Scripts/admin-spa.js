// Helper universal para asegurar la disponibilidad del escáner en todo el ERP
window.ensureCameraScanner = function(callback) {
    if (window.UniversalCameraScanner && window.UniversalCameraScanner.isReal) {
        if (callback) callback();
        return;
    }
    if (window.scannerRemoteLoading) return;
    window.scannerRemoteLoading = true;
    const scannerScript = document.createElement('script');
    scannerScript.src = 'Scripts/scanner-camara.js';
    scannerScript.onload = function() {
        window.scannerRemoteLoading = false;
        if (callback) callback();
    };
    scannerScript.onerror = function() { window.scannerRemoteLoading = false; };
    document.head.appendChild(scannerScript);
};

// Proxy para capturar llamadas directas a UniversalCameraScanner.open antes de que el script termine de cargar
if (!window.UniversalCameraScanner || !window.UniversalCameraScanner.isReal) {
    window.UniversalCameraScanner = {
        isReal: false,
        open: function(options) {
            window.ensureCameraScanner(function() {
                if (window.UniversalCameraScanner && window.UniversalCameraScanner.isReal && typeof window.UniversalCameraScanner.open === 'function') {
                    window.UniversalCameraScanner.open(options);
                }
            });
        },
        close: function() {
            if (window.UniversalCameraScanner && window.UniversalCameraScanner.isReal) {
                window.UniversalCameraScanner.close();
            }
        }
    };
}

const SPA = {
    init: function() {
        this.setupAjax();
        this.createToastContainer();
        this.createLoader();
        this.setupNavigation();
        this.preloadScannerLibraries();

        window.addEventListener('popstate', (e) => {
            if (e.state && e.state.url) {
                this.loadModule(e.state.url, false);
            } else {
                location.reload();
            }
        });
    },

    preloadScannerLibraries: function() {
        setTimeout(function() {
            window.ensureCameraScanner();
        }, 100);
    },

    setupAjax: function() {
        $.ajaxSetup({
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            error: function(jqXHR, textStatus, errorThrown) {
                SPA.handleHttpError(jqXHR);
            }
        });
    },

    handleHttpError: function(jqXHR) {
        let msg = "Ocurrió un error inesperado.";
        try {
            if (jqXHR.responseText) {
                const response = JSON.parse(jqXHR.responseText);
                if (response.message) msg = response.message;
            }
        } catch (e) {
            
        }

        switch (jqXHR.status) {
            case 401:
                SPA.toast('error', 'Sesión expirada', 'Tu sesión ha terminado. Serás redirigido...');
                setTimeout(() => window.location.href = 'index.php?action=login', 2000);
                return;
            case 403:
                msg = "No tienes permisos para realizar esta acción.";
                break;
            case 419:
                msg = "Token de seguridad (CSRF) inválido o vencido. Recarga la página.";
                break;
            case 422:
                msg = msg || "Error de validación en los datos enviados.";
                break;
            case 500:
                msg = "Error interno del servidor. Inténtalo más tarde.";
                break;
        }

        SPA.toast('error', 'Error', msg);
    },

    createLoader: function() {
        if (!$('#spa-loader').length) {
            $('body').prepend('<div id="spa-loader"></div>');
        }
    },

    showLoader: function() { $('#spa-loader').show(); },
    hideLoader: function() { $('#spa-loader').hide(); },

    createToastContainer: function() {
        if (!$('#toast-container').length) {
            $('body').append('<div id="toast-container"></div>');
        }
    },

    toast: function(type, title, message, duration = 4000) {
        message = (message || '').trim();
        // Evitar duplicados idénticos en pantalla
        const existingToasts = $('#toast-container .custom-toast');
        for (let i = 0; i < existingToasts.length; i++) {
            if ($(existingToasts[i]).find('.toast-message').text().trim() === message) {
                return;
            }
        }
        // No acumular más de 2 toasts simultáneos
        if (existingToasts.length >= 2) {
            existingToasts.first().remove();
        }

        const icons = {
            'success': '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>',
            'error': '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>',
            'info': '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>',
            'warning': '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>'
        };

        const $toast = $(`
            <div class="custom-toast toast-${type}">
                <div class="toast-icon">${icons[type] || icons['info']}</div>
                <div class="toast-content">
                    <div class="toast-title">${title}</div>
                    <div class="toast-message">${message}</div>
                </div>
                <button class="toast-close">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
                <div class="toast-progress" style="animation-duration: ${duration}ms"></div>
            </div>
        `);

        $('#toast-container').append($toast);

        $toast[0].offsetHeight; 
        $toast.addClass('toast-show');

        let removalTimer = null;
        let timerStartedAt = 0;
        let remainingDuration = duration;
        const progress = $toast.find('.toast-progress')[0];

        const removeToast = () => {
            if (removalTimer) clearTimeout(removalTimer);
            removalTimer = null;
            $toast.removeClass('toast-show').addClass('toast-hide');
            setTimeout(() => $toast.remove(), 300);
        };

        $toast.find('.toast-close').on('click', removeToast);

        const resumeRemovalTimer = () => {
            if (duration <= 0 || removalTimer || remainingDuration <= 0) return;
            timerStartedAt = Date.now();
            removalTimer = setTimeout(removeToast, remainingDuration);
            if (progress) progress.style.animationPlayState = 'running';
        };
        const pauseRemovalTimer = () => {
            if (!removalTimer) return;
            clearTimeout(removalTimer);
            removalTimer = null;
            remainingDuration = Math.max(0, remainingDuration - (Date.now() - timerStartedAt));
            if (progress) progress.style.animationPlayState = 'paused';
        };

        $toast.on('mouseenter focusin', pauseRemovalTimer);
        $toast.on('mouseleave', resumeRemovalTimer);
        $toast.on('focusout', function(event) {
            if (!$toast[0].contains(event.relatedTarget)) resumeRemovalTimer();
        });

        resumeRemovalTimer();
    },

    setupNavigation: function() {
        $(document).on('click', '#openAdminSidebar', function(e) {
            e.preventDefault();
            $('#adminSidebar, #adminSidebarOverlay').addClass('active');
            $('body').css('overflow', 'hidden');
        });

        $(document).on('click', '#closeAdminSidebar, #adminSidebarOverlay', function(e) {
            e.preventDefault();
            $('#adminSidebar, #adminSidebarOverlay').removeClass('active');
            $('body').css('overflow', '');
        });

        $(document).on('click', '#adminSidebar .sidebar-link, #admin-container .spa-link', function(e) {
            const url = $(this).attr('href');
            if (!url) return;

            if (url.indexOf('action=logout') !== -1 || url.indexOf('action=home_usuario') !== -1 || url.indexOf('action=ver_categoria') !== -1) {
                window.location.href = url;
                return;
            }

            e.preventDefault();
            
            $('#adminSidebar, #adminSidebarOverlay').removeClass('active');
            $('body').css('overflow', '');

            $('#adminSidebar .sidebar-link').removeClass('active');
            $(this).addClass('active');

            SPA.loadModule(url, true);
        });
    },

    loadModule: function(url, pushState = true) {
        if (window.UniversalCameraScanner && window.UniversalCameraScanner.isOpen) {
            window.UniversalCameraScanner.close();
        }
        this.showLoader();
        const $container = $('#admin-container');
        
        $container.addClass('admin-main-transition').removeClass('admin-main-visible');

        $.ajax({
            url: url,
            cache: false
        })
            .done(function(htmlFragment) {
                // Parse and extract inner content to prevent nested layouts and duplicate topbars
                const $doc = $('<div>').append($.parseHTML(htmlFragment, document, true));
                
                // Extract and apply any <style> tags from the response
                $doc.find('style').each(function() {
                    const css = $(this).html();
                    if (css && css.trim().length > 0) {
                        $('<style class="spa-dynamic-style">').html(css).appendTo('head');
                    }
                });

                const actionStylesheets = {
                    caja_admin: ['Styles/Vistas/Admin/caja_admin.css'],
                    carrusel_admin: ['Styles/Vistas/Admin/carrusel_admin.css'],
                    categorias_admin: ['Styles/Vistas/Admin/categorias_admin.css'],
                    compras_admin: ['Styles/Vistas/Admin/compras_admin.css'],
                    dashboard_ganancias: ['Styles/Vistas/Admin/dashboard_ganancias.css'],
                    delivery_admin: ['Styles/Vistas/Admin/delivery_admin.css'],
                    kardex_admin: ['Styles/Vistas/Admin/kardex_admin.css'],
                    proveedores_admin: ['Styles/Vistas/Admin/proveedores_admin.css'],
                    ventas_admin: ['Styles/Vistas/Admin/ventas_admin.css'],
                    verificacion_pagos: ['Styles/Vistas/Admin/verificacion_pagos.css'],
                    mi_perfil_admin: ['Styles/styles-cuenta.css'],
                    inventario: ['Styles/Vistas/Inventario/inventario.css', 'Styles/Vistas/Inventario/inventario_panel.css'],
                    productos_admin: ['Styles/Vistas/Inventario/inventario.css', 'Styles/Vistas/Inventario/inventario_panel.css'],
                    pos: ['Styles/styles-pos.css', 'Styles/styles-users-admin.css', 'Styles/Vistas/Cajero/caja_punto_venta.css']
                };
                const requestedAction = new URL(url, window.location.href).searchParams.get('action');
                $('link[data-admin-view-style]').remove();
                (actionStylesheets[requestedAction] || []).forEach(function(stylesheet) {
                    $('<link rel="stylesheet" data-admin-view-style>')
                        .attr('href', `${stylesheet}?v=${Date.now()}`)
                        .appendTo('head');
                });

                if (requestedAction === 'pos') {
                    $('<link rel="stylesheet" data-admin-view-style>')
                        .attr('href', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css')
                        .appendTo('head');
                }

                let $extracted = $doc.find('#admin-container');
                if (!$extracted.length) {
                    $extracted = $doc.find('.admin-content');
                }
                if (!$extracted.length) {
                    $extracted = $doc.find('.admin-main');
                }

                // Update topbar brand title and icon seamlessly
                const $newBrand = $doc.find('.admin-topbar-brand');
                if ($newBrand.length) {
                    $('.admin-topbar .admin-topbar-brand').html($newBrand.html());
                }

                // Update topbar actions seamlessly to prevent buttons leaking between views
                const $newActions = $doc.find('.admin-topbar-actions');
                if ($newActions.length) {
                    $('.admin-topbar .admin-topbar-actions').html($newActions.html());
                }

                if ($extracted.length) {
                    const $contentNodes = $extracted.contents().clone();
                    $contentNodes.filter('script').add($contentNodes.find('script')).remove();
                    $contentNodes.filter('link[href*="Styles/Vistas/"], link[href*="Styles/styles-pos.css"], link[href*="Styles/styles-users-admin.css"], link[href*="font-awesome/6.4.0/css/all.min.css"]')
                        .add($contentNodes.find('link[href*="Styles/Vistas/"], link[href*="Styles/styles-pos.css"], link[href*="Styles/styles-users-admin.css"], link[href*="font-awesome/6.4.0/css/all.min.css"]')).remove();
                    $container.empty().append($contentNodes);
                } else {
                    const $contentNodes = $('<div>').append($.parseHTML(htmlFragment, document, true)).contents();
                    $contentNodes.filter('script').add($contentNodes.find('script')).remove();
                    $contentNodes.filter('link[href*="Styles/Vistas/"], link[href*="Styles/styles-pos.css"], link[href*="Styles/styles-users-admin.css"], link[href*="font-awesome/6.4.0/css/all.min.css"]')
                        .add($contentNodes.find('link[href*="Styles/Vistas/"], link[href*="Styles/styles-pos.css"], link[href*="Styles/styles-users-admin.css"], link[href*="font-awesome/6.4.0/css/all.min.css"]')).remove();
                    $container.empty().append($contentNodes);
                }
                $container.find('link[rel="stylesheet"]').remove();

                // Execute scripts with window.eval to preserve global function definitions
                $doc.find('script').each(function() {
                    const src = $(this).attr('src');
                    if (src && src.indexOf('admin-spa.js') === -1 && src.indexOf('jquery') === -1) {
                        $.getScript(src);
                    } else if (!src) {
                        const scriptCode = $(this).html();
                        if (scriptCode && scriptCode.trim().length > 0) {
                            try { 
                                window.eval(scriptCode); 
                            } catch(e) { 
                                console.error('Error running SPA inline script:', e); 
                            }
                        }
                    }
                });

                setTimeout(() => {
                    $container.addClass('admin-main-visible').removeClass('admin-main-transition');
                }, 50);

                if (pushState) {
                    window.history.pushState({ url: url }, '', url);
                }
            })
            .always(function() {
                SPA.hideLoader();
            });
    }
};

$(document).ready(function() {
    SPA.init();
});
