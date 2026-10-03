/**
 * users-admin.js — Gestión de Usuarios y Cajeros POS (Pure Vanilla JS)
 */

var ultimoUsuarioDetalles = null;
var formDesactivacionActual = null;

// Abrir modal para crear Administrador o Cajero POS
window.abrirModalUsuario = function(tipo = 'admin') {
    const modal = document.getElementById('modalUsuario');
    const form = document.getElementById('formUsuario');
    const btnBack = document.getElementById('btnBackModalUsuario');
    const modalTitle = document.getElementById('modalUsuarioTitle');
    const btnSubmit = document.getElementById('btnSubmitUsuario');
    const formRol = document.getElementById('formRol');
    const rolHelper = document.getElementById('rolHelper');
    const estadoGroup = document.getElementById('estadoGroup');
    const passwordSection = document.getElementById('passwordSection');
    const formPassword = document.getElementById('formPassword');
    const formConfirm = document.getElementById('formConfirmPassword');

    if (!modal || !form) return;

    if (btnBack) btnBack.style.display = 'none';
    modal.classList.add('active');
    form.reset();

    const idInput = document.getElementById('formIdUsuario');
    if (idInput) idInput.value = '';

    ['formNombre', 'formApellido', 'formCorreo', 'formTelefono'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.readOnly = false;
    });

    if (tipo === 'cajero') {
        if (modalTitle) modalTitle.textContent = 'Crear Cajero POS';
        if (btnSubmit) btnSubmit.textContent = 'Guardar Cajero';
        form.setAttribute('action', 'index.php?action=crear_cajero');
        if (formRol) {
            formRol.value = 'cajero';
            formRol.style.pointerEvents = 'none';
        }
        if (rolHelper) {
            rolHelper.textContent = 'Registrarás una cuenta de Cajero para el Punto de Venta (POS).';
            rolHelper.style.display = 'block';
        }
    } else if (tipo === 'repartidor') {
        if (modalTitle) modalTitle.textContent = 'Crear Repartidor Delivery';
        if (btnSubmit) btnSubmit.textContent = 'Guardar Repartidor';
        form.setAttribute('action', 'index.php?action=crear_repartidor');
        if (formRol) {
            formRol.value = 'repartidor';
            formRol.style.pointerEvents = 'none';
        }
        if (rolHelper) {
            rolHelper.textContent = 'Registrarás un repartidor para despachos y entregas de pedidos.';
            rolHelper.style.display = 'block';
        }
    } else if (tipo === 'nuevo') {
        if (modalTitle) modalTitle.textContent = 'Crear Usuario';
        if (btnSubmit) btnSubmit.textContent = 'Guardar Usuario';
        
        if (formRol) {
            formRol.value = 'admin';
            formRol.style.pointerEvents = 'auto';
            form.setAttribute('action', 'index.php?action=crear_' + (formRol.value === 'admin' ? 'administrador' : formRol.value));
            
            formRol.onchange = function() {
                let action = formRol.value;
                if (action === 'admin') action = 'administrador';
                form.setAttribute('action', 'index.php?action=crear_' + action);
                if (rolHelper) {
                    if (action === 'administrador') rolHelper.textContent = 'Crearás un Administrador con acceso total.';
                    else if (action === 'cajero') rolHelper.textContent = 'Crearás un Cajero para el POS.';
                    else if (action === 'repartidor') rolHelper.textContent = 'Crearás un Repartidor para delivery.';
                    else rolHelper.textContent = '';
                }
            };
        }
        if (rolHelper) {
            rolHelper.textContent = 'Crearás un Administrador con acceso total.';
            rolHelper.style.display = 'block';
        }
    } else {
        if (modalTitle) modalTitle.textContent = 'Crear Administrador';
        if (btnSubmit) btnSubmit.textContent = 'Guardar Administrador';
        form.setAttribute('action', 'index.php?action=crear_administrador');
        if (formRol) {
            formRol.value = 'admin';
            formRol.style.pointerEvents = 'none';
        }
        if (rolHelper) {
            rolHelper.textContent = 'Por seguridad, crearás un Administrador.';
            rolHelper.style.display = 'block';
        }
    }

    if (estadoGroup) estadoGroup.style.display = 'none';
    if (passwordSection) passwordSection.style.display = 'block';
    if (formPassword) formPassword.required = true;
    if (formConfirm) formConfirm.required = true;

    document.body.style.overflow = 'hidden';
};

