<?php
/**
 * WCFM Notifications Toggle
 * 
 * Botón flotante para activar/desactivar notificaciones con un solo click
 * 
 * @package CV_Front
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class CV_WCFM_Notifications_Toggle {
    
    public function __construct() {
        // Añadir botón flotante de toggle
        add_action('wp_footer', array($this, 'add_notification_toggle_button'));
        
        // AJAX para toggle notificaciones
        add_action('wp_ajax_cv_toggle_notifications', array($this, 'ajax_toggle_notifications'));
        
        // Ocultar icono de campana de WCFM si están desactivadas
        add_filter('wcfm_is_allow_notifications', array($this, 'filter_notifications_display'), 999);
    }
    
    /**
     * Añadir botón flotante de toggle de notificaciones
     */
    public function add_notification_toggle_button() {
        // Solo en My Account o Store Manager
        if (!is_page(array('my-account', 'store-manager'))) {
            return;
        }
        
        // Solo para usuarios logueados
        if (!is_user_logged_in()) {
            return;
        }
        
        $enabled = $this->are_notifications_enabled();
        $icon_class = $enabled ? 'fa-bell' : 'fa-bell-slash';
        $tooltip = $enabled ? 'Desactivar notificaciones' : 'Activar notificaciones';
        
        ?>
        <div id="cv-notification-toggle-float" 
             class="<?php echo $enabled ? 'enabled' : 'disabled'; ?>"
             title="<?php echo esc_attr($tooltip); ?>"
             style="
            position: fixed;
            top: 80px;
            right: 20px;
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 999999;
            box-shadow: 0 4px 20px rgba(102, 126, 234, 0.4);
            transition: all 0.3s ease;
        ">
            <i class="wcfmfa <?php echo esc_attr($icon_class); ?>" id="cv-notification-icon" style="
                font-size: 22px;
                color: white;
            "></i>
        </div>
        
        <style>
        #cv-notification-toggle-float:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 25px rgba(102, 126, 234, 0.6);
        }
        
        #cv-notification-toggle-float.processing {
            pointer-events: none;
            opacity: 0.6;
        }
        
        #cv-notification-toggle-float.processing i {
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            $('#cv-notification-toggle-float').on('click', function() {
                var $btn = $(this);
                var $icon = $('#cv-notification-icon');
                var isEnabled = $btn.hasClass('enabled');
                
                // Confirmar acción
                var confirmMsg = isEnabled 
                    ? '¿Desactivar notificaciones?\n\nPodrás reactivarlas haciendo click en este mismo botón.'
                    : '¿Activar notificaciones?\n\nSe te pedirá permiso del navegador.';
                
                if (!confirm(confirmMsg)) {
                    return;
                }
                
                // Marcar como procesando
                $btn.addClass('processing');
                $icon.removeClass('fa-bell fa-bell-slash').addClass('fa-spinner');
                
                // Si se está activando, limpiar localStorage y pedir permiso
                if (!isEnabled) {
                    // Limpiar marcas de "no mostrar"
                    localStorage.removeItem('cv_notification_never_show');
                    localStorage.removeItem('cv_notification_dismissed');
                    
                    // Pedir permiso del navegador
                    if ('Notification' in window) {
                        Notification.requestPermission().then(function(permission) {
                            console.log('✅ Permiso de notificaciones:', permission);
                        });
                    }
                }
                
                // Toggle via AJAX
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'cv_toggle_notifications',
                        enable: !isEnabled,
                        nonce: '<?php echo wp_create_nonce('cv_notifications_toggle'); ?>'
                    },
                    success: function(response) {
                        $btn.removeClass('processing');
                        
                        if (response.success) {
                            // Cambiar estado
                            if (isEnabled) {
                                // Desactivar
                                $btn.removeClass('enabled').addClass('disabled');
                                $icon.removeClass('fa-spinner').addClass('fa-bell-slash');
                                $btn.attr('title', 'Activar notificaciones');
                            } else {
                                // Activar
                                $btn.removeClass('disabled').addClass('enabled');
                                $icon.removeClass('fa-spinner').addClass('fa-bell');
                                $btn.attr('title', 'Desactivar notificaciones');
                            }
                            
                            // Mostrar mensaje temporal
                            var msg = isEnabled ? '🔕 Notificaciones desactivadas' : '🔔 Notificaciones activadas';
                            $('<div style="position: fixed; top: 140px; right: 20px; background: #4CAF50; color: white; padding: 15px 25px; border-radius: 8px; z-index: 999998; box-shadow: 0 4px 15px rgba(0,0,0,0.2); animation: slideInRight 0.3s;">' + msg + '</div>')
                                .appendTo('body')
                                .delay(2000)
                                .fadeOut(300, function() { $(this).remove(); });
                        } else {
                            alert('❌ Error: ' + (response.data || 'Desconocido'));
                            $icon.removeClass('fa-spinner').addClass(isEnabled ? 'fa-bell' : 'fa-bell-slash');
                        }
                    },
                    error: function() {
                        $btn.removeClass('processing');
                        alert('❌ Error de conexión');
                        $icon.removeClass('fa-spinner').addClass(isEnabled ? 'fa-bell' : 'fa-bell-slash');
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * AJAX: Toggle notificaciones
     */
    public function ajax_toggle_notifications() {
        check_ajax_referer('cv_notifications_toggle', 'nonce');
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error('Usuario no identificado');
        }
        
        $enable = isset($_POST['enable']) && $_POST['enable'] === 'true';
        
        if ($enable) {
            // Activar: eliminar meta
            delete_user_meta($user_id, 'cv_notifications_disabled');
        } else {
            // Desactivar: guardar meta
            update_user_meta($user_id, 'cv_notifications_disabled', '1');
        }
        
        wp_send_json_success(array(
            'enabled' => $enable,
            'message' => $enable ? 'Notificaciones activadas' : 'Notificaciones desactivadas'
        ));
    }
    
    /**
     * Filtrar si se deben mostrar notificaciones de WCFM
     */
    public function filter_notifications_display($is_allowed) {
        if (!$this->are_notifications_enabled()) {
            return false;
        }
        
        return $is_allowed;
    }
    
    /**
     * Verificar si las notificaciones están habilitadas
     */
    private function are_notifications_enabled() {
        $user_id = get_current_user_id();
        if (!$user_id) {
            return true;
        }
        
        $disabled = get_user_meta($user_id, 'cv_notifications_disabled', true);
        return empty($disabled);
    }
}

new CV_WCFM_Notifications_Toggle();
