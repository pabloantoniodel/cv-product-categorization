/* Kaya QR Code Generator JavaScript */

/**
 * Generate QR Code Image.
 */
function wpkqcg_qrcode_encode(tab_data)
{
	//	get QR Code content to encrypt
	var img_id = tab_data[0];
	var qr_data = tab_data[1];
	var qr_ecclevel = tab_data[2];
	var img_size = tab_data[3];
	var img_border = tab_data[4];
	var img_color = tab_data[5];
	var img_bgcolor = tab_data[6];

	// set image code color
	if ('' === img_color)
	{
		img_color = '#000000';
	}
	else if ('#' !== img_color.charAt(0))
	{
		img_color = "#" + img_color;
	}

	// set image background color
	if ('' === img_bgcolor)
	{
		img_bgcolor = '#FFFFFF';
	}
	else if ('#' !== img_bgcolor.charAt(0))
	{
		img_bgcolor = "#" + img_bgcolor;
	}

	// set image border level
	if ('' === img_border)
	{
		img_border = '4';
	}

	var options = {};
	var qrcode_img;

	// set image size
	if ('' === img_size)
	{
		//	get generation options without resize
		options = { ecclevel: qr_ecclevel, margin: img_border, customcolor: img_color, custombgcolor: img_bgcolor };
		//	generate img
		qrcode_img = QRCode.generatePNG(qr_data, options);
	}
	else
	{
		//	get generation options with img resized
		var qrcode_matrix = QRCode.generate(qr_data, { ecclevel: qr_ecclevel });
		var matrix_size = qrcode_matrix.length + 8;
		var module_resize = 1;

		if (img_size > matrix_size)
		{
			module_resize = Math.round(img_size / matrix_size);
		}

		options = { ecclevel: qr_ecclevel, modulesize: module_resize, margin: img_border, customcolor: img_color, custombgcolor: img_bgcolor };
		//	generate img
		qrcode_img = QRCode.generatePNG(qr_data, options);
	}
	//	include to img src
	document.getElementById(img_id).src = qrcode_img;

	return false;
}

/**
 * Get values to generate QR Code Image.
 */
function wpkqcg_qrcode_display()
{
	var qrcode = document.getElementsByClassName('wpkqcg_qrcode');
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
			var qrcode_data = [qrcode_img.id, qrcode_content.value, qrcode_ecclevel.value, qrcode_size.value, qrcode_border.value, qrcode_color.value, qrcode_bgcolor.value];
			wpkqcg_qrcode_encode(qrcode_data);
		}
	}
}

/**
 * Download the QR Code Image.
 *
 * @since 1.6.0
 */
function wpkqcg_qrcode_download(qrCodeID)
{
	var qrcode = document.getElementById('wpkqcg_qrcode_outputimg_' + qrCodeID);
	if (qrcode)
	{

		// get qrcode data
		var qrcodeB64Data = qrcode.src.replace('data:image/png;base64,', '');
		var qrcodeBlob = wpkqcg_qrcode_convertB64toBlob(qrcodeB64Data, 'image/png');
		var qrcodeFilename = 'kaya-qr-code-' + qrCodeID + '.png';

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

/**
 * Convert a base64 data to Blob.
 *
 * @since 1.6.0
 */
function wpkqcg_qrcode_convertB64toBlob(b64Data, contentType, sliceSize)
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