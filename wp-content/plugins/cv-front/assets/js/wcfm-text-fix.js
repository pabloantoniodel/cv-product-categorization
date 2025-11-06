/**
 * Fix para corregir textos duplicados en menú WCFM
 */
(function($) {
    'use strict';
    
    function fixWCFMTexts() {
        // Buscar el elemento "Vendedor Vendedores" y cambiarlo a solo "Vendedores"
        $('.wcfm_menu_wcfm-vendors .text').each(function() {
            var text = $(this).text().trim();
            // Limpiar espacios extra y saltos de línea
            text = text.replace(/\s+/g, ' ');
            
            if (text.indexOf('Vendedor') !== -1 && text.indexOf('Vendedores') !== -1) {
                $(this).text('Vendedores');
                console.log('✅ CV Front: Texto menú WCFM corregido: "' + text + '" → "Vendedores"');
            }
        });
    }
    
    // Ejecutar múltiples veces para asegurar que se aplique
    $(document).ready(function() {
        fixWCFMTexts();
        setTimeout(fixWCFMTexts, 500);
        setTimeout(fixWCFMTexts, 1000);
        setTimeout(fixWCFMTexts, 2000);
    });
    
    $(window).on('load', function() {
        fixWCFMTexts();
    });
    
})(jQuery);

