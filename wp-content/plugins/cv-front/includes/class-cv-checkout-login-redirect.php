<?php
/**
 * CV Front: Checkout Login Redirect
 * 
 * Redirige al checkout después del login si el usuario venía del checkout
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CV_Checkout_Login_Redirect {
	
	/**
	 * Constructor
	 */
	public function __construct() {
		// Guardar referer del checkout antes del login
		add_action( 'woocommerce_before_checkout_form', array( $this, 'save_checkout_referer' ), 1 );
		
		// Redirigir después del login si venía del checkout
		add_filter( 'woocommerce_login_redirect', array( $this, 'redirect_to_checkout' ), 10, 2 );
		add_filter( 'login_redirect', array( $this, 'redirect_to_checkout_wp' ), 10, 3 );
		
		// Para User Registration plugin
		add_filter( 'user_registration_login_redirect_url', array( $this, 'redirect_to_checkout_ur' ), 10, 3 );
	}
	
	/**
	 * Guardar referer del checkout en cookie
	 */
	public function save_checkout_referer() {
		if ( ! is_checkout() ) {
			return;
		}
		
		// Guardar en cookie que estamos en checkout (más confiable que sesión)
		setcookie( 'cv_from_checkout', '1', time() + 300, '/', '', is_ssl(), true ); // 5 minutos
		setcookie( 'cv_checkout_url', wc_get_checkout_url(), time() + 300, '/', '', is_ssl(), true );
	}
	
	/**
	 * Redirigir al checkout después del login (WooCommerce)
	 *
	 * @param string $redirect URL de redirección
	 * @param WP_User $user Usuario que hizo login
	 * @return string
	 */
	public function redirect_to_checkout( $redirect, $user ) {
		// Verificar si venía del checkout
		if ( $this->is_from_checkout() ) {
			$checkout_url = wc_get_checkout_url();
			$this->clear_checkout_flag();
			return $checkout_url;
		}
		
		return $redirect;
	}
	
	/**
	 * Redirigir al checkout después del login (WordPress)
	 *
	 * @param string $redirect_to URL de redirección
	 * @param string $requested_redirect_to URL solicitada
	 * @param WP_User|WP_Error $user Usuario o error
	 * @return string
	 */
	public function redirect_to_checkout_wp( $redirect_to, $requested_redirect_to, $user ) {
		// Solo si el login fue exitoso
		if ( is_wp_error( $user ) ) {
			return $redirect_to;
		}
		
		// Verificar si venía del checkout
		if ( $this->is_from_checkout() ) {
			$checkout_url = wc_get_checkout_url();
			$this->clear_checkout_flag();
			return $checkout_url;
		}
		
		return $redirect_to;
	}
	
	/**
	 * Redirigir al checkout después del login (User Registration)
	 *
	 * @param string $redirect URL de redirección
	 * @param WP_User $user Usuario
	 * @param string $redirect_option Opción de redirección
	 * @return string
	 */
	public function redirect_to_checkout_ur( $redirect, $user, $redirect_option ) {
		// Verificar si venía del checkout
		if ( $this->is_from_checkout() ) {
			$checkout_url = wc_get_checkout_url();
			$this->clear_checkout_flag();
			return $checkout_url;
		}
		
		return $redirect;
	}
	
	/**
	 * Verificar si el usuario venía del checkout
	 *
	 * @return bool
	 */
	private function is_from_checkout() {
		// Método 1: Verificar cookie
		if ( isset( $_COOKIE['cv_from_checkout'] ) && $_COOKIE['cv_from_checkout'] === '1' ) {
			return true;
		}
		
		// Método 2: Verificar referer
		$referer = wp_get_referer();
		if ( $referer && ( strpos( $referer, 'checkout' ) !== false || strpos( $referer, '/checkout/' ) !== false ) ) {
			return true;
		}
		
		// Método 3: Verificar si hay productos en el carrito y el referer es checkout
		if ( function_exists( 'WC' ) && WC()->cart && ! WC()->cart->is_empty() ) {
			if ( $referer && ( strpos( $referer, 'checkout' ) !== false || strpos( $referer, 'cart' ) !== false ) ) {
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * Limpiar flag de checkout
	 */
	private function clear_checkout_flag() {
		// Eliminar cookies
		setcookie( 'cv_from_checkout', '', time() - 3600, '/', '', is_ssl(), true );
		setcookie( 'cv_checkout_url', '', time() - 3600, '/', '', is_ssl(), true );
	}
}

// Inicializar
new CV_Checkout_Login_Redirect();

