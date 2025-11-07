<?php

/** 
 * Kaya QR Code Generator - Widgets Class
 * Adds WordPress Widgets for QR Code
 */

if (!defined('ABSPATH'))
{
	exit; // Exit if accessed directly
}

if (!function_exists('wpkqcg_register_widget_qrcodewidget'))
{
	/**
	 * Register the Widget QRCodeWidget class
	 */
	function wpkqcg_register_widget_qrcodewidget()
	{
		register_widget('WPKQCG_Widget_qrcodewidget');
	}
	add_action('widgets_init', 'wpkqcg_register_widget_qrcodewidget');
}

if (!class_exists('WPKQCG_Widget_qrcodewidget'))
{
	/** 
	 * Kaya QR Code Generator - QRCodeWidget Class
	 * Create QRCodeWidget widget extend WP_Widget
	 */
	class WPKQCG_Widget_qrcodewidget extends WP_Widget
	{
		public function __construct()
		{
			$widget_ops = array(
				'classname' => 'WPKQCG_Widget_qrcodewidget',
				'description' => esc_html__('Simple QR Code generator', WPKQCG_TEXT_DOMAIN)
			);
			parent::__construct('WPKQCG_Widget_qrcodewidget', 'Kaya QR Code', $widget_ops);
		}

		/**
		 * Front-end Outputs the content of the widget
		 *
		 * @param array $args
		 * @param array $instance
		 */
		public function widget($args, $instance)
		{
			// set dynamic content
			if (!empty($instance['dynamic_content']) && $instance['dynamic_content'] == '1')
			{
				$instance['content'] = do_shortcode($instance['content']);
			}

			//	validate custom values
			$fields_valid = WPKQCG_Forms_QRCode::validate_fields($instance);

			//	display widget
			$o = $args['before_widget'];
			$o .= wpkqcg_doDisplayQRCode($fields_valid, $args);
			$o .= '<div style="height: 0;clear: both;margin: 0;padding: 0;"></div>';
			$o .= $args['after_widget'];

			echo $o;
		}

		/**
		 * Back-end Outputs the options form on admin
		 *
		 * @param array $instance The widget options
		 */
		public function form($instance)
		{
			//	set callable functions to get ids values
			$wpkqcg_function_ids = array(
				'_id' => array(&$this, 'get_field_id'),
				'_name' => array(&$this, 'get_field_name')
			);
			//	get form fields with custom values
			$html_form_field = WPKQCG_Forms_QRCode::display_form_fields($instance, $wpkqcg_function_ids);

			//	display
			$o = '<p>';
			$o .= $html_form_field;
			$o .= '</p>';

			echo $o;
		}

		/**
		 * Processing widget options on save
		 *
		 * @param array $new_instance The new options
		 * @param array $old_instance The previous options
		 */
		public function update($new_instance, $old_instance)
		{
			if (empty($new_instance)) return '';

			//	validate fields values
			$instance = WPKQCG_Forms_QRCode::validate_fields($new_instance);

			return $instance;
		}
	}
}
