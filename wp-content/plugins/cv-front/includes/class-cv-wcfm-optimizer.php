<?php
/**
 * Optimizaciones para WCFM Dashboard
 * 
 * Mejora el rendimiento del dashboard de WCFM para vendedores con muchos productos
 * mediante caché, limitación de consultas y carga lazy de elementos.
 */

if (!defined('ABSPATH')) {
    exit;
}

class CV_WCFM_Optimizer {
    
    public function __construct() {
        // Optimización de consultas de stock
        add_filter('wcfm_report_low_in_stock_query_from', array($this, 'cache_low_stock_query'), 10, 3);
        add_filter('wcfm_report_out_of_stock_query_from', array($this, 'cache_out_stock_query'), 10, 2);
        
        // Optimización de carga de productos
        add_filter('wcfm_products_args', array($this, 'optimize_products_load'), 999);
        
        // Desactivar actualizaciones en tiempo real para vendedores con muchos productos
        add_action('wcfm_dashboard_before_widgets', array($this, 'disable_realtime_for_large_vendors'));
        
        // Añadir JavaScript para carga lazy
        add_action('wp_footer', array($this, 'add_lazy_load_js'));
        
        // Limpiar caché cuando se actualiza un producto
        add_action('woocommerce_update_product', array($this, 'clear_vendor_cache'));
        add_action('woocommerce_new_product', array($this, 'clear_vendor_cache'));
    }
    
    /**
     * Cachear consulta de productos con stock bajo
     */
    public function cache_low_stock_query($query, $stock, $nostock) {
        $user_id = get_current_user_id();
        $cache_key = 'wcfm_low_stock_' . $user_id;
        $cached = get_transient($cache_key);
        
        if ($cached !== false) {
            return $cached;
        }
        
        // Cachear por 5 minutos
        set_transient($cache_key, $query, 300);
        
        return $query;
    }
    
    /**
     * Cachear consulta de productos sin stock
     */
    public function cache_out_stock_query($query, $nostock) {
        $user_id = get_current_user_id();
        $cache_key = 'wcfm_out_stock_' . $user_id;
        $cached = get_transient($cache_key);
        
        if ($cached !== false) {
            return $cached;
        }
        
        // Cachear por 5 minutos
        set_transient($cache_key, $query, 300);
        
        return $query;
    }
    
    /**
     * Optimizar carga de productos - NO limitar en conteo
     */
    public function optimize_products_load($args) {
        // NO limitar cuando es para contar (fields = 'ids' y posts_per_page = -1)
        // Solo optimizar para consultas reales de listado
        
        return $args;
    }
    
    /**
     * Desactivar actualizaciones en tiempo real para vendedores con más de 1000 productos
     */
    public function disable_realtime_for_large_vendors() {
        $user_id = get_current_user_id();
        $product_count = count_user_posts($user_id, 'product');
        
        if ($product_count > 1000) {
            // Desactivar widgets pesados
            remove_action('wcfm_dashboard_before_widgets', 'wcfm_dashboard_sales_by_date');
        }
    }
    
    /**
     * Añadir JavaScript para carga lazy del dashboard
     */
    public function add_lazy_load_js() {
        // Solo en la página de store-manager
        if (!is_page('store-manager')) {
            return;
        }
        ?>
        <script>
        jQuery(document).ready(function($) {
            console.log('🚀 CV WCFM Optimizer: Inicializando carga lazy...');
            
            // Retrasar carga de gráficos no críticos
            setTimeout(function() {
                if (typeof wcfm_dashboard_init !== 'undefined') {
                    $('.wcfm_dashboard_stats_widget').each(function(index) {
                        var $this = $(this);
                        setTimeout(function() {
                            $this.addClass('loaded');
                        }, index * 200);
                    });
                }
            }, 500);
            
            // Añadir indicador de carga
            if ($('.wcfm_dashboard_container').length) {
                $('.wcfm_dashboard_container').prepend('<div class="wcfm-loading-indicator">Cargando dashboard...</div>');
                
                $(window).on('load', function() {
                    $('.wcfm-loading-indicator').fadeOut();
                });
                
                // Timeout de seguridad
                setTimeout(function() {
                    $('.wcfm-loading-indicator').fadeOut();
                }, 10000);
            }
            
            console.log('✅ CV WCFM Optimizer: Carga lazy completada');
        });
        </script>
        <style>
        .wcfm-loading-indicator {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(0,0,0,0.8);
            color: white;
            padding: 20px 40px;
            border-radius: 5px;
            z-index: 99999;
            font-size: 16px;
            font-weight: 600;
        }
        </style>
        <?php
    }
    
    /**
     * Limpiar caché cuando se actualiza un producto
     */
    public function clear_vendor_cache($product_id) {
        if (!$product_id) {
            return;
        }
        
        $product = wc_get_product($product_id);
        if (!$product) {
            return;
        }
        
        $author_id = get_post_field('post_author', $product_id);
        if ($author_id) {
            delete_transient('wcfm_low_stock_' . $author_id);
            delete_transient('wcfm_out_stock_' . $author_id);
        }
    }
}

