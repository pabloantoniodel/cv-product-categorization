<?php
/**
 * Proxy para peticiones a Nominatim
 * 
 * Este proxy intercepta las peticiones a Nominatim y las maneja desde el servidor,
 * permitiendo control, cacheo y rate limiting.
 */

if (!defined('ABSPATH')) {
    exit;
}

class CV_Nominatim_Proxy {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Registrar endpoint REST API
        add_action('rest_api_init', array($this, 'register_routes'));
        
        // Añadir variable JavaScript con la URL del proxy
        add_action('wp_enqueue_scripts', array($this, 'add_proxy_url'));
    }
    
    /**
     * Registrar rutas REST API
     */
    public function register_routes() {
        // Endpoint para reverse geocoding
        register_rest_route('cv-front/v1', '/nominatim/reverse', array(
            'methods' => 'GET',
            'callback' => array($this, 'reverse_geocode'),
            'permission_callback' => '__return_true', // Público
            'args' => array(
                'lat' => array(
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                ),
                'lon' => array(
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                ),
            ),
        ));
        
        // Endpoint para forward geocoding (search)
        register_rest_route('cv-front/v1', '/nominatim/search', array(
            'methods' => 'GET',
            'callback' => array($this, 'search_geocode'),
            'permission_callback' => '__return_true', // Público
            'args' => array(
                'q' => array(
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'format' => array(
                    'default' => 'json',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ));
    }
    
    /**
     * Reverse Geocoding (coordenadas -> dirección)
     */
    public function reverse_geocode($request) {
        $lat = $request->get_param('lat');
        $lon = $request->get_param('lon');
        
        // Cache key
        $cache_key = 'cv_nominatim_reverse_' . md5($lat . '_' . $lon);
        
        // Intentar obtener del cache (1 día)
        $cached = get_transient($cache_key);
        if ($cached !== false) {
            error_log('🔄 CV Nominatim Proxy: Sirviendo desde caché - ' . $lat . ', ' . $lon);
            return rest_ensure_response($cached);
        }
        
        // Rate limiting básico
        $rate_key = 'cv_nominatim_rate_' . get_current_user_id();
        $requests = (int) get_transient($rate_key);
        
        if ($requests > 60) { // Máximo 60 peticiones por minuto
            error_log('⚠️ CV Nominatim Proxy: Rate limit excedido');
            return new WP_Error('rate_limit', 'Demasiadas peticiones, intenta de nuevo en un minuto', array('status' => 429));
        }
        
        // Incrementar contador
        set_transient($rate_key, $requests + 1, 60);
        
        // Hacer petición a Nominatim
        $url = sprintf(
            'https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=%s&lon=%s',
            urlencode($lat),
            urlencode($lon)
        );
        
        error_log('🌍 CV Nominatim Proxy: Petición reverse - ' . $url);
        
        $response = wp_remote_get($url, array(
            'timeout' => 10,
            'headers' => array(
                'User-Agent' => 'CiudadVirtual-Proxy/1.0',
            ),
        ));
        
        if (is_wp_error($response)) {
            error_log('❌ CV Nominatim Proxy: Error - ' . $response->get_error_message());
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if ($data) {
            // Guardar en caché por 24 horas
            set_transient($cache_key, $data, DAY_IN_SECONDS);
            error_log('✅ CV Nominatim Proxy: Respuesta cacheada - ' . $lat . ', ' . $lon);
        }
        
        return rest_ensure_response($data);
    }
    
    /**
     * Forward Geocoding (dirección -> coordenadas)
     */
    public function search_geocode($request) {
        $query = $request->get_param('q');
        $format = $request->get_param('format');
        
        // Cache key
        $cache_key = 'cv_nominatim_search_' . md5($query);
        
        // Intentar obtener del cache (1 día)
        $cached = get_transient($cache_key);
        if ($cached !== false) {
            error_log('🔄 CV Nominatim Proxy: Sirviendo búsqueda desde caché - ' . $query);
            return rest_ensure_response($cached);
        }
        
        // Rate limiting básico
        $rate_key = 'cv_nominatim_rate_' . get_current_user_id();
        $requests = (int) get_transient($rate_key);
        
        if ($requests > 60) {
            error_log('⚠️ CV Nominatim Proxy: Rate limit excedido');
            return new WP_Error('rate_limit', 'Demasiadas peticiones, intenta de nuevo en un minuto', array('status' => 429));
        }
        
        set_transient($rate_key, $requests + 1, 60);
        
        // Hacer petición a Nominatim
        $url = sprintf(
            'https://nominatim.openstreetmap.org/search?format=%s&q=%s',
            urlencode($format),
            urlencode($query)
        );
        
        error_log('🌍 CV Nominatim Proxy: Petición search - ' . $url);
        
        $response = wp_remote_get($url, array(
            'timeout' => 10,
            'headers' => array(
                'User-Agent' => 'CiudadVirtual-Proxy/1.0',
            ),
        ));
        
        if (is_wp_error($response)) {
            error_log('❌ CV Nominatim Proxy: Error - ' . $response->get_error_message());
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if ($data) {
            // Guardar en caché por 24 horas
            set_transient($cache_key, $data, DAY_IN_SECONDS);
            error_log('✅ CV Nominatim Proxy: Respuesta búsqueda cacheada - ' . $query);
        }
        
        return rest_ensure_response($data);
    }
    
    /**
     * Añadir URL del proxy a JavaScript
     */
    public function add_proxy_url() {
        if (is_shop() || is_product_category() || is_product_tag()) {
            wp_localize_script('jquery', 'cv_nominatim_proxy', array(
                'reverse_url' => rest_url('cv-front/v1/nominatim/reverse'),
                'search_url' => rest_url('cv-front/v1/nominatim/search'),
            ));
        }
    }
}

