/**
 * Geolocation Manager
 * 
 * Gestiona la visibilidad de mapas, filtros y orden según el estado de geolocalización
 * 
 * @package CV_Front
 * @since 3.4.0
 */

(function($) {
    'use strict';
    
    var GeoManager = {
        
        /**
         * Inicializar
         */
        init: function() {
            console.log('🗺️ GeoManager: Inicializando...');
            
            // Aplicar estado inicial al cargar la página
            this.applyGeoState();
            
            // Escuchar cambios en localStorage (desde otros tabs/ventanas)
            $(window).on('storage', function(e) {
                if (e.originalEvent.key === 'cv_geolocation_enabled') {
                    console.log('🔄 GeoManager: Estado cambió desde otro tab');
                    GeoManager.applyGeoState();
                }
            });
            
            console.log('✅ GeoManager: Inicializado');
        },
        
        /**
         * Aplicar estado de geolocalización
         */
        applyGeoState: function() {
            // Por defecto, la geolocalización está DESACTIVADA
            var isEnabled = localStorage.getItem('cv_geolocation_enabled') === 'true';
            
            // Si no existe la clave en localStorage, establecer como desactivada
            if (localStorage.getItem('cv_geolocation_enabled') === null) {
                localStorage.setItem('cv_geolocation_enabled', 'false');
                isEnabled = false;
            }
            
            console.log('📍 GeoManager: Geolocalización ' + (isEnabled ? 'ACTIVADA' : 'DESACTIVADA'));
            
            if (isEnabled) {
                this.showGeoElements();
                this.setGeoOrder();
            } else {
                this.hideGeoElements();
                this.setDefaultOrder();
            }
        },
        
        /**
         * Mostrar elementos de geolocalización
         */
        showGeoElements: function() {
            console.log('👁️ GeoManager: Mostrando mapas y filtros...');
            
            // Mostrar mapa de productos (shop)
            $('#wcfmmp_product_geolocate_wrapper').show();
            $('#wcfmmp_product_geolocate_filter_wrapper').show();
            
            // Mostrar mapa de tiendas (comercios)
            $('#wcfmmp_store_geolocate_wrapper').show();
            $('#wcfmmp_store_geolocate_filter_wrapper').show();
            
            // Mostrar controles de radio/distancia
            $('.wcfmmp-radius-range-wrapper').show();
            $('.wcfmmp-radius-range').show();
            $('#wcfmmp_radius_range').parent().show();
            
            // Mostrar campo de dirección
            $('#wcfmmp_radius_addr').parent().show();
            $('.wcfmmp-address-wrapper').show();
            
            // Mostrar botón "Filtrar"
            $('.wcfm_radius_slidecontainer input[type="button"]').show();
            
            // Mostrar banner de filtro activo
            $('.wcfm-radius-active-filter').show();
            
            // Mostrar opción "Distancia" en el selector de orden
            $('.orderby option[value="distance"], select[name="orderby"] option[value="distance"]').show();
            
            console.log('✅ GeoManager: Elementos mostrados');
        },
        
        /**
         * Ocultar elementos de geolocalización
         */
        hideGeoElements: function() {
            console.log('🙈 GeoManager: Ocultando mapas y filtros...');
            
            // Ocultar mapa de productos (shop)
            $('#wcfmmp_product_geolocate_wrapper').hide();
            $('#wcfmmp_product_geolocate_filter_wrapper').hide();
            
            // Ocultar mapa de tiendas (comercios)
            $('#wcfmmp_store_geolocate_wrapper').hide();
            $('#wcfmmp_store_geolocate_filter_wrapper').hide();
            
            // Ocultar controles de radio/distancia
            $('.wcfmmp-radius-range-wrapper').hide();
            $('.wcfmmp-radius-range').hide();
            $('#wcfmmp_radius_range').parent().hide();
            
            // Ocultar campo de dirección
            $('#wcfmmp_radius_addr').parent().hide();
            $('.wcfmmp-address-wrapper').hide();
            
            // Ocultar botón "Filtrar"
            $('.wcfm_radius_slidecontainer input[type="button"]').hide();
            
            // Ocultar banner de filtro activo
            $('.wcfm-radius-active-filter').hide();
            
            // Ocultar opción "Distancia" en el selector de orden
            $('.orderby option[value="distance"], select[name="orderby"] option[value="distance"]').hide();
            
            console.log('✅ GeoManager: Elementos ocultados');
        },
        
        /**
         * Establecer orden por geolocalización
         */
        setGeoOrder: function() {
            console.log('📊 GeoManager: Estableciendo orden por distancia...');
            
            // Cambiar el selector de ordenamiento a "distance" si existe
            var $orderby = $('.orderby, select[name="orderby"]');
            
            if ($orderby.length > 0) {
                // Verificar si existe la opción "distance"
                if ($orderby.find('option[value="distance"]').length > 0) {
                    $orderby.val('distance').trigger('change');
                    console.log('✅ GeoManager: Orden cambiado a "distance"');
                } else {
                    console.log('⚠️ GeoManager: Opción "distance" no encontrada');
                }
            }
            
            // Para WCFM, también actualizar el parámetro en la URL si es necesario
            this.updateUrlParam('orderby', 'distance');
        },
        
        /**
         * Establecer orden por defecto (relevancia)
         */
        setDefaultOrder: function() {
            console.log('📊 GeoManager: Estableciendo orden por defecto...');
            
            // Cambiar el selector de ordenamiento a "menu_order" (relevancia) o vacío
            var $orderby = $('.orderby, select[name="orderby"]');
            
            if ($orderby.length > 0) {
                // Intentar "menu_order" primero, luego vacío
                if ($orderby.find('option[value="menu_order"]').length > 0) {
                    $orderby.val('menu_order').trigger('change');
                    console.log('✅ GeoManager: Orden cambiado a "menu_order"');
                } else if ($orderby.find('option[value=""]').length > 0) {
                    $orderby.val('').trigger('change');
                    console.log('✅ GeoManager: Orden cambiado a defecto');
                } else {
                    console.log('⚠️ GeoManager: No se pudo cambiar el orden');
                }
            }
            
            // Limpiar parámetro de la URL
            this.updateUrlParam('orderby', null);
        },
        
        /**
         * Actualizar parámetro en la URL sin recargar
         */
        updateUrlParam: function(key, value) {
            if (!window.history || !window.history.replaceState) {
                return;
            }
            
            var url = new URL(window.location.href);
            
            if (value === null || value === '') {
                url.searchParams.delete(key);
            } else {
                url.searchParams.set(key, value);
            }
            
            window.history.replaceState({}, '', url.toString());
            console.log('🔗 GeoManager: URL actualizada');
        }
    };
    
    // Inicializar cuando el DOM esté listo
    $(document).ready(function() {
        GeoManager.init();
    });
    
    // También inicializar después de AJAX de WCFM
    $(document).on('wcfm_ajax_loaded', function() {
        console.log('🔄 GeoManager: Replicando después de AJAX WCFM');
        GeoManager.applyGeoState();
    });
    
    // Exponer globalmente para debugging
    window.GeoManager = GeoManager;
    
})(jQuery);

