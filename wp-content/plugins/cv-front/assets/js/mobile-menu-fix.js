/**
 * Fix para evitar scroll al abrir menú móvil
 */
(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Solo guardar posición de scroll, NO prevenir el comportamiento del menú
        var savedScrollPosition = 0;
        
        $('.menu-toggle, button.menu-toggle').on('click', function(e) {
            // Guardar posición actual
            savedScrollPosition = $(window).scrollTop();
            
            // Restaurar posición después de que el menú se abra
            setTimeout(function() {
                $(window).scrollTop(savedScrollPosition);
            }, 50);
        });
        
        console.log('✅ CV Front: Fix de scroll del menú móvil aplicado (sin preventDefault)');
    });
})(jQuery);

