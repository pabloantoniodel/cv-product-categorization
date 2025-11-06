<?php
/**
 * Fix para visualización de reseñas en WCFM
 * Corrige colores de texto que no se ven
 *
 * @package CV_Front
 */

if (!defined('ABSPATH')) {
    exit;
}

class CV_WCFM_Reviews_Fix {
    
    public function __construct() {
        // Añadir CSS en páginas WCFM con máxima prioridad
        add_action('wp_head', array($this, 'add_reviews_css'), 999);
        add_action('admin_head', array($this, 'add_reviews_css'), 999);
        add_action('wp_footer', array($this, 'add_reviews_css_footer'), 999);
    }
    
    /**
     * Añadir CSS para corregir colores de texto en reseñas
     */
    public function add_reviews_css() {
        // Solo en páginas WCFM
        if (!function_exists('is_wcfm_page') || !is_wcfm_page()) {
            return;
        }
        
        ?>
        <style id="cv-wcfm-reviews-fix">
            /* Scroll horizontal en wrapper de tabla de reseñas */
            #wcfm-reviews_wrapper {
                overflow-x: auto !important;
                -webkit-overflow-scrolling: touch !important;
            }
            
            /* Tabla con ancho mínimo para que no se compriman las columnas */
            table#wcfm-reviews {
                min-width: 1400px !important;
                width: 100% !important;
            }
            
            /* Fix colores de texto en tabla de reseñas WCFM - MÁXIMA PRIORIDAD */
            table#wcfm-reviews td,
            table#wcfm-reviews th,
            table#wcfm-reviews tbody td,
            table#wcfm-reviews thead th {
                color: #333 !important;
                background-color: transparent !important;
            }
            
            table#wcfm-reviews .wcfmmp-author-meta,
            table#wcfm-reviews .wcfmmp-author-meta *,
            table#wcfm-reviews .wcfmmp-comments-content,
            table#wcfm-reviews .wcfmmp-comments-content * {
                color: #444 !important;
            }
            
            table#wcfm-reviews a,
            table#wcfm-reviews a * {
                color: #2271b1 !important;
            }
            
            table#wcfm-reviews a:hover,
            table#wcfm-reviews a:hover * {
                color: #135e96 !important;
            }
            
            /* Asegurar contraste en links de productos */
            table#wcfm-reviews .wcfm_dashboard_item_title {
                color: #2271b1 !important;
                font-weight: 600 !important;
            }
            
            /* Email y nombres visibles */
            table#wcfm-reviews .wcfmmp-author-meta {
                font-size: 13px !important;
                line-height: 1.5 !important;
            }
            
            /* Contenido de comentarios */
            table#wcfm-reviews .wcfmmp-comments-content {
                max-height: 150px !important;
                overflow-y: auto !important;
                padding: 5px !important;
                background: #f9f9f9 !important;
                border-radius: 4px !important;
            }
        </style>
        <?php
    }
    
    /**
     * Añadir CSS en footer para sobrescribir JS de WCFM
     */
    public function add_reviews_css_footer() {
        // Solo en páginas WCFM
        if (!function_exists('is_wcfm_page') || !is_wcfm_page()) {
            return;
        }
        
        ?>
        <style id="cv-wcfm-reviews-fix-footer">
            /* SOBRESCRIBIR estilos inline de DataTables */
            table#wcfm-reviews td[style],
            table#wcfm-reviews th[style] {
                color: #333 !important;
            }
            
            table#wcfm-reviews .wcfmmp-author-meta,
            table#wcfm-reviews .wcfmmp-comments-content {
                color: #444 !important;
            }
        </style>
        <script>
        jQuery(document).ready(function($) {
            
            // Función para mostrar todas las columnas y corregir colores
            function fixReviewsTable() {
                // Forzar colores
                $('#wcfm-reviews td, #wcfm-reviews th').css('color', '#333');
                $('#wcfm-reviews .wcfmmp-author-meta, #wcfm-reviews .wcfmmp-comments-content').css('color', '#444');
                $('#wcfm-reviews a').css('color', '#2271b1');
                
                // Mostrar todas las columnas ocultas
                $('#wcfm-reviews td[style*="display: none"], #wcfm-reviews th[style*="display: none"]').each(function() {
                    var currentStyle = $(this).attr('style') || '';
                    var newStyle = currentStyle.replace(/display:\s*none;?/gi, 'display: table-cell;');
                    $(this).attr('style', newStyle);
                });
                
                // Eliminar clase responsive de DataTables
                $('#wcfm-reviews').removeClass('dtr-inline').removeClass('collapsed');
            }
            
            // Ejecutar al cargar
            setTimeout(fixReviewsTable, 1000);
            
            // Re-ejecutar cada vez que DataTables redibuje (al cambiar pestaña)
            if ($.fn.DataTable) {
                $(document).on('draw.dt', '#wcfm-reviews', function() {
                    console.log('🔄 CV Front: DataTables redibujado, aplicando fix...');
                    fixReviewsTable();
                });
            }
            
            // También ejecutar cuando se hace clic en los filtros de estado
            $(document).on('click', '.wcfm_reviews_filter_status a, .wcfm_reviews_status_filter a', function() {
                setTimeout(fixReviewsTable, 500);
            });
            
            // Re-aplicar cada 2 segundos por seguridad
            setInterval(fixReviewsTable, 2000);
        });
        </script>
        <?php
    }
}

new CV_WCFM_Reviews_Fix();

