<?php
/**
 * Plugin Name: CV Tab Publicaciones Vendedores
 * Description: Añade pestaña de publicaciones en tiendas WCFM
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class CV_Publicaciones_Tab {
    
    public function __construct() {
        // Añadir pestaña
        add_filter('wcfmmp_store_tabs', array($this, 'add_publicaciones_tab'), 90, 2);
        
        // Query vars
        add_filter('wcfmp_store_default_query_vars', array($this, 'publicaciones_query_var'));
        
        // URL de pestaña
        add_filter('wcfmp_store_tabs_url', array($this, 'publicaciones_tab_url'), 10, 2);
        
        // Template
        add_filter('wcfmmp_store_default_template', array($this, 'publicaciones_template'), 50, 2);
        
        // Rewrite rules
        add_action('wcfmmp_rewrite_rules_loaded', array($this, 'publicaciones_rewrite_rules'), 8);
        
        // Endpoint y query vars
        add_action('init', array($this, 'publicaciones_endpoint'), 12);
        add_filter('query_vars', array($this, 'register_query_vars'));
    }
    
    public function register_query_vars($vars) {
        $vars[] = 'publicaciones';
        return $vars;
    }
    
    public function add_publicaciones_tab($store_tabs, $vendor_id) {
        $store_tabs['publicaciones'] = __('Publicaciones', 'wc-multivendor-marketplace');
        return $store_tabs;
    }
    
    public function publicaciones_query_var($query_var) {
        if (get_query_var('publicaciones')) {
            $query_var = 'publicaciones';
        }
        return $query_var;
    }
    
    public function publicaciones_tab_url($store_tab_url, $tab) {
        if ($tab == 'publicaciones') {
            $store_tab_url = $store_tab_url . 'publicaciones';
        }
        return $store_tab_url;
    }
    
    public function publicaciones_template($template, $tab) {
        if ($tab == 'publicaciones') {
            $template = 'store/wcfmmp-view-store-publicaciones.php';
        }
        return $template;
    }
    
    public function publicaciones_rewrite_rules($wcfm_store_url) {
        global $WCFMmp;
        $store_endpoint = $WCFMmp->wcfmmp_rewrite->store_endpoint('publicaciones');
        add_rewrite_rule(
            $wcfm_store_url . '/([^/]+)/' . $store_endpoint . '/?$',
            'index.php?post_type=product&' . $wcfm_store_url . '=$matches[1]&' . $store_endpoint . '=true',
            'top'
        );
        add_rewrite_rule(
            $wcfm_store_url . '/([^/]+)/' . $store_endpoint . '/page/?([0-9]{1,})/?$',
            'index.php?post_type=product&' . $wcfm_store_url . '=$matches[1]&paged=$matches[2]&' . $store_endpoint . '=true',
            'top'
        );
    }
    
    public function publicaciones_endpoint() {
        $wcfm_store_url = get_option('wcfm_store_url', 'store');
        add_rewrite_endpoint('publicaciones', EP_ROOT | EP_PAGES);
    }
}

new CV_Publicaciones_Tab();

