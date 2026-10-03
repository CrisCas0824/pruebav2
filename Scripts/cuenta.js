'use strict';

document.addEventListener('DOMContentLoaded', function () {

    const menuItems = document.querySelectorAll(
        '.cuenta-menu-item'
    );

    const tabs = document.querySelectorAll(
        '.cuenta-tab'
    );

    const modalDireccion = document.getElementById('modalDireccion');
    const modalDetallePedido = document.getElementById('modalDetallePedido');
    const modalEliminarDireccion = document.getElementById('modalEliminarDireccion');
    const formPerfil = document.getElementById('formActualizarPerfil');
    const formPassword = document.getElementById('formCambiarPassword');
    const formDireccion = document.getElementById('formDireccion');
    const direccionId = document.getElementById('direccionId');
    const direccionDepartamento = document.getElementById('direccionDepartamento');
    const direccionProvincia = document.getElementById('direccionProvincia');
    const direccionDistrito = document.getElementById('direccionDistrito');
    const direccionCalle = document.getElementById('direccionCalle');
    const direccionReferencia = document.getElementById('direccionReferencia');
    const direccionPrincipal = document.getElementById('direccionPrincipal');
    const tituloModalDireccion = document.getElementById('tituloModalDireccion');
    const btnGuardarDireccion = document.getElementById('btnGuardarDireccion');
    const detallePedidoTitulo = document.getElementById('detallePedidoTitulo');
    const detallePedidoContenido = document.getElementById('detallePedidoContenido');
    const confirmarEliminarDireccion = document.getElementById('confirmarEliminarDireccion');

    let idDireccionEliminar = null;

    const toggleEditPerfil = document.getElementById('toggleEditPerfil');
    const sliderPerfil = document.getElementById('sliderPerfil');
    const sliderCirclePerfil = document.getElementById('sliderCirclePerfil');
    const perfilNombre = document.getElementById('perfilNombre');
    const perfilApellido = document.getElementById('perfilApellido');
    const perfilTelefono = document.getElementById('perfilTelefono');
    const accionesPerfil = document.getElementById('accionesPerfil');

    if (toggleEditPerfil) {
        toggleEditPerfil.addEventListener('change', function() {
            const isEditing = this.checked;
            
            if (isEditing) {
                sliderPerfil.style.backgroundColor = '#0d9488';
                sliderCirclePerfil.style.transform = 'translateX(20px)';
            } else {
                sliderPerfil.style.backgroundColor = '#d1d5db';
                sliderCirclePerfil.style.transform = 'translateX(0)';
            }
            
            if (perfilNombre) perfilNombre.readOnly = !isEditing;
            if (perfilApellido) perfilApellido.readOnly = !isEditing;
            if (perfilTelefono) perfilTelefono.readOnly = !isEditing;
            
            if (accionesPerfil) {
                if (isEditing) {
                    accionesPerfil.style.display = '';
                    setTimeout(() => {
                        accionesPerfil.style.opacity = '1';
                    }, 10);
                } else {
                    accionesPerfil.style.opacity = '0';
                    setTimeout(() => {
                        if (!toggleEditPerfil.checked) {
                            accionesPerfil.style.display = 'none';
                        }
                    }, 300);
                }
            }
        });
    }

    const btnVerificarPassword = document.getElementById('btnVerificarPassword');
    const passwordActual = document.getElementById('passwordActual');
    const passwordNueva = document.getElementById('passwordNueva');
    const passwordConfirmacion = document.getElementById('passwordConfirmacion');
    const accionesPassword = document.getElementById('accionesPassword');
    const mensajeBloqueoPassword = document.getElementById('mensajeBloqueoPassword');

    function iniciarBloqueo(segundos) {
        if (!btnVerificarPassword) return;
        
        passwordActual.disabled = true;
        btnVerificarPassword.disabled = true;
        mensajeBloqueoPassword.style.display = 'block';
        
        mensajeBloqueoPassword.textContent = `Por el momento no puede cambiar la contraseña, bloqueado por ${segundos} segundos.`;
        
        const intervalo = setInterval(() => {
            segundos--;
            if (segundos <= 0) {
                clearInterval(intervalo);
                passwordActual.disabled = false;
                passwordActual.value = '';
                btnVerificarPassword.disabled = false;
                mensajeBloqueoPassword.style.display = 'none';
            } else {
                mensajeBloqueoPassword.textContent = `Por el momento no puede cambiar la contraseña, bloqueado por ${segundos} segundos.`;
            }
        }, 1000);
    }

    if (typeof initialLockTime !== 'undefined' && initialLockTime > 0) {
        iniciarBloqueo(initialLockTime);
    }

    if (btnVerificarPassword) {
        btnVerificarPassword.addEventListener('click', async function() {
            if (btnVerificarPassword.disabled) return;
            
            const currentPassword = passwordActual.value.trim();
            if (!currentPassword) {
                mostrarToast('Ingresa tu contraseña actual primero.', 'error');
                return;
            }

            const formData = new FormData();
            formData.append('password_actual', currentPassword);
            formData.append('csrf_token', formPassword.querySelector('input[name="csrf_token"]').value);
            
            cambiarEstadoBoton(btnVerificarPassword, true, 'Verificando...');

            try {
                const respuesta = await peticionAjax('index.php?action=verificar_password_actual', formData);
                
                mostrarToast(respuesta.message, 'success');
                passwordActual.readOnly = true;
                passwordActual.style.backgroundColor = '#f3f4f6';
                passwordActual.style.cursor = 'not-allowed';
                btnVerificarPassword.style.display = 'none';
                
                passwordNueva.readOnly = false;
                passwordNueva.style.backgroundColor = '';
                passwordNueva.style.cursor = '';
                
                passwordConfirmacion.readOnly = false;
                passwordConfirmacion.style.backgroundColor = '';
                passwordConfirmacion.style.cursor = '';
                
                accionesPassword.style.display = '';
                setTimeout(() => { accionesPassword.style.opacity = '1'; }, 10);
                
            } catch (error) {
                mostrarToast(error.message, 'error');
                
                const match = error.message.match(/bloqueado por (\d+) segundos/);
                if (match) {
                    iniciarBloqueo(parseInt(match[1]));
                }
            } finally {
                cambiarEstadoBoton(btnVerificarPassword, false, 'Verificar');
            }
        });
    }

    const btnCancelarPassword = document.getElementById('btnCancelarPassword');
    const mensajeConfirmacion = document.getElementById('mensajeConfirmacion');

    if (btnCancelarPassword) {
        btnCancelarPassword.addEventListener('click', function() {
            passwordActual.value = '';
            passwordNueva.value = '';
            passwordConfirmacion.value = '';
            
            passwordActual.readOnly = false;
            passwordActual.style.backgroundColor = '';
            passwordActual.style.cursor = '';
            btnVerificarPassword.style.display = 'flex';
            
            passwordNueva.readOnly = true;
            passwordNueva.style.backgroundColor = '#f3f4f6';
            passwordNueva.style.cursor = 'not-allowed';
            
            passwordConfirmacion.readOnly = true;
            passwordConfirmacion.style.backgroundColor = '#f3f4f6';
            passwordConfirmacion.style.cursor = 'not-allowed';
            
            passwordConfirmacion.style.borderColor = '';
            passwordConfirmacion.style.boxShadow = '';
            if (mensajeConfirmacion) {
                mensajeConfirmacion.style.color = '#6b7280';
                mensajeConfirmacion.textContent = 'Asegúrate de que coincida con la nueva contraseña.';
            }
            
            accionesPassword.style.opacity = '0';
            setTimeout(() => { accionesPassword.style.display = 'none'; }, 300);
            
            limpiarErrores(formPassword);
        });
    }

    function validarCoincidenciaPasswords() {
        if (passwordNueva.readOnly) return;
        
        const valNueva = passwordNueva.value;
        const valConf = passwordConfirmacion.value;
        
        if (valConf.length > 0) {
            if (valNueva !== valConf) {
                passwordConfirmacion.style.setProperty('border-color', '#ef4444', 'important');
                passwordConfirmacion.style.setProperty('box-shadow', '0 0 0 3px rgba(239, 68, 68, 0.15)', 'important');
                if (mensajeConfirmacion) {
                    mensajeConfirmacion.style.color = '#ef4444';
                    mensajeConfirmacion.textContent = 'Las contraseñas no coinciden.';
                }
            } else {
                passwordConfirmacion.style.setProperty('border-color', '#10b981', 'important');
                passwordConfirmacion.style.setProperty('box-shadow', '0 0 0 3px rgba(16, 185, 129, 0.15)', 'important');
                if (mensajeConfirmacion) {
                    mensajeConfirmacion.style.color = '#10b981';
                    mensajeConfirmacion.textContent = 'Las contraseñas coinciden.';
                }
            }
        } else {
            passwordConfirmacion.style.borderColor = '';
            passwordConfirmacion.style.boxShadow = '';
            if (mensajeConfirmacion) {
                mensajeConfirmacion.style.color = '#6b7280';
                mensajeConfirmacion.textContent = 'Asegúrate de que coincida con la nueva contraseña.';
            }
        }
    }

    if (passwordNueva && passwordConfirmacion) {
        passwordNueva.addEventListener('input', validarCoincidenciaPasswords);
        passwordConfirmacion.addEventListener('input', validarCoincidenciaPasswords);
    }

    function activarTab(nombreTab) {
        menuItems.forEach(function (item) {
            item.classList.toggle(
                'active',
                item.dataset.tab === nombreTab
            );
        });

        tabs.forEach(function (tab) {
            tab.classList.toggle(
                'active',
                tab.id === `tab-${nombreTab}`
            );
        });

        history.replaceState(
            null,
            '',
            `#${nombreTab}`
        );
    }

    menuItems.forEach(function (item) {
        item.addEventListener('click', function () {
            activarTab(item.dataset.tab);
        });
    });

    const tabInicial =
        window.location.hash.replace('#', '');

    if (
        ['pedidos', 'direcciones', 'informacion', 'seguridad']
            .includes(tabInicial)
    ) {
        activarTab(tabInicial);
    }

    function abrirModal(idModal) {
        const modal =
            document.getElementById(idModal);

        if (!modal) {
            return;
        }

        modal.classList.add('active');
        modal.setAttribute('aria-hidden', 'false');

        document.body.classList.add(
            'cuenta-modal-open'
        );
    }

    function cerrarModal(idModal) {
        const modal =
            document.getElementById(idModal);

        if (!modal) {
            return;
        }

        modal.classList.remove('active');
        modal.setAttribute('aria-hidden', 'true');

        const otroModalAbierto =
            document.querySelector(
                '.cuenta-modal-overlay.active'
            );

        if (!otroModalAbierto) {
            document.body.classList.remove(
                'cuenta-modal-open'
            );
        }
    }

    document.addEventListener('click', function (event) {
        const closeButton =
            event.target.closest('[data-close-modal]');

        if (closeButton) {
            cerrarModal(
                closeButton.dataset.closeModal
            );

            return;
        }

        if (
            event.target.classList.contains(
                'cuenta-modal-overlay'
            )
        ) {
            cerrarModal(event.target.id);
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') {
            return;
        }

        document
            .querySelectorAll(
                '.cuenta-modal-overlay.active'
            )
            .forEach(function (modal) {
                cerrarModal(modal.id);
            });
    });

    function limpiarFormularioDireccion() {
        formDireccion?.reset();

        if (direccionId) {
            direccionId.value = '';
        }

        limpiarErrores(formDireccion);

        if (formDireccion) {
            formDireccion.action =
                'index.php?action=crear_direccion';
        }

        if (tituloModalDireccion) {
            tituloModalDireccion.textContent =
                'Nueva dirección';
        }

        if (btnGuardarDireccion) {
            btnGuardarDireccion.textContent =
                'Guardar dirección';
        }
    }

    function abrirNuevaDireccion() {
        limpiarFormularioDireccion();
        abrirModal('modalDireccion');
    }

    document
        .getElementById('btnNuevaDireccion')
        ?.addEventListener(
            'click',
            abrirNuevaDireccion
        );

    document
        .getElementById('btnNuevaDireccionEmpty')
        ?.addEventListener(
            'click',
            abrirNuevaDireccion
        );

    document.addEventListener('click', function (event) {
        const editarButton =
            event.target.closest(
                '.editar-direccion'
            );

        if (!editarButton) {
            return;
        }

        const card =
            editarButton.closest(
                '.direccion-card'
            );

        if (!card) {
            return;
        }

        let direccion;

        try {
            direccion = JSON.parse(
                card.dataset.direccion
            );
        } catch (error) {
            mostrarToast(
                'No se pudieron cargar los datos de la dirección.',
                'error'
            );

            return;
        }

        limpiarFormularioDireccion();

        direccionId.value =
            direccion.id_direccion || '';

        direccionDepartamento.value =
            direccion.departamento || '';

        direccionProvincia.value =
            direccion.provincia || '';

        direccionDistrito.value =
            direccion.distrito || '';

        direccionCalle.value =
            direccion.calle || '';

        direccionReferencia.value =
            direccion.referencia || '';

        direccionPrincipal.checked =
            Number(direccion.es_principal) === 1;

        formDireccion.action =
            'index.php?action=actualizar_direccion';

        tituloModalDireccion.textContent =
            'Editar dirección';

        btnGuardarDireccion.textContent =
            'Actualizar dirección';

        abrirModal('modalDireccion');
    });

    document.addEventListener('click', async function (event) {
        const principalButton =
            event.target.closest(
                '.principal-direccion'
            );

        if (!principalButton) {
            return;
        }

        const formData = new FormData();

        formData.append(
            'id_direccion',
            principalButton.dataset.idDireccion
        );

        formData.append(
            'csrf_token',
            obtenerCsrf()
        );

        try {
            const respuesta = await peticionAjax(
                'index.php?action=direccion_principal',
                formData
            );

            mostrarToast(
                respuesta.message,
                'success'
            );

            setTimeout(function () {
                window.location.reload();
            }, 550);

        } catch (error) {
            mostrarToast(
                error.message,
                'error'
            );
        }
    });

    document.addEventListener('click', function (event) {
        const deleteButton =
            event.target.closest(
                '.eliminar-direccion'
            );

        if (!deleteButton) {
            return;
        }

        idDireccionEliminar =
            deleteButton.dataset.idDireccion;

        abrirModal('modalEliminarDireccion');
    });

    confirmarEliminarDireccion?.addEventListener(
        'click',
        async function () {

            if (!idDireccionEliminar) {
                return;
            }

            const formData = new FormData();

            formData.append(
                'id_direccion',
                idDireccionEliminar
            );

            formData.append(
                'csrf_token',
                obtenerCsrf()
            );

            confirmarEliminarDireccion.disabled = true;
            confirmarEliminarDireccion.textContent =
                'Eliminando...';

            try {
                const respuesta = await peticionAjax(
                    'index.php?action=eliminar_direccion',
                    formData
                );

                mostrarToast(
                    respuesta.message,
                    'success'
                );

                cerrarModal(
                    'modalEliminarDireccion'
                );

                setTimeout(function () {
                    window.location.reload();
                }, 550);

            } catch (error) {
                mostrarToast(
                    error.message,
                    'error'
                );

            } finally {
                confirmarEliminarDireccion.disabled = false;
                confirmarEliminarDireccion.textContent =
                    'Eliminar';

                idDireccionEliminar = null;
            }
        }
    );

    formPerfil?.addEventListener(
        'submit',
        async function (event) {

            event.preventDefault();

            limpiarErrores(formPerfil);

            const submitButton =
                formPerfil.querySelector(
                    '[type="submit"]'
                );

            cambiarEstadoBoton(
                submitButton,
                true,
                'Guardando...'
            );

            try {
                const respuesta = await peticionAjax(
                    formPerfil.action,
                    new FormData(formPerfil)
                );

                mostrarToast(
                    respuesta.message,
                    'success'
                );

                const usuario =
                    respuesta.data?.usuario;

                if (usuario) {
                    actualizarPerfilVisual(usuario);
                }

            } catch (error) {
                mostrarErrores(
                    formPerfil,
                    error.errors
                );

                mostrarToast(
                    error.message,
                    'error'
                );

            } finally {
                cambiarEstadoBoton(
                    submitButton,
                    false,
                    'Guardar cambios'
                );
            }
        }
    );

    formPassword?.addEventListener(
        'submit',
        async function (event) {

            event.preventDefault();

            limpiarErrores(formPassword);

            const submitButton =
                formPassword.querySelector(
                    '[type="submit"]'
                );

            cambiarEstadoBoton(
                submitButton,
                true,
                'Actualizando...'
            );

            try {
                const respuesta = await peticionAjax(
                    formPassword.action,
                    new FormData(formPassword)
                );

                mostrarToast(
                    respuesta.message,
                    'success'
                );

                formPassword.reset();

            } catch (error) {
                mostrarErrores(
                    formPassword,
                    error.errors
                );

                mostrarToast(
                    error.message,
                    'error'
                );

            } finally {
                cambiarEstadoBoton(
                    submitButton,
                    false,
                    'Actualizar contraseña'
                );
            }
        }
    );

    formDireccion?.addEventListener(
        'submit',
        async function (event) {

            event.preventDefault();

            limpiarErrores(formDireccion);

            const submitButton =
                formDireccion.querySelector(
                    '[type="submit"]'
                );

            cambiarEstadoBoton(
                submitButton,
                true,
                'Guardando...'
            );

            try {
                const respuesta = await peticionAjax(
                    formDireccion.action,
                    new FormData(formDireccion)
                );

                mostrarToast(
                    respuesta.message,
                    'success'
                );

                cerrarModal('modalDireccion');
                limpiarFormularioDireccion();

                setTimeout(function () {
                    window.location.reload();
                }, 550);

            } catch (error) {
                mostrarErrores(
                    formDireccion,
                    error.errors
                );

                mostrarToast(
                    error.message,
                    'error'
                );

            } finally {
                cambiarEstadoBoton(
                    submitButton,
                    false,
                    direccionId?.value
                        ? 'Actualizar dirección'
                        : 'Guardar dirección'
                );
            }
        }
    );

    document.addEventListener('click', async function (event) {
        const pedidoButton =
            event.target.closest(
                '.pedido-view-button'
            );

        if (!pedidoButton) {
            return;
        }

        const idPedido =
            pedidoButton.dataset.idPedido;

        detallePedidoTitulo.textContent =
            `Pedido #${String(idPedido).padStart(5, '0')}`;

        detallePedidoContenido.innerHTML = `
            <div class="cuenta-loading">
                Cargando detalle del pedido...
            </div>
        `;

        abrirModal('modalDetallePedido');

        try {
            const respuesta = await peticionAjax(
                `index.php?action=detalle_pedido_usuario&id=${encodeURIComponent(idPedido)}`
            );

            renderizarDetallePedido(
                respuesta.data
            );

        } catch (error) {
            detallePedidoContenido.innerHTML = `
                <div class="cuenta-empty-state">
                    <h3>No se pudo cargar el pedido</h3>
                    <p>${escaparHtml(error.message)}</p>
                </div>
            `;
        }
    });

    function renderizarDetallePedido(data) {
        const pedido = data?.pedido || {};
        const detalle = Array.isArray(data?.detalle)
            ? data.detalle
            : [];

        let productosHtml = '';

        detalle.forEach(function (item) {
            const unidad =
                item.unidad_medida === 'KG'
                    ? 'kg'
                    : 'und.';

            productosHtml += `
                <article class="detalle-pedido-item">

                    <img
                        src="${escaparHtml(item.imagen || '')}"
                        alt="${escaparHtml(item.titulo || 'Producto')}"
                    >

                    <div>
                        <h4>${escaparHtml(item.titulo || '')}</h4>

                        <p>
                            ${Number(item.cantidad)} ${unidad}
                            ×
                            S/ ${Number(item.precio_unitario).toFixed(2)}
                        </p>
                    </div>

                    <strong>
                        S/ ${Number(item.subtotal).toFixed(2)}
                    </strong>

                </article>
            `;
        });

        if (!productosHtml) {
            productosHtml = `
                <div class="cuenta-empty-state">
                    <p>No se encontraron productos en este pedido.</p>
                </div>
            `;
        }

        detallePedidoContenido.innerHTML = `
            <div class="detalle-pedido-summary">

                <div class="detalle-summary-card">
                    <small>Total</small>
                    <strong>
                        S/ ${Number(pedido.total || 0).toFixed(2)}
                    </strong>
                </div>

                <div class="detalle-summary-card">
                    <small>Estado</small>
                    <strong>
                        ${escaparHtml(
                            String(pedido.estado || 'pendiente')
                                .replace('_', ' ')
                        )}
                    </strong>
                </div>

                <div class="detalle-summary-card">
                    <small>Entrega</small>
                    <strong>
                        ${escaparHtml(
                            pedido.tipo_entrega || 'delivery'
                        )}
                    </strong>
                </div>

            </div>

            <div>
                ${productosHtml}
            </div>
        `;
    }

    document.addEventListener('click', function (event) {
        const button =
            event.target.closest(
                '.toggle-password'
            );

        if (!button) {
            return;
        }

        const input =
            document.getElementById(
                button.dataset.target
            );

        if (!input) {
            return;
        }

        const oculto =
            input.type === 'password';

        input.type =
            oculto ? 'text' : 'password';

        const eyeClosed = button.querySelector('.eye-closed');
        const eyeOpen = button.querySelector('.eye-open');
        if (eyeClosed) eyeClosed.style.display = oculto ? 'none' : 'block';
        if (eyeOpen) eyeOpen.style.display = oculto ? 'block' : 'none';
        const activeEye = oculto ? eyeOpen : eyeClosed;
        if (activeEye) {
            activeEye.classList.remove('eye-transition-in');
            void activeEye.offsetWidth;
            activeEye.classList.add('eye-transition-in');
        }

        const label = oculto ? 'Ocultar contraseña' : 'Mostrar contraseña';
        button.setAttribute('aria-label', label);
        button.setAttribute('title', label);
    });

    async function peticionAjax(url, formData = null) {
        const opciones = {
            method: formData ? 'POST' : 'GET',
            headers: {
                'X-Requested-With':
                    'XMLHttpRequest'
            }
        };

        if (formData) {
            opciones.body = formData;
        }

        const response =
            await fetch(url, opciones);

        let data;

        try {
            data = await response.json();
        } catch (error) {
            throw crearError(
                'El servidor devolvió una respuesta inválida.',
                {}
            );
        }

        if (!response.ok || !data.success) {
            throw crearError(
                data.message || 'Ocurrió un error.',
                data.errors || {}
            );
        }

        return data;
    }

    function crearError(message, errors) {
        const error = new Error(message);

        error.errors = errors;

        return error;
    }

    function obtenerCsrf() {
        return document.querySelector(
            'input[name="csrf_token"]'
        )?.value || '';
    }

    function limpiarErrores(form) {
        if (!form) {
            return;
        }

        form.querySelectorAll(
            '.input-error'
        ).forEach(function (input) {
            input.classList.remove(
                'input-error'
            );
        });

        form.querySelectorAll(
            '.field-error'
        ).forEach(function (field) {
            field.textContent = '';
        });
    }

    function mostrarErrores(form, errors = {}) {
        if (!form) {
            return;
        }

        Object.entries(errors).forEach(
            function ([campo, mensaje]) {

                const input =
                    form.querySelector(
                        `[name="${campo}"]`
                    );

                const errorField =
                    form.querySelector(
                        `[data-error-for="${campo}"]`
                    );

                input?.classList.add(
                    'input-error'
                );

                if (errorField) {
                    errorField.textContent =
                        mensaje;
                }
            }
        );
    }

    function actualizarPerfilVisual(usuario) {
        const nombreCompleto =
            `${usuario.nombre || ''} ${usuario.apellido || ''}`
                .trim();

        const nombreVisual =
            document.getElementById(
                'perfilNombreVisual'
            );

        const correoVisual =
            document.getElementById(
                'perfilCorreoVisual'
            );

        const telefonoVisual =
            document.getElementById(
                'perfilTelefonoVisual'
            );

        if (nombreVisual) {
            nombreVisual.textContent =
                nombreCompleto;
        }

        if (correoVisual) {
            correoVisual.textContent =
                usuario.correo || '';
        }

        if (telefonoVisual) {
            telefonoVisual.textContent =
                usuario.telefono ||
                'Sin teléfono registrado';
        }
    }


    function cambiarEstadoBoton(
        button,
        cargando,
        texto
    ) {
        if (!button) {
            return;
        }

        button.disabled = cargando;
        button.textContent = texto;
    }

    function mostrarToast(
        mensaje,
        tipo = 'success'
    ) {
        const container =
            document.getElementById(
                'cuentaToastContainer'
            );

        if (!container) {
            return;
        }

        const toast =
            document.createElement('div');

        toast.className =
            `cuenta-toast ${tipo}`;

        toast.textContent = mensaje;

        container.appendChild(toast);

        setTimeout(function () {
            toast.style.opacity = '0';
            toast.style.transform =
                'translateX(18px)';

            setTimeout(function () {
                toast.remove();
            }, 250);

        }, 3300);
    }

    function escaparHtml(valor) {
        const div =
            document.createElement('div');

        div.textContent =
            String(valor ?? '');

        return div.innerHTML;
    }

    window.mostrarToastCuenta =
        mostrarToast;

    const searchInput = document.getElementById('busquedaPedidos');
    const filterSelect = document.getElementById('filtroEstadoPedidos');
    const dateInput = document.getElementById('filtroFechaPedidos');
    const orderRows = document.querySelectorAll('.pedido-row');

    function filterOrders() {
        if (!searchInput || !filterSelect || !dateInput) return;
        
        const searchTerm = searchInput.value.toLowerCase();
        const selectedStatus = filterSelect.value.toLowerCase();
        const selectedDate = dateInput.value; 

        orderRows.forEach(row => {
            const orderId = row.querySelector('.pedido-id').textContent.toLowerCase();
            const orderStatus = row.getAttribute('data-estado').toLowerCase();
            const rawDate = row.querySelector('.pedido-fecha-raw').getAttribute('data-raw-date');

            const matchesSearch = orderId.includes(searchTerm);
            const matchesStatus = selectedStatus === 'todos' || orderStatus === selectedStatus;
            const matchesDate = !selectedDate || rawDate === selectedDate;

            if (matchesSearch && matchesStatus && matchesDate) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    if (searchInput) {
        searchInput.addEventListener('input', filterOrders);
    }
    
    if (filterSelect) {
        filterSelect.addEventListener('change', filterOrders);
    }

    if (dateInput) {
        dateInput.addEventListener('change', filterOrders);
    }

});