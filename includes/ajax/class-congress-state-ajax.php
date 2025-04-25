<?php
/**
 * A collection of AJAX handlers for state settings.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Congress
 * @subpackage Congress/includes
 */

/**
 * Imports Congress_State_Settings to manage state settings.
 */
require_once plugin_dir_path( __DIR__ ) .
	'../admin/partials/states/class-congress-state-settings.php';

/**
 * Imports Congress_AJAX_Collection interface.
 */
require_once plugin_dir_path( __FILE__ ) .
	'class-congress-ajax-collection.php';

/**
 * Imports Congress_AJAX_Handler for creating handlers.
 */
require_once plugin_dir_path( __FILE__ ) .
	'class-congress-ajax-handler.php';

/**
 * Imports Congress_State enum.
 */
require_once plugin_dir_path( __DIR__ ) .
	'enum-congress-state.php';

/**
 * Imports Table Manager for creating tables.
 */
require_once plugin_dir_path( __DIR__ ) .
	'class-congress-table-manager.php';

/**
 * A collection of AJAX handlers for state settings.
 *
 * @since      1.0.0
 * @package    Congress
 * @subpackage Congress/includes
 * @author     Ryan Sauers <ryan.sauers@exploreveg.org>
 */
class Congress_State_AJAX implements Congress_AJAX_Collection {

	/**
	 * Returns a list of ajax handlers for admin page.
	 *
	 * @return array<Congress_AJAX_Handler>
	 */
	public function get_admin_handlers(): array {
		return array(
			new Congress_AJAX_Handler(
				callee: $this,
				func_name: 'activate_states',
				ajax_name: 'activate_states'
			),
			new Congress_AJAX_Handler(
				callee: $this,
				func_name: 'deactivate_states',
				ajax_name: 'deactivate_states'
			),
			new Congress_AJAX_Handler(
				callee: $this,
				func_name: 'enable_state_sync',
				ajax_name: 'enable_state_sync'
			),
			new Congress_AJAX_Handler(
				callee: $this,
				func_name: 'disable_state_sync',
				ajax_name: 'disable_state_sync'
			),
			new Congress_AJAX_Handler(
				callee: $this,
				func_name: 'enable_federal_sync',
				ajax_name: 'enable_federal_sync'
			),
			new Congress_AJAX_Handler(
				callee: $this,
				func_name: 'disable_federal_sync',
				ajax_name: 'disable_federal_sync'
			),
			new Congress_AJAX_Handler(
				callee: $this,
				func_name: 'enable_state_api',
				ajax_name: 'enable_state_api'
			),
			new Congress_AJAX_Handler(
				callee: $this,
				func_name: 'disable_state_api',
				ajax_name: 'disable_state_api'
			),
			new Congress_AJAX_Handler(
				callee: $this,
				func_name: 'set_sync_email',
				ajax_name: 'set_sync_email'
			),
			new Congress_AJAX_Handler(
				callee: $this,
				func_name: 'set_default_sync_email',
				ajax_name: 'set_default_sync_email'
			),
		);
	}

	/**
	 * Returns a list of ajax handlers for public page.
	 *
	 * @return array<Congress_AJAX_Handler>
	 */
	public function get_public_handlers(): array {
		return array();
	}

	/**
	 * Helper function to handle bulk operations.
	 *
	 * Handles AJAX requests with the field 'states'.
	 * 'states' should comma separated state codes e.g. 'MN,WI,WA'.
	 *
	 * Sends a JSON response with the states successfully operated on.
	 *
	 * @param callable $callback is called with an argument of the
	 * form Congress_State_Settings and should return a WP_Error on failure.
	 */
	private function handle_bulk_operation( callable $callback ): void {

		if ( ! current_user_can( 'congress_manage_states' ) ) {
			wp_send_json(
				array(
					'error' => 'Insufficient Permissions.',
				),
				403
			);
		}

		if (
			! isset( $_POST['states'] )
		) {
			wp_send_json(
				array(
					'error' => 'Missing parameters',
				),
				400
			);
		}

		if ( ! check_ajax_referer( 'states-bulk-operation', false, false ) ) {
			wp_send_json(
				array(
					'error' => 'Incorrect Nonce',
				),
				403
			);
		}

		$sanitized_states_str = sanitize_text_field( wp_unslash( $_POST['states'] ) );
		$state_codes          = explode( ',', $sanitized_states_str );
		$states               = array();

		foreach ( $state_codes as $state_code ) {
			try {
				$state = Congress_State::from_string( $state_code );
			} catch ( Exception $e ) {
				continue;
			}

			$settings = new Congress_State_Settings( $state );

			$res = $callback( $settings );

			if ( is_wp_error( $res ) ) {
				continue;
			}

			array_push( $states, $state_code );
		}

		wp_send_json( $states, 200 );
	}

