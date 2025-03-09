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
				'id'                => $campaign_id,
				'name'              => $name,
				'level'             => $level,
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
