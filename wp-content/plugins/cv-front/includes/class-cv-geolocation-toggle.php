<?php
/**
 * Geolocation Toggle Button
 * 
 * Botón flotante morado para activar/desactivar geolocalización
 * 
 * @package CV_Front
 * @since 3.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class CV_Geolocation_Toggle {
    
    public function __construct() {
        // Cargar dashicons en el frontend
        add_action('wp_enqueue_scripts', array($this, 'enqueue_dashicons'));
        
        // Añadir botón flotante de geolocalización
        add_action('wp_footer', array($this, 'add_geolocation_button'));
    }
    
    /**
     * Cargar dashicons en el frontend
     */
    public function enqueue_dashicons() {
        wp_enqueue_style('dashicons');
    }
    
    /**
     * Añadir botón flotante de geolocalización
     */
    public function add_geolocation_button() {
        // Solo en páginas donde tiene sentido la geolocalización
        if (!is_shop() && !is_product_category() && !is_product_tag() && 
            !(function_exists('wcfmmp_is_stores_list_page') && wcfmmp_is_stores_list_page())) {
            return;
        }
        
        ?>
        <div id="cv-geolocation-toggle-float" 
             title="Geolocalización desactivada"
             style="
            position: fixed;
            top: 150px;
            left: 20px;
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 999999;
            box-shadow: 0 4px 20px rgba(102, 126, 234, 0.4);
            transition: all 0.3s ease;
        ">
            <span class="dashicons dashicons-location" id="cv-geolocation-icon" style="
                font-size: 24px;
                color: white;
                width: 24px;
                height: 24px;
                position: relative;
            "></span>
            <!-- Línea diagonal para tachar el icono -->
            <div id="cv-geolocation-slash" style="
                position: absolute;
                width: 2px;
                height: 38px;
                background: white;
                transform: rotate(45deg);
                box-shadow: 0 0 3px rgba(0,0,0,0.3);
            "></div>
        </div>
        
        <style>
        #cv-geolocation-toggle-float:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 25px rgba(102, 126, 234, 0.6);
        }
        
        #cv-geolocation-toggle-float.enabled #cv-geolocation-slash {
            display: none;
        }
        
        #cv-geolocation-toggle-float.disabled #cv-geolocation-slash {
            display: block;
        }
        
        #cv-geolocation-toggle-float.processing {
            pointer-events: none;
            opacity: 0.6;
        }
        
        #cv-geolocation-toggle-float.processing #cv-geolocation-icon {
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            var $btn = $('#cv-geolocation-toggle-float');
            var $icon = $('#cv-geolocation-icon');
            var $slash = $('#cv-geolocation-slash');
            
            // Estado inicial: desactivado (con tachado)
            $btn.addClass('disabled');
            
            $('#cv-geolocation-toggle-float').on('click', function() {
                var isEnabled = $btn.hasClass('enabled');
                
                // Confirmar acción
                var confirmMsg = isEnabled 
                    ? '¿Desactivar geolocalización?\n\nLos productos y comercios no se filtrarán por distancia.'
                    : '¿Activar geolocalización?\n\nSe te pedirá tu ubicación para mostrar comercios cercanos.';
                
                if (!confirm(confirmMsg)) {
                    return;
                }
                
                // Marcar como procesando
                $btn.addClass('processing');
                
                if (!isEnabled) {
                    // ACTIVAR: Pedir ubicación del navegador
                    if ('geolocation' in navigator) {
                        navigator.geolocation.getCurrentPosition(
                            function(position) {
                                // Éxito: guardar ubicación
                                var lat = position.coords.latitude;
                                var lng = position.coords.longitude;
                                
                                console.log('✅ Ubicación obtenida:', lat, lng);
                                
                                // Guardar en localStorage
                                localStorage.setItem('cv_geolocation_enabled', 'true');
                                localStorage.setItem('cv_user_lat', lat);
                                localStorage.setItem('cv_user_lng', lng);
                                
                                // Cambiar estado a activado
                                $btn.removeClass('processing disabled').addClass('enabled');
                                $btn.attr('title', 'Geolocalización activada');
                                
                                // Mostrar mensaje
                                showMessage('📍 Geolocalización activada', '#4CAF50');
                                
                                // Aplicar cambios sin recargar
                                if (window.GeoManager) {
                                    window.GeoManager.applyGeoState();
                                }
                            },
                            function(error) {
                                // Error: no se pudo obtener ubicación
                                $btn.removeClass('processing');
                                
                                var errorMsg = 'No se pudo obtener tu ubicación';
                                if (error.code === 1) {
                                    errorMsg = 'Permiso de ubicación denegado';
                                } else if (error.code === 2) {
                                    errorMsg = 'Ubicación no disponible';
                                } else if (error.code === 3) {
                                    errorMsg = 'Tiempo de espera agotado';
                                }
                                
                                alert('❌ ' + errorMsg);
                                console.error('Error geolocalización:', error);
                            }
                        );
                    } else {
                        $btn.removeClass('processing');
                        alert('❌ Tu navegador no soporta geolocalización');
                    }
                } else {
                    // DESACTIVAR: Limpiar localStorage
                    localStorage.removeItem('cv_geolocation_enabled');
                    localStorage.removeItem('cv_user_lat');
                    localStorage.removeItem('cv_user_lng');
                    
                    // Cambiar estado a desactivado
                    $btn.removeClass('processing enabled').addClass('disabled');
                    $btn.attr('title', 'Geolocalización desactivada');
                    
                    // Mostrar mensaje
                    showMessage('🔕 Geolocalización desactivada', '#FF9800');
                    
                    // Aplicar cambios sin recargar
                    if (window.GeoManager) {
                        window.GeoManager.applyGeoState();
                    }
                }
            });
            
            // Función auxiliar para mostrar mensajes
            function showMessage(text, color) {
                $('<div style="position: fixed; top: 210px; left: 20px; background: ' + color + '; color: white; padding: 15px 25px; border-radius: 8px; z-index: 999998; box-shadow: 0 4px 15px rgba(0,0,0,0.2);">' + text + '</div>')
                    .appendTo('body')
                    .delay(2000)
                    .fadeOut(300, function() { $(this).remove(); });
            }
            
            // Verificar estado inicial desde localStorage
            var geoEnabled = localStorage.getItem('cv_geolocation_enabled') === 'true';
            if (geoEnabled) {
                $btn.removeClass('disabled').addClass('enabled');
                $btn.attr('title', 'Geolocalización activada');
            }
        });
        </script>
        <?php
    }
}

new CV_Geolocation_Toggle();

