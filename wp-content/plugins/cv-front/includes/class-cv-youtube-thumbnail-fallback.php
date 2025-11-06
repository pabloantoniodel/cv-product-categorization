<?php
/**
 * Fallback a Miniaturas de YouTube
 * 
 * Si un post no tiene imagen destacada, usa la miniatura del video de YouTube
 * 
 * @package CV_Front
 * @since 2.8.5
 */

if (!defined('ABSPATH')) {
    exit;
}

class CV_YouTube_Thumbnail_Fallback {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Interceptar cuando no hay thumbnail
        add_filter('post_thumbnail_html', array($this, 'fallback_to_youtube_thumbnail'), 10, 5);
        
        // También para has_post_thumbnail
        add_filter('has_post_thumbnail', array($this, 'has_youtube_thumbnail'), 10, 3);
        
        // Y para get_post_thumbnail_id
        add_filter('get_post_thumbnail_id', array($this, 'get_youtube_thumbnail_id'), 10, 2);
    }
    
    /**
     * Obtener ID de video de YouTube del contenido del post
     * 
     * @param int $post_id ID del post
     * @return string|false ID del video de YouTube o false
     */
    private function get_youtube_video_id($post_id) {
        $post = get_post($post_id);
        if (!$post) {
            return false;
        }
        
        // Buscar en el contenido del post
        $content = $post->post_content;
        
        // También buscar en el contenido procesado (puede tener shortcodes)
        $processed_content = apply_filters('the_content', $content);
        
        // Combinar ambos contenidos
        $search_content = $content . ' ' . $processed_content;
        
        // Patrones para URLs de YouTube (los IDs de YouTube tienen 11 caracteres)
        $patterns = array(
            '/youtube\.com\/watch\?v=([a-zA-Z0-9_-]{11})/',
            '/youtu\.be\/([a-zA-Z0-9_-]{11})/',
            '/youtube\.com\/embed\/([a-zA-Z0-9_-]{11})/',
            '/youtube-nocookie\.com\/embed\/([a-zA-Z0-9_-]{11})/'
        );
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $search_content, $matches)) {
                return $matches[1];
            }
        }
        
        return false;
    }
    
    /**
     * Obtener URL de la miniatura de YouTube
     * 
     * @param string $video_id ID del video de YouTube
     * @return string URL de la miniatura
     */
    private function get_youtube_thumbnail_url($video_id) {
        // maxresdefault es la resolución más alta disponible
        return "https://img.youtube.com/vi/{$video_id}/maxresdefault.jpg";
    }
    
    /**
     * Fallback: usar miniatura de YouTube si no hay thumbnail
     * 
     * @param string $html HTML del thumbnail
     * @param int $post_id ID del post
     * @param int $post_thumbnail_id ID del attachment
     * @param string|array $size Tamaño de la imagen
     * @param string $attr Atributos
     * @return string HTML del thumbnail
     */
    public function fallback_to_youtube_thumbnail($html, $post_id, $post_thumbnail_id, $size, $attr) {
        // Si ya hay thumbnail, no hacer nada
        if (!empty($html)) {
            return $html;
        }
        
        // Solo en posts
        if (get_post_type($post_id) !== 'post') {
            return $html;
        }
        
        // Buscar video de YouTube
        $video_id = $this->get_youtube_video_id($post_id);
        if (!$video_id) {
            return $html;
        }
        
        // Obtener URL de la miniatura
        $thumbnail_url = $this->get_youtube_thumbnail_url($video_id);
        
        // Obtener atributos del tamaño
        $size_class = is_array($size) ? implode('x', $size) : $size;
        $attr = is_array($attr) ? $attr : array();
        
        $default_attr = array(
            'src' => $thumbnail_url,
            'class' => "attachment-{$size_class} size-{$size_class} wp-post-image",
            'alt' => get_the_title($post_id),
            'loading' => 'lazy'
        );
        
        $attr = wp_parse_args($attr, $default_attr);
        
        // Generar HTML de la imagen
        $html = sprintf(
            '<img %s />',
            $this->build_attr_string($attr)
        );
        
        return $html;
    }
    
    /**
     * Construir string de atributos HTML
     * 
     * @param array $attr Atributos
     * @return string String de atributos
     */
    private function build_attr_string($attr) {
        $output = array();
        foreach ($attr as $key => $value) {
            $output[] = sprintf('%s="%s"', esc_attr($key), esc_attr($value));
        }
        return implode(' ', $output);
    }
    
    /**
     * Verificar si el post tiene thumbnail (real o de YouTube)
     * 
     * @param bool $has_thumbnail Si tiene thumbnail
     * @param int $post_id ID del post
     * @param int $thumbnail_id ID del thumbnail
     * @return bool
     */
    public function has_youtube_thumbnail($has_thumbnail, $post_id, $thumbnail_id) {
        // Si ya tiene thumbnail, no hacer nada
        if ($has_thumbnail) {
            return $has_thumbnail;
        }
        
        // Solo en posts
        if (get_post_type($post_id) !== 'post') {
            return $has_thumbnail;
        }
        
        // Verificar si hay video de YouTube
        $video_id = $this->get_youtube_video_id($post_id);
        return $video_id !== false;
    }
    
    /**
     * Devolver un ID ficticio para YouTube thumbnail
     * 
     * @param int $thumbnail_id ID del thumbnail
     * @param int $post_id ID del post
     * @return int ID del thumbnail
     */
    public function get_youtube_thumbnail_id($thumbnail_id, $post_id) {
        // Si ya tiene thumbnail, no hacer nada
        if ($thumbnail_id) {
            return $thumbnail_id;
        }
        
        // Solo en posts
        if (get_post_type($post_id) !== 'post') {
            return $thumbnail_id;
        }
        
        // Verificar si hay video de YouTube
        $video_id = $this->get_youtube_video_id($post_id);
        if ($video_id) {
            // Devolver un ID negativo para indicar que es de YouTube
            // El filtro post_thumbnail_html se encargará del resto
            return -1;
        }
        
        return $thumbnail_id;
    }
}

// Inicializar
new CV_YouTube_Thumbnail_Fallback();

