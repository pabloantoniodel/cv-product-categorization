<?php

/** 
 * Kaya QR Code Generator - Admin Dashboard Class
 * Manages Kaya QR Code Generator admin page.
 *
 * @since 1.4.1
 */

if (!defined('ABSPATH'))
{
	exit; // Exit if accessed directly
}

if (!class_exists('WPKQCG_Admin_Dashboard'))
{
	class WPKQCG_Admin_Dashboard
	{
		/**
		 * Menu Page Slug
		 */
		public static $_pageSlug = 'wpkqcg-kaya-qr-code-generator-admin-settings-page';

		/**
		 * Notice Option Name
		 */
		public static $_noticeOptionName = 'wpkqcg_kaya_qr_code_generator_admin_notices';

		/**
		 * Main Initialisation
		 * Adds admin menu and enqueue scripts.
		 */
		public static function init()
		{
			add_action('admin_menu', array(__CLASS__, 'addAdminMenuPage'));
			add_action('admin_enqueue_scripts', array(__CLASS__, 'addAdminCssJs'));
			add_action('admin_post_wpkqcg_qr_code_generator_form', array(__CLASS__, 'doAdminPostRequests'));
			add_action('admin_notices', array(__CLASS__, 'doAdminNotices'));
		}

		/**
		 * Adds admin menu
		 * Adds a submenu for Kaya QR Code Generator admin page.
		 */
		public static function addAdminMenuPage()
		{
			// add plugin features page
			add_submenu_page(
				WP_KayaStudio_Plugins_Admin_Dashboard::$_pageSlug,
				esc_html__('QR Code Generator', WPKQCG_TEXT_DOMAIN),
				esc_html__('QR Code Generator', WPKQCG_TEXT_DOMAIN),
				'manage_options',
				self::$_pageSlug,
				array(__CLASS__, 'doAdminPage')
			);
		}

		/**
		 * Return the plugin informations to be added in Plugins List
		 *
		 * @return array
		 */
		public static function getPluginInfos()
		{
			return array(
				'title'		=> esc_attr('Kaya QR Code Generator'),
				'page_name'	=> esc_attr__('QR Code Generator settings', WPKQCG_TEXT_DOMAIN),
				'page_slug'	=> self::$_pageSlug,
				'page_text'	=> esc_attr__('Generate QR Code through Widgets and Shortcodes, without any dependencies.', WPKQCG_TEXT_DOMAIN),
			);
		}

		/**
		 * Displays admin page
		 * Includes the page and display it.
		 */
		public static function doAdminPage()
		{
			if (is_file(plugin_dir_path(__FILE__) . '../includes/wpkqcg-admin-page.php'))
			{
				include_once plugin_dir_path(__FILE__) . '../includes/wpkqcg-admin-page.php';
				wpkqcg_admin_doOptionPage();
			}
		}

		/**
		 * Adds admin menu styles and scripts
		 * Registers and enqueue styles and scripts for Kaya QR Code Generator admin page.
		 *
		 * @param int $hook Hook suffix for the current admin page.
		 */
		public static function addAdminCssJs($hook)
		{
			if (isset($hook) && !empty($hook) && get_plugin_page_hookname(self::$_pageSlug, WP_KayaStudio_Plugins_Admin_Dashboard::$_pageSlug) === $hook)
			{
				wp_register_style('kayastudio_wp_admin_css', plugin_dir_url(__FILE__) . '../css/kayastudio-admin-page-pkg.min.css', false, '1.0.0');
				wp_enqueue_style('kayastudio_wp_admin_css');
			}
		}

		/**
		 * Manages admin page requests actions
		 * Edit or reset the WPKQCG_qrcode_generator object and redirect.
		 *
		 * @return bool
		 *
		 * @since 1.5.0
		 */
		public static function doAdminPostRequests()
		{
			if (empty($_POST) || empty($_POST['wpkqcg_action']) || empty($_POST['wpkqcg']))
			{
				return false;
			}

			if (!current_user_can('manage_options'))
			{
				return false;
			}

			if (!empty($_POST['wpkqcg_target']) && 'qr_code_generator' === $_POST['wpkqcg_target'])
			{
				// include the WPKQCG_qrcode_generator class
				require_once(WPKQCG_PLUGIN_PATH . 'lib/class.crud_qrcode_generator.php');

				if ($_POST['wpkqcg_action'] == 'edit')
				{
					// init WPKQCG_qrcode_generator object for update
					$kayaQRCodeGenerator = new WPKQCG_qrcode_generator('update');
				}
				elseif ($_POST['wpkqcg_action'] == 'delete')
				{
					// init WPKQCG_qrcode_generator object for reset
					$kayaQRCodeGenerator = new WPKQCG_qrcode_generator('delete');
					// reset with default settings
					require_once(WPKQCG_PLUGIN_PATH . 'lib/functions-database.php');
					wpkqcg_database_defaultValues();
				}

				// set admin url query with the page and the action message
				$adminUrlQuery = array(
					'page'		=> self::$_pageSlug,
					'message'	=> '1'
				);
				// set the full admin url for redirection
				$redirectURL = esc_url_raw(admin_url('admin.php?' . http_build_query($adminUrlQuery)));

				if (wp_redirect($redirectURL))
				{
					return true;
				}
			}

			return false;
		}

		/**
		 * Adds notice to admin page.
		 *
		 * @param string	$p_message The notice message.
		 * @param bool	$p_success Set this to TRUE for a success notice.
		 *
		 * @since 1.5.0
		 */
		public static function addAdminNotice($p_message = '', $p_success = false)
		{
			// get all notices
			$notices = get_option(self::$_noticeOptionName, array());
			// add the notice to the actual list
			array_push($notices, array(
				'message'	=> $p_message,
				'type'		=> (($p_success) ? '1' : '0')
			));
			// save notices
			update_option(self::$_noticeOptionName, $notices);
		}

		/**
		 * Displays admin page notices
		 * Prints admin screen notices about form requests.
		 *
		 * @since 1.5.0
		 */
		public static function doAdminNotices()
		{
			$currentScreen = get_current_screen();
			if (get_plugin_page_hookname(self::$_pageSlug, WP_KayaStudio_Plugins_Admin_Dashboard::$_pageSlug) !== $currentScreen->id)
			{
				return false;
			}

			// get all notices
			$notices = get_option(self::$_noticeOptionName, array());

			$output = '';
			foreach ($notices as $i_notice)
			{
				// get notice message
				$noticeMessage = $i_notice['message'];

				// set default error notice data
				$noticeClasses	= 'notice-error';
				$noticeTitle	= __('Error!', WPKQCG_TEXT_DOMAIN);

				// set success notice data
				if ('1' === $i_notice['type'])
				{
					$noticeClasses	= 'notice-success';
					$noticeTitle	= __('Success!', WPKQCG_TEXT_DOMAIN);
				}
				// set notice HTML structure
				$output .= '<div class="notice ' . esc_attr($noticeClasses) . ' is-dismissible">';
				$output .= '<p><b>' . esc_html($noticeTitle) . '</b><br />' . esc_html($noticeMessage) . '</p>';
				$output .= '</div>';
			}

			if (!empty($notices))
			{
				// display the notices
				echo $output;
				// delete notices to prevent other displaying
				delete_option(self::$_noticeOptionName, array());
			}
		}
	}
}
