<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tienda La Caserita - Recuperar Contraseña</title>
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

                <h2 class="titulo">RECUPERAR CONTRASEÑA</h2>
                <p class="subtitulo">Ingresa tu correo para buscar tu cuenta</p>

                <?php if (isset($error_message) && !empty($error_message)): ?>
                    <div class="auth-global-error">
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                    
                    <?php if ($error_message === "El correo electrónico no se encuentra registrado en el sistema."): ?>
                        <div style="text-align: center; display: flex; justify-content: center; margin-bottom: 16px;">
                            <a href="index.php?action=registro&correo=<?php echo urlencode($form_data['correo'] ?? ''); ?>" class="btn-suggest-register" style="display: inline-flex; align-items: center; gap: 8px; background: #15803D; color: #fff; padding: 10px 18px; border-radius: 10px; text-decoration: none; font-weight: 600; font-size: 0.9rem;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="9" cy="7" r="4"></circle>
                                    <line x1="19" y1="8" x2="19" y2="14"></line>
                                    <line x1="22" y1="11" x2="16" y2="11"></line>
                                </svg>
                                Registrar nuevo usuario
                            </a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <form id="recuperarForm" action="index.php?action=recuperar" method="POST">
                    <div class="grupo">
                        <label class="label">Correo Electrónico</label>
                        <div class="input-wrapper">
                            <span class="input-icon" style="display: flex; align-items: center; justify-content: center;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                            </span>
                            <input type="email" name="correo" id="correo" placeholder="tu@email.com" value="<?php echo isset($form_data['correo']) ? htmlspecialchars($form_data['correo']) : ''; ?>" required autocomplete="email">
                        </div>
                    </div>

                    <button type="submit" class="login-button">BUSCAR CUENTA</button>
                </form>

                <div style="margin-top: 25px; text-align: center; border-top: 1px solid #eaeaea; padding-top: 20px;">
                    <p style="font-size: 0.92rem; color: #64748b;">¿Recordaste tu contraseña? <a href="index.php?action=login"
                            style="color: #15803D; font-weight: 700; text-decoration: none;">Inicia sesión aquí</a></p>
                </div>
            </div>
        </div>
    </div>


</body>
</html>
