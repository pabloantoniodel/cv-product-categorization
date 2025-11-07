<?php

/**
 * Kaya Studio - Admin Main Page
 * Displays Kaya Studio admin main page.
 *
 * @version 1.0.0
 */

if (!function_exists('kayastudio_plugins_admin_doMainPage'))
{
	/**
	 * Displays Kaya Studio page.
	 */
	function kayastudio_plugins_admin_doMainPage()
	{
		if (!current_user_can('manage_options'))
		{
			wp_die('<p>' . __('You do not have sufficient permissions.') . '</p>');
		}

		global $wp_kayastudio_dashboard_pluginsList;

		$pluginsList = $wp_kayastudio_dashboard_pluginsList->getPluginList();

?>

		<div class="ks-wp-dashboard-page-container">
			<div class="ks-wp-dashboard-page-row">

				<div class="ks-wp-dashboard-page-header">
					<div class="ks-wp-dashboard-page-header-title">
						<h1>Kaya Studio</h1>
					</div>
				</div>

				<div class="ks-wp-dashboard-page-content">

					<?php

					foreach ($pluginsList as $i_plugin)
					{

					?>
						<div class="ks-wp-dashboard-page-content-card">
							<h6 class="ks-wp-dashboard-page-content-card-title"><?php echo esc_html($i_plugin['title']); ?></h6>
							<p>
								<?php echo esc_html($i_plugin['page_text']); ?>
							</p>
							<p>
								<a href="<?php echo esc_url(admin_url('admin.php?page=' . $i_plugin['page_slug'])); ?>" class="ks-wp-dashboard-page-btn ks-wp-dashboard-page-btn-primary" title="<?php echo esc_attr($i_plugin['page_name']); ?>"><?php echo esc_html($i_plugin['page_name']); ?></a>
							</p>
						</div>
					<?php

					}

					?>

					<div class="ks-wp-dashboard-page-content-card">
						<h6 class="ks-wp-dashboard-page-content-card-title"><?php echo esc_html__('WordPress plugins by Kaya Studio', WPKQCG_TEXT_DOMAIN); ?></h6>
						<p>
							<?php echo esc_html__('Kaya Studio is managed by a freelance web developer. These plugins are developed in his free time and for the love of making useful open source softwares.', WPKQCG_TEXT_DOMAIN); ?>
						</p>
						<p>
							<?php echo esc_html__('Discover all the WordPress plugins developed by Kaya Studio.', WPKQCG_TEXT_DOMAIN); ?>
						</p>
						<p>
							<a href="<?php echo esc_url('https://profiles.wordpress.org/kayastudio#content-plugins'); ?>" class="ks-wp-dashboard-page-btn ks-wp-dashboard-page-btn-primary" target="_blank" rel="noopener noreferrer" title="<?php echo esc_attr__('All plugins by Kaya Studio', WPKQCG_TEXT_DOMAIN); ?>"><?php echo esc_html__('All plugins', WPKQCG_TEXT_DOMAIN); ?></a>
						</p>
					</div>

				</div>

				<div class="ks-wp-dashboard-page-sidebar">
					<?php
					if (is_file(plugin_dir_path(__FILE__) . 'kayastudio-admin-page-sidebar.php'))
					{
						include_once plugin_dir_path(__FILE__) . 'kayastudio-admin-page-sidebar.php';
						kayastudio_plugins_admin_doMainPageSidebar();
					}
					?>
				</div>

			</div>
		</div>

<?php
	}
}
