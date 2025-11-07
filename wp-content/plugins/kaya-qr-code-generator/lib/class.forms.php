<?php

/** 
 *	Kaya QR Code Generator - Forms Class
 *  Set and Display Forms 
 *
 *	$p_field = field object with attributes :
 *
 *	'_meta_type' = input type (text, email, number, checkbox, select)
 *	'_meta_slug' = unique field id
 *	'_label' = main field text
 *	'_description' = field description, help text
 *	'_class' = css class added to the field
 *	'_value_default' = default field value
 *	'_options' = array('key' => 'value') for multiple choices (select)
 *
 *
 *	$p_options = form options :
 *
 *	'_form_onchange' = JavaScript function called on field value change
 */

if (!defined('ABSPATH'))
{
	exit; // Exit if accessed directly
}

if (!class_exists('WPKQCG_Forms'))
{
	class WPKQCG_Forms
	{
		/**
		 * Display HTML form with all fields
		 */
		public static function display_form($p_fields, $p_options)
		{
			if (empty($p_fields)) return '';

			$o  = '';
			foreach ($p_fields as $field)
			{
				$o .= WPKQCG_Forms::do_form_field_line($field, $p_options);
			}

			return $o;
		}

		/**
		 * Display one field line
		 */
		private static function do_form_field_line($p_field, $p_options)
		{
			if (empty($p_field) || empty($p_field->_meta_type)) return '';

			$field_function = 'do_form_' . $p_field->_meta_type;

			$o = '';
			if (method_exists(__CLASS__, $field_function))
			{
				$o .= call_user_func_array(array(__CLASS__, $field_function), array($p_field, $p_options));
				$o .= '<br />';
			}
			else
			{
				$o .= '<br />' . esc_html__('No layout found for field type:', WPKQCG_TEXT_DOMAIN) . ' ' . esc_html($p_field->_meta_type) . '<br />';
			}

			return $o;
		}

		/**
		 * Display section head
		 */
		private static function do_form_section_head($p_field, $p_options)
		{
			if (empty($p_field)) return '';

			$o = WPKQCG_Forms::get_field_section_head($p_field, $p_options);

			return $o;
		}

		/**
		 * Display form text input
		 */
		private static function do_form_text($p_field, $p_options)
		{
			if (empty($p_field)) return '';

			$o = WPKQCG_Forms::get_field_label($p_field);
			$o .= WPKQCG_Forms::get_field_text($p_field, $p_options);
			$o .= WPKQCG_Forms::get_field_description($p_field);

			return $o;
		}

		/**
		 * Display form color input
		 */
		private static function do_form_color($p_field, $p_options)
		{
			if (empty($p_field)) return '';

			$o = WPKQCG_Forms::get_field_label($p_field);
			$o .= WPKQCG_Forms::get_field_color($p_field, $p_options);
			$o .= WPKQCG_Forms::get_field_description($p_field);

			return $o;
		}

		/**
		 * Display form url input
		 */
		private static function do_form_url($p_field, $p_options)
		{
			if (empty($p_field)) return '';

			$o = WPKQCG_Forms::get_field_label($p_field);
			$o .= WPKQCG_Forms::get_field_url($p_field, $p_options);
			$o .= WPKQCG_Forms::get_field_description($p_field);

			return $o;
		}

		/**
		 * Display form checkbox input
		 */
		private static function do_form_checkbox($p_field, $p_options)
		{
			if (empty($p_field)) return '';

			$o = WPKQCG_Forms::get_field_checkbox($p_field, $p_options);
			$o .= WPKQCG_Forms::get_field_description($p_field);

			return $o;
		}

		/**
		 * Display form select input
		 */
		private static function do_form_select($p_field, $p_options)
		{
			if (empty($p_field)) return '';

			$o = WPKQCG_Forms::get_field_label($p_field);
			$o .= WPKQCG_Forms::get_field_select($p_field, $p_options);
			$o .= WPKQCG_Forms::get_field_description($p_field);

			return $o;
		}

		/**
		 *  Output section head
		 */
		private static function get_field_section_head($p_field)
		{
			if (empty($p_field) || empty($p_field->_label)) return '';

			$field_label = (!empty($p_field->_label)) ? esc_html($p_field->_label) : '';

			$o = '<hr /><h4 style="font-size: 1.2em; margin: 1em 0 0.5em 0;">' . $field_label . '</h4>';

			return $o;
		}

		/**
		 *  Output field: label
		 */
		private static function get_field_label($p_field)
		{
			if (empty($p_field) || empty($p_field->_id) || empty($p_field->_label)) return '';

			$field_id = (!empty($p_field->_id)) ? esc_attr($p_field->_id) : '';
			$field_label = (!empty($p_field->_label)) ? esc_html($p_field->_label) : '';

			$o = '<label for="' . $field_id . '"><strong>' . $field_label . '</strong></label><br />';

			return $o;
		}

		/**
		 *  Output field: text
		 */
		private static function get_field_text($p_field, $p_options)
		{
			if (empty($p_field) || empty($p_field->_id) || empty($p_field->_name)) return '';

			$field_value = (!empty($p_field->_value)) ? esc_attr($p_field->_value) : '';
			$field_class = (!empty($p_field->_class)) ? esc_attr($p_field->_class) : '';
			$field_id = (!empty($p_field->_id)) ? esc_attr($p_field->_id) : '';
			$field_name = (!empty($p_field->_name)) ? esc_attr($p_field->_name) : '';
			$field_meta_slug = (!empty($p_field->_meta_slug)) ? esc_attr($p_field->_meta_slug) : '';
			$option_onchange = (isset($p_options) && !empty($p_options->_form_onchange)) ? esc_attr($p_options->_form_onchange) : '';

			$o = '<input id="' . $field_id . '"';
			$o .= ' name="' . $field_name . '"';
			$o .= ' type="text" value="' . $field_value . '" ';
			if (!empty($field_class)) $o .= ' class="' . $field_class . '"';
			if (!empty($option_onchange)) $o .= ' onkeyup="' . $option_onchange . '(this, \'' . $field_meta_slug . '\');"';
			$o .= '/><br />';

			return $o;
		}

		/**
		 *  Output field: color
		 */
		private static function get_field_color($p_field, $p_options)
		{
			if (empty($p_field) || empty($p_field->_id) || empty($p_field->_name)) return '';

			$field_value = (!empty($p_field->_value)) ? esc_attr($p_field->_value) : '';
			$field_class = (!empty($p_field->_class)) ? esc_attr($p_field->_class) : '';
			$field_id = (!empty($p_field->_id)) ? esc_attr($p_field->_id) : '';
			$field_name = (!empty($p_field->_name)) ? esc_attr($p_field->_name) : '';
			$field_meta_slug = (!empty($p_field->_meta_slug)) ? esc_attr($p_field->_meta_slug) : '';
			$option_onchange = (isset($p_options) && !empty($p_options->_form_onchange)) ? esc_attr($p_options->_form_onchange) : '';

			$o = '<input id="' . $field_id . '"';
			$o .= ' name="' . $field_name . '"';
			$o .= ' type="text" class="wp-kqcg-color-picker" value="' . $field_value . '" ';
			if (!empty($field_class)) $o .= ' class="' . $field_class . '"';
			if (!empty($option_onchange)) $o .= ' data-slug="' . $field_meta_slug . '" onchange="' . $option_onchange . '(this, \'' . $field_meta_slug . '\')"';
			$o .= '/><br />';

			return $o;
		}

		/**
		 *  Output field: url
		 */
		private static function get_field_url($p_field, $p_options)
		{
			if (empty($p_field) || empty($p_field->_id) || empty($p_field->_name)) return '';

			$field_value = (!empty($p_field->_value)) ? esc_attr($p_field->_value) : '';
			$field_class = (!empty($p_field->_class)) ? esc_attr($p_field->_class) : '';
			$field_id = (!empty($p_field->_id)) ? esc_attr($p_field->_id) : '';
			$field_name = (!empty($p_field->_name)) ? esc_attr($p_field->_name) : '';
			$field_meta_slug = (!empty($p_field->_meta_slug)) ? esc_attr($p_field->_meta_slug) : '';
			$option_onchange = (isset($p_options) && !empty($p_options->_form_onchange)) ? esc_attr($p_options->_form_onchange) : '';

			$o = '<input id="' . $field_id . '"';
			$o .= ' name="' . $field_name . '"';
			$o .= ' type="url" value="' . $field_value . '" placeholder="http://..." ';
			if (!empty($field_class)) $o .= ' class="' . $field_class . '"';
			if (!empty($option_onchange)) $o .= ' onkeyup="' . $option_onchange . '(this, \'' . $field_meta_slug . '\');"';
			$o .= '/><br />';

			return $o;
		}

		/**
		 *  Output field: checkbox
		 */
		private static function get_field_checkbox($p_field, $p_options)
		{
			if (empty($p_field) || empty($p_field->_id) || empty($p_field->_name) || empty($p_field->_label)) return '';

			$field_checked = (!empty($p_field->_value)) ? true : false;
			$field_class = (!empty($p_field->_class)) ? esc_attr($p_field->_class) : '';
			$field_id = (!empty($p_field->_id)) ? esc_attr($p_field->_id) : '';
			$field_name = (!empty($p_field->_name)) ? esc_attr($p_field->_name) : '';
			$field_label = (!empty($p_field->_label)) ? esc_html($p_field->_label) : '';
			$field_meta_slug = (!empty($p_field->_meta_slug)) ? esc_attr($p_field->_meta_slug) : '';
			$option_onchange = (isset($p_options) && !empty($p_options->_form_onchange)) ? esc_attr($p_options->_form_onchange) : '';

			$o = '<label for="' . $field_id . '">';
			$o .= '<input id="' . $field_id . '"';
			$o .= ' name="' . $field_name . '"';
			$o .= ' value="1" type="checkbox"';
			if ($field_checked) $o .= ' checked';
			if (!empty($field_class)) $o .= ' class="' . $field_class . '"';
			if (!empty($option_onchange)) $o .= ' onclick="' . $option_onchange . '(this, \'' . $field_meta_slug . '\');"';
			$o .= '>' . $field_label;
			$o .= '</label><br />';

			return $o;
		}

		/**
		 *  Output field: select
		 */
		private static function get_field_select($p_field, $p_options)
		{
			if (empty($p_field) || empty($p_field->_id) || empty($p_field->_name)) return '';

			$field_value = ((!empty($p_field->_value)) ? esc_attr($p_field->_value) : ((!empty($p_field->_value_default)) ? esc_attr($p_field->_value_default) : ''));
			$field_class = (!empty($p_field->_class)) ? esc_attr($p_field->_class) : '';
			$field_id = (!empty($p_field->_id)) ? esc_attr($p_field->_id) : '';
			$field_name = (!empty($p_field->_name)) ? esc_attr($p_field->_name) : '';
			$field_meta_slug = (!empty($p_field->_meta_slug)) ? esc_attr($p_field->_meta_slug) : '';
			$option_onchange = (isset($p_options) && !empty($p_options->_form_onchange)) ? esc_attr($p_options->_form_onchange) : '';

			$o = '<select id="' . $field_id . '"';
			$o .= ' name="' . $field_name . '"';
			if (!empty($option_onchange)) $o .= ' onchange="' . $option_onchange . '(this, \'' . $field_meta_slug . '\');"';
			if (!empty($field_class)) $o .= ' class="' . $field_class . '"';
			$o .= '>';
			foreach ($p_field->_options as $value => $display)
			{
				$o .= '<option value="' . esc_attr($value) . '" ';
				if ($field_value == $value) $o .= 'selected="selected"';
				$o .= '>' . esc_html($display) . '</option>';
			}
			$o .= '</select><br />';

			return $o;
		}

		/**
		 *  Output field: description
		 */
		private static function get_field_description($p_field)
		{
			if (empty($p_field) || empty($p_field->_description)) return '';

			$field_description = (!empty($p_field->_description)) ? esc_html($p_field->_description) : '';
			$o = '<small>' . $field_description . '</small><br />';

			return $o;
		}
	}
}
