<?php
/**
 * Fix para filtro de radio en categorías
 * WCFM no soporta filtro de distancia en categorías, solo en shop
 * Esta clase elimina los parámetros de radio en categorías ANTES de que WCFM los procese
 */

if (!defined('ABSPATH')) {
    exit;
}

class CV_Category_Radius_Fix {
    
    public function __construct() {
        // Desactivar filtro de geolocalización de WCFM en categorías
        add_filter('wcfm_is_allow_product_list_geo_location_filter', array($this, 'disable_geo_filter_in_categories'), 10);
        
        // Hook con prioridad ALTA (1) para ejecutarse ANTES que WCFM
        add_action('template_redirect', array($this, 'remove_radius_params_in_categories'), 1);
    }
    
    /**
     * Desactivar filtro de geolocalización en categorías
     */
    public function disable_geo_filter_in_categories($allow) {
        // Desactivar en categorías y tags, mantener solo en shop
        if (is_product_category() || is_product_tag() || is_product_taxonomy()) {
            error_log('🚫 CV Front: Desactivando filtro de geolocalización en categoría');
            return false;
        }
        return $allow;
    }
    
    /**
     * Eliminar parámetros de radio en categorías de productos
     */
    public function remove_radius_params_in_categories() {
        // DESACTIVADO TEMPORALMENTE - Puede estar causando problemas con la carga de productos
        return;
        
        // Solo en categorías de productos
        if (!is_product_category() && !is_product_tag() && !is_product_taxonomy()) {
            return;
        }
        
        // Verificar si hay parámetros de radio en la URL
        $has_radius_params = isset($_GET['radius_range']) || isset($_GET['radius_lat']) || 
                            isset($_GET['radius_lng']) || isset($_GET['radius_addr']);
        
        if (!$has_radius_params) {
            return;
        }
        
        error_log('⚠️ CV Front: Categoría con parámetros de radio detectada - WCFM no soporta esto');
        error_log('🔄 CV Front: Redirigiendo sin parámetros de radio...');
        
        // Construir URL sin parámetros de radio
        $url = $_SERVER['REQUEST_URI'];
        $url = remove_query_arg(array('radius_range', 'radius_lat', 'radius_lng', 'radius_addr'), $url);
        
        // Redirección 302 (temporal) 
        wp_redirect($url, 302);
        exit;
    }
}

