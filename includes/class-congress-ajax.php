<?php
/**
 * A collection of AJAX handlers.
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
	'class-congress-table-manager.php';

/**
 * Imports Table Manager for getting table names;
 */
require_once plugin_dir_path( __FILE__ ) .
	'class-congress-ajax-handler.php';

/**
 * A collection of AJAX handlers.
 *
 * @since      1.0.0
 * @package    Congress
 * @subpackage Congress/includes
 * @author     Ryan Sauers <ryan.sauers@exploreveg.org>
 */
class Congress_AJAX {

	/**
	 * The singleton instance.
	 *
	 * @var Congress_AJAX $instance
	 */
	private static ?Congress_AJAX $instance = null;

	/**
	 * Retrieves the singleton instance.
	 *
	 * @return Congress_AJAX
	 */
	public static function get_instance(): Congress_AJAX {
		if ( null === self::$instance ) {
			self::$instance = new Congress_AJAX();
		}
		return self::$instance;
	}

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
		);
	}

	/**
	 * Returns a list of ajax handlers for public page.
	 *
	 * - ajax_name is the slug to refer to the ajax request.
	 * - func is the name of the handler in this file.
	 *
	 * @return array<'ajax_name'|'func',string>
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
				'district'   => sanitize_text_field(
					wp_unslash( $_POST['district'] )
				),
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
				'district'   => sanitize_text_field(
					wp_unslash( $_POST['district'] )
				),
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
	 * Returns a JSON response with the staffers in the database.
	 */
	public function get_staffers(): void {

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

	/**
	 * Handles AJAX requests to add campaigns to the table.
	 * Sends a JSON response with the campaign data and nonces.
	 */
	public function insert_campaign(): void {

		if (
			! isset( $_POST['name'] ) ||
			! isset( $_POST['level'] )
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

		$name  = sanitize_text_field(
			wp_unslash( $_POST['name'] ),
		);
		$level = sanitize_text_field(
			wp_unslash( $_POST['level'] ),
		);

		global $wpdb;

		$wpdb->query( 'START TRANSACTION' ); // phpcs:ignore

		$campaign_table = Congress_Table_Manager::get_table_name( 'campaign' );
		// phpcs:ignore
		$main_result = $wpdb->insert(
			$campaign_table,
			array(
				'name'  => $name,
				'level' => $level,
			)
		);

		if ( false === $main_result ) {
			$wpdb->query( 'ROLLBACK' ); // phpcs:ignore
			wp_send_json(
				array(
					'error' => $wpdb->last_error,
				),
				500
			);
		}

		if ( 0 === $main_result ) {
			$wpdb->query( 'ROLLBACK' ); // phpcs:ignore
			wp_send_json(
				array(
					'error' => 'Malformed request.',
				),
				400
			);
		}

		$campaign_id = $wpdb->insert_id;

		$active_campaign_table = Congress_Table_Manager::get_table_name( 'active_campaign' );

		$active_result = $wpdb->insert( // phpcs:ignore
			$active_campaign_table,
			array(
				'id' => $campaign_id,
			)
		);

		if ( false === $active_result ) {
			$wpdb->query( 'ROLLBACK' ); // phpcs:ignore
			wp_send_json(
				array(
					'error' => $wpdb->last_error,
				),
				500
			);
		}

		if ( 0 === $active_result ) {
			$wpdb->query( 'ROLLBACK' ); // phpcs:ignore
			wp_send_json(
				array(
					'error' => 'Malformed request.',
				),
				400
			);
		}

		$wpdb->query( 'COMMIT' ); // phpcs:ignore

		wp_send_json(
			array(
				'id'           => $campaign_id,
				'name'         => $name,
				'level'        => $level,
				'editNonce'    => wp_create_nonce( "update-campaign_$campaign_id" ),
				'archiveNonce' => wp_create_nonce( "archive-campaign_$campaign_id" ),
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
			! isset( $_POST['level'] )
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
		$level       = sanitize_text_field(
			wp_unslash( $_POST['level'] )
		);

		if ( ! check_ajax_referer( "update-campaign_$campaign_id", false, false ) ) {
			wp_send_json(
				array(
					'error' => 'Incorrect Nonce',
				),
				403
			);
		}

		global $wpdb;

		$tablename = Congress_Table_Manager::get_table_name( 'campaign' );
		// phpcs:ignore
		$result    = $wpdb->update(
			$tablename,
			array(
				'name'  => $name,
				'level' => $level,
			),
			array(
				'id' => $campaign_id,
			)
		);

		if ( false === $result ) {
			wp_send_json(
				array(
					'error' => $wpdb->last_error,
				),
				500
			);
		}

		if ( 0 === $result ) {
			wp_send_json(
				array(
					'error' => 'No Change',
				),
				400
			);
		}

		$campaign_id = $wpdb->insert_id;

		wp_send_json(
			array(
				'id'    => $campaign_id,
				'name'  => $name,
				'level' => $level,
			),
		);
	}

	/**
	 * Handles AJAX requests to archive a campaign in the table.
	 * Sends a JSON response with the archived date.
	 */
	public function archive_campaign(): void {

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

		$wpdb->query( 'START TRANSACTION' ); // phpcs:ignore

		// phpcs:ignore
		$result = $wpdb->query(
			$wpdb->prepare(
				"INSERT INTO $archived_campaign (id, email_count) " . // phpcs:ignore
				"SELECT $campaign.id, COUNT($email.campaign_id) FROM $campaign " . // phpcs:ignore
				"LEFT JOIN $email ON $email.campaign_id = $campaign.id " . // phpcs:ignore
				"WHERE $campaign.id = %d", // phpcs:ignore
				array(
					$campaign_id,
				),
			)
		);

		if ( false === $result ) {
			$wpdb->query( 'ROLLBACK' ); // phpcs:ignore
			wp_send_json(
				array(
					'error' => $wpdb->last_error,
				),
				500
			);
			return;
		}

		if ( 0 === $result ) {
			$wpdb->query( 'ROLLBACK' ); // phpcs:ignore
			wp_send_json(
				array(
					'error' => 'No Change',
				),
				400
			);
			return;
		}

		$result = $wpdb->delete( // phpcs:ignore
			$active_campaign,
			array(
				'id' => $campaign_id,
			),
			'%d',
		);

		if ( false === $result ) {
			$wpdb->query( 'ROLLBACK' ); // phpcs:ignore
			wp_send_json(
				array(
					'error' => $wpdb->last_error,
				),
				500
			);
			return;
		}

		if ( 0 === $result ) {
			$wpdb->query( 'ROLLBACK' ); // phpcs:ignore
			wp_send_json(
				array(
					'error' => 'No Change',
				),
				400
			);
			return;
		}

		$wpdb->query( 'COMMIT' ); // phpcs:ignore

		//phpcs:disable
		$results = $wpdb->get_results( // phpcs:ignore
			$wpdb->prepare(
				"SELECT * FROM $archived_campaign " .
				"INNER JOIN $campaign ON $archived_campaign.id = $campaign.id " .
				"WHERE $campaign.id = %d",
				array( $campaign_id )
			),
		);
		// phpcs:enable

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
				'level'        => $result->level,
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

		$result = $wpdb->delete( // phpcs:ignore
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
