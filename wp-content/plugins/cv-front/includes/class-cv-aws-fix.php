<?php
/**
 * Fix para Advanced Woo Search (AWS)
 * Asegurar que muestre el mismo número de productos que WooCommerce
 *
 * @package CV_Front
 */

if (!defined('ABSPATH')) {
    exit;
}

class CV_AWS_Fix {
    
    public function __construct() {
        // Desactivar AWS en búsquedas vacías ANTES de que se procese
        add_action('pre_get_posts', array($this, 'fix_search_per_page'), 1);
        
        // Ajustar número de resultados por página en AWS con máxima prioridad
        add_filter('aws_posts_per_page', array($this, 'set_aws_posts_per_page'), 9999);
        add_filter('aws_page_results', array($this, 'set_aws_max_results'), 9999);
        
        // Desactivar completamente AWS si la búsqueda está vacía
        add_filter('aws_searchpage_enabled', array($this, 'disable_aws_empty_search'), 1, 2);
    }
    
    /**
     * Fix directo en pre_get_posts para búsquedas
     */
    public function fix_search_per_page($query) {
        // Solo en búsquedas de productos en frontend
        if (!is_admin() && $query->is_main_query() && $query->is_search() && isset($query->query_vars['post_type']) && $query->query_vars['post_type'] === 'product') {
            
            // Si la búsqueda está vacía, desactivar AWS y usar query normal
            $search_term = isset($_GET['s']) ? trim($_GET['s']) : '';
            
            error_log('CV AWS Fix PRE_GET_POSTS: is_search=' . ($query->is_search ? 'SI' : 'NO') . ', term="' . $search_term . '", product_cat=' . (isset($_GET['product_cat']) ? $_GET['product_cat'] : 'none'));
            
            if (empty($search_term)) {
                // Búsqueda vacía: desactivar AWS y usar paginación normal
                $query->set('s', ''); // Limpiar término de búsqueda
                $query->is_search = false; // Desactivar búsqueda
                error_log('CV AWS Fix: Búsqueda vacía detectada - DESACTIVANDO is_search');
            }
            
            $per_page = $this->get_wc_per_page();
            $query->set('posts_per_page', $per_page);
            error_log('CV AWS Fix: Configurado posts_per_page=' . $per_page);
        }
        
        // Log para categorías normales (sin búsqueda)
        if (!is_admin() && $query->is_main_query() && !$query->is_search() && is_tax('product_cat')) {
            error_log('CV AWS Fix: Categoría normal (sin búsqueda) - posts_per_page actual: ' . $query->get('posts_per_page'));
        }
    }
    
    /**
     * Obtener productos por página de WooCommerce
     */
    private function get_wc_per_page() {
        // Si WooCommerce tiene configuración de filas y columnas
        $wc_rows = get_option('woocommerce_catalog_rows', 4);
        $wc_cols = get_option('woocommerce_catalog_columns', 4);
        
        if ($wc_rows && $wc_cols) {
            return $wc_rows * $wc_cols;
        }
        
        // Usar el valor de posts_per_page o un mínimo de 12
        return max(get_option('posts_per_page', 12), 12);
    }
    
    /**
     * Establecer número de productos por página en AWS
     * Usa el mismo valor que WooCommerce
     */
    public function set_aws_posts_per_page($num) {
        $calculated = $this->get_wc_per_page();
        error_log('CV AWS Fix: aws_posts_per_page cambiado de ' . $num . ' a ' . $calculated);
        return $calculated;
    }
    
    /**
     * Establecer número máximo de resultados en AWS
     * Para evitar límites artificiales
     */
    public function set_aws_max_results($num) {
        error_log('CV AWS Fix: aws_page_results cambiado de ' . $num . ' a 1000');
        // Permitir hasta 1000 resultados como máximo
        return 1000;
    }
    
    /**
     * Desactivar AWS completamente si la búsqueda está vacía
     */
    public function disable_aws_empty_search($enabled, $query) {
        // Verificar si tenemos parámetro 's' vacío
        $search_term = isset($_GET['s']) ? trim($_GET['s']) : '';
        
        if (isset($_GET['s']) && empty($search_term)) {
            error_log('CV AWS Fix: Desactivando AWS - búsqueda vacía detectada');
            return false; // Desactivar AWS
        }
        
        return $enabled; // Mantener comportamiento normal
    }
}

new CV_AWS_Fix();

