<div class="cart-overlay" id="cartOverlay"></div>
<aside
    class="cart-sidebar"
    id="cartSidebar"
    aria-hidden="true"
    aria-labelledby="cartTitle">
    
    <div class="cart-header">
        <div>
            <h2 id="cartTitle">Carrito</h2>
            <p class="cart-header-subtitle">
                Revisa tus productos agregados
            </p>
        </div>

        <button
            type="button"
            class="btn-close-cart"
            id="closeCart"
            aria-label="Cerrar carrito">
            &times;
        </button>
    </div>

    
    <div class="cart-content" id="cartContent">

        
        <div class="cart-empty" id="cartEmpty">
            <div class="cart-empty-icon">
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    width="46"
                    height="46"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.7"
                    stroke-linecap="round"
                    stroke-linejoin="round">
                    <circle cx="9" cy="20" r="1"></circle>
                    <circle cx="19" cy="20" r="1"></circle>
                    <path d="M3 3h2l2.5 11h10.8l2-7H7"></path>
                </svg>
            </div>

            <h3>Tu carrito está vacío</h3>

            <p>
                Agrega productos para comenzar tu compra.
            </p>

            <button
                type="button"
                class="cart-continue-btn"
                id="btnContinuarComprando">
                Seguir comprando
            </button>
        </div>

        
        <div class="cart-items" id="cartItems"></div>
    </div>

    
    <div class="cart-footer">
        <div class="cart-summary">

            <div class="summary-row">
                <span>Productos</span>
                <span id="cartQuantitySummary">0 productos</span>
            </div>

            <div class="summary-row">
                <span>Subtotal</span>
                <span id="cartSubtotal">S/ 0.00</span>
            </div>

            <div class="summary-row">
                <span>Entrega</span>
                <span id="cartDelivery">Por calcular</span>
            </div>

            <div class="summary-divider"></div>

            <div class="summary-row total">
                <span>Total</span>
                <span id="cartTotal">S/ 0.00</span>
            </div>
        </div>

        <button
            type="button"
            class="btn-checkout"
            id="btnVerCarrito"
            disabled>
            VER CARRITO
        </button>

        <!-- Botón Vaciar: aparece debajo de VER CARRITO solo cuando hay productos -->
        <button
            type="button"
            id="btnVaciarCarrito"
            class="btn-vaciar-footer"
            style="display:none;"
            onclick="if(typeof vaciarCarritoCompleto==='function') vaciarCarritoCompleto()">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="3 6 5 6 21 6"></polyline>
                <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path>
                <path d="M10 11v6"></path>
                <path d="M14 11v6"></path>
                <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"></path>
            </svg>
            Vaciar carrito
        </button>
    </div>
</aside>

<div class="cart-confirm-overlay" id="cartConfirmOverlay" aria-hidden="true">
    <section class="cart-confirm-dialog" role="dialog" aria-modal="true" aria-labelledby="cartConfirmTitle" aria-describedby="cartConfirmMessage">
        <div class="cart-confirm-icon" id="cartConfirmIcon" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"></path><path d="M8 6V4h8v2"></path><path d="m19 6-1 14H6L5 6"></path></svg>
        </div>
        <h2 id="cartConfirmTitle">Confirmar acción</h2>
        <p id="cartConfirmMessage"></p>
        <div class="cart-confirm-actions">
            <button type="button" class="cart-confirm-cancel" id="cartConfirmCancel">Cancelar</button>
            <button type="button" class="cart-confirm-accept" id="cartConfirmAccept">Confirmar</button>
        </div>
    </section>
</div>