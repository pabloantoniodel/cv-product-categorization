<?php
/**
 * Muestra la distancia en el header de la tienda y la oculta de los productos
 *
 * @package CV_Front
 * @version 2.3.9
 */

if (!defined('ABSPATH')) {
    exit;
}

class CV_Store_Distance {
    
    public function __construct() {
        error_log('✅ CV Store Distance: Clase inicializada');
        
        // Enqueue CSS para distancia
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
        
        // Añadir JavaScript para obtener ubicación del navegador e inyectar distancia
        add_action('wp_footer', array($this, 'add_distance_script'));
        
        // Ocultar distancia de los productos con CSS
        add_action('wp_head', array($this, 'hide_distance_from_products'));
        
        error_log('✅ CV Store Distance: Hooks registrados (wp_enqueue_scripts, wp_footer, wp_head)');
    }
    
    /**
     * Enqueue CSS para estilos modernos de distancia
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            'cv-store-distance',
            CV_FRONT_PLUGIN_URL . 'assets/css/store-distance.css',
            array(),
            CV_FRONT_VERSION
        );
    }
    
    /**
     * Añadir distancia al header de la tienda
     * Obtiene la distancia del primer producto si está disponible
     */
    public function add_distance_to_header($store_id) {
        global $WCFMmp, $wpdb;
        
        error_log('CV Store Distance: Ejecutando para store_id: ' . $store_id);
        
        // Obtener coordenadas del vendedor
        $vendor_data = get_user_meta($store_id, 'wcfmmp_profile_settings', true);
        $store_lat = isset($vendor_data['store_lat']) ? floatval($vendor_data['store_lat']) : 0;
        $store_lng = isset($vendor_data['store_lng']) ? floatval($vendor_data['store_lng']) : 0;
        
        error_log('CV Store Distance: Store lat/lng: ' . $store_lat . ',' . $store_lng);
        
        // Intentar obtener ubicación del usuario desde cookies
        $user_lat = isset($_COOKIE['wcfmmp_user_lat']) ? floatval($_COOKIE['wcfmmp_user_lat']) : 0;
        $user_lng = isset($_COOKIE['wcfmmp_user_lng']) ? floatval($_COOKIE['wcfmmp_user_lng']) : 0;
        
        error_log('CV Store Distance: User lat/lng: ' . $user_lat . ',' . $user_lng);
        
        $distance = '';
        
        // Si tenemos ambas coordenadas, calcular distancia
        if ($store_lat && $store_lng && $user_lat && $user_lng) {
            // Calcular distancia usando fórmula de Haversine
            $earth_radius = 6371; // km
            
            $lat_diff = deg2rad($user_lat - $store_lat);
            $lng_diff = deg2rad($user_lng - $store_lng);
            
            $a = sin($lat_diff / 2) * sin($lat_diff / 2) +
                 cos(deg2rad($store_lat)) * cos(deg2rad($user_lat)) *
                 sin($lng_diff / 2) * sin($lng_diff / 2);
            
            $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
            $distance = round($earth_radius * $c, 2);
            
            error_log('CV Store Distance: Distancia calculada: ' . $distance . ' km');
        }
        
        // Si no pudimos calcular, intentar obtenerla de la función de WCFM
        if ($distance === '' || $distance === 0) {
            $distance = wcfmmp_get_user_vendor_distance($store_id);
            error_log('CV Store Distance: wcfmmp_get_user_vendor_distance() = ' . var_export($distance, true));
        }
        
        // Si aún no hay distancia, intentar obtenerla del primer producto del vendedor
        if ($distance === '' || $distance === 0) {
            // Intentar con post_author
            $product = $wpdb->get_row($wpdb->prepare(
                "SELECT p.ID, p.post_title, pm.meta_value as distance 
                FROM {$wpdb->posts} p
                LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = 'distance'
                WHERE p.post_author = %d 
                AND p.post_type = 'product' 
                AND p.post_status = 'publish'
                LIMIT 1",
                $store_id
            ));
            
            // Si no hay con post_author, buscar con _wcfm_product_author
            if (!$product) {
                $product = $wpdb->get_row($wpdb->prepare(
                    "SELECT p.ID, p.post_title, pm2.meta_value as distance 
                    FROM {$wpdb->posts} p
                    INNER JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id AND pm1.meta_key = '_wcfm_product_author' AND pm1.meta_value = %d
                    LEFT JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = 'distance'
                    WHERE p.post_type = 'product' 
                    AND p.post_status = 'publish'
                    LIMIT 1",
                    $store_id
                ));
            }
            
            if ($product) {
                error_log('CV Store Distance: Producto encontrado: ' . $product->post_title . ' (ID: ' . $product->ID . ')');
                error_log('CV Store Distance: Distancia del producto: ' . var_export($product->distance, true));
                
                if (!empty($product->distance)) {
                    $distance = round($product->distance, 2);
                }
            } else {
                error_log('CV Store Distance: No se encontraron productos para store_id: ' . $store_id);
            }
        }
        
        $radius_unit = isset($WCFMmp->wcfmmp_marketplace_options['radius_unit']) 
            ? $WCFMmp->wcfmmp_marketplace_options['radius_unit'] 
            : 'km';
        
        if ($distance !== '' && $distance !== null) {
            if ($distance > 0) {
                $msg = $distance . ' ' . $radius_unit . ' ' . __('away', 'wc-multivendor-marketplace');
            } else {
                $msg = __('You are here!', 'wc-multivendor-marketplace');
            }
            ?>
            <p class="header_store_name wcfmmp_store_header_distance" style="margin: 10px 0; color: #667eea; font-weight: 600;">
                <i class="wcfmfa fa-map-marker-alt" aria-hidden="true"></i>
                <span style="margin-left: 5px;"><?php echo wp_kses_post($msg); ?></span>
            </p>
            <?php
        }
    }
    
