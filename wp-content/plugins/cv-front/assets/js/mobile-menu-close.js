/**
 * Botón de cierre del menú móvil
 */
(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Esperar a que el DOM esté completamente cargado
        setTimeout(function() {
            // Añadir botón de cierre (X) arriba
            if ($('#mobile-menu-wrapper').length && !$('.cv-mobile-menu-close').length) {
                $('#mobile-menu-wrapper').prepend('<button class="cv-mobile-menu-close" aria-label="Cerrar menú"><i class="fa fa-times"></i></button>');
                console.log('✅ CV Front: Botón X añadido');
            }
            
            // Añadir logo de la tienda debajo del botón cerrar
            if ($('#mobile-menu-wrapper').length && !$('.cv-mobile-menu-logo').length) {
                // Priorizar data-src (lazy load) sobre src
                var logoUrl = $('.custom-logo').attr('data-src') || $('.custom-logo').attr('src') || $('.site-logo img').attr('data-src') || $('.site-logo img').attr('src') || '';
                
                // Evitar imágenes base64 vacías
                if (logoUrl && !logoUrl.startsWith('data:image/png;base64,iVBORw0KGgo')) {
                    $('#mobile-menu-wrapper').append('<div class="cv-mobile-menu-logo"><img src="' + logoUrl + '" alt="Ciudad Virtual Marketplace"></div>');
                    console.log('✅ CV Front: Logo añadido al menú móvil:', logoUrl);
                } else {
                    console.log('⚠️ CV Front: No se encontró logo válido del sitio (evitando placeholder)');
                }
            }
            
            // Buscar el menú en varias ubicaciones posibles
            var $menu = $('.mobile-menu ul.menu');
            if (!$menu.length) {
                $menu = $('#mobile-menu-wrapper ul.menu');
            }
            if (!$menu.length) {
                $menu = $('#mobile-menu-wrapper ul');
            }
            
            // Añadir opción "Mi cuenta" si el usuario está logueado
            if ($menu.length && !$('.cv-menu-myaccount-item').length) {
                var myAccountUrl = $('a[href*="my-account"]').first().attr('href') || '/my-account/';
                if (myAccountUrl) {
                    $menu.append('<li class="cv-menu-myaccount-item"><a href="' + myAccountUrl + '" class="cv-menu-myaccount-link"><span class="cv-icon">🔒</span> Mi cuenta</a></li>');
                    console.log('✅ CV Front: Opción Mi cuenta añadida al menú');
                }
            }
            
            // Añadir opción "Cerrar" al final del menú
            if ($menu.length && !$('.cv-menu-close-item').length) {
                $menu.append('<li class="cv-menu-close-item"><a href="#" class="cv-menu-close-link"><span class="cv-icon">✖️</span> Cerrar</a></li>');
                console.log('✅ CV Front: Opción Cerrar añadida al menú');
            } else {
                console.log('⚠️ CV Front: No se encontró el menú. Selectores probados:', $('.mobile-menu ul.menu').length, $('#mobile-menu-wrapper ul.menu').length, $('#mobile-menu-wrapper ul').length);
            }
        }, 500);
        
        // Delegación de eventos para manejar los clics
        $(document).on('click', '.cv-mobile-menu-close, .cv-menu-close-link', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $('body').removeClass('mobile-menu-active');
            console.log('✅ CV Front: Menú cerrado');
            return false;
        });
    });
})(jQuery);

