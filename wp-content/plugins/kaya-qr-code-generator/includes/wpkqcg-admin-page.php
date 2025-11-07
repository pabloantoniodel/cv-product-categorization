<?php

/**
 * Kaya QR Code Generator - Admin Page.
 * Displays the admin plugin page.
 *
 * @since 1.4.1
 */

if (!function_exists('wpkqcg_admin_doOptionPage'))
{
	/**
	 * Display QR Code Generator Page.
	 */
	function wpkqcg_admin_doOptionPage()
	{
		if (!current_user_can('manage_options'))
		{
			wp_die('<p>' . __('You do not have sufficient permissions.') . '</p>');
		}

		$wpkqcg_footer = sprintf(/* translators: 1: Plugin Name 2: Plugin Version */__('%1$s - Version %2$s', WPKQCG_TEXT_DOMAIN), 'Kaya QR Code Generator', WPKQCG_VERSION);
?>

		<div class="ks-wp-dashboard-page-container">
			<div class="ks-wp-dashboard-page-row">

				<div class="ks-wp-dashboard-page-header">
					<div class="ks-wp-dashboard-page-header-title">
						<h1>Kaya QR Code Generator</h1>
					</div>
				</div>

				<div class="ks-wp-dashboard-page-content">

					<div class="ks-wp-dashboard-page-content-card">
						<h2 class="ks-wp-dashboard-page-content-card-title"><?php echo esc_html__('QR Code Generator', WPKQCG_TEXT_DOMAIN); ?></h2>
						<p>
							<?php echo esc_html__('Generate QR Code through Widgets and Shortcodes, without any dependencies.', WPKQCG_TEXT_DOMAIN); ?>
						</p>
						<p>
							<?php echo esc_html__('Use built-in Widgets in "Appearance > Widgets".', WPKQCG_TEXT_DOMAIN); ?>
						</p>
						<p>
							<?php echo esc_html__('Use shortcodes like this:', WPKQCG_TEXT_DOMAIN); ?>
						</p>
						<table class="ks-wp-dashboard-page-content-table">
							<tbody>
								<tr>
									<td><?php echo esc_html__('Basic shortcode for a static content:', WPKQCG_TEXT_DOMAIN); ?></td>
									<td><code>[kaya_qrcode content="<?php echo esc_attr__('My encoded content', WPKQCG_TEXT_DOMAIN); ?>"]</code></td>
								</tr>
								<tr>
									<td><?php echo esc_html__('Basic shortcode for a dynamic content:', WPKQCG_TEXT_DOMAIN); ?></td>
									<td><code>[kaya_qrcode_dynamic][example_shortcode][/kaya_qrcode_dynamic]</code></td>
								</tr>
							</tbody>
						</table>
						<p>
							<?php echo esc_html__('Or use the following shortcode generator assistant to assist you on the shortcode structure construction. The generated shortcode must be pasted in a “shortcode block” or directly in the page content.', WPKQCG_TEXT_DOMAIN); ?><br /><br />
							<?php echo esc_html__('The shortcode generator assistant is also available in pages, posts, WooCommerce products or in any public custom post type, the generator is under the administration primary content of your page / post / product.', WPKQCG_TEXT_DOMAIN); ?>
						</p>
					</div>

					<div class="ks-wp-dashboard-page-content-card">
						<h2 class="ks-wp-dashboard-page-content-card-title"><?php echo esc_html__('QR Code Generator settings', WPKQCG_TEXT_DOMAIN); ?></h2>

						<?php

						// include the WPKQCG_qrcode_generator class
						require_once(WPKQCG_PLUGIN_PATH . 'lib/class.crud_qrcode_generator.php');
						// init WPKQCG_qrcode_generator object
						$kayaQRCodeGenerator = new WPKQCG_qrcode_generator();

						// QR Code Generator variables texts
						$wpkqcg_textSave			= __('Save Changes', WPKQCG_TEXT_DOMAIN);
						$wpkqcg_textReset			= __('Reset settings', WPKQCG_TEXT_DOMAIN);
						$wpkqcg_textResetConfirm	= __('Do you want to delete the current settings?', WPKQCG_TEXT_DOMAIN);

						// QR Code Generator save panel
						$wpkqcg_admin_panel = '<table class="form-table"><tbody><tr>';
						$wpkqcg_admin_panel .= '<td><input class="ks-wp-dashboard-page-btn ks-wp-dashboard-page-btn-primary" class="left" type="submit" name="save_qr_code_generator" value="' . esc_attr($wpkqcg_textSave) . '" /></td>';
						$wpkqcg_admin_panel .= '</tr></tbody></table>';

						// QR Code Generator delete panel
						$wpkqcg_delete_panel = '<table class="form-table"><tbody><tr>';
						$wpkqcg_delete_panel .= '<td><form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
						$wpkqcg_delete_panel .= '<input type="hidden" name="wpkqcg[id]" value="' . esc_attr($kayaQRCodeGenerator->id) . '" />';
						$wpkqcg_delete_panel .= '<input type="hidden" name="wpkqcg_action" value="delete" />';
						$wpkqcg_delete_panel .= '<input type="hidden" name="action" value="wpkqcg_qr_code_generator_form">';
						$wpkqcg_delete_panel .= '<input type="hidden" name="wpkqcg_target" value="qr_code_generator" />';
						$wpkqcg_delete_panel .= wp_nonce_field('wpkqcg_crud_' . get_current_user_id(), 'wpkqcg_crud_delete', true, false);
						$wpkqcg_delete_panel .= '<input class="ks-wp-dashboard-page-btn ks-wp-dashboard-page-btn-warning" class="left" type="submit" name="delete_qr_code_generator" value="' . esc_attr($wpkqcg_textReset) . '" onclick="return confirm(\'' . esc_attr($wpkqcg_textResetConfirm) . '\');" />';
						$wpkqcg_delete_panel .= '</form></td>';
						$wpkqcg_delete_panel .= '</tr></tbody></table>';

						// QR Code Generator customs roles inputs
						$wpkqcg_customRoles = wpkqcg_admin_getUsersRoles();
						$wpkqcg_customRoles_inputs = '';
						foreach ($wpkqcg_customRoles as $i_role)
						{
							$roleKey = esc_attr($i_role['id']);
							$roleValue = esc_html(translate_user_role($i_role['name']));

							$wpkqcg_customRoles_inputs .= '<label for="wpkqcg_shortcode_assistant_to_' . $roleKey . '">';
							$wpkqcg_customRoles_inputs .= '<input id="wpkqcg_shortcode_assistant_to_' . $roleKey . '" name="wpkqcg[data][_shortcode_assistant_to_' . $roleKey . ']" value="1" type="checkbox" ' . ((isset($kayaQRCodeGenerator->data->{'_shortcode_assistant_to_' . $roleKey}) && $kayaQRCodeGenerator->data->{'_shortcode_assistant_to_' . $roleKey}) ? 'checked' : '') . '>';
							$wpkqcg_customRoles_inputs .=  $roleValue;
							$wpkqcg_customRoles_inputs .= '</label><br />';
						}

						// QR Code Generator public post types
						$wpkqcg_postTypes = wpkqcg_admin_getAllPostTypesAsList();
						unset($wpkqcg_postTypes['wpkqcg_admin_dashboard']);
						$wpkqcg_postTypes_inputs = '';
						foreach ($wpkqcg_postTypes as $i_postTypeKey => $i_postTypeValue)
						{
							$postType_key	= esc_attr($i_postTypeKey);
							$postTypeObj	= get_post_type_object($postType_key);
							$postTypeValue	= esc_html($postTypeObj->label);

							$wpkqcg_postTypes_inputs .= '<label for="wpkqcg_shortcode_assistant_in_' . $postType_key . '">';
							$wpkqcg_postTypes_inputs .= '<input id="wpkqcg_shortcode_assistant_in_' . $postType_key . '" name="wpkqcg[data][_shortcode_assistant_in_' . $postType_key . ']" value="1" type="checkbox" ' . ((isset($kayaQRCodeGenerator->data->{'_shortcode_assistant_in_' . $postType_key}) && $kayaQRCodeGenerator->data->{'_shortcode_assistant_in_' . $postType_key}) ? 'checked' : '') . '>';
							$wpkqcg_postTypes_inputs .=  $postTypeValue;
							$wpkqcg_postTypes_inputs .= '</label><br />';
						}

						// QR Code Generator light form input
						$wpkqcg_lightForm_inputs = '<label for="wpkqcg_shortcode_assistant_light">';
						$wpkqcg_lightForm_inputs .= '<input id="wpkqcg_shortcode_assistant_light" name="wpkqcg[data][_shortcode_assistant_light]" value="1" type="checkbox" ' . ((isset($kayaQRCodeGenerator->data->{'_shortcode_assistant_light'}) && $kayaQRCodeGenerator->data->{'_shortcode_assistant_light'}) ? 'checked' : '') . '>';
						$wpkqcg_lightForm_inputs .=  __('Activate', WPKQCG_TEXT_DOMAIN);
						$wpkqcg_lightForm_inputs .= '</label><br />';

						// QR Code Generator meta box context inputs
						$wpkqcg_metaboxContext_inputs = '<select id="wpkqcg_shortcode_assistant_metabox_context" name="wpkqcg[data][_shortcode_assistant_metabox_context]">';
						$wpkqcg_metaboxContext_inputs .= '<option value="normal" ' . ((isset($kayaQRCodeGenerator->data->{'_shortcode_assistant_metabox_context'}) && $kayaQRCodeGenerator->data->{'_shortcode_assistant_metabox_context'} == 'normal') ? 'selected="selected"' : '') . '>' . esc_html__('Normal location', WPKQCG_TEXT_DOMAIN) . '</option>';
						$wpkqcg_metaboxContext_inputs .= '<option value="advanced" ' . ((isset($kayaQRCodeGenerator->data->{'_shortcode_assistant_metabox_context'}) && $kayaQRCodeGenerator->data->{'_shortcode_assistant_metabox_context'} == 'advanced') ? 'selected="selected"' : '') . '>' . esc_html__('Advanced location', WPKQCG_TEXT_DOMAIN) . '</option>';
						$wpkqcg_metaboxContext_inputs .= '</select>';

						// QR Code Generator meta box priority inputs
						$wpkqcg_metaboxPriority_inputs = '<select id="wpkqcg_shortcode_assistant_metabox_priority" name="wpkqcg[data][_shortcode_assistant_metabox_priority]">';
						$wpkqcg_metaboxPriority_inputs .= '<option value="high" ' . ((isset($kayaQRCodeGenerator->data->{'_shortcode_assistant_metabox_priority'}) && $kayaQRCodeGenerator->data->{'_shortcode_assistant_metabox_priority'} == 'high') ? 'selected="selected"' : '') . '>' . esc_html__('High priority', WPKQCG_TEXT_DOMAIN) . '</option>';
						$wpkqcg_metaboxPriority_inputs .= '<option value="core" ' . ((isset($kayaQRCodeGenerator->data->{'_shortcode_assistant_metabox_priority'}) && $kayaQRCodeGenerator->data->{'_shortcode_assistant_metabox_priority'} == 'core') ? 'selected="selected"' : '') . '>' . esc_html__('Core priority', WPKQCG_TEXT_DOMAIN) . '</option>';
						$wpkqcg_metaboxPriority_inputs .= '<option value="default" ' . ((isset($kayaQRCodeGenerator->data->{'_shortcode_assistant_metabox_priority'}) && $kayaQRCodeGenerator->data->{'_shortcode_assistant_metabox_priority'} == 'default') ? 'selected="selected"' : '') . '>' . esc_html__('Default priority', WPKQCG_TEXT_DOMAIN) . '</option>';
						$wpkqcg_metaboxPriority_inputs .= '<option value="low" ' . ((isset($kayaQRCodeGenerator->data->{'_shortcode_assistant_metabox_priority'}) && $kayaQRCodeGenerator->data->{'_shortcode_assistant_metabox_priority'} == 'low') ? 'selected="selected"' : '') . '>' . esc_html__('Low priority', WPKQCG_TEXT_DOMAIN) . '</option>';
						$wpkqcg_metaboxPriority_inputs .= '</select>';

						// QR Code Generator default image color input
						$wpkqcg_imageDefaultColor_inputs = '<input id="wpkqcg_shortcode_assistant_image_defaultColor" name="wpkqcg[data][_shortcode_assistant_image_defaultColor]" type="text" class="wp-kqcg-color-picker" value="' . (isset($kayaQRCodeGenerator->data->{'_shortcode_assistant_image_defaultColor'}) ? $kayaQRCodeGenerator->data->{'_shortcode_assistant_image_defaultColor'} : '#000000') . '" />';

						// QR Code Generator default image background-color input
						$wpkqcg_imageDefaultBGColor_inputs = '<input id="wpkqcg_shortcode_assistant_image_defaultBGColor" name="wpkqcg[data][_shortcode_assistant_image_defaultBGColor]" type="text" class="wp-kqcg-color-picker" value="' . (isset($kayaQRCodeGenerator->data->{'_shortcode_assistant_image_defaultBGColor'}) ? $kayaQRCodeGenerator->data->{'_shortcode_assistant_image_defaultBGColor'} : '#FFFFFF') . '" />';

						?>
						<style>
							table.settings-table th {
								padding-left: 20px;
							}
						</style>
						<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
							<table class="settings-table form-table fixed">
								<tbody>
									<tr>
										<th scope="raw" colspan="2" style="background-color: #f0f0f1;">
											<?php echo esc_html__('Generator assistant settings', WPKQCG_TEXT_DOMAIN); ?>
										</th>
									</tr>
									<tr>
										<th scope="raw">
											<label><?php echo esc_html__('Display the shortcode generator assistant to:', WPKQCG_TEXT_DOMAIN); ?></label>
										</th>
										<td>
											<?php echo $wpkqcg_customRoles_inputs; ?>
										</td>
									</tr>
									<tr>
										<th scope="raw">
											<label><?php echo esc_html__('Display the shortcode generator assistant in:', WPKQCG_TEXT_DOMAIN); ?></label>
										</th>
										<td>
											<?php echo $wpkqcg_postTypes_inputs; ?>
										</td>
									</tr>
									<tr>
										<th scope="raw">
											<label><?php echo esc_html__('Reduced shortcode generator assistant in editor:', WPKQCG_TEXT_DOMAIN); ?></label>
										</th>
										<td>
											<?php echo $wpkqcg_lightForm_inputs; ?>
										</td>
									</tr>
									<tr>
										<th scope="raw">
											<label><?php echo esc_html__('Meta box display context of the shortcode generator assistant in the editor:', WPKQCG_TEXT_DOMAIN); ?></label>
										</th>
										<td>
											<?php echo $wpkqcg_metaboxContext_inputs; ?>
										</td>
									</tr>
									<tr>
										<th scope="raw">
											<label><?php echo esc_html__('Meta box display priority of the shortcode generator assistant in the editor:', WPKQCG_TEXT_DOMAIN); ?></label>
										</th>
										<td>
											<?php echo $wpkqcg_metaboxPriority_inputs; ?>
										</td>
									</tr>
									<tr>
										<th scope="raw" colspan="2" style="background-color: #f0f0f1;">
											<?php echo esc_html__('QR Code image settings', WPKQCG_TEXT_DOMAIN); ?>
										</th>
									</tr>
									<tr>
										<th scope="raw">
											<label><?php echo esc_html__('Default color for QR Code image:', WPKQCG_TEXT_DOMAIN); ?></label><br>
											<small><?php echo esc_html__('Used if no color is specified in shortcode/widget.', WPKQCG_TEXT_DOMAIN); ?></small>
										</th>
										<td>
											<?php echo $wpkqcg_imageDefaultColor_inputs; ?>
										</td>
									</tr>
									<tr>
										<th scope="raw">
											<label><?php echo esc_html__('Default background color for QR Code image:', WPKQCG_TEXT_DOMAIN); ?></label><br>
											<small><?php echo esc_html__('Used if no background color is specified in shortcode/widget.', WPKQCG_TEXT_DOMAIN); ?></small>
										</th>
										<td>
											<?php echo $wpkqcg_imageDefaultBGColor_inputs; ?>
										</td>
									</tr>
								</tbody>
							</table>

							<?php echo wp_nonce_field('wpkqcg_crud_' . get_current_user_id(), 'wpkqcg_crud_edit', true, false); ?>
							<input type="hidden" name="wpkqcg[id]" value="<?php echo esc_attr($kayaQRCodeGenerator->id); ?>" />
							<input type="hidden" name="wpkqcg_action" value="edit" />
							<input type="hidden" name="action" value="wpkqcg_qr_code_generator_form">
							<input type="hidden" name="wpkqcg_target" value="qr_code_generator" />

							<?php echo $wpkqcg_admin_panel; ?>
						</form>

						<?php echo $wpkqcg_delete_panel; ?>

					</div>

					<div class="ks-wp-dashboard-page-content-card">
						<h2 class="ks-wp-dashboard-page-content-card-title"><?php echo esc_html__('Shortcode generator assistant', WPKQCG_TEXT_DOMAIN); ?></h2>
						<?php
						// get form fields and default values
						$formFieldsHTML = WPKQCG_Forms_QRCode::display_form_fields_options();
						$formFieldsDefaultValues = WPKQCG_Forms_QRCode::get_fields_default_value();

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
						$currentPostID			= false;
						$currentPostPermalink	= '';
						$currentAdminURL		= (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
						$formFieldsValidated	= WPKQCG_Forms_QRCode::validate_fields($formFieldsDefaultValues);

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
						$qrcodeImgHTML .= '<div>' . wpkqcg_doDisplayQRCode($formFieldsValidated) . '</div>';
						$qrcodeImgHTML .= '<div>';
						$qrcodeImgHTML .= '<button type="button" onclick="wpkqcg_qrcode_preview_download();" id="wpkqcg_shortcode_generator_preview_open" class="button button-secondary is-button is-default is-large">' . esc_html__('Download QR Code', WPKQCG_TEXT_DOMAIN) . '</button>';
						$qrcodeImgHTML .= '</div></div>';

						// alert permalink html
						$qrcodeAlertHTML = '<div id="wpkqcg_shortcode_generator_preview_permalink_alert" style="display:none;color:#000;background-color:#ddffff;border:1px solid #ccc;padding:16px 32px;">';
						$qrcodeAlertHTML .= esc_html__('The QR Code preview is not available in plugin options page with the permalink as content.', WPKQCG_TEXT_DOMAIN);
						$qrcodeAlertHTML .= '</div>';

						// alert dynamic html
						$qrcodeAlertHTML .= '<div id="wpkqcg_shortcode_generator_preview_dynamic_alert" style="display:none;color:#000;background-color:#ddffff;border:1px solid #ccc;padding:16px 32px;">';
						$qrcodeAlertHTML .= esc_html__('The QR Code preview is not available with a dynamic content.', WPKQCG_TEXT_DOMAIN);
						$qrcodeAlertHTML .= '</div>';

						// set metabox HTML content
						$output = '<table class="form-table"><tbody>';
						$output .= '<tr><th>' . esc_html__('Shortcode Generated:', WPKQCG_TEXT_DOMAIN) . '</th></tr>';
						$output .= '<tr><td>' . $qrcodeShortcodeHTML . '</td></tr>';
						$output .= '<tr><th>' . esc_html__('QR Code Preview:', WPKQCG_TEXT_DOMAIN) . '</th></tr>';
						$output .= '<tr><td>' . $qrcodeImgHTML . $qrcodeAlertHTML . '</td></tr>';
						$output .= '<tr><th>' . esc_html__('Shortcode settings:', WPKQCG_TEXT_DOMAIN) . '</th></tr>';
						$output .= '<tr><td>' . $formFieldsHTML . '</td></tr>';
						$output .= '</tbody></table>';

						// display metabox HTML content
						echo $output;
						?>
					</div>

				</div>

				<div class="ks-wp-dashboard-page-sidebar">
					<?php
					if (is_file(plugin_dir_path(__FILE__) . 'wpkqcg-admin-page-sidebar.php'))
					{
						include_once plugin_dir_path(__FILE__) . 'wpkqcg-admin-page-sidebar.php';
						wpkqcg_admin_doPageSidebar();
					}
					if (is_file(plugin_dir_path(__FILE__) . 'kayastudio-admin-page-sidebar.php'))
					{
						include_once plugin_dir_path(__FILE__) . 'kayastudio-admin-page-sidebar.php';
						kayastudio_plugins_admin_doMainPageSidebar();
					}
					?>
				</div>

				<div class="ks-wp-dashboard-page-footer">
					<div class="ks-wp-dashboard-page-footer-version">
						<p><?php echo esc_html($wpkqcg_footer); ?></p>
					</div>
				</div>

			</div>
		</div>

<?php
	}
}
