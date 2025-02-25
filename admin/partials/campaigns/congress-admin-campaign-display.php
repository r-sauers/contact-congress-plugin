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

/**
 * A component for stacked inputs in forms.
 */
require_once plugin_dir_path( __FILE__ ) . '../class-congress-admin-stacked-input.php';

$display_campaign_type = 'active';

?>


<!-- This file should primarily consist of HTML with a little bit of PHP. -->
<div>
	<h1>Campaigns
		<select id="congress-campaign-archive-toggle">
			<option 
				value="active"
				<?php echo esc_attr( 'active' === $display_campaign_type ? 'selected' : '' ); ?>
			>Active</option>
			<option
				value="archived"
				<?php echo esc_attr( 'archived' === $display_campaign_type ? 'selected' : '' ); ?>
			>Archived</option>
		</select>
	</h1>
	<div 
		id="congress-active-campaigns-container" 
		class="<?php echo esc_attr( 'active' === $display_campaign_type ? '' : 'congress-hidden' ); ?>"
	>
		<form id="congress-campaign-add" class="congress-inline-form-group">
			<button type="submit" class="button button-primary">Add Campaign</button>
			<?php
			Congress_Admin_Stacked_Input::display_dropdown(
				id: 'congress-campaign-add-level',
				label: 'Level',
				name: 'level',
				value: 'federal',
				options: array(
					array(
						'label' => 'Federal',
						'value' => 'federal',
					),
					array(
						'label' => 'State',
						'value' => 'state',
					),
				),
			);
			wp_nonce_field( 'create-campaign' );
			Congress_Admin_Stacked_Input::display_text(
				id: 'congress-campaign-add-name',
				label: 'Campaign Name',
				name: 'name',
				value: '',
			);
			?>
		</form>
		<ul class="congress-campaign-list">
		<?php
		$campaigns = Congress_Admin_Active_Campaign::get_from_db();
		foreach ( $campaigns as $campaign ) {
			?>
			<li id="<?php echo esc_attr( 'congress-campaign-' . $campaign->get_id() ); ?>">
			<?php
				$campaign->display( false );
			?>
			</li>
			<?php

		}
		?>
		</ul>
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
