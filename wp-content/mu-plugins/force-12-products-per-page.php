<?php
/**
 * Plugin Name: CV Force 12 Products Per Page
 * Description: Fuerza 12 productos por página en tiendas de vendedores
 * Version: 1.0.0
 * Author: Ciudad Virtual
 */

// Forzar 12 productos por página en tiendas de vendedores
add_action('pre_get_posts', function($query) {
    // Solo en frontend y en queries principales de productos
    if (is_admin() || !$query->is_main_query()) {
        return;
    }
    
    // Si es una tienda de vendedor
    if (function_exists('wcfmmp_is_store_page') && wcfmmp_is_store_page()) {
        $query->set('posts_per_page', 12);
        error_log('CV: Forzando 12 productos por página en tienda');
    }
    
    // Si es página de productos de WooCommerce
    if ($query->is_post_type_archive('product') || $query->is_tax(get_object_taxonomies('product'))) {
        if (!$query->get('posts_per_page') || $query->get('posts_per_page') == 10) {
            $query->set('posts_per_page', 12);
            error_log('CV: Forzando 12 productos por página en archivo WooCommerce');
        }
    }
}, 99);

// También forzar en el filtro de WooCommerce
add_filter('loop_shop_per_page', function($cols) {
    return 12;
}, 99);