// Cerrar modal de usuario
window.cerrarModalUsuario = function() {
    const modal = document.getElementById('modalUsuario');
    if (modal) modal.classList.remove('active');
    if (!document.querySelector('.modal-overlay.active')) {
        document.body.style.overflow = '';
    }
};

// Abrir modal en modo edición
window.editarUsuario = function(usuario) {
    if (!usuario) return;

    const modal = document.getElementById('modalUsuario');
    const form = document.getElementById('formUsuario');
    const btnBack = document.getElementById('btnBackModalUsuario');
    const modalTitle = document.getElementById('modalUsuarioTitle');
    const btnSubmit = document.getElementById('btnSubmitUsuario');
    const formRol = document.getElementById('formRol');
    const rolHelper = document.getElementById('rolHelper');
    const estadoGroup = document.getElementById('estadoGroup');
    const formActivo = document.getElementById('formActivo');
    const passwordSection = document.getElementById('passwordSection');
    const formPassword = document.getElementById('formPassword');
    const formConfirm = document.getElementById('formConfirmPassword');

    if (!modal || !form) return;

    if (btnBack) btnBack.style.display = 'none';
    modal.classList.add('active');
    form.reset();

    if (modalTitle) modalTitle.textContent = 'Editar Usuario';
    if (btnSubmit) btnSubmit.textContent = 'Actualizar Usuario';
    form.setAttribute('action', 'index.php?action=actualizar_usuario');

    const idInput = document.getElementById('formIdUsuario');
    if (idInput) idInput.value = usuario.id_usuario;

    const setField = (id, val, readonly = true) => {
        const el = document.getElementById(id);
        if (el) {
            el.value = val || '';
            el.readOnly = readonly;
        }
    };

    setField('formNombre', usuario.nombre, true);
    setField('formApellido', usuario.apellido, true);
    setField('formCorreo', usuario.correo, true);
    setField('formTelefono', usuario.telefono, true);

    if (formRol) {
        formRol.value = usuario.rol;
        formRol.style.pointerEvents = 'auto';
    }
    if (rolHelper) rolHelper.style.display = 'none';

    if (estadoGroup) estadoGroup.style.display = 'block';
    if (formActivo) formActivo.value = usuario.activo;

    if (passwordSection) passwordSection.style.display = 'none';
    if (formPassword) formPassword.required = false;
    if (formConfirm) formConfirm.required = false;

    document.body.style.overflow = 'hidden';
};

// Abrir edición desde la ficha de detalles
window.abrirEditarDesdeDetalles = function(u) {
    cerrarModalDetalles();
    editarUsuario(u);
    const btnBack = document.getElementById('btnBackModalUsuario');
    if (btnBack) btnBack.style.display = 'inline-flex';
};

// Regresar a la ficha de detalles desde edición
window.regresarAModalDetalles = function() {
    cerrarModalUsuario();
    if (ultimoUsuarioDetalles) {
        verDetallesUsuario(ultimoUsuarioDetalles.u, ultimoUsuarioDetalles.currentUserId);
    }
};

// Modal de confirmación para desactivación
window.abrirModalDesactivacion = function(formId) {
    formDesactivacionActual = document.getElementById(formId);
    const modal = document.getElementById('modalConfirmarDesactivacion');
    if (modal) modal.classList.add('active');
    document.body.style.overflow = 'hidden';
};

