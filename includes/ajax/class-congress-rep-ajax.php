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
			new Congress_AJAX_Handler(
				callee: $this,
				func_name: 'sync_all_reps',
				ajax_name: 'sync_all_reps'
			),
		);
	}

	/**
	 * Returns a list of ajax handlers for public page.
	 *
	 * @return array<Congress_AJAX_Handler>
	 */
	public function get_public_handlers(): array {
		return array(
			new Congress_AJAX_Handler(
				callee: $this,
				func_name: 'get_reps',
				ajax_name: 'get_representatives'
			),
		);
	}

	/**
	 * Returns a JSON response with the representatives in the database.
	 */
	public function get_reps(): void {

		$state = 'all';
		if ( isset( $_GET['state'] ) ) {
			$state = sanitize_text_field( wp_unslash( $_GET['state'] ) );
		}

		if ( ! check_ajax_referer( 'get-reps', false, false ) ) {
			wp_send_json(
				array(
					'error' => 'Incorrect Nonce',
				),
				403
			);
		}

		global $wpdb;

		$tablename = Congress_Table_Manager::get_table_name( 'representative' );

		if ( 'all' === $state ) {
			$result = $wpdb->get_results( // phpcs:ignore
				$wpdb->prepare(
					"SELECT * FROM $tablename WHERE state=%s", // phpcs:ignore
					array(
						$state,
					),
				)
			);
		} else {

			$result = $wpdb->get_results( "SELECT * FROM $tablename" ); // phpcs:ignore
		}

		wp_send_json(
			$result
		);
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

		if ( ! check_ajax_referer( 'create-rep', false, false ) ) {
			wp_send_json(
				array(
					'error' => 'Incorrect Nonce',
				),
				403
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
		// phpcs:ignore
		$result    = $wpdb->insert(
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
				'editNonce'   => wp_create_nonce( 'edit-rep_' . $wpdb->insert_id ),
				'deleteNonce' => wp_create_nonce( 'delete-rep_' . $wpdb->insert_id ),
				'createNonce' => wp_create_nonce( 'create-staffer_' . $wpdb->insert_id ),
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

		if ( ! check_ajax_referer( "delete-rep_$rep_id", false, false ) ) {
			wp_send_json(
				array(
					'error' => 'Incorrect Nonce',
				),
				403
			);
		}

		global $wpdb;

		$tablename = Congress_Table_Manager::get_table_name( 'representative' );
		// phpcs:ignore
		$result = $wpdb->delete(
			$tablename,
			array(
				'id' => $rep_id,
			),
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

		if ( ! check_ajax_referer( "edit-rep_$rep_id", false, false ) ) {
			wp_send_json(
				array(
					'error' => 'Incorrect Nonce',
				),
				403
			);
		}

		$district = sanitize_text_field(
			wp_unslash( $_POST['district'] )
		);

		if ( '' === $district ) {
			$district = null;
		}

		global $wpdb;

		$tablename = Congress_Table_Manager::get_table_name( 'representative' );
		// phpcs:ignore
		$result    = $wpdb->update(
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

		if ( 0 === $result ) {
			wp_send_json(
				array(
					'error' => 'Malformed request.',
				),
				400
			);
		}

		wp_send_json( $result );
	}

	/**
	 * An AJAX handler for syncing all representatives.
	 *
	 * Sends a JSON response with the updated representatives.
	 */
	public function sync_all_reps(): void {

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

		$res = Congress_Rep_Sync::sync_all_reps();

		if ( is_wp_error( $res ) ) {
			match ( $res->get_error_code() ) {
				'API_FAILURE' => wp_send_json(
					array(
						'error' => $res->get_error_message(),
					),
					500
				),
				'DB_FAILURE' => wp_send_json(
					array(
						'error' => $res->get_error_message(),
					),
					500
				),
				default => wp_send_json(
					array(
						'error' => $res->get_error_message(),
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

				$rep_json['createNonce'] = wp_create_nonce( 'create-staffer_' . $rep_json['id'] );
				$rep_json['editNonce']   = wp_create_nonce( 'edit-rep_' . $rep_json['id'] );
				$rep_json['deleteNonce'] = wp_create_nonce( 'delete-rep_' . $rep_json['id'] );

				if ( isset( $rep_json['staffers'] ) ) {
					foreach ( $rep_json['staffers'] as &$staffer ) {
						$rep_id                 = $rep_json['id'];
						$staffer_id             = $staffer['id'];
						$staffer['editNonce']   = wp_create_nonce( "edit-staffer_$rep_id-$staffer_id" );
						$staffer['deleteNonce'] = wp_create_nonce( "delete-staffer_$rep_id-$staffer_id" );
					}
				}

				return $rep_json;
			},
			$res['reps_added']
		);

		wp_send_json( $res );
	}

	/**
	 * An AJAX handler for syncing representatives based on the state and level.
	 *
	 * Requires 'state' and 'level' fields.
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

		if (
			! isset( $_POST['state'] ) ||
			! isset( $_POST['level'] )
		) {
			wp_send_json(
				array(
					'error' => 'Invalid parameters!',
				),
				400
			);
		}

		try {
			$state = Congress_State::from_string(
				sanitize_text_field(
					wp_unslash( $_POST['state'] )
				)
			);
			$level = Congress_Level::from_string(
				sanitize_text_field(
					wp_unslash( $_POST['level'] )
				)
			);
		} catch ( Error $e ) {
			wp_send_json(
				array(
					'error' => 'Invalid parameters!',
				),
				400
			);
		}

		$res = Congress_Rep_Sync::sync_reps( $state, $level );

		if ( is_wp_error( $res ) ) {
			match ( $res->get_error_code() ) {
				'API_NOT_IMPLEMENTED' => wp_send_json(
					array(
						'error' => $res->get_error_message(),
					),
					501
				),
				'MISSING_API_KEY' => wp_send_json(
					array(
						'error'   => $res->get_error_message(),
						'message' => $res->get_error_data(),
					),
					501
				),
				'API_FAILURE' => wp_send_json(
					array(
						'error' => $res->get_error_message(),
					),
					500
				),
				'DB_FAILURE' => wp_send_json(
					array(
						'error' => $res->get_error_message(),
					),
					500
				),
				default => wp_send_json(
					array(
						'error' => $res->get_error_message(),
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

				$rep_json['createNonce'] = wp_create_nonce( 'create-staffer_' . $rep_json['id'] );
				$rep_json['editNonce']   = wp_create_nonce( 'edit-rep_' . $rep_json['id'] );
				$rep_json['deleteNonce'] = wp_create_nonce( 'delete-rep_' . $rep_json['id'] );

				if ( isset( $rep_json['staffers'] ) ) {
					foreach ( $rep_json['staffers'] as &$staffer ) {
						$rep_id                 = $rep_json['id'];
						$staffer_id             = $staffer['id'];
						$staffer['editNonce']   = wp_create_nonce( "edit-staffer_$rep_id-$staffer_id" );
						$staffer['deleteNonce'] = wp_create_nonce( "delete-staffer_$rep_id-$staffer_id" );
					}
				}

				return $rep_json;
			},
			$res['reps_added']
		);

		wp_send_json( $res );
	}
}
