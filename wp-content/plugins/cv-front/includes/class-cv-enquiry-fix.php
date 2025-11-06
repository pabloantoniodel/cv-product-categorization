<?php
/**
 * Fix para sistema de consultas de WCFM
 * Resolver errores al enviar consultas en productos
 *
 * @package CV_Front
 */

if (!defined('ABSPATH')) {
    exit;
}

class CV_Enquiry_Fix {
    
    public function __construct() {
        // Logs de debugging para enquiries
        add_action('wp_ajax_wcfm_ajax_controller', array($this, 'log_enquiry_request'), 1);
        add_action('wp_ajax_nopriv_wcfm_ajax_controller', array($this, 'log_enquiry_request'), 1);
        
        // Fix para formulario de enquiry
        add_filter('wcfm_enquiry_content', array($this, 'fix_enquiry_content'), 10, 4);
        
        // Permitir consultas sin restricciones de categoría
        add_filter('wcfm_is_allow_enquiry', array($this, 'allow_enquiry_all_categories'), 999, 2);
    }
    
    /**
     * Log de peticiones de enquiry para debugging
     */
    public function log_enquiry_request() {
        if (isset($_POST['controller']) && $_POST['controller'] === 'wcfm-enquiry-form') {
            error_log('🔍 CV Enquiry: Petición de consulta recibida');
            error_log('🔍 CV Enquiry: POST data: ' . print_r($_POST, true));
            error_log('🔍 CV Enquiry: Usuario logueado: ' . (is_user_logged_in() ? 'Sí (ID: ' . get_current_user_id() . ')' : 'No'));
        }
    }
    
    /**
     * Fix para contenido de enquiry
     */
    public function fix_enquiry_content($enquiry, $product_id, $vendor_id, $customer_id) {
        error_log('🔍 CV Enquiry Content: Product ID: ' . $product_id . ', Vendor ID: ' . $vendor_id . ', Customer ID: ' . $customer_id);
        return $enquiry;
    }
    
    /**
     * Permitir consultas en todas las categorías
     */
    public function allow_enquiry_all_categories($allowed, $product_id = 0) {
        // Siempre permitir consultas
        if (!$allowed) {
            error_log('⚠️ CV Enquiry: Consulta NO permitida para producto ' . $product_id . ' - FORZANDO a permitir');
            return true;
        }
        return $allowed;
    }
}

new CV_Enquiry_Fix();

