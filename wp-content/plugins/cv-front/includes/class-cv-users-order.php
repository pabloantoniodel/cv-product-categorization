<?php
/**
 * Ordenar lista de usuarios por fecha de registro descendente
 * 
 * Por defecto WordPress muestra usuarios alfabéticamente.
 * Esta clase cambia el orden para mostrar los últimos registrados primero.
 * 
 * @package CV_Front
 */

if (!defined('ABSPATH')) {
    exit;
}

class CV_Users_Order {
    
    public function __construct() {
        // Modificar query de usuarios en el admin
        add_action('pre_get_users', array($this, 'order_users_by_registered_date'));
        
        error_log('✅ CV Front: Orden de usuarios configurado (últimos registrados primero)');
    }
    
    /**
     * Ordenar usuarios por fecha de registro descendente
     */
    public function order_users_by_registered_date($query) {
        // Solo en el admin
        if (!is_admin()) {
            return;
        }
        
        // Solo en la pantalla de usuarios
        $screen = get_current_screen();
        if (!$screen || $screen->id !== 'users') {
            return;
        }
        
        // Si el usuario ya ha elegido un orden, respetarlo
        if (isset($_GET['orderby'])) {
            return;
        }
        
        // Ordenar por fecha de registro descendente (más nuevos primero)
        $query->set('orderby', 'registered');
        $query->set('order', 'DESC');
        
        error_log('📋 CV Front: Lista de usuarios ordenada por fecha de registro (DESC)');
    }
}

new CV_Users_Order();


