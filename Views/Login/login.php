<?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    $csrf_token = $_SESSION['csrf_token'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tienda La Caserita - Iniciar Sesión</title>
    <link rel="stylesheet" href="Styles/global.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="Styles/styles-login.css?v=<?php echo time(); ?>">
    <!-- Google Identity Services -->
    <script src="https://accounts.google.com/gsi/client" async defer></script>
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
            <div class="login-card login-card-tall" style="position: relative;">
                <a href="index.php" class="btn-close-universal" style="position: absolute; top: 16px; right: 16px; z-index: 50;" title="Volver a la tienda" aria-label="Cerrar">
                    <span class="modal-close-glyph" aria-hidden="true">&times;</span>
                </a>

                <h2 class="titulo">¡BIENVENIDO DE NUEVO!</h2>
                <p class="subtitulo">Ingrese sus datos para continuar</p>

                <!-- Botón Oficial de Google Sign-In -->
                <div style="margin-bottom: 20px; display: flex; flex-direction: column; align-items: center; width: 100%;">
                    <div id="g_id_onload"
                         data-client_id="1026978247159-p7mer3bmdjm52dkji27b02ucbk5cdo6o.apps.googleusercontent.com"
                         data-callback="handleGoogleCredentialResponse"
                         data-auto_prompt="false">
                    </div>
                    <div class="g_id_signin" data-type="standard" data-shape="pill" data-theme="outline" data-text="continue_with" data-size="large" data-logo_alignment="left" style="width: 100%;"></div>
                </div>

                <div style="display:flex; align-items:center; margin: 15px 0 20px; color: #94a3b8; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em;">
                    <div style="flex:1; height:1px; background:#e2e8f0;"></div>
                    <span style="padding:0 10px;">o con correo electrónico</span>
                    <div style="flex:1; height:1px; background:#e2e8f0;"></div>
                </div>

                <?php if (isset($totalUsuarios) && $totalUsuarios === 0): ?>
                    <div style="background: #e0f2fe; color: #0369a1; padding: 14px; border-radius: 8px; border: 1px solid #bae6fd; margin-bottom: 20px; font-size: 0.95rem; text-align: center;">
                        👋 <strong>¡Sistema listo!</strong> Aún no hay usuarios creados.<br>
                        <a href="index.php?action=registro" style="display:inline-block; margin-top:8px; background:#15803D; color:#fff; padding:6px 14px; border-radius:5px; text-decoration:none; font-weight:bold;">🛡️ Registrar Primer Administrador</a>
                    </div>
                <?php endif; ?>

                <?php if (isset($success_message) && !empty($success_message)): ?>
                    <div style="background: #d4edda; color: #155724; padding: 12px; border-radius: 8px; border: 1px solid #c3e6cb; margin-bottom: 20px; font-weight: 500; font-size: 0.95rem; text-align: center;">
                        <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($error_message) && !empty($error_message)): ?>
                    <div class="auth-global-error">
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <form id="loginForm" action="index.php?action=login" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <div class="grupo">
                        <label class="label">Correo Electrónico</label>
                        <div class="input-wrapper">
                            <span class="input-icon" style="display: flex; align-items: center; justify-content: center;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                            </span>
                            <input type="email" name="correo" id="email" placeholder="tu@email.com" value="<?php echo isset($form_data['correo']) ? htmlspecialchars($form_data['correo']) : ''; ?>" required autocomplete="email">
                        </div>
                        <span class="error-message" id="email-error"></span>
                    </div>

                    <div class="grupo">
                        <label class="label">Contraseña</label>
                        <div class="input-wrapper">
                            <span class="input-icon" style="display: flex; align-items: center; justify-content: center;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                            </span>
                            <input type="password" name="password" id="password" placeholder="Ingrese su contraseña" value="<?php echo isset($form_data['password']) ? htmlspecialchars($form_data['password']) : ''; ?>" required autocomplete="current-password">
                            <button type="button" class="password-toggle" onclick="togglePass('password', this)" style="background:transparent; border:none; cursor:pointer; color:#94a3b8; display:flex; align-items:center; padding-right:12px; transition: color 0.2s;">
                                <svg class="eye-closed" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                                <svg class="eye-open" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                            </button>
                        </div>
                        <span class="error-message" id="password-error"></span>
                    </div>

                    <div class="forgot-password-container" style="text-align: right; margin-bottom: 20px;">
                        <a href="index.php?action=recuperar" class="forgot-password" style="color: #15803D; text-decoration: none; font-size: 0.95rem; font-weight: 600; transition: color 0.3s ease;">¿Olvidaste tu contraseña?</a>
                    </div>

                    <div id="lockMessage" style="display: none; background: #fee2e2; color: #dc2626; padding: 12px; border-radius: 8px; text-align: center; margin-bottom: 20px; font-weight: 500; font-size: 0.95rem;">
                        Cuenta bloqueada. Intente nuevamente en <span id="lockTimer"></span> s.
                    </div>

                    <button type="submit" class="login-button" id="btnLogin">INICIAR SESIÓN</button>
                </form>

                <div style="margin-top: 25px; text-align: center; border-top: 1px solid #eaeaea; padding-top: 20px;">
                    <p style="font-size: 0.92rem; color: #64748b;">¿No tienes cuenta? <a href="index.php?action=registro"
                            style="color: #15803D; font-weight: 700; text-decoration: none;">Regístrate aquí</a></p>
                </div>
            </div>
        </div>
    </div>



    
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-content">
            <div class="loading-spinner"></div>
            <img src="Images/logo.png" class="loading-logo" alt="Cargando...">
        </div>
        <h3 class="loading-text">Cargando...</h3>
    </div>

    <script>
        
        const lockExpires = <?php echo isset($lock_expires) ? (int)$lock_expires : 0; ?>;
        const currentTimeServer = <?php echo time(); ?>;
        const timeOffset = Math.floor(Date.now() / 1000) - currentTimeServer;
        
        function updateLockTimer() {
            if (lockExpires > 0) {
                const now = Math.floor(Date.now() / 1000) - timeOffset;
                const remaining = lockExpires - now;
                
                const btnLogin = document.getElementById('btnLogin');
                const lockMessage = document.getElementById('lockMessage');
                const lockTimerSpan = document.getElementById('lockTimer');
                
                if (remaining > 0) {
                    
                    btnLogin.disabled = true;
                    btnLogin.style.backgroundColor = '#9ca3af'; 
                    btnLogin.style.cursor = 'not-allowed';
                    btnLogin.textContent = 'BLOQUEADO';
                    
                    lockMessage.style.display = 'block';
                    lockTimerSpan.textContent = remaining;
                    setTimeout(updateLockTimer, 1000);
                } else {
                    
                    btnLogin.disabled = false;
                    btnLogin.style.backgroundColor = ''; 
                    btnLogin.style.cursor = 'pointer';
                    btnLogin.textContent = 'INICIAR SESIÓN';
                    
                    lockMessage.style.display = 'none';
                }
            }
        }
        
        
        updateLockTimer();

        function togglePass(id, btn) {
            var input = document.getElementById(id);
            var eyeClosed = btn.querySelector('.eye-closed');
            var eyeOpen = btn.querySelector('.eye-open');

            if (input.type === 'password') {
                input.type = 'text';
                if(eyeClosed) eyeClosed.style.display = 'none';
                if(eyeOpen) eyeOpen.style.display = 'block';
            } else {
                input.type = 'password';
                if(eyeClosed) eyeClosed.style.display = 'block';
                if(eyeOpen) eyeOpen.style.display = 'none';
            }
        }


        
        document.getElementById('email').addEventListener('input', function () {
            this.classList.remove('error');
            document.getElementById('email-error').classList.remove('show');
            document.getElementById('email-error').textContent = '';
        });

        document.getElementById('password').addEventListener('input', function () {
            this.classList.remove('error');
            document.getElementById('password-error').classList.remove('show');
            document.getElementById('password-error').textContent = '';
        });

        
        document.getElementById('loginForm').addEventListener('submit', function (e) {
            var email = document.getElementById('email');
            var password = document.getElementById('password');
            var valid = true;

            
            var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!email.value || !emailRegex.test(email.value)) {
                email.classList.add('error');
                document.getElementById('email-error').textContent = 'Ingrese un correo válido';
                document.getElementById('email-error').classList.add('show');
                valid = false;
            }

            
            if (!password.value || password.value.length < 6) {
                password.classList.add('error');
                document.getElementById('password-error').textContent = 'La contraseña debe tener al menos 6 caracteres';
                document.getElementById('password-error').classList.add('show');
                valid = false;
            }

            if (!valid) {
                e.preventDefault();
            }
        });

        
        
        
        (function () {
            const secondsLeft = <?php echo isset($blockedFor) ? (int)$blockedFor : 0; ?>;
            if (!secondsLeft) return;

            const btn = document.getElementById('btnLogin');
            
            
            let msg = document.querySelector('.auth-global-error');
            if (!msg) {
                msg = document.createElement('div');
                msg.className = 'auth-global-error';
                document.getElementById('loginForm').before(msg);
            }

            btn.disabled = true;
            btn.style.opacity = '0.6';
            btn.style.cursor = 'not-allowed';
            let t = secondsLeft;

            const store = () => localStorage.setItem('lock_expires', Date.now() + t*1000);

            const tick = () => {
                if (t <= 0) {
                    localStorage.removeItem('lock_expires');
                    btn.disabled = false;
                    btn.style.opacity = '1';
                    btn.style.cursor = 'pointer';
                    msg.textContent = 'Intente de nuevo 😊';
                    return;
                }
                msg.textContent = `Usuario bloqueado. Intente nuevamente en ${t--} s.`;
                store();
                setTimeout(tick, 1000);
            };
            tick();
        })();

        
        (function () {
            const saved = localStorage.getItem('lock_expires');
            if (!saved) return;
            const diff = Math.floor((saved - Date.now()) / 1000);
            const blockedForPHP = <?php echo isset($blockedFor) ? (int)$blockedFor : 0; ?>;
            if (diff > 0 && !blockedForPHP) {
                
                
            }
            if (diff <= 0) localStorage.removeItem('lock_expires');
        })();

        // Manejador Google Sign-In Credential Response
        function handleGoogleCredentialResponse(response) {
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
            .catch(() => alert('Error de conexión con el servidor al iniciar sesión con Google'));
        }
    </script>

</body>
</html>
