<?php
/**
 * CV Store QR Code
 *
 * Añade un código QR en el header de la tienda del vendedor
 *
 * @package CV_Front
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class CV_Store_QR {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_footer', array($this, 'add_store_qr'), 999);
        error_log('✅ CV Store QR: Clase inicializada');
    }
    
    public function test_hook() {
        error_log('CV Store QR: Hook de prueba ejecutado');
    }
    
    /**
     * Añade el código QR al header de la tienda del vendedor
     */
    public function add_store_qr() {
        error_log('CV Store QR: Función add_store_qr ejecutada');
        
        // Solo en páginas de tienda de vendedor
        if (!function_exists('wcfmmp_is_store_page')) {
            error_log('CV Store QR: Función wcfmmp_is_store_page no existe');
            return;
        }
        
        // También intentar detectar por la URL o por el contenido
        $is_store_page = wcfmmp_is_store_page();
        
        // Detección alternativa por URL
        $current_url = $_SERVER['REQUEST_URI'];
        $is_store_url = (strpos($current_url, '/store/') !== false);
        
        error_log('CV Store QR: wcfmmp_is_store_page(): ' . ($is_store_page ? 'true' : 'false'));
        error_log('CV Store QR: URL contiene /store/: ' . ($is_store_url ? 'true' : 'false'));
        
        if (!$is_store_page && !$is_store_url) {
            error_log('CV Store QR: No es página de tienda');
            return;
        }
        
        error_log('CV Store QR: Es página de tienda');
        
        // Intentar obtener el store_user de diferentes formas
        global $store_user, $WCFMmp;
        
        if (!$store_user) {
            // Intentar obtener el ID del vendedor desde la URL
            $wcfm_store_url = wcfm_get_option('wcfm_store_url', 'store');
            $wcfm_store_name = apply_filters('wcfmmp_store_query_var', get_query_var($wcfm_store_url));
            
            if ($wcfm_store_name) {
                $seller_info = get_user_by('slug', $wcfm_store_name);
                if ($seller_info) {
                    $store_user = wcfmmp_get_store($seller_info->ID);
                    error_log('CV Store QR: store_user obtenido desde URL, ID: ' . $seller_info->ID);
                }
            }
        }
        
        if (!$store_user) {
            error_log('CV Store QR: store_user no está disponible');
            return;
        }
        
        error_log('CV Store QR: store_user disponible, ID: ' . $store_user->get_id());
        
        // En lugar de apuntar a la tienda, apuntar a la página de captura de tickets
        $vendor_id = $store_user->get_id();
        $vendor_email = get_userdata($vendor_id)->user_email;
        
        // Obtener nombre de la tienda y código
        $store_settings = get_user_meta($vendor_id, 'wcfmmp_profile_settings', true);
        $store_name = isset($store_settings['store_name']) ? $store_settings['store_name'] : get_userdata($vendor_id)->display_name;
        
        // Buscar código del comercio en los meta del usuario
        $codigo_comercio = get_user_meta($vendor_id, 'wcfm_codigo_comercio', true);
        if (empty($codigo_comercio)) {
            $codigo_comercio = $vendor_id; // Usar ID como código por defecto
        }
        
        // Construir URL de captura de tickets
        $capture_url = home_url('/captura-tu-ticket/');
        $capture_url = add_query_arg(array(
            'comercio' => urlencode($store_name),
            'email_comercio' => urlencode($vendor_email),
            'codigo_comercio' => urlencode($codigo_comercio)
        ), $capture_url);
        
        error_log('CV Store QR: URL de captura: ' . $capture_url);
        
        ?>
        <style type="text/css">
        @media screen and (max-width: 768px) {
            .cv-store-qr-container {
                width: 105.6px !important;
                height: 105.6px !important;
                padding: 10.56px !important;
            }
            .cv-store-qr-image {
                width: 105.6px !important;
                height: 105.6px !important;
            }
        }
        </style>
        <script type="text/javascript">
        console.log('CV Store QR: Script inline cargado');
        
        setTimeout(function() {
            console.log('CV Store QR: Ejecutando con timeout');
            
            if (typeof jQuery === 'undefined') {
                console.error('CV Store QR: jQuery no está disponible');
                return;
            }
            
            jQuery(document).ready(function($) {
                console.log('CV Store QR: jQuery ready ejecutado');
                
                var captureUrl = '<?php echo esc_js($capture_url); ?>';
                // Decodificar &amp; a &
                captureUrl = captureUrl.replace(/&amp;/g, '&');
                console.log('CV Store QR: URL de captura:', captureUrl);
                
                // Buscar si ya existe un contenedor QR
                if ($('.cv-store-qr-container').length > 0) {
                    console.log('CV Store QR: Ya existe un contenedor QR');
                    return;
                }
                
                // Crear contenedor para el QR
                var qrHtml = '<div class="cv-store-qr-container" style="position: absolute; top: 15px; right: 15px; background: white; padding: 13px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.15); z-index: 200;">' +
                    '<div class="cv-store-qr-image" style="width: 120px; height: 120px;"></div>' +
                '</div>';
                
                // Buscar el contenedor del banner
                var $bannerArea = $('.wcfm_banner_area');
                console.log('CV Store QR: Banner area encontrado:', $bannerArea.length);
                
                if ($bannerArea.length > 0) {
                    console.log('CV Store QR: Añadiendo QR al banner area');
                    $bannerArea.css('position', 'relative');
                    $bannerArea.prepend(qrHtml);
                    
                    // Generar QR usando la API de QR Server apuntando a la página de captura
                    var qrImageUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=' + encodeURIComponent(captureUrl);
                    console.log('CV Store QR: URL del QR:', qrImageUrl);
                    
                    $('.cv-store-qr-image').html('<img src="' + qrImageUrl + '" alt="QR Code" style="width: 100%; height: 100%; object-fit: contain;" />');
                    console.log('CV Store QR: QR generado y añadido');
                } else {
                    console.log('CV Store QR: No se encontró banner area');
                }
            });
        }, 1000);
        </script>
        <?php
    }
}

