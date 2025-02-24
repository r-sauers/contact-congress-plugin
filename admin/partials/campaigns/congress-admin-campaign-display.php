<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the Campaigns admin page.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Congress
 * @subpackage Congress/admin/partials
 */

/**
 * The class used to display archived campaigns.
 */
require plugin_dir_path( __FILE__ ) . 'class-congress-admin-archived-campaign.php';

/**
 * The class used to display active campaigns.
 */
require plugin_dir_path( __FILE__ ) . 'class-congress-admin-active-campaign.php';

$display_campaign_type = 'active';

?>


<!-- This file should primarily consist of HTML with a little bit of PHP. -->
<div>
	<h1>Campaigns
		<select>
			<option 
				<?php echo esc_attr( 'active' === $display_campaign_type ? 'selected' : '' ); ?>
			>Active</option>
			<option
				<?php echo esc_attr( 'archived' === $display_campaign_type ? 'selected' : '' ); ?>
			>Archived</option>
		</select>
	</h1>
	<div 
		id="congress-active-campaigns-container" 
		class="<?php echo esc_attr( 'active' === $display_campaign_type ? '' : 'congress-hidden' ); ?>"
	>
		<ul class="congress-campaign-list">
		<?php
		$campaign = new Congress_Admin_Active_Campaign( 0, 'EATS Act', 'Federal', 12 );
		?>
			<li id="<?php echo esc_attr( 'congress-campaign-' . $campaign->get_id() ); ?>">
			<?php
			$campaign->display( true );
			?>
			</li>
		</ul>
		<button>Add Campaign</button>
	</div>
	<div
		id="congress-archived-campaigns-container"
		class="<?php echo esc_attr( 'archived' === $display_campaign_type ? '' : 'congress-hidden' ); ?>"
	>
		<ul class="congress-campaign-list">
		<?php
		$date     = new DateTimeImmutable();
		$campaign = new Congress_Admin_Archived_Campaign(
			0,
			'EATS Act',
			'Federal',
			12,
			$date->getTimestamp() - 360 * 24 * 120,
			$date->getTimestamp(),
		);
		?>
			<li id="<?php echo esc_attr( 'congress-campaign-' . $campaign->get_id() ); ?>">
			<?php
			$campaign->display();
			?>
			</li>
		</ul>
	</div>
	<template id="congress-campaign-template">
		<?php
			Congress_Admin_Active_Campaign::get_html_template();
		?>
	</template>
</div>
