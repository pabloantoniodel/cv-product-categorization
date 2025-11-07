<?php

/** 
 * Kaya QR Code Generator - QRCode Forms Class
 * Set QRCode Forms.
 */

if (!defined('ABSPATH'))
{
	exit; // Exit if accessed directly
}

if (!class_exists('WPKQCG_Forms_QRCode'))
{
	class WPKQCG_Forms_QRCode
	{
		/**
		 * return Array of fields strucutre
		 */
		private static function get_fields()
		{

			// include the WPKQCG_qrcode_generator class
			require_once(WPKQCG_PLUGIN_PATH . 'lib/class.crud_qrcode_generator.php');
			// init WPKQCG_qrcode_generator object
			$kayaQRCodeGenerator = new WPKQCG_qrcode_generator();

			//	Fields setting
			$fields = array(
				//	title settings
				array(
					'_meta_type'		=> 'section_head',
					'_meta_slug'		=> 'section_title',
					'_label'			=> __('Title', WPKQCG_TEXT_DOMAIN),
					'_description'		=> '',
					'_class'			=> '',
					'_value_default'	=> ''
				),
				array(
					'_meta_type'		=> 'text',
					'_meta_slug'		=> 'title',
					'_label'			=> __('Title:', WPKQCG_TEXT_DOMAIN),
					'_description'		=> __('Displayed as widget title or as heading 2 for shortcodes.', WPKQCG_TEXT_DOMAIN),
					'_class'			=> 'widefat',
					'_value_default'	=> ''
				),
				array(
					'_meta_type'		=> 'select',
					'_meta_slug'		=> 'title_align',
					'_label'			=> __('Title Alignment:', WPKQCG_TEXT_DOMAIN),
					'_description'		=> __('Horizontal alignment of the title.', WPKQCG_TEXT_DOMAIN),
					'_options'			=> array(
						'alignnone'		=> __('None', WPKQCG_TEXT_DOMAIN),
						'alignleft'		=> __('Left', WPKQCG_TEXT_DOMAIN),
						'aligncenter'	=> __('Center', WPKQCG_TEXT_DOMAIN),
						'alignright'	=> __('Right', WPKQCG_TEXT_DOMAIN)
					),
					'_value_default'	=> 'alignnone'
				),
				//	content settings
				array(
					'_meta_type'		=> 'section_head',
					'_meta_slug'		=> 'section_content',
					'_label'			=> __('Content', WPKQCG_TEXT_DOMAIN),
					'_description'		=> '',
					'_class'			=> '',
					'_value_default'	=> ''
				),
				array(
					'_meta_type'		=> 'text',
					'_meta_slug'		=> 'content',
					'_label'			=> __('Content to encode in QR Code:', WPKQCG_TEXT_DOMAIN),
					'_description'		=> __('Leave empty to use current page url, or enter content to be encoded in QR Code.', WPKQCG_TEXT_DOMAIN),
					'_class'			=> 'widefat',
					'_value_default'	=> ''
				),
				array(
					'_meta_type'		=> 'checkbox',
					'_meta_slug'		=> 'dynamic_content',
					'_label'			=> __('Use dynamic content (other shortcodes)', WPKQCG_TEXT_DOMAIN),
					'_value_default'	=> 0
				),
				array(
					'_meta_type'		=> 'text',
					'_meta_slug'		=> 'querystring',
					'_label'			=> __('Query string:', WPKQCG_TEXT_DOMAIN),
					'_description'		=> __('Query string added to the automatic current page url (without the "?" code). Only available with empty content to encode.', WPKQCG_TEXT_DOMAIN),
					'_class'			=> 'widefat',
					'_value_default'	=> ''
				),
				array(
					'_meta_type'		=> 'text',
					'_meta_slug'		=> 'anchor',
					'_label'			=> __('Anchor link:', WPKQCG_TEXT_DOMAIN),
					'_description'		=> __('Anchor link added to the automatic current page url (without the "#" code). Only available with empty content to encode.', WPKQCG_TEXT_DOMAIN),
					'_class'			=> 'widefat',
					'_value_default'	=> ''
				),
				//	image settings
				array(
					'_meta_type'		=> 'section_head',
					'_meta_slug'		=> 'section_image',
					'_label'			=> __('Image', WPKQCG_TEXT_DOMAIN),
					'_description'		=> '',
					'_class'			=> '',
					'_value_default'	=> ''
				),
				array(
					'_meta_type'		=> 'select',
					'_meta_slug'		=> 'ecclevel',
					'_label'			=> __('Information repetition level:', WPKQCG_TEXT_DOMAIN),
					'_description'		=> __('Ability to correct read errors.', WPKQCG_TEXT_DOMAIN),
					'_options'			=> array(
						'L' => __('Low ~7%', WPKQCG_TEXT_DOMAIN),
						'M' => __('Medium ~15%', WPKQCG_TEXT_DOMAIN),
						'Q' => __('Quarter ~25%', WPKQCG_TEXT_DOMAIN),
						'H' => __('High ~30%', WPKQCG_TEXT_DOMAIN)
					),
					'_value_default'	=> 'L'
				),
				array(
					'_meta_type'		=> 'text',
					'_meta_slug'		=> 'size',
					'_label'			=> __('Size:', WPKQCG_TEXT_DOMAIN),
					'_description'		=> __('Leave empty for automatic size, or enter size in px.', WPKQCG_TEXT_DOMAIN),
					'_value_default'	=> ''
				),
				array(
					'_meta_type'		=> 'select',
					'_meta_slug'		=> 'border',
					'_label'			=> __('Border width level:', WPKQCG_TEXT_DOMAIN),
					'_description'		=> __('Sets the thickness of the image margin. Default level: 4.', WPKQCG_TEXT_DOMAIN),
					'_options'			=> array(
						'1' => '1',
						'2' => '2',
						'3' => '3',
						'4' => '4',
						'5' => '5',
						'6' => '6',
						'7' => '7',
						'8'	=> '8',
						'9'	=> '9',
						'10' => '10'
					),
					'_value_default'	=> '4'
				),
				array(
					'_meta_type'		=> 'color',
					'_meta_slug'		=> 'color',
					'_label'			=> __('Color:', WPKQCG_TEXT_DOMAIN),
					'_description'		=> __('Leave empty for default color, or use Hex color code as "#000000".', WPKQCG_TEXT_DOMAIN),
					'_value_default'	=> isset($kayaQRCodeGenerator->data->_shortcode_assistant_image_defaultColor) ? $kayaQRCodeGenerator->data->_shortcode_assistant_image_defaultColor : ''
				),
				array(
					'_meta_type'		=> 'color',
					'_meta_slug'		=> 'bgcolor',
					'_label'			=> __('Background Color:', WPKQCG_TEXT_DOMAIN),
					'_description'		=> __('Leave empty for default color, or use Hex color code as "#FFFFFF". You can set a transparent background with "#FFFFFF00".', WPKQCG_TEXT_DOMAIN),
					'_value_default'	=> isset($kayaQRCodeGenerator->data->_shortcode_assistant_image_defaultBGColor) ? $kayaQRCodeGenerator->data->_shortcode_assistant_image_defaultBGColor : ''
				),
				array(
					'_meta_type'		=> 'select',
					'_meta_slug'		=> 'align',
					'_label'			=> __('Image Alignment:', WPKQCG_TEXT_DOMAIN),
					'_description'		=> __('Horizontal alignment of the QR Code image.', WPKQCG_TEXT_DOMAIN),
					'_options'			=> array(
						'alignnone'		=> __('None', WPKQCG_TEXT_DOMAIN),
						'alignleft'		=> __('Left', WPKQCG_TEXT_DOMAIN),
						'aligncenter'	=> __('Center', WPKQCG_TEXT_DOMAIN),
						'alignright'	=> __('Right', WPKQCG_TEXT_DOMAIN)
					),
					'_value_default'	=> 'alignnone'
				),
				array(
					'_meta_type'		=> 'checkbox',
					'_meta_slug'		=> 'css_shadow',
					'_label'			=> __('Add shadows to QR Code image', WPKQCG_TEXT_DOMAIN),
					'_value_default'	=> 0
				),
				array(
					'_meta_type'		=> 'checkbox',
					'_meta_slug'		=> 'no_css',
					'_label'			=> __('Remove plugin style from QR code image', WPKQCG_TEXT_DOMAIN),
					'_value_default'	=> 0
				),
				array(
					'_meta_type'		=> 'text',
					'_meta_slug'		=> 'alt',
					'_label'			=> __('Image alternate text:', WPKQCG_TEXT_DOMAIN),
					'_description'		=> __('Leave empty for default image alternate text "QR Code".', WPKQCG_TEXT_DOMAIN),
					'_class'			=> 'widefat',
					'_value_default'	=> ''
				),
				//	link settings
				array(
					'_meta_type'		=> 'section_head',
					'_meta_slug'		=> 'section_url',
					'_label'			=> __('Clickable link', WPKQCG_TEXT_DOMAIN),
					'_description'		=> '',
					'_class'			=> '',
					'_value_default'	=> ''
				),
				array(
					'_meta_type'		=> 'url',
					'_meta_slug'		=> 'url',
					'_label'			=> __('Destination URL:', WPKQCG_TEXT_DOMAIN),
					'_description'		=> __('QR Code image clickable link. URL with http://', WPKQCG_TEXT_DOMAIN),
					'_class'			=> 'widefat',
					'_value_default'	=> ''
				),
				array(
					'_meta_type'		=> 'checkbox',
					'_meta_slug'		=> 'new_window',
					'_label'			=> __('Open link in a new window', WPKQCG_TEXT_DOMAIN),
					'_value_default'	=> 0
				),
				array(
					'_meta_type'		=> 'checkbox',
					'_meta_slug'		=> 'content_url',
					'_label'			=> __('Use automatic current page URL or QR Code content URL as clickable link.', WPKQCG_TEXT_DOMAIN),
					'_description'		=> __('Only available with empty destination URL.', WPKQCG_TEXT_DOMAIN),
					'_value_default'	=> 0
				),
				//	Download settings
				array(
					'_meta_type'		=> 'section_head',
					'_meta_slug'		=> 'section_download',
					'_label'			=> __('Download button', WPKQCG_TEXT_DOMAIN),
					'_description'		=> '',
					'_class'			=> '',
					'_value_default'	=> ''
				),
				array(
					'_meta_type'		=> 'checkbox',
					'_meta_slug'		=> 'download_button',
					'_label'			=> __('Add a button to download the QR Code image', WPKQCG_TEXT_DOMAIN),
					'_value_default'	=> 0
				),
				array(
					'_meta_type'		=> 'text',
					'_meta_slug'		=> 'download_text',
					'_label'			=> __('Button text:', WPKQCG_TEXT_DOMAIN),
					'_description'		=> __('Leave empty for default text "Download QR Code".', WPKQCG_TEXT_DOMAIN),
					'_class'			=> 'widefat',
					'_value_default'	=> ''
				),
				array(
					'_meta_type'		=> 'select',
					'_meta_slug'		=> 'download_align',
					'_label'			=> __('Button Alignment:', WPKQCG_TEXT_DOMAIN),
					'_description'		=> __('Horizontal alignment of the button.', WPKQCG_TEXT_DOMAIN),
					'_options'			=> array(
						'alignnone'		=> __('None', WPKQCG_TEXT_DOMAIN),
						'alignleft'		=> __('Left', WPKQCG_TEXT_DOMAIN),
						'aligncenter'	=> __('Center', WPKQCG_TEXT_DOMAIN),
						'alignright'	=> __('Right', WPKQCG_TEXT_DOMAIN)
					),
					'_value_default'	=> 'alignnone'
				),
			);

			return $fields;
		}

		/**
		 * return Array of fields default values
		 */
		public static function get_fields_default_value()
		{
			$fields_default = array();
			//	get form fields array
			$fields_Tab = WPKQCG_Forms_QRCode::get_fields();
			//	get default values
			foreach ($fields_Tab as $field)
			{
				$fields_default[$field['_meta_slug']] = $field['_value_default'];
			}

			return $fields_default;
		}

		/**
		 * return Array field by slug
		 *
		 * @return mixed array|boolean
		 */
		private static function get_field_by_slug($p_slug)
		{
			//	get form fields array
			$fields_Tab = WPKQCG_Forms_QRCode::get_fields();
			//	get field by slug
			foreach ($fields_Tab as $field)
			{
				if ($field['_meta_slug'] == $p_slug)
				{
					return $field;
				}
			}

			return false;
		}

		/**
		 * return options Object
		 *
		 * @return stdClass
		 */
		private static function get_form_options()
		{
			$options_Obj = new stdClass();
			//	Options setting
			$options_Tab = array(
				'_form_onchange' => 'wpkqcg_qrcode_sc_gen',
			);
			//	switch from array to Object
			foreach ($options_Tab as $attr => $val)
			{
				$options_Obj->$attr = $val;
			}

			return $options_Obj;
		}

		/**
		 * return Array accepted options
		 *
		 * @return mixed array|boolean
		 */
		private static function get_accepted_options($p_slug)
		{
			$field = WPKQCG_Forms_QRCode::get_field_by_slug($p_slug);

			if (isset($field['_options'])) return $field['_options'];

			return false;
		}

		/**
		 * Display HTML form fields
		 */
		public static function display_form_fields($p_fields_val = false, $p_function_ids = false)
		{
			//	get form fields
			$form_fields = WPKQCG_Forms_QRCode::get_form_fields($p_fields_val, $p_function_ids);
			//	display form fields
			$o = WPKQCG_Forms::display_form($form_fields, false);

			return $o;
		}

		/**
		 * Display HTML form fields with form options
		 */
		public static function display_form_fields_options($p_fields_val = false, $p_function_ids = false)
		{
			//	get form fields
			$form_fields = WPKQCG_Forms_QRCode::get_form_fields($p_fields_val, $p_function_ids);
			//	get form options
			$form_options = WPKQCG_Forms_QRCode::get_form_options();
			//	display form fields
			$o = WPKQCG_Forms::display_form($form_fields, $form_options);

			return $o;
		}

		/**
		 * return Array with fields Object
		 */
		private static function get_form_fields($p_fields_val = false, $p_function_ids = false)
		{
			$fields_Obj = array();
			//	get form fields array
			$fields_Tab = WPKQCG_Forms_QRCode::get_fields();

			foreach ($fields_Tab as $field)
			{
				$form_field = new stdClass();
				//	switch from array to Object
				foreach ($field as $attr => $val)
				{
					$form_field->$attr = $val;
				}
				//	get field value
				if (empty($p_fields_val))
				{
					$field_val = false;
				}
				else
				{
					$field_val = isset($p_fields_val[$form_field->_meta_slug]) ? $p_fields_val[$form_field->_meta_slug] : false;
				}
				//	validate field value
				$form_field->_value = WPKQCG_Forms_QRCode::validate_field($form_field->_meta_slug, $field_val, $form_field->_value_default);
				//	get callable functions to set ids values
				if (!$p_function_ids)
				{
					$function_ids_id	= false;
					$function_ids_name	= false;
				}
				else
				{
					$function_ids_id	= $p_function_ids['_id'];
					$function_ids_name	= $p_function_ids['_name'];
				}
				//	set ids values : field id and name
				$form_field->_id	= WPKQCG_Forms_QRCode::set_field_id($form_field->_meta_slug, $function_ids_id);
				$form_field->_name	= WPKQCG_Forms_QRCode::set_field_name($form_field->_meta_slug, $function_ids_name);
				//	Add object to the list
				$fields_Obj[] = $form_field;
			}

			return $fields_Obj;
		}

		/**
		 * Set one field id
		 */
		private static function set_field_id($p_meta_slug, $p_function_id = false)
		{
			$field_id = '';
			if (!empty($p_function_id) && method_exists($p_function_id[0], $p_function_id[1]))
			{
				//	get id through callable function
				$field_id = call_user_func(array($p_function_id[0], $p_function_id[1]), $p_meta_slug);
			}
			else
			{
				//	generate unique field id
				$field_unique_id = rand(0, 99) . uniqid() . rand(0, 99);
				$field_id = 'wpkqcg_data_' . $p_meta_slug . '_' . $field_unique_id;
			}

			return $field_id;
		}

		/**
		 * Set one field name
		 */
		private static function set_field_name($p_meta_slug, $p_function_name = false)
		{
			$field_name = '';
			if (!empty($p_function_name) && method_exists($p_function_name[0], $p_function_name[1]))
			{
				//	get name through callable function
				$field_name = call_user_func(array($p_function_name[0], $p_function_name[1]), $p_meta_slug);
			}
			else
			{
				//	generate field name
				$field_name = 'wpkqcg[data][' . $p_meta_slug . ']';
			}

			return $field_name;
		}

		/**
		 * validate all fields values
		 */
		public static function validate_fields($p_fields_values)
		{
			$fields_valid = array();
			//	get form fields array
			$fields_Tab = WPKQCG_Forms_QRCode::get_fields();

			foreach ($fields_Tab as $field)
			{
				$_meta_type		= $field['_meta_type'];
				$_meta_slug		= $field['_meta_slug'];
				$_value_default	= $field['_value_default'];
				if ($_meta_type !== 'section_head')
				{
					//	validate field value
					$fields_valid[$_meta_slug] = WPKQCG_Forms_QRCode::validate_field($_meta_slug, (isset($p_fields_values[$_meta_slug]) ? $p_fields_values[$_meta_slug] : NULL), $_value_default);
				}
			}

			return $fields_valid;
		}

		/**
		 * validate one field value
		 */
		private static function validate_field($p_field_slug, $p_field_value, $p_field_value_default)
		{
			$field_valid = '';

			//	title settings
			if ($p_field_slug == 'title')
			{
				$field_valid = (!empty($p_field_value) && is_string($p_field_value)) ? $p_field_value : $p_field_value_default;	//	title is string
				$field_valid = sanitize_text_field($field_valid);
			}
			elseif ($p_field_slug == 'title_align')
			{
				$accepted_options = WPKQCG_Forms_QRCode::get_accepted_options($p_field_slug);
				$field_valid = (!empty($p_field_value) && is_string($p_field_value) && array_key_exists($p_field_value, $accepted_options)) ? $p_field_value : $p_field_value_default;	//	title_align is string and key
				$field_valid = sanitize_text_field($field_valid);
			}
			//	content settings
			elseif ($p_field_slug == 'content')
			{
				$field_valid = (!empty($p_field_value) && is_string($p_field_value)) ? $p_field_value : $p_field_value_default;	//	content is string
				$field_valid = WPKQCG_Forms_QRCode::sanitize_qrCodeContent($field_valid);
			}
			elseif ($p_field_slug == 'dynamic_content')
			{
				$field_valid = (!empty($p_field_value) && is_numeric($p_field_value)) ? $p_field_value : $p_field_value_default;	//	dynamic_content is numeric
				$field_valid = sanitize_text_field($field_valid);
			}
			elseif ($p_field_slug == 'querystring')
			{
				$field_valid = (!empty($p_field_value) && is_string($p_field_value)) ? $p_field_value : $p_field_value_default;	//	querystring is string
				$field_valid = ltrim($field_valid, '?');
				$field_valid = sanitize_text_field($field_valid);
			}
			elseif ($p_field_slug == 'anchor')
			{
				$field_valid = (!empty($p_field_value) && is_string($p_field_value)) ? $p_field_value : $p_field_value_default;	//	anchor is string
				$field_valid = ltrim($field_valid, '#');
				$field_valid = sanitize_text_field($field_valid);
			}
			//	image settings
			elseif ($p_field_slug == 'ecclevel')
			{
				$accepted_options = WPKQCG_Forms_QRCode::get_accepted_options($p_field_slug);
				$field_valid = (!empty($p_field_value) && is_string($p_field_value) && array_key_exists($p_field_value, $accepted_options)) ? $p_field_value : $p_field_value_default;	//	ecclevel is string and key
				$field_valid = sanitize_text_field($field_valid);
			}
			elseif ($p_field_slug == 'size')
			{
				$field_valid = (!empty($p_field_value) && is_numeric(intval($p_field_value))) ? intval($p_field_value) : $p_field_value_default;	//	size is numeric
				$field_valid = sanitize_key($field_valid);
			}
			elseif ($p_field_slug == 'border')
			{
				$accepted_options = WPKQCG_Forms_QRCode::get_accepted_options($p_field_slug);
				$field_valid = (!empty($p_field_value) && is_string($p_field_value) && array_key_exists($p_field_value, $accepted_options)) ? $p_field_value : $p_field_value_default;	//	border is string and key
				$field_valid = sanitize_text_field($field_valid);
			}
			elseif ($p_field_slug == 'color')
			{
				$field_valid = (!empty($p_field_value) && is_string($p_field_value)) ? $p_field_value : $p_field_value_default;	//	color is string
				$field_valid = str_replace('"', '', $field_valid);
				$field_valid = str_replace("'", "", $field_valid);
				$field_valid = ltrim($field_valid, '#');
				$field_valid = (ctype_xdigit($field_valid)) ? '#' . $field_valid : $p_field_value_default;
				$field_valid = sanitize_text_field($field_valid);
			}
			elseif ($p_field_slug == 'bgcolor')
			{
				$field_valid = (!empty($p_field_value) && is_string($p_field_value)) ? $p_field_value : $p_field_value_default;	//	bgcolor is string
				$field_valid = str_replace('"', '', $field_valid);
				$field_valid = str_replace("'", "", $field_valid);
				$field_valid = ltrim($field_valid, '#');
				$field_valid = (ctype_xdigit($field_valid)) ? '#' . $field_valid : $p_field_value_default;
				$field_valid = sanitize_text_field($field_valid);
			}
			elseif ($p_field_slug == 'align')
			{
				$accepted_options = WPKQCG_Forms_QRCode::get_accepted_options($p_field_slug);
				$field_valid = (!empty($p_field_value) && is_string($p_field_value) && array_key_exists($p_field_value, $accepted_options)) ? $p_field_value : $p_field_value_default;	//	align is string and key
				$field_valid = sanitize_text_field($field_valid);
			}
			elseif ($p_field_slug == 'css_shadow')
			{
				$field_valid = (!empty($p_field_value) && is_numeric($p_field_value)) ? $p_field_value : $p_field_value_default;	//	css_shadow is numeric
				$field_valid = sanitize_text_field($field_valid);
			}
			elseif ($p_field_slug == 'no_css')
			{
				$field_valid = (!empty($p_field_value) && is_numeric($p_field_value)) ? $p_field_value : $p_field_value_default;	//	no_css is numeric
				$field_valid = sanitize_text_field($field_valid);
			}
			elseif ($p_field_slug == 'alt')
			{
				$field_valid = (!empty($p_field_value) && is_string($p_field_value)) ? $p_field_value : $p_field_value_default;	//	alt is string
				$field_valid = sanitize_text_field($field_valid);
			}
			//	link settings
			elseif ($p_field_slug == 'url')
			{
				$field_valid = (!empty($p_field_value) && is_string($p_field_value)) ? $p_field_value : $p_field_value_default;	//	url is string
				$field_valid = sanitize_text_field($field_valid);
			}
			elseif ($p_field_slug == 'new_window')
			{
				$field_valid = (!empty($p_field_value) && is_numeric($p_field_value)) ? $p_field_value : $p_field_value_default;	//	new_window is numeric
				$field_valid = sanitize_text_field($field_valid);
			}
			elseif ($p_field_slug == 'content_url')
			{
				$field_valid = (!empty($p_field_value) && is_numeric($p_field_value)) ? $p_field_value : $p_field_value_default;	//	content_url is numeric
				$field_valid = sanitize_text_field($field_valid);
			}
			elseif ($p_field_slug == 'download_button')
			{
				$field_valid = (!empty($p_field_value) && is_numeric($p_field_value)) ? $p_field_value : $p_field_value_default;	//	download_button is numeric
				$field_valid = sanitize_text_field($field_valid);
			}
			elseif ($p_field_slug == 'download_text')
			{
				$field_valid = (!empty($p_field_value) && is_string($p_field_value)) ? $p_field_value : $p_field_value_default;	//	download_text is string
				$field_valid = sanitize_text_field($field_valid);
			}
			elseif ($p_field_slug == 'download_align')
			{
				$accepted_options = WPKQCG_Forms_QRCode::get_accepted_options($p_field_slug);
				$field_valid = (!empty($p_field_value) && is_string($p_field_value) && array_key_exists($p_field_value, $accepted_options)) ? $p_field_value : $p_field_value_default;	//	download_align is string and key
				$field_valid = sanitize_text_field($field_valid);
			}

			return $field_valid;
		}

		/**
		 * Sanitizes a string for qrcode content.
		 *
		 * @since 1.4.0
		 */
		public static function sanitize_qrCodeContent($str)
		{
			$filtered = WPKQCG_Forms_QRCode::_sanitize_qrCodeContents($str, false);

			return apply_filters('sanitize_text_field', $filtered, $str);
		}

		/**
		 * Sanitizes a string for qrcode content.
		 *
		 * @since 1.4.0
		 */
		public static function _sanitize_qrCodeContents($str, $keep_newlines = false)
		{
			if (is_object($str) || is_array($str))
			{
				return '';
			}

			$str = (string) $str;

			$filtered = wp_check_invalid_utf8($str);

			if (strpos($filtered, '<') !== false)
			{
				$filtered = wp_pre_kses_less_than($filtered);
				// This will strip extra whitespace for us.
				$filtered = wp_strip_all_tags($filtered, false);

				// Use HTML entities in a special case to make sure no later
				// newline stripping stage could lead to a functional tag.
				$filtered = str_replace("<\n", "&lt;\n", $filtered);
			}

			if (!$keep_newlines)
			{
				$filtered = preg_replace('/[\r\n\t ]+/', ' ', $filtered);
			}
			$filtered = trim($filtered);

			$found = false;
			while (preg_match('/[\x00-\x1F\x7F\xA0]/u', $filtered, $match))
			{
				$filtered = str_replace($match[0], '', $filtered);
				$found    = true;
			}

			if ($found)
			{
				// Strip out the whitespace that may now exist after removing the octets.
				$filtered = trim(preg_replace('/ +/', ' ', $filtered));
			}

			return $filtered;
		}
	}
}
