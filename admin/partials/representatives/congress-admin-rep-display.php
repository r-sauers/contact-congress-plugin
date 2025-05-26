<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the Representatives admin page.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Congress
 * @subpackage Congress/admin/partials
 */

/**
 * Imports Congress_Admin_Rep to get and display representatives.
 */
require_once plugin_dir_path( __FILE__ ) . 'class-congress-admin-rep.php';

/**
 * Imports Congress_Admin_Staffer to make template for staffers.
 */
require_once plugin_dir_path( __FILE__ ) . 'class-congress-admin-staffer.php';

/**
 * Imports Congress_State_Settings.
 */
require_once plugin_dir_path( __DIR__ ) . 'states/class-congress-state-settings.php';

?>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->
<div>
	<div style="padding-top: 1em;">
		<h1>Representatives</h1>
		<form id="congress-filter-form" action="get_representatives" method="get" style="display: flex; align-items: flex-end;">
			<div class="congress-inline-form-group">
				<span class="congress-stacked-input">
					<label>Filter By:</label>
				</span>
				<?php
				Congress_Admin_Stacked_Input::display_dropdown(
					id: 'congress-filter-level',
					label: 'Level',
					name: 'level',
					value: Congress_Level::Federal->to_db_string(),
					options: array(
						array(
							'value' => '',
							'label' => 'All',
						),
						array(
							'value' => Congress_Level::Federal->to_db_string(),
							'label' => Congress_Level::Federal->to_display_string(),
						),
						array(
							'value' => Congress_Level::State->to_db_string(),
							'label' => Congress_Level::State->to_display_string(),
						),
					),
				);

				$options = array(
					array(
						'value' => '',
						'label' => 'All',
					),
				);

				$states = Congress_State_Settings::get_active_states();

				foreach ( $states as &$state ) {
					array_push(
						$options,
						array(
							'value' => $state->to_db_string(),
							'label' => strtoupper( $state->to_state_code() ),
						)
					);
				}

				Congress_Admin_Stacked_Input::display_dropdown(
					id: 'congress-filter-state',
					label: 'State',
					name: 'state',
					value: '',
					options: $options
				);

				Congress_Admin_Stacked_Input::display_dropdown(
					id: 'congress-filter-title',
					label: 'Title',
					name: 'title',
					value: '',
					options: array(
						array(
							'value' => '',
							'label' => 'All',
						),
						array(
							'value' => Congress_Title::Senator->to_db_string(),
							'label' => Congress_Title::Senator->to_display_string(),
						),
						array(
							'value' => Congress_Title::Representative->to_db_string(),
							'label' => Congress_Title::Representative->to_display_string(),
						),
					),
				);
				?>
				<button type="submit" class="button button-primary">Filter</button>
			</div>
			<?php
				wp_nonce_field( 'get-reps' );
			?>
			<div style="align-self: stretch; display: flex; align-items: center; padding-top: 1.35em;">
				<span id="congress-filter-hint"></span>
			</div>
		</form>
	</div>

	<div id="congress-rep-actions-header">
		<div>
			<button id="congress-add-rep-button" class="buttton button-primary">Add Representative</button>
		</div>
		<form id="congress-sync-form" action="sync_reps" method="post" style="display: flex; align-items: flex-end;">
			<div class="congress-inline-form-group">
				<span class="congress-stacked-input">
					<label>Sync Representatives:</label>
				</span>
				<?php
				Congress_Admin_Stacked_Input::display_dropdown(
					id: 'congress-sync-level',
					label: 'Level',
					name: 'level',
					value: Congress_Level::Federal->to_db_string(),
					options: array(
						array(
							'value' => '',
							'label' => 'All',
						),
						array(
							'value' => Congress_Level::Federal->to_db_string(),
							'label' => Congress_Level::Federal->to_display_string(),
						),
						array(
							'value' => Congress_Level::State->to_db_string(),
							'label' => Congress_Level::State->to_display_string(),
						),
					),
				);

				$options = array(
					array(
						'value' => '',
						'label' => 'All',
					),
				);

				$states = Congress_State_Settings::get_active_states();

				foreach ( $states as &$state ) {
					array_push(
						$options,
						array(
							'value' => $state->to_db_string(),
							'label' => strtoupper( $state->to_state_code() ),
						)
					);
				}

				Congress_Admin_Stacked_Input::display_dropdown(
					id: 'congress-sync-state',
					label: 'State',
					name: 'state',
					value: '',
					options: $options
				);
				?>
				<button type="submit" id="congress-sync-reps-button" class="buttton button-primary">Sync</button>
			</div>
			<?php
				wp_nonce_field( 'get-reps' );
			?>
			<div style="align-self: stretch; display: flex; align-items: center; padding-top: 1.35em;">
				<span id="congress-sync-reps-hint" class="congress-form-success"></span>
			</div>
		</form>
	</div>
	<div id="congress-reps-container">
		<?php
		$reps = Congress_Admin_Rep::get_reps_from_db( Congress_Level::Federal );
		foreach ( $reps as $rep ) {
			$rep->display();
		}
		?>
	</div>
	<template id="congress-staffer-template">
		<?php
			Congress_Admin_Staffer::get_html_template();
		?>
	</template>
	<template id="congress-rep-template">
		<?php
			Congress_Admin_Rep::get_html_template();
		?>
	</template>
</div>
