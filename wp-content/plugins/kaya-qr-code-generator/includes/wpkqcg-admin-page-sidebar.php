<?php

/**
 * Kaya QR Code Generator - Admin Page Sidebar
 * Displays Kaya QR Code Generator admin page sidebar.
 *
 * @since 1.4.1
 */

if (!function_exists('wpkqcg_admin_doPageSidebar'))
{
	/**
	 * Displays Kaya Studio page sidebar.
	 */
	function wpkqcg_admin_doPageSidebar()
	{
		if (!current_user_can('manage_options'))
		{
			wp_die('<p>' . __('You do not have sufficient permissions.') . '</p>');
		}

?>

		<div class="ks-wp-dashboard-page-card">
			<div class="ks-wp-dashboard-page-card-header">
				<?php echo esc_html__('Reviews', WPKQCG_TEXT_DOMAIN); ?>
			</div>
			<div class="ks-wp-dashboard-page-card-body">
				<h5 class="ks-wp-dashboard-page-card-title"><?php echo esc_html__('Rate and review this plugin at WordPress.org', WPKQCG_TEXT_DOMAIN); ?>&nbsp;&#9733;</h5>
				<p class="ks-wp-dashboard-page-card-text">
					<?php echo esc_html__('Please take the time to let me know about your experience and rate this plugin.', WPKQCG_TEXT_DOMAIN); ?>
				</p>
				<p class="ks-wp-dashboard-page-card-text">
					<a href="<?php echo esc_url('https://wordpress.org/support/plugin/kaya-qr-code-generator/reviews/?rate=5#new-post'); ?>" class="ks-wp-dashboard-page-btn ks-wp-dashboard-page-btn-primary" target="_blank" rel="noopener noreferrer" title="<?php echo esc_attr__('Rate and review this plugin at WordPress.org', WPKQCG_TEXT_DOMAIN); ?>"><?php echo esc_html__('Rate this plugin', WPKQCG_TEXT_DOMAIN); ?></a>
				</p>
			</div>
		</div>

<?php
	}
}