window.cerrarModalDesactivacion = function() {
    const modal = document.getElementById('modalConfirmarDesactivacion');
    if (modal) modal.classList.remove('active');
    if (formDesactivacionActual) {
        formDesactivacionActual.dataset.confirmed = 'false';
    }
    formDesactivacionActual = null;
    if (!document.querySelector('.modal-overlay.active')) {
        document.body.style.overflow = '';
    }
};

window.ejecutarDesactivacion = function() {
    if (formDesactivacionActual) {
        formDesactivacionActual.dataset.confirmed = 'true';
        if (typeof formDesactivacionActual.requestSubmit === 'function') {
            formDesactivacionActual.requestSubmit();
        } else {
            formDesactivacionActual.submit();
        }
    }
};

// Ficha de detalles de usuario
window.verDetallesUsuario = function(u, currentUserId) {
    ultimoUsuarioDetalles = { u: u, currentUserId: currentUserId };
    const avatarChar = u.nombre ? u.nombre.charAt(0).toUpperCase() : 'U';

    let rolText = 'Cliente';
    let rolClass = 'status-tag-user';
    const rolLower = (u.rol || '').toLowerCase();
    if (rolLower === 'admin') {
        rolText = 'Administrador';
        rolClass = 'status-tag-admin';
    } else if (rolLower === 'cajero') {
        rolText = 'Cajero POS';
        rolClass = 'status-tag-teal';
    } else if (rolLower === 'repartidor') {
        rolText = 'Repartidor Delivery';
        rolClass = 'status-tag-delivery';
    }

    const estadoText = (parseInt(u.activo) === 1) ? 'Activo' : 'Desactivado';
    const estadoClass = (parseInt(u.activo) === 1) ? 'status-tag-success' : 'status-tag-error';
    const isSelf = (parseInt(currentUserId) === parseInt(u.id_usuario));
    const uJson = JSON.stringify(u).replace(/'/g, "&apos;");

    let html = `
        <div class="user-detail-card-header">
            <div class="user-detail-avatar">${avatarChar}</div>
            <div class="user-detail-info">
                <h4>${u.nombre} ${u.apellido || ''}</h4>
                <span class="user-detail-id">ID de usuario: #${u.id_usuario}</span>
            </div>
        </div>
        
        <div class="user-detail-badges">
            <span class="status-tag ${rolClass}">${rolText}</span>
            <span class="status-tag ${estadoClass}">${estadoText}</span>
        </div>

        <div class="user-detail-group">
            <div class="user-detail-row">
                <span class="detail-label">✉️ Correo:</span>
                <span class="detail-value">${u.correo}</span>
            </div>
            <div class="user-detail-row">
                <span class="detail-label">📞 Teléfono:</span>
                <span class="detail-value">${u.telefono || 'Sin registrar'}</span>
            </div>
            <div class="user-detail-row">
                <span class="detail-label">🗓️ Registro:</span>
                <span class="detail-value">${u.fecha_registro ? u.fecha_registro : '-'}</span>
            </div>
        </div>
    `;

    if (!isSelf) {
        html += `<div class="user-detail-actions">`;
        html += `
            <button type="button" class="btn btn-edit-modal btn-full" onclick='abrirEditarDesdeDetalles(${uJson});'>
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                Editar Usuario
            </button>
        `;
        if (parseInt(u.activo) === 1) {
            html += `
                <button type="button" class="btn btn-danger-modal btn-full" onclick='cerrarModalDetalles(); abrirModalDesactivacion("formDesactivar_${u.id_usuario}");'>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                    Desactivar Cuenta
                </button>
            `;
        } else {
            html += `
                <button type="button" class="btn btn-success-modal btn-full" onclick='cerrarModalDetalles(); document.getElementById("formReactivar_${u.id_usuario}").submit();'>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    Reactivar Cuenta
                </button>
            `;
        }
        html += `</div>`;
    }

    const bodyEl = document.getElementById('detallesUsuarioBody');
    if (bodyEl) bodyEl.innerHTML = html;

    const modal = document.getElementById('modalDetallesUsuario');
    if (modal) modal.classList.add('active');
    document.body.style.overflow = 'hidden';
};

window.cerrarModalDetalles = function() {
    const modal = document.getElementById('modalDetallesUsuario');
    if (modal) modal.classList.remove('active');
    if (!document.querySelector('.modal-overlay.active')) {
        document.body.style.overflow = '';
    }
};

// Filtrado de usuarios en tiempo real
function filtrarUsuarios() {
    const searchInput = document.getElementById('searchUser');
    if (!searchInput) return;
    const value = searchInput.value.toLowerCase().trim();
    const rows = document.querySelectorAll('#usersTable tbody tr');

    rows.forEach(row => {
        if (row.children.length === 1) return;
        const text = row.textContent.toLowerCase();
        if (value === '' || text.indexOf(value) !== -1) {
            row.classList.remove('search-hidden');
            row.style.display = '';
        } else {
            row.classList.add('search-hidden');
            row.style.display = 'none';
        }
    });
}

// Inicialización de eventos nativos
document.addEventListener('input', function(e) {
    if (e.target && e.target.id === 'searchUser') {
        filtrarUsuarios();
    }
});

// Cerrar modales al hacer clic fuera del contenido
document.addEventListener('click', function(e) {
    if (e.target && e.target.classList.contains('modal-overlay')) {
        e.target.classList.remove('active');
        if (!document.querySelector('.modal-overlay.active')) {
            document.body.style.overflow = '';
        }
    }
});

// Envío AJAX del formulario de usuarios (Crear/Editar)
document.addEventListener('submit', function(e) {
    if (e.target && e.target.id === 'formUsuario') {
        e.preventDefault();
        const form = e.target;
        const url = form.getAttribute('action') || 'index.php?action=crear_administrador';
        const formData = new FormData(form);
        const btnSubmit = document.getElementById('btnSubmitUsuario');
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
                cerrarModalUsuario();
                if (typeof SPA !== 'undefined' && typeof SPA.toast === 'function') {
                    SPA.toast('success', 'Éxito', response.message);
                    SPA.loadModule('index.php?action=usuarios_admin', false);
                } else {
                    window.location.reload();
                }
            } else {
                const msg = response.message || 'Ocurrió un error.';
                if (typeof SPA !== 'undefined' && typeof SPA.toast === 'function') {
                    SPA.toast('error', 'Error', msg);
                } else {
                    alert(msg);
                }
            }
        })
        .catch(err => {
            console.error('Error al guardar usuario:', err);
            // Fallback: submit standard
            form.submit();
        })
        .finally(() => {
            if (btnSubmit) {
                btnSubmit.disabled = false;
                btnSubmit.textContent = btnText;
            }
        });
    }

    // Formularios de desactivación / reactivación
    if (e.target && e.target.classList.contains('form-estado-usuario')) {
        const form = e.target;
        if (form.id && form.id.startsWith('formDesactivar_')) {
            if (form.dataset.confirmed !== 'true') {
                e.preventDefault();
                abrirModalDesactivacion(form.id);
                return;
            }
        }

        e.preventDefault();
        const url = form.getAttribute('action');
        const formData = new FormData(form);

        fetch(url, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(response => {
            if (response.success) {
                if (formDesactivacionActual) cerrarModalDesactivacion();
                if (typeof SPA !== 'undefined' && typeof SPA.toast === 'function') {
                    SPA.toast('success', 'Éxito', response.message);
                    SPA.loadModule('index.php?action=usuarios_admin', false);
                } else {
                    window.location.reload();
                }
            } else {
                const msg = response.message || 'No se pudo cambiar el estado.';
                if (typeof SPA !== 'undefined' && typeof SPA.toast === 'function') {
                    SPA.toast('error', 'Error', msg);
                } else {
                    alert(msg);
                }
                if (formDesactivacionActual) cerrarModalDesactivacion();
            }
        })
        .catch(() => {
            form.submit();
        });
    }
});
