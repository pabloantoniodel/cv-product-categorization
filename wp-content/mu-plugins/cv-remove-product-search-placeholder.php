<?php
/**
 * Plugin Name: CV Remove Product Search Placeholder
 * Description: Elimina el atributo placeholder del buscador de productos de WooCommerce.
 * Version: 1.0.0
 * Author: Ciudad Virtual
 */

if (!defined('ABSPATH')) {
    exit;
}

add_filter('get_product_search_form', function ($form_html) {
    // Quitar cualquier atributo placeholder del input principal del buscador.
    $form_html = preg_replace('/\splaceholder="[^"]*"/', '', $form_html);
    $form_html = preg_replace("/\splaceholder='[^']*'/", '', $form_html);
    return $form_html;
}, 20);

add_filter('woocommerce_product_search_placeholder', '__return_empty_string', 20);

