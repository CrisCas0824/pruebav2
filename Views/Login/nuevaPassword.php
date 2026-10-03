<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tienda La Caserita - Nueva Contraseña</title>
    <link rel="stylesheet" href="Styles/global.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="Styles/styles-login.css?v=<?php echo time(); ?>">
    <link rel="icon" type="image/png" href="Images/logo.png">
</head>
<body>
    <div class="container">
        <div class="left-panel">
            <div class="logo">
                <img src="Images/icono_tienda_blanco.png" alt="logo-tienda">
            </div>
            <h1 class="brand-title">Tienda "La Caserita"</h1>
            <p class="brand-subtitle">Tu tienda de confianza, ahora en línea. Productos frescos para tu hogar.</p>
        </div>

        <div class="right-panel">
            <div class="login-card" style="position: relative;">
                <a href="index.php" class="btn-close-universal" style="position: absolute; top: 16px; right: 16px; z-index: 50;" title="Volver a la tienda" aria-label="Cerrar">
                    <span class="modal-close-glyph" aria-hidden="true">&times;</span>
                </a>

                <h2 class="titulo">NUEVA CONTRASEÑA</h2>
                <p class="subtitulo">Crea una nueva contraseña segura</p>

                <?php if (isset($error_message) && !empty($error_message)): ?>
                    <div class="auth-global-error">
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <form id="finalResetForm" action="index.php?action=nueva_password" method="POST">
                    <div class="grupo">
                        <label class="label">Nueva Contraseña</label>
                        <div class="input-wrapper">
                            <span class="input-icon" style="display: flex; align-items: center; justify-content: center;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                            </span>
                            <input type="password" name="password" id="newPassword" placeholder="Tu nueva contraseña" value="<?php echo isset($form_data['password']) ? htmlspecialchars($form_data['password']) : ''; ?>" required autocomplete="new-password">
                            <button type="button" class="password-toggle" onclick="togglePass('newPassword', this)" style="background:transparent; border:none; cursor:pointer; color:#94a3b8; display:flex; align-items:center; padding-right:12px; transition: color 0.2s;">
                                <svg class="eye-closed" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                                <svg class="eye-open" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                            </button>
                        </div>
                    </div>

                    <div class="grupo">
                        <label class="label">Confirmar Nueva Contraseña</label>
                        <div class="input-wrapper">
                            <span class="input-icon" style="display: flex; align-items: center; justify-content: center;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                            </span>
                            <input type="password" name="confirm_password" id="confirmNewPassword" placeholder="Confirma tu nueva contraseña" value="<?php echo isset($form_data['confirm_password']) ? htmlspecialchars($form_data['confirm_password']) : ''; ?>" required autocomplete="new-password">
                            <button type="button" class="password-toggle" onclick="togglePass('confirmNewPassword', this)" style="background:transparent; border:none; cursor:pointer; color:#94a3b8; display:flex; align-items:center; padding-right:12px; transition: color 0.2s;">
                                <svg class="eye-closed" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                                <svg class="eye-open" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                            </button>
                        </div>
                        <span class="error-message" id="reset-match-error" style="color:#ef4444; font-size:12px; font-weight:600; margin-top:4px; display:none;"></span>
                    </div>

                    <button type="submit" class="login-button">CAMBIAR CONTRASEÑA</button>
                </form>

                <div style="margin-top: 25px; text-align: center; border-top: 1px solid #eaeaea; padding-top: 20px;">
                    <p style="font-size: 0.92rem; color: #64748b;">¿Recordaste tu contraseña? <a href="index.php?action=login"
                            style="color: #15803D; font-weight: 700; text-decoration: none;">Inicia sesión aquí</a></p>
                </div>
            </div>
        </div>
    </div>



    <script src="Scripts/Vistas/Login/nuevaPassword.js?v=<?= time(); ?>"></script>
</body>
</html>
