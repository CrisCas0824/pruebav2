// ==========================================
// FUNCIONES DEL MODAL DE AUTENTICACIÓN
// ==========================================
window.validarPasswordModal = function(val) {
    const card = document.getElementById('authPassChecklist');
    const passInput = document.getElementById('modal_reg_pass');

    const hasMin = val.length >= 8;
    const hasUpper = /[A-Z]/.test(val);
    const hasNum = /[0-9]/.test(val);
    const hasSpecial = /[^A-Za-z0-9]/.test(val);

    const updateItem = (id, valid) => {
        const el = document.getElementById(id);
        if (!el) return;
        if (valid) {
            el.classList.add('valid');
        } else {
            el.classList.remove('valid');
        }
    };

    updateItem('chkMinChar', hasMin);
    updateItem('chkUpper', hasUpper);
    updateItem('chkNum', hasNum);
    updateItem('chkSpecial', hasSpecial);

    const todoCompleto = hasMin && hasUpper && hasNum && hasSpecial;

    // Si ya completó todos los requisitos, ocultar el recuadro dinámicamente
    if (card) {
        if (todoCompleto) {
            card.style.display = 'none';
        } else if (document.activeElement === passInput) {
            card.style.display = 'block';
        }
    }

    validarConfirmModal();
};

window.mostrarRequisitosPass = function() {
    const card = document.getElementById('authPassChecklist');
    const val = document.getElementById('modal_reg_pass').value;
    const hasMin = val.length >= 8;
    const hasUpper = /[A-Z]/.test(val);
    const hasNum = /[0-9]/.test(val);
    const hasSpecial = /[^A-Za-z0-9]/.test(val);
    const todoCompleto = hasMin && hasUpper && hasNum && hasSpecial;

    if (card && !todoCompleto) {
        card.style.display = 'block';
    }
};

window.ocultarRequisitosPass = function() {
    setTimeout(() => {
        const card = document.getElementById('authPassChecklist');
        if (card) {
            card.style.display = 'none';
        }
    }, 200);
};

window.validarConfirmModal = function() {
    const pass = document.getElementById('modal_reg_pass').value;
    const confirmInput = document.getElementById('modal_reg_confirm');
    const confirmVal = confirmInput ? confirmInput.value : '';
    const feedback = document.getElementById('modalConfirmFeedback');

    if (!feedback) return;

    if (confirmVal.length === 0) {
        feedback.style.display = 'none';
        feedback.className = 'auth-confirm-msg';
        return;
    }

    feedback.style.display = 'flex';
    if (confirmVal === pass) {
        feedback.className = 'auth-confirm-msg match';
        feedback.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg> <span>Las contraseñas coinciden</span>`;
    } else {
        feedback.className = 'auth-confirm-msg mismatch';
        feedback.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg> <span>Las contraseñas no coinciden</span>`;
    }
};
window.abrirModalAuth = function(modo = 'login') {
    const modal = document.getElementById('authModal');
    if (!modal) return;

    cambiarModoAuth(modo);
    modal.style.display = 'flex';
    // Forzar reflow para animación suave
    void modal.offsetWidth;
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';

    // Inicializar Google Sign-In si está disponible
    iniciarGoogleServices();
};

window.cerrarModalAuth = function(e) {
    if (e && e.target && e.target.closest('.auth-container-redesign')) {
        return; // Click dentro del contenedor, no cerrar
    }
    const modal = document.getElementById('authModal');
    if (!modal) return;

    modal.classList.remove('active');
    setTimeout(() => {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }, 300);
};

window.cambiarModoAuth = function(modo) {
    const loginForm = document.getElementById('modalLoginForm');
    const regForm = document.getElementById('modalRegisterForm');
    const errLogin = document.getElementById('authLoginError');
    const errReg = document.getElementById('authRegisterError');

    if (errLogin) { errLogin.style.display = 'none'; errLogin.textContent = ''; }
    if (errReg) { errReg.style.display = 'none'; errReg.textContent = ''; }

    if (modo === 'register') {
        loginForm.style.display = 'none';
        regForm.style.display = 'block';
    } else {
        regForm.style.display = 'none';
        loginForm.style.display = 'block';
    }
};

window.togglePassModal = function(idInput, btn) {
    const input = document.getElementById(idInput);
    if (!input) return;
    if (input.type === 'password') {
        input.type = 'text';
        btn.style.color = '#15803D';
        btn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>`;
    } else {
        input.type = 'password';
        btn.style.color = '#94a3b8';
        btn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>`;
    }
};

window.handleModalLogin = async function(e) {
    e.preventDefault();
    const form = e.target;
    const btnSubmit = document.getElementById('btnModalLoginSubmit');
    const errorBox = document.getElementById('authLoginError');
    const email = document.getElementById('modal_login_email').value.trim();
    const pass = document.getElementById('modal_login_pass').value;

    errorBox.style.display = 'none';
    btnSubmit.disabled = true;
    btnSubmit.textContent = 'Verificando...';

    try {
        const formData = new FormData(form);
        const resp = await fetch('index.php?action=login', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        });

        const contentType = resp.headers.get('content-type') || '';
        if (contentType.includes('application/json')) {
            const data = await resp.json();
            if (data.nuevo_csrf_token) {
                document.querySelectorAll('input[name="csrf_token"]').forEach(input => {
                    input.value = data.nuevo_csrf_token;
                });
            }
            if (data.ok || data.success) {
                btnSubmit.textContent = '¡Bienvenido!';
                window.location.href = data.redirect || 'index.php?action=home_usuario';
            } else {
                throw new Error(data.mensaje || data.message || 'Credenciales incorrectas');
            }
        } else {
            // Si el servidor redirigió normalmente
            window.location.reload();
        }
    } catch (err) {
        errorBox.textContent = err.message || 'Error al iniciar sesión.';
        errorBox.style.display = 'block';
        btnSubmit.disabled = false;
        btnSubmit.textContent = 'Iniciar Sesión';
    }
    return false;
};

