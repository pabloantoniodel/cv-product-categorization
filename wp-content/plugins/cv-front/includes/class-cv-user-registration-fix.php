<?php
/**
 * CV User Registration Fix
 *
 * Mejora el diseño del formulario de registro en pantallas grandes
 *
 * @package CV_Front
 * @since 2.4.1
 */

if (!defined('ABSPATH')) {
    exit;
}

class CV_User_Registration_Fix {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
    }
    
    /**
     * Enqueue estilos para páginas de User Registration
     */
    public function enqueue_styles() {
        // Solo en páginas con formularios de User Registration
        if (is_page() && (has_shortcode(get_post()->post_content, 'user_registration_form') || 
            strpos($_SERVER['REQUEST_URI'], 'tarjeta-visita-registro') !== false)) {
            
            wp_enqueue_style(
                'cv-user-registration-fix',
                CV_FRONT_PLUGIN_URL . 'assets/css/user-registration-fix.css',
                array(),
                CV_FRONT_VERSION
            );
            
            error_log('✅ CV Front: CSS de User Registration Fix cargado');
        }
    }
}


