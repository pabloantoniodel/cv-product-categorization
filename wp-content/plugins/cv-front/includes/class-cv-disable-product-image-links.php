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
        // Filtrar contenido de productos con prioridad MUY ALTA (999)
        // Para ejecutar DESPUÉS de todos los demás filtros
        add_filter('the_content', array($this, 'remove_image_links_from_product'), 999);
        
        // También aplicar al contenido corto (excerpt)
        add_filter('woocommerce_short_description', array($this, 'remove_image_links'), 999);
        
        // Aplicar también directamente a la descripción de WooCommerce
        add_filter('woocommerce_product_description_heading', array($this, 'init_description_filter'));
        
        error_log('✅ CV Disable Product Image Links: Clase inicializada');
    }
    
    /**
     * Inicializar filtro de descripción
     */
    public function init_description_filter() {
        add_filter('woocommerce_product_get_description', array($this, 'remove_image_links'), 999);
        return null; // No cambiar el heading
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
        
        // Patrón mejorado para detectar enlaces que contienen imágenes
        // Captura <a ...><img ...></a> con cualquier contenido entre las etiquetas
        // Incluyendo espacios, noscript, etc.
        $pattern = '/<a\s+[^>]*href=[^>]*>\s*(<img[^>]*>.*?<\/noscript>|<img[^>]*>)\s*(?:&nbsp;|\s)*<\/a>/is';
        
        // Reemplazar <a><img>...</a> por solo <img>
        $content = preg_replace($pattern, '$1', $content);
        
        // Segundo patrón más simple para casos básicos
        $pattern2 = '/<a\s+[^>]*>\s*(<img[^>]*>)\s*<\/a>/i';
        $content = preg_replace($pattern2, '$1', $content);
        
        return $content;
    }
}

// Inicializar
new CV_Disable_Product_Image_Links();

