'use strict';

document.addEventListener('DOMContentLoaded', function () {

    /* ── References ── */
    const direccionIdInput   = document.getElementById('checkoutDireccionId');
    const tipoEntregaInput   = document.getElementById('checkoutTipoEntrega');
    const metodoIdInput      = document.getElementById('checkoutMetodoId');
    const subtotalElement    = document.getElementById('checkoutSubtotal');
    const deliveryCostEl     = document.getElementById('checkoutDeliveryCost');
    const totalEl            = document.getElementById('checkoutTotal');
    const observacionInput   = document.getElementById('checkoutObservacion');
    const observacionCount   = document.getElementById('observacionCount');
    const formNuevaDireccion = document.getElementById('formNuevaDireccionCheckout');
    const btnGuardarDir      = document.getElementById('btnGuardarDireccionCheckout');

    let costoDeliveryActual = 5.00; // Se actualiza al seleccionar distrito

    const subtotalBase = obtenerNumeroMoneda(
        subtotalElement ? subtotalElement.textContent : '0'
    );

    /* ════════════════════════════════════
       MODALS
    ════════════════════════════════════ */
    function abrirModal(id) {
        const modal = document.getElementById(id);
        if (!modal) return;
        modal.classList.add('active');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('co-modal-open');
    }

    function cerrarModal(id) {
        const modal = document.getElementById(id);
        if (!modal) return;
        modal.classList.remove('active');
        modal.setAttribute('aria-hidden', 'true');
        if (!document.querySelector('.co-modal-overlay.active')) {
            document.body.classList.remove('co-modal-open');
        }
    }

    /* Close via [data-close-modal] buttons */
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('[data-close-modal]');
        if (btn) { cerrarModal(btn.dataset.closeModal); return; }

        /* Click outside modal */
        if (e.target.classList.contains('co-modal-overlay')) {
            cerrarModal(e.target.id);
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        document.querySelectorAll('.co-modal-overlay.active').forEach(function (m) {
            cerrarModal(m.id);
        });
    });

    /* Cambiar / agregar dirección */
    const btnCambiar = document.getElementById('btnCambiarDireccion');
    if (btnCambiar) btnCambiar.addEventListener('click', function () {
        abrirModal('modalDireccionesCheckout');
    });

    const btnAgregarPrimera = document.getElementById('btnAgregarPrimeraDireccion');
    if (btnAgregarPrimera) btnAgregarPrimera.addEventListener('click', function () {
        prepararFormNuevaDireccion();
        abrirModal('modalNuevaDireccionCheckout');
    });

    const btnNuevaDireccion = document.getElementById('btnAbrirNuevaDireccion');
    if (btnNuevaDireccion) btnNuevaDireccion.addEventListener('click', function () {
        cerrarModal('modalDireccionesCheckout');
        prepararFormNuevaDireccion();
        abrirModal('modalNuevaDireccionCheckout');
    });

    /* ════════════════════════════════════
       ADDRESS SELECTION (modal list)
    ════════════════════════════════════ */
    document.addEventListener('click', function (e) {
        const opt = e.target.closest('.co-address-opt');
        if (!opt) return;

        document.querySelectorAll('.co-address-opt').forEach(function (item) {
            item.classList.remove('active');
            const radio = item.querySelector('input[type="radio"]');
            if (radio) radio.checked = false;
        });

        opt.classList.add('active');
        const radio = opt.querySelector('input[type="radio"]');
        if (radio) radio.checked = true;

        const id       = opt.dataset.idDireccion || '';
        const calle    = opt.dataset.calle    || '';
        const numero   = opt.dataset.numero   || '';
        const lote     = opt.dataset.lote     || '';
        const distrito = opt.dataset.distrito || '';
        const referencia = opt.dataset.referencia || '';
        const costo    = parseFloat(opt.dataset.costo || '5') || 5;

        if (direccionIdInput) direccionIdInput.value = id;

        const parts = [calle];
        if (numero) parts.push('#' + numero);
        if (lote)   parts.push('Lote ' + lote);
        if (distrito) parts.push(distrito);
        const full = parts.filter(Boolean).join(', ');

        actualizarDireccionVisual(full, referencia, costo, calle, numero, lote, distrito);

        cerrarModal('modalDireccionesCheckout');
        mostrarToast('Dirección seleccionada correctamente.', 'success');
    });

    function actualizarDireccionVisual(full, referencia, costo, calle, numero, lote, distrito) {
        const addrText = document.getElementById('checkoutAddressText');
        const addrRef  = document.getElementById('checkoutAddressReference');
        const addrCard = document.getElementById('checkoutSelectedAddress');
        const noAddr   = document.getElementById('checkoutNoAddress');
        const addrReq  = document.getElementById('checkoutAddressRequirement');

        if (addrText) addrText.textContent = full;
        if (addrRef) addrRef.textContent = referencia ? 'Ref: ' + referencia : '';

        if (addrCard) addrCard.style.display = '';
        if (noAddr) noAddr.style.display = 'none';
        if (addrReq) addrReq.setAttribute('hidden', '');

        // Rellenar campos del formulario en pantalla (estilo Imagen 2 y 3)
        const calleIn = document.getElementById('checkoutCalle');
        const numIn   = document.getElementById('checkoutNumero');
        const loteIn  = document.getElementById('checkoutLote');
        const distIn  = document.getElementById('checkoutDistrito');
        const refIn   = document.getElementById('checkoutReferencia');

        if (calleIn && calle !== undefined) calleIn.value = calle;
        if (numIn && numero !== undefined) numIn.value = numero;
        if (loteIn && lote !== undefined) loteIn.value = lote;
        if (refIn && referencia !== undefined) refIn.value = referencia;

        if (distIn && distrito) {
            for (let i = 0; i < distIn.options.length; i++) {
                if (distIn.options[i].value.toLowerCase() === distrito.toLowerCase()) {
                    distIn.selectedIndex = i;
                    distIn.dispatchEvent(new Event('change'));
                    break;
                }
            }
        }

        // Update delivery cost if provided
        if (typeof costo === 'number') {
            actualizarCostoDelivery(costo);
        }
    }

    // Escuchar cambios en el selector de distrito para actualizar el costo dinámicamente
    const distSelectEl = document.getElementById('checkoutDistrito');
    if (distSelectEl) {
        distSelectEl.addEventListener('change', function () {
            const opt = this.options[this.selectedIndex];
            if (!opt) return;
            const costo = parseFloat(opt.dataset.costo || '5') || 5;
            actualizarCostoDelivery(costo);
        });
    }

    function actualizarCostoDelivery(costo) {
        costoDeliveryActual = costo;
        actualizarTotales();
        const displayEl = document.getElementById('costoDeliveryDisplay');
        if (displayEl) displayEl.textContent = 'S/ ' + costo.toFixed(2);
    }

    /* ════════════════════════════════════
       DELIVERY OPTION SELECTION
    ════════════════════════════════════ */
    document.addEventListener('click', function (e) {
        const opt = e.target.closest('[data-delivery-option]');
        if (!opt) return;

        document.querySelectorAll('[data-delivery-option]').forEach(function (item) {
            item.classList.remove('active');
            const r = item.querySelector('input[type="radio"]');
            if (r) r.checked = false;
        });

        opt.classList.add('active');
        const r = opt.querySelector('input[type="radio"]');
        if (r) r.checked = true;

        if (tipoEntregaInput) tipoEntregaInput.value = opt.dataset.deliveryOption;

        const secDir = document.getElementById('seccionDireccion');
        const addrReq = document.getElementById('checkoutAddressRequirement');

        if (opt.dataset.deliveryOption === 'recojo') {
            if (secDir) secDir.style.display = 'none';
        } else {
            if (secDir) secDir.style.display = '';
        }

        actualizarTotales();
    });

    /* ════════════════════════════════════
       PAYMENT OPTION SELECTION
    ════════════════════════════════════ */
    document.addEventListener('click', function (e) {
        const opt = e.target.closest('[data-payment-option]');
        if (!opt) return;

        document.querySelectorAll('[data-payment-option]').forEach(function (item) {
            item.classList.remove('active');
            const r = item.querySelector('input[type="radio"]');
            if (r) r.checked = false;
        });

        opt.classList.add('active');
        const r = opt.querySelector('input[type="radio"]');
        if (r) r.checked = true;

        if (metodoIdInput) metodoIdInput.value = opt.dataset.paymentOption;

        actualizarPanelVoucher(opt);
    });

    function actualizarPanelVoucher(option) {
        document.querySelectorAll('.co-dynamic-panel').forEach(panel => {
            panel.style.display = 'none';
        });

        if (!option) return;

        const panel = option.nextElementSibling;
        if (panel && panel.classList.contains('co-dynamic-panel')) {
            panel.style.display = 'block';
        }

        const nombre = (option.dataset.nombreMetodo) ? option.dataset.nombreMetodo.toLowerCase() : '';
        if (nombre.includes('efectivo') || nombre.includes('recibir')) {
            const montoEfectivo = document.getElementById('montoEfectivoTexto');
            if (montoEfectivo && totalEl) montoEfectivo.textContent = totalEl.textContent;
        } else if (nombre.includes('yape') || nombre.includes('plin')) {
            const numeroBilletera = document.getElementById('numeroBilletera');
            if (numeroBilletera && option.dataset.cuenta) {
                numeroBilletera.textContent = option.dataset.cuenta;
            }
        }
    }

    // Tabs Yape/Plin
    document.addEventListener('click', function(e) {
        const tab = e.target.closest('.co-yp-tab');
        if (!tab) return;
        
        const parent = tab.closest('.co-yape-plin-tabs');
        if (parent) {
            parent.querySelectorAll('.co-yp-tab').forEach(t => {
                t.classList.remove('active');
                t.style.background = 'transparent';
                t.style.borderColor = '#e2e8f0';
            });
            tab.classList.add('active');
            tab.style.background = '#fff0f0';
            tab.style.borderColor = '#d32f2f';
            
            const target = tab.dataset.target;
            const lbl = document.getElementById('lblCelularYP');
            const hidden = document.getElementById('billetera_seleccionada');
            if (lbl) lbl.textContent = target === 'yape' ? 'Ingresa tu celular Yape' : 'Ingresa tu celular Plin';
            if (hidden) hidden.value = target === 'yape' ? 'Yape' : 'Plin';
        }
    });

    /* ════════════════════════════════════
       TOTALS
    ════════════════════════════════════ */
    function actualizarTotales() {
        const tipo      = tipoEntregaInput ? tipoEntregaInput.value : 'delivery';
        const envio     = tipo === 'delivery' ? costoDeliveryActual : 0;
        const total     = subtotalBase + envio;

        const fmtEnvio  = formatearMoneda(envio);
        const fmtTotal  = formatearMoneda(total);

        /* Update all delivery cost / total elements across panels */
        ['', '2', '3', '4'].forEach(function (suffix) {
            const dc = document.getElementById('checkoutDeliveryCost' + suffix);
            const t  = document.getElementById('checkoutTotal' + suffix);
            if (dc) dc.textContent = fmtEnvio;
            if (t)  t.textContent  = fmtTotal;
        });

        const montoEfectivo = document.getElementById('montoEfectivoTexto');
        if (montoEfectivo) montoEfectivo.textContent = fmtTotal;
    }

    function formatearMoneda(valor) {
        return 'S/ ' + Number(valor).toFixed(2);
    }

    function obtenerNumeroMoneda(texto) {
        return parseFloat(
            String(texto).replace('S/', '').replace(',', '').trim()
        ) || 0;
    }

    /* ════════════════════════════════════
       OBSERVACION COUNTER
    ════════════════════════════════════ */
    if (observacionInput && observacionCount) {
        observacionInput.addEventListener('input', function () {
            observacionCount.textContent = observacionInput.value.length;
        });
    }

    /* ════════════════════════════════════
       NUEVA DIRECCIÓN — FORM SUBMIT
    ════════════════════════════════════ */
    function prepararFormNuevaDireccion() {
        if (!formNuevaDireccion) return;
        formNuevaDireccion.reset();
        formNuevaDireccion.querySelectorAll('[data-error-for]').forEach(function (el) {
            el.textContent = '';
        });
    }

    if (formNuevaDireccion && btnGuardarDir) {
        formNuevaDireccion.addEventListener('submit', function (e) {
            e.preventDefault();

            const fields = ['departamento', 'provincia', 'distrito', 'calle'];
            let valid = true;

            fields.forEach(function (field) {
                const input = formNuevaDireccion.querySelector('[name="' + field + '"]');
                const errorEl = formNuevaDireccion.querySelector('[data-error-for="' + field + '"]');
                if (input && !input.value.trim()) {
                    valid = false;
                    if (errorEl) errorEl.textContent = 'Este campo es requerido.';
                } else {
                    if (errorEl) errorEl.textContent = '';
                }
            });

            if (!valid) return;

            btnGuardarDir.disabled = true;
            btnGuardarDir.textContent = 'Guardando...';

            const formData = new FormData(formNuevaDireccion);

            fetch(formNuevaDireccion.action, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.success) {
                    const idNueva = data.id_direccion || data.data?.id_direccion;

                    const calle  = formNuevaDireccion.querySelector('[name="calle"]').value.trim();
                    const dist   = formNuevaDireccion.querySelector('[name="distrito"]').value.trim();
                    const prov   = formNuevaDireccion.querySelector('[name="provincia"]').value.trim();
                    const depto  = formNuevaDireccion.querySelector('[name="departamento"]').value.trim();
                    const ref    = formNuevaDireccion.querySelector('[name="referencia"]').value.trim();

                    if (idNueva && direccionIdInput) direccionIdInput.value = idNueva;

                    const full = [calle, dist, prov, depto].filter(Boolean).join(', ');
                    actualizarDireccionVisual(full, ref);

                    cerrarModal('modalNuevaDireccionCheckout');
                    mostrarToast('Dirección guardada correctamente.', 'success');

                    formNuevaDireccion.reset();
                } else {
                    mostrarToast(data.message || 'Error al guardar la dirección.', 'error');
                }
            })
            .catch(function () {
                mostrarToast('Error de conexión. Intenta de nuevo.', 'error');
            })
            .finally(function () {
                btnGuardarDir.disabled = false;
                btnGuardarDir.textContent = 'Guardar dirección';
            });
        });
    }

    /* ════════════════════════════════════
       FORM SUBMIT
    ════════════════════════════════════ */
    const formCheckout = document.getElementById('formCheckout');
    const btnConfirmarPedidoFinal = document.getElementById('btnConfirmarPedidoFinal');
    const totalConfirmacionPedido = document.getElementById('checkoutModalConfirmTotal');
    let pedidoConfirmado = false;
    // Credit Card Visual Updates
    const ccNum = document.getElementById('cc_numero');
    const ccName = document.getElementById('cc_nombre');
    const ccExp = document.getElementById('cc_vencimiento');
    const ccVisNum = document.getElementById('ccNumVisual');
    const ccVisName = document.getElementById('ccNameVisual');
    const ccVisExp = document.getElementById('ccExpVisual');
    const ccCvv = document.getElementById('cc_cvv');
    const ccCvvVis = document.getElementById('ccCvvVisual');
    const ccFlipper = document.getElementById('ccFlipper');

    if (ccNum) ccNum.addEventListener('input', e => {
        let val = e.target.value.replace(/\D/g, '');
        let formatted = val.match(/.{1,4}/g)?.join(' ') || '';
        e.target.value = formatted;
        if (ccVisNum) ccVisNum.textContent = formatted || '•••• •••• •••• ••••';
    });
    if (ccName) ccName.addEventListener('input', e => {
        if (ccVisName) ccVisName.textContent = e.target.value.toUpperCase() || 'NOMBRE APELLIDO';
    });
    if (ccExp) ccExp.addEventListener('input', e => {
        let val = e.target.value.replace(/\D/g, '');
        if (val.length > 2) val = val.substring(0,2) + '/' + val.substring(2,4);
        e.target.value = val;
        if (ccVisExp) ccVisExp.textContent = val || 'MM/AA';
    });
    if (ccCvv && ccFlipper) {
        ccCvv.addEventListener('focus', () => ccFlipper.style.transform = 'rotateY(180deg)');
        ccCvv.addEventListener('blur', () => ccFlipper.style.transform = 'rotateY(0deg)');
        ccCvv.addEventListener('input', e => {
            if (ccCvvVis) ccCvvVis.textContent = e.target.value || '•••';
        });
    }

    // Validar monto efectivo en tiempo real
    const montoEfectivo = document.getElementById('monto_efectivo');
    const efectivoError = document.getElementById('efectivoError');
    if (montoEfectivo) {
        montoEfectivo.addEventListener('input', e => {
            const val = parseFloat(e.target.value || 0);
            const total = subtotalBase + (tipoEntregaInput && tipoEntregaInput.value === 'delivery' ? costoDeliveryActual : 0);
            if (val > 0 && val < total) {
                efectivoError.style.display = 'block';
            } else {
                efectivoError.style.display = 'none';
            }
        });
    }

    // Phone / Code Formatting for Yape/Plin
    const celYP = document.getElementById('celular_yape_plin');
    const codYP = document.getElementById('codigo_yape_plin');
    if (celYP) celYP.addEventListener('input', e => {
        let val = e.target.value.replace(/\D/g, '');
        let formatted = val.match(/.{1,3}/g)?.join(' ') || '';
        e.target.value = formatted;
    });
    if (codYP) codYP.addEventListener('input', e => {
        let val = e.target.value.replace(/\D/g, '');
        let formatted = val.match(/.{1,3}/g)?.join(' ') || '';
        e.target.value = formatted;
    });

    if (formCheckout) {
        formCheckout.addEventListener('submit', function (e) {
            const metodoOption = document.querySelector('[data-payment-option].active');
            const nombreMetodo = (metodoOption && metodoOption.dataset.nombreMetodo) ? metodoOption.dataset.nombreMetodo.toLowerCase() : '';
            
            // Validación Efectivo
            if (nombreMetodo.includes('efectivo') || nombreMetodo.includes('recibir')) {
                const total = subtotalBase + (tipoEntregaInput && tipoEntregaInput.value === 'delivery' ? costoDeliveryActual : 0);
                const val = parseFloat(montoEfectivo ? montoEfectivo.value : 0);
                if (isNaN(val) || val < total) {
                    e.preventDefault();
                    mostrarToast('El monto ingresado es menor al total a pagar.', 'error');
                    if (efectivoError) efectivoError.style.display = 'block';
                    return;
                }
            }
            
            // Validación Yape / Plin
            if (nombreMetodo.includes('yape') || nombreMetodo.includes('plin')) {
                const celularLimpio = (celYP ? celYP.value.replace(/\D/g, '') : '');
                if (!celYP || celularLimpio.length < 9 || !celularLimpio.startsWith('9')) {
                    e.preventDefault();
                    mostrarToast('Ingresa un número de celular válido (9 dígitos). Debe empezar con 9.', 'error');
                    return;
                }
                if (!codYP || codYP.value.replace(/\D/g, '').length < 6) {
                    e.preventDefault();
                    mostrarToast('Ingresa el código de aprobación (mín. 6 dígitos).', 'error');
                    return;
                }
                const voucher = document.getElementById('captura_voucher');
                if (!voucher || !voucher.files || voucher.files.length === 0) {
                    e.preventDefault();
                    mostrarToast('Por favor sube la captura de pago.', 'error');
                    return;
                }
            }

            // Validación Tarjeta
            if (nombreMetodo.includes('tarjeta') || nombreMetodo.includes('débito') || nombreMetodo.includes('crédito')) {
                if (!ccNum || ccNum.value.replace(/\D/g, '').length < 14) {
                    e.preventDefault();
                    mostrarToast('Ingresa un número de tarjeta válido.', 'error');
                    return;
                }
                if (!ccExp || ccExp.value.replace(/\D/g, '').length < 4) {
                    e.preventDefault();
                    mostrarToast('Ingresa una fecha de vencimiento válida (MM/AA).', 'error');
                    return;
                }
                if (!ccCvv || ccCvv.value.replace(/\D/g, '').length < 3) {
                    e.preventDefault();
                    mostrarToast('Ingresa un CVV válido (3 dígitos).', 'error');
                    return;
                }
                if (!ccName || ccName.value.trim().length < 3) {
                    e.preventDefault();
                    mostrarToast('Ingresa el nombre del titular.', 'error');
                    return;
                }
            }

            if (!pedidoConfirmado) {
                e.preventDefault();
                const totalActual = document.getElementById('checkoutTotal3');
                if (totalConfirmacionPedido && totalActual) {
                    totalConfirmacionPedido.textContent = totalActual.textContent;
                }
                abrirModal('modalConfirmarPedido');
                return;
            }

            const submits = formCheckout.querySelectorAll('[type="submit"]');
            submits.forEach(function (btn) {
                const def = btn.querySelector('.co-submit-default');
                const loading = btn.querySelector('.co-submit-loading');
                if (def) def.hidden = true;
                if (loading) loading.hidden = false;
                btn.disabled = true;
            });
        });
    }

    if (btnConfirmarPedidoFinal && formCheckout) {
        btnConfirmarPedidoFinal.addEventListener('click', function() {
            pedidoConfirmado = true;
            cerrarModal('modalConfirmarPedido');
            const submitter = document.getElementById('btnHacerPedidoSidebar');
            if (typeof formCheckout.requestSubmit === 'function') {
                submitter && !submitter.disabled
                    ? formCheckout.requestSubmit(submitter)
                    : formCheckout.requestSubmit();
            } else {
                formCheckout.submit();
            }
        });
    }

    /* ════════════════════════════════════
       TOAST
    ════════════════════════════════════ */
    function mostrarToast(mensaje, tipo) {
        const container = document.getElementById('checkoutToastContainer');
        if (!container) return;

        const toast = document.createElement('div');
        toast.style.cssText = [
            'padding: 12px 18px',
            'border-radius: 10px',
            'font-size: 0.84rem',
            'font-weight: 600',
            'color: #fff',
            'box-shadow: 0 8px 24px rgba(15,23,42,.18)',
            'pointer-events: all',
            'animation: fadeInUp .3s ease',
            'background: ' + (tipo === 'success' ? '#15803D' : tipo === 'error' ? '#c62828' : '#334155')
        ].join(';');
        toast.textContent = mensaje;
        container.appendChild(toast);

        setTimeout(function () {
            toast.style.opacity = '0';
            toast.style.transition = 'opacity .4s';
            setTimeout(function () { toast.remove(); }, 400);
        }, 3200);
    }

    /* ════════════════════════════════════
       INIT
    ════════════════════════════════════ */
    // Init totals
    actualizarTotales();

    // Init voucher panel for initially selected payment
    const initPayment = document.querySelector('[data-payment-option].active');
    if (initPayment) actualizarPanelVoucher(initPayment);

});
