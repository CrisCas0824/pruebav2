<?php
require_once 'Core/ajaxHelper.php';
$isAjax = AjaxHelper::isAjax();
$isAdminPOS = ($_SESSION['usuario_rol'] ?? '') === 'admin';
$isCashierPOS = ($_SESSION['usuario_rol'] ?? '') === 'cajero';
?>
<?php if (!$isAjax): ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Punto de Venta POS  La Caserita</title>
    
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap">
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="Styles/styles-pos.css?v=<?= filemtime(__DIR__ . '/../../Styles/styles-pos.css') ?>">
    <link rel="icon" type="image/png" href="Images/logo.png">
    <?php if ($isAdminPOS || $isCashierPOS): ?>
    <link rel="stylesheet" href="Styles/global.css?v=<?= filemtime(__DIR__ . '/../../Styles/global.css') ?>">
    <link rel="stylesheet" href="Styles/styles-home.css?v=<?= filemtime(__DIR__ . '/../../Styles/styles-home.css') ?>">
    <?php endif; ?>
    <?php if ($isAdminPOS): ?>
    <link rel="stylesheet" href="Styles/styles-users-admin.css">
    <link rel="stylesheet" href="Styles/admin-spa.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="Scripts/admin-spa.js" defer></script>
    <?php endif; ?>
</head>
<body class="<?= $isAdminPOS ? '' : 'pos-body' . ($isCashierPOS ? ' role-staff' : '') ?>">
<?php if ($isAdminPOS): ?>
    <div class="admin-layout">
        <?php include __DIR__ . '/../Admin/partials/admin_sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-topbar">
                <div class="admin-topbar-left">
                    <button type="button" class="admin-hamburger-btn hide-on-desktop" id="openAdminSidebar" aria-label="Abrir menú">
                        <i class="fa-solid fa-bars"></i>
                    </button>
                    <div class="admin-topbar-brand">
                        <span class="brand-title">Punto de Venta POS</span>
                    </div>
                </div>
                <div class="admin-topbar-actions">
                    <?php include __DIR__ . '/../Admin/partials/admin_topbar_actions.php'; ?>
                </div>
            </header>
            <div id="admin-container">
<?php endif; ?>
<?php if (!$isAjax && $isCashierPOS): ?>
    <?php $paginaPanelStaff = 'pos'; include __DIR__ . '/../Staff/partials/staff_sidebar.php'; ?>
<?php endif; ?>
<?php else: ?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="Styles/styles-pos.css?v=<?= filemtime(__DIR__ . '/../../Styles/styles-pos.css') ?>">
<link rel="stylesheet" href="Styles/Vistas/Cajero/caja_punto_venta.css?v=<?= filemtime(__DIR__ . '/../../Styles/Vistas/Cajero/caja_punto_venta.css') ?>">
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<?php endif; ?>

<?php if (!$isAjax && !$isAdminPOS): ?>
    <header class="pos-header">
        <?php if ($isCashierPOS): ?>
        <button type="button" class="btn-menu-mobile pos-menu-toggle" id="staffMenuToggle" aria-label="Abrir menú">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
        </button>
        <?php endif; ?>
        <div class="pos-brand">
            <i class="fa-solid fa-cash-register"></i>
            <span>La Caserita POS <small style="font-size:0.75rem; opacity:0.8;">[Caja 01]</small></span>
        </div>
        <div class="pos-user-info" style="display:flex; align-items:center; gap:12px;">
            <span style="font-size:0.88rem; font-weight:600;">
                <i class="fa-solid fa-user-gear" style="color:var(--pos-accent-light); margin-right:4px;"></i>
                <?= htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Cajero') ?>
            </span>
            <a href="index.php?action=home_usuario" class="topbar-store-btn" style="text-decoration:none;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                Volver a Tienda
            </a>
            <a href="index.php?action=logout" class="btn-pos-logout">
                <i class="fa-solid fa-right-from-bracket"></i> Salir
            </a>
        </div>
    </header>
