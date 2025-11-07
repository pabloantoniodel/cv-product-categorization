<?php

/** 
 * Kaya QR Code Generator - Shortcodes Class
 * Adds hook for shortcodes tags
 */

if (!defined('ABSPATH'))
{
	exit; // Exit if accessed directly
}

if (!class_exists('WPKQCG_Shortcodes_qrcodeshortcode'))
{
	class WPKQCG_Shortcodes_qrcodeshortcode
	{
		/**
		 * Displays through shortcode
		 * [kaya_qrcode] for default display
		 * and
		 * [kaya_qrcode key="value"] for custom display
		 */
		public static function wpkqcg_shortcode_qrcodeshortcode_handler($atts)
		{
			// check for empty attributes
			$atts = (!is_array($atts)) ? array() : $atts;
			//	get schortcode custom and default values
			$args = shortcode_atts(WPKQCG_Forms_QRCode::get_fields_default_value(), $atts);
			//	validate fields
			$fields_valid = WPKQCG_Forms_QRCode::validate_fields($args);
			//	display QR Code img
			$o = wpkqcg_doDisplayQRCode($fields_valid);

			return $o;
		}

		/**
		 * Displays through shortcode
		 * [kaya_qrcode_dynamic]content[/kaya_qrcode_dynamic] for default display
		 * and
		 * [kaya_qrcode_dynamic key="value"]content[/kaya_qrcode_dynamic] for custom display
		 *
		 * @since 1.3.0
		 */
		public static function wpkqcg_shortcode_qrcodeshortcodedynamic_handler($atts, $content)
		{
			// run shortcode parser recursively
			$content = do_shortcode($content);
			// check for empty attributes and content	
			$atts = (!is_array($atts)) ? array() : $atts;
			$content = (empty($content)) ? '' : $content;
			// set content attribute
			$atts['content'] = $content;

			//	get schortcode custom and default values
			$args = shortcode_atts(WPKQCG_Forms_QRCode::get_fields_default_value(), $atts);
			//	validate fields
			$fields_valid = WPKQCG_Forms_QRCode::validate_fields($args);
			//	display QR Code img
			$o = wpkqcg_doDisplayQRCode($fields_valid);

			return $o;
		}
	}
	add_shortcode('kaya_qrcode', array('WPKQCG_Shortcodes_qrcodeshortcode', 'wpkqcg_shortcode_qrcodeshortcode_handler'));
	add_shortcode('kaya_qrcode_dynamic', array('WPKQCG_Shortcodes_qrcodeshortcode', 'wpkqcg_shortcode_qrcodeshortcodedynamic_handler'));
}
