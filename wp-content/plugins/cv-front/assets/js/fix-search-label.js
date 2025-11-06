/**
 * Fix para forzar el texto "Todas" en el buscador
 * Sobrescribe cualquier versión cacheada
 */
(function($) {
    'use strict';
    
    function forceTodasLabel() {
        // Cambiar inmediatamente
        $('.nav-search-label').text('Todas');
        
        // Observar cambios y forzar "Todas"
        var observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList' || mutation.type === 'characterData') {
                    var currentText = $('.nav-search-label').text();
                    if (currentText === 'All' || currentText === '') {
                        $('.nav-search-label').text('Todas');
                    }
                }
            });
        });
        
        // Observar el elemento
        var targetNode = document.querySelector('.nav-search-label');
        if (targetNode) {
            observer.observe(targetNode, {
                childList: true,
                characterData: true,
                subtree: true
            });
        }
        
        console.log('✅ CV Front: Label "Todas" forzado');
    }
    
    $(document).ready(function() {
        forceTodasLabel();
        
        // También al cambiar el select
        $('.shopper-cat-list').on('change', function() {
            var val = $(this).val();
            if (val === '') {
                $('.nav-search-label').text('Todas');
            }
        });
    });
    
})(jQuery);

