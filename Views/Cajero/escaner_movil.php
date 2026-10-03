<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Pistola Lectora Láser - Tienda La Caserita</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&family=JetBrains+Mono:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Librería local ultra-rápida y compatible con escaneo 1D y 2D sin depender de internet externo -->
    <script src="Scripts/html5-qrcode.min.js"></script>
    <link rel="stylesheet" href="Styles/Vistas/Cajero/escaner_movil.css?v=<?= time(); ?>">
    <link rel="icon" type="image/png" href="Images/logo.png">
</head>
<body>

    <header class="header">
        <div class="header-title">
            <i class="fa-solid fa-barcode"></i>
            <div>
                <h1>Pistola Lectora Láser</h1>
                <small>Transmisión Directa a la PC</small>
            </div>
        </div>
        <div class="connection-badge" id="badgeEstado">
            <span class="pulse-dot"></span>
            <span id="txtBadge">Conectando con PC...</span>
        </div>
    </header>

    <div class="main-container">
        <!-- Barra de Estado / Token -->
        <div class="info-bar">
            <div class="info-bar-left">
                <i class="fa-solid fa-desktop"></i>
                <div>Vinculado a: <strong>Pantalla Activa en PC</strong></div>
            </div>
            <div>
                <span class="info-token" id="lblToken"><?= htmlspecialchars($token ? substr($token, 0, 12).'...' : 'ACTIVO') ?></span>
            </div>
        </div>

        <!-- Cámara / Visor de Escaneo con Láser -->
        <div class="camera-card" id="cardCamera">
            <div class="scanner-laser" id="scannerLaser"></div>
            <div class="scanner-aim-box">
                <span class="aim-hint"><i class="fa-solid fa-crosshairs"></i> Apunta al código de barras</span>
            </div>
            <div id="reader"></div>
        </div>

        <!-- Controles de Cámara (Linterna, Pausa, Lente) -->
        <div class="cam-controls">
            <button type="button" class="btn-cam" id="btnTorch" onclick="toggleLinterna()">
                <i class="fa-solid fa-bolt"></i> <span id="txtBtnTorch">Linterna</span>
            </button>
            <button type="button" class="btn-cam active" id="btnToggleCam" onclick="toggleCamara()">
                <i class="fa-solid fa-camera"></i> <span id="txtBtnCam">Pausar</span>
            </button>
            <button type="button" class="btn-cam" onclick="cambiarCamara()">
                <i class="fa-solid fa-rotate"></i> Lente
            </button>
        </div>

    </div>

    <!-- Banner emergente de confirmación instantánea -->
    <div class="banner-confirm" id="toastBox">
        <i class="fa-solid fa-circle-check" id="toastIcon"></i>
        <span id="toastMsg">Código transmitido a la PC</span>
    </div>

    <script>
        let TOKEN = "<?= htmlspecialchars($token ?: '') ?>";
        let html5QrCode = null;
        let camaraActiva = true;
        let linternaEncendida = false;
        let ultimoCodigoLeido = null;
        let ultimoTiempoLeido = 0;
        let videoTrack = null;

        function liberarCodigoTrasAusencia() {
            if (ultimoCodigoLeido && Date.now() - ultimoTiempoLeido > 900) {
                ultimoCodigoLeido = null;
            }
        }

        // Contexto de audio Web Audio API para simular el pitido de pistola de código de barras
        let audioCtx = null;
        function getAudioContext() {
            if (!audioCtx) {
                audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            }
            if (audioCtx.state === 'suspended') {
                audioCtx.resume();
            }
            return audioCtx;
        }
        document.addEventListener('touchstart', () => getAudioContext(), { once: true });
        document.addEventListener('click', () => getAudioContext(), { once: true });

        function playBeep(success = true) {
            try {
                const ctx = getAudioContext();
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.connect(gain);
                gain.connect(ctx.destination);
                
                osc.type = 'sine';
                osc.frequency.setValueAtTime(success ? 2200 : 380, ctx.currentTime);
                gain.gain.setValueAtTime(0.24, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + (success ? 0.09 : 0.25));
                
                osc.start();
                osc.stop(ctx.currentTime + (success ? 0.10 : 0.26));

                // Vibración háptica en celulares
                if (navigator.vibrate) {
                    navigator.vibrate(success ? [80] : [160, 60, 160]);
                }
            } catch (e) {
                console.log('Audio no inicializado:', e);
            }
        }

        function mostrarConfirmacion(mensaje, esError = false) {
            const toast = document.getElementById('toastBox');
            const icon = document.getElementById('toastIcon');
            const msg = document.getElementById('toastMsg');

            toast.classList.toggle('error', esError);
            icon.className = esError ? 'fa-solid fa-triangle-exclamation' : 'fa-solid fa-circle-check';
            msg.textContent = mensaje;

            toast.classList.add('show');
            setTimeout(() => {
                toast.classList.remove('show');
            }, 1800);
        }

        // Heartbeat constante hacia la PC cada 3.5 segundos para certificar que el celular está conectado
        function validarConexionConServidor() {
            const formData = new FormData();
            if (TOKEN) formData.append('token', TOKEN);
            formData.append('dispositivo_info', 'Celular Moto G53 (Lector USB)');

            fetch('index.php?action=pos_validar_emparejamiento', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.token) {
                    TOKEN = data.token;
                    const lbl = document.getElementById('lblToken');
                    if (lbl) lbl.textContent = TOKEN.substring(0, 12) + '...';
                }
                const badge = document.getElementById('badgeEstado');
                const txt = document.getElementById('txtBadge');
                if (badge && txt) {
                    const conectado = data.status === 'success';
                    badge.classList.toggle('syncing', !conectado);
                    txt.textContent = conectado ? 'Conectado con PC' : 'Sin conexión con PC';
                }
            })
            .catch(err => {
                console.warn('Error en heartbeat:', err);
                const badge = document.getElementById('badgeEstado');
                const txt = document.getElementById('txtBadge');
                if (badge && txt) {
                    badge.classList.add('syncing');
                    txt.textContent = 'Sin conexión con PC';
                }
            });
        }

        // Enviar código escaneado al servidor (viaja inmediatamente al formulario activo de la PC)
        function registrarEscaneoEnServidor(codigo) {
            const badge = document.getElementById('badgeEstado');
            const txt = document.getElementById('txtBadge');
            if (badge && txt) {
                badge.classList.add('syncing');
                txt.textContent = 'Transmitiendo...';
            }

            const formData = new FormData();
            if (TOKEN) {
                formData.append('token', TOKEN);
            }
            formData.append('codigo', codigo);
            formData.append('cantidad', 1);
            formData.append('dispositivo', 'Celular Lector (Láser USB)');

            fetch('index.php?action=pos_escanear_movil', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (!data.ok) throw new Error(data.mensaje || 'No se pudo enviar el código');
                if (badge && txt) {
                    badge.classList.remove('syncing');
                    txt.textContent = 'Conectado con PC';
                }

                playBeep(true);

                mostrarConfirmacion('Escaneo enviado a la PC');
            })
            .catch(err => {
                console.error(err);
                if (badge && txt) {
                    badge.classList.remove('syncing');
                    txt.textContent = 'Error de Conexión';
                }
                playBeep(false);
                mostrarConfirmacion('Error de conexión con la PC', true);
            });
        }

        let camarasDisponibles = [];
        let camaraIndiceActual = 0;
        let ultimoFacingMode = 'environment';
        let configGlobal = null;
        let qrCodeCallbackGlobal = null;

        // Iniciar visor de cámara con láser
        function initCameraScanner() {
            if (html5QrCode) {
                try {
                    html5QrCode.stop().then(() => {
                        html5QrCode.clear();
                        iniciarCamaraMotor();
                    }).catch(() => {
                        iniciarCamaraMotor();
                    });
                    return;
                } catch (e) {}
            }
            iniciarCamaraMotor();
        }

        function iniciarCamaraMotor() {
            html5QrCode = new Html5Qrcode("reader");

            configGlobal = { 
                fps: 25,
                experimentalFeatures: {
                    useBarCodeDetectorIfSupported: true
                }
            };

            qrCodeCallbackGlobal = (decodedText, decodedResult) => {
                const ahora = Date.now();
                if (decodedText === ultimoCodigoLeido) {
                    ultimoTiempoLeido = ahora;
                    return;
                }
                ultimoCodigoLeido = decodedText;
                ultimoTiempoLeido = ahora;

                // Destello visual verde en el marco de la cámara
                const card = document.getElementById('cardCamera');
                if (card) {
                    card.classList.add('success-flash');
                    setTimeout(() => { card.classList.remove('success-flash'); }, 300);
                }

                // Enviar inmediatamente a la PC
                registrarEscaneoEnServidor(decodedText);
            };

            // Iniciar cámara trasera
            html5QrCode.start(
                { facingMode: "environment" },
                configGlobal,
                qrCodeCallbackGlobal,
                liberarCodigoTrasAusencia
            ).then(() => {
                capturarTrackVideo();
                Html5Qrcode.getCameras().then(devs => {
                    camarasDisponibles = devs || [];
                }).catch(e => {});
            }).catch(err => {
                console.warn("Fallo cámara trasera, intentando frontal:", err);
                html5QrCode.start(
                    { facingMode: "user" },
                    configGlobal,
                    qrCodeCallbackGlobal,
                    liberarCodigoTrasAusencia
                ).then(() => {
                    capturarTrackVideo();
                }).catch(err2 => {
                    console.error("No se pudo iniciar ninguna cámara:", err2);
                    const laser = document.getElementById('scannerLaser');
                    if (laser) laser.style.display = 'none';
                    document.getElementById('reader').innerHTML = `
                        <div style="padding: 30px 15px; text-align: center; color: #94a3b8; font-size: 0.85rem;">
                            <i class="fa-solid fa-camera-slash" style="font-size: 2rem; color: #ef4444; margin-bottom: 8px; display: block;"></i>
                            Otorga permisos de cámara a Chrome en tu celular para activar el lector láser.
                        </div>
                    `;
                });
            });
        }

        function capturarTrackVideo() {
            try {
                const videoEl = document.querySelector('#reader video');
                if (videoEl) {
                    videoEl.style.width = '100%';
                    videoEl.style.height = '100%';
                    videoEl.style.objectFit = 'cover';
                    if (videoEl.srcObject) {
                        const tracks = videoEl.srcObject.getVideoTracks();
                        if (tracks && tracks.length > 0) {
                            videoTrack = tracks[0];
                        }
                    }
                }
            } catch (e) {
                console.log(e);
            }
        }

        function toggleLinterna() {
            if (!videoTrack) {
                capturarTrackVideo();
            }
            if (!videoTrack) {
                mostrarConfirmacion('Linterna no disponible', true);
                return;
            }

            const capabilities = videoTrack.getCapabilities ? videoTrack.getCapabilities() : {};
            if (!capabilities.torch) {
                mostrarConfirmacion('Tu cámara no soporta linterna web', true);
                return;
            }

            linternaEncendida = !linternaEncendida;
            videoTrack.applyConstraints({
                advanced: [{ torch: linternaEncendida }]
            }).then(() => {
                const btn = document.getElementById('btnTorch');
                const txt = document.getElementById('txtBtnTorch');
                btn.classList.toggle('torch-on', linternaEncendida);
                txt.textContent = linternaEncendida ? 'Apagar' : 'Linterna';
            }).catch(e => {
                console.warn('Error torch:', e);
                mostrarConfirmacion('No se pudo activar linterna', true);
            });
        }

        function toggleCamara() {
            if (!html5QrCode) return;
            const btn = document.getElementById('btnToggleCam');
            const txt = document.getElementById('txtBtnCam');
            const laser = document.getElementById('scannerLaser');

            if (camaraActiva) {
                html5QrCode.pause();
                camaraActiva = false;
                btn.classList.remove('active');
                txt.textContent = 'Reanudar';
                if (laser) laser.style.display = 'none';
            } else {
                html5QrCode.resume();
                camaraActiva = true;
                btn.classList.add('active');
                txt.textContent = 'Pausar';
                if (laser) laser.style.display = 'block';
            }
        }

        function cambiarCamara() {
            if (!html5QrCode) return;
            if (camarasDisponibles && camarasDisponibles.length > 1) {
                camaraIndiceActual = (camaraIndiceActual + 1) % camarasDisponibles.length;
                const nextCam = camarasDisponibles[camaraIndiceActual];
                html5QrCode.stop().then(() => {
                    html5QrCode.start(nextCam.id, configGlobal, qrCodeCallbackGlobal, liberarCodigoTrasAusencia).then(() => capturarTrackVideo());
                }).catch(e => console.error(e));
            } else {
                html5QrCode.stop().then(() => {
                    const nuevoFacing = (ultimoFacingMode === 'environment') ? 'user' : 'environment';
                    ultimoFacingMode = nuevoFacing;
                    html5QrCode.start({ facingMode: nuevoFacing }, configGlobal, qrCodeCallbackGlobal, liberarCodigoTrasAusencia).then(() => capturarTrackVideo());
                }).catch(e => console.error(e));
            }
        }

        window.addEventListener('DOMContentLoaded', () => {
            validarConexionConServidor();
            initCameraScanner();
            // Mantener heartbeat cada 3.5s para informar a la PC que el celular está conectado y activo
            setInterval(validarConexionConServidor, 3500);
        });
    </script>
</body>
</html>
