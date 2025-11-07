<?php

/**
 * Kaya QR Code Generator - Main Admin Functions.
 * Managing Admin features.
 */

if (!defined('ABSPATH'))
{
	exit; // Exit if accessed directly
}

/**
 * Check for KayaStudio Plugins object and create it if not found.
 *
 * @since 1.4.1
 */
if (!isset($wp_kayastudio_dashboard_pluginsList))
{
	global $wp_kayastudio_dashboard_pluginsList;
	$wp_kayastudio_dashboard_pluginsList = new WP_KayaStudio_Plugins_List_Admin_Dashboard();
}

if (!function_exists('wpkqcg_admin_addMenuPages'))
{
	/**
	 * Adds administration plugin menu pages.
	 *
	 * Adds pages to admin menu (Main page, Plugin Settings), and adds plugin infos in plugins list.
	 *
	 * @return bool	True if the current user has the specified capability for seeing the menu, or False if not.
	 *
	 * @since 1.4.1
	 */
	function wpkqcg_admin_addMenuPages()
	{
		if (!current_user_can('manage_options'))
		{
			return false;
		}
		global $wp_kayastudio_dashboard_pluginsList;

		// Add Kaya Studio Main page
		WP_KayaStudio_Plugins_Admin_Dashboard::init();
		// Add Kaya QR Code Generator page
		WPKQCG_Admin_Dashboard::init();
		// Add Kaya QR Code Generator infos in plugins list
		$wp_kayastudio_dashboard_pluginsList->addPluginInList(WPKQCG_Admin_Dashboard::getPluginInfos());

		return true;
	}
	add_action('init', 'wpkqcg_admin_addMenuPages');
}

if (!function_exists('wpkqcg_admin_enqueueAdminScripts'))
{
	/**
	 * Enqueue Admin scripts in footer.
	 * Required for the shortcode generator assistant, available on pages, posts and custom post types.
	 *
	 * @since 1.5.0
	 */
	function wpkqcg_admin_enqueueAdminScripts()
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

		// get current screen id
		$currentScreen = get_current_screen();
		$currentScreenID = $currentScreen ? $currentScreen->id : '';

		// Get alowed post types
		$postTypes = wpkqcg_admin_getAllowedPostTypes($kayaQRCodeGenerator, array('widgets'), array());

		// return if current screen is not allowed
		if (!in_array($currentScreenID, $postTypes))
		{
			return;
		}

		wp_enqueue_style('wp-color-picker');
		wp_enqueue_script('wpkqcg-admin-pkg', WPKQCG_PLUGIN_URL . 'js/wpkqcg-admin-pkg.min.js', array('wp-color-picker'), WPKQCG_VERSION, true);
		wp_enqueue_script('wpkqcg-asset', WPKQCG_PLUGIN_URL . 'assets/qrcode-v2.min.js', array(), WPKQCG_VERSION, true);
		wp_enqueue_script('wpkqcg-pkg', WPKQCG_PLUGIN_URL . 'js/wpkqcg-pkg.min.js', array(), WPKQCG_VERSION, true);

		if ($currentScreenID == 'widgets')
		{
			wp_enqueue_script('underscore');
			wp_enqueue_script('wpkqcg-admin-widget', WPKQCG_PLUGIN_URL . 'js/wpkqcg-admin-widget.min.js', array('jquery'), WPKQCG_VERSION, true);
		}
		else
		{
			wp_enqueue_script('wpkqcg-admin-display', WPKQCG_PLUGIN_URL . 'js/wpkqcg-admin-display.min.js', array('jquery'), WPKQCG_VERSION, true);
		}
	}
	add_action('admin_enqueue_scripts', 'wpkqcg_admin_enqueueAdminScripts');
}

if (!function_exists('wpkqcg_admin_isCurrentRoleAllowed'))
{
	/**
	 * Returns if the current role is allowed to see shortcode assistant.
	 *
	 * @param WPKQCG_qrcode_generator $kayaQRCodeGenerator	QRCode generator object.
	 *
	 * @return bool
	 *
	 * @since 1.5.0
	 */
	function wpkqcg_admin_isCurrentRoleAllowed($kayaQRCodeGenerator)
	{
		// Get current user role
		$current_user		= wp_get_current_user();
		$current_userRoles	= (array) $current_user->roles;

		// Get all customs roles
		$wpkqcg_customRoles = wpkqcg_admin_getUsersRoles();

		// check for allowed roles
		$currentRoleAllowed = false;
		foreach ($wpkqcg_customRoles as $i_role)
		{
			$role_key = esc_attr($i_role['id']);
			$role_id = esc_attr('_shortcode_assistant_to_' . $role_key);

			if (isset($kayaQRCodeGenerator->data->{$role_id}) && $kayaQRCodeGenerator->data->{$role_id} && in_array($role_key, $current_userRoles))
			{
				$currentRoleAllowed = true;
			}
		}

		return $currentRoleAllowed;
	}
}

