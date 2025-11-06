<?php
/**
 * Desactivar comentarios en posts de Radio Comercio
 * 
 * @package CV_Front
 * @since 2.7.2
 */

if (!defined('ABSPATH')) {
    exit;
}

class CV_Disable_Comments_RadioComercio {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Desactivar comentarios en posts de categorías específicas
        add_filter('comments_open', array($this, 'disable_comments'), 20, 2);
        add_filter('pings_open', array($this, 'disable_comments'), 20, 2);
        
        // Ocultar formulario de comentarios
        add_filter('comments_template', array($this, 'hide_comments_template'), 20);
        
        // Ocultar comentarios del admin bar
        add_action('admin_bar_menu', array($this, 'remove_comments_admin_bar'), 999);
    }
    
    /**
     * Desactivar comentarios en categorías específicas
     * 
     * @param bool $open Estado de comentarios
     * @param int $post_id ID del post
     * @return bool
     */
    public function disable_comments($open, $post_id) {
        $post = get_post($post_id);
        
        if (!$post || $post->post_type !== 'post') {
            return $open;
        }
        
        // Verificar si el post está en las categorías de Radio Comercio o Autónomos
        $categories = wp_get_post_categories($post_id, array('fields' => 'names'));
        
        if (in_array('Radio Comercio', $categories) || in_array('Autónomos', $categories)) {
            return false;
        }
        
        return $open;
    }
    
    /**
     * Ocultar template de comentarios
     * 
     * @param string $template Path del template
     * @return string
     */
    public function hide_comments_template($template) {
        if (is_single()) {
            $post_id = get_the_ID();
            $categories = wp_get_post_categories($post_id, array('fields' => 'names'));
            
            if (in_array('Radio Comercio', $categories) || in_array('Autónomos', $categories)) {
                return dirname(__FILE__) . '/../templates/comments-disabled.php';
            }
        }
        
        return $template;
    }
    
    /**
     * Remover comentarios del admin bar
     */
    public function remove_comments_admin_bar($wp_admin_bar) {
        if (is_single()) {
            $post_id = get_the_ID();
            $categories = wp_get_post_categories($post_id, array('fields' => 'names'));
            
            if (in_array('Radio Comercio', $categories) || in_array('Autónomos', $categories)) {
                $wp_admin_bar->remove_node('comments');
            }
        }
    }
}

// Inicializar
new CV_Disable_Comments_RadioComercio();

