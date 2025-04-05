<?php
/**
 * A collection of AJAX handlers for staffers.
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
 * A collection of AJAX handlers for staffers.
 *
 * @since      1.0.0
 * @package    Congress
 * @subpackage Congress/includes
 * @author     Ryan Sauers <ryan.sauers@exploreveg.org>
 */
class Congress_Staffer_AJAX implements Congress_AJAX_Collection {

	/**
	 * Returns a list of ajax handlers for admin page.
	 *
	 * @return array<Congress_AJAX_Handler>
	 */
	public function get_admin_handlers(): array {
		return array(
			new Congress_AJAX_Handler(
				callee: $this,
				func_name: 'get_staffers',
				ajax_name: 'get_staffers'
			),
			new Congress_AJAX_Handler(
				callee: $this,
				func_name: 'insert_staffer',
				ajax_name: 'add_staffer'
			),
			new Congress_AJAX_Handler(
				callee: $this,
				func_name: 'delete_staffer',
				ajax_name: 'delete_staffer'
			),
			new Congress_AJAX_Handler(
				callee: $this,
				func_name: 'update_staffer',
				ajax_name: 'update_staffer'
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
	 * Returns a JSON response with the staffers in the database.
	 */
	public function get_staffers(): void {

		if ( ! current_user_can( 'congress_manage_representatives' ) ) {
			wp_send_json(
				array(
					'error' => 'Insufficient Permissions.',
				),
				403
			);
		}

		$rep_id = 'all';
		if ( isset( $_GET['rep_id'] ) ) {
			$rep_id = sanitize_text_field(
				wp_unslash( $_GET['rep_id'] )
			);
		}

		if ( ! check_ajax_referer( "get-staffers_$rep_id", false, false ) ) {
			wp_send_json(
				array(
					'error' => 'Incorrect Nonce',
				),
				403
			);
		}

		global $wpdb;

		$tablename = Congress_Table_Manager::get_table_name( 'staffer' );

		if ( 'all' !== $rep_id ) {
			$result = $wpdb->get_results( // phpcs:ignore
				$wpdb->prepare(
					"SELECT * FROM $tablename WHERE representative=%d", // phpcs:ignore
					array( $rep_id ),
				)
			);
		} else {
			$result = $wpdb->get_results( "SELECT * FROM $tablename" ); // phpcs:ignore
		}

		if ( false === $result ) {
			wp_send_json(
				array(
					'error' => 'DB error',
				),
				500
			);
		}

		wp_send_json(
			$result
		);
	}

	/**
	 * Handles AJAX requests to add staffers to the table.
	 * Sends a JSON response with the id and nonces.
	 */
	public function insert_staffer(): void {

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
			! isset( $_POST['first_name'] ) ||
			! isset( $_POST['last_name'] ) ||
			! isset( $_POST['email'] )
		) {
			wp_send_json(
				array(
					'error' => 'Missing parameters',
				),
				400
			);
		}

		$rep_id = sanitize_text_field(
			wp_unslash( $_POST['rep_id'] ),
		);

		if ( ! check_ajax_referer( "create-staffer_$rep_id", false, false ) ) {
			wp_send_json(
				array(
					'error' => 'Incorrect Nonce',
				),
				403
			);
		}

		global $wpdb;

		$tablename = Congress_Table_Manager::get_table_name( 'staffer' );
		// phpcs:ignore
		$result    = $wpdb->insert(
			$tablename,
			array(
				'title'          => sanitize_text_field(
					wp_unslash( $_POST['title'] ),
				),
				'representative' => $rep_id,
				'first_name'     => sanitize_text_field(
					wp_unslash( $_POST['first_name'] ),
				),
				'last_name'      => sanitize_text_field(
					wp_unslash( $_POST['last_name'] ),
				),
				'email'          => sanitize_email(
					wp_unslash( $_POST['email'] ),
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

		$staffer_id = $wpdb->insert_id;

		wp_send_json(
			array(
				'rawID'       => $staffer_id,
				'editNonce'   => wp_create_nonce( "edit-staffer_$rep_id-$staffer_id" ),
				'deleteNonce' => wp_create_nonce( "delete-staffer_$rep_id-$staffer_id" ),
			)
		);
	}

	/**
	 * Handles AJAX requests to delate a staffer from the table.
	 */
	public function delete_staffer(): void {

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
			! isset( $_POST['staffer_id'] )
		) {
			wp_send_json(
				array(
					'error' => 'Missing parameters',
				),
				400
			);
		}

		$rep_id     = sanitize_text_field(
			wp_unslash( $_POST['rep_id'] )
		);
		$staffer_id = sanitize_text_field(
			wp_unslash( $_POST['staffer_id'] )
		);

		if ( ! check_ajax_referer( "delete-staffer_$rep_id-$staffer_id", false, false ) ) {
			wp_send_json(
				array(
					'error' => 'Incorrect Nonce',
				),
				403
			);
		}

		global $wpdb;

		$tablename = Congress_Table_Manager::get_table_name( 'staffer' );
		// phpcs:ignore
		$result = $wpdb->delete(
			$tablename,
			array(
				'id'             => $staffer_id,
				'representative' => $rep_id,
			),
			array( '%d', '%d' ),
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
	 * Handles AJAX requests to update a staffer in the table.
	 */
	public function update_staffer(): void {

		if ( ! current_user_can( 'congress_manage_representatives' ) ) {
			wp_send_json(
				array(
					'error' => 'Insufficient Permissions.',
				),
				403
			);
		}

		if (
			! isset( $_POST['staffer_id'] ) ||
			! isset( $_POST['rep_id'] ) ||
			! isset( $_POST['title'] ) ||
			! isset( $_POST['first_name'] ) ||
			! isset( $_POST['last_name'] ) ||
			! isset( $_POST['email'] )
		) {
			wp_send_json(
				array(
					'error' => 'Missing parameters',
				),
				400
			);
		}

		$rep_id     = sanitize_text_field(
			wp_unslash( $_POST['rep_id'] )
		);
		$staffer_id = sanitize_text_field(
			wp_unslash( $_POST['staffer_id'] )
		);

		if ( ! check_ajax_referer( "edit-staffer_$rep_id-$staffer_id", false, false ) ) {
			wp_send_json(
				array(
					'error' => 'Incorrect Nonce',
				),
				403
			);
		}

		global $wpdb;

		$tablename = Congress_Table_Manager::get_table_name( 'staffer' );
		// phpcs:ignore
		$result    = $wpdb->update(
			$tablename,
			array(
				'title'      => sanitize_text_field(
					wp_unslash( $_POST['title'] ),
				),
				'first_name' => sanitize_text_field(
					wp_unslash( $_POST['first_name'] ),
				),
				'last_name'  => sanitize_text_field(
					wp_unslash( $_POST['last_name'] ),
				),
				'email'      => sanitize_text_field(
					wp_unslash( $_POST['email'] ),
				),
			),
			array(
				'id'             => $staffer_id,
				'representative' => $rep_id,
			),
			array( '%s', '%s', '%s', '%s' ),
			'%d',
		);

		if ( 0 === $result ) {
			wp_send_json(
				array(
					'error' => 'Malformed request.',
				),
				400
			);
		}

		if ( false === $result || 0 === $result ) {
			wp_send_json(
				array(
					'error' => 'DB error',
				),
				500
			);
		}

		wp_send_json( $result );
	}
}
