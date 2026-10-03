/**
 * UniversalCameraScanner - Sistema Integral de Lectura de Códigos de Barras por Cámara / Celular
 * El celular vinculado transmite códigos al campo activo del ERP.
 * - Lectura de códigos 1D (EAN-13, EAN-8, UPC-A, UPC-E, CODE 128, CODE 39) y 2D (QR Code)
 * Tienda La Caserita
 */

(function (window) {
    'use strict';

    // Contexto de audio Web Audio API para emitir pitido de escaneo sin requerir archivos mp3
    let audioCtx = null;
    function playBarcodeBeep(isSuccess = true) {
        try {
            if (!audioCtx) {
                audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            }
            if (audioCtx.state === 'suspended') {
                audioCtx.resume();
            }
            const osc = audioCtx.createOscillator();
            const gain = audioCtx.createGain();
            osc.connect(gain);
            gain.connect(audioCtx.destination);

            osc.type = 'sine';
            osc.frequency.setValueAtTime(isSuccess ? 1900 : 380, audioCtx.currentTime);
            gain.gain.setValueAtTime(0.18, audioCtx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + (isSuccess ? 0.085 : 0.22));

            osc.start();
            osc.stop(audioCtx.currentTime + (isSuccess ? 0.095 : 0.23));

            if (navigator.vibrate) {
                navigator.vibrate(isSuccess ? [60] : [100, 50, 100]);
            }
        } catch (e) {
            console.warn('Audio/Haptic feedback no disponible:', e);
        }
    }

    function isEditableScanTarget(element) {
        if (!element || element.disabled || element.readOnly) return false;
        if (element.tagName === 'TEXTAREA') return true;
        if (element.tagName !== 'INPUT') return false;
        return !['button', 'submit', 'reset', 'checkbox', 'radio', 'file', 'range', 'color', 'hidden', 'image'].includes(element.type);
    }

    const UniversalCameraScanner = {
        isReal: true,
        currentConfig: null,
        isOpen: false,
        lastScannedCode: null,
        lastScannedTime: 0,
        domElements: null,

        // Inicializa o asegura que el modal exista en el DOM
        ensureModalDOM: function () {
            if (document.getElementById('universalCameraScannerModal')) {
                return;
            }

            // Inyectar estilos para el modal y la animación láser
            const style = document.createElement('style');
            style.id = 'universalCameraScannerStyles';
            style.textContent = `
                .ucs-overlay {
                    position: fixed;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background: rgba(15, 23, 42, 0.75);
                    backdrop-filter: blur(6px);
                    z-index: 999999;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    padding: 16px;
                    opacity: 0;
                    visibility: hidden;
                    transition: opacity 0.25s ease, visibility 0.25s ease;
                }
                .ucs-overlay.active {
                    opacity: 1;
                    visibility: visible;
                }
                .ucs-card {
                    background: #ffffff;
                    border-radius: 20px;
                    width: 100%;
                    max-width: 520px;
                    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.45);
                    overflow: hidden;
                    display: flex;
                    flex-direction: column;
                    transform: translateY(20px) scale(0.97);
                    transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1);
                }
                .ucs-overlay.active .ucs-card {
                    transform: translateY(0) scale(1);
                }
                .ucs-header {
                    padding: 16px 20px;
                    background: linear-gradient(135deg, #15803D, #083c46);
                    color: #ffffff;
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                }
                .ucs-header-title {
                    display: flex;
                    align-items: center;
                    gap: 12px;
                }
                .ucs-header-icon {
                    width: 40px;
                    height: 40px;
                    background: rgba(255, 255, 255, 0.18);
                    border-radius: 10px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 1.25rem;
                }
                .ucs-btn-close {
                    background: rgba(255, 255, 255, 0.15);
                    border: none;
                    color: #ffffff;
                    width: 34px;
                    height: 34px;
                    border-radius: 50%;
                    cursor: pointer;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 1.3rem;
                    transition: background 0.2s;
                }
                .ucs-btn-close:hover {
                    background: rgba(255, 255, 255, 0.3);
                }
                .ucs-body {
                    padding: 18px 20px;
                    display: flex;
                    flex-direction: column;
                    gap: 14px;
                }
                .ucs-select-group {
                    display: flex;
                    gap: 8px;
                    align-items: center;
                }
                .ucs-select {
                    flex: 1;
                    padding: 10px 14px;
                    border-radius: 10px;
                    border: 1.5px solid #cbd5e1;
                    font-size: 0.88rem;
                    font-weight: 600;
                    color: #0f172a;
                    background: #f8fafc;
                    outline: none;
                    cursor: pointer;
                }
                .ucs-select:focus {
                    border-color: #15803D;
                    background: #ffffff;
                }
                .ucs-btn-refresh {
                    padding: 0 12px;
                    height: 42px;
                    background: #f1f5f9;
                    border: 1.5px solid #cbd5e1;
                    border-radius: 10px;
                    color: #475569;
                    cursor: pointer;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    transition: all 0.2s;
                }
                .ucs-btn-refresh:hover {
                    background: #e2e8f0;
                    color: #0f172a;
                }
                .ucs-viewport-wrapper {
                    position: relative;
                    width: 100%;
                    border-radius: 16px;
                    background: #000000;
                    overflow: hidden;
                    min-height: 280px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    box-shadow: inset 0 0 25px rgba(0, 0, 0, 0.8);
                }
                #ucsCameraReader {
                    width: 100% !important;
                    height: 100% !important;
                }
                #ucsCameraReader video {
                    width: 100% !important;
                    height: auto !important;
                    border-radius: 16px;
                    object-fit: cover;
                }
                .ucs-laser-overlay {
                    position: absolute;
                    inset: 18px;
                    border: 2px dashed rgba(34, 197, 94, 0.75);
                    border-radius: 14px;
                    pointer-events: none;
                    box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.4);
                }
                .ucs-laser-line {
                    position: absolute;
                    left: 4%;
                    right: 4%;
                    height: 2.5px;
                    background: #ef4444;
                    box-shadow: 0 0 12px #ef4444, 0 0 4px #ffffff;
                    animation: ucsLaserScan 2s infinite alternate ease-in-out;
                }
                @keyframes ucsLaserScan {
                    0% { top: 12%; opacity: 0.85; }
                    50% { opacity: 1; }
                    100% { top: 88%; opacity: 0.85; }
                }
                .ucs-target-hint {
                    position: absolute;
                    bottom: 10px;
                    left: 0;
                    right: 0;
                    text-align: center;
                    color: #ffffff;
                    font-size: 0.78rem;
                    font-weight: 700;
                    text-shadow: 0 1px 4px rgba(0,0,0,0.9);
                    background: rgba(0, 0, 0, 0.6);
                    padding: 4px 10px;
                    border-radius: 8px;
                    margin: 0 20px;
                    backdrop-filter: blur(2px);
                }
                .ucs-feedback-card {
                    padding: 12px 14px;
                    border-radius: 12px;
                    background: #f0fdf4;
                    border: 1px solid #bbf7d0;
                    display: none;
                    align-items: center;
                    gap: 10px;
                }
                .ucs-feedback-card.active {
                    display: flex;
                }
                .ucs-feedback-icon {
                    color: #16a34a;
                    font-size: 1.3rem;
                }
                .ucs-feedback-title {
                    font-size: 0.86rem;
                    font-weight: 800;
                    color: #166534;
                }
                .ucs-feedback-sub {
                    font-size: 0.78rem;
                    color: #15803d;
                }
                .ucs-footer {
                    padding: 12px 20px;
                    background: #f8fafc;
                    border-top: 1px solid #e2e8f0;
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                }
                .ucs-checkbox-label {
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    font-size: 0.82rem;
                    font-weight: 600;
                    color: #475569;
                    cursor: pointer;
                    user-select: none;
                }
                .ucs-checkbox-label input {
                    width: 16px;
                    height: 16px;
                    accent-color: #15803D;
                    cursor: pointer;
                }
                .ucs-btn-done {
                    background: #e2e8f0;
                    color: #334155;
                    border: none;
                    padding: 8px 18px;
                    border-radius: 8px;
                    font-weight: 700;
                    font-size: 0.85rem;
                    cursor: pointer;
                    transition: background 0.2s;
                }
                .ucs-btn-done:hover {
                    background: #cbd5e1;
                    color: #0f172a;
                }
            `;
            document.head.appendChild(style);

            // Inyectar HTML del modal
            const modal = document.createElement('div');
            modal.id = 'universalCameraScannerModal';
            modal.className = 'ucs-overlay';
            modal.setAttribute('role', 'dialog');
            modal.setAttribute('aria-modal', 'true');
            modal.innerHTML = `
                <div class="ucs-card" onclick="event.stopPropagation()">
                    <div class="ucs-header">
                        <div class="ucs-header-title">
                            <div class="ucs-header-icon">
                                <i class="fa-solid fa-camera"></i>
                            </div>
                            <div>
                                <h3 id="ucsModalTitle" style="margin:0; font-size:1.15rem; font-weight:800; line-height:1.2;">Lector de Código de Barras</h3>
                                <small id="ucsModalSubtitle" style="opacity:0.85; font-size:0.75rem;">Celular vinculado por cable o Bluetooth</small>
                            </div>
                        </div>
                        <button type="button" class="ucs-btn-close" id="ucsBtnClose" title="Cerrar (Esc)">&times;</button>
                    </div>

                    <div class="ucs-body">
                        <div class="ucs-viewport-wrapper" style="min-height:120px; background:#f8fafc; box-shadow:none; color:#475569; font-weight:700; text-align:center; padding:20px;">
                            <i class="fa-solid fa-mobile-screen-button" style="font-size:2rem; color:#15803D; margin-bottom:8px;"></i>
                            <div>Escanea con el celular vinculado. El código se enviará al campo activo de esta pantalla.</div>
                        </div>

                        <!-- Feedback de lectura -->
                        <div class="ucs-feedback-card" id="ucsFeedbackBox">
                            <div class="ucs-feedback-icon">
                                <i class="fa-solid fa-circle-check"></i>
                            </div>
                            <div style="flex:1;">
                                <div class="ucs-feedback-title" id="ucsFeedbackTitle">¡Código leído con éxito!</div>
                                <div class="ucs-feedback-sub" id="ucsFeedbackSub">Código: <strong id="ucsFeedbackCode"></strong></div>
                            </div>
                        </div>
                    </div>

                    <div class="ucs-footer">
                        <label class="ucs-checkbox-label">
                            <input type="checkbox" id="ucsChkContinuous">
                            <span>Modo continuo</span>
                        </label>
                        <div style="display:flex; align-items:center; gap:8px;">
                            <div id="ucsPhoneStatus" style="display:flex; align-items:center; gap:6px; font-size:0.78rem; font-weight:700; color:#15803d; background:rgba(21,128,61,0.08); padding:6px 12px; border-radius:8px; border:1px solid rgba(21,128,61,0.2);">
                                <span style="width:7px; height:7px; border-radius:50%; background:#10b981; display:inline-block; box-shadow:0 0 6px #10b981;"></span>
                                <span id="ucsPhoneStatusText">Esperando celular conectado</span>
                            </div>
                            <button type="button" class="ucs-btn-done" id="ucsBtnDone">Cerrar</button>
                        </div>
                    </div>
                </div>
            `;

            document.body.appendChild(modal);

            // Listeners de cierre
            modal.addEventListener('click', () => {
                UniversalCameraScanner.close();
            });
            document.getElementById('ucsBtnClose').addEventListener('click', () => {
                UniversalCameraScanner.close();
            });
            document.getElementById('ucsBtnDone').addEventListener('click', () => {
                UniversalCameraScanner.close();
            });
            // Teclado Esc
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && UniversalCameraScanner.isOpen) {
                    UniversalCameraScanner.close();
                }
            });
        },

        // Manejador central cuando se detecta un código válido
        handleScanSuccess: function (decodedText, decodedResult) {
            const now = Date.now();
            UniversalCameraScanner.lastScannedCode = decodedText;
            UniversalCameraScanner.lastScannedTime = now;

            // Emitir sonido tipo lector de supermercado
            playBarcodeBeep(true);

            // Mostrar feedback visual en modal
            const fb = document.getElementById('ucsFeedbackBox');
            const fbCode = document.getElementById('ucsFeedbackCode');
            const fbTitle = document.getElementById('ucsFeedbackTitle');
            const fbSub = document.getElementById('ucsFeedbackSub');
            if (fb && fbCode) {
                fb.className = 'ucs-feedback-card active';
                fb.style.borderColor = '#bbf7d0';
                fb.style.background = '#f0fdf4';
                fbTitle.textContent = '¡Código de barras leído!';
                fbCode.textContent = decodedText;
            }

            const targetInputId = UniversalCameraScanner.currentConfig && UniversalCameraScanner.currentConfig.targetInputId;
            let targetInput = targetInputId ? document.getElementById(targetInputId) : null;
            if (!targetInput && !(UniversalCameraScanner.isOpen && UniversalCameraScanner.currentConfig && UniversalCameraScanner.currentConfig.onScan)) {
                const focusedElement = document.activeElement;
                if (isEditableScanTarget(focusedElement)) targetInput = focusedElement;
            }
            if (targetInput) {
                    targetInput.value = decodedText;
                    targetInput.dispatchEvent(new Event('input', { bubbles: true }));
                    targetInput.dispatchEvent(new Event('change', { bubbles: true }));
                    
                    // Efecto de pulso verde en el input
                    targetInput.style.transition = 'box-shadow 0.3s ease, border-color 0.3s ease';
                    targetInput.style.boxShadow = '0 0 0 3px rgba(34, 197, 94, 0.4)';
                    targetInput.style.borderColor = '#16a34a';
                    setTimeout(() => {
                        targetInput.style.boxShadow = '';
                        targetInput.style.borderColor = '';
                    }, 1500);
            }

            // Invocar callback personalizado si existe
            if (UniversalCameraScanner.isOpen && UniversalCameraScanner.currentConfig && typeof UniversalCameraScanner.currentConfig.onScan === 'function') {
                try {
                    UniversalCameraScanner.currentConfig.onScan(decodedText, decodedResult, UniversalCameraScanner);
                } catch (e) {
                    console.error('Error en callback onScan:', e);
                }
            }

            // Si no está activo el modo continuo, cerrar el modal automáticamente tras breve pausa
            const chkContinuous = document.getElementById('ucsChkContinuous');
            const isContinuous = chkContinuous ? chkContinuous.checked : false;

            if (UniversalCameraScanner.isOpen && !isContinuous) {
                setTimeout(() => {
                    UniversalCameraScanner.close();
                }, 450);
            }
        },

        remotePhonePollingTimer: null,
        startRemotePhonePolling: function () {
            if (UniversalCameraScanner.remotePhonePollingTimer) {
                clearInterval(UniversalCameraScanner.remotePhonePollingTimer);
            }
            UniversalCameraScanner.remotePhonePollingTimer = setInterval(() => {
                if (document.getElementById('posTableBody')) {
                    return;
                }
                const config = UniversalCameraScanner.currentConfig;
                const configuredTarget = config && config.targetInputId && document.getElementById(config.targetInputId);
                const focusedElement = document.activeElement;
                const focusedTarget = isEditableScanTarget(focusedElement);
                const callbackTarget = UniversalCameraScanner.isOpen && config && typeof config.onScan === 'function';
                if (!configuredTarget && !focusedTarget && !callbackTarget) return;
                fetch('index.php?action=pos_escaneos_pendientes')
                    .then(res => res.json())
                    .then(res => {
                        if (res.status === 'success' && Array.isArray(res.data) && res.data.length > 0) {
                            res.data.forEach(item => {
                                const codigo = item.codigo_escaneado;
                                if (codigo) {
                                    UniversalCameraScanner.handleScanSuccess(codigo, { text: codigo, source: 'celular_remoto' });
                                }
                            });
                        }
                    })
                    .catch(() => {});
            }, 500);
        },

        stopRemotePhonePolling: function () {
            if (UniversalCameraScanner.remotePhonePollingTimer) {
                clearInterval(UniversalCameraScanner.remotePhonePollingTimer);
                UniversalCameraScanner.remotePhonePollingTimer = null;
            }
        },

        /**
         * Abre el lector de cámara
         * Opciones soportadas:
         * - title: Título del modal
         * - subtitle: Subtítulo descriptivo
         * - continuous: true/false (por defecto false)
         * - targetInputId: ID del <input> donde se inyectará el código leído
         * - onScan: function(code, result, scanner)
         */
        open: function (options = {}) {
            UniversalCameraScanner.ensureModalDOM();
            UniversalCameraScanner.currentConfig = options;
            UniversalCameraScanner.isOpen = true;

            const modal = document.getElementById('universalCameraScannerModal');
            const title = document.getElementById('ucsModalTitle');
            const sub = document.getElementById('ucsModalSubtitle');
            const chk = document.getElementById('ucsChkContinuous');
            const fb = document.getElementById('ucsFeedbackBox');

            if (title && options.title) title.textContent = options.title;
            if (sub && options.subtitle) sub.textContent = options.subtitle;
            if (chk) chk.checked = !!options.continuous;
            if (fb) fb.className = 'ucs-feedback-card'; // ocultar

            modal.classList.add('active');

            UniversalCameraScanner.startRemotePhonePolling();
        },

        // Cierra el lector y apaga la cámara
        close: function () {
            UniversalCameraScanner.isOpen = false;
            UniversalCameraScanner.currentConfig = null;
            const modal = document.getElementById('universalCameraScannerModal');
            if (modal) {
                modal.classList.remove('active');
            }
        }
    };

    // Exportar al objeto global window
    window.UniversalCameraScanner = UniversalCameraScanner;
    window.playBarcodeBeep = playBarcodeBeep;
    UniversalCameraScanner.startRemotePhonePolling();

})(window);
