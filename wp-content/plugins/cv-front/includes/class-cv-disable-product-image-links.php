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
        
        // Filtrar el output de la pestaña de descripción (woocommerce_product_tabs)
        add_filter('woocommerce_product_tabs', array($this, 'filter_description_tab'), 999);
        
        error_log('✅ CV Disable Product Image Links: Clase inicializada');
    }
    
    /**
     * Filtrar el contenido de la pestaña de descripción
     */
    public function filter_description_tab($tabs) {
        if (isset($tabs['description']['callback'])) {
            // Guardar el callback original
            $original_callback = $tabs['description']['callback'];
            
            // Reemplazar con nuestro callback que filtra el contenido
            $tabs['description']['callback'] = function() use ($original_callback) {
                // Ejecutar el callback original y capturar el output
                ob_start();
                if (is_callable($original_callback)) {
                    call_user_func($original_callback);
                } else {
                    woocommerce_product_description_tab();
                }
                $output = ob_get_clean();
                
                // Aplicar filtro de enlaces
                echo $this->remove_image_links($output);
            };
        }
        
        return $tabs;
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
        
        // Usar DOMDocument para un parsing más robusto y preciso
        // Esto es más confiable que regex para HTML complejo
        if (class_exists('DOMDocument')) {
            // Suprimir errores de HTML mal formado
            libxml_use_internal_errors(true);
            
            $dom = new DOMDocument();
            $dom->encoding = 'UTF-8';
            
            // Agregar encoding UTF-8 y wrapper para evitar problemas con HTML fragmentado
            $html = '<?xml encoding="UTF-8">' . '<div>' . mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8') . '</div>';
            
            @$dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
            
            $xpath = new DOMXPath($dom);
            
            // Buscar todos los enlaces que contienen imágenes
            $links = $xpath->query('//a[.//img]');
            
            if ($links && $links->length > 0) {
                foreach ($links as $link) {
                    // Obtener todas las imágenes dentro del enlace
                    $images = $xpath->query('.//img', $link);
                    
                    if ($images && $images->length > 0) {
                        // Crear un fragmento para contener las imágenes
                        $fragment = $dom->createDocumentFragment();
                        
                        // Mover todas las imágenes fuera del enlace
                        foreach ($images as $img) {
                            $fragment->appendChild($img->cloneNode(true));
                        }
                        
                        // Reemplazar el enlace con solo las imágenes
                        $link->parentNode->replaceChild($fragment, $link);
                    }
                }
                
                // Obtener el contenido procesado
                $body = $dom->getElementsByTagName('div')->item(0);
                if ($body) {
                    $content = '';
                    foreach ($body->childNodes as $node) {
                        $content .= $dom->saveHTML($node);
                    }
                }
            }
            
            libxml_clear_errors();
        }
        
        // Fallback a regex si DOMDocument no está disponible o falla
        // Patrones mejorados para capturar TODOS los casos posibles
        
        // Patrón 1: Enlace con imagen simple <a href="..."><img></a>
        $pattern1 = '/<a\s+[^>]*href\s*=\s*["\'][^"\']*["\'][^>]*>\s*(?:&nbsp;|\s)*(<img[^>]*>)\s*(?:&nbsp;|\s)*<\/a>/is';
        $content = preg_replace($pattern1, '$1', $content);
        
        // Patrón 2: Enlace con imagen y noscript <a><noscript><img></noscript></a>
        $pattern2 = '/<a\s+[^>]*href\s*=\s*["\'][^"\']*["\'][^>]*>\s*(?:&nbsp;|\s)*(?:<noscript[^>]*>.*?)?(<img[^>]*>).*?(?:<\/noscript>)?\s*(?:&nbsp;|\s)*<\/a>/is';
        $content = preg_replace($pattern2, '$1', $content);
        
        // Patrón 3: Enlace con múltiples espacios, saltos de línea y contenido adicional
        $pattern3 = '/<a[^>]*href\s*=\s*["\'][^"\']*["\'][^>]*>[\s\S]*?(<img[^>]*>)[\s\S]*?<\/a>/is';
        $content = preg_replace($pattern3, '$1', $content);
        
        // Patrón 4: Enlace sin href explícito pero con atributos
        $pattern4 = '/<a\s+[^>]*>\s*(?:&nbsp;|\s)*(<img[^>]*>)\s*(?:&nbsp;|\s)*<\/a>/is';
        $content = preg_replace($pattern4, '$1', $content);
        
        // Patrón 5: Enlaces anidados o con contenido adicional después de la imagen
        $pattern5 = '/<a[^>]*>[\s\S]*?(<img[^>]*>)[\s\S]*?<\/a>/is';
        $content = preg_replace($pattern5, '$1', $content);
        
        // Limpiar enlaces vacíos que puedan quedar
        $content = preg_replace('/<a[^>]*>\s*<\/a>/is', '', $content);
        
        return $content;
    }
}

// Inicializar
new CV_Disable_Product_Image_Links();