if (!function_exists('wpkqcg_admin_getAllowedPostTypes'))
{
	/**
	 * Returns allowed post types to display shortcode assistant.
	 *
	 * @param WPKQCG_qrcode_generator	$kayaQRCodeGenerator	QRCode generator object.
	 * @param array						$postTypesToAdd			Post types to add to the list.
	 * @param array						$postTypesToRemove		Post types to remove from the list.
	 *
	 * @return array List of allowed post types.
	 *
	 * @since 1.5.0
	 */
	function wpkqcg_admin_getAllowedPostTypes($kayaQRCodeGenerator, $postTypesToAdd, $postTypesToRemove)
	{
		// get all public post types
		$postTypes = wpkqcg_admin_getAllPostTypesAsList();

		// add post types
		if (!empty($postTypesToAdd) && is_array($postTypesToAdd))
		{
			foreach ($postTypesToAdd as $i_postTypeToAdd)
			{
				$postTypes[$i_postTypeToAdd] = $i_postTypeToAdd;
			}
		}

		// remove post types
		if (!empty($postTypesToRemove) && is_array($postTypesToRemove))
		{
			foreach ($postTypesToRemove as $i_postTypeToRemove)
			{
				if (isset($postTypes[$i_postTypeToRemove]))
				{
					unset($postTypes[$i_postTypeToRemove]);
				}
			}
		}

		// check for allowed post types
		foreach ($postTypes as $i_postType)
		{
			$postType_id = esc_attr('_shortcode_assistant_in_' . $i_postType);
			if (isset($kayaQRCodeGenerator->data->{$postType_id}) && !$kayaQRCodeGenerator->data->{$postType_id})
			{
				unset($postTypes[$i_postType]);
			}
		}

		return $postTypes;
	}
}

if (!function_exists('wpkqcg_admin_getAllPostTypesAsList'))
{
	/**
	 * Get all public post types.
	 * 
	 * Return public basics post types and custom post types as list.
	 *
	 * @return array
	 *
	 * @since 1.3.0
	 */
	function wpkqcg_admin_getAllPostTypesAsList()
	{
		// Get all post types as list
		$postTypesArgs = array(
			'public' => true,
		);
		$postTypesOutput	= 'names';
		$postTypesOperator	= 'and';
		$postTypes			= get_post_types($postTypesArgs, $postTypesOutput, $postTypesOperator);

		// Remove some built ins or others
		$postTypesRemove	= array('attachment', 'revision', 'nav_menu_item', 'custom_css', 'customize_changeset', 'oembed_cache', 'user_request', 'wp_block');
		$postTypesCleaned	= array();

		foreach ($postTypes as $i_postType)
		{
			if (in_array($i_postType, $postTypesRemove)) continue;
			$postTypesCleaned[esc_attr($i_postType)] = esc_attr($i_postType);
		}

		// Add admin options page
		$postTypesCleaned['wpkqcg_admin_dashboard'] = esc_attr(get_plugin_page_hookname(WPKQCG_Admin_Dashboard::$_pageSlug, WP_KayaStudio_Plugins_Admin_Dashboard::$_pageSlug));

		return $postTypesCleaned;
	}
}

if (!function_exists('wpkqcg_admin_getUsersRoles'))
{
	/**
	 * Return WordPress users roles.
	 *
	 * Retrieve list of WordPress roles id and names, including 'super admin' for multisite.
	 *
	 * @return array List of roles id and names.
	 *
	 * @since 1.5.0
	 */
	function wpkqcg_admin_getUsersRoles()
	{
		global $wp_roles;
		$usersRoles = array();
		foreach ($wp_roles->get_names() as $i_roleKey => $i_roleValue)
		{
			$usersRoles[] = array('id' => $i_roleKey, 'name' => $i_roleValue);
		}

		// add super admin role for multisite
		if (is_multisite())
		{
			array_unshift($usersRoles, array('id' => 'superadmin', 'name' => __('Super Admin')));
		}

		return $usersRoles;
	}
}
