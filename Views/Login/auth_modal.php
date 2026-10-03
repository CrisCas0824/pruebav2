<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];
?>

<!-- Estilos del Modal de Autenticación -->
<link rel="stylesheet" href="Styles/styles-auth-modal.css?v=<?= time(); ?>">
<!-- Google Identity Services SDK -->
<script src="https://accounts.google.com/gsi/client" async defer></script>

<!-- Modal de Autenticación Unificado (Estilo Amaranto) -->
<div class="auth-modal-overlay" id="authModal" onclick="cerrarModalAuth(event)">
    <div class="auth-container-redesign" onclick="event.stopPropagation()">
        <!-- Botón cerrar modal -->
        <a href="index.php" class="btn-close-universal" style="position: absolute; top: 16px; right: 16px; z-index: 50;" onclick="cerrarModalAuth(); event.preventDefault();" title="Volver a la tienda" aria-label="Cerrar">
            &times;
        </a>

        <!-- Columna Izquierda: Panel de Marca "La Caserita" (Estilo Teal Caserita) -->
        <div class="auth-visual auth-brand-side">
            <div class="auth-brand-side-content">
                <div class="auth-brand-side-logo">
                    <img src="Images/logo.png" alt="Tienda La Caserita" class="auth-brand-side-icon">
                </div>
                <h2 class="auth-brand-side-title">Tienda "La Caserita"</h2>
                <p class="auth-brand-side-desc">Tu tienda de confianza, ahora en línea. Productos frescos para tu hogar.</p>
            </div>
        </div>

        <!-- Columna Derecha: Panel de Formularios -->
        <div class="auth-panel">
            <div class="auth-brand-badge">
                <img src="Images/logo.png" alt="Logo" class="auth-login-icon">
                <span class="auth-brand-name">Tienda "La Caserita"</span>
            </div>

            <!-- ==========================================
                 FORMULARIO 1: INICIAR SESIÓN
                 ========================================== -->
            <div id="modalLoginForm">
                <h2 class="auth-title">INICIAR SESIÓN</h2>
                <p class="auth-subtitle">Accede a tu cuenta de La Caserita</p>

                <div id="authLoginError" class="auth-alert-error" style="display: none;"></div>

                <form id="formModalLogin" onsubmit="return handleModalLogin(event);">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token); ?>">

                    <div class="auth-input-wrapper">
                        <span class="auth-input-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                        </span>
                        <input type="email" id="modal_login_email" name="correo" class="auth-input" placeholder="Correo electrónico" required autocomplete="email">
                    </div>

                    <div class="auth-input-wrapper">
                        <span class="auth-input-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                        </span>
                        <input type="password" id="modal_login_pass" name="password" class="auth-input" placeholder="Contraseña" required autocomplete="current-password">
                        <button type="button" class="auth-pass-toggle" onclick="togglePassModal('modal_login_pass', this)">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                        </button>
                    </div>

                    <div style="text-align: right; margin-bottom: 14px;">
                        <a href="index.php?action=recuperar" style="font-size: 0.8rem; color: #15803D; text-decoration: none; font-weight: 600;">¿Olvidaste tu contraseña?</a>
                    </div>

                    <button type="submit" id="btnModalLoginSubmit" class="auth-btn">Iniciar Sesión</button>
                </form>

                <div class="auth-divider">
                    <span>O CONTINÚA CON</span>
                </div>

                <!-- Google Sign In Button -->
                <div class="auth-google-wrapper">
                    <button type="button" class="auth-google-btn" onclick="iniciarGoogleModal()">
                        <svg width="19" height="19" viewBox="0 0 24 24">
                            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                        </svg>
                        <span>Iniciar sesión con Google</span>
                    </button>
                    <!-- Google Identity Hidden Render Target -->
                    <div id="modalGoogleTarget" style="display: none;"></div>
                </div>

                <p class="auth-switch-text">
                    ¿No tienes cuenta?
                    <button type="button" class="auth-switch-link" onclick="cambiarModoAuth('register')">Regístrate aquí</button>
                </p>
            </div>

            <!-- ==========================================
                 FORMULARIO 2: CREAR CUENTA
                 ========================================== -->
            <div id="modalRegisterForm" style="display: none;">
                <h2 class="auth-title">CREAR CUENTA</h2>
                <p class="auth-subtitle">Únete a la familia La Caserita</p>

                <div id="authRegisterError" class="auth-alert-error" style="display: none;"></div>

                <form id="formModalRegister" onsubmit="return handleModalRegister(event);">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token); ?>">

                    <div class="auth-input-wrapper">
                        <span class="auth-input-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                        </span>
                        <input type="text" id="modal_reg_nombre" name="nombre" class="auth-input" placeholder="Nombre completo" required autocomplete="name">
                    </div>

                    <div class="auth-input-wrapper">
                        <span class="auth-input-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                        </span>
                        <input type="email" id="modal_reg_email" name="correo" class="auth-input" placeholder="Correo electrónico" required autocomplete="email">
                    </div>

                    <div class="auth-input-wrapper">
                        <span class="auth-input-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                        </span>
                        <input type="password" id="modal_reg_pass" name="password" class="auth-input" placeholder="Contraseña" required autocomplete="new-password" oninput="validarPasswordModal(this.value)" onfocus="mostrarRequisitosPass()" onblur="ocultarRequisitosPass()">
                        <button type="button" class="auth-pass-toggle" onclick="togglePassModal('modal_reg_pass', this)">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                        </button>
                    </div>

                    <!-- Panel Compacto de Requisitos de Contraseña (Se muestra al hacer clic/enfocar, se oculta al completar o salir) -->
                    <div class="auth-pass-checklist-card" id="authPassChecklist" style="display: none;">
                        <div class="auth-pass-checklist-title">La contraseña debe tener:</div>
                        <div class="auth-pass-checklist-item" id="chkMinChar">
                            <span class="auth-chk-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            </span>
                            <span class="auth-chk-text">Mínimo 8 caracteres</span>
                        </div>
                        <div class="auth-pass-checklist-item" id="chkUpper">
                            <span class="auth-chk-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            </span>
                            <span class="auth-chk-text">Al menos 1 letra mayúscula</span>
                        </div>
                        <div class="auth-pass-checklist-item" id="chkNum">
                            <span class="auth-chk-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            </span>
                            <span class="auth-chk-text">Al menos 1 número</span>
                        </div>
                        <div class="auth-pass-checklist-item" id="chkSpecial">
                            <span class="auth-chk-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            </span>
                            <span class="auth-chk-text">Al menos 1 carácter especial (@, #, $, %, etc.)</span>
                        </div>
                    </div>

                    <div class="auth-input-wrapper" style="margin-bottom: 4px;">
                        <span class="auth-input-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                        </span>
                        <input type="password" id="modal_reg_confirm" name="confirm_password" class="auth-input" placeholder="Confirmar contraseña" required autocomplete="new-password" oninput="validarConfirmModal()">
                        <button type="button" class="auth-pass-toggle" onclick="togglePassModal('modal_reg_confirm', this)">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                        </button>
                    </div>

                    <!-- Mensaje dinámico de confirmación de contraseña -->
                    <div id="modalConfirmFeedback" class="auth-confirm-msg" style="display: none;"></div>

                    <!-- Términos y Condiciones -->
                    <div class="auth-terms-box">
                        <label class="auth-terms-label">
                            <input type="checkbox" id="modal_reg_terms" name="acepto_terminos" required>
                            <span>He leído y acepto los <a href="#" onclick="alert('Al crear una cuenta en Tienda La Caserita declaras aceptar nuestros términos de servicio y política de privacidad.'); return false;">Términos y Condiciones</a></span>
                        </label>
                    </div>

                    <button type="submit" id="btnModalRegisterSubmit" class="auth-btn">Crear Cuenta</button>
                </form>

                <div class="auth-divider">
                    <span>O CONTINÚA CON</span>
                </div>

                <div class="auth-google-wrapper">
                    <button type="button" class="auth-google-btn" onclick="iniciarGoogleModal()">
                        <svg width="19" height="19" viewBox="0 0 24 24">
                            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                        </svg>
                        <span>Registrarse con Google</span>
                    </button>
                </div>

                <p class="auth-switch-text">
                    ¿Ya tienes cuenta?
                    <button type="button" class="auth-switch-link" onclick="cambiarModoAuth('login')">Inicia sesión</button>
                </p>
            </div>
        </div>
    </div>
</div>

<script src="Scripts/Vistas/Login/auth_modal.js?v=<?= time(); ?>"></script>

