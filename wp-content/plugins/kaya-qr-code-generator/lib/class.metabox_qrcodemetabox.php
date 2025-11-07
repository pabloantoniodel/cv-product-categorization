<?php

/**
 * Kaya QR Code Generator - Metabox.
 * The Kaya QR Code metabox display the Shortcode generator assistant to the admin interface.
 * The Shortcode generator assistant is available on pages, posts, WooCommerce products and all other public custom post types.
 */

if (!defined('ABSPATH'))
{
	exit; // Exit if accessed directly
}


if (!function_exists('wpkqcg_call_Metabox_qrcodemetabox'))
{
	/**
	 * Calls the QRCodeMetaBox Metabox class on the page edit screen.
	 */
	function wpkqcg_call_Metabox_qrcodemetabox()
	{
		return new WPKQCG_Metabox_qrcodemetabox();
	}
	if (is_admin())
	{
		add_action('load-post.php', 'wpkqcg_call_Metabox_qrcodemetabox');
		add_action('load-post-new.php', 'wpkqcg_call_Metabox_qrcodemetabox');
	}
}


if (!class_exists('WPKQCG_Metabox_qrcodemetabox'))
{
	/** 
	 * QRCodeMetaBox Metabox Class.
	 *
	 * @class WPKQCG_Metabox_qrcodemetabox
	 */
	class WPKQCG_Metabox_qrcodemetabox
	{
		/**
		 * Construct the Metabox and Hook into the appropriate actions.
		 */
		public function __construct()
		{
			add_action('add_meta_boxes', array(&$this, 'add_page_meta_box'));
		}

		/**
		 * Adds the meta box container.
		 */
		public function add_page_meta_box()
		{
			// include the WPKQCG_qrcode_generator class
			require_once(WPKQCG_PLUGIN_PATH . 'lib/class.crud_qrcode_generator.php');
			// init WPKQCG_qrcode_generator object
			$kayaQRCodeGenerator = new WPKQCG_qrcode_generator();

			// check if current role is allowed
			$currentRoleAllowed = wpkqcg_admin_isCurrentRoleAllowed($kayaQRCodeGenerator);

			// return if current role is not allowed
			if (!$currentRoleAllowed)
			{
				return;
			}

			// Get meta box context
			$metaBoxContext = isset($kayaQRCodeGenerator->data->_shortcode_assistant_metabox_context) ? $kayaQRCodeGenerator->data->_shortcode_assistant_metabox_context : 'normal';
			if (!in_array($metaBoxContext, array('normal', 'advanced')))
			{
				$metaBoxContext = 'normal';
			}

			// Get meta box priority
			$metaBoxPriority = isset($kayaQRCodeGenerator->data->_shortcode_assistant_metabox_priority) ? $kayaQRCodeGenerator->data->_shortcode_assistant_metabox_priority : 'high';
			if (!in_array($metaBoxPriority, array('high', 'core', 'default', 'low')))
			{
				$metaBoxPriority = 'high';
			}

			// Get alowed post types
			$postTypes = wpkqcg_admin_getAllowedPostTypes($kayaQRCodeGenerator, array(), array('wpkqcg_admin_dashboard'));

			add_meta_box(
				'wpkqcg-page-meta-box-qrcodemetabox',
				esc_html__('Kaya QR Code Generator', WPKQCG_TEXT_DOMAIN),
				array(&$this, 'render_meta_box_content'),
				$postTypes,
				$metaBoxContext,
				$metaBoxPriority,
				null
			);
		}

		/**
		 * Render Meta Box content.
		 *
		 * @param WP_Post $post The post object.
		 */
		public function render_meta_box_content($post)
		{
			// include the WPKQCG_qrcode_generator class
			require_once(WPKQCG_PLUGIN_PATH . 'lib/class.crud_qrcode_generator.php');
			// init WPKQCG_qrcode_generator object
			$kayaQRCodeGenerator = new WPKQCG_qrcode_generator();

			// get form fields and default values
			$formFieldsHTML = WPKQCG_Forms_QRCode::display_form_fields_options();
			$formFieldsDefaultValues = WPKQCG_Forms_QRCode::get_fields_default_value();

			// check for light form settings
			$isLightForm = false;
			if (isset($kayaQRCodeGenerator->data->{'_shortcode_assistant_light'}) && $kayaQRCodeGenerator->data->{'_shortcode_assistant_light'})
			{
				$isLightForm = true;
			}

			// shortcode preparation
			$shortcodeGenerated = '[kaya_qrcode';
			foreach ($formFieldsDefaultValues as $i_attr => $i_val)
			{
				if ('' != $i_val)
				{
					$shortcodeGenerated .= ' ' . $i_attr . '="' . $i_val . '"';
				}
			}
			$shortcodeGenerated .= ']';

			// img preview preparation
			$currentPostID			= isset($_GET['post']) ? $_GET['post'] : (isset($_POST['post_ID']) ? $_POST['post_ID'] : false);
			$currentPostPermalink	= (!empty($currentPostID) ? get_permalink($currentPostID) : '');
			$currentAdminURL		= (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
			$formFieldsValidated	= WPKQCG_Forms_QRCode::validate_fields($formFieldsDefaultValues);

			// notice html
			$qrcodeNoticeHTML = '<p style="padding: .75rem 1.25rem; border: 1px solid #721c24;">';
			$qrcodeNoticeHTML .= '<b>' . esc_html__('Important notice:', WPKQCG_TEXT_DOMAIN) . '</b><br / ><br / >';
			$qrcodeNoticeHTML .= esc_html__('This is a shortcode generator assistant used to assist you on the shortcode structure construction, it is not used as custom fields for the post and don’t affect anything in the page content.', WPKQCG_TEXT_DOMAIN);
			$qrcodeNoticeHTML .= '<br />';
			$qrcodeNoticeHTML .= esc_html__('The generated shortcode must be pasted in a “shortcode block” or directly in the page content.', WPKQCG_TEXT_DOMAIN);
			$qrcodeNoticeHTML .= '</p>';

			// shortcode html
			$qrcodeShortcodeHTML = '<input type="hidden" id="wpkqcg_shortcode_generator_sc_name" value="kaya_qrcode" />';
			$qrcodeShortcodeHTML .= '<input type="hidden" id="wpkqcg_shortcode_generator_sc_name_dynamic" value="kaya_qrcode_dynamic" />';
			$qrcodeShortcodeHTML .= '<input type="hidden" id="wpkqcg_shortcode_generator_fields_default" value="' . esc_attr(json_encode($formFieldsDefaultValues)) . '" />';
			$qrcodeShortcodeHTML .= '<div><code id="wpkqcg_shortcode_generator_display" style="display:block;border:1px solid #ccc;padding:16px 32px;" >' . esc_html($shortcodeGenerated) . '</code></div>';

			// img html
			$qrcodeImgHTML = '<input type="hidden" id="wpkqcg_shortcode_generator_content_post_id" value="' . esc_attr($currentPostID) . '" />';
			$qrcodeImgHTML .= '<input type="hidden" id="wpkqcg_shortcode_generator_content_url_admin" value="' . esc_attr(esc_url($currentAdminURL)) . '" />';
			$qrcodeImgHTML .= '<input type="hidden" id="wpkqcg_shortcode_generator_content_url_default" value="' . esc_attr(esc_url($currentPostPermalink)) . '" />';
			$qrcodeImgHTML .= '<div id="wpkqcg_shortcode_generator_preview_img">';
			$qrcodeImgHTML .= '<div><center>' . wpkqcg_doDisplayQRCode($formFieldsValidated) . '</center></div>';
			$qrcodeImgHTML .= '<div><center>';
			$qrcodeImgHTML .= '<button type="button" onclick="wpkqcg_qrcode_preview_download();" id="wpkqcg_shortcode_generator_preview_open" class="button button-secondary is-button is-default is-large">' . esc_html__('Download QR Code', WPKQCG_TEXT_DOMAIN) . '</button>';
			$qrcodeImgHTML .= '</center></div></div>';

			// alert permalink html
			$qrcodeAlertHTML = '<div id="wpkqcg_shortcode_generator_preview_permalink_alert" style="display:none;color:#000;background-color:#ddffff;border:1px solid #ccc;padding:16px 32px;">';
			$qrcodeAlertHTML .= esc_html__('The post must be saved for a QR Code preview with the permalink as content.', WPKQCG_TEXT_DOMAIN);
			$qrcodeAlertHTML .= '</div>';

			// alert dynamic html
			$qrcodeAlertHTML .= '<div id="wpkqcg_shortcode_generator_preview_dynamic_alert" style="display:none;color:#000;background-color:#ddffff;border:1px solid #ccc;padding:16px 32px;">';
			$qrcodeAlertHTML .= esc_html__('The QR Code preview is not available with a dynamic content.', WPKQCG_TEXT_DOMAIN);
			$qrcodeAlertHTML .= '</div>';

			// set light form display
			$formClass = '';
			$formStyle = '';
			$formToggle = '';
			if ($isLightForm)
			{
				$formClass	= 'wpkqcg_shortcode_generator_hide';
				$formStyle	= 'display: none;';
				$formToggle	= '<br /><button type="button" onclick="wpkqcg_qrcode_light_form_toggle();" id="wpkqcg_shortcode_generator_form_open" class="components-button button-primary is-primary">' . esc_html__('Show / Hide the Shortcode generator assistant', WPKQCG_TEXT_DOMAIN) . '</button><br /><br />';
			}

			// set metabox HTML content
			$output = '';

			// $output .= $formToggle;

			$output .= '<table class="form-table"><tbody>';
			$output .= '<tr>';
			$output .= '<td valign="top" style="vertical-align: top;"><center><h3>' . esc_html__('QR Code Preview:', WPKQCG_TEXT_DOMAIN) . '</h3></center>' . $qrcodeImgHTML . $qrcodeAlertHTML . '</td>';
			$output .= '<td valign="top" style="vertical-align: top;"><h3>' . esc_html__('Shortcode Generated:', WPKQCG_TEXT_DOMAIN) . '</h3>' . $qrcodeShortcodeHTML . '<br /><br />' . $formToggle . '</td>';
			$output .= '</tr>';
			$output .= '</tbody></table>';

			$output .= '<table class="form-table ' . esc_attr($formClass) . '" style="' . esc_attr($formStyle) . '"><tbody>';
			$output .= '<tr><td>' . $qrcodeNoticeHTML . '</td></tr>';
			$output .= '<tr><th><h3>' . esc_html__('Shortcode settings:', WPKQCG_TEXT_DOMAIN) . '</h3></th></tr>';
			$output .= '<tr><td>' . $formFieldsHTML . '</td></tr>';
			$output .= '</tbody></table>';

			// display metabox HTML content
			echo $output;
		}
	}
}
