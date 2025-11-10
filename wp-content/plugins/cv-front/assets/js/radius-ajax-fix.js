/**
 * CV Front - Fix para que el slider de radio funcione correctamente con AJAX
 * 
 * Este script intercepta el slider de radio y fuerza la actualización del mapa
 * cuando se mueve, sin depender de los scripts de WCFM.
 */
(function($, window) {
    'use strict';

    var geoEnabled = localStorage.getItem('cv_geolocation_enabled') === 'true';
    if (!geoEnabled) {
        console.log('[CV Radius Fix] ℹ️ Geolocalización desactivada - script deshabilitado');
        return;
    }

    console.log('[CV Radius Fix] 🚀 Script cargado');

    // Esperar a que el slider exista
    function waitForSlider(callback, maxAttempts = 20) {
        let attempts = 0;
        
        const check = setInterval(function() {
            attempts++;
            const $slider = $('#wcfmmp_radius_range');
            
            if ($slider.length > 0) {
                clearInterval(check);
                console.log('[CV Radius Fix] ✅ Slider encontrado después de', attempts, 'intentos');
                callback($slider);
            } else if (attempts >= maxAttempts) {
                clearInterval(check);
                console.warn('[CV Radius Fix] ⚠️ Slider no encontrado después de', maxAttempts, 'intentos');
            }
        }, 250);
    }

    // Función para refrescar el mapa de productos
    function refreshProductMap() {
        console.log('[CV Radius Fix] 🔄 Refrescando mapa de productos...');
        
        if ($('.wcfmmp-product-list-map').length === 0) {
            console.warn('[CV Radius Fix] ⚠️ Mapa de productos no encontrado');
            return;
        }

        // Intentar llamar a la función fetchMarkers expuesta por el plugin personalizado
        if (typeof window.wcfmmpFetchMarkers === 'function') {
            console.log('[CV Radius Fix] 📍 Llamando a wcfmmpFetchMarkers()...');
            window.wcfmmpFetchMarkers();
        } else {
            // Si no está disponible, esperar un poco y reintentar
            console.warn('[CV Radius Fix] ⚠️ wcfmmpFetchMarkers() aún no disponible, esperando 500ms...');
            setTimeout(function() {
                if (typeof window.wcfmmpFetchMarkers === 'function') {
                    console.log('[CV Radius Fix] 📍 Llamando a wcfmmpFetchMarkers() (segundo intento)...');
                    window.wcfmmpFetchMarkers();
                } else {
                    console.error('[CV Radius Fix] ❌ wcfmmpFetchMarkers() no encontrado después de esperar. Plugin personalizado no activo o script no cargado.');
                }
            }, 500);
        }
    }

    // Variable para controlar el tiempo de carga inicial
    var pageLoadTime = Date.now();
    
    // Función para refrescar el listado de tiendas
    function refreshStoreList() {
        console.log('[CV Radius Fix] 🔄 Refrescando listado de tiendas...');
        
        if ($('.wcfmmp-stores-listing').length === 0) {
            console.warn('[CV Radius Fix] ⚠️ Listado de tiendas no encontrado');
            return;
        }
        
        // CV FIX: NO ejecutar si stores-persistence está manejando la paginación
        if (window.cvStoresPaginating) {
            console.log('[CV Radius Fix] ⏭️ Paginación en curso - stores-persistence lo maneja');
            return;
        }
        
        // CV FIX: Solo prevenir el refresh en los primeros 2 segundos después de cargar la página
        // Esto evita duplicados en la carga inicial pero permite actualizaciones posteriores
        var timeSinceLoad = Date.now() - pageLoadTime;
        var existingStores = $('.wcfmmp-single-store').length;
        
        if (existingStores > 0 && timeSinceLoad < 2000) {
            console.log('[CV Radius Fix] ⏭️ Carga inicial reciente (', timeSinceLoad, 'ms) con', existingStores, 'comercios - SALTANDO refresh para evitar duplicados');
            return;
        }
        
        console.log('[CV Radius Fix] ✅ Ejecutando refresh (tiempo desde carga:', timeSinceLoad, 'ms)');

        const $form = $('.wcfmmp-store-search-form');
        const formData = $form.serialize();
        console.log('[CV Radius Fix] 📋 Datos del formulario:', formData);

        $('.wcfmmp-stores-listing').block({
            message: null,
            overlayCSS: {
                background: '#fff',
                opacity: 0.6
            }
        });

        $.ajax({
            url: wcfm_params.ajax_url,
            type: 'POST',
            data: {
                action: 'wcfmmp_stores_list_search',
                pagination_base: $form.find('#pagination_base').val(),
                paged: $form.find('#wcfm_paged').val(),
                orderby: $('#wcfmmp_store_orderby').val(),
                search_term: $('.wcfmmp-store-search').val(),
                wcfmmp_store_category: $('#wcfmmp_store_category').val(),
                search_data: formData,
                wcfm_ajax_nonce: wcfm_params.wcfm_ajax_nonce,
                _wpnonce: $form.find('#nonce').val()
            },
            success: function(response) {
                console.log('[CV Radius Fix] ✅ Respuesta recibida');
                if (response.success) {
                    $('#wcfmmp-stores-wrap').html($(response.data).find('.wcfmmp-stores-content'));
                    
                    // Refrescar marcadores del mapa
                    if (typeof window.fetchMarkers === 'function') {
                        window.fetchMarkers();
                    }
                }
                $('.wcfmmp-stores-listing').unblock();
            },
            error: function(xhr, status, error) {
                console.error('[CV Radius Fix] ❌ Error AJAX:', error);
                $('.wcfmmp-stores-listing').unblock();
            }
        });
    }

    // Inicializar cuando el slider esté listo
    $(document).ready(function() {
        waitForSlider(function($slider) {
            const max_radius = parseInt($slider.attr('max')) || 1200;
            let debounceTimer = null;

            console.log('[CV Radius Fix] 🎯 Instalando manejador de eventos...');
            
            // Eliminar todos los eventos previos
            $slider.off('input');
            
            // Instalar nuestro manejador
            $slider.on('input', function() {
                const value = this.value;
                console.log('[CV Radius Fix] 🎚️ Slider movido a:', value);

                // Actualizar el valor visible
                const $cur = $('.wcfmmp_radius_range_cur');
                const unit = $cur.text().replace(/[0-9]/g, '').trim();
                $cur.html(value + (unit || ' Km'));
                
                // Actualizar posición del indicador
                const containerWidth = $('.wcfm_radius_slidecontainer').outerWidth();
                const position = ((value / max_radius) * containerWidth) - 7.5;
                $cur.css('left', position + 'px');

                // Asegurar que los campos lat/lng tengan valores
                const $lat = $('#wcfmmp_radius_lat');
                const $lng = $('#wcfmmp_radius_lng');
                
                if (!$lat.val() || !$lng.val()) {
                    // Usar valores por defecto de España
                    $lat.val('40.4168');
                    $lng.val('-3.7038');
                    console.log('[CV Radius Fix] 📍 Campos lat/lng vacíos, usando Madrid por defecto');
                }

                // Debounce para evitar demasiadas peticiones
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(function() {
                    // Determinar qué tipo de página es
                    if ($('.wcfmmp-product-list-map').length > 0) {
                        refreshProductMap();
                    } else if ($('.wcfmmp-stores-listing').length > 0) {
                        refreshStoreList();
                    }
                }, 300);
            });

            console.log('[CV Radius Fix] ✅ Manejador instalado correctamente');
        });
    });

})(jQuery, window);

