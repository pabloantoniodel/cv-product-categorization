<?php

/**
 * Kaya QR Code Generator - Database Functions.
 * Functions for the plugin database management.
 *
 * @since 1.5.0
 */

if (!defined('ABSPATH'))
{
	exit; // Exit if accessed directly
}

if (!function_exists('wpkqcg_database_installSetup'))
{
	/**
	 * Default setup on plugin activation.
	 *
	 * @since 1.5.0
	 */
	function wpkqcg_database_installSetup()
	{
		if (!current_user_can('manage_options'))
		{
			wp_die('<p>' . __('You do not have sufficient permissions.') . '</p>');
		}

		if (!get_option(WPKQCG_QRCODE_GENERATOR_DB))
		{
			// loading localisation before the first setup.
			wpkqcg_plugin_loadLocalisation();
			// set default settings
			wpkqcg_database_defaultValues();
		}

		// setup default settings for multisite
		if (is_multisite())
		{
			$subSites = get_sites();
			foreach ($subSites as $i_site)
			{
				switch_to_blog($i_site->blog_id);
				if (!get_option(WPKQCG_QRCODE_GENERATOR_DB))
				{
					// loading localisation before the setup.
					wpkqcg_plugin_loadLocalisation();
					// set default settings
					wpkqcg_database_defaultValues();
				}
				restore_current_blog();
			}
		}
	}
}

if (!function_exists('wpkqcg_database_installSetupNewSite'))
{
	/**
	 * Default setup on new site creation in multisite.
	 *
	 * @param WP_Site $new_site New site object.
	 *
	 * @since 1.5.0
	 */
	function wpkqcg_database_installSetupNewSite($new_site)
	{
		if (!current_user_can('manage_options'))
		{
			wp_die('<p>' . __('You do not have sufficient permissions.') . '</p>');
		}

		// setup default settings for multisite
		if (is_multisite())
		{
			switch_to_blog($new_site->id);
			if (!get_option(WPKQCG_QRCODE_GENERATOR_DB))
			{
				// loading localisation before the setup.
				wpkqcg_plugin_loadLocalisation();
				// set default settings
				wpkqcg_database_defaultValues();
			}
			restore_current_blog();
		}
	}
}

if (!function_exists('wpkqcg_database_defaultValues'))
{
	/**
	 * Set default database values.
	 *
	 * @since 1.5.0
	 */
	function wpkqcg_database_defaultValues()
	{
		if (!current_user_can('manage_options'))
		{
			wp_die('<p>' . __('You do not have sufficient permissions.') . '</p>');
		}

		require_once(WPKQCG_PLUGIN_PATH . 'lib/class.crud_qrcode_generator.php');
		$kayaQRCodeGenerator = new WPKQCG_qrcode_generator('new');
	}
}
