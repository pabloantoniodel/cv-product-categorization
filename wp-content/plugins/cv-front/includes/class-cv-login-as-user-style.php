<?php
/**
 * Estilos personalizados para Login as User
 * 
 * Badge flotante arriba a la izquierda con email y botón
 * 
 * @package CV_Front
 */

if (!defined('ABSPATH')) {
    exit;
}

class CV_Login_As_User_Style {
    
    public function __construct() {
        // Modificar el HTML antes de que se renderice
        add_action('template_redirect', array($this, 'start_buffer'), 1);
        
        // Agregar estilos y JS con prioridad máxima
        add_action('wp_print_footer_scripts', array($this, 'output_custom_styles'), 99999);
        add_action('admin_print_footer_scripts', array($this, 'output_custom_styles'), 99999);
        add_action('wp_footer', array($this, 'output_custom_styles'), 99999);
        add_action('admin_footer', array($this, 'output_custom_styles'), 99999);
    }
    
    /**
     * Iniciar output buffering para modificar el HTML
     */
    public function start_buffer() {
        // Solo si estamos logueados como otro usuario
        if (!isset($_COOKIE['login_as_user_old_user_id'])) {
            return;
        }
        
        ob_start(array($this, 'modify_login_badge_html'));
    }
    
    /**
     * Modificar el HTML del badge
     */
    public function modify_login_badge_html($html) {
        // Solo si hay cookie de login as user
        if (!isset($_COOKIE['login_as_user_old_user_id'])) {
            return $html;
        }
        
        $current_user = wp_get_current_user();
        $user_email = esc_html($current_user->user_email);
        
        // Buscar y reemplazar el mensaje largo
        $html = preg_replace(
            '/<div class="login-as-user-msg">.*?<\/div>/s',
            '<div class="login-as-user-msg" style="display:none;"></div><span class="cv-login-email" style="font-size: 11px; color: white; font-weight: 500;">' . $user_email . '</span>',
            $html
        );
        
        // Cambiar el texto del botón
        $html = preg_replace(
            '/(w357-login-as-user-btn[^>]*>)[^<]+(<\/a>)/i',
            '$1← Volver Administrador$2',
            $html
        );
        
        return $html;
    }
    
    public function output_custom_styles() {
        $current_user = wp_get_current_user();
        $user_email = esc_js($current_user->user_email);
        
        ?>
        <style type="text/css">
            /* FORZAR estilos del badge flotante */
            .login-as-user,
            .login-as-user.login-as-user-top,
            .login-as-user.login-as-user-bottom {
                position: fixed !important;
                top: 10px !important;
                left: 10px !important;
                right: auto !important;
                bottom: auto !important;
                width: auto !important;
                max-width: 350px !important;
                z-index: 9999999 !important;
                box-shadow: 0 4px 20px rgba(0,0,0,0.4) !important;
                border-radius: 10px !important;
                transform: none !important;
            }
            
            .login-as-user-inner {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
                border: none !important;
                padding: 0 !important;
                height: auto !important;
            }
            
            .login-as-user-content {
                padding: 10px 15px !important;
                display: flex !important;
                flex-direction: row !important;
                align-items: center !important;
                gap: 12px !important;
                text-align: left !important;
            }
            
            /* Ocultar mensaje largo */
            .login-as-user-msg {
                display: none !important;
                visibility: hidden !important;
                opacity: 0 !important;
                width: 0 !important;
                height: 0 !important;
                overflow: hidden !important;
            }
            
            /* Estilo del botón DEL BADGE FLOTANTE */
            .login-as-user .button.w357-login-as-user-btn,
            .login-as-user a.button.w357-login-as-user-btn {
                margin: 0 !important;
                padding: 6px 14px !important;
                background: rgba(255,255,255,0.25) !important;
                border: 1px solid rgba(255,255,255,0.4) !important;
                color: white !important;
                border-radius: 6px !important;
                font-size: 12px !important;
                font-weight: 600 !important;
                white-space: nowrap !important;
                text-decoration: none !important;
                display: inline-block !important;
            }
            
            .login-as-user .button.w357-login-as-user-btn:hover,
            .login-as-user .button.w357-login-as-user-btn:focus {
                background: rgba(255,255,255,0.35) !important;
                color: white !important;
                transform: translateY(-1px) !important;
            }
            
            /* Estilo del botón EN LA LISTA DE USUARIOS (admin) */
            .wp-list-table .w357-login-as-user-col-btn,
            .wp-list-table a.w357-login-as-user-col-btn {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
                border: 1px solid #5568d3 !important;
                color: white !important;
                padding: 6px 12px !important;
                border-radius: 4px !important;
                text-decoration: none !important;
                font-weight: 600 !important;
                display: inline-block !important;
                box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3) !important;
            }
            
            .wp-list-table .w357-login-as-user-col-btn:hover,
            .wp-list-table a.w357-login-as-user-col-btn:hover {
                background: linear-gradient(135deg, #5568d3 0%, #6b4a9e 100%) !important;
                color: white !important;
                transform: translateY(-1px) !important;
                box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4) !important;
            }
            
            .wp-list-table .w357-login-as-user-col-btn .dashicons {
                color: white !important;
                vertical-align: middle !important;
            }
            
            /* Ocultar animación ping */
            .w357Ping {
                display: none !important;
            }
            
            /* Ajuste para admin bar */
            body.admin-bar .login-as-user {
                top: 42px !important;
            }
            
            @media (max-width: 782px) {
                body.admin-bar .login-as-user {
                    top: 56px !important;
                }
                
                .login-as-user {
                    max-width: 280px !important;
                }
            }
        </style>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            console.log('🔧 CV Front: Iniciando fix de Login as User badge...');
            
            function fixBadge() {
                var $msg = $('.login-as-user-msg');
                var $btn = $('.button.w357-login-as-user-btn, a.w357-login-as-user-btn');
                var $content = $('.login-as-user-content');
                
                console.log('🔍 CV Front: Login Badge - Msg:', $msg.length, 'Btn:', $btn.length, 'Content:', $content.length);
                
                if ($msg.length === 0 || $btn.length === 0 || $content.length === 0) {
                    console.log('⏳ CV Front: Badge aún no cargado, reintentando...');
                    return false;
                }
                
                // Ocultar mensaje largo
                $msg.hide().empty();
                
                // Cambiar texto del botón
                $btn.text('← Volver Administrador');
                
                // Agregar email si no existe
                if (!$content.find('.cv-login-email').length) {
                    $content.prepend('<span class="cv-login-email" style="font-size: 11px; color: white; font-weight: 500;"><?php echo $user_email; ?></span>');
                }
                
                console.log('✅ CV Front: Login as User badge MODIFICADO correctamente');
                return true;
            }
            
            // Ejecutar inmediatamente
            fixBadge();
            
            // Reintentar varias veces
            setTimeout(fixBadge, 100);
            setTimeout(fixBadge, 300);
            setTimeout(fixBadge, 500);
            setTimeout(fixBadge, 1000);
            setTimeout(fixBadge, 2000);
            
            // Observador con jQuery
            var checkInterval = setInterval(function() {
                if ($('.login-as-user').length && !$('.cv-login-email').length) {
                    console.log('🔄 CV Front: Badge detectado, aplicando fix...');
                    if (fixBadge()) {
                        clearInterval(checkInterval);
                    }
                }
            }, 200);
            
            // Limpiar después de 10 segundos
            setTimeout(function() {
                clearInterval(checkInterval);
            }, 10000);
        });
        </script>
        <?php
    }
}

new CV_Login_As_User_Style();