    /**
     * Añadir JavaScript para calcular y mostrar distancia con geolocalización del navegador
     */
    public function add_distance_script() {
        // Solo en páginas de tienda
        if (!function_exists('wcfmmp_is_store_page') || !wcfmmp_is_store_page()) {
            return;
        }
        
        global $WCFMmp;
        
        // Obtener coordenadas del vendedor
        $store_user = wcfmmp_get_store($GLOBALS['author']);
        if (!$store_user) {
            return;
        }
        
        $vendor_data = get_user_meta($store_user->get_id(), 'wcfmmp_profile_settings', true);
        $store_lat = isset($vendor_data['store_lat']) ? floatval($vendor_data['store_lat']) : 0;
        $store_lng = isset($vendor_data['store_lng']) ? floatval($vendor_data['store_lng']) : 0;
        
        if (!$store_lat || !$store_lng) {
            return;
        }
        
        $radius_unit = isset($WCFMmp->wcfmmp_marketplace_options['radius_unit']) 
            ? $WCFMmp->wcfmmp_marketplace_options['radius_unit'] 
            : 'km';
        
        ?>
        <script type="text/javascript">
        (function() {
            // Prevenir ejecución múltiple
            if (window.cvStoreDistanceLoaded) {
                console.log('ℹ️ CV Store Distance: Script ya ejecutado, evitando duplicados');
                return;
            }
            window.cvStoreDistanceLoaded = true;
            
            jQuery(document).ready(function($) {
                // Verificar que no exista ya en el DOM
                if ($('.wcfmmp_store_header_distance').length > 0) {
                    console.log('ℹ️ CV Store Distance: Distancia ya existe en DOM, eliminando duplicados');
                    $('.wcfmmp_store_header_distance').remove();
                }
                
                // Forzar altura del header INMEDIATAMENTE (450px desktop)
                setTimeout(function() {
                    if ($(window).width() > 768) {
                        // Añadir !important directamente al atributo style
                        var $header = $('#wcfmmp-store #wcfm_store_header');
                        var currentStyle = $header.attr('style') || '';
                        $header.attr('style', currentStyle + ';min-height:450px !important;height:450px !important;');
                        console.log('✅ CV Store Distance: Altura del header fijada a 450px (desktop)');
                    } else {
                        $('#wcfmmp-store #wcfm_store_header').css({
                            'min-height': 'auto',
                            'height': 'auto'
                        });
                        console.log('✅ CV Store Distance: Altura del header auto (móvil)');
                    }
                }, 100);
                
                // Intentar obtener ubicación del navegador
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(function(position) {
                        var userLat = position.coords.latitude;
                        var userLng = position.coords.longitude;
                        var storeLat = <?php echo $store_lat; ?>;
                        var storeLng = <?php echo $store_lng; ?>;
                        
                        // Calcular distancia con Haversine
                        var R = 6371; // km
                        var dLat = (userLat - storeLat) * Math.PI / 180;
                        var dLon = (userLng - storeLng) * Math.PI / 180;
                        var a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                                Math.cos(storeLat * Math.PI / 180) * Math.cos(userLat * Math.PI / 180) *
                                Math.sin(dLon/2) * Math.sin(dLon/2);
                        var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
                        var distance = (R * c).toFixed(2);
                        
                        console.log('CV Store Distance: Calculada desde navegador: ' + distance + ' km');
                        
                        // Añadir ID al contenedor display:inline
                        var $inlineContainer = $('.address.rgt div[style*="display:inline"]').first();
                        if ($inlineContainer.length) {
                            $inlineContainer.attr('id', 'cv-captura-ticket-container');
                            
                            // Quitar los <br><br> que vienen después
                            $inlineContainer.nextAll('br').slice(0, 2).remove();
                            
                            // Igualar ancho al botón Tarjeta de visita
                            var $tarjetaButton = $('.store-phone a.button:contains("Tarjeta de visita")');
                            if ($tarjetaButton.length) {
                                var tarjetaWidth = $tarjetaButton.outerWidth();
                                $('#cv-captura-ticket-container').css('max-width', tarjetaWidth + 'px');
                                console.log('✅ CV Store Distance: Ancho igualado a Tarjeta (' + tarjetaWidth + 'px)');
                            }
                        }
                        
                        // Buscar la línea de dirección y añadir distancia entre paréntesis
                        var $addressLink = $('.wcfmmp_store_header_address a span').first();
                        if ($addressLink.length) {
                            var currentText = $addressLink.text();
                            $addressLink.text(currentText + ' (' + distance + ' <?php echo $radius_unit; ?>)');
                            console.log('✅ CV Store Distance: Distancia añadida al final de la dirección');
                        } else {
                            console.log('⚠️ CV Store Distance: No se encontró línea de dirección');
                        }
                        
                        // Corregir enlace WhatsApp para que funcione (formato correcto) y normalizar número
                        $('.store-phone a[href*="wa.me"]').each(function() {
                            var $link = $(this);
                            var href = $link.attr('href');
                            
                            // Extraer número de teléfono del header
                            var phone = '';
                            var $phoneElement = $('.wcfmmp_store_header_phone a[href^="tel:"]');
                            if ($phoneElement.length) {
                                phone = $phoneElement.attr('href').replace('tel:', '').replace(/\s+/g, '');
                            }
                            
                            // Si encontramos el teléfono, normalizar y construir el enlace correcto
                            if (phone) {
                                // Normalizar teléfono (añadir +34 si tiene 9 dígitos)
                                var cleanPhone = phone.replace(/[^0-9+]/g, '');
                                
                                // Si no tiene prefijo + y tiene 9 dígitos, añadir +34
                                if (cleanPhone.indexOf('+') !== 0 && cleanPhone.length === 9) {
                                    cleanPhone = '+34' + cleanPhone;
                                }
                                // Si empieza con 34 y tiene 11 dígitos, añadir +
                                else if (cleanPhone.indexOf('34') === 0 && cleanPhone.length === 11) {
                                    cleanPhone = '+' + cleanPhone;
                                }
                                // Si empieza con 0034, convertir a +34
                                else if (cleanPhone.indexOf('0034') === 0) {
                                    cleanPhone = '+' + cleanPhone.substring(2);
                                }
                                
                                var message = encodeURIComponent('Solicito informacion');
                                href = 'https://wa.me/' + cleanPhone + '?text=' + message;
                                $link.attr('href', href);
                                console.log('✅ CV Store Distance: Enlace WhatsApp normalizado:', href);
                            } else {
                                // Fallback: intentar reparar el enlace existente
                                href = href.replace(/&amp;/g, '&');
                                if (href.indexOf('&text=') !== -1) {
                                    href = href.replace('&text=', '?text=');
                                }
                                $link.attr('href', href);
                                console.log('⚠️ CV Store Distance: Enlace WhatsApp reparado:', href);
                            }
                        });
                    }, function(error) {
                        console.log('⚠️ CV Store Distance: Geolocalización no disponible o denegada');
                    });
                } else {
                    console.log('⚠️ CV Store Distance: Navegador no soporta geolocalización');
                }
            });
        })();
        </script>
        <?php
    }
    
    /**
     * Ocultar distancia de los productos
     */
    public function hide_distance_from_products() {
        // Solo en páginas de tienda
        if (!function_exists('wcfmmp_is_store_page') || !wcfmmp_is_store_page()) {
            return;
        }
        ?>
        <style type="text/css">
            /* Ocultar distancia de los productos en listados */
            .products .product .wcfmmp-sold-by-wrapper .store-distance,
            .products .product .gmw-distance,
            .products .product .distance,
            ul.products li.product .wcfmmp-sold-by-wrapper .store-distance,
            ul.products li.product .gmw-distance,
            ul.products li.product .distance {
                display: none !important;
            }
            
            /* Asegurar que la distancia del header sea visible */
            .wcfmmp_store_header_distance {
                display: inline-block !important;
                margin-right: 10px;
            }
        </style>
        <?php
    }
}

// Inicializar
new CV_Store_Distance();

