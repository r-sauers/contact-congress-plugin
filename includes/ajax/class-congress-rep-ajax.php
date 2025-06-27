<?php
/**
 * A collection of AJAX handlers for representatives.
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
 * Imports Congress_Rep_Sync.
 */
require_once plugin_dir_path( __DIR__ ) .
	'class-congress-rep-sync.php';

/**
 * Imports Congress_State enum.
 */
require_once plugin_dir_path( __DIR__ ) .
	'enum-congress-state.php';

/**
 * Imports Congress_Level enum.
 */
require_once plugin_dir_path( __DIR__ ) .
	'enum-congress-level.php';

/**
 * A collection of AJAX handlers for representatives.
 *
 * @since      1.0.0
 * @package    Congress
 * @subpackage Congress/includes
 * @author     Ryan Sauers <ryan.sauers@exploreveg.org>
 */
class Congress_Rep_AJAX implements Congress_AJAX_Collection {

	/**
	 * Returns a list of ajax handlers for admin page.
	 *
	 * @return array<Congress_AJAX_Handler>
	 */
	public function get_admin_handlers(): array {
		return array(
			new Congress_AJAX_Handler(
				callee: $this,
				func_name: 'get_reps',
				ajax_name: 'get_representatives'
			),
			new Congress_AJAX_Handler(
				callee: $this,
				func_name: 'insert_rep',
				ajax_name: 'add_representative'
			),
			new Congress_AJAX_Handler(
				callee: $this,
				func_name: 'delete_rep',
				ajax_name: 'delete_representative'
			),
			new Congress_AJAX_Handler(
				callee: $this,
				func_name: 'update_rep',
				ajax_name: 'update_representative'
			),
			new Congress_AJAX_Handler(
				callee: $this,
				func_name: 'sync_reps',
				ajax_name: 'sync_reps'
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
	 * Returns a JSON response with the representatives in the database.
	 *
	 * May take arguments for 'state', 'level', and 'title' to filter results.
	 */
	public function get_reps(): void {

		if ( ! current_user_can( 'congress_manage_representatives' ) ) {
			wp_send_json(
				array(
					'error' => 'Insufficient Permissions.',
				),
				403
			);
		}

		if ( ! check_ajax_referer( 'get-reps', false, false ) ) {
			wp_send_json(
				array(
					'error' => 'Incorrect Nonce',
				),
				403
			);
		}

		$state = null;
		if ( isset( $_GET['state'] ) ) {
			try {
				$state = Congress_State::from_string(
					sanitize_text_field( wp_unslash( $_GET['state'] ) )
				);
			} catch ( Error $e ) {
				wp_send_json(
					array(
						'error' => 'Invalid state parameter.',
					),
					400
				);
			}
		}

		$level = null;
		if ( isset( $_GET['level'] ) ) {
			try {
				$level = Congress_Level::from_string(
					sanitize_text_field( wp_unslash( $_GET['level'] ) )
				);
			} catch ( Error $e ) {
				wp_send_json(
					array(
						'error' => 'Invalid level parameter.',
					),
					400
				);
			}
		}

		$title = null;
		if ( isset( $_GET['title'] ) ) {
			try {
				$title = Congress_Title::from_string(
					sanitize_text_field( wp_unslash( $_GET['title'] ) )
				);
			} catch ( Error $e ) {
				wp_send_json(
					array(
						'error' => 'Invalid title parameter.',
					),
					400
				);
			}
		}

		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT ' .
					'r.id         AS rep_id, ' .
					'r.title      AS rep_title, ' .
					'r.first_name AS rep_first, ' .
					'r.last_name  AS rep_last, ' .
					'r.state      AS rep_state, ' .
					'r.district   AS rep_district, ' .
					'r.level,     AS rep_level' .
					's.id         AS staffer_id, ' .
					's.title      AS staffer_title, ' .
					's.first_name AS staffer_first, ' .
					's.last_name  AS staffer_last, ' .
					's.email      AS staffer_email' .
				'FROM %i AS r' .
				'LEFT JOIN %i AS s ON r.id = s.representative ' .
				'WHERE ' .
					'(0=%d OR r.state=%s) AND ' .
					'(0=%d OR r.level=%s) AND ' .
					'(0=%d OR r.title=%s)',
				array(
					Congress_Table_Manager::get_table_name( 'representative' ),
					Congress_Table_Manager::get_table_name( 'staffer' ),
					$state ? 1 : 0,
					$state || '',
					$level ? 1 : 0,
					$level || '',
					$title ? 1 : 0,
					$title || '',
				)
			)
		);

		if ( null === $result ) {
			wp_send_json(
				array(
					'error' => 'Failed to get representatives.',
				),
				500
			);
		}

		$reps = Congress_Rep_Interface::from_db_result( $result, true );
		$json = $reps->to_json();
		$json = array_map(
			function ( $rep ) {
				$rep['editNonce']   = wp_create_nonce( 'edit-rep' );
				$rep['deleteNonce'] = wp_create_nonce( 'delete-rep' );
				$rep['createNonce'] = wp_create_nonce( 'create-staffer' );
				$rep['staffers']    = array_map(
					function ( $staffer ) use ( $rep ) {
						$staffer['editNonce']   = wp_create_nonce( 'edit-staffer' );
						$staffer['deleteNonce'] = wp_create_nonce( 'create-staffer' );
						return $staffer;
					},
					rep['staffers']
				);
				return $rep;
			},
			$json
		);

		wp_send_json( $json );
	}

	/**
	 * Handles AJAX requests to add representatives to the table.
	 * Sends a JSON response with the id and nonces.
	 */
	public function insert_rep(): void {

		if ( ! current_user_can( 'congress_manage_representatives' ) ) {
			wp_send_json(
				array(
					'error' => 'Insufficient Permissions.',
				),
				403
			);
		}

		if ( ! check_ajax_referer( 'create-rep', false, false ) ) {
			wp_send_json(
				array(
					'error' => 'Incorrect Nonce',
				),
				403
			);
		}

		if (
			! isset( $_POST['title'] ) ||
			! isset( $_POST['state'] ) ||
			! isset( $_POST['district'] ) ||
			! isset( $_POST['first_name'] ) ||
			! isset( $_POST['last_name'] ) ||
			! isset( $_POST['level'] )
		) {
			wp_send_json(
				array(
					'error' => 'Missing parameters',
				),
				400
			);
		}

		global $wpdb;

		$district = sanitize_text_field(
			wp_unslash( $_POST['district'] )
		);

		if ( '' === $district ) {
			$district = null;
		}

		$tablename = Congress_Table_Manager::get_table_name( 'representative' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $wpdb->insert(
			$tablename,
			array(
				'title'      => sanitize_text_field(
					wp_unslash( $_POST['title'] )
				),
				'state'      => sanitize_text_field(
					wp_unslash( $_POST['state'] )
				),
				'district'   => $district,
				'first_name' => sanitize_text_field(
					wp_unslash( $_POST['first_name'] )
				),
				'last_name'  => sanitize_text_field(
					wp_unslash( $_POST['last_name'] )
				),
				'level'      => sanitize_text_field(
					wp_unslash( $_POST['level'] ),
				),
			)
		);

		if ( false === $result ) {
			wp_send_json(
				array(
					'error' => 'DB error',
				),
				500
			);
		}

		if ( 0 === $result ) {
			wp_send_json(
				array(
					'error' => 'Malformed request.',
				),
				400
			);
		}

		wp_send_json(
			array(
				'rawID'       => $wpdb->insert_id,
				'editNonce'   => wp_create_nonce( 'edit-rep' ),
				'deleteNonce' => wp_create_nonce( 'delete-rep' ),
				'createNonce' => wp_create_nonce( 'create-staffer' ),
			)
		);
	}

	/**
	 * Handles AJAX requests to delate a representative from the table.
	 */
	public function delete_rep(): void {

		if ( ! current_user_can( 'congress_manage_representatives' ) ) {
			wp_send_json(
				array(
					'error' => 'Insufficient Permissions.',
				),
				403
			);
		}

		if ( ! check_ajax_referer( 'delete-rep', false, false ) ) {
			wp_send_json(
				array(
					'error' => 'Incorrect Nonce',
				),
				403
			);
		}

		if ( ! isset( $_POST['rep_id'] ) ) {
			wp_send_json(
				array(
					'error' => 'Missing parameters',
				),
				400
			);
		}

		$rep_id = sanitize_text_field(
			wp_unslash( $_POST['rep_id'] )
		);

		Congress_Table_Manager::delete_representative( $rep_id )->submit(
			error: function ( $query_res_value, $wpdb_error ) {
				if ( 0 === $query_res_value ) {
					wp_send_json(
						array(
							'error' => 'Could not find representative!',
						),
						400
					);
				} else {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
					error_log( $wpdb_error );
					wp_send_json(
						array(
							'error' => 'Failed to delete representative!',
						),
						500
					);
				}
			},
			success: function ( $results ) {
				wp_send_json( $results[0] );
			}
		);
	}

	/**
	 * Handles AJAX requests to update a representative in the table.
	 */
	public function update_rep(): void {

		if ( ! current_user_can( 'congress_manage_representatives' ) ) {
			wp_send_json(
				array(
					'error' => 'Insufficient Permissions.',
				),
				403
			);
		}

		if ( ! check_ajax_referer( 'edit-rep', false, false ) ) {
			wp_send_json(
				array(
					'error' => 'Incorrect Nonce',
				),
				403
			);
		}

		if (
			! isset( $_POST['rep_id'] ) ||
			! isset( $_POST['title'] ) ||
			! isset( $_POST['state'] ) ||
			! isset( $_POST['district'] ) ||
			! isset( $_POST['first_name'] ) ||
			! isset( $_POST['last_name'] ) ||
			! isset( $_POST['level'] )
		) {
			wp_send_json(
				array(
					'error' => 'Missing parameters',
				),
				400
			);
		}

		$rep_id = sanitize_text_field(
			wp_unslash( $_POST['rep_id'] )
		);

		$district = sanitize_text_field(
			wp_unslash( $_POST['district'] )
		);

		if ( '' === $district ) {
			$district = null;
		}

		global $wpdb;

		$tablename = Congress_Table_Manager::get_table_name( 'representative' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->update(
			$tablename,
			array(
				'title'      => sanitize_text_field(
					wp_unslash( $_POST['title'] )
				),
				'state'      => sanitize_text_field(
					wp_unslash( $_POST['state'] )
				),
				'district'   => $district,
				'first_name' => sanitize_text_field(
					wp_unslash( $_POST['first_name'] )
				),
				'last_name'  => sanitize_text_field(
					wp_unslash( $_POST['last_name'] )
				),
				'level'      => sanitize_text_field(
					wp_unslash( $_POST['level'] ),
				),
			),
			array(
				'id' => $rep_id,
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s' ),
			array( '%d' ),
		);

		if ( false === $result ) {
			wp_send_json(
				array(
					'error' => 'DB error',
				),
				500
			);
		}

		wp_send_json( $result );
	}

	/**
	 * An AJAX handler for syncing representatives.
	 *
	 * Accepts 'state' and 'level' fields.
	 *
	 * Sends a JSON response with the updated representatives.
	 */
	public function sync_reps(): void {

		if ( ! current_user_can( 'congress_manage_representatives' ) ) {
			wp_send_json(
				array(
					'error' => 'Insufficient Permissions.',
				),
				403
			);
		}

		if ( ! check_ajax_referer( 'sync-reps', false, false ) ) {
			wp_send_json(
				array(
					'error' => 'Incorrect Nonce',
				),
				403
			);
		}

		$state = null;
		$level = null;
		try {
			if ( isset( $_POST['state'] ) ) {
				$state = Congress_State::from_string(
					sanitize_text_field(
						wp_unslash( $_POST['state'] )
					)
				);
			}
			if ( isset( $_POST['level'] ) ) {
				$level = Congress_Level::from_string(
					sanitize_text_field(
						wp_unslash( $_POST['level'] )
					)
				);
			}
		} catch ( Error $e ) {
			wp_send_json(
				array(
					'error' => 'Invalid parameters!',
				),
				400
			);
		}

		$res = Congress_Rep_Sync::sync_reps( $state, $level );

		if ( 0 < count( $res['errors'] ) ) {
			$error = $res['errors'][0];
			match ( $error->get_error_code() ) {
				'API_NOT_IMPLEMENTED' => wp_send_json(
					array(
						'error' => $error->get_error_message(),
					),
					501
				),
				'MISSING_API_KEY' => wp_send_json(
					array(
						'error'   => $error->get_error_message(),
						'message' => $error->get_error_data(),
					),
					501
				),
				'API_FAILURE' => wp_send_json(
					array(
						'error' => $error->get_error_message(),
					),
					500
				),
				'DB_FAILURE' => wp_send_json(
					array(
						'error' => $error->get_error_message(),
					),
					500
				),
				default => wp_send_json(
					array(
						'error' => $error->get_error_message(),
					),
					500
				)
			};
		}

		global $wpdb;
		$res['reps_removed'] = array_map(
			function ( Congress_Rep_Interface $rep ) {
				return $rep->to_json();
			},
			$res['reps_removed']
		);

		$res['reps_added'] = array_map(
			function ( Congress_Rep_Interface $rep ) {
				$rep_json = $rep->to_json();

				$rep_json['createNonce'] = wp_create_nonce( 'create-staffer' );
				$rep_json['editNonce']   = wp_create_nonce( 'edit-rep' );
				$rep_json['deleteNonce'] = wp_create_nonce( 'delete-rep' );

				if ( isset( $rep_json['staffers'] ) ) {
					foreach ( $rep_json['staffers'] as &$staffer ) {
						$rep_id                 = $rep_json['id'];
						$staffer_id             = $staffer['id'];
						$staffer['editNonce']   = wp_create_nonce( 'edit-staffer' );
						$staffer['deleteNonce'] = wp_create_nonce( 'delete-staffer' );
					}
				}

				return $rep_json;
			},
			$res['reps_added']
		);

		wp_send_json( $res );
	}
}
