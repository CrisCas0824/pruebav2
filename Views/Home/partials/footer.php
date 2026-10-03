<?php
/**
 * Footer Oscuro Canónico - La Caserita
 * Unificado para todas las vistas del proyecto (Home, Categorías, Detalle de Producto, etc.)
 */
?>
<footer class="caserita-footer-slate">
    <div class="footer-slate-container">
        <div class="footer-slate-top-grid">
            <!-- Columna 1: Identidad La Caserita -->
            <div>
                <div class="footer-brand-header">
                    <div class="header-brand-icon" style="width: 38px; height: 38px; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center; background: #15803d; color: #ffffff;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                            <polyline points="9 22 9 12 15 12 15 22"/>
                        </svg>
                    </div>
                    <h3>La Caserita</h3>
                </div>
                <p class="footer-slate-desc">
                    Tu minimarket de confianza. Calidad fresca, precios de barrio y atención amable justo en tu vecindario.
                </p>
            </div>

            <!-- Columna 2: Categorías -->
            <div>
                <h4 class="footer-col-orange-title">CATEGORÍAS</h4>
                <ul class="footer-slate-links">
                    <li><a href="index.php?action=ver_categoria&nombre=Abarrotes">Abarrotes & Despensa</a></li>
                    <li><a href="index.php?action=ver_categoria&nombre=Frutas">Frutas y Verduras</a></li>
                    <li><a href="index.php?action=ver_categoria&nombre=Lácteos">Lácteos & Bebidas</a></li>
                    <li><a href="index.php?action=ver_categoria&nombre=Limpieza">Limpieza & Hogar</a></li>
                </ul>
            </div>

            <!-- Columna 3: Ayuda -->
            <div>
                <h4 class="footer-col-orange-title">AYUDA</h4>
                <ul class="footer-slate-links">
                    <li><a href="javascript:void(0)" onclick="if(typeof mostrarModalAyuda==='function') mostrarModalAyuda('faq');">Preguntas Frecuentes</a></li>
                    <li><a href="javascript:void(0)" onclick="if(typeof mostrarModalAyuda==='function') mostrarModalAyuda('delivery');">Zonas de Delivery</a></li>
                    <li><a href="javascript:void(0)" onclick="if(typeof mostrarModalAyuda==='function') mostrarModalAyuda('terminos');">Términos y Condiciones</a></li>
                    <li><a href="javascript:void(0)" onclick="if(typeof abrirModalConfigCookies==='function') abrirModalConfigCookies();">Preferencias de Cookies</a></li>
                </ul>
            </div>

            <!-- Columna 4: Atención al Cliente -->
            <div>
                <h4 class="footer-col-orange-title">ATENCIÓN AL CLIENTE</h4>
                <div class="footer-phone-row">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                    </svg>
                    <span>+51 987 654 321</span>
                </div>
                <div class="footer-hours-text">
                    Lun a Dom: 7:00 AM - 10:00 PM
                </div>
            </div>
        </div>

        <!-- Fila Inferior con Derechos y Pasarelas de Pago -->
        <div class="footer-slate-bottom-divider">
            <div class="footer-copyright-text">
                © 2026 La Caserita Minimarket. Todos los derechos reservados.
            </div>
            <div class="footer-payments-text">
                Yape • Plin • Visa • Mastercard
            </div>
        </div>
    </div>
</footer>
