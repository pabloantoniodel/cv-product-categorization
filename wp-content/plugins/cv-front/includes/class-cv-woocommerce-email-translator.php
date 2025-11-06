<?php
/**
 * Traductor automático de emails de WooCommerce al español
 * Traduce emails de reset password y password changed
 * 
 * @package CV_Front
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class CV_WooCommerce_Email_Translator {
    
    public function __construct() {
        // Filtros para traducir el contenido de los emails
        add_filter('woocommerce_email_subject_customer_reset_password', array($this, 'translate_reset_password_subject'), 10, 2);
        add_filter('woocommerce_email_heading_customer_reset_password', array($this, 'translate_reset_password_heading'), 10, 2);
        add_filter('retrieve_password_message', array($this, 'translate_reset_password_message'), 10, 4);
        
        add_filter('woocommerce_email_subject_customer_new_account', array($this, 'translate_new_account_subject'), 10, 2);
        add_filter('woocommerce_email_heading_customer_new_account', array($this, 'translate_new_account_heading'), 10, 2);
        
        // Email de contraseña cambiada (WordPress core)
        add_filter('password_change_email', array($this, 'translate_password_changed_email'), 10, 3);
        
        // Emails de Ultimate Affiliate Pro
        add_filter('wp_mail', array($this, 'translate_affiliate_emails'), 999);
        
        // Traducir textos adicionales en los emails
        add_filter('gettext', array($this, 'translate_email_strings'), 20, 3);
        add_filter('gettext_with_context', array($this, 'translate_email_strings_with_context'), 20, 4);
    }
    
    /**
     * Traducir asunto del email de reset password
     */
    public function translate_reset_password_subject($subject, $user_login) {
        return 'Restablecer contraseña para ' . get_bloginfo('name');
    }
    
    /**
     * Traducir encabezado del email de reset password
     */
    public function translate_reset_password_heading($heading, $user_login) {
        return 'Restablecer contraseña';
    }
    
    /**
     * Traducir mensaje completo de reset password
     */
    public function translate_reset_password_message($message, $key, $user_login, $user_data) {
        $reset_url = network_site_url("wp-login.php?action=rp&key=$key&login=" . rawurlencode($user_login), 'login');
        
        $message = '<p>Hola,</p>';
        $message .= '<p>Has solicitado restablecer tu contraseña en <strong>' . get_bloginfo('name') . '</strong>.</p>';
        $message .= '<p>Si no has solicitado este cambio, puedes ignorar este correo y no pasará nada.</p>';
        $message .= '<p>Para restablecer tu contraseña, haz clic en el siguiente enlace:</p>';
        $message .= '<p><a href="' . esc_url($reset_url) . '" style="display: inline-block; padding: 12px 24px; background: #667eea; color: #ffffff; text-decoration: none; border-radius: 5px; font-weight: bold;">Restablecer mi contraseña</a></p>';
        $message .= '<p>O copia y pega esta URL en tu navegador:</p>';
        $message .= '<p>' . esc_url($reset_url) . '</p>';
        $message .= '<p>Este enlace expirará en 24 horas por seguridad.</p>';
        $message .= '<p>Gracias,<br>El equipo de ' . get_bloginfo('name') . '</p>';
        
        return $message;
    }
    
    /**
     * Traducir asunto del email de nueva cuenta
     */
    public function translate_new_account_subject($subject, $user_login) {
        return 'Tu cuenta en ' . get_bloginfo('name') . ' ha sido creada';
    }
    
    /**
     * Traducir encabezado del email de nueva cuenta
     */
    public function translate_new_account_heading($heading, $user_login) {
        return '¡Bienvenido a ' . get_bloginfo('name') . '!';
    }
    
    /**
     * Traducir email de contraseña cambiada (WordPress core)
     */
    public function translate_password_changed_email($email, $user, $userdata) {
        $email['subject'] = 'Tu contraseña ha sido cambiada en ' . get_bloginfo('name');
        
        $email['message'] = 'Hola ' . $user['user_login'] . ',

Tu contraseña ha sido cambiada exitosamente en ' . get_bloginfo('name') . '.

Esta es una confirmación de que el cambio se realizó correctamente.

Si no realizaste este cambio, por favor contacta con nosotros inmediatamente.

Gracias,
El equipo de ' . get_bloginfo('name');
        
        return $email;
    }
    
    /**
     * Traducir emails de Ultimate Affiliate Pro
     */
    public function translate_affiliate_emails($args) {
        // Solo traducir si es email de afiliados
        if (is_array($args) && isset($args['subject'])) {
            // Password changed
            if (strpos($args['subject'], 'Your Password has been changed') !== false) {
                $args['subject'] = 'Tu contraseña ha sido cambiada';
                
                // Traducir el mensaje
                if (isset($args['message'])) {
                    $message = $args['message'];
                    
                    // Reemplazar textos comunes
                    $message = str_replace('Hi ', 'Hola ', $message);
                    $message = str_replace('Your Password has been changed', 'Tu contraseña ha sido cambiada', $message);
                    $message = str_replace('To login please fill out your credentials on:', 'Para iniciar sesión, ingresa tus credenciales en:', $message);
                    $message = str_replace('Your Username:', 'Tu nombre de usuario:', $message);
                    
                    $args['message'] = $message;
                }
            }
            
            // Otros emails de afiliados que puedan aparecer
            if (strpos($args['subject'], 'Password Reset') !== false) {
                $args['subject'] = str_replace('Password Reset', 'Restablecer Contraseña', $args['subject']);
            }
        }
        
        return $args;
    }
    
    /**
     * Traducir cadenas de texto en emails de WooCommerce
     */
    public function translate_email_strings($translated, $text, $domain) {
        if ($domain !== 'woocommerce') {
            return $translated;
        }
        
        $translations = array(
            // Reset Password
            'Password Reset Request for %s' => 'Solicitud de restablecimiento de contraseña para %s',
            'Password reset for %s' => 'Restablecer contraseña para %s',
            'Reset password' => 'Restablecer contraseña',
            'Someone has requested a new password for the following account on %s:' => 'Alguien ha solicitado una nueva contraseña para la siguiente cuenta en %s:',
            'Username: %s' => 'Usuario: %s',
            'If this was a mistake, ignore this email and nothing will happen.' => 'Si esto fue un error, ignora este correo y no pasará nada.',
            'To reset your password, visit the following address:' => 'Para restablecer tu contraseña, visita la siguiente dirección:',
            'Click here to reset your password' => 'Haz clic aquí para restablecer tu contraseña',
            
            // Password Changed
            'Your password has been changed' => 'Tu contraseña ha sido cambiada',
            'Password changed for %s' => 'Contraseña cambiada para %s',
            'Hi %s,' => 'Hola %s,',
            'This is a confirmation that your password was changed.' => 'Esta es una confirmación de que tu contraseña ha sido cambiada.',
            'This notice confirms that your password was changed on %s.' => 'Este aviso confirma que tu contraseña fue cambiada el %s.',
            'Your password has been reset' => 'Tu contraseña ha sido restablecida',
            'Your password has been reset successfully' => 'Tu contraseña ha sido restablecida exitosamente',
            
            // New Account
            'Your %s account has been created!' => '¡Tu cuenta en %s ha sido creada!',
            'Welcome to %s' => 'Bienvenido a %s',
            'Thanks for creating an account on %s. Your username is %s.' => 'Gracias por crear una cuenta en %s. Tu nombre de usuario es %s.',
            'You can access your account area to view orders, change your password, and more at:' => 'Puedes acceder al área de tu cuenta para ver pedidos, cambiar tu contraseña y más en:',
            'We look forward to seeing you soon.' => 'Esperamos verte pronto.',
            
            // Botones y enlaces
            'View your account' => 'Ver tu cuenta',
            'Go to your account' => 'Ir a tu cuenta',
            'My Account' => 'Mi Cuenta',
            
            // Genéricos
            'Thanks' => 'Gracias',
            'Regards' => 'Saludos',
            'The %s Team' => 'El equipo de %s',
        );
        
        if (isset($translations[$text])) {
            return $translations[$text];
        }
        
        return $translated;
    }
    
    /**
     * Traducir cadenas con contexto
     */
    public function translate_email_strings_with_context($translated, $text, $context, $domain) {
        if ($domain !== 'woocommerce') {
            return $translated;
        }
        
        $translations = array(
            'email' => array(
                'Reset password' => 'Restablecer contraseña',
                'Password changed' => 'Contraseña cambiada',
            ),
        );
        
        if (isset($translations[$context][$text])) {
            return $translations[$context][$text];
        }
        
        return $translated;
    }
}

new CV_WooCommerce_Email_Translator();

