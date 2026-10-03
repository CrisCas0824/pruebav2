<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$correo = $_SESSION['correo_recuperacion'] ?? '';
$pin_dev_helper = $_SESSION['pin_dev_helper'] ?? null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tienda La Caserita - Verificar Código PIN</title>
    <link rel="stylesheet" href="Styles/global.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="Styles/styles-login.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="Styles/Vistas/Login/verificarPin.css?v=<?= time(); ?>">
    <link rel="icon" type="image/png" href="Images/logo.png">
</head>
<body>
    <div class="container">
        <div class="left-panel">
            <div class="logo">
                <img src="Images/icono_tienda_blanco.png" alt="logo-tienda">
            </div>
            <h1 class="brand-title">Tienda "La Caserita"</h1>
            <p class="brand-subtitle">Tu tienda de confianza, ahora en línea. Seguridad y frescura para tu hogar.</p>
        </div>

        <div class="right-panel">
            <div class="login-card" style="position: relative;">
                <a href="index.php" class="btn-close-universal" style="position: absolute; top: 16px; right: 16px; z-index: 50;" title="Volver a la tienda" aria-label="Cerrar">
                    <span class="modal-close-glyph" aria-hidden="true">&times;</span>
                </a>

                <div style="display: flex; justify-content: center; margin-bottom: 12px;">
                    <div style="width: 52px; height: 52px; border-radius: 50%; background: #ecfdf5; border: 1px solid #a7f3d0; display: flex; align-items: center; justify-content: center; color: #15803D;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                    </div>
                </div>

                <h2 class="titulo">CÓDIGO DE SEGURIDAD</h2>
                <p class="subtitulo" style="margin-bottom: 1rem;">
                    Ingresa el PIN de 6 dígitos enviado a:<br>
                    <strong style="color: #0f172a; font-weight: 700; word-break: break-all;"><?php echo htmlspecialchars($correo); ?></strong>
                </p>

                <?php if (isset($pin_dev_helper) && !empty($pin_dev_helper)): ?>
                    <div style="background: #ecfdf5; border: 1px dashed #10b981; border-radius: 10px; padding: 10px 14px; margin-bottom: 16px; font-size: 0.88rem; color: #065f46; text-align: center;">
                        🔑 <strong>PIN de recuperación:</strong> <span style="font-family: monospace; font-size: 1.25rem; letter-spacing: 3px; font-weight: 800; color: #15803D;"><?php echo htmlspecialchars($pin_dev_helper); ?></span>
                    </div>
                <?php endif; ?>

                <?php if (isset($success_message) && !empty($success_message)): ?>
                    <div style="background: #d4edda; color: #155724; padding: 12px; border-radius: 8px; border: 1px solid #c3e6cb; margin-bottom: 16px; font-size: 0.92rem; text-align: center;">
                        <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($error_message) && !empty($error_message)): ?>
                    <div class="auth-global-error" style="margin-bottom: 16px;">
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <form id="pinForm" action="index.php?action=verificar_pin" method="POST">
                    <input type="hidden" name="pin" id="fullPin" value="">

                    <div class="pin-inputs-container" style="display: flex; gap: 8px; justify-content: center; margin: 15px 0 25px;">
                        <input type="text" class="pin-digit-input" maxlength="1" pattern="[0-9]" inputmode="numeric" autocomplete="one-time-code" autofocus>
                        <input type="text" class="pin-digit-input" maxlength="1" pattern="[0-9]" inputmode="numeric">
                        <input type="text" class="pin-digit-input" maxlength="1" pattern="[0-9]" inputmode="numeric">
                        <input type="text" class="pin-digit-input" maxlength="1" pattern="[0-9]" inputmode="numeric">
                        <input type="text" class="pin-digit-input" maxlength="1" pattern="[0-9]" inputmode="numeric">
                        <input type="text" class="pin-digit-input" maxlength="1" pattern="[0-9]" inputmode="numeric">
                    </div>

                    <button type="submit" id="btnVerificar" class="login-button" style="margin-top: 5px;">VERIFICAR CÓDIGO</button>
                </form>

                <div class="pin-resend-container" style="margin-top: 20px; text-align: center;">
                    <p style="font-size: 0.9rem; color: #64748b;">
                        ¿No recibiste el código?
                        <a href="index.php?action=reenviar_pin" id="resendLink" class="pin-resend-link disabled" style="color: #94a3b8; pointer-events: none; text-decoration: none;">
                            Reenviar en <span id="countdown">60</span>s
                        </a>
                    </p>
                </div>

                <div style="margin-top: 25px; text-align: center; border-top: 1px solid #eaeaea; padding-top: 18px;">
                    <a href="index.php?action=recuperar" style="color: #64748b; font-size: 0.92rem; text-decoration: none; font-weight: 500; display: inline-flex; align-items: center; gap: 6px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                        Cambiar correo electrónico
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="Scripts/Vistas/Login/verificarPin.js?v=<?= time(); ?>"></script>
</body>
</html>
