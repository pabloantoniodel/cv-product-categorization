<?php
/**
 * Ajustar anchos de columnas en el admin de WordPress
 *
 * @package CV_Front
 */

if (!defined('ABSPATH')) {
    exit;
}

class CV_Admin_Column_Fix {
    
    public function __construct() {
        add_action('admin_head', array($this, 'adjust_admin_columns_width'));
    }
    
    /**
     * Ajustar ancho de columnas en la tabla de usuarios del admin
     */
    public function adjust_admin_columns_width() {
        // Solo aplicar en la página de usuarios
        $screen = get_current_screen();
        if (!$screen || $screen->id !== 'users') {
            return;
        }
        
        ?>
        <style>
            /* Reducir ancho de columna Nombre en tabla de usuarios */
            .wp-list-table .column-name {
                width: 15% !important;
                max-width: 200px !important;
            }
            
            /* Ajustar otras columnas para que se vean mejor */
            .wp-list-table .column-username {
                width: 15% !important;
            }
            
            .wp-list-table .column-email {
                width: 20% !important;
            }
            
            .wp-list-table .column-role {
                width: 15% !important;
            }
            
            .wp-list-table .column-posts {
                width: 10% !important;
            }
            
            /* Responsive: en pantallas pequeñas, permitir más espacio */
            @media screen and (max-width: 782px) {
                .wp-list-table .column-name {
                    width: auto !important;
                    max-width: none !important;
                }
            }
        </style>
        <?php
    }
}

new CV_Admin_Column_Fix();

