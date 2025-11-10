/**
 * Geolocation Initialization
 * 
 * Asegura que la geolocalización esté desactivada por defecto en la primera visita
 * 
 * @package CV_Front
 * @since 3.4.3
 */

(function($) {
    'use strict';
    
    // Ejecutar inmediatamente (antes del DOM ready)
    (function() {
        // Si es la primera vez que se visita la página (no hay preferencia guardada)
        if (localStorage.getItem('cv_geolocation_enabled') === null) {
            console.log('🆕 Primera visita: Estableciendo geolocalización como DESACTIVADA');
            localStorage.setItem('cv_geolocation_enabled', 'false');
        }
        
        // Verificar el estado actual
        var isEnabled = localStorage.getItem('cv_geolocation_enabled') === 'true';
        console.log('🔧 Estado inicial de geolocalización:', isEnabled ? 'ACTIVADA' : 'DESACTIVADA');
        
        // Si está desactivada, limpiar cualquier parámetro de geolocalización de la URL
        if (!isEnabled) {
            var url = new URL(window.location.href);
            var hasGeoParams = false;
            
            if (url.searchParams.has('radius_lat')) {
                url.searchParams.delete('radius_lat');
                hasGeoParams = true;
            }
            if (url.searchParams.has('radius_lng')) {
                url.searchParams.delete('radius_lng');
                hasGeoParams = true;
            }
            if (url.searchParams.has('radius_range')) {
                url.searchParams.delete('radius_range');
                hasGeoParams = true;
            }
            if (url.searchParams.has('radius_addr')) {
                url.searchParams.delete('radius_addr');
                hasGeoParams = true;
            }
            
            // Si había parámetros de geolocalización, limpiar la URL
            if (hasGeoParams && window.history && window.history.replaceState) {
                window.history.replaceState({}, '', url.toString());
                console.log('🧹 Parámetros de geolocalización eliminados de la URL');
            }
        }
    })();
    
})(jQuery);

