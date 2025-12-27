<?php
/**
 * CV Front: Admin Menu Order
 * 
 * Reordena los menús de administración para que los plugins que empiezan por "CV" aparezcan primero
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CV_Admin_Menu_Order {
	
	/**
	 * Constructor
	 */
	public function __construct() {
		// Habilitar orden personalizado
		add_filter( 'custom_menu_order', '__return_true' );
		
		// Reordenar menús usando menu_order
		add_filter( 'menu_order', array( $this, 'reorder_menu_order' ), 999 );
		
		// También reordenar directamente el array $menu después de que todos se registren
		add_action( 'admin_menu', array( $this, 'reorder_admin_menu' ), 99999 );
	}
	
	/**
	 * Reordenar usando menu_order filter
	 *
	 * @param array $menu_order Array de slugs de menús
	 * @return array
	 */
	public function reorder_menu_order( $menu_order ) {
		global $menu;
		
		if ( ! is_array( $menu_order ) || empty( $menu_order ) ) {
			return $menu_order;
		}
		
		// Obtener slugs de menús CV
		$cv_slugs = array();
		$other_slugs = array();
		
		foreach ( $menu_order as $slug ) {
			// Buscar el menú en el array global
			$menu_item = $this->find_menu_by_slug( $slug );
			
			if ( $menu_item && $this->is_cv_menu( $menu_item ) ) {
				$cv_slugs[] = $slug;
			} else {
				$other_slugs[] = $slug;
			}
		}
		
		// Devolver CV primero, luego el resto
		return array_merge( $cv_slugs, $other_slugs );
	}
	
	/**
	 * Reordenar directamente el array $menu
	 */
	public function reorder_admin_menu() {
		global $menu;
		
		if ( ! is_array( $menu ) || empty( $menu ) ) {
			return;
		}
		
		// Separar menús CV del resto
		$cv_menus = array();
		$other_menus = array();
		
		foreach ( $menu as $key => $menu_item ) {
			if ( ! isset( $menu_item[0] ) || empty( $menu_item[0] ) ) {
				// Mantener separadores y otros elementos especiales
				$other_menus[ $key ] = $menu_item;
				continue;
			}
			
			if ( $this->is_cv_menu( $menu_item ) ) {
				$cv_menus[ $key ] = $menu_item;
			} else {
				$other_menus[ $key ] = $menu_item;
			}
		}
		
		// Si no hay menús CV, no hacer nada
		if ( empty( $cv_menus ) ) {
			return;
		}
		
		// Reconstruir el menú con CV primero
		$new_menu = array();
		
		// Calcular nueva posición inicial para menús CV (empezar desde 2, después de Dashboard)
		$position = 2;
		
		// Añadir menús CV primero con nuevas posiciones secuenciales
		foreach ( $cv_menus as $old_key => $menu_item ) {
			$new_menu[ $position ] = $menu_item;
			$position += 1;
		}
		
		// Añadir separador después de los menús CV si hay otros menús
		if ( ! empty( $other_menus ) ) {
			$separator_position = $position;
			$new_menu[ $separator_position ] = array(
				'',
				'read',
				'cv-menu-separator',
				'',
				'wp-menu-separator cv-menu-separator'
			);
			$position += 1;
		}
		
		// Añadir el resto de menús, pero saltando los que ya están en CV
		// Ordenar otras posiciones para mantener el orden relativo
		ksort( $other_menus );
		
		foreach ( $other_menus as $old_key => $menu_item ) {
			// Buscar siguiente posición disponible
			while ( isset( $new_menu[ $position ] ) ) {
				$position += 0.1; // Usar decimales para evitar conflictos
			}
			$new_menu[ $position ] = $menu_item;
			$position += 1;
		}
		
		// Ordenar por clave (posición) numéricamente
		uksort( $new_menu, function( $a, $b ) {
			return floatval( $a ) - floatval( $b );
		} );
		
		// Actualizar el menú global
		$menu = $new_menu;
	}
	
	/**
	 * Verificar si un menú es de CV
	 *
	 * @param array $menu_item Item del menú
	 * @return bool
	 */
	private function is_cv_menu( $menu_item ) {
		// Prioridad 1: Verificar por slug (más confiable)
		if ( isset( $menu_item[2] ) && ! empty( $menu_item[2] ) ) {
			$menu_slug = strtolower( trim( $menu_item[2] ) );
			
			// Verificar si el slug empieza con "cv-"
			if ( strpos( $menu_slug, 'cv-' ) === 0 ) {
				return true;
			}
			
			// Verificar si el slug empieza con "cv_"
			if ( strpos( $menu_slug, 'cv_' ) === 0 ) {
				return true;
			}
			
			// Verificar si el slug contiene "cv" al principio (sin guión)
			if ( preg_match( '/^cv[a-z0-9_-]/i', $menu_slug ) ) {
				return true;
			}
			
			// Verificar si es el tipo de contenido "Tarjetas" (card)
			if ( $menu_slug === 'edit.php?post_type=card' || $menu_slug === 'card' || strpos( $menu_slug, 'post_type=card' ) !== false ) {
				return true;
			}
		}
		
		// Prioridad 2: Verificar por título
		if ( isset( $menu_item[0] ) && ! empty( $menu_item[0] ) ) {
			$menu_title = strip_tags( $menu_item[0] ); // Limpiar HTML del título
			$menu_title = trim( $menu_title );
			
			// Verificar si el menú empieza por "CV" (case insensitive)
			if ( stripos( $menu_title, 'CV' ) === 0 ) {
				return true;
			}
			
			// Verificar "Ciudad Virtual"
			if ( stripos( $menu_title, 'Ciudad Virtual' ) === 0 ) {
				return true;
			}
			
			// Verificar si contiene "CV" seguido de espacio o guión
			if ( preg_match( '/^CV[\s-]/i', $menu_title ) ) {
				return true;
			}
			
			// Verificar si es "Tarjetas" (tipo de contenido)
			if ( stripos( $menu_title, 'Tarjetas' ) === 0 || $menu_title === 'Tarjetas' ) {
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * Buscar menú por slug
	 *
	 * @param string $slug Slug del menú
	 * @return array|null
	 */
	private function find_menu_by_slug( $slug ) {
		global $menu;
		
		if ( ! is_array( $menu ) ) {
			return null;
		}
		
		foreach ( $menu as $menu_item ) {
			if ( isset( $menu_item[2] ) && $menu_item[2] === $slug ) {
				return $menu_item;
			}
		}
		
		return null;
	}
}

// Inicializar
new CV_Admin_Menu_Order();