window.handleModalRegister = async function(e) {
    e.preventDefault();
    const form = e.target;
    const btnSubmit = document.getElementById('btnModalRegisterSubmit');
    const errorBox = document.getElementById('authRegisterError');
    const pass = document.getElementById('modal_reg_pass').value;
    const confirm = document.getElementById('modal_reg_confirm').value;
    const terms = document.getElementById('modal_reg_terms');

    errorBox.style.display = 'none';

    if (pass.length < 8) {
        errorBox.textContent = 'La contraseña debe tener al menos 8 caracteres.';
        errorBox.style.display = 'block';
        return false;
    }

    if (!/[A-Z]/.test(pass)) {
        errorBox.textContent = 'La contraseña debe contener al menos 1 letra mayúscula.';
        errorBox.style.display = 'block';
        return false;
    }

    if (!/[0-9]/.test(pass)) {
        errorBox.textContent = 'La contraseña debe contener al menos 1 número.';
        errorBox.style.display = 'block';
        return false;
    }

    if (!/[^A-Za-z0-9]/.test(pass)) {
        errorBox.textContent = 'La contraseña debe contener al menos 1 carácter especial (ej. @, #, $, %, !).';
        errorBox.style.display = 'block';
        return false;
    }

    if (pass !== confirm) {
        errorBox.textContent = 'Las contraseñas no coinciden.';
        errorBox.style.display = 'block';
        return false;
    }

    if (terms && !terms.checked) {
        errorBox.textContent = 'Debes leer y aceptar los Términos y Condiciones para crear tu cuenta.';
        errorBox.style.display = 'block';
        return false;
    }

    btnSubmit.disabled = true;
    btnSubmit.textContent = 'Creando cuenta...';

    try {
        const formData = new FormData(form);
        const resp = await fetch('index.php?action=registro', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        });

        const contentType = resp.headers.get('content-type') || '';
        if (contentType.includes('application/json')) {
            const data = await resp.json();
            if (data.nuevo_csrf_token) {
                document.querySelectorAll('input[name="csrf_token"]').forEach(input => {
                    input.value = data.nuevo_csrf_token;
                });
            }
            if (data.ok || data.success) {
                btnSubmit.textContent = '¡Cuenta lista!';
                window.location.href = data.redirect || 'index.php?action=home_usuario';
            } else {
                throw new Error(data.mensaje || data.message || 'No se pudo crear la cuenta');
            }
        } else {
            window.location.reload();
        }
    } catch (err) {
        errorBox.textContent = err.message || 'Error al registrar usuario.';
        errorBox.style.display = 'block';
        btnSubmit.disabled = false;
        btnSubmit.textContent = 'Crear Cuenta';
    }
    return false;
};

// Google OAuth en Modal (Carga e inicialización robusta)
function cargarScriptGoogleSDK(callback) {
    if (window.google && window.google.accounts) {
        if (callback) callback();
        return;
    }
    let script = document.querySelector('script[src*="gsi/client"]');
    if (!script) {
        script = document.createElement('script');
        script.src = 'https://accounts.google.com/gsi/client';
        script.async = true;
        script.defer = true;
        document.head.appendChild(script);
    }
    script.onload = function() {
        iniciarGoogleServices();
        if (callback) callback();
    };
}

function iniciarGoogleServices() {
    if (window.google && window.google.accounts) {
        try {
            google.accounts.id.initialize({
                client_id: "1026978247159-p7mer3bmdjm52dkji27b02ucbk5cdo6o.apps.googleusercontent.com",
                callback: handleModalGoogleCallback,
                auto_prompt: false
            });

            const target = document.getElementById('modalGoogleTarget');
            if (target && !target.dataset.rendered) {
                google.accounts.id.renderButton(target, {
                    type: 'standard',
                    theme: 'outline',
                    size: 'large',
                    text: 'continue_with',
                    shape: 'rectangular',
                    width: 300
                });
                target.dataset.rendered = 'true';
            }
        } catch (e) {
            console.error('Error inicializando Google Identity:', e);
        }
    }
}

function iniciarGoogleModal() {
    if (!window.google || !window.google.accounts) {
        cargarScriptGoogleSDK(function() {
            ejecutarClickGoogle();
        });
        return;
    }
    ejecutarClickGoogle();
}

function ejecutarClickGoogle() {
    iniciarGoogleServices();
    const target = document.getElementById('modalGoogleTarget');
    const realBtn = target ? target.querySelector('div[role="button"]') : null;
    if (realBtn) {
        realBtn.click();
    } else if (window.google && window.google.accounts) {
        google.accounts.id.prompt();
    }
}

function handleModalGoogleCallback(response) {
    if (!response.credential) {
        alert('No se pudo obtener la credencial de Google.');
        return;
    }

    fetch('index.php?action=login_google', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'credential=' + encodeURIComponent(response.credential)
    })
    .then(res => res.json())
    .then(data => {
        if (data.ok) {
            window.location.href = data.redirect || 'index.php?action=home_usuario';
        } else {
            alert(data.mensaje || 'Error al autenticar con Google');
        }
    })
    .catch(() => alert('Error de conexión con el servidor al iniciar sesión con Google.'));
}

// Abrir modal automáticamente si se pasa ?open_auth=1 o ?action=login
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('open_auth') === '1' || urlParams.get('open_login') === '1' || window.location.hash === '#login') {
        abrirModalAuth('login');
    } else if (urlParams.get('open_register') === '1' || window.location.hash === '#registro') {
        abrirModalAuth('register');
    }
});
