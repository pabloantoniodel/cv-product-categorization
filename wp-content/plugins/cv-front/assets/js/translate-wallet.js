/**
 * Traducir textos del Wallet en tiempo real
 */
(function($) {
    'use strict';
    
    $(document).ready(function() {
        console.log('🌐 CV Front: Traductor de Wallet inicializado');
        
        // Función para traducir textos
        function translateWalletTexts() {
            // Buscar el div con el mensaje de cashback
            $('.woocommerce-info, .woocommerce-message, .wc-block-components-notice-banner').each(function() {
                var $element = $(this);
                var html = $element.html();
                
                if (!html) return;
                
                // Realizar traducciones
                var translations = {
                    'Please': 'Por favor',
                    '>log in</a>': '>inicia sesión</a>',
                    'to avail': 'para recibir',
                    'cashback from this order': 'de cashback de este pedido',
                    'cashback from this order.': 'de cashback de este pedido.',
                    'Add to cart': 'Añadir al carrito',
                    'Update cart': 'Actualizar carrito',
                    'Apply coupon': 'Aplicar cupón',
                    'Proceed to checkout': 'Proceder al pago',
                    'Continue shopping': 'Continuar comprando'
                };
                
                var modified = false;
                for (var original in translations) {
                    if (html.indexOf(original) !== -1) {
                        html = html.replace(new RegExp(original, 'g'), translations[original]);
                        modified = true;
                    }
                }
                
                if (modified) {
                    $element.html(html);
                    console.log('✅ CV Front: Texto del Wallet traducido');
                }
            });
        }
        
        // Ejecutar traducción al cargar
        translateWalletTexts();
        
        // Re-ejecutar cuando se actualice el carrito (AJAX)
        $(document.body).on('updated_cart_totals updated_checkout', function() {
            setTimeout(translateWalletTexts, 100);
        });
        
        // Observer para detectar cambios dinámicos
        var observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.addedNodes.length) {
                    translateWalletTexts();
                }
            });
        });
        
        // Observar cambios en el body
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    });
})(jQuery);

