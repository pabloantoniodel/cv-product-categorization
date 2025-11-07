/* Kaya QR Code Generator Admin JavaScript */

// contain current shortcode data
var wpkqcg_shortcode_curent = {};
// contain current shortcode settings
var wpkqcg_shortcode_settings = {};

/**
 * ShortCode Generator from the assistant fields.
 *
 * @param element selected_field.
 * @param string slug.
 */
function wpkqcg_qrcode_sc_gen(selected_field, slug)
{
	var field_value = selected_field.value;
	var field_type = selected_field.type;
	var sc_name = document.getElementById('wpkqcg_shortcode_generator_sc_name').value;
	var sc_name_dynamic = document.getElementById('wpkqcg_shortcode_generator_sc_name_dynamic').value;
	var fields_default_data = document.getElementById('wpkqcg_shortcode_generator_fields_default').value;
	var fields_default = JSON.parse(fields_default_data);
	var sc_structure_start, sc_structure_content, sc_structure_end;

	// init with default
	if (Object.keys(wpkqcg_shortcode_curent).length === 0)
	{
		Object.keys(fields_default).forEach(function (slug)
		{
			wpkqcg_shortcode_curent[slug] = fields_default[slug];
		});
	}

	// set custom values
	if (field_type == 'checkbox' && !selected_field.checked)
	{
		wpkqcg_shortcode_curent[slug] = '';
	}
	else
	{
		wpkqcg_shortcode_curent[slug] = field_value;
		// set content in settings
		if (slug === 'content')
		{
			wpkqcg_shortcode_settings[slug] = field_value;
		}
	}

	// set dynamic_content settings
	if (slug === 'dynamic_content')
	{
		// delete from shortcode attributes
		wpkqcg_shortcode_curent[slug] = '';
		// add to settings
		if (!selected_field.checked)
		{
			wpkqcg_shortcode_settings[slug] = '';
		}
		else
		{
			wpkqcg_shortcode_settings[slug] = field_value;
		}
	}

	// set shortcode structure
	if (typeof wpkqcg_shortcode_settings.dynamic_content !== 'undefined' && wpkqcg_shortcode_settings.dynamic_content !== '')
	{
		// get content attribute
		sc_structure_content = ']';
		if (typeof wpkqcg_shortcode_settings.content !== 'undefined' && wpkqcg_shortcode_settings.content != '')
		{
			sc_structure_content += wpkqcg_shortcode_settings.content;
			// delete from shortcode attributes
			wpkqcg_shortcode_curent.content = '';
		}
		// set dynamic shortcode start structure
		sc_structure_start = '[' + sc_name_dynamic;
		// set dynamic shortcode end structure
		sc_structure_end = '[/' + sc_name_dynamic + ']';
	}
	else
	{
		// get content attribute
		sc_structure_content = '';
		if (typeof wpkqcg_shortcode_settings.content !== 'undefined' && wpkqcg_shortcode_settings.content != '')
		{
			// set as shortcode attributes
			wpkqcg_shortcode_curent.content = wpkqcg_shortcode_settings.content;
		}
		// set static shortcode start structure
		sc_structure_start = '[' + sc_name;
		// set static shortcode end structure
		sc_structure_end = ']';
	}

	// display shortcode
	var sc_curent = sc_structure_start;
	Object.keys(wpkqcg_shortcode_curent).forEach(function (slug)
	{
		if (wpkqcg_shortcode_curent[slug] != '')
		{
			if ('size' == slug && !wpkqcg_qrcode_isInt(wpkqcg_shortcode_curent[slug]))
			{
				wpkqcg_shortcode_curent[slug] = '';
			}
			var shortcodeValueEscaped = wpkqcg_qrcode_escapeHtml(wpkqcg_shortcode_curent[slug]);
			sc_curent += ' ' + slug + '="' + shortcodeValueEscaped + '"';
		}
	});
	sc_curent += sc_structure_content;
	sc_curent += sc_structure_end;
	document.getElementById('wpkqcg_shortcode_generator_display').innerHTML = sc_curent;

	// set content for preview
	if (typeof wpkqcg_shortcode_settings.dynamic_content !== 'undefined' && wpkqcg_shortcode_settings.dynamic_content !== '')
	{
		if (typeof wpkqcg_shortcode_settings.content !== 'undefined' && wpkqcg_shortcode_settings.content != '')
		{
			wpkqcg_shortcode_curent.content = wpkqcg_shortcode_settings.content;
		}
	}
	// set data for preview display
	wpkqcg_qrcode_preview_gen(wpkqcg_shortcode_curent);

	// display qrcode preview
	wpkqcg_qrcode_preview_display();

	return false;
}