	/**
	 * Performs a bulk operation activating each state for use across the plugin.
	 *
	 * @see handle_bulk_operation for more details.
	 */
	public function activate_states(): void {
		$this->handle_bulk_operation(
			function ( Congress_State_Settings &$setting ) {
				return $setting->activate();
			}
		);
	}

	/**
	 * Performs a bulk operation deactivating each state for use across the plugin.
	 *
	 * Removes representatives from the state, and archives campaigns from the state.
	 *
	 * @see handle_bulk_operation for more details.
	 */
	public function deactivate_states(): void {
		$this->handle_bulk_operation(
			function ( Congress_State_Settings &$setting ) {
				$rep_t = Congress_Table_Manager::get_table_name( 'representative' );
				global $wpdb;

				$state   = $setting->get_state();
				$del_res = $wpdb->delete( // phpcs:ignore
					$rep_t,
					array(
						'state' => $state->to_db_string(),
					)
				);

				if ( false === $del_res ) {
					return new WP_Error( 'DB_ERROR', 'Failed to remove representatives.' );
				}

				global $wpdb;

				$email             = Congress_Table_Manager::get_table_name( 'email' );
				$active_campaign   = Congress_Table_Manager::get_table_name( 'active_campaign' );
				$archived_campaign = Congress_Table_Manager::get_table_name( 'archived_campaign' );
				$campaign_state    = Congress_Table_Manager::get_table_name( 'campaign_state' );

				$wpdb->query( 'START TRANSACTION' ); // phpcs:ignore

				// phpcs:ignore
				$result = $wpdb->query(
					$wpdb->prepare(
						"INSERT INTO $archived_campaign (id, email_count) " . // phpcs:ignore
						"SELECT $active_campaign.id, COUNT($email.id) FROM $active_campaign " . // phpcs:ignore
						"LEFT JOIN $email ON $email.campaign_id = $active_campaign.id " . // phpcs:ignore
						"WHERE $active_campaign.id IN (" . // phpcs:ignore
							"SELECT $campaign_state.campaign_id as id FROM $campaign_state " . // phpcs:ignore
							"WHERE $campaign_state.state=%s" . // phpcs:ignore
						') ' .
						"GROUP BY $active_campaign.id", // phpcs:ignore
						array(
							$state->to_db_string(),
						),
					)
				);

				if ( false === $result ) {
					$wpdb->query( 'ROLLBACK' ); // phpcs:ignore
					return new WP_Error( 'DB_ERROR', 'Failed to create archived campaign rows!' );
				}

				// phpcs:ignore
				$result = $wpdb->query(
					$wpdb->prepare(
						"DELETE FROM $active_campaign " . // phpcs:ignore
						"WHERE $active_campaign.id IN (" . // phpcs:ignore
							"SELECT $campaign_state.campaign_id as id FROM $campaign_state " . // phpcs:ignore
							"WHERE $campaign_state.state=%s" . // phpcs:ignore
						')',
						array(
							$state->to_db_string(),
						),
					)
				);

				if ( false === $result ) {
					$wpdb->query( 'ROLLBACK' ); // phpcs:ignore
					return new WP_Error( 'DB_ERROR', 'Failed to remove active campaign rows!' );
				}

				$wpdb->query( 'COMMIT' ); // phpcs:ignore

				return $setting->deactivate();
			}
		);
	}

	/**
	 * Performs a bulk operation enabling API use for each state.
	 *
	 * @see handle_bulk_operation for more details.
	 */
	public function enable_state_api(): void {
		$this->handle_bulk_operation(
			function ( Congress_State_Settings &$setting ) {
				return $setting->enable_api();
			}
		);
	}

