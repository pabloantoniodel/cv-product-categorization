<?php
/**
 * Customizador de Menús
 * 
 * Modifica los elementos del menú principal
 * 
 * @package CV_Front
 * @since 2.8.1
 */

if (!defined('ABSPATH')) {
    exit;
}

class CV_Menu_Customizer {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_filter('wp_nav_menu_objects', array($this, 'customize_menu_items'), 10, 2);
        add_action('wp_head', array($this, 'add_custom_css'));
    }
    
    /**
     * Agregar CSS para ocultar elementos del menú
     */
    public function add_custom_css() {
        echo '<style>
            .menu-item-hidden {
                display: none !important;
            }
        </style>';
    }
    
    /**
     * Customizar elementos del menú
     * 
     * @param array $items Los elementos del menú
     * @param object $args Los argumentos del menú
     * @return array Los elementos modificados
     */
    public function customize_menu_items($items, $args) {
        $found_noticias_cv = false;
        $items_to_remove = array();
        
        foreach ($items as $key => $item) {
            // Primero, buscar si existe "Noticias CV"
            if ($item->title === 'Noticias CV') {
                $found_noticias_cv = true;
                // Cambiar "Noticias CV" a "Noticias"
                $items[$key]->title = 'Noticias';
            }
        }
        
        // Si encontramos "Noticias CV", eliminar el primer elemento con título "Noticias"
        if ($found_noticias_cv) {
            foreach ($items as $key => $item) {
                // Eliminar el elemento "Noticias" (el original) que NO sea el que acabamos de renombrar
                // Verificamos por URL que contenga /category/ o similares
                if ($item->title === 'Noticias' && 
                    (strpos($item->url, '/category/') !== false || 
                     strpos($item->url, '/noticias/') !== false) &&
                    strpos($item->url, '/noticias-cv/') === false) {
                    // Marcar para eliminación
                    $items_to_remove[] = $key;
                    break; // Solo eliminar el primero que encontremos
                }
            }
        }
        
        // Eliminar los elementos marcados
        foreach ($items_to_remove as $key) {
            unset($items[$key]);
        }
        
        return $items;
    }
}

// Inicializar
new CV_Menu_Customizer();

