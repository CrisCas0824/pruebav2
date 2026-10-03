<?php
    $csrf_token = isset($csrf_token)
    ? $csrf_token
    : ($_SESSION['csrf_token'] ?? '');   
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tienda La Caserita - Crear Cuenta</title>
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
                <?php 
                    $esPrimerAdmin = (isset($totalUsuarios) && $totalUsuarios === 0);
                ?>
                <h2 class="titulo"><?php echo $esPrimerAdmin ? 'REGISTRO DE ADMINISTRADOR' : 'CREAR CUENTA'; ?></h2>
                <p class="subtitulo"><?php echo $esPrimerAdmin ? 'Configura la cuenta de Administrador Principal del sistema' : 'Únete a La Caserita hoy mismo'; ?></p>

                <?php if ($esPrimerAdmin): ?>
                    <div style="background: #e0f2fe; color: #0369a1; padding: 12px; border-radius: 8px; border: 1px solid #bae6fd; margin-bottom: 20px; font-weight: 500; font-size: 0.9rem; text-align: center;">
                        🛡️ <strong>Primer Registro:</strong> Esta cuenta tendrá el rol de <strong>ADMINISTRADOR</strong> con control total del sistema.
                    </div>
                <?php endif; ?>

                <?php if (isset($error_message) && !empty($error_message)): ?>
                    <div class="auth-global-error">
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <form id="registerForm" action="index.php?action=registro" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <div class="grupo">
                        <label class="label">Nombre Completo</label>
                        <div class="input-wrapper">
                            <span class="input-icon">👤</span>
                            <input type="text" name="nombre" id="regNombre" placeholder="Tu nombre completo" value="<?php echo isset($form_data['nombre']) ? htmlspecialchars($form_data['nombre']) : ''; ?>" required>
                        </div>
                    </div>

                    <div class="grupo">
                        <label class="label">Correo Electrónico</label>
                        <div class="input-wrapper">
                            <span class="input-icon">✉</span>
                            <input type="email" name="correo" id="regEmail" placeholder="tu@email.com" value="<?php echo isset($form_data['correo']) ? htmlspecialchars($form_data['correo']) : ''; ?>" required>
                        </div>
                    </div>

                    <div class="grupo">
                        <label class="label">Contraseña</label>
                        <div class="input-wrapper">
                            <span class="input-icon">🔒</span>
                            <input type="password" name="password" id="regPassword" placeholder="Crea una contraseña" value="<?php echo isset($form_data['password']) ? htmlspecialchars($form_data['password']) : ''; ?>" required>
                            <button type="button" class="password-toggle" onclick="togglePass('regPassword', this)" style="background:transparent; border:none; cursor:pointer; color:#666; display:flex; align-items:center; padding-right:10px;">
                                <svg class="eye-closed" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                                <svg class="eye-open" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                            </button>
                        </div>
                    </div>

                    <div class="grupo">
                        <label class="label">Confirmar Contraseña</label>
                        <div class="input-wrapper">
                            <span class="input-icon">🔒</span>
                            <input type="password" name="confirm_password" id="regConfirmPassword" placeholder="Repite tu contraseña" value="<?php echo isset($form_data['confirm_password']) ? htmlspecialchars($form_data['confirm_password']) : ''; ?>" required>
                            <button type="button" class="password-toggle" onclick="togglePass('regConfirmPassword', this)" style="background:transparent; border:none; cursor:pointer; color:#666; display:flex; align-items:center; padding-right:10px;">
                                <svg class="eye-closed" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                                <svg class="eye-open" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                            </button>
                        </div>
                        <span class="error-message" id="match-error" style="color:red; font-size:12px;"></span>
                    </div>

                    <button type="submit" class="login-button">REGISTRARSE</button>
                </form>

                <div style="margin-top: 20px; text-align: center;">
                    <p>¿Ya tienes cuenta? <a href="index.php?action=login"
                            style="color: #15803D; font-weight: bold;">Inicia Sesión</a></p>
                </div>
            </div>
        </div>
    </div>

    <script src="Scripts/Vistas/Login/registro.js?v=<?= time(); ?>"></script>
</body>
</html>

