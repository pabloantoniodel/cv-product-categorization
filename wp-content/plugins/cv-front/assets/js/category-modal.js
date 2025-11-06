/**
 * Reescribir enlaces de categorías en Market
 * Cambia de /product-category/X/ a /?product_cat=X&post_type=product&s=
 * El formato de búsqueda muestra productos correctamente (no tiene problemas de WCFM)
 */
(function($) {
    'use strict';
    
    $(document).ready(function() {
        console.log('📂 CV Front: Category Links Fixer inicializado');
        
        // Solo ejecutar si hay .product-categories
        if ($('.product-categories').length === 0) {
            console.log('ℹ️ CV Front: No hay .product-categories, no activo');
            return;
        }
        
        console.log('📂 CV Front: .product-categories detectado (' + $('.product-categories').length + ' encontrado)');
        
        // Reescribir TODOS los enlaces de categorías al formato de búsqueda
        var linksChanged = 0;
        $('.product-categories a').each(function() {
            var $link = $(this);
            var originalUrl = $link.attr('href');
            
            // Extraer slug de la URL
            var categorySlug = originalUrl.match(/product-category\/([^\/]+)/);
            
            if (categorySlug && categorySlug[1]) {
                // Construir URL en formato de búsqueda (funciona correctamente)
                var newUrl = '/?product_cat=' + categorySlug[1] + '&post_type=product&s=';
                
                // Cambiar el href
                $link.attr('href', newUrl);
                
                linksChanged++;
                console.log('🔗 CV Front: ' + categorySlug[1] + ' → ' + newUrl);
            }
        });
        
        console.log('✅ CV Front: ' + linksChanged + ' enlaces reescritos al formato de búsqueda');
        console.log('💡 CV Front: Formato: /?product_cat=X&post_type=product&s=');
        console.log('✅ CV Front: Ahora los clics en categorías funcionarán correctamente');
    });
})(jQuery);
