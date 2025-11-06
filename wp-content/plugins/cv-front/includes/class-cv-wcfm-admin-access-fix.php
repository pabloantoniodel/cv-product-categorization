<?php
/**
 * Fix para acceso de administradores a WCFM Store Manager
 * 
 * @package CV_Front
 */

if (!defined('ABSPATH')) {
    exit;
}

class CV_WCFM_Admin_Access_Fix {
    
    public function __construct() {
        // Asegurar que administradores siempre tengan acceso a WCFM
        add_filter('wcfm_allwoed_user_roles', array($this, 'ensure_admin_access'), 999);
        
        // Prevenir redirección de administradores
        add_action('template_redirect', array($this, 'prevent_admin_redirect'), 1);
    }
    
    /**
     * Asegurar que administrator esté en los roles permitidos
     */
    public function ensure_admin_access($allowed_roles) {
        if (!in_array('administrator', $allowed_roles)) {
            $allowed_roles[] = 'administrator';
            error_log('✅ CV WCFM Fix: Añadiendo administrator a roles permitidos');
        }
        return $allowed_roles;
    }
    
    /**
     * Prevenir redirección de administradores en store-manager
     */
    public function prevent_admin_redirect() {
        // Solo en store-manager o cualquier página WCFM
        if (!is_page('store-manager') && !function_exists('is_wcfm_page')) {
            return;
        }
        
        // Verificar si es página WCFM
        $is_wcfm_page = function_exists('is_wcfm_page') && is_wcfm_page();
        
        if (!is_page('store-manager') && !$is_wcfm_page) {
            return;
        }
        
        // Solo para usuarios logueados
        if (!is_user_logged_in()) {
            return;
        }
        
        // Verificar si es administrador
        if (!current_user_can('administrator')) {
            return;
        }
        
        error_log('✅ CV WCFM Fix: Administrador accediendo a WCFM, permitiendo acceso');
    }
}

new CV_WCFM_Admin_Access_Fix();

