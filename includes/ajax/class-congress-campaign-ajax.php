<?php
/**
 * A collection of AJAX handlers for campaigns.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Congress
 * @subpackage Congress/includes
 */

/**
 * Imports Table Manager for getting table names;
 */
require_once plugin_dir_path( __FILE__ ) .
	'../class-congress-table-manager.php';

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
 * Import enums.
 */
require_once plugin_dir_path( __DIR__ ) .
	'enum-congress-state.php';
require_once plugin_dir_path( __DIR__ ) .
	'enum-congress-level.php';

/**
 * A collection of AJAX handlers for campaigns.
 *
 * @since      1.0.0
 * @package    Congress
 * @subpackage Congress/includes
 * @author     Ryan Sauers <ryan.sauers@exploreveg.org>
 */
class Congress_Campaign_AJAX implements Congress_AJAX_Collection {

	/**
	 * Returns a list of ajax handlers for admin page.
	 *
	 * @return array<Congress_AJAX_Handler>
	 */
	public function get_admin_handlers(): array {
		return array(
			new Congress_AJAX_Handler(
				callee: $this,
				func_name: 'insert_campaign',
				ajax_name: 'add_campaign'
			),
			new Congress_AJAX_Handler(
				callee: $this,
				func_name: 'update_campaign',
				ajax_name: 'update_campaign'
			),
			new Congress_AJAX_Handler(
				callee: $this,
				func_name: 'archive_campaign',
				ajax_name: 'archive_campaign'
			),
			new Congress_AJAX_Handler(
				callee: $this,
				func_name: 'delete_archived_campaign',
				ajax_name: 'delete_archived_campaign'
			),
			new Congress_AJAX_Handler(
				callee: $this,
				func_name: 'get_campaign_names',
				ajax_name: 'get_campaign_names'
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
	 * Handles ajax request to get campaign names.
	 *
	 * Returns an array of objects with the campaign id and name.
	 */
	public function get_campaign_names(): void {

		$campaign_t = Congress_Table_Manager::get_table_name( 'campaign' );
		$active_t   = Congress_Table_Manager::get_table_name( 'active_campaign' );

		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT camp.id, name ' .
				'FROM %i AS active ' .
				'LEFT JOIN %i AS camp ON active.id = camp.id',
				array(
					$active_t,
					$campaign_t,
				)
			)
		);

		if ( false === $results ) {
			wp_send_json(
				array(
					'error' => $wpdb->last_error,
				),
				500
			);
			return;
		}

		wp_send_json( $results );
	}

	/**
	 * Handles AJAX requests to add campaigns to the table.
	 * Sends a JSON response with the campaign data and nonces.
	 */
	public function insert_campaign(): void {

		if ( ! current_user_can( 'congress_manage_campaigns' ) ) {
			wp_send_json(
				array(
					'error' => 'Insufficient Permissions.',
				),
				403
			);
		}

		if (
			! isset( $_POST['name'] ) ||
			! isset( $_POST['region'] )
		) {
			wp_send_json(
				array(
					'error' => 'Missing parameters',
				),
				400
			);
		}

		if ( ! check_ajax_referer( 'create-campaign', false, false ) ) {
			wp_send_json(
				array(
					'error' => 'Incorrect Nonce',
				),
				403
			);
		}

		$name   = sanitize_text_field(
			wp_unslash( $_POST['name'] ),
		);
		$region = sanitize_text_field(
			wp_unslash( $_POST['region'] ),
		);

		try {
			$region = Congress_Level::from_string( $region );
		} catch ( Error $e ) {
			try {
				$region = Congress_State::from_string( $region );
			} catch ( Error $e ) {
				wp_send_json(
					array(
						'error' => 'Invalid parameters',
					),
					400
				);
			}
		}

		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( 'START TRANSACTION' );

		$campaign_table = Congress_Table_Manager::get_table_name( 'campaign' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$main_result = $wpdb->insert(
			$campaign_table,
			array(
				'name' => $name,
			)
		);

		if ( false === $main_result ) {

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query( 'ROLLBACK' );
			wp_send_json(
				array(
					'error' => $wpdb->last_error,
				),
				500
			);
		}

		if ( 0 === $main_result ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query( 'ROLLBACK' );
			wp_send_json(
				array(
					'error' => 'Malformed request.',
				),
				400
			);
		}

		$campaign_id = $wpdb->insert_id;

		if ( Congress_Level::Federal !== $region ) {
			$campaign_state_t = Congress_Table_Manager::get_table_name( 'campaign_state' );

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$state_res = $wpdb->insert(
				$campaign_state_t,
				array(
					'campaign_id' => $campaign_id,
					'state'       => $region->to_db_string(),
				)
			);

			if ( false === $state_res ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->query( 'ROLLBACK' );
				wp_send_json(
					array(
						'error' => $wpdb->last_error,
					),
					500
				);
			}

			if ( 0 === $state_res ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->query( 'ROLLBACK' );
				wp_send_json(
					array(
						'error' => 'Malformed request.',
					),
					400
				);
			}
		}

		$active_campaign_table = Congress_Table_Manager::get_table_name( 'active_campaign' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$active_result = $wpdb->insert(
			$active_campaign_table,
			array(
				'id' => $campaign_id,
			)
		);

		if ( false === $active_result ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query( 'ROLLBACK' );
			wp_send_json(
				array(
					'error' => $wpdb->last_error,
				),
				500
			);
		}

		if ( 0 === $active_result ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query( 'ROLLBACK' );
			wp_send_json(
				array(
					'error' => 'Malformed request.',
				),
				400
			);
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( 'COMMIT' );

		wp_send_json(
			array(
				'id'                => $campaign_id,
				'name'              => $name,
				'region'            => $region->to_db_string(),
				'regionDisplay'     => $region->to_display_string(),
				'editNonce'         => wp_create_nonce( "update-campaign_$campaign_id" ),
				'archiveNonce'      => wp_create_nonce( "archive-campaign_$campaign_id" ),
				'templateLoadNonce' => wp_create_nonce( "load-templates_$campaign_id" ),
			)
		);
	}

	/**
	 * Handles AJAX requests to update a campaign in the table.
	 * Sends a JSON response with the updated campaign.
	 */
	public function update_campaign(): void {

		if (
			! isset( $_POST['id'] ) ||
			! isset( $_POST['name'] ) ||
			! isset( $_POST['region'] )
		) {
			wp_send_json(
				array(
					'error' => 'Missing parameters',
				),
				400
			);
		}

		$campaign_id = sanitize_text_field(
			wp_unslash( $_POST['id'] )
		);
		$name        = sanitize_text_field(
			wp_unslash( $_POST['name'] )
		);
		$region      = sanitize_text_field(
			wp_unslash( $_POST['region'] )
		);

		try {
			$region = Congress_Level::from_string( $region );
		} catch ( Error $e ) {
			try {
				$region = Congress_State::from_string( $region );
			} catch ( Error $e ) {
				wp_send_json(
					array(
						'error' => 'Invalid parameters',
					),
					400
				);
			}
		}

		if ( ! check_ajax_referer( "update-campaign_$campaign_id", false, false ) ) {
			wp_send_json(
				array(
					'error' => 'Incorrect Nonce',
				),
				403
			);
		}

		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( 'START TRANSACTION' );

		$tablename = Congress_Table_Manager::get_table_name( 'campaign' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->update(
			$tablename,
			array(
				'name' => $name,
			),
			array(
				'id' => $campaign_id,
			)
		);

		if ( false === $result ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query( 'ROLLBACK' );
			wp_send_json(
				array(
					'error' => $wpdb->last_error,
				),
				500
			);
		}

		$campaign_state_t = Congress_Table_Manager::get_table_name( 'campaign_state' );
		if ( Congress_Level::Federal !== $region ) {

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$state_res = $wpdb->query(
				$wpdb->prepare(
					'INSERT INTO %i AS state (campaign_id, state) ' .
					'VALUES (%d, %s) ' .
					'ON DUPLICATE KEY UPDATE campaign_id=%d',
					array(
						$campaign_state_t,
						$campaign_id,
						$region->to_db_string(),
						$campaign_id,
					)
				)
			);

			if ( false === $state_res ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->query( 'ROLLBACK' );
				wp_send_json(
					array(
						'error' => $wpdb->last_error,
					),
					500
				);
			}
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$delete_res = $wpdb->delete(
				$campaign_state_t,
				array(
					'campaign_id' => $campaign_id,
				)
			);

			if ( false === $delete_res ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->query( 'ROLLBACK' );
				wp_send_json(
					array(
						'error' => $wpdb->last_error,
					),
					500
				);
			}
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( 'COMMIT' );

		wp_send_json(
			array(
				'id'            => $campaign_id,
				'name'          => $name,
				'region'        => $region->to_db_string(),
				'regionDisplay' => $region->to_display_string(),
			),
		);
	}

	/**
	 * Handles AJAX requests to archive a campaign in the table.
	 * Sends a JSON response with the archived date.
	 */
	public function archive_campaign(): void {

		if ( ! current_user_can( 'congress_manage_campaigns' ) ) {
			wp_send_json(
				array(
					'error' => 'Insufficient Permissions.',
				),
				403
			);
		}

		if (
			! isset( $_POST['id'] )
		) {
			wp_send_json(
				array(
					'error' => 'Missing parameters',
				),
				400
			);
			return;
		}

		$campaign_id = sanitize_text_field(
			wp_unslash( $_POST['id'] )
		);

		if ( ! check_ajax_referer( "archive-campaign_$campaign_id", false, false ) ) {
			wp_send_json(
				array(
					'error' => 'Incorrect Nonce',
				),
				403
			);
			return;
		}

		global $wpdb;

		$campaign          = Congress_Table_Manager::get_table_name( 'campaign' );
		$email             = Congress_Table_Manager::get_table_name( 'email' );
		$active_campaign   = Congress_Table_Manager::get_table_name( 'active_campaign' );
		$archived_campaign = Congress_Table_Manager::get_table_name( 'archived_campaign' );
		$campaign_state    = Congress_Table_Manager::get_table_name( 'campaign_state' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( 'START TRANSACTION' );

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->query(
			$wpdb->prepare(
				'INSERT INTO %i AS arch (id, email_count) ' .
				'SELECT camp.id, COUNT(email.campaign_id) FROM %i AS camp ' .
				'LEFT JOIN %i AS email ON email.campaign_id = camp.id ' .
				'WHERE camp.id = %d',
				array(
					$archived_campaign,
					$campaign,
					$email,
					$campaign_id,
				),
			)
		);

		if ( false === $result ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query( 'ROLLBACK' );
			wp_send_json(
				array(
					'error' => $wpdb->last_error,
				),
				500
			);
			return;
		}

		if ( 0 === $result ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query( 'ROLLBACK' );
			wp_send_json(
				array(
					'error' => 'No Change',
				),
				400
			);
			return;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->delete(
			$active_campaign,
			array(
				'id' => $campaign_id,
			),
			'%d',
		);

		if ( false === $result ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query( 'ROLLBACK' );
			wp_send_json(
				array(
					'error' => $wpdb->last_error,
				),
				500
			);
			return;
		}

		if ( 0 === $result ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query( 'ROLLBACK' );
			wp_send_json(
				array(
					'error' => 'No Change',
				),
				400
			);
			return;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( 'COMMIT' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT camp.id, name, ifnull( state, 'FEDERAL' ) as region, email_count, archived_date, created_date " .
				'FROM %i AS arch' .
				'INNER JOIN %i AS camp ON arch.id = camp.id ' .
				'LEFT JOIN %i AS state ON state.campaign_id = camp.id' .
				'WHERE camp.id = %d',
				array(
					$archived_campaign,
					$campaign,
					$campaign_state,
					$campaign_id,
				)
			),
		);

		if ( false === $results ) {
			wp_send_json(
				array(
					'error' => $wpdb->last_error,
				),
				500
			);
			return;
		}

		if ( 0 === count( $results ) ) {
			wp_send_json(
				array(
					'error' => "Campaign doesn't exist",
				),
				500
			);
			return;
		}

		$result = $results[0];

		wp_send_json(
			array(
				'id'           => $result->id,
				'name'         => $result->name,
				'region'       => $result->region,
				'emailCount'   => $result->email_count,
				'archivedDate' => $result->archived_date,
				'createdDate'  => $result->created_date,
				'deleteNonce'  => wp_create_nonce( "delete-archived-campaign_$campaign_id" ),
			),
		);
	}

	/**
	 * Handles AJAX requests to delete a campaign in the table.
	 * Sends a JSON response with a success message.
	 */
	public function delete_archived_campaign(): void {

		if ( ! current_user_can( 'congress_manage_campaigns' ) ) {
			wp_send_json(
				array(
					'error' => 'Insufficient Permissions.',
				),
				403
			);
		}

		if (
			! isset( $_POST['id'] )
		) {
			wp_send_json(
				array(
					'error' => 'Missing parameters',
				),
				400
			);
			return;
		}

		$campaign_id = sanitize_text_field(
			wp_unslash( $_POST['id'] )
		);

		if ( ! check_ajax_referer( "delete-archived-campaign_$campaign_id", false, false ) ) {
			wp_send_json(
				array(
					'error' => 'Incorrect Nonce',
				),
				403
			);
			return;
		}

		global $wpdb;

		$campaign = Congress_Table_Manager::get_table_name( 'campaign' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->delete(
			$campaign,
			array(
				'id' => $campaign_id,
			),
		);

		if ( false === $result ) {
			wp_send_json(
				array(
					'error' => $wpdb->last_error,
				),
				500
			);
			return;
		}

		if ( 0 === $result ) {
			wp_send_json(
				array(
					'error' => 'No Change',
				),
				400
			);
			return;
		}

		wp_send_json(
			array(
				'success' => 'successfully deleted',
			),
		);
	}
}