	/**
	 * Performs a bulk operation disabling API use for each state.
	 *
	 * @see handle_bulk_operation for more details.
	 */
	public function disable_state_api(): void {
		$this->handle_bulk_operation(
			function ( Congress_State_Settings &$setting ) {
				return $setting->disable_api();
			}
		);
	}

	/**
	 * Performs a bulk operation enabling state-level sync use for each state.
	 *
	 * @see handle_bulk_operation for more details.
	 */
	public function enable_state_sync(): void {
		$this->handle_bulk_operation(
			function ( Congress_State_Settings &$setting ) {
				return $setting->enable_state_sync();
			}
		);
	}

	/**
	 * Performs a bulk operation disabling state-level sync use for each state.
	 *
	 * @see handle_bulk_operation for more details.
	 */
	public function disable_state_sync(): void {
		$this->handle_bulk_operation(
			function ( Congress_State_Settings &$setting ) {
				return $setting->disable_state_sync();
			}
		);
	}

	/**
	 * Performs a bulk operation enabling federal-level sync use for each state.
	 *
	 * @see handle_bulk_operation for more details.
	 */
	public function enable_federal_sync(): void {
		$this->handle_bulk_operation(
			function ( Congress_State_Settings &$setting ) {
				return $setting->enable_federal_sync();
			}
		);
	}

	/**
	 * Performs a bulk operation disabling federal-level sync use for each state.
	 *
	 * @see handle_bulk_operation for more details.
	 */
	public function disable_federal_sync(): void {
		$this->handle_bulk_operation(
			function ( Congress_State_Settings &$setting ) {
				return $setting->disable_federal_sync();
			}
		);
	}

	/**
	 * Handles AJAX requests to set a state's sync alert email.
	 * Requires the fields:
	 * - 'state': a state code e.g. 'MN'
	 * - 'email': the email to alert.
	 *
	 * Sends a response with the updated email.
	 */
	public function set_sync_email(): void {
		if ( ! current_user_can( 'congress_manage_states' ) ) {
			wp_send_json(
				array(
					'error' => 'Insufficient Permissions.',
				),
				403
			);
		}

		if (
			! isset( $_POST['state'] ) ||
			! isset( $_POST['email'] )
		) {
			wp_send_json(
				array(
					'error' => 'Missing parameters',
				),
				400
			);
		}

		if ( ! check_ajax_referer( 'states-set-sync-email', false, false ) ) {
			wp_send_json(
				array(
					'error' => 'Incorrect Nonce',
				),
				403
			);
		}

		$sanitized_state_str = sanitize_text_field(
			wp_unslash(
				$_POST['state']
			)
		);

		$email = sanitize_text_field(
			wp_unslash(
				$_POST['email']
			)
		);

		try {
			$state = Congress_State::from_string( $sanitized_state_str );
		} catch ( Exception $e ) {
			wp_send_json(
				array(
					'Failed to parse state.',
				),
				400
			);
		}

		$settings = new Congress_State_Settings( $state );

		$res = $settings->set_sync_email( $email );

		if ( is_wp_error( $res ) ) {
			wp_send_json(
				array(
					'error' => $res->get_error_message(),
				),
				500
			);
		}

		wp_send_json( $email );
	}

	/**
	 * Handles AJAX requests to set the default sync alert email.
	 * Requires the fields:
	 * - 'email': the email to alert.
	 *
	 * Sends a response with the updated email.
	 */
	public function set_default_sync_email(): void {
		if ( ! current_user_can( 'congress_manage_states' ) ) {
			wp_send_json(
				array(
					'error' => 'Insufficient Permissions.',
				),
				403
			);
		}

		if (
			! isset( $_POST['email'] )
		) {
			wp_send_json(
				array(
					'error' => 'Missing parameters',
				),
				400
			);
		}

		if ( ! check_ajax_referer( 'states-set-default-sync-email', false, false ) ) {
			wp_send_json(
				array(
					'error' => 'Incorrect Nonce',
				),
				403
			);
		}

		$email = sanitize_text_field(
			wp_unslash(
				$_POST['email']
			)
		);

		$res = Congress_State_Settings::set_default_sync_email( $email );

		if ( is_wp_error( $res ) ) {
			wp_send_json(
				array(
					'error' => $res->get_error_message(),
				),
				500
			);
		}

		wp_send_json( $email );
	}
}
