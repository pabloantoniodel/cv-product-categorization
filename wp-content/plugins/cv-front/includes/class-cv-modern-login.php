    <?php
/**
 * Clase para modernizar el login de WooCommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

class CV_Modern_Login {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
        add_action('woocommerce_before_customer_login_form', array($this, 'login_header'));
        add_action('woocommerce_after_customer_login_form', array($this, 'login_footer'));
    }
    
    /**
     * Encolar estilos modernos
     */
    public function enqueue_styles() {
        // Estilos globales en toda la web (incluyendo centrado de botones)
        wp_enqueue_style(
            'cv-modern-login',
            CV_FRONT_PLUGIN_URL . 'assets/css/modern-login.css',
            array(),
            CV_FRONT_VERSION
        );
        
        // Script de paginación con scroll (productos y mi cuenta)
        // IMPORTANTE: Se carga en HEADER con prioridad ALTA para ejecutarse ANTES que WCFM
        if (is_shop() || is_product_category() || is_product_tag() || is_account_page()) {
            wp_enqueue_script(
                'cv-pagination-scroll',
                CV_FRONT_PLUGIN_URL . 'assets/js/pagination-scroll.js',
                array('jquery'),
                CV_FRONT_VERSION,
                false // FALSE = cargar en HEADER, no en footer
            );
        }
    }
    
    /**
     * Cabecera del login con diseño moderno
     */
    public function login_header() {
        if (!is_user_logged_in()) {
            ?>
            <div class="cv-login-wrapper">
                <div class="cv-login-container">
                    <div class="cv-login-brand">
                        <div class="cv-login-logo">
                            <?php 
                            $custom_logo_id = get_theme_mod('custom_logo');
                            if ($custom_logo_id) {
                                echo wp_get_attachment_image($custom_logo_id, 'full', false, array('class' => 'cv-logo-img'));
                            } else {
                                echo '<h1>' . get_bloginfo('name') . '</h1>';
                            }
                            ?>
                        </div>
                        <p class="cv-login-tagline"><?php echo get_bloginfo('description'); ?></p>
                    </div>
                    <div class="woocommerce-form-login-wrapper">
            <?php
        }
    }
    
    /**
     * Pie del login
     */
    public function login_footer() {
        if (!is_user_logged_in()) {
            ?>
                    </div>
                </div>
            </div>
            <?php
        }
    }
}

