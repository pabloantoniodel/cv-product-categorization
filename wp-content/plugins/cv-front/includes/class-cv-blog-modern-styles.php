<?php
/**
 * Estilos Modernos para Blog (Categorías y Posts)
 * 
 * @package CV_Front
 * @since 2.7.1
 */

if (!defined('ABSPATH')) {
    exit;
}

class CV_Blog_Modern_Styles {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
    }
    
    /**
     * Encolar estilos modernos para blog
     */
    public function enqueue_styles() {
        // Solo en categorías, archivo, posts individuales
        if (is_category() || is_archive() || is_single() || is_home() || is_front_page() || is_page()) {
            wp_enqueue_style(
                'cv-blog-modern',
                plugins_url('assets/css/blog-modern.css', dirname(__FILE__)),
                array(),
                '2.8.8.' . time() // Versión con timestamp para forzar recarga
            );
        }
    }
}

// Inicializar
new CV_Blog_Modern_Styles();

