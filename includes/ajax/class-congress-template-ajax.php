<?php
/**
 * A collection of AJAX handlers for email templates.
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
 * A collection of AJAX handlers for email templates.
 *
 * @since      1.0.0
 * @package    Congress
 * @subpackage Congress/includes
 * @author     Ryan Sauers <ryan.sauers@exploreveg.org>
 */
class Congress_Template_AJAX implements Congress_AJAX_Collection {

	/**
	 * Returns a list of ajax handlers for admin page.
	 *
	 * @return array<Congress_AJAX_Handler>
	 */
	public function get_admin_handlers(): array {
		return array(
			new Congress_AJAX_Handler(
				callee: $this,
				func_name: 'load_email_templates',
				ajax_name: 'load_email_templates'
			),
			new Congress_AJAX_Handler(
				callee: $this,
				func_name: 'create_email_template',
				ajax_name: 'create_email_template'
			),
			new Congress_AJAX_Handler(
				callee: $this,
				func_name: 'delete_all_email_templates',
				ajax_name: 'delete_all_email_templates'
			),
			new Congress_AJAX_Handler(
				callee: $this,
				func_name: 'update_email_template',
				ajax_name: 'update_email_template'
			),
			new Congress_AJAX_Handler(
				callee: $this,
				func_name: 'delete_email_template',
				ajax_name: 'delete_email_template'
			),
			new Congress_AJAX_Handler(
				callee: $this,
				func_name: 'upload_csv_email_templates',
				ajax_name: 'upload_csv_email_templates'
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
	 * Handles a request for email templates.
	 * Sends a JSON response with an array of email template data.
	 */
	public function load_email_templates(): void {

		if ( ! current_user_can( 'congress_manage_campaigns' ) ) {
			wp_send_json(
				array(
					'error' => 'Insufficient Permissions.',
				),
				403
			);
		}

		if (
			! isset( $_POST['campaign_id'] )
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
			wp_unslash( $_POST['campaign_id'] )
		);

		if ( ! check_ajax_referer( "load-templates_$campaign_id", false, false ) ) {
			wp_send_json(
				array(
					'error' => 'Incorrect Nonce',
				),
				403
			);
			return;
		}

		global $wpdb;

		$email_template = Congress_Table_Manager::get_table_name( 'email_template' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM %i WHERE campaign_id = %d',
				array(
					$email_template,
					$campaign_id,
				)
			)
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

		$return_results = array();

		foreach ( $results as $result ) {
			$campaign_id = $result->campaign_id;
			$email_id    = $result->id;
			$edit_nonce  = wp_create_nonce( "edit-email-template_$campaign_id-$email_id" );
			array_push(
				$return_results,
				array(
					'campaignID' => $result->campaign_id,
					'id'         => $result->id,
					'subject'    => $result->subject,
					'favorable'  => $result->favorable,
					'template'   => $result->template,
					'editNonce'  => $edit_nonce,
				)
			);
		}

		wp_send_json(
			array(
				'createNonce'    => wp_create_nonce( "create-email_$campaign_id" ),
				'csvNonce'       => wp_create_nonce( "upload-csv-emails_$campaign_id" ),
				'deleteAllNonce' => wp_create_nonce( "delete-all-emails_$campaign_id" ),
				'templates'      => $return_results,
			)
		);
	}

	/**
	 * Handles a request for creating an email template.
	 * Sends a JSON response with the email template data.
	 */
	public function create_email_template(): void {

		if ( ! current_user_can( 'congress_manage_campaigns' ) ) {
			wp_send_json(
				array(
					'error' => 'Insufficient Permissions.',
				),
				403
			);
		}

		if (
			! isset( $_POST['campaign_id'] ) ||
			! isset( $_POST['subject'] )
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
			wp_unslash( $_POST['campaign_id'] )
		);
		$subject     = sanitize_text_field(
			wp_unslash( $_POST['subject'] )
		);
		$template    = "DEAR [[REP_TITLE]] [[REP_FIRST]] [[REP_LAST]],\n\nPlease support...\n\nSincerely,\n[[SENDER_FIRST]] [[SENDER_LAST]]\n[[ADDRESS]]";
		$favorable   = false;

		if ( ! check_ajax_referer( "create-email_$campaign_id", false, false ) ) {
			wp_send_json(
				array(
					'error' => 'Incorrect Nonce',
				),
				403
			);
			return;
		}

		global $wpdb;

		$email_template = Congress_Table_Manager::get_table_name( 'email_template' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $wpdb->insert(
			$email_template,
			array(
				'campaign_id' => $campaign_id,
				'subject'     => $subject,
				'template'    => $template,
				'favorable'   => $favorable,
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
				'id'         => $wpdb->insert_id,
				'campaignID' => $campaign_id,
				'subject'    => $subject,
				'template'   => $template,
				'favorable'  => $favorable,
				'editNonce'  => wp_create_nonce( "edit-email-template_$campaign_id-$wpdb->insert_id" ),
			)
		);
	}

	/**
	 * Handles a request for deleting email templates
	 * Sends a JSON success message.
	 */
	public function delete_all_email_templates(): void {

		if ( ! current_user_can( 'congress_manage_campaigns' ) ) {
			wp_send_json(
				array(
					'error' => 'Insufficient Permissions.',
				),
				403
			);
		}

		if (
			! isset( $_POST['campaign_id'] )
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
			wp_unslash( $_POST['campaign_id'] )
		);

		if ( ! check_ajax_referer( "delete-all-emails_$campaign_id", false, false ) ) {
			wp_send_json(
				array(
					'error' => 'Incorrect Nonce',
				),
				403
			);
			return;
		}

		global $wpdb;

		$email_template = Congress_Table_Manager::get_table_name( 'email_template' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->delete(
			$email_template,
			array(
				'campaign_id' => $campaign_id,
			),
			array(
				'%d',
			)
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

		wp_send_json(
			array(
				'success' => "Deleted $result templates.",
			)
		);
	}

	/**
	 * Handles a request for deleting an email template
	 * Sends a JSON success message.
	 */
	public function delete_email_template(): void {

		if ( ! current_user_can( 'congress_manage_campaigns' ) ) {
			wp_send_json(
				array(
					'error' => 'Insufficient Permissions.',
				),
				403
			);
		}

		if (
			! isset( $_POST['campaign_id'] ) ||
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

		$email_id    = sanitize_text_field(
			wp_unslash( $_POST['id'] )
		);
		$campaign_id = sanitize_text_field(
			wp_unslash( $_POST['campaign_id'] )
		);

		if ( ! check_ajax_referer( "edit-email-template_$campaign_id-$email_id", false, false ) ) {
			wp_send_json(
				array(
					'error' => 'Incorrect Nonce',
				),
				403
			);
			return;
		}

		global $wpdb;

		$email_template = Congress_Table_Manager::get_table_name( 'email_template' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->delete(
			$email_template,
			array(
				'campaign_id' => $campaign_id,
				'id'          => $email_id,
			),
			array( '%d', '%d' )
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
					'error' => 'Template not found.',
				),
				500
			);
			return;
		}

		wp_send_json(
			array(
				'success' => 'Deleted successfully.',
			)
		);
	}

	/**
	 * Handles a request to edit an email template.
	 *
	 * Sends a json response with the columns that have been changed and their new values.
	 */
	public function update_email_template(): void {

		if ( ! current_user_can( 'congress_manage_campaigns' ) ) {
			wp_send_json(
				array(
					'error' => 'Insufficient Permissions.',
				),
				403
			);
		}

		if (
			! isset( $_POST['id'] ) ||
			! isset( $_POST['campaign_id'] )
		) {
			wp_send_json(
				array(
					'error' => 'Missing parameters',
				),
				400
			);
			return;
		}

		$email_id    = sanitize_text_field(
			wp_unslash( $_POST['id'] )
		);
		$campaign_id = sanitize_text_field(
			wp_unslash( $_POST['campaign_id'] )
		);

		if ( ! check_ajax_referer( "edit-email-template_$campaign_id-$email_id", false, false ) ) {
			wp_send_json(
				array(
					'error' => 'Incorrect Nonce',
				),
				403
			);
			return;
		}

		global $wpdb;

		$email_template = Congress_Table_Manager::get_table_name( 'email_template' );

		$update_array      = array();
		$update_type_array = array();

		if ( isset( $_POST['subject'] ) ) {
			$update_array['subject'] = sanitize_text_field(
				wp_unslash( $_POST['subject'] )
			);
			array_push( $update_type_array, '%s' );
		}
		if ( isset( $_POST['favorable'] ) ) {
			$update_array['favorable'] = sanitize_text_field(
				wp_unslash( $_POST['favorable'] )
			);
			array_push( $update_type_array, '%s' );
		}
		if ( isset( $_POST['template'] ) ) {
			$update_array['template'] = sanitize_textarea_field(
				wp_unslash( $_POST['template'] )
			);
			array_push( $update_type_array, '%s' );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->update(
			$email_template,
			$update_array,
			array(
				'id'          => $email_id,
				'campaign_id' => $campaign_id,
			),
			$update_type_array,
			array( '%d', '%d' ),
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

		$return_result               = $update_array;
		$return_result['id']         = $email_id;
		$return_result['campaignID'] = $campaign_id;

		wp_send_json( $return_result );
	}

	/**
	 * Handles a request for creating email templates from a csv file.
	 * Sends a JSON response with the data for the email templates.
	 */
	public function upload_csv_email_templates(): void {

		if ( ! current_user_can( 'congress_manage_campaigns' ) ) {
			wp_send_json(
				array(
					'error' => 'Insufficient Permissions.',
				),
				403
			);
		}

		/**
		 *  Messages associated with the upload error code
		 */
		$err_messages = array(
			UPLOAD_ERR_OK         => 'File uploaded successfully',
			UPLOAD_ERR_INI_SIZE   => 'File is too big to upload',
			UPLOAD_ERR_FORM_SIZE  => 'File is too big to upload',
			UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded',
			UPLOAD_ERR_NO_FILE    => 'No file was uploaded',
			UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder on the server',
			UPLOAD_ERR_CANT_WRITE => 'File is failed to save to disk.',
			UPLOAD_ERR_EXTENSION  => 'File is not allowed to upload to this server',
		);

		if (
			! isset( $_POST['campaign_id'] ) ||
			! isset( $_FILES['csv'] )
		) {
			wp_send_json(
				array(
					'error' => 'Missing parameters',
				),
				400
			);
			return;
		}

		$campaign_id       = sanitize_text_field(
			wp_unslash( $_POST['campaign_id'] )
		);
		$default_favorable = false;

		if ( ! check_ajax_referer( "upload-csv-emails_$campaign_id", false, false ) ) {
			wp_send_json(
				array(
					'error' => 'Incorrect Nonce',
				),
				403
			);
			return;
		}

		global $wpdb;

		if ( isset( $_FILES['csv']['error'] ) && 0 !== $_FILES['csv']['error'] ) {
			wp_send_json(
				array(
					'error' => 'Failed to process file!',
				),
				400
			);
			return;

		}
		if ( isset( $_FILES['csv']['type'] ) && 'text/csv' !== $_FILES['csv']['type'] ) {
			wp_send_json(
				array(
					'error' => 'Incorrect file type!',
				),
				400
			);
			return;
		}
		if ( isset( $_FILES['csv']['size'] ) && 1024 * 2024 < $_FILES['csv']['size'] ) {
			wp_send_json(
				array(
					'error'   => 'Upload Failed',
					'message' => 'Maximum file size is 1MB',
				),
				413
			);
			return;
		}

		if ( ! isset( $_FILES['csv']['tmp_name'] ) ) {
			wp_send_json(
				array(
					'error' => 'Upload Failed',
				)
			);
		}

		global $wp_filesystem;
		WP_Filesystem();

		$filename = sanitize_file_name( $_FILES['csv']['tmp_name'] );
		if ( ! $wp_filesystem->exists( $filename ) ) {
			wp_send_json(
				array(
					'error' => 'Upload Failed',
				)
			);
		}
		$file_contents = $wp_filesystem->get_contents( $filename );
		$file_contents = explode( "\n", $file_contents );

		$csv_data        = array();
		$col_ind         = null;
		$max_col_ind     = 0;
		$csv_parse_error = '';
		$row_count       = 0;
		foreach ( $file_contents as $row ) {
			$row       = explode( ',', $row );
			$col_count = count( $row );

			// parse headers.
			if ( null === $col_ind ) {

				$col_ind = array();

				// store header indexes.
				for ( $c = 0; $c < $col_count; $c++ ) {
					if ( 0 === strcasecmp( 'subject', $row[ $c ] ) ) {
						$col_ind['subject'] = $c;
					}
					if ( 0 === strcasecmp( 'body', $row[ $c ] ) ) {
						$col_ind['body'] = $c;
					}
					if ( 0 === strcasecmp( 'favorable', $row[ $c ] ) ) {
						$col_ind['favorable'] = $c;
					}
				}

				// handle required indexes.
				if ( ! isset( $col_ind['subject'] ) ) {
					$csv_parse_error = 'Missing "subject" header.';
					break;
				}
				if ( ! isset( $col_ind['body'] ) ) {
					$csv_parse_error = 'Missing "body" header.';
					break;
				}

				continue;
			}

			++$row_count;

			// validate required fields.
			if ( $col_ind['subject'] >= $col_count || '' === $row[ $col_ind['subject'] ] ) {
				$csv_parse_error = "Missing 'subject' field in row $row_count";
				break;
			}
			if ( $col_ind['body'] >= $col_count || '' === $row[ $col_ind['body'] ] ) {
				$csv_parse_error = "Missing 'body' field in row $row_count";
				break;
			}

			$template_data = array(
				'campaign_id' => $campaign_id,
				'subject'     => sanitize_text_field( $row[ $col_ind['subject'] ] ),
				'template'    => sanitize_textarea_field( $row[ $col_ind['body'] ] ),
				'favorable'   => false,
			);

			// validate not required fields.
			if (
				isset( $col_ind['favorable'] ) &&
				$col_ind['favorable'] < $col_count
			) {
				if ( 0 === strcasecmp( 'true', $row[ $col_ind['favorable'] ] ) ) {
					$template_data['favorable'] = true;
				} elseif ( 0 === strcasecmp( 'false', $row[ $col_ind['favorable'] ] ) ) {
					$template_data['favorable'] = false;
				}
			}

			array_push( $csv_data, $template_data );
		}

		if ( '' !== $csv_parse_error ) {
			wp_send_json(
				array(
					'error'   => 'Upload Failed!',
					'message' => $csv_parse_error,
				),
				400
			);
			return;
		}

		if ( 0 === count( $csv_data ) ) {
			wp_send_json(
				array(
					'error' => 'No change.',
				),
				400
			);
			return;
		}

		$email_template = Congress_Table_Manager::get_table_name( 'email_template' );

		$sql_count      = 0;
		$errors         = array();
		$template_count = count( $csv_data );
		for ( $i = 0; $i < $template_count; $i++ ) {
			$csv_datum = $csv_data[ $i ];

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$result = $wpdb->insert(
				$email_template,
				$csv_datum
			);
			if ( false === $result ) {
				array_push( $errors, $sql_count . ': ' . $wpdb->last_error );
				continue;
			}
			$sql_count           += $result;
			$template_id          = $wpdb->insert_id;
			$csv_data[ $i ]['id'] = $template_id;
			unset( $csv_data[ $i ]['campaign_id'] );
			$csv_data[ $i ]['campaignID'] = $campaign_id;
			$csv_data[ $i ]['editNonce']  = wp_create_nonce( "edit-email-template_$campaign_id-$template_id" );
		}

		if ( 0 < count( $errors ) ) {
			wp_send_json(
				array(
					'error'  => 'Failed to add templates.',
					'errors' => $errors,
				),
				400
			);
			return;
		}

		wp_send_json( $csv_data );
	}
}