<?php endif; ?>

    <main class="pos-container">
        
        
        <section class="pos-left-panel">
            <div style="display: flex; gap: 10px; align-items: stretch; margin-bottom: 1.5rem; flex-wrap: wrap;">
                <div class="pos-search-box" style="flex: 1; min-width: 280px; margin-bottom: 0; position: relative;">
                    <i class="fa-solid fa-magnifying-glass pos-search-icon"></i>
                    <input type="text" 
                           id="inputBuscarManual" 
                           class="pos-search-input" 
                           placeholder="Buscar por letras, nombre, marca o código de barras... (F2)" 
                           autocomplete="off">
                    <button type="button" id="posSearchClear" style="display: none; position: absolute; right: 14px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #94a3b8; cursor: pointer; font-size: 1rem; padding: 4px;" title="Limpiar búsqueda">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                    <div id="posSearchDropdown" class="pos-search-dropdown"></div>
                </div>
                <button type="button" id="statusBadge" class="pos-status-badge" role="status" onclick="abrirModalConexionEscaner()" title="Abrir conexión del escáner móvil">
                    <span class="status-dot"></span>
                    <span id="statusText">Conecta el celular para escanear</span>
                </button>
            </div>

            <div class="pos-table-wrapper">
                <table class="pos-table">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Producto</th>
                            <th>P. Unit.</th>
                            <th>Cant.</th>
                            <th>Subtotal</th>
                            <th style="text-align: center;">Acción</th>
                        </tr>
                    </thead>
                    <tbody id="posTableBody">
                        
                    </tbody>
                </table>
            </div>
        </section>

        
        <section class="pos-right-panel">
            <h3 style="margin: 0 0 1rem; color:var(--pos-primary); font-size:1.1rem; font-weight:800;">
                <i class="fa-solid fa-receipt"></i> Resumen de la Venta
            </h3>

            <div class="pos-summary-card">
                <div class="summary-line">
                    <span>Subtotal:</span>
                    <strong id="lblSubtotal">S/ 0.00</strong>
                </div>
                <div class="summary-line">
                    <span>IGV (18% incluido):</span>
                    <strong id="lblIgv">S/ 0.00</strong>
                </div>
                <div class="summary-line total">
                    <span>TOTAL A PAGAR:</span>
                    <span id="lblTotal">S/ 0.00</span>
                </div>
            </div>

            
            <!-- CLIENTE / IDENTIFICACIÓN (DNI) -->
            <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:12px; padding:12px; margin-bottom:1rem; box-shadow:0 1px 3px rgba(0,0,0,0.04);">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                    <label style="font-size:0.82rem; font-weight:800; color:var(--pos-text-secondary); text-transform:uppercase; margin:0;">
                        <i class="fa-solid fa-id-card" style="color:var(--pos-primary);"></i> Documento del cliente:
                    </label>
                    <button type="button" onclick="setClienteGenerico()" style="background:#e6f4f6; color:var(--pos-primary); border:none; padding:4px 9px; border-radius:6px; font-size:0.75rem; font-weight:700; cursor:pointer;" title="Asignar Público General">
                        <i class="fa-solid fa-user"></i> Público General
                    </button>
                </div>
                <div style="display:flex; gap:6px; margin-bottom:6px;">
                    <div style="position:relative; flex:1;">
                        <input type="text" id="inputClienteDni" class="pos-input-field" maxlength="11" inputmode="numeric" placeholder="DNI (8) o RUC (11)" value="00000000" style="padding-right:32px; font-weight:700;" onkeypress="if(event.key==='Enter'){event.preventDefault(); consultarDniCajero();}">
                        <button type="button" onclick="consultarDniCajero()" style="position:absolute; right:6px; top:50%; transform:translateY(-50%); background:none; border:none; color:var(--pos-primary); font-size:14px; cursor:pointer;" title="Consultar RENIEC o SUNAT">
                            <i class="fa-solid fa-magnifying-glass" id="iconBuscarDni"></i>
                        </button>
                    </div>
                </div>
                <div style="display:flex; align-items:center; gap:6px; font-size:0.82rem;">
                    <span style="color:#64748b; font-size:0.78rem;">Nombre:</span>
                    <strong id="lblClienteNombre" style="color:#0f172a; flex:1; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">Público General</strong>
                    <button type="button" onclick="editarNombreClienteManual()" style="background:none; border:none; color:#15803D; cursor:pointer; font-size:11px;" title="Editar nombre manualmente">
                        <i class="fa-solid fa-pen-to-square"></i>
                    </button>
                </div>
            </div>

            <label style="display:block; margin-bottom:8px; font-size:0.85rem; font-weight:700; color:var(--pos-text-secondary);">
                MÉTODO DE PAGO:
            </label>
            <div class="payment-methods-grid">
                <button type="button" class="payment-btn active" data-method="efectivo" onclick="seleccionarMetodoPago('efectivo')">
                    <i class="fa-solid fa-money-bill-wave" style="font-size:1.2rem;"></i>
                    <span>Efectivo</span>
                </button>
                <button type="button" class="payment-btn" data-method="tarjeta" onclick="seleccionarMetodoPago('tarjeta')">
                    <i class="fa-solid fa-credit-card" style="font-size:1.2rem;"></i>
                    <span>Tarjeta</span>
                </button>
                <button type="button" class="payment-btn" data-method="yape_plin" onclick="seleccionarMetodoPago('yape_plin')">
                    <i class="fa-solid fa-mobile-screen-button" style="font-size:1.2rem;"></i>
                    <span>Yape / Plin</span>
                </button>
            </div>

            
            <div id="boxMontoRecibido" style="margin-bottom: 1.25rem;">
                <div style="display:flex; gap:12px; align-items:center;">
                    <div style="flex:1;">
                        <label style="display:block; margin-bottom:6px; font-size:0.8rem; font-weight:700; color:var(--pos-text-secondary);">Monto Recibido (S/):</label>
                        <input type="number" step="0.50" id="inputMontoRecibido" class="pos-input-field" placeholder="0.00" oninput="calcularVueltas()">
                    </div>
                    <div style="flex:1;">
                        <label style="display:block; margin-bottom:6px; font-size:0.8rem; font-weight:700; color:var(--pos-text-secondary);">Vueltas / Cambio (S/):</label>
                        <input type="text" id="inputVueltas" class="pos-input-field" readonly value="S/ 0.00">
                    </div>
                </div>
            </div>

            
            <button type="button" class="btn-cobrar-pos" id="btnCobrarVenta" onclick="procesarVentaCaja()">
                <i class="fa-solid fa-circle-check"></i> COBRAR VENTA (F9)
            </button>
            <button type="button" id="btnImprimirVentaAnterior" onclick="imprimirVentaAnterior()" disabled style="width:100%; margin-top:8px; padding:10px; border:1px solid #cbd5e1; border-radius:8px; background:#fff; color:#334155; font-weight:700; cursor:pointer; opacity:.55;">
                <i class="fa-solid fa-print"></i> Imprimir venta anterior
            </button>
        </section>
    </main>

    <div id="posValidationModal" class="pos-validation-overlay" aria-hidden="true">
        <section class="pos-validation-card" role="alertdialog" aria-modal="true" aria-labelledby="posValidationTitle" aria-describedby="posValidationMessage">
            <button type="button" class="btn-close-universal pos-validation-close" onclick="cerrarAvisoValidacionCaja()" aria-label="Cerrar aviso"><span class="modal-close-glyph" aria-hidden="true">&times;</span></button>
            <div class="pos-validation-icon"><i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i></div>
            <h2 id="posValidationTitle">Monto recibido insuficiente</h2>
            <p id="posValidationMessage"></p>
            <button type="button" class="pos-validation-accept" onclick="cerrarAvisoValidacionCaja(); document.getElementById('inputMontoRecibido').focus();">Corregir monto</button>
        </section>
    </div>

    
    <div id="modalLockQR" class="pos-lock-overlay">
        <div class="pos-qr-modal">
            <button type="button" id="btnCerrarConexionEscaner" class="btn-close-universal pos-qr-close" aria-label="Cerrar conexión del escáner">&times;</button>
            <h2 style="margin:0 0 8px; color:var(--pos-primary); font-size:1.35rem; font-weight:800;">
                <i class="fa-solid fa-qrcode" style="color:var(--pos-primary);"></i> Emparejar Escáner Móvil
            </h2>
            <p style="margin:0; color:var(--pos-text-secondary); font-size:0.88rem; line-height:1.4;">
                Vincula una vez tu celular por QR. La cámara del teléfono escanea y los productos aparecen en esta caja; no se usa la cámara de la PC.
            </p>

            <div class="qr-container-box">
                <div id="qrcode"></div>
            </div>

            <div style="font-size:0.82rem; color:var(--pos-text-secondary); margin-bottom:12px;">
                Token de Sesión: <code id="lblTokenSesion" style="color:var(--pos-primary); background:#e6f4f6; padding:4px 8px; border-radius:6px; font-weight:700;">Cargando...</code>
            </div>

            <div style="margin-bottom: 12px; display:flex; flex-direction:column; gap:8px;">
                <button type="button" class="btn-simulate-scanner" onclick="abrirLectorEnCelular()" style="background: #15803d; color: #ffffff; border: none; font-weight: 800; font-size: 0.90rem; box-shadow: 0 4px 12px rgba(21,128,61,0.2); display:flex; align-items:center; justify-content:center; gap:8px;">
                    <i class="fa-solid fa-mobile-screen-button"></i> Abrir Automáticamente en Celular (Cable USB)
                </button>

                <div style="text-align: center;">
                    <a id="btnLinkEscanerMovil" href="#" target="_blank" style="display:inline-flex; align-items:center; gap:6px; background:#f1f5f9; color:var(--pos-primary); text-decoration:none; padding:8px 14px; border-radius:8px; font-size:0.80rem; font-weight:700; border:1px solid #cbd5e1;">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i> Abrir en otra Pestaña / Enlace Manual
                    </a>
                </div>
            </div>

            <div style="display:flex; gap:8px;">
                <button type="button" id="btnEntrarCaja" class="btn-simulate-scanner" style="flex:1; background: #334155; color: #fff; margin-bottom: 0; border: none; font-weight: 700; font-size: 0.82rem;">
                    <i class="fa-solid fa-cash-register"></i> Entrar a Caja
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL QR YAPE / PLIN EN MOSTRADOR -->
    <div id="modalYapePlinPOS" class="pos-lock-overlay" style="display:none;">
        <div class="pos-qr-modal" style="max-width:380px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                <h3 style="margin:0; color:#7c3aed; font-size:1.2rem; font-weight:800;">
                    <i class="fa-solid fa-mobile-screen-button"></i> Cobro con Yape / Plin
                </h3>
                <button type="button" onclick="cerrarModalYapePlin()" class="btn-close-universal">&times;</button>
            </div>
            <p style="margin:0 0 14px; font-size:0.85rem; color:var(--pos-text-secondary);">
                Pide al cliente que escanee el código QR o transfiera al número:
            </p>
            <div style="background:#f8fafc; border:2px dashed #cbd5e1; border-radius:12px; padding:15px; text-align:center; margin-bottom:14px;">
                <div style="font-size:1.3rem; font-weight:900; color:#0f172a; margin-bottom:4px;">999 111 222</div>
                <div style="font-size:0.85rem; font-weight:700; color:#15803D;">La Caserita S.A.C.</div>
                <div style="margin:12px auto; width:160px; height:160px; background:#fff; border-radius:10px; display:flex; align-items:center; justify-content:center; box-shadow:0 4px 10px rgba(0,0,0,0.08);">
                    <img src="uploads/qr/qr_yape.png" onerror="this.onerror=null; this.src='https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=0002010102115802PE5917LA%20CASERITA%20SAC6004LIMA540500.0053036045802PE62070503***';" style="width:140px; height:140px; object-fit:contain;" alt="QR Yape/Plin">
                </div>
                <div style="display:flex; justify-content:center; gap:8px;">
                    <span style="background:#7c3aed; color:#fff; padding:3px 10px; border-radius:10px; font-size:0.75rem; font-weight:800;">YAPE</span>
                    <span style="background:#0284c7; color:#fff; padding:3px 10px; border-radius:10px; font-size:0.75rem; font-weight:800;">PLIN</span>
                </div>
            </div>
            <button type="button" class="btn-cobrar-pos" onclick="cerrarModalYapePlin()" style="margin:0; font-size:0.95rem;">
                ? Código Validado en Celular
            </button>
        </div>
    </div>

    <!-- MODAL TICKET TÉRMICO DE VENTA SIMULADO (80MM / PDF LOCAL) -->
    <div id="modalTicketVenta" class="pos-lock-overlay" style="display:none; z-index:99999;">
        <div style="background:#fff; border-radius:16px; width:100%; max-width:440px; padding:20px; box-shadow:0 25px 50px -12px rgba(0,0,0,0.35); max-height:92vh; display:flex; flex-direction:column;">
            <div class="ticket-no-print" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; border-bottom:1px solid #f1f5f9; padding-bottom:10px;">
                <h3 style="margin:0; font-size:1.1rem; color:#0f172a; font-weight:800;">
                    ?? Comprobante de Venta
                </h3>
                <button type="button" onclick="cerrarTicketVenta()" class="btn-close-universal">&times;</button>
            </div>

            <div style="overflow-y:auto; flex:1; background:#f8fafc; padding:12px; border-radius:10px;">
                <div id="ticketTermicoImprimible" style="background:#fff; padding:20px; font-family:'Courier New', Courier, monospace; font-size:12px; color:#000; line-height:1.35; border:1px solid #e2e8f0; border-radius:6px; max-width:320px; margin:0 auto; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
                    <div style="text-align:center; margin-bottom:10px;">
                        <strong style="font-size:16px; display:block;">TIENDA "LA CASERITA"</strong>
                        <div style="font-size:11px;">RUC: 20512345678</div>
                        <div style="font-size:10px;">Av. Las Flores 123 - San Juan, Lima</div>
                        <div style="font-size:10px;">Teléfono: (01) 456-7890</div>
                        <div style="margin:6px 0; border-bottom:1px dashed #000;"></div>
                        <div style="font-weight:bold; font-size:13px;" id="lblTicketTipoDoc">BOLETA DE VENTA ELECTRÓNICA</div>
                        <div style="font-weight:bold; font-size:13px;" id="lblTicketCodigo">POS-2026-0000</div>
                    </div>

                    <div style="margin-bottom:8px; font-size:11px;">
                        <div><strong>Fecha:</strong> <span id="lblTicketFecha">--</span></div>
                        <div><strong>Cajero:</strong> <span id="lblTicketCajero">--</span></div>
                        <div><strong>Caja:</strong> CAJA-01 (Mostrador)</div>
                        <div><strong>Cliente:</strong> <span id="lblTicketCliente">CLIENTE GENERAL</span></div>
                        <div><strong>DNI / Doc:</strong> <span id="lblTicketDni">00000000</span></div>
                    </div>

                    <div style="border-bottom:1px dashed #000; margin:6px 0;"></div>

                    <table style="width:100%; border-collapse:collapse; font-size:11px;">
                        <thead>
                            <tr style="border-bottom:1px solid #000; text-align:left;">
                                <th>CANT</th>
                                <th>DESCRIPCIÓN</th>
                                <th style="text-align:right;">P.U</th>
                                <th style="text-align:right;">TOTAL</th>
                            </tr>
                        </thead>
                        <tbody id="ticketTbodyItems">
                        </tbody>
                    </table>

                    <div style="border-bottom:1px dashed #000; margin:8px 0;"></div>

                    <div style="font-size:11px; text-align:right;">
                        <div>SUBTOTAL: <strong id="lblTicketSubtotal">S/ 0.00</strong></div>
                        <div>I.G.V. (18%): <strong id="lblTicketIgv">S/ 0.00</strong></div>
                        <div style="font-size:14px; margin-top:4px; font-weight:bold;">TOTAL A PAGAR: <span id="lblTicketTotal">S/ 0.00</span></div>
                    </div>

                    <div style="border-bottom:1px dashed #000; margin:8px 0;"></div>

                    <div style="font-size:11px;">
                        <div><strong>FORMA DE PAGO:</strong> <span id="lblTicketMetodo">EFECTIVO</span></div>
                        <div id="rowTicketEfectivo">
                            <div>RECIBIDO: <span id="lblTicketRecibido">S/ 0.00</span></div>
                            <div>VUELTO: <span id="lblTicketVuelto">S/ 0.00</span></div>
                        </div>
                    </div>

                    <div style="border-bottom:1px dashed #000; margin:10px 0;"></div>

                    <div style="text-align:center; font-size:10px;">
                        <p style="margin:2px 0;">¡GRACIAS POR SU PREFERENCIA!</p>
                        <p style="margin:2px 0;">Conserve este comprobante para cambios o reclamos.</p>
                        <div style="margin-top:8px; letter-spacing:2px; font-size:18px;">||| | ||||| || |||| |||||</div>
                    </div>
                </div>
            </div>

            <div class="ticket-no-print" style="margin-top:14px; display:flex; gap:10px;">
                <button type="button" onclick="imprimirTicketTermico()" style="flex:1; padding:12px; background:#15803D; color:#fff; border:none; border-radius:10px; font-weight:800; cursor:pointer; font-size:0.95rem; display:flex; align-items:center; justify-content:center; gap:8px;">
                    <i class="fa-solid fa-print"></i> IMPRIMIR
                </button>
                <button type="button" onclick="descargarTicketPDF()" style="flex:1; padding:12px; background:#ff7f00; color:#fff; border:none; border-radius:10px; font-weight:800; cursor:pointer; font-size:0.95rem; display:flex; align-items:center; justify-content:center; gap:8px;">
                    <i class="fa-solid fa-file-pdf"></i> DESCARGAR PDF
                </button>
            </div>
            <button type="button" class="ticket-no-print" onclick="cerrarTicketVenta()" style="margin-top:8px; padding:10px; background:#f1f5f9; color:#475569; border:none; border-radius:10px; font-weight:700; cursor:pointer; font-size:0.9rem;">
                ? Nueva Venta
            </button>
        </div>
    </div>

    <!-- Estilos de Impresión Térmica Directa -->
    <link rel="stylesheet" href="Styles/Vistas/Cajero/caja_punto_venta.css?v=<?= filemtime(__DIR__ . '/../../Styles/Vistas/Cajero/caja_punto_venta.css') ?>">
 
    <script>
        var TOKEN_SESION = "<?= htmlspecialchars($sesionCaja['token_emparejamiento'] ?? '') ?>";
        var CAJA_ID = "<?= htmlspecialchars($sesionCaja['caja_id'] ?? 'CAJA-01') ?>";
        var estadoActualConexion = <?= json_encode($sesionCaja['estado_conexion'] ?? 'esperando') ?>;
        var metodoPagoSeleccionado = 'efectivo';
        var totalGeneralVenta = 0;

        
        var clienteDniActual = '00000000';
        var clienteNombreActual = 'Público General';
        var ultimoTicketData = null;
        var accesoCajaPermitido = false;

        function inicializarPOS() {
            const scannerFullUrl = window.location.origin + window.location.pathname + `?action=pos_escaner_movil&token=${encodeURIComponent(TOKEN_SESION)}`;
            const linkEscaner = document.getElementById('btnLinkEscanerMovil');
            if (linkEscaner) {
                linkEscaner.href = scannerFullUrl;
            }

            document.getElementById('lblTokenSesion').textContent = TOKEN_SESION.substring(0, 12) + '...';

            const renderizarQR = () => {
                if (typeof QRCode === 'undefined') return;
                const qrContainer = document.getElementById('qrcode');
                if (!qrContainer) return;
                qrContainer.innerHTML = '';
                new QRCode(qrContainer, {
                    text: scannerFullUrl,
                    width: 180,
                    height: 180,
                    colorDark: '#0f172a',
                    colorLight: '#ffffff',
                    correctLevel: QRCode.CorrectLevel.M
                });
            };

            if (typeof QRCode === 'undefined') {
                const qrContainer = document.getElementById('qrcode');
                if (qrContainer) qrContainer.textContent = 'Abre el enlace manual si el QR no está disponible.';
                if (!window.posQrLoading) {
                    window.posQrLoading = true;
                    const qrScript = document.createElement('script');
                    qrScript.src = 'https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js';
                    qrScript.onload = () => {
                        window.posQrLoading = false;
                        renderizarQR();
                    };
                    qrScript.onerror = () => { window.posQrLoading = false; };
                    document.head.appendChild(qrScript);
                }
            } else {
                renderizarQR();
            }

            document.getElementById('btnEntrarCaja')?.addEventListener('click', omitirLockQR);
            document.getElementById('btnCerrarConexionEscaner')?.addEventListener('click', cerrarModalConexionEscaner);

            verificarEstadoLock();
            iniciarPollingCaja();
            iniciarPollingEscaneosMovil();
            cargarItemsPOS();
            try {
                ultimoTicketData = JSON.parse(sessionStorage.getItem('caserita_pos_ultimo_ticket') || 'null');
                document.getElementById('btnImprimirVentaAnterior').disabled = !ultimoTicketData;
                document.getElementById('btnImprimirVentaAnterior').style.opacity = ultimoTicketData ? '1' : '.55';
            } catch (error) {
                ultimoTicketData = null;
            }

            
            document.addEventListener('keydown', (e) => {
                if (e.key === 'F2') {
                    e.preventDefault();
                    document.getElementById('inputBuscarManual').focus();
                } else if (e.key === 'F9') {
                    e.preventDefault();
                    procesarVentaCaja();
                }
            });

            
            setupSearchManual();
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', inicializarPOS, { once: true });
        } else {
            inicializarPOS();
        }

        var previoItemsCount = -1;
        var previoTotal = -1;
        var audioPosCtx = null;

        function playDingSound() {
            try {
                if (!audioPosCtx) {
                    audioPosCtx = new (window.AudioContext || window.webkitAudioContext)();
                }
                if (audioPosCtx.state === 'suspended') {
                    audioPosCtx.resume();
                }
                const osc = audioPosCtx.createOscillator();
                const gain = audioPosCtx.createGain();
                osc.connect(gain);
                gain.connect(audioPosCtx.destination);
                osc.type = 'sine';
                osc.frequency.setValueAtTime(1760, audioPosCtx.currentTime);
                osc.frequency.setValueAtTime(2637, audioPosCtx.currentTime + 0.08);
                gain.gain.setValueAtTime(0.25, audioPosCtx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.001, audioPosCtx.currentTime + 0.22);
                osc.start();
                osc.stop(audioPosCtx.currentTime + 0.23);
            } catch(e) {}
        }

        function mostrarNotificacionCaja(msg) {
            let toast = document.getElementById('posSyncToast');
            if (!toast) {
                toast = document.createElement('div');
                toast.id = 'posSyncToast';
                toast.style.cssText = 'position:fixed; top:20px; right:20px; background:#15803D; color:#fff; padding:12px 20px; border-radius:12px; font-weight:700; font-size:0.92rem; z-index:999999; box-shadow:0 8px 25px rgba(0,0,0,0.25); display:flex; align-items:center; gap:10px; transition:all 0.3s; transform:translateY(-50px); opacity:0; pointer-events:none;';
                document.body.appendChild(toast);
            }
            toast.innerHTML = `<i class="fa-solid fa-mobile-screen-button" style="font-size:1.2rem; color:#ffca3a;"></i> ${escapeHtml(msg)}`;
            toast.style.transform = 'translateY(0)';
            toast.style.opacity = '1';
            clearTimeout(window.toastSyncTimer);
            window.toastSyncTimer = setTimeout(() => {
                toast.style.transform = 'translateY(-50px)';
                toast.style.opacity = '0';
            }, 2500);
        }

        function omitirLockQR() {
            accesoCajaPermitido = true;
            verificarEstadoLock();
        }

        function abrirModalConexionEscaner() {
            if (estadoActualConexion !== 'conectado') {
                document.getElementById('modalLockQR').classList.add('active');
            }
        }

        function cerrarModalConexionEscaner() {
            accesoCajaPermitido = true;
            document.getElementById('modalLockQR').classList.remove('active');
        }

        function abrirLectorEnCelular() {
            mostrarNotificacionCaja('Conectando y abriendo escáner en tu celular...');
            const formData = new FormData();
            formData.append('token', TOKEN_SESION);
            fetch('index.php?action=pos_abrir_lector_celular', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.ok) {
                        mostrarNotificacionCaja(data.ya_conectado ? 'Celular conectado y listo' : 'Solicitud enviada al celular; esperando conexión');
                    } else {
                        alert(data.mensaje || 'No se pudo abrir automáticamente en el celular. Puedes abrir el enlace manualmente.');
                    }
                })
                .catch(() => mostrarNotificacionCaja('No se pudo conectar con el celular. Revisa la red y vuelve a intentar.'));
        }

        function verificarEstadoLock() {
            const modal = document.getElementById('modalLockQR');
            const badge = document.getElementById('statusBadge');
            const badgeText = document.getElementById('statusText');

            if (estadoActualConexion === 'conectado') {
                accesoCajaPermitido = true;
                modal.classList.remove('active');
                badge.classList.add('connected');
                badgeText.textContent = 'Escáner conectado';
            } else {
                modal.classList.toggle('active', !accesoCajaPermitido);
                badge.classList.remove('connected');
                badgeText.textContent = 'Conecta el celular para escanear';
            }
        }

        // Polling constante a 400ms para actualización en tiempo real desde el celular
        function iniciarPollingCaja() {
            if (window.posPollingTimer) clearInterval(window.posPollingTimer);
            window.posPollingTimer = setInterval(() => {
                if (!document.getElementById('posTableBody')) {
                    clearInterval(window.posPollingTimer);
                    window.posPollingTimer = null;
                    return;
                }
                fetch(`index.php?action=pos_estado_sesion&token=${TOKEN_SESION}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'success') {
                            if (data.estado_conexion !== estadoActualConexion) {
                                estadoActualConexion = data.estado_conexion;
                                verificarEstadoLock();
                            }

                            const items = data.items || [];
                            const total = parseFloat(data.total_venta || 0);

                            // Detectar si el celular agregó un nuevo producto
                            if (previoTotal !== -1 && (items.length > previoItemsCount || total > previoTotal)) {
                                playDingSound();
                                const ultimo = items[items.length - 1];
                                if (ultimo) {
                                    mostrarNotificacionCaja(`📱 Agregado desde Celular: ${ultimo.nombre} (x${ultimo.cantidad})`);
                                }
                            }
                            previoItemsCount = items.length;
                            previoTotal = total;

                            renderizarTablaItems(items, total);
                        }
                    })
                    .catch(err => console.error('Error polling POS:', err));
            }, 400);
        }

        function cargarItemsPOS() {
            fetch(`index.php?action=pos_estado_sesion&token=${TOKEN_SESION}`)
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        const items = data.items || [];
                        const total = parseFloat(data.total_venta || 0);
                        previoItemsCount = items.length;
                        previoTotal = total;
                        renderizarTablaItems(items, total);
                    }
                });
        }

            function iniciarPollingEscaneosMovil() {
                if (window.posScanQueueTimer) clearInterval(window.posScanQueueTimer);
                window.posScanQueueTimer = setInterval(() => {
                    if (!document.getElementById('posTableBody')) {
                        clearInterval(window.posScanQueueTimer);
                        window.posScanQueueTimer = null;
                        return;
                    }
                    if (window.posScanQueueBusy) return;
                    window.posScanQueueBusy = true;
                    fetch(`index.php?action=pos_escaneos_pendientes&token=${encodeURIComponent(TOKEN_SESION)}`)
                        .then(res => res.json())
                        .then(async res => {
                            if (res.status === 'success' && Array.isArray(res.data)) {
                                for (const item of res.data) {
                                    if (item.codigo_escaneado) await procesarEscaneoMovil(item.codigo_escaneado);
                                }
                            }
                        })
                        .catch(() => {})
                        .finally(() => { window.posScanQueueBusy = false; });
                }, 500);
            }

            function procesarEscaneoMovil(codigo) {
                const campo = document.activeElement;
                const esCampoTexto = campo && /^(INPUT|TEXTAREA)$/.test(campo.tagName)
                    && !campo.disabled && !campo.readOnly
                    && (campo.tagName === 'TEXTAREA' || !['button', 'submit', 'reset', 'checkbox', 'radio', 'file', 'range', 'color', 'hidden', 'image'].includes(campo.type));

                if (campo?.id === 'inputBuscarManual') {
                    campo.value = codigo;
                    campo.dispatchEvent(new Event('input', { bubbles: true }));
                    return escanearCodigo(codigo).finally(() => {
                        campo.value = '';
                        campo.dispatchEvent(new Event('input', { bubbles: true }));
                        document.getElementById('posSearchDropdown')?.classList.remove('active');
                    });
                }

                if (esCampoTexto) {
                    campo.value = codigo;
                    campo.dispatchEvent(new Event('input', { bubbles: true }));
                    campo.dispatchEvent(new Event('change', { bubbles: true }));
                    return Promise.resolve();
                }

                return escanearCodigo(codigo);
            }

        function renderizarTablaItems(items, total) {
            totalGeneralVenta = parseFloat(total || 0);
            const tbody = document.getElementById('posTableBody');
            if (!tbody) return;

            if (!items || items.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6" style="text-align:center; padding: 40px 10px; color:#64748b;">
                            <i class="fa-solid fa-barcode" style="font-size: 2.5rem; margin-bottom:10px; display:block;"></i>
                            Escanea un código de barras con la cámara o busca un producto manualmente.
                        </td>
                    </tr>`;
                actualizarTotalesUI(0);
                return;
            }

            let html = '';
            items.forEach(item => {
                html += `
                    <tr>
                        <td><code style="color:var(--pos-primary); font-weight:700;">${item.codigo}</code></td>
                        <td><strong>${item.nombre}</strong></td>
                        <td>S/ ${parseFloat(item.precio).toFixed(2)}</td>
                        <td>
                            <div class="pos-qty-control">
                                <button type="button" class="btn-qty" onclick="cambiarCantidad(${item.id_producto}, ${item.cantidad - 1})">-</button>
                                <span style="padding:0 6px; font-weight:700;">${item.cantidad}</span>
                                <button type="button" class="btn-qty" onclick="cambiarCantidad(${item.id_producto}, ${item.cantidad + 1})">+</button>
                            </div>
                        </td>
                        <td style="font-weight:800; color:var(--pos-primary);">S/ ${parseFloat(item.subtotal).toFixed(2)}</td>
                        <td style="text-align:center;">
                            <button type="button" class="btn-remove-item" onclick="cambiarCantidad(${item.id_producto}, 0)" title="Eliminar ítem">
                                <i class="fa-solid fa-trash-can"></i>
                            </button>
                        </td>
                    </tr>`;
            });

            tbody.innerHTML = html;
            actualizarTotalesUI(totalGeneralVenta);
        }

        function actualizarTotalesUI(total) {
            const igv = total * 0.18;
            const subtotal = total - igv;

            document.getElementById('lblSubtotal').textContent = `S/ ${subtotal.toFixed(2)}`;
            document.getElementById('lblIgv').textContent = `S/ ${igv.toFixed(2)}`;
            document.getElementById('lblTotal').textContent = `S/ ${total.toFixed(2)}`;

            calcularVueltas();
        }

        function cambiarCantidad(idProducto, nuevaCant) {
            const formData = new FormData();
            formData.append('token', TOKEN_SESION);
            formData.append('id_producto', idProducto);
            formData.append('cantidad', nuevaCant);

            fetch('index.php?action=pos_actualizar_cantidad', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    renderizarTablaItems(data.data.items, data.data.total);
                }
            });
        }

        function seleccionarMetodoPago(metodo) {
            metodoPagoSeleccionado = metodo;
            document.querySelectorAll('.payment-btn').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.method === metodo);
            });

            const boxEfectivo = document.getElementById('boxMontoRecibido');
            boxEfectivo.style.display = (metodo === 'efectivo') ? 'block' : 'none';

            if (metodo === 'yape_plin') {
                abrirModalYapePlin();
            }
        }

        function abrirModalYapePlin() {
            document.getElementById('modalYapePlinPOS').style.display = 'flex';
        }

        function cerrarModalYapePlin() {
            document.getElementById('modalYapePlinPOS').style.display = 'none';
        }

        function calcularVueltas() {
            if (metodoPagoSeleccionado !== 'efectivo') return;
            const recibido = parseFloat(document.getElementById('inputMontoRecibido').value || 0);
            const cambio = recibido - totalGeneralVenta;
            document.getElementById('inputVueltas').value = `S/ ${Math.max(0, cambio).toFixed(2)}`;
        }

        function setClienteGenerico() {
            clienteDniActual = '00000000';
            clienteNombreActual = 'Público General';
            document.getElementById('inputClienteDni').value = '00000000';
            document.getElementById('lblClienteNombre').textContent = 'Público General';
            document.getElementById('lblClienteNombre').style.color = '#0f172a';
        }

        function consultarDniCajero() {
            const input = document.getElementById('inputClienteDni');
            const dni = input.value.trim();
            const icon = document.getElementById('iconBuscarDni');
            const lbl = document.getElementById('lblClienteNombre');

            if (!dni || dni === '00000000' || dni === '99999999') {
                setClienteGenerico();
                return;
            }

            if (!/^\d{8}(\d{3})?$/.test(dni)) {
                alert('Ingresa un DNI de 8 dígitos o un RUC de 11 dígitos.');
                return;
            }

            icon.className = 'fa-solid fa-spinner fa-spin';
            clienteDniActual = dni;
            clienteNombreActual = '';
            lbl.textContent = dni.length === 11 ? 'Consultando SUNAT...' : 'Consultando RENIEC...';
            lbl.style.color = 'var(--pos-primary)';

            fetch(`index.php?action=pos_consultar_dni&documento=${encodeURIComponent(dni)}`)
                .then(res => res.json())
                .then(data => {
                    icon.className = 'fa-solid fa-magnifying-glass';
                    if (data && data.ok) {
                        clienteDniActual = data.dni || dni;
                        const nombreEncontrado = data.nombre_completo || data.razon_social || data.nombre || '';
                        if (nombreEncontrado.trim().length > 0) {
                            clienteNombreActual = nombreEncontrado.trim();
                            lbl.textContent = clienteNombreActual;
                            lbl.style.color = '#15803d';
                        } else {
                            lbl.textContent = data.mensaje || 'No se encontraron datos. Ingresa el nombre manualmente.';
                            lbl.style.color = '#d97706';
                            setTimeout(() => { editarNombreClienteManual(); }, 300);
                        }
                    } else {
                        lbl.textContent = data.mensaje || (dni.length === 11 ? 'No se encontró información en SUNAT.' : 'No se encontró información en RENIEC.');
                        lbl.style.color = '#dc2626';
                        setTimeout(() => { editarNombreClienteManual(); }, 300);
                    }
                })
                .catch(err => {
                    console.error('Error al consultar DNI:', err);
                    icon.className = 'fa-solid fa-magnifying-glass';
                    lbl.textContent = 'Error de conexión. Ingrese nombre manual.';
                    lbl.style.color = '#dc2626';
                    setTimeout(() => { editarNombreClienteManual(); }, 300);
                });
        }

        function editarNombreClienteManual() {
            const actual = clienteNombreActual === 'Público General' ? '' : clienteNombreActual;
            const nuevo = prompt('Ingresa el Nombre Completo o Razón Social del Cliente:', actual);
            if (nuevo !== null && nuevo.trim().length > 0) {
                clienteNombreActual = nuevo.trim();
                document.getElementById('lblClienteNombre').textContent = clienteNombreActual;
                document.getElementById('lblClienteNombre').style.color = '#0f172a';
            }
        }

        var productosEnDropdown = [];
        var dropdownSelectedIndex = -1;

        function setupSearchManual() {
            const input = document.getElementById('inputBuscarManual');
            const dropdown = document.getElementById('posSearchDropdown');
            let debounceTimer = null;

            input.addEventListener('input', () => {
                const query = input.value.trim();
                clearTimeout(debounceTimer);
                dropdownSelectedIndex = -1;

                if (query.length < 1) {
                    productosEnDropdown = [];
                    dropdown.innerHTML = '';
                    dropdown.classList.remove('active');
                    return;
                }

                debounceTimer = setTimeout(() => {
                    fetch(`index.php?action=pos_buscar_productos&q=${encodeURIComponent(query)}`)
                        .then(res => res.json())
                        .then(data => {
                            if (data.status === 'success' && data.data && data.data.length > 0) {
                                productosEnDropdown = data.data;
                                renderDropdownItems(data.data);
                            } else {
                                productosEnDropdown = [];
                                dropdown.innerHTML = `
                                    <div class="pos-search-no-results" style="padding: 12px 16px; color: #64748b; font-size: 0.85rem; display: flex; align-items: center; gap: 8px;">
                                        <i class="fa-solid fa-circle-exclamation"></i> No se encontraron productos coincidentes
                                    </div>`;
                                dropdown.classList.add('active');
                            }
                        })
                        .catch(err => {
                            console.error('Error buscando productos:', err);
                            productosEnDropdown = [];
                        });
                }, 180);
            });

            input.addEventListener('keydown', (e) => {
                if (dropdown.classList.contains('active') && productosEnDropdown.length > 0) {
                    if (e.key === 'ArrowDown') {
                        e.preventDefault();
                        dropdownSelectedIndex = (dropdownSelectedIndex + 1) % productosEnDropdown.length;
                        actualizarHighlightDropdown();
                        return;
                    }
                    if (e.key === 'ArrowUp') {
                        e.preventDefault();
                        dropdownSelectedIndex = (dropdownSelectedIndex - 1 + productosEnDropdown.length) % productosEnDropdown.length;
                        actualizarHighlightDropdown();
                        return;
                    }
                    if (e.key === 'Escape') {
                        dropdown.classList.remove('active');
                        return;
                    }
                }

                if (e.key === 'Enter') {
                    e.preventDefault();
                    if (dropdown.classList.contains('active') && productosEnDropdown.length > 0) {
                        const targetIdx = dropdownSelectedIndex >= 0 ? dropdownSelectedIndex : 0;
                        seleccionarProductoPorIndice(targetIdx);
                        return;
                    }

                    const val = input.value.trim();
                    if (val !== '') {
                        escanearCodigo(val);
                        input.value = '';
                        dropdown.classList.remove('active');
                    }
                }
            });

            document.addEventListener('click', (e) => {
                if (!input.contains(e.target) && !dropdown.contains(e.target)) {
                    dropdown.classList.remove('active');
                }
            });
        }

        function actualizarHighlightDropdown() {
            const items = document.querySelectorAll('.pos-search-item');
            items.forEach((it, idx) => {
                if (idx === dropdownSelectedIndex) {
                    it.classList.add('selected');
                    it.style.background = '#dcfce7';
                    it.scrollIntoView({ block: 'nearest' });
                } else {
                    it.classList.remove('selected');
                    it.style.background = '';
                }
            });
        }

        function renderDropdownItems(productos) {
            const dropdown = document.getElementById('posSearchDropdown');
            let html = '';

            productos.forEach((prod, idx) => {
                const cod = prod.codigo_barras || prod.codigo || ('ID: ' + prod.id_producto);
                const nombreProducto = prod.nombre || prod.titulo || 'Producto';
                const sku = prod.codigo || '';
                const codigoBarras = prod.codigo_barras || '';
                const codigos = [sku ? `SKU: ${sku}` : '', codigoBarras ? `Barras: ${codigoBarras}` : '']
                    .filter(Boolean).join(' · ') || `ID: ${prod.id_producto}`;
                const stock = prod.stock !== undefined ? parseInt(prod.stock) : 0;
                const stockBadge = stock > 0 
                    ? `<span class="pos-stock-badge in-stock" style="background:#dcfce7; color:#15803d; font-size:0.75rem; padding:2px 8px; border-radius:12px; font-weight:700;">${stock} disponibles</span>` 
                    : `<span class="pos-stock-badge out-stock" style="background:#fee2e2; color:#dc2626; font-size:0.75rem; padding:2px 8px; border-radius:12px; font-weight:700;">Agotado</span>`;

                html += `
                    <div class="pos-search-item" data-index="${idx}" onclick="seleccionarProductoPorIndice(${idx})" style="padding:10px 14px; display:flex; justify-content:space-between; align-items:center; cursor:pointer; border-bottom:1px solid #f1f5f9; transition:background 0.15s ease;">
                        <div class="pos-search-item-info" style="display:flex; flex-direction:column; gap:2px;">
                            <strong class="pos-search-item-name" style="font-size:0.9rem; color:#0f172a;">${escapeHtml(nombreProducto)}</strong>
                            <small class="pos-search-item-code" style="font-size:0.75rem; color:#64748b;">${escapeHtml(codigos)}</small>
                        </div>
                        <div class="pos-search-item-price-stock" style="display:flex; flex-direction:column; align-items:flex-end; gap:4px;">
                            <span class="pos-search-item-price" style="font-size:0.95rem; font-weight:800; color:#15803d;">S/ ${parseFloat(prod.precio).toFixed(2)}</span>
                            ${stockBadge}
                        </div>
                    </div>`;
            });

            dropdown.innerHTML = html;
            dropdown.classList.add('active');
        }

        function seleccionarProductoDropdown(idOcodigo) {
            const input = document.getElementById('inputBuscarManual');
            const dropdown = document.getElementById('posSearchDropdown');
            escanearCodigo(idOcodigo, true);
            input.value = '';
            dropdown.classList.remove('active');
            dropdownSelectedIndex = -1;
            productosEnDropdown = [];
            setTimeout(() => input.focus(), 80);
        }

        function seleccionarProductoPorIndice(index) {
            const producto = productosEnDropdown[index];
            if (!producto) return;
            seleccionarProductoDropdown(String(producto.id_producto));
        }

        function escapeHtml(str) {
            if (!str) return '';
            return String(str)
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

            function mostrarAvisoProductoPOS(mensaje) {
                document.getElementById('posProductNotice')?.remove();
                const overlay = document.createElement('div');
                overlay.id = 'posProductNotice';
                overlay.className = 'pos-product-notice';
                overlay.setAttribute('role', 'alertdialog');
                overlay.setAttribute('aria-live', 'assertive');
                const panel = document.createElement('div');
                panel.className = 'pos-product-notice-panel';
                panel.tabIndex = 0;
                panel.innerHTML = '<i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i><strong>Producto no encontrado</strong>';
                const detail = document.createElement('span');
                detail.textContent = mensaje || 'Revisa el código o el nombre e inténtalo de nuevo.';
                panel.appendChild(detail);
                overlay.appendChild(panel);
                document.body.appendChild(overlay);
                let remaining = 1800;
                let startedAt = Date.now();
                let removalTimer = window.setTimeout(() => overlay.remove(), remaining);
                const pauseNotice = () => {
                    if (!removalTimer) return;
                    window.clearTimeout(removalTimer);
                    removalTimer = null;
                    remaining = Math.max(0, remaining - (Date.now() - startedAt));
                };
                const resumeNotice = () => {
                    if (removalTimer || remaining <= 0) return;
                    startedAt = Date.now();
                    removalTimer = window.setTimeout(() => overlay.remove(), remaining);
                };
                panel.addEventListener('pointerenter', pauseNotice);
                panel.addEventListener('pointerleave', resumeNotice);
                panel.addEventListener('focusin', pauseNotice);
                panel.addEventListener('focusout', event => {
                    if (!panel.contains(event.relatedTarget)) resumeNotice();
                });
            }

        function escanearCodigo(codigo, esIdProducto = false) {
            const formData = new FormData();
            formData.append('token', TOKEN_SESION);
            formData.append(esIdProducto ? 'id_producto' : 'codigo_barras', codigo);

            return fetch('index.php?action=pos_escanear_producto', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    renderizarTablaItems(data.items, data.total);
                } else {
                    mostrarAvisoProductoPOS(data.message);
                }
            })
            .catch(() => mostrarAvisoProductoPOS('No se pudo consultar el producto. Revisa la conexión.'));
        }

        function mostrarAvisoValidacionCaja(mensaje) {
            const modal = document.getElementById('posValidationModal');
            const message = document.getElementById('posValidationMessage');
            if (!modal || !message) return;
            message.textContent = mensaje;
            modal.classList.add('active');
            modal.setAttribute('aria-hidden', 'false');
        }

        function cerrarAvisoValidacionCaja() {
            const modal = document.getElementById('posValidationModal');
            if (!modal) return;
            modal.classList.remove('active');
            modal.setAttribute('aria-hidden', 'true');
        }

        function procesarVentaCaja() {
            if (totalGeneralVenta <= 0) {
                alert('No hay productos en la orden de cobro.');
                return;
            }

            if (!['00000000', '99999999'].includes(clienteDniActual) && !clienteNombreActual.trim()) {
                alert('Consulta el DNI/RUC o ingresa manualmente el nombre del cliente antes de cobrar.');
                editarNombreClienteManual();
                if (!clienteNombreActual.trim()) return;
            }

            const recibido = parseFloat(document.getElementById('inputMontoRecibido').value || 0);
            if (metodoPagoSeleccionado === 'efectivo' && recibido < totalGeneralVenta) {
                mostrarAvisoValidacionCaja(`Recibiste S/ ${recibido.toFixed(2)} y el total de la venta es S/ ${totalGeneralVenta.toFixed(2)}. Ingresa un monto igual o mayor para continuar.`);
                return;
            }

            if (!confirm(`¿Confirmar venta por un total de S/ ${totalGeneralVenta.toFixed(2)} con pago en ${metodoPagoSeleccionado.toUpperCase()}?`)) {
                return;
            }

            const formData = new FormData();
            formData.append('token', TOKEN_SESION);
            formData.append('metodo_pago', metodoPagoSeleccionado);
            formData.append('monto_recibido', recibido);
            formData.append('cliente_dni', clienteDniActual);
            formData.append('cliente_nombre', clienteNombreActual);

            fetch('index.php?action=pos_procesar_venta', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    ultimoTicketData = data;
                    try { sessionStorage.setItem('caserita_pos_ultimo_ticket', JSON.stringify(data)); } catch (error) {}
                    document.getElementById('btnImprimirVentaAnterior').disabled = false;
                    document.getElementById('btnImprimirVentaAnterior').style.opacity = '1';
                    mostrarTicketTermico(data);
                    document.getElementById('inputMontoRecibido').value = '';
                    document.getElementById('inputVueltas').value = 'S/ 0.00';
                    cargarItemsPOS();
                } else {
                    alert(data.message);
                }
            });
        }

        function mostrarTicketTermico(data) {
            const numeroDocumento = data.cliente_dni || clienteDniActual;
            const metodoTicket = (data.metodo_pago || metodoPagoSeleccionado).toLowerCase();
            document.getElementById('lblTicketTipoDoc').textContent = numeroDocumento.length === 11 ? 'FACTURA DE VENTA' : 'BOLETA DE VENTA';
            document.getElementById('lblTicketCodigo').textContent = data.codigo_pedido || `POS-${data.id_pedido}`;
            document.getElementById('lblTicketFecha').textContent = data.fecha || new Date().toLocaleString();
            document.getElementById('lblTicketCajero').textContent = data.cajero || 'Cajero de Turno';
            document.getElementById('lblTicketCliente').textContent = data.cliente_nombre || clienteNombreActual;
            document.getElementById('lblTicketDni').textContent = numeroDocumento;
            document.getElementById('lblTicketTotal').textContent = `S/ ${parseFloat(data.total).toFixed(2)}`;
            document.getElementById('lblTicketSubtotal').textContent = `S/ ${parseFloat(data.subtotal || data.total / 1.18).toFixed(2)}`;
            document.getElementById('lblTicketIgv').textContent = `S/ ${parseFloat(data.igv || (data.total - (data.total / 1.18))).toFixed(2)}`;
            document.getElementById('lblTicketMetodo').textContent = metodoTicket.toUpperCase();

            const rowEfectivo = document.getElementById('rowTicketEfectivo');
            if (metodoTicket === 'efectivo') {
                rowEfectivo.style.display = 'block';
                document.getElementById('lblTicketRecibido').textContent = `S/ ${parseFloat(data.monto_recibido || 0).toFixed(2)}`;
                document.getElementById('lblTicketVuelto').textContent = `S/ ${parseFloat(data.vueltas || 0).toFixed(2)}`;
            } else {
                rowEfectivo.style.display = 'none';
            }

            const tbody = document.getElementById('ticketTbodyItems');
            let rows = '';
            if (data.items && Array.isArray(data.items)) {
                data.items.forEach(it => {
                    rows += `
                        <tr>
                            <td style="padding:3px 0;">${it.cantidad}</td>
                            <td style="padding:3px 0;">${escapeHtml(it.nombre)}</td>
                            <td style="padding:3px 0; text-align:right;">${parseFloat(it.precio).toFixed(2)}</td>
                            <td style="padding:3px 0; text-align:right; font-weight:bold;">${parseFloat(it.subtotal).toFixed(2)}</td>
                        </tr>`;
                });
            }
            tbody.innerHTML = rows;

            document.getElementById('modalTicketVenta').style.display = 'flex';
        }

        function cerrarTicketVenta() {
            document.getElementById('modalTicketVenta').style.display = 'none';
        }

        function imprimirTicketTermico() {
            const ticketElement = document.getElementById('ticketTermicoImprimible');
            if (!ticketElement) {
                window.print();
                return;
            }

            let iframe = document.getElementById('posThermalPrintFrame');
            if (!iframe) {
                iframe = document.createElement('iframe');
                iframe.id = 'posThermalPrintFrame';
                iframe.style.position = 'fixed';
                iframe.style.right = '0';
                iframe.style.bottom = '0';
                iframe.style.width = '0';
                iframe.style.height = '0';
                iframe.style.border = '0';
                iframe.style.visibility = 'hidden';
                document.body.appendChild(iframe);
            }

            const doc = iframe.contentWindow.document;
            doc.open();
            doc.write(`<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Imprimir Comprobante</title>
    <link rel="stylesheet" href="${new URL('Styles/Vistas/Cajero/ticket_impresion.css', window.location.href).href}">
</head>
<body>
    ${ticketElement.innerHTML}
</body>
</html>`);
            doc.close();

            const imprimir = () => setTimeout(() => {
                try {
                    iframe.contentWindow.focus();
                    iframe.contentWindow.print();
                } catch (e) {
                    console.error('Error al imprimir ticket:', e);
                    window.print();
                }
            }, 200);
            const cssImpresion = doc.querySelector('link[rel="stylesheet"]');
            if (cssImpresion && !cssImpresion.sheet) {
                cssImpresion.addEventListener('load', imprimir, { once: true });
                cssImpresion.addEventListener('error', imprimir, { once: true });
            } else {
                imprimir();
            }
        }

        function imprimirVentaAnterior() {
            if (!ultimoTicketData) {
                alert('Todavía no hay una venta anterior guardada en esta PC.');
                return;
            }
            mostrarTicketTermico(ultimoTicketData);
            setTimeout(imprimirTicketTermico, 200);
        }

        function descargarTicketPDF() {
            const ticketElement = document.getElementById('ticketTermicoImprimible');
            const codigo = ultimoTicketData ? (ultimoTicketData.codigo_pedido || 'POS-Ticket') : 'Ticket';
            const opt = {
                margin:       [5, 5, 5, 5],
                filename:     `${codigo}.pdf`,
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2 },
                jsPDF:        { unit: 'mm', format: [80, 200], orientation: 'portrait' }
            };
            html2pdf().set(opt).from(ticketElement).save();
        }
    </script>
<?php if (!$isAjax && $isAdminPOS): ?>
            </div>
        </div>
    </div>
<?php endif; ?>
<?php if (!$isAjax): ?>
</body>
</html>
<?php endif; ?>

