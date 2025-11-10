<?php
/**
 * Geolocation Default Disabled
 * 
 * Desactiva la geolocalización por defecto en WCFM
 * 
 * @package CV_Front
 * @since 3.4.3
 */

if (!defined('ABSPATH')) {
    exit;
}

class CV_Geolocation_Default_Disabled {
    
    public function __construct() {
        // Desactivar geolocalización por defecto en WCFM
        add_filter('wcfm_is_allow_store_list_by_user_location', array($this, 'disable_default_geolocation'), 999);
        add_filter('wcfm_is_allow_product_list_by_user_location', array($this, 'disable_default_geolocation'), 999);
        
        // Cambiar orden por defecto (NO distance)
        add_filter('wcfmmp_stores_default_orderby', array($this, 'change_default_orderby'), 999);
        add_filter('woocommerce_default_catalog_orderby', array($this, 'change_default_catalog_orderby'), 999);
        
        // Eliminar parámetros de geolocalización de la URL por defecto
        add_action('template_redirect', array($this, 'clean_geo_params'), 1);
    }
    
    /**
     * Desactivar geolocalización por defecto
     */
    public function disable_default_geolocation($is_allowed) {
        // Solo permitir si el usuario ha activado explícitamente la geolocalización
        // Esto se verifica mediante JavaScript en el frontend
        return false;
    }
    
    /**
     * Cambiar orden por defecto de tiendas (NO distance)
     */
    public function change_default_orderby($orderby) {
        // Si el orden es 'distance', cambiarlo a 'menu_order' (relevancia)
        if ($orderby === 'distance') {
            return 'menu_order';
        }
        return $orderby;
    }
    
    /**
     * Cambiar orden por defecto del catálogo (NO distance)
     */
    public function change_default_catalog_orderby($orderby) {
        // Si el orden es 'distance', cambiarlo a 'menu_order' (relevancia)
        if ($orderby === 'distance') {
            return 'menu_order';
        }
        return $orderby;
    }
    
    /**
     * Limpiar parámetros de geolocalización de la URL si no está activada
     */
    public function clean_geo_params() {
        // Solo en páginas de productos o tiendas
        if (!is_shop() && !is_product_category() && !is_product_tag() && 
            !(function_exists('wcfmmp_is_stores_list_page') && wcfmmp_is_stores_list_page())) {
            return;
        }
        
        // Si hay parámetros de geolocalización pero no está activada, limpiar
        if (isset($_GET['radius_lat']) || isset($_GET['radius_lng']) || isset($_GET['radius_range'])) {
            // Verificar si el usuario tiene geolocalización activada (esto se hace en JS)
            // Por ahora, simplemente no forzamos estos parámetros
        }
    }
}

new CV_Geolocation_Default_Disabled();

