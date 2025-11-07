<?php

/** 
 * Kaya QR Code Generator - QR Code Generator CRUD Class
 * Loads, Saves and Reset QR Code Generator Object.
 *
 * @since 1.5.0
 */

if (!defined('ABSPATH'))
{
	exit; // Exit if accessed directly
}

if (!class_exists('WPKQCG_qrcode_generator'))
{
	class WPKQCG_qrcode_generator
	{
		public $id;
		public $data;
		public $new;
		public $saved;
		public $deleted;

		/**
		 * Creates QR Code Generator Object.
		 * Preloads with all QR Code Generator through 'all'
		 *
		 * @param string $action : 'all' will load all QR Code Generator records into $kayaQRCodeGenerator
		 * @param string $action : 'new' will load new QR Code Generator with defaults
		 */
		public function __construct($action = 'all')
		{
			if ($action == 'all') $this->load_all();
			elseif ($action == 'new') $this->load_new();
			elseif ($action == 'update') $this->update();
			elseif ($action == 'delete') $this->destroy();
		}

		/**
		 * Defaults data
		 *
		 * @return array
		 */
		private function default_new()
		{
			if (!current_user_can('manage_options'))
			{
				wp_die('<p>' . __('You do not have sufficient permissions.') . '</p>');
			}

			$new_qcg = array();
			$new_qcg['new'] = true;
			$new_qcg['id'] = WPKQCG_QRCODE_GENERATOR_DB;
			$new_qcg['data'] = array();
			$new_qcg['data']['_shortcode_assistant_to_administrator']	= true;
			$new_qcg['data']['_shortcode_assistant_to_editor']			= true;
			$new_qcg['data']['_shortcode_assistant_to_author']			= true;
			$new_qcg['data']['_shortcode_assistant_to_contributor']		= true;
			$new_qcg['data']['_shortcode_assistant_to_subscriber']		= true;
			// add super admin role if multisite
			if (is_multisite())
			{
				$new_qcg['data']['_shortcode_assistant_to_superadmin']	= true;
			}

			// add customs roles
			$wpkqcg_customRoles = wpkqcg_admin_getUsersRoles();
			foreach ($wpkqcg_customRoles as $i_role)
			{
				$role_key = esc_attr($i_role['id']);
				$data_role_id = esc_attr('_shortcode_assistant_to_' . $role_key);

				if (!isset($new_qcg['data'][$data_role_id]))
				{
					$new_qcg['data'][$data_role_id] = true;
				}
			}

			$new_qcg['data']['_shortcode_assistant_in_page']	= true;
			$new_qcg['data']['_shortcode_assistant_in_post']	= true;

			// add all public post types
			$wpkqcg_postTypes = wpkqcg_admin_getAllPostTypesAsList();
			unset($wpkqcg_postTypes['wpkqcg_admin_dashboard']);
			foreach ($wpkqcg_postTypes as $i_postTypeKey => $i_postTypeValue)
			{
				$postType_key = esc_attr($i_postTypeKey);
				$data_post_id = esc_attr('_shortcode_assistant_in_' . $postType_key);

				if (!isset($new_qcg['data'][$data_post_id]))
				{
					$new_qcg['data'][$data_post_id] = true;
				}
			}

			// add light form
			$new_qcg['data']['_shortcode_assistant_light'] = true;

			// add metabox context
			$new_qcg['data']['_shortcode_assistant_metabox_context'] = 'normal';

			// add metabox priority
			$new_qcg['data']['_shortcode_assistant_metabox_priority'] = 'high';

			// add image default color
			$new_qcg['data']['_shortcode_assistant_image_defaultColor'] = '#000000';

			// add image default background color
			$new_qcg['data']['_shortcode_assistant_image_defaultBGColor'] = '#FFFFFF';

			return $new_qcg;
		}

		/**
		 * Loads defaults into class attributes
		 */
		private function load_new()
		{
			if (!current_user_can('manage_options'))
			{
				wp_die('<p>' . __('You do not have sufficient permissions.') . '</p>');
			}

			$kayaQRCodeGenerator = stripslashes_deep($this->default_new());
			if (empty($kayaQRCodeGenerator)) return '';

			$this->create($kayaQRCodeGenerator);
		}

		/**
		 * Creates new QR Code Generator record
		 */
		private function create($kayaQRCodeGenerator)
		{
			if (!current_user_can('manage_options'))
			{
				wp_die('<p>' . __('You do not have sufficient permissions.') . '</p>');
			}

			$this->saved = false;
			if (empty($kayaQRCodeGenerator)) return '';

			if ($kayaQRCodeGenerator['new'])
			{
				$this->saved = update_option(WPKQCG_QRCODE_GENERATOR_DB, $this->prepare($kayaQRCodeGenerator), false);
			}
		}

		/**
		 * Updates QR Code Generator record and call update functions.
		 * Set $this->saved on success.
		 */
		private function update()
		{
			if (!current_user_can('manage_options'))
			{
				wp_die('<p>' . __('You do not have sufficient permissions.') . '</p>');
			}

			$this->saved = false;
			if (empty($_POST) || empty($_POST['wpkqcg']) || empty($_POST['wpkqcg_crud_edit'])) return '';

			if (wp_verify_nonce($_POST['wpkqcg_crud_edit'], 'wpkqcg_crud_' . get_current_user_id()))
			{
				$kayaQRCodeGenerator = $_POST['wpkqcg'];
				if (empty($kayaQRCodeGenerator) || !isset($kayaQRCodeGenerator['id'])) return '';

				$preparedSettingsData = $this->prepare($kayaQRCodeGenerator);

				$this->saved = update_option(WPKQCG_QRCODE_GENERATOR_DB, $preparedSettingsData, false);
				if (!$this->saved)
				{
					$currentSettingsData = get_option(WPKQCG_QRCODE_GENERATOR_DB);
					if ($currentSettingsData == $preparedSettingsData)
					{
						// new settings value is same as current settings value
						$this->saved = true;
					}
				}
			}
			WPKQCG_Admin_Dashboard::addAdminNotice(__('Saving QR Code Generator settings', WPKQCG_TEXT_DOMAIN), $this->saved);
		}

		/**
		 * Delete QR Code Generator record from $_POST
		 * Set $this->deleted on success.
		 */
		private function destroy()
		{
			if (!current_user_can('manage_options'))
			{
				wp_die('<p>' . __('You do not have sufficient permissions.') . '</p>');
			}

			if (empty($_POST) || empty($_POST['wpkqcg']) || empty($_POST['wpkqcg_crud_delete'])) return '';

			if (wp_verify_nonce($_POST['wpkqcg_crud_delete'], 'wpkqcg_crud_' . get_current_user_id()))
			{
				$kayaQRCodeGenerator = $_POST['wpkqcg'];
				if (empty($kayaQRCodeGenerator) || !isset($kayaQRCodeGenerator['id'])) return '';

				$this->deleted = update_option(WPKQCG_QRCODE_GENERATOR_DB, '', false);
			}
			WPKQCG_Admin_Dashboard::addAdminNotice(__('Reset QR Code Generator settings', WPKQCG_TEXT_DOMAIN), $this->deleted);
		}

		/**
		 * Converts array input values into values need for database storage
		 *
		 * @param array $q : raw QR Code Generator associative array from $_POST
		 *
		 * @return array : array prepared for database insertion
		 */
		private function prepare($q)
		{
			if (empty($q) || empty($q['data']) || empty($q['id'])) return '';

			$attributes = array();
			$bools = array();

			$wpkqcg_custom_roles = wpkqcg_admin_getUsersRoles();
			foreach ($wpkqcg_custom_roles as $i_role)
			{
				$role_key = esc_attr($i_role['id']);
				$bools[] = esc_attr('_shortcode_assistant_to_' . $role_key);
			}

			$wpkqcg_postTypes = wpkqcg_admin_getAllPostTypesAsList();
			unset($wpkqcg_postTypes['wpkqcg_admin_dashboard']);
			foreach ($wpkqcg_postTypes as $i_postTypeKey => $i_postTypeValue)
			{
				$postType_key = esc_attr($i_postTypeKey);
				$bools[] = esc_attr('_shortcode_assistant_in_' . $postType_key);
			}

			$bools[] = esc_attr('_shortcode_assistant_light');

			$attributes[] = esc_attr('_shortcode_assistant_metabox_context');
			$attributes[] = esc_attr('_shortcode_assistant_metabox_priority');
			$attributes[] = esc_attr('_shortcode_assistant_image_defaultColor');
			$attributes[] = esc_attr('_shortcode_assistant_image_defaultBGColor');

			foreach ($attributes as $att)
			{
				$attributes[$att] = !empty($q['data'][$att]) ? esc_attr(sanitize_text_field($q['data'][$att])) : '';
			}

			foreach ($bools as $b)
			{
				$attributes[$b] = empty($q['data'][$b]) ? 0 : 1;
			}

			$result = base64_encode(serialize($attributes));

			return $result;
		}

		/**
		 * Load all QR Code Generator
		 * Stripslashes on DB return values.
		 */
		private function load_all()
		{
			$settingsData = get_option(WPKQCG_QRCODE_GENERATOR_DB);

			if (empty($settingsData))
			{
				$this->load_new();
				$settingsData = get_option(WPKQCG_QRCODE_GENERATOR_DB);
			}

			if (!empty($settingsData))
			{
				$kayaQRCodeGenerator = stripslashes_deep($settingsData);
				$this->id = WPKQCG_QRCODE_GENERATOR_DB;
				$data_attributes = stripslashes_deep(unserialize(base64_decode($kayaQRCodeGenerator)));

				if (empty($data_attributes) || !is_array($data_attributes)) return '';

				if (!isset($this->data))
					$this->data = new stdClass();

				foreach ($data_attributes as $attr => $val)
				{
					$this->data->$attr = $val;
				}
				$this->new = false;
			}
		}
	}
}