/**
 * Escape unsafe string to HTML.
 *
 * @since 1.2.0
 */
function wpkqcg_qrcode_escapeHtml(unsafe)
{
	return unsafe
		.replace(/&/g, "&amp;")
		.replace(/</g, "&lt;")
		.replace(/>/g, "&gt;")
		.replace(/"/g, "&quot;")
		.replace(/'/g, "&#039;");
}

/**
 * Check for integer value.
 *
 * @since 1.2.0
 */
function wpkqcg_qrcode_isInt(value)
{
	var x;
	if (isNaN(value))
	{
		return false;
	}
	x = parseFloat(value);

	return (x | 0) === x;
}

/**
 * Set values to generate QR Code Image.
 *
 * @since 1.2.0
 */
function wpkqcg_qrcode_preview_gen(p_curentShortcodeSettings)
{
	var qrcode = document.getElementsByClassName('wpkqcg_qrcode');
	var qrcodeImgSlugs = ['ecclevel', 'size', 'border', 'color', 'bgcolor', 'content'];

	if (qrcode.length !== 0)
	{
		for (var i = 0; i < qrcode.length; ++i)
		{
			var qrcode_img = qrcode[i];
			// Iterate over the keys of the p_curentShortcodeSettings object
			var keys = Object.keys(p_curentShortcodeSettings);
			for (var j = 0; j < keys.length; j++)
			{
				var slug = keys[j];
				// Check if the slug is part of the expected parameters
				if (qrcodeImgSlugs.indexOf(slug) > -1)
				{
					// Update the value of the element corresponding to the slug
					document.getElementById(qrcode_img.id + '_' + slug).value = p_curentShortcodeSettings[slug];
				}
			}
		}
	}
}

/**
 * Get values to generate QR Code Image.
 *
 * @since 1.2.0
 */
function wpkqcg_qrcode_preview_display()
{
	var qrcode = document.getElementsByClassName('wpkqcg_qrcode');
	var qrcodeContentUrlAdmin = document.getElementById('wpkqcg_shortcode_generator_content_url_admin').value;
	var qrcodeContentUrlDefault = document.getElementById('wpkqcg_shortcode_generator_content_url_default').value;

	if (qrcode.length !== 0)
	{
		for (var i = 0; i < qrcode.length; ++i)
		{
			var qrcode_img = qrcode[i];
			var qrcode_ecclevel = document.getElementById(qrcode_img.id + '_ecclevel');
			var qrcode_size = document.getElementById(qrcode_img.id + '_size');
			var qrcode_border = document.getElementById(qrcode_img.id + '_border');
			var qrcode_color = document.getElementById(qrcode_img.id + '_color');
			var qrcode_bgcolor = document.getElementById(qrcode_img.id + '_bgcolor');
			var qrcode_content = document.getElementById(qrcode_img.id + '_content');

			if (!wpkqcg_qrcode_isInt(qrcode_size.value))
			{
				qrcode_size.value = '';
			}

			if (!wpkqcg_qrcode_isInt(qrcode_border.value))
			{
				qrcode_border.value = '4';
			}

			if ((qrcode_content.value == qrcodeContentUrlAdmin) || (qrcode_content.value == ''))
			{
				qrcode_content.value = qrcodeContentUrlDefault;
			}

			var qrcode_data = [qrcode_img.id, qrcode_content.value, qrcode_ecclevel.value, qrcode_size.value, qrcode_border.value, qrcode_color.value, qrcode_bgcolor.value];
			wpkqcg_qrcode_encode(qrcode_data);

			if (qrcode_content.value !== '')
			{
				document.getElementById('wpkqcg_shortcode_generator_preview_img').style.display = 'block';
				document.getElementById('wpkqcg_shortcode_generator_preview_permalink_alert').style.display = 'none';
				document.getElementById('wpkqcg_shortcode_generator_preview_dynamic_alert').style.display = 'none';

				if (typeof wpkqcg_shortcode_settings.dynamic_content !== 'undefined' && wpkqcg_shortcode_settings.dynamic_content !== '')
				{
					document.getElementById('wpkqcg_shortcode_generator_preview_img').style.display = 'none';
					document.getElementById('wpkqcg_shortcode_generator_preview_dynamic_alert').style.display = 'block';
				}
			}
			else
			{
				document.getElementById('wpkqcg_shortcode_generator_preview_img').style.display = 'none';
				document.getElementById('wpkqcg_shortcode_generator_preview_permalink_alert').style.display = 'block';
			}
		}
	}
}

/**
 * Download the preview QR Code Image.
 *
 * @since 1.2.0
 */
function wpkqcg_qrcode_preview_download()
{
	var qrcode = document.getElementsByClassName('wpkqcg_qrcode');
	if (qrcode.length !== 0)
	{
		for (var i = 0; i < qrcode.length; ++i)
		{
			// get qrcode data
			var qrcodeB64Data = qrcode[i].src.replace('data:image/png;base64,', '');
			var qrcodeBlob = wpkqcg_qrcode_b64toBlob(qrcodeB64Data, 'image/png');
			var qrcodeFilename = 'kaya-qr-code.png';

			// download qrcode image as a file
			if (window.navigator && window.navigator.msSaveOrOpenBlob)
			{
				window.navigator.msSaveOrOpenBlob(qrcodeBlob, qrcodeFilename); // for microsoft IE
			}
			else
			{
				// for other browsers
				var qrcodeBlobUrl = URL.createObjectURL(qrcodeBlob);
				var downloadLink = document.createElement('a');
				downloadLink.style.display = 'none';
				downloadLink.setAttribute('href', qrcodeBlobUrl);
				downloadLink.setAttribute('target', '_blank');
				downloadLink.setAttribute('rel', 'noopener noreferrer');
				downloadLink.setAttribute('download', qrcodeFilename);
				document.body.appendChild(downloadLink);
				downloadLink.click();
				document.body.removeChild(downloadLink);
				downloadLink = null;
			}
		}
	}
}

/**
 * Convert a base64 data to Blob.
 *
 * @since 1.2.0
 */
function wpkqcg_qrcode_b64toBlob(b64Data, contentType, sliceSize)
{
	contentType = contentType || '';
	sliceSize = sliceSize || 512;

	var byteCharacters = atob(b64Data);
	var byteArrays = [];

	for (var offset = 0; offset < byteCharacters.length; offset += sliceSize)
	{
		var slice = byteCharacters.slice(offset, offset + sliceSize);
		var byteNumbers = new Array(slice.length);
		for (var i = 0; i < slice.length; i++)
		{
			byteNumbers[i] = slice.charCodeAt(i);
		}
		var byteArray = new Uint8Array(byteNumbers);

		byteArrays.push(byteArray);
	}
	var blob = new Blob(byteArrays, { type: contentType });

	return blob;
}

/**
 * Toggle light form for the shortcode generator assistant.
 *
 * @since 1.5.0
 */
function wpkqcg_qrcode_light_form_toggle()
{
	var hideClasses = document.getElementsByClassName('wpkqcg_shortcode_generator_hide');
	for (var i = 0; i < hideClasses.length; i++)
	{
		if (hideClasses.item(i).style.display == 'none')
		{
			hideClasses.item(i).style.display = 'table';
		}
		else
		{
			hideClasses.item(i).style.display = 'none';
		}
	}
}
