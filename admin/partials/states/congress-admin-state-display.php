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
 * A component for stacked inputs in forms.
 */
require_once plugin_dir_path( __FILE__ ) . '../class-congress-admin-stacked-input.php';

/**
 * Utils for flat inputs in forms.
 */
require_once plugin_dir_path( __FILE__ ) . '../class-congress-admin-flat-input.php';

/**
 * Import Congress_State.
 */
require_once plugin_dir_path( __DIR__ ) . '../../includes/enum-congress-state.php';

/**
 * Import Congress_State_Settings.
 */
require_once plugin_dir_path( __FILE__ ) . 'class-congress-state-settings.php';

/**
 */
function congress_draw_state_row( Congress_State $state ) {

	$state_settings       = new Congress_State_Settings( $state );
	$activated            = $state_settings->is_active();
	$state_sync_enabled   = $state_settings->is_state_sync_enabled();
	$federal_sync_enabled = $state_settings->is_federal_sync_enabled();
	$api_supported        = $state_settings->is_api_supported();
	$api_enabled          = $state_settings->is_api_enabled();
	$sync_email           = $state_settings->get_sync_email();

	if (
		is_wp_error( $activated ) ||
		is_wp_error( $state_sync_enabled ) ||
		is_wp_error( $api_enabled ) ||
		is_wp_error( $sync_email ) ||
		is_wp_error( $federal_sync_enabled )
	) {
		wp_die();
	}

	$api_class = '';
	$api_text  = '';
	if ( ! $api_supported ) {
		$api_text  = 'Not Supported!';
		$api_class = 'congress-no-support';
	} elseif ( $api_enabled ) {
		$api_text  = 'Enabled!';
		$api_class = 'congress-enabled';
	} else {
		$api_text  = 'Disabled';
		$api_class = 'congress-disabled';
	}

	?>

	<tr class="congress-state-row">

		<td class="congress-state-row-checkbox">
			<input type="checkbox"/>
		</td>

		<td
			class="congress-state-row-name"
		>
			<?php echo esc_html( $state->to_display_string() ); ?>
		</td>

		<td
			class="<?php echo $activated ? 'congress-activated' : 'congress-deactivated'; ?> congress-state-row-status"
		>
			<?php echo $activated ? 'Activated!' : 'Deactivated'; ?>
		</td>

		<td
			class="<?php echo esc_attr( $api_class ); ?> congress-state-row-api"
		>
			<?php echo esc_html( $api_text ); ?>
		</td>

		<td
			class="<?php echo $federal_sync_enabled ? 'congress-enabled' : 'congress-disabled'; ?> congress-state-row-federal-sync"
		>
			<?php echo $federal_sync_enabled ? 'Enabled!' : 'Disabled'; ?>
		</td>

		<td
			class="<?php echo $state_sync_enabled ? 'congress-enabled' : 'congress-disabled'; ?> congress-state-row-state-sync"
		>
			<?php echo $state_sync_enabled ? 'Enabled!' : 'Disabled'; ?>
		</td>

		<td style="text-align: center;">
			<button class="button button-primary congress-state-row-expand">More
				<span class="congress-icon-plus"></span>
			</button>
		</td>
	</tr>
	<tr class="congress-state-row-expansion">
		<td></td>
		<td colspan="6">
			<h3>Settings</h3>
			<form
				action="set_sync_email"
				class="congress-state-row-sync-form"
			>
				<?php
					wp_nonce_field( 'states-set-sync-email' );
				?>
				<input
					type="hidden"
					name="stateCode"
					value="<?php echo esc_attr( strtolower( $state->to_state_code() ) ); ?>"
				/>
				<div style="display: flex; align-items: center; gap: 1em;">
					<div class="congress-inline-form-group">
						<?php
							Congress_Admin_Stacked_Input::display_email(
								id: 'congress-state-' . strtolower( $state->to_state_code() ) . '-sync-email',
								label: 'Sync Alert Email',
								name: 'email',
								value: $sync_email,
								placeholder: 'policy@gmail.com',
							);
						?>
						<div>
							<button type="submit" class="button button-primary">Update</button>
						</div>
					</div>
					<span style="flex-shrink: 1;">
						Whenever syncing makes changes to the representatives,
						this email will be notified so staffers can be updated.
					</span>
				</div>
				<p class="congress-sync-email-hint"></p>
			</form>
		</td>
	</tr>
	<?php
}

