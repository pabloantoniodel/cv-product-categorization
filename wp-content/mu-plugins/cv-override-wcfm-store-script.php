<?php
/**
 * Plugin Name: CV Override WCFM Store Script
 * Description: Reemplaza el script de listado de comercios de WCFM por la versión personalizada y fuerza un número de versión nuevo.
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_enqueue_scripts', function () {
    $handle = 'wcfmmp_store_list_js';

    if (!wp_script_is($handle, 'enqueued')) {
        return;
    }

    $custom_script = content_url('plugins/wc-multivendor-marketplace-custom/assets/js/store-lists/wcfmmp-script-store-lists.js');
    if (!$custom_script) {
        return;
    }

    wp_dequeue_script($handle);
    wp_deregister_script($handle);

    wp_register_script(
        $handle,
        $custom_script,
        array('jquery'),
        '3.6.15-cv2',
        true
    );

    wp_enqueue_script($handle);
}, 50);

