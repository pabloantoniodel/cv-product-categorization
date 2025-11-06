<?php
/**
 * Gestión del Rol "Comerciales"
 * 
 * @package CV_Front
 * @since 2.6.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class CV_Comerciales_Role {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Crear rol si no existe
        add_action('init', array($this, 'ensure_comerciales_role_exists'));
    }
    
    /**
     * Crear rol de Comerciales
     */
    public function create_comerciales_role() {
        // Verificar si el rol ya existe
        if (get_role('comerciales')) {
            error_log('✅ CV Comerciales: Rol ya existe');
            return;
        }
        
        // Obtener capacidades del rol wcfm_vendor como base
        $vendor_role = get_role('wcfm_vendor');
        
        if (!$vendor_role) {
            error_log('❌ CV Comerciales: Rol wcfm_vendor no encontrado');
            // Usar capacidades de subscriber como fallback
            $capabilities = get_role('subscriber')->capabilities;
        } else {
            $capabilities = $vendor_role->capabilities;
        }
        
        // Añadir capacidades adicionales específicas de comerciales
        $capabilities = array_merge($capabilities, array(
            // Capacidades de lectura
            'read' => true,
            
            // Capacidades de comerciales (personalizadas)
            'view_comerciales_dashboard' => true,
            'manage_own_products' => true,
            'view_sales_reports' => true,
            'contact_customers' => true,
        ));
        
        // Crear el rol
        $result = add_role(
            'comerciales',
            'Comerciales',
            $capabilities
        );
        
        if ($result) {
            error_log('✅ CV Comerciales: Rol creado exitosamente');
        } else {
            error_log('❌ CV Comerciales: Error al crear rol');
        }
        
        return $result;
    }
    
    /**
     * Asegurar que el rol existe
     */
    public function ensure_comerciales_role_exists() {
        if (!get_role('comerciales')) {
            $this->create_comerciales_role();
        }
    }
    
    /**
     * Añadir rol comerciales a usuarios wcfm_vendor
     * (Mantiene el rol vendor, solo añade comerciales)
     */
    public function add_comerciales_to_vendors() {
        global $wpdb;
        
        error_log('🔄 CV Comerciales: Iniciando asignación a vendors...');
        
        // Obtener todos los usuarios con rol wcfm_vendor
        $vendor_users = get_users(array(
            'role' => 'wcfm_vendor',
            'fields' => array('ID', 'user_login', 'display_name'),
        ));
        
        if (empty($vendor_users)) {
            error_log('⚠️ CV Comerciales: No se encontraron vendors');
            return array(
                'total' => 0,
                'updated' => 0,
                'errors' => 0
            );
        }
        
        $total = count($vendor_users);
        $updated = 0;
        $errors = 0;
        
        error_log('📊 CV Comerciales: Total vendors encontrados: ' . $total);
        
        foreach ($vendor_users as $user) {
            $user_obj = new WP_User($user->ID);
            
            // Verificar si ya tiene el rol
            if (in_array('comerciales', $user_obj->roles)) {
                error_log('   ⏭️ Usuario ' . $user->user_login . ' (#' . $user->ID . ') ya tiene rol comerciales');
                $updated++;
                continue;
            }
            
            // Añadir rol (mantiene los roles existentes)
            $user_obj->add_role('comerciales');
            
            // Verificar que se añadió correctamente
            $user_obj = new WP_User($user->ID); // Recargar
            if (in_array('comerciales', $user_obj->roles)) {
                error_log('   ✅ Añadido rol comerciales a: ' . $user->user_login . ' (#' . $user->ID . ')');
                $updated++;
            } else {
                error_log('   ❌ Error añadiendo rol a: ' . $user->user_login . ' (#' . $user->ID . ')');
                $errors++;
            }
        }
        
        error_log('✅ CV Comerciales: Proceso completado');
        error_log('   Total: ' . $total);
        error_log('   Actualizados: ' . $updated);
        error_log('   Errores: ' . $errors);
        
        return array(
            'total' => $total,
            'updated' => $updated,
            'errors' => $errors
        );
    }
    
    /**
     * Remover rol comerciales de un usuario
     * 
     * @param int $user_id ID del usuario
     * @return bool
     */
    public function remove_comerciales_from_user($user_id) {
        $user = new WP_User($user_id);
        
        if (!in_array('comerciales', $user->roles)) {
            return true; // Ya no tiene el rol
        }
        
        $user->remove_role('comerciales');
        
        error_log('🗑️ CV Comerciales: Rol removido de usuario #' . $user_id);
        
        return true;
    }
    
    /**
     * Verificar si un usuario es comercial
     * 
     * @param int $user_id ID del usuario
     * @return bool
     */
    public static function is_comercial($user_id = 0) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            return false;
        }
        
        $user = new WP_User($user_id);
        return in_array('comerciales', $user->roles);
    }
}

// Inicializar
new CV_Comerciales_Role();

