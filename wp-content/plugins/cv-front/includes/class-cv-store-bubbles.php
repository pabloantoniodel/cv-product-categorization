<?php
/**
 * Sistema de Burbujas Animadas para Geolocalización de Tiendas
 * 
 * Visualización interactiva de tiendas cercanas con burbujas flotantes
 * 
 * @package CV_Front
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase CV_Store_Bubbles
 * 
 * Gestiona el sistema de burbujas animadas para mostrar tiendas cercanas
 * Shortcode: [cv_store_bubbles]
 */
class CV_Store_Bubbles {
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Inicializar hooks
     */
    private function init_hooks() {
        // Registrar shortcode
        add_shortcode('cv_store_bubbles', array($this, 'render_bubbles'));
        
        // Cargar estilos y scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        
        // AJAX: obtener tiendas cercanas
        add_action('wp_ajax_cv_get_nearby_stores', array($this, 'ajax_get_nearby_stores'));
        add_action('wp_ajax_nopriv_cv_get_nearby_stores', array($this, 'ajax_get_nearby_stores'));
    }
    
    /**
     * Cargar assets (CSS y JS)
     */
    public function enqueue_assets() {
        // Solo cargar si el shortcode está presente
        if (!is_singular()) {
            return;
        }
        
        global $post;
        if (!$post || !has_shortcode($post->post_content, 'cv_store_bubbles')) {
            return;
        }
        
        // CSS
        wp_enqueue_style('cv-store-bubbles',
            CV_FRONT_PLUGIN_URL . 'assets/css/store-bubbles.css',
            array(),
            CV_FRONT_VERSION
        );
        
        // JavaScript
        wp_enqueue_script('cv-bubble-engine',
            CV_FRONT_PLUGIN_URL . 'assets/js/bubble-engine.js',
            array('jquery'),
            CV_FRONT_VERSION,
            true
        );
        
        // Localizar script
        wp_localize_script('cv-bubble-engine', 'cvBubblesData', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cv_store_bubbles'),
            'home_url' => home_url(),
        ));
    }
    
    /**
     * Renderizar shortcode de burbujas
     * 
     * Uso: [cv_store_bubbles radius="10" limit="50"]
     */
    public function render_bubbles($atts) {
        $atts = shortcode_atts(array(
            'radius' => 10,      // Radio en km
            'limit' => 50,       // Máximo de tiendas
            'view' => 'bubbles', // Vista por defecto: 'bubbles' o 'map'
        ), $atts, 'cv_store_bubbles');
        
        ob_start();
        include CV_FRONT_PLUGIN_DIR . 'views/bubbles-view.php';
        return ob_get_clean();
    }
    
    /**
     * AJAX: Obtener tiendas cercanas
     */
    public function ajax_get_nearby_stores() {
        check_ajax_referer('cv_store_bubbles', 'nonce');
        
        $lat = isset($_POST['lat']) ? floatval($_POST['lat']) : 0;
        $lng = isset($_POST['lng']) ? floatval($_POST['lng']) : 0;
        $radius = isset($_POST['radius']) ? intval($_POST['radius']) : 10;
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 50;
        
        if (!$lat || !$lng) {
            wp_send_json_error(array('message' => 'Coordenadas no válidas'));
        }
        
        $stores = $this->get_nearby_stores($lat, $lng, $radius, $limit);
        
        wp_send_json_success(array(
            'stores' => $stores,
            'total' => count($stores),
            'user_location' => array('lat' => $lat, 'lng' => $lng)
        ));
    }
    
    /**
     * Obtener tiendas cercanas usando fórmula de Haversine
     */
    private function get_nearby_stores($user_lat, $user_lng, $radius_km, $limit) {
        global $wpdb;
        
        // Query optimizada con cálculo de distancia Haversine
        $query = $wpdb->prepare("
            SELECT 
                u.ID as vendor_id,
                u.user_login,
                u.display_name as store_name,
                m1.meta_value as store_lat,
                m2.meta_value as store_lng,
                m3.meta_value as store_logo,
                m4.meta_value as store_banner,
                m5.meta_value as store_location,
                (
                    6371 * acos(
                        cos(radians(%f)) * 
                        cos(radians(CAST(m1.meta_value AS DECIMAL(10,8)))) * 
                        cos(radians(CAST(m2.meta_value AS DECIMAL(10,8))) - radians(%f)) + 
                        sin(radians(%f)) * 
                        sin(radians(CAST(m1.meta_value AS DECIMAL(10,8))))
                    )
                ) AS distance
            FROM {$wpdb->users} u
            INNER JOIN {$wpdb->usermeta} m1 ON u.ID = m1.user_id AND m1.meta_key = 'wcfm_store_location_data_latitude'
            INNER JOIN {$wpdb->usermeta} m2 ON u.ID = m2.user_id AND m2.meta_key = 'wcfm_store_location_data_longitude'
            LEFT JOIN {$wpdb->usermeta} m3 ON u.ID = m3.user_id AND m3.meta_key = 'store_logo'
            LEFT JOIN {$wpdb->usermeta} m4 ON u.ID = m4.user_id AND m4.meta_key = 'wcfm_store_banner'
            LEFT JOIN {$wpdb->usermeta} m5 ON u.ID = m5.user_id AND m5.meta_key = 'wcfm_store_location'
            WHERE EXISTS (
                SELECT 1 FROM {$wpdb->usermeta} um 
                WHERE um.user_id = u.ID 
                AND um.meta_key = 'wp_capabilities' 
                AND um.meta_value LIKE '%%wcfm_vendor%%'
            )
            HAVING distance < %f
            ORDER BY distance ASC
            LIMIT %d
        ", $user_lat, $user_lng, $user_lat, $radius_km, $limit);
        
        $results = $wpdb->get_results($query);
        
        // Formatear resultados
        $stores = array();
        foreach ($results as $store) {
            $stores[] = array(
                'id' => $store->vendor_id,
                'name' => $store->store_name,
                'slug' => $store->user_login,
                'logo' => $this->get_store_logo_url($store->store_logo),
                'banner' => $this->get_store_banner_url($store->store_banner),
                'lat' => floatval($store->store_lat),
                'lng' => floatval($store->store_lng),
                'distance' => round(floatval($store->distance), 2),
                'url' => home_url('/store/' . $store->user_login . '/'),
                'location' => $store->store_location
            );
        }
        
        return $stores;
    }
    
    /**
     * Obtener URL del logo de la tienda
     */
    private function get_store_logo_url($logo_id) {
        if (!$logo_id) {
            return CV_FRONT_PLUGIN_URL . 'assets/images/default-store-logo.png';
        }
        
        $url = wp_get_attachment_url($logo_id);
        return $url ? $url : CV_FRONT_PLUGIN_URL . 'assets/images/default-store-logo.png';
    }
    
    /**
     * Obtener URL del banner de la tienda
     */
    private function get_store_banner_url($banner_id) {
        if (!$banner_id) {
            return '';
        }
        
        $url = wp_get_attachment_url($banner_id);
        return $url ? $url : '';
    }
}





