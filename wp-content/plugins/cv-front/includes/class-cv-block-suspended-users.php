<?php
/**
 * Bloquea usuarios con rol "suspended"
 * Los usuarios suspendidos no pueden:
 * - Iniciar sesión
 * - Recuperar su contraseña
 * - Actualizar su contraseña
 * Se tratan como usuarios inexistentes
 * 
 * @package CV_Front
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class CV_Block_Suspended_Users {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Bloquear login de usuarios suspendidos
        add_filter('authenticate', array($this, 'block_suspended_login'), 30, 3);
        
        // Bloquear recuperación de contraseña
        add_filter('allow_password_reset', array($this, 'block_suspended_password_reset'), 10, 2);
        
        // Bloquear cuando intentan buscar usuario por email/login (lost password)
        add_filter('retrieve_password', array($this, 'block_suspended_lost_password'), 10, 1);
        
        // Bloquear actualización de contraseña directamente
        add_action('wp_authenticate_user', array($this, 'check_before_auth'), 5, 2);
    }
    
    /**
     * Verificar si un usuario tiene el rol "suspended"
     * 
     * @param int|WP_User $user_id User ID o objeto WP_User
     * @return bool True si el usuario está suspendido
     */
    private function is_suspended($user_id) {
        $user = $user_id instanceof WP_User ? $user_id : get_userdata($user_id);
        
        if (!$user) {
            return false;
        }
        
        // Verificar si tiene el rol "suspended"
        return in_array('suspended', (array) $user->roles, true);
    }
    
    /**
     * Bloquear login de usuarios suspendidos
     * 
     * @param WP_User|WP_Error|null $user Usuario autenticado o error
     * @param string $username Nombre de usuario o email
     * @param string $password Contraseña
     * @return WP_User|WP_Error Usuario o error si está suspendido
     */
    public function block_suspended_login($user, $username, $password) {
        // Si ya hay un error, no modificar
        if (is_wp_error($user) && $user->get_error_code() !== 'invalid_username') {
            return $user;
        }
        
        // Si no hay usuario aún, intentar obtenerlo
        if (!$user instanceof WP_User) {
            // Intentar por username
            $user = get_user_by('login', $username);
            
            // Si no se encuentra, intentar por email
            if (!$user && is_email($username)) {
                $user = get_user_by('email', $username);
            }
        }
        
        // Si encontramos el usuario, verificar si está suspendido
        if ($user instanceof WP_User && $this->is_suspended($user)) {
            // Devolver error genérico como si el usuario no existiera
            return new WP_Error(
                'invalid_username',
                __('<strong>Error:</strong> El nombre de usuario o la dirección de correo electrónico que has introducido no está registrada en este sitio. Si no estás seguro de tu nombre de usuario, prueba tu dirección de correo electrónico en su lugar.')
            );
        }
        
        return $user;
    }
    
    /**
     * Bloquear recuperación de contraseña para usuarios suspendidos
     * 
     * @param bool $allow Si se permite el reset de contraseña
     * @param int $user_id ID del usuario
     * @return bool|WP_Error False o error si el usuario está suspendido
     */
    public function block_suspended_password_reset($allow, $user_id) {
        if ($this->is_suspended($user_id)) {
            // Devolver error genérico como si el usuario no existiera
            return new WP_Error(
                'invalid_user',
                __('<strong>Error:</strong> El nombre de usuario o la dirección de correo electrónico que has introducido no está registrada en este sitio.')
            );
        }
        
        return $allow;
    }
    
    /**
     * Bloquear lost password antes de que se procese
     * 
     * @param string $user_login Login del usuario
     */
    public function block_suspended_lost_password($user_login) {
        $user = get_user_by('login', $user_login);
        
        // Si no se encuentra por login, intentar por email
        if (!$user && is_email($user_login)) {
            $user = get_user_by('email', $user_login);
        }
        
        if ($user && $this->is_suspended($user)) {
            // Interrumpir el proceso y devolver error genérico
            wp_die(
                __('<strong>Error:</strong> El nombre de usuario o la dirección de correo electrónico que has introducido no está registrada en este sitio. Si no estás seguro de tu nombre de usuario, prueba tu dirección de correo electrónico en su lugar.'),
                __('Error de autenticación'),
                array('response' => 403, 'back_link' => true)
            );
        }
    }
    
    /**
     * Verificar antes de autenticar (segunda capa de seguridad)
     * 
     * @param WP_User|WP_Error $user Usuario
     * @param string $password Contraseña
     * @return WP_User|WP_Error Usuario o error
     */
    public function check_before_auth($user, $password) {
        if (is_wp_error($user)) {
            return $user;
        }
        
        if ($user instanceof WP_User && $this->is_suspended($user)) {
            // Devolver error genérico
            return new WP_Error(
                'invalid_username',
                __('<strong>Error:</strong> El nombre de usuario o la dirección de correo electrónico que has introducido no está registrada en este sitio. Si no estás seguro de tu nombre de usuario, prueba tu dirección de correo electrónico en su lugar.')
            );
        }
        
        return $user;
    }
}

