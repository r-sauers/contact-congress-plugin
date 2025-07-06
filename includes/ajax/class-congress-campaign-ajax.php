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

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Imports Table Manager for getting table names.
 */
require_once plugin_dir_path( __FILE__ ) .
	'../class-congress-table-manager.php';

/**
 * Imports Congress_Table_Transaction for running transactions.
 */
require_once plugin_dir_path( __FILE__ ) .
	'../class-congress-table-transaction.php';

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

		if ( null === $results || $wpdb->last_error ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log(
				'Contact Congress Failed Database Call: ' .
				$wpdb->last_query . "\n\n" .
				$wpdb->last_error
			);
			wp_send_json(
				array(
					'error' => 'Failed to get campaign names.',
				),
				500
			);
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

		if ( ! check_ajax_referer( 'create-campaign', false, false ) ) {
			wp_send_json(
				array(
					'error' => 'Incorrect Nonce',
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
		$campaign_t        = Congress_Table_Manager::get_table_name( 'campaign' );
		$campaign_state_t  = Congress_Table_Manager::get_table_name( 'campaign_state' );
		$active_campaign_t = Congress_Table_Manager::get_table_name( 'active_campaign' );
		$campaign_id       = -1;

		( new Congress_Table_Transaction() )->query(
			function () use ( $wpdb, $campaign_t, $name ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$main_result = $wpdb->insert(
					$campaign_t,
					array(
						'name' => $name,
					)
				);
			},
			true
		)->query(
			function ( $insert_id ) use ( $wpdb, $campaign_state_t, $region, &$campaign_id ) {
				$campaign_id = $insert_id;

				if ( Congress_Level::Federal !== $region ) {

					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
					return $wpdb->insert(
						$campaign_state_t,
						array(
							'campaign_id' => $campaign_id,
							'state'       => $region->to_db_string(),
						)
					);
				} else {
					return true;
				}
			},
			true
		)->query(
			function () use ( $wpdb, $active_campaign_t, $campaign_id ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				return $wpdb->insert(
					$active_campaign_t,
					array(
						'id' => $campaign_id,
					)
				);
			},
			true
		)->submit(
			error: function ( $query_return_value, $wpdb_error ) {
				if ( 0 === $query_return_value ) {
					wp_send_json(
						array(
							'error' => 'No Change',
						),
						400
					);
				} else {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
					error_log( $wpdb_error );
					wp_send_json(
						array(
							'error' => 'Failed to add campaign!',
						),
						500
					);
				}
			}
		);

		wp_send_json(
			array(
				'id'                => $campaign_id,
				'name'              => $name,
				'region'            => $region->to_db_string(),
				'regionDisplay'     => $region->to_display_string(),
				'editNonce'         => wp_create_nonce( 'update-campaign' ),
				'archiveNonce'      => wp_create_nonce( 'archive-campaign' ),
				'templateLoadNonce' => wp_create_nonce( 'load-templates' ),
			)
		);
	}

	/**
	 * Handles AJAX requests to update a campaign in the table.
	 * Sends a JSON response with the updated campaign.
	 */
	public function update_campaign(): void {

		if ( ! current_user_can( 'congress_manage_campaigns' ) ) {
			wp_send_json(
				array(
					'error' => 'Insufficient Permissions.',
				),
				403
			);
		}

		if ( ! check_ajax_referer( 'update-campaign', false, false ) ) {
			wp_send_json(
				array(
					'error' => 'Incorrect Nonce',
				),
				403
			);
		}

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

		global $wpdb;
		$campaign_t       = Congress_Table_Manager::get_table_name( 'campaign' );
		$campaign_state_t = Congress_Table_Manager::get_table_name( 'campaign_state' );

		( new Congress_Table_Transaction() )->query(
			function () use ( $wpdb, $name, $campaign_id, $campaign_t ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$result = $wpdb->update(
					$campaign_t,
					array(
						'name' => $name,
					),
					array(
						'id' => $campaign_id,
					)
				);
			}
		)->query(
			function () use ( $wpdb, $region, $campaign_state_t, $campaign_id ) {
				if ( Congress_Level::Federal !== $region ) {

					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					return $wpdb->query(
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

				} else {
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					return $wpdb->delete(
						$campaign_state_t,
						array(
							'campaign_id' => $campaign_id,
						)
					);
				}
			}
		)->submit(
			error: function ( $query_return_value, $wpdb_error ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( $wpdb_error );
				wp_send_json(
					array(
						'error' => 'Failed to update campaign!',
					),
					500
				);
			},
			success: function () use ( $campaign_id, $name, $region ) {
				wp_send_json(
					array(
						'id'            => $campaign_id,
						'name'          => $name,
						'region'        => $region->to_db_string(),
						'regionDisplay' => $region->to_display_string(),
					),
				);
			}
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

		if ( ! check_ajax_referer( 'archive-campaign', false, false ) ) {
			wp_send_json(
				array(
					'error' => 'Incorrect Nonce',
				),
				403
			);
			return;
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

		Congress_Table_Manager::archive_campaign( $campaign_id )->submit(
			error: function ( $query_return_value, $wpdb_error ) {
				if ( 0 === $query_return_value ) {
					wp_send_json(
						array(
							'error' => 'No Change',
						),
						400
					);
				} else {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
					error_log( $wpdb_error );
					wp_send_json(
						array(
							'error' => 'Error archiving campaign! ',
						),
						500
					);
				}
			}
		);

		global $wpdb;

		$campaign          = Congress_Table_Manager::get_table_name( 'campaign' );
		$email             = Congress_Table_Manager::get_table_name( 'email' );
		$active_campaign   = Congress_Table_Manager::get_table_name( 'active_campaign' );
		$archived_campaign = Congress_Table_Manager::get_table_name( 'archived_campaign' );
		$campaign_state    = Congress_Table_Manager::get_table_name( 'campaign_state' );

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

		if ( null === $results || $wpdb->last_error ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log(
				'Contact Congress Failed Database Call: ' .
				$wpdb->last_query . "\n\n" .
				$wpdb->last_error
			);
			wp_send_json(
				array(
					'error' => 'Failed to get archived campaign.',
				),
				500
			);
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
				'deleteNonce'  => wp_create_nonce( 'delete-archived-campaign' ),
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

		if ( ! check_ajax_referer( 'delete-archived-campaign', false, false ) ) {
			wp_send_json(
				array(
					'error' => 'Incorrect Nonce',
				),
				403
			);
			return;
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

		global $wpdb;

		Congress_Table_Manager::delete_archived_campaign( $campaign_id )->submit(
			error: function ( $query_return_value, $wpdb_error ) {
				if ( 0 === $query_return_value ) {
					wp_send_json(
						array(
							'error' => 'No Change',
						),
						400
					);
				} else {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
					error_log( $wpdb_error );
					wp_send_json(
						array(
							'error' => 'Failed to delete',
						),
						500
					);
				}
			},
			success: function () {
				wp_send_json(
					array(
						'success' => 'successfully deleted',
					),
				);
			}
		);
	}
}
