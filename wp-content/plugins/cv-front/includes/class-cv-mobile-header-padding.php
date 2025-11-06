<?php
/**
 * Padding superior para iconos de header móvil (carrito y hamburguesa)
 *
 * @package CV_Front
 * @since 2.4.1
 */

if (!defined('ABSPATH')) {
    exit;
}

class CV_Mobile_Header_Padding {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
    }
    
    /**
     * Cargar estilos
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            'cv-mobile-header-padding',
            CV_FRONT_PLUGIN_URL . 'assets/css/mobile-header-padding.css',
            array(),
            CV_FRONT_VERSION,
            'all'
        );
    }
}

