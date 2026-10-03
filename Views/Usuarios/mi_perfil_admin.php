<?php
    require_once 'Core/ajaxHelper.php';
    if(session_status() == PHP_SESSION_NONE){ session_start(); }
    if(!isset($_SESSION['usuario_id'])){ header('Location: index.php?action=login'); exit(); }
    if($_SESSION['usuario_rol'] !== 'admin'){ header('Location: index.php?action=home_usuario'); exit(); }
    $nombre = htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Administrador');
    $csrf_token = isset($csrf_token) ? $csrf_token : ($_SESSION['csrf_token'] ?? '');
    $remainingLock = isset($remainingLock) ? (int)$remainingLock : 0;
?>
<?php if (!AjaxHelper::isAjax()): ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil — Admin La Caserita</title>
    <link rel="icon" type="image/png" href="Images/logo.png">
    <link rel="stylesheet" href="Styles/global.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="Styles/styles-home.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="Styles/styles-users-admin.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="Styles/styles-cuenta.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="Styles/admin-spa.css?v=<?php echo time(); ?>">
    <script src="Scripts/admin-spa.js?v=<?php echo time(); ?>" defer></script>
</head>
<body>
    <div class="admin-layout">
        <?php include __DIR__ . '/../Admin/partials/admin_sidebar.php'; ?>

        <div class="admin-main">
            <header class="admin-topbar">
                <div class="admin-topbar-left">
                    <button type="button" class="admin-hamburger-btn hide-on-desktop" id="openAdminSidebar" aria-label="Abrir Menú">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="3" y1="6" x2="21" y2="6"></line>
                            <line x1="3" y1="12" x2="21" y2="12"></line>
                            <line x1="3" y1="18" x2="21" y2="18"></line>
                        </svg>
                    </button>
                    <div class="admin-topbar-brand">
                        <span class="brand-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                        </span>
                        <span class="brand-title">Mi Perfil Admin</span>
                    </div>
                </div>
                <div class="admin-topbar-actions">
                    <?php include __DIR__ . '/../Admin/partials/admin_topbar_actions.php'; ?>
                </div>
            </header>
            <div id="admin-app">
<?php else: ?>
<link rel="stylesheet" href="Styles/styles-cuenta.css?v=<?php echo time(); ?>">
<?php endif; ?>

