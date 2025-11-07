<?php

/**
 * Kaya Studio - Admin Main Page Sidebar
 * Displays Kaya Studio admin main page sidebar.
 *
 * @version 1.0.0
 */

if (!function_exists('kayastudio_plugins_admin_doMainPageSidebar'))
{
	/**
	 * Displays Kaya Studio page sidebar.
	 */
	function kayastudio_plugins_admin_doMainPageSidebar()
	{
		if (!current_user_can('manage_options'))
		{
			wp_die('<p>' . __('You do not have sufficient permissions.') . '</p>');
		}

?>

		<div class="ks-wp-dashboard-page-card">
			<div class="ks-wp-dashboard-page-card-header">
				<?php echo esc_html__('Donate', WPKQCG_TEXT_DOMAIN); ?>
			</div>
			<div class="ks-wp-dashboard-page-card-body">
				<h5 class="ks-wp-dashboard-page-card-title"><?php echo esc_html__('Support the advancement of this plugin', WPKQCG_TEXT_DOMAIN); ?>&nbsp;&#9829;</h5>
				<p class="ks-wp-dashboard-page-card-text">
					<?php echo esc_html__('You can give to support this plugin development and to encourage the developer for making open source softwares.', WPKQCG_TEXT_DOMAIN); ?>
				</p>
				<p class="ks-wp-dashboard-page-card-text">
					<?php echo esc_html__('Make a donation with Paypal:', WPKQCG_TEXT_DOMAIN); ?>
				</p>
				<p class="ks-wp-dashboard-page-card-text">
					<a href="https://www.paypal.me/dotkaya" class="ks-wp-dashboard-page-btn ks-wp-dashboard-page-btn-primary" target="_blank" rel="noopener noreferrer"><?php echo esc_html__('Donate', WPKQCG_TEXT_DOMAIN); ?></a>
				</p>
				<p class="ks-wp-dashboard-page-card-text">
					<?php echo esc_html__('Make a donation with Bitcoin:', WPKQCG_TEXT_DOMAIN); ?></p>
				<p class="ks-wp-dashboard-page-card-text">
					<a href="bitcoin:1DosbNiE4L8HPBJrwYpWmcomTyh3Yxy5oU?label=KayaStudio" target="_blank" rel="noopener noreferrer">
						<img src="data:image/png;base64,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" alt="Make a donation with Bitcoin" class="ks-wp-dashboard-page-qrcode" style="width: auto; height: auto; max-width: 100%;">
					</a>
				</p>
			</div>
		</div>

<?php
	}
}
