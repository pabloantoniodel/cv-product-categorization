<?php
/**
 * Mostrar subcategorías en categorías de productos
 * Funciona tanto en URLs normales (/product-category/X/) como en búsquedas (?product_cat=X)
 */

if (!defined('ABSPATH')) {
    exit;
}

class CV_Subcategories {
    
    public function __construct() {
        // Desactivar mapa cuando hay categoría
        add_filter('wcfmmp_is_allow_product_list_map', array($this, 'disable_map_in_categories'));
        
        // Mostrar subcategorías ANTES del loop de productos (diseño moderno)
        add_action('woocommerce_before_shop_loop', array($this, 'show_subcategories'), 30);
        
        // Reescribir enlaces de categorías al formato de búsqueda
        add_filter('term_link', array($this, 'rewrite_category_links'), 10, 3);
        
        // Cargar JavaScript y CSS para Market
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    /**
     * Reescribir enlaces de categorías al formato de búsqueda (PHP)
     */
    public function rewrite_category_links($url, $term, $taxonomy) {
        // Solo para categorías de productos
        if ($taxonomy !== 'product_cat') {
            return $url;
        }
        
        // Construir URL en formato de búsqueda (funciona correctamente)
        $new_url = home_url('/?product_cat=' . $term->slug . '&post_type=product&s=');
        
        return $new_url;
    }
    
    /**
     * Desactivar mapa en categorías
     */
    public function disable_map_in_categories($allow) {
        // Desactivar mapa si hay parámetro product_cat O si es página de categoría
        if (isset($_REQUEST['product_cat']) || is_product_category()) {
            return false;
        }
        return $allow;
    }
    
    /**
     * Mostrar subcategorías antes de los productos
     */
    public function show_subcategories() {
        // Solo mostrar si hay parámetro product_cat en URL
        if (!isset($_REQUEST['product_cat'])) {
            return;
        }
        
        $category_slug = sanitize_text_field($_REQUEST['product_cat']);
        
        // Obtener el término de la categoría
        $term = get_term_by('slug', $category_slug, 'product_cat');
        
        if (!$term || is_wp_error($term)) {
            return;
        }
        
        // Título principal con clase CSS moderna
        echo '<h2 class="cv-subcategories-title">Subcategorías</h2>';
        echo '<div class="cv-category-name">' . esc_html($term->name) . '</div>';
        
        // Obtener subcategorías de la categoría actual
        $subcategories = get_terms(array(
            'taxonomy'    => 'product_cat',
            'hide_empty'  => true,
            'parent'      => $term->term_id
        ));
        
        if (empty($subcategories) || is_wp_error($subcategories)) {
            return;
        }
        
        $output = '<ul class="subcategories-list">';
        
        foreach ($subcategories as $subcat) {
            // Construir URL en formato de búsqueda
            $url = esc_url(get_home_url() . '/?product_cat=' . $subcat->slug . '&radius_range=2000&post_type=product&s=');
            $output .= '<li class="' . esc_attr($subcat->slug) . '">';
            $output .= '<a href="' . $url . '">' . esc_html($subcat->name) . '</a>';
            $output .= '</li>';
        }
        
        $output .= '</ul>';
        
        echo $output;
    }
    
    /**
     * Cargar JavaScript y CSS
     */
    public function enqueue_scripts() {
        // Cargar JavaScript para reescribir enlaces
        wp_enqueue_script(
            'cv-category-links-fixer',
            CV_FRONT_PLUGIN_URL . 'assets/js/category-modal.js',
            array('jquery'),
            CV_FRONT_VERSION,
            true
        );
        
        // Cargar CSS para subcategorías
        wp_enqueue_style(
            'cv-subcategories-style',
            CV_FRONT_PLUGIN_URL . 'assets/css/subcategories.css',
            array(),
            CV_FRONT_VERSION
        );
        
        // Cargar CSS para títulos modernos
        wp_enqueue_style(
            'cv-modern-titles',
            CV_FRONT_PLUGIN_URL . 'assets/css/modern-titles.css',
            array(),
            CV_FRONT_VERSION
        );
        
        // Cargar CSS para fix de cantidad en productos
        wp_enqueue_style(
            'cv-product-quantity-fix',
            CV_FRONT_PLUGIN_URL . 'assets/css/product-quantity-fix.css',
            array(),
            CV_FRONT_VERSION
        );
        
        // Cargar CSS para menú móvil moderno
        wp_enqueue_style(
            'cv-mobile-menu',
            CV_FRONT_PLUGIN_URL . 'assets/css/mobile-menu.css',
            array(),
            CV_FRONT_VERSION
        );
        
        wp_enqueue_style(
            'cv-wcfm-menu-wider',
            CV_FRONT_PLUGIN_URL . 'assets/css/wcfm-menu-wider.css',
            array(),
            CV_FRONT_VERSION
        );
        
        wp_enqueue_style(
            'cv-qr-code-fix',
            CV_FRONT_PLUGIN_URL . 'assets/css/qr-code-fix.css',
            array(),
            CV_FRONT_VERSION
        );
        
        // Cargar JavaScript para traducir textos del Wallet en carrito/checkout
        if (is_cart() || is_checkout()) {
            wp_enqueue_script(
                'cv-translate-wallet',
                CV_FRONT_PLUGIN_URL . 'assets/js/translate-wallet.js',
                array('jquery'),
                CV_FRONT_VERSION,
                true
            );
        }
        
        // Cargar JavaScript para botón de cierre del menú móvil
        wp_enqueue_script(
            'cv-mobile-menu-close',
            CV_FRONT_PLUGIN_URL . 'assets/js/mobile-menu-close.js',
            array('jquery'),
            CV_FRONT_VERSION,
            true
        );
        
        // Cargar JavaScript para forzar "Todas" en el buscador
        wp_enqueue_script(
            'cv-fix-search-label',
            CV_FRONT_PLUGIN_URL . 'assets/js/fix-search-label.js',
            array('jquery'),
            CV_FRONT_VERSION,
            true
        );
        
        // Cargar JavaScript para corregir textos WCFM
        wp_enqueue_script(
            'cv-wcfm-text-fix',
            CV_FRONT_PLUGIN_URL . 'assets/js/wcfm-text-fix.js',
            array('jquery'),
            CV_FRONT_VERSION,
            true
        );
        
        // Cargar JavaScript para scroll automático en paginación
        wp_enqueue_script(
            'cv-pagination-scroll',
            CV_FRONT_PLUGIN_URL . 'assets/js/pagination-scroll.js',
            array('jquery'),
            CV_FRONT_VERSION,
            true
        );
    }
}