<div class="container-admin admin-profile-container" style="padding-top: 15px; padding-bottom: 50px;">
    
    
    <div class="profile-hero-card">
        <div class="profile-hero-content">
            <div class="profile-avatar-wrapper">
                <div class="profile-avatar-large">
                    <?php echo strtoupper(substr($nombre, 0, 1)); ?>
                </div>
                <span class="profile-online-badge" title="Administrador En Línea"></span>
            </div>
            <div class="profile-hero-info">
                <h2><?php echo htmlspecialchars(($usuario['nombre'] ?? '') . ' ' . ($usuario['apellido'] ?? '')); ?></h2>
                <div class="profile-hero-badges">
                    <span class="profile-role-tag">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px; margin-right:4px;"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>Administrador
                    </span>
                    <span class="profile-email-tag">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px; margin-right:4px;"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg><?php echo htmlspecialchars($usuario['correo'] ?? ''); ?>
                    </span>
                </div>
            </div>
        </div>
        
        <div class="profile-hero-toggle">
            <div class="toggle-edit-box">
                <span class="toggle-edit-label">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px; margin-right:4px;"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>Modificar datos
                </span>
                <label class="toggle-switch-field">
                    <input type="checkbox" id="toggleEditPerfil">
                    <span class="toggle-slider-bg" id="sliderPerfil"></span>
                    <span class="toggle-slider-dot" id="sliderCirclePerfil"></span>
                </label>
            </div>
        </div>
    </div>

    
    <div class="panel admin-panel-card" style="margin-bottom: 28px;">
        <div class="panel-header" style="border-bottom: 1px solid #f1f5f9; padding: 18px 20px 14px 20px;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 36px; height: 36px; border-radius: 10px; background: #e0f2fe; color: #0369a1; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                </div>
                <div>
                    <h2 style="margin: 0; font-size: 1.1rem; color: #0f172a; font-weight: 800;">Información Personal</h2>
                    <p style="margin: 2px 0 0; font-size: 0.84rem; color: #64748b;">Mantén actualizados tus datos institucionales de contacto.</p>
                </div>
            </div>
        </div>

        <div class="panel-body admin-panel-body">
            <form id="formActualizarPerfil" class="cuenta-form" action="index.php?action=actualizar_perfil_admin" method="POST" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

                <div class="cuenta-form-grid-2col">
                    <div class="cuenta-form-group">
                        <label for="perfilNombre" class="input-label-std">Nombre</label>
                        <input type="text" name="nombre" id="perfilNombre" class="input-field-std" value="<?php echo htmlspecialchars($usuario['nombre'] ?? ''); ?>" maxlength="80" readonly required>
                        <small class="field-error field-error-msg" data-error-for="nombre"></small>
                    </div>

                    <div class="cuenta-form-group">
                        <label for="perfilApellido" class="input-label-std">Apellido</label>
                        <input type="text" name="apellido" id="perfilApellido" class="input-field-std" value="<?php echo htmlspecialchars($usuario['apellido'] ?? ''); ?>" maxlength="80" readonly required>
                        <small class="field-error field-error-msg" data-error-for="apellido"></small>
                    </div>

                    <div class="cuenta-form-group">
                        <label for="perfilCorreo" class="input-label-std">Correo electrónico (Lectura sola)</label>
                        <div class="input-field-icon-wrap">
                            <input type="email" name="correo" id="perfilCorreo" class="input-field-std input-field-readonly" value="<?php echo htmlspecialchars($usuario['correo'] ?? ''); ?>" maxlength="120" readonly required style="padding-right: 35px;">
                            <svg class="input-field-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" width="20" height="20">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                        </div>
                        <small class="field-error field-error-msg" data-error-for="correo"></small>
                    </div>

                    <div class="cuenta-form-group">
                        <label for="perfilTelefono" class="input-label-std">Teléfono</label>
                        <input type="tel" name="telefono" id="perfilTelefono" class="input-field-std" value="<?php echo htmlspecialchars($usuario['telefono'] ?? ''); ?>" maxlength="15" readonly placeholder="Ej. 987654321">
                        <small class="field-error field-error-msg" data-error-for="telefono"></small>
                    </div>
                </div>

                <div class="cuenta-form-actions" id="accionesPerfil" style="display: none; transition: opacity 0.3s ease; margin-top: 20px; justify-content: flex-end; opacity: 0;">
                    <button type="submit" class="btn btn-primary" style="background: linear-gradient(135deg, #15803D 0%, #14b8a6 100%); color: white; padding: 10px 24px; border-radius: 20px; font-weight: 700; cursor: pointer; border: none; min-height: 44px; box-shadow: 0 4px 12px rgba(20, 184, 166, 0.25); display: inline-flex; align-items: center; gap: 8px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                        Guardar cambios
                    </button>
                </div>
            </form>
        </div>
    </div>

    
    <div class="panel admin-panel-card" style="margin-bottom: 40px;">
        <div class="panel-header" style="border-bottom: 1px solid #f1f5f9; padding: 18px 20px 14px 20px;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 36px; height: 36px; border-radius: 10px; background: #fef3c7; color: #d97706; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                </div>
                <div>
                    <h2 style="margin: 0; font-size: 1.1rem; color: #0f172a; font-weight: 800;">Seguridad y Contraseña</h2>
                    <p style="margin: 2px 0 0; font-size: 0.84rem; color: #64748b;">Protege tu cuenta utilizando una clave segura de acceso.</p>
                </div>
            </div>
        </div>

        <div class="panel-body admin-panel-body">
            <div class="security-layout">
                
                
                <form id="formCambiarPasswordAdmin" class="cuenta-form" action="index.php?action=cambiar_password_admin" method="POST" autocomplete="off">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

                    <div style="display: grid; grid-template-columns: 1fr; gap: 16px; width: 100%;">
                        
                        <div class="cuenta-form-group">
                            <label for="passwordActual" class="input-label-std">Contraseña actual</label>
                            <div class="password-verify-group">
                                <div class="password-wrapper" style="position: relative; flex: 1;">
                                    <input type="password" name="password_actual" id="passwordActual" class="input-field-std" required placeholder="Ingresa tu contraseña actual" style="padding-right: 50px;">
                                    <button type="button" class="toggle-password-btn" data-target="passwordActual">Ver</button>
                                </div>
                                <button type="button" id="btnVerificarPassword" class="btn-verify-pass">Verificar</button>
                            </div>
                            <div id="mensajeBloqueoPassword" style="color: #ef4444; font-size: 0.875rem; margin-top: 5px; display: none;"></div>
                            <small class="field-error field-error-msg" data-error-for="password_actual"></small>
                        </div>

                        <div class="cuenta-form-group">
                            <label for="passwordNueva" class="input-label-std">Nueva contraseña</label>
                            <div class="password-wrapper" style="position: relative;">
                                <input type="password" name="password_nueva" id="passwordNueva" class="input-field-std input-field-readonly" minlength="8" readonly required placeholder="Mínimo 8 caracteres" style="padding-right: 50px;">
                                <button type="button" class="toggle-password-btn" data-target="passwordNueva">Ver</button>
                            </div>
                            <small class="field-error field-error-msg" data-error-for="password_nueva"></small>
                        </div>

                        <div class="cuenta-form-group">
                            <label for="passwordConfirmacion" class="input-label-std">Confirmar contraseña</label>
                            <div class="password-wrapper" style="position: relative;">
                                <input type="password" name="password_confirmacion" id="passwordConfirmacion" class="input-field-std input-field-readonly" minlength="8" readonly required placeholder="Repite la nueva contraseña" style="padding-right: 50px;">
                                <button type="button" class="toggle-password-btn" data-target="passwordConfirmacion">Ver</button>
                            </div>
                            <small id="mensajeConfirmacion" style="display: block; margin-top: 4px; font-size: 0.82rem; color: #6b7280;">Asegúrate de que coincida con la nueva contraseña.</small>
                            <small class="field-error field-error-msg" data-error-for="password_confirmacion"></small>
                        </div>

                        <div class="cuenta-form-actions" id="accionesPassword" style="display: none; transition: opacity 0.3s ease; opacity: 0; margin-top: 10px; gap: 10px; flex-wrap: wrap;">
                            <button type="submit" class="btn btn-primary" style="background: linear-gradient(135deg, #15803D 0%, #14b8a6 100%); color: white; padding: 10px 24px; border-radius: 20px; font-weight: 700; cursor: pointer; border: none; min-height: 44px; box-shadow: 0 4px 12px rgba(20, 184, 166, 0.25); display: inline-flex; align-items: center; gap: 8px;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                                Guardar Contraseña
                            </button>
                            <button type="button" id="btnCancelarPassword" class="btn" style="background: #f1f5f9; color: #475569; padding: 10px 24px; border-radius: 20px; font-weight: 700; cursor: pointer; border: 1px solid #cbd5e1; min-height: 44px;">Cancelar</button>
                        </div>
                    </div>
                </form>

                
                <div class="security-tips-panel">
                    <div class="security-tips-icon" style="display:flex; align-items:center; justify-content:center;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                    </div>
                    <h3>Recomendaciones</h3>
                    <ul>
                        <li>Usa al menos 8 caracteres.</li>
                        <li>Combina números y letras.</li>
                        <li>No reutilices claves antiguas.</li>
                        <li>Mantén confidencial tu clave.</li>
                    </ul>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {

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
                
                perfilNombre.readOnly = false;
                perfilApellido.readOnly = false;
                perfilTelefono.readOnly = false;
                
                perfilNombre.style.borderColor = '#0d9488';
                perfilApellido.style.borderColor = '#0d9488';
                perfilTelefono.style.borderColor = '#0d9488';
                
                accionesPerfil.style.display = 'flex';
                setTimeout(() => {
                    accionesPerfil.style.opacity = '1';
                }, 10);
            } else {
                sliderPerfil.style.backgroundColor = '#d1d5db';
                sliderCirclePerfil.style.transform = 'translateX(0)';
                
                perfilNombre.readOnly = true;
                perfilApellido.readOnly = true;
                perfilTelefono.readOnly = true;
                
                perfilNombre.style.borderColor = '#cbd5e1';
                perfilApellido.style.borderColor = '#cbd5e1';
                perfilTelefono.style.borderColor = '#cbd5e1';
                
                accionesPerfil.style.opacity = '0';
                setTimeout(() => {
                    if (!toggleEditPerfil.checked) {
                        accionesPerfil.style.display = 'none';
                    }
                }, 300);
            }
        });
    }


    $('#formActualizarPerfil').off('submit').on('submit', function(e) {
        e.preventDefault();
        
        $('.field-error').text('');
        $('input').removeClass('input-error');
        
        const $form = $(this);
        const url = $form.attr('action');
        const formData = $form.serialize();
        
        $.ajax({
            url: url,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    SPA.toast('success', 'Éxito', response.message);
                    

                    const nuevoNombre = $('#perfilNombre').val();
                    $('.sidebar-user-name').text(nuevoNombre);
                    

                    if (toggleEditPerfil && toggleEditPerfil.checked) {
                        toggleEditPerfil.click();
                    }
                } else {
                    SPA.toast('error', 'Error', response.message || 'No se pudieron actualizar tus datos.');
                }
            },
            error: function(jqXHR) {
                if (jqXHR.status === 422) {
                    const errors = jqXHR.responseJSON.errors || {};
                    $.each(errors, function(field, msg) {
                        $(`[data-error-for="${field}"]`).text(msg);
                        $(`#perfil${field.charAt(0).toUpperCase() + field.slice(1)}`).addClass('input-error');
                    });
                    SPA.toast('error', 'Datos inválidos', 'Corrige los errores en el formulario.');
                } else {
                    SPA.toast('error', 'Error', 'Ocurrió un error en la comunicación con el servidor.');
                }
            }
        });
    });


    const btnVerificarPassword = document.getElementById('btnVerificarPassword');
    const passwordActual = document.getElementById('passwordActual');
    const passwordNueva = document.getElementById('passwordNueva');
    const passwordConfirmacion = document.getElementById('passwordConfirmacion');
    const accionesPassword = document.getElementById('accionesPassword');
    const mensajeBloqueoPassword = document.getElementById('mensajeBloqueoPassword');
    const formPassword = document.getElementById('formCambiarPasswordAdmin');

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


    const initialLockTime = <?php echo (int)$remainingLock; ?>;
    if (initialLockTime > 0) {
        iniciarBloqueo(initialLockTime);
    }

    
    $('.toggle-password-btn').off('click').on('click', function() {
        const targetId = $(this).data('target');
        const input = document.getElementById(targetId);
        if (input.type === 'password') {
            input.type = 'text';
            $(this).text('Ocultar');
        } else {
            input.type = 'password';
            $(this).text('Ver');
        }
    });

    
    if (btnVerificarPassword) {
        btnVerificarPassword.addEventListener('click', function() {
            if (btnVerificarPassword.disabled) return;
            
            const currentPassword = passwordActual.value.trim();
            if (!currentPassword) {
                SPA.toast('error', 'Error', 'Ingresa tu contraseña actual primero.');
                return;
            }

            const csrfToken = formPassword.querySelector('input[name="csrf_token"]').value;
            
            $(btnVerificarPassword).prop('disabled', true).text('Verificando...');

            $.ajax({
                url: 'index.php?action=verificar_password_actual_admin',
                type: 'POST',
                data: {
                    password_actual: currentPassword,
                    csrf_token: csrfToken
                },
                success: function(response) {
                    if (response.success) {
                        SPA.toast('success', 'Éxito', response.message);
                        
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
                        
                        accionesPassword.style.display = 'flex';
                        setTimeout(() => { accionesPassword.style.opacity = '1'; }, 10);
                    } else {
                        SPA.toast('error', 'Error', response.message);
                    }
                },
                error: function(jqXHR) {
                    const errMsg = jqXHR.responseJSON ? jqXHR.responseJSON.message : 'Error en la verificación.';
                    SPA.toast('error', 'Error', errMsg);
                    
                    const match = errMsg.match(/bloqueado por (\d+) segundos/);
                    if (match) {
                        iniciarBloqueo(parseInt(match[1]));
                    }
                },
                complete: function() {
                    $(btnVerificarPassword).prop('disabled', false).text('Verificar');
                }
            });
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
            
            $('.field-error').text('');
            $('input').removeClass('input-error');
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

    
    $('#formCambiarPasswordAdmin').off('submit').on('submit', function(e) {
        e.preventDefault();
        
        $('.field-error').text('');
        $('input').removeClass('input-error');
        
        const $form = $(this);
        const url = $form.attr('action');
        const formData = $form.serialize();
        
        $.ajax({
            url: url,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    SPA.toast('success', 'Éxito', response.message);
                    if (btnCancelarPassword) btnCancelarPassword.click();
                } else {
                    SPA.toast('error', 'Error', response.message || 'No se pudo actualizar tu contraseña.');
                }
            },
            error: function(jqXHR) {
                if (jqXHR.status === 422) {
                    const errors = jqXHR.responseJSON.errors || {};
                    $.each(errors, function(field, msg) {
                        $(`[data-error-for="${field}"]`).text(msg);
                        const idInput = field === 'password_actual' ? 'passwordActual' : (field === 'password_nueva' ? 'passwordNueva' : 'passwordConfirmacion');
                        $(`#${idInput}`).addClass('input-error');
                    });
                    SPA.toast('error', 'Datos inválidos', 'Corrige los errores en el formulario.');
                } else {
                    SPA.toast('error', 'Error', 'Ocurrió un error en la comunicación con el servidor.');
                }
            }
        });
    });
});
</script>
<?php if (!AjaxHelper::isAjax()): ?>
            </div>
        </div>
    </div>
</body>
</html>
<?php endif; ?>

