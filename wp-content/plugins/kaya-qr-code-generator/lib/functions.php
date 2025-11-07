<?php

/**
 * Kaya QR Code Generator - Main Functions.
 * Functions for displaying QR Code image.
 */

if (!defined('ABSPATH'))
{
	exit; // Exit if accessed directly
}

/**
 * Global boolean $wpkqcg_qrcode_isDisplayed, true if a qrcode is about to be displayed.
 * Used to a better management and lighter loading of scripts and resources.
 *
 * @since 1.1.1
 */
global $wpkqcg_qrcode_isDisplayed;

if (!function_exists('wpkqcg_doDisplayQRCode'))
{
	/**
	 * Displays QR Code structure.
	 *
	 * @param array	$p_qrcodeValues QR Code form fields values.
	 * @param array	$p_widgetArgs Arguments from the widget.
	 *
	 * @return string
	 */
	function wpkqcg_doDisplayQRCode($p_qrcodeValues, $p_widgetArgs = array())
	{
		global $wpkqcg_qrcode_isDisplayed;
		$wpkqcg_qrcode_isDisplayed = true;

		// get QR Code values
		foreach ($p_qrcodeValues as $i_attr => $i_val)
		{
			${'qrcodeMeta_' . $i_attr} = $i_val;
		}

		// set QR Code img ID
		$qrcodeUniqueID	= rand(0, 99) . uniqid() . rand(0, 99);
		$qrcodeImgID	= esc_attr('wpkqcg_qrcode_outputimg_' . $qrcodeUniqueID);

		// prepare QR Code values
		$qrcodeTitle		= (!empty($qrcodeMeta_title)) ? esc_html($qrcodeMeta_title) : '';						// QR Code title
		$qrcodeTitleAlign	= (!empty($qrcodeMeta_title_align)) ? esc_attr($qrcodeMeta_title_align) : '';			// Horizontal alignment of the QR Code title
		$qrcodeContent		= (!empty($qrcodeMeta_content)) ? esc_attr($qrcodeMeta_content) : '';					// Content to encode in QR Code
		$qrcodeAnchor		= (!empty($qrcodeMeta_anchor)) ? esc_attr($qrcodeMeta_anchor) : '';						// Anchor link added to the automatic current page url
		$qrcodeQueryString	= (!empty($qrcodeMeta_querystring)) ? esc_attr($qrcodeMeta_querystring) : '';			// Query String added to the automatic current page url
		$qrcodeEccLevel		= (!empty($qrcodeMeta_ecclevel)) ? esc_attr($qrcodeMeta_ecclevel) : '';					// QR Code information repetition level
		$qrcodeURL			= (!empty($qrcodeMeta_url)) ? esc_attr($qrcodeMeta_url) : '';							// QR Code image clickable link
		$qrcodeNewWindow	= (!empty($qrcodeMeta_new_window)) ? esc_attr($qrcodeMeta_new_window) : '';				// Open link in a new window
		$qrcodeContentURL	= (!empty($qrcodeMeta_content_url)) ? esc_attr($qrcodeMeta_content_url) : '';			// Use content or automatic current page URL as clickable link
		$qrcodeCssShadow	= (!empty($qrcodeMeta_css_shadow)) ? esc_attr($qrcodeMeta_css_shadow) : '';				// Add shadows to QR Code image
		$qrcodeNoCss		= (!empty($qrcodeMeta_no_css)) ? esc_attr($qrcodeMeta_no_css) : '';						// Remove plugin style from QR Code image
		$qrcodeAlign		= (!empty($qrcodeMeta_align)) ? esc_attr($qrcodeMeta_align) : '';						// Horizontal alignment of the QR Code image
		$qrcodeSize			= (!empty($qrcodeMeta_size)) ? esc_attr($qrcodeMeta_size) : '';							// Size in pixel of the QR Code image
		$qrcodeBorder		= (!empty($qrcodeMeta_border)) ? esc_attr($qrcodeMeta_border) : '4';					// Border level of the QR Code image
		$qrcodeColor		= (!empty($qrcodeMeta_color)) ? esc_attr($qrcodeMeta_color) : '';						// Hexadecimal color code of the QR Code image patterns
		$qrcodeBgColor		= (!empty($qrcodeMeta_bgcolor)) ? esc_attr($qrcodeMeta_bgcolor) : '';					// Hexadecimal color code of the QR Code image background
		$qrcodeAlt			= (!empty($qrcodeMeta_alt)) ? esc_attr($qrcodeMeta_alt) : 'QR Code';					// Image alternate text
		$qrcodeDlButton		= (!empty($qrcodeMeta_download_button)) ? esc_attr($qrcodeMeta_download_button) : '';	// Add button to download QR Code image
		$qrcodeDlText		= (!empty($qrcodeMeta_download_text)) ? esc_html($qrcodeMeta_download_text) : '';		// Download button text
		$qrcodeDlAlign		= (!empty($qrcodeMeta_download_align)) ? esc_attr($qrcodeMeta_download_align) : '';		// Horizontal alignment of the download button

		// set content as current page url
		if (empty($qrcodeContent))
		{
			$qrcodeContent = esc_attr(esc_url((isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"));
			$qrcodeContent .= (!empty($qrcodeQueryString)) ? esc_attr((!str_contains($qrcodeContent, '?') ? '?' : '&') . $qrcodeQueryString) : '';
			$qrcodeContent .= (!empty($qrcodeAnchor)) ? esc_attr('#' . $qrcodeAnchor) : '';
		}

		// get QR Code URL for image link
		$qrcodeLinkToClean = '';
		$qrcodeLinkToDisplay = '';
		if (!empty($qrcodeURL))
		{
			// from QR Code URL attribute
			$qrcodeLinkToClean = filter_var($qrcodeURL, FILTER_SANITIZE_URL);
		}
		elseif (!empty($qrcodeContentURL))
		{
			// from QR Code content URL
			$qrcodeLinkToClean = filter_var($qrcodeContent, FILTER_SANITIZE_URL);
		}

		// set QR Code image link sanitized and escaped
		if (!empty($qrcodeLinkToClean))
		{
			if (strtolower(esc_url_raw($qrcodeLinkToClean)) === strtolower($qrcodeLinkToClean)) // regular URL
			{
				$qrcodeLinkToDisplay = filter_var($qrcodeLinkToClean, FILTER_VALIDATE_URL);
				$qrcodeLinkToDisplay = filter_var($qrcodeLinkToDisplay, FILTER_SANITIZE_URL);
				$qrcodeLinkToDisplay = esc_attr(esc_url($qrcodeLinkToDisplay));
			}
			elseif (filter_var($qrcodeLinkToClean, FILTER_VALIDATE_URL)) // allow custom URI scheme with hierarchy
			{
				$qrcodeLinkToDisplay = filter_var($qrcodeLinkToClean, FILTER_VALIDATE_URL);
				$qrcodeLinkToDisplay = filter_var($qrcodeLinkToDisplay, FILTER_SANITIZE_URL);
				$qrcodeLinkToDisplay = esc_attr($qrcodeLinkToDisplay);
			}
			elseif (
				strpos($qrcodeLinkToClean, ':') !== false &&
				preg_match("/^([a-z][a-z0-9+.-]*):(?:\\/\\/((?:(?=((?:[a-z0-9-._~!$&'()*+,;=:]|%[0-9A-F]{2})*))(\\3)@)?(?=(\\[[0-9A-F:.]{2,}\\]|(?:[a-z0-9-._~!$&'()*+,;=]|%[0-9A-F]{2})*))\\5(?::(?=(\\d*))\\6)?)(\\/(?=((?:[a-z0-9-._~!$&'()*+,;=:@\\/]|%[0-9A-F]{2})*))\\8)?|(\\/?(?!\\/)(?=((?:[a-z0-9-._~!$&'()*+,;=:@\\/]|%[0-9A-F]{2})*))\\10)?)(?:\\?(?=((?:[a-z0-9-._~!$&'()*+,;=:@\\/?]|%[0-9A-F]{2})*))\\11)?(?:#(?=((?:[a-z0-9-._~!$&'()*+,;=:@\\/?]|%[0-9A-F]{2})*))\\12)?$/i", $qrcodeLinkToClean)
			) // allow custom URI scheme without hierarchy
			{
				$notAllowedInURIs = array(
					"data:",
					"mocha:",
					"feed:",
					"javascript:",
					"livescript:",
					"vbscript:"
				);
				$qrcodeLinkToDisplay = str_ireplace($notAllowedInURIs, "", $qrcodeLinkToClean);
				$qrcodeLinkToDisplay = filter_var($qrcodeLinkToDisplay, FILTER_SANITIZE_URL);
				$qrcodeLinkToDisplay = esc_attr($qrcodeLinkToDisplay);
			}
		}

		// set the title alignment
		$qrcodeTitleInlineAlign = '';
		if ($qrcodeTitleAlign == 'alignleft')
		{
			$qrcodeTitleInlineAlign = 'text-align: left;';
		}
		else if ($qrcodeTitleAlign == 'alignright')
		{
			$qrcodeTitleInlineAlign = 'text-align: right;';
		}
		else if ($qrcodeTitleAlign == 'aligncenter')
		{
			$qrcodeTitleInlineAlign = 'text-align: center;';
		}

		// set QR Code image style
		$qrcodeCssInlineBasic = 'width: auto; height: auto; max-width: 100%;';
		$qrcodeCssInlineShadow = (!empty($qrcodeCssShadow)) ? ' box-shadow: 2px 2px 10px #4A4242;' : '';

		// set QR Code image alignment
		$qrcodeCssInlineAlign = '';
		$qrcodeLinkCssInlineAlign = 'display: table; width: auto; height: auto; max-width: 100%;';
		$qrcodeClearBlock = '<div style="clear: none;"></div>';
		if ($qrcodeAlign == 'alignleft')
		{
			$qrcodeCssInlineAlign		= ' display: block; float: left; margin-right: 1.5em;';
			$qrcodeLinkCssInlineAlign	= 'display: block; float: left; width: auto; height: auto; max-width: 100%;';
			$qrcodeClearBlock			= '<div style="clear: left;"></div>';
		}
		else if ($qrcodeAlign == 'alignright')
		{
			$qrcodeCssInlineAlign		= ' display: block; float: right; margin-left: 1.5em;';
			$qrcodeLinkCssInlineAlign	= 'display: block; float: right; width: auto; height: auto; max-width: 100%;';
			$qrcodeClearBlock			= '<div style="clear: right;"></div>';
		}
		else if ($qrcodeAlign == 'aligncenter')
		{
			$qrcodeCssInlineAlign		= ' clear: both; display: block; margin-left: auto; margin-right: auto;';
			$qrcodeLinkCssInlineAlign	= 'display: table; width: auto; height: auto; max-width: 100%; margin-left: auto; margin-right: auto;';
			$qrcodeClearBlock			= '<div style="clear: both;"></div>';
		}

		// include the WPKQCG_qrcode_generator class
		require_once(WPKQCG_PLUGIN_PATH . 'lib/class.crud_qrcode_generator.php');
		// init WPKQCG_qrcode_generator object
		$kayaQRCodeGenerator = new WPKQCG_qrcode_generator();

		// Get default QR Code image color
		if (empty($qrcodeColor))
		{
			$qrcodeColor = isset($kayaQRCodeGenerator->data->_shortcode_assistant_image_defaultColor) ? $kayaQRCodeGenerator->data->_shortcode_assistant_image_defaultColor : '#000000';
			$qrcodeColor = str_replace('"', '', $qrcodeColor);
			$qrcodeColor = str_replace("'", "", $qrcodeColor);
			$qrcodeColor = ltrim($qrcodeColor, '#');
			$qrcodeColor = (ctype_xdigit($qrcodeColor)) ? '#' . $qrcodeColor : '000000';
			$qrcodeColor = sanitize_text_field($qrcodeColor);
		}

		// Get default QR Code image background color
		if (empty($qrcodeBgColor))
		{
			$qrcodeBgColor = isset($kayaQRCodeGenerator->data->_shortcode_assistant_image_defaultBGColor) ? $kayaQRCodeGenerator->data->_shortcode_assistant_image_defaultBGColor : '#FFFFFF';
			$qrcodeBgColor = str_replace('"', '', $qrcodeBgColor);
			$qrcodeBgColor = str_replace("'", "", $qrcodeBgColor);
			$qrcodeBgColor = ltrim($qrcodeBgColor, '#');
			$qrcodeBgColor = (ctype_xdigit($qrcodeBgColor)) ? '#' . $qrcodeBgColor : 'FFFFFF';
			$qrcodeBgColor = sanitize_text_field($qrcodeBgColor);
		}

		// QR Code structure to display
		$output = '<!-- START Kaya QR Code Generator -->';
		$output .= '<div class="wpkqcg_qrcode_wrapper">';
		$output .= '<input type="hidden" id="' . $qrcodeImgID . '_ecclevel" value="' . $qrcodeEccLevel . '" />';
		$output .= '<input type="hidden" id="' . $qrcodeImgID . '_size" value="' . $qrcodeSize . '" />';
		$output .= '<input type="hidden" id="' . $qrcodeImgID . '_border" value="' . $qrcodeBorder . '" />';
		$output .= '<input type="hidden" id="' . $qrcodeImgID . '_color" value="' . $qrcodeColor . '" />';
		$output .= '<input type="hidden" id="' . $qrcodeImgID . '_bgcolor" value="' . $qrcodeBgColor . '" />';
		$output .= '<input type="hidden" id="' . $qrcodeImgID . '_content" value="' . $qrcodeContent . '" />';

		// set the title
		if (!empty($qrcodeTitle) && empty($p_widgetArgs))
		{
			$output .= '<h2 style="' . $qrcodeTitleInlineAlign . '">' . $qrcodeTitle . '</h2>'; // shortcode title
		}
		elseif (!empty($qrcodeTitle) && !empty($p_widgetArgs) && !empty($p_widgetArgs['before_title']) && !empty($p_widgetArgs['after_title']))
		{
			if (strpos($p_widgetArgs['before_title'], '>') !== false)
			{
				$p_widgetArgs['before_title'] = str_replace('>', ' style="' . $qrcodeTitleInlineAlign . '">', $p_widgetArgs['before_title']);
			}
			$output .= $p_widgetArgs['before_title'] . $qrcodeTitle . $p_widgetArgs['after_title']; // widget title
		}

		// surround with a link to the URL
		if (!empty($qrcodeLinkToDisplay))
		{
			$output .= '<a href="' . $qrcodeLinkToDisplay . '"';
			$output .= ' style="' . $qrcodeLinkCssInlineAlign . '" ';
			if (!empty($qrcodeNewWindow))
			{
				$output .= ' target="_blank" rel="noopener noreferrer"'; // open in new window, rel="noopener noreferrer" improves security.
			}
			$output .= '>';
		}

		// set QR Code image structure
		$output .= '<img src="" id="' . $qrcodeImgID . '" alt="' . $qrcodeAlt . '" class="wpkqcg_qrcode"';
		if (empty($qrcodeNoCss))
		{
			$output .= ' style="' . $qrcodeCssInlineBasic . $qrcodeCssInlineShadow . $qrcodeCssInlineAlign . '"';
		}
		$output .= ' >';

		// close the link
		if (!empty($qrcodeLinkToDisplay))
		{
			$output .= '</a>';
		}

		$output .= $qrcodeClearBlock;

		// Add QR Code download button
		if (!empty($qrcodeDlButton))
		{
			// set the button alignment
			$qrcodeButtonInlineAlign = 'margin-top: 10px;';
			if ($qrcodeDlAlign == 'alignleft')
			{
				$qrcodeButtonInlineAlign .= 'display: block; float: left; margin-right: 1.5em;';
			}
			else if ($qrcodeDlAlign == 'alignright')
			{
				$qrcodeButtonInlineAlign .= 'display: block; float: right; margin-left: 1.5em;';
			}
			else if ($qrcodeDlAlign == 'aligncenter')
			{
				$qrcodeButtonInlineAlign .= 'clear: both; display: block; margin-left: auto; margin-right: auto;';
			}
			$output .= '<button type="button" 
				style="' . $qrcodeButtonInlineAlign . '" 
				onclick="wpkqcg_qrcode_download(\'' . $qrcodeUniqueID . '\');" 
				id="wpkqcg_qrcode_download_button_' . $qrcodeUniqueID . '" class="wpkqcg_button button">'
				. (!empty($qrcodeDlText) ? $qrcodeDlText : esc_html__('Download QR Code', WPKQCG_TEXT_DOMAIN)) .
				'</button>';
		}

		$output .= '</div>';
		$output .= '<!-- END Kaya QR Code Generator -->';

		return $output;
	}
}

if (!function_exists('wpkqcg_registerFrontScripts'))
{
	/**
	 * Register Public front scripts in footer.
	 * Required for qrcode generation and display.
	 *
	 * @since 1.5.0
	 */
	function wpkqcg_registerFrontScripts()
	{
		global $wpkqcg_qrcode_isDisplayed;
		if ($wpkqcg_qrcode_isDisplayed)
		{
			wp_enqueue_script('wpkqcg-asset', WPKQCG_PLUGIN_URL . 'assets/qrcode-v2.min.js', array(), WPKQCG_VERSION, true);
			wp_enqueue_script('wpkqcg-pkg', WPKQCG_PLUGIN_URL . 'js/wpkqcg-pkg.min.js', array(), WPKQCG_VERSION, true);
			wp_enqueue_script('wpkqcg-display', WPKQCG_PLUGIN_URL . 'js/wpkqcg-display.min.js', array('jquery'), WPKQCG_VERSION, true);
		}
	}
	add_action('wp_footer', 'wpkqcg_registerFrontScripts');
}
