/**
 * JavaScript para RSS Feed Display
 * @package CV_Front
 * @since 2.7.0
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        console.log('✅ CV RSS Feed: JavaScript cargado');
        
        // Lazy loading de imágenes
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        if (img.dataset.src) {
                            img.src = img.dataset.src;
                            img.removeAttribute('data-src');
                            observer.unobserve(img);
                        }
                    }
                });
            });
            
            document.querySelectorAll('.cv-rss-image img[data-src]').forEach(img => {
                imageObserver.observe(img);
            });
        }
        
        // Animación de entrada
        $('.cv-rss-item').each(function(index) {
            $(this).css({
                'opacity': '0',
                'transform': 'translateY(20px)'
            });
            
            setTimeout(() => {
                $(this).css({
                    'opacity': '1',
                    'transform': 'translateY(0)',
                    'transition': 'all 0.5s ease'
                });
            }, index * 100);
        });
    });
    
})(jQuery);

