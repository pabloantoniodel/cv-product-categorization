<?php
/**
 * Desactivar Enlaces en Imágenes de Productos
 * 
 * Elimina los enlaces de las imágenes en la descripción de productos de WooCommerce
 * para evitar que los usuarios hagan clic y salgan de la página del producto
 * 
 * @package CV_Front
 * @since 3.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class CV_Disable_Product_Image_Links {
    
    public function __construct() {
        // Filtrar contenido de productos antes de mostrarlo
        add_filter('the_content', array($this, 'remove_image_links_from_product'), 20);
        
        // También aplicar al contenido corto (excerpt)
        add_filter('woocommerce_short_description', array($this, 'remove_image_links'), 20);
        
        error_log('✅ CV Disable Product Image Links: Clase inicializada');
    }
    
    /**
     * Remover enlaces solo en páginas de producto
     */
    public function remove_image_links_from_product($content) {
        // Solo aplicar en páginas de producto único
        if (is_product()) {
            return $this->remove_image_links($content);
        }
        
        return $content;
    }
    
    /**
     * Remover enlaces de imágenes del contenido
     */
    public function remove_image_links($content) {
        if (empty($content)) {
            return $content;
        }
        
        // Patrón para detectar enlaces que contienen imágenes
        // <a ...><img ... /></a> o <a ...><img ...></a>
        $pattern = '/<a([^>]*)>\s*(<img[^>]*>)\s*<\/a>/i';
        
        // Reemplazar <a><img></a> por solo <img>
        $content = preg_replace($pattern, '$2', $content);
        
        return $content;
    }
}

// Inicializar
new CV_Disable_Product_Image_Links();

