<?php
/**
 * Shopper Modern Child Theme Functions
 * 
 * @package Shopper Modern
 * @since 1.0.0
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Enqueue parent and child theme styles
 */
function shopper_modern_enqueue_styles() {
    // Cargar estilos del tema padre
    wp_enqueue_style(
        'shopper-parent-style',
        get_template_directory_uri() . '/style.css',
        array(),
        wp_get_theme()->parent()->get('Version')
    );
    
    // Cargar estilos del child theme
    wp_enqueue_style(
        'shopper-modern-style',
        get_stylesheet_uri(),
        array('shopper-parent-style'),
        wp_get_theme()->get('Version')
    );
    
    // Cargar Google Fonts (Poppins para headings modernos)
    wp_enqueue_style(
        'shopper-modern-fonts',
        'https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap',
        array(),
        null
    );
    
    // Cargar estilos de WooCommerce personalizados CON MÁXIMA PRIORIDAD
    if (class_exists('WooCommerce')) {
        wp_enqueue_style(
            'shopper-modern-woocommerce',
            get_stylesheet_directory_uri() . '/woocommerce.css',
            array('shopper-modern-style', 'woocommerce-general', 'shopper-woocommerce-style'),
            wp_get_theme()->get('Version') . '.' . time() // Forzar recarga con timestamp
        );
    }
    
    // Cargar animaciones CSS
    wp_enqueue_style(
        'shopper-modern-animations',
        get_stylesheet_directory_uri() . '/assets/css/animations.css',
        array('shopper-modern-style'),
        wp_get_theme()->get('Version')
    );
    
    // Cargar JavaScript personalizado si existe
    if (file_exists(get_stylesheet_directory() . '/assets/js/custom.js')) {
        wp_enqueue_script(
            'shopper-modern-custom',
            get_stylesheet_directory_uri() . '/assets/js/custom.js',
            array('jquery'),
            wp_get_theme()->get('Version'),
            true
        );
    }
}
add_action('wp_enqueue_scripts', 'shopper_modern_enqueue_styles', 999); // Prioridad MUY alta

/**
 * Añadir soporte para características modernas
 */
function shopper_modern_setup() {
    // Añadir soporte para editor de bloques
    add_theme_support('align-wide');
    add_theme_support('responsive-embeds');
    add_theme_support('editor-styles');
    
    // Añadir colores personalizados al editor
    add_theme_support('editor-color-palette', array(
        array(
            'name'  => __('Primary', 'shopper-modern'),
            'slug'  => 'primary',
            'color' => '#2196F3',
        ),
        array(
            'name'  => __('Secondary', 'shopper-modern'),
            'slug'  => 'secondary',
            'color' => '#FF5722',
        ),
        array(
            'name'  => __('Accent', 'shopper-modern'),
            'slug'  => 'accent',
            'color' => '#00BCD4',
        ),
        array(
            'name'  => __('Success', 'shopper-modern'),
            'slug'  => 'success',
            'color' => '#4CAF50',
        ),
        array(
            'name'  => __('Warning', 'shopper-modern'),
            'slug'  => 'warning',
            'color' => '#FF9800',
        ),
        array(
            'name'  => __('Error', 'shopper-modern'),
            'slug'  => 'error',
            'color' => '#F44336',
        ),
    ));
}
add_action('after_setup_theme', 'shopper_modern_setup');

/**
 * Personalizar WooCommerce
 */
function shopper_modern_woocommerce_setup() {
    // Cambiar número de productos por fila
    add_filter('loop_shop_columns', function() {
        return 4; // 4 columnas en desktop
    });
    
    // Cambiar número de productos relacionados
    add_filter('woocommerce_output_related_products_args', function($args) {
        $args['posts_per_page'] = 4;
        $args['columns'] = 4;
        return $args;
    });
}
add_action('after_setup_theme', 'shopper_modern_woocommerce_setup');

/**
 * Añadir clases CSS personalizadas al body
 */
function shopper_modern_body_classes($classes) {
    $classes[] = 'modern-theme';
    $classes[] = 'smooth-animations';
    
    return $classes;
}
add_filter('body_class', 'shopper_modern_body_classes');

/**
 * Optimización: Remover emoji scripts innecesarios
 */
function shopper_modern_disable_emojis() {
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('admin_print_scripts', 'print_emoji_detection_script');
    remove_action('wp_print_styles', 'print_emoji_styles');
    remove_action('admin_print_styles', 'print_emoji_styles');
}
add_action('init', 'shopper_modern_disable_emojis');

/**
 * Añadir snippet de schema.org para SEO (opcional)
 */
function shopper_modern_schema_org() {
    if (is_singular('product')) {
        ?>
        <script type="application/ld+json">
        {
            "@context": "https://schema.org/",
            "@type": "Product",
            "name": "<?php echo esc_js(get_the_title()); ?>",
            "image": "<?php echo esc_url(get_the_post_thumbnail_url()); ?>",
            "description": "<?php echo esc_js(wp_strip_all_tags(get_the_excerpt())); ?>"
        }
        </script>
        <?php
    }
}
add_action('wp_head', 'shopper_modern_schema_org');

/**
 * Convertir enlaces de YouTube en videos embebidos
 * Convierte automáticamente URLs de YouTube en iframes embebidos en descripciones de productos
 */
function cv_embed_youtube_videos($content) {
    // Solo aplicar en productos de WooCommerce
    if (!is_singular('product') && !is_shop() && !is_product_category()) {
        return $content;
    }
    
    // Patrón para detectar URLs de YouTube (varios formatos)
    $patterns = array(
        // https://www.youtube.com/watch?v=VIDEO_ID
        '/https?:\/\/(?:www\.)?youtube\.com\/watch\?v=([a-zA-Z0-9_-]+)(&[^\s<]*)?/i',
        // https://youtu.be/VIDEO_ID
        '/https?:\/\/(?:www\.)?youtu\.be\/([a-zA-Z0-9_-]+)(\?[^\s<]*)?/i',
        // https://www.youtube.com/embed/VIDEO_ID
        '/https?:\/\/(?:www\.)?youtube\.com\/embed\/([a-zA-Z0-9_-]+)(\?[^\s<]*)?/i',
    );
    
    foreach ($patterns as $pattern) {
        // Buscar todos los enlaces de YouTube
        if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $video_id = $match[1];
                $full_url = $match[0];
                
                // Crear iframe embebido responsive
                $iframe = '<div class="cv-youtube-embed" style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; max-width: 100%; margin: 20px 0; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">' .
                         '<iframe src="https://www.youtube.com/embed/' . esc_attr($video_id) . '?rel=0&modestbranding=1" ' .
                         'style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: 0;" ' .
                         'frameborder="0" ' .
                         'allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" ' .
                         'allowfullscreen>' .
                         '</iframe>' .
                         '</div>';
                
                // Reemplazar el enlace por el iframe
                $content = str_replace($full_url, $iframe, $content);
            }
        }
    }
    
    return $content;
}
// Aplicar a la descripción del producto (contenido principal)
add_filter('the_content', 'cv_embed_youtube_videos', 20);

// Aplicar también a la descripción corta del producto
add_filter('woocommerce_short_description', 'cv_embed_youtube_videos', 20);

// Aplicar también al editor de bloques
add_filter('render_block', function($block_content, $block) {
    if (is_singular('product')) {
        return cv_embed_youtube_videos($block_content);
    }
    return $block_content;
}, 20, 2);