?>
<div>
	<h1>State Settings</h1>

	<div style="display: flex; align-items: center; gap: 1em; margin-block: 1em;">
		<label
			for="congress-state-search"
			style="font-size: 1.4em;"
		>Search Rows: </label>
		<input id="congress-state-search" type="text"/>
	</div>

	<div id="congress-bulk-action-table-wrapper">
		<table id="congress-bulk-action-table">
			<colgroup>
				<col width="1em;"/>
			</colgroup>
			<thead>
				<tr>
					<th class="congress-header-checkbox">
						<div>
							<input type="checkbox"/>
						</div>
					</th>
					<th class="congress-header-state-name congress-sortable-header">
						<div class="congress-sort-toggle">
							<button class="congress-no-button">State Name</button>
						</div>
					</th>
					<th class="congress-header-status congress-sortable-header">
						<div class="congress-sort-toggle">
							<button
								class="congress-no-button"
							>Activation Status 
								<sup><a href="#congress-notes-activation-status">1</a></sup>
							</button>
						</div>
					</th>
					<th class="congress-header-api congress-sortable-header">
						<div class="congress-sort-toggle">
							<button
								class="congress-no-button"
							>API
								<sup><a href="#congress-notes-api">2</a></sup>
							</button>
						</div>
					</th>
					<th class="congress-header-federal-sync congress-sortable-header">
						<div class="congress-sort-toggle">
							<button
								class="congress-no-button"
							>Federal Sync
								<sup><a href="#congress-notes-federal-sync">3</a></sup>
							</button>
						</div>
					</th>
					<th class="congress-header-state-sync congress-sortable-header">
						<div class="congress-sort-toggle">
							<button
								class="congress-no-button"
							>State Sync
								<sup><a href="#congress-notes-state-sync">4</a></sup>
							</button>
						</div>
					</th>
					<th><div></div></th>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach ( Congress_State::cases() as $state ) {
					congress_draw_state_row( $state );
				}
				?>
			</tbody>
			<tfoot>
				<tr>
					<td colspan="7">
						<div>
						<form id="congress-bulk-action-form" method="post">
							<?php
								wp_nonce_field( "states-bulk-operation" );
							?>
							<div class="congress-inline-form-group">
								<?php
								Congress_Admin_Flat_Input::display_dropdown(
									id: 'congress-bulk-action-dropdown',
									label: 'Bulk Action',
									name: 'action',
									value: 'none',
									options: array(
										array(
											'value' => 'none',
											'label' => 'None',
										),
										array(
											'value' => 'activate_states',
											'label' => 'Activate States',
										),
										array(
											'value' => 'deactivate_states',
											'label' => 'Deactivate States',
										),
										array(
											'value' => 'enable_federal_sync',
											'label' => 'Enable Federal-Level Syncing',
										),
										array(
											'value' => 'disable_federal_sync',
											'label' => 'Disable Federal-Level Syncing',
										),
										array(
											'value' => 'enable_state_sync',
											'label' => 'Enable State-Level Syncing',
										),
										array(
											'value' => 'disable_state_sync',
											'label' => 'Disable State-Level Syncing',
										),
									)
								);
								?>
								<div style="display: flex; align-items: center; gap: 1em;">
									<button
										type="submit"
										id="congrss-bulk-action button"
										class="button button-primary"
									>Perform Action</button>
									<span
										id="congress-bulk-action-results"
										class="congress-form-success"
									></span>
								</div>
							</div>
						</form>
						</div>
					</td>
				</tr>
			</tfoot>
		</table>
	</div>
	<h3>Notes:</h3>
	<ol>
		<li id="congress-notes-activation-status"><strong>Activation Status</strong> determines whether or not the state is used across the plugin. 
		The main purpose is to reduce the size of state dropdowns.
		You can use the "Activate States" and "Deactivate States" bulk actions to edit the status.
		Deactivating a state will delete its representatives.</li>
		<li id="congress-notes-api"><strong>API</strong> is used to find a reader's representatives, and sync representatives.
		If this field states 'Not Supported', please contact the plugin development team to see if it can be added.</li>
		<li id="congress-notes-federal-sync"><strong>Federal Sync</strong> will use the API to update the federal-level representatives for the given state every day.
		If any representative details change, a notification will be sent to the email you specify in the state dropdown.
		Use the 'Enable Federal-Level Syncing' and 'Disable Federal-Level Syncing' bulk actions to enable/disable.</li>
		<li id="congress-notes-state-sync"><strong>State Sync</strong> is similary to <strong>Federal Sync</strong>, but for local state-level representatives.</li>
	</ol>
</div>
