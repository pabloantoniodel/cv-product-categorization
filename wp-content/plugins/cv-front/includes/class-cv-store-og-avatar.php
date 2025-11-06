<?php
/**
 * Cambiar imagen Open Graph de tiendas para usar avatar en lugar de banner
 * 
 * @package CV_Front
 */

if (!defined('ABSPATH')) {
    exit;
}

class CV_Store_OG_Avatar {
    
    public function __construct() {
        // Filtrar imagen Open Graph de WCFM (páginas de tienda)
        add_filter('wcfmmp_replace_og_img', array($this, 'use_avatar_for_og_image'), 10, 1);
        add_filter('wcfmmp_replace_twitter_img', array($this, 'use_avatar_for_og_image'), 10, 1);
        
        // Para tarjetas: capturar TODO el output y modificar meta tags
        add_action('template_redirect', array($this, 'start_card_buffer'));
    }
    
    /**
     * Iniciar buffer de output para tarjetas
     */
    public function start_card_buffer() {
        global $wp;
        $current_url = home_url(add_query_arg(array(), $wp->request));
        
        if (strpos($current_url, '/card/') !== false) {
            ob_start(array($this, 'replace_og_in_html'));
        }
    }
    
    /**
     * Reemplazar meta tags OG en el HTML completo
     */
    public function replace_og_in_html($html) {
        $avatar_url = $this->get_card_avatar_url();
        
        if (empty($avatar_url)) {
            return $html;
        }
        
        // Eliminar TODOS los meta og:image y twitter:image
        $html = preg_replace('/<meta\s+property=["\']og:image["\']\s+content=["\'][^"\']*["\']\s*\/?>\s*/i', '', $html);
        $html = preg_replace('/<meta\s+name=["\']twitter:image["\']\s+content=["\'][^"\']*["\']\s*\/?>\s*/i', '', $html);
        
        // Agregar SOLO el avatar justo antes de </head>
        $new_meta = "\n\t<!-- CV: Open Graph Avatar -->\n";
        $new_meta .= "\t<meta property=\"og:image\" content=\"" . esc_attr($avatar_url) . "\" />\n";
        $new_meta .= "\t<meta name=\"twitter:image\" content=\"" . esc_attr($avatar_url) . "\" />\n";
        
        $html = str_replace('</head>', $new_meta . '</head>', $html);
        
        return $html;
    }
    
    /**
     * Obtener URL del avatar de la tarjeta
     */
    private function get_card_avatar_url() {
        global $wp;
        $current_url = home_url(add_query_arg(array(), $wp->request));
        
        if (strpos($current_url, '/card/') === false) {
            return '';
        }
        
        // Extraer slug
        $url_parts = explode('/card/', $current_url);
        if (count($url_parts) < 2) {
            return '';
        }
        
        $card_slug = trim($url_parts[1], '/');
        
        // Buscar tarjeta
        $cards = get_posts(array(
            'post_type' => 'card',
            'name' => $card_slug,
            'posts_per_page' => 1
        ));
        
        if (empty($cards)) {
            return '';
        }
        
        $card = $cards[0];
        $author_id = $card->post_author;
        
        // PRIORIDAD 1: Buscar en wp_user_avatar (avatar subido por el usuario)
        $user_avatar_id = get_user_meta($author_id, 'wp_user_avatar', true);
        if ($user_avatar_id) {
            $user_avatar_url = wp_get_attachment_url($user_avatar_id);
            if ($user_avatar_url) {
                return $user_avatar_url;
            }
        }
        
        // PRIORIDAD 2: Buscar en simple_local_avatar
        $simple_avatar_id = get_user_meta($author_id, 'simple_local_avatar', true);
        if ($simple_avatar_id && is_array($simple_avatar_id) && isset($simple_avatar_id['media_id'])) {
            $simple_avatar_url = wp_get_attachment_url($simple_avatar_id['media_id']);
            if ($simple_avatar_url) {
                return $simple_avatar_url;
            }
        } elseif ($simple_avatar_id && is_numeric($simple_avatar_id)) {
            $simple_avatar_url = wp_get_attachment_url($simple_avatar_id);
            if ($simple_avatar_url) {
                return $simple_avatar_url;
            }
        }
        
        // PRIORIDAD 3: Featured image del post card
        $featured_image_id = get_post_thumbnail_id($card->ID);
        if ($featured_image_id) {
            $featured_url = wp_get_attachment_url($featured_image_id);
            if ($featured_url) {
                return $featured_url;
            }
        }
        
        // PRIORIDAD 4: Avatar de WordPress del autor (gravatar 512px)
        return get_avatar_url($author_id, array('size' => 512));
    }
    
    /**
     * Usar avatar del vendedor en lugar del banner para Open Graph
     */
    public function use_avatar_for_og_image($img_url) {
        // Solo aplicar en páginas de tienda
        if (!wcfm_is_store_page()) {
            return $img_url;
        }
        
        // Obtener ID del vendedor
        global $WCFMmp;
        $store_user = wcfmmp_get_store($WCFMmp->store->id);
        
        if (!$store_user) {
            return $img_url;
        }
        
        $vendor_id = $store_user->get_id();
        
        // Obtener configuración de la tienda
        $store_settings = get_user_meta($vendor_id, 'wcfmmp_profile_settings', true);
        
        // Intentar obtener el logo/avatar de la tienda
        $gravatar_id = isset($store_settings['gravatar']) ? $store_settings['gravatar'] : 0;
        
        if ($gravatar_id) {
            $avatar_url = wp_get_attachment_url($gravatar_id);
            
            if ($avatar_url) {
                return $avatar_url;
            }
        }
        
        // Fallback: usar avatar de WordPress del usuario
        $avatar_url = get_avatar_url($vendor_id, array('size' => 512));
        
        if ($avatar_url) {
            return $avatar_url;
        }
        
        // Si no hay nada, devolver la imagen original
        return $img_url;
    }
    
}

// Inicializar
new CV_Store_OG_Avatar();

